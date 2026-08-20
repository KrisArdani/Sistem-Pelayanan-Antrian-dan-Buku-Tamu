<?php
// SPST BPS Kota Tegal - Global System Configuration & Environment Loader

// Setel Zona Waktu Resmi Indonesia Barat (WIB)
date_default_timezone_set('Asia/Jakarta');

/**
 * Fungsi pembantu untuk memuat variabel dari file .env
 */
if (!function_exists('loadEnvFile')) {
    function loadEnvFile(string $filePath): void
    {
        if (!file_exists($filePath)) {
            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            // Lewati baris komentar atau baris kosong
            if (empty($line) || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Hilangkan tanda kutip jika ada
            $value = trim($value, '"\'');

            if (!empty($key)) {
                // Setel ke $_ENV dan putenv jika belum ada di environment sistem
                if (getenv($key) === false) {
                    putenv("{$key}={$value}");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }
    }
}

// Muat file .env lokal jika tersedia
loadEnvFile(__DIR__ . '/.env');

/**
 * Helper untuk mengambil nilai environment dengan nilai default
 */
if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $val = getenv($key);
        if ($val === false) {
            return $_ENV[$key] ?? $default;
        }

        // Konversi tipe data boolean / integer sederhana
        return match (strtolower($val)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'empty', '(empty)' => '',
            'null', '(null)' => null,
            default => $val,
        };
    }
}

// 1. Konfigurasi Database
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_NAME', env('DB_NAME', 'db_spst'));

// 2. Konstanta Keamanan & Sesi
define('CSRF_TOKEN_NAME', env('CSRF_TOKEN_NAME', 'spst_csrf_token'));
define('SESSION_TIMEOUT', (int) env('SESSION_TIMEOUT', 3600));
define('MAX_LOGIN_ATTEMPTS', (int) env('MAX_LOGIN_ATTEMPTS', 5));
define('LOGIN_LOCKOUT_TIME', (int) env('LOGIN_LOCKOUT_TIME', 900));

// 3. Konfigurasi Lingkungan ('development' atau 'production')
define('APP_ENV', env('APP_ENV', 'development'));

// 4. Konfigurasi Resmi Web API BPS
define('BPS_API_KEY', env('BPS_API_KEY', ''));
define('BPS_API_BASE', env('BPS_API_BASE', 'https://webapi.bps.go.id/v1/api'));
define('BPS_DOMAIN_KOTA_TEGAL', env('BPS_DOMAIN_KOTA_TEGAL', '3376'));
define('BPS_DOMAIN_PROV_JATENG', env('BPS_DOMAIN_PROV_JATENG', '3300'));
define('BPS_DOMAIN_PUSAT', env('BPS_DOMAIN_PUSAT', '0000'));
define('BPS_CACHE_DIR', __DIR__ . '/cache/bps');
define('BPS_CACHE_TTL', (int) env('BPS_CACHE_TTL', 86400));
?>
