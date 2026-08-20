<?php
// SPST BPS Kota Tegal - BPS Data Service for WebGIS
require_once __DIR__ . '/bps_client.php';

class BpsService
{
    private BpsClient $client;
    private array $mapping;

    public function __construct(?BpsClient $client = null)
    {
        $this->client = $client ?? new BpsClient();
        $this->mapping = require __DIR__ . '/bps_mapping.php';
    }

    /**
     * Konversi tahun Masehi (misal 2024) ke th_id BPS (misal 124)
     */
    public function yearToThId(int $year): int
    {
        return $year - 1900;
    }

    /**
     * Konversi th_id BPS (misal 124) ke tahun Masehi (misal 2024)
     */
    public function thIdToYear(int $thId): int
    {
        return $thId + 1900;
    }

    /**
     * Mengambil daftar mapping kategori & indikator BPS
     */
    public function getCategories(): array
    {
        return $this->mapping;
    }

    /**
     * Mengambil data lengkap indikator BPS untuk WebGIS
     */
    public function getWebGisData(
        string $catCode,
        string $indCode,
        int $year = 2024,
        int $startYear = 2020,
        int $endYear = 2024
    ): array {
        if (!isset($this->mapping[$catCode])) {
            return ['status' => 'error', 'message' => "Kategori BPS '$catCode' tidak ditemukan."];
        }

        $catMeta = $this->mapping[$catCode];
        if (!isset($catMeta['indicators'][$indCode])) {
            $indCode = array_key_first($catMeta['indicators']);
        }
        $indMeta = $catMeta['indicators'][$indCode];

        // Tentukan var_id, turvar_id, dan vervar
        $varId = $indMeta['bps_var_id'] ?? ($catMeta['bps_var_id'] ?? null);
        $turvarId = $indMeta['turvar'] ?? null;
        $vervarKota = $indMeta['vervar_kota'] ?? 3376;
        $vervarJateng = $indMeta['vervar_jateng'] ?? null;

        if (!$varId) {
            return ['status' => 'error', 'message' => "Variabel BPS untuk indikator '$indCode' tidak valid."];
        }

        // Batasi rentang tahun
        if ($startYear > $endYear) {
            [$startYear, $endYear] = [$endYear, $startYear];
        }
        $yearsRange = range($startYear, $endYear);

        // 1. Ambil data tahun aktif
        $thId = $this->yearToThId($year);
        $activeRaw = $this->client->fetchDynamicData($varId, $thId, '3376');

        $valKota = null;
        $valJateng = null;
        $unit = $indMeta['unit'] ?? ($activeRaw['var'][0]['unit'] ?? '');
        $lastUpdate = $activeRaw['last_update'] ?? date('Y-m-d');

        if (isset($activeRaw['datacontent']) && is_array($activeRaw['datacontent'])) {
            $valKota = $this->extractDataContentValue($activeRaw['datacontent'], $vervarKota, $varId, $turvarId, $thId);
            if ($vervarJateng !== null) {
                $valJateng = $this->extractDataContentValue($activeRaw['datacontent'], $vervarJateng, $varId, $turvarId, $thId);
            }
        }

        // 2. Ambil data tren tahunan (multi-year)
        $trendKota = [];
        $trendJateng = [];

        foreach ($yearsRange as $y) {
            $yThId = $this->yearToThId($y);
            if ($y === $year && isset($activeRaw['datacontent'])) {
                $yRaw = $activeRaw;
            } else {
                $yRaw = $this->client->fetchDynamicData($varId, $yThId, '3376');
            }

            $yValKota = 0;
            $yValJateng = 0;

            if (isset($yRaw['datacontent']) && is_array($yRaw['datacontent'])) {
                $extractedKota = $this->extractDataContentValue($yRaw['datacontent'], $vervarKota, $varId, $turvarId, $yThId);
                if ($extractedKota !== null) {
                    $yValKota = $extractedKota;
                }

                if ($vervarJateng !== null) {
                    $extractedJateng = $this->extractDataContentValue($yRaw['datacontent'], $vervarJateng, $varId, $turvarId, $yThId);
                    if ($extractedJateng !== null) {
                        $yValJateng = $extractedJateng;
                    }
                }
            }

            $trendKota[] = $yValKota;
            $trendJateng[] = $yValJateng;
        }

        $valKotaNum = $valKota !== null ? floatval($valKota) : (end($trendKota) ?: 0);
        $valJatengNum = $valJateng !== null ? floatval($valJateng) : 0;

        // Susun perbandingan
        $comparisonData = [
            '3376' => [
                'code' => '3376',
                'name' => 'Kota Tegal',
                'level' => 'Kota',
                'value' => $valKotaNum,
                'unit' => $unit,
                'formatted' => number_format($valKotaNum, ($valKotaNum == intval($valKotaNum) ? 0 : 2), ',', '.'),
                'is_primary' => true
            ]
        ];

        if ($vervarJateng !== null) {
            $comparisonData['3399'] = [
                'code' => '3399',
                'name' => 'Provinsi Jawa Tengah',
                'level' => 'Provinsi',
                'value' => $valJatengNum,
                'unit' => $unit,
                'formatted' => number_format($valJatengNum, ($valJatengNum == intval($valJatengNum) ? 0 : 2), ',', '.'),
                'is_primary' => false
            ];
        }

        // Format datasets untuk Chart.js
        $trendDatasets = [
            [
                'label' => 'Kota Tegal',
                'data' => $trendKota,
                'borderColor' => $catMeta['color'] ?? '#0284c7',
                'backgroundColor' => 'rgba(2, 132, 199, 0.1)'
            ]
        ];

        if ($vervarJateng !== null && array_sum($trendJateng) > 0) {
            $trendDatasets[] = [
                'label' => 'Jawa Tengah (Provinsi)',
                'data' => $trendJateng,
                'borderColor' => '#94a3b8',
                'backgroundColor' => 'rgba(148, 163, 184, 0.05)'
            ];
        }

        // Susun narasi auto summary
        $autoSummary = $this->buildAutoSummary(
            $indMeta['name'],
            $year,
            $valKotaNum,
            $valJatengNum,
            $unit,
            $startYear,
            $endYear,
            $trendKota,
            $indMeta['desc'] ?? '',
            $vervarJateng !== null
        );

        return [
            'status' => 'success',
            'data_source' => 'bps_api',
            'bps_last_update' => $lastUpdate,
            'active_category' => [
                'code' => $catCode,
                'name' => $catMeta['name'],
                'icon' => $catMeta['icon'],
                'color' => $catMeta['color'],
                'source' => 'bps_api'
            ],
            'active_indicator' => [
                'code' => $indCode,
                'name' => $indMeta['name'],
                'unit' => $unit,
                'desc' => $indMeta['desc'] ?? ''
            ],
            'current_year' => $year,
            'start_year' => $startYear,
            'end_year' => $endYear,
            'total_city_value' => $valKotaNum,
            'total_city_formatted' => number_format($valKotaNum, ($valKotaNum == intval($valKotaNum) ? 0 : 2), ',', '.'),
            'comparison_data' => $comparisonData,
            'trend_years' => $yearsRange,
            'trend_datasets' => $trendDatasets,
            'auto_summary' => $autoSummary,
            'is_from_cache' => $activeRaw['_from_cache'] ?? false
        ];
    }

