<?php
// Konfigurasi URL untuk aplikasi
return [
    // Base URL untuk development
    'base_url' => [
        'localhost' => 'http://localhost/login_google_mysql',
        'test' => 'http://test.akhmadkhoirudin.site/login_google_mysql'
    ],
    
    // Redirect URI untuk Google OAuth - sesuai dengan struktur folder yang sebenarnya
    'redirect_uris' => [
        'http://localhost/login_google_mysql/callback.php',
        'http://test.akhmadkhoirudin.site/login_google_mysql/callback.php'
    ],
    
    // Halaman login dan lainnya
    'pages' => [
        'login' => 'index.php',
        'home' => 'home.php',
        'callback' => 'callback.php',
        'logout' => 'logout.php'
    ],
    
    // API endpoints jika diperlukan
    'api' => [
        'base' => 'api/'
    ]
];