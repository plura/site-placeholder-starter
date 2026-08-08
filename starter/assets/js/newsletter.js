import { initForm } from './form.js';

// OPTIONAL: only needed if this project keeps the standalone mailing-list signup in index.html.
// Guarded on #newsletter-form existing, so leaving this imported after deleting that markup
// (rather than also removing the import) doesn't break the page.
const form = document.getElementById('newsletter-form');

// No labels: one field, and its <label> is sr-only. Nothing resets it either — the signup is
// one-shot, unlike the contact form's dialog, which can be reopened.
if (form) initForm({ form, endpoint: 'subscribe.php' });
