# custom/

Project-specific code that isn't part of the starter. Nothing in here ships with the starter
itself — it's empty on a fresh fork, and this file is the only thing in it.

The point is sorting, not isolation. When a starter update lands you need to know which files
to compare against the starter and which to ignore outright. `assets/` and `app/` came from the
starter; `custom/` never did.

## What goes here

Code that is **added**, not edited — self-contained and with no starter equivalent:

```
custom/
  components/<name>/   a bespoke component, keeping its CSS, JS and images together
  js/                  bespoke modules, imported from assets/js/main.js
```

An animated logo intro, a canvas background, a bespoke scroll effect, a third-party widget
wrapper. If it could be deleted without breaking anything the starter shipped, it belongs here.

**Put a component's styles in its own stylesheet, not in `assets/css/layout.css`.** This is
where the split earns most of its keep — bespoke CSS merged into a starter file is the thing
that makes a later update painful, and it's avoidable.

## What doesn't go here

Changes to files the starter owns. These stay where they are:

| Change | Stays in |
| --- | --- |
| Brand colors, fonts | `assets/css/base.css` (tokens), the font `<link>`s in each page |
| Page copy, logo markup, JSON-LD | `index.html` / `pt/index.html` |
| Endpoint copy | `app/strings.php` |
| Removed optional features | Deleted in place, per the main README |

Those are edits and deletions, and no folder catches them. That's what the fork-commit note at
the top of the project README is for — `git diff <fork-hash>` shows what changed, and this
folder shows what was never the starter's.

## Wiring it up

- **Component CSS** — a `<link>` in each page's `<head>`, after the three starter stylesheets,
  so it can override them.
- **JS** — an `import '../../custom/js/<name>.js';` in `assets/js/main.js`, alongside the
  existing module imports. That one line is an edit to a starter file; it's unavoidable, and
  it's one line.
- **Assets** — reference them relative to the stylesheet or page that uses them. Remember
  `pt/index.html` sits one directory down, so its paths need `../`.

`custom/` deploys like any other directory — it's live code, so don't add it to the SFTP ignore
list.
