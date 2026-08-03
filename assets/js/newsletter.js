import STRINGS from './strings.js';

// OPTIONAL: only needed if this project keeps the standalone mailing-list signup in index.html.
// Guarded on #newsletter-form existing, so leaving this imported after deleting that markup
// (rather than also removing the import) doesn't break the page.
const form = document.getElementById('newsletter-form');

if (form) {
    const submitBtn = form.querySelector('[type="submit"]');

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        const data = new FormData(form);

        // See the note in modal.js — tells the endpoint which language to answer in.
        data.set('lang', document.documentElement.lang);

        submitBtn.disabled = true;

        try {
            const res  = await fetch('./app/subscribe.php', { method: 'POST', body: data });
            const json = await res.json();

            if (res.ok && json.success) {
                form.querySelector('.newsletter__row').style.display = 'none';
                const msg = Object.assign(document.createElement('p'), { className: 'form-success', textContent: json.message || STRINGS.subscribeSuccess });
                form.appendChild(msg);
            } else {
                throw new Error(json.message);
            }
        } catch (err) {
            submitBtn.disabled = false;
            const errEl = form.querySelector('.form-error') || Object.assign(document.createElement('p'), { className: 'form-error' });
            errEl.textContent = err.message || STRINGS.subscribeError;
            if (!form.contains(errEl)) form.appendChild(errEl);
        }
    });
}
