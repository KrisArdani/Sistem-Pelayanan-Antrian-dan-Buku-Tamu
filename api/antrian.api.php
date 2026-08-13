<?php
// SPST BPS Kota Tegal - API Module: Antrean

switch ($action) {
    case 'save_antrian':
        // Ambil parameter dengan aman
        $userId = getCurrentUserId();
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
        $tanggal = sanitizeInput($_POST['tanggal'] ?? getTodayDate());
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

    case 'get_my_antrian':
        requireAuth(['pengunjung', 'petugas', 'admin', 'kepala']);
        $userId = $_SESSION['user_id'] ?? 0;
        $userNoHp = $_SESSION['user_nohp'] ?? '';
        $todayStr = date('Y-m-d');

        $stmt = $conn->prepare("SELECT id, user_id, kode_antrian, nomor, nama, jenis_kelamin, umur, nohp, email, pendidikan, pekerjaan, instansi, kategori_instansi, fasilitas, layanan, pemanfaatan, data_diinginkan, foto, monev, tanggal, waktu, pendapat, catatan, tipe_pendaftaran, status, created_at FROM antrian WHERE user_id = ? OR (nohp = ? AND nohp != '') ORDER BY id DESC");
        $stmt->bind_param("is", $userId, $userNoHp);
        $stmt->execute();
        $result = $stmt->get_result();

        // Ambil nomor antrean yang sedang dipanggil/dilayani hari ini (jika ada)
        $stmtActive = $conn->prepare("SELECT nomor, status FROM antrian WHERE tanggal = ? AND status IN ('Dipanggil', 'Dilayani') ORDER BY id DESC LIMIT 1");
        $stmtActive->bind_param("s", $todayStr);
        $stmtActive->execute();
        $activeRow = $stmtActive->get_result()->fetch_assoc();
        $activeNo = $activeRow['nomor'] ?? '-';
        $activeStatus = $activeRow['status'] ?? '-';
        $stmtActive->close();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $rowId = intval($row['id']);
            $rowTanggal = $row['tanggal'];
            $rowStatus = $row['status'];

            if (in_array($rowStatus, ['Menunggu', 'Dipanggil', 'Dilayani']) && $rowTanggal === $todayStr) {
                // Hitung berapa antrean berstatus Menunggu/Dipanggil/Dilayani yang ada sebelum ID antrean ini pada hari ini
                $stmtAhead = $conn->prepare("SELECT COUNT(*) as total_ahead FROM antrian WHERE tanggal = ? AND status IN ('Menunggu', 'Dipanggil', 'Dilayani') AND id < ?");
                $stmtAhead->bind_param("si", $todayStr, $rowId);
                $stmtAhead->execute();
                $aheadRow = $stmtAhead->get_result()->fetch_assoc();
                $stmtAhead->close();

                $aheadCount = intval($aheadRow['total_ahead'] ?? 0);
                $row['antrean_aktif_saat_ini'] = $activeNo;
                $row['status_aktif_saat_ini'] = $activeStatus;
                $row['antrian_di_depan'] = $aheadCount;

                if ($rowStatus === 'Dipanggil' || $rowStatus === 'Dilayani') {
                    $row['estimasi_waktu'] = 'Sedang Dipanggil / Dilayani Saat Ini';
                } else if ($aheadCount === 0) {
                    $row['estimasi_waktu'] = 'Giliran Anda Berikutnya!';
                } else {
                    $minMins = $aheadCount * 10;
                    $maxMins = $aheadCount * 15;
                    $row['estimasi_waktu'] = "± $minMins - $maxMins menit";
                }
            } else {
                $row['antrean_aktif_saat_ini'] = null;
                $row['antrian_di_depan'] = null;
                $row['estimasi_waktu'] = null;
            }

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

    case 'get_visitor_history':
        requireAuth(['petugas', 'admin', 'kepala']);
        $userId = intval($_GET['user_id'] ?? 0);
        $nohp = sanitizeInput($_GET['nohp'] ?? '');
        $nik = sanitizeInput($_GET['nik'] ?? '');
        $search = sanitizeInput($_GET['search'] ?? '');

        // Detail akun user (jika terdaftar di sistem)
        $userInfo = null;
        if ($userId > 0) {
            $stmtUser = $conn->prepare("SELECT id, name, username, nik, nohp, email, jenis_kelamin, umur, pendidikan, pekerjaan, instansi, kategori_instansi, created_at FROM users WHERE id = ?");
            $stmtUser->bind_param("i", $userId);
            $stmtUser->execute();
            $userInfo = $stmtUser->get_result()->fetch_assoc();
            $stmtUser->close();
        }

        if (!$userInfo && !empty($nohp)) {
            $stmtUser = $conn->prepare("SELECT id, name, username, nik, nohp, email, jenis_kelamin, umur, pendidikan, pekerjaan, instansi, kategori_instansi, created_at FROM users WHERE nohp = ? LIMIT 1");
            $stmtUser->bind_param("s", $nohp);
            $stmtUser->execute();
            $userInfo = $stmtUser->get_result()->fetch_assoc();
            $stmtUser->close();
        }

        // Ambil seluruh riwayat kunjungan antrean
        $whereClauses = [];
        $params = [];
        $types = "";

        if ($userId > 0) {
            $whereClauses[] = "user_id = ?";
            $params[] = $userId;
            $types .= "i";
        }
        if (!empty($nohp)) {
            $whereClauses[] = "nohp = ?";
            $params[] = $nohp;
            $types .= "s";
        }
        if (!empty($nik)) {
            $whereClauses[] = "nik = ?";
            $params[] = $nik;
            $types .= "s";
        }

        $sql = "SELECT id, user_id, kode_antrian, nomor, nama, nik, jenis_kelamin, umur, nohp, email, pendidikan, pekerjaan, instansi, kategori_instansi, fasilitas, layanan, pemanfaatan, data_diinginkan, foto, monev, tanggal, waktu, pendapat, catatan, catatan_petugas, tipe_pendaftaran, status, created_at FROM antrian";

        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" OR ", $whereClauses);
        } else if (!empty($search)) {
            $searchLike = "%$search%";
            $sql .= " WHERE nama LIKE ? OR nohp LIKE ? OR nik LIKE ? OR email LIKE ?";
            $params = [$searchLike, $searchLike, $searchLike, $searchLike];
            $types = "ssss";
        }

        $sql .= " ORDER BY id DESC";

        $stmt = $conn->prepare($sql);
        if (!empty($types)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();

        $history = [];
        while ($row = $res->fetch_assoc()) {
            $history[] = $row;
        }

        sendJsonResponse('success', 'Data rekam jejak pengunjung berhasil diambil.', [
            'user' => $userInfo,
            'total_kunjungan' => count($history),
            'history' => $history
        ]);
        break;

    case 'get_antrian':
        requireAuth(['petugas', 'admin', 'kepala']);

        $filterTanggal = sanitizeInput($_GET['tanggal'] ?? 'today');
        $assignedLayanan = trim($_SESSION['user_layanan_tugas'] ?? '');
        $userRole = $_SESSION['user_role'] ?? '';

        $filterLayanan = sanitizeInput($_GET['layanan'] ?? 'all');
        if (empty($filterLayanan)) {
            $filterLayanan = 'all';
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
        $catatanPetugas = isset($_POST['catatan_petugas']) ? sanitizeInput($_POST['catatan_petugas']) : null;
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

        $allowedStatuses = ['Menunggu', 'Dipanggil', 'Dilayani', 'Selesai', 'Terlewat', 'Dibatalkan'];
        if (!in_array($status, $allowedStatuses)) {
            sendJsonResponse('error', 'Status antrian tidak valid: ' . $status);
        }

        if ($catatanPetugas !== null) {
            $stmt = $conn->prepare("UPDATE antrian SET status = ?, catatan_petugas = ? WHERE id = ?");
            $stmt->bind_param("ssi", $status, $catatanPetugas, $id);
        } else {
            $stmt = $conn->prepare("UPDATE antrian SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $id);
        }

        if ($stmt->execute()) {
            logSecurityEvent($conn, 'update_status_antrian', "ID: $id, New status: $status, Catatan: " . ($catatanPetugas ?? '-'));
            sendJsonResponse('success', "Status antrian diubah menjadi $status", ['catatan_petugas' => $catatanPetugas]);
        } else {
            sendJsonResponse('error', 'Gagal mengubah status antrian.');
        }
        break;

    case 'save_catatan_petugas':
        requireAuth(['petugas', 'admin', 'kepala']);
        $id = intval($_POST['id'] ?? 0);
        $catatanPetugas = sanitizeInput($_POST['catatan_petugas'] ?? '');

        if ($id <= 0) sendJsonResponse('error', 'ID antrian tidak valid.');

        $stmt = $conn->prepare("UPDATE antrian SET catatan_petugas = ? WHERE id = ?");
        $stmt->bind_param("si", $catatanPetugas, $id);
        if ($stmt->execute()) {
            logSecurityEvent($conn, 'save_catatan_petugas', "ID: $id, Catatan Petugas Updated");
            sendJsonResponse('success', 'Catatan pelayanan petugas berhasil disimpan.', ['catatan_petugas' => $catatanPetugas]);
        } else {
            sendJsonResponse('error', 'Gagal menyimpan catatan pelayanan petugas.');
        }
        break;

    case 'get_waiting_count':
        $assignedLayanan = trim($_SESSION['user_layanan_tugas'] ?? '');
        $userRole = $_SESSION['user_role'] ?? '';

        $sql = "SELECT COUNT(*) as total_menunggu FROM antrian WHERE status = 'Menunggu' AND DATE(tanggal) = CURDATE()";
        $params = [];
        $types = "";

        if ($userRole === 'petugas' && !empty($assignedLayanan)) {
            $sql .= " AND layanan = ?";
            $params[] = $assignedLayanan;
            $types .= "s";
        }

        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $count = (int)($res['total_menunggu'] ?? 0);

        // Juga dapatkan statistik per-layanan
        $sqlServices = "SELECT layanan, COUNT(*) as count FROM antrian WHERE status = 'Menunggu' AND DATE(tanggal) = CURDATE() GROUP BY layanan";
        $resServices = $conn->query($sqlServices);
        $servicesCount = [];
        if ($resServices) {
            while ($r = $resServices->fetch_assoc()) {
                $servicesCount[$r['layanan']] = (int)$r['count'];
            }
        }

        sendJsonResponse('success', 'Jumlah antrean menunggu.', [
            'total_menunggu' => $count,
            'services' => $servicesCount
        ]);
        break;

    case 'batal_ant_pengunjung':
        requireAuth(['pengunjung', 'petugas', 'admin', 'kepala']);
        $userId = getCurrentUserId();
        $id = intval($_POST['id'] ?? 0);

        if ($id <= 0) sendJsonResponse('error', 'ID antrian tidak valid.');

        // Pastikan tiket milik pengunjung yang bersangkutan (atau admin)
        if ($_SESSION['user_role'] === 'pengunjung') {
            $stmtCheck = $conn->prepare("SELECT id FROM antrian WHERE id = ? AND user_id = ? AND status IN ('Menunggu', 'Dipanggil')");
            $stmtCheck->bind_param("ii", $id, $userId);
            $stmtCheck->execute();
            if ($stmtCheck->get_result()->num_rows === 0) {
                sendJsonResponse('error', 'Tiket antrean tidak ditemukan atau sudah diproses.');
            }
        }

        $stmt = $conn->prepare("UPDATE antrian SET status = 'Dibatalkan' WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            logSecurityEvent($conn, 'batal_ant_pengunjung', "Visitor cancelled queue ID: $id");
            sendJsonResponse('success', 'Antrean berhasil dibatalkan. Anda dapat membuat reservasi baru sekarang.');
        } else {
            sendJsonResponse('error', 'Gagal membatalkan antrean.');
        }
        break;

    default:
        sendJsonResponse('error', 'Action antrean tidak dikenali.', null, 400);
        break;
}
