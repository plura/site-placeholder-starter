import './modal.js';
import './theme.js'; // OPTIONAL (2/2) — remove for an auto-only dark/light site (or both theme tiers, see base.css)
import './newsletter.js'; // OPTIONAL — remove if this project has no mailing-list signup

const { lucide } = window;

lucide.createIcons();

// Project-specific entrance animations / canvas backgrounds (GSAP, particles, etc.)
// are bespoke per client — add them as their own modules and wire them up here,
// following the pattern of sibling sites built from this starter.
