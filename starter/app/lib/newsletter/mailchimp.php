<?php
declare(strict_types=1);

/**
 * Adds an email to a Mailchimp audience via the Marketing API. Reached through
 * newsletter_subscribe() in lib/newsletter.php, never called directly.
 *
 * Double opt-in comes free here: posting status 'pending' makes Mailchimp send its own
 * confirmation email, so the address isn't on the list until the visitor clicks through. Brevo
 * needs account-side setup for the same thing — see the note in lib/newsletter/brevo.php.
 *
 * @param array  $config  Parsed starter/app/config.php.
 * @param string $email   Address to subscribe; assumed already validated by the caller.
 * @param array  $strings Resolved copy from starter/app/strings.php — the returned messages are
 *                        shown to the user, so they have to come from the caller's language.
 * @return array{success: bool, message: string}
 */
function mailchimp_subscribe(array $config, string $email, array $strings): array
{
    $apiKey = $config['newsletter']['api_key'] ?? '';
    $listId = $config['newsletter']['list_id'] ?? '';

    // The datacenter suffix isn't cosmetic — the API hostname is built from it below, so a key
    // pasted without it can't produce a valid URL at all.
    if (!$apiKey || !$listId || strpos($apiKey, '-') === false) {
        return ['success' => false, 'message' => $strings['newsletter_not_configured']];
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
