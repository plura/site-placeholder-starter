# Site Placeholder Starter

A reusable starting point for Plura's single-page "coming soon" client sites: a static
placeholder page with a modal contact form, backed by a PHP handler that sends both a
notification email (to the site owner) and an auto-reply (to the submitter), using
MJML-authored HTML email templates.

Extracted from the Prevention Lab and Buscardini projects after building the same pattern
independently in both — the shapes here were converged on twice before being pulled out, not
invented once. Use it as the base for new projects instead of re-deriving it each time.

## What's genuinely reusable vs. what you must customize

**Copy as-is, rarely needs changes:**
- `starter/app/submit.php` — field-agnostic: it loops raw POST data with no hardcoded field list,
  and reads human-readable labels from a `labels` JSON field the frontend builds live from
  the form's own `<label for="...">` elements (see `starter/assets/js/modal.js`). Adding a field to
  the contact form (e.g. a company name) requires zero changes here. The one exception is
  `newsletter` — a reserved field name for the optional mailing-list opt-in checkbox (see
  "Optional features" below), which is deliberately excluded from the generic loop.
- `starter/app/lib/phpmailer/` — vendored PHPMailer, never modified.
- The MJML **structural** patterns in `mail-templates/_partials/` — the responsive
  side-by-side/stacked field grid, the card's border/radius construction, the full-bleed frame
  background. See "Mail template gotchas" in [docs/mail-templates.md](docs/mail-templates.md)
  before changing these.

**Must customize per project:**
- `starter/assets/css/base.css` — brand colors, fonts, spacing scale (all under one `:root` block,
  everything else in `layout.css`/`components.css` references these variables by name). Colors
  are dark/light-theme-reactive — see "Dark/light mode" below before editing them. The
  webfonts themselves load via `<link>` tags in each page's `<head>`, so changing typeface
  means editing those *and* the two `--site-font-` values here.
- `index.html` — logo/wordmark, copy, contact details, JSON-LD business info, meta tags,
  contact form fields beyond the universal name/email/phone/message.
- `mail-templates/_tokens.json` — every `{{CLIENT_*}}`/`{{BRAND_*}}` build-time token used across
  `mail-templates/`, with defaults and notes. Brand colors for the email are kept intentionally
  separate from the website's own CSS variables — see below.
- `starter/app/config.example.php` → `config.php` — SMTP credentials, `site_name` for email subjects.
- Favicons (`starter/assets/favicons/`) and `starter/assets/images/og.png` — not included; see the spec below.
- Bespoke entrance animations / canvas backgrounds, if wanted — these are genuinely
  per-client art direction, not part of this starter. They go in `starter/custom/`, wired up from
  `starter/assets/js/main.js`; see `starter/custom/README.md` for the convention and, more importantly, for
  what does *not* belong there.

## Mail templates

The MJML email templates have their own color/font tokens, deliberately separate from the
website's own CSS, and their own compile step (`npm run build:mail`). Two things worth knowing
before touching `mail-templates/`, both covered in **[docs/mail-templates.md](docs/mail-templates.md)**:

- **Colors and fonts don't follow the website's.** Light-background email cards read more
  reliably than dark ones across mail clients that mangle dark-mode email, so the email keeps
  its own, usually lighter, palette and web-safe font stacks regardless of the site's theme.
- **A handful of MJML quirks cost real debugging time if you don't know them going in** —
  `mj-class` vs `css-class`, `mj-wrapper`'s one-wrapper-per-page limit, a token/HTML-comment
  collision that fools `build-guard.js`, and more.

`docs/mail-templates.md` has the full picture: the color/font rationale, the complete compile
pipeline (`tools/build-mail.mjs`, `tokens.json` vs `tokens.example.json`, `build-guard.js`), and
every MJML gotcha found so far.

## Dark/light mode

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

## Optional features

Everything ships "on" by default. Decide per project what's actually needed, then delete the
rest — every optional block is delimited with a matching marker (`<!-- OPTIONAL: ... -->` /
`<!-- /OPTIONAL -->` in HTML, `/* OPTIONAL: ... */` / `/* /OPTIONAL */` in CSS, a `// OPTIONAL:`
comment in PHP/JS, or a whole-file comment for files that are optional in their entirety) so
removal is a clean, complete delete rather than guesswork about what else goes with it.

Work through these questions before starting the "New project checklist" below:

