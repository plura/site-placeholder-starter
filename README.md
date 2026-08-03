# Site Placeholder Starter

A reusable starting point for Plura's single-page "coming soon" client sites: a static
placeholder page with a modal contact form, backed by a PHP handler that sends both a
notification email (to the site owner) and an auto-reply (to the submitter), using
MJML-authored HTML email templates.

Extracted from the Prevention Lab and Buscardini projects after building the same pattern
independently in both. Use this as the base for new projects (Cristina Mesquita, Sandra
Macieira, and future ones) instead of re-deriving it each time.

## What's genuinely reusable vs. what you must customize

**Copy as-is, rarely needs changes:**
- `app/submit.php` — field-agnostic: it loops raw POST data with no hardcoded field list,
  and reads human-readable labels from a `labels` JSON field the frontend builds live from
  the form's own `<label for="...">` elements (see `assets/js/modal.js`). Adding a field to
  the contact form (e.g. a company name) requires zero changes here. The one exception is
  `newsletter` — a reserved field name for the optional mailing-list opt-in checkbox (see
  "Optional features" below), which is deliberately excluded from the generic loop.
- `app/lib/phpmailer/` — vendored PHPMailer, never modified.
- The MJML **structural** patterns in `mail-templates/_partials/` — the responsive
  side-by-side/stacked field layout, the message quote-block, the full-bleed frame
  background. See "Mail template gotchas" below before changing these.

**Must customize per project:**
- `assets/css/base.css` — brand colors, fonts, spacing scale (all under one `:root` block,
  everything else in `layout.css`/`components.css` references these variables by name). Colors
  are dark/light-theme-reactive — see "Dark/light mode" below before editing them.
- `index.html` — logo/wordmark, copy, contact details, JSON-LD business info, meta tags,
  contact form fields beyond the universal name/email/phone/message.
