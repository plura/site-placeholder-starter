import { initForm } from './form.js';

const FOCUSABLE ='a[href], button:not([disabled]), input:not([disabled]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';

const dialog   = document.getElementById('contact-dialog');
const openBtn  = document.getElementById('open-modal');
const closeBtn = document.getElementById('close-modal');
const form     = document.getElementById('contact-form');

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

// labels: the notification email renders them as its field names — see %label_FIELD% in
// mail-templates/contact/_partials/_fields.mjml.
//
// If a project adds a <select> whose posted value is a slug rather than something readable, the
// email will show the slug. Swap it for the option's visible text in a submit listener added
// before this one, e.g.:
//   form.addEventListener('submit', () => { ... }, { capture: true });
const contactForm = initForm({
    form,
    endpoint: 'submit.php',
    labels: true,
    onSuccess: () => setTimeout(closeModal, 2800),
});

dialog.addEventListener('close', () => contactForm.reset());

openBtn.addEventListener('click', openModal);
closeBtn.addEventListener('click', closeModal);

// Close on backdrop click (click lands on the dialog element itself, not .dialog__inner)
dialog.addEventListener('click', e => {
    if (e.target === dialog) closeModal();
});
