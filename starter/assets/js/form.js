// What the contact and newsletter forms both do: post, and render the outcome. Only the parts
// that are genuinely identical live here — what's left in each module is what actually differs
// (the contact form's label collection, button labels and auto-close; nothing for the newsletter).
//
// Delete this file only if both forms are gone; it survives removing either one.

// Where the PHP endpoints sit relative to THIS page, set per page in <html data-app-base>. A
// language version one directory down reaches them by a different path, and both forms share
// one copy of this module. Falls back to the root layout.
const APP_BASE = document.documentElement.dataset.appBase ?? 'starter/app/';

/**
 * POSTs a form to one of the starter/app/ endpoints.
 *
 * Never rejects: a dead connection or a non-JSON response resolves as a failure with an empty
 * message, so callers fall through to their own copy rather than showing the browser's raw
 * error text ("Failed to fetch") to a visitor.
 *
 * @param {string}   endpoint Filename under starter/app/, e.g. 'submit.php'.
 * @param {FormData} data     Fields to post. The page's language is added here.
 * @returns {Promise<{ok: boolean, message: string}>} `message` is the server's own copy, in the
 *          language the page was rendered in, and is empty when there was no response to read.
 */
export async function post(endpoint, data) {
    // Tells the endpoint which language to answer in (see starter/app/strings.php). Inert on a
    // single-language site, where strings.php just falls through to its base copy.
    data.set('lang', document.documentElement.lang);

    try {
        const res  = await fetch(APP_BASE + endpoint, { method: 'POST', body: data });
        // Guarded so an HTML error page (a 500 from the host, say) is treated as a failure
        // rather than throwing on the parse.
        const json = await res.json().catch(() => ({}));

        return { ok: res.ok && json.success === true, message: json.message ?? '' };
    } catch {
        return { ok: false, message: '' };
    }
}

/**
 * Marks the form as submitted and shows the server's success message. The `is-sent` class is
 * what hides the fields — the rule lives in components.css, so nothing here lists them.
 *
 * @param {HTMLFormElement} form
 * @param {string}          message Server copy, already in the page's language.
 */
export function showSuccess(form, message) {
    form.classList.add('is-sent');
    form.appendChild(Object.assign(document.createElement('p'), {
        className: 'form-success',
        textContent: message,
    }));
}

/**
 * Shows a failure message, reusing the existing element on a repeat attempt rather than stacking
 * one per try. Falls back to the form's own `data-network-error` when the server said nothing —
 * a dead connection has no message worth showing a visitor.
 *
 * @param {HTMLFormElement} form
 * @param {string}          message Server copy, or '' when the request never completed.
 * @param {Element}         [before] Insert ahead of this element instead of appending.
 */
export function showError(form, message, before) {
    const el = form.querySelector('.form-error')
        || Object.assign(document.createElement('p'), { className: 'form-error' });

    el.textContent = message || form.dataset.networkError;

    if (form.contains(el)) return;

    if (before) {
        before.before(el);
    } else {
        form.appendChild(el);
    }
}

/**
 * Toggles the button's busy state. The label swap comes from the button's own `data-submitting`,
 * so a button without one just disables — which is what the newsletter's arrow wants.
 *
 * @param {HTMLButtonElement} btn
 * @param {boolean}           busy
 */
const idleLabels = new WeakMap();

export function setBusy(btn, busy) {
    if (!idleLabels.has(btn)) idleLabels.set(btn, btn.textContent);

    btn.disabled = busy;

    const busyLabel = btn.dataset.submitting;
    if (busyLabel) btn.textContent = busy ? busyLabel : idleLabels.get(btn);
}

/**
 * Returns the form to its pre-submit state — undoes everything showSuccess/showError did, plus
 * the field values and the button. Lives here rather than in the caller so it can't fall out of
 * step with what those two write; that split is what once left the opt-in checkbox on screen.
 *
 * @param {HTMLFormElement} form
 */
export function resetForm(form) {
    form.reset();
    form.classList.remove('is-sent');
    form.querySelectorAll('.form-success, .form-error').forEach((el) => el.remove());

    const submitBtn = form.querySelector('[type="submit"]');
    if (submitBtn) setBusy(submitBtn, false);
}

/**
 * Harvests each field's visible label text, for the notification email's `%label_FIELD%`
 * placeholders. Optional-field markers are stripped structurally, via `.label-note`, rather than
 * by matching the marker's text — so translating a label can't silently stop it working.
 *
 * @param {HTMLFormElement} form
 * @returns {Record<string, string>} Keyed by field `name`.
 */
export function collectLabels(form) {
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
