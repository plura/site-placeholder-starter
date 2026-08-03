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

// 'newsletter' and 'lang' are control flags (see the optional opt-in block near the bottom of
// this file, and app/strings.php), not content — excluded here so they never leak into the
// email body as fields. 'lang' is surfaced deliberately via %lang% instead, see below.
$data = [];
foreach ($_POST as $key => $value) {
    if ($key === 'botcheck' || $key === 'labels' || $key === 'newsletter' || $key === 'lang' || !is_string($value)) {
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
 * @param string|null $template_path Compiled template under app/templates/, or null for text.
 * @param array       $labels        Human-readable field names posted by the form. Only used by
 *                                   the plain-text fallback — the HTML templates carry their own.
 * @param string      $lang_note     Pre-formatted language note substituted for %lang%, e.g.
 *                                   " · PT". Empty on a single-language site, rendering nothing.
 * @return string
 */
function build_body(array $data, ?string $template_path, array $labels = [], string $lang_note = ''): string {
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

    $html = str_replace(['%year%', '%date%', '%lang%'], [date('Y'), date('d/m/Y H:i'), $lang_note], $template);
    foreach ($data as $key => $value) {
        $html = str_replace('%' . $key . '%', nl2br(htmlspecialchars($value, ENT_QUOTES, 'UTF-8')), $html);
    }
    return $html;
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
// Appended to the notification's date line: the owner reads one language whatever version the
// visitor used, so this is the only place they learn which language to reply in. Empty — and so
// invisible — on a single-language site.
$lang_note = $strings['_lang'] !== '' ? ' · ' . strtoupper($strings['_lang']) : '';

$template = __DIR__ . '/templates/contact.html';
$sent = send_mail(
    $config,
    $config['contact']['to_email'],
    $config['contact']['to_name'],
    sprintf($strings['subject_notify'], $config['contact']['site_name']),
    build_body($data, $template, $labels, $lang_note),
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
send_mail(
    $config,
    $data['email'],
    $data['name'],
    sprintf($strings['subject_reply'], $config['contact']['site_name']),
    build_body($data, $reply_template, $labels),
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
