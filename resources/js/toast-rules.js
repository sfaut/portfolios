/**
 * Rules of the toasts (resources/js/toasts.js), without DOM nor Alpine: types, display time, queue
 * and timer. They are tested by Node (tests/js/toast-rules.test.js).
 */

/**
 * Types of toast, those of <x-alert> (App\View\Components\Alert); any other value falls back to
 * the first.
 */
export const TYPES = ["info", "success", "warning", "danger"];

/**
 * Places of the toasts on the screen: edge, then alignment along the edge.
 */
export const POSITIONS = ["top-left", "top-center", "top-right", "bottom-left", "bottom-center", "bottom-right"];

/**
 * Place of the toasts when neither the region nor the toast gives one.
 */
export const DEFAULT_POSITION = "bottom-right";

/**
 * Toasts shown at once in a place: a newer one closes the oldest of the place.
 */
export const LIMIT = 3;

/**
 * Display time of a toast, in milliseconds.
 */
export const DURATION = 5000;

/**
 * Display time of a toast with an action, longer to leave the time to use it.
 */
export const ACTION_DURATION = 8000;

/**
 * Turns any value into a toast type, "info" by default.
 *
 * @param {unknown} type
 * @returns {"info" | "success" | "warning" | "danger"}
 */
export function typeOf(type) {
    return TYPES.includes(type) ? type : TYPES[0];
}

/**
 * Turns any value into a place, the fallback when the value is unknown.
 *
 * @param {unknown} position
 * @param {string} fallback A place of POSITIONS.
 * @returns {string}
 */
export function positionOf(position, fallback = DEFAULT_POSITION) {
    if (POSITIONS.includes(position)) {
        return position;
    }

    return POSITIONS.includes(fallback) ? fallback : DEFAULT_POSITION;
}

/**
 * Whether the toasts of a place stack from the top edge: the newest is then the first, closest to
 * the edge, as at the bottom, where it is the last.
 *
 * @param {string} position A place of POSITIONS.
 * @returns {boolean}
 */
export function isTop(position) {
    return position.startsWith("top-");
}

/**
 * Display time of a toast: the given one, or else by default 0 (kept until closed) for a failure,
 * ACTION_DURATION with an action and DURATION otherwise.
 *
 * @param {{type?: string, action?: object|null, duration?: number|null}} options
 * @returns {number} Milliseconds, 0 to keep the toast until it is closed.
 */
export function durationOf(options) {
    if (Number.isFinite(options.duration) && options.duration >= 0) {
        return options.duration;
    }

    if (typeOf(options.type) === "danger") {
        return 0;
    }

    return options.action ? ACTION_DURATION : DURATION;
}

/**
 * Adds a toast to the list: it takes the place of the toast of the same key, or else comes last,
 * the oldest ones of its place being dropped beyond the limit.
 *
 * @template {{key?: string|null, position: string}} T
 * @param {T[]} list Toasts shown, oldest first.
 * @param {T} toast
 * @param {number} limit
 * @returns {{list: T[], dropped: T[]}} New list, and the toasts it no longer holds.
 */
export function enqueue(list, toast, limit = LIMIT) {
    const index = toast.key ? list.findIndex((item) => item.key === toast.key) : -1;

    if (index !== -1) {
        const next = [...list];
        const [replaced] = next.splice(index, 1, toast);

        return { list: next, dropped: [replaced] };
    }

    const same = list.filter((item) => item.position === toast.position);
    const dropped = same.slice(0, Math.max(0, same.length + 1 - limit));

    return { list: [...list.filter((item) => !dropped.includes(item)), toast], dropped };
}

/**
 * Timer of a toast, which can be paused and resumed: the time is given to each call, so that the
 * timer holds no clock.
 *
 * @param {number} duration Milliseconds, 0 for a timer that never ends.
 * @param {number} now Start time, in milliseconds.
 */
export function createTimer(duration, now) {
    let remaining = duration;
    let startedAt = now;
    let paused = false;

    return {
        /**
         * Whether the timer is paused.
         */
        get paused() {
            return paused;
        },

        /**
         * Milliseconds left, Infinity for a timer that never ends.
         *
         * @param {number} at
         */
        remaining(at) {
            if (duration === 0) {
                return Infinity;
            }

            return Math.max(0, paused ? remaining : remaining - (at - startedAt));
        },

        /**
         * Stops the timer, keeping the time left.
         *
         * @param {number} at
         */
        pause(at) {
            if (!paused) {
                remaining = this.remaining(at);
                paused = true;
            }
        },

        /**
         * Starts the timer again, with the time left.
         *
         * @param {number} at
         */
        resume(at) {
            if (paused) {
                startedAt = at;
                paused = false;
            }
        },
    };
}
