<?php
// SPST BPS Kota Tegal - Executive Dashboard
$allowed_roles = ['admin', 'kepala', 'petugas'];
require_once __DIR__ . '/../auth_check.php';
$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
  <title>Executive Dashboard - SPST Admin BPS Kota Tegal</title>

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
  <!-- Chart.js CDN -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
          <a href="dashboard.php" class="bps-nav-item active"><span class="material-icons">dashboard</span> Executive Dashboard</a>
          <a href="bukutamu.php" class="bps-nav-item"><span class="material-icons">groups</span> Kelola Buku Tamu</a>
          <a href="antrian.php" class="bps-nav-item flex items-center justify-between">
            <div class="flex items-center gap-3">
              <span class="material-icons">summarize</span>
              <span>Kelola Loket Antrian</span>
            </div>
            <span id="admin_sidebar_waiting_badge" class="hidden px-2 py-0.5 bg-amber-500 text-slate-950 font-extrabold text-[10px] rounded-full shadow-sm animate-pulse" title="Antrean Menunggu Hari Ini">0</span>
          </a>
          <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
          <a href="users.php" class="bps-nav-item"><span class="material-icons">manage_accounts</span> Kelola Pengguna</a>
          <?php endif; ?>
          <div class="pt-4 pb-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Akses Utama</div>
          <a href="../index.php" class="bps-nav-item"><span class="material-icons">open_in_new</span> Portal Publik</a>
        </nav>
      </div>

      <div class="p-3 bg-slate-800/90 rounded-2xl border border-slate-700/70 space-y-2 shadow-inner">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-sky-600 via-indigo-600 to-blue-700 text-white font-extrabold text-sm flex items-center justify-center shrink-0 shadow-md">
            <span class="material-icons text-xl">admin_panel_settings</span>
          </div>
          <div class="min-w-0 flex-1">
            <div id="user_display_name" class="font-extrabold text-white text-xs truncate leading-tight" title="<?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Executive BPS'); ?>">
              <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Executive BPS'); ?>
            </div>
            <div id="user_display_role" class="text-[10px] font-bold text-sky-400 uppercase tracking-wider mt-0.5 flex items-center gap-1">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
              <span>Aktor: <?php echo htmlspecialchars($_SESSION['user_role'] ?? 'Executive'); ?></span>
            </div>
          </div>
        </div>
        <button onclick="logoutUser()" type="button" class="w-full py-2 px-3 bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 hover:text-rose-300 border border-rose-500/30 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-sm mt-1">
          <span class="material-icons text-sm">logout</span>
          <span>Keluar / Logout Sesi</span>
        </button>
      </div>
    </aside>

    <!-- Mobile Navigation Offcanvas Drawer (Admin Panel) -->
    <div class="offcanvas offcanvas-start bps-sidebar text-slate-100 w-80 lg:hidden" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
      <div class="offcanvas-header p-6 border-b border-slate-700/60">
        <div class="flex items-center gap-3">
          <img src="../img/Logo_BPS.png" alt="Logo BPS Kota Tegal" class="w-10 h-10 object-contain filter drop-shadow">
          <div>
            <h5 class="offcanvas-title font-extrabold text-base tracking-wide leading-tight brand-font text-white" id="mobileSidebarLabel">PANEL SPST</h5>
            <p class="text-[10px] text-sky-400 font-semibold tracking-wider uppercase">BPS KOTA TEGAL</p>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body p-6 flex flex-col justify-between overflow-y-auto">
        <div>
          <nav class="space-y-1">
            <a href="dashboard.php" class="bps-nav-item active"><span class="material-icons">dashboard</span> Executive Dashboard</a>
            <a href="bukutamu.php" class="bps-nav-item"><span class="material-icons">groups</span> Kelola Buku Tamu</a>
            <a href="antrian.php" class="bps-nav-item flex items-center justify-between">
              <div class="flex items-center gap-3">
                <span class="material-icons">summarize</span>
                <span>Kelola Loket Antrian</span>
              </div>
              <span id="admin_mobile_waiting_badge" class="hidden px-2 py-0.5 bg-amber-500 text-slate-950 font-extrabold text-[10px] rounded-full shadow-sm animate-pulse">0</span>
            </a>
            <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
            <a href="users.php" class="bps-nav-item"><span class="material-icons">manage_accounts</span> Kelola Pengguna</a>
            <?php endif; ?>
            <div class="pt-4 pb-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Akses Utama</div>
            <a href="../index.php" class="bps-nav-item"><span class="material-icons">open_in_new</span> Portal Publik</a>
          </nav>
        </div>

        <div class="p-3 bg-slate-800/90 rounded-2xl border border-slate-700/70 space-y-2 shadow-inner mt-6">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-sky-600 via-indigo-600 to-blue-700 text-white font-extrabold text-sm flex items-center justify-center shrink-0 shadow-md">
              <span class="material-icons text-xl">admin_panel_settings</span>
            </div>
            <div class="min-w-0 flex-1">
              <div class="font-extrabold text-white text-xs truncate leading-tight" title="<?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Executive BPS'); ?>">
                <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Executive BPS'); ?>
              </div>
              <div class="text-[10px] font-bold text-sky-400 uppercase tracking-wider mt-0.5 flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>Aktor: <?php echo htmlspecialchars($_SESSION['user_role'] ?? 'Executive'); ?></span>
              </div>
            </div>
          </div>
          <button onclick="logoutUser()" type="button" class="w-full py-2 px-3 bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 hover:text-rose-300 border border-rose-500/30 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-sm mt-1">
            <span class="material-icons text-sm">logout</span>
            <span>Keluar / Logout Sesi</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Main Content Area -->
    <main class="flex-1 lg:ml-72 min-h-screen flex flex-col justify-between">
      
      <!-- Top Navbar Header -->
      <header class="bg-white/80 backdrop-blur-md border-b border-slate-200 sticky top-0 z-20 px-6 py-4 flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-3">
          <button class="lg:hidden p-2 rounded-lg bg-slate-100 text-slate-700" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
            <span class="material-icons">menu</span>
          </button>
          <span class="text-xs font-bold px-3 py-1 bg-sky-100 text-sky-700 rounded-full uppercase tracking-wider">Executive Dashboard Monitoring</span>
        </div>

        <div class="flex items-center gap-3">
          <button onclick="logoutUser()" class="btn btn-sm btn-outline-danger text-xs flex items-center gap-1">
            <span class="material-icons text-sm">logout</span> Logout
          </button>
        </div>
      </header>

      <!-- Main Container -->
      <div class="p-6 md:p-10 max-w-7xl mx-auto w-full space-y-10">
        
        <!-- Page Title & Header -->
        <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 pb-4 border-b border-slate-200">
          <div>
            <h1 class="text-3xl md:text-4xl font-black text-slate-900 brand-font tracking-tight">Executive Dashboard Monitoring PST</h1>
            <p class="text-slate-500 text-sm font-medium mt-1">Laporan komprehensif & analisis performa pelayanan publik BPS Kota Tegal</p>
          </div>
          <div class="flex items-center gap-2.5 shrink-0 flex-wrap sm:flex-nowrap">
            <span class="px-3.5 py-2 bg-sky-100/80 text-sky-800 text-xs font-bold rounded-xl border border-sky-200 shadow-sm flex items-center gap-1.5 shrink-0">
              <span class="w-2 h-2 rounded-full bg-sky-500 animate-ping"></span> Real-time Live Sync
            </span>
            <span id="dashboard_live_clock" class="px-3.5 py-2 bg-slate-100 text-slate-700 text-xs font-bold rounded-xl border border-slate-200 shadow-sm flex items-center gap-1.5 shrink-0">
              <span class="material-icons text-sm text-slate-500">schedule</span> <span id="live_clock_text"><?php echo date('d M Y, H:i'); ?> WIB</span>
            </span>

            <!-- Select Filter Periode Waktu SKM -->
            <div class="flex items-center gap-1 bg-white px-3 py-2 rounded-xl border border-slate-200 shadow-sm shrink-0">
              <span class="material-icons text-slate-400 text-sm">filter_alt</span>
              <select id="filter_tanggal_skm" class="bg-transparent border-none text-xs font-extrabold text-slate-700 focus:ring-0 cursor-pointer pr-3">
                <option value="all" selected>Periode: Semua Periode</option>
                <option value="today">Periode: Hari Ini (<?php echo date('d/m/Y'); ?>)</option>
                <option value="this_week">Periode: Minggu Ini</option>
                <option value="this_month">Periode: Bulan Ini (<?php echo date('F Y'); ?>)</option>
              </select>
            </div>

            <!-- Dropdown Export SKM & Kinerja -->
            <div class="dropdown shrink-0">
              <button type="button" class="btn btn-primary bg-sky-700 hover:bg-sky-800 text-white font-bold text-xs px-4 py-2.5 rounded-xl flex items-center gap-2 shadow-md dropdown-toggle" data-bs-toggle="dropdown">
                <span class="material-icons text-sm">download</span>
                <span>Ekspor SKM & Kinerja</span>
              </button>
              <ul class="dropdown-menu dropdown-menu-end text-xs shadow-xl rounded-xl border border-slate-200 p-2">
                <li>
                  <button type="button" onclick="exportData('skm', 'excel')" class="dropdown-item py-2 px-3 rounded-lg flex items-center gap-2 font-semibold text-emerald-700 hover:bg-emerald-50">
                    <span class="material-icons text-sm">table_view</span>
                    <span>Unduh Excel SKM (Hasil Survei)</span>
                  </button>
                </li>
                <li>
                  <button type="button" onclick="exportData('skm', 'pdf')" class="dropdown-item py-2 px-3 rounded-lg flex items-center gap-2 font-semibold text-rose-700 hover:bg-rose-50">
                    <span class="material-icons text-sm">picture_as_pdf</span>
                    <span>Cetak Laporan PDF SKM</span>
                  </button>
                </li>
              </ul>
            </div>
          </div>
        </div>

        <!-- 6 Primary Large KPI Cards Grid -->
        <div>
          <h2 class="text-xs font-extrabold text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
            <span class="material-icons text-sky-600 text-sm">stars</span> METRIC OPERASIONAL UTAMA PST
          </h2>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            
            <div class="glass-card p-6 md:p-7 flex items-center gap-5 border border-sky-100 hover:shadow-lg transition">
              <div class="w-16 h-16 bg-sky-100 text-sky-800 rounded-2xl flex items-center justify-center shrink-0 shadow-sm">
                <span class="material-icons text-4xl">groups</span>
              </div>
              <div class="flex-1 min-w-0 space-y-1">
                <span class="text-[10px] font-extrabold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200 inline-block">Semua Waktu</span>
                <div class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Total Pengunjung Terdaftar</div>
                <div class="text-4xl font-black text-slate-900 brand-font" id="kpi_total_pengunjung">0</div>
                <div class="text-xs font-semibold text-sky-700 flex items-center gap-1"><span class="material-icons text-sm">badge</span> Akun Terverifikasi</div>
              </div>
            </div>

            <div class="glass-card p-6 md:p-7 flex items-center gap-5 border border-orange-100 hover:shadow-lg transition">
              <div class="w-16 h-16 bg-orange-100 text-orange-600 rounded-2xl flex items-center justify-center shrink-0 shadow-sm">
                <span class="material-icons text-4xl">confirmation_number</span>
              </div>
              <div class="flex-1 min-w-0 space-y-1">
                <span class="text-[10px] font-extrabold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200 inline-block">Semua Waktu</span>
                <div class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Akumulasi Seluruh Antrean</div>
                <div class="text-4xl font-black text-slate-900 brand-font" id="kpi_total_antrian">0</div>
                <div class="text-xs font-semibold text-orange-700 flex items-center gap-1"><span class="material-icons text-sm">view_list</span> Total Permohonan Kunjungan</div>
              </div>
            </div>

            <div class="glass-card p-6 md:p-7 flex items-center gap-5 border border-purple-100 hover:shadow-lg transition">
              <div class="w-16 h-16 bg-purple-100 text-purple-600 rounded-2xl flex items-center justify-center shrink-0 shadow-sm">
                <span class="material-icons text-4xl">today</span>
              </div>
              <div class="flex-1 min-w-0 space-y-1">
                <span class="text-[10px] font-extrabold px-2.5 py-0.5 rounded-full bg-purple-100 text-purple-800 border border-purple-200 inline-block">Hari Ini</span>
                <div class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Kunjungan Hari Ini</div>
                <div class="text-4xl font-black text-purple-700 brand-font" id="kpi_today_antrian">0</div>
                <div class="text-xs font-semibold text-purple-700 flex items-center gap-1"><span class="material-icons text-sm">event</span> Trafik Kunjungan Hari Ini</div>
              </div>
            </div>

            <div class="glass-card p-6 md:p-7 flex items-center gap-5 border border-teal-100 hover:shadow-lg transition">
              <div class="w-16 h-16 bg-teal-100 text-teal-700 rounded-2xl flex items-center justify-center shrink-0 shadow-sm">
                <span class="material-icons text-4xl">check_circle</span>
              </div>
              <div class="flex-1 min-w-0 space-y-1">
                <span class="text-[10px] font-extrabold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200 inline-block">Semua Waktu</span>
                <div class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Selesai Dilayani Loket</div>
                <div class="text-4xl font-black text-teal-800 brand-font" id="kpi_total_selesai">0</div>
                <div class="text-xs font-semibold text-teal-700 flex items-center gap-1"><span class="material-icons text-sm">task_alt</span> Kunjungan Tuntas Dilayani</div>
              </div>
            </div>

            <div class="glass-card p-6 md:p-7 flex items-center gap-5 border border-emerald-100 hover:shadow-lg transition">
              <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center shrink-0 shadow-sm">
                <span class="material-icons text-4xl">sentiment_very_satisfied</span>
              </div>
              <div class="flex-1 min-w-0 space-y-1">
                <span class="text-[10px] font-extrabold px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200 inline-block">PermenPAN-RB</span>
                <div class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Indeks Kepuasan (SKM)</div>
                <div class="text-4xl font-black text-emerald-600 brand-font flex items-baseline gap-1">
                  <span id="kpi_skm_skor">4.00</span>
                  <span class="text-sm font-bold text-slate-400">/ 4.00</span>
                </div>
                <div class="text-xs font-semibold text-emerald-700 flex items-center gap-1">
                  <span class="material-icons text-sm">verified</span>
                  <span id="kpi_skm_mutu">Mutu: A (Sangat Baik)</span>
                </div>
              </div>
            </div>

            <div class="glass-card p-6 md:p-7 flex items-center gap-5 border border-cyan-100 hover:shadow-lg transition">
              <div class="w-16 h-16 bg-cyan-100 text-cyan-700 rounded-2xl flex items-center justify-center shrink-0 shadow-sm">
                <span class="material-icons text-4xl">support_agent</span>
              </div>
              <div class="flex-1 min-w-0 space-y-1">
                <span class="text-[10px] font-extrabold px-2.5 py-0.5 rounded-full bg-cyan-100 text-cyan-800 border border-cyan-300 animate-pulse inline-block">Live Saat Ini</span>
                <div class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Sedang Dipanggil / Dilayani</div>
                <div class="text-4xl font-black text-cyan-700 brand-font" id="kpi_total_aktif">0</div>
                <div class="text-xs font-semibold text-cyan-800 flex items-center gap-1"><span class="material-icons text-sm">volume_up</span> Beban Loket Aktif Saat Ini</div>
              </div>
            </div>

          </div>
        </div>

        <!-- 4 Secondary Supporting KPI Cards -->
        <div>
          <h2 class="text-xs font-extrabold text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
            <span class="material-icons text-indigo-600 text-sm">bar_chart</span> INDIKATOR OPERASIONAL PENDUKUNG
          </h2>
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            
            <div class="glass-card p-5 border border-amber-100 flex items-center gap-4">
              <div class="w-12 h-12 bg-amber-100 text-amber-700 rounded-xl flex items-center justify-center shrink-0">
                <span class="material-icons text-2xl">hourglass_top</span>
              </div>
              <div class="flex-1 min-w-0 space-y-0.5">
                <span class="text-[9px] font-extrabold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 border border-amber-200 inline-block">Hari Ini</span>
                <div class="text-[11px] font-bold text-slate-500 uppercase">Menunggu Hari Ini</div>
                <div class="text-2xl font-black text-amber-700 brand-font" id="kpi_total_menunggu">0</div>
              </div>
            </div>

            <div class="glass-card p-5 border border-sky-100 flex items-center gap-4">
              <div class="w-12 h-12 bg-sky-100 text-sky-700 rounded-xl flex items-center justify-center shrink-0">
                <span class="material-icons text-2xl">computer</span>
              </div>
              <div class="flex-1 min-w-0 space-y-0.5">
                <span class="text-[9px] font-extrabold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200 inline-block">Semua Waktu</span>
                <div class="text-[11px] font-bold text-slate-500 uppercase">Pendaftaran Online</div>
                <div class="text-2xl font-black text-sky-800 brand-font" id="kpi_total_online">0</div>
              </div>
            </div>

            <div class="glass-card p-5 border border-purple-100 flex items-center gap-4">
              <div class="w-12 h-12 bg-purple-100 text-purple-700 rounded-xl flex items-center justify-center shrink-0">
                <span class="material-icons text-2xl">directions_walk</span>
              </div>
              <div class="flex-1 min-w-0 space-y-0.5">
                <span class="text-[9px] font-extrabold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200 inline-block">Semua Waktu</span>
                <div class="text-[11px] font-bold text-slate-500 uppercase">Walk-In Offline</div>
                <div class="text-2xl font-black text-purple-800 brand-font" id="kpi_total_walkin">0</div>
              </div>
            </div>

            <div class="glass-card p-5 border border-rose-100 flex items-center gap-4">
              <div class="w-12 h-12 bg-rose-100 text-rose-700 rounded-xl flex items-center justify-center shrink-0">
                <span class="material-icons text-2xl">report_problem</span>
              </div>
              <div class="flex-1 min-w-0 space-y-0.5">
                <span class="text-[9px] font-extrabold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200 inline-block">Semua Waktu</span>
                <div class="text-[11px] font-bold text-slate-500 uppercase">Pengaduan Masuk</div>
                <div class="text-2xl font-black text-rose-700 brand-font" id="kpi_total_pengaduan">0</div>
              </div>
            </div>

          </div>
        </div>

        <!-- SECTION 1: OPERASIONAL PELAYANAN PST (3 CHARTS) -->
        <div class="space-y-4">
          <div class="flex items-center gap-2 border-b border-slate-200 pb-2">
            <span class="material-icons text-sky-600">tune</span>
            <h2 class="text-lg font-extrabold text-slate-900 brand-font">1. Analisis Operasional & Trafik Pelayanan</h2>
          </div>
          
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <div class="glass-card p-6 space-y-3 hover:shadow-md transition">
              <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900 brand-font flex items-center gap-2">
                  <span class="material-icons text-sky-600 text-lg">pie_chart</span> Jenis Layanan PST
                </h3>
                <span class="text-[10px] font-bold text-sky-700 bg-sky-50 px-2 py-0.5 rounded-lg">PST Services</span>
              </div>
              <div class="p-2">
                <canvas id="chartLayanan" height="240"></canvas>
              </div>
            </div>

            <div class="glass-card p-6 space-y-3 hover:shadow-md transition">
              <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900 brand-font flex items-center gap-2">
                  <span class="material-icons text-amber-600 text-lg">bar_chart</span> Status Pelayanan Loket
                </h3>
                <span class="text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-lg">Queue Status</span>
              </div>
              <div class="p-2">
                <canvas id="chartStatus" height="240"></canvas>
              </div>
            </div>

            <div class="glass-card p-6 space-y-3 hover:shadow-md transition">
              <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900 brand-font flex items-center gap-2">
                  <span class="material-icons text-purple-600 text-lg">donut_large</span> Kanal Pendaftaran
                </h3>
                <span class="text-[10px] font-bold text-purple-700 bg-purple-50 px-2 py-0.5 rounded-lg">Online vs Walk-In</span>
              </div>
              <div class="p-2">
                <canvas id="chartTipe" height="240"></canvas>
              </div>
            </div>

          </div>
        </div>

        <!-- SECTION 2: PROFIL DEMOGRAFI PENGUNJUNG (5 CHARTS) -->
        <div class="space-y-4">
          <div class="flex items-center gap-2 border-b border-slate-200 pb-2">
            <span class="material-icons text-indigo-600">face</span>
            <h2 class="text-lg font-extrabold text-slate-900 brand-font">2. Demografi & Profil Pemohon Data</h2>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <div class="glass-card p-6 space-y-3 hover:shadow-md transition">
              <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900 brand-font flex items-center gap-2">
                  <span class="material-icons text-indigo-600 text-lg">work</span> Profil Pekerjaan
                </h3>
                <span class="text-[10px] font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-lg">Occupation</span>
              </div>
              <div class="p-2">
                <canvas id="chartPekerjaan" height="240"></canvas>
              </div>
            </div>

            <div class="glass-card p-6 space-y-3 hover:shadow-md transition">
              <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900 brand-font flex items-center gap-2">
                  <span class="material-icons text-emerald-600 text-lg">school</span> Tingkat Pendidikan
                </h3>
                <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-lg">Education</span>
              </div>
              <div class="p-2">
                <canvas id="chartPendidikan" height="240"></canvas>
              </div>
            </div>

            <div class="glass-card p-6 space-y-3 hover:shadow-md transition">
              <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900 brand-font flex items-center gap-2">
                  <span class="material-icons text-teal-600 text-lg">domain</span> Kategori Instansi
                </h3>
                <span class="text-[10px] font-bold text-teal-700 bg-teal-50 px-2 py-0.5 rounded-lg">Institution</span>
              </div>
              <div class="p-2">
                <canvas id="chartInstansi" height="240"></canvas>
              </div>
            </div>

          </div>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <div class="glass-card p-6 space-y-3 hover:shadow-md transition">
              <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900 brand-font flex items-center gap-2">
                  <span class="material-icons text-orange-600 text-lg">cake</span> Kelompok Umur Pengunjung
                </h3>
                <span class="text-[10px] font-bold text-orange-700 bg-orange-50 px-2 py-0.5 rounded-lg">Age Group</span>
              </div>
              <div class="p-2">
                <canvas id="chartUmur" height="240"></canvas>
              </div>
            </div>

            <div class="glass-card p-6 space-y-3 hover:shadow-md transition">
              <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900 brand-font flex items-center gap-2">
                  <span class="material-icons text-pink-600 text-lg">wc</span> Rasio Jenis Kelamin
                </h3>
                <span class="text-[10px] font-bold text-pink-700 bg-pink-50 px-2 py-0.5 rounded-lg">Gender Ratio</span>
              </div>
              <div class="p-2">
                <canvas id="chartJK" height="240"></canvas>
              </div>
            </div>

          </div>
        </div>

        <!-- SECTION 3: INSIGHT PEMANFAATAN & KEBUTUHAN DATA (3 CHARTS) -->
        <div class="space-y-4">
          <div class="flex items-center gap-2 border-b border-slate-200 pb-2">
            <span class="material-icons text-teal-600">insights</span>
            <h2 class="text-lg font-extrabold text-slate-900 brand-font">3. Insight Pemanfaatan & Kebutuhan Data</h2>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <div class="glass-card p-6 space-y-3 hover:shadow-md transition">
              <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900 brand-font flex items-center gap-2">
                  <span class="material-icons text-blue-600 text-lg">flag</span> Tujuan Pemanfaatan Data
                </h3>
                <span class="text-[10px] font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded-lg">Purpose</span>
              </div>
              <div class="p-2">
                <canvas id="chartPemanfaatan" height="240"></canvas>
              </div>
            </div>

            <div class="glass-card p-6 space-y-3 hover:shadow-md transition">
              <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900 brand-font flex items-center gap-2">
                  <span class="material-icons text-emerald-600 text-lg">trending_up</span> Perencanaan & Monev Pembangunan
                </h3>
                <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-lg font-mono">Gov Evaluation</span>
              </div>
              <div class="p-2">
                <canvas id="chartMonev" height="240"></canvas>
              </div>
            </div>

            <div class="glass-card p-6 space-y-3 hover:shadow-md transition">
              <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900 brand-font flex items-center gap-2">
                  <span class="material-icons text-cyan-600 text-lg">devices</span> Preferred Facility / Mode
                </h3>
                <span class="text-[10px] font-bold text-cyan-700 bg-cyan-50 px-2 py-0.5 rounded-lg">Onsite vs Online</span>
              </div>
              <div class="p-2">
                <canvas id="chartFasilitas" height="240"></canvas>
              </div>
            </div>

          </div>
        </div>

        <!-- Live Activity Stream & Recent SKM Feedback Section -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 pt-2">
          
          <!-- Stream Transaksi Kunjungan Terbaru (Large 7 Col) -->
          <div class="lg:col-span-7 glass-card p-7 space-y-5">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
              <div>
                <h3 class="text-lg font-bold text-slate-900 brand-font flex items-center gap-2">
                  <span class="material-icons text-sky-600 text-xl">history</span> Activity Stream Transaksi Terbaru
                </h3>
                <p class="text-xs text-slate-500 font-medium">8 transaksi registrasi antrean terkini di loket PST</p>
              </div>
              <a href="bukutamu.php" class="text-xs text-sky-600 hover:text-sky-800 font-bold flex items-center gap-1 bg-sky-50 px-3 py-1.5 rounded-xl border border-sky-200">
                <span>Kelola Semua</span> <span class="material-icons text-xs">arrow_forward</span>
              </a>
            </div>

            <div class="overflow-x-auto">
              <table class="w-full text-left text-sm">
                <thead>
                  <tr class="border-b border-slate-200 text-slate-400 uppercase text-xs font-bold tracking-wider">
                    <th class="py-3 px-2">No. Antrean</th>
                    <th class="py-3 px-2">Nama Pengunjung</th>
                    <th class="py-3 px-2">Jenis Layanan</th>
                    <th class="py-3 px-2">Kanal</th>
                    <th class="py-3 px-2 text-right">Status</th>
                  </tr>
                </thead>
                <tbody id="tbody_recent_antrian">
                  <tr><td colspan="5" class="py-6 text-center text-slate-400 font-medium">Memuat data aktivitas antrean...</td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Feed Ulasan SKM Pengunjung Terbaru (Large 5 Col) -->
          <div class="lg:col-span-5 glass-card p-7 space-y-5">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
              <div>
                <h3 class="text-lg font-bold text-slate-900 brand-font flex items-center gap-2">
                  <span class="material-icons text-amber-500 text-xl">rate_review</span> Ulasan & Feedback SKM
                </h3>
                <p class="text-xs text-slate-500 font-medium">Kutipan ulasan & penilaian nyata dari pengunjung</p>
              </div>
              <span class="badge bg-amber-100 text-amber-800 text-xs font-bold px-2.5 py-1 rounded-lg">Terbaru</span>
            </div>

            <div id="container_recent_skm" class="space-y-4 max-h-[420px] overflow-y-auto pr-1">
              <div class="text-center py-6 text-slate-400 font-medium">Memuat feed ulasan SKM...</div>
            </div>
          </div>

        </div>

        <!-- NEW SECTION: Pengaduan & Feedback Widget Stream -->
        <div class="glass-card p-7 space-y-5">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
              <h3 class="text-lg font-bold text-slate-900 brand-font flex items-center gap-2">
                <span class="material-icons text-rose-600 text-xl">feedback</span> Masukan, Saran & Pengaduan Publik
              </h3>
              <p class="text-xs text-slate-500 font-medium">Data pengaduan & feedback dari widget publik website</p>
            </div>
            <span class="badge bg-rose-100 text-rose-800 text-xs font-bold px-3 py-1 rounded-lg">Widget Stream</span>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
              <thead>
                <tr class="border-b border-slate-200 text-slate-400 uppercase text-xs font-bold tracking-wider">
                  <th class="py-3 px-2">Tipe</th>
                  <th class="py-3 px-2">Nama Pengirim</th>
                  <th class="py-3 px-2">Kontak</th>
                  <th class="py-3 px-2">Kategori / Rating</th>
                  <th class="py-3 px-2">Isi Pesan / Pengaduan</th>
                  <th class="py-3 px-2 text-right">Waktu</th>
                </tr>
              </thead>
              <tbody id="tbody_recent_pengaduan">
                <tr><td colspan="6" class="py-6 text-center text-slate-400 font-medium">Memuat data pengaduan...</td></tr>
              </tbody>
            </table>
          </div>
        </div>

      </div>

      <!-- Footer Status Bar Admin Modern -->
      <footer class="bg-white/80 backdrop-blur border-t border-slate-200/80 py-2.5 px-6 text-xs text-slate-500 flex flex-col sm:flex-row items-center justify-between gap-2 mt-auto">
        <div class="flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
          <span class="font-semibold text-slate-700">SPST BPS Kota Tegal</span>
          <span class="text-slate-400">• Portal Loket & Antrean Real-Time v2.4</span>
        </div>
        <div class="text-[11px] text-slate-400 font-medium">
          © <?php echo date('Y'); ?> Badan Pusat Statistik Kota Tegal
        </div>
      </footer>

    </main>
  </div>

  <script src="../js/app.js"></script>
  <script src="../js/admin-dashboard.js"></script>
</body>
</html>
