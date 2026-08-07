# Working with forks

## Projects built from this

| Project | Uses |
| --- | --- |
| [site-sandramacieira](https://github.com/plura/site-sandramacieira) | Full — pages, endpoints, mail, both languages |
| [site-preventionlab](https://github.com/plura/site-preventionlab) | Full |
| [site-cristinamesquita](https://github.com/plura/site-cristinamesquita) | Conventions only — CSS tokens and layout, no PHP layer |
| [site-buscardini](https://github.com/plura/site-buscardini) | Not yet. Predates this starter; planned migration |

Add new projects here. This list answers "who needs this change" — the fork note in each project
answers the opposite question, and neither replaces the other.

"Conventions only" changes what applies: a `strings.php` or mail change doesn't reach Cristina
Mesquita at all, a `base.css` token change does.

Not tracking how current each project is. That goes stale weekly.

## What a project's README should say

Record the **decisions**, not the mechanism. `site-sandramacieira` is the working example.

**Include:**

- The fork note, with links pinned to the ported commit — `/tree/<hash>#readme`. Branch links
  move once work merges upstream.
- One line on how the layout maps, e.g. "the starter's repo root is our `placeholder/`".
- A **Divergences** section: removed features, the site/owner language decision, what's in
  `starter/custom/`, anything deleted rather than left empty, and any inverted default — with
  the reason, so nobody "fixes" it back.

**Don't include:** how the language system works, compile commands, the optional-features tree,
MJML gotchas, or a listing of `starter/`. Link here instead.

The test: would a reader who already knows this starter be surprised? If not, cut it.

## Applying a change to a project

For "I added X, update this installation accordingly".

1. **Find every place it touches** — [touchpoints.md](touchpoints.md).
2. **Take structure from here, not content.** Adapt language, copy, brand tokens and `data-*`
   strings to the project.
3. **Mirror across every language page.**
4. **Run the checks** — `npm run build:mail` if `mail-templates/` changed, then `check:pages`
   and `check:config`.
5. **Update the project's Divergences** if this changed them.

## Porting starter changes into a fork

No update mechanism exists. It's a manual pass, and the cost depends on the file.

| | Travels how |
| --- | --- |
| `starter/app/*.php`, `starter/app/lib/` | **Copy wholesale.** Forks diverge here by stripped comments, never logic |
| `starter/assets/js/*.js` | **Copy, then re-check.** Exceptions: brand values in `theme.js`, added imports in `main.js` |
| `starter/assets/css/*.css` | **Merge by hand.** Brand tokens interleaved throughout |
| `index.html`, `pt/index.html` | **Merge by hand.** Only structural changes travel |
| `robots.txt`, `sitemap.xml`, `site.webmanifest` | **Never.** Real domain — copying back breaks the live site |
| `config.php`, `strings.php` | **Never.** Credentials and project copy |
| `starter/custom/**` | **Never.** Not the starter's |

Start from the fork note. `git log <hash>..HEAD` here lists exactly what the project is missing.
