<?php
// SPST BPS Kota Tegal - API Module: Buku Tamu

switch ($action) {
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
            $stmt = $conn->prepare("SELECT id, user_id, kode_antrian AS kode_bt, nomor, nama, jenis_kelamin, umur, nohp, email, pendidikan, pekerjaan, instansi, kategori_instansi, fasilitas, layanan, pemanfaatan, data_diinginkan, foto, pendapat, monev, catatan, catatan_petugas, tipe_pendaftaran, status, CONCAT(tanggal, ' ', waktu) AS timestamp, created_at FROM antrian WHERE nama LIKE ? OR instansi LIKE ? OR layanan LIKE ? OR kode_antrian LIKE ? OR nomor LIKE ? OR nohp LIKE ? ORDER BY id DESC");
            $stmt->bind_param("ssssss", $likeSearch, $likeSearch, $likeSearch, $likeSearch, $likeSearch, $likeSearch);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $conn->query("SELECT id, user_id, kode_antrian AS kode_bt, nomor, nama, jenis_kelamin, umur, nohp, email, pendidikan, pekerjaan, instansi, kategori_instansi, fasilitas, layanan, pemanfaatan, data_diinginkan, foto, pendapat, monev, catatan, catatan_petugas, tipe_pendaftaran, status, CONCAT(tanggal, ' ', waktu) AS timestamp, created_at FROM antrian ORDER BY id DESC");
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
        $catatanPetugas = isset($_POST['catatan_petugas']) ? sanitizeInput($_POST['catatan_petugas']) : null;
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

        if ($catatanPetugas !== null) {
            $stmt = $conn->prepare("UPDATE antrian SET status = ?, catatan_petugas = ? WHERE id = ?");
            $stmt->bind_param("ssi", $status, $catatanPetugas, $id);
        } else {
            $stmt = $conn->prepare("UPDATE antrian SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $id);
        }

        if ($stmt->execute()) {
            logSecurityEvent($conn, 'verify_bukutamu', "Updated status to $status for Queue ID: $id");
            sendJsonResponse('success', "Status kunjungan berhasil diperbarui menjadi '$status'!");
        } else {
            sendJsonResponse('error', 'Gagal memperbarui status.');
        }
        break;

    default:
        sendJsonResponse('error', 'Action buku tamu tidak dikenali.', null, 400);
        break;
}
