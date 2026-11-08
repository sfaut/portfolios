import { createRestriction, describe, keyOf, nameOf, parse, seek } from "./month-picker-rules.js";

/**
 * Month picker (components/month-picker.blade.php, App\View\Components\MonthPicker), inspired by
 * <input type="month">: the panel shows one year and its twelve months.
 *
 * Three modes: "single" (one month, "2026-11" or ""), "multiple" (sorted list of months) and
 * "range" ({start, end}, "" when missing). The restriction (bounds, white list, black list) comes
 * from App\Support\MonthRestriction; its rule and the trigger label live in month-picker-rules.js.
 * In range mode, only the ends of a range must be choosable: forbidden months inside it are shown
 * in the range, greyed.
 *
 * Validation: this component validates nothing. Its copy of the restriction only greys the
 * forbidden months and skips them with the keyboard; the server checks the value it receives
 * against its own restriction (App\Rules\MonthSelection), whatever the browser sends.
 *
 * Keyboard, on a month: arrows move to the previous / next choosable month (Left / Right) or three
 * months away (Up / Down), Page Up / Page Down change the year, Home / End go to the first / last
 * choosable month of the year.
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
     * Year to show when the panel opens: that of the selection, else the current one, kept within
     * the bounds.
     *
     * @param {string|string[]|{start: string, end: string}} selection
     * @returns {number}
     */
    const yearOf = (selection) => {
        const first = firstOf(selection);
        let year = first === "" ? now.getFullYear() : parse(first)[0];

        if (max !== "") {
            year = Math.min(year, parse(max)[0]);
        }

        if (min !== "") {
            year = Math.max(year, parse(min)[0]);
        }

        return year;
    };

    return {
        mode,
        value,
        placeholder,
        year: yearOf(value),
        today: keyOf(now.getFullYear(), now.getMonth() + 1),

        /**
         * Start of a range being chosen, "" otherwise.
         */
        pending: "",

        /**
         * Month under the pointer or the focus, which previews the range being chosen.
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
         * Whether an earlier year can be shown.
         */
        get hasPreviousYear() {
            return min === "" || this.year > parse(min)[0];
        },

        /**
         * Whether a later year can be shown.
         */
        get hasNextYear() {
            return max === "" || this.year < parse(max)[0];
        },

        /**
         * Range shown, sorted: the selected one, or the one being chosen up to the hovered month.
         *
         * @returns {?[string, string]}
         */
        get range() {
            if (this.mode !== "range") {
                return null;
            }

            if (this.pending !== "") {
                const other = this.hovered !== "" && isChoosable(this.hovered) ? this.hovered : this.pending;

                return [this.pending, other].sort();
            }

            const { start, end } = this.value;

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
         * The twelve months of the year shown.
         *
         * @returns {{key: string, short: string, name: string, active: boolean, selected: boolean, inside: boolean, current: boolean}[]}
         */
        get cells() {
            return Array.from({ length: 12 }, (_, index) => {
                const key = keyOf(this.year, index + 1);

                return {
                    key,
                    short: short_format.format(new Date(this.year, index, 1)),
                    name: nameOf(key, lang),
                    active: isChoosable(key),
                    selected: this.isSelected(key),
                    inside: this.range !== null && key > this.range[0] && key < this.range[1],
                    current: key === this.today,
                };
            });
        },

        /**
         * Month of the year shown that the Tab key reaches: the last focused, else the first
         * selected, else the first choosable; null if no month of the year can be chosen.
         *
         * @returns {?string}
         */
        get focusKey() {
            const cells = this.cells.filter((cell) => cell.active);

            return (
                cells.find((cell) => cell.key === this.focused)?.key
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
         * Chooses a month, after the mode: replaces the selection and closes (single), adds or
         * removes the month (multiple), sets the start then the end of the range and closes (range).
         *
         * @param {string} key
         */
        choose(key) {
            if (!isChoosable(key)) {
                return;
            }

            if (this.mode === "multiple") {
                this.value = this.value.includes(key)
                    ? this.value.filter((month) => month !== key)
                    : [...this.value, key].sort();

                return;
            }

            if (this.mode === "range") {
                if (this.pending === "") {
                    this.pending = key;

                    return;
                }

                const [start, end] = [this.pending, key].sort();

                this.pending = "";
                this.value = { start, end };
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
         * Shows the previous year.
         */
        previousYear() {
            if (this.hasPreviousYear) {
                this.year--;
            }
        },

        /**
         * Shows the next year.
         */
        nextYear() {
            if (this.hasNextYear) {
                this.year++;
            }
        },

        /**
         * Closes the panel and gives the focus back to the trigger.
         */
        close() {
            this.$refs.panel.hidePopover();
            this.$refs.trigger.focus();
        },

        /**
         * Opening: shows the year of the selection and focuses its month. Closing: drops a range
         * whose end was not chosen.
         *
         * @param {ToggleEvent} event
         */
        onToggle(event) {
            if (event.newState !== "open") {
                this.pending = "";
                this.hovered = "";

                return;
            }

            this.year = yearOf(this.value);
            this.focused = "";
            this.$nextTick(() => this.focusMonth(this.focusKey));
        },

        /**
         * Gives the focus to a month, showing its year; to the next year button without month.
         *
         * @param {?string} key
         */
        focusMonth(key) {
            if (key === null) {
                this.$refs.nextYear.focus();

                return;
            }

            this.year = parse(key)[0];
            this.$nextTick(() => this.$refs.panel.querySelector(`[data-month="${key}"]`)?.focus());
        },

        /**
         * Keyboard navigation between the months (see the module description).
         *
         * @param {KeyboardEvent} event
         */
        onKeydown(event) {
            const key = event.target.dataset?.month;

            if (key === undefined) {
                return;
            }

            const [year, month] = parse(key);
            const months_of_year = Array.from({ length: 12 }, (_, index) => keyOf(year, index + 1)).filter(isChoosable);
            let target;

            switch (event.key) {
                case "ArrowLeft":
                    target = seek(rules, key, -1);
                    break;
                case "ArrowRight":
                    target = seek(rules, key, 1);
                    break;
                case "ArrowUp":
                    target = seek(rules, key, -3);
                    break;
                case "ArrowDown":
                    target = seek(rules, key, 3);
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

                    if (event.key === "PageUp" ? !this.hasPreviousYear : !this.hasNextYear) {
                        target = null;
                        break;
                    }

                    // The months of the year shown are replaced: without choosable month in the
                    // new year, the focus goes to the next year button rather than being lost.
                    this.year = other_year;
                    event.preventDefault();
                    this.focusMonth(isChoosable(same_month) ? same_month : this.focusKey);

                    return;
                }
                default:
                    return;
            }

            event.preventDefault();

            if (target) {
                this.focusMonth(target);
            }
        },
    };
}
