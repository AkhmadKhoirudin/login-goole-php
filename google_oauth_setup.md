# Panduan Setup Google OAuth untuk Aplikasi

## Masalah: Error 400 redirect_uri_mismatch

Error ini terjadi karena URI redirect yang dikirim oleh aplikasi tidak cocok dengan yang terdaftar di Google Cloud Console.

## Langkah Perbaikan:

### 1. Login ke Google Cloud Console
- Buka https://console.cloud.google.com/
- Pilih project yang sedang Anda gunakan

### 2. Update OAuth 2.0 Client ID
1. Buka menu **APIs & Services** > **Credentials**
2. Cari OAuth 2.0 Client ID yang digunakan untuk aplikasi ini
3. Klik **EDIT** (ikon pensil)
4. Pada bagian **Authorized redirect URIs**, tambahkan URL berikut:

### 3. Redirect URI yang Harus Ditambahkan
```
http://localhost/login_google_mysql/callback.php
http://test.akhmadkhoirudin.site/login_google_mysql/callback.php
```

### 4. Struktur URL yang Benar
Pastikan URL persis seperti di atas, termasuk:
- Protocol: `http://` (untuk development)
- Domain: `localhost` atau `test.akhmadkhoirudin.site`
- Path: `/login_google_mysql/callback.php`

### 5. Simpan Perubahan
- Klik **SAVE** untuk menyimpan perubahan

## Verifikasi:
Setelah mengupdate, coba login dengan Google OAuth lagi. Error seharusnya tidak muncul lagi.

## Troubleshooting:
Jika masih ada error:
1. Pastikan URL tidak ada trailing slash tambahan
2. Pastikan URL tidak ada typo
3. Clear browser cache
4. Coba di browser private/incognito