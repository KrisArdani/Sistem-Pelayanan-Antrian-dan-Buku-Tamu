<?php
// SPST BPS Kota Tegal - Pelindung Autentikasi Sisi Server
require_once __DIR__ . '/config.php';

setSecurityHeaders();

// Periksa apakah pengguna sudah login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: ../login.php');
    exit;
}

// Periksa batas waktu (kedaluwarsa) sesi
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
    header('Location: ../login.php?expired=1');
    exit;
}
$_SESSION['last_activity'] = time();

// Periksa Hak Akses Peran (Role Permissions)
$allowed_roles = $allowed_roles ?? ['petugas', 'admin', 'kepala'];
if (!in_array($_SESSION['user_role'], $allowed_roles)) {
    header('Location: ../index.php');
    exit;
}
?>

