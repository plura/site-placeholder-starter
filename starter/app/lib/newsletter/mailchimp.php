<?php
declare(strict_types=1);

/**
 * Adds an email to a Mailchimp audience via the Marketing API. Reached through
 * newsletter_subscribe() in lib/newsletter.php, never called directly — the credentials,
 * transport and failure logging all live there.
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
    [$apiKey, $listId] = newsletter_credentials($config);

    // The datacenter suffix isn't cosmetic — the API hostname is built from it below, so a key
    // pasted without it can't produce a valid URL at all.
    if (!$apiKey || !$listId || strpos($apiKey, '-') === false) {
        return ['success' => false, 'message' => $strings['newsletter_not_configured']];
    }

    [, $dataCenter] = explode('-', $apiKey);

    $result = newsletter_post(
        "https://{$dataCenter}.api.mailchimp.com/3.0/lists/{$listId}/members",
        ['Authorization: Basic ' . base64_encode('anystring:' . $apiKey)],
        [
            'email_address' => $email,
            // Double opt-in: Mailchimp emails the visitor and only adds them once they confirm.
            'status' => 'pending',
        ]
    );

    if ($result['failed']) {
        return newsletter_failure('Mailchimp', 'cURL error: ' . $result['error'], $strings);
    }

    if ($result['ok']) {
        return ['success' => true, 'message' => $strings['subscribe_confirm']];
    }

    // Mailchimp returns 400 with this title if the address is already on the list.
    if (($result['data']['title'] ?? '') === 'Member Exists') {
        return ['success' => true, 'message' => $strings['already_subscribed']];
    }

    return newsletter_failure('Mailchimp', $result['body'], $strings);
}
