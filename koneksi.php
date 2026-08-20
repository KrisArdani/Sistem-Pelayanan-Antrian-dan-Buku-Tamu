<?php
// SPST BPS Kota Tegal - Sistem Pelayanan Statistik Terpadu
require_once __DIR__ . '/config.php';

$host = DB_HOST;
$user = DB_USER;
$pass = DB_PASS;
$dbname = DB_NAME;

// Hubungkan ke server MySQL
$conn = new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    error_log("DB Connection Error: " . $conn->connect_error);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal terkoneksi ke database server. Silakan hubungi administrator.'
    ]);
    exit;
}

// Buat database & tabel secara otomatis jika db_spst belum ada
try {
    $db_check = @$conn->select_db($dbname);
} catch (Throwable $e) {
    $db_check = false;
}

if (!$db_check) {
    $conn->query("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->select_db($dbname);

    // Muat file skema SQL
    $sql_file = __DIR__ . '/db_spst.sql';
    if (file_exists($sql_file)) {
        $sql = file_get_contents($sql_file);
        $conn->multi_query($sql);
        // Bersihkan memori multi query
        while ($conn->next_result()) {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        }
    }
}

// Atur set karakter (charset)
$conn->set_charset("utf8mb4");

// Migrasi otomatis tabel database jika ada kolom baru yang belum tersedia
try {
    $checkUserNik = $conn->query("SHOW COLUMNS FROM users LIKE 'nik'");
    if ($checkUserNik && $checkUserNik->num_rows === 0) {
        $conn->query("ALTER TABLE users ADD COLUMN nik VARCHAR(16) DEFAULT NULL AFTER name");
    }

    $checkUserCol = $conn->query("SHOW COLUMNS FROM users LIKE 'nohp'");
    if ($checkUserCol && $checkUserCol->num_rows === 0) {
        $conn->query("ALTER TABLE users 
            MODIFY COLUMN role ENUM('petugas', 'admin', 'kepala', 'pengunjung') NOT NULL,
            ADD COLUMN jenis_kelamin ENUM('Laki Laki', 'Perempuan') DEFAULT 'Laki Laki' AFTER role,
            ADD COLUMN umur VARCHAR(30) DEFAULT NULL AFTER jenis_kelamin,
            ADD COLUMN nohp VARCHAR(20) DEFAULT NULL AFTER umur,
            ADD COLUMN email VARCHAR(100) DEFAULT NULL AFTER nohp,
            ADD COLUMN pendidikan VARCHAR(50) DEFAULT NULL AFTER email,
            ADD COLUMN pekerjaan VARCHAR(100) DEFAULT NULL AFTER pendidikan,
            ADD COLUMN instansi VARCHAR(150) DEFAULT NULL AFTER pekerjaan,
            ADD COLUMN kategori_instansi VARCHAR(100) DEFAULT NULL AFTER instansi");
    } else {
        $conn->query("ALTER TABLE users MODIFY COLUMN role ENUM('petugas', 'admin', 'kepala', 'pengunjung') NOT NULL");
    }

    $checkUserLayananTugas = $conn->query("SHOW COLUMNS FROM users LIKE 'layanan_tugas'");
    if ($checkUserLayananTugas && $checkUserLayananTugas->num_rows === 0) {
        $conn->query("ALTER TABLE users ADD COLUMN layanan_tugas VARCHAR(150) DEFAULT NULL AFTER role");
    }

    $checkAntrianNik = $conn->query("SHOW COLUMNS FROM antrian LIKE 'nik'");
    if ($checkAntrianNik && $checkAntrianNik->num_rows === 0) {
        $conn->query("ALTER TABLE antrian ADD COLUMN nik VARCHAR(16) DEFAULT NULL AFTER nama");
    }

    $checkBtNik = $conn->query("SHOW COLUMNS FROM buku_tamu LIKE 'nik'");
    if ($checkBtNik && $checkBtNik->num_rows === 0) {
        $conn->query("ALTER TABLE buku_tamu ADD COLUMN nik VARCHAR(16) DEFAULT NULL AFTER nama");
    }

    $checkAntrianCol = $conn->query("SHOW COLUMNS FROM antrian LIKE 'fasilitas'");
    if ($checkAntrianCol && $checkAntrianCol->num_rows === 0) {
        $conn->query("ALTER TABLE antrian 
            ADD COLUMN user_id INT DEFAULT NULL AFTER id,
            ADD COLUMN jenis_kelamin ENUM('Laki Laki', 'Perempuan') DEFAULT 'Laki Laki' AFTER nama,
            ADD COLUMN umur VARCHAR(30) DEFAULT NULL AFTER jenis_kelamin,
            ADD COLUMN nohp VARCHAR(20) DEFAULT NULL AFTER umur,
            ADD COLUMN email VARCHAR(100) DEFAULT NULL AFTER nohp,
            ADD COLUMN pendidikan VARCHAR(50) DEFAULT NULL AFTER email,
            ADD COLUMN pekerjaan VARCHAR(100) DEFAULT NULL AFTER pendidikan,
            ADD COLUMN instansi VARCHAR(150) DEFAULT NULL AFTER pekerjaan,
            ADD COLUMN kategori_instansi VARCHAR(100) DEFAULT NULL AFTER instansi,
            ADD COLUMN fasilitas VARCHAR(150) DEFAULT 'Datang Langsung Ke PST BPS Kota Tegal' AFTER kategori_instansi,
            ADD COLUMN pemanfaatan VARCHAR(150) DEFAULT NULL AFTER layanan,
            ADD COLUMN data_diinginkan TEXT DEFAULT NULL AFTER pemanfaatan,
            ADD COLUMN foto LONGTEXT DEFAULT NULL AFTER data_diinginkan,
            ADD COLUMN monev ENUM('Ya', 'Tidak') DEFAULT 'Ya' AFTER foto,
            ADD COLUMN pendapat ENUM('Sangat Puas', 'Puas', 'Cukup Puas', 'Tidak Puas') DEFAULT NULL AFTER waktu,
            ADD COLUMN catatan TEXT DEFAULT NULL AFTER pendapat,
            ADD COLUMN tipe_pendaftaran ENUM('online', 'walkin') DEFAULT 'online' AFTER catatan");
    } else {
        // Pastikan ENUM kolom status memuat opsi 'Dilayani' dan 'Dibatalkan'
        $conn->query("ALTER TABLE antrian MODIFY COLUMN status ENUM('Menunggu', 'Dipanggil', 'Dilayani', 'Selesai', 'Terlewat', 'Dibatalkan') DEFAULT 'Menunggu'");
    }
    $checkResetToken = $conn->query("SHOW COLUMNS FROM users LIKE 'reset_token'");
    if ($checkResetToken && $checkResetToken->num_rows === 0) {
        $conn->query("ALTER TABLE users ADD COLUMN reset_token VARCHAR(64) DEFAULT NULL, ADD COLUMN reset_expires_at DATETIME DEFAULT NULL");
    }

    $checkCalledAt = $conn->query("SHOW COLUMNS FROM antrian LIKE 'called_at'");
    if ($checkCalledAt && $checkCalledAt->num_rows === 0) {
        $conn->query("ALTER TABLE antrian ADD COLUMN called_at DATETIME DEFAULT NULL AFTER status, ADD COLUMN panggil_count INT DEFAULT 0 AFTER called_at");
    }
} catch (Throwable $e) {
    error_log("Auto Migration Note: " . $e->getMessage());
}

// Fungsi pembantu respons JSON
function sendJsonResponse($status, $message, $data = null, $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}
?>
