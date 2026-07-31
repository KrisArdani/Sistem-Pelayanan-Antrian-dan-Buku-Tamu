<?php
// TOBASA BPS Kota Tegal - Global Security & System Configuration

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Ubah ke user khusus di environment produksi
define('DB_PASS', '');     // Ubah ke password kuat di environment produksi
define('DB_NAME', 'db_tobasa');

// Security Constants
define('CSRF_TOKEN_NAME', 'tobasa_csrf_token');
define('SESSION_TIMEOUT', 3600); // Session expired setelah 1 jam inaktif
define('MAX_LOGIN_ATTEMPTS', 5); // Maksimal percobaan login
define('LOGIN_LOCKOUT_TIME', 900); // Lockout 15 menit jika gagal 5x

// Environment Configuration ('development' atau 'production')
define('APP_ENV', 'development');
?>
