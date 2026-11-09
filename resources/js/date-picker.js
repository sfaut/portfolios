import {
    createRestriction,
    daysOf,
    describe,
    keyOf,
    monthOf,
    monthTitle,
    nameOf,
    parse,
    seek,
    shift,
    shiftByMonths,
    shiftMonth,
    weekdayOf,
} from "./date-picker-rules.js";

/**
 * Date picker (components/date-picker.blade.php, App\View\Components\DatePicker), inspired by
 * <input type="date">: a panel shows one month and its days, in weeks from Monday. It follows the
 * month picker (month-picker.js), which does the same for months.
 *
 * Three modes: "single" (one day, "2026-11-09" or ""), "multiple" (sorted list of days) and
 * "range" ({start, end}, "" when missing, equal for one day). The restriction (bounds, white list,
 * black list) comes from App\Support\DateRestriction; its rule and the trigger label live in
 * date-picker-rules.js. In range mode, only the ends of a range must be choosable: forbidden days
 * inside it are shown in the range, greyed.
 *
 * Panels: one in single and multiple modes ("main"); two in range mode, side by side, "start" then
 * "end", each with its own month. A day chosen in "start" becomes the start being chosen and sends
 * the focus to "end", whose days before that start are greyed; a day chosen in "end" sets the range
 * and closes. Closing before the end drops the start being chosen. In range mode, presets (ready
 * ranges prepared by the server) set the range at once.
 *
 * Validation: this component validates nothing. Its copy of the restriction only greys the
 * forbidden days and skips them with the keyboard; the server checks the value it receives against
 * its own restriction (App\Rules\DateSelection), whatever the browser sends.
 *
 * Keyboard, on a day of a panel: arrows move to the previous / next choosable day (Left / Right) or
 * a week away (Up / Down), Home / End go to the first / last choosable day of the week, Page Up /
 * Page Down change the month, and the year with Shift; Tab goes from one panel to the other.
 *
 * @param {object} config Configuration prepared by DatePicker.
 * @param {"single"|"multiple"|"range"} config.mode Selection mode.
 * @param {string|string[]|{start: string, end: string}} config.value Initial selection.
 * @param {import("./date-picker-rules.js").RestrictionConfig} config.restriction Copy of the restriction, for the display.
 * @param {string} config.placeholder Text of the trigger without selection.
 * @param {{label: string, start: string, end: string}[]} config.presets Ready ranges, in range mode.
 */
