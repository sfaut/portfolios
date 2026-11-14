/**
 * Analytic breakdown editor, shared by the form of an operation (operation/partials/form.blade.php)
 * and the default breakdown of an account (account/partials/form.blade.php): lines that each give
 * an analytic axis and a percentage, which total 100 %.
 *
 * The lines are posted as <name>[i][analytic_axis_id] and <name>[i][percentage], in their order.
 * With the amount option, each line also shows the part of the amount it stands for, read from the
 * "amount" field of the form, outside the component. With the sortable option, the lines are
 * reordered by their handle (x-sort, @alpinejs/sort), or with the arrow keys on it.
 * The total is computed in whole hundredths, as the server does, which has the last word.
 *
 * @param {object} form Data prepared by the controller (OperationController::analyticForm(),
 *     AccountController::analyticForm()).
 * @param {{id: number, name: string, archived: boolean, icon: string}[]} form.axes Selectable axes,
 *     each with its colored icon, an SVG rendered by the server and shown before the select.
 * @param {{analytic_axis_id: string, percentage: string}[]} form.lines Initial lines.
 * @param {Object<string, string[]>} errors Server errors of the form, those of the lines keyed
 *     "<name>.<i>.<field>".
 * @param {object} [options]
 * @param {string} [options.name] Name of the field of the lines.
 * @param {boolean} [options.amount] Whether each line shows its part of the amount.
 */
