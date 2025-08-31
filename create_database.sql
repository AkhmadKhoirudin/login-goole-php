-- Script untuk membuat database dan tabel users
-- Server: 127.0.0.1
-- Database: server
-- Tabel: users
git 
-- Buat database jika belum ada
CREATE DATABASE IF NOT EXISTS server DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- Gunakan database server
USE server;

-- Buat tabel users jika belum ada
CREATE TABLE IF NOT EXISTS users (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email TEXT NOT NULL,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    is_blocked TINYINT(4) DEFAULT 0,
    role ENUM('admin', 'sekben1', 'sekben2', 'pending', 'TU') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Tambahkan unique constraint untuk email dan username
    UNIQUE KEY email (email),
    UNIQUE KEY username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tambahkan komentar untuk tabel
ALTER TABLE users COMMENT 'Tabel untuk menyimpan data pengguna sistem';

-- Tambahkan komentar untuk kolom
ALTER TABLE users MODIFY id INT(11) AUTO_INCREMENT COMMENT 'ID unik untuk setiap pengguna';
ALTER TABLE users MODIFY name VARCHAR(100) NOT NULL COMMENT 'Nama lengkap pengguna';
ALTER TABLE users MODIFY email TEXT NOT NULL COMMENT 'Alamat email pengguna';
ALTER TABLE users MODIFY username VARCHAR(50) NOT NULL COMMENT 'Username unik pengguna';
ALTER TABLE users MODIFY password VARCHAR(255) NOT NULL COMMENT 'Password terenkripsi pengguna';
ALTER TABLE users MODIFY is_blocked TINYINT(4) DEFAULT 0 COMMENT 'Status blokir (0=aktif, 1=diblokir)';
ALTER TABLE users MODIFY created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu pembuatan akun';

-- Insert sample admin user (password: admin123)
INSERT INTO users (name, email, username, password, is_blocked) 
VALUES ('Administrator', 'admin@example.com', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0)
ON DUPLICATE KEY UPDATE name = name;

-- Tampilkan struktur tabel
DESCRIBE users;