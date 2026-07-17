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
  the contact form (e.g. a phone number) requires zero changes here.
- `app/lib/phpmailer/` — vendored PHPMailer, never modified.
- The MJML **structural** patterns in `mail-templates/_partials/` — the responsive
  side-by-side/stacked field layout, the message quote-block, the full-bleed frame
  background. See "Mail template gotchas" below before changing these.

**Must customize per project:**
- `assets/css/base.css` — brand colors, fonts, spacing scale (all under one `:root` block,
  everything else in `layout.css`/`components.css` references these variables by name).
- `index.html` — logo/wordmark, copy, contact details, JSON-LD business info, meta tags,
  contact form fields beyond the universal name/email/message.
- `mail-templates/_partials/_head.mjml` — brand colors for the email (kept intentionally
  separate from the website's own CSS variables — see below).
- `app/config.example.php` → `config.php` — SMTP credentials, `site_name` for email subjects.
- Favicons (`assets/favicons/`) and `assets/images/og.png` — not included; see the spec below.
- Bespoke entrance animations / canvas backgrounds, if wanted — these are genuinely
  per-client art direction, not part of this starter. Add them as their own JS modules and
  wire them up from `assets/js/main.js`.

## Why the email's colors are separate from the website's

The website CSS and the MJML email templates each have their own color tokens, deliberately
not shared. A client's actual site may be light or dark themed, but light-background email
cards read more reliably across mail clients that mangle dark-mode email — so the email
almost always wants its own, usually lighter, palette regardless of the site's theme. Set
both when customizing a project; don't assume one implies the other.

## New project checklist

1. Copy this whole repo as the starting point for the new client repo (or its `placeholder/`
   subfolder, if the target project also has a WordPress `theme/`/`plugin/` per the Plura
   lean repo structure).
2. `assets/css/base.css` — replace the color/font values, keep the variable names.
3. `index.html` — replace all placeholder text, URLs, JSON-LD fields (fill in `@type`,
   address, phone, socials — don't invent `openingHours`/`geo`/`priceRange` without real
   verified values), meta tags, favicon `<link>` paths.
4. Add real contact-form fields if needed beyond name/email/message — copy a row from
   `mail-templates/_partials/_fields.mjml`, no `submit.php` changes required. Make sure the
   `<label for="...">` text in `index.html` matches what should appear in the email.
5. `mail-templates/_partials/_head.mjml` — replace the placeholder color palette (see the
   comment at the top of the file) and swap the header logo (`_header.mjml`) for a real
   `<mj-image>` once a public-facing logo PNG exists (never point it at `app/templates/` —
   that's `.htaccess`-locked and unreachable by email clients; put it under
   `assets/images/mail/`).
6. Compile the MJML (see below), then `cp app/config.example.php app/config.php` and fill
   in real SMTP credentials.
7. Generate favicons and `assets/images/og.png` — see specs below.
8. Copy `.vscode/sftp.json.example` → `sftp.json`, fill in real host/credentials.
9. Update `robots.txt` / `sitemap.xml` / `site.webmanifest` with the real domain.

## Compiling the MJML

```
npx mjml mail-templates/contact/contact.mjml -o app/templates/contact.html --config.allowIncludes true --config.includePath . --config.minify true
npx mjml mail-templates/contact/contact-reply.mjml -o app/templates/contact-reply.html --config.allowIncludes true --config.includePath . --config.minify true
```

Run from the repo root, after any `mail-templates/` edit.

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

## i18n

Not built in here — buscardini has a working `t()` / `lang.php` dictionary pattern
(English keys, `pt` as the default rendered language) worth reusing if/when a project
actually needs a second language. Don't add translation infrastructure speculatively;
it's cheap to add once a project commits to it, and premature dictionaries tend to guess
wrong about the URL/routing strategy anyway.
