<?php
// SPST BPS Kota Tegal - Penangan Endpoint API Teramankan
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/security.php';

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

// Pembantu: Wajibkan Autentikasi & Otorisasi
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

// Validasi CSRF untuk permintaan yang mengubah data (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== 'login') {
    $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    
    // Pastikan token CSRF ada di sesi untuk pengunjung baru
    generateCsrfToken();

    if (!validateCsrfToken($csrfToken)) {
        sendJsonResponse('error', 'Token keamanan (CSRF) tidak valid atau telah kedaluwarsa. Silakan muat ulang halaman.', null, 403);
    }
}

switch ($action) {
    // ----------------------------------------------------
    // 1. AUTENTIKASI & SESI
    // ----------------------------------------------------
    case 'register_pengunjung':
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

        if (empty($username) || empty($password) || empty($name) || empty($nik) || empty($nohp) || empty($instansi)) {
            sendJsonResponse('error', 'Harap lengkapi semua kolom pendaftaran yang wajib diisi.');
        }

        if (strlen($nik) !== 16 || !ctype_digit($nik)) {
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
                sendJsonResponse('error', 'Gagal memproses registrasi (Kesalahan struktur DB): ' . $conn->error);
            }

            $stmt->bind_param("sssssssssssss", $username, $hashedPassword, $name, $nik, $role, $jenis_kelamin, $umur, $nohp, $email, $pendidikan, $pekerjaan, $instansi, $kategori_instansi);

            if ($stmt->execute()) {
                logSecurityEvent($conn, 'register_pengunjung', "Registered visitor username: $username (NIK: $nik)");
                sendJsonResponse('success', 'Registrasi akun pengunjung berhasil! Silakan login.');
            } else {
                sendJsonResponse('error', 'Gagal mendaftarkan akun pengunjung: ' . $stmt->error);
            }
        } catch (Throwable $e) {
            error_log("Exception register_pengunjung: " . $e->getMessage());
            sendJsonResponse('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
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


    // ----------------------------------------------------
    // 2. ENDPOINT BUKU TAMU
    // ----------------------------------------------------
    case 'save_bukutamu':
        $timestamp = sanitizeInput($_POST['timestamp'] ?? date('Y-m-d H:i:s'));
        $nama = sanitizeInput($_POST['nama'] ?? '');
        $jenis_kelamin = sanitizeInput($_POST['jenis_kelamin'] ?? 'Laki Laki');
        $umur = sanitizeInput($_POST['umur'] ?? '');
        $nohp = sanitizeInput($_POST['nohp'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $pendidikan = sanitizeInput($_POST['pendidikan'] ?? '');
        $pekerjaan = sanitizeInput($_POST['pekerjaan'] ?? '');
        $instansi = sanitizeInput($_POST['instansi'] ?? '');
        $kategori_instansi = sanitizeInput($_POST['kategori_instansi'] ?? '');
        $fasilitas = sanitizeInput($_POST['fasilitas'] ?? '');
        $layanan = sanitizeInput($_POST['layanan'] ?? '');
        $pemanfaatan = sanitizeInput($_POST['pemanfaatan'] ?? '');
        $data_diinginkan = sanitizeInput($_POST['data_diinginkan'] ?? '');
        $foto = $_POST['foto'] ?? ''; // Base64 data string
        $pendapat = !empty($_POST['pendapat']) ? sanitizeInput($_POST['pendapat']) : NULL;
        $monev = sanitizeInput($_POST['monev'] ?? 'Ya');
        $catatan = sanitizeInput($_POST['catatan'] ?? '');

        if (empty($nama) || empty($nohp) || empty($instansi)) {
            sendJsonResponse('error', 'Harap isi kolom data yang wajib diisi.');
        }

        // Validasi format Email jika diisi
        if (!empty($email) && !validateEmail($email)) {
            sendJsonResponse('error', 'Format email yang dimasukkan tidak valid.');
        }

        // Validasi format Telepon/HP
        if (!validatePhone($nohp)) {
            sendJsonResponse('error', 'Format nomor HP/WhatsApp tidak valid.');
        }

        // Validasi Ukuran Foto Base64 (Maksimal 2MB)
        if (!empty($foto)) {
            if (strlen($foto) > 2.8 * 1024 * 1024) { // pengali beban overhead base64 ~1.37
                sendJsonResponse('error', 'Ukuran file foto terlalu besar (Maksimal 2 MB).');
            }
        }

        // Pembuatan Kode BT Aman dengan Kunci Transaksi
        $conn->begin_transaction();
        try {
            $countResult = $conn->query("SELECT COUNT(*) as total FROM buku_tamu FOR UPDATE");
            $countRow = $countResult->fetch_assoc();
            $kode_bt = 'BT-' . str_pad($countRow['total'] + 1, 3, '0', STR_PAD_LEFT);

            $stmt = $conn->prepare("INSERT INTO buku_tamu (
                kode_bt, timestamp, nama, jenis_kelamin, umur, nohp, email, pendidikan,
                pekerjaan, instansi, kategori_instansi, fasilitas, layanan, pemanfaatan,
                data_diinginkan, foto, pendapat, monev, catatan, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");

            $stmt->bind_param(
                "sssssssssssssssssss",
                $kode_bt, $timestamp, $nama, $jenis_kelamin, $umur, $nohp, $email, $pendidikan,
                $pekerjaan, $instansi, $kategori_instansi, $fasilitas, $layanan, $pemanfaatan,
                $data_diinginkan, $foto, $pendapat, $monev, $catatan
            );

            $stmt->execute();
            $insertId = $stmt->insert_id;
            $conn->commit();

            sendJsonResponse('success', 'Data registrasi kunjungan berhasil disimpan!', [
                'kode_bt' => $kode_bt,
                'id' => $insertId
            ]);
        } catch (Exception $e) {
            $conn->rollback();
            error_log("DB Error save_bukutamu: " . $e->getMessage());
            sendJsonResponse('error', 'Gagal menyimpan data ke database server.');
        }
        break;

    case 'get_bukutamu':
        requireAuth(['petugas', 'admin', 'kepala']);

        $search = sanitizeInput($_GET['search'] ?? '');
        
        if (!empty($search)) {
            $likeSearch = '%' . $search . '%';
            $stmt = $conn->prepare("SELECT id, user_id, kode_antrian AS kode_bt, nomor, nama, jenis_kelamin, umur, nohp, email, pendidikan, pekerjaan, instansi, kategori_instansi, fasilitas, layanan, pemanfaatan, data_diinginkan, foto, pendapat, monev, catatan, tipe_pendaftaran, status, CONCAT(tanggal, ' ', waktu) AS timestamp, created_at FROM antrian WHERE nama LIKE ? OR instansi LIKE ? OR layanan LIKE ? OR kode_antrian LIKE ? OR nomor LIKE ? OR nohp LIKE ? ORDER BY id DESC");
            $stmt->bind_param("ssssss", $likeSearch, $likeSearch, $likeSearch, $likeSearch, $likeSearch, $likeSearch);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $conn->query("SELECT id, user_id, kode_antrian AS kode_bt, nomor, nama, jenis_kelamin, umur, nohp, email, pendidikan, pekerjaan, instansi, kategori_instansi, fasilitas, layanan, pemanfaatan, data_diinginkan, foto, pendapat, monev, catatan, tipe_pendaftaran, status, CONCAT(tanggal, ' ', waktu) AS timestamp, created_at FROM antrian ORDER BY id DESC");
        }

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        sendJsonResponse('success', 'Data buku tamu terintegrasi berhasil diambil.', $data);
        break;

    case 'verify_bukutamu':
        requireAuth(['petugas', 'admin', 'kepala']);

        $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
        $status = sanitizeInput($_POST['status'] ?? 'Selesai');
        if ($id <= 0) sendJsonResponse('error', 'ID tidak valid.');

        $allowedStatuses = ['Menunggu', 'Dipanggil', 'Dilayani', 'Selesai', 'Terlewat', 'Dibatalkan'];
        if (!in_array($status, $allowedStatuses)) {
            sendJsonResponse('error', 'Status tidak valid.');
        }

        // Periksa status saat ini
        $checkStmt = $conn->prepare("SELECT status FROM antrian WHERE id = ?");
        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        $curRow = $checkStmt->get_result()->fetch_assoc();
        if (!$curRow) sendJsonResponse('error', 'Data kunjungan tidak ditemukan.');

        if ($curRow['status'] === 'Dibatalkan' && $status === 'Selesai') {
            sendJsonResponse('error', 'Kunjungan yang sudah Dibatalkan tidak dapat langsung diubah menjadi Selesai.');
        }

        $stmt = $conn->prepare("UPDATE antrian SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        if ($stmt->execute()) {
            logSecurityEvent($conn, 'verify_bukutamu', "Updated status to $status for Queue ID: $id");
            sendJsonResponse('success', "Status kunjungan berhasil diperbarui menjadi '$status'!");
        } else {
            sendJsonResponse('error', 'Gagal memperbarui status.');
        }
        break;


    // ----------------------------------------------------
    // 3. ENDPOINT ANTREAN
    // ----------------------------------------------------
    case 'save_antrian':
        // Ambil parameter dengan aman
        $userId = $_SESSION['user_id'] ?? null;
        $isWalkin = isset($_POST['is_walkin']) && ($_POST['is_walkin'] == '1' || $_POST['is_walkin'] == 'true');
        $tipePendaftaran = $isWalkin ? 'walkin' : 'online';

        // Jika pendaftaran langsung (walkin), wajibkan autentikasi petugas
        if ($isWalkin) {
            requireAuth(['petugas', 'admin', 'kepala']);
        } else {
            // Untuk pendaftaran online, wajibkan login pengunjung atau petugas
            requireAuth(['pengunjung', 'petugas', 'admin', 'kepala']);
        }

        $nik = sanitizeInput($_POST['nik'] ?? $_SESSION['user_nik'] ?? '');
        $nama = sanitizeInput($_POST['nama'] ?? $_SESSION['user_name'] ?? '');
        $jenis_kelamin = sanitizeInput($_POST['jenis_kelamin'] ?? $_SESSION['user_jenis_kelamin'] ?? 'Laki Laki');
        $umur = sanitizeInput($_POST['umur'] ?? $_SESSION['user_umur'] ?? '17-25 tahun');
        $nohp = sanitizeInput($_POST['nohp'] ?? $_SESSION['user_nohp'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? $_SESSION['user_email'] ?? '');
        $pendidikan = sanitizeInput($_POST['pendidikan'] ?? $_SESSION['user_pendidikan'] ?? 'D4-S1');
        $pekerjaan = sanitizeInput($_POST['pekerjaan'] ?? $_SESSION['user_pekerjaan'] ?? 'Mahasiswa');
        $instansi = sanitizeInput($_POST['instansi'] ?? $_SESSION['user_instansi'] ?? '');
        $kategori_instansi = sanitizeInput($_POST['kategori_instansi'] ?? $_SESSION['user_kategori_instansi'] ?? 'Sekolah/Universitas');

        $fasilitas = sanitizeInput($_POST['fasilitas'] ?? 'Datang Langsung Ke PST BPS Kota Tegal');
        $layanan = str_replace('&amp;', '&', sanitizeInput($_POST['layanan'] ?? 'Konsultasi Statistik'));
        $pemanfaatan = sanitizeInput($_POST['pemanfaatan'] ?? '');
        $data_diinginkan = sanitizeInput($_POST['data_diinginkan'] ?? '');
        $foto = $_POST['foto'] ?? '';
        $monev = sanitizeInput($_POST['monev'] ?? 'Ya');
        $tanggal = sanitizeInput($_POST['tanggal'] ?? date('Y-m-d'));
        $waktu = sanitizeInput($_POST['waktu'] ?? '09:00');

        if (empty($nama) || empty($tanggal) || empty($waktu) || empty($layanan)) {
            sendJsonResponse('error', 'Harap lengkapi semua kolom antrean & keperluan layanan.');
        }

        // Pemeriksaan Akhir Pekan Sisi Server (Senin - Jumat)
        $dayOfWeek = date('N', strtotime($tanggal)); // 1 (Senin) hingga 7 (Minggu)
        if ($dayOfWeek > 5 && !$isWalkin) {
            sendJsonResponse('error', 'Reservasi antrean hanya tersedia pada hari kerja (Senin s.d. Jumat).');
        }

        // Pemeriksaan Jam Kerja Sisi Server (08:00 - 15:30)
        if (($waktu < '08:00' || $waktu > '15:30') && !$isWalkin) {
            sendJsonResponse('error', 'Waktu kunjungan hanya tersedia pada jam kerja (08:00 s.d. 15:30 WIB).');
        }

        // Perlindungan Antrean Aktif: Cegah pembuatan antrean online baru jika pengunjung masih memiliki antrean aktif untuk hari ini atau tanggal mendatang
        if (!$isWalkin) {
            $activeStmt = null;
            if ($userId > 0) {
                $activeStmt = $conn->prepare("SELECT id, nomor, kode_antrian, status, layanan FROM antrian WHERE user_id = ? AND status IN ('Menunggu', 'Dipanggil', 'Dilayani') AND tanggal >= CURDATE() ORDER BY id DESC LIMIT 1");
                $activeStmt->bind_param("i", $userId);
            } else if (!empty($nohp)) {
                $activeStmt = $conn->prepare("SELECT id, nomor, kode_antrian, status, layanan FROM antrian WHERE nohp = ? AND status IN ('Menunggu', 'Dipanggil', 'Dilayani') AND tanggal >= CURDATE() ORDER BY id DESC LIMIT 1");
                $activeStmt->bind_param("s", $nohp);
            }

            if ($activeStmt) {
                $activeStmt->execute();
                $activeRow = $activeStmt->get_result()->fetch_assoc();
                if ($activeRow) {
                    $activeNo = $activeRow['nomor'] ?: $activeRow['kode_antrian'];
                    $activeStatus = $activeRow['status'];
                    sendJsonResponse(
                        'error',
                        "Anda masih memiliki antrean aktif (Nomor: $activeNo) dengan status '$activeStatus'. Mohon tunggu hingga antrean selesai diproses di loket atau batalkan antrean sebelumnya di halaman 'Riwayat Tiket Saya' sebelum membuat reservasi baru.",
                        ['active_queue' => $activeRow]
                    );
                }
            }
        }

        // Tentukan awalan (prefix)
        $prefix = 'KS';
        if (str_contains($layanan, 'Perpustakaan')) $prefix = 'PD';
        if (str_contains($layanan, 'Rekomendasi') || str_contains($layanan, 'ROMANTIK')) $prefix = 'RS';
        if (str_contains($layanan, 'Pengaduan')) $prefix = 'PG';

        $conn->begin_transaction();
        try {
            // Hitung jumlah antrean pada tanggal & awalan yang sama dengan penguncian baris (row lock)
            $stmtCount = $conn->prepare("SELECT COUNT(*) as total FROM antrian WHERE tanggal = ? AND nomor LIKE ? FOR UPDATE");
            $likePrefix = $prefix . '-%';
            $stmtCount->bind_param("ss", $tanggal, $likePrefix);
            $stmtCount->execute();
            $countRow = $stmtCount->get_result()->fetch_assoc();
            
            $nomor = $prefix . '-' . str_pad($countRow['total'] + 1, 2, '0', STR_PAD_LEFT);
            $kode_antrian = 'ANT-' . substr(time(), -6);

            $stmt = $conn->prepare("INSERT INTO antrian (
                user_id, kode_antrian, nomor, nik, nama, jenis_kelamin, umur, nohp, email, pendidikan,
                pekerjaan, instansi, kategori_instansi, fasilitas, layanan, pemanfaatan, data_diinginkan,
                foto, monev, tanggal, waktu, tipe_pendaftaran, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Menunggu')");

            if (!$stmt) {
                $conn->rollback();
                error_log("DB Prepare Error save_antrian: " . $conn->error);
                sendJsonResponse('error', 'Gagal memproses antrean (DB Prepare Error): ' . $conn->error);
            }

            $stmt->bind_param(
                "isssssssssssssssssssss",
                $userId, $kode_antrian, $nomor, $nik, $nama, $jenis_kelamin, $umur, $nohp, $email, $pendidikan,
                $pekerjaan, $instansi, $kategori_instansi, $fasilitas, $layanan, $pemanfaatan, $data_diinginkan,
                $foto, $monev, $tanggal, $waktu, $tipePendaftaran
            );

            $stmt->execute();
            $conn->commit();

            logSecurityEvent($conn, 'save_antrian', "Kode: $kode_antrian, Type: $tipePendaftaran");

            sendJsonResponse('success', 'Nomor antrian berhasil dibuat!', [
                'id' => $kode_antrian,
                'nomor' => $nomor,
                'nama' => $nama,
                'nohp' => $nohp,
                'instansi' => $instansi,
                'layanan' => $layanan,
                'fasilitas' => $fasilitas,
                'pemanfaatan' => $pemanfaatan,
                'monev' => $monev,
                'data_diinginkan' => $data_diinginkan,
                'tanggal' => $tanggal,
                'waktu' => $waktu,
                'tipe_pendaftaran' => $tipePendaftaran,
                'status' => 'Menunggu',
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Throwable $e) {
            if (isset($conn) && $conn instanceof mysqli && @$conn->ping()) {
                @$conn->rollback();
            }
            error_log("DB Error save_antrian: " . $e->getMessage());
            sendJsonResponse('error', 'Gagal memproses tiket antrian: ' . $e->getMessage());
        }
        break;

    case 'get_stepper_status':
        $isUserLoggedIn = isset($_SESSION['user_id']);
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $userNik = trim($_SESSION['user_nik'] ?? '');
        $userEmail = trim($_SESSION['user_email'] ?? '');
        $userNohp = trim($_SESSION['user_nohp'] ?? '');
        $userName = trim($_SESSION['user_name'] ?? '');

        $activeTicket = null;
        $completedTicket = null;

        if ($isUserLoggedIn) {
            // Check active ticket
            $sqlActive = "SELECT id, nomor, kode_antrian, status, tanggal, waktu, layanan, pendapat, catatan FROM antrian 
                          WHERE status IN ('Menunggu', 'Dipanggil', 'Dilayani') 
                            AND (
                              (? > 0 AND user_id = ?) 
                              OR (? != '' AND nik = ?) 
                              OR (? != '' AND nohp = ?) 
                              OR (? != '' AND email = ?) 
                              OR (? != '' AND nama = ?)
                            ) 
                          ORDER BY id DESC LIMIT 1";
            $stmt = $conn->prepare($sqlActive);
            if ($stmt) {
                $stmt->bind_param("iissssssss", $userId, $userId, $userNik, $userNik, $userNohp, $userNohp, $userEmail, $userEmail, $userName, $userName);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($row = $res->fetch_assoc()) {
                    $activeTicket = $row;
                }
                $stmt->close();
            }

            // Check completed ticket
            $sqlCompleted = "SELECT id, nomor, kode_antrian, status, tanggal, waktu, layanan, pendapat, catatan FROM antrian 
                             WHERE status = 'Selesai' 
                               AND (
                                 (? > 0 AND user_id = ?) 
                                 OR (? != '' AND nik = ?) 
                                 OR (? != '' AND nohp = ?) 
                                 OR (? != '' AND email = ?) 
                                 OR (? != '' AND nama = ?)
                               ) 
                             ORDER BY id DESC LIMIT 1";
            $stmt = $conn->prepare($sqlCompleted);
            if ($stmt) {
                $stmt->bind_param("iissssssss", $userId, $userId, $userNik, $userNik, $userNohp, $userNohp, $userEmail, $userEmail, $userName, $userName);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($row = $res->fetch_assoc()) {
                    $completedTicket = $row;
                }
                $stmt->close();
            }
        }

        sendJsonResponse('success', 'Status alur berhasil diambil.', [
            'is_logged_in' => $isUserLoggedIn,
            'has_active_ticket' => !empty($activeTicket),
            'has_completed_ticket' => !empty($completedTicket),
            'active_ticket' => $activeTicket,
            'completed_ticket' => $completedTicket,
        ]);
        break;

    case 'get_my_antrian':
        requireAuth(['pengunjung', 'petugas', 'admin', 'kepala']);
        $userId = $_SESSION['user_id'] ?? 0;
        $userNoHp = $_SESSION['user_nohp'] ?? '';

        $stmt = $conn->prepare("SELECT id, user_id, kode_antrian, nomor, nama, jenis_kelamin, umur, nohp, email, pendidikan, pekerjaan, instansi, kategori_instansi, fasilitas, layanan, pemanfaatan, data_diinginkan, foto, monev, tanggal, waktu, pendapat, catatan, tipe_pendaftaran, status, created_at FROM antrian WHERE user_id = ? OR (nohp = ? AND nohp != '') ORDER BY id DESC");
        $stmt->bind_param("is", $userId, $userNoHp);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        sendJsonResponse('success', 'Data riwayat antrean berhasil diambil.', $data);
        break;

    case 'cancel_antrian':
        requireAuth(['pengunjung', 'petugas', 'admin', 'kepala']);
        $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
        $userId = $_SESSION['user_id'] ?? 0;
        $role = $_SESSION['user_role'] ?? '';

        if ($id <= 0) sendJsonResponse('error', 'ID antrean tidak valid.');

        // Hanya izinkan pembatalan antrean berstatus 'Menunggu'
        if (in_array($role, ['petugas', 'admin', 'kepala'])) {
            $stmt = $conn->prepare("UPDATE antrian SET status = 'Dibatalkan' WHERE id = ? AND status = 'Menunggu'");
            $stmt->bind_param("i", $id);
        } else {
            $stmt = $conn->prepare("UPDATE antrian SET status = 'Dibatalkan' WHERE id = ? AND user_id = ? AND status = 'Menunggu'");
            $stmt->bind_param("ii", $id, $userId);
        }

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                logSecurityEvent($conn, 'cancel_antrian', "Cancelled Queue ID: $id");
                sendJsonResponse('success', 'Antrean berhasil dibatalkan.');
            } else {
                sendJsonResponse('error', 'Antrean tidak dapat dibatalkan. Hanya antrean berstatus "Menunggu" yang dapat dibatalkan.');
            }
        } else {
            sendJsonResponse('error', 'Gagal membatalkan antrean.');
        }
        break;

    case 'submit_skm':
        requireAuth(['pengunjung', 'petugas', 'admin', 'kepala']);
        $id = intval($_POST['id'] ?? 0);
        $pendapat = sanitizeInput($_POST['pendapat'] ?? 'Sangat Puas');
        $catatan = sanitizeInput($_POST['catatan'] ?? '');

        if ($id <= 0) sendJsonResponse('error', 'ID antrean tidak valid.');

        $stmt = $conn->prepare("UPDATE antrian SET pendapat = ?, catatan = ? WHERE id = ?");
        $stmt->bind_param("ssi", $pendapat, $catatan, $id);

        if ($stmt->execute()) {
            logSecurityEvent($conn, 'submit_skm', "Submitted SKM Queue ID: $id, Rating: $pendapat");
            sendJsonResponse('success', 'Ulasan & penilaian kepuasan berhasil disimpan. Terima kasih atas masukan Anda!');
        } else {
            sendJsonResponse('error', 'Gagal menyimpan penilaian kepuasan.');
        }
        break;

    case 'get_antrian':
        requireAuth(['petugas', 'admin', 'kepala']);

        $filterTanggal = sanitizeInput($_GET['tanggal'] ?? 'today');
        $assignedLayanan = trim($_SESSION['user_layanan_tugas'] ?? '');
        $userRole = $_SESSION['user_role'] ?? '';

        if ($userRole === 'petugas' && !empty($assignedLayanan)) {
            $filterLayanan = $assignedLayanan;
        } else {
            $filterLayanan = sanitizeInput($_GET['layanan'] ?? $assignedLayanan);
        }

        $todayStr = date('Y-m-d');
        $tomorrowStr = date('Y-m-d', strtotime('+1 day'));

        $query = "SELECT * FROM antrian WHERE 1=1";
        $types = "";
        $params = [];

        if ($filterTanggal === 'today') {
            $query .= " AND tanggal = ?";
            $types .= "s";
            $params[] = $todayStr;
        } else if ($filterTanggal === 'tomorrow') {
            $query .= " AND tanggal = ?";
            $types .= "s";
            $params[] = $tomorrowStr;
        } else if ($filterTanggal !== 'all' && !empty($filterTanggal)) {
            $query .= " AND tanggal = ?";
            $types .= "s";
            $params[] = $filterTanggal;
        }

        // Filter khusus berdasarkan layanan loket tugas
        if (!empty($filterLayanan) && $filterLayanan !== 'all') {
            $query .= " AND layanan LIKE ?";
            $types .= "s";
            $params[] = '%' . $filterLayanan . '%';
        }

        $query .= " ORDER BY id DESC";

        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        sendJsonResponse('success', 'Data antrian berhasil diambil.', $data);
        break;

    case 'panggil_antrian':
        requireAuth(['petugas', 'admin', 'kepala']);

        $id = intval($_POST['id'] ?? 0);
        $isRepeat = isset($_POST['repeat']) && ($_POST['repeat'] == '1' || $_POST['repeat'] == 'true');
        $assignedLayanan = trim($_SESSION['user_layanan_tugas'] ?? '');
        $userRole = $_SESSION['user_role'] ?? '';

        if ($userRole === 'petugas' && !empty($assignedLayanan)) {
            $filterLayanan = $assignedLayanan;
        } else {
            $filterLayanan = sanitizeInput($_POST['layanan'] ?? $assignedLayanan);
        }

        $tanggal = date('Y-m-d');

        if ($id > 0) {
            // Verifikasi petugas hanya boleh memanggil antrean layanannya sendiri
            if ($userRole === 'petugas' && !empty($assignedLayanan)) {
                $stmtCheck = $conn->prepare("SELECT id FROM antrian WHERE id = ? AND layanan LIKE ?");
                $likeAssigned = '%' . $assignedLayanan . '%';
                $stmtCheck->bind_param("is", $id, $likeAssigned);
                $stmtCheck->execute();
                if ($stmtCheck->get_result()->num_rows === 0) {
                    sendJsonResponse('error', 'Anda tidak memiliki hak akses untuk memanggil antrean dari loket layanan lain.');
                }
            }

            if ($isRepeat) {
                // Pemanggilan ulang: cukup atur ulang status ke 'Dipanggil'
                $stmt = $conn->prepare("UPDATE antrian SET status = 'Dipanggil' WHERE id = ? AND status = 'Dipanggil'");
                $stmt->bind_param("i", $id);
                $stmt->execute();
            } else {
                // Selesaikan antrean LAIN yang sedang berstatus 'Dipanggil' (BUKAN 'Dilayani') kecuali ID yang diminta
                if (!empty($filterLayanan) && $filterLayanan !== 'all') {
                    $stmtFinish = $conn->prepare("UPDATE antrian SET status = 'Selesai' WHERE status = 'Dipanggil' AND tanggal = ? AND id != ? AND layanan LIKE ?");
                    $likeLayanan = '%' . $filterLayanan . '%';
                    $stmtFinish->bind_param("sis", $tanggal, $id, $likeLayanan);
                } else {
                    $stmtFinish = $conn->prepare("UPDATE antrian SET status = 'Selesai' WHERE status = 'Dipanggil' AND tanggal = ? AND id != ?");
                    $stmtFinish->bind_param("si", $tanggal, $id);
                }
                $stmtFinish->execute();

                // Hanya ubah ke Dipanggil jika saat ini berstatus Menunggu atau sudah Dipanggil
                $stmt = $conn->prepare("UPDATE antrian SET status = 'Dipanggil' WHERE id = ? AND status IN ('Menunggu', 'Dipanggil')");
                $stmt->bind_param("i", $id);
                $stmt->execute();
            }
        } else {
            // Selesaikan antrean yang sedang berstatus 'Dipanggil' secara aman sebelum mengambil antrean berikutnya
            if (!empty($filterLayanan) && $filterLayanan !== 'all') {
                $stmtFinish = $conn->prepare("UPDATE antrian SET status = 'Selesai' WHERE status = 'Dipanggil' AND tanggal = ? AND layanan LIKE ?");
                $likeLayanan = '%' . $filterLayanan . '%';
                $stmtFinish->bind_param("ss", $tanggal, $likeLayanan);
            } else {
                $stmtFinish = $conn->prepare("UPDATE antrian SET status = 'Selesai' WHERE status = 'Dipanggil' AND tanggal = ?");
                $stmtFinish->bind_param("s", $tanggal);
            }
            $stmtFinish->execute();

            // Cari antrean berikutnya yang berstatus 'Menunggu' saja
            if (!empty($filterLayanan) && $filterLayanan !== 'all') {
                $stmtNext = $conn->prepare("SELECT id FROM antrian WHERE status = 'Menunggu' AND tanggal = ? AND layanan LIKE ? ORDER BY id ASC LIMIT 1");
                $likeLayanan = '%' . $filterLayanan . '%';
                $stmtNext->bind_param("ss", $tanggal, $likeLayanan);
            } else {
                $stmtNext = $conn->prepare("SELECT id FROM antrian WHERE status = 'Menunggu' AND tanggal = ? ORDER BY id ASC LIMIT 1");
                $stmtNext->bind_param("s", $tanggal);
            }
            
            $stmtNext->execute();
            $res = $stmtNext->get_result();
            if ($next = $res->fetch_assoc()) {
                $nextId = $next['id'];
                $stmtCall = $conn->prepare("UPDATE antrian SET status = 'Dipanggil' WHERE id = ? AND status = 'Menunggu'");
                $stmtCall->bind_param("i", $nextId);
                $stmtCall->execute();
                $id = $nextId;
            }
        }
        logSecurityEvent($conn, 'panggil_antrian', "Called queue ID: $id (Repeat: " . ($isRepeat ? '1' : '0') . ")");
        sendJsonResponse('success', 'Antrian berhasil dipanggil!');
        break;

    case 'update_status_antrian':
        requireAuth(['petugas', 'admin', 'kepala']);

        $id = intval($_POST['id'] ?? 0);
        $status = sanitizeInput($_POST['status'] ?? 'Selesai');
        $assignedLayanan = trim($_SESSION['user_layanan_tugas'] ?? '');
        $userRole = $_SESSION['user_role'] ?? '';

        if ($id <= 0) sendJsonResponse('error', 'ID antrian tidak valid.');

        // Verifikasi petugas hanya boleh mengubah status antrean layanannya sendiri
        if ($userRole === 'petugas' && !empty($assignedLayanan)) {
            $stmtCheck = $conn->prepare("SELECT id FROM antrian WHERE id = ? AND layanan LIKE ?");
            $likeAssigned = '%' . $assignedLayanan . '%';
            $stmtCheck->bind_param("is", $id, $likeAssigned);
            $stmtCheck->execute();
            if ($stmtCheck->get_result()->num_rows === 0) {
                sendJsonResponse('error', 'Anda tidak memiliki hak akses untuk mengubah status antrean dari loket layanan lain.');
            }
        }
        if ($id <= 0) sendJsonResponse('error', 'ID antrian tidak valid.');

        $allowedStatuses = ['Menunggu', 'Dipanggil', 'Dilayani', 'Selesai', 'Terlewat', 'Dibatalkan'];
        if (!in_array($status, $allowedStatuses)) {
            sendJsonResponse('error', 'Status antrian tidak valid: ' . $status);
        }

        $stmt = $conn->prepare("UPDATE antrian SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        if ($stmt->execute()) {
            logSecurityEvent($conn, 'update_status_antrian', "ID: $id, New status: $status");
            sendJsonResponse('success', "Status antrian diubah menjadi $status");
        } else {
            sendJsonResponse('error', 'Gagal mengubah status antrian.');
        }
        break;


    // ----------------------------------------------------
    // 4. ENDPOINT DASHBOARD EKSEKUTIF & KPI
    // ----------------------------------------------------
    case 'get_dashboard_kpi':
        requireAuth(['admin', 'kepala', 'petugas']);

        // Total Registered Visitors (Akun Pengunjung Terdaftar)
        $resUsers = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'pengunjung'");
        $totalPengunjung = $resUsers->fetch_assoc()['total'];

        // Total Queues / Visits Recorded (Akumulasi Pendaftaran Antrean)
        $resAntrian = $conn->query("SELECT COUNT(*) as total FROM antrian");
        $totalAntrian = $resAntrian->fetch_assoc()['total'];

        // SKM Score & Index PermenPAN-RB (Hanya dari antrean yang diisi ulasan)
        $resSKM = $conn->query("SELECT 
            COUNT(*) as total,
            SUM(CASE 
                WHEN pendapat = 'Sangat Puas' THEN 4
                WHEN pendapat = 'Puas' THEN 3
                WHEN pendapat = 'Cukup Puas' THEN 2
                WHEN pendapat = 'Tidak Puas' THEN 1
                ELSE 4 END) as total_skor,
            SUM(CASE WHEN pendapat IN ('Sangat Puas', 'Puas') THEN 1 ELSE 0 END) as puas_count
            FROM antrian WHERE pendapat IS NOT NULL AND pendapat != ''");
        $rowSKM = $resSKM->fetch_assoc();
        $totalResponSKM = $rowSKM['total'];
        if ($totalResponSKM > 0) {
            $skmSkor = round($rowSKM['total_skor'] / $totalResponSKM, 2);
            $skmNilai = round($skmSkor * 25, 2);
            $skmPersen = round(($rowSKM['puas_count'] / $totalResponSKM) * 100, 1);
        } else {
            $skmSkor = 4.00;
            $skmNilai = 100.0;
            $skmPersen = 100;
        }

        if ($skmNilai >= 88.31) {
            $skmMutu = 'A (Sangat Baik)';
        } else if ($skmNilai >= 76.61) {
            $skmMutu = 'B (Baik)';
        } else if ($skmNilai >= 65.31) {
            $skmMutu = 'C (Kurang Baik)';
        } else {
            $skmMutu = 'D (Tidak Baik)';
        }

        // Chart Layanan Breakdown
        $resChartLayanan = $conn->query("SELECT layanan, COUNT(*) as jumlah FROM antrian GROUP BY layanan");
        $chartLayanan = [];
        while ($r = $resChartLayanan->fetch_assoc()) {
            $chartLayanan[] = $r;
        }

        // Demografi: Pekerjaan
        $resPekerjaan = $conn->query("SELECT pekerjaan, COUNT(*) as jumlah FROM antrian WHERE pekerjaan IS NOT NULL AND pekerjaan != '' GROUP BY pekerjaan");
        $chartPekerjaan = [];
        while ($r = $resPekerjaan->fetch_assoc()) {
            $chartPekerjaan[] = $r;
        }

        // Demografi: Pendidikan
        $resPendidikan = $conn->query("SELECT pendidikan, COUNT(*) as jumlah FROM antrian WHERE pendidikan IS NOT NULL AND pendidikan != '' GROUP BY pendidikan");
        $chartPendidikan = [];
        while ($r = $resPendidikan->fetch_assoc()) {
            $chartPendidikan[] = $r;
        }

        // Demografi: Kategori Instansi
        $resInstansi = $conn->query("SELECT kategori_instansi, COUNT(*) as jumlah FROM antrian WHERE kategori_instansi IS NOT NULL AND kategori_instansi != '' GROUP BY kategori_instansi");
        $chartInstansi = [];
        while ($r = $resInstansi->fetch_assoc()) {
            $chartInstansi[] = $r;
        }

        // Total Antrean Hari Ini
        $resToday = $conn->query("SELECT COUNT(*) as total FROM antrian WHERE tanggal = CURRENT_DATE()");
        $totalAntrianToday = $resToday->fetch_assoc()['total'];

        // Total Antrean Selesai
        $resSelesai = $conn->query("SELECT COUNT(*) as total FROM antrian WHERE status = 'Selesai'");
        $totalSelesai = $resSelesai->fetch_assoc()['total'];

        // Chart Status Breakdown
        $resStatus = $conn->query("SELECT status, COUNT(*) as jumlah FROM antrian GROUP BY status");
        $chartStatus = [];
        while ($r = $resStatus->fetch_assoc()) {
            $chartStatus[] = $r;
        }

        // Chart Tipe Pendaftaran Breakdown (Online vs Walkin)
        $resTipe = $conn->query("SELECT tipe_pendaftaran, COUNT(*) as jumlah FROM antrian GROUP BY tipe_pendaftaran");
        $chartTipe = [];
        while ($r = $resTipe->fetch_assoc()) {
            $chartTipe[] = $r;
        }

        // 10 Pendaftaran Terbaru
        $resRecent = $conn->query("SELECT id, nomor, kode_antrian, nama, layanan, status, tipe_pendaftaran, DATE_FORMAT(created_at, '%H:%i') as jam FROM antrian ORDER BY id DESC LIMIT 8");
        $recentAntrian = [];
        while ($r = $resRecent->fetch_assoc()) {
            $recentAntrian[] = $r;
        }

        // Ulasan SKM Terbaru
        $resSKMFeed = $conn->query("SELECT id, nomor, nama, layanan, pendapat, catatan, created_at FROM antrian WHERE pendapat IS NOT NULL AND pendapat != '' ORDER BY id DESC LIMIT 5");
        $recentFeedback = [];
        while ($r = $resSKMFeed->fetch_assoc()) {
            $recentFeedback[] = $r;
        }

        // Total Antrean Aktif (Sedang Dipanggil / Dilayani Saat Ini)
        $resAktif = $conn->query("SELECT COUNT(*) as total FROM antrian WHERE status IN ('Dipanggil', 'Dilayani')");
        $totalAktif = $resAktif->fetch_assoc()['total'];

        // Total Antrean Menunggu Hari Ini
        $resMenunggu = $conn->query("SELECT COUNT(*) as total FROM antrian WHERE status = 'Menunggu' AND tanggal = CURRENT_DATE()");
        $totalMenunggu = $resMenunggu->fetch_assoc()['total'];

        // Total Online vs Walkin
        $resOnline = $conn->query("SELECT COUNT(*) as total FROM antrian WHERE tipe_pendaftaran = 'online'");
        $totalOnline = $resOnline->fetch_assoc()['total'];
        $resWalkin = $conn->query("SELECT COUNT(*) as total FROM antrian WHERE tipe_pendaftaran = 'walkin'");
        $totalWalkin = $resWalkin->fetch_assoc()['total'];

        // Total Pengaduan Masuk dari Tabel skm_pengaduan
        $resPengaduan = $conn->query("SELECT COUNT(*) as total FROM skm_pengaduan WHERE tipe = 'pengaduan'");
        $totalPengaduan = $resPengaduan->fetch_assoc()['total'];

        // Demografi: Kelompok Umur
        $resUmur = $conn->query("SELECT umur, COUNT(*) as jumlah FROM antrian WHERE umur IS NOT NULL AND umur != '' GROUP BY umur");
        $chartUmur = [];
        while ($r = $resUmur->fetch_assoc()) {
            $chartUmur[] = $r;
        }

        // Demografi: Jenis Kelamin
        $resJK = $conn->query("SELECT jenis_kelamin, COUNT(*) as jumlah FROM antrian WHERE jenis_kelamin IS NOT NULL AND jenis_kelamin != '' GROUP BY jenis_kelamin");
        $chartJK = [];
        while ($r = $resJK->fetch_assoc()) {
            $chartJK[] = $r;
        }

        // Pemanfaatan Data
        $resPemanfaatan = $conn->query("SELECT pemanfaatan, COUNT(*) as jumlah FROM antrian WHERE pemanfaatan IS NOT NULL AND pemanfaatan != '' GROUP BY pemanfaatan");
        $chartPemanfaatan = [];
        while ($r = $resPemanfaatan->fetch_assoc()) {
            $chartPemanfaatan[] = $r;
        }

        // Monev Pembangunan (Ya / Tidak)
        $resMonev = $conn->query("SELECT monev, COUNT(*) as jumlah FROM antrian WHERE monev IS NOT NULL AND monev != '' GROUP BY monev");
        $chartMonev = [];
        while ($r = $resMonev->fetch_assoc()) {
            $chartMonev[] = $r;
        }

        // Fasilitas Layanan (Datang Langsung / Live Chat)
        $resFasilitas = $conn->query("SELECT fasilitas, COUNT(*) as jumlah FROM antrian WHERE fasilitas IS NOT NULL AND fasilitas != '' GROUP BY fasilitas");
        $chartFasilitas = [];
        while ($r = $resFasilitas->fetch_assoc()) {
            $chartFasilitas[] = $r;
        }

        // 5 Feedback & Pengaduan Terbaru dari skm_pengaduan
        $resRecentPengaduan = $conn->query("SELECT id, tipe, nama, kontak, rating_atau_kategori, pesan, DATE_FORMAT(created_at, '%d/%m/%Y %H:%i') as created_at FROM skm_pengaduan ORDER BY id DESC LIMIT 5");
        $recentPengaduan = [];
        while ($r = $resRecentPengaduan->fetch_assoc()) {
            $recentPengaduan[] = $r;
        }

        sendJsonResponse('success', 'Data KPI berhasil dihitung.', [
            'total_pengunjung' => $totalPengunjung,
            'total_antrian' => $totalAntrian,
            'total_antrian_today' => $totalAntrianToday,
            'total_selesai' => $totalSelesai,
            'total_aktif' => $totalAktif,
            'total_menunggu' => $totalMenunggu,
            'total_online' => $totalOnline,
            'total_walkin' => $totalWalkin,
            'total_pengaduan' => $totalPengaduan,
            'skm_puas_persen' => $skmPersen,
            'skm_skor' => $skmSkor,
            'skm_nilai' => $skmNilai,
            'skm_mutu' => $skmMutu,
            'chart_layanan' => $chartLayanan,
            'chart_pekerjaan' => $chartPekerjaan,
            'chart_pendidikan' => $chartPendidikan,
            'chart_instansi' => $chartInstansi,
            'chart_status' => $chartStatus,
            'chart_tipe' => $chartTipe,
            'chart_umur' => $chartUmur,
            'chart_jk' => $chartJK,
            'chart_pemanfaatan' => $chartPemanfaatan,
            'chart_monev' => $chartMonev,
            'chart_fasilitas' => $chartFasilitas,
            'recent_antrian' => $recentAntrian,
            'recent_feedback' => $recentFeedback,
            'recent_pengaduan' => $recentPengaduan
        ]);
        break;


    // ----------------------------------------------------
    // 5. WIDGET MELAYANG FEEDBACK / PENGADUAN
    // ----------------------------------------------------
    case 'save_widget_feedback':
        // Batasi laju 3 masukan feedback per jam per IP
        if (!checkRateLimit($conn, 'widget_feedback', 3, 3600)) {
            sendJsonResponse('error', 'Batas masukan feedback tercapai. Silakan coba lagi beberapa saat lagi.', null, 429);
        }

        $tipe = sanitizeInput($_POST['tipe'] ?? 'penilaian');
        $nama = sanitizeInput($_POST['nama'] ?? 'Pengunjung');
        $kontak = sanitizeInput($_POST['kontak'] ?? '-');
        $rating_atau_kategori = sanitizeInput($_POST['rating_atau_kategori'] ?? 'Sangat Puas');
        $pesan = sanitizeInput($_POST['pesan'] ?? '');

        if (empty($pesan)) sendJsonResponse('error', 'Pesan wajib diisi.');

        $allowedTypes = ['penilaian', 'pengaduan'];
        if (!in_array($tipe, $allowedTypes)) $tipe = 'penilaian';

        $stmt = $conn->prepare("INSERT INTO skm_pengaduan (tipe, nama, kontak, rating_atau_kategori, pesan) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $tipe, $nama, $kontak, $rating_atau_kategori, $pesan);

        if ($stmt->execute()) {
            sendJsonResponse('success', 'Terima kasih atas partisipasi dan masukan Anda!');
        } else {
            sendJsonResponse('error', 'Gagal menyimpan feedback.');
        }
        break;

    default:
        sendJsonResponse('error', 'Action API tidak dikenali.', null, 400);
        break;
}
?>
