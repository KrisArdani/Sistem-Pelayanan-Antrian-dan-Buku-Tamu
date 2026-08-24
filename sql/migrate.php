<?php
// SPST BPS Kota Tegal - CLI Database Migration Utility
// Gunakan: php sql/migrate.php

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Akses ditolak: Skrip migrasi basis data hanya dapat dijalankan melalui antarmuka baris perintah (CLI).\n");
}

require_once __DIR__ . '/../config.php';

$host   = DB_HOST;
$user   = DB_USER;
$pass   = DB_PASS;
$dbname = DB_NAME;

echo "========================================================\n";
echo " SPST BPS Kota Tegal - Database Migration\n";
echo " Target DB: {$dbname} @ {$host}\n";
echo "========================================================\n\n";

$conn = @new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    die("❌ Gagal terhubung ke MySQL Server: " . $conn->connect_error . "\n");
}

// 1. Buat database jika belum ada (hanya di lingkungan lokal/CLI yang memiliki hak akses)
echo "[1/3] Memeriksa database `{$dbname}`... ";
$conn->query("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
if (!$conn->select_db($dbname)) {
    die("\n❌ Gagal memilih database `{$dbname}`: " . $conn->error . "\n");
}
echo "OK\n";

$conn->set_charset("utf8mb4");

// 2. Eksekusi file skema SQL
$schemaFile = __DIR__ . '/schema.sql';
if (!file_exists($schemaFile)) {
    $schemaFile = __DIR__ . '/../db_spst.sql';
}

echo "[2/3] Memuat file skema: " . basename($schemaFile) . "... ";
if (!file_exists($schemaFile)) {
    die("\n❌ File skema tidak ditemukan.\n");
}

$sql = file_get_contents($schemaFile);
if ($conn->multi_query($sql)) {
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    echo "OK\n";
} else {
    echo "Peringatan saat multi-query: " . $conn->error . "\n";
}

// 3. Verifikasi tabel
echo "[3/3] Memverifikasi tabel di database `{$dbname}`...\n";
$tables = ['users', 'antrian', 'buku_tamu', 'login_attempts', 'security_log', 'skm_pengaduan', 'webgis_data'];
foreach ($tables as $t) {
    $res = $conn->query("SHOW TABLES LIKE '{$t}'");
    $exists = $res && $res->num_rows > 0;
    echo "  - Tabel `{$t}`: " . ($exists ? "✅ Siap" : "❌ Belum ada") . "\n";
}

echo "\n✨ Migrasi basis data selesai dengan sukses.\n";
