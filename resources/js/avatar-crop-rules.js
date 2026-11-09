/**
 * Calculs de la photo de profil (recadrage, prise de vue), sans DOM ni Alpine, pour que Node les teste
 * (tests/js/avatar-crop-rules.test.js).
 *
 * L'image se place sur la scène par son échelle (pixels affichés par pixel de l'image) et par le
 * décalage de son centre par rapport au centre de la scène, en pixels affichés. Le cercle de
 * recadrage, de diamètre `diameter`, est centré sur la scène : l'image doit toujours le couvrir.
 */

/**
 * Zoom maximal, en multiple de l'échelle minimale.
 */
export const MAX_ZOOM = 4;

/**
 * Échelle minimale : celle où le petit côté de l'image remplit juste le cercle.
 *
 * @param {number} width Largeur de l'image, en pixels.
 * @param {number} height Hauteur de l'image, en pixels.
 * @param {number} diameter Diamètre du cercle, en pixels affichés.
 * @returns {number}
 */
export function minScale(width, height, diameter) {
    return diameter / Math.min(width, height);
}

/**
 * Borne le zoom entre 1 (échelle minimale) et MAX_ZOOM.
 *
 * @param {number} zoom
 * @returns {number}
 */
export function clampZoom(zoom) {
    return Math.min(MAX_ZOOM, Math.max(1, Number.isFinite(zoom) ? zoom : 1));
}

/**
 * Borne le décalage de l'image pour qu'elle couvre toujours le cercle.
 *
 * @param {{x: number, y: number}} offset Décalage voulu du centre de l'image.
 * @param {number} width Largeur de l'image.
 * @param {number} height Hauteur de l'image.
 * @param {number} scale Échelle de l'image.
 * @param {number} diameter Diamètre du cercle.
 * @returns {{x: number, y: number}}
 */
export function clampOffset(offset, width, height, scale, diameter) {
    const limitX = Math.max(0, (width * scale - diameter) / 2);
    const limitY = Math.max(0, (height * scale - diameter) / 2);
    const clamp = (value, limit) => Math.min(limit, Math.max(-limit, value)) + 0;

    return { x: clamp(offset.x, limitX), y: clamp(offset.y, limitY) };
}

/**
 * Carré de l'image visible dans le cercle, en pixels de l'image : la partie à garder.
 *
 * @param {number} width Largeur de l'image.
 * @param {number} height Hauteur de l'image.
 * @param {number} scale Échelle de l'image.
 * @param {{x: number, y: number}} offset Décalage du centre de l'image.
 * @param {number} diameter Diamètre du cercle.
 * @returns {{x: number, y: number, size: number}}
 */
export function sourceRect(width, height, scale, offset, diameter) {
    const size = diameter / scale;

    return {
        x: width / 2 - offset.x / scale - size / 2,
        y: height / 2 - offset.y / scale - size / 2,
        size,
    };
}

/**
 * Carré central d'une image, en pixels de l'image : la partie de la vidéo de la caméra que montre la
 * scène carrée (object-cover), et que garde la prise de vue.
 *
 * @param {number} width Largeur de l'image.
 * @param {number} height Hauteur de l'image.
 * @returns {{x: number, y: number, size: number}}
 */
export function centerSquare(width, height) {
    const size = Math.min(width, height);

    return { x: (width - size) / 2, y: (height - size) / 2, size };
}

/**
 * Message à afficher quand la caméra ne s'ouvre pas, selon le nom de l'erreur de getUserMedia.
 *
 * @param {string|undefined} name Nom de l'erreur (DOMException.name).
 * @returns {string}
 */
export function cameraError(name) {
    switch (name) {
        case "NotAllowedError":
        case "SecurityError":
            return "L'accès à la caméra est refusé. Autorisez-le dans les réglages du navigateur, ou importez une image.";
        case "NotFoundError":
        case "OverconstrainedError":
            return "Aucune caméra n'est disponible.";
        default:
            return "La caméra ne répond pas. Réessayez, ou importez une image.";
    }
}
