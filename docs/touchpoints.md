# Touchpoints

**This starter has everything by default.** A project is the starter minus what it didn't need,
so whenever something has to be added — a social account the client just opened, a feature that
was removed at fork time and is now wanted — the complete, correct structure already exists
here. Read how this repo does it, then adapt.

This file is the map of *where* each thing lives. Not what it contains — that's in the code —
but how many places it hides in, and which of those are easy to miss.

## Why this exists

Most client values aren't in one place, and the copies aren't all visible. Adding a social
account touches the page footer *and* the JSON-LD `sameAs` array, which renders nothing and so
gets forgotten; the omission only shows up in structured-data testing months later. Changing the
site name touches eleven places per page.

Grep finds most of it if you know the value to search for. What grep won't tell you is that
`_footer.mjml`'s separator has to go with its link, that the icon SVG has to exist under a
matching filename, or that mail templates need rebuilding afterwards. Those are the rows below
worth reading before starting.

## Cross-cutting client values

Counts are for the starter as shipped, with both language pages present.

| Value | Where | Watch for |
| --- | --- | --- |
| **Social account** | Footer `<a>` + icon span in each page; `sameAs` array in each page's JSON-LD; `_footer.mjml`; `CLIENT_*` in `tokens.json` | `sameAs` is invisible on the page. `_footer.mjml`'s `&middot;` separator must go with the link, not be left orphaned. The icon needs `starter/assets/icons/<name>.svg` and the exact filename referenced in `mask-image` |
| **Phone** | 2× per page (footer link, form placeholder); `CLIENT_PHONE` | `CLIENT_PHONE_RAW` is derived by the build, never set by hand. Page markup uses `&zwnj;` between digit groups to stop Gmail restyling it — keep it |
| **Email** | 3× per page; `CLIENT_EMAIL` | Same `&zwnj;` treatment in the mail footer |
| **Site name** | 11× per page (title, meta description, OG, Twitter, JSON-LD, sr-only `<h1>`, logo, footer, legal line); `site.webmanifest` `name`/`short_name`; `CLIENT_NAME` | The `<h1>` is `sr-only` — invisible but crawled, so it can't be skipped |
| **Address** | JSON-LD `PostalAddress` per page; `_footer.mjml`; `CLIENT_ADDRESS` + `CLIENT_MAP_URL` | Don't invent `openingHours`/`geo` to fill the JSON-LD out — wrong structured data is worse than absent |
| **Domain** | `canonical` + OG/Twitter URLs per page; `hreflang` hrefs; `sitemap.xml`; `robots.txt`; `CLIENT_URL` | Ships as `example.com` everywhere. `robots.txt`'s `Disallow: /starter/app/` is a path, not a domain — leave it |

After any `tokens.json` change, `npm run build:mail`. The compiled `starter/app/templates/*.html`
is what PHP loads; editing MJML alone ships nothing.

## Adding back a removed feature

The removal instructions live in "Optional features" in the [README](../README.md) — read them
backwards for what has to come back. Two things they don't say:

**Copy the structure, not the content.** The starter's version of a block carries the starter's
English copy, its generic classes and its placeholder strings. A fork needs its own language (and
its second page, if bilingual), its own `data-network-error` and `data-submitting` strings, and
its own brand tokens. The insertion is the quick part; adapting it is most of the work.

**Then run the checks.** `npm run check:config` fails if markup now offers something `config.php`
can't serve — the exact state re-adding a feature creates when the credentials aren't in yet.
`npm run check:pages` catches a block added to one language page and not the other.

## Removing a feature

Do it in **its own commit**, separate from any other customization. That keeps `git revert`
available if the client changes their mind, and it's the difference between restoring one block
and unpicking it from a commit that also renamed everything. Once bundled into a general
"customize for <client>" commit, that option is gone and re-adding means coming back here.

Record it in the project README's Divergences section either way — that's what tells you
something was removed deliberately rather than never existing.
