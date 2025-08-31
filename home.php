<?php
session_start();
require_once 'google-api-client.php';
require_once 'config.php';
require_once 'auth.php';

// Periksa apakah pengguna sudah login
requireLogin();

// Tampilkan informasi pengguna
echo "<h1>Selamat datang, " . htmlspecialchars($_SESSION['user']['name']) . "!</h1>";
echo "<p>Email: " . htmlspecialchars($_SESSION['user']['email']) . "</p>";

// Tambahkan navigasi
echo '<br><br>';
echo '<a href="users.php">Manajemen User</a> | ';
echo '<a href="logout.php">Logout</a>';
?>