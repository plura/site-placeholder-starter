#!/usr/bin/env node
/**
 * Structural drift check between index.html and every language version beside it.
 *
 *     node tools/check-pages.mjs
 *
 * Static HTML has no include mechanism, so each language version duplicates the root page's
 * whole <head> and form markup. Nothing enforces that they stay in step — add a favicon link or
 * a form field to one and the others silently fall behind. This can't prevent that; it makes it
 * visible, which is the honest ceiling for duplicated static pages.
 *
 * Language directories are discovered, not hardcoded, so this stays correct for a
 * single-language project that deleted pt/ and for one that added a third language.
 *
 * It compares STRUCTURE, never content: which meta names exist, not what they say. Copy is
 * supposed to differ. Paths are normalized (pt/ reaches starter/assets one level up) and HTML comments
 * are stripped first, so a block commented out in both pages — Tier 2 while inactive — reads
 * as absent from both rather than as drift.
 *
 * Exits 1 on drift so it can gate a pre-commit hook.
 */
import { readFile, readdir, access } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const ROOT = resolve(dirname(fileURLToPath(import.meta.url)), '..');

/**
 * Every language version present, found rather than hardcoded, so adding a third language is
 * covered without editing this file. A language directory is a two-letter name at the repo root
 * holding an index.html — which matches pt/, en/, fr/ and can't match starter/app/, starter/assets/ or starter/custom/.
 *
 * @returns {Promise<string[]>} Directory names, e.g. ['pt'].
 */
async function languageDirs() {
    const entries = await readdir(ROOT, { withFileTypes: true });
    const found = [];

    for (const e of entries) {
        if (!e.isDirectory() || !/^[a-z]{2}$/.test(e.name)) continue;
        try {
            await access(resolve(ROOT, e.name, 'index.html'));
            found.push(e.name);
        } catch {
            // A two-letter directory without an index.html isn't a language version.
        }
    }

    return found.sort();
}

const stripComments = (html) => html.replace(/<!--[\s\S]*?-->/g, '');
const normalizePath = (p) => p.replace(/^(\.\.\/)+/, '');
const collect = (html, re, pick = (m) => m[1]) => [...html.matchAll(re)].map(pick);

/**
 * Each entry returns the set of structural signals of one kind. Anything whose value legitimately
 * differs per language (copy, canonical href, lang attribute) is deliberately absent.
 */
const SIGNALS = {
    'local <link> targets':   (h) => collect(h, /<link\b[^>]*\bhref="(?!https?:)([^"]+)"/g).map(normalizePath),
    'external <link> hosts':  (h) => collect(h, /<link\b[^>]*\bhref="(https?:\/\/[^/"]+)/g),
    '<meta name=> keys':      (h) => collect(h, /<meta\b[^>]*\bname="([^"]+)"/g),
    '<meta property=> keys':  (h) => collect(h, /<meta\b[^>]*\bproperty="([^"]+)"/g),
    'script sources':         (h) => collect(h, /<script\b[^>]*\bsrc="([^"]+)"/g).map(normalizePath),
    'element ids':            (h) => collect(h, /\bid="([^"]+)"/g),
    'form field names':       (h) => collect(h, /<(?:input|select|textarea|button)\b[^>]*\bname="([^"]+)"/g),
    'data- attributes':       (h) => collect(h, /\b(data-[a-z-]+)=/g),
    'stylesheet classes':     (h) => collect(h, /\bclass="([^"]+)"/g).flatMap((c) => c.split(/\s+/)),
};

const only = (a, b) => [...new Set(a)].filter((x) => !new Set(b).has(x)).sort();

const langs = await languageDirs();

if (!langs.length) {
    console.log('No language directories alongside index.html — nothing to compare.');
    process.exit(0);
}

const root = stripComments(await readFile(resolve(ROOT, 'index.html'), 'utf8'));

let drifted = 0;

for (const lang of langs) {
    const page = `${lang}/index.html`;
    const other = stripComments(await readFile(resolve(ROOT, page), 'utf8'));

    for (const [label, extract] of Object.entries(SIGNALS)) {
        const a = extract(root);
        const b = extract(other);
        const missingThere = only(a, b);
        const missingHere = only(b, a);

        if (!missingThere.length && !missingHere.length) continue;

        drifted++;
        console.log(`\n${page} — ${label}`);
        for (const x of missingThere) console.log(`  index.html only  ${x}`);
        for (const x of missingHere) console.log(`  ${page} only  ${x}`);
    }
}

if (drifted) {
    console.log(`\n${drifted} kind(s) of structural drift. Copy may differ; structure should not.`);
    process.exit(1);
}

console.log(`index.html and ${langs.map((l) => `${l}/index.html`).join(', ')} are structurally in step.`);
