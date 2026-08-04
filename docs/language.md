# Language

The starter ships in English.

## Two independent axes

Language isn't one setting. **The site's language and the owner's language are separate**, and
conflating them is the mistake to avoid:

| | Follows | Files |
| --- | --- | --- |
| Page + auto-reply | **the visitor** | `index.html`, `pt/index.html`, `$BASE`/`$OVERRIDES` in `starter/app/strings.php`, `contact-reply*.mjml` |
| Notification email | **the site owner** | `contact.mjml`, `_partials/_fields.mjml`, `$OWNER` in `starter/app/strings.php` |

A Portuguese client running an English site gets an English page, English auto-replies to
visitors, and a **Portuguese** notification — because they're the only one reading it. That's
one template set to their language once at fork time, not a per-request variant.
`_fields.mjml` is pulled in by `contact.mjml` alone, so it follows the owner too.

The `%lang%` placeholder on the notification's date line shows which language version an
enquiry came from — the owner's only cue as to which language to reply in. It renders as
nothing on a single-language site.

## Where the copy lives

| Where | What |
| --- | --- |
| `index.html` / `pt/index.html` | All page copy, **including what the JS renders** — `data-submitting` and `data-network-error` attributes |
| `starter/app/strings.php` | Everything the endpoints return, **including both email subject lines** — `$BASE`/`$OVERRIDES` follow the visitor, `$OWNER` doesn't |
| `mail-templates/` | Email copy, inline — recompile after editing |

**There is no JS dictionary.** `modal.js` and `newsletter.js` take their copy from the markup:
the idle button label is the button's own text, and the two strings with no natural home are
`data-` attributes. Each language is a separate HTML file, so a page already *is* its language
and there's nothing for JS to resolve. Every other message the user sees comes from the
server's own response — the JS only supplies `data-network-error`, for when a request never
completes and there is no response to read.

`starter/app/strings.php` is the one dictionary, because a single endpoint serves every language
version. It holds a `$BASE` set plus an `$OVERRIDES` map listing only keys that differ. Keys
are semantic (`sent`), not the English source text used by gettext/`.po` — this copy gets
rewritten every project, and source-string keys go stale the moment one does.

## Changing the site's language

1. `index.html` — the copy, the `data-submitting` / `data-network-error` attributes, plus
   `<html lang>` and `og:locale`.
2. `starter/app/strings.php` — translate `$BASE`. Don't miss `subject_notify` / `subject_reply`: the
   email subjects are built here, not in the templates.
3. `mail-templates/contact/contact-reply.mjml` — the visitor-facing auto-reply. Then recompile
   (see "Compiling the MJML" in the [README](../README.md)); `starter/app/templates/*.html` is what PHP loads, so editing the MJML
   alone ships nothing.
4. `mail-templates/contact/contact.mjml` + `_partials/_fields.mjml` — **only if the owner's
   language is also changing.** See "Two independent axes" above.
5. `site.webmanifest` — only if `name`/`short_name` are language-dependent.

Leave alone: `+351` number formatting (including the `&zwnj;` treatment), `addressCountry`,
the postal code format, `Europe/Lisbon` in `submit.php`, and its `d/m/Y` date format. Those
track where the client is, not what language the site is in.

## Portuguese notification copy

For the common case of a Portuguese owner, the notification side is seven strings:

| File | English | Portuguese |
| --- | --- | --- |
| `contact.mjml` | `<mj-title>New enquiry` | `Novo contacto` |
| `contact.mjml` | `<mj-preview>New enquiry — Site Name` | `Novo contacto — Site Name` |
| `contact.mjml` | heading `New enquiry` | `Novo contacto` |
| `_fields.mjml` | `Name` | `Nome` |
| `_fields.mjml` | `Phone` | `Telefone` |
| `_fields.mjml` | `Message` | `Mensagem` |
| `starter/app/strings.php` | `$OWNER['subject_notify']` | `'%s — novo contacto do site'` |

