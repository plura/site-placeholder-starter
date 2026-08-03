<?php
declare(strict_types=1);

/**
 * Subscribes an email to a Mailchimp audience via the Marketing API, using double opt-in
 * (Mailchimp sends its own confirmation email — this starter never sends one itself).
 * Shared by app/subscribe.php (standalone signup) and app/submit.php's optional newsletter
 * checkbox. To swap in a different ESP, this is the one function to replace.
 *
 * @param array  $config  Parsed app/config.php.
 * @param string $email   Address to subscribe; assumed already validated by the caller.
 * @param array  $strings Resolved copy from app/strings.php — the returned messages are
 *                        shown to the user, so they have to come from the caller's language.
 * @return array{success: bool, message: string}
 */
function mailchimp_subscribe(array $config, string $email, array $strings): array
{
    $apiKey = $config['mailchimp']['api_key'] ?? '';
    $listId = $config['mailchimp']['list_id'] ?? '';

    if (!$apiKey || !$listId || strpos($apiKey, '-') === false) {
        return ['success' => false, 'message' => $strings['mailchimp_not_configured']];
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

    if ($response === false) {
        error_log('Mailchimp subscribe cURL error: ' . $curlError);
        return ['success' => false, 'message' => $strings['generic_error']];
    }

    if ($statusCode >= 200 && $statusCode < 300) {
        return ['success' => true, 'message' => $strings['subscribe_confirm']];
    }

    $data = json_decode($response, true);

    // Mailchimp returns 400 with this title if the address is already on the list.
    if (($data['title'] ?? '') === 'Member Exists') {
        return ['success' => true, 'message' => $strings['already_subscribed']];
    }

    error_log('Mailchimp subscribe error: ' . $response);
    return ['success' => false, 'message' => $data['detail'] ?? $strings['generic_error']];
}
