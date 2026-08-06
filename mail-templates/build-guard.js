#!/usr/bin/env node
// Fails the build if any build-time token survived into compiled output.
// Usage: node mail-templates/build-guard.js <dir-of-compiled-html>
const fs = require('fs');
const path = require('path');

const dir = process.argv[2];
if (!dir) { console.error('usage: node build-guard.js <dir>'); process.exit(2); }

const TOKEN = /\{\{\s*[A-Za-z0-9_]+\s*\}\}/g;
let failures = 0;

const walk = (d) => fs.readdirSync(d, { withFileTypes: true }).forEach((e) => {
	const p = path.join(d, e.name);
	if (e.isDirectory()) return walk(p);
	if (!/\.html?$/i.test(e.name)) return;
	const found = [...new Set(fs.readFileSync(p, 'utf8').match(TOKEN) || [])];
	if (found.length) { failures++; console.error(`FAIL ${p}\n      unreplaced: ${found.join(', ')}`); }
});

walk(dir);

if (failures) { console.error(`\n${failures} file(s) contain unreplaced tokens.`); process.exit(1); }
console.log('OK — no unreplaced tokens.');