export default function analyticLines({ axes, lines }, errors, { name = "analytic_lines", amount = true } = {}) {
    let next_key = 0;

    /**
     * Copy of a line, with the key that x-for tracks it by.
     *
     * @param {{analytic_axis_id: ?string, percentage: ?string}} line
     */
    const keyed = (line) => ({
        key: next_key++,
        analytic_axis_id: String(line.analytic_axis_id ?? ""),
        percentage: String(line.percentage ?? ""),
    });

    /**
     * Whole number of hundredths in a decimal written with a dot ("12.5" gives 1250), or null if
     * the text is not a number.
     *
     * @param {string} text
     * @returns {?number}
     */
    const hundredths = (text) => {
        const value = Number.parseFloat(text);

        return Number.isFinite(value) ? Math.round(value * 100) : null;
    };

    return {
        axes,
        lines: lines.map(keyed),
        errors,
        name,
        amount: "",

        /**
         * Follows the amount field of the form, whose value may come back from a failed validation,
         * when the lines show their part of it.
         */
        init() {
            const field = amount ? this.$root.closest("form")?.elements.namedItem("amount") : null;

            if (!field) {
                return;
            }

            this.amount = field.value;
            field.addEventListener("input", () => {
                this.amount = field.value;
            });
        },

        /**
         * Total of the percentages, in hundredths (10000 for 100 %).
         */
        get total() {
            return this.lines.reduce((sum, line) => sum + (hundredths(line.percentage) ?? 0), 0);
        },

        /**
         * Whether the lines total exactly 100 %.
         */
        get complete() {
            return this.total === 10000;
        },

        /**
         * Whether a line can still be added: an axis remains free.
         */
        get canAdd() {
            return this.lines.length < this.axes.length;
        },

        /**
         * Colored icon of the axis of a line, empty without axis chosen.
         *
         * @param {string} axis_id
         * @returns {string} SVG rendered by the server.
         */
        iconOf(axis_id) {
            return this.axes.find((axis) => String(axis.id) === axis_id)?.icon ?? "";
        },

        /**
         * Whether the axis is already chosen by another line than the given one.
         *
         * @param {number} axis_id
         * @param {number} index Index of the line whose select is rendered.
         */
        isTaken(axis_id, index) {
            return this.lines.some((line, other) => other !== index && line.analytic_axis_id === String(axis_id));
        },

        /**
         * Adds a line on the first free axis, prefilled with what remains up to 100 %.
         */
        add() {
            if (!this.canAdd) {
                return;
            }

            const free_axis = this.axes.find((axis) => !this.isTaken(axis.id, -1));
            const remaining = 10000 - this.total;

            this.lines.push(keyed({
                analytic_axis_id: free_axis?.id ?? "",
                percentage: remaining > 0 ? String(remaining / 100) : "",
            }));
        },

        /**
         * Enter in a percentage field: adds a line, then selects the percentage of the new line,
         * ready to be typed over. The form is never sent from there; without a free axis, nothing
         * happens.
         */
        addFromKeyboard() {
            if (!this.canAdd) {
                return;
            }

            this.add();

            this.$nextTick(() => {
                const fields = this.$root.querySelectorAll('input[name$="[percentage]"]');
                const field = fields[fields.length - 1];

                field?.focus();
                field?.select();
            });
        },

        /**
         * Removes a line. The server errors are cleared, as the indexes they refer to shift.
         *
         * @param {number} index
         */
        remove(index) {
            this.lines.splice(index, 1);
            this.errors = {};
        },

        /**
         * Name of a field of a line, posted in the order of the lines.
         *
         * @param {number} index
         * @param {string} field analytic_axis_id or percentage.
         */
        fieldName(index, field) {
            return `${this.name}[${index}][${field}]`;
        },

        /**
         * Handler of x-sort: puts the dragged line at its new position. The plugin has already moved
         * its element, where x-for, keyed, then finds it. The server errors are cleared, as for a
         * removal.
         *
         * @param {number} key Key of the dragged line.
         * @param {number} position New index of the line.
         */
        move(key, position) {
            const from = this.lines.findIndex((line) => line.key === key);

            if (from === -1 || from === position) {
                return;
            }

            const [line] = this.lines.splice(from, 1);

            this.lines.splice(position, 0, line);
            this.errors = {};
        },

        /**
         * Arrow keys on the handle of a line: moves it up or down by one place, the focus staying
         * on its handle.
         *
         * @param {number} index
         * @param {number} delta -1 (up) or 1 (down).
         */
        moveBy(index, delta) {
            const position = index + delta;

            if (position < 0 || position >= this.lines.length) {
                return;
            }

            const key = this.lines[index].key;

            this.move(key, position);
            this.$nextTick(() => this.$root.querySelector(`[data-line-key="${key}"] [data-line-handle]`)?.focus());
        },

        /**
         * Server errors of a line.
         *
         * @param {number} index
         * @returns {string[]}
         */
        lineErrors(index) {
            return [
                ...(this.errors[`${this.name}.${index}`] ?? []),
                ...(this.errors[`${this.name}.${index}.analytic_axis_id`] ?? []),
                ...(this.errors[`${this.name}.${index}.percentage`] ?? []),
            ];
        },

        /**
         * Part of the amount that a line stands for, in cents, rounded half away from zero; null
         * while the amount or the percentage is not a number, or the percentage is not positive.
         *
         * @param {{percentage: string}} line
         * @returns {?number}
         */
        lineAmount(line) {
            const cents = hundredths(this.amount);
            const percentage = hundredths(line.percentage);

            if (cents === null || percentage === null || percentage <= 0) {
                return null;
            }

            return Math.sign(cents) * Math.round((Math.abs(cents) * percentage) / 10000);
        },

        /**
         * Amount in cents written as Number::amount() does without currency: French, two
         * decimals, typographic minus sign (U+2212) before a negative amount, no sign for zero.
         * An empty string for null.
         *
         * @param {?number} cents
         * @returns {string}
         */
        formatAmount(cents) {
            if (cents === null) {
                return "";
            }

            const digits = (Math.abs(cents) / 100).toLocaleString("fr-FR", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });

            return cents < 0 ? `−${digits}` : digits;
        },

        /**
         * Hundredths of percent written in French, without the percent sign: "33,5".
         *
         * @param {number} hundredths
         */
        format(hundredths) {
            return (hundredths / 100).toLocaleString("fr-FR", { maximumFractionDigits: 2 });
        },
    };
}
