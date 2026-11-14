/**
 * Checks that every Tailwind CSS class of the project is written in its canonical form, as the
 * "suggestCanonicalClasses" warning of Tailwind CSS IntelliSense asks (`aspect-640/520`, not
 * `aspect-[640/520]`), on every file, open in an editor or not. Nothing is written.
 *
 * The design system is the project's own, loaded from resources/css/app.css (theme, plugins,
 * custom variants); the classes are the candidates that Tailwind itself finds in the views, the
 * scripts and the PHP classes. Only valid classes are checked: a word that merely looks like a
 * class is left alone.
 *
 * Prints each class to rewrite (`file:line  class → canonical`) and exits with 1 if there is any,
 * 0 otherwise. Usage: `npm run lint:css`.
 *
 * The design system comes from `__unstable__loadDesignSystem()`, an API that Tailwind may change:
 * a Tailwind update that breaks it makes this check fail, never pass silently.
 */

import { readFileSync } from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";
import { __unstable__loadDesignSystem } from "@tailwindcss/node";
import { Scanner } from "@tailwindcss/oxide";

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");
const cssPath = path.join(root, "resources/css/app.css");

/**
 * Files where the project writes its classes, as Tailwind globs from the project root.
 */
const SOURCES = [
    { base: path.join(root, "resources/views"), pattern: "**/*.blade.php" },
    { base: path.join(root, "resources/js"), pattern: "**/*.js" },
    { base: path.join(root, "app"), pattern: "**/*.php" },
];

/**
 * Root font size in pixels, as in Tailwind CSS IntelliSense: `mt-[16px]` becomes `mt-4`.
 */
const ROOT_FONT_SIZE = 16;

const designSystem = await __unstable__loadDesignSystem(readFileSync(cssPath, "utf8"), {
    base: path.dirname(cssPath),
});

const scanner = new Scanner({
    sources: SOURCES.map((source) => ({ ...source, negated: false })),
});

scanner.scan();

/**
 * Line number (from 1) of a byte position of a file: the scanner gives positions in bytes of the
 * UTF-8 content.
 *
 * @param {Buffer} bytes Content of the file.
 * @param {number} position Byte position.
 * @returns {number}
 */
function lineAt(bytes, position) {
    let line = 1;

    for (let index = bytes.indexOf(10); index !== -1 && index < position; index = bytes.indexOf(10, index + 1)) {
        line++;
    }

    return line;
}

/** @type {Map<string, string | null>} Canonical form of each class seen, null when it is not a class. */
const canonicalForms = new Map();

/**
 * Canonical form of a valid class, or null when the candidate is not a class of the design system.
 *
 * @param {string} candidate
 * @returns {string | null}
 */
function canonicalForm(candidate) {
    if (!canonicalForms.has(candidate)) {
        const isClass = designSystem.candidatesToCss([candidate])[0] !== null;

        canonicalForms.set(
            candidate,
            isClass ? designSystem.canonicalizeCandidates([candidate], { rem: ROOT_FONT_SIZE })[0] : null,
        );
    }

    return canonicalForms.get(candidate);
}

const findings = [];

for (const file of [...scanner.files].sort()) {
    const content = readFileSync(file, "utf8");
    const bytes = Buffer.from(content, "utf8");
    const seen = new Set();

    for (const { candidate, position } of scanner.getCandidatesWithPositions({
        content,
        extension: path.extname(file).slice(1),
    })) {
        const canonical = canonicalForm(candidate);

        if (canonical === null || canonical === candidate) {
            continue;
        }

        const line = lineAt(bytes, position);
        const key = `${line} ${candidate}`;

        if (!seen.has(key)) {
            seen.add(key);
            findings.push(`${path.relative(root, file).replaceAll("\\", "/")}:${line}  ${candidate} → ${canonical}`);
        }
    }
}

if (findings.length > 0) {
    console.log(findings.join("\n"));
    console.log(`\n${findings.length} classe(s) Tailwind à écrire sous leur forme canonique.`);
    process.exitCode = 1;
} else {
    console.log(`Classes Tailwind sous leur forme canonique (${scanner.files.length} fichiers lus).`);
}
