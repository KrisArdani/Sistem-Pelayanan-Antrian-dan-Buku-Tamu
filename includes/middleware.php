<?php
// SPST BPS Kota Tegal - Middleware Keamanan & Autentikasi

if (!function_exists('requireAuth')) {
    /**
     * Wajibkan Autentikasi & Otorisasi Pengguna berdasarkan Role
     * @param array $roles Daftar role yang diizinkan (misal ['admin', 'petugas'])
     */
    function requireAuth($roles = []) {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
            sendJsonResponse('error', 'Akses ditolak. Silakan login terlebih dahulu.', null, 401);
        }
        
        // Periksa batas waktu (timeout)
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
            session_unset();
            session_destroy();
            sendJsonResponse('error', 'Sesi telah berakhir. Silakan login kembali.', null, 401);
        }
        $_SESSION['last_activity'] = time();

        if (!empty($roles) && !in_array($_SESSION['user_role'], $roles)) {
            sendJsonResponse('error', 'Anda tidak memiliki hak akses untuk tindakan ini.', null, 403);
        }
    }
}
