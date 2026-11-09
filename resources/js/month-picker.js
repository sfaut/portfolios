import { createRestriction, describe, keyOf, nameOf, parse, seek } from "./month-picker-rules.js";

/**
 * Month picker (components/month-picker.blade.php, App\View\Components\MonthPicker), inspired by
 * <input type="month">: a panel shows one year and its twelve months.
 *
 * Three modes: "single" (one month, "2026-11" or ""), "multiple" (sorted list of months) and
 * "range" ({start, end}, "" when missing). The restriction (bounds, white list, black list) comes
 * from App\Support\MonthRestriction; its rule and the trigger label live in month-picker-rules.js.
 * In range mode, only the ends of a range must be choosable: forbidden months inside it are shown
 * in the range, greyed.
 *
 * Panels: one in single and multiple modes ("main"); two in range mode, side by side, "start" then
 * "end", each with its own year. A month chosen in "start" becomes the start being chosen and sends
 * the focus to "end", whose months before that start are greyed; a month chosen in "end" sets the
 * range and closes. Closing before the end drops the start being chosen.
 *
 * Validation: this component validates nothing. Its copy of the restriction only greys the
 * forbidden months and skips them with the keyboard; the server checks the value it receives
 * against its own restriction (App\Rules\MonthSelection), whatever the browser sends.
 *
 * Keyboard, on a month of a panel: arrows move to the previous / next choosable month (Left /
 * Right) or three months away (Up / Down), Page Up / Page Down change the year, Home / End go to the
 * first / last choosable month of the year; Tab goes from one panel to the other.
 *
 * @param {object} config Configuration prepared by MonthPicker.
 * @param {"single"|"multiple"|"range"} config.mode Selection mode.
 * @param {string|string[]|{start: string, end: string}} config.value Initial selection.
 * @param {import("./month-picker-rules.js").RestrictionConfig} config.restriction Copy of the restriction, for the display.
 * @param {string} config.placeholder Text of the trigger without selection.
 */
