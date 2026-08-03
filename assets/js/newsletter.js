// See the note in modal.js.
const APP_BASE = document.documentElement.dataset.appBase ?? 'app/';

// OPTIONAL: only needed if this project keeps the standalone mailing-list signup in index.html.
// Guarded on #newsletter-form existing, so leaving this imported after deleting that markup
// (rather than also removing the import) doesn't break the page.
const form = document.getElementById('newsletter-form');

if (form) {
    const submitBtn = form.querySelector('[type="submit"]');

    // See the note in modal.js — the only copy this module needs, since every other message
    // comes from the server's own response.
    const NETWORK_ERROR = form.dataset.networkError;

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        const data = new FormData(form);

        // See the note in modal.js — tells the endpoint which language to answer in.
        data.set('lang', document.documentElement.lang);

        submitBtn.disabled = true;

        const fail = (message) => {
            submitBtn.disabled = false;
            const errEl = form.querySelector('.form-error') || Object.assign(document.createElement('p'), { className: 'form-error' });
            errEl.textContent = message;
            if (!form.contains(errEl)) form.appendChild(errEl);
        };

        try {
            const res  = await fetch(APP_BASE + 'subscribe.php', { method: 'POST', body: data });
            const json = await res.json().catch(() => ({}));

            if (res.ok && json.success) {
                form.querySelector('.newsletter__row').style.display = 'none';
                const msg = Object.assign(document.createElement('p'), { className: 'form-success', textContent: json.message ?? '' });
                form.appendChild(msg);
            } else {
                fail(json.message || NETWORK_ERROR);
            }
        } catch {
            fail(NETWORK_ERROR);
        }
    });
}
