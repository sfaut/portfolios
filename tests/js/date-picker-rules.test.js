/**
 * Tests of the date picker rules (resources/js/date-picker-rules.js), run by the test runner built
 * into Node: npm run test:js.
 *
 * The restriction cases and the labels come from tests/fixtures, which the Pest tests read too
 * (DateRestrictionTest, DatePickerTest): the browser and the server must agree on them.
 */
import assert from "node:assert/strict";
import { readFileSync } from "node:fs";
import { describe as group, test } from "node:test";
import {
    createRestriction,
    daysIn,
    daysOf,
    describe,
    keyOf,
    monthOf,
    monthTitle,
    nameOf,
    parse,
    seek,
    shift,
    shiftByMonths,
    shiftMonth,
    weekdayOf,
} from "../../resources/js/date-picker-rules.js";

const fixture = (name) => JSON.parse(readFileSync(new URL(`../fixtures/${name}`, import.meta.url), "utf8"));
const restrictions = fixture("date-restrictions.json");
const labels = fixture("date-picker-labels.json");

group("day keys", () => {
    test("are written with a four-digit year, a two-digit month and a two-digit day", () => {
        assert.equal(keyOf(2026, 3, 9), "2026-03-09");
        assert.deepEqual(parse("2026-11-09"), [2026, 11, 9]);
        assert.equal(monthOf("2026-11-09"), "2026-11");
    });

    test("shift across the months and the years, both ways", () => {
        assert.equal(shift("2026-11-30", 1), "2026-12-01");
        assert.equal(shift("2026-01-01", -1), "2025-12-31");
        assert.equal(shift("2028-02-28", 1), "2028-02-29");
        assert.equal(shift("2026-02-28", 1), "2026-03-01");
        assert.equal(shift("2026-11-09", 0), "2026-11-09");
    });

    test("shift across a time change without losing a day", () => {
        assert.equal(shift("2026-03-28", 1), "2026-03-29");
        assert.equal(shift("2026-03-29", 1), "2026-03-30");
        assert.equal(shift("2026-10-25", 1), "2026-10-26");
        assert.equal(shift("2026-10-26", -1), "2026-10-25");
    });

    test("shift by months, kept within the month reached", () => {
        assert.equal(shiftMonth("2026-11", 2), "2027-01");
        assert.equal(shiftMonth("2026-01", -1), "2025-12");
        assert.equal(shiftByMonths("2026-01-31", 1), "2026-02-28");
        assert.equal(shiftByMonths("2028-01-31", 1), "2028-02-29");
        assert.equal(shiftByMonths("2026-03-15", -12), "2025-03-15");
        assert.equal(shiftByMonths("2028-02-29", 12), "2029-02-28");
    });
});

group("calendar of a month", () => {
    test("counts the days of a month, leap years included", () => {
        assert.equal(daysIn(2026, 2), 28);
        assert.equal(daysIn(2028, 2), 29);
        assert.equal(daysIn(2026, 11), 30);
        assert.equal(daysIn(2026, 12), 31);
    });

    test("lists the days of a month in order", () => {
        const days = daysOf("2026-02");

        assert.equal(days.length, 28);
        assert.equal(days[0], "2026-02-01");
        assert.equal(days[27], "2026-02-28");
    });

    test("numbers the days of the week from Monday", () => {
        assert.equal(weekdayOf("2026-11-09"), 0);
        assert.equal(weekdayOf("2026-11-01"), 6);
        assert.equal(weekdayOf("2026-11-14"), 5);
    });

    test("names a month and a day in the language of the page", () => {
        assert.equal(monthTitle("2026-11", "fr"), "Novembre 2026");
        assert.equal(nameOf("2026-11-09", "fr"), "lundi 9 novembre 2026");
    });
});

group("restriction, as the server applies it", () => {
    for (const [name, { normalized, choosable, refused }] of Object.entries(restrictions)) {
        test(name, () => {
            const restriction = createRestriction(normalized);

            for (const day of choosable) {
                assert.equal(restriction.isChoosable(day), true, `${day} should be choosable`);
            }

            for (const day of refused) {
                assert.equal(restriction.isChoosable(day), false, `${day} should be refused`);
            }
        });
    }

    test("a day out of the white or black lists stays within the bounds", () => {
        const restriction = createRestriction(restrictions["white and black lists"].normalized);

        assert.equal(restriction.isWithinBounds("2026-05-01"), true);
        assert.equal(restriction.isWithinBounds("2027-01-01"), false);
    });
});

group("keyboard search of the nearest choosable day", () => {
    const restriction = createRestriction(restrictions["white list of days and ranges, one reversed"].normalized);

    test("moves to the next or previous choosable day", () => {
        assert.equal(seek(restriction, "2026-02-27", 1), "2026-02-28");
        assert.equal(seek(restriction, "2026-03-01", -1), "2026-02-28");
    });

    test("skips the forbidden days, across the months and the years", () => {
        assert.equal(seek(restriction, "2026-03-02", 1), "2026-06-01");
        assert.equal(seek(restriction, "2026-02-25", -1), "2025-12-30");
    });

    test("moves by a week, skipping the forbidden days", () => {
        assert.equal(seek(restriction, "2026-06-01", 7), "2026-06-08");
        assert.equal(seek(restriction, "2026-03-02", 7), "2026-06-01");
        assert.equal(seek(restriction, "2026-06-10", -7), "2026-06-03");
    });

    test("stops at the bounds", () => {
        assert.equal(seek(restriction, "2026-06-15", 1), null);
        assert.equal(seek(restriction, "2025-12-30", -1), null);
        assert.equal(seek(restriction, "2026-06-10", 7), null);
    });

    test("skips the black list without bounds", () => {
        const black_list = createRestriction(restrictions["black list of days and ranges"].normalized);

        assert.equal(seek(black_list, "2026-10-07", 1), "2026-10-13");
        assert.equal(seek(black_list, "2026-10-13", -1), "2026-10-07");
        assert.equal(seek(black_list, "2026-03-11", -1), "2026-03-09");
    });

    test("finds nothing when no day can be chosen", () => {
        assert.equal(seek(createRestriction(restrictions["empty white list"].normalized), "2026-11-09", 1), null);
    });
});

group("trigger label, as the server writes it", () => {
    for (const { mode, value, label } of labels) {
        test(`${mode}: ${JSON.stringify(value)}`, () => {
            assert.equal(describe(mode, value, "fr"), label);
        });
    }
});
