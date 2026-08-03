<?php
declare(strict_types=1);

// Every user-facing string the endpoints return, in one place — the PHP counterpart to
// assets/js/strings.js, in the same base-plus-overlay shape. Three of these are shared by
// submit.php and subscribe.php, which is why they live here rather than per-file.
//
// Keys are semantic, not the English source text: see the note in assets/js/strings.js for
// why source-string keys go stale in a starter whose copy is rewritten every project.

// Default-language copy — the only complete set, and the fallback for anything a translation
// below leaves out.
$BASE = [
    'method_not_allowed' => 'Method not allowed.',
    'config_error'       => 'Server configuration error.',
    'required_fields'    => 'Please fill in the required fields.',
    'invalid_email'      => 'Please enter a valid email address.',
    'send_error'         => 'Could not send. Please try again, or email us.',

    // Subject lines — %s is contact.site_name from config.php. Easy to miss when translating,
    // since they are built here rather than in the mail templates.
    'subject_notify' => '%s — new enquiry from the website',
    'subject_reply'  => '%s — we received your message',

    // OPTIONAL: mailing list — remove these four if this project has no newsletter.
    'mailchimp_not_configured' => 'Subscriptions are not configured correctly.',
    'subscribe_confirm'        => 'Thanks for subscribing! Check your inbox to confirm.',
    'already_subscribed'       => 'You are already subscribed.',
    'generic_error'            => 'Something went wrong. Please try again later.',
    // /OPTIONAL
];

// OPTIONAL (Tier 2): translations, as a delta against $BASE — only keys that differ need
// listing. Remove this whole block for a single-language site. To change the default
// language, rewrite $BASE in the new language and move the old copy down here.
$OVERRIDES = [];
// /OPTIONAL

// The form posts the language it was rendered in (see modal.js). Trimmed to a bare two-letter
// code so 'en-GB' matches 'en'; anything unknown or absent falls through to $BASE.
$lang = strtolower(substr((string) ($_POST['lang'] ?? ''), 0, 2));

return array_merge($BASE, $OVERRIDES[$lang] ?? []);
