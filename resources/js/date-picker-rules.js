/**
 * Rules of the date picker, without DOM nor Alpine, so that Node can test them
 * (tests/js/date-picker-rules.test.js).
 *
 * This module validates nothing: it reproduces, for the display only, the rule of
 * App\Support\DateRestriction (which days can be chosen) and the trigger label of
 * App\View\Components\DatePicker. The server checks the value it receives against its own
 * restriction (App\Rules\DateSelection), whatever the browser shows or sends.
 *
 * Days are written "2026-11-09", so that comparing their texts compares their dates. Their
 * arithmetic is done in UTC, where no day is shortened or lengthened by a time change.
 */

/**
 * Day key of a year, a month number (1 to 12) and a day number: "2026-11-09".
 *
 * @param {number} year
 * @param {number} month
 * @param {number} day
 * @returns {string}
 */
export const keyOf = (year, month, day) =>
    `${String(year).padStart(4, "0")}-${String(month).padStart(2, "0")}-${String(day).padStart(2, "0")}`;

/**
 * Year, month number and day number of a day key.
 *
 * @param {string} key
 * @returns {[number, number, number]}
 */
export const parse = (key) => [Number(key.slice(0, 4)), Number(key.slice(5, 7)), Number(key.slice(8, 10))];

/**
 * Month of a day key: "2026-11".
 *
 * @param {string} key
 * @returns {string}
 */
export const monthOf = (key) => key.slice(0, 7);

/**
 * Number of days of a month.
 *
 * @param {number} year
 * @param {number} month Month number, 1 to 12.
 * @returns {number}
 */
export const daysIn = (year, month) => new Date(Date.UTC(year, month, 0)).getUTCDate();

/**
 * Day key a number of days away.
 *
 * @param {string} key
 * @param {number} offset Days to add, negative to go back.
 * @returns {string}
 */
export const shift = (key, offset) => {
    const [year, month, day] = parse(key);
    const date = new Date(Date.UTC(year, month - 1, day + offset));

    return keyOf(date.getUTCFullYear(), date.getUTCMonth() + 1, date.getUTCDate());
};

/**
 * Month key ("2026-11") a number of months away.
 *
 * @param {string} month_key
 * @param {number} offset Months to add, negative to go back.
 * @returns {string}
 */
export const shiftMonth = (month_key, offset) => {
    const index = Number(month_key.slice(0, 4)) * 12 + Number(month_key.slice(5, 7)) - 1 + offset;

    return `${String(Math.floor(index / 12)).padStart(4, "0")}-${String((index % 12) + 1).padStart(2, "0")}`;
};

/**
 * Same day a number of months away, kept within the month reached: a month after January 31 is
 * the last day of February.
 *
 * @param {string} key
 * @param {number} offset Months to add, negative to go back.
 * @returns {string}
 */
export const shiftByMonths = (key, offset) => {
    const month_key = shiftMonth(monthOf(key), offset);
    const year = Number(month_key.slice(0, 4));
    const month = Number(month_key.slice(5, 7));

    return keyOf(year, month, Math.min(parse(key)[2], daysIn(year, month)));
};

/**
 * Day of the week of a day key, from Monday (0) to Sunday (6).
 *
 * @param {string} key
 * @returns {number}
 */
export const weekdayOf = (key) => {
    const [year, month, day] = parse(key);

    return (new Date(Date.UTC(year, month - 1, day)).getUTCDay() + 6) % 7;
};

/**
 * Days of a month, in order.
 *
 * @param {string} month_key Month, "2026-11".
 * @returns {string[]}
 */
export const daysOf = (month_key) => {
    const year = Number(month_key.slice(0, 4));
    const month = Number(month_key.slice(5, 7));

    return Array.from({ length: daysIn(year, month) }, (_, index) => keyOf(year, month, index + 1));
};

/**
 * Restriction of the days, as sent by DateRestriction::toArray(): bounds ("" without bound),
 * white list (null without white list) and black list, both as sorted [start, end] ranges.
 *
 * @typedef {{min: string, max: string, allow: ?[string, string][], deny: [string, string][]}} RestrictionConfig
 */

/**
 * Tests of a restriction: whether a day is within the bounds, and whether it can be chosen
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
 * Nearest choosable day from a day, moving by a step, within the bounds; null if none. Without
 * bound, the search stops after ten years.
 *
 * @param {ReturnType<typeof createRestriction>} restriction
 * @param {string} key Starting day, excluded.
 * @param {number} step Days to move by at each try, negative to go back.
 * @returns {?string}
 */
