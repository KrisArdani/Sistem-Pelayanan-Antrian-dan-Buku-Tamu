<?php
// SPST BPS Kota Tegal - Unified API Endpoint WebGIS Data (Local Spatial + Live BPS Web API)
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../includes/bps_service.php';

// 1. Definisi Kategori Spasial Mikro Lokal (Per Kecamatan)
$localCategories = [
    'geografi' => [
        'name' => 'Data Geografi',
        'icon' => 'public',
        'color' => '#0284c7', // Sky Blue
        'source' => 'local_spatial',
        'badge' => 'Per Kecamatan',
        'indicators' => [
            'area_sqkm' => ['name' => 'Luas Wilayah', 'unit' => 'Km²'],
            'percentage_total_area' => ['name' => 'Persentase Luas Wilayah', 'unit' => '%'],
            'altitude_mdpl' => ['name' => 'Tinggi Wilayah', 'unit' => 'Mdpl'],
            'distance_to_capital_km' => ['name' => 'Jarak ke Pusat Kota', 'unit' => 'Km'],
            'jumlah_desa' => ['name' => 'Jumlah Kelurahan', 'unit' => 'Kelurahan'],
        ]
    ],
    'penduduk' => [
        'name' => 'Data Penduduk (BPS 519)',
        'icon' => 'groups',
        'color' => '#2563eb', // Blue
        'source' => 'local_spatial',
        'badge' => 'Per Kecamatan',
        'indicators' => [
            'penduduk_total' => ['name' => 'Jumlah Penduduk Total', 'unit' => 'Jiwa'],
            'penduduk_laki' => ['name' => 'Jumlah Penduduk Laki-laki', 'unit' => 'Jiwa'],
            'penduduk_perempuan' => ['name' => 'Jumlah Penduduk Perempuan', 'unit' => 'Jiwa'],
            'kepadatan_penduduk' => ['name' => 'Kepadatan Penduduk', 'unit' => 'Jiwa/Km²'],
            'sex_ratio' => ['name' => 'Rasio Jenis Kelamin (Sex Ratio)', 'unit' => 'Laki/100 Per.'],
            'laju_pertumbuhan' => ['name' => 'Laju Pertumbuhan Penduduk', 'unit' => '%'],
            'persen_penduduk' => ['name' => 'Persentase Distribusi Penduduk', 'unit' => '%'],
        ]
    ],
    'ekonomi' => [
        'name' => 'Sarana & Ekonomi Wilayah',
        'icon' => 'storefront',
        'color' => '#16a34a', // Green
        'source' => 'local_spatial',
        'badge' => 'Per Kecamatan',
        'indicators' => [
            'pasar_tradisional' => ['name' => 'Jumlah Pasar Tradisional', 'unit' => 'Unit'],
            'toko_modern' => ['name' => 'Jumlah Toko Modern / Perbelanjaan', 'unit' => 'Unit'],
            'kantor_bank' => ['name' => 'Jumlah Kantor Bank & Keuangan', 'unit' => 'Unit'],
            'jumlah_umkm' => ['name' => 'Jumlah UMKM Terdaftar', 'unit' => 'Unit'],
        ]
    ],
    'lingkungan' => [
        'name' => 'Lingkungan & Sanitasi',
        'icon' => 'nature_people',
        'color' => '#059669', // Emerald
        'source' => 'local_spatial',
        'badge' => 'Per Kecamatan',
        'indicators' => [
            'air_minum_layak' => ['name' => 'Persentase Air Minum Layak', 'unit' => '%'],
            'sanitasi_layak' => ['name' => 'Persentase Sanitasi Layak', 'unit' => '%'],
            'tps_sampah' => ['name' => 'Jumlah TPS / Tempat Sampah', 'unit' => 'Unit'],
            'kejadian_bencana' => ['name' => 'Jumlah Kejadian Bencana Alam', 'unit' => 'Kejadian'],
        ]
    ],
    'pendidikan' => [
        'name' => 'Sarana Pendidikan',
        'icon' => 'school',
        'color' => '#d97706', // Amber
        'source' => 'local_spatial',
        'badge' => 'Per Kecamatan',
        'indicators' => [
            'sd_mi' => ['name' => 'Jumlah Sekolah Dasar (SD/MI)', 'unit' => 'Sekolah'],
            'smp_mts' => ['name' => 'Jumlah SMP / MTs', 'unit' => 'Sekolah'],
            'sma_smk' => ['name' => 'Jumlah SMA / SMK / MA', 'unit' => 'Sekolah'],
            'perguruan_tinggi' => ['name' => 'Jumlah Perguruan Tinggi', 'unit' => 'Kampus'],
            'apm_sekolah' => ['name' => 'Angka Partisipasi Murni (APM)', 'unit' => '%'],
        ]
    ],
    'sosial_budaya' => [
        'name' => 'Sosial, Budaya & Faskes',
        'icon' => 'diversity_3',
        'color' => '#9333ea', // Purple
        'source' => 'local_spatial',
        'badge' => 'Per Kecamatan',
        'indicators' => [
            'tempat_ibadah' => ['name' => 'Jumlah Tempat Ibadah', 'unit' => 'Unit'],
            'faskes' => ['name' => 'Jumlah Sarana Kesehatan', 'unit' => 'Unit'],
            'sarana_olahraga' => ['name' => 'Jumlah Sarana Olahraga', 'unit' => 'Unit'],
            'penerima_bansos' => ['name' => 'Jumlah Penerima Bansos (PKH)', 'unit' => 'KPM'],
        ]
    ],
];