    /**
     * Ekstraksi nilai dari datacontent BPS berdasarkan pola composite key
     */
    private function extractDataContentValue(array $datacontent, int $vervar, int $varId, ?int $turvar, int $thId): ?float
    {
        // 1. Coba pencocokan presisi: {vervar}{var}{turvar}{thId}0
        if ($turvar !== null) {
            $exactKey = "{$vervar}{$varId}{$turvar}{$thId}0";
            if (isset($datacontent[$exactKey])) {
                return floatval($datacontent[$exactKey]);
            }
        }

        // 2. Coba cari key yang diawali vervar dan varId serta mengandung thId
        foreach ($datacontent as $key => $val) {
            $keyStr = (string)$key;
            if (str_starts_with($keyStr, (string)$vervar)) {
                if ($turvar !== null && str_contains($keyStr, "{$varId}{$turvar}")) {
                    if (str_contains($keyStr, (string)$thId)) {
                        return floatval($val);
                    }
                } elseif ($turvar === null && str_contains($keyStr, (string)$varId)) {
                    if (str_contains($keyStr, (string)$thId)) {
                        return floatval($val);
                    }
                }
            }
        }

        // 3. Fallback: cari substring thId pada key
        foreach ($datacontent as $key => $val) {
            $keyStr = (string)$key;
            if (str_contains($keyStr, (string)$thId)) {
                if ($turvar !== null && str_contains($keyStr, (string)$turvar)) {
                    return floatval($val);
                }
                if ($turvar === null) {
                    return floatval($val);
                }
            }
        }

        return null;
    }

