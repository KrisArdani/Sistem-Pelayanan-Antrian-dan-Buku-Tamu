<?php
// SPST BPS Kota Tegal - API Module: Self-Service Password Reset Pengunjung
require_once __DIR__ . '/../includes/mail_service.php';

switch ($action) {
    case 'request_password_reset':
        // Rate limit: Maksimal 5 permintaan per jam per IP address
        if (!checkRateLimit($conn, 'request_password_reset', 5, 3600)) {
            sendJsonResponse('error', 'Batas percobaan reset password tercapai. Silakan coba lagi setelah beberapa saat.', null, 429);
        }

        $email = trim($_POST['email'] ?? '');
        if (empty($email)) {
            sendJsonResponse('error', 'Alamat email wajib diisi.');
        }

        if (!validateEmail($email)) {
            sendJsonResponse('error', 'Format alamat email tidak valid.');
        }

        $stmt = $conn->prepare("SELECT id, username, name, email FROM users WHERE email = ? AND role = 'pengunjung'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($user = $res->fetch_assoc()) {
            // Buat token kriptografis acak 64 karakter
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmtUpdate = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires_at = ? WHERE id = ?");
            $stmtUpdate->bind_param("ssi", $token, $expiresAt, $user['id']);
            $stmtUpdate->execute();

            // Bangun URL reset password
            $baseUrl = defined('APP_URL') && !empty(APP_URL) ? APP_URL : '';
            if (empty($baseUrl)) {
                $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
                $baseUrl = "$scheme://$host$scriptDir";
            }
            $resetUrl = rtrim($baseUrl, '/') . "/reset_password.php?token=" . urlencode($token);

            // Kirim email resmi via SMTP (PHPMailer)
            $mailResult = sendResetPasswordEmail($user['email'], $user['name'] ?: $user['username'], $resetUrl);

            if ($mailResult['success']) {
                logSecurityEvent($conn, 'request_password_reset_sent', "Email reset password terkirim ke: {$user['email']}");

                sendJsonResponse('success', 'Tautan instruksi pemulihan kata sandi telah berhasil dikirim ke email Anda (' . htmlspecialchars($user['email']) . '). Silakan periksa kotak masuk (inbox) atau folder spam Anda.', [
                    'email' => $user['email']
                ]);
            } else {
                logSecurityEvent($conn, 'request_password_reset_failed', "Gagal kirim email ke: {$user['email']}. Error: " . $mailResult['message']);

                // Jika di environment development, berikan detail error untuk mempermudah konfigurasi SMTP
                if (defined('APP_ENV') && APP_ENV === 'development') {
                    sendJsonResponse('error', 'Gagal mengirim email: ' . $mailResult['message'] . '. Pastikan konfigurasi SMTP di file .env sudah diisi dengan benar.', [
                        'debug_error' => $mailResult['message'],
                        'reset_url'   => $resetUrl // Fallback link hanya di mode development lokal
                    ], 500);
                } else {
                    sendJsonResponse('error', 'Gagal mengirimkan email pemulihan kata sandi. Silakan hubungi layanan bantuan BPS Kota Tegal.', null, 500);
                }
            }
        } else {
            // Catat percobaan dengan email tidak terdaftar untuk audit keamanan
            recordFailedAttempt($conn, "reset_nonexistent_$email");
            logSecurityEvent($conn, 'request_password_reset_not_found', "Reset requested for unregistered email: $email");
            
            // Berikan pesan yang jelas bagi pengunjung
            sendJsonResponse('error', 'Alamat email tidak terdaftar sebagai akun pengunjung di SPST BPS Kota Tegal.');
        }
        break;

    case 'verify_reset_token':
        $token = trim($_GET['token'] ?? $_POST['token'] ?? '');
        if (empty($token)) {
            sendJsonResponse('error', 'Token reset password wajib disertakan.');
        }

        $nowStr = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("SELECT id, username, name, email FROM users WHERE reset_token = ? AND reset_expires_at >= ? AND role = 'pengunjung'");
        $stmt->bind_param("ss", $token, $nowStr);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($user = $res->fetch_assoc()) {
            sendJsonResponse('success', 'Token reset password sah dan aktif.', [
                'username' => $user['username'],
                'email'    => $user['email'],
                'name'     => $user['name']
            ]);
        } else {
            sendJsonResponse('error', 'Tautan pemulihan kata sandi tidak valid atau telah kedaluwarsa (masa berlaku 1 jam). Silakan ajukan permohonan baru.', null, 400);
        }
        break;

    case 'reset_password_with_token':
        $token = trim($_POST['token'] ?? '');
        $newPassword = trim($_POST['new_password'] ?? '');

        if (empty($token) || empty($newPassword)) {
            sendJsonResponse('error', 'Token dan kata sandi baru wajib diisi.');
        }

        if (!validatePasswordLength($newPassword, 6)) {
            sendJsonResponse('error', 'Kata sandi minimal terdiri dari 6 karakter.');
        }

        $nowStr = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("SELECT id, username, email FROM users WHERE reset_token = ? AND reset_expires_at >= ? AND role = 'pengunjung'");
        $stmt->bind_param("ss", $token, $nowStr);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($user = $res->fetch_assoc()) {
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmtUpdate = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires_at = NULL WHERE id = ?");
            $stmtUpdate->bind_param("si", $hashedPassword, $user['id']);

            if ($stmtUpdate->execute()) {
                clearFailedAttempts($conn);
                logSecurityEvent($conn, 'reset_password_success', "Kata sandi akun @{$user['username']} ({$user['email']}) berhasil diperbarui.");
                sendJsonResponse('success', 'Kata sandi akun Anda berhasil diperbarui! Silakan masuk dengan kata sandi baru Anda.');
            } else {
                sendJsonResponse('error', 'Gagal memperbarui kata sandi di database.');
            }
        } else {
            sendJsonResponse('error', 'Tautan pemulihan kata sandi tidak valid atau telah kedaluwarsa. Silakan ajukan permohonan baru.');
        }
        break;

    default:
        sendJsonResponse('error', 'Aksi reset password tidak dikenali.', null, 400);
        break;
}
