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
 *
 * The helpers below hold everything that isn't provider-specific: reading credentials, the HTTP
 * round trip, and failure logging. Every list API here is the same shape — POST JSON with an auth
 * header, read a status code — so a provider file is left holding only what genuinely differs:
 * its URL, its auth header, its payload, and how it reports an address that's already on the list.
 * Keeping the transport in one place also means a fix to it (a timeout, a header, how a non-JSON
 * error body is handled) reaches every provider instead of only the one that was being debugged.
 */

/**
 * @param array $config Parsed starter/app/config.php.
 * @return array{0: string, 1: string} API key and list ID, either possibly empty.
 */
function newsletter_credentials(array $config): array
{
    return [
        (string) ($config['newsletter']['api_key'] ?? ''),
        (string) ($config['newsletter']['list_id'] ?? ''),
    ];
}

/**
 * POSTs a JSON payload and normalizes the outcome, so providers branch on a plain array rather
 * than juggling curl handles.
 *
 * @param string   $url
 * @param string[] $authHeaders Provider-specific auth header(s); Content-Type/Accept are added.
 * @param array    $payload     Encoded as JSON.
 * @return array{failed: bool, ok: bool, status: int, body: string, data: array, error: string}
 *         `failed` means the request never completed (DNS, timeout, TLS) and `status`/`body` are
 *         meaningless. `data` is the decoded body, or [] when the response wasn't JSON — an error
 *         page from a proxy, say — so callers can read keys off it without checking the type.
 */
function newsletter_post(string $url, array $authHeaders, array $payload): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => array_merge([
            'Content-Type: application/json',
            'Accept: application/json',
        ], $authHeaders),
        CURLOPT_TIMEOUT => 10,
    ]);

    $response = curl_exec($ch);
    $status   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);

    if ($response === false) {
        return ['failed' => true, 'ok' => false, 'status' => 0, 'body' => '', 'data' => [], 'error' => $error];
    }

    $data = json_decode($response, true);

    return [
        'failed' => false,
        'ok'     => $status >= 200 && $status < 300,
        'status' => $status,
        'body'   => $response,
        'data'   => is_array($data) ? $data : [],
        'error'  => '',
    ];
}

/**
 * Logs the real reason and returns the generic user-facing message. Provider error bodies are
 * developer-facing ("Key not found", "Invalid listIds") and never shown to visitors.
 *
 * @param string $provider Label for the log line, e.g. 'Brevo'.
 * @param string $detail   Raw response body or transport error.
 * @param array  $strings
 * @return array{success: bool, message: string}
 */
function newsletter_failure(string $provider, string $detail, array $strings): array
{
    error_log("{$provider} subscribe failed: {$detail}");

    return ['success' => false, 'message' => $strings['generic_error']];
}

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
