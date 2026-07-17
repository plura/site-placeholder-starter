<?php
// Copy this file to config.php and fill in the values below.
// config.php is gitignored and must be created manually on the server.
return [
    'smtp_host'   => '',           // e.g. mail.example.com
    'smtp_user'   => '',           // e.g. info@example.com
    'smtp_pass'   => '',
    'smtp_port'   => 587,
    'smtp_secure' => 'tls',        // 'tls' (STARTTLS/587) or 'ssl' (SMTPS/465)

    'from_email'  => '',           // sending address (usually same as smtp_user)
    'from_name'   => '',

    'to_email'    => '',           // where contact notifications go
    'to_name'     => '',

    'site_name'   => '',           // used in email subject lines, e.g. "Site Name — novo contacto do site"
];
