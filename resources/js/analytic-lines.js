/**
 * Analytic breakdown of an operation (operation/partials/form.blade.php): lines that each
 * give an analytic axis and a percentage of the amount, which total 100 %.
 *
 * The lines are posted as analytic_lines[i][analytic_axis_id] and analytic_lines[i][percentage].
 * Each line also shows the part of the amount it stands for, read from the "amount" field of the
 * form, outside the component.
 * The total is computed in whole hundredths, as OperationRequest does, which has the last word.
 *
 * @param {object} form Data prepared by OperationController::analyticForm().
 * @param {{id: number, name: string, archived: boolean}[]} form.axes Selectable axes.
 * @param {{analytic_axis_id: string, percentage: string}[]} form.lines Initial lines.
 * @param {Object<string, string[]>} errors Server errors of the form, those of the lines keyed
 *     "analytic_lines.<i>.<field>".
 */
export default function analyticLines({ axes, lines }, errors) {
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
        amount: "",

        /**
         * Follows the amount field of the form, whose value may come back from a failed validation.
         */
        init() {
            const field = this.$root.closest("form")?.elements.namedItem("amount");

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
         * Removes a line. The server errors are cleared, as the indexes they refer to shift.
         *
         * @param {number} index
         */
        remove(index) {
            this.lines.splice(index, 1);
            this.errors = {};
        },

        /**
         * Server errors of a line.
         *
         * @param {number} index
         * @returns {string[]}
         */
        lineErrors(index) {
            return [
                ...(this.errors[`analytic_lines.${index}.analytic_axis_id`] ?? []),
                ...(this.errors[`analytic_lines.${index}.percentage`] ?? []),
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
