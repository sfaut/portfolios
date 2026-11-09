/**
 * Toasts: short notices shown over the page, in the region of components/toast-region.blade.php,
 * which the app layout holds once.
 *
 * A component shows one by Alpine.store("toasts").show({...}), whose options are listed on show().
 * The server shows one by the "toast" flash of the session, which the region shows on load. The
 * rules (types, display time, queue, timer) are in toast-rules.js.
 *
 * Each toast closes alone after its display time, except a failure, kept until closed. Its timer
 * stops while the pointer is over it, while the focus is in it and while the tab is hidden; a thin
 * line shows the time left. A toast never takes the focus. Its message is announced to screen
 * readers by two live regions, as <x-alert>: assertive (alert) for a failure, polite otherwise.
 */
import { createTimer, durationOf, enqueue, isTop, POSITIONS, positionOf, typeOf } from "./toast-rules";

/**
 * Time of the leaving fade, in milliseconds, as the transition of the toasts.
 */
const LEAVE_DURATION = 150;

/**
 * Classes of the stack of each place, added to "left-4 right-4 sm:w-96": full width less 16 px on
 * each side on a narrow screen, then 24 rem against its corner or centered.
 */
const STACK_CLASSES = {
    "top-left": "top-4 sm:right-auto",
    "top-center": "top-4 sm:right-auto sm:left-1/2 sm:-translate-x-1/2",
    "top-right": "top-4 sm:left-auto",
    "bottom-left": "bottom-4 sm:right-auto",
    "bottom-center": "bottom-4 sm:right-auto sm:left-1/2 sm:-translate-x-1/2",
    "bottom-right": "bottom-4 sm:left-auto",
};

/**
 * Identifier of the last toast shown.
 */
let sequence = 0;

/**
 * Timer and pending timeout of each toast, by identifier, kept out of the reactive state.
 *
 * @type {Map<number, {timer: ReturnType<typeof createTimer>, handle: ?number}>}
 */
const timers = new Map();

/**
 * Alpine store, registered as "toasts".
 */
