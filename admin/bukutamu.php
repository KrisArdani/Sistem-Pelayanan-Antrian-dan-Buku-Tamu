<?php
// SPST BPS Kota Tegal - Admin Panel Buku Tamu
$allowed_roles = ['petugas', 'admin', 'kepala'];
require_once __DIR__ . '/../auth_check.php';
$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
  <title>Kelola Buku Tamu - SPST Admin BPS Kota Tegal</title>

  <!-- Tailwind CSS Play CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Bootstrap 5.3.3 CSS & Bundle -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Icons & Fonts -->
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
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
          <a href="bukutamu.php" class="bps-nav-item active"><span class="material-icons">groups</span> Kelola Buku Tamu</a>
          <a href="antrian.php" class="bps-nav-item"><span class="material-icons">summarize</span> Kelola Loket Antrian</a>
          <div class="pt-4 pb-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Akses Utama</div>
          <a href="../index.php" class="bps-nav-item"><span class="material-icons">open_in_new</span> Portal Publik</a>
        </nav>
      </div>

      <div class="p-4 bg-slate-800/80 rounded-xl border border-slate-700/50 text-xs text-slate-300 space-y-2">
        <button onclick="logoutUser()" class="btn btn-outline-danger btn-sm w-full flex items-center justify-center gap-1">
          <span class="material-icons text-sm">logout</span> Logout
        </button>
      </div>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 lg:ml-72 min-h-screen flex flex-col justify-between">
      
      <!-- Top Navbar Header -->
      <header class="bg-white/80 backdrop-blur-md border-b border-slate-200 sticky top-0 z-20 px-6 py-4 flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-3">
          <button class="lg:hidden p-2 rounded-lg bg-slate-100 text-slate-700" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
            <span class="material-icons">menu</span>
          </button>
          <span class="text-xs font-bold px-3 py-1 bg-sky-100 text-sky-700 rounded-full uppercase tracking-wider">Petugas PST - Buku Tamu & Presensi</span>
        </div>

        <div class="flex items-center gap-3">
          <button onclick="logoutUser()" class="btn btn-sm btn-outline-danger text-xs flex items-center gap-1">
            <span class="material-icons text-sm">logout</span> Logout
          </button>
        </div>
      </header>

      <!-- Main Container -->
      <div class="p-6 md:p-10 max-w-7xl mx-auto w-full space-y-6">
        
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <h1 class="text-2xl font-extrabold text-slate-900 brand-font">Kelola Buku Tamu & Data Pengunjung Terintegrasi</h1>
            <p class="text-slate-500 text-xs">Presensi seluruh pengunjung PST BPS Kota Tegal (Online & Walk-In Onsite).</p>
          </div>
          <button id="btn_export_csv" class="btn btn-success btn-sm flex items-center gap-1 font-bold px-4 py-2 rounded-xl shadow-sm">
            <span class="material-icons text-sm">download</span> Export CSV Lengkap
          </button>
        </div>

        <!-- Filter & Categorization Controls -->
        <div class="glass-card p-4 space-y-3">
          <div class="flex flex-col md:flex-row items-center gap-3">
            <!-- Search Bar -->
            <div class="relative flex-1 w-full">
              <span class="material-icons absolute left-3 top-2.5 text-slate-400">search</span>
              <input type="text" id="search_bukutamu" class="form-control pl-10 text-sm rounded-xl" placeholder="Cari nama, no hp, instansi, layanan, atau nomor tiket...">
            </div>

            <!-- Filter Kategori Instansi -->
            <div class="w-full md:w-48 shrink-0">
              <select id="filter_kategori_instansi" class="form-select text-xs font-semibold rounded-xl">
                <option value="">Semua Instansi</option>
                <option value="Sekolah/Universitas">Sekolah / Universitas</option>
                <option value="Instansi Pemerintah">Instansi Pemerintah</option>
                <option value="BUMN/BUMD">BUMN / BUMD</option>
                <option value="Swasta">Swasta / Lembaga Media</option>
                <option value="Perorangan/Lainnya">Perorangan / Lainnya</option>
              </select>
            </div>

            <!-- Filter Kategori Layanan -->
            <div class="w-full md:w-48 shrink-0">
              <select id="filter_kategori_layanan" class="form-select text-xs font-semibold rounded-xl">
                <option value="">Semua Layanan PST</option>
                <option value="Konsultasi Statistik">Konsultasi Statistik</option>
                <option value="Perpustakaan">Perpustakaan & Diseminasi</option>
                <option value="Rekomendasi">Rekomendasi Kegiatan Statistik</option>
                <option value="Pengaduan">Layanan Pengaduan</option>
              </select>
            </div>

            <!-- Filter Berdasarkan Waktu / Tanggal (Baru) -->
            <div class="w-full md:w-44 shrink-0">
              <select id="filter_waktu" class="form-select text-xs font-bold rounded-xl text-sky-900 border-sky-300 bg-sky-50">
                <option value="all">🕒 Semua Waktu</option>
                <option value="today">☀️ Hari Ini</option>
                <option value="this_month">🗓️ Bulan Ini</option>
                <option value="custom">📆 Rentang Tanggal...</option>
              </select>
            </div>
          </div>

          <!-- Custom Date Range Inputs (Tampil jika Rentang Tanggal dipilih) -->
          <div id="custom_date_range_box" class="hidden flex-wrap items-center gap-3 bg-sky-50/80 p-3 rounded-xl border border-sky-200">
            <div class="flex items-center gap-1.5 text-xs font-extrabold text-sky-900">
              <span class="material-icons text-sm text-sky-600">date_range</span>
              <span>Filter Rentang Tanggal:</span>
            </div>
            <div class="flex items-center gap-2">
              <label for="filter_tanggal_mulai" class="text-[11px] font-bold text-slate-600">Dari:</label>
              <input type="date" id="filter_tanggal_mulai" class="form-control form-control-sm text-xs rounded-lg w-36 border-sky-200">
            </div>
            <div class="flex items-center gap-2">
              <label for="filter_tanggal_selesai" class="text-[11px] font-bold text-slate-600">Sampai:</label>
              <input type="date" id="filter_tanggal_selesai" class="form-control form-control-sm text-xs rounded-lg w-36 border-sky-200">
            </div>
            <div class="flex items-center gap-2 ml-auto">
              <button type="button" id="btn_apply_date_filter" class="btn btn-primary btn-sm text-xs font-bold px-3 py-1 rounded-lg bg-sky-600 border-sky-600 shadow-sm flex items-center gap-1">
                <span class="material-icons text-xs">filter_alt</span> Terapkan
              </button>
              <button type="button" id="btn_reset_date_filter" class="btn btn-light btn-sm text-xs font-bold px-3 py-1 rounded-lg border flex items-center gap-1">
                <span class="material-icons text-xs">restart_alt</span> Reset
              </button>
            </div>
          </div>

          <!-- Quick Tabs Filter for Type & Status -->
          <div class="flex flex-wrap items-center justify-between gap-2 border-t pt-3 border-slate-100">
            <div class="flex items-center gap-1 overflow-x-auto pb-1" id="type-tabs">
              <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mr-2">Tipe:</span>
              <button type="button" class="btn btn-sm btn-primary bg-sky-600 border-sky-600 text-xs font-bold rounded-lg px-2.5 py-1 filter-type-btn active" data-type="all">Semua Tipe</button>
              <button type="button" class="btn btn-sm btn-light border text-xs font-bold rounded-lg px-2.5 py-1 filter-type-btn" data-type="online">Online</button>
              <button type="button" class="btn btn-sm btn-light border text-xs font-bold rounded-lg px-2.5 py-1 filter-type-btn" data-type="walkin">Walk-In</button>
            </div>

            <div class="flex items-center gap-1 overflow-x-auto pb-1" id="status-tabs">
              <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mr-2">Status:</span>
              <button type="button" class="btn btn-sm btn-primary bg-sky-600 border-sky-600 text-xs font-bold rounded-lg px-2.5 py-1 filter-status-btn active" data-status="all">Semua Status</button>
              <button type="button" class="btn btn-sm btn-light border text-xs font-bold rounded-lg px-2.5 py-1 filter-status-btn" data-status="Menunggu">Menunggu</button>
              <button type="button" class="btn btn-sm btn-light border text-xs font-bold rounded-lg px-2.5 py-1 filter-status-btn" data-status="Dilayani">Dilayani</button>
              <button type="button" class="btn btn-sm btn-light border text-xs font-bold rounded-lg px-2.5 py-1 filter-status-btn" data-status="Selesai">Selesai</button>
              <button type="button" class="btn btn-sm btn-light border text-xs font-bold rounded-lg px-2.5 py-1 filter-status-btn" data-status="Terlewat">Terlewat</button>
              <button type="button" class="btn btn-sm btn-light border text-xs font-bold rounded-lg px-2.5 py-1 filter-status-btn" data-status="Dibatalkan">Dibatalkan</button>
            </div>
          </div>
        </div>

        <!-- Table Card -->
        <div class="glass-card overflow-hidden rounded-2xl shadow-sm border border-slate-200">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-sm">
              <thead class="bg-slate-100 text-slate-700 text-xs uppercase tracking-wider">
                <tr>
                  <th class="py-3 px-3 text-center">No</th>
                  <th class="py-3 px-3">No. & Kode Tiket</th>
                  <th class="py-3 px-3">Nama Pengunjung</th>
                  <th class="py-3 px-3">Instansi & Kategori</th>
                  <th class="py-3 px-3">Layanan PST</th>
                  <th class="py-3 px-3">Tanggal & Waktu</th>
                  <th class="py-3 px-3">Tipe & Status</th>
                  <th class="py-3 px-3 text-center">Aksi</th>
                </tr>
              </thead>
              <tbody id="tbody_bukutamu">
                <!-- Javascript rendered rows -->
              </tbody>
            </table>
          </div>
        </div>

      </div>

      <!-- Footer -->
      <footer class="bg-slate-900 text-slate-400 py-4 px-6 text-center text-xs border-t border-slate-800">
        Panel Admin SPST BPS Kota Tegal © 2026
      </footer>

    </main>
  </div>

  <!-- Modal Detail Dokumen Pengunjung -->
  <div class="modal fade" id="modalVisitorDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content rounded-2xl border-0 shadow-2xl overflow-hidden">
        <div class="modal-header bg-slate-900 text-white p-4">
          <h5 class="modal-title font-bold text-base flex items-center gap-2">
            <span class="material-icons text-sky-400">account_box</span>
            <span>Detail Lengkap Pengunjung</span>
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-6 space-y-4 text-xs text-slate-800" id="detail_visitor_content">
          <!-- Rendered via JS -->
        </div>
        <div class="modal-footer bg-slate-100 p-3">
          <button type="button" class="btn btn-secondary text-xs rounded-xl" data-bs-dismiss="modal">Tutup</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Mobile Offcanvas Sidebar Admin -->
  <div class="offcanvas offcanvas-start bps-sidebar" tabindex="-1" id="mobileSidebar">
    <div class="offcanvas-header border-b border-slate-700/60 p-4">
      <div class="flex items-center gap-3">
        <img src="../img/Logo_BPS.png" alt="Logo BPS Kota Tegal" class="w-10 h-10 object-contain filter drop-shadow">
        <div>
          <h5 class="offcanvas-title text-white font-extrabold brand-font text-base leading-tight">PANEL SPST</h5>
          <p class="text-[10px] text-sky-400 font-semibold tracking-wider uppercase">BPS KOTA TEGAL</p>
        </div>
      </div>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-5 flex flex-col justify-between">
      <nav class="space-y-1">
        <a href="dashboard.php" class="bps-nav-item"><span class="material-icons">dashboard</span> Executive Dashboard</a>
        <a href="bukutamu.php" class="bps-nav-item active"><span class="material-icons">groups</span> Kelola Buku Tamu</a>
        <a href="antrian.php" class="bps-nav-item"><span class="material-icons">summarize</span> Kelola Loket Antrian</a>
        <div class="pt-4 pb-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Akses Utama</div>
        <a href="../index.php" class="bps-nav-item"><span class="material-icons">open_in_new</span> Portal Publik</a>
      </nav>

      <div class="p-4 bg-slate-800/80 rounded-xl border border-slate-700/50 text-xs text-slate-300 space-y-2 mt-6">
        <button onclick="logoutUser()" class="btn btn-outline-danger btn-sm w-full flex items-center justify-center gap-1">
          <span class="material-icons text-sm">logout</span> Logout
        </button>
      </div>
    </div>
  </div>

  <script src="../js/app.js?v=<?php echo time(); ?>"></script>
  <script src="../js/admin-bukutamu.js?v=<?php echo time(); ?>"></script>
</body>
</html>
