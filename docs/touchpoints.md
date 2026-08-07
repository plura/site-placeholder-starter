# Touchpoints

Where each client value lives, and how many places it hides in.

Counts are for the starter as shipped, with both language pages.

## Social account — 6 places

- Footer `<a>` + icon span, in each page
- `sameAs` array in each page's JSON-LD
- `mail-templates/_partials/_footer.mjml`
- `CLIENT_*` in `tokens.json`

Watch for:

- `sameAs` renders nothing. Easiest to miss.
- The `&middot;` separator in `_footer.mjml` goes with its link.
- The icon needs `starter/assets/icons/<name>.svg`, filename matching the `mask-image`.

## Phone — 5 places

Two per page (footer link, form placeholder), plus `CLIENT_PHONE`.

- `CLIENT_PHONE_RAW` is derived by the build. Never set it by hand.
- Keep the `&zwnj;` between digit groups. It stops Gmail restyling the number.

## Email — 7 places

Three per page, plus `CLIENT_EMAIL`. Same `&zwnj;` treatment in the mail footer.

## Site name — 25 places

Eleven per page: title, meta description, OG, Twitter, JSON-LD, sr-only `<h1>`, logo, footer,
legal line. Plus `site.webmanifest` `name`/`short_name`, plus `CLIENT_NAME`.

The `<h1>` is `sr-only` — invisible but crawled.

## Address

JSON-LD `PostalAddress` per page, `_footer.mjml`, `CLIENT_ADDRESS`, `CLIENT_MAP_URL`.

Don't invent `openingHours`/`geo` to fill the JSON-LD out.

## Domain

`canonical` and OG/Twitter URLs per page, `hreflang` hrefs, `sitemap.xml`, `robots.txt`,
`CLIENT_URL`.

Ships as `example.com`. `robots.txt`'s `Disallow: /starter/app/` is a path — leave it.

---

After any `tokens.json` change: `npm run build:mail`. PHP loads the compiled
`starter/app/templates/*.html`, not the MJML.

## Adding back a removed feature

Removal instructions are in the README's "Optional features". Read them backwards.

Two things they don't say:

**Copy structure, not content.** The starter's blocks carry its English copy, generic classes
and placeholder strings. Adapt them to the project's language, `data-*` strings and brand
tokens.

**Then run the checks.** `npm run check:config` catches markup offering something `config.php`
can't serve. `npm run check:pages` catches a block added to one language page only.
