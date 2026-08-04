<?php
// TOBASA BPS Kota Tegal - Konfigurasi Keamanan & Sistem Global

// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Ubah ke user khusus di lingkungan produksi
define('DB_PASS', '');     // Ubah ke kata sandi kuat di lingkungan produksi
define('DB_NAME', 'db_tobasa');

// Konstanta Keamanan
define('CSRF_TOKEN_NAME', 'tobasa_csrf_token');
define('SESSION_TIMEOUT', 3600); // Sesi kedaluwarsa setelah 1 jam tidak aktif
define('MAX_LOGIN_ATTEMPTS', 5); // Maksimal percobaan login
define('LOGIN_LOCKOUT_TIME', 900); // Penguncian 15 menit jika gagal 5 kali

// Konfigurasi Lingkungan ('development' atau 'production')
define('APP_ENV', 'development');
?>
