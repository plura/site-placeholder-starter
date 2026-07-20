<?php
// Copy this file to config.php and fill in the values below.
// config.php is gitignored and must be created manually on the server.
return [
    'smtp' => [
        'host'   => '',           // e.g. mail.example.com
        'user'   => '',           // e.g. info@example.com
        'pass'   => '',
        'port'   => 587,
        'secure' => 'tls',        // 'tls' (STARTTLS/587) or 'ssl' (SMTPS/465)
    ],

    'contact' => [
        'from_email' => '',       // sending address (usually same as smtp.user)
        'from_name'  => '',

        'to_email'   => '',       // where contact notifications go
        'to_name'    => '',

        'site_name'  => '',       // used in email subject lines, e.g. "Site Name — novo contacto do site"
    ],

    // OPTIONAL — only needed for the mailing-list signup (standalone form and/or the contact
    // form's newsletter checkbox). Leave both empty and remove the newsletter feature (see
    // README) if this project doesn't need it.
    'mailchimp' => [
        'api_key' => '', // includes the datacenter suffix, e.g. abc123def-us21
        'list_id' => '', // Audience/List ID
    ],
];
