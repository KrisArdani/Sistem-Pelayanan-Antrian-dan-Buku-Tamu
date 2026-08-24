<?php
// SPST BPS Kota Tegal - API Module: Manajemen Pengguna

switch ($action) {
    case 'get_users':
        requireAuth(['admin']);

        $search = sanitizeInput($_GET['search'] ?? '');
        $roleFilter = sanitizeInput($_GET['role'] ?? 'all');

        $query = "SELECT id, username, name, nik, role, layanan_tugas, jenis_kelamin, umur, nohp, email, pendidikan, pekerjaan, instansi, kategori_instansi, created_at FROM users WHERE 1=1";
        $types = "";
        $params = [];

        if (!empty($roleFilter) && $roleFilter !== 'all') {
            $query .= " AND role = ?";
            $types .= "s";
            $params[] = $roleFilter;
        }

        if (!empty($search)) {
            $likeSearch = '%' . $search . '%';
            $query .= " AND (username LIKE ? OR name LIKE ? OR email LIKE ? OR nohp LIKE ? OR instansi LIKE ?)";
            $types .= "sssss";
            $params[] = $likeSearch;
            $params[] = $likeSearch;
            $params[] = $likeSearch;
            $params[] = $likeSearch;
            $params[] = $likeSearch;
        }

        $query .= " ORDER BY id DESC";

        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $users = [];
        $counts = [
            'total' => 0,
            'petugas' => 0,
            'admin' => 0,
            'kepala' => 0,
            'pengunjung' => 0
        ];

        // Hitung total ringkasan pengguna
        $resCount = $conn->query("SELECT role, COUNT(*) as cnt FROM users GROUP BY role");
        while ($row = $resCount->fetch_assoc()) {
            if (isset($counts[$row['role']])) {
                $counts[$row['role']] = (int)$row['cnt'];
            }
            $counts['total'] += (int)$row['cnt'];
        }

        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }

        sendJsonResponse('success', 'Data pengguna berhasil diambil.', [
            'users' => $users,
            'summary' => $counts
        ]);
        break;

    case 'save_user':
        requireAuth(['admin']);

        $id = intval($_POST['id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $nik = trim($_POST['nik'] ?? '');
        $role = trim($_POST['role'] ?? 'petugas');
        $layanan_tugas = ($role === 'petugas') ? trim($_POST['layanan_tugas'] ?? 'Pelayanan Terpadu') : NULL;
        $jenis_kelamin = trim($_POST['jenis_kelamin'] ?? 'Laki Laki');
        $umur = trim($_POST['umur'] ?? '17-25 tahun');
        $nohp = trim($_POST['nohp'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pendidikan = trim($_POST['pendidikan'] ?? 'D4-S1');
        $pekerjaan = trim($_POST['pekerjaan'] ?? 'Pegawai BPS');
        $instansi = trim($_POST['instansi'] ?? 'BPS Kota Tegal');
        $kategori_instansi = trim($_POST['kategori_instansi'] ?? 'Instansi Pemerintah');

        if (empty($username) || empty($name) || empty($role)) {
            sendJsonResponse('error', 'Username, Nama Lengkap, dan Role wajib diisi.');
        }

        $allowedRoles = ['petugas', 'admin', 'kepala'];
        if (!in_array($role, $allowedRoles)) {
            sendJsonResponse('error', 'Admin hanya dapat mengelola akun staf internal (Petugas, Admin, Kepala). Akun pengunjung mendaftar secara mandiri.');
        }

        if (!empty($email) && !validateEmail($email)) {
            sendJsonResponse('error', 'Format email tidak valid.');
        }

        if (!empty($nohp) && !validatePhone($nohp)) {
            sendJsonResponse('error', 'Format nomor HP tidak valid.');
        }

        // Cek keunikan username
        if ($id > 0) {
            // Cek role target user sebelum diubah
            $stmtRole = $conn->prepare("SELECT role FROM users WHERE id = ?");
            $stmtRole->bind_param("i", $id);
            $stmtRole->execute();
            $targetUser = $stmtRole->get_result()->fetch_assoc();
            if ($targetUser && $targetUser['role'] === 'pengunjung') {
                sendJsonResponse('error', 'Admin tidak dapat mengubah akun pengunjung. Akun pengunjung mengelola profil & password secara mandiri.');
            }

            $stmtCheck = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmtCheck->bind_param("si", $username, $id);
        } else {
            $stmtCheck = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmtCheck->bind_param("s", $username);
        }
        $stmtCheck->execute();
        if ($stmtCheck->get_result()->num_rows > 0) {
            sendJsonResponse('error', 'Username sudah digunakan oleh akun lain.');
        }

        try {
            if ($id > 0) {
                // Update User Internal
                if (!empty($password)) {
                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $conn->prepare("UPDATE users SET username = ?, password = ?, name = ?, nik = ?, role = ?, layanan_tugas = ?, jenis_kelamin = ?, umur = ?, nohp = ?, email = ?, pendidikan = ?, pekerjaan = ?, instansi = ?, kategori_instansi = ? WHERE id = ?");
                    $stmt->bind_param("ssssssssssssssi", $username, $hashedPassword, $name, $nik, $role, $layanan_tugas, $jenis_kelamin, $umur, $nohp, $email, $pendidikan, $pekerjaan, $instansi, $kategori_instansi, $id);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET username = ?, name = ?, nik = ?, role = ?, layanan_tugas = ?, jenis_kelamin = ?, umur = ?, nohp = ?, email = ?, pendidikan = ?, pekerjaan = ?, instansi = ?, kategori_instansi = ? WHERE id = ?");
                    $stmt->bind_param("sssssssssssssi", $username, $name, $nik, $role, $layanan_tugas, $jenis_kelamin, $umur, $nohp, $email, $pendidikan, $pekerjaan, $instansi, $kategori_instansi, $id);
                }

                if ($stmt->execute()) {
                    logSecurityEvent($conn, 'update_user', "Updated internal user ID: $id ($username - $layanan_tugas)");
                    sendJsonResponse('success', 'Data akun staf internal berhasil diperbarui!');
                } else {
                    error_log("DB Update Error save_user: " . $stmt->error);
                    sendJsonResponse('error', 'Gagal memperbarui data pengguna. Terjadi kesalahan pada basis data.');
                }
            } else {
                // Insert New Internal User
                if (empty($password)) {
                    sendJsonResponse('error', 'Password wajib diisi untuk pendaftaran akun internal baru.');
                }

                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("INSERT INTO users (username, password, name, nik, role, layanan_tugas, jenis_kelamin, umur, nohp, email, pendidikan, pekerjaan, instansi, kategori_instansi) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssssssssss", $username, $hashedPassword, $name, $nik, $role, $layanan_tugas, $jenis_kelamin, $umur, $nohp, $email, $pendidikan, $pekerjaan, $instansi, $kategori_instansi);

                if ($stmt->execute()) {
                    logSecurityEvent($conn, 'create_user', "Created new internal user username: $username ($role - $layanan_tugas)");
                    sendJsonResponse('success', 'Akun staf internal baru berhasil dibuat!');
                } else {
                    error_log("DB Insert Error save_user: " . $stmt->error);
                    sendJsonResponse('error', 'Gagal membuat akun pengguna baru. Silakan periksa kembali data isian.');
                }
            }
        } catch (Throwable $e) {
            error_log("Exception save_user: " . $e->getMessage());
            sendJsonResponse('error', 'Terjadi kesalahan sistem saat menyimpan data pengguna.');
        }
        break;

    case 'reset_password_user':
        requireAuth(['admin']);

        $id = intval($_POST['id'] ?? 0);
        $newPassword = trim($_POST['new_password'] ?? '');

        if ($id <= 0) sendJsonResponse('error', 'ID pengguna tidak valid.');
        if (empty($newPassword)) sendJsonResponse('error', 'Password baru tidak boleh kosong.');
        if (!validatePasswordLength($newPassword, 6)) sendJsonResponse('error', 'Password minimal terdiri dari 6 karakter.');

        // Proteksi: Admin tidak boleh reset password pengunjung secara langsung
        $stmtRole = $conn->prepare("SELECT role, username FROM users WHERE id = ?");
        $stmtRole->bind_param("i", $id);
        $stmtRole->execute();
        $targetUser = $stmtRole->get_result()->fetch_assoc();

        if (!$targetUser) sendJsonResponse('error', 'Pengguna tidak ditemukan.');
        if ($targetUser['role'] === 'pengunjung') {
            sendJsonResponse('error', 'Admin tidak dapat mereset password akun pengunjung. Pengunjung melakukan reset password mandiri via Email.');
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $id);

        if ($stmt->execute()) {
            logSecurityEvent($conn, 'reset_password_user', "Reset password for internal user ID: $id ({$targetUser['username']})");
            sendJsonResponse('success', 'Password akun staf internal berhasil direset.');
        } else {
            sendJsonResponse('error', 'Gagal mereset password pengguna.');
        }
        break;

    case 'delete_user':
        requireAuth(['admin']);

        $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) sendJsonResponse('error', 'ID pengguna tidak valid.');

        // Self deletion protection
        if ($id === intval($_SESSION['user_id'] ?? 0)) {
            sendJsonResponse('error', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang digunakan saat ini.');
        }

        // Proteksi: Hapus pengunjung dari admin
        $stmtRole = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $stmtRole->bind_param("i", $id);
        $stmtRole->execute();
        $targetUser = $stmtRole->get_result()->fetch_assoc();

        if ($targetUser && $targetUser['role'] === 'pengunjung') {
            sendJsonResponse('error', 'Admin tidak dapat menghapus akun pengunjung. Akun pengunjung bersifat read-only di panel admin.');
        }

        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            logSecurityEvent($conn, 'delete_user', "Deleted internal user ID: $id");
            sendJsonResponse('success', 'Akun pengguna berhasil dihapus.');
        } else {
            sendJsonResponse('error', 'Gagal menghapus pengguna. Pengguna mungkin memiliki data keterkaitan.');
        }
        break;

    default:
        sendJsonResponse('error', 'Action user management tidak dikenali.', null, 400);
        break;
}
