import STRINGS from './strings.js';

const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';

const dialog   = document.getElementById('contact-dialog');
const openBtn  = document.getElementById('open-modal');
const closeBtn = document.getElementById('close-modal');
const form     = document.getElementById('contact-form');
const submitBtn = form.querySelector('[type="submit"]');

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
    submitBtn.textContent = STRINGS.submit;
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

    // If a project adds a <select> whose posted value is a slug (not human-readable),
    // swap it for the selected option's visible text before submitting — e.g.:
    //   const interest = form.querySelector('[name="interest"]');
    //   if (interest?.value) data.set('interest', interest.selectedOptions[0].text);

    const labels = {};
    form.querySelectorAll('[name]').forEach(field => {
        const label = form.querySelector(`label[for="${field.id}"]`);
        if (label) labels[field.name] = label.textContent.replace(STRINGS.optionalSuffix, '').trim();
    });
    data.set('labels', JSON.stringify(labels));

    submitBtn.disabled = true;
    submitBtn.textContent = STRINGS.submitting;

    try {
        const res  = await fetch('./app/submit.php', { method: 'POST', body: data });
        const json = await res.json();

        if (res.ok && json.success) {
            form.querySelectorAll('.form-group, .btn-submit').forEach(el => el.style.display = 'none');
            const msg = Object.assign(document.createElement('p'), { className: 'form-success', textContent: STRINGS.success });
            form.appendChild(msg);
            setTimeout(closeModal, 2800);
        } else {
            throw new Error(json.message);
        }
    } catch {
        submitBtn.disabled = false;
        submitBtn.textContent = STRINGS.submit;
        const err = form.querySelector('.form-error') || Object.assign(document.createElement('p'), { className: 'form-error' });
        err.textContent = STRINGS.error;
        if (!form.contains(err)) submitBtn.before(err);
    }
});
