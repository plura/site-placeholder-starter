# Installations

An installation isn't a git fork. Each client project is its own repo, with this starter's
contents living in a subfolder — usually `placeholder/`, alongside the real site as it's built:

```
client-repo/
  placeholder/     this starter, installed
  src/             the main site, in development
  theme/ plugin/   if it's a WordPress project
```

So nothing is pulled or merged between the two repos. Changes are carried across by hand.

## Projects using this starter

| Project | Uses |
| --- | --- |
| [site-sandramacieira](https://github.com/plura/site-sandramacieira) | Full — pages, endpoints, mail, both languages |
| [site-preventionlab](https://github.com/plura/site-preventionlab) | Full |
| [site-cristinamesquita](https://github.com/plura/site-cristinamesquita) | Conventions only — CSS tokens and layout, no PHP layer |
| [site-buscardini](https://github.com/plura/site-buscardini) | Not yet. Predates this starter; planned migration |

Add new projects here. This list answers "who needs this change". The starter-commit note in
each project answers the opposite question, and neither replaces the other.

"Conventions only" changes what applies: a `strings.php` or mail change doesn't reach Cristina
Mesquita at all, a `base.css` token change does.

Not tracking how current each project is. That goes stale weekly.

## What a project's README should say

Record the **decisions**, not the mechanism. `site-sandramacieira` is the working example.

**Include:**

- The starter-commit note, with links pinned to that commit — `/tree/<hash>#readme`. Branch
  links move once work merges upstream.
- One line on how the layout maps, e.g. "the starter's repo root is our `placeholder/`".
- A **Divergences** section: removed features, the site/owner language decision, what's in
  `starter/custom/`, anything deleted rather than left empty, and any inverted default — with
  the reason, so nobody "fixes" it back.

**Don't include:** how the language system works, compile commands, the optional-features tree,
MJML gotchas, or a listing of `starter/`. Link here instead.

The test: would a reader who already knows this starter be surprised? If not, cut it.

## Applying a change to an installation

For "I added X, update this installation accordingly".

1. **Find every place it touches** — [touchpoints.md](touchpoints.md).
2. **Take structure from here, not content.** Adapt language, copy, brand tokens and `data-*`
   strings to the project.
3. **Mirror across every language page.**
4. **Run the checks** — `npm run build:mail` if `mail-templates/` changed, then `check:pages`
   and `check:config`.
5. **Update the project's Divergences** if this changed them.

## Porting starter changes into an installation

A manual pass. The cost depends on the file.

| | Travels how |
| --- | --- |
| `starter/app/*.php`, `starter/app/lib/` | **Copy wholesale.** Installations diverge here by stripped comments, never logic |
| `starter/assets/js/*.js` | **Copy, then re-check.** Exception: added imports in `main.js`. `theme.js` no longer carries brand values — it reads them from the CSS |
| `starter/assets/css/*.css` | **Merge by hand.** Brand tokens interleaved throughout |
| `index.html`, `pt/index.html` | **Merge by hand.** Only structural changes travel |
| `robots.txt`, `sitemap.xml`, `site.webmanifest` | **Never.** Real domain — copying back breaks the live site |
| `config.php`, `strings.php` | **Never.** Credentials and project copy |
| `starter/custom/**` | **Never.** Not the starter's |

Start from the starter-commit note. `git log <hash>..HEAD` here lists exactly what the
installation is missing.
