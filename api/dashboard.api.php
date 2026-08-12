<?php
// SPST BPS Kota Tegal - API Module: Dashboard KPI & Widget Feedback

switch ($action) {
    case 'get_dashboard_kpi':
        requireAuth(['admin', 'kepala', 'petugas']);

        $filterTanggal = sanitizeInput($_GET['tanggal'] ?? $_GET['waktu'] ?? $_POST['tanggal'] ?? $_POST['waktu'] ?? 'all');
        $tglMulai = sanitizeInput($_GET['tanggal_mulai'] ?? $_POST['tanggal_mulai'] ?? '');
        $tglSelesai = sanitizeInput($_GET['tanggal_selesai'] ?? $_POST['tanggal_selesai'] ?? '');

        $whereClause = [];
        $params = [];
        $types = "";

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

        $whereSql = !empty($whereClause) ? " WHERE " . implode(" AND ", $whereClause) : "";

        // Total Registered Visitors (Akun Pengunjung Terdaftar)
        $resUsers = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'pengunjung'");
        $totalPengunjung = $resUsers->fetch_assoc()['total'];

        // Total Queues / Visits Recorded (Akumulasi Pendaftaran Antrean)
        $resAntrian = $conn->query("SELECT COUNT(*) as total FROM antrian $whereSql");
        $totalAntrian = $resAntrian->fetch_assoc()['total'];

        // SKM Score & Index PermenPAN-RB (Hanya dari antrean yang diisi ulasan)
        $skmWhere = !empty($whereSql) ? "$whereSql AND pendapat IS NOT NULL AND pendapat != ''" : "WHERE pendapat IS NOT NULL AND pendapat != ''";
        $resSKM = $conn->query("SELECT 
            COUNT(*) as total,
            SUM(CASE 
                WHEN pendapat = 'Sangat Puas' THEN 4
                WHEN pendapat = 'Puas' THEN 3
                WHEN pendapat = 'Cukup Puas' THEN 2
                WHEN pendapat = 'Tidak Puas' THEN 1
                ELSE 4 END) as total_skor,
            SUM(CASE WHEN pendapat IN ('Sangat Puas', 'Puas') THEN 1 ELSE 0 END) as puas_count
            FROM antrian $skmWhere");
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
        $resChartLayanan = $conn->query("SELECT layanan, COUNT(*) as jumlah FROM antrian $whereSql GROUP BY layanan");
        $chartLayanan = [];
        while ($r = $resChartLayanan->fetch_assoc()) {
            $chartLayanan[] = $r;
        }

        // Demografi: Pekerjaan
        $pekerjaanWhere = !empty($whereSql) ? "$whereSql AND pekerjaan IS NOT NULL AND pekerjaan != ''" : "WHERE pekerjaan IS NOT NULL AND pekerjaan != ''";
        $resPekerjaan = $conn->query("SELECT pekerjaan, COUNT(*) as jumlah FROM antrian $pekerjaanWhere GROUP BY pekerjaan");
        $chartPekerjaan = [];
        while ($r = $resPekerjaan->fetch_assoc()) {
            $chartPekerjaan[] = $r;
        }

        // Demografi: Pendidikan
        $pendidikanWhere = !empty($whereSql) ? "$whereSql AND pendidikan IS NOT NULL AND pendidikan != ''" : "WHERE pendidikan IS NOT NULL AND pendidikan != ''";
        $resPendidikan = $conn->query("SELECT pendidikan, COUNT(*) as jumlah FROM antrian $pendidikanWhere GROUP BY pendidikan");
        $chartPendidikan = [];
        while ($r = $resPendidikan->fetch_assoc()) {
            $chartPendidikan[] = $r;
        }

        // Demografi: Kategori Instansi
        $instansiWhere = !empty($whereSql) ? "$whereSql AND kategori_instansi IS NOT NULL AND kategori_instansi != ''" : "WHERE kategori_instansi IS NOT NULL AND kategori_instansi != ''";
        $resInstansi = $conn->query("SELECT kategori_instansi, COUNT(*) as jumlah FROM antrian $instansiWhere GROUP BY kategori_instansi");
        $chartInstansi = [];
        while ($r = $resInstansi->fetch_assoc()) {
            $chartInstansi[] = $r;
        }

        // Total Antrean Hari Ini
        $resToday = $conn->query("SELECT COUNT(*) as total FROM antrian WHERE tanggal = CURRENT_DATE()");
        $totalAntrianToday = $resToday->fetch_assoc()['total'];

        // Total Antrean Selesai
        $selesaiWhere = !empty($whereSql) ? "$whereSql AND status = 'Selesai'" : "WHERE status = 'Selesai'";
        $resSelesai = $conn->query("SELECT COUNT(*) as total FROM antrian $selesaiWhere");
        $totalSelesai = $resSelesai->fetch_assoc()['total'];

        // Chart Status Breakdown
        $resStatus = $conn->query("SELECT status, COUNT(*) as jumlah FROM antrian $whereSql GROUP BY status");
        $chartStatus = [];
        while ($r = $resStatus->fetch_assoc()) {
            $chartStatus[] = $r;
        }

        // Chart Tipe Pendaftaran Breakdown (Online vs Walkin)
        $resTipe = $conn->query("SELECT tipe_pendaftaran, COUNT(*) as jumlah FROM antrian $whereSql GROUP BY tipe_pendaftaran");
        $chartTipe = [];
        while ($r = $resTipe->fetch_assoc()) {
            $chartTipe[] = $r;
        }

        // 10 Pendaftaran Terbaru
        $resRecent = $conn->query("SELECT id, nomor, kode_antrian, nama, layanan, status, tipe_pendaftaran, DATE_FORMAT(created_at, '%H:%i') as jam FROM antrian $whereSql ORDER BY id DESC LIMIT 8");
        $recentAntrian = [];
        while ($r = $resRecent->fetch_assoc()) {
            $recentAntrian[] = $r;
        }

        // Ulasan SKM Terbaru
        $resSKMFeed = $conn->query("SELECT id, nomor, nama, layanan, pendapat, catatan, created_at FROM antrian $skmWhere ORDER BY id DESC LIMIT 5");
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
        $onlineWhere = !empty($whereSql) ? "$whereSql AND tipe_pendaftaran = 'online'" : "WHERE tipe_pendaftaran = 'online'";
        $resOnline = $conn->query("SELECT COUNT(*) as total FROM antrian $onlineWhere");
        $totalOnline = $resOnline->fetch_assoc()['total'];
        
        $walkinWhere = !empty($whereSql) ? "$whereSql AND tipe_pendaftaran = 'walkin'" : "WHERE tipe_pendaftaran = 'walkin'";
        $resWalkin = $conn->query("SELECT COUNT(*) as total FROM antrian $walkinWhere");
        $totalWalkin = $resWalkin->fetch_assoc()['total'];

        // Total Pengaduan Masuk dari Tabel skm_pengaduan
        $resPengaduan = $conn->query("SELECT COUNT(*) as total FROM skm_pengaduan WHERE tipe = 'pengaduan'");
        $totalPengaduan = $resPengaduan->fetch_assoc()['total'];

        // Demografi: Kelompok Umur
        $umurWhere = !empty($whereSql) ? "$whereSql AND umur IS NOT NULL AND umur != ''" : "WHERE umur IS NOT NULL AND umur != ''";
        $resUmur = $conn->query("SELECT umur, COUNT(*) as jumlah FROM antrian $umurWhere GROUP BY umur");
        $chartUmur = [];
        while ($r = $resUmur->fetch_assoc()) {
            $chartUmur[] = $r;
        }

        // Demografi: Jenis Kelamin
        $jkWhere = !empty($whereSql) ? "$whereSql AND jenis_kelamin IS NOT NULL AND jenis_kelamin != ''" : "WHERE jenis_kelamin IS NOT NULL AND jenis_kelamin != ''";
        $resJK = $conn->query("SELECT jenis_kelamin, COUNT(*) as jumlah FROM antrian $jkWhere GROUP BY jenis_kelamin");
        $chartJK = [];
        while ($r = $resJK->fetch_assoc()) {
            $chartJK[] = $r;
        }

        // Pemanfaatan Data
        $pemanfaatanWhere = !empty($whereSql) ? "$whereSql AND pemanfaatan IS NOT NULL AND pemanfaatan != ''" : "WHERE pemanfaatan IS NOT NULL AND pemanfaatan != ''";
        $resPemanfaatan = $conn->query("SELECT pemanfaatan, COUNT(*) as jumlah FROM antrian $pemanfaatanWhere GROUP BY pemanfaatan");
        $chartPemanfaatan = [];
        while ($r = $resPemanfaatan->fetch_assoc()) {
            $chartPemanfaatan[] = $r;
        }

        // Monev Pembangunan (Ya / Tidak)
        $monevWhere = !empty($whereSql) ? "$whereSql AND monev IS NOT NULL AND monev != ''" : "WHERE monev IS NOT NULL AND monev != ''";
        $resMonev = $conn->query("SELECT monev, COUNT(*) as jumlah FROM antrian $monevWhere GROUP BY monev");
        $chartMonev = [];
        while ($r = $resMonev->fetch_assoc()) {
            $chartMonev[] = $r;
        }

        // Fasilitas Layanan (Datang Langsung / Live Chat)
        $fasilitasWhere = !empty($whereSql) ? "$whereSql AND fasilitas IS NOT NULL AND fasilitas != ''" : "WHERE fasilitas IS NOT NULL AND fasilitas != ''";
        $resFasilitas = $conn->query("SELECT fasilitas, COUNT(*) as jumlah FROM antrian $fasilitasWhere GROUP BY fasilitas");
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
        sendJsonResponse('error', 'Action dashboard tidak dikenali.', null, 400);
        break;
}