1. **Does this project want the contact form (modal)?** This is the starter's core feature —
   assumed present. If not needed at all, remove `#contact-dialog` from `index.html`,
   `starter/assets/js/modal.js` (and its import in `main.js`), `starter/app/submit.php`, and the
   `mail-templates/contact/` + `starter/app/templates/contact*.html` pair. `starter/assets/js/post.js` is
   shared with the newsletter form — delete it only if that's going too.
2. **If yes — which fields?** Name + Email are the only two `submit.php` actually requires;
   Phone and Message are optional by default (see step 4 below for adding/removing rows).
3. **Does this project want the mailing-list opt-in checkbox on the contact form?** An
   optional checkbox (`name="newsletter"`) that, when checked, also subscribes the submitter
   to the mailing list on successful contact submission. To remove: delete the marked block in
   `index.html`'s `#contact-form` and the marked block near the end of `starter/app/submit.php`.
4. **Does this project want the standalone mailing-list signup?** A separate, inline (not
   modal) single-email-field section on the page — lower friction than a checkbox buried in
   a longer form, for a passive "leave your email" ask. Same pattern used on the Buscardini
   site. To remove: delete the marked `.newsletter` block in `index.html`, its styles in
   `components.css`, `starter/assets/js/newsletter.js` and its import in `main.js`. `starter/assets/js/post.js`
   stays as long as the contact form does.
5. **If keeping either mailing-list feature**, both go through `newsletter_subscribe()` in
   `starter/app/lib/newsletter.php`, which dispatches to the provider named in `config.php`'s
   `newsletter.provider` — **`brevo`** or **`mailchimp`**, implemented in `lib/newsletter/`.
   Fill in `newsletter.api_key` / `newsletter.list_id` too.

   The kit only *adds addresses to the list*; it never sends campaigns. Those are the client's
   to run in the provider's own tools, so the deciding factor is usually whichever provider the
   client already uses. Brevo is the default: EU-hosted, which is an easier conversation for a
   client in a regulated sector than a US-based processor.

   **Double opt-in differs and the shipped copy assumes it doesn't happen.** Mailchimp always
   double opt-ins (`status: 'pending'`, it sends its own confirmation email). Brevo only does
   once a DOI template is configured in the account — the API call here doesn't trigger one. So
   `subscribe_confirm` in `strings.php` deliberately says just "Thanks for subscribing."; add
   the "check your inbox" line only once the project's provider actually sends a confirmation.

   If neither mailing-list feature survives step 3/4, also delete `starter/app/subscribe.php`,
   `starter/app/lib/newsletter.php`, the whole `starter/app/lib/newsletter/` folder, and the
   `newsletter` block in `config.example.php`/`config.php`. Adding a third provider is one file
   in `lib/newsletter/` plus one `case` in the dispatcher — nothing else changes.
6. **Does this project want dark/light mode at all?** See "Dark/light mode" above for the full
   mechanism. Two independently removable tiers, marked `OPTIONAL (1/2)` and `OPTIONAL (2/2)`:
   - Keep both for the default: auto-follows the OS/browser preference, with a manual toggle
     button that overrides it and persists the choice.
   - Keep only `OPTIONAL (1/2)` (delete every `OPTIONAL (2/2)` block, `#theme-toggle` in
     `index.html`, and `starter/assets/js/theme.js` + its import) for auto-only — no toggle button, no
     JS at all, purely `prefers-color-scheme`-driven.
   - Delete both tiers entirely for a single (dark) theme — `--site-*` already resolves to
     `--dark-*` unconditionally, so nothing else needs to change once the switching blocks are
     gone.
7. **Does this project want a second language?** Ships **built but inactive** — `pt/` exists as
   a working Portuguese page, but it's `noindex` and nothing links to or declares it, so a
   single-language project can simply ignore it. Unlike the other optional features, forgetting
   this one is harmless rather than harmful, which is why it isn't on by default. See "The
   second language" under "Language" below for the activation checklist, the removal steps if
   you're sure, and the separate procedure for making Portuguese the default rather than English.

## New project checklist

1. Copy this whole repo as the starting point for the new client repo (or its `placeholder/`
   subfolder, if the target project also has a WordPress `theme/`/`plugin/` per the Plura
   lean repo structure). Before copying, note this starter's current commit
   (`git log -1 --format="%H %ad" --date=short`) and add a line near the top of the new
   project's own README: "Forked from site-placeholder-starter at commit `<hash>` (`<date>`)."
   Cheap now, saves real reconstruction work later if this starter changes and the new project
   ever wants to check what it might be missing. Then add that project to "Projects built from
   this" above — the note records where the fork came from, the list records who needs telling
   when something changes here, and neither substitutes for the other. See "What a project's own
   README should say" for what else belongs in it, and what deliberately doesn't.
