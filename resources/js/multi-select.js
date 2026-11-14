/**
 * Multiple selection (components/multi-select.blade.php, App\View\Components\MultiSelect): a
 * trigger that sums up the selection, and a popover of native checkboxes, which the form sends by
 * itself.
 *
 * This component keeps the trigger label up to date ("" without checked option, the label of the
 * only one, else their count and a noun: "3 comptes"), filters the list after the search field,
 * whatever the case and the accents, and moves the focus between the options shown with the arrows,
 * Home and End. Space checks an option and Escape closes the popover, natively.
 *
 * In single choice (multiple false), the options are native radios and the value is a string, ""
 * without choice. The arrows move the choice natively, without closing; a mouse click or Enter on a
 * radio closes the popover.
 *
 * @param {object} config Configuration prepared by MultiSelect.
 * @param {string[]|string} config.value Checked values, or the chosen one in single choice.
 * @param {{value: string, label: string}[]} config.options Options, in order.
 * @param {string} config.placeholder Text of the trigger without checked option.
 * @param {string} config.noun Plural noun after the count of checked options.
 * @param {boolean} config.multiple Whether several options can be checked.
 */
export default function multiSelect({ value, options, placeholder, noun, multiple = true }) {
    /**
     * Text without case nor accents, to compare a label with the search.
     *
     * @param {string} text
     * @returns {string}
     */
    const fold = (text) => text.normalize("NFD").replace(/\p{Diacritic}/gu, "").toLowerCase();

    return {
        value,
        placeholder,

        /**
         * Text of the search field.
         */
        query: "",

        /**
         * Whether no option is checked.
         */
        get empty() {
            return multiple ? this.value.length === 0 : this.value === "";
        },

        /**
         * Label of the selection, "" without checked option.
         *
         * @returns {string}
         */
        get display() {
            if (!multiple) {
                return options.find((option) => option.value === this.value)?.label ?? "";
            }

            if (this.value.length > 1) {
                return `${this.value.length} ${noun}`;
            }

            return options.find((option) => option.value === this.value[0])?.label ?? "";
        },

        /**
         * Whether the search keeps an option, by its label.
         *
         * @param {string} label
         */
        matches(label) {
            return fold(label).includes(fold(this.query.trim()));
        },

        /**
         * Whether the search keeps no option.
         */
        get noMatch() {
            return options.every((option) => !this.matches(option.label));
        },

        /**
         * Unchecks every option.
         */
        clear() {
            this.value = multiple ? [] : "";
        },

        /**
         * Closes the popover and gives the focus back to the trigger.
         */
        close() {
            this.$refs.panel.hidePopover();
            this.$refs.trigger.focus();
        },

        /**
         * Click on a radio in single choice: a mouse click closes the popover once the choice
         * made; the click that the arrows fire on the radio they check (detail 0) leaves it open.
         *
         * @param {MouseEvent} event
         */
        onPick(event) {
            if (event.detail > 0) {
                this.close();
            }
        },

        /**
         * Opening: empties the search and focuses its field, else the chosen radio, else the first
         * option.
         *
         * @param {ToggleEvent} event
         */
        onToggle(event) {
            if (event.newState !== "open") {
                return;
            }

            this.query = "";
            this.$nextTick(() => (this.$refs.search ?? this.boxes().find((box) => !multiple && box.checked) ?? this.boxes()[0])?.focus());
        },

        /**
         * Checkboxes, or radios, of the options shown.
         *
         * @returns {HTMLInputElement[]}
         */
        boxes() {
            return [...this.$refs.panel.querySelectorAll('input[type="checkbox"], input[type="radio"]')].filter((box) => box.offsetParent !== null);
        },

        /**
         * Moves the focus between the options shown: arrows, Home and End; the Down arrow leaves the
         * search field for the first option. Radios keep their native arrows, which move the choice.
         *
         * @param {KeyboardEvent} event
         */
        onKeydown(event) {
            if (!multiple && event.target.type === "radio") {
                return;
            }

            const boxes = this.boxes();
            const index = boxes.indexOf(event.target);
            let target;

            if (event.target === this.$refs.search) {
                target = event.key === "ArrowDown" ? boxes[0] : undefined;
            } else if (index !== -1) {
                target = {
                    ArrowDown: boxes[index + 1],
                    ArrowUp: boxes[index - 1] ?? this.$refs.search,
                    Home: boxes[0],
                    End: boxes[boxes.length - 1],
                }[event.key];

                if (!["ArrowDown", "ArrowUp", "Home", "End"].includes(event.key)) {
                    return;
                }
            } else {
                return;
            }

            if (target) {
                event.preventDefault();
                target.focus();
            }
        },
    };
}
