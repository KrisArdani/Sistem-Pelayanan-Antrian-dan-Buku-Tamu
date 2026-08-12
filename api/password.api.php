<?php
// SPST BPS Kota Tegal - API Module: Self-Service Password Reset Pengunjung

switch ($action) {
    case 'request_password_reset':
        // Rate limit: Max 5 reset requests per hour per IP
        if (!checkRateLimit($conn, 'request_password_reset', 5, 3600)) {
            sendJsonResponse('error', 'Batas percobaan reset password tercapai. Silakan coba lagi nanti.', null, 429);
        }

        $email = trim($_POST['email'] ?? '');
        if (empty($email)) {
            sendJsonResponse('error', 'Email wajib diisi.');
        }

        if (!validateEmail($email)) {
            sendJsonResponse('error', 'Format email tidak valid.');
        }

        $stmt = $conn->prepare("SELECT id, username, name, email FROM users WHERE email = ? AND role = 'pengunjung'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($user = $res->fetch_assoc()) {
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmtUpdate = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires_at = ? WHERE id = ?");
            $stmtUpdate->bind_param("ssi", $token, $expiresAt, $user['id']);
            $stmtUpdate->execute();

            $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
            $resetUrl = "$scheme://$host$scriptDir/reset_password.php?token=$token";

            logSecurityEvent($conn, 'request_password_reset', "Reset requested for visitor email: $email");

            sendJsonResponse('success', 'Instruksi reset password telah dikirimkan ke email Anda. Silakan periksa inbox/spam email Anda.', [
                'reset_url' => $resetUrl,
                'email' => $email
            ]);
        } else {
            // Generik response untuk proteksi kebocoran email
            sendJsonResponse('error', 'Email tidak ditemukan sebagai akun pengunjung terdaftar.');
        }
        break;

    case 'verify_reset_token':
        $token = trim($_GET['token'] ?? $_POST['token'] ?? '');
        if (empty($token)) {
            sendJsonResponse('error', 'Token reset password wajib diisi.');
        }

        $nowStr = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("SELECT id, username, name, email FROM users WHERE reset_token = ? AND reset_expires_at >= ? AND role = 'pengunjung'");
        $stmt->bind_param("ss", $token, $nowStr);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($user = $res->fetch_assoc()) {
            sendJsonResponse('success', 'Token reset password valid.', [
                'username' => $user['username'],
                'email' => $user['email'],
                'name' => $user['name']
            ]);
        } else {
            sendJsonResponse('error', 'Token reset password tidak valid atau telah kedaluwarsa. Silakan ajukan reset password kembali.', null, 400);
        }
        break;

    case 'reset_password_with_token':
        $token = trim($_POST['token'] ?? '');
        $newPassword = trim($_POST['new_password'] ?? '');

        if (empty($token) || empty($newPassword)) {
            sendJsonResponse('error', 'Token dan password baru wajib diisi.');
        }

        if (!validatePasswordLength($newPassword, 6)) {
            sendJsonResponse('error', 'Password minimal terdiri dari 6 karakter.');
        }

        $nowStr = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE reset_token = ? AND reset_expires_at >= ? AND role = 'pengunjung'");
        $stmt->bind_param("ss", $token, $nowStr);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($user = $res->fetch_assoc()) {
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmtUpdate = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires_at = NULL WHERE id = ?");
            $stmtUpdate->bind_param("si", $hashedPassword, $user['id']);

            if ($stmtUpdate->execute()) {
                logSecurityEvent($conn, 'reset_password_success', "Visitor password reset successfully for ID: {$user['id']}");
                sendJsonResponse('success', 'Password Anda berhasil diperbarui! Silakan masuk dengan password baru Anda.');
            } else {
                sendJsonResponse('error', 'Gagal memperbarui password.');
            }
        } else {
            sendJsonResponse('error', 'Token reset password tidak valid atau telah kedaluwarsa. Silakan ajukan reset password baru.');
        }
        break;

    default:
        sendJsonResponse('error', 'Action reset password tidak dikenali.', null, 400);
        break;
}
