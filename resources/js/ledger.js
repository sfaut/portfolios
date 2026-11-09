/**
 * Ledger of a portfolio (ledger/index.blade.php): filters, sort and pages of its operations.
 *
 * Any change of a filter fetches the page with the filters from the server, which answers ajax
 * requests with the "ledger" fragment alone (totals, tools and table); the fragment replaces the
 * results ($refs.results). The sort headings and the pagination links of the fragment are fetched
 * the same way. Filters, sort and page are kept in the URL, so that a reload shows them again.
 *
 * The filters are bound to the fields by x-model (text and number fields after a short pause), and
 * the server reads them leniently: a value it refuses is ignored.
 *
 * @param {object} filters Filters given by the server (LedgerFilters::state()).
 * @param {string} filters.label Text searched in the labels.
 * @param {string[]} filters.accounts Identifiers of the accounts kept.
 * @param {string[]} filters.axes Identifiers of the analytic axes kept, and "none".
 * @param {{start: string, end: string}} filters.effective Effective dates.
 * @param {{start: string, end: string}} filters.written Writing days.
 * @param {string} filters.flow "sales", "payments" or "".
 * @param {string} filters.amountMin Lowest absolute amount, "" without bound.
 * @param {string} filters.amountMax Highest absolute amount, "" without bound.
 */
export default function ledger(filters) {
    /**
     * Controller of the pending request, aborted by a newer one.
     *
     * @type {?AbortController}
     */
    let controller = null;

    /**
     * Whether a refresh is already queued for the changes of this tick.
     */
    let queued = false;

    return {
        ...filters,
        loading: false,
        failed: false,

        /**
         * Whether at least one filter is set.
         */
        get filtered() {
            return Object.keys(this.parameters()).length > 0;
        },

        /**
         * Fetches the ledger again as soon as a filter changes; several filters changed at once
         * fetch it once.
         */
        init() {
            for (const name of Object.keys(filters)) {
                this.$watch(name, () => {
                    if (queued) {
                        return;
                    }

                    queued = true;
                    queueMicrotask(() => {
                        queued = false;
                        this.refresh();
                    });
                });
            }
        },

        /**
         * URL parameters of the filters set, as the server reads them.
         *
         * @returns {Record<string, string|string[]>}
         */
        parameters() {
            const parameters = {
                label: this.label.trim(),
                "accounts[]": [...this.accounts],
                "axes[]": [...this.axes],
                "effective[start]": this.effective.start,
                "effective[end]": this.effective.end,
                "written[start]": this.written.start,
                "written[end]": this.written.end,
                flow: this.flow,
                "amount[min]": String(this.amountMin ?? ""),
                "amount[max]": String(this.amountMax ?? ""),
            };

            return Object.fromEntries(Object.entries(parameters).filter(([, value]) => value.length > 0));
        },

        /**
         * Fetches the first page of the operations matching the filters, with the sort of the URL.
         */
        refresh() {
            const current = new URL(window.location.href);
            const url = new URL(current.pathname, current.origin);

            for (const [name, value] of Object.entries(this.parameters())) {
                for (const item of [value].flat()) {
                    url.searchParams.append(name, item);
                }
            }

            for (const name of ["sort", "dir"]) {
                if (current.searchParams.has(name)) {
                    url.searchParams.set(name, current.searchParams.get(name));
                }
            }

            return this.load(url);
        },

        /**
         * Fetches the fragment of a URL, shows it in place of the results and keeps the URL.
         *
         * @param {URL} url
         */
        async load(url) {
            controller?.abort();
            controller = new AbortController();

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
                window.history.replaceState(window.history.state, "", url);
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
         * Submitting the filters fetches the ledger, except from the "Tout" toggle button, whose
         * preference the server keeps before reloading the page.
         *
         * @param {SubmitEvent} event
         */
        onSubmit(event) {
            if (event.submitter?.name === "all") {
                return;
            }

            event.preventDefault();
            this.refresh();
        },

        /**
         * A click on a sort heading or on a pagination link of the results fetches its fragment
         * instead of loading its page; a modified click (new tab…) is left to the browser.
         *
         * @param {MouseEvent} event
         */
        onClick(event) {
            const link = event.target.closest?.("a[data-fragment-link], nav a");

            if (!link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) {
                return;
            }

            event.preventDefault();
            this.load(new URL(link.href));
        },
    };
}
