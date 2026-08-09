# Site Placeholder Starter

A reusable starting point for Plura's single-page "coming soon" client sites: a static
placeholder page with a modal contact form, backed by a PHP handler that sends both a
notification email (to the site owner) and an auto-reply (to the submitter), using
MJML-authored HTML email templates.

Extracted from the Prevention Lab and Buscardini projects after building the same pattern
independently in both — the shapes here were converged on twice before being pulled out, not
invented once.

## Quick start

1. **Copy this whole repo** as the new client repo (or its `placeholder/` subfolder, if the
   project also has a WordPress `theme/`/`plugin/` per the Plura lean repo structure). Note this
   starter's commit (`git log -1 --format="%H %ad" --date=short`) and record it in the new
   project's README — see [docs/installations.md](docs/installations.md) for its format and what else
   belongs there.
2. **Decide the two languages before touching any copy.** They're independent, and getting it
   wrong means redoing work rather than adjusting it. What language does the **site** serve, and
   what language does the **owner** read? A Portuguese client with an English site is normal —
   see [docs/language.md](docs/language.md).
3. **Decide on "Optional features"** below and delete what's not needed.
4. **`starter/assets/css/base.css`** — replace the color/font values, keep the variable names.
   Webfonts load via `<link>` in each page's `<head>`, so a typeface change means editing those
   *and* the two `--site-font-` values.
5. **`index.html`** — replace all placeholder text, URLs, JSON-LD, meta tags. Work from
   [docs/touchpoints.md](docs/touchpoints.md): none of these live in one place, and the JSON-LD
   copies render nothing, so they're the ones that get missed.
6. **Contact-form fields**, if you need more than name/email/phone/message — copy a row+divider
   pair in `mail-templates/contact/_partials/_fields.mjml` and rename both placeholders. No
   `submit.php` change, and no label text to keep in sync.
7. **`cp mail-templates/tokens.example.json mail-templates/tokens.json`** and fill in the
   client's real values. See `mail-templates/_tokens.json` for the full list and rules.
8. **`npm run build:mail`**, then `cp starter/app/config.example.php
   starter/app/config.php` and fill in SMTP credentials (and the `newsletter` block, if keeping
   a mailing list).