// 2. Inisialisasi BpsService dan Kategori BPS
$bpsService = new BpsService();
$bpsCategories = $bpsService->getCategories();

// Gabungkan semua kategori untuk dropdown sinkronisasi
$allCategoriesMeta = array_merge($localCategories, $bpsCategories);

// Format list categories untuk dikembalikan ke frontend
$categoriesResponse = [];
foreach ($allCategoriesMeta as $cKey => $cMeta) {
    $indItems = [];
    foreach ($cMeta['indicators'] as $iKey => $iMeta) {
        $indItems[] = [
            'code' => $iKey,
            'name' => $iMeta['name'],
            'unit' => $iMeta['unit'] ?? '',
            'desc' => $iMeta['desc'] ?? ''
        ];
    }
    $categoriesResponse[] = [
        'code' => $cKey,
        'name' => $cMeta['name'],
        'icon' => $cMeta['icon'],
        'color' => $cMeta['color'] ?? '#0284c7',
        'source' => $cMeta['source'] ?? 'local_spatial',
        'badge' => isset($cMeta['source']) && $cMeta['source'] === 'bps_api' ? 'BPS Live' : 'Per Kecamatan',
        'indicators' => $indItems
    ];
}

// 3. Baca Parameter Request
$catParam = isset($_GET['category']) && array_key_exists($_GET['category'], $allCategoriesMeta) ? $_GET['category'] : 'penduduk';
$catInfo  = $allCategoriesMeta[$catParam];
$indList  = array_keys($catInfo['indicators']);
$indParam = isset($_GET['indicator']) && array_key_exists($_GET['indicator'], $catInfo['indicators']) ? $_GET['indicator'] : $indList[0];
$areaParam = isset($_GET['area']) ? trim($_GET['area']) : '';
$yearParam = isset($_GET['year']) ? intval($_GET['year']) : 2024;
$startYr   = isset($_GET['start_year']) ? intval($_GET['start_year']) : 2020;
$endYr     = isset($_GET['end_year']) ? intval($_GET['end_year']) : 2024;

// Normalisasi rentang tahun
if ($startYr > $endYr) {
    $temp = $startYr;
    $startYr = $endYr;
    $endYr = $temp;
}

