/**
 * Tests of the toast rules (resources/js/toast-rules.js), run by the test runner built into Node:
 * npm run test:js.
 */
import assert from "node:assert/strict";
import { describe as group, test } from "node:test";
import {
    ACTION_DURATION,
    createTimer,
    DURATION,
    durationOf,
    enqueue,
    isTop,
    positionOf,
    typeOf,
} from "../../resources/js/toast-rules.js";

group("types", () => {
    test("are those of the alerts, info by default", () => {
        for (const type of ["info", "success", "warning", "danger"]) {
            assert.equal(typeOf(type), type);
        }

        assert.equal(typeOf("error"), "info");
        assert.equal(typeOf(undefined), "info");
    });
});

group("places", () => {
    test("fall back on the place given, then on the bottom right corner", () => {
        assert.equal(positionOf("top-center"), "top-center");
        assert.equal(positionOf("middle", "top-left"), "top-left");
        assert.equal(positionOf(undefined, "nowhere"), "bottom-right");
    });

    test("stack from the edge they are on", () => {
        assert.equal(isTop("top-right"), true);
        assert.equal(isTop("bottom-center"), false);
    });
});

group("display times", () => {
    test("are longer with an action, and endless for a failure", () => {
        assert.equal(durationOf({ type: "success" }), DURATION);
        assert.equal(durationOf({ type: "warning", action: { label: "Annuler" } }), ACTION_DURATION);
        assert.equal(durationOf({ type: "danger" }), 0);
    });

    test("can be given, 0 keeping the toast until it is closed", () => {
        assert.equal(durationOf({ type: "danger", duration: 3000 }), 3000);
        assert.equal(durationOf({ type: "info", duration: 0 }), 0);
        assert.equal(durationOf({ type: "info", duration: -1 }), DURATION);
    });
});

group("queue", () => {
    const toast = (id, position = "bottom-right", key = null) => ({ id, position, key });

    test("puts a new toast last", () => {
        const { list, dropped } = enqueue([toast(1)], toast(2));

        assert.deepEqual(list.map((item) => item.id), [1, 2]);
        assert.deepEqual(dropped, []);
    });

    test("puts a toast in the place of the toast of the same key", () => {
        const { list, dropped } = enqueue([toast(1, "bottom-right", "operation-7"), toast(2)], toast(3, "bottom-right", "operation-7"));

        assert.deepEqual(list.map((item) => item.id), [3, 2]);
        assert.deepEqual(dropped.map((item) => item.id), [1]);
    });

    test("drops the oldest toast of a full place, and of that place only", () => {
        const { list, dropped } = enqueue([toast(1), toast(2, "top-left"), toast(3), toast(4)], toast(5), 3);

        assert.deepEqual(list.map((item) => item.id), [2, 3, 4, 5]);
        assert.deepEqual(dropped.map((item) => item.id), [1]);
    });
});

group("timer", () => {
    test("counts down from its start", () => {
        const timer = createTimer(5000, 1000);

        assert.equal(timer.remaining(3000), 3000);
        assert.equal(timer.remaining(9000), 0);
    });

    test("keeps the time left while paused, and goes on from it", () => {
        const timer = createTimer(5000, 0);

        timer.pause(2000);
        assert.equal(timer.paused, true);
        assert.equal(timer.remaining(60000), 3000);

        timer.resume(60000);
        assert.equal(timer.remaining(61000), 2000);
    });

    test("never ends with a duration of 0", () => {
        assert.equal(createTimer(0, 0).remaining(1e9), Infinity);
    });
});
