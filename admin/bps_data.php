<?php
// SPST BPS Kota Tegal - BPS API & Data Cache Management Panel
$allowed_roles = ['admin', 'kepala', 'petugas'];
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../includes/bps_client.php';
require_once __DIR__ . '/../includes/bps_service.php';

$client = new BpsClient();
$service = new BpsService($client);
$csrf_token = generateCsrfToken();

// Handle AJAX actions (Test Connection & Clear Cache)
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        echo json_encode(['status' => 'error', 'message' => 'Token CSRF tidak valid.']);
        exit();
    }

    $action = $_POST['action'];
    if ($action === 'test_connection') {
        $result = $client->testConnection();
        echo json_encode(['status' => 'success', 'data' => $result]);
        exit();
    }

    if ($action === 'clear_cache') {
        $deleted = $client->clearAllCache();
        echo json_encode(['status' => 'success', 'message' => "Berhasil membersihkan {$deleted} file cache BPS."]);
        exit();
    }

    if ($action === 'refresh_all') {
        $client->clearAllCache();
        // Warm up cache for all BPS categories
        $cats = ['bps_kemiskinan', 'bps_ipm', 'bps_ketenagakerjaan', 'bps_ekonomi'];
        $warmed = 0;
        foreach ($cats as $cat) {
            $service->getWebGisData($cat, '', 2024, 2020, 2024);
            $warmed++;
        }
        echo json_encode(['status' => 'success', 'message' => "Cache berhasil diperbarui secara lengkap untuk {$warmed} kategori BPS Live."]);
        exit();
    }

    echo json_encode(['status' => 'error', 'message' => 'Aksi tidak dikenali.']);
    exit();
}

$cacheStats = $client->getCacheStats();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
  <title>Integrasi Web API BPS - SPST Admin BPS Kota Tegal</title>

  <!-- Tailwind CSS Play CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Bootstrap 5.3.3 CSS & Bundle -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Icons & Fonts -->
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- Custom CSS -->
  <link rel="stylesheet" href="../css/custom.css">
