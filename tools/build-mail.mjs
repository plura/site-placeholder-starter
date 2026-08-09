#!/usr/bin/env node
/**
 * Replaces every {{TOKEN}} in mail-templates/, compiles the three contact templates with MJML,
 * and runs build-guard.js against the result — the full pipeline documented under "Compiling
 * the MJML" in docs/mail-templates.md, as one command instead of four.
 *
 *     npm run build:mail
 *     npm run build:mail -- --watch
 *
 * Token values come from mail-templates/tokens.json if present, otherwise from
 * mail-templates/tokens.example.json (the starter's own generic placeholder values — same ones
 * _header.mjml/_footer.mjml/_credit.mjml hardcoded before tokenization). Real per-project values
 * go in tokens.json, gitignored like config.php: copy tokens.example.json to tokens.json and
 * fill it in, same pattern as config.example.php -> config.php.
 *
 * mail-templates/ itself is never written to — tokens are replaced in a throwaway temp copy, so
 * the sources stay pristine and every installation recompiles from its own tokens.json rather than
 * accumulating hand-edits in place, which is what would make future starter updates (copying
 * mail-templates/ wholesale into an already-customized installation) turn into a diff-and-merge exercise.
 *
 * The .mjml sources cannot be previewed — they hold tokens, not values, so there is nothing for
 * a browser or the VS Code MJML extension to render correctly. The compiled output is the real
 * test point; --watch rebuilds it on every mail-templates/ change. See "Compiling the MJML" in
 * docs/mail-templates.md.
 */
import { readFile, writeFile, readdir, mkdtemp, rm, cp, access } from 'node:fs/promises';
import { watch } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, resolve, join } from 'node:path';
import { tmpdir } from 'node:os';
import { spawnSync } from 'node:child_process';

const ROOT = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const SRC = resolve(ROOT, 'mail-templates');
const OUT = resolve(ROOT, 'starter/app/templates');

const TEMPLATES = [
	{ src: 'contact/contact.mjml', out: 'contact.html' },
	{ src: 'contact/contact-reply.mjml', out: 'contact-reply.html' },
	{ src: 'contact/contact-reply.pt.mjml', out: 'contact-reply.pt.html' }, // Tier 2 only
];

/**
 * @param {string} path
 * @returns {Promise<boolean>}
 */
async function exists(path) {
	try { await access(path); return true; } catch { return false; }
}

/**
 * Derives CLIENT_PHONE_RAW from CLIENT_PHONE — strips everything but digits and a leading + —
 * rather than reading it from tokens.json, so there's one source of truth for the phone number,
 * not two that can drift out of sync (someone updates the display format and forgets the tel:
 * href, or types the digits-only form wrong the second time). See CLIENT_PHONE_RAW's own note
 * in _tokens.json.
 *
 * @param {Record<string, string>} values
 * @returns {Record<string, string>}
 */
function withDerivedTokens(values) {
	if (!values.CLIENT_PHONE) return values;
	return { ...values, CLIENT_PHONE_RAW: values.CLIENT_PHONE.replace(/[^\d+]/g, '') };
}

/**
 * Replaces every {{TOKEN}} occurrence across all .mjml files under a directory, in place.
 *
 * @param {string} dir
 * @param {Record<string, string>} values
 */
async function replaceTokens(dir, values) {
	const entries = await readdir(dir, { withFileTypes: true });
	for (const entry of entries) {
		const path = join(dir, entry.name);
		if (entry.isDirectory()) { await replaceTokens(path, values); continue; }
		if (!entry.name.endsWith('.mjml')) continue;
		let content = await readFile(path, 'utf8');
		for (const [key, value] of Object.entries(values)) {
			content = content.split(`{{${key}}}`).join(value);
		}
		await writeFile(path, content, 'utf8');
	}
}

/**
 * Runs the full replace-compile-guard pipeline once.
 *
 * @returns {Promise<boolean>} Whether every template compiled and build-guard passed.
 */
async function build() {
	const tokensPath = (await exists(join(SRC, 'tokens.json')))
		? join(SRC, 'tokens.json')
		: join(SRC, 'tokens.example.json');

	console.log(`Using tokens from ${tokensPath.endsWith('tokens.json') ? 'tokens.json' : 'tokens.example.json (no tokens.json yet)'}`);

	const values = withDerivedTokens(JSON.parse(await readFile(tokensPath, 'utf8')));

	const tmp = await mkdtemp(join(tmpdir(), 'mail-templates-'));
	let ok = true;
	try {
		await cp(SRC, tmp, { recursive: true });
		await replaceTokens(tmp, values);

		for (const { src, out } of TEMPLATES) {
			const srcPath = join(tmp, src);
			if (!(await exists(srcPath))) continue; // Tier 2 removed — skip, don't fail.

			// Shells out to the mjml CLI via npx rather than importing the mjml package as a
			// library — same as this project's original compile commands, so there's nothing to
			// `npm install`: npx resolves/caches mjml on first use like it always did.
			//
			// shell:true is required on Windows: npx resolves to npx.cmd, a batch launcher that
			// Node can only invoke through a shell, not as a direct child process (confirmed —
			// spawnSync('npx.cmd', ...) without it fails EINVAL here). Node warns that shell:true
			// stops argv from being escaped, but every argument below is either a fixed literal
			// or a path this script generated itself (mkdtemp/path.join) — never user input —
			// so there's nothing here for that warning to actually protect against.
			const result = spawnSync('npx', [
				'--yes', 'mjml', srcPath,
				'-o', join(OUT, out),
				'--config.allowIncludes', 'true',
				'--config.includePath', tmp,
				'--config.minify', 'true',
			], { stdio: 'inherit', shell: true });

			if (result.status !== 0) {
				ok = false;
				continue;
			}

			console.log(`Compiled ${out}`);
		}
	} finally {
		await rm(tmp, { recursive: true, force: true });
	}

	if (!ok) {
		console.error('\nCompile errors above — build-guard skipped.');
		return false;
	}

	const guard = spawnSync(process.execPath, [join(ROOT, 'mail-templates/build-guard.js'), OUT], { stdio: 'inherit' });
	return guard.status === 0;
}

const watchMode = process.argv.includes('--watch');

if (!watchMode) {
	process.exit((await build()) ? 0 : 1);
}

await build();
console.log(`\nWatching ${SRC} for changes — open starter/app/templates/contact.html (or contact-reply.html) in a browser to preview. Ctrl+C to stop.`);

// recursive:true is supported on Windows/macOS and modern Linux kernels; a burst of saves (an
// editor's own temp-file dance) is debounced into one rebuild rather than one per fs event.
let pending = null;
watch(SRC, { recursive: true }, () => {
	clearTimeout(pending);
	pending = setTimeout(async () => {
		console.log('\nRebuilding...');
		await build();
	}, 150);
});
