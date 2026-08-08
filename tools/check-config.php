#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Fails if the markup offers a feature that config.php can't actually serve.
 *
 *     npm run check:config      (or: php tools/check-config.php)
 *
 * The mistake this exists to catch is a half-configured deploy: a form the visitor can see and
 * fill in, backed by credentials nobody filled in. That state is invisible locally — the page
 * looks finished — and only surfaces when a real visitor submits and gets an error, by which
 * point their address is gone.
 *
 * It deliberately does NOT fail when config.php is absent entirely. That's the documented state
 * of a fresh installation, and it fails loudly on its own the moment any endpoint is hit ("Server
 * configuration error"). The subtle case is a config.php that looks complete because the contact
 * half is filled in and the newsletter half isn't.
 *
 * Values are never printed — only which keys are empty.
 */

$root   = dirname(__DIR__);
$config = $root . '/starter/app/config.php';

if (!file_exists($config)) {
    echo "No starter/app/config.php yet — nothing to check.\n";
    echo "(Expected on a fresh installation; copy config.example.php and fill it in before deploying.)\n";
    exit(0);
}

/** Every page that can carry a form: the root page plus any language directory beside it. */
$pages = array_merge(
    glob($root . '/index.html') ?: [],
    glob($root . '/??/index.html') ?: []
);

$markup = '';
foreach ($pages as $page) {
    // Comments are stripped so a feature that ships commented out — Tier 2's switcher, an
    // OPTIONAL block someone disabled rather than deleted — doesn't count as present.
    $markup .= preg_replace('/<!--.*?-->/s', '', (string) file_get_contents($page));
}

$cfg = require $config;

/**
 * @param array    $cfg
 * @param string[] $path Nested keys, e.g. ['smtp', 'host'].
 */
function filled(array $cfg, array $path): bool
{
    $value = $cfg;
    foreach ($path as $key) {
        if (!is_array($value) || !isset($value[$key])) {
            return false;
        }
        $value = $value[$key];
    }

    return is_string($value) ? trim($value) !== '' : !empty($value);
}

$problems = [];

// —— Contact form ————————————————————————————————————————————————————————————
if (str_contains($markup, 'id="contact-form"')) {
    foreach ([['smtp', 'host'], ['smtp', 'user'], ['smtp', 'pass'], ['contact', 'from_email'], ['contact', 'to_email']] as $path) {
        if (!filled($cfg, $path)) {
            $problems[] = 'contact form is in the markup, but config.php has no ' . implode('.', $path);
        }
    }
}

// —— OPTIONAL: mailing list ——————————————————————————————————————————————————
// Either entry point counts: the standalone signup, or the opt-in checkbox on the contact form.
$hasNewsletter = str_contains($markup, 'id="newsletter-form"')
    || str_contains($markup, 'name="newsletter"');

if ($hasNewsletter) {
    $provider = $cfg['newsletter']['provider'] ?? '';
    $known    = ['brevo', 'mailchimp'];

    if (!in_array($provider, $known, true)) {
        $problems[] = 'newsletter signup is in the markup, but config.php\'s newsletter.provider is '
            . var_export($provider, true) . ' (expected one of: ' . implode(', ', $known) . ')';
    } else {
        foreach ([['newsletter', 'api_key'], ['newsletter', 'list_id']] as $path) {
            if (!filled($cfg, $path)) {
                $problems[] = 'newsletter signup is in the markup, but config.php has no ' . implode('.', $path);
            }
        }
    }
}
// /OPTIONAL

if ($problems) {
    echo "Config doesn't match the markup:\n\n";
    foreach ($problems as $problem) {
        echo "  - {$problem}\n";
    }
    echo "\nFill these in, or remove the feature from the markup — see \"Optional features\" in the README.\n";
    exit(1);
}

echo "OK — every feature in the markup has the config it needs.\n";
