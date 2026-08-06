# Mail templates

## Why the email's colors and fonts are separate from the website's

The website CSS and the MJML email templates each have their own color tokens, deliberately
not shared. A client's actual site may be light or dark themed, but light-background email
cards read more reliably across mail clients that mangle dark-mode email — so the email
almost always wants its own, usually lighter, palette regardless of the site's theme. Set
both when customizing a project; don't assume one implies the other, and don't expect the
`{{BRAND_*}}` defaults (`mail-templates/_tokens.json`) to line up with `base.css`'s own
placeholder values — `BRAND_FRAME` (`#f4f3f1`) happens to sit close to the site's `--light-bg`
(`#f5f4f2`), but `BRAND_ACCENT` (`#3d5a80`, a muted slate blue) is nothing like `--site-color-accent`
(`#4a90d9`). That's fine — a real client's `BRAND_ACCENT` should be their own brand color anyway
(see `_colour_rules` in `_tokens.json` for the contrast floor it has to clear), not a value
borrowed from the website.

Fonts are separate for the same reliability reason, not shared with `starter/assets/css/base.css`'s
`--site-font-serif`/`--site-font-sans` (Cardo/Outfit, loaded via Google Fonts): most mail
clients strip external font loading entirely, so `_head.mjml` uses a single web-safe sans stack
by default (`Arial, Helvetica, sans-serif`, for both headings and body) — renders identically
everywhere with zero load risk. `BRAND_FONT_HEADING` can be set to `Georgia, 'Times New Roman',
serif` for editorial or creative clients who want a heading/body split; web-safe alternatives
closer to Cardo/Outfit's actual character (e.g. Baskerville, Century Gothic) have materially
worse Outlook/Windows support, trading real rendering reliability for a closer but riskier
aesthetic match, so this starter doesn't reach for those. Don't try to load the website's
Google Fonts into the email to "fix" this — the structural language (uppercase tracked labels,
thin dividers, the accent flag) already carries the brand identity independent of the exact
typeface.

## Compiling the MJML

```
npm run build:mail
```

Run from the repo root, after any `mail-templates/` edit (`npm install` once first). It's
`tools/build-mail.mjs`: replaces every `{{TOKEN}}` in a throwaway copy of `mail-templates/`
(never the sources themselves — see the comment at the top of that file for why), compiles the
three contact templates with MJML into `starter/app/templates/`, and finishes by running
`node mail-templates/build-guard.js starter/app/templates` — which fails the build if any
`{{TOKEN}}` survived, the only thing that actually confirms nothing was missed. `%field%`
runtime placeholders are untouched throughout; only `submit.php` ever fills those, on every send.

Token values come from `mail-templates/tokens.json` if it exists, otherwise from
`mail-templates/tokens.example.json` — the starter's own generic placeholder values, so this
command produces working (if genuinely placeholder) output with zero setup. Create `tokens.json`
(step 7 of the checklist in the [README](../README.md)) once there's a real client to compile for.

If `mail-templates/contact/contact-reply.pt.mjml` was removed (single-language project, see
"Removing it" in [docs/language.md](language.md)), the script skips it rather than failing —
Tier 2 is optional, not required.

