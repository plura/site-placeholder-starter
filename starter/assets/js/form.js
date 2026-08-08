// Everything the contact and newsletter forms do the same way: post, render the outcome, reset.
// Exports one function — a form is wired by describing it, not by calling six helpers in the
// right order, which is the part both callers would otherwise repeat.
//
// Delete this file only if both forms are gone; it survives removing either one.

// Where the PHP endpoints sit relative to THIS page, set per page in <html data-app-base>. A
// language version one directory down reaches them by a different path, and both forms share
// one copy of this module. Falls back to the root layout.
const APP_BASE = document.documentElement.dataset.appBase ?? 'starter/app/';

/** Idle button labels, so setBusy can restore one it replaced. Keyed on the element itself. */
const idleLabels = new WeakMap();

/**
 * POSTs to one of the starter/app/ endpoints.
 *
 * Never rejects: a dead connection or a non-JSON response resolves as a failure with an empty
 * message, so the caller falls back to the page's own copy rather than showing a visitor the
 * browser's raw error text ("Failed to fetch").
 *
 * @param {string}   endpoint Filename under starter/app/, e.g. 'submit.php'.
 * @param {FormData} data
 * @returns {Promise<{ok: boolean, message: string}>} `message` is the server's own copy, in the
 *          page's language, and empty when there was no response to read.
 */
async function post(endpoint, data) {
    // Tells the endpoint which language to answer in (see starter/app/strings.php). Inert on a
    // single-language site, where strings.php just falls through to its base copy.
    data.set('lang', document.documentElement.lang);

    try {
        const res = await fetch(APP_BASE + endpoint, { method: 'POST', body: data });
        // Guarded so an HTML error page — a 500 from the host, say — is a failure rather than a
        // parse exception.
        const json = await res.json().catch(() => ({}));

        return { ok: res.ok && json.success === true, message: json.message ?? '' };
    } catch {
        return { ok: false, message: '' };
    }
}

/**
 * Toggles the button's busy state. The busy label comes from the button's own `data-submitting`;
 * a button without one just disables, which is what the newsletter's arrow wants.
 *
 * @param {HTMLButtonElement} btn
 * @param {boolean}           busy
 */
function setBusy(btn, busy) {
    if (!idleLabels.has(btn)) idleLabels.set(btn, btn.textContent);

    btn.disabled = busy;

    const busyLabel = btn.dataset.submitting;
    if (busyLabel) btn.textContent = busy ? busyLabel : idleLabels.get(btn);
}

/**
 * Harvests each field's visible label text, for the notification email's `%label_FIELD%`
 * placeholders. Optional-field markers are stripped structurally, via `.label-note`, rather than
 * by matching the marker's text — so translating a label can't silently stop it working.
 *
 * @param {HTMLFormElement} form
 * @returns {Record<string, string>} Keyed by field `name`.
 */
function collectLabels(form) {
    const labels = {};

    form.querySelectorAll('[name]').forEach((field) => {
        const label = form.querySelector(`label[for="${field.id}"]`);
        if (!label) return;

        const clean = label.cloneNode(true);
        clean.querySelector('.label-note')?.remove();
        labels[field.name] = clean.textContent.trim();
    });

    return labels;
}

/**
 * Wires a form's whole submit lifecycle.
 *
 * The server owns every message a visitor sees. The page supplies only `data-network-error`, for
 * when the request never completed and there is no response to read.
 *
 * @param {object}          options
 * @param {HTMLFormElement} options.form
 * @param {string}          options.endpoint  Filename under starter/app/, e.g. 'submit.php'.
 * @param {boolean}         [options.labels]  Post the form's own label text, for the notification
 *                                            email's field names. Pointless on a single-field form.
 * @param {() => void}      [options.onSuccess] Runs once the success message is on screen — the
 *                                            contact form uses it to auto-close its dialog.
 * @returns {{reset: () => void}} `reset` returns the form to its pre-submit state. It lives here
 *          rather than in the caller so it can't fall out of step with what submitting writes;
 *          that split is what once left the opt-in checkbox on screen after a send.
 */
export function initForm({ form, endpoint, labels = false, onSuccess }) {
    const submitBtn = form.querySelector('[type="submit"]');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const data = new FormData(form);
        if (labels) data.set('labels', JSON.stringify(collectLabels(form)));

        setBusy(submitBtn, true);

        const { ok, message } = await post(endpoint, data);

        if (ok) {
            // `is-sent` is what hides the fields — the rule lives in components.css, so nothing
            // here lists them.
            form.classList.add('is-sent');
            form.appendChild(Object.assign(document.createElement('p'), {
                className: 'form-success',
                textContent: message,
            }));

            onSuccess?.();
            return;
        }

        setBusy(submitBtn, false);

        // Reuse the existing element on a repeat attempt rather than stacking one per try.
        const error = form.querySelector('.form-error')
            || Object.assign(document.createElement('p'), { className: 'form-error' });

        error.textContent = message || form.dataset.networkError;
        if (!form.contains(error)) form.appendChild(error);
    });

    return {
        reset() {
            form.reset();
            form.classList.remove('is-sent');
            form.querySelectorAll('.form-success, .form-error').forEach((el) => el.remove());
            setBusy(submitBtn, false);
        },
    };
}
