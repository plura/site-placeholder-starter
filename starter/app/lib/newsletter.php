<?php
declare(strict_types=1);

/**
 * OPTIONAL: mailing list. Safe to delete this file (along with the whole lib/newsletter/ folder,
 * starter/app/subscribe.php, the newsletter config block, and the frontend pieces listed in the
 * README) if the project has no mailing list.
 *
 * Dispatches a subscribe call to whichever provider config.php names. The kit never sends
 * campaign email itself — it only puts the address on the client's list, so they can run
 * campaigns in the provider's own tools.
 *
 * The provider is named explicitly rather than inferred from whichever credentials happen to be
 * filled in. config.php starts as a copy of config.example.php with every field present but
 * empty, so a half-completed second provider would otherwise change where subscribers land
 * silently — discovered only when the client can't find them. A wrong name fails loudly here.
 *
 * Adding a provider: write lib/newsletter/<name>.php exposing <name>_subscribe() with this same
 * signature and return shape, then add one case below. Nothing else changes — subscribe.php and
 * submit.php only ever call newsletter_subscribe(), and only the provider named in config.php is
 * ever loaded.
 */

/**
 * @param array  $config  Parsed starter/app/config.php.
 * @param string $email   Address to subscribe; assumed already validated by the caller.
 * @param array  $strings Resolved copy from starter/app/strings.php — the returned messages are
 *                        shown to the user, so they have to come from the caller's language.
 * @return array{success: bool, message: string}
 */
function newsletter_subscribe(array $config, string $email, array $strings): array
{
    $provider = $config['newsletter']['provider'] ?? '';

    switch ($provider) {
        case 'brevo':
            require_once __DIR__ . '/newsletter/brevo.php';
            return brevo_subscribe($config, $email, $strings);

        case 'mailchimp':
            require_once __DIR__ . '/newsletter/mailchimp.php';
            return mailchimp_subscribe($config, $email, $strings);
    }

    // Covers both an empty provider (config.php never filled in) and a typo'd one. Logged with
    // the offending value because "not configured correctly" on its own sends you looking at the
    // API key when the actual problem is one wrong word.
    error_log('Newsletter: unknown provider ' . var_export($provider, true));

    return ['success' => false, 'message' => $strings['newsletter_not_configured']];
}
