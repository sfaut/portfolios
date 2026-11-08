/**
 * Search of the accounts of a portfolio by name (portfolio/show.blade.php).
 *
 * Typing shows the spinner at once and, after a short pause, fetches the page with the search
 * from the server, which answers ajax requests with the "accounts" fragment alone. The fragment
 * replaces the results ($refs.results), and its summary ([data-summary]) goes to the status next
 * to the field ($refs.summary). The search is kept in the URL, so that a reload shows it again.
 *
 * @param {string} search Search given by the server, empty without search.
 */
export default function accountSearch(search) {
    /**
     * Controller of the pending request, aborted by a newer search.
     *
     * @type {?AbortController}
     */
    let controller = null;

    return {
        search,
        loading: false,
        failed: false,

        /**
         * Fetches the accounts matching the search and shows them.
         */
        async refresh() {
            controller?.abort();
            controller = new AbortController();

            const url = new URL(window.location.href);
            const value = this.search.trim();

            if (value === "") {
                url.searchParams.delete("search");
            } else {
                url.searchParams.set("search", value);
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
    };
}
