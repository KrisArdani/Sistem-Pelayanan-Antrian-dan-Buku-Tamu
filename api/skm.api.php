<?php
// SPST BPS Kota Tegal - API Module: SKM (Survei Kepuasan Masyarakat)

switch ($action) {
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

    default:
        sendJsonResponse('error', 'Action SKM tidak dikenali.', null, 400);
        break;
}
