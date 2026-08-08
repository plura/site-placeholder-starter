# Dark / light mode

The site follows the visitor's OS/browser preference (`prefers-color-scheme`) by default, with
a manual toggle (top-right corner, `#theme-toggle` / `starter/assets/js/theme.js`) that overrides it and
persists the choice in `localStorage`. The contact modal re-themes too (a dark card in dark
mode), not just the page canvas — it doesn't stay fixed-light the way the email templates
deliberately do (see above); the website's modal is fully within our own CSS, so there's no
equivalent mail-client-dark-mode-mangling constraint forcing it to stay light-only.

- **Two independent color axes** in `starter/assets/css/base.css`: **page** tokens (`--site-bg`,
  `--site-fg`, `--site-muted`, `--site-border`) style the canvas; **surface** tokens
  (`--site-surface`, `--site-surface-fg`, `--site-surface-muted`, `--site-surface-label`) style
  the modal card. Kept separate because the modal doesn't always reuse the page's exact
  background/foreground pairing — e.g. `.btn-submit` deliberately inverts the surface tokens
  for its own fill/text, independent of what the page tokens are doing. `--site-color-accent`
  and `--site-danger` are shared constants, not split per axis.
- Each token has raw `--dark-*`/`--light-*` hex values defined once near the top of `:root`;
  `--site-*` then resolves to whichever set is active. **When doing BRAND CUSTOMIZATION, edit
  the raw `--dark-*`/`--light-*` values, not the `--site-*` lines** — the `--site-*` lines are
  the switching mechanism itself, not the palette.
- Switching precedence: an explicit `[data-theme="light"]`/`[data-theme="dark"]` attribute on
  `<html>` (set by the toggle) always wins; otherwise `@media (prefers-color-scheme: light)`
  applies the light palette; the unconditional `:root` default (no query needed) is dark.
- An early inline `<script>` in `index.html`'s `<head>` applies a stored `localStorage`
  override (if any) and updates the `theme-color` `<meta>` tag before first paint, to avoid a
  flash of the wrong theme. It must stay a plain synchronous script (no `defer`/`type="module"`)
  and must come *after* the `theme-color` meta tag in document order, since it looks that tag up
  by selector — moving it earlier silently breaks the meta-tag update (not the theme itself).
- The `<select>` arrow icon (`.form-group select`'s `background-image`) is a literal data-URI
  SVG, which can't reference CSS custom properties — its dark/light variants are spelled out as
  separate rules instead of tokenized. Doesn't matter unless a project actually adds a
  `<select>` to the contact form (none does by default).
- **Both the mode and the toggle are optional, independently of each other** — see "Optional
  features" below. Every block involved is marked `OPTIONAL (1/2)` (dark/light mode itself —
  auto-follows the OS/browser preference, no JS) or `OPTIONAL (2/2)` (the manual toggle layered
  on top of it). Keeping (1/2) without (2/2) is a valid, smaller combination (auto-only, no
  override button); removing both means a single (dark) theme.

