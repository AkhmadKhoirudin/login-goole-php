<?php
session_start();
require_once 'google-api-client.php';
require_once 'config.php';
require_once 'auth.php';

// Handle Google login callback
$client = getGoogleClient();
if (isset($_GET['code'])) {
    $loginResult = handleGoogleLogin($client);
    
    if ($loginResult === true) {
        // Login berhasil
        $urlConfig = require 'url_config.php';
        header('Location: ' . $urlConfig['pages']['home']);
        exit;
    } elseif ($loginResult === 'pending') {
        // Login gagal - akun pending
        $_SESSION['login_error'] = '<i class="fas fa-clock"></i> Akun Anda masih <strong>menunggu persetujuan</strong> administrator. Silakan tunggu paling lama 1x24 jam.';
        $urlConfig = require 'url_config.php';
        header('Location: ' . $urlConfig['pages']['login']);
        exit;
    } elseif ($loginResult === 'blocked') {
        // Login gagal - akun diblokir
        $_SESSION['login_error'] = '<i class="fas fa-ban"></i> Akun Anda telah <strong>diblokir</strong> oleh administrator. Silakan hubungi administrator untuk aktivasi kembali.';
        $urlConfig = require 'url_config.php';
        header('Location: ' . $urlConfig['pages']['login']);
        exit;
    } else {
        // Login gagal - error lain
        $_SESSION['login_error'] = '<i class="fas fa-exclamation-circle"></i> Login gagal. Silakan coba lagi.';
        $urlConfig = require 'url_config.php';
        header('Location: ' . $urlConfig['pages']['login']);
        exit;
    }
}

// Jika tidak ada kode, redirect ke login
$urlConfig = require 'url_config.php';
header('Location: ' . $urlConfig['pages']['login']);
exit;
?>