    /**
     * Membangun narasi deskriptif otomatis berbasis indikator BPS
     */
    private function buildAutoSummary(
        string $indName,
        int $year,
        float $valKota,
        float $valJateng,
        string $unit,
        int $startYear,
        int $endYear,
        array $trendKota,
        string $desc,
        bool $hasJatengComparison = true
    ): string {
        $valKotaFmt = number_format($valKota, ($valKota == intval($valKota) ? 0 : 2), ',', '.');
        $valJatengFmt = number_format($valJateng, ($valJateng == intval($valJateng) ? 0 : 2), ',', '.');

        $text = "Berdasarkan rilis resmi <b>Web API BPS</b> tahun {$year}, capaian indikator <b>{$indName}</b> di <b>Kota Tegal</b> tercatat sebesar <b>{$valKotaFmt} {$unit}</b>. ";

        if ($hasJatengComparison && $valJateng > 0) {
            $diff = round($valKota - $valJateng, 2);
            $absDiff = abs($diff);
            if ($diff > 0) {
                $text .= "Angka ini berada <b>{$absDiff} {$unit} di atas</b> rata-rata Provinsi Jawa Tengah ({$valJatengFmt} {$unit}). ";
            } elseif ($diff < 0) {
                $text .= "Angka ini berada <b>{$absDiff} {$unit} di bawah</b> rata-rata Provinsi Jawa Tengah ({$valJatengFmt} {$unit}). ";
            } else {
                $text .= "Angka ini setara dengan rata-rata Provinsi Jawa Tengah ({$valJatengFmt} {$unit}). ";
            }
        }

        // Hitung arah tren
        $validPoints = array_filter($trendKota, fn($v) => $v > 0);
        if (count($validPoints) >= 2) {
            $firstVal = reset($validPoints);
            $lastVal = end($validPoints);
            $growth = round((($lastVal - $firstVal) / $firstVal) * 100, 2);
            $absGrowth = abs($growth);
            if ($growth > 0.5) {
                $text .= "Dalam rentang tahun {$startYear}–{$endYear}, indikator ini mengalami <b>tren kenaikan sebesar {$growth}%</b>. ";
            } elseif ($growth < -0.5) {
                $text .= "Dalam rentang tahun {$startYear}–{$endYear}, indikator ini mencatatkan <b>tren penurunan sebesar {$absGrowth}%</b>. ";
            } else {
                $text .= "Dalam kurun waktu {$startYear}–{$endYear}, pergerakan angka terpantau relatif stabil. ";
            }
        }

        if (!empty($desc)) {
            $text .= "<br><span class='text-xs text-slate-500 mt-1 inline-block'><i class='fa-solid fa-info-circle mr-1'></i>Definisi: {$desc}</span>";
        }

        return $text;
    }
}
