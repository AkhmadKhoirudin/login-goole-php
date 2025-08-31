<?php
// Konfigurasi Database MySQL
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'server');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Fungsi untuk mendapatkan koneksi database
function getDbConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Log error dan tampilkan pesan yang aman
        error_log("Database connection failed: " . $e->getMessage());
        die("Koneksi database gagal. Silakan coba lagi nanti.");
    }
}

// Fungsi untuk membuat tabel users jika belum ada
function createUsersTable($pdo) {
    try {
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT(11) PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            email TEXT NOT NULL,
            username VARCHAR(50) NOT NULL,
            password VARCHAR(255) NOT NULL,
            is_blocked TINYINT(4) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY email (email),
            UNIQUE KEY username (username)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        $pdo->exec($sql);
        return true;
    } catch (PDOException $e) {
        error_log("Error creating users table: " . $e->getMessage());
        return false;
    }
}