<?php
// SPST BPS Kota Tegal - Sistem Pelayanan Statistik Terpadu
// Database Connection Manager & Response Dispatcher

require_once __DIR__ . '/config.php';

$host   = DB_HOST;
$user   = DB_USER;
$pass   = DB_PASS;
$dbname = DB_NAME;

// Hubungkan langsung ke server MySQL & database aktif
$conn = @new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    error_log("[SPST DB ERROR] Gagal terhubung ke MySQL ($host/$dbname): " . $conn->connect_error);

    $isAjaxOrApi = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'))
        || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
        || (isset($_SERVER['SCRIPT_NAME']) && str_contains($_SERVER['SCRIPT_NAME'], 'api'));

    if ($isAjaxOrApi) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'status'  => 'error',
            'message' => 'Layanan database server sedang tidak dapat diakses. Silakan hubungi administrator sistem.'
        ]);
        exit;
    } else {
        http_response_code(500);
        echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Kendala Koneksi Database - SPST BPS Kota Tegal</title>'
            . '<style>body{font-family:system-ui,-apple-system,sans-serif;background:#0b132b;color:#f8fafc;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:1rem;}'
            . '.card{background:#1c2541;padding:2.5rem;border-radius:1.25rem;max-width:480px;text-align:center;box-shadow:0 20px 40px rgba(0,0,0,0.5);border:1px solid #3a506b;}'
            . 'h1{color:#f87171;font-size:1.35rem;margin-bottom:0.75rem;font-weight:700;}p{color:#94a3b8;font-size:0.9rem;line-height:1.6;margin-bottom:1.5rem;}'
            . '.btn{display:inline-block;background:#0284c7;color:#fff;text-decoration:none;font-weight:600;font-size:0.85rem;padding:0.6rem 1.4rem;border-radius:0.75rem;}</style></head>'
            . '<body><div class="card"><h1>Kendala Koneksi Database</h1>'
            . '<p>Sistem Pelayanan Statistik Terpadu sedang mengalami kendala komunikasi dengan server database. Silakan muat ulang halaman beberapa saat lagi.</p>'
            . '<a href="javascript:location.reload()" class="btn">Muat Ulang Halaman</a>'
            . '</div></body></html>';
        exit;
    }
}

// Atur set karakter (charset) ke utf8mb4
$conn->set_charset("utf8mb4");

/**
 * Fungsi pembantu standar untuk mengirim respons JSON terstruktur
 *
 * @param string $status 'success' atau 'error'
 * @param string $message Pesan deskriptif
 * @param mixed $data Data muatan (opsional)
 * @param int $httpCode Kode status HTTP (default: 200)
 */
if (!function_exists('sendJsonResponse')) {
    function sendJsonResponse(string $status, string $message, mixed $data = null, int $httpCode = 200): void
    {
        http_response_code($httpCode);
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo json_encode([
            'status'  => $status,
            'message' => $message,
            'data'    => $data
        ]);
        exit;
    }
}
?>
