// Set per page in <html data-app-base>, since a language version one directory down reaches
// the endpoints by a different path than the one at the root. Falls back to the root layout.
const APP_BASE = document.documentElement.dataset.appBase ?? 'app/';

const FOCUSABLE ='a[href], button:not([disabled]), input:not([disabled]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';

const dialog   = document.getElementById('contact-dialog');
const openBtn  = document.getElementById('open-modal');
const closeBtn = document.getElementById('close-modal');
const form     = document.getElementById('contact-form');
const submitBtn = form.querySelector('[type="submit"]');

// Copy comes from the markup, not a dictionary — each language is its own HTML file, so the
// page already is its language and there is nothing for JS to resolve. The idle label is just
// whatever the button ships with.
const LABEL_SUBMIT     = submitBtn.textContent;
const LABEL_SUBMITTING = submitBtn.dataset.submitting;
const NETWORK_ERROR    = form.dataset.networkError;

let opener = null;

function trapFocus() {
    const els   = [...dialog.querySelectorAll(FOCUSABLE)];
    if (!els.length) return;
    const first = els[0];
    const last  = els[els.length - 1];

    const onKeyDown = (e) => {
        if (e.key !== 'Tab') return;
        if (e.shiftKey) {
            if (document.activeElement === first) { e.preventDefault(); last.focus(); }
        } else {
            if (document.activeElement === last)  { e.preventDefault(); first.focus(); }
        }
    };

    dialog.addEventListener('keydown', onKeyDown);
    dialog.addEventListener('close', () => dialog.removeEventListener('keydown', onKeyDown), { once: true });
}

function openModal() {
    opener = document.activeElement;
    dialog.showModal();
    trapFocus();
}

function closeModal() {
    dialog.close();
    opener?.focus();
}

dialog.addEventListener('close', () => {
    form.reset();
    submitBtn.disabled = false;
    submitBtn.textContent = LABEL_SUBMIT;
    form.querySelectorAll('.form-success, .form-error').forEach(el => el.remove());
    form.querySelectorAll('.form-group, .btn-submit').forEach(el => el.style.display = '');
});

openBtn.addEventListener('click', openModal);
closeBtn.addEventListener('click', closeModal);

// Close on backdrop click (click lands on the dialog element itself, not .dialog__inner)
dialog.addEventListener('click', e => {
    if (e.target === dialog) closeModal();
});

form.addEventListener('submit', async function (e) {
    e.preventDefault();

    const data = new FormData(form);

    // Tells the endpoint which language to answer in (see app/strings.php). Inert on a
    // single-language site, where strings.php just falls through to its base copy.
    data.set('lang', document.documentElement.lang);

    // If a project adds a <select> whose posted value is a slug (not human-readable),
    // swap it for the selected option's visible text before submitting — e.g.:
    //   const interest = form.querySelector('[name="interest"]');
    //   if (interest?.value) data.set('interest', interest.selectedOptions[0].text);

    // Optional-field markers are stripped structurally, via .label-note, rather than by matching
    // the marker's text — so translating the labels can't silently stop it working.
    const labels = {};
    form.querySelectorAll('[name]').forEach(field => {
        const label = form.querySelector(`label[for="${field.id}"]`);
        if (!label) return;
        const clean = label.cloneNode(true);
        clean.querySelector('.label-note')?.remove();
        labels[field.name] = clean.textContent.trim();
    });
    data.set('labels', JSON.stringify(labels));

    submitBtn.disabled = true;
    submitBtn.textContent = LABEL_SUBMITTING;

    // The server owns every message it can produce — validation, mail failure, success. The page
    // supplies only NETWORK_ERROR, for when there is no response to read. Never surface a thrown
    // error's own text: a failed fetch yields "Failed to fetch", which is not for users.
    const fail = (message) => {
        submitBtn.disabled = false;
        submitBtn.textContent = LABEL_SUBMIT;
        const err = form.querySelector('.form-error') || Object.assign(document.createElement('p'), { className: 'form-error' });
        err.textContent = message;
        if (!form.contains(err)) submitBtn.before(err);
    };

    try {
        const res  = await fetch(APP_BASE + 'submit.php', { method: 'POST', body: data });
        // Guarded so an HTML error page (a 500 from the host, say) falls through to NETWORK_ERROR
        // rather than throwing on the parse.
        const json = await res.json().catch(() => ({}));

        if (res.ok && json.success) {
            form.querySelectorAll('.form-group, .btn-submit').forEach(el => el.style.display = 'none');
            const msg = Object.assign(document.createElement('p'), { className: 'form-success', textContent: json.message ?? '' });
            form.appendChild(msg);
            setTimeout(closeModal, 2800);
        } else {
            fail(json.message || NETWORK_ERROR);
        }
    } catch {
        fail(NETWORK_ERROR);
    }
});