// =========================================================================
// 4. JIKA KATEGORI ADALAH DATA RESMI DARI BPS WEB API (LIVE BPS)
// =========================================================================
if (isset($catInfo['source']) && $catInfo['source'] === 'bps_api') {
    $bpsResponse = $bpsService->getWebGisData($catParam, $indParam, $yearParam, $startYr, $endYr);
    $bpsResponse['categories'] = $categoriesResponse;
    echo json_encode($bpsResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

// =========================================================================
// 5. JIKA KATEGORI ADALAH DATA SPASIAL MIKRO LOKAL (PER KECAMATAN DARI MYSQL)
// =========================================================================
$currentData = [];
$totalValSum = 0;

$stmt = $conn->prepare("SELECT kecamatan_code, kecamatan_name, value, unit, indicator_name 
  FROM webgis_data 
  WHERE main_category = ? AND indicator_code = ? AND year = ?
  ORDER BY kecamatan_code ASC");
$stmt->bind_param("ssi", $catParam, $indParam, $yearParam);
$stmt->execute();
$res = $stmt->get_result();

$unitStr = $localCategories[$catParam]['indicators'][$indParam]['unit'] ?? '';
$indNameStr = $localCategories[$catParam]['indicators'][$indParam]['name'] ?? '';

while ($row = $res->fetch_assoc()) {
    $val = floatval($row['value']);
    $totalValSum += $val;
    $currentData[$row['kecamatan_code']] = [
        'code' => $row['kecamatan_code'],
        'name' => $row['kecamatan_name'],
        'value' => $val,
        'unit' => $row['unit'] ?: $unitStr,
        'formatted' => number_format($val, ($val == intval($val) ? 0 : 2), ',', '.')
    ];
}
$stmt->close();

// Hitung persentase kontribusi per kecamatan
foreach ($currentData as $code => &$item) {
    $item['percentage'] = $totalValSum > 0 ? round(($item['value'] / $totalValSum) * 100, 2) : 0;
}
unset($item);

// Kueri Tren Multi-Tahun
$yearsRange = range($startYr, $endYr);
$sqlTrend = "SELECT year, kecamatan_code, kecamatan_name, value 
  FROM webgis_data 
  WHERE main_category = ? AND indicator_code = ? AND year BETWEEN ? AND ? ";
if (!empty($areaParam)) {
    $sqlTrend .= " AND kecamatan_code = ? ";
}
$sqlTrend .= " ORDER BY year ASC, kecamatan_code ASC";

$stmtTr = $conn->prepare($sqlTrend);
if (!empty($areaParam)) {
    $stmtTr->bind_param("ssiis", $catParam, $indParam, $startYr, $endYr, $areaParam);
} else {
    $stmtTr->bind_param("ssii", $catParam, $indParam, $startYr, $endYr);
}
$stmtTr->execute();
$resTr = $stmtTr->get_result();

$rawTrend = [];
while ($r = $resTr->fetch_assoc()) {
    $yr = intval($r['year']);
    $cCode = $r['kecamatan_code'];
    $rawTrend[$yr][$cCode] = floatval($r['value']);
}
$stmtTr->close();

// Susun datasets tren
$trendDatasets = [];
if (!empty($areaParam) && isset($currentData[$areaParam])) {
    $kecName = $currentData[$areaParam]['name'];
    $dataPoints = [];
    foreach ($yearsRange as $yr) {
        $dataPoints[] = isset($rawTrend[$yr][$areaParam]) ? $rawTrend[$yr][$areaParam] : 0;
    }
    $trendDatasets[] = [
        'label' => 'Kec. ' . $kecName,
        'data' => $dataPoints,
        'borderColor' => $catInfo['color'] ?? '#0284c7',
        'backgroundColor' => 'rgba(2, 132, 199, 0.1)'
    ];
} else {
    $totalPoints = [];
    foreach ($yearsRange as $yr) {
        $sumYr = 0;
        if (isset($rawTrend[$yr])) {
            foreach ($rawTrend[$yr] as $cVal) {
                $sumYr += $cVal;
            }
        }
        $totalPoints[] = round($sumYr, 2);
    }
    $trendDatasets[] = [
        'label' => 'Total Kota Tegal',
        'data' => $totalPoints,
        'borderColor' => $catInfo['color'] ?? '#0284c7',
        'backgroundColor' => 'rgba(2, 132, 199, 0.1)'
    ];
}

// Narasi Auto-Summary
$highestKec = null;
$lowestKec = null;
$maxVal = -1;
$minVal = PHP_INT_MAX;

foreach ($currentData as $item) {
    if ($item['value'] > $maxVal) {
        $maxVal = $item['value'];
        $highestKec = $item;
    }
    if ($item['value'] < $minVal) {
        $minVal = $item['value'];
        $lowestKec = $item;
    }
}

$firstYrTotal = isset($trendDatasets[0]['data'][0]) ? $trendDatasets[0]['data'][0] : 0;
$lastYrTotal  = isset($trendDatasets[0]['data'][count($trendDatasets[0]['data'])-1]) ? $trendDatasets[0]['data'][count($trendDatasets[0]['data'])-1] : 0;
$diffPercent  = $firstYrTotal > 0 ? round((($lastYrTotal - $firstYrTotal) / $firstYrTotal) * 100, 2) : 0;

$trendDirectionStr = "stabil";
if ($diffPercent > 0.5) {
    $trendDirectionStr = "mengalami tren kenaikan sebesar {$diffPercent}%";
} else if ($diffPercent < -0.5) {
    $absDiff = abs($diffPercent);
    $trendDirectionStr = "mengalami tren penurunan sebesar {$absDiff}%";
}

$activeAreaLabel = !empty($areaParam) && isset($currentData[$areaParam]) ? "Kecamatan " . $currentData[$areaParam]['name'] : "seluruh Kota Tegal";

$autoSummaryText = "Berdasarkan data statistik kompilasi BPS Kota Tegal tahun {$yearParam}, indikator <b>{$indNameStr}</b> pada {$activeAreaLabel} mencatatkan total sebesar <b>" . number_format($totalValSum, ($totalValSum == intval($totalValSum) ? 0 : 2), ',', '.') . " {$unitStr}</b>. ";

if ($highestKec && $lowestKec) {
    $autoSummaryText .= "Kecamatan dengan nilai tertinggi diraih oleh <b>Kecamatan {$highestKec['name']}</b> sebanyak <b>{$highestKec['formatted']} {$unitStr}</b> ({$highestKec['percentage']}% dari total kota), sedangkan nilai terendah berada di <b>Kecamatan {$lowestKec['name']}</b> sejumlah <b>{$lowestKec['formatted']} {$unitStr}</b> ({$lowestKec['percentage']}%). ";
}

$autoSummaryText .= "Dalam rentang tahun {$startYr} hingga {$endYr}, indikator ini {$trendDirectionStr} secara berkelanjutan.";

$response = [
    'status' => 'success',
    'data_source' => 'local_spatial',
    'active_category' => [
        'code' => $catParam,
        'name' => $catInfo['name'],
        'icon' => $catInfo['icon'],
        'color' => $catInfo['color'],
        'source' => 'local_spatial'
    ],
    'active_indicator' => [
        'code' => $indParam,
        'name' => $indNameStr,
        'unit' => $unitStr,
    ],
    'active_area' => $areaParam,
    'current_year' => $yearParam,
    'start_year' => $startYr,
    'end_year' => $endYr,
    'total_city_value' => $totalValSum,
    'total_city_formatted' => number_format($totalValSum, ($totalValSum == intval($totalValSum) ? 0 : 2), ',', '.'),
    'current_data' => $currentData,
    'trend_years' => $yearsRange,
    'trend_datasets' => $trendDatasets,
    'auto_summary' => $autoSummaryText,
    'categories' => $categoriesResponse
];

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
