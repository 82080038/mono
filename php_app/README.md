# Aplikasi Manajemen Koperasi KSP Lam Gabe Jaya (PHP)

Aplikasi web sederhana untuk mengelola anggota, pinjaman, dan membuat formulir surat untuk koperasi simpan pinjam.

## Fitur

- Kelola data anggota
- Kelola data pinjaman
- Buat formulir surat permohonan anggota, pinjaman, lamaran kerja, dan kesepakatan bersama
- Generate teks surat yang dapat disalin ke PDF

## Instalasi

1. Buat database MySQL di hosting Anda, misalnya nama 'ksp'.
2. Update config.php dengan kredensial database Anda (host, nama db, username, password).
3. Upload semua file ke shared hosting.
4. Akses index.php melalui browser. Tabel akan dibuat otomatis.

## Catatan

- Menggunakan MySQL untuk database.
- Untuk generate PDF, salin teks ke editor seperti Microsoft Word atau Google Docs.
- Jika ingin PDF otomatis, install library seperti FPDF di hosting.
