<?php
// SPST BPS Kota Tegal - WebGIS Data Seeder
// Skrip ini hanya dapat dieksekusi via CLI untuk mencegah eksekusi tanpa izin dari browser publik

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Akses ditolak: Skrip seeding basis data WebGIS hanya dapat dijalankan melalui antarmuka baris perintah (CLI).\n");
}

require_once __DIR__ . '/koneksi.php';

echo "Memulai seeding database WebGIS Kota Tegal...\n";

// Ensure table exists
$sqlCreateTable = "CREATE TABLE IF NOT EXISTS `webgis_data` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kecamatan_code` VARCHAR(10) NOT NULL,
  `kecamatan_name` VARCHAR(100) NOT NULL,
  `main_category` VARCHAR(50) NOT NULL,
  `indicator_code` VARCHAR(50) NOT NULL,
  `indicator_name` VARCHAR(150) NOT NULL,
  `unit` VARCHAR(30) DEFAULT '',
  `year` INT NOT NULL,
  `value` DECIMAL(12,2) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `idx_kec_cat_ind_yr` (`kecamatan_code`, `main_category`, `indicator_code`, `year`),
  KEY `idx_lookup` (`main_category`, `indicator_code`, `year`, `kecamatan_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$conn->query($sqlCreateTable);

$kecamatanList = [
    '010' => ['name' => 'TEGAL SELATAN', 'luas' => 4.52, 'kelurahan' => 8, 'tinggi' => 4.0, 'jarak' => 3.5],
    '020' => ['name' => 'TEGAL TIMUR',   'luas' => 6.27, 'kelurahan' => 5, 'tinggi' => 3.0, 'jarak' => 1.8],
    '030' => ['name' => 'TEGAL BARAT',   'luas' => 15.13,'kelurahan' => 7, 'tinggi' => 2.0, 'jarak' => 2.1],
    '040' => ['name' => 'MARGADANA',     'luas' => 13.76,'kelurahan' => 7, 'tinggi' => 3.0, 'jarak' => 4.2],
];

// Base values for 2024
$dataBase = [
    '010' => [
        'penduduk_total' => 67850, 'penduduk_laki' => 34120, 'penduduk_perempuan' => 33730,
        'laju_pertumbuhan' => 0.85,
        'pasar' => 3, 'toko_modern' => 18, 'bank' => 6, 'umkm' => 1240,
        'air_minum_layak' => 96.5, 'sanitasi_layak' => 94.2, 'tps' => 8, 'bencana' => 2,
        'sd_mi' => 24, 'smp_mts' => 7, 'sma_smk' => 5, 'pt' => 2, 'apm' => 98.4,
        'tempat_ibadah' => 58, 'faskes' => 12, 'sarana_olahraga' => 14, 'bansos' => 4820
    ],
    '020' => [
        'penduduk_total' => 81420, 'penduduk_laki' => 40810, 'penduduk_perempuan' => 40610,
        'laju_pertumbuhan' => 0.72,
        'pasar' => 4, 'toko_modern' => 26, 'bank' => 15, 'umkm' => 1850,
        'air_minum_layak' => 98.1, 'sanitasi_layak' => 96.8, 'tps' => 10, 'bencana' => 1,
        'sd_mi' => 28, 'smp_mts' => 9, 'sma_smk' => 8, 'pt' => 3, 'apm' => 99.1,
        'tempat_ibadah' => 64, 'faskes' => 16, 'sarana_olahraga' => 18, 'bansos' => 5120
    ],
    '030' => [
        'penduduk_total' => 92110, 'penduduk_laki' => 46350, 'penduduk_perempuan' => 45760,
        'laju_pertumbuhan' => 0.91,
        'pasar' => 5, 'toko_modern' => 32, 'bank' => 18, 'umkm' => 2310,
        'air_minum_layak' => 95.8, 'sanitasi_layak' => 93.5, 'tps' => 14, 'bencana' => 4,
        'sd_mi' => 32, 'smp_mts' => 11, 'sma_smk' => 10, 'pt' => 4, 'apm' => 98.7,
        'tempat_ibadah' => 78, 'faskes' => 22, 'sarana_olahraga' => 22, 'bansos' => 6450
    ],
    '040' => [
        'penduduk_total' => 59620, 'penduduk_laki' => 29980, 'penduduk_perempuan' => 29640,
        'laju_pertumbuhan' => 1.05,
        'pasar' => 2, 'toko_modern' => 14, 'bank' => 4, 'umkm' => 980,
        'air_minum_layak' => 94.2, 'sanitasi_layak' => 91.8, 'tps' => 7, 'bencana' => 5,
        'sd_mi' => 21, 'smp_mts' => 5, 'sma_smk' => 4, 'pt' => 1, 'apm' => 97.5,
        'tempat_ibadah' => 46, 'faskes' => 9, 'sarana_olahraga' => 11, 'bansos' => 4180
    ],
];

$years = [2020, 2021, 2022, 2023, 2024, 2025, 2026];

$stmt = $conn->prepare("INSERT INTO webgis_data 
  (kecamatan_code, kecamatan_name, main_category, indicator_code, indicator_name, unit, year, value)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?)
  ON DUPLICATE KEY UPDATE value = VALUES(value), indicator_name = VALUES(indicator_name), unit = VALUES(unit)");

$totalRows = 0;

foreach ($years as $yr) {
    // Multiplier for growth trend based on year
    $yrFactor = 1.0 + (($yr - 2024) * 0.008); // ~0.8% growth/year
    
    // Calculate total area sum for percentage calculation
    $totalAreaKota = 39.68;
    
    // Calculate total population for year
    $totalPendudukKota = 0;
    foreach ($kecamatanList as $code => $info) {
        $totalPendudukKota += round($dataBase[$code]['penduduk_total'] * $yrFactor);
    }

    foreach ($kecamatanList as $code => $info) {
        $name = $info['name'];
        $base = $dataBase[$code];

        // Calculated values for year
        $pTotal = round($base['penduduk_total'] * $yrFactor);
        $pLaki  = round($base['penduduk_laki'] * $yrFactor);
        $pPerem = round($base['penduduk_perempuan'] * $yrFactor);
        $kepadatan = round($pTotal / $info['luas'], 2);
        $sexRatio = round(($pLaki / max(1, $pPerem)) * 100, 2);
        $persenLuas = round(($info['luas'] / $totalAreaKota) * 100, 2);
        $persenPenduduk = round(($pTotal / max(1, $totalPendudukKota)) * 100, 2);

        $records = [
            // 1. Geografi
            ['geografi', 'area_sqkm', 'Luas Wilayah', 'Km²', $info['luas']],
            ['geografi', 'percentage_total_area', 'Persentase Terhadap Luas Wilayah', '%', $persenLuas],
            ['geografi', 'altitude_mdpl', 'Tinggi Wilayah', 'Mdpl', $info['tinggi']],
            ['geografi', 'distance_to_capital_km', 'Jarak ke Pusat Kota', 'Km', $info['jarak']],
            ['geografi', 'jumlah_desa', 'Jumlah Kelurahan', 'Kelurahan', $info['kelurahan']],

            // 2. Penduduk (BPS Subjek 519)
            ['penduduk', 'penduduk_total', 'Jumlah Penduduk Total', 'Jiwa', $pTotal],
            ['penduduk', 'penduduk_laki', 'Jumlah Penduduk Laki-laki', 'Jiwa', $pLaki],
            ['penduduk', 'penduduk_perempuan', 'Jumlah Penduduk Perempuan', 'Jiwa', $pPerem],
            ['penduduk', 'kepadatan_penduduk', 'Kepadatan Penduduk', 'Jiwa/Km²', $kepadatan],
            ['penduduk', 'sex_ratio', 'Rasio Jenis Kelamin (Sex Ratio)', 'Laki/100 Per.', $sexRatio],
            ['penduduk', 'laju_pertumbuhan', 'Laju Pertumbuhan Penduduk', '%', $base['laju_pertumbuhan']],
            ['penduduk', 'persen_penduduk', 'Persentase Distribusi Penduduk', '%', $persenPenduduk],

            // 3. Ekonomi
            ['ekonomi', 'pasar_tradisional', 'Jumlah Pasar Tradisional', 'Unit', round($base['pasar'])],
            ['ekonomi', 'toko_modern', 'Jumlah Toko Modern / Perbelanjaan', 'Unit', round($base['toko_modern'] * (1 + ($yr - 2024)*0.03))],
            ['ekonomi', 'kantor_bank', 'Jumlah Kantor Bank & Keuangan', 'Unit', round($base['bank'])],
            ['ekonomi', 'jumlah_umkm', 'Jumlah UMKM Terdaftar', 'Unit', round($base['umkm'] * (1 + ($yr - 2024)*0.04))],

            // 4. Lingkungan
            ['lingkungan', 'air_minum_layak', 'Persentase Air Minum Layak', '%', min(100, round($base['air_minum_layak'] + ($yr - 2024)*0.4, 2))],
            ['lingkungan', 'sanitasi_layak', 'Persentase Sanitasi Layak', '%', min(100, round($base['sanitasi_layak'] + ($yr - 2024)*0.5, 2))],
            ['lingkungan', 'tps_sampah', 'Jumlah TPS / Tempat Sampah', 'Unit', round($base['tps'])],
            ['lingkungan', 'kejadian_bencana', 'Jumlah Kejadian Bencana Alam', 'Kejadian', max(0, round($base['bencana'] + rand(-1, 1)))],

            // 5. Pendidikan
            ['pendidikan', 'sd_mi', 'Jumlah Sekolah Dasar (SD/MI)', 'Sekolah', round($base['sd_mi'])],
            ['pendidikan', 'smp_mts', 'Jumlah SMP / MTs', 'Sekolah', round($base['smp_mts'])],
            ['pendidikan', 'sma_smk', 'Jumlah SMA / SMK / MA', 'Sekolah', round($base['sma_smk'])],
            ['pendidikan', 'perguruan_tinggi', 'Jumlah Perguruan Tinggi', 'Kampus', round($base['pt'])],
            ['pendidikan', 'apm_sekolah', 'Angka Partisipasi Murni (APM)', '%', min(100, round($base['apm'] + ($yr - 2024)*0.2, 2))],

            // 6. Sosial Budaya & Kesehatan
            ['sosial_budaya', 'tempat_ibadah', 'Jumlah Tempat Ibadah', 'Unit', round($base['tempat_ibadah'] + ($yr - 2024))],
            ['sosial_budaya', 'faskes', 'Jumlah Sarana Kesehatan', 'Unit', round($base['faskes'])],
            ['sosial_budaya', 'sarana_olahraga', 'Jumlah Sarana Olahraga', 'Unit', round($base['sarana_olahraga'])],
            ['sosial_budaya', 'penerima_bansos', 'Jumlah Penerima Bansos (PKH)', 'KPM', round($base['bansos'] * (1 - ($yr - 2024)*0.01))],
        ];

        foreach ($records as $r) {
            $catCode = $r[0];
            $indCode = $r[1];
            $indName = $r[2];
            $unit    = $r[3];
            $val     = $r[4];

            $stmt->bind_param("ssssssid", $code, $name, $catCode, $indCode, $indName, $unit, $yr, $val);
            $stmt->execute();
            $totalRows++;
        }
    }
}

echo "Seeding selesai! Total $totalRows baris data WebGIS berhasil disimpan ke MySQL db_spst.\n";