export function seek(restriction, key, step) {
    let candidate = shift(key, step);

    for (let tries = 0; tries < 3660 / Math.abs(step); tries++, candidate = shift(candidate, step)) {
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
 * Local date of a day key, for the formatters.
 *
 * @param {string} key
 * @returns {Date}
 */
const dateOf = (key) => {
    const [year, month, day] = parse(key);

    return new Date(year, month - 1, day);
};

/**
 * Formatters already built, by language and options: building one costs far more than using it,
 * and a calendar names each of its days at every render.
 *
 * @type {Map<string, Intl.DateTimeFormat>}
 */
const formatters = new Map();

/**
 * Date formatter of a language and options, built once.
 *
 * @param {string} lang Language of the page.
 * @param {Intl.DateTimeFormatOptions} options
 * @returns {Intl.DateTimeFormat}
 */
const formatterOf = (lang, options) => {
    const id = `${lang}|${JSON.stringify(options)}`;

    if (!formatters.has(id)) {
        formatters.set(id, new Intl.DateTimeFormat(lang, options));
    }

    return formatters.get(id);
};

/**
 * Short name of the month of a day: "nov.".
 *
 * @param {string} key
 * @param {string} lang Language of the page.
 * @returns {string}
 */
const shortMonthOf = (key, lang) => formatterOf(lang, { month: "short" }).format(dateOf(key));

/**
 * Full name of a day: "lundi 9 novembre 2026".
 *
 * @param {string} key
 * @param {string} lang Language of the page.
 * @returns {string}
 */
export function nameOf(key, lang) {
    return formatterOf(lang, { weekday: "long", day: "numeric", month: "long", year: "numeric" }).format(dateOf(key));
}

/**
 * Title of a month: "Novembre 2026".
 *
 * @param {string} month_key Month, "2026-11".
 * @param {string} lang Language of the page.
 * @returns {string}
 */
export function monthTitle(month_key, lang) {
    const name = formatterOf(lang, { month: "long", year: "numeric" }).format(dateOf(`${month_key}-01`));

    return name.charAt(0).toLocaleUpperCase(lang) + name.slice(1);
}

/**
 * Label of a day: "9 nov. 2026".
 *
 * @param {string} key
 * @param {string} lang Language of the page.
 * @returns {string}
 */
export function labelOf(key, lang) {
    const [year, , day] = parse(key);

    return `${day} ${shortMonthOf(key, lang)} ${year}`;
}

/**
 * Label of a range of days, what its start and end share being written once, as
 * DatePicker::rangeLabel() does: "9 nov. 2026", "1 – 30 nov. 2026", "28 oct. – 9 nov. 2026",
 * "28 déc. 2025 – 9 janv. 2026".
 *
 * @param {string} start
 * @param {string} end
 * @param {string} lang Language of the page.
 * @returns {string}
 */
export function rangeLabelOf(start, end, lang) {
    const end_label = labelOf(end, lang);
    const day = parse(start)[2];

    if (start === end) {
        return end_label;
    }

    if (monthOf(start) === monthOf(end)) {
        return `${day} – ${end_label}`;
    }

    if (start.slice(0, 4) === end.slice(0, 4)) {
        return `${day} ${shortMonthOf(start, lang)} – ${end_label}`;
    }

    return `${labelOf(start, lang)} – ${end_label}`;
}

/**
 * Label of a selection for the trigger, "" without selection, as DatePicker::describe() does:
 * "9 nov. 2026" (single, multiple with one day, range of one day), "3 jours" (multiple),
 * "1 – 30 nov. 2026" (range), "Depuis le 9 nov. 2026" (range without end).
 *
 * @param {"single"|"multiple"|"range"} mode
 * @param {string|string[]|{start: string, end: string}} value
 * @param {string} lang Language of the page.
 * @returns {string}
 */
export function describe(mode, value, lang) {
    if (mode === "multiple") {
        if (value.length > 1) {
            return `${value.length} jours`;
        }

        return value.length === 1 ? labelOf(value[0], lang) : "";
    }

    if (mode === "range") {
        const { start, end } = value;

        if (start === "") {
            return "";
        }

        return end === "" ? `Depuis le ${labelOf(start, lang)}` : rangeLabelOf(start, end, lang);
    }

    return value === "" ? "" : labelOf(value, lang);
}