export default function datePicker({ mode, value, restriction, placeholder, presets }) {
    const lang = document.documentElement.lang || "fr";
    const rules = createRestriction(restriction);
    const { min, max, isChoosable } = rules;
    const now = new Date();
    const today = keyOf(now.getFullYear(), now.getMonth() + 1, now.getDate());

    // January 1, 2024 is a Monday: the seven days from it name the columns.
    const weekday_format = new Intl.DateTimeFormat(lang, { weekday: "narrow" });
    const weekdays = Array.from({ length: 7 }, (_, index) => weekday_format.format(new Date(2024, 0, index + 1)));

    /**
     * First day of the selection, "" without selection.
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
     * Month of a day, else the current one, kept within the bounds.
     *
     * @param {string} key Day, or "".
     * @returns {string}
     */
    const viewOf = (key) => {
        let month = monthOf(key === "" ? today : key);

        if (max !== "" && month > monthOf(max)) {
            month = monthOf(max);
        }

        if (min !== "" && month < monthOf(min)) {
            month = monthOf(min);
        }

        return month;
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
        presets,
        panels,
        weekdays,
        today,

        /**
         * Month shown by each panel, "2026-11".
         *
         * @type {Record<string, string>}
         */
        months: Object.fromEntries(panels.map((panel) => [panel.id, viewOf(firstOf(value))])),

        /**
         * Start of a range being chosen, "" otherwise.
         */
        pending: "",

        /**
         * Day of the "end" panel under the pointer or the focus, which previews the range being
         * chosen; "" otherwise.
         */
        hovered: "",

        /**
         * Day that last had the focus.
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
         * Title of the month shown by a panel: "Novembre 2026".
         *
         * @param {string} panel
         * @returns {string}
         */
        title(panel) {
            return monthTitle(this.months[panel], lang);
        },

        /**
         * Whether a panel can show an earlier month.
         *
         * @param {string} panel
         */
        hasPreviousMonth(panel) {
            return min === "" || this.months[panel] > monthOf(min);
        },

        /**
         * Whether a panel can show a later month.
         *
         * @param {string} panel
         */
        hasNextMonth(panel) {
            return max === "" || this.months[panel] < monthOf(max);
        },

        /**
         * First day the "end" panel accepts: the start being chosen, else the selected start ("" without).
         *
         * @returns {string}
         */
        get endFloor() {
            return this.mode === "range" ? this.pending || this.value.start : "";
        },

        /**
         * Whether a day can be chosen in a panel: choosable, and not before the start in "end".
         *
         * @param {string} panel
         * @param {string} key
         */
        isChoosableIn(panel, key) {
            return isChoosable(key) && (panel !== "end" || key >= this.endFloor);
        },

        /**
         * Range shown, sorted: the selected one, or the one being chosen, up to the day hovered in
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
         * Whether the day is selected (an end of the range in range mode).
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
         * Empty cells before the first day of the month shown by a panel, whose week starts on Monday.
         *
         * @param {string} panel
         * @returns {number}
         */
        blanks(panel) {
            return weekdayOf(`${this.months[panel]}-01`);
        },

        /**
         * The days of the month shown by a panel.
         *
         * @param {string} panel
         * @returns {{key: string, day: number, name: string, active: boolean, selected: boolean, inside: boolean, current: boolean}[]}
         */
        cells(panel) {
            return daysOf(this.months[panel]).map((key) => ({
                key,
                day: parse(key)[2],
                name: nameOf(key, lang),
                active: this.isChoosableIn(panel, key),
                selected: this.isSelected(key),
                inside: this.range !== null && key > this.range[0] && key < this.range[1],
                current: key === this.today,
            }));
        },

        /**
         * Day of a panel that the Tab key reaches: the last focused, else the end of the range that
         * the panel chooses, else the first selected, else today, else the first choosable; null if
         * no day of the month shown can be chosen.
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
                ?? cells.find((cell) => cell.current)?.key
                ?? cells[0]?.key
                ?? null
            );
        },

        /**
         * Classes of a day, after its state.
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
                // Forbidden day inside a range: part of the range, but greyed.
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
         * Notes the day under the pointer or the focus; only the "end" panel previews a range.
         *
         * @param {string} panel
         * @param {string} key
         */
        hover(panel, key) {
            this.hovered = panel === "end" ? key : "";
        },

        /**
         * Chooses a day of a panel, after the mode: replaces the selection and closes (single),
         * adds or removes the day (multiple), sets the start being chosen and goes to the "end"
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
                    ? this.value.filter((day) => day !== key)
                    : [...this.value, key].sort();

                return;
            }

            if (this.mode === "range") {
                if (panel === "start") {
                    this.pending = key;
                    this.focused = "";
                    this.focusDay("end", this.range[1]);

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
         * Chooses a ready range and closes.
         *
         * @param {{start: string, end: string}} preset
         */
        choosePreset(preset) {
            this.pending = "";
            this.value = { start: preset.start, end: preset.end };
            this.close();
        },

        /**
         * Whether a ready range is the selected one.
         *
         * @param {{start: string, end: string}} preset
         */
        isPreset(preset) {
            return this.value.start === preset.start && this.value.end === preset.end;
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
         * Shows the previous month in a panel.
         *
         * @param {string} panel
         */
        previousMonth(panel) {
            if (this.hasPreviousMonth(panel)) {
                this.months[panel] = shiftMonth(this.months[panel], -1);
            }
        },

        /**
         * Shows the next month in a panel.
         *
         * @param {string} panel
         */
        nextMonth(panel) {
            if (this.hasNextMonth(panel)) {
                this.months[panel] = shiftMonth(this.months[panel], 1);
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
         * Opening: each panel shows the month of its end of the selection, and the first panel
         * focuses its day. Closing: drops a range whose end was not chosen.
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
                this.months.start = viewOf(this.value.start);
                this.months.end = viewOf(this.value.end || this.value.start);
            } else {
                this.months.main = viewOf(firstOf(this.value));
            }

            this.focused = "";
            this.$nextTick(() => this.focusDay(this.panels[0].id, this.focusKey(this.panels[0].id)));
        },

        /**
         * Gives the focus to a day of a panel, showing its month; to the next month button of the
         * panel without day.
         *
         * @param {string} panel
         * @param {?string} key
         */
        focusDay(panel, key) {
            const root = () => this.$refs.panel.querySelector(`[data-panel="${panel}"]`);

            if (key === null) {
                this.$nextTick(() => root()?.querySelector("[data-next-month]")?.focus());

                return;
            }

            this.months[panel] = monthOf(key);
            this.$nextTick(() => root()?.querySelector(`[data-day="${key}"]`)?.focus());
        },

        /**
         * Keyboard navigation between the days of a panel (see the module description).
         *
         * @param {KeyboardEvent} event
         */
        onKeydown(event) {
            const key = event.target.dataset?.day;
            const panel = event.target.closest?.("[data-panel]")?.dataset.panel;

            if (key === undefined || panel === undefined) {
                return;
            }

            const panel_rules = { ...rules, isChoosable: (day) => this.isChoosableIn(panel, day) };
            const monday = shift(key, -weekdayOf(key));
            const days_of_week = Array.from({ length: 7 }, (_, index) => shift(monday, index))
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
                    target = seek(panel_rules, key, -7);
                    break;
                case "ArrowDown":
                    target = seek(panel_rules, key, 7);
                    break;
                case "Home":
                    target = days_of_week[0];
                    break;
                case "End":
                    target = days_of_week[days_of_week.length - 1];
                    break;
                case "PageUp":
                case "PageDown": {
                    const offset = (event.key === "PageUp" ? -1 : 1) * (event.shiftKey ? 12 : 1);
                    const same_day = shiftByMonths(key, offset);
                    const month = monthOf(same_day);

                    event.preventDefault();

                    if ((min !== "" && month < monthOf(min)) || (max !== "" && month > monthOf(max))) {
                        return;
                    }

                    // The days of the month shown are replaced: without choosable day in the new
                    // month, the focus goes to the next month button rather than being lost.
                    this.months[panel] = month;
                    this.focusDay(panel, panel_rules.isChoosable(same_day) ? same_day : this.focusKey(panel));

                    return;
                }
                default:
                    return;
            }

            event.preventDefault();

            if (target) {
                this.focusDay(panel, target);
            }
        },
    };
}
