<?php
declare(strict_types=1);

date_default_timezone_set('Europe/Lisbon');

header('Content-Type: application/json; charset=UTF-8');

$strings = require __DIR__ . '/strings.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => $strings['method_not_allowed']]);
    exit;
}

// Config
if (!file_exists(__DIR__ . '/config.php')) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $strings['config_error']]);
    exit;
}
$config = require __DIR__ . '/config.php';

// PHPMailer
require_once __DIR__ . '/lib/phpmailer/Exception.php';
require_once __DIR__ . '/lib/phpmailer/PHPMailer.php';
require_once __DIR__ . '/lib/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// —— Honeypot ————————————————————————————————————————————————————————————————
if (!empty($_POST['botcheck'])) {
    // Same response shape as a real success, so nothing signals that the honeypot fired.
    echo json_encode(['success' => true, 'message' => $strings['sent']]);
    exit;
}

// —— Collect submitted fields (agnostic to whatever the form sends) ———————————
// Human-readable field labels are supplied by the form itself (from its <label> elements),
// not hardcoded here — keeps this handler agnostic to whatever fields a given form has.
$labels = [];
if (!empty($_POST['labels']) && is_string($_POST['labels'])) {
    $decoded = json_decode($_POST['labels'], true);
    if (is_array($decoded)) {
        foreach ($decoded as $key => $label) {
            if (is_string($key) && is_string($label)) {
                $labels[$key] = strip_tags(trim($label));
            }
        }
    }
}

// 'newsletter', 'lang' and 'form_type' are control flags (see the optional opt-in block near the
// bottom of this file, starter/app/strings.php, and the notification kicker below), not content —
// excluded here so they never leak into the email body as ordinary fields. Both feed into
// %kicker% deliberately instead, see build_kicker() below.
$data = [];
foreach ($_POST as $key => $value) {
    if ($key === 'botcheck' || $key === 'labels' || $key === 'newsletter' || $key === 'lang' || $key === 'form_type' || !is_string($value)) {
        continue;
    }
    $data[$key] = strip_tags(trim($value));
}

// —— Validate ————————————————————————————————————————————————————————————————
if (empty($data['name']) || empty($data['email'])) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => $strings['required_fields']]);
    exit;
}

if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => $strings['invalid_email']]);
    exit;
}

$data['email'] = filter_var($data['email'], FILTER_SANITIZE_EMAIL);

// —— Build email body ———————————————————————————————————————————————————————
/**
 * Fills a mail template with the submitted data, falling back to a plain-text list when the
 * template file is missing.
 *
 * @param array       $data          Submitted fields, keyed by input name.
 * @param string|null $template_path Compiled template under starter/app/templates/, or null for text.
 * @param array       $labels        Human-readable field names posted by the form, substituted
 *                                   for %label_<field>%. Falls back to the field's own name.
 * @param string      $kicker        Pre-built kicker line substituted for %kicker%, e.g.
 *                                   "Contact form · 06/08/2026 14:32 · PT". Built by
 *                                   build_kicker() below from whichever parts apply to this send.
 * @return string
 */
