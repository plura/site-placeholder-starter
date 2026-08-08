<?php
declare(strict_types=1);

/**
 * Adds an email to a Brevo list via the REST API. Reached through newsletter_subscribe() in
 * lib/newsletter.php, never called directly — the credentials, transport and failure logging all
 * live there.
 *
 * DOUBLE OPT-IN IS NOT AUTOMATIC HERE, unlike Mailchimp. This posts a plain contact, which lands
 * on the list already confirmed. To get double opt-in you configure a DOI template and redirect
 * URL in the Brevo account and use its dedicated DOI endpoint instead — account setup, not a flag
 * on this request. Until that's done, 'subscribe_confirm' in strings.php must not tell the visitor
 * to check their inbox; see the note on that string.
 *
 * @param array  $config  Parsed starter/app/config.php.
 * @param string $email   Address to subscribe; assumed already validated by the caller.
 * @param array  $strings Resolved copy from starter/app/strings.php — the returned messages are
 *                        shown to the user, so they have to come from the caller's language.
 * @return array{success: bool, message: string}
 */
function brevo_subscribe(array $config, string $email, array $strings): array
{
    [$apiKey, $listId] = newsletter_credentials($config);

    if (!$apiKey || !$listId) {
        return ['success' => false, 'message' => $strings['newsletter_not_configured']];
    }

    $result = newsletter_post(
        'https://api.brevo.com/v3/contacts',
        ['api-key: ' . $apiKey],
        [
            'email' => $email,
            // Brevo list IDs are numeric and the API rejects them as strings. config.php holds
            // whatever the account UI showed, so cast rather than trusting it was typed unquoted.
            'listIds' => [(int) $listId],
            // Without this, re-submitting an existing address is a 400 rather than a no-op. With
            // it, an already-subscribed visitor gets a clean success instead of an error they
            // can't act on.
            'updateEnabled' => true,
        ]
    );

    if ($result['failed']) {
        return newsletter_failure('Brevo', 'cURL error: ' . $result['error'], $strings);
    }

    // 201 for a new contact, 204 for an existing one updated via updateEnabled.
    if ($result['ok']) {
        return ['success' => true, 'message' => $strings['subscribe_confirm']];
    }

    // Not reachable while updateEnabled is true, but kept so the mapping still holds if someone
    // turns that off to stop existing contacts' attributes being overwritten.
    if (($result['data']['code'] ?? '') === 'duplicate_parameter') {
        return ['success' => true, 'message' => $strings['already_subscribed']];
    }

    return newsletter_failure('Brevo', $result['body'], $strings);
}
