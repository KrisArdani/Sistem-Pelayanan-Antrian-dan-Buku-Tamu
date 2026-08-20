<?php
// SPST BPS Kota Tegal - BPS Web API Client Library
require_once __DIR__ . '/../config.php';

class BpsClient
{
    private string $apiKey;
    private string $baseUrl;
    private string $defaultDomain;
    private string $cacheDir;
    private int $cacheTtl;

    public function __construct(
        ?string $apiKey = null,
        ?string $baseUrl = null,
        ?string $defaultDomain = null,
        ?string $cacheDir = null,
        ?int $cacheTtl = null
    ) {
        $this->apiKey = $apiKey ?? (defined('BPS_API_KEY') ? BPS_API_KEY : '');
        $this->baseUrl = rtrim($baseUrl ?? (defined('BPS_API_BASE') ? BPS_API_BASE : 'https://webapi.bps.go.id/v1/api'), '/');
        $this->defaultDomain = $defaultDomain ?? (defined('BPS_DOMAIN_KOTA_TEGAL') ? BPS_DOMAIN_KOTA_TEGAL : '3376');
        $this->cacheDir = $cacheDir ?? (defined('BPS_CACHE_DIR') ? BPS_CACHE_DIR : __DIR__ . '/../cache/bps');
        $this->cacheTtl = $cacheTtl ?? (defined('BPS_CACHE_TTL') ? BPS_CACHE_TTL : 86400);

        $this->initCacheDir();
    }

    /**
     * Inisialisasi direktori cache dan file proteksi .htaccess
     */
    private function initCacheDir(): void
    {
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
        $htaccess = $this->cacheDir . '/.htaccess';
        if (!file_exists($htaccess)) {
            @file_put_contents($htaccess, "Deny from all\n");
        }
    }

    /**
     * Mengambil path file cache berdasarkan key
     */
    private function getCacheFilePath(string $key): string
    {
        return $this->cacheDir . '/' . md5($key) . '.json';
    }

    /**
     * Membaca data dari cache jika masih berlaku
     */
    private function readCache(string $key, bool $allowStale = false): ?array
    {
        $file = $this->getCacheFilePath($key);
        if (!file_exists($file)) {
            return null;
        }

        $content = @file_get_contents($file);
        if (!$content) {
            return null;
        }

        $json = json_decode($content, true);
        if (!$json || !isset($json['expires_at'], $json['payload'])) {
            return null;
        }

        if (!$allowStale && time() > $json['expires_at']) {
            return null;
        }

        return $json['payload'];
    }