export default {
    /**
     * Toasts shown, oldest first.
     *
     * @type {Array<{id: number, key: ?string, type: string, message: string, action: ?{label: string, run: Function}, position: string, duration: number, hovered: boolean, focused: boolean, leaving: boolean}>}
     */
    items: [],

    /**
     * Places, in the order of their stacks.
     */
    positions: POSITIONS,

    /**
     * Place of a toast that gives none, set by the region.
     */
    position: positionOf(null),

    /**
     * Whether the tab is hidden, which stops every timer.
     */
    hidden: false,

    /**
     * Texts of the polite and assertive live regions.
     */
    polite: "",
    assertive: "",

    /**
     * Region of the toasts, a manual popover.
     *
     * @type {?HTMLElement}
     */
    region: null,

    /**
     * Follows the visibility of the tab.
     */
    init() {
        document.addEventListener("visibilitychange", () => {
            this.hidden = document.hidden;
            this.items.forEach((item) => this.sync(item));
        });
    },

    /**
     * Binds the region of the page and the place it gives to the toasts by default, then shows the
     * toast of the server.
     *
     * @param {HTMLElement} region
     * @param {string} position
     * @param {?{type: string, message: string}} flash Toast of the "toast" flash of the session.
     */
    mount(region, position, flash = null) {
        this.region = region;
        this.position = positionOf(position);
        this.raise();

        if (flash) {
            this.show(flash);
        }
    },

    /**
     * Puts the region back on top of the top layer, above a dialog or a popover opened after it.
     */
    raise() {
        if (!this.region?.isConnected) {
            return;
        }

        try {
            if (this.region.matches(":popover-open")) {
                this.region.hidePopover();
            }

            this.region.showPopover();
        } catch {
            //
        }
    },

    /**
     * Shows a toast.
     *
     * @param {object} options
     * @param {string} options.message Text of the toast, always shown as text.
     * @param {"info"|"success"|"warning"|"danger"} [options.type] Meaning of the toast, as for
     *     <x-alert>: its icon and its color; "info" by default.
     * @param {{label: string, run: Function}} [options.action] Button of the toast, which runs the
     *     function, then closes the toast ("Annuler"…).
     * @param {number} [options.duration] Display time in milliseconds, 0 to keep the toast until
     *     it is closed; by default 5 s, 8 s with an action, kept for a failure.
     * @param {string} [options.key] A toast of the same key takes the place of the former.
     * @param {string} [options.position] Place of POSITIONS, the one of the region by default.
     * @returns {number} Identifier of the toast, for dismiss().
     */
    show(options) {
        const action = options.action && typeof options.action.run === "function"
            ? { label: String(options.action.label ?? ""), run: options.action.run }
            : null;

        const item = {
            id: ++sequence,
            key: options.key ? String(options.key) : null,
            type: typeOf(options.type),
            message: String(options.message ?? ""),
            action,
            position: positionOf(options.position, this.position),
            duration: durationOf({ ...options, action }),
            hovered: false,
            focused: false,
            leaving: false,
        };

        const { list, dropped } = enqueue(this.items, item);

        dropped.forEach((toast) => this.clear(toast.id));
        this.items = list;

        timers.set(item.id, { timer: createTimer(item.duration, performance.now()), handle: null });
        this.sync(item);
        this.announce(item);
        this.raise();

        return item.id;
    },

    /**
     * Closes a toast, after its leaving fade.
     *
     * @param {number} id
     */
    dismiss(id) {
        const item = this.find(id);

        if (!item || item.leaving) {
            return;
        }

        this.clear(id);
        item.leaving = true;

        setTimeout(() => {
            this.items = this.items.filter((toast) => toast.id !== id);
        }, LEAVE_DURATION);
    },

    /**
     * Runs the action of a toast, then closes it.
     *
     * @param {number} id
     */
    run(id) {
        const item = this.find(id);

        if (!item?.action || item.leaving) {
            return;
        }

        const run = item.action.run;

        this.dismiss(id);
        run();
    },

    /**
     * Toast of an identifier.
     *
     * @param {number} id
     */
    find(id) {
        return this.items.find((toast) => toast.id === id);
    },

    /**
     * Toasts of a place, in the order of the stack: the newest closest to the edge.
     *
     * @param {string} position
     */
    at(position) {
        const list = this.items.filter((toast) => toast.position === position);

        return isTop(position) ? list.reverse() : list;
    },

    /**
     * Classes of the stack of a place.
     *
     * @param {string} position
     */
    stackClass(position) {
        return STACK_CLASSES[position];
    },

    /**
     * Whether the timer of a toast is stopped.
     */
    isPaused(item) {
        return item.hovered || item.focused || this.hidden;
    },

    /**
     * Records that the pointer or the focus enters or leaves a toast.
     *
     * @param {number} id
     * @param {"hovered"|"focused"} flag
     * @param {boolean} value
     */
    hold(id, flag, value) {
        const item = this.find(id);

        if (item) {
            item[flag] = value;
            this.sync(item);
        }
    },

    /**
     * Starts or stops the timer of a toast, as its state asks.
     */
    sync(item) {
        const entry = timers.get(item.id);

        if (!entry || item.duration === 0) {
            return;
        }

        clearTimeout(entry.handle);

        const now = performance.now();

        if (this.isPaused(item)) {
            entry.timer.pause(now);

            return;
        }

        entry.timer.resume(now);
        entry.handle = setTimeout(() => this.dismiss(item.id), entry.timer.remaining(now));
    },

    /**
     * Forgets the timer of a toast.
     *
     * @param {number} id
     */
    clear(id) {
        clearTimeout(timers.get(id)?.handle);
        timers.delete(id);
    },

    /**
     * Announces the message of a toast by its live region, emptied first, so that the same message
     * is announced again.
     */
    announce(item) {
        const region = item.type === "danger" ? "assertive" : "polite";

        this[region] = "";
        setTimeout(() => {
            this[region] = item.message;
        }, 50);
    },
};