function build_body(array $data, ?string $template_path, array $labels = [], string $kicker = ''): string {
    $template = $template_path ? @file_get_contents($template_path) : false;

    if ($template === false) {
        $lines = [];
        foreach ($data as $key => $value) {
            if ($value === '') {
                continue;
            }
            $lines[] = ($labels[$key] ?? ucfirst($key)) . ': ' . $value;
        }
        return implode("\n", $lines);
    }

    $pairs = [
        '%year%'   => date('Y'),
        '%kicker%' => $kicker,
    ];

    foreach ($data as $key => $value) {
        // Field labels come from the form that was submitted, not from the template — which is
        // what makes this handler field-agnostic, and incidentally puts them in the visitor's
        // language. ucfirst() covers a direct POST that carried no labels at all (bots, curl).
        $pairs['%label_' . $key . '%'] = htmlspecialchars($labels[$key] ?? ucfirst($key), ENT_QUOTES, 'UTF-8');
        $pairs['%' . $key . '%']       = nl2br(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
    }

    // strtr(), not str_replace(): it substitutes in a single pass, so a submitted value that
    // happens to contain a %placeholder% can't be re-substituted by a later replacement.
    return strtr($template, $pairs);
}

/**
 * Joins the kicker's parts (form type, date, language) with a middle dot, dropping any that are
 * empty. A single implode replaces having each part carry its own conditional leading/trailing
 * separator — the earlier approach only worked because form type was always first and language
 * always last; this one has no position-dependent rules to keep in sync if a part is ever added,
 * removed, or reordered.
 *
 * @param string[] $parts
 * @return string
 */
function build_kicker(array $parts): string {
    return implode(' · ', array_filter($parts, fn(string $part): bool => $part !== ''));
}

function send_mail(array $cfg, string $to_email, string $to_name, string $subject, string $body, bool $is_html, string $reply_email = '', string $reply_name = ''): bool {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $cfg['smtp']['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $cfg['smtp']['user'];
        $mail->Password   = $cfg['smtp']['pass'];
        $mail->SMTPSecure = $cfg['smtp']['secure'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int) $cfg['smtp']['port'];
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($cfg['contact']['from_email'], $cfg['contact']['from_name']);
        $mail->addAddress($to_email, $to_name);

        if ($reply_email) {
            $mail->addReplyTo($reply_email, $reply_name);
        }

        $mail->isHTML($is_html);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception) {
        error_log('PHPMailer: ' . $mail->ErrorInfo);
        return false;
    }
}

// —— Notification to site owner ————————————————————————————————————————————
// Shared by both kickers below, so the notification and the reply report the exact same instant
// rather than two independent date() calls a few milliseconds apart.
$date = date('d/m/Y H:i');

// Which language version the enquiry came from — the owner's only cue as to which language to
// reply in. Empty on a single-language site, and build_kicker() drops it rather than leaving an
// orphaned separator.
$lang = $strings['_lang'] !== '' ? strtoupper($strings['_lang']) : '';

// A plain hidden field on the contact form (see index.html), not part of the generic per-field
// loop above — its value is fixed in the OWNER's language, which is why it's only ever passed
// into the notification's kicker below, never the reply's.
$form_type = htmlspecialchars(trim((string) ($_POST['form_type'] ?? '')), ENT_QUOTES, 'UTF-8');

$template = __DIR__ . '/templates/contact.html';
$sent = send_mail(
    $config,
    $config['contact']['to_email'],
    $config['contact']['to_name'],
    sprintf($strings['subject_notify'], $config['contact']['site_name']),
    build_body($data, $template, $labels, build_kicker([$form_type, $date, $lang])),
    file_exists($template),
    $data['email'],
    $data['name']
);

if (!$sent) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $strings['send_error']]);
    exit;
}

// —— Auto-reply to submitter —————————————————————————————————————————————————
// OPTIONAL (Tier 2): prefers a per-language reply template (contact-reply.pt.html) and falls
// back to the default-language one. Unlike the notification above, this email goes to the
// visitor, so it has to match the language they filled the form in — the notification goes to
// the site owner, who has just the one language, and is deliberately left alone.
$reply_template = __DIR__ . '/templates/contact-reply.html';
if ($strings['_lang'] !== '') {
    $localized = __DIR__ . "/templates/contact-reply.{$strings['_lang']}.html";
    if (file_exists($localized)) {
        $reply_template = $localized;
    }
}
// /OPTIONAL
// No form-type or language part here: form-type is fixed in the owner's language (see above),
// and language is redundant to the person who just submitted the form in it.
send_mail(
    $config,
    $data['email'],
    $data['name'],
    sprintf($strings['subject_reply'], $config['contact']['site_name']),
    build_body($data, $reply_template, $labels, build_kicker([$date])),
    file_exists($reply_template),
    $config['contact']['to_email'],
    $config['contact']['to_name']
);

// —— OPTIONAL: newsletter opt-in checkbox ————————————————————————————————————
// Only fires if the contact form includes a `newsletter` checkbox (see index.html) and it
// was checked. Best-effort: a Mailchimp failure here doesn't fail the contact submission
// itself, since the notification/reply emails have already sent successfully by this point.
// Safe to delete this block (and the checkbox in index.html) if this project has no
// newsletter, or delete the newsletter feature's other pieces per the README.
if (!empty($_POST['newsletter'])) {
    require_once __DIR__ . '/lib/mailchimp.php';
    $optin = mailchimp_subscribe($config, $data['email'], $strings);
    if (!$optin['success']) {
        error_log('Newsletter opt-in via contact form failed: ' . $optin['message']);
    }
}

// —— Done ————————————————————————————————————————————————————————————————————
echo json_encode(['success' => true, 'message' => $strings['sent']]);