9. **Generate favicons** and `starter/assets/images/og.png` — see [Favicon spec](#favicon-spec).
10. **`cp .vscode/sftp.json.example .vscode/sftp.json`**, fill in host/credentials.
11. **Update `robots.txt` / `sitemap.xml` / `site.webmanifest`** with the real domain.

## Structure

```
index.html              the page
pt/index.html           second language — ships built but inactive
starter/                everything the browser loads
  app/                  PHP endpoints, templates, config
  assets/               css, js, icons, favicons
  custom/               project-specific code; never the starter's
mail-templates/         MJML source (not deployed)
tools/                  build + checks (not deployed)
docs/                   the rest of this documentation
```

`starter/` is wrapped so the placeholder can share a webroot with something else — the real site
under development, a staging copy — without either claiming `assets/` or `app/`.

**Copy as-is:** `starter/app/submit.php` (field-agnostic — it loops raw POST with no hardcoded
field list, and takes labels from the form's own `<label>` elements), `starter/app/lib/phpmailer/`,
and the MJML structural patterns in `mail-templates/_partials/`.

**Always customize:** `base.css` tokens, `index.html`, `tokens.json`, `config.php`, favicons.
Bespoke animations go in `starter/custom/` — see `starter/custom/README.md`.

## Optional features

Everything ships on by default. Delete what's not needed — every optional block is delimited
with matching `OPTIONAL` markers (in HTML, CSS, PHP and JS) listing every other file that goes
with it, so removal is a complete delete rather than guesswork.

Remove each feature in **its own commit**, separate from other customization, so `git revert`
stays available if the client changes their mind. Read the instructions backwards to add one
back, and see [docs/touchpoints.md](docs/touchpoints.md) for what still needs adapting.

1. **Contact form (modal)?** The core feature — assumed present. To remove: `#contact-dialog` in
   `index.html`, `starter/assets/js/modal.js` + its import, `starter/app/submit.php`, and the
   `mail-templates/contact/` + `starter/app/templates/contact*.html` pair. `form.js` is shared
   with the newsletter form — delete it only if that's going too.
2. **Which fields?** Name + Email are the only two `submit.php` requires.
3. **Mailing-list opt-in checkbox on the contact form?** To remove: the marked block in
   `index.html`'s `#contact-form` and the marked block near the end of `submit.php`.
4. **Standalone mailing-list signup?** A separate inline single-field section — lower friction
   than a checkbox buried in a longer form. To remove: the marked `.newsletter` block in
   `index.html`, its styles in `components.css`, `newsletter.js` + its import.
5. **Keeping either mailing list?** Both go through `newsletter_subscribe()` in
   `starter/app/lib/newsletter.php`, which dispatches to `config.php`'s `newsletter.provider` —
   `brevo` or `mailchimp`, implemented in `lib/newsletter/`. The kit only *adds addresses*; it
   never sends campaigns, so pick whichever provider the client already uses. Brevo is the
   default, being EU-hosted.

   **Double opt-in differs between them** and the shipped copy assumes it doesn't happen — see
   the note in `lib/newsletter/brevo.php` before changing `subscribe_confirm`.

   If neither survives: delete `subscribe.php`, `lib/newsletter.php`, `lib/newsletter/`, and the
   `newsletter` config block.
6. **Dark/light mode?** Two independently removable tiers — `OPTIONAL (1/2)` is the mode itself
   (no JS), `OPTIONAL (2/2)` the manual toggle on top. Keep both, keep only (1/2), or remove
   both for a single dark theme. See [docs/theming.md](docs/theming.md).
7. **A second language?** Ships **built but inactive** — `pt/` exists and works, but it's
   `noindex` and nothing links to it, so a single-language project can ignore it. Unlike the
   others, forgetting this one is harmless, which is why it isn't on by default. See
   [docs/language.md](docs/language.md).

## Deploying

Two patterns, depending on the host — see the global CLAUDE.md for both:

- Plain SFTP/FTPS sync (most shared hosts).
- cPanel Git Version Control + a manually-triggered GitHub Actions deploy (`workflow_dispatch`,
  not `on: push`), where supported.

**Before the first sync, run `npm run check:config`.** It fails if the markup offers a feature
`config.php` can't serve — a contact form with no SMTP, a newsletter signup with no credentials.

That state looks fine locally. It surfaces when a real visitor submits and their address is
already gone. Fill the credentials in, or remove the feature. Never ship the middle.

## Favicon spec

Design as the icon/mark alone — a full wordmark is illegible at 16–32px.

| File | Size | Notes |
|---|---|---|
| `starter/assets/favicons/favicon-96x96.png` | 96×96 | transparent OK |
| `starter/assets/favicons/apple-touch-icon.png` | 180×180 | **solid background, no transparency, no pre-rounded corners** — iOS rounds it |
| `starter/assets/favicons/favicon-192.png` | 192×192 | Android/PWA |
| `starter/assets/favicons/favicon-512.png` | 512×512 | Android/PWA |

A `favicon.svg` is a nice-to-have but optional.

## Commands

| | |
|---|---|
| `npm run build:mail` | Replace tokens, compile MJML, guard against unreplaced ones. `-- --watch` to rebuild on save |
| `npm run check:pages` | Structural drift between `index.html` and each language page |
| `npm run check:config` | Markup offers nothing `config.php` can't serve |

## Docs

| | |
|---|---|
| [language.md](docs/language.md) | Where copy lives, the site/owner language split, activating or removing the second language |
| [mail-templates.md](docs/mail-templates.md) | The compile pipeline, tokens, and the MJML gotchas that cost real debugging time |
| [touchpoints.md](docs/touchpoints.md) | Every place a client value hides — a social account touches six, the site name twenty-five |
| [theming.md](docs/theming.md) | The dark/light token architecture and switching precedence |
| [installations.md](docs/installations.md) | Which projects use this, what their READMEs should say, applying a change, porting one back up to date |

## Conventions

**This starter has everything by default.** A project is the starter minus what it didn't need.
So this repo is the reference for what "complete" looks like.

Docs here are written for whoever does that work, agent or human. The question isn't "can this
be automated" — it's **"what does the executor need written down."** Which means:

- **Every instruction names all the files it touches.** Most mistakes here are doing four fifths
  of the right thing.
- **Anything checkable is checked**, not left to memory.
- **What can't be checked is mapped** — `touchpoints.md`, the travels-how table in `installations.md`.

Adding to this repo: if a change touches more than one file, say so **in the inline comment at
the site of the work** — not only in a doc someone has to know to open.
