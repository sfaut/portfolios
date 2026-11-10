/**
 * Tests des calculs de la photo de profil (resources/js/avatar-crop-rules.js), lancés par le
 * lanceur intégré à Node : npm run test:js.
 */
import assert from "node:assert/strict";
import { test } from "node:test";
import { cameraError, cameraSupport, centerSquare, clampOffset, clampZoom, MAX_ZOOM, minScale, sourceRect } from "../../resources/js/avatar-crop-rules.js";

test("the minimal scale fits the short side of the image in the circle", () => {
    assert.equal(minScale(1200, 800, 200), 0.25);
    assert.equal(minScale(800, 1200, 200), 0.25);
});

test("the zoom stays between 1 and the maximal zoom", () => {
    assert.equal(clampZoom(0.5), 1);
    assert.equal(clampZoom(2.5), 2.5);
    assert.equal(clampZoom(99), MAX_ZOOM);
    assert.equal(clampZoom(Number.NaN), 1);
});

test("a landscape image moves sideways only, as far as the circle stays covered", () => {
    // 1200 × 800 at 0.25: 300 × 200 shown, for a circle of 200.
    assert.deepEqual(clampOffset({ x: 80, y: 30 }, 1200, 800, 0.25, 200), { x: 50, y: 0 });
    assert.deepEqual(clampOffset({ x: -20, y: -30 }, 1200, 800, 0.25, 200), { x: -20, y: 0 });
});

test("a portrait image moves up and down only", () => {
    assert.deepEqual(clampOffset({ x: 30, y: -80 }, 800, 1200, 0.25, 200), { x: 0, y: -50 });
});

test("the kept square is the part of the image under the circle", () => {
    // Centred landscape image: the middle square.
    assert.deepEqual(sourceRect(1200, 800, 0.25, { x: 0, y: 0 }, 200), { x: 200, y: 0, size: 800 });
    // Image moved 50 px to the right: the circle shows its left part.
    assert.deepEqual(sourceRect(1200, 800, 0.25, { x: 50, y: 0 }, 200), { x: 0, y: 0, size: 800 });
    // Portrait image zoomed twice, moved 100 px up: a smaller square, lower in the image.
    assert.deepEqual(sourceRect(800, 1200, 0.5, { x: 0, y: -100 }, 200), { x: 200, y: 600, size: 400 });
});

test("the camera keeps the centre square of its video", () => {
    assert.deepEqual(centerSquare(1280, 720), { x: 280, y: 0, size: 720 });
    assert.deepEqual(centerSquare(720, 1280), { x: 0, y: 280, size: 720 });
    assert.deepEqual(centerSquare(640, 640), { x: 0, y: 0, size: 640 });
});

test("a camera that does not open says why, and offers to import an image", () => {
    assert.match(cameraError("NotAllowedError"), /^L'accès à la caméra est refusé\./);
    assert.equal(cameraError("SecurityError"), cameraError("NotAllowedError"));
    assert.equal(cameraError("NotFoundError"), "Aucune caméra n'est disponible.");
    assert.equal(cameraError("OverconstrainedError"), cameraError("NotFoundError"));
    assert.match(cameraError("NotReadableError"), /importez une image\.$/);
    assert.equal(cameraError(undefined), cameraError("NotReadableError"));
});

test("la caméra est proposée en contexte sécurisé, désactivée sans HTTPS, cachée sans getUserMedia", () => {
    assert.equal(cameraSupport(true, true), "available");
    assert.equal(cameraSupport(false, true), "insecure");
    assert.equal(cameraSupport(false, false), "insecure");
    assert.equal(cameraSupport(true, false), "unsupported");
});
