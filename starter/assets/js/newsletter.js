import { post } from './post.js';

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

        submitBtn.disabled = true;

        const { ok, message } = await post('subscribe.php', data);

        if (ok) {
            form.classList.add('is-sent');
            const msg = Object.assign(document.createElement('p'), { className: 'form-success', textContent: message });
            form.appendChild(msg);
            return;
        }

        submitBtn.disabled = false;
        const errEl = form.querySelector('.form-error') || Object.assign(document.createElement('p'), { className: 'form-error' });
        errEl.textContent = message || NETWORK_ERROR;
        if (!form.contains(errEl)) form.appendChild(errEl);
    });
}
