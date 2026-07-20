<?php
declare(strict_types=1);

// Subscribes an email to a Mailchimp audience via the Marketing API, using double opt-in
// (Mailchimp sends its own confirmation email — this starter never sends one itself).
// Shared by app/subscribe.php (standalone signup) and app/submit.php's optional newsletter
// checkbox. To swap in a different ESP, this is the one function to replace.
function mailchimp_subscribe(array $config, string $email): array
{
    $apiKey = $config['mailchimp']['api_key'] ?? '';
    $listId = $config['mailchimp']['list_id'] ?? '';

    if (!$apiKey || !$listId || strpos($apiKey, '-') === false) {
        return ['success' => false, 'message' => 'A subscrição não está configurada corretamente.'];
    }

    [, $dataCenter] = explode('-', $apiKey);
    $url = "https://{$dataCenter}.api.mailchimp.com/3.0/lists/{$listId}/members";

    $payload = json_encode([
        'email_address' => $email,
        'status'        => 'pending',
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode('anystring:' . $apiKey),
        ],
        CURLOPT_TIMEOUT => 10,
    ]);

    $response   = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError  = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        error_log('Mailchimp subscribe cURL error: ' . $curlError);
        return ['success' => false, 'message' => 'Ocorreu um erro. Por favor tente novamente mais tarde.'];
    }

    if ($statusCode >= 200 && $statusCode < 300) {
        return ['success' => true, 'message' => 'Obrigado por subscrever! Verifique o seu email para confirmar a subscrição.'];
    }

    $data = json_decode($response, true);

    // Mailchimp returns 400 with this title if the address is already on the list.
    if (($data['title'] ?? '') === 'Member Exists') {
        return ['success' => true, 'message' => 'Já está subscrito!'];
    }

    error_log('Mailchimp subscribe error: ' . $response);
    return ['success' => false, 'message' => $data['detail'] ?? 'Ocorreu um erro. Por favor tente novamente mais tarde.'];
}
