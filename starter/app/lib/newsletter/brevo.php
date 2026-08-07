<?php
declare(strict_types=1);

/**
 * Adds an email to a Brevo list via the REST API. Reached through newsletter_subscribe() in
 * lib/newsletter.php, never called directly.
 *
 * DOUBLE OPT-IN IS NOT AUTOMATIC HERE, unlike Mailchimp. This posts a plain contact, which lands
 * on the list confirmed. To get double opt-in you configure a DOI template and redirection URL in
 * the Brevo account and use its dedicated DOI endpoint instead — account setup, not a flag on this
 * request. Until that's done, 'subscribe_confirm' in strings.php is wrong: it tells the visitor to
 * check their inbox for a confirmation that will never arrive. See the note on that string.
 *
 * @param array  $config  Parsed starter/app/config.php.
 * @param string $email   Address to subscribe; assumed already validated by the caller.
 * @param array  $strings Resolved copy from starter/app/strings.php — the returned messages are
 *                        shown to the user, so they have to come from the caller's language.
 * @return array{success: bool, message: string}
 */
function brevo_subscribe(array $config, string $email, array $strings): array
{
    $apiKey = $config['newsletter']['api_key'] ?? '';
    $listId = $config['newsletter']['list_id'] ?? '';

    if (!$apiKey || !$listId) {
        return ['success' => false, 'message' => $strings['newsletter_not_configured']];
    }

    $payload = json_encode([
        'email'         => $email,
        // Brevo list IDs are numeric, and the API rejects them as strings. config.php holds
        // whatever the account UI showed, so cast rather than trusting it was typed unquoted.
        'listIds'       => [(int) $listId],
        // Without this, re-submitting an existing address is a 400 rather than a no-op. With it,
        // an already-subscribed visitor gets a clean success instead of an error they can't act on.
        'updateEnabled' => true,
    ]);

    $ch = curl_init('https://api.brevo.com/v3/contacts');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
            'api-key: ' . $apiKey,
        ],
        CURLOPT_TIMEOUT => 10,
    ]);

    $response   = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError  = curl_error($ch);

    if ($response === false) {
        error_log('Brevo subscribe cURL error: ' . $curlError);
        return ['success' => false, 'message' => $strings['generic_error']];
    }

    // 201 for a new contact, 204 for an existing one updated via updateEnabled.
    if ($statusCode >= 200 && $statusCode < 300) {
        return ['success' => true, 'message' => $strings['subscribe_confirm']];
    }

    $data = json_decode($response, true);

    // Not reachable while updateEnabled is true, but kept so the mapping still holds if someone
    // turns that off to stop existing contacts' attributes being overwritten.
    if (($data['code'] ?? '') === 'duplicate_parameter') {
        return ['success' => true, 'message' => $strings['already_subscribed']];
    }

    // Brevo's 'message' is developer-facing ("Key not found", "Invalid listIds") — logged, never
    // shown, unlike Mailchimp's 'detail' which the old code surfaced to visitors.
    error_log('Brevo subscribe error: ' . $response);

    return ['success' => false, 'message' => $strings['generic_error']];
}