export default function monthPicker({ mode, value, restriction, placeholder }) {
    const lang = document.documentElement.lang || "fr";
    const short_format = new Intl.DateTimeFormat(lang, { month: "short" });
    const rules = createRestriction(restriction);
    const { min, max, isChoosable } = rules;
    const now = new Date();

    /**
     * First month of the selection, "" without selection.
     *
     * @param {string|string[]|{start: string, end: string}} selection
     * @returns {string}
     */
    const firstOf = (selection) => {
        if (mode === "multiple") {
            return selection[0] ?? "";
        }

        return mode === "range" ? selection.start : selection;
    };

    /**
     * Year of a month, else the current one, kept within the bounds.
     *
     * @param {string} key Month, or "".
     * @returns {number}
     */
    const yearOf = (key) => {
        let year = key === "" ? now.getFullYear() : parse(key)[0];

        if (max !== "") {
            year = Math.min(year, parse(max)[0]);
        }

        if (min !== "") {
            year = Math.max(year, parse(min)[0]);
        }

        return year;
    };

    /**
     * Panels of the mode: identifier and visible title ("" without title).
     *
     * @type {{id: "main"|"start"|"end", title: string}[]}
     */
    const panels = mode === "range"
        ? [{ id: "start", title: "Début" }, { id: "end", title: "Fin" }]
        : [{ id: "main", title: "" }];

    return {
        mode,
        value,
        placeholder,
        panels,
        today: keyOf(now.getFullYear(), now.getMonth() + 1),

        /**
         * Year shown by each panel.
         *
         * @type {Record<string, number>}
         */
        years: Object.fromEntries(panels.map((panel) => [panel.id, yearOf(firstOf(value))])),

        /**
         * Start of a range being chosen, "" otherwise.
         */
        pending: "",

        /**
         * Month of the "end" panel under the pointer or the focus, which previews the range being
         * chosen; "" otherwise.
         */
        hovered: "",

        /**
         * Month that last had the focus.
         */
        focused: "",

        /**
         * Whether nothing is selected.
         */
        get empty() {
            return firstOf(this.value) === "";
        },

        /**
         * Label of the selection, "" without selection.
         *
         * @returns {string}
         */
        get display() {
            return describe(this.mode, this.value, lang);
        },

        /**
         * Whether a panel can show an earlier year.
         *
         * @param {string} panel
         */
        hasPreviousYear(panel) {
            return min === "" || this.years[panel] > parse(min)[0];
        },

        /**
         * Whether a panel can show a later year.
         *
         * @param {string} panel
         */
        hasNextYear(panel) {
            return max === "" || this.years[panel] < parse(max)[0];
        },

        /**
         * First month the "end" panel accepts: the start being chosen, else the selected start ("" without).
         *
         * @returns {string}
         */
        get endFloor() {
            return this.mode === "range" ? this.pending || this.value.start : "";
        },

        /**
         * Whether a month can be chosen in a panel: choosable, and not before the start in "end".
         *
         * @param {string} panel
         * @param {string} key
         */
        isChoosableIn(panel, key) {
            return isChoosable(key) && (panel !== "end" || key >= this.endFloor);
        },

        /**
         * Range shown, sorted: the selected one, or the one being chosen, up to the month hovered in
         * the "end" panel, else up to the selected end if it still follows the new start.
         *
         * @returns {?[string, string]}
         */
        get range() {
            if (this.mode !== "range") {
                return null;
            }

            const { start, end } = this.value;

            if (this.pending !== "") {
                const candidates = [this.hovered, end].filter((key) => key !== "" && key >= this.pending && isChoosable(key));

                return [this.pending, candidates[0] ?? this.pending];
            }

            return start === "" ? null : [start, end === "" ? start : end];
        },

        /**
         * Whether the month is selected (an end of the range in range mode).
         *
         * @param {string} key
         */
        isSelected(key) {
            if (this.mode === "multiple") {
                return this.value.includes(key);
            }

            if (this.mode === "range") {
                return this.range !== null && (key === this.range[0] || key === this.range[1]);
            }

            return this.value === key;
        },

        /**
         * The twelve months of the year shown by a panel.
         *
         * @param {string} panel
         * @returns {{key: string, short: string, name: string, active: boolean, selected: boolean, inside: boolean, current: boolean}[]}
         */
        cells(panel) {
            const year = this.years[panel];

            return Array.from({ length: 12 }, (_, index) => {
                const key = keyOf(year, index + 1);

                return {
                    key,
                    short: short_format.format(new Date(year, index, 1)),
                    name: nameOf(key, lang),
                    active: this.isChoosableIn(panel, key),
                    selected: this.isSelected(key),
                    inside: this.range !== null && key > this.range[0] && key < this.range[1],
                    current: key === this.today,
                };
            });
        },

        /**
         * Month of a panel that the Tab key reaches: the last focused, else the end of the range
         * that the panel chooses, else the first selected, else the first choosable; null if no month
         * of the year shown can be chosen.
         *
         * @param {string} panel
         * @returns {?string}
         */
        focusKey(panel) {
            const cells = this.cells(panel).filter((cell) => cell.active);
            const own_end = { start: this.range?.[0], end: this.range?.[1] }[panel];

            return (
                cells.find((cell) => cell.key === this.focused)?.key
                ?? cells.find((cell) => cell.key === own_end)?.key
                ?? cells.find((cell) => cell.selected)?.key
                ?? cells[0]?.key
                ?? null
            );
        },

        /**
         * Classes of a month, after its state.
         *
         * @param {{active: boolean, selected: boolean, inside: boolean, current: boolean}} cell
         * @returns {string}
         */
        cellClass(cell) {
            if (cell.selected) {
                return "bg-indigo-700 font-semibold text-white dark:bg-indigo-500";
            }

            const classes = [];

            if (cell.inside && cell.active) {
                classes.push("bg-indigo-100 text-indigo-900 dark:bg-indigo-900/60 dark:text-indigo-100");
            } else if (cell.inside) {
                // Forbidden month inside a range: part of the range, but greyed.
                classes.push("cursor-default bg-indigo-50 text-indigo-300 dark:bg-indigo-950/60 dark:text-indigo-700");
            } else if (!cell.active) {
                classes.push("cursor-default text-gray-300 dark:text-gray-600");
            }

            if (cell.active && !cell.inside) {
                classes.push("hover:bg-indigo-50 dark:hover:bg-indigo-950/60");
            }

            if (cell.current) {
                classes.push("ring-1 ring-inset ring-indigo-400");
            }

            return classes.join(" ");
        },

        /**
         * Notes the month under the pointer or the focus; only the "end" panel previews a range.
         *
         * @param {string} panel
         * @param {string} key
         */
        hover(panel, key) {
            this.hovered = panel === "end" ? key : "";
        },

        /**
         * Chooses a month of a panel, after the mode: replaces the selection and closes (single),
         * adds or removes the month (multiple), sets the start being chosen and goes to the "end"
         * panel, or sets the range and closes (range).
         *
         * @param {string} panel
         * @param {string} key
         */
        choose(panel, key) {
            if (!this.isChoosableIn(panel, key)) {
                return;
            }

            if (this.mode === "multiple") {
                this.value = this.value.includes(key)
                    ? this.value.filter((month) => month !== key)
                    : [...this.value, key].sort();

                return;
            }

            if (this.mode === "range") {
                if (panel === "start") {
                    this.pending = key;
                    this.focused = "";
                    this.focusMonth("end", this.range[1]);

                    return;
                }

                const start = this.endFloor || key;

                this.pending = "";
                this.value = { start, end: key };
                this.close();

                return;
            }

            this.value = key;
            this.close();
        },

        /**
         * Empties the selection.
         */
        clear() {
            this.pending = "";

            if (this.mode === "multiple") {
                this.value = [];
            } else if (this.mode === "range") {
                this.value = { start: "", end: "" };
            } else {
                this.value = "";
            }
        },

        /**
         * Shows the previous year in a panel.
         *
         * @param {string} panel
         */
        previousYear(panel) {
            if (this.hasPreviousYear(panel)) {
                this.years[panel]--;
            }
        },

        /**
         * Shows the next year in a panel.
         *
         * @param {string} panel
         */
        nextYear(panel) {
            if (this.hasNextYear(panel)) {
                this.years[panel]++;
            }
        },

        /**
         * Closes the panels and gives the focus back to the trigger.
         */
        close() {
            this.$refs.panel.hidePopover();
            this.$refs.trigger.focus();
        },

        /**
         * Opening: each panel shows the year of its end of the selection, and the first panel
         * focuses its month. Closing: drops a range whose end was not chosen.
         *
         * @param {ToggleEvent} event
         */
        onToggle(event) {
            if (event.newState !== "open") {
                this.pending = "";
                this.hovered = "";

                return;
            }

            if (this.mode === "range") {
                this.years.start = yearOf(this.value.start);
                this.years.end = yearOf(this.value.end || this.value.start);
            } else {
                this.years.main = yearOf(firstOf(this.value));
            }

            this.focused = "";
            this.$nextTick(() => this.focusMonth(this.panels[0].id, this.focusKey(this.panels[0].id)));
        },

        /**
         * Gives the focus to a month of a panel, showing its year; to the next year button of the
         * panel without month.
         *
         * @param {string} panel
         * @param {?string} key
         */
        focusMonth(panel, key) {
            const root = () => this.$refs.panel.querySelector(`[data-panel="${panel}"]`);

            if (key === null) {
                this.$nextTick(() => root()?.querySelector("[data-next-year]")?.focus());

                return;
            }

            this.years[panel] = parse(key)[0];
            this.$nextTick(() => root()?.querySelector(`[data-month="${key}"]`)?.focus());
        },

        /**
         * Keyboard navigation between the months of a panel (see the module description).
         *
         * @param {KeyboardEvent} event
         */
        onKeydown(event) {
            const key = event.target.dataset?.month;
            const panel = event.target.closest?.("[data-panel]")?.dataset.panel;

            if (key === undefined || panel === undefined) {
                return;
            }

            const panel_rules = { ...rules, isChoosable: (month) => this.isChoosableIn(panel, month) };
            const [year, month] = parse(key);
            const months_of_year = Array.from({ length: 12 }, (_, index) => keyOf(year, index + 1))
                .filter(panel_rules.isChoosable);
            let target;

            switch (event.key) {
                case "ArrowLeft":
                    target = seek(panel_rules, key, -1);
                    break;
                case "ArrowRight":
                    target = seek(panel_rules, key, 1);
                    break;
                case "ArrowUp":
                    target = seek(panel_rules, key, -3);
                    break;
                case "ArrowDown":
                    target = seek(panel_rules, key, 3);
                    break;
                case "Home":
                    target = months_of_year[0];
                    break;
                case "End":
                    target = months_of_year[months_of_year.length - 1];
                    break;
                case "PageUp":
                case "PageDown": {
                    const other_year = year + (event.key === "PageUp" ? -1 : 1);
                    const same_month = keyOf(other_year, month);

                    if (event.key === "PageUp" ? !this.hasPreviousYear(panel) : !this.hasNextYear(panel)) {
                        target = null;
                        break;
                    }

                    // The months of the year shown are replaced: without choosable month in the
                    // new year, the focus goes to the next year button rather than being lost.
                    this.years[panel] = other_year;
                    event.preventDefault();
                    this.focusMonth(panel, panel_rules.isChoosable(same_month) ? same_month : this.focusKey(panel));

                    return;
                }
                default:
                    return;
            }

            event.preventDefault();

            if (target) {
                this.focusMonth(panel, target);
            }
        },
    };
}
