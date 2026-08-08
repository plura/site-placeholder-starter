import { post, showSuccess, showError, setBusy } from './form.js';

// OPTIONAL: only needed if this project keeps the standalone mailing-list signup in index.html.
// Guarded on #newsletter-form existing, so leaving this imported after deleting that markup
// (rather than also removing the import) doesn't break the page.
const form = document.getElementById('newsletter-form');

if (form) {
    const submitBtn = form.querySelector('[type="submit"]');

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        const data = new FormData(form);

        setBusy(submitBtn, true);

        const { ok, message } = await post('subscribe.php', data);

        if (ok) {
            showSuccess(form, message);
            return;
        }

        setBusy(submitBtn, false);
        showError(form, message);
    });
}
