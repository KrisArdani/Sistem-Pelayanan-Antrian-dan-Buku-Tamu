<?php
// SPST BPS Kota Tegal - Sistem Pelayanan Statistik Terpadu
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
$activeMenu = 'home';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
  <title>SPST - Sistem Pelayanan Statistik Terpadu BPS Kota Tegal</title>

  <!-- Tailwind CSS Play CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Bootstrap 5.3.8 CSS & Bundle -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Icons & Fonts -->
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- Custom CSS -->
  <link rel="stylesheet" href="css/custom.css">
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
          <!-- Mobile Toggle Button -->
          <button class="lg:hidden p-2 rounded-lg bg-slate-100 text-slate-700" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
            <span class="material-icons">menu</span>
          </button>
          <span class="text-xs font-bold px-3 py-1 bg-sky-100 text-sky-700 rounded-full uppercase tracking-wider">Pelayanan Statistik Terpadu</span>
        </div>

        <div class="flex items-center gap-3">
          <!-- TTS Toggle Button -->
          <button id="btn-tts-toggle" class="btn btn-secondary btn-sm flex items-center gap-1 shadow-sm" title="Aktifkan Pembacaan Suara (Text-To-Speech)">
            <span class="material-icons text-base">volume_off</span>
            <span class="hidden sm:inline text-xs font-semibold">Suara</span>
          </button>

          <?php if (isset($_SESSION['user_id'])): ?>
            <div class="flex items-center gap-2 px-3 py-1.5 bg-sky-50 border border-sky-200 rounded-full text-xs font-semibold text-slate-700 shadow-sm">
              <span class="material-icons text-sky-600 text-sm">account_circle</span>
              <span class="hidden sm:inline">Pengunjung: <b class="text-sky-900"><?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?></b></span>
              <span class="sm:hidden font-bold text-sky-900"><?php echo htmlspecialchars(explode(' ', $_SESSION['user_name'] ?? '')[0]); ?></span>
            </div>
            <a href="logout.php" class="btn btn-outline-danger btn-sm text-xs flex items-center gap-1 border-rose-300 text-rose-700 hover:bg-rose-600 hover:text-white font-bold px-3 rounded-xl shadow-sm" title="Keluar dari Akun">
              <span class="material-icons text-sm">logout</span>
              <span class="hidden sm:inline">Keluar</span>
            </a>
          <?php else: ?>
            <a href="login.php" class="btn btn-primary btn-sm flex items-center gap-1 bg-[#003366] border-[#003366]">
              <span class="material-icons text-base">login</span>
              <span class="text-xs font-semibold">Masuk / Login</span>
            </a>
          <?php endif; ?>
        </div>
      </header>

      <!-- Hero Section & Main Cards -->
      <div class="p-6 md:p-10 space-y-8 max-w-6xl mx-auto w-full">
        
        <!-- Welcome Hero Banner -->
        <div class="bg-gradient-to-r from-[#003366] via-[#004080] to-[#00A3E0] rounded-3xl p-8 md:p-12 text-white shadow-xl relative overflow-hidden">
          <div class="relative z-10 space-y-4 max-w-2xl">
            <span class="inline-block bg-white/20 backdrop-blur-md px-3 py-1 rounded-full text-xs font-semibold text-sky-100 uppercase tracking-widest">PST BPS KOTA TEGAL</span>
            <h1 class="text-3xl md:text-5xl font-extrabold leading-tight brand-font">Sistem Pelayanan Statistik Terpadu</h1>
            <p class="text-sky-100 text-sm md:text-base leading-relaxed">
              Selamat datang di portal resmi pelayanan statistik BPS Kota Tegal. Dapatkan layanan konsultasi statistik, data publikasi, serta reservasi antrian secara cepat, transparan, dan nyaman.
            </p>

            <div class="pt-4 flex flex-wrap gap-4">
              <a href="antrian.php" class="btn btn-warning btn-lg px-8 py-3.5 font-extrabold text-white bg-[#FF6B35] hover:bg-[#E85A24] border-none shadow-xl flex items-center gap-2.5 rounded-2xl hover:scale-105 transition-all">
                <span class="material-icons text-2xl">confirmation_number</span>
                <span class="text-base">Reservasi Sekarang</span>
              </a>
            </div>
          </div>
          <div class="absolute -right-10 -bottom-10 opacity-10 text-white pointer-events-none">
            <span class="material-icons" style="font-size: 260px;">analytics</span>
          </div>
        </div>

        <!-- ----------------------------------------------------
             DYNAMIC PROGRESS TIMELINE STEPPER BAR
             ---------------------------------------------------- -->
        <?php include __DIR__ . '/stepper.php'; ?>

        <!-- 2 Main Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          
          <!-- Card 1: Registrasi Pengunjung -->
          <div class="glass-card p-8 flex flex-col justify-between space-y-6">
            <div class="space-y-4">
              <div class="w-14 h-14 bg-sky-100 text-sky-700 rounded-2xl flex items-center justify-center font-bold">
                <span class="material-icons text-3xl">how_to_reg</span>
              </div>
              <h2 class="text-2xl font-bold text-slate-900 brand-font">Registrasi Akun Pengunjung</h2>
              <p class="text-slate-600 text-sm leading-relaxed">
                Daftarkan NIK KTP & identitas Anda <b>cukup 1x</b>. Dapatkan akses cepat untuk memesan antrean dan konsultasi statistik tanpa mengulang pengisian profil.
              </p>
            </div>
            <a href="register.php" class="btn btn-primary w-full py-3 bg-[#003366] border-[#003366] font-semibold flex items-center justify-center gap-2">
              <span>Daftar Akun Sekarang</span>
              <span class="material-icons text-sm">arrow_forward</span>
            </a>
          </div>

          <!-- Card 2: Reservasi Antrean & Layanan -->
          <div class="glass-card p-8 flex flex-col justify-between space-y-6">
            <div class="space-y-4">
              <div class="w-14 h-14 bg-amber-100 text-amber-700 rounded-2xl flex items-center justify-center font-bold">
                <span class="material-icons text-3xl">confirmation_number</span>
              </div>
              <h2 class="text-2xl font-bold text-slate-900 brand-font">Reservasi Antrean & Layanan</h2>
              <p class="text-slate-600 text-sm leading-relaxed">
                Pilih tanggal kedatangan dan tuliskan rincian data statistik yang Anda cari sebelum berkunjung ke kantor PST BPS Kota Tegal.
              </p>
            </div>
            <a href="antrian.php" class="btn btn-warning w-full py-3 bg-[#FF6B35] text-white border-none font-semibold flex items-center justify-center gap-2">
              <span>Ambil Tiket Antrian</span>
              <span class="material-icons text-sm">qr_code_2</span>
            </a>
          </div>

        </div>

      </div>

      <!-- Include Footer Component (Lengkap dengan Profil, Link, Medsos, & Floating Widgets) -->
      <?php include 'footer.php'; ?>

    </main>
  </div>

  <!-- Mobile Offcanvas Sidebar -->
  <div class="offcanvas offcanvas-start bps-sidebar" tabindex="-1" id="mobileSidebar">
    <div class="offcanvas-header border-b border-slate-700">
      <h5 class="offcanvas-title text-white font-bold brand-font">SPST BPS KOTA TEGAL</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6 space-y-4">
      <a href="index.php" class="bps-nav-item active"><span class="material-icons">home</span> Beranda Utama</a>
      <a href="antrian.php" class="bps-nav-item"><span class="material-icons">confirmation_number</span> Reservasi Antrean</a>
      <a href="bukutamu.php" class="bps-nav-item"><span class="material-icons">receipt_long</span> Riwayat & Tiket Saya</a>
      
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="logout.php" class="bps-nav-item text-rose-400 hover:text-rose-300 font-bold border border-rose-500/20 rounded-xl my-2">
          <span class="material-icons text-rose-400">logout</span> Keluar / Logout Sesi
        </a>
      <?php else: ?>
        <a href="login.php" class="bps-nav-item"><span class="material-icons">login</span> Masuk / Login</a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Script Helpers -->
  <script src="js/app.js"></script>
  <script src="js/tts.js"></script>
</body>
</html>
