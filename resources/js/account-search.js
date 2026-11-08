/**
 * Filters of the accounts of a portfolio (portfolio/show.blade.php): search by name, and month
 * in which the account has at least one operation.
 *
 * Typing shows the spinner at once and, after a short pause, fetches the page with the filters
 * from the server; choosing a month (x-month-picker, bound to "month") fetches it at once. The server answers ajax requests with
 * the "accounts" fragment alone. The fragment replaces the results ($refs.results), and its summary ([data-summary]) goes to the status next
 * to the filters ($refs.summary). The filters are kept in the URL, so that a reload shows them
 * again.
 *
 * @param {string} search Search given by the server, empty without search.
 * @param {string} month Month given by the server ("2026-11"), empty without month filter.
 */
export default function accountSearch(search, month) {
    /**
     * Controller of the pending request, aborted by a newer search.
     *
     * @type {?AbortController}
     */
    let controller = null;

    return {
        search,
        month,
        loading: false,
        failed: false,

        /**
         * Fetches the accounts again as soon as the month changes.
         */
        init() {
            this.$watch("month", () => this.refresh());
        },

        /**
         * Fetches the accounts matching the filters and shows them.
         */
        async refresh() {
            controller?.abort();
            controller = new AbortController();

            const url = new URL(window.location.href);
            const filters = { search: this.search.trim(), month: this.month };

            for (const [name, value] of Object.entries(filters)) {
                if (value === "") {
                    url.searchParams.delete(name);
                } else {
                    url.searchParams.set(name, value);
                }
            }

            window.history.replaceState(window.history.state, "", url);

            this.loading = true;

            try {
                const response = await fetch(url, {
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                    signal: controller.signal,
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                this.$refs.results.innerHTML = await response.text();
                this.$refs.summary.textContent =
                    this.$refs.results.querySelector("[data-summary]")?.textContent ?? "";
                this.failed = false;
            } catch (error) {
                if (error.name === "AbortError") {
                    return;
                }

                this.failed = true;
            }

            this.loading = false;
        },

        /**
         * Empties the search, shows all the accounts again and gives the focus back to the field.
         */
        clear() {
            this.search = "";
            this.refresh();
            this.$refs.input.focus();
        },

        /**
         * Empties the search and the month, shows all the accounts again and gives the focus back
         * to the search field.
         */
        clearFilters() {
            this.month = "";
            this.clear();
        },
    };
}
