/**
 * Tests of the month picker rules (resources/js/month-picker-rules.js), run by the test runner
 * built into Node: npm run test:js.
 *
 * The restriction cases and the labels come from tests/fixtures, which the Pest tests read too
 * (MonthRestrictionTest, MonthPickerTest): the browser and the server must agree on them.
 */
import assert from "node:assert/strict";
import { readFileSync } from "node:fs";
import { describe as group, test } from "node:test";
import { createRestriction, describe, keyOf, parse, seek, shift } from "../../resources/js/month-picker-rules.js";

const fixture = (name) => JSON.parse(readFileSync(new URL(`../fixtures/${name}`, import.meta.url), "utf8"));
const restrictions = fixture("month-restrictions.json");
const labels = fixture("month-picker-labels.json");

group("month keys", () => {
    test("are written with a four-digit year and a two-digit month", () => {
        assert.equal(keyOf(2026, 3), "2026-03");
        assert.deepEqual(parse("2026-11"), [2026, 11]);
    });

    test("shift across the years, both ways", () => {
        assert.equal(shift("2026-11", 2), "2027-01");
        assert.equal(shift("2026-01", -1), "2025-12");
        assert.equal(shift("2026-11", -23), "2024-12");
        assert.equal(shift("2026-11", 0), "2026-11");
    });
});

group("restriction, as the server applies it", () => {
    for (const [name, { normalized, choosable, refused }] of Object.entries(restrictions)) {
        test(name, () => {
            const restriction = createRestriction(normalized);

            for (const month of choosable) {
                assert.equal(restriction.isChoosable(month), true, `${month} should be choosable`);
            }

            for (const month of refused) {
                assert.equal(restriction.isChoosable(month), false, `${month} should be refused`);
            }
        });
    }

    test("a month out of the white or black lists stays within the bounds", () => {
        const restriction = createRestriction(restrictions["white and black lists"].normalized);

        assert.equal(restriction.isWithinBounds("2025-06"), true);
        assert.equal(restriction.isWithinBounds("2026-01"), false);
    });
});

group("keyboard search of the nearest choosable month", () => {
    const restriction = createRestriction(restrictions["white list of months and ranges, one reversed"].normalized);

    test("moves to the next or previous choosable month", () => {
        assert.equal(seek(restriction, "2025-05", 1), "2025-06");
        assert.equal(seek(restriction, "2025-06", -1), "2025-05");
    });

    test("skips the forbidden months, across the years", () => {
        assert.equal(seek(restriction, "2025-07", 1), "2026-01");
        assert.equal(seek(restriction, "2025-05", -1), "2024-11");
    });

    test("moves by three months, skipping the forbidden ones", () => {
        assert.equal(seek(restriction, "2025-05", 3), "2026-02");
        assert.equal(seek(restriction, "2026-03", -3), "2025-06");
    });

    test("stops at the bounds", () => {
        assert.equal(seek(restriction, "2026-06", 1), null);
        assert.equal(seek(restriction, "2024-11", -1), null);
    });

    test("skips the black list without bounds", () => {
        const black_list = createRestriction(restrictions["black list of months and ranges"].normalized);

        assert.equal(seek(black_list, "2025-07", 1), "2025-11");
        assert.equal(seek(black_list, "2025-11", -1), "2025-07");
        assert.equal(seek(black_list, "2025-04", -1), "2025-02");
    });

    test("finds nothing when no month can be chosen", () => {
        assert.equal(seek(createRestriction(restrictions["empty white list"].normalized), "2026-11", 1), null);
    });
});

group("trigger label, as the server writes it", () => {
    for (const { mode, value, label } of labels) {
        test(`${mode}: ${JSON.stringify(value)}`, () => {
            assert.equal(describe(mode, value, "fr"), label);
        });
    }
});
