import { test } from "node:test";
import assert from "node:assert/strict";
import { matches, normalize } from "../../resources/js/axis-appearance-rules.js";

test("normalize drops case, accents and ligatures, and squeezes spaces", () => {
    assert.equal(normalize("  Échecs   Œuvre ÆTHER "), "echecs oeuvre aether");
});

test("a search matches when each of its words is in the text, in any order and case", () => {
    const text = "Cavalier échecs jeu de société chess-knight Jeux";

    assert.equal(matches(text, ""), true);
    assert.equal(matches(text, "   "), true);
    assert.equal(matches(text, "ECHEC"), true);
    assert.equal(matches(text, "jeu cavalier"), true);
    assert.equal(matches(text, "knight"), true);
    assert.equal(matches(text, "cavalier piano"), false);
});