    /**
     * Menyimpan data ke dalam file cache
     */
    private function saveCache(string $key, array $payload, ?int $ttl = null): void
    {
        $file = $this->getCacheFilePath($key);
        $ttl = $ttl ?? $this->cacheTtl;
        $data = [
            'key' => $key,
            'cached_at' => time(),
            'cached_date' => date('Y-m-d H:i:s'),
            'expires_at' => time() + $ttl,
            'payload' => $payload
        ];
        @file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Menghapus semua file cache BPS
     */
    public function clearAllCache(): int
    {
        $count = 0;
        if (is_dir($this->cacheDir)) {
            $files = glob($this->cacheDir . '/*.json');
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                    $count++;
                }
            }
        }
        return $count;
    }

    /**
     * Mengambil ringkasan status cache
     */
    public function getCacheStats(): array
    {
        $stats = [
            'total_files' => 0,
            'total_size_bytes' => 0,
            'files' => []
        ];

        if (is_dir($this->cacheDir)) {
            $files = glob($this->cacheDir . '/*.json');
            $stats['total_files'] = count($files);
            foreach ($files as $f) {
                $sz = filesize($f);
                $stats['total_size_bytes'] += $sz;
                $mtime = filemtime($f);
                $content = @json_decode(@file_get_contents($f), true);
                $stats['files'][] = [
                    'filename' => basename($f),
                    'size_kb' => round($sz / 1024, 2),
                    'cached_date' => $content['cached_date'] ?? date('Y-m-d H:i:s', $mtime),
                    'key' => $content['key'] ?? 'unknown',
                    'is_expired' => isset($content['expires_at']) ? (time() > $content['expires_at']) : false
                ];
            }
        }

        return $stats;
    }

    /**
     * Request HTTP ke API BPS dengan cURL
     */
    public function request(string $endpointPath, bool $useCache = true, int $timeout = 15): array
    {
        $endpointPath = ltrim($endpointPath, '/');
        // Pastikan parameter key terpasang jika belum ada
        if (!str_contains($endpointPath, '/key/')) {
            $endpointPath = rtrim($endpointPath, '/') . '/key/' . $this->apiKey . '/';
        }

        $fullUrl = $this->baseUrl . '/' . $endpointPath;
        $cacheKey = $fullUrl;

        // 1. Cek cache jika diperbolehkan
        if ($useCache) {
            $cached = $this->readCache($cacheKey);
            if ($cached !== null) {
                $cached['_from_cache'] = true;
                return $cached;
            }
        }

        // 2. Lakukan request cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) SPST-BPS/1.0');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Cache-Control: no-cache'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // 3. Tangani kegagalan koneksi dengan fallback ke stale cache jika ada
        if ($response === false || $httpCode !== 200) {
            $stale = $this->readCache($cacheKey, true);
            if ($stale !== null) {
                $stale['_from_cache'] = true;
                $stale['_stale_warning'] = "Koneksi ke API BPS gagal ($curlError), menggunakan data cache tersimpan.";
                return $stale;
            }

            return [
                'status' => 'Error',
                'message' => 'Gagal menghubungi server Web API BPS: ' . ($curlError ?: "HTTP Status $httpCode"),
                'data' => null
            ];
        }

        $json = json_decode($response, true);
        if (!$json) {
            return [
                'status' => 'Error',
                'message' => 'Format respons dari API BPS bukan JSON yang valid',
                'data' => null
            ];
        }

        // 4. Simpan ke cache jika respons berstatus OK
        if (isset($json['status']) && $json['status'] === 'OK') {
            $this->saveCache($cacheKey, $json);
        }

        $json['_from_cache'] = false;
        return $json;
    }

    /**
     * Uji koneksi ke API BPS
     */
    public function testConnection(): array
    {
        $startTime = microtime(true);
        $res = $this->request("domain/type/prov", false, 15);
        $duration = round((microtime(true) - $startTime) * 1000, 2);

        $isOk = isset($res['status']) && $res['status'] === 'OK';
        return [
            'success' => $isOk,
            'latency_ms' => $duration,
            'response' => $res,
            'api_key_masked' => substr($this->apiKey, 0, 6) . '...' . substr($this->apiKey, -4)
        ];
    }

    /**
     * Mengambil daftar subjek statistik
     */
    public function fetchSubjects(?string $domain = null, ?int $subcatId = null, int $page = 1, bool $useCache = true): array
    {
        $domain = $domain ?? $this->defaultDomain;
        $path = "list/model/subject/domain/{$domain}/page/{$page}/lang/ind/";
        if ($subcatId !== null) {
            $path .= "subcat/{$subcatId}/";
        }
        return $this->request($path, $useCache);
    }

    /**
     * Mengambil daftar variabel untuk subjek tertentu
     */
    public function fetchVariables(int $subjectId, ?string $domain = null, int $page = 1, bool $useCache = true): array
    {
        $domain = $domain ?? $this->defaultDomain;
        $path = "list/model/var/domain/{$domain}/subject/{$subjectId}/page/{$page}/lang/ind/";
        return $this->request($path, $useCache);
    }

    /**
     * Mengambil daftar tahun data untuk variabel tertentu
     */
    public function fetchYears(int $varId, ?string $domain = null, bool $useCache = true): array
    {
        $domain = $domain ?? $this->defaultDomain;
        $path = "list/model/th/domain/{$domain}/var/{$varId}/";
        return $this->request($path, $useCache);
    }

    /**
     * Mengambil data statistik dinamis (Dynamic Data)
     * Parameter $thId bisa berupa ID tahun tunggal (misal 124 untuk 2024) atau rentang tahun "118:124"
     */
    public function fetchDynamicData(
        int $varId,
        string|int $thId,
        ?string $domain = null,
        ?int $turvar = null,
        ?int $vervar = null,
        bool $useCache = true
    ): array {
        $domain = $domain ?? $this->defaultDomain;
        $path = "list/model/data/domain/{$domain}/var/{$varId}/th/{$thId}/";
        if ($turvar !== null) {
            $path .= "turvar/{$turvar}/";
        }
        if ($vervar !== null) {
            $path .= "vervar/{$vervar}/";
        }
        return $this->request($path, $useCache);
    }

    /**
     * Mengambil daftar tabel statis
     */
    public function fetchStaticTables(?string $domain = null, string $keyword = '', int $page = 1, bool $useCache = true): array
    {
        $domain = $domain ?? $this->defaultDomain;
        $path = "list/model/statictable/domain/{$domain}/page/{$page}/lang/ind/";
        if (!empty($keyword)) {
            $path .= "keyword/" . urlencode($keyword) . "/";
        }
        return $this->request($path, $useCache);
    }

    /**
     * Mengambil daftar publikasi BPS (seperti Kota Tegal Dalam Angka / Kecamatan Dalam Angka)
     */
    public function fetchPublications(?string $domain = null, string $keyword = '', int $page = 1, bool $useCache = true): array
    {
        $domain = $domain ?? $this->defaultDomain;
        $path = "list/model/publication/domain/{$domain}/page/{$page}/lang/ind/";
        if (!empty($keyword)) {
            $path .= "keyword/" . urlencode($keyword) . "/";
        }
        return $this->request($path, $useCache);
    }
}
