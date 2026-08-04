<?php
// TOBASA BPS Kota Tegal - Fungsi Pembantu Keamanan & Header HTTP
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    // Pengaturan parameter keamanan cookie sesi
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

/**
 * Pengaturan Header Keamanan HTTP
 */
function setSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

/**
 * Buat Token CSRF untuk sesi saat ini
 */
function generateCsrfToken() {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Validasi Token CSRF
 */
function validateCsrfToken($token) {
    if (empty($_SESSION[CSRF_TOKEN_NAME]) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Sanitasi input teks untuk mencegah XSS
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validasi format email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validasi format nomor telepon (format Indonesia)
 */
function validatePhone($phone) {
    return preg_match('/^[0-9\-\+\s]{8,20}$/', $phone);
}

/**
 * Pemeriksaan Pembatasan Laju Akses (Rate Limiting sederhana berbasis Database / Sesi)
 */
function checkRateLimit($conn, $action_key, $max_attempts = 5, $time_window = 900) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    
    // Periksa tabel database login_attempts jika koneksi tersedia
    if ($conn instanceof mysqli) {
        // Bersihkan percobaaan lama
        $cutoff = date('Y-m-d H:i:s', time() - $time_window);
        $stmtClean = $conn->prepare("DELETE FROM login_attempts WHERE attempted_at < ?");
        if ($stmtClean) {
            $stmtClean->bind_param("s", $cutoff);
            $stmtClean->execute();
        }

        // Hitung percobaan dalam rentang waktu
        $stmtCount = $conn->prepare("SELECT COUNT(*) as total FROM login_attempts WHERE ip_address = ? AND attempted_at >= ?");
        if ($stmtCount) {
            $stmtCount->bind_param("ss", $ip, $cutoff);
            $stmtCount->execute();
            $res = $stmtCount->get_result()->fetch_assoc();
            if ($res && $res['total'] >= $max_attempts) {
                return false;
            }
        }
    }
    return true;
}

/**
 * Catat percobaan gagal untuk Rate Limiting
 */
function recordFailedAttempt($conn, $username = '') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    if ($conn instanceof mysqli) {
        $stmt = $conn->prepare("INSERT INTO login_attempts (ip_address, username) VALUES (?, ?)");
        if ($stmt) {
            $stmt->bind_param("ss", $ip, $username);
            $stmt->execute();
        }
    }
}

/**
 * Bersihkan percobaan gagal setelah tindakan berhasil
 */
function clearFailedAttempts($conn) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    if ($conn instanceof mysqli) {
        $stmt = $conn->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
        if ($stmt) {
            $stmt->bind_param("s", $ip);
            $stmt->execute();
        }
    }
}

/**
 * Catat Log Peristiwa Keamanan
 */
function logSecurityEvent($conn, $event_type, $details = '') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $user_id = $_SESSION['user_id'] ?? null;
    
    if ($conn instanceof mysqli) {
        $stmt = $conn->prepare("INSERT INTO security_log (event_type, ip_address, user_id, details) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssis", $event_type, $ip, $user_id, $details);
            $stmt->execute();
        }
    } else {
        error_log("[SECURITY LOG][$event_type][IP: $ip] $details");
    }
}
?>
