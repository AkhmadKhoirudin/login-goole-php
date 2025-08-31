<?php
// Konfigurasi Google API
$urlConfig = require 'url_config.php';

return [
    'client_id' => '947639411300-viiim4qjvq44ljb7t8e2ihfn4a4ivv7n.apps.googleusercontent.com',
    'client_secret' => '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',
    'redirect_uris' => $urlConfig['redirect_uris'],
    'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
    'token_uri' => 'https://oauth2.googleapis.com/token',
    'javascript_origins' => [
        'http://localhost',
        'http://test.akhmadkhoirudin.site'
    ]
];