- `mail-templates/_partials/_head.mjml` — brand colors for the email (kept intentionally
  separate from the website's own CSS variables — see below).
- `app/config.example.php` → `config.php` — SMTP credentials, `site_name` for email subjects.
- Favicons (`assets/favicons/`) and `assets/images/og.png` — not included; see the spec below.
- Bespoke entrance animations / canvas backgrounds, if wanted — these are genuinely
  per-client art direction, not part of this starter. Add them as their own JS modules and
  wire them up from `assets/js/main.js`.

## Why the email's colors and fonts are separate from the website's

The website CSS and the MJML email templates each have their own color tokens, deliberately
not shared. A client's actual site may be light or dark themed, but light-background email
cards read more reliably across mail clients that mangle dark-mode email — so the email
almost always wants its own, usually lighter, palette regardless of the site's theme. Set
both when customizing a project; don't assume one implies the other. In the starter's default
placeholder palette, this already lines up more than it might look at a glance: the frame/card
colors (`#eeeeee`/`#ffffff`) closely mirror the website's own light-theme tokens
(`--light-bg`/`--light-surface` in `assets/css/base.css`), and the accent blue (`#4a90d9`) is
an exact match with `--site-color-accent` — not a coincidence, kept in sync deliberately.

Fonts are separate for the same reliability reason, not shared with `assets/css/base.css`'s
`--site-font-serif`/`--site-font-sans` (Cardo/Outfit, loaded via Google Fonts): most mail
clients strip external font loading entirely, so `_head.mjml` uses system font stacks instead
(`Georgia, 'Times New Roman', serif` for headings, `Helvetica Neue, Helvetica, Arial,
sans-serif` for body text) — these render identically everywhere with zero load risk. They're
not a personality match for Cardo/Outfit (more neutral/classic than delicate-serif/
light-geometric-sans), and that's an intentional tradeoff, not an oversight: web-safe
alternatives closer to Cardo/Outfit's character (e.g. Baskerville, Century Gothic) have
materially worse Outlook/Windows support, trading real rendering reliability for a closer but
riskier aesthetic match. Don't try to load the website's Google Fonts into the email to "fix"
this — the structural language (uppercase tracked labels, thin dividers, solid uppercase
buttons) already carries the brand identity independent of the exact typeface.

## Dark/light mode

The site follows the visitor's OS/browser preference (`prefers-color-scheme`) by default, with
a manual toggle (top-right corner, `#theme-toggle` / `assets/js/theme.js`) that overrides it and
persists the choice in `localStorage`. The contact modal re-themes too (a dark card in dark
mode), not just the page canvas — it doesn't stay fixed-light the way the email templates
deliberately do (see above); the website's modal is fully within our own CSS, so there's no
equivalent mail-client-dark-mode-mangling constraint forcing it to stay light-only.

- **Two independent color axes** in `assets/css/base.css`: **page** tokens (`--site-bg`,
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

## Optional features

Everything ships "on" by default. Decide per project what's actually needed, then delete the
rest — every optional block is delimited with a matching marker (`<!-- OPTIONAL: ... -->` /
`<!-- /OPTIONAL -->` in HTML, `/* OPTIONAL: ... */` / `/* /OPTIONAL */` in CSS, a `// OPTIONAL:`
comment in PHP/JS, or a whole-file comment for files that are optional in their entirety) so
removal is a clean, complete delete rather than guesswork about what else goes with it.

Work through these questions before starting the "New project checklist" below:

1. **Does this project want the contact form (modal)?** This is the starter's core feature —
   assumed present. If not needed at all, remove `#contact-dialog` from `index.html`,
   `assets/js/modal.js` (and its import in `main.js`), `app/submit.php`, and the
   `mail-templates/contact/` + `app/templates/contact*.html` pair. `assets/js/post.js` is
   shared with the newsletter form — delete it only if that's going too.
2. **If yes — which fields?** Name + Email are the only two `submit.php` actually requires;
   Phone and Message are optional by default (see step 4 below for adding/removing rows).
3. **Does this project want the mailing-list opt-in checkbox on the contact form?** An
   optional checkbox (`name="newsletter"`) that, when checked, also subscribes the submitter
   via Mailchimp on successful contact submission. To remove: delete the marked block in
   `index.html`'s `#contact-form` and the marked block near the end of `app/submit.php`.
4. **Does this project want the standalone mailing-list signup?** A separate, inline (not
   modal) single-email-field section on the page — lower friction than a checkbox buried in
   a longer form, for a passive "leave your email" ask. Same pattern used on the Buscardini
   site. To remove: delete the marked `.newsletter` block in `index.html`, its styles in
   `components.css`, `assets/js/newsletter.js` and its import in `main.js`. `assets/js/post.js`
   stays as long as the contact form does.
5. **If keeping either mailing-list feature**, both use the **Mailchimp Marketing API**
   (double opt-in — Mailchimp sends its own confirmation email, this starter never does) via
   the shared `app/lib/mailchimp.php` helper. Fill in `mailchimp.api_key` / `mailchimp.list_id`
   in `config.php`. If neither mailing-list feature survives step 3/4, also delete
   `app/subscribe.php`, `app/lib/mailchimp.php`, and the `mailchimp` block in
   `config.example.php`/`config.php`. If a project needs a different ESP than Mailchimp,
   that's a rewrite of the one function in `app/lib/mailchimp.php`, not a new architecture.
6. **Does this project want dark/light mode at all?** See "Dark/light mode" above for the full
   mechanism. Two independently removable tiers, marked `OPTIONAL (1/2)` and `OPTIONAL (2/2)`:
   - Keep both for the default: auto-follows the OS/browser preference, with a manual toggle
     button that overrides it and persists the choice.
   - Keep only `OPTIONAL (1/2)` (delete every `OPTIONAL (2/2)` block, `#theme-toggle` in
     `index.html`, and `assets/js/theme.js` + its import) for auto-only — no toggle button, no
     JS at all, purely `prefers-color-scheme`-driven.
   - Delete both tiers entirely for a single (dark) theme — `--site-*` already resolves to
     `--dark-*` unconditionally, so nothing else needs to change once the switching blocks are
     gone.
7. **Does this project want a second language?** Ships on, with `pt/` as a worked example
   alongside the English root page. Most projects are single-language and should delete the
   whole tier — see "The second language" under "Language" below for the file list, and for
   the separate procedure when the default language should be Portuguese rather than English.

## New project checklist

1. Copy this whole repo as the starting point for the new client repo (or its `placeholder/`
   subfolder, if the target project also has a WordPress `theme/`/`plugin/` per the Plura
   lean repo structure). Before copying, note this starter's current commit
   (`git log -1 --format="%H %ad" --date=short`) and add a line near the top of the new
   project's own README: "Forked from site-placeholder-starter at commit `<hash>` (`<date>`)."
   Cheap now, saves real reconstruction work later if this starter changes and the new project
   ever wants to check what it might be missing — see the two examples this note format was
   validated against: `plura/site-sandramacieira` and `plura/site-preventionlab`.
2. **Decide the two languages before touching any copy** — they're independent, and getting
   this wrong means redoing work rather than adjusting it (see "Language" below):
   - What language does the **site** serve? Sets `$BASE` in `app/strings.php`, `index.html`,
     and — if a second language is kept — whether Tier 2's directory is `pt/` or `en/`.
   - What language does the **owner** read? Sets `$OWNER` in `app/strings.php` and the three
     chrome strings in `contact.mjml`. A Portuguese client with an English site is normal.
3. Decide on "Optional features" above and delete what's not needed.
4. `assets/css/base.css` — replace the color/font values, keep the variable names.
5. `index.html` — replace all placeholder text, URLs, JSON-LD fields (fill in `@type`,
   address, phone, socials — don't invent `openingHours`/`geo`/`priceRange` without real
   verified values), meta tags, favicon `<link>` paths.
6. Add real contact-form fields if needed beyond name/email/phone/message — copy a row+divider
   pair in `mail-templates/_partials/_fields.mjml` and rename both placeholders to the field's
   `name`. No `submit.php` changes, and no label text to keep in sync — the email takes its
   labels from the form's own `<label>` elements.
7. `mail-templates/_partials/_head.mjml` — replace the placeholder color palette (see the
   comment at the top of the file) and swap the header logo (`_header.mjml`) for a real
   `<mj-image>` once a public-facing logo PNG exists (never point it at `app/templates/` —
   that's `.htaccess`-locked and unreachable by email clients; put it under
   `assets/images/mail/`).
8. Compile the MJML (see below), then `cp app/config.example.php app/config.php` and fill
   in real SMTP credentials (and Mailchimp keys, if keeping either mailing-list feature).
9. Generate favicons and `assets/images/og.png` — see specs below.
10. Copy `.vscode/sftp.json.example` → `sftp.json`, fill in real host/credentials.
11. Update `robots.txt` / `sitemap.xml` / `site.webmanifest` with the real domain.

## Compiling the MJML

```
npx mjml mail-templates/contact/contact.mjml -o app/templates/contact.html --config.allowIncludes true --config.includePath . --config.minify true
npx mjml mail-templates/contact/contact-reply.mjml -o app/templates/contact-reply.html --config.allowIncludes true --config.includePath . --config.minify true
npx mjml mail-templates/contact/contact-reply.pt.mjml -o app/templates/contact-reply.pt.html --config.allowIncludes true --config.includePath . --config.minify true
```

Run from the repo root, after any `mail-templates/` edit. The third line is Tier 2 only — drop
it (and its source file) for a single-language site.

## Mail template gotchas (cost real debugging time — read before changing structure)

- `mj-class="x"` (pulls real attribute values from `<mj-class name="x">` in `_head.mjml`) is
  **not** the same as `css-class="x"` (just stamps a literal, unstyled HTML class name).
  Using the wrong one silently drops every color/padding/size with no error.
- A mobile media-query override on a section only works if it targets
  `.classname > table > tbody > tr > td` — MJML puts `css-class` on the outer `max-width`
  div, but the real padding lives on a nested `<td>` one level down.
- `mj-text` does **not** support `background-color`/`border-left` (only section/column/button
  do). The message quote-block uses a real CSS class (`<mj-style inline="inline">`) on a raw
  HTML `<td>` for this reason.
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
- VS Code's MJML preview extension is not a reliable proxy for the real compiled output —
  it appears to use its own, less complete rendering engine. If something looks broken only
  in that preview, verify against the actual compiled `app/templates/*.html` (open directly
  in a browser, or send a real test email) before treating it as a bug.

## Favicon spec

Design as just the icon/mark alone (not a full wordmark — illegible at 16–32px). Needed:

| File | Size | Notes |
|---|---|---|
| `assets/favicons/favicon-96x96.png` | 96×96 | transparent OK |
| `assets/favicons/apple-touch-icon.png` | 180×180 | **solid background, no transparency, no pre-rounded corners** — iOS rounds it automatically |
| `assets/favicons/favicon-192.png` | 192×192 | Android/PWA |
| `assets/favicons/favicon-512.png` | 512×512 | Android/PWA |

A `favicon.svg` is a nice-to-have (crisp at any size in supporting browsers) but optional.

## Deploying

Two patterns already in use across Plura projects, depending on what the host supports —
see the global CLAUDE.md for the full writeup of both:
- Plain SFTP/FTPS sync (most shared hosts).
- cPanel Git Version Control + a manually-triggered (`workflow_dispatch`, not `on: push`)
  GitHub Actions deploy, for hosts where that's set up (e.g. Buscardini).

## Language

The starter ships in English.

### Two independent axes

Language isn't one setting. **The site's language and the owner's language are separate**, and
conflating them is the mistake to avoid:

| | Follows | Files |
| --- | --- | --- |
| Page + auto-reply | **the visitor** | `index.html`, `pt/index.html`, `$BASE`/`$OVERRIDES` in `app/strings.php`, `contact-reply*.mjml` |
| Notification email | **the site owner** | `contact.mjml`, `_partials/_fields.mjml`, `$OWNER` in `app/strings.php` |

A Portuguese client running an English site gets an English page, English auto-replies to
visitors, and a **Portuguese** notification — because they're the only one reading it. That's
one template set to their language once at fork time, not a per-request variant.
`_fields.mjml` is pulled in by `contact.mjml` alone, so it follows the owner too.

The `%lang%` placeholder on the notification's date line shows which language version an
enquiry came from — the owner's only cue as to which language to reply in. It renders as
nothing on a single-language site.

### Where the copy lives

| Where | What |
| --- | --- |
| `index.html` / `pt/index.html` | All page copy, **including what the JS renders** — `data-submitting` and `data-network-error` attributes |
| `app/strings.php` | Everything the endpoints return, **including both email subject lines** — `$BASE`/`$OVERRIDES` follow the visitor, `$OWNER` doesn't |
| `mail-templates/` | Email copy, inline — recompile after editing |

**There is no JS dictionary.** `modal.js` and `newsletter.js` take their copy from the markup:
the idle button label is the button's own text, and the two strings with no natural home are
`data-` attributes. Each language is a separate HTML file, so a page already *is* its language
and there's nothing for JS to resolve. Every other message the user sees comes from the
server's own response — the JS only supplies `data-network-error`, for when a request never
completes and there is no response to read.

`app/strings.php` is the one dictionary, because a single endpoint serves every language
version. It holds a `$BASE` set plus an `$OVERRIDES` map listing only keys that differ. Keys
are semantic (`sent`), not the English source text used by gettext/`.po` — this copy gets
rewritten every project, and source-string keys go stale the moment one does.

### Changing the site's language

1. `index.html` — the copy, the `data-submitting` / `data-network-error` attributes, plus
   `<html lang>` and `og:locale`.
2. `app/strings.php` — translate `$BASE`. Don't miss `subject_notify` / `subject_reply`: the
   email subjects are built here, not in the templates.
3. `mail-templates/contact/contact-reply.mjml` — the visitor-facing auto-reply. Then recompile
   (see "Compiling the MJML"); `app/templates/*.html` is what PHP loads, so editing the MJML
   alone ships nothing.
4. `mail-templates/contact/contact.mjml` + `_partials/_fields.mjml` — **only if the owner's
   language is also changing.** See "Two independent axes" above.
5. `site.webmanifest` — only if `name`/`short_name` are language-dependent.

Leave alone: `+351` number formatting (including the `&zwnj;` treatment), `addressCountry`,
the postal code format, `Europe/Lisbon` in `submit.php`, and its `d/m/Y` date format. Those
track where the client is, not what language the site is in.

### Portuguese notification copy

For the common case of a Portuguese owner, the notification side is seven strings:

| File | English | Portuguese |
| --- | --- | --- |
| `contact.mjml` | `<mj-title>New enquiry` | `Novo contacto` |
| `contact.mjml` | `<mj-preview>New enquiry — Site Name` | `Novo contacto — Site Name` |
| `contact.mjml` | heading `New enquiry` | `Novo contacto` |
| `_fields.mjml` | `Name` | `Nome` |
| `_fields.mjml` | `Phone` | `Telefone` |
| `_fields.mjml` | `Message` | `Mensagem` |
| `app/strings.php` | `$OWNER['subject_notify']` | `'%s — novo contacto do site'` |

That last row is why `strings.php` has a separate `$OWNER` block. Everything in `$BASE` is
resolved against the *visitor's* language; `$OWNER` is applied afterwards and can't be
overridden, because the notification's subject has to match its body — which is in the owner's
language regardless of which version the enquiry came from.

Local dev: the page is static, so Live Server serves it as-is. `php -S localhost:8000` from the
repo root only if you need to exercise the endpoints — note it ignores `.htaccess`, so
`app/templates/` is readable locally but blocked in production.

### The second language (Tier 2)

Ships on, with Portuguese at `/pt/` as a worked example. **Most projects are single-language
and should delete the whole tier.** It consists of:

- `pt/` — a full copy of the root page. Static HTML has no include mechanism, so the `<head>`
  is duplicated wholesale. **Any structural change to `index.html` has to be mirrored there**
  (favicons, meta, the theme script, form fields, JSON-LD) or the two drift. Only copy, paths,
  and language metadata are meant to differ.
- The `.lang-switch` anchor in both pages, and the `.lang-switch` block in `components.css`.
- `<link rel="alternate" hreflang>` in both pages, and the `xhtml:link` entries in `sitemap.xml`.
- `$OVERRIDES` in `app/strings.php`.
- `mail-templates/contact/contact-reply.pt.mjml` → `app/templates/contact-reply.pt.html`, plus
  the marked block in `submit.php` that selects it.

Leave `data-app-base`, `.page-controls`, and the `%lang%` placeholder in place when removing the
tier — all three are correct, and inert, for a single root-level page.

**Adding a third language:** copy `pt/` to the new code, add its `$OVERRIDES` block, and add its
`hreflang` line to *every* page and to the sitemap. Every version must list every version, itself
included — a one-sided pairing is ignored outright.

### Changing the default language

Serving Portuguese at `/` and English at `/en/` is rearrangement, not deletion, and it touches
more than the copy:

1. **`app/strings.php`** — `$BASE` takes the new default language, `$OVERRIDES` takes the old
   one. Rename the override key (`pt` → `en`). Leave `$OWNER` alone unless the *owner's*
   language is changing too — it's a separate axis.
2. **The pages** — swap the copy between `index.html` and `pt/index.html`, including the
   `data-submitting` and `data-network-error` attributes, then rename the directory (`pt/` →
   `en/`). Its `data-app-base="../app/"` is unchanged; the root page keeps `app/`.
3. **Language metadata in both** — `<html lang>`, `og:locale`, `<link rel="canonical">`, and
   every `hreflang` href. `x-default` must point at whatever now sits at `/`.
4. **`sitemap.xml`** — same `x-default` rule, and the `<loc>` values swap.
5. **Mail templates** — the *unsuffixed* `contact-reply.mjml` is always the default language, so
   its copy and the suffixed file's swap over; rename the suffixed pair to the new non-default
   code and recompile both.
6. **The switcher** — label, `href`, `hreflang`, `lang`, and `aria-label` in both pages.

The trap in this direction is step 1: English key names with Portuguese values is fine, but a key
*missing* from `$OVERRIDES` silently falls back to `$BASE` — which is now Portuguese, so an
untranslated English page would render a Portuguese string. Submit both forms end to end after
the swap rather than eyeballing the pages; these strings only appear in responses.
