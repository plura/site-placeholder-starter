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

        'site_name'  => '',       // used in email subject lines, e.g. "Site Name — new enquiry from the website"
    ],

    // OPTIONAL — only needed for the mailing-list signup (standalone form and/or the contact
    // form's newsletter checkbox). Leave empty and remove the newsletter feature (see README) if
    // this project doesn't need it.
    //
    // The kit only puts addresses on the list; it never sends campaigns. Those are the client's
    // to run in the provider's own tools, which is the main reason to pick one provider over
    // another — whichever the client already uses, or would rather learn.
    'newsletter' => [
        // 'brevo' or 'mailchimp'. Named explicitly rather than guessed from which credentials are
        // filled in: this file starts as a copy of this example with every field present but
        // empty, so inferring it would let a half-finished second provider quietly take over.
        'provider' => 'brevo',

        // Brevo:     Profile -> SMTP & API -> API Keys (v3). List ID is the number in the URL
        //            when the list is open, or the ID column in Contacts -> Lists.
        // Mailchimp: Account -> Extras -> API keys. The key must keep its datacenter suffix
        //            (abc123def-us21) — the API hostname is built from it. List ID is the
        //            Audience ID under Audience -> Settings -> Audience name and defaults.
        'api_key' => '',
        'list_id' => '',
    ],
];