That last row is why `strings.php` has a separate `$OWNER` block. Everything in `$BASE` is
resolved against the *visitor's* language; `$OWNER` is applied afterwards and can't be
overridden, because the notification's subject has to match its body — which is in the owner's
language regardless of which version the enquiry came from.

Local dev: the page is static, so Live Server serves it as-is. `php -S localhost:8000` from the
repo root only if you need to exercise the endpoints — note it ignores `.htaccess`, so
`starter/app/templates/` is readable locally but blocked in production.

## The second language (Tier 2)

**Ships built but inactive.** `pt/` exists as a working Portuguese page, and everything that
would make it *public* — the switcher, the `hreflang` tags, the sitemap entries — ships
commented out, with `pt/index.html` set to `noindex`.

This one is deliberately not "on by default, delete if unwanted" like the other optional
features. Dark mode and the mailing list are harmless if left in: they work. A forgotten Tier 2
is not — it would publish a page of `Site Name` placeholder copy, linked by a visible switcher
and declared in `hreflang` and the sitemap, and Google would index it. So the failure mode of
forgetting is silence, not a broken page on a client's domain.

The parts that ship **live** are inert without a second language and cost nothing:
`data-app-base`, `.page-controls`, `%lang%`, `$OVERRIDES` in `starter/app/strings.php`, and
`contact-reply.pt.*` (only ever selected when a form posts `lang=pt`).

## Activating it

1. `pt/index.html` — `<meta name="robots">` from `noindex` to `index, follow`.
2. Uncomment the `hreflang` block in **both** pages' `<head>`. Every version must list every
   version including itself; a one-sided pairing is ignored outright.
3. Uncomment the `.lang-switch` anchor in **both** pages.
4. `sitemap.xml` — swap the live single-URL `<urlset>` for the commented multilingual one.
5. Replace the real domain throughout — the `hreflang` hrefs, the canonicals, and the sitemap
   all ship pointing at `example.com`.
6. Translate `pt/index.html`'s copy, and check `$OVERRIDES['pt']` in `starter/app/strings.php` covers
   every key.

Then run `node tools/check-pages.mjs` (below) and submit both forms.

## Removing it

Delete `pt/`, the commented blocks in `index.html` and `sitemap.xml`, `$OVERRIDES`, the
`.lang-switch` rule in `components.css`, and `mail-templates/contact/contact-reply.pt.mjml` plus
its compiled output. Leave `data-app-base`, `.page-controls`, and `%lang%` — all three are
correct for a single root-level page.

Only worth doing if you're certain the project will never want a second language. Left alone it
publishes nothing.

## Keeping the two pages in step

Static HTML has no include mechanism, so `pt/index.html` duplicates the root page's entire
`<head>` and form markup. Nothing enforces that they stay aligned — add a favicon link or a form
field to one and the other silently falls behind.

```
node tools/check-pages.mjs
```

Compares structure, never content: which `<meta>` names exist, not what they say. Paths are
normalized for `pt/`'s extra directory level, and commented-out blocks are ignored, so an
inactive Tier 2 reads as absent from both rather than as drift. Exits non-zero on drift, so it
can gate a pre-commit hook. It can't *prevent* the duplication going stale — that would need a
build step or a template engine — but it makes it visible, which is the honest ceiling here.

**Adding a third language:** copy `pt/` to the new code, add its `$OVERRIDES` block, and add its
`hreflang` line to *every* page and to the sitemap.

## Changing the default language

Serving Portuguese at `/` and English at `/en/` is rearrangement, not deletion, and it touches
more than the copy:

1. **`starter/app/strings.php`** — `$BASE` takes the new default language, `$OVERRIDES` takes the old
   one. Rename the override key (`pt` → `en`). Leave `$OWNER` alone unless the *owner's*
   language is changing too — it's a separate axis.
2. **The pages** — swap the copy between `index.html` and `pt/index.html`, including the
   `data-submitting` and `data-network-error` attributes, then rename the directory (`pt/` →
   `en/`). Its `data-app-base="../starter/app/"` is unchanged; the root page keeps `starter/app/`.
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
