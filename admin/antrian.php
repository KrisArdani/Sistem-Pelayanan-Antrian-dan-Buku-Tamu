<?php
// SPST BPS Kota Tegal - Admin Panel Loket Antrian
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
  <title>Kelola Loket Antrian - SPST Admin BPS Kota Tegal</title>

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
          <a href="bukutamu.php" class="bps-nav-item"><span class="material-icons">groups</span> Kelola Buku Tamu</a>
          <a href="antrian.php" class="bps-nav-item flex items-center justify-between active">
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
          <a href="../display.php" target="_blank" class="bps-nav-item"><span class="material-icons text-amber-400">tv</span> Layar Display TV</a>
          <a href="../index.php" class="bps-nav-item"><span class="material-icons">open_in_new</span> Portal Publik</a>
        </nav>
      </div>

      <div class="p-3 bg-slate-800/90 rounded-2xl border border-slate-700/70 space-y-2 shadow-inner">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-sky-600 via-indigo-600 to-blue-700 text-white font-extrabold text-sm flex items-center justify-center shrink-0 shadow-md">
            <span class="material-icons text-xl">admin_panel_settings</span>
          </div>
          <div class="min-w-0 flex-1">
            <div id="user_display_name" class="font-extrabold text-white text-xs truncate leading-tight" title="<?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Petugas PST'); ?>">
              <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Petugas PST'); ?>
            </div>
            <div id="user_display_role" class="text-[10px] font-bold text-sky-400 uppercase tracking-wider mt-0.5 flex items-center gap-1">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
              <span>Aktor: <?php echo htmlspecialchars($_SESSION['user_role'] ?? 'Petugas'); ?></span>
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
            <a href="dashboard.php" class="bps-nav-item"><span class="material-icons">dashboard</span> Executive Dashboard</a>
            <a href="bukutamu.php" class="bps-nav-item"><span class="material-icons">groups</span> Kelola Buku Tamu</a>
            <a href="antrian.php" class="bps-nav-item flex items-center justify-between active">
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
              <div class="font-extrabold text-white text-xs truncate leading-tight" title="<?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Petugas PST'); ?>">
                <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Petugas PST'); ?>
              </div>
              <div class="text-[10px] font-bold text-sky-400 uppercase tracking-wider mt-0.5 flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>Aktor: <?php echo htmlspecialchars($_SESSION['user_role'] ?? 'Petugas'); ?></span>
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
          <span class="text-xs font-bold px-3 py-1 bg-sky-100 text-sky-700 rounded-full uppercase tracking-wider">Petugas PST - Loket Antrian</span>
        </div>

        <div class="flex items-center gap-3">
          <button onclick="logoutUser()" class="btn btn-sm btn-outline-danger text-xs flex items-center gap-1">
            <span class="material-icons text-sm">logout</span> Logout
          </button>
        </div>
      </header>

      <!-- Main Container -->
      <div class="p-6 md:p-10 max-w-7xl mx-auto w-full space-y-8">
        
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <h1 class="text-2xl font-extrabold text-slate-900 brand-font">Kelola & Pemanggilan Antrian Loket</h1>
            <p class="text-slate-500 text-xs">Panggil nomor antrian yang menunggu secara teratur.</p>
          </div>

          <!-- Action Bar: Buka Display TV & Export Laporan -->
          <div class="flex flex-wrap items-center gap-3">
            <!-- Tombol Buka Layar Display TV -->
            <a href="../display.php" target="_blank" class="btn btn-warning bg-amber-500 hover:bg-amber-600 text-slate-950 font-extrabold text-xs px-4 py-2.5 rounded-xl flex items-center gap-2 shadow-md transition transform active:scale-95" title="Buka Papan Antrean Layar Penuh untuk TV Ruang Tunggu">
              <span class="material-icons text-sm text-slate-950">tv</span>
              <span>Buka Layar Display TV</span>
            </a>

            <!-- Dropdown Export Laporan -->
            <div class="dropdown shrink-0">
              <button type="button" class="btn btn-primary bg-sky-700 hover:bg-sky-800 text-white font-bold text-xs px-4 py-2.5 rounded-xl flex items-center gap-2 shadow-md dropdown-toggle" data-bs-toggle="dropdown">
                <span class="material-icons text-sm">download</span>
                <span>Ekspor Laporan</span>
              </button>
              <ul class="dropdown-menu dropdown-menu-end text-xs shadow-xl rounded-xl border border-slate-200 p-2">
                <li>
                  <button type="button" onclick="exportData('antrian', 'excel')" class="dropdown-item py-2 px-3 rounded-lg flex items-center gap-2 font-semibold text-emerald-700 hover:bg-emerald-50">
                    <span class="material-icons text-sm">table_view</span>
                    <span>Unduh Format Excel / CSV</span>
                  </button>
                </li>
                <li>
                  <button type="button" onclick="exportData('antrian', 'pdf')" class="dropdown-item py-2 px-3 rounded-lg flex items-center gap-2 font-semibold text-rose-700 hover:bg-rose-50">
                    <span class="material-icons text-sm">picture_as_pdf</span>
                    <span>Pratinjau / Cetak Laporan PDF</span>
                  </button>
                </li>
              </ul>
            </div>
          </div>
        </div>

        <!-- Call Board Header & Next Queue Card -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          
          <!-- Board Active Card (Sedang Dipanggil) -->
          <div class="lg:col-span-2 bg-gradient-to-r from-[#002B5B] via-[#003366] to-[#0284c7] rounded-3xl p-6 md:p-8 text-white shadow-xl flex flex-col justify-between space-y-4 relative overflow-hidden border border-sky-400/20">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-sky-200 uppercase tracking-widest flex items-center gap-1.5">
                <span class="material-icons text-amber-400 text-sm">volume_up</span>
                <span>LOKET PST 1 (SEDANG DIPANGGIL)</span>
              </span>
              <span class="badge bg-amber-400 text-slate-900 font-extrabold text-xs uppercase px-3 py-1.5 rounded-full shadow-sm">
                Panggilan Aktif
              </span>
            </div>
            
            <div class="text-center py-2 space-y-2">
              <div class="text-5xl md:text-7xl font-black tracking-wider brand-font text-white drop-shadow-md" id="board_active_number">---</div>
              <div class="text-lg md:text-xl font-bold text-sky-100" id="board_active_name">Belum Ada Panggilan</div>
              <div class="text-xs font-semibold text-sky-300 bg-white/10 backdrop-blur-md px-3.5 py-1 rounded-full inline-block border border-white/10" id="board_active_service">-</div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
              <button id="btn_panggil_berikutnya" class="btn btn-warning btn-lg w-full py-3 bg-[#FF6B35] border-none text-white font-bold flex items-center justify-center gap-2 shadow-lg hover:bg-[#E85A24] text-sm rounded-2xl transition">
                <span class="material-icons text-xl">volume_up</span>
                <span>Panggil Berikutnya</span>
              </button>

              <button id="btn_panggil_ulang_aktif" class="btn btn-sky btn-lg w-full py-3 bg-sky-600 border-none text-white font-bold flex items-center justify-center gap-2 shadow-lg hover:bg-sky-500 text-sm rounded-2xl transition">
                <span class="material-icons text-xl">replay</span>
                <span>Panggil Ulang (Re-Call)</span>
              </button>
            </div>
          </div>

          <!-- Board Next Queue Card (Akan Dipanggil Berikutnya) -->
          <div class="bg-gradient-to-br from-emerald-900 via-teal-900 to-slate-900 rounded-3xl p-6 text-white shadow-xl flex flex-col justify-between space-y-4 border border-emerald-400/20 relative overflow-hidden">
            <div class="flex items-center justify-between">
              <span class="text-xs font-extrabold text-emerald-300 uppercase tracking-widest flex items-center gap-1.5">
                <span class="material-icons text-emerald-400 text-sm">queue_play_next</span>
                <span>AKAN DIPANGGIL</span>
              </span>
              <span id="board_next_badge" class="badge bg-emerald-500 text-white font-bold text-[10px] uppercase px-2.5 py-1 rounded-full shadow-sm">
                Antrean Kosong
              </span>
            </div>
            
            <div class="text-center py-2 space-y-1.5">
              <div class="text-xs text-emerald-300 font-semibold uppercase tracking-wider">Nomor Berikutnya:</div>
              <div class="text-4xl md:text-5xl font-black tracking-wider brand-font text-emerald-200 drop-shadow-md" id="board_next_number">---</div>
              <div class="text-base font-bold text-white truncate max-w-[240px] mx-auto" id="board_next_name">Tidak Ada Antrean Menunggu</div>
              <div class="text-xs text-emerald-300 truncate max-w-[240px] mx-auto" id="board_next_service">-</div>
              <div class="text-[11px] text-emerald-400/80 font-mono pt-1" id="board_next_info">-</div>
            </div>

            <div class="p-3 bg-emerald-950/80 rounded-2xl border border-emerald-500/20 text-center space-y-1">
              <div class="text-[11px] font-bold text-emerald-300 flex items-center justify-center gap-1">
                <span class="material-icons text-xs">info</span> 
                <span>Informasi Petugas Loket:</span>
              </div>
              <p class="text-[10px] text-emerald-100/70 leading-relaxed">
                Nomor di atas akan dipanggil otomatis saat Anda menekan tombol <b>Panggil Berikutnya</b>.
              </p>
            </div>
          </div>

        </div>

        <!-- Antrian Table Header & Walk-In Button -->
        <div class="glass-card overflow-hidden">
          <div class="p-6 border-b border-slate-100 flex flex-col xl:flex-row xl:items-center justify-between gap-4">
            <div>
              <h3 class="text-lg font-bold text-slate-900 brand-font">Daftar Seluruh Antrian</h3>
              <p class="text-xs text-slate-500">Antrian terintegrasi online dan pengunjung walk-in offline.</p>
            </div>
            
            <div class="flex items-center gap-3 flex-wrap sm:flex-nowrap shrink-0">
              <!-- Filter Tanggal Loket Antrian -->
              <div class="flex items-center gap-2 shrink-0">
                <label for="filter_tanggal_antrian" class="text-xs font-bold text-slate-600 shrink-0">Tanggal:</label>
                <select id="filter_tanggal_antrian" class="form-select form-select-sm text-xs font-bold rounded-xl text-sky-900 border-sky-300 bg-sky-50 w-48">
                  <option value="today" selected>☀️ Hari Ini (<?php echo date('d/m/Y'); ?>)</option>
                  <option value="tomorrow">🗓️ Besok (<?php echo date('d/m/Y', strtotime('+1 day')); ?>)</option>
                  <option value="all">🌐 Semua Tanggal (Mendatang)</option>
                </select>
              </div>

              <!-- Filter Layanan Loket Antrian -->
              <?php
              $assignedLayanan = trim($_SESSION['user_layanan_tugas'] ?? '');
              $userRole = $_SESSION['user_role'] ?? '';
              ?>
              <div class="flex items-center gap-2 shrink-0">
                <label for="filter_layanan_antrian" class="text-xs font-bold text-slate-600 shrink-0">
                  Loket Layanan:
                  <?php if ($userRole === 'petugas' && !empty($assignedLayanan)): ?>
                    <span class="text-[10px] text-purple-700 font-extrabold bg-purple-100 px-1.5 py-0.5 rounded border border-purple-200">Tugas: <?php echo htmlspecialchars($assignedLayanan); ?></span>
                  <?php endif; ?>
                </label>
                <select id="filter_layanan_antrian" class="form-select form-select-sm text-xs font-bold rounded-xl text-purple-900 border-purple-300 bg-purple-50 w-48">
                  <option value="all" selected>🏢 Semua Loket Layanan</option>
                  <option value="Konsultasi Statistik">💬 Loket Konsultasi</option>
                  <option value="Perpustakaan">📚 Loket Perpustakaan</option>
                  <option value="Rekomendasi Kegiatan Statistik">📑 Loket Rekomendasi</option>
                  <option value="Layanan Pengaduan">📣 Loket Pengaduan</option>
                </select>
              </div>

              <button type="button" class="btn btn-success bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl flex items-center gap-2 shadow-md shrink-0 whitespace-nowrap" data-bs-toggle="modal" data-bs-target="#modalWalkin">
                <span class="material-icons text-sm">person_add_alt_1</span>
                <span>Input Pengunjung Walk-In (Offline)</span>
              </button>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-sm">
              <thead class="bg-slate-100 text-slate-700 text-xs uppercase tracking-wider">
                <tr>
                  <th class="py-3 px-4">No</th>
                  <th class="py-3 px-4">Nomor Antrian</th>
                  <th class="py-3 px-4">Nama Pemohon</th>
                  <th class="py-3 px-4">Instansi & Layanan</th>
                  <th class="py-3 px-4">Tipe & Jam</th>
                  <th class="py-3 px-4">Status</th>
                  <th class="py-3 px-4">Aksi</th>
                </tr>
              </thead>
              <tbody id="tbody_antrian_admin">
                <!-- JS rendered -->
              </tbody>
            </table>
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

  <!-- ================= MODALS (Placed at body root level) ================= -->
  
  <!-- Modal Walk-In Pengunjung Offline -->
  <div class="modal fade" id="modalWalkin" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content rounded-2xl border-none shadow-2xl overflow-hidden">
        <div class="modal-header bg-emerald-700 text-white p-4">
          <h5 class="modal-title font-bold text-base brand-font flex items-center gap-2">
            <span class="material-icons">person_add_alt_1</span> Input Pengunjung Walk-In (Offline)
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form id="formWalkin">
          <div class="modal-body p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="form-label text-xs font-bold text-slate-700 uppercase">Nama Lengkap Pengunjung <span class="text-red-500">*</span></label>
                <input type="text" id="walkin_nama" class="form-control text-sm rounded-xl" placeholder="Nama pengunjung..." required>
              </div>
              <div>
                <label class="form-label text-xs font-bold text-slate-700 uppercase">NIK KTP <span class="text-slate-400 font-normal lowercase">(16 digit)</span></label>
                <input type="text" id="walkin_nik" class="form-control text-sm rounded-xl font-mono" maxlength="16" placeholder="33760...">
              </div>
              <div>
                <label class="form-label text-xs font-bold text-slate-700 uppercase">Nomor HP / WA <span class="text-red-500">*</span></label>
                <input type="tel" id="walkin_nohp" class="form-control text-sm rounded-xl" placeholder="0812..." required>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="form-label text-xs font-bold text-slate-700 uppercase">Alamat Email</label>
                <input type="email" id="walkin_email" class="form-control text-sm rounded-xl" placeholder="email@contoh.com">
              </div>
              <div>
                <label class="form-label text-xs font-bold text-slate-700 uppercase">Jenis Kelamin</label>
                <select id="walkin_jk" class="form-select text-sm rounded-xl">
                  <option value="Laki Laki">Laki Laki</option>
                  <option value="Perempuan">Perempuan</option>
                </select>
              </div>
              <div>
                <label class="form-label text-xs font-bold text-slate-700 uppercase">Usia</label>
                <select id="walkin_umur" class="form-select text-sm rounded-xl">
                  <option value="17-25 tahun">17-25 tahun</option>
                  <option value="26-34 tahun">26-34 tahun</option>
                  <option value="35-44 tahun">35-44 tahun</option>
                  <option value="45+ tahun">45+ tahun</option>
                  <option value="< 17 tahun">&lt; 17 tahun</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="form-label text-xs font-bold text-slate-700 uppercase">Pendidikan Terakhir</label>
                <select id="walkin_pendidikan" class="form-select text-sm rounded-xl">
                  <option value="D4-S1">D4-S1</option>
                  <option value="SMA Ke Bawah">SMA Ke Bawah</option>
                  <option value="D1/D2/D3">D1/D2/D3</option>
                  <option value="S2-S3">S2-S3</option>
                </select>
              </div>
              <div>
                <label class="form-label text-xs font-bold text-slate-700 uppercase">Pekerjaan <span class="text-red-500">*</span></label>
                <select id="walkin_pekerjaan" class="form-select text-sm rounded-xl" required>
                  <option value="Mahasiswa">Mahasiswa / Pelajar</option>
                  <option value="Peneliti/Dosen">Peneliti / Dosen</option>
                  <option value="Pegawai Negeri / TNI POLRI">Pegawai Negeri / TNI POLRI</option>
                  <option value="Pegawai Swasta">Pegawai Swasta</option>
                  <option value="Wiraswasta">Wiraswasta</option>
                  <option value="Lainnya">Lainnya</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="form-label text-xs font-bold text-slate-700 uppercase">Nama Instansi <span class="text-red-500">*</span></label>
                <input type="text" id="walkin_instansi" class="form-control text-sm rounded-xl" placeholder="UPS Tegal, Pemda, umum..." required>
              </div>
              <div>
                <label class="form-label text-xs font-bold text-slate-700 uppercase">Kategori Instansi <span class="text-red-500">*</span></label>
                <select id="walkin_kategori_instansi" class="form-select text-sm rounded-xl" required>
                  <option value="Sekolah/Universitas">Sekolah / Universitas</option>
                  <option value="Kementerian/Lembaga/Pemda">Kementerian / Pemda / OPD</option>
                  <option value="BUMN/BUMD">BUMN / BUMD</option>
                  <option value="Swasta/Wirausaha">Swasta / Usaha Bisnis</option>
                  <option value="Lembaga Internasional">Lembaga Internasional</option>
                  <option value="Perorangan/Lainnya">Perorangan / Masyarakat Umum</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="form-label text-xs font-bold text-slate-700 uppercase">Jenis Layanan PST <span class="text-red-500">*</span></label>
                <select id="walkin_layanan" class="form-select text-sm rounded-xl" required>
                  <option value="Konsultasi Statistik">Konsultasi Statistik</option>
                  <option value="Perpustakaan">Perpustakaan</option>
                  <option value="Rekomendasi Kegiatan Statistik">Rekomendasi Kegiatan Statistik</option>
                  <option value="Layanan Pengaduan">Layanan Pengaduan</option>
                </select>
              </div>
              <div>
                <label class="form-label text-xs font-bold text-slate-700 uppercase">Tujuan Pemanfaatan Data</label>
                <select id="walkin_pemanfaatan" class="form-select text-sm rounded-xl">
                  <option value="Tugas Sekolah/Kuliah">Tugas Sekolah / Kuliah</option>
                  <option value="Penelitian">Penelitian / Skripsi</option>
                  <option value="Pemerintah">Pemerintah</option>
                  <option value="Komersial/Wirausaha">Komersial / Usaha</option>
                  <option value="Lainnya">Lainnya</option>
                </select>
              </div>
            </div>

            <div>
              <label class="form-label text-xs font-bold text-slate-700 uppercase">Digunakan untuk Perencanaan / Monev Pembangunan? <span class="text-red-500">*</span></label>
              <select id="walkin_monev" class="form-select text-sm rounded-xl" required>
                <option value="Ya">Ya (Perencanaan, Monitoring & Evaluasi Pembangunan)</option>
                <option value="Tidak">Tidak</option>
              </select>
            </div>

            <div>
              <label class="form-label text-xs font-bold text-slate-700 uppercase">Rincian Data yang Dicari</label>
              <textarea id="walkin_data_diinginkan" class="form-control text-sm rounded-xl" rows="2" placeholder="Catatan data yang dicari pengunjung..."></textarea>
            </div>
          </div>
          <div class="modal-footer bg-slate-100 p-4">
            <button type="button" class="btn btn-secondary text-xs rounded-xl" data-bs-dismiss="modal">Batal</button>
            <button type="submit" id="btnSubmitWalkin" class="btn btn-success bg-emerald-600 text-white font-bold text-xs rounded-xl flex items-center gap-1">
              <span class="material-icons text-sm">save</span> Simpan & Buat Antrian Walk-In
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal Input Catatan Pelayanan Petugas (Saat Selesai Pelayanan) -->
  <div class="modal fade" id="modalSelesaiPelayanan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-2xl border-0 shadow-2xl overflow-hidden">
        <div class="modal-header bg-gradient-to-r from-emerald-700 to-teal-800 text-white p-4">
          <h5 class="modal-title font-extrabold text-sm md:text-base flex items-center gap-2">
            <span class="material-icons text-amber-300">task_alt</span>
            <span>Konfirmasi Penyelesaian & Catatan Pelayanan</span>
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form id="formSelesaiPelayanan" class="space-y-0">
          <input type="hidden" id="selesai_antrian_id" value="">
          
          <div class="modal-body p-5 space-y-4 text-xs">
            <!-- Notice Banner saat dipanggil dari Panggil Berikutnya -->
            <div id="selesai_modal_notice_autocall" class="hidden p-3 bg-amber-50 rounded-xl border border-amber-300 text-amber-900 font-semibold text-xs flex items-center gap-2 shadow-sm">
              <span class="material-icons text-amber-600 text-sm">info</span>
              <span>Mohon isi catatan pelayanan antrean saat ini sebelum memanggil antrean berikutnya.</span>
            </div>

            <!-- Summary Card Ringkasan Pengunjung -->
            <div class="p-3.5 bg-emerald-50 rounded-xl border border-emerald-200 text-emerald-950 space-y-1">
              <div class="flex items-center justify-between font-bold">
                <span class="text-xs text-emerald-800" id="selesai_modal_nomor">KS-01</span>
                <span class="text-[11px] px-2 py-0.5 bg-emerald-200 text-emerald-900 rounded-md font-mono" id="selesai_modal_kode">ANT-123456</span>
              </div>
              <div class="text-sm font-extrabold text-slate-900" id="selesai_modal_nama">Nama Pengunjung</div>
              <div class="text-xs text-slate-600 font-semibold flex items-center gap-1" id="selesai_modal_layanan">
                <span class="material-icons text-xs text-emerald-600">storefront</span> Konsultasi Statistik
              </div>
              <div class="pt-1.5 border-t border-emerald-200/60 text-[11px] text-slate-700">
                <span class="font-bold text-slate-900">Data Dicari Pengunjung:</span>
                <p class="italic text-slate-600 bg-white/70 p-2 rounded-lg mt-1 border border-emerald-200/50" id="selesai_modal_data_diinginkan">-</p>
              </div>
            </div>

            <!-- Form Input Catatan Petugas -->
            <div class="space-y-2">
              <label class="form-label font-extrabold text-slate-900 text-xs flex items-center justify-between">
                <span>Catatan Pelayanan & Data Yang Diberikan:</span>
                <span class="text-[10px] text-slate-400 font-normal">(Opsional / Sangat Dianjurkan)</span>
              </label>
              <textarea id="selesai_catatan_petugas" class="form-control text-xs rounded-xl p-3 border-2 border-slate-300 focus:border-emerald-500" rows="3" placeholder="Contoh: Menyerahkan Softcopy PDF Tegal Dalam Angka 2024 & Konsultasi SBR..."></textarea>
            </div>

            <!-- Quick Tags / Chips -->
            <div class="space-y-1.5">
              <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Sisipkan Quick-Tag Cepat:</div>
              <div class="flex flex-wrap gap-1.5">
                <button type="button" onclick="appendQuickTag('[Softcopy PDF/Excel] ')" class="px-2.5 py-1 bg-slate-100 hover:bg-emerald-100 hover:text-emerald-800 text-slate-700 font-semibold text-[11px] rounded-lg border border-slate-200 transition">📄 Softcopy PDF/Excel</button>
                <button type="button" onclick="appendQuickTag('[Publikasi BPS] ')" class="px-2.5 py-1 bg-slate-100 hover:bg-emerald-100 hover:text-emerald-800 text-slate-700 font-semibold text-[11px] rounded-lg border border-slate-200 transition">📚 Publikasi BPS</button>
                <button type="button" onclick="appendQuickTag('[Konsultasi Metodologi] ')" class="px-2.5 py-1 bg-slate-100 hover:bg-emerald-100 hover:text-emerald-800 text-slate-700 font-semibold text-[11px] rounded-lg border border-slate-200 transition">💡 Konsultasi Metodologi</button>
                <button type="button" onclick="appendQuickTag('[Surat Rekomendasi] ')" class="px-2.5 py-1 bg-slate-100 hover:bg-emerald-100 hover:text-emerald-800 text-slate-700 font-semibold text-[11px] rounded-lg border border-slate-200 transition">📜 Surat Rekomendasi</button>
                <button type="button" onclick="appendQuickTag('[Bimbingan SBR] ')" class="px-2.5 py-1 bg-slate-100 hover:bg-emerald-100 hover:text-emerald-800 text-slate-700 font-semibold text-[11px] rounded-lg border border-slate-200 transition">🏢 Bimbingan SBR</button>
              </div>
            </div>
          </div>

          <div class="modal-footer bg-slate-100 p-3 flex items-center justify-between">
            <button type="button" class="btn btn-secondary text-xs rounded-xl" data-bs-dismiss="modal">Batal</button>
            <button type="submit" id="btnConfirmSelesai" class="btn btn-emerald bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs px-4 py-2 rounded-xl border-none shadow flex items-center gap-1.5">
              <span class="material-icons text-sm">check_circle</span>
              <span>Simpan & Selesaikan Pelayanan</span>
            </button>
          </div>
        </form>
      </div>
    </div>
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
  <!-- Modal Detail Dokumen Pengunjung -->

  <script src="../js/app.js?v=<?php echo time(); ?>"></script>
  <script src="../js/admin-antrian.js?v=<?php echo time(); ?>"></script>
</body>
</html>