2. **Decide the two languages before touching any copy** — they're independent, and getting
   this wrong means redoing work rather than adjusting it (see "Language" below):
   - What language does the **site** serve? Sets `$BASE` in `starter/app/strings.php`, `index.html`,
     and — if a second language is kept — whether Tier 2's directory is `pt/` or `en/`.
   - What language does the **owner** read? Sets `$OWNER` in `starter/app/strings.php`, the
     chrome strings in `contact.mjml`, and the `form_type` hidden field's value in **both**
     `index.html` and `pt/index.html` (same value in both — see "Portuguese notification copy"
     in [docs/language.md](docs/language.md)). A Portuguese client with an English site is normal.
3. Decide on "Optional features" above and delete what's not needed.
4. `starter/assets/css/base.css` — replace the color/font values, keep the variable names.
5. `index.html` — replace all placeholder text, URLs, JSON-LD fields (fill in `@type`,
   address, phone, socials — don't invent `openingHours`/`geo`/`priceRange` without real
   verified values), meta tags, favicon `<link>` paths.
6. Add real contact-form fields if needed beyond name/email/phone/message — copy a row+divider
   pair in `mail-templates/contact/_partials/_fields.mjml` and rename both placeholders to the field's
   `name`. No `submit.php` changes, and no label text to keep in sync — the email takes its
   labels from the form's own `<label>` elements.
7. `cp mail-templates/tokens.example.json mail-templates/tokens.json` (gitignored, same pattern
   as `config.example.php` → `config.php`) and fill in the client's real `{{CLIENT_*}}`/
   `{{BRAND_*}}` values — see `mail-templates/_tokens.json` for the full list, defaults, and
   rules (contrast floors, the derived `CLIENT_PHONE_RAW`). Swap the header wordmark
   (`_header.mjml`) for a real `<mj-image>` once a public-facing logo PNG exists (never point it
   at `starter/app/templates/` — that's `.htaccess`-locked and unreachable by email clients; put
   it under `starter/assets/images/mail/`).
8. `npm install`, then `npm run build:mail` — replaces the tokens, compiles the MJML, and runs
   `build-guard.js`, all in one step (see [docs/mail-templates.md](docs/mail-templates.md)). Then
   `cp starter/app/config.example.php starter/app/config.php` and fill in real SMTP credentials
   (and the `newsletter` provider/keys, if keeping either mailing-list feature).
9. Generate favicons and `starter/assets/images/og.png` — see specs below.
10. Copy `.vscode/sftp.json.example` → `sftp.json`, fill in real host/credentials.
11. Update `robots.txt` / `sitemap.xml` / `site.webmanifest` with the real domain.

## Favicon spec

Design as just the icon/mark alone (not a full wordmark — illegible at 16–32px). Needed:

| File | Size | Notes |
|---|---|---|
| `starter/assets/favicons/favicon-96x96.png` | 96×96 | transparent OK |
| `starter/assets/favicons/apple-touch-icon.png` | 180×180 | **solid background, no transparency, no pre-rounded corners** — iOS rounds it automatically |
| `starter/assets/favicons/favicon-192.png` | 192×192 | Android/PWA |
| `starter/assets/favicons/favicon-512.png` | 512×512 | Android/PWA |

A `favicon.svg` is a nice-to-have (crisp at any size in supporting browsers) but optional.

## Deploying

Two patterns already in use across Plura projects, depending on what the host supports —
see the global CLAUDE.md for the full writeup of both:
- Plain SFTP/FTPS sync (most shared hosts).
- cPanel Git Version Control + a manually-triggered (`workflow_dispatch`, not `on: push`)
  GitHub Actions deploy, where the host supports it.

**Before the first sync, run `npm run check:config`.** It fails if the markup offers a feature
`config.php` can't serve — a contact form with no SMTP, or a newsletter signup whose provider or
credentials are blank. That state is invisible locally, because the page looks finished; it only
surfaces when a real visitor submits and gets an error, and their address is gone by then. The
fix is either filling the credentials in or removing the feature from the markup — **never
shipping the half-configured middle**, which is what the check exists to make impossible.

It's deliberately quiet when `config.php` doesn't exist at all: that's the normal state of a
fresh fork, and it announces itself the moment any endpoint is hit.

## Projects built from this

The propagation list. Each project's own README records the commit it forked from, but that
answers "where did this come from" — not "who needs this change", which is the direction that
matters when something lands here.

