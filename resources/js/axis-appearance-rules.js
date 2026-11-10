/**
 * Rules of the icon search of an analytic axis (resources/js/axis-appearance.js), without DOM nor
 * Alpine. They are tested by Node (tests/js/axis-appearance-rules.test.js).
 */

/**
 * Text made comparable: lower case, without accents nor ligatures, single spaces.
 *
 * @param {string} text
 * @returns {string}
 */
export function normalize(text) {
    return String(text)
        .toLowerCase()
        .replaceAll("œ", "oe")
        .replaceAll("æ", "ae")
        .normalize("NFD")
        .replace(/\p{Diacritic}/gu, "")
        .replace(/\s+/g, " ")
        .trim();
}

/**
 * Whether a searched text matches: each of its words is found in the text of the icon (name,
 * keywords, Lucide name, theme). An empty search matches everything.
 *
 * @param {string} haystack Text of the icon.
 * @param {string} query Text searched.
 * @returns {boolean}
 */
export function matches(haystack, query) {
    const text = normalize(haystack);

    return normalize(query)
        .split(" ")
        .filter((word) => word !== "")
        .every((word) => text.includes(word));
}
