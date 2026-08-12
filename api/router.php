<?php
// SPST BPS Kota Tegal - Main API Router & Dispatcher

require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../security.php';
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../includes/helpers.php';

// Urai Body Permintaan JSON jika Content-Type adalah application/json
$contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
if (str_contains($contentType, 'application/json')) {
    $rawInput = file_get_contents('php://input');
    if (!empty($rawInput)) {
        $jsonData = json_decode($rawInput, true);
        if (is_array($jsonData)) {
            $_POST = array_merge($_POST, $jsonData);
        }
    }
}

// Tentukan Tindakan (Action)
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Validasi CSRF untuk permintaan yang mengubah data (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== 'login') {
    $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    
    // Pastikan token CSRF ada di sesi untuk pengunjung baru
    generateCsrfToken();

    if (!validateCsrfToken($csrfToken)) {
        sendJsonResponse('error', 'Token keamanan (CSRF) tidak valid atau telah kedaluwarsa. Silakan muat ulang halaman.', null, 403);
    }
}

// Peta Rute Action ke File Modul API
$routes = [
    // 1. Autentikasi & Sesi
    'register_pengunjung'      => 'auth.api.php',
    'login'                    => 'auth.api.php',
    'logout'                   => 'auth.api.php',
    'check_session'            => 'auth.api.php',
    'get_csrf_token'           => 'auth.api.php',

    // 2. Buku Tamu
    'save_bukutamu'            => 'bukutamu.api.php',
    'get_bukutamu'             => 'bukutamu.api.php',
    'verify_bukutamu'          => 'bukutamu.api.php',

    // 3. Antrean
    'save_antrian'             => 'antrian.api.php',
    'get_my_antrian'           => 'antrian.api.php',
    'cancel_antrian'           => 'antrian.api.php',
    'get_antrian'              => 'antrian.api.php',
    'panggil_antrian'          => 'antrian.api.php',
    'update_status_antrian'    => 'antrian.api.php',
    'get_waiting_count'        => 'antrian.api.php',

    // 4. Stepper Alur
    'get_stepper_status'       => 'stepper.api.php',

    // 5. SKM
    'submit_skm'               => 'skm.api.php',

    // 6. Dashboard KPI & Widget
    'get_dashboard_kpi'        => 'dashboard.api.php',
    'save_widget_feedback'     => 'dashboard.api.php',

    // 7. User Management
    'get_users'                => 'users.api.php',
    'save_user'                => 'users.api.php',
    'reset_password_user'      => 'users.api.php',
    'delete_user'              => 'users.api.php',

    // 8. Password Reset Self-Service
    'request_password_reset'   => 'password.api.php',
    'verify_reset_token'       => 'password.api.php',
    'reset_password_with_token'=> 'password.api.php',

    // 9. Export & Laporan
    'export_antrian'           => 'export.api.php',
    'export_bukutamu'          => 'export.api.php',
    'export_skm'               => 'export.api.php'
];

if (isset($routes[$action])) {
    require_once __DIR__ . '/' . $routes[$action];
} else {
    sendJsonResponse('error', 'Action API tidak dikenali.', null, 400);
}