| Project | Repo | Uses |
| --- | --- | --- |
| Sandra Macieira | [site-sandramacieira](https://github.com/plura/site-sandramacieira) | Full — pages, PHP endpoints, mail templates, both languages |
| Prevention Lab | [site-preventionlab](https://github.com/plura/site-preventionlab) | Full |
| Cristina Mesquita | [site-cristinamesquita](https://github.com/plura/site-cristinamesquita) | Conventions only — the CSS token architecture and layout structure, no PHP layer |
| Buscardini | [site-buscardini](https://github.com/plura/site-buscardini) | Not yet — predates this starter (`process/`, `mail-template/`); planned migration |

"Conventions only" matters when deciding what to propagate: a change to `strings.php` or the
mail templates simply doesn't apply there, while a change to `base.css`'s token structure does.

Deliberately **not** tracking how current each project is. Existence changes rarely; sync status
changes constantly, and a stale "caught up as of…" column is worse than no column.

## What a project's own README should say

Record the **decisions**, not the mechanism. A fork's README starts going stale the moment it
explains how something in here works — this repo changes, that copy doesn't, and nothing tells
you they've diverged. `plura/site-sandramacieira` is the working example of the shape below.

**Do include:**

- The fork note from step 1 of the checklist, with links **pinned to the ported commit** rather
  than a branch — branch links move once work merges upstream, so `/tree/<hash>#readme` keeps
  pointing at the docs that were actually true when the port happened.
- One line saying how the layout maps (e.g. "the starter's repo root is our `placeholder/`").
- A **Divergences** section: which optional features were removed, the site/owner language
  decision and whether Tier 2 is active, what's in `starter/custom/`, any file deliberately
  deleted rather than left as empty tokens, and any place the project inverts a starter default
  — with the reason, so the next person doesn't "fix" it back.

**Don't include:** how the language system works, the compile commands, the optional-features
tree, the mail-template gotchas, or a directory listing of `starter/`. All of that lives here
and changes here. Link to it.

The test: **would a reader who already knows this starter be surprised?** If not, it doesn't
belong in the project's README.

## Bringing a project up to date with the starter

There is no update mechanism — a fork is a fork. Bringing one level again is a manual pass, and
what that costs depends entirely on which file you're looking at. Measured against a real fork
rather than guessed:

| | Travels how | Why |
| --- | --- | --- |
| `starter/app/*.php`, `starter/app/lib/` | **Copy wholesale** | Projects diverge here by comments only (stripped `OPTIONAL` markers), never logic |
| `starter/assets/js/*.js` | **Copy, then re-check** | Usually identical; the exceptions are brand values in `theme.js` and added imports in `main.js` |
| `starter/assets/css/*.css` | **Merge by hand** | Brand tokens and stripped comments are interleaved through it |
| `index.html`, `pt/index.html` | **Merge by hand** | Copy, logo markup and JSON-LD are the project's; only structural changes travel |
| `robots.txt`, `sitemap.xml`, `site.webmanifest` | **Never** | Real domain and name — copying these back breaks the live site |
| `starter/app/config.php`, `starter/app/strings.php` | **Never** | Credentials and project copy |
| `starter/custom/**` | **Never** | Not the starter's, by definition |

Start from the fork-commit note at the top of the project's own README (step 1 of the checklist
above) — `git log <hash>..HEAD` in this repo lists exactly what the project is missing, which is
faster than diffing files and guessing what was deliberate.

The reason this stays a manual pass: making `starter/assets/` mechanically replaceable would mean
brand values moving to an override file *and* projects no longer stripping the `OPTIONAL`
comments they've acted on. That's a rewrite of how this starter is organized, to save a couple
of easy merges a year. Not worth it at this scale — revisit if the number of live forks grows
by an order of magnitude.


## Language

The starter ships in English, with a second language (Portuguese at `/pt/`) built but
**inactive** — it exists as a working page, but nothing links to or declares it and it's
`noindex`, so a single-language project can ignore it entirely.

Two things worth knowing before you touch any copy, both covered in **[docs/language.md](docs/language.md)**:

- **The site's language and the owner's language are separate axes.** A Portuguese client
  running an English site gets English pages, English auto-replies, and a *Portuguese*
  notification email — because they're the only one reading it.
- **There is no JS dictionary.** Page copy lives in the markup, endpoint copy in
  `starter/app/strings.php`. Each language is its own HTML file, so a page already is its
  language.

`docs/language.md` has the full picture: where every string lives, how to change the site's
language, how to activate or remove the second one, how to swap which language is the default,
and the drift check that keeps the two pages structurally aligned.
