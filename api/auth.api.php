<?php
// SPST BPS Kota Tegal - API Module: Autentikasi & Sesi

switch ($action) {
    case 'register_pengunjung':
        // Rate limit: Maksimal 5 pendaftaran per jam per IP
        if (!checkRateLimit($conn, 'register_pengunjung', 5, 3600)) {
            sendJsonResponse('error', 'Terlalu banyak percobaan pendaftaran akun. Silakan coba lagi setelah beberapa saat.', null, 429);
        }

        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $nik = trim($_POST['nik'] ?? '');
        $nohp = trim($_POST['nohp'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $jenis_kelamin = trim($_POST['jenis_kelamin'] ?? 'Laki Laki');
        $umur = trim($_POST['umur'] ?? '17-25 tahun');
        $pendidikan = trim($_POST['pendidikan'] ?? 'D4-S1');
        $pekerjaan = trim($_POST['pekerjaan'] ?? 'Mahasiswa');
        $instansi = trim($_POST['instansi'] ?? '');
        $kategori_instansi = trim($_POST['kategori_instansi'] ?? 'Sekolah/Universitas');

        if (!empty(validateRequiredFields(['username', 'password', 'name', 'nik', 'nohp', 'instansi'], $_POST))) {
            sendJsonResponse('error', 'Harap lengkapi semua kolom pendaftaran yang wajib diisi.');
        }

        if (!validatePasswordLength($password, 6)) {
            sendJsonResponse('error', 'Kata sandi (password) minimal harus 6 karakter.');
        }

        if (!validateNIK($nik)) {
            sendJsonResponse('error', 'NIK wajib diisi dengan 16 digit angka sesuai KTP.');
        }

        if (!validatePhone($nohp)) {
            sendJsonResponse('error', 'Format nomor HP/WhatsApp tidak valid.');
        }

        if (!empty($email) && !validateEmail($email)) {
            sendJsonResponse('error', 'Format email tidak valid.');
        }

        try {
            // Periksa apakah username sudah terdaftar
            $stmtCheck = $conn->prepare("SELECT id FROM users WHERE username = ?");
            if ($stmtCheck) {
                $stmtCheck->bind_param("s", $username);
                $stmtCheck->execute();
                if ($stmtCheck->get_result()->num_rows > 0) {
                    sendJsonResponse('error', 'Username sudah digunakan. Silakan gunakan username lain.');
                }
            }

            // Periksa apakah NIK sudah terdaftar
            $stmtCheckNik = $conn->prepare("SELECT id FROM users WHERE nik = ?");
            if ($stmtCheckNik) {
                $stmtCheckNik->bind_param("s", $nik);
                $stmtCheckNik->execute();
                if ($stmtCheckNik->get_result()->num_rows > 0) {
                    sendJsonResponse('error', 'NIK tersebut sudah terdaftar dalam sistem.');
                }
            }

            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $role = 'pengunjung';

            $stmt = $conn->prepare("INSERT INTO users (username, password, name, nik, role, jenis_kelamin, umur, nohp, email, pendidikan, pekerjaan, instansi, kategori_instansi) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            if (!$stmt) {
                error_log("DB Prepare Error register_pengunjung: " . $conn->error);
                sendJsonResponse('error', 'Gagal memproses pendaftaran akun. Terjadi gangguan pada sistem basis data.');
            }

            $stmt->bind_param("sssssssssssss", $username, $hashedPassword, $name, $nik, $role, $jenis_kelamin, $umur, $nohp, $email, $pendidikan, $pekerjaan, $instansi, $kategori_instansi);

            if ($stmt->execute()) {
                logSecurityEvent($conn, 'register_pengunjung', "Registered visitor username: $username (NIK: $nik)");
                sendJsonResponse('success', 'Registrasi akun pengunjung berhasil! Silakan login.');
            } else {
                error_log("DB Execute Error register_pengunjung: " . $stmt->error);
                sendJsonResponse('error', 'Gagal mendaftarkan akun pengunjung. Silakan coba kembali.');
            }
        } catch (Throwable $e) {
            error_log("Exception register_pengunjung: " . $e->getMessage());
            sendJsonResponse('error', 'Terjadi kesalahan sistem saat memproses registrasi.');
        }
        break;

    case 'login':
        // Pemeriksaan pembatasan laju login (Rate Limiting)
        if (!checkRateLimit($conn, 'login', MAX_LOGIN_ATTEMPTS, LOGIN_LOCKOUT_TIME)) {
            sendJsonResponse('error', 'Terlalu banyak percobaan login gagal. Akun/IP diblokir sementara selama 15 menit.', null, 429);
        }

        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($username) || empty($password)) {
            sendJsonResponse('error', 'Username dan password wajib diisi.');
        }

        $stmt = $conn->prepare("SELECT id, username, password, name, nik, role, layanan_tugas, jenis_kelamin, umur, nohp, email, pendidikan, pekerjaan, instansi, kategori_instansi FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            // Verifikasi Kata Sandi Aman (Khusus Bcrypt)
            if (password_verify($password, $user['password'])) {
                // Bersihkan percobaan gagal
                clearFailedAttempts($conn);
                
                // Buat ulang ID sesi untuk mencegah serangan session fixation
                session_regenerate_id(true);

                // Tetapkan variabel sesi
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_nik'] = $user['nik'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_layanan_tugas'] = $user['layanan_tugas'] ?? '';
                $_SESSION['user_username'] = $user['username'];
                $_SESSION['user_jenis_kelamin'] = $user['jenis_kelamin'];
                $_SESSION['user_umur'] = $user['umur'];
                $_SESSION['user_nohp'] = $user['nohp'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_pendidikan'] = $user['pendidikan'];
                $_SESSION['user_pekerjaan'] = $user['pekerjaan'];
                $_SESSION['user_instansi'] = $user['instansi'];
                $_SESSION['user_kategori_instansi'] = $user['kategori_instansi'];
                $_SESSION['last_activity'] = time();

                // Tentukan rute pengalihan (redirect)
                if ($user['role'] === 'pengunjung') {
                    $user['redirect'] = 'index.php';
                } else if ($user['role'] === 'petugas') {
                    $user['redirect'] = 'admin/antrian.php';
                } else {
                    $user['redirect'] = 'admin/dashboard.php';
                }

                unset($user['password']);
                logSecurityEvent($conn, 'login_success', "User ID: {$user['id']}, Role: {$user['role']}");

                sendJsonResponse('success', 'Login berhasil!', $user);
            } else {
                recordFailedAttempt($conn, $username);
                sendJsonResponse('error', 'Username atau password salah.');
            }
        }

        // Catat percobaan login gagal
        recordFailedAttempt($conn, $username);
        logSecurityEvent($conn, 'login_failed', "Attempted username: $username");
        sendJsonResponse('error', 'Username atau password salah.');
        break;

    case 'logout':
        logSecurityEvent($conn, 'logout', "User ID: " . ($_SESSION['user_id'] ?? 'guest'));
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
               || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

        if ($isAjax) {
            sendJsonResponse('success', 'Logout berhasil.');
        } else {
            header("Location: login.php");
            exit();
        }
        break;

    case 'check_session':
        if (isset($_SESSION['user_id'])) {
            // Sinkronkan data pengguna terbaru dari basis data
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

            sendJsonResponse('success', 'Session aktif.', [
                'user_id' => $_SESSION['user_id'],
                'name' => $_SESSION['user_name'],
                'nik' => $_SESSION['user_nik'] ?? '',
                'role' => $_SESSION['user_role'],
                'layanan_tugas' => $_SESSION['user_layanan_tugas'] ?? '',
                'csrf_token' => generateCsrfToken()
            ]);
        } else {
            sendJsonResponse('error', 'Sesi tidak aktif.', null, 401);
        }
        break;

    case 'get_csrf_token':
        sendJsonResponse('success', 'Token CSRF dibuat.', [
            'csrf_token' => generateCsrfToken()
        ]);
        break;

    default:
        sendJsonResponse('error', 'Action autentikasi tidak dikenali.', null, 400);
        break;
}
