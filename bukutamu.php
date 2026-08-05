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
  <style>
    @keyframes emojiPulseHeart {
      0%, 100% { transform: scale(1.3); }
      50% { transform: scale(1.6) rotate(-8deg); }
    }
    @keyframes emojiBounceNod {
      0%, 100% { transform: scale(1.3) translateY(0); }
      50% { transform: scale(1.45) translateY(-8px); }
    }
    @keyframes emojiSwaySide {
      0%, 100% { transform: scale(1.3) rotate(0deg); }
      25% { transform: scale(1.4) rotate(-14deg); }
      75% { transform: scale(1.4) rotate(14deg); }
    }
    @keyframes emojiShakeSide {
      0%, 100% { transform: scale(1.3) translateX(0); }
      20% { transform: scale(1.35) translateX(-5px); }
      40% { transform: scale(1.35) translateX(5px); }
      60% { transform: scale(1.35) translateX(-4px); }
      80% { transform: scale(1.35) translateX(4px); }
    }

    .anim-heartbeat {
      animation: emojiPulseHeart 0.75s infinite ease-in-out !important;
      display: inline-block !important;
    }
    .anim-nod {
      animation: emojiBounceNod 0.8s infinite ease-in-out !important;
      display: inline-block !important;
    }
    .anim-sway {
      animation: emojiSwaySide 1.0s infinite ease-in-out !important;
      display: inline-block !important;
    }
    .anim-shake {
      animation: emojiShakeSide 0.65s infinite ease-in-out !important;
      display: inline-block !important;
    }
  </style>
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
            <div class="text-xs font-semibold text-slate-700 flex items-center gap-2 bg-slate-100 px-3 py-1.5 rounded-full">
              <span class="material-icons text-sm text-sky-600">account_circle</span>
              <span><?php echo htmlspecialchars($userName); ?></span>
            </div>
          <?php else: ?>
            <a href="login.php" class="btn btn-sm btn-outline-primary rounded-full text-xs font-bold">Masuk / Login</a>
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

          <div class="bg-slate-50 p-4 rounded-xl text-xs space-y-1 text-slate-700 text-left border">
            <div><b>Nama:</b> <span id="ticket_name">-</span></div>
            <div><b>Layanan:</b> <span id="ticket_service">-</span></div>
            <div><b>Tanggal:</b> <span id="ticket_date">-</span></div>
            <div><b>Jam Rencana:</b> <span id="ticket_time">-</span></div>
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
  <script>
  document.addEventListener('DOMContentLoaded', () => {
    let allTickets = [];
    let currentFilter = 'all';

    const container = document.getElementById('tickets-container');
    const searchInput = document.getElementById('search_ticket');

    async function loadTickets() {
      try {
        const res = await fetch('api.php?action=get_my_antrian');
        const json = await res.json();
        if (json.status === 'success') {
          allTickets = json.data || [];
          renderTickets();
        } else {
          container.innerHTML = `
            <div class="p-8 bg-white rounded-2xl text-center space-y-2 border border-slate-200">
              <span class="material-icons text-amber-500 text-3xl">info</span>
              <p class="text-xs text-slate-600 font-semibold">${json.message || 'Gagal memuat data antrean.'}</p>
            </div>
          `;
        }
      } catch (err) {
        container.innerHTML = `
          <div class="p-8 bg-white rounded-2xl text-center space-y-2 border border-slate-200">
            <span class="material-icons text-red-500 text-3xl">wifi_off</span>
            <p class="text-xs text-slate-600 font-semibold">Gagal terhubung ke server.</p>
          </div>
        `;
      }
    }

    function renderTickets() {
      let filtered = allTickets;

      // Filter Status
      if (currentFilter !== 'all') {
        filtered = filtered.filter(t => t.status === currentFilter);
      }

      // Filter Berdasarkan Waktu / Tanggal
      const userWaktuSelect = document.getElementById('user_filter_waktu');
      const waktuVal = userWaktuSelect ? userWaktuSelect.value : 'all';
      const now = new Date();
      const year = now.getFullYear();
      const month = String(now.getMonth() + 1).padStart(2, '0');
      const day = String(now.getDate()).padStart(2, '0');
      const todayStr = `${year}-${month}-${day}`;
      const thisMonthStr = `${year}-${month}`;

      if (waktuVal === 'today') {
        filtered = filtered.filter(t => t.tanggal && t.tanggal === todayStr);
      } else if (waktuVal === 'this_month') {
        filtered = filtered.filter(t => t.tanggal && t.tanggal.startsWith(thisMonthStr));
      } else if (waktuVal === 'custom') {
        const userDateMulai = document.getElementById('user_date_mulai');
        const userDateSelesai = document.getElementById('user_date_selesai');
        const startDate = userDateMulai ? userDateMulai.value : '';
        const endDate = userDateSelesai ? userDateSelesai.value : '';

        if (startDate) {
          filtered = filtered.filter(t => t.tanggal && t.tanggal >= startDate);
        }
        if (endDate) {
          filtered = filtered.filter(t => t.tanggal && t.tanggal <= endDate);
        }
      }

      // Search Query
      const q = searchInput ? searchInput.value.toLowerCase().trim() : '';
      if (q) {
        filtered = filtered.filter(t => 
          (t.nomor && t.nomor.toLowerCase().includes(q)) ||
          (t.kode_antrian && t.kode_antrian.toLowerCase().includes(q)) ||
          (t.layanan && t.layanan.toLowerCase().includes(q)) ||
          (t.data_diinginkan && t.data_diinginkan.toLowerCase().includes(q))
        );
      }

      if (filtered.length === 0) {
        container.innerHTML = `
          <div class="p-10 bg-white rounded-2xl text-center space-y-3 border border-slate-200">
            <span class="material-icons text-slate-300 text-4xl">inbox</span>
            <h4 class="text-sm font-bold text-slate-700">Belum Ada Tiket Kunjungan</h4>
            <p class="text-xs text-slate-400 max-w-sm mx-auto">Anda belum memiliki reservasi antrean dengan status ini. Silakan buat reservasi baru.</p>
            <a href="antrian.php" class="btn btn-sky btn-sm bg-sky-600 text-white font-bold text-xs px-4 py-2 rounded-xl border-none">Buat Reservasi Baru</a>
          </div>
        `;
        return;
      }

      container.innerHTML = filtered.map(t => {
        let statusBadge = '';
        if (t.status === 'Menunggu') {
          statusBadge = '<span class="badge bg-amber-100 text-amber-800 border border-amber-300 px-3 py-1 text-[11px] font-bold rounded-full">⏳ Menunggu</span>';
        } else if (t.status === 'Dilayani') {
          statusBadge = '<span class="badge bg-sky-100 text-sky-800 border border-sky-300 px-3 py-1 text-[11px] font-bold rounded-full">🗣️ Sedang Dilayani</span>';
        } else if (t.status === 'Selesai') {
          statusBadge = '<span class="badge bg-emerald-100 text-emerald-800 border border-emerald-300 px-3 py-1 text-[11px] font-bold rounded-full">✅ Selesai</span>';
        } else if (t.status === 'Terlewat') {
          statusBadge = '<span class="badge bg-rose-100 text-rose-800 border border-rose-300 px-3 py-1 text-[11px] font-bold rounded-full">⏭️ Terlewat</span>';
        } else {
          statusBadge = '<span class="badge bg-slate-100 text-slate-600 border border-slate-300 px-3 py-1 text-[11px] font-bold rounded-full">❌ Dibatalkan</span>';
        }

        const formattedDate = formatTanggalIndo(t.tanggal + ' ' + t.waktu);

        return `
          <div class="p-5 md:p-6 bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b pb-4 border-slate-100">
              <div class="flex items-center gap-3.5">
                <div class="px-4 py-2.5 bg-gradient-to-br from-[#002B5B] via-[#003366] to-[#0284c7] text-white rounded-2xl border border-sky-400/30 shadow-md flex flex-col items-center justify-center shrink-0 min-w-[95px] relative overflow-hidden">
                  <span class="text-[9px] font-black tracking-widest uppercase text-sky-200 leading-none mb-1 opacity-90">NO. ANTREAN</span>
                  <span class="text-xl md:text-2xl font-black brand-font text-white tracking-wider leading-none drop-shadow">${escapeHtml(t.nomor)}</span>
                </div>
                <div>
                  <div class="text-[11px] font-extrabold text-sky-700 uppercase tracking-wider flex items-center gap-1">
                    <span class="material-icons text-xs text-sky-600">confirmation_number</span>
                    <span>Kode Tiket: ${escapeHtml(t.kode_antrian)}</span>
                  </div>
                  <h3 class="text-base font-bold text-slate-900 mt-0.5">${escapeHtml(t.layanan)}</h3>
                </div>
              </div>
              <div class="shrink-0 flex items-center gap-2">
                ${statusBadge}
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs text-slate-600 bg-slate-50 p-4 rounded-xl border border-slate-100">
              <div><b>Jadwal Rencana:</b> ${formattedDate}</div>
              <div><b>Fasilitas:</b> ${escapeHtml(t.fasilitas || '-')}</div>
              <div><b>Tujuan Pemanfaatan:</b> ${escapeHtml(t.pemanfaatan || '-')}</div>
              <div><b>Rincian Data:</b> ${escapeHtml(t.data_diinginkan || '-')}</div>
              <div><b>Evaluasi Pembangunan (Monev):</b> ${escapeHtml(t.monev || 'Ya')}</div>
              ${t.pendapat ? `<div><b>Ulasan SKM Anda:</b> <span class="font-bold text-sky-700">${escapeHtml(t.pendapat)}</span> ${t.catatan ? `("${escapeHtml(t.catatan)}")` : ''}</div>` : ''}
            </div>

            ${!t.pendapat && t.status === 'Selesai' ? `
              <div class="p-2.5 bg-gradient-to-r from-amber-500/10 via-amber-400/5 to-amber-500/10 border border-amber-300 rounded-xl text-xs text-amber-900 flex items-center gap-1.5 font-semibold">
                <span class="material-icons text-amber-600 text-sm shrink-0">stars</span>
                <span>Pelayanan Anda telah selesai. Mohon luangkan waktu sejenak untuk menekan tombol <b>⭐ Beri Ulasan SKM</b> di bawah.</span>
              </div>
            ` : ''}

            <div class="flex flex-wrap items-center justify-end gap-2 pt-1">
              <button type="button" onclick="showTicketModal('${t.nomor}', '${t.kode_antrian}', '${escapeHtml(t.nama)}', '${escapeHtml(t.layanan)}', '${t.tanggal}', '${t.waktu}')" class="btn btn-sm btn-primary bg-sky-600 border-sky-600 font-bold text-xs rounded-xl flex items-center gap-1">
                <span class="material-icons text-sm">qr_code_2</span>
                <span>Lihat & Cetak Tiket</span>
              </button>

              ${t.status === 'Selesai' ? `
                <button type="button" onclick="openSKMModal(${t.id}, '${t.pendapat || ''}', '${escapeHtml(t.catatan || '')}')" class="btn btn-sm ${t.pendapat ? 'btn-outline-sky border-sky-300 text-sky-700 font-semibold' : 'btn-warning bg-amber-500 text-slate-900 border-amber-400 font-extrabold shadow-sm'} text-xs rounded-xl flex items-center gap-1">
                  <span class="material-icons text-sm">${t.pendapat ? 'edit_note' : 'star'}</span>
                  <span>${t.pendapat ? 'Edit Ulasan SKM' : '⭐ Beri Ulasan SKM'}</span>
                </button>
              ` : ''}

              ${t.status === 'Menunggu' ? `
                <button type="button" onclick="confirmCancelTicket(${t.id})" class="btn btn-sm btn-outline-danger font-semibold text-xs rounded-xl flex items-center gap-1">
                  <span class="material-icons text-sm">cancel</span>
                  <span>Batalkan</span>
                </button>
              ` : ''}
            </div>
          </div>
        `;
      }).join('');
    }

    // Filter Buttons Listener
    document.querySelectorAll('.filter-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.filter-btn').forEach(b => {
          b.classList.remove('btn-primary', 'bg-sky-600', 'border-sky-600', 'active');
          b.classList.add('btn-light', 'border');
        });
        btn.classList.remove('btn-light', 'border');
        btn.classList.add('btn-primary', 'bg-sky-600', 'border-sky-600', 'active');

        currentFilter = btn.getAttribute('data-filter');
        renderTickets();
      });
    });

    // Filter Waktu Listener
    const userWaktuSelect = document.getElementById('user_filter_waktu');
    const userCustomBox = document.getElementById('user_custom_date_box');
    const btnApplyUserDate = document.getElementById('btn_apply_user_date');
    const btnResetUserDate = document.getElementById('btn_reset_user_date');
    const userDateMulai = document.getElementById('user_date_mulai');
    const userDateSelesai = document.getElementById('user_date_selesai');

    if (userWaktuSelect) {
      userWaktuSelect.addEventListener('change', () => {
        if (userWaktuSelect.value === 'custom') {
          if (userCustomBox) userCustomBox.classList.remove('hidden'), userCustomBox.classList.add('flex');
        } else {
          if (userCustomBox) userCustomBox.classList.add('hidden'), userCustomBox.classList.remove('flex');
          renderTickets();
        }
      });
    }

    if (btnApplyUserDate) {
      btnApplyUserDate.addEventListener('click', () => renderTickets());
    }

    if (btnResetUserDate) {
      btnResetUserDate.addEventListener('click', () => {
        if (userWaktuSelect) userWaktuSelect.value = 'all';
        if (userDateMulai) userDateMulai.value = '';
        if (userDateSelesai) userDateSelesai.value = '';
        if (userCustomBox) userCustomBox.classList.add('hidden'), userCustomBox.classList.remove('flex');
        renderTickets();
      });
    }

    if (searchInput) searchInput.addEventListener('input', renderTickets);

    loadTickets();
  });

  // Show Ticket QR Modal
  function showTicketModal(nomor, kode, nama, layanan, tanggal, waktu) {
    document.getElementById('ticket_number').textContent = nomor;
    document.getElementById('ticket_name').textContent = nama;
    document.getElementById('ticket_service').textContent = layanan;
    document.getElementById('ticket_date').textContent = tanggal;
    document.getElementById('ticket_time').textContent = waktu + ' WIB';

    const qrContainer = document.getElementById('qrcode_box');
    qrContainer.innerHTML = '';
    if (typeof QRCode !== 'undefined') {
      new QRCode(qrContainer, {
        text: JSON.stringify({ id: kode, nomor: nomor, nama: nama }),
        width: 140,
        height: 140,
        colorDark: '#003366',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.H
      });
    }

    const bsModal = new bootstrap.Modal(document.getElementById('modalTicket'));
    bsModal.show();
  }

  // Confirm Cancel Ticket
  function confirmCancelTicket(id) {
    Swal.fire({
      title: 'Batalkan Reservasi Antrean?',
      text: 'Apakah Anda yakin ingin membatalkan reservasi tiket antrean ini?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, Batalkan',
      cancelButtonText: 'Batal',
      confirmButtonColor: '#d33'
    }).then(async (res) => {
      if (res.isConfirmed) {
        try {
          const fd = new FormData();
          fd.append('id', id);
          fd.append('csrf_token', getCsrfToken());

          const resp = await fetch('api.php?action=cancel_antrian', { method: 'POST', body: fd });
          const json = await resp.json();
          if (json.status === 'success') {
            Swal.fire('Berhasil', 'Reservasi antrean berhasil dibatalkan.', 'success').then(() => {
              window.location.reload();
            });
          } else {
            Swal.fire('Gagal', json.message || 'Gagal membatalkan antrean.', 'error');
          }
        } catch (e) {
          Swal.fire('Error', 'Gagal terhubung ke server.', 'error');
        }
      }
    });
  }

  // Open SKM Modal
  function openSKMModal(id, currentPendapat, currentCatatan) {
    document.getElementById('skm_antrian_id').value = id;
    document.getElementById('skm_catatan').value = currentCatatan || '';

    const targetVal = currentPendapat || 'Sangat Puas';
    const radio = document.querySelector(`input[name="skm_pendapat"][value="${targetVal}"]`);
    if (radio) {
      radio.checked = true;
      if (radio.closest('.skm-card')) {
        selectSKMCard(radio.closest('.skm-card'));
      } else {
        updateSKMVisualState();
      }
    } else {
      updateSKMVisualState();
    }

    const bsModal = new bootstrap.Modal(document.getElementById('modalSKM'));
    bsModal.show();
  }

  function selectSKMCard(el) {
    const radio = el.querySelector('input[type="radio"]');
    if (radio) radio.checked = true;

    document.querySelectorAll('.skm-card').forEach(card => {
      const input = card.querySelector('input[type="radio"]');
      const badge = card.querySelector('.skm-badge');
      const emoji = card.querySelector('.skm-emoji');
      const label = card.querySelector('.skm-label');

      if (emoji) {
        emoji.className = 'skm-emoji text-3xl mb-1 transition-all duration-200 inline-block';
      }

      if (input && input.checked) {
        card.className = 'skm-card cursor-pointer border-2 border-sky-600 bg-sky-100/90 shadow-md ring-2 ring-sky-500/30 scale-105 rounded-2xl p-3.5 text-center transition-all duration-200 relative overflow-hidden select-none';
        if (badge) badge.classList.remove('hidden');
        if (label) label.className = 'text-xs font-extrabold text-sky-900 skm-label';

        if (emoji) {
          const val = input.value;
          if (val === 'Sangat Puas') emoji.classList.add('anim-heartbeat');
          else if (val === 'Puas') emoji.classList.add('anim-nod');
          else if (val === 'Cukup Puas') emoji.classList.add('anim-sway');
          else if (val === 'Tidak Puas') emoji.classList.add('anim-shake');
          else emoji.classList.add('anim-heartbeat');
        }
      } else {
        card.className = 'skm-card cursor-pointer border-2 border-slate-200 rounded-2xl p-3.5 text-center transition-all duration-200 relative overflow-hidden select-none hover:border-sky-400 hover:bg-sky-50/50';
        if (badge) badge.classList.add('hidden');
        if (label) label.className = 'text-xs font-bold text-slate-700 skm-label';
      }
    });
  }

  function updateSKMVisualState() {
    const checkedRadio = document.querySelector('input[name="skm_pendapat"]:checked');
    if (checkedRadio && checkedRadio.closest('.skm-card')) {
      selectSKMCard(checkedRadio.closest('.skm-card'));
    }
  }

  // Submit SKM Rating Handler
  document.getElementById('formSKM').addEventListener('submit', async function(e) {
    e.preventDefault();
    const id = document.getElementById('skm_antrian_id').value;
    const pendapat = document.querySelector('input[name="skm_pendapat"]:checked').value;
    const catatan = document.getElementById('skm_catatan').value.trim();

    try {
      const fd = new FormData();
      fd.append('id', id);
      fd.append('pendapat', pendapat);
      fd.append('catatan', catatan);
      fd.append('csrf_token', getCsrfToken());

      const res = await fetch('api.php?action=submit_skm', { method: 'POST', body: fd });
      const json = await res.json();
      if (json.status === 'success') {
        Swal.fire('Terima Kasih', json.message, 'success').then(() => {
          window.location.reload();
        });
      } else {
        Swal.fire('Gagal', json.message || 'Gagal menyimpan penilaian.', 'error');
      }
    } catch (err) {
      Swal.fire('Error', 'Gagal terhubung ke server.', 'error');
    }
  });

  // Print Ticket Handler
  document.getElementById('btn_print_ticket').addEventListener('click', () => {
    window.print();
  });
  </script>
</body>
</html>
