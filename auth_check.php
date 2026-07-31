<?php
// TOBASA BPS Kota Tegal - Server-Side Authentication Guard
require_once __DIR__ . '/security.php';

setSecurityHeaders();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: ../login.php');
    exit;
}

// Check session expiration
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
    header('Location: ../login.php?expired=1');
    exit;
}
$_SESSION['last_activity'] = time();

// Check Role Permissions
$allowed_roles = $allowed_roles ?? ['petugas', 'admin', 'kepala'];
if (!in_array($_SESSION['user_role'], $allowed_roles)) {
    header('Location: ../index.php');
    exit;
}
?>
