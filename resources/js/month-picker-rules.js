/**
 * Rules of the month picker, without DOM nor Alpine, so that Node can test them
 * (tests/js/month-picker-rules.test.js).
 *
 * This module validates nothing: it reproduces, for the display only, the rule of
 * App\Support\MonthRestriction (which months can be chosen) and the trigger label of
 * App\View\Components\MonthPicker. The server checks the value it receives against its own
 * restriction (App\Rules\MonthSelection), whatever the browser shows or sends.
 *
 * Months are written "2026-11", so that comparing their texts compares their dates.
 */

/**
 * Month key of a year and a month number (1 to 12): "2026-11".
 *
 * @param {number} year
 * @param {number} month
 * @returns {string}
 */
export const keyOf = (year, month) => `${year}-${String(month).padStart(2, "0")}`;

/**
 * Year and month number of a month key.
 *
 * @param {string} key
 * @returns {[number, number]}
 */
export const parse = (key) => [Number(key.slice(0, 4)), Number(key.slice(5, 7))];

/**
 * Month key a number of months away.
 *
 * @param {string} key
 * @param {number} offset Months to add, negative to go back.
 * @returns {string}
 */
export const shift = (key, offset) => {
    const [year, month] = parse(key);
    const index = year * 12 + month - 1 + offset;

    return keyOf(Math.floor(index / 12), (index % 12) + 1);
};

/**
 * Restriction of the months, as sent by MonthRestriction::toArray(): bounds ("" without bound),
 * white list (null without white list) and black list, both as sorted [start, end] ranges.
 *
 * @typedef {{min: string, max: string, allow: ?[string, string][], deny: [string, string][]}} RestrictionConfig
 */

/**
 * Tests of a restriction: whether a month is within the bounds, and whether it can be chosen
 * (within the bounds, in the white list when given, out of the black list).
 *
 * @param {RestrictionConfig} config
 * @returns {{min: string, max: string, isWithinBounds: (key: string) => boolean, isChoosable: (key: string) => boolean}}
 */
export function createRestriction({ min, max, allow, deny }) {
    const covers = (ranges, key) => ranges.some(([start, end]) => key >= start && key <= end);
    const isWithinBounds = (key) => (min === "" || key >= min) && (max === "" || key <= max);

    return {
        min,
        max,
        isWithinBounds,
        isChoosable: (key) => isWithinBounds(key) && (allow === null || covers(allow, key)) && !covers(deny, key),
    };
}

/**
 * Nearest choosable month from a month, moving by a step, within the bounds; null if none. Without
 * bound, the search stops after a hundred years.
 *
 * @param {ReturnType<typeof createRestriction>} restriction
 * @param {string} key Starting month, excluded.
 * @param {number} step Months to move by at each try, negative to go back.
 * @returns {?string}
 */
export function seek(restriction, key, step) {
    let candidate = shift(key, step);

    for (let tries = 0; tries < 1200 / Math.abs(step); tries++, candidate = shift(candidate, step)) {
        if (!restriction.isWithinBounds(candidate)) {
            return null;
        }

        if (restriction.isChoosable(candidate)) {
            return candidate;
        }
    }

    return null;
}

/**
 * Full name of a month: "novembre 2026".
 *
 * @param {string} key
 * @param {string} lang Language of the page.
 * @returns {string}
 */
export function nameOf(key, lang) {
    const [year, month] = parse(key);

    return new Intl.DateTimeFormat(lang, { month: "long", year: "numeric" }).format(new Date(year, month - 1, 1));
}

/**
 * Label of a month: "Novembre 2026".
 *
 * @param {string} key
 * @param {string} lang Language of the page.
 * @returns {string}
 */
export function labelOf(key, lang) {
    const name = nameOf(key, lang);

    return name.charAt(0).toLocaleUpperCase(lang) + name.slice(1);
}

/**
 * Label of a selection for the trigger, "" without selection, as MonthPicker::describe() does:
 * "Novembre 2026" (single, multiple with one month, range of one month), "3 mois" (multiple),
 * "Octobre 2025 – novembre 2026" (range), "Depuis novembre 2026" (range without end).
 *
 * @param {"single"|"multiple"|"range"} mode
 * @param {string|string[]|{start: string, end: string}} value
 * @param {string} lang Language of the page.
 * @returns {string}
 */
export function describe(mode, value, lang) {
    if (mode === "multiple") {
        if (value.length > 1) {
            return `${value.length} mois`;
        }

        return value.length === 1 ? labelOf(value[0], lang) : "";
    }

    if (mode === "range") {
        const { start, end } = value;

        if (start === "") {
            return "";
        }

        if (end === "") {
            return `Depuis ${nameOf(start, lang)}`;
        }

        return end === start ? labelOf(start, lang) : `${labelOf(start, lang)} – ${nameOf(end, lang)}`;
    }

    return value === "" ? "" : labelOf(value, lang);
}
