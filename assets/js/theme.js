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
        themeColorMeta?.setAttribute('content', next === 'light' ? '#f5f4f2' : '#1a1a1a');
    });
}