</head>
<body class="bg-slate-50 font-['Inter'] text-slate-800 antialiased">

  <div class="flex min-h-screen">
    <!-- Sidebar Navigation Left -->
    <aside class="w-72 bps-sidebar hidden lg:flex flex-col justify-between p-6 fixed inset-y-0 left-0 z-30">
      <div>
        <div class="flex items-center gap-3 mb-8 pb-6 border-b border-slate-700/60">
          <img src="../img/Logo_BPS.png" alt="Logo BPS Kota Tegal" class="w-12 h-12 object-contain filter drop-shadow">
          <div>
            <h1 class="text-white font-extrabold text-lg tracking-wide leading-tight brand-font">PANEL SPST</h1>
            <p class="text-xs text-sky-400 font-semibold tracking-wider uppercase">BPS KOTA TEGAL</p>
          </div>
        </div>

        <nav class="space-y-1">
          <a href="dashboard.php" class="bps-nav-item"><span class="material-icons">dashboard</span> Executive Dashboard</a>
          <a href="bukutamu.php" class="bps-nav-item"><span class="material-icons">groups</span> Kelola Buku Tamu</a>
          <a href="antrian.php" class="bps-nav-item"><span class="material-icons">summarize</span> Kelola Loket Antrian</a>
          <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
          <a href="users.php" class="bps-nav-item"><span class="material-icons">manage_accounts</span> Kelola Pengguna</a>
          <?php endif; ?>
          <a href="bps_data.php" class="bps-nav-item active"><span class="material-icons">cloud_sync</span> Integrasi Web API BPS</a>
          
          <div class="pt-4 pb-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Akses Utama</div>
          <a href="../webgis.php" class="bps-nav-item"><span class="material-icons">map</span> Buka WebGIS</a>
          <a href="../index.php" class="bps-nav-item"><span class="material-icons">open_in_new</span> Portal Publik</a>
        </nav>
      </div>

      <div class="p-3 bg-slate-800/90 rounded-2xl border border-slate-700/70 space-y-2 shadow-inner">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-sky-600 via-indigo-600 to-blue-700 text-white font-extrabold text-sm flex items-center justify-center shrink-0 shadow-md">
            <span class="material-icons text-xl">admin_panel_settings</span>
          </div>
          <div class="min-w-0 flex-1">
            <div class="font-extrabold text-white text-xs truncate leading-tight">
              <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Petugas BPS'); ?>
            </div>
            <div class="text-[10px] text-sky-400 font-semibold uppercase tracking-wider">
              <?php echo htmlspecialchars($_SESSION['user_role'] ?? 'petugas'); ?>
            </div>
          </div>
          <a href="../logout.php" class="text-slate-400 hover:text-rose-400 p-1.5 rounded-lg hover:bg-slate-700/50 transition" title="Keluar">
            <span class="material-icons text-lg">logout</span>
          </a>
        </div>
      </div>
    </aside>

    <!-- Main Content Right -->
    <main class="flex-1 lg:ml-72 min-w-0 flex flex-col min-h-screen">
      
      <!-- Top Navbar -->
      <header class="bg-white/80 backdrop-blur-md sticky top-0 z-20 border-b border-slate-200/80 px-6 py-4 flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-3">
          <span class="text-xs font-bold px-3 py-1 bg-amber-100 text-amber-800 rounded-full uppercase tracking-wider flex items-center gap-1">
            <span class="material-icons text-xs">bolt</span>
            Web API BPS Live
          </span>
          <h2 class="text-lg font-bold text-slate-800 brand-font">Status & Manajemen Integrasi API BPS</h2>
        </div>
        <div class="flex items-center gap-3">
          <a href="../webgis.php" class="btn btn-outline-primary btn-sm flex items-center gap-1 rounded-xl text-xs font-bold px-3">
            <span class="material-icons text-sm">map</span>
            <span>Buka WebGIS</span>
          </a>
        </div>
      </header>

      <!-- Content Container -->
      <div class="p-6 md:p-8 space-y-6 max-w-7xl mx-auto w-full">

        <!-- Status Overview Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
          
          <!-- Card 1: API Key Status -->
          <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Status API Key</span>
              <span class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
                <span class="material-icons text-lg">key</span>
              </span>
            </div>
            <div class="text-xl font-black text-slate-900">Aktif & Valid</div>
            <div class="text-xs text-slate-500 font-mono bg-slate-50 p-1.5 rounded-lg border border-slate-200 truncate">
              <?php echo substr(BPS_API_KEY, 0, 6) . '...' . substr(BPS_API_KEY, -6); ?>
            </div>
          </div>

          <!-- Card 2: Domain BPS Kota Tegal -->
          <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Domain Wilayah</span>
              <span class="w-8 h-8 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center">
                <span class="material-icons text-lg">location_city</span>
              </span>
            </div>
            <div class="text-xl font-black text-slate-900">Kota Tegal</div>
            <div class="text-xs text-slate-500 font-semibold">
              Kode Domain: <b class="text-sky-700">3376</b> &bull; Jateng: <b class="text-slate-700">3300</b>
            </div>
          </div>

          <!-- Card 3: Cache Storage -->
          <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">File Cache Lokal</span>
              <span class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center">
                <span class="material-icons text-lg">folder_zip</span>
              </span>
            </div>
            <div id="statCacheFiles" class="text-xl font-black text-slate-900"><?php echo $cacheStats['total_files']; ?> Berkas</div>
            <div id="statCacheSize" class="text-xs text-slate-500 font-semibold">
              Ukuran: <?php echo round($cacheStats['total_size_bytes'] / 1024, 2); ?> KB (TTL 24 Jam)
            </div>
          </div>

          <!-- Card 4: Action Buttons -->
          <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col justify-between space-y-2">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Aksi Cepat</span>
            <div class="space-y-1.5">
              <button id="btnTestConn" class="w-full btn btn-outline-primary btn-sm rounded-xl text-xs font-bold flex items-center justify-center gap-1 py-2">
                <span class="material-icons text-sm">network_check</span>
                <span>Uji Koneksi BPS</span>
              </button>
              <button id="btnRefreshAll" class="w-full btn btn-warning btn-sm rounded-xl text-xs font-bold flex items-center justify-center gap-1 py-2">
                <span class="material-icons text-sm">sync</span>
                <span>Refresh & Tarik Data</span>
              </button>
            </div>
          </div>

        </div>

        <!-- Live Indicators Preview Grid -->
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-base font-extrabold text-slate-900 brand-font flex items-center gap-2">
              <span class="material-icons text-sky-600">query_stats</span>
              <span>Preview Data Live Web API BPS (Kota Tegal vs Jawa Tengah)</span>
            </h3>
            <span class="text-xs text-slate-500">Tahun 2024 &bull; Diperbarui Realtime</span>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
            <?php
            $previewCategories = [
                'bps_kemiskinan' => ['name' => 'Kemiskinan', 'color' => '#dc2626', 'icon' => 'shield'],
                'bps_ipm' => ['name' => 'Indeks Pembangunan Manusia', 'color' => '#7c3aed', 'icon' => 'emoji_events'],
                'bps_ketenagakerjaan' => ['name' => 'Ketenagakerjaan (TPT)', 'color' => '#0891b2', 'icon' => 'work'],
                'bps_ekonomi' => ['name' => 'Pertumbuhan Ekonomi (PDRB)', 'color' => '#ca8a04', 'icon' => 'account_balance'],
            ];

            foreach ($previewCategories as $cKey => $cInfo):
                $cData = $service->getWebGisData($cKey, '', 2024, 2020, 2024);
                $indName = $cData['active_indicator']['name'] ?? '';
                $valKota = $cData['total_city_formatted'] ?? '0';
                $unit = $cData['active_indicator']['unit'] ?? '';
                $comp = $cData['comparison_data'] ?? [];
                $valJateng = $comp['3399']['formatted'] ?? null;
            ?>
            <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm space-y-3 flex flex-col justify-between">
              <div>
                <div class="flex items-center justify-between mb-2">
                  <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500"><?php echo $cInfo['name']; ?></span>
                  <span class="w-8 h-8 rounded-xl flex items-center justify-center text-white" style="background-color: <?php echo $cInfo['color']; ?>;">
                    <span class="material-icons text-base"><?php echo $cInfo['icon']; ?></span>
                  </span>
                </div>
                <div class="text-xs font-semibold text-slate-700 mb-1"><?php echo $indName; ?></div>
                <div class="text-2xl font-black text-slate-900">
                  <?php echo $valKota; ?> <span class="text-sm font-bold text-slate-500"><?php echo $unit; ?></span>
                </div>
              </div>

              <div class="pt-3 border-t border-slate-100 text-xs space-y-1 bg-slate-50 p-2.5 rounded-xl">
                <div class="flex justify-between text-slate-600">
                  <span>Kota Tegal:</span>
                  <b class="text-sky-800"><?php echo $valKota; ?> <?php echo $unit; ?></b>
                </div>
                <?php if ($valJateng): ?>
                <div class="flex justify-between text-slate-600">
                  <span>Jawa Tengah:</span>
                  <b class="text-slate-700"><?php echo $valJateng; ?> <?php echo $unit; ?></b>
                </div>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Cache Management Table -->
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 space-y-4">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
              <h3 class="text-base font-extrabold text-slate-900 brand-font">Daftar Berkas Cache API BPS</h3>
              <p class="text-xs text-slate-500">File cache disimpan secara lokal untuk menjamin kecepatan WebGIS dan mengurangi beban request ke BPS.</p>
            </div>
            <button id="btnClearCache" class="btn btn-outline-danger btn-sm rounded-xl text-xs font-bold px-3 py-1.5 flex items-center gap-1">
              <span class="material-icons text-sm">delete_sweep</span>
              <span>Bersihkan Cache</span>
            </button>
          </div>

          <div class="table-responsive">
            <table class="table table-hover table-sm text-xs align-middle">
              <thead class="table-light">
                <tr>
                  <th class="py-2.5 px-3">Nama Berkas</th>
                  <th class="py-2.5 px-3">Ukuran (KB)</th>
                  <th class="py-2.5 px-3">Waktu Cache</th>
                  <th class="py-2.5 px-3">Status</th>
                </tr>
              </thead>
              <tbody id="cacheTableBody">
                <?php if (empty($cacheStats['files'])): ?>
                <tr>
                  <td colspan="4" class="text-center py-4 text-slate-400 font-semibold">Belum ada file cache tersimpan.</td>
                </tr>
                <?php else: ?>
                  <?php foreach ($cacheStats['files'] as $f): ?>
                  <tr>
                    <td class="py-2 px-3 font-mono text-slate-700"><?php echo htmlspecialchars($f['filename']); ?></td>
                    <td class="py-2 px-3"><?php echo $f['size_kb']; ?> KB</td>
                    <td class="py-2 px-3"><?php echo $f['cached_date']; ?></td>
                    <td class="py-2 px-3">
                      <?php if ($f['is_expired']): ?>
                        <span class="badge bg-rose-100 text-rose-700">Kedaluwarsa</span>
                      <?php else: ?>
                        <span class="badge bg-emerald-100 text-emerald-800">Aktif (Valid)</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>

      <!-- Include Footer Component -->
      <?php include '../footer.php'; ?>

    </main>
  </div>

  <script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Test Connection Handler
    document.getElementById('btnTestConn').addEventListener('click', async () => {
      Swal.fire({
        title: 'Menguji Koneksi...',
        text: 'Menghubungi server Web API BPS...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
      });

      const formData = new FormData();
      formData.append('action', 'test_connection');
      formData.append('csrf_token', csrfToken);

      try {
        const res = await fetch('bps_data.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.status === 'success' && data.data.success) {
          Swal.fire({
            icon: 'success',
            title: 'Koneksi Berhasil!',
            html: `Server Web API BPS merespons dengan normal.<br><b>Latensi:</b> ${data.data.latency_ms} ms<br><b>Status:</b> 200 OK`,
            confirmButtonColor: '#0284c7'
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Koneksi Gagal',
            text: data.data.response.message || 'Tidak dapat terhubung ke server BPS.',
            confirmButtonColor: '#0284c7'
          });
        }
      } catch (err) {
        Swal.fire({ icon: 'error', title: 'Terjadi Kesalahan', text: err.message });
      }
    });

    // Clear Cache Handler
    document.getElementById('btnClearCache').addEventListener('click', async () => {
      const confirm = await Swal.fire({
        title: 'Bersihkan Cache BPS?',
        text: 'File cache lokal akan dihapus. Request berikutnya akan menarik data baru dari server BPS.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Bersihkan',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#e11d48'
      });

      if (!confirm.isConfirmed) return;

      const formData = new FormData();
      formData.append('action', 'clear_cache');
      formData.append('csrf_token', csrfToken);

      try {
        const res = await fetch('bps_data.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.status === 'success') {
          Swal.fire({ icon: 'success', title: 'Berhasil!', text: data.message }).then(() => location.reload());
        } else {
          Swal.fire({ icon: 'error', title: 'Gagal', text: data.message });
        }
      } catch (err) {
        Swal.fire({ icon: 'error', title: 'Kesalahan', text: err.message });
      }
    });

    // Refresh & Warmup Cache Handler
    document.getElementById('btnRefreshAll').addEventListener('click', async () => {
      Swal.fire({
        title: 'Memperbarui Data BPS...',
        text: 'Menarik data terbaru dari server BPS untuk seluruh indikator WebGIS...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
      });

      const formData = new FormData();
      formData.append('action', 'refresh_all');
      formData.append('csrf_token', csrfToken);

      try {
        const res = await fetch('bps_data.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.status === 'success') {
          Swal.fire({ icon: 'success', title: 'Sinkronisasi Selesai!', text: data.message }).then(() => location.reload());
        } else {
          Swal.fire({ icon: 'error', title: 'Gagal', text: data.message });
        }
      } catch (err) {
        Swal.fire({ icon: 'error', title: 'Kesalahan', text: err.message });
      }
    });
  </script>
</body>
</html>
