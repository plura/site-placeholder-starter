<?php
declare(strict_types=1);

// Every user-facing string the endpoints return, in one place. This is the project's only
// dictionary: a single endpoint serves every language version, so it has to resolve a language
// per request. The pages don't — each language is its own HTML file, so a page already is its
// language, and the JS reads the few strings it needs straight from the markup.
//
// Three of these are shared by submit.php and subscribe.php, which is why they live in one file
// rather than a block per endpoint.
//
// Keys are semantic, not the English source text used by gettext/.po. This copy gets rewritten
// every project, and source-string keys go stale the moment one does — a reworded string leaves
// a dead key that quietly falls back to the new default-language text.

// Default-language copy — the only complete set, and the fallback for anything a translation
// below leaves out.
$BASE = [
    'method_not_allowed' => 'Method not allowed.',
    'config_error'       => 'Server configuration error.',
    'required_fields'    => 'Please fill in the required fields.',
    'invalid_email'      => 'Please enter a valid email address.',
    'send_error'         => 'Could not send. Please try again, or email us.',
    'sent'               => 'Message sent. We\'ll be in touch shortly.',

    // %s is contact.site_name from config.php. Easy to miss when translating, since the subject
    // is built here rather than in the mail template. This one is visitor-facing — the auto-reply
    // goes back to whoever submitted the form. Its owner-facing counterpart is in $OWNER below.
    'subject_reply'  => '%s — we received your message',

    // OPTIONAL: mailing list — remove these four if this project has no newsletter.
    'newsletter_not_configured' => 'Subscriptions are not configured correctly.',
    // Deliberately says nothing about confirming by email: Mailchimp always double opt-ins, but
    // Brevo only does once a DOI template is set up in the account, and this ships defaulting to
    // Brevo. Add "Check your inbox to confirm." once the project's provider actually sends one —
    // promising a confirmation that never arrives is worse than not mentioning it.
    'subscribe_confirm'         => 'Thanks for subscribing.',
    'already_subscribed'        => 'You are already subscribed.',
    'generic_error'             => 'Something went wrong. Please try again later.',
    // /OPTIONAL
];

// OPTIONAL (Tier 2): translations, as a delta against $BASE — only keys that differ need
// listing. Remove this whole block for a single-language site. To change the default
// language, rewrite $BASE in the new language and move the old copy down here.
$OVERRIDES = [
    'pt' => [
        'method_not_allowed' => 'Método não permitido.',
        'config_error'       => 'Erro de configuração do servidor.',
        'required_fields'    => 'Por favor preencha os campos obrigatórios.',
        'invalid_email'      => 'Por favor introduza um endereço de email válido.',
        'send_error'         => 'Erro ao enviar. Por favor tente novamente ou contacte-nos por email.',
        'sent'               => 'Mensagem enviada. Entraremos em contacto em breve.',

        'subject_reply'  => '%s — recebemos o seu contacto',

        // OPTIONAL: mailing list. See the note on subscribe_confirm in $BASE — this one drops the
        // confirmation instruction for the same reason.
        'newsletter_not_configured' => 'A subscrição não está configurada corretamente.',
        'subscribe_confirm'         => 'Obrigado por subscrever.',
        'already_subscribed'        => 'Já está subscrito!',
        'generic_error'             => 'Ocorreu um erro. Por favor tente novamente mais tarde.',
        // /OPTIONAL
    ],
];
// /OPTIONAL

// Owner-facing copy. Deliberately outside the language resolution below: the notification email
// goes to the site owner, who reads one language whichever version the visitor used. Set this to
// the OWNER's language, which on a bilingual site — or an English site with a Portuguese client —
// is not necessarily the site's. Applied last so a translation can never override it.
// Its body counterpart is contact.mjml + _partials/_fields.mjml; see docs/language.md.
$OWNER = [
    'subject_notify' => '%s — new enquiry from the website',
];

// The form posts the language it was rendered in (see modal.js). Trimmed to a bare two-letter
// code so 'en-GB' matches 'en'; anything unknown or absent falls through to $BASE.
$lang = strtolower(substr((string) ($_POST['lang'] ?? ''), 0, 2));

// '_lang' is the resolved code, not copy — submit.php needs it to pick a per-language mail
// template, and normalizing it in two places would be one place too many. Prefixed to keep it
// visibly apart from the string keys.
return array_merge($BASE, $OVERRIDES[$lang] ?? [], $OWNER, ['_lang' => $lang]);
