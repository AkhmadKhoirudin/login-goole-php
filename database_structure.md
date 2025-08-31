# Struktur Database Aplikasi Login

## Server: 127.0.0.1
## Database: server
## Tabel: users
**Keterangan**: Tabel untuk menyimpan data pengguna sistem

## Struktur Tabel users

| No | Nama Kolom | Tipe Data | Atribut | Default | Keterangan |
|----|------------|-----------|---------|---------|------------|
| 1 | id | int(11) | PRIMARY KEY | AUTO_INCREMENT | ID unik untuk setiap pengguna |
| 2 | name | varchar(100) | NOT NULL | - | Nama lengkap pengguna |
| 3 | email | text | NOT NULL | - | Alamat email pengguna (UNIQUE) |
| 4 | username | varchar(50) | NOT NULL | - | Username unik pengguna (UNIQUE) |
| 5 | password | varchar(255) | NOT NULL | - | Password terenkripsi pengguna |
| 6 | is_blocked | tinyint(4) | - | 0 | Status blokir (0=aktif, 1=diblokir) |
| 7 | role | enum | - | 'pending' | Role pengguna (admin, sekben1, sekben2, pending, TU) |
| 8 | created_at | datetime | - | CURRENT_TIMESTAMP | Waktu pembuatan akun |

## Enum Values untuk Role
- `admin` - Administrator sistem
- `sekben1` - Sekretaris Bendahara 1
- `sekben2` - Sekretaris Bendahara 2
- `pending` - Menunggu persetujuan (default)
- `TU` - Tata Usaha

## Status Akun
- **is_blocked = 0** - Akun aktif
- **is_blocked = 1** - Akun diblokir
- **role = 'pending'** - Akun menunggu persetujuan admin

## Index dan Constraints
- **PRIMARY KEY**: id
- **UNIQUE KEY**: email
- **UNIQUE KEY**: username

## Contoh Data
```sql
-- Admin user (password: admin123)
INSERT INTO users (name, email, username, password, is_blocked, role) 
VALUES ('Administrator', 'admin@example.com', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, 'admin');
```

## Query Penting
### Cek status akun user
```sql
SELECT id, name, email, username, is_blocked, role, created_at 
FROM users 
WHERE username = 'username';
```

### Update role user
```sql
UPDATE users SET role = 'admin' WHERE username = 'username';
```

### Block/unblock user
```sql
-- Blokir user
UPDATE users SET is_blocked = 1 WHERE username = 'username';

-- Aktifkan kembali user
UPDATE users SET is_blocked = 0 WHERE username = 'username';
```

## File Terkait
- `create_database.sql` - Script pembuatan database dan tabel
- `db_config.php` - Konfigurasi koneksi database
- `auth.php` - Fungsi autentikasi dan manajemen user
- `users.php` - Halaman manajemen user (admin)