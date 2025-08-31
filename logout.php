<?php
session_start();
require_once 'google-api-client.php';
require_once 'config.php';
require_once 'auth.php';

// Hapus session user - TIDAK menghapus dari database
if (isset($_SESSION['user'])) {
    $userEmail = $_SESSION['user']['email'];
    error_log("User logout: " . $userEmail);
    
    // Hapus session data
    session_unset();
    session_destroy();
}

// Hapus semua session data
session_destroy();

// Redirect ke halaman login
$urlConfig = require 'url_config.php';
header('Location: ' . $urlConfig['pages']['login']);
exit;
?>