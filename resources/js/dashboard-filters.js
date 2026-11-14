/**
 * Filter bar of the dashboard (portfolio/dashboard.blade.php): reloads the page when a filter
 * changes, by sending the form as is (GET).
 *
 * A new period applies at once. The accounts are checked one by one in the popover of their
 * <x-multi-select>: the page reloads when the popover closes, if the selection changed, or at once
 * when the selection changes with no popover open (the button that clears it).
 *
 * @param {object} config
 * @param {{start: string, end: string}|null} config.period
 *     Chosen period, null when the chosen accounts have no operation (the bar then has no period).
 * @param {string[]} config.accounts Chosen accounts, empty for all of them.
 */
export default function dashboardFilters({ period, accounts }) {
    /**
     * Selection of accounts as a comparable text, whatever the order of the checks.
     *
     * @param {string[]} ids
     * @returns {string}
     */
    const key = (ids) => JSON.stringify([...ids].sort());

    const sent = key(accounts);

    return {
        period,
        accounts,

        init() {
            this.$watch("period", () => this.submit());
            this.$watch("accounts", () => {
                if (this.$root.querySelector(":popover-open") === null) {
                    this.submitIfChanged();
                }
            });
        },

        /**
         * Toggle of a popover of the bar, caught on its way down to the popover (the event does
         * not bubble): a closing sends the accounts chosen meanwhile.
         *
         * @param {ToggleEvent} event
         */
        onToggle(event) {
            if (event.newState === "closed") {
                this.submitIfChanged();
            }
        },

        /**
         * Sends the form if the accounts differ from those of the page.
         */
        submitIfChanged() {
            if (key(this.accounts) !== sent) {
                this.submit();
            }
        },

        /**
         * Sends the form, once the inputs are up to date.
         */
        submit() {
            this.$nextTick(() => this.$root.requestSubmit());
        },
    };
}
