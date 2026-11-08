/**
 * Month picker (components/month-picker.blade.php, App\View\Components\MonthPicker), inspired by
 * <input type="month">: the panel shows one year and its twelve months.
 *
 * Three modes: "single" (one month, "2026-11" or ""), "multiple" (sorted list of months) and
 * "range" ({start, end}, "" when missing). Months are written "2026-11", so that comparing their
 * texts compares their dates. min and max bound the months; months, when given, restricts them
 * further. The trigger label is built as MonthPicker::describe() does on the server.
 *
 * Keyboard, on a month: arrows move to the previous / next choosable month (Left / Right) or three
 * months away (Up / Down), Page Up / Page Down change the year, Home / End go to the first / last
 * choosable month of the year.
 *
 * @param {object} config Configuration prepared by MonthPicker.
 * @param {"single"|"multiple"|"range"} config.mode Selection mode.
 * @param {string|string[]|{start: string, end: string}} config.value Initial selection.
 * @param {?string[]} config.months Choosable months, or null for all those within the bounds.
 * @param {string} config.min First month, "" without bound.
 * @param {string} config.max Last month, "" without bound.
 * @param {string} config.placeholder Text of the trigger without selection.
 */
export default function monthPicker({ mode, value, months, min, max, placeholder }) {
    const lang = document.documentElement.lang || "fr";
    const long_format = new Intl.DateTimeFormat(lang, { month: "long", year: "numeric" });
    const short_format = new Intl.DateTimeFormat(lang, { month: "short" });
    const choosable_months = months === null ? null : new Set(months);
    const now = new Date();

    /**
     * Month key of a year and a month number (1 to 12): "2026-11".
     *
     * @param {number} year
     * @param {number} month
     * @returns {string}
     */
    const keyOf = (year, month) => `${year}-${String(month).padStart(2, "0")}`;

    /**
     * Year and month number of a month key.
     *
     * @param {string} key
     * @returns {[number, number]}
     */
    const parse = (key) => [Number(key.slice(0, 4)), Number(key.slice(5, 7))];

    /**
     * Month key a number of months away.
     *
     * @param {string} key
     * @param {number} offset Months to add, negative to go back.
     * @returns {string}
     */
    const shift = (key, offset) => {
        const [year, month] = parse(key);
        const index = year * 12 + month - 1 + offset;

        return keyOf(Math.floor(index / 12), (index % 12) + 1);
    };

    /**
     * Full name of a month, "novembre 2026".
     *
     * @param {string} key
     * @returns {string}
     */
    const nameOf = (key) => {
        const [year, month] = parse(key);

        return long_format.format(new Date(year, month - 1, 1));
    };

    /**
     * Label of a month, "Novembre 2026".
     *
     * @param {string} key
     * @returns {string}
     */
    const labelOf = (key) => {
        const name = nameOf(key);

        return name.charAt(0).toLocaleUpperCase(lang) + name.slice(1);
    };

    /**
     * Whether the month is within the bounds.
     *
     * @param {string} key
     */
    const isWithinBounds = (key) => (min === "" || key >= min) && (max === "" || key <= max);

    /**
     * Whether the month can be chosen.
     *
     * @param {string} key
     */
    const isChoosable = (key) => isWithinBounds(key) && (choosable_months === null || choosable_months.has(key));

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
            if (this.mode === "multiple") {
                return this.value.length > 1
                    ? `${this.value.length} mois`
                    : this.value.length === 1 ? labelOf(this.value[0]) : "";
            }

            if (this.mode === "range") {
                const { start, end } = this.value;

                if (start === "") {
                    return "";
                }

                if (end === "") {
                    return `Depuis ${nameOf(start)}`;
                }

                return end === start ? labelOf(start) : `${labelOf(start)} – ${nameOf(end)}`;
            }

            return this.value === "" ? "" : labelOf(this.value);
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
                    name: nameOf(key),
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

            if (cell.inside) {
                classes.push("bg-indigo-100 text-indigo-900 dark:bg-indigo-900/60 dark:text-indigo-100");
            }

            if (cell.active) {
                classes.push("hover:bg-indigo-50 dark:hover:bg-indigo-950/60");
            } else {
                classes.push("cursor-default text-gray-300 dark:text-gray-600");
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
         * Nearest choosable month from a month, moving by a step, within the bounds; null if none.
         *
         * @param {string} key Starting month, excluded.
         * @param {number} step Months to move by at each try, negative to go back.
         * @returns {?string}
         */
        seek(key, step) {
            for (let candidate = shift(key, step), tries = 0; tries < 1200; candidate = shift(candidate, step), tries++) {
                if (!isWithinBounds(candidate)) {
                    return null;
                }

                if (isChoosable(candidate)) {
                    return candidate;
                }
            }

            return null;
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
                    target = this.seek(key, -1);
                    break;
                case "ArrowRight":
                    target = this.seek(key, 1);
                    break;
                case "ArrowUp":
                    target = this.seek(key, -3);
                    break;
                case "ArrowDown":
                    target = this.seek(key, 3);
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
