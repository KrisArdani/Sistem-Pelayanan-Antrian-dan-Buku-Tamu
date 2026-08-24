<?php
// SPST BPS Kota Tegal - Pelindung Autentikasi Sisi Server
require_once __DIR__ . '/security.php';

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

// Sinkronkan data peran & penugasan loket terbaru dari basis data
require_once __DIR__ . '/koneksi.php';
if (isset($conn) && $conn instanceof mysqli && !empty($_SESSION['user_id'])) {
    $stmtUser = $conn->prepare("SELECT role, layanan_tugas, name, nik FROM users WHERE id = ?");
    if ($stmtUser) {
        $stmtUser->bind_param("i", $_SESSION['user_id']);
        $stmtUser->execute();
        if ($uData = $stmtUser->get_result()->fetch_assoc()) {
            $_SESSION['user_role'] = $uData['role'];
            $_SESSION['user_layanan_tugas'] = $uData['layanan_tugas'] ?? '';
            $_SESSION['user_name'] = $uData['name'];
            $_SESSION['user_nik'] = $uData['nik'];
        }
        $stmtUser->close();
    }
}

// Periksa Hak Akses Peran (Role Permissions)
$allowed_roles = $allowed_roles ?? ['petugas', 'admin', 'kepala'];
if (!in_array($_SESSION['user_role'], $allowed_roles)) {
    header('Location: ../index.php');
    exit;
}
?>

