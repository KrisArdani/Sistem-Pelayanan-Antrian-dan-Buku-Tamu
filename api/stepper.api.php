<?php
// SPST BPS Kota Tegal - API Module: Stepper Status Alur

switch ($action) {
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

    default:
        sendJsonResponse('error', 'Action stepper tidak dikenali.', null, 400);
        break;
}
