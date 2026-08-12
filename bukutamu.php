<?php
// SPST BPS Kota Tegal - Halaman Riwayat & Tiket Kunjungan Pengunjung
require_once __DIR__ . '/security.php';
setSecurityHeaders();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Wajibkan login: Jika pengguna belum login, alihkan ke login.php terlebih dahulu
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$csrf_token = generateCsrfToken();
$activeMenu = 'bukutamu';

$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['user_role'] ?? 'guest';
$userName = $_SESSION['user_name'] ?? '';
$userNoHp = $_SESSION['user_nohp'] ?? '';
$userInstansi = $_SESSION['user_instansi'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
  <title>Riwayat & Tiket Kunjungan - SPST BPS Kota Tegal</title>

  <!-- Tailwind CSS Play CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Bootstrap 5.3.3 CSS & Bundle -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Icons & Fonts -->
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- QRCode.js CDN -->
  <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
  <!-- Custom CSS & Emoji Animation Keyframes -->
  <link rel="stylesheet" href="css/custom.css">
  <link rel="stylesheet" href="css/bukutamu.css">
</head>
<body class="bg-slate-50 font-['Inter'] text-slate-800 antialiased">

  <div class="flex min-h-screen">
    <!-- Include Sidebar Navigation -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content Area -->
    <main class="flex-1 lg:ml-72 min-h-screen flex flex-col justify-between">
      
      <!-- Top Navbar Header -->
      <header class="bg-white/80 backdrop-blur-md border-b border-slate-200 sticky top-0 z-20 px-6 py-4 flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-3">
          <button class="lg:hidden p-2 rounded-lg bg-slate-100 text-slate-700" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
            <span class="material-icons">menu</span>
          </button>
          <span class="text-xs font-bold px-3 py-1 bg-sky-100 text-sky-700 rounded-full uppercase tracking-wider">Riwayat & Tiket Kunjungan Saya</span>
        </div>

        <div class="flex items-center gap-3">
          <?php if ($isLoggedIn): ?>
            <div class="flex items-center gap-2 px-3 py-1.5 bg-sky-50 border border-sky-200 rounded-full text-xs font-semibold text-slate-700 shadow-sm">
              <span class="material-icons text-sky-600 text-sm">account_circle</span>
              <span class="hidden sm:inline">Pengunjung: <b class="text-sky-900"><?php echo htmlspecialchars($userName); ?></b></span>
              <span class="sm:hidden font-bold text-sky-900"><?php echo htmlspecialchars(explode(' ', $userName)[0]); ?></span>
            </div>
            <a href="logout.php" class="btn btn-outline-danger btn-sm text-xs flex items-center gap-1 border-rose-300 text-rose-700 hover:bg-rose-600 hover:text-white font-bold px-3 rounded-xl shadow-sm" title="Keluar dari Akun">
              <span class="material-icons text-sm">logout</span>
              <span class="hidden sm:inline">Keluar</span>
            </a>
          <?php else: ?>
            <a href="login.php" class="btn btn-primary btn-sm bg-[#003366] border-[#003366] text-xs flex items-center gap-1">
              <span class="material-icons text-sm">login</span> Masuk / Login
            </a>
          <?php endif; ?>
        </div>
      </header>

      <!-- Main Container -->
      <div class="p-6 md:p-10 max-w-5xl mx-auto w-full space-y-8">
        
        <!-- Header Title Banner -->
        <div class="bg-gradient-to-r from-sky-900 via-sky-800 to-slate-900 rounded-2xl p-6 md:p-8 text-white shadow-xl flex flex-col md:flex-row md:items-center md:justify-between gap-6 relative overflow-hidden">
          <div class="space-y-2 z-10">
            <span class="text-xs font-bold uppercase tracking-widest text-sky-300 bg-sky-950/60 px-3 py-1 rounded-full border border-sky-400/30">BPS KOTA TEGAL</span>
            <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight brand-font">Riwayat & Tiket Kunjungan Saya</h1>
            <p class="text-sky-100 text-xs md:text-sm max-w-xl leading-relaxed">
              Kelola daftar reservasi antrean PST BPS Kota Tegal, cetak ulang QR Code tiket digital, dan sampaikan ulasan kepuasan pelayanan Anda.
            </p>
          </div>

          <div class="z-10 shrink-0">
            <a href="antrian.php" class="btn btn-sky bg-sky-500 hover:bg-sky-400 text-white font-bold py-3 px-5 rounded-xl shadow-lg flex items-center gap-2 text-sm border-none">
              <span class="material-icons">add_circle</span>
              <span>Reservasi Antrean Baru</span>
            </a>
          </div>
        </div>

        <!-- DYNAMIC PROGRESS TIMELINE STEPPER BAR -->
        <?php include __DIR__ . '/stepper.php'; ?>

        <?php if (!$isLoggedIn): ?>
        <!-- Banner Jika Pengunjung Belum Login -->
        <div class="p-8 bg-white rounded-2xl shadow-sm border border-slate-200 text-center space-y-4">
          <div class="w-16 h-16 bg-sky-100 text-sky-600 rounded-full flex items-center justify-center mx-auto">
            <span class="material-icons text-3xl">lock</span>
          </div>
          <div class="space-y-1">
            <h3 class="text-lg font-bold text-slate-800">Silakan Login atau Daftar Akun</h3>
            <p class="text-xs text-slate-500 max-w-md mx-auto">
              Masuk dengan akun pengunjung Anda untuk melihat riwayat kunjungan, mencetak QRCode tiket digital, dan menyampaikan indeks kepuasan (SKM).
            </p>
          </div>
          <div class="flex items-center justify-center gap-3 pt-2">
            <a href="login.php" class="btn btn-primary bg-sky-600 border-sky-600 font-bold text-xs px-5 py-2.5 rounded-xl">Login Pengunjung</a>
            <a href="register.php" class="btn btn-outline-secondary font-bold text-xs px-5 py-2.5 rounded-xl">Daftar Akun Pengunjung</a>
          </div>
        </div>
        <?php else: ?>

        <!-- Petunjuk Pengisian SKM (Guidance Alert Banner) -->
        <div class="p-4 bg-gradient-to-r from-amber-500/10 via-sky-500/10 to-emerald-500/10 border-2 border-amber-300/80 rounded-2xl flex flex-col md:flex-row items-start md:items-center justify-between gap-3 shadow-sm">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-400 text-slate-900 flex items-center justify-center font-bold text-xl shrink-0 shadow">⭐</div>
            <div>
              <div class="text-xs md:text-sm font-extrabold text-slate-800">Petunjuk Pengunjung: Ulasan Kepuasan Layanan (SKM)</div>
              <p class="text-xs text-slate-600 leading-relaxed">
                Setelah pelayanan Anda selesai di loket PST BPS Kota Tegal, <b>mohon luangkan waktu sejenak</b> untuk menekan tombol <span class="bg-amber-100 text-amber-900 font-bold px-1.5 py-0.5 rounded border border-amber-300">⭐ Beri Ulasan SKM</span> pada tiket Anda. Penilaian & masukan Anda sangat berharga bagi kami!
              </p>
            </div>
          </div>
        </div>

        <!-- Filter & Search Controls -->
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 space-y-3">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-1 overflow-x-auto pb-1 sm:pb-0" id="filter-tabs">
              <button type="button" class="btn btn-sm btn-primary bg-sky-600 border-sky-600 text-xs font-bold rounded-lg px-3 py-1.5 filter-btn active" data-filter="all">Semua Status</button>
              <button type="button" class="btn btn-sm btn-light border text-xs font-bold rounded-lg px-3 py-1.5 filter-btn" data-filter="Menunggu">Menunggu</button>
              <button type="button" class="btn btn-sm btn-light border text-xs font-bold rounded-lg px-3 py-1.5 filter-btn" data-filter="Dilayani">Dilayani</button>
              <button type="button" class="btn btn-sm btn-light border text-xs font-bold rounded-lg px-3 py-1.5 filter-btn" data-filter="Selesai">Selesai</button>
              <button type="button" class="btn btn-sm btn-light border text-xs font-bold rounded-lg px-3 py-1.5 filter-btn" data-filter="Terlewat">Terlewat</button>
              <button type="button" class="btn btn-sm btn-light border text-xs font-bold rounded-lg px-3 py-1.5 filter-btn" data-filter="Dibatalkan">Dibatalkan</button>
            </div>

            <div class="flex flex-wrap items-center gap-2">
              <!-- Filter Waktu -->
              <select id="user_filter_waktu" class="form-select form-select-sm text-xs font-bold rounded-xl text-sky-900 border-sky-300 bg-sky-50 w-36">
                <option value="all">🕒 Semua Waktu</option>
                <option value="today">☀️ Hari Ini</option>
                <option value="this_month">🗓️ Bulan Ini</option>
                <option value="custom">📆 Tanggal...</option>
              </select>

              <!-- Search Bar -->
              <div class="relative w-full sm:w-48">
                <span class="material-icons absolute left-3 top-2 text-slate-400 text-sm">search</span>
                <input type="text" id="search_ticket" class="form-control form-control-sm pl-9 text-xs rounded-xl" placeholder="Cari tiket...">
              </div>
            </div>
          </div>

          <!-- Custom Date Input Box -->
          <div id="user_custom_date_box" class="hidden flex-wrap items-center gap-3 bg-sky-50/80 p-3 rounded-xl border border-sky-200">
            <div class="flex items-center gap-1.5 text-xs font-bold text-sky-900">
              <span class="material-icons text-sm text-sky-600">event</span>
              <span>Rentang Tanggal:</span>
            </div>
            <div class="flex items-center gap-2">
              <label for="user_date_mulai" class="text-[11px] font-bold text-slate-600">Dari:</label>
              <input type="date" id="user_date_mulai" class="form-control form-control-sm text-xs rounded-lg w-36 border-sky-200">
            </div>
            <div class="flex items-center gap-2">
              <label for="user_date_selesai" class="text-[11px] font-bold text-slate-600">Sampai:</label>
              <input type="date" id="user_date_selesai" class="form-control form-control-sm text-xs rounded-lg w-36 border-sky-200">
            </div>
            <div class="flex items-center gap-2 ml-auto">
              <button type="button" id="btn_apply_user_date" class="btn btn-primary btn-sm text-xs font-bold px-3 py-1 rounded-lg bg-sky-600 border-sky-600 shadow-sm">Terapkan</button>
              <button type="button" id="btn_reset_user_date" class="btn btn-light btn-sm text-xs font-bold px-3 py-1 rounded-lg border">Reset</button>
            </div>
          </div>
        </div>

        <!-- Container Daftar Tiket & Riwayat -->
        <div id="tickets-container" class="space-y-4">
          <div class="text-center py-12 text-slate-400 space-y-2">
            <span class="spinner-border spinner-border-sm text-sky-600"></span>
            <p class="text-xs">Memuat data riwayat kunjungan...</p>
          </div>
        </div>

        <?php endif; ?>

      </div>

      <!-- Footer Component -->
      <?php include 'footer.php'; ?>

    </main>
  </div>

  <!-- Modal Display Tiket Digital & Print Area -->
  <div class="modal fade" id="modalTicket" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-2xl overflow-hidden border-none shadow-2xl">
        <div class="modal-header bg-[#003366] text-white p-4">
          <h5 class="modal-title font-bold text-base brand-font">Tiket Antrian Digital PST</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-6 text-center space-y-4" id="printable-ticket">
          <!-- Premium Ticket Number Header -->
          <div class="bg-gradient-to-r from-[#002B5B] via-[#003366] to-[#0284c7] rounded-2xl p-5 text-white shadow-xl text-center space-y-1 relative overflow-hidden border border-sky-400/20">
            <div class="absolute -right-8 -top-8 w-24 h-24 bg-sky-400/20 rounded-full blur-xl pointer-events-none"></div>
            <div class="text-[10px] font-black uppercase tracking-widest text-sky-200 opacity-90">BPS KOTA TEGAL • ANTREAN DIGITAL</div>
            <h2 class="text-4xl md:text-5xl font-black tracking-wider brand-font text-white drop-shadow-md py-1" id="ticket_number">KS-001</h2>
            <div class="text-[11px] font-medium text-sky-100/80">Pelayanan Statistik Terpadu</div>
          </div>
          
          <div class="py-2">
            <div id="qrcode_box" class="flex justify-center mx-auto"></div>
          </div>

          <div class="bg-slate-50 p-4 rounded-xl text-xs space-y-1.5 text-slate-700 text-left border">
            <div><b>Nama:</b> <span id="ticket_name">-</span></div>
            <div><b>Layanan:</b> <span id="ticket_service">-</span></div>
            <div><b>Tanggal:</b> <span id="ticket_date">-</span></div>
            <div><b>Jam Rencana:</b> <span id="ticket_time">-</span></div>

            <!-- Row Estimasi Waktu Tunggu Real-Time -->
            <div id="ticket_live_status_row" class="pt-2 mt-2 border-t border-slate-200 hidden space-y-1">
              <div class="font-extrabold text-sky-900 flex items-center gap-1">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Status Antrean Real-Time Hari Ini:</span>
              </div>
              <div class="text-[11px] text-slate-600">• Sedang Dipanggil: <b id="ticket_active_called" class="text-amber-700">-</b></div>
              <div class="text-[11px] text-slate-600">• Antrean Di Depan Anda: <b id="ticket_queue_ahead" class="text-sky-700">-</b></div>
              <div class="text-[11px] text-slate-600">• Estimasi Waktu Tunggu: <b id="ticket_estimated_wait" class="text-emerald-700">-</b></div>
            </div>
          </div>

          <p class="text-xs text-slate-500">Tunjukkan tiket/QR Code ini kepada petugas loket PST BPS Kota Tegal saat Anda tiba.</p>
        </div>
        <div class="modal-footer bg-slate-100 p-4 flex justify-between">
          <button type="button" class="btn btn-secondary text-xs" data-bs-dismiss="modal">Tutup</button>
          <button type="button" id="btn_print_ticket" class="btn btn-primary bg-[#003366] border-[#003366] text-xs flex items-center gap-1">
            <span class="material-icons text-sm">print</span> Cetak Tiket
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal SKM / Ulasan Kepuasan Layanan -->
  <div class="modal fade" id="modalSKM" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-2xl border-0 shadow-2xl overflow-hidden">
        <div class="modal-header bg-sky-900 text-white p-4">
          <h5 class="modal-title text-sm font-bold flex items-center gap-2">
            <span class="material-icons text-amber-400">star</span>
            <span>Penilaian Kepuasan Layanan (SKM)</span>
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form id="formSKM" class="modal-body p-6 space-y-4 text-slate-800">
          <input type="hidden" id="skm_antrian_id" value="">
          
          <div class="space-y-2 text-center">
            <label class="form-label text-xs font-bold text-slate-700 uppercase">Seberapa Puas Anda Dengan Layanan Kami? <span class="text-red-500">*</span></label>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2.5" id="skm-options-container">
              <label class="skm-card cursor-pointer border-2 border-slate-200 rounded-2xl p-3.5 text-center transition-all duration-200 relative overflow-hidden select-none" onclick="selectSKMCard(this)">
                <input type="radio" name="skm_pendapat" value="Sangat Puas" class="hidden">
                <div class="skm-badge absolute top-1.5 right-1.5 hidden bg-sky-600 text-white rounded-full w-5 h-5 flex items-center justify-center text-[10px] font-bold shadow-sm">✓</div>
                <div class="skm-emoji text-3xl mb-1 transition-all duration-200 inline-block">😍</div>
                <div class="text-xs font-bold text-slate-700 skm-label">Sangat Puas</div>
              </label>
              <label class="skm-card cursor-pointer border-2 border-slate-200 rounded-2xl p-3.5 text-center transition-all duration-200 relative overflow-hidden select-none" onclick="selectSKMCard(this)">
                <input type="radio" name="skm_pendapat" value="Puas" class="hidden">
                <div class="skm-badge absolute top-1.5 right-1.5 hidden bg-sky-600 text-white rounded-full w-5 h-5 flex items-center justify-center text-[10px] font-bold shadow-sm">✓</div>
                <div class="skm-emoji text-3xl mb-1 transition-all duration-200 inline-block">😊</div>
                <div class="text-xs font-bold text-slate-700 skm-label">Puas</div>
              </label>
              <label class="skm-card cursor-pointer border-2 border-slate-200 rounded-2xl p-3.5 text-center transition-all duration-200 relative overflow-hidden select-none" onclick="selectSKMCard(this)">
                <input type="radio" name="skm_pendapat" value="Cukup Puas" class="hidden">
                <div class="skm-badge absolute top-1.5 right-1.5 hidden bg-sky-600 text-white rounded-full w-5 h-5 flex items-center justify-center text-[10px] font-bold shadow-sm">✓</div>
                <div class="skm-emoji text-3xl mb-1 transition-all duration-200 inline-block">😐</div>
                <div class="text-xs font-bold text-slate-700 skm-label">Cukup Puas</div>
              </label>
              <label class="skm-card cursor-pointer border-2 border-slate-200 rounded-2xl p-3.5 text-center transition-all duration-200 relative overflow-hidden select-none" onclick="selectSKMCard(this)">
                <input type="radio" name="skm_pendapat" value="Tidak Puas" class="hidden">
                <div class="skm-badge absolute top-1.5 right-1.5 hidden bg-sky-600 text-white rounded-full w-5 h-5 flex items-center justify-center text-[10px] font-bold shadow-sm">✓</div>
                <div class="skm-emoji text-3xl mb-1 transition-all duration-200 inline-block">🙁</div>
                <div class="text-xs font-bold text-slate-700 skm-label">Tidak Puas</div>
              </label>
            </div>
          </div>

          <div class="space-y-1">
            <label class="form-label text-xs font-bold text-slate-700 uppercase">Saran & Masukan (Opsional)</label>
            <textarea id="skm_catatan" class="form-control text-xs rounded-xl" rows="3" placeholder="Tuliskan masukan atau kritik membangun untuk pelayanan PST BPS Kota Tegal..."></textarea>
          </div>

          <div class="pt-2 flex justify-end gap-2">
            <button type="button" class="btn btn-secondary text-xs rounded-xl" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary bg-sky-600 border-sky-600 font-bold text-xs rounded-xl px-4">Kirim Penilaian</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="js/app.js"></script>
  <script src="js/bukutamu.js"></script>
</body>
</html>
