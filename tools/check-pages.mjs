#!/usr/bin/env node
/**
 * Structural drift check between index.html and pt/index.html.
 *
 *     node tools/check-pages.mjs
 *
 * Static HTML has no include mechanism, so the two language versions duplicate their whole
 * <head> and form markup. Nothing enforces that they stay in step — add a favicon link or a
 * form field to one and the other silently falls behind. This can't prevent that; it makes it
 * visible, which is the honest ceiling for duplicated static pages.
 *
 * It compares STRUCTURE, never content: which meta names exist, not what they say. Copy is
 * supposed to differ. Paths are normalized (pt/ reaches assets one level up) and HTML comments
 * are stripped first, so a block commented out in both pages — Tier 2 while inactive — reads
 * as absent from both rather than as drift.
 *
 * Exits 1 on drift so it can gate a pre-commit hook.
 */
import { readFile } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const ROOT = resolve(dirname(fileURLToPath(import.meta.url)), '..');

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

const [en, pt] = await Promise.all([
    readFile(resolve(ROOT, 'index.html'), 'utf8').then(stripComments),
    readFile(resolve(ROOT, 'pt/index.html'), 'utf8').then(stripComments),
]);

let drifted = 0;

for (const [label, extract] of Object.entries(SIGNALS)) {
    const a = extract(en);
    const b = extract(pt);
    const missingInPt = only(a, b);
    const missingInEn = only(b, a);

    if (!missingInPt.length && !missingInEn.length) continue;

    drifted++;
    console.log(`\n${label}`);
    for (const x of missingInPt) console.log(`  index.html only    ${x}`);
    for (const x of missingInEn) console.log(`  pt/index.html only ${x}`);
}

if (drifted) {
    console.log(`\n${drifted} kind(s) of structural drift. Copy may differ; structure should not.`);
    process.exit(1);
}

console.log('index.html and pt/index.html are structurally in step.');
