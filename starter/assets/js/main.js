import './modal.js';
import './theme.js'; // OPTIONAL (2/2) — remove for an auto-only dark/light site (or both theme tiers, see base.css)
import './newsletter.js'; // OPTIONAL — remove if this project has no mailing-list signup

const { lucide } = window;

lucide.createIcons();

// Project-specific entrance animations / canvas backgrounds (GSAP, particles, etc.) are
// bespoke per client. They live in starter/custom/ — see starter/custom/README.md — and get wired up here:
//
//     import '../../starter/custom/js/intro.js';
//
// Keeping them out of starter/assets/ means a starter update never has to distinguish your code
// from its own.
