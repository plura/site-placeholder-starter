<?php
declare(strict_types=1);

date_default_timezone_set('Europe/Lisbon');

header('Content-Type: application/json; charset=UTF-8');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

// Config
if (!file_exists(__DIR__ . '/config.php')) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro de configuração do servidor.']);
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
    echo json_encode(['success' => true]);
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

$data = [];
foreach ($_POST as $key => $value) {
    if ($key === 'botcheck' || $key === 'labels' || !is_string($value)) {
        continue;
    }
    $data[$key] = strip_tags(trim($value));
}

// —— Validate ————————————————————————————————————————————————————————————————
if (empty($data['name']) || empty($data['email'])) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Por favor preencha os campos obrigatórios.']);
    exit;
}

if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Por favor introduza um endereço de email válido.']);
    exit;
}

$data['email'] = filter_var($data['email'], FILTER_SANITIZE_EMAIL);

// —— Build email body ———————————————————————————————————————————————————————
function build_body(array $data, ?string $template_path, array $labels = []): string {
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

    $html = str_replace(['%year%', '%date%'], [date('Y'), date('d/m/Y H:i')], $template);
    foreach ($data as $key => $value) {
        $html = str_replace('%' . $key . '%', nl2br(htmlspecialchars($value, ENT_QUOTES, 'UTF-8')), $html);
    }
    return $html;
}

function send_mail(array $cfg, string $to_email, string $to_name, string $subject, string $body, bool $is_html, string $reply_email = '', string $reply_name = ''): bool {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $cfg['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $cfg['smtp_user'];
        $mail->Password   = $cfg['smtp_pass'];
        $mail->SMTPSecure = $cfg['smtp_secure'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int) $cfg['smtp_port'];
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($cfg['from_email'], $cfg['from_name']);
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
$template = __DIR__ . '/templates/contact.html';
$sent = send_mail(
    $config,
    $config['to_email'],
    $config['to_name'],
    "{$config['site_name']} — novo contacto do site",
    build_body($data, $template, $labels),
    file_exists($template),
    $data['email'],
    $data['name']
);

if (!$sent) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao enviar. Por favor tente novamente ou contacte-nos por email.']);
    exit;
}

// —— Auto-reply to submitter —————————————————————————————————————————————————
$reply_template = __DIR__ . '/templates/contact-reply.html';
send_mail(
    $config,
    $data['email'],
    $data['name'],
    "{$config['site_name']} — recebemos o seu contacto",
    build_body($data, $reply_template, $labels),
    file_exists($reply_template),
    $config['to_email'],
    $config['to_name']
);

// —— Done ————————————————————————————————————————————————————————————————————
echo json_encode(['success' => true]);
