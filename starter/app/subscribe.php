<?php
// OPTIONAL: only needed if this project uses the mailing-list signup. Safe to delete this
// file (along with starter/app/lib/newsletter.php and lib/newsletter/, the newsletter config block, and the frontend
// pieces listed in the README) if the project doesn't need it.
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

$strings = require __DIR__ . '/strings.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => $strings['method_not_allowed']]);
    exit;
}

if (!file_exists(__DIR__ . '/config.php')) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $strings['config_error']]);
    exit;
}
$config = require __DIR__ . '/config.php';

require_once __DIR__ . '/lib/newsletter.php';

// —— Honeypot ————————————————————————————————————————————————————————————————
if (!empty($_POST['botcheck'])) {
    // Same response shape as a real success, so nothing signals that the honeypot fired.
    echo json_encode(['success' => true, 'message' => $strings['subscribe_confirm']]);
    exit;
}

$email = trim((string) ($_POST['email'] ?? ''));

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => $strings['invalid_email']]);
    exit;
}

$email = filter_var($email, FILTER_SANITIZE_EMAIL);

$result = newsletter_subscribe($config, $email, $strings);

http_response_code($result['success'] ? 200 : 500);
echo json_encode($result);
