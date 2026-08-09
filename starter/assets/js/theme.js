// OPTIONAL (2/2): manual dark/light toggle. Safe to delete this whole file (and its import in
// main.js, #theme-toggle in index.html, and the OPTIONAL (2/2) blocks in base.css/
// components.css) for an auto-only (no manual override) dark/light site — the OPTIONAL (1/2)
// blocks alone already make the site auto-follow the OS/browser preference with no JS at all.
const STORAGE_KEY = 'theme';

const root       = document.documentElement;
const toggleBtn  = document.getElementById('theme-toggle');
const themeColorMeta = document.querySelector('meta[name="theme-color"]');

if (toggleBtn) {
    toggleBtn.addEventListener('click', () => {
        const current = root.getAttribute('data-theme')
            || (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
        const next = current === 'light' ? 'dark' : 'light';

        root.setAttribute('data-theme', next);
        localStorage.setItem(STORAGE_KEY, next);
        // setAttribute above forces a style recalc, so --site-bg already holds the new theme's
        // background — read it back instead of repeating the hex. The inline pre-paint script in
        // <head> can't do this: it runs above the stylesheet <link>s, so nothing resolves yet.
        themeColorMeta?.setAttribute('content', getComputedStyle(root).getPropertyValue('--site-bg').trim());
    });
}
