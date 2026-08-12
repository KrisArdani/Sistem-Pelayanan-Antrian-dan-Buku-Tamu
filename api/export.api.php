<?php
// SPST BPS Kota Tegal - API Module: Export & Laporan Excel/CSV/PDF

switch ($action) {
    case 'export_antrian':
        requireAuth(['petugas', 'admin', 'kepala']);
        $filterTanggal = sanitizeInput($_GET['tanggal'] ?? $_POST['tanggal'] ?? 'today');
        $filterLayanan = sanitizeInput($_GET['layanan'] ?? $_POST['layanan'] ?? 'all');
        $filterStatus = sanitizeInput($_GET['status'] ?? $_POST['status'] ?? 'all');
        $format = strtolower(trim($_GET['format'] ?? $_POST['format'] ?? 'excel'));

        $assignedLayanan = trim($_SESSION['user_layanan_tugas'] ?? '');
        if ($_SESSION['user_role'] === 'petugas' && !empty($assignedLayanan)) {
            $filterLayanan = $assignedLayanan;
        }

        if ($format === 'pdf') {
            sendJsonResponse('success', 'URL Cetak PDF', [
                'redirect_url' => "admin/cetak_laporan.php?type=antrian&tanggal=" . urlencode($filterTanggal) . "&layanan=" . urlencode($filterLayanan) . "&status=" . urlencode($filterStatus)
            ]);
        }

        // Export Excel / CSV
        $whereClause = [];
        $params = [];
        $types = "";

        if ($filterTanggal === 'today') {
            $whereClause[] = "DATE(tanggal) = CURDATE()";
        } else if ($filterTanggal === 'tomorrow') {
            $whereClause[] = "DATE(tanggal) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)";
        } else if ($filterTanggal !== 'all' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterTanggal)) {
            $whereClause[] = "DATE(tanggal) = ?";
            $params[] = $filterTanggal;
            $types .= "s";
        }

        if ($filterLayanan !== 'all') {
            $whereClause[] = "layanan = ?";
            $params[] = $filterLayanan;
            $types .= "s";
        }
        if ($filterStatus !== 'all') {
            $whereClause[] = "status = ?";
            $params[] = $filterStatus;
            $types .= "s";
        }

        $sql = "SELECT * FROM antrian";
        if (!empty($whereClause)) {
            $sql .= " WHERE " . implode(" AND ", $whereClause);
        }
        $sql .= " ORDER BY id DESC";

        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $filename = "Rekap_Antrian_SPST_" . date('Ymd_His') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF"; // UTF-8 BOM

        $output = fopen('php://output', 'w');
        fputcsv($output, ['No', 'Kode Antrian', 'Nama', 'NIK', 'Jenis Kelamin', 'Umur', 'No HP', 'Email', 'Pendidikan', 'Pekerjaan', 'Instansi', 'Kategori Instansi', 'Fasilitas', 'Layanan PST', 'Tujuan Pemanfaatan', 'Data Diinginkan', 'Monev', 'Tipe Pendaftaran', 'Status', 'Tanggal', 'Waktu', 'Catatan Petugas / Data Diberikan', 'Tingkat Kepuasan SKM', 'Catatan SKM']);

        foreach ($rows as $idx => $r) {
            fputcsv($output, [
                $idx + 1,
                $r['kode_antrian'],
                $r['nama'],
                "'" . $r['nik'], // Prefix single quote for Excel text formatting
                $r['jenis_kelamin'],
                $r['umur'],
                "'" . $r['nohp'],
                $r['email'],
                $r['pendidikan'],
                $r['pekerjaan'],
                $r['instansi'],
                $r['kategori_instansi'],
                $r['fasilitas'],
                $r['layanan'],
                $r['pemanfaatan'],
                $r['data_diinginkan'],
                $r['monev'],
                $r['tipe_pendaftaran'],
                $r['status'],
                $r['tanggal'],
                $r['waktu'],
                $r['catatan_petugas'] ?? '-',
                $r['pendapat'],
                $r['catatan']
            ]);
        }
        fclose($output);
        exit();

    case 'export_bukutamu':
        requireAuth(['petugas', 'admin', 'kepala']);
        $filterTanggal = sanitizeInput($_GET['tanggal'] ?? $_GET['waktu'] ?? $_POST['tanggal'] ?? $_POST['waktu'] ?? 'today');
        $filterLayanan = sanitizeInput($_GET['layanan'] ?? $_POST['layanan'] ?? 'all');
        $filterStatus = sanitizeInput($_GET['status'] ?? $_POST['status'] ?? 'all');
        $filterTipe = sanitizeInput($_GET['tipe'] ?? $_POST['tipe'] ?? 'all');
        $filterKategoriInstansi = sanitizeInput($_GET['kategori_instansi'] ?? $_POST['kategori_instansi'] ?? '');
        $q = sanitizeInput($_GET['q'] ?? $_POST['q'] ?? '');
        $tglMulai = sanitizeInput($_GET['tanggal_mulai'] ?? $_POST['tanggal_mulai'] ?? '');
        $tglSelesai = sanitizeInput($_GET['tanggal_selesai'] ?? $_POST['tanggal_selesai'] ?? '');
        $format = strtolower(trim($_GET['format'] ?? $_POST['format'] ?? 'excel'));

        $assignedLayanan = trim($_SESSION['user_layanan_tugas'] ?? '');
        if ($_SESSION['user_role'] === 'petugas' && !empty($assignedLayanan)) {
            $filterLayanan = $assignedLayanan;
        }

        if ($format === 'pdf') {
            $queryStr = http_build_query([
                'type' => 'bukutamu',
                'q' => $q,
                'kategori_instansi' => $filterKategoriInstansi,
                'layanan' => $filterLayanan,
                'waktu' => $filterTanggal,
                'tanggal_mulai' => $tglMulai,
                'tanggal_selesai' => $tglSelesai,
                'tipe' => $filterTipe,
                'status' => $filterStatus
            ]);
            sendJsonResponse('success', 'URL Cetak PDF', [
                'redirect_url' => "admin/cetak_laporan.php?" . $queryStr
            ]);
        }

        $whereClause = [];
        $params = [];
        $types = "";

        if (!empty($q)) {
            $whereClause[] = "(nama LIKE ? OR instansi LIKE ? OR layanan LIKE ? OR kode_antrian LIKE ? OR nomor LIKE ? OR nohp LIKE ? OR email LIKE ?)";
            $likeQ = '%' . $q . '%';
            for ($i = 0; $i < 7; $i++) {
                $params[] = $likeQ;
                $types .= "s";
            }
        }

        if (!empty($filterKategoriInstansi)) {
            $whereClause[] = "kategori_instansi = ?";
            $params[] = $filterKategoriInstansi;
            $types .= "s";
        }

        if ($filterLayanan !== 'all' && !empty($filterLayanan)) {
            $whereClause[] = "layanan LIKE ?";
            $params[] = '%' . $filterLayanan . '%';
            $types .= "s";
        }

        if ($filterTipe !== 'all' && !empty($filterTipe)) {
            $whereClause[] = "tipe_pendaftaran = ?";
            $params[] = $filterTipe;
            $types .= "s";
        }

        if ($filterStatus !== 'all' && !empty($filterStatus)) {
            $whereClause[] = "status = ?";
            $params[] = $filterStatus;
            $types .= "s";
        }

        if ($filterTanggal === 'today') {
            $whereClause[] = "DATE(tanggal) = CURDATE()";
        } else if ($filterTanggal === 'tomorrow') {
            $whereClause[] = "DATE(tanggal) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)";
        } else if ($filterTanggal === 'this_week') {
            $whereClause[] = "YEARWEEK(tanggal, 1) = YEARWEEK(CURDATE(), 1)";
        } else if ($filterTanggal === 'this_month') {
            $whereClause[] = "YEAR(tanggal) = YEAR(CURDATE()) AND MONTH(tanggal) = MONTH(CURDATE())";
        } else if ($filterTanggal === 'custom' && !empty($tglMulai) && !empty($tglSelesai)) {
            $whereClause[] = "tanggal BETWEEN ? AND ?";
            $params[] = $tglMulai;
            $params[] = $tglSelesai;
            $types .= "ss";
        } else if ($filterTanggal !== 'all' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterTanggal)) {
            $whereClause[] = "DATE(tanggal) = ?";
            $params[] = $filterTanggal;
            $types .= "s";
        }

        $sql = "SELECT id, user_id, kode_antrian AS kode_bt, nomor, nama, nik, jenis_kelamin, umur, nohp, email, pendidikan, pekerjaan, instansi, kategori_instansi, fasilitas, layanan, pemanfaatan, data_diinginkan, foto, pendapat, monev, catatan, catatan_petugas, tipe_pendaftaran, status, tanggal, waktu, created_at FROM antrian";
        if (!empty($whereClause)) {
            $sql .= " WHERE " . implode(" AND ", $whereClause);
        }
        $sql .= " ORDER BY id DESC";

        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $filename = "Rekap_BukuTamu_SPST_" . date('Ymd_His') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF";

        $output = fopen('php://output', 'w');
        fputcsv($output, ['No', 'Kode BT / Antrian', 'Nama Tamu', 'NIK', 'Jenis Kelamin', 'Umur', 'No HP', 'Email', 'Pendidikan', 'Pekerjaan', 'Instansi', 'Kategori Instansi', 'Layanan Terkait', 'Data Diinginkan (Pengunjung)', 'Catatan Petugas / Data Diberikan', 'Tipe Pendaftaran', 'Status', 'Tanggal', 'Waktu']);

        foreach ($rows as $idx => $r) {
            fputcsv($output, [
                $idx + 1,
                $r['kode_bt'],
                $r['nama'],
                "'" . $r['nik'],
                $r['jenis_kelamin'],
                $r['umur'],
                "'" . $r['nohp'],
                $r['email'],
                $r['pendidikan'],
                $r['pekerjaan'],
                $r['instansi'],
                $r['kategori_instansi'],
                $r['layanan'],
                $r['data_diinginkan'],
                $r['catatan_petugas'] ?? '-',
                $r['tipe_pendaftaran'],
                $r['status'],
                $r['tanggal'],
                $r['waktu']
            ]);
        }
        fclose($output);
        exit();

    case 'export_skm':
        requireAuth(['petugas', 'admin', 'kepala']);
        $filterTanggal = sanitizeInput($_GET['tanggal'] ?? $_GET['waktu'] ?? $_POST['tanggal'] ?? $_POST['waktu'] ?? 'all');
        $filterLayanan = sanitizeInput($_GET['layanan'] ?? $_POST['layanan'] ?? 'all');
        $tglMulai = sanitizeInput($_GET['tanggal_mulai'] ?? $_POST['tanggal_mulai'] ?? '');
        $tglSelesai = sanitizeInput($_GET['tanggal_selesai'] ?? $_POST['tanggal_selesai'] ?? '');
        $format = strtolower(trim($_GET['format'] ?? $_POST['format'] ?? 'excel'));

        if ($format === 'pdf') {
            sendJsonResponse('success', 'URL Cetak PDF', [
                'redirect_url' => "admin/cetak_laporan.php?type=skm&tanggal=" . urlencode($filterTanggal) . "&layanan=" . urlencode($filterLayanan) . "&tanggal_mulai=" . urlencode($tglMulai) . "&tanggal_selesai=" . urlencode($tglSelesai)
            ]);
        }

        $whereClause = ["pendapat IS NOT NULL AND pendapat != ''"];
        $params = [];
        $types = "";

        if ($filterLayanan !== 'all') {
            $whereClause[] = "layanan LIKE ?";
            $params[] = '%' . $filterLayanan . '%';
            $types .= "s";
        }

        if ($filterTanggal === 'today') {
            $whereClause[] = "DATE(tanggal) = CURDATE()";
        } else if ($filterTanggal === 'tomorrow') {
            $whereClause[] = "DATE(tanggal) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)";
        } else if ($filterTanggal === 'this_week') {
            $whereClause[] = "YEARWEEK(tanggal, 1) = YEARWEEK(CURDATE(), 1)";
        } else if ($filterTanggal === 'this_month') {
            $whereClause[] = "YEAR(tanggal) = YEAR(CURDATE()) AND MONTH(tanggal) = MONTH(CURDATE())";
        } else if ($filterTanggal === 'custom' && !empty($tglMulai) && !empty($tglSelesai)) {
            $whereClause[] = "tanggal BETWEEN ? AND ?";
            $params[] = $tglMulai;
            $params[] = $tglSelesai;
            $types .= "ss";
        } else if ($filterTanggal !== 'all' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterTanggal)) {
            $whereClause[] = "DATE(tanggal) = ?";
            $params[] = $filterTanggal;
            $types .= "s";
        }

        $sql = "SELECT id, kode_antrian, nama, layanan, pendapat, catatan, tanggal FROM antrian WHERE " . implode(" AND ", $whereClause) . " ORDER BY id DESC";

        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $filename = "Rekap_SKM_SPST_" . date('Ymd_His') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF";

        $output = fopen('php://output', 'w');
        fputcsv($output, ['No', 'Kode Tiket', 'Responden', 'Layanan PST', 'Tingkat Kepuasan SKM', 'Kritik & Saran', 'Tanggal']);

        foreach ($rows as $idx => $r) {
            fputcsv($output, [
                $idx + 1,
                $r['kode_antrian'],
                $r['nama'],
                $r['layanan'],
                $r['pendapat'],
                $r['catatan'],
                $r['tanggal']
            ]);
        }
        fclose($output);
        exit();

    default:
        sendJsonResponse('error', 'Action export tidak dikenali.', null, 400);
        break;
}