There is no way to preview `mail-templates/*.mjml` directly, in a browser or in the VS Code MJML
extension — the source deliberately has no color/font values, only tokens, so it has nothing to
render correctly. The compiled `starter/app/templates/*.html` this script produces is the real
test point; open one of those files directly in a browser. `npm run build:mail -- --watch`
rebuilds on every `mail-templates/` change, so that file can just be left open with a browser
auto-refresh extension (e.g. VS Code's Live Preview) pointed at it.

Prefer to see the exact MJML CLI invocations instead (for debugging a compile that `build:mail`
doesn't explain well enough on its own)? They're spelled out in `tools/build-mail.mjs`'s use of
the `mjml` package — same compile, just wired to run programmatically against the temp copy
rather than shelled out three times.

## Mail template gotchas (cost real debugging time — read before changing structure)

- `mj-class="x"` (pulls real attribute values from `<mj-class name="x">` in `_head.mjml`) is
  **not** the same as `css-class="x"` (just stamps a literal, unstyled HTML class name).
  Using the wrong one silently drops every color/padding/size with no error.
- A mobile media-query override on a section only works if it targets
  `.classname > table > tbody > tr > td` — MJML puts `css-class` on the outer `max-width`
  div, but the real padding lives on a nested `<td>` one level down.
- `mj-text` does **not** support `background-color`/`border-left` (only section/column/button
  do) — reach for a real CSS class via `<mj-style inline="inline">` on a raw HTML `<td>` if a
  block of text genuinely needs its own fill or side border.
- `mj-wrapper` cannot nest inside another `mj-wrapper` — only inside `mj-attributes`/`mj-body`.
  One wrapper per page, full stop. The card's border+radius box (`card-body` through
  `field-row(-last)`) is built from matching `border-left`/`border-right` on every section in
  the group instead, with `border-top`+top radius on the first section and the dark
  `footer-band` fill (own bottom radius, no border needed) closing the bottom — see
  `_head.mjml`. It's more moving parts than a single wrapper, but it's the only legal way to
  group several sibling sections under what reads as one continuous box. The MJML CLI's error
  message names the line if this regresses; the VS Code MJML preview extension does **not**
  reliably catch it (see below) — always confirm against a real `npx mjml` compile.
- MJML ships HTML `<!-- -->` comments verbatim into the compiled output — including comments
  inside an `<mj-include>`d partial, which land in every file that includes it. A comment that
  spells out a literal `{{LIKE_THIS}}` example (e.g. documenting the token syntax) becomes an
  unreplaced-looking token in the compiled HTML, and `build-guard.js`'s regex will flag it as a
  real failure; write example syntax descriptively ("double-curly UPPER_SNAKE") instead. Same
  problem, different mechanism, for a `%field%`-shaped mention in a comment explaining why some
  field is *absent* from a template — `strtr()` in `submit.php` will substitute it if that field
  happens to be one it's filling that send, and even where it doesn't, it makes the template
  look (to a human grepping the compiled output, or to a future contributor skimming it) like it
  still uses a placeholder it deliberately doesn't. Bitten by both shapes of this while writing
  the redesign — worth checking any comment near a placeholder-heavy section before it ships.
- Raw HTML spliced via `<mj-raw>` into an existing column must be a bare `<tr><td>` — never
  a full `<table>`. A column's content shares one `<tbody>`, and `<table>` is not a valid
  direct child of `<tbody>`; browsers "fix" this via foster parenting, silently relocating
  the content and breaking the layout around it. If a block needs its own independent
  column grid (different width ratios than sibling rows), use `<mj-table>` instead — it
  properly wraps its content in an isolated `<tr><td><table>...</table></td></tr>`.
- Similarly, `mj-section` cannot nest inside `mj-column` — for a genuinely responsive
  side-by-side/stacked field layout, each field is its own top-level `mj-section` with two
  `mj-column`s (30%/70%), not a shared raw HTML table. MJML's own column system compiles to
  real `<div>`s with CSS-class-based widths (no legacy `width=` HTML attribute to conflict
  with a mobile override) and already auto-generates the mobile-first stacking media query —
  this is far more reliable across real clients (Gmail's app especially) than any hand-rolled
  `display:block` table-cell trick.
- `mj-wrapper`/`mj-section` `background-color` only bleeds as wide as its nearest
  width-constrained ancestor allows. For a true full-bleed page background regardless of
  viewport width, set `background-color` on `<mj-body>` itself (via `mj-attributes`) — that's
  the one thing that paints the real, unconstrained `<body>` tag.
- Gmail's mobile **app** is known to be inconsistent about honoring
  `<meta name="color-scheme" content="light">` / `supported-color-schemes` — it may still
  auto-dark-mode-invert the email. An unofficial `data-ogsc`/`data-ogsb` override trick
  exists but has been a deliberate skip so far (unofficial, inconsistent, real markup cost).
- Any image referenced by URL in an email (logo, etc.) must be public — never point it at a
  path covered by an `.htaccess: Deny from all`; email clients fetch it externally.
- `_footer.mjml`'s `{{CLIENT_EMAIL}}`/`{{CLIENT_PHONE}}` are **not** hardened against Gmail's
  auto-detect-and-relink behavior (it re-styles phone numbers/emails with its own blue link
  color, ignoring inline anchor styles — the usual fix is a `&zwnj;` between characters to
  break the pattern match). That fix can't live in the token source: `{{CLIENT_EMAIL}}` is one
  opaque token reused for both the `mailto:` href and the display text, and splicing `&zwnj;`
  into an href would break it. If this matters for a given send volume, it has to happen in
  whatever process substitutes the token values — inserting `&zwnj;` into the *display* copy
  only, after replacement, never into the href.
- VS Code's MJML preview extension is not a reliable proxy for the real compiled output —
  it appears to use its own, less complete rendering engine. If something looks broken only
  in that preview, verify against the actual compiled `starter/app/templates/*.html` (open directly
  in a browser, or send a real test email) before treating it as a bug.
