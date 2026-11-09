/**
 * Tick box of an operation (components/tick-box.blade.php): saves the state at once, without
 * leaving the page, and confirms it by a toast (resources/js/toasts.js).
 *
 * The box changes at the click, then the state is sent (PATCH operation.tick, in JSON). The
 * request carries the state wanted, never an inversion, so that the server always ends on the last
 * click: a click while a request is pending is sent once it is done. On success, a toast quotes
 * the message of the server and offers to cancel, which sends the former state. On failure, the
 * box comes back to the last state saved, and a toast, kept until closed, says why.
 *
 * @param {object} operation
 * @param {number} operation.id Identifier, for the key of the toasts: a newer toast of the same
 *     operation takes the place of the former.
 * @param {string} operation.label Label, quoted by the failure toast.
 * @param {boolean} operation.ticked State saved on load.
 */
export default function tickBox(operation) {
    /**
     * Reason of a failure, by HTTP status.
     */
    const REASONS = {
        404: "l'opération n'existe plus ou son compte est archivé",
        419: "la session a expiré, rechargez la page",
    };

    return {
        /**
         * Last state saved by the server.
         */
        saved: operation.ticked,

        /**
         * State wanted by the last click.
         */
        wanted: operation.ticked,

        /**
         * Whether a request is pending.
         */
        busy: false,

        /**
         * Sends a state, then confirms it by a toast.
         *
         * @param {boolean} ticked
         */
        async send(ticked) {
            this.wanted = ticked;
            this.$refs.box.checked = ticked;

            if (this.busy) {
                return;
            }

            this.busy = true;

            let result;

            do {
                result = await this.request(this.wanted);
            } while (result.ok && this.wanted !== this.saved);

            this.busy = false;
            this.wanted = this.saved;
            this.$refs.box.checked = this.saved;

            if (result.ok) {
                this.$store.toasts.show({
                    type: "success",
                    message: result.message,
                    key: `operation-${operation.id}`,
                    action: { label: "Annuler", run: () => this.send(!this.saved) },
                });
            } else {
                this.$store.toasts.show({
                    type: "danger",
                    message: `Pointage de « ${operation.label} » non enregistré : ${result.reason}.`,
                    key: `operation-${operation.id}`,
                });
            }
        },

        /**
         * Saves a state on the server.
         *
         * @param {boolean} ticked
         * @returns {Promise<{ok: true, message: string} | {ok: false, reason: string}>}
         */
        async request(ticked) {
            let response;

            try {
                response = await fetch(this.$root.action, {
                    method: "PATCH",
                    headers: {
                        "Accept": "application/json",
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content ?? "",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                    body: JSON.stringify({ is_ticked: ticked }),
                });
            } catch {
                return { ok: false, reason: "la connexion a échoué, réessayez" };
            }

            if (!response.ok) {
                return { ok: false, reason: REASONS[response.status] ?? "erreur du serveur, réessayez" };
            }

            try {
                const data = await response.json();

                this.saved = data.is_ticked === true;

                return { ok: true, message: String(data.message) };
            } catch {
                return { ok: false, reason: "erreur du serveur, réessayez" };
            }
        },
    };
}
