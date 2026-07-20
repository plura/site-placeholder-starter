<?php
// OPTIONAL: only needed if this project uses the mailing-list signup. Safe to delete this
// file (along with app/lib/mailchimp.php, the mailchimp config block, and the frontend
// pieces listed in the README) if the project doesn't need it.
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

if (!file_exists(__DIR__ . '/config.php')) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro de configuração do servidor.']);
    exit;
}
$config = require __DIR__ . '/config.php';

require_once __DIR__ . '/lib/mailchimp.php';

// —— Honeypot ————————————————————————————————————————————————————————————————
if (!empty($_POST['botcheck'])) {
    echo json_encode(['success' => true]);
    exit;
}

$email = trim((string) ($_POST['email'] ?? ''));

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Por favor introduza um endereço de email válido.']);
    exit;
}

$email = filter_var($email, FILTER_SANITIZE_EMAIL);

$result = mailchimp_subscribe($config, $email);

http_response_code($result['success'] ? 200 : 500);
echo json_encode($result);
