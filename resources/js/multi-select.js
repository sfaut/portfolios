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
 * @param {object} config Configuration prepared by MultiSelect.
 * @param {string[]} config.value Checked values.
 * @param {{value: string, label: string}[]} config.options Options, in order.
 * @param {string} config.placeholder Text of the trigger without checked option.
 * @param {string} config.noun Plural noun after the count of checked options.
 */
export default function multiSelect({ value, options, placeholder, noun }) {
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
            return this.value.length === 0;
        },

        /**
         * Label of the selection, "" without checked option.
         *
         * @returns {string}
         */
        get display() {
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
            this.value = [];
        },

        /**
         * Closes the popover and gives the focus back to the trigger.
         */
        close() {
            this.$refs.panel.hidePopover();
            this.$refs.trigger.focus();
        },

        /**
         * Opening: empties the search and focuses its field, else the first option.
         *
         * @param {ToggleEvent} event
         */
        onToggle(event) {
            if (event.newState !== "open") {
                return;
            }

            this.query = "";
            this.$nextTick(() => (this.$refs.search ?? this.boxes()[0])?.focus());
        },

        /**
         * Checkboxes of the options shown.
         *
         * @returns {HTMLInputElement[]}
         */
        boxes() {
            return [...this.$refs.panel.querySelectorAll('input[type="checkbox"]')].filter((box) => box.offsetParent !== null);
        },

        /**
         * Moves the focus between the options shown: arrows, Home and End; the Down arrow leaves the
         * search field for the first option.
         *
         * @param {KeyboardEvent} event
         */
        onKeydown(event) {
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
