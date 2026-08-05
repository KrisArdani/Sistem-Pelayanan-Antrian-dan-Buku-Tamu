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
          <a href="login.php" class="btn btn-primary btn-sm flex items-center gap-1 bg-[#003366] border-[#003366]">
            <span class="material-icons text-base">lock</span>
            <span class="text-xs font-semibold">Login Petugas</span>
          </a>
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
             CONNECTED PROGRESS TIMELINE STEPPER BAR
             ---------------------------------------------------- -->
        <div class="glass-card bg-white p-6 rounded-2xl border border-slate-200/90 shadow-md space-y-6">
          
          <!-- Header Bar -->
          <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
            <div>
              <h2 class="text-base md:text-lg font-black text-slate-900 brand-font flex items-center gap-2">
                <span class="material-icons text-sky-600 text-2xl">alt_route</span>
                <span>Alur & Langkah Pendaftaran Layanan PST</span>
              </h2>
              <p class="text-xs text-slate-500">Ikuti 5 langkah berurutan di bawah ini untuk melakukan reservasi antrean.</p>
            </div>
            <span class="text-xs font-extrabold text-sky-800 bg-sky-100 px-3.5 py-1.5 rounded-full border border-sky-300 shadow-sm shrink-0">Alur Berurutan</span>
          </div>

          <!-- Connected Timeline Container -->
          <div class="relative px-2 py-2">
            
            <!-- Continuous Connecting Background Line (Desktop) -->
            <div class="hidden lg:block absolute top-1/2 left-10 right-10 -translate-y-1/2 h-1.5 bg-gradient-to-r from-sky-500 via-amber-500 to-purple-600 rounded-full z-0 opacity-30"></div>

            <!-- 5 Steps Flow Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 relative z-10">
              
              <!-- STEP 1 -->
              <a href="register.php" class="group bg-white hover:bg-sky-50/80 p-3.5 rounded-2xl border-2 border-sky-500 shadow-md hover:shadow-lg transition-all duration-300 flex flex-col items-center text-center space-y-2 relative">
                <!-- Step Badge -->
                <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-sky-600 to-blue-500 text-white font-black text-sm flex items-center justify-center shadow-md shadow-sky-500/30 group-hover:scale-110 transition">
                  1
                </div>
                <div>
                  <div class="text-xs font-extrabold text-slate-900 group-hover:text-sky-700">1. Registrasi Akun</div>
                  <div class="text-[10px] font-semibold text-sky-700 mt-0.5">NIK KTP 16 Digit</div>
                </div>
                <div class="inline-flex items-center gap-1 text-[10px] font-bold text-sky-600 bg-sky-100/80 px-2 py-0.5 rounded-full mt-1">
                  <span>Daftar Akun</span>
                  <span class="material-icons text-xs">arrow_forward</span>
                </div>

                <!-- Floating Desktop Arrow Badge connecting to Step 2 -->
                <div class="hidden lg:flex absolute -right-4 top-1/2 -translate-y-1/2 z-20 w-8 h-8 rounded-full bg-sky-600 text-white shadow-lg items-center justify-center border-2 border-white font-extrabold">
                  <span class="material-icons text-sm">east</span>
                </div>
              </a>

              <!-- STEP 2 -->
              <a href="login.php" class="group bg-white hover:bg-blue-50/80 p-3.5 rounded-2xl border-2 border-blue-500 shadow-md hover:shadow-lg transition-all duration-300 flex flex-col items-center text-center space-y-2 relative">
                <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-black text-sm flex items-center justify-center shadow-md shadow-blue-500/30 group-hover:scale-110 transition">
                  2
                </div>
                <div>
                  <div class="text-xs font-extrabold text-slate-900 group-hover:text-blue-700">2. Masuk / Login</div>
                  <div class="text-[10px] font-semibold text-blue-700 mt-0.5">Username & Password</div>
                </div>
                <div class="inline-flex items-center gap-1 text-[10px] font-bold text-blue-600 bg-blue-100/80 px-2 py-0.5 rounded-full mt-1">
                  <span>Masuk Portal</span>
                  <span class="material-icons text-xs">arrow_forward</span>
                </div>

                <!-- Floating Desktop Arrow Badge connecting to Step 3 -->
                <div class="hidden lg:flex absolute -right-4 top-1/2 -translate-y-1/2 z-20 w-8 h-8 rounded-full bg-blue-600 text-white shadow-lg items-center justify-center border-2 border-white font-extrabold">
                  <span class="material-icons text-sm">east</span>
                </div>
              </a>

              <!-- STEP 3 -->
              <a href="antrian.php" class="group bg-white hover:bg-amber-50/80 p-3.5 rounded-2xl border-2 border-amber-500 shadow-md hover:shadow-lg transition-all duration-300 flex flex-col items-center text-center space-y-2 relative">
                <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-amber-500 to-orange-500 text-white font-black text-sm flex items-center justify-center shadow-md shadow-amber-500/30 group-hover:scale-110 transition">
                  3
                </div>
                <div>
                  <div class="text-xs font-extrabold text-slate-900 group-hover:text-amber-700">3. Pesan Antrean</div>
                  <div class="text-[10px] font-semibold text-amber-700 mt-0.5">Jadwal & Layanan</div>
                </div>
                <div class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-700 bg-amber-100/80 px-2 py-0.5 rounded-full mt-1">
                  <span>Pesan Layanan</span>
                  <span class="material-icons text-xs">arrow_forward</span>
                </div>

                <!-- Floating Desktop Arrow Badge connecting to Step 4 -->
                <div class="hidden lg:flex absolute -right-4 top-1/2 -translate-y-1/2 z-20 w-8 h-8 rounded-full bg-amber-500 text-white shadow-lg items-center justify-center border-2 border-white font-extrabold">
                  <span class="material-icons text-sm">east</span>
                </div>
              </a>

              <!-- STEP 4 -->
              <a href="bukutamu.php" class="group bg-white hover:bg-emerald-50/80 p-3.5 rounded-2xl border-2 border-emerald-500 shadow-md hover:shadow-lg transition-all duration-300 flex flex-col items-center text-center space-y-2 relative">
                <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-emerald-600 to-teal-500 text-white font-black text-sm flex items-center justify-center shadow-md shadow-emerald-500/30 group-hover:scale-110 transition">
                  4
                </div>
                <div>
                  <div class="text-xs font-extrabold text-slate-900 group-hover:text-emerald-700">4. Tiket Digital QR</div>
                  <div class="text-[10px] font-semibold text-emerald-700 mt-0.5">Resi Tiket 2 Hal</div>
                </div>
                <div class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-100/80 px-2 py-0.5 rounded-full mt-1">
                  <span>Lihat Tiket</span>
                  <span class="material-icons text-xs">arrow_forward</span>
                </div>

                <!-- Floating Desktop Arrow Badge connecting to Step 5 -->
                <div class="hidden lg:flex absolute -right-4 top-1/2 -translate-y-1/2 z-20 w-8 h-8 rounded-full bg-emerald-600 text-white shadow-lg items-center justify-center border-2 border-white font-extrabold">
                  <span class="material-icons text-sm">east</span>
                </div>
              </a>

              <!-- STEP 5 -->
              <a href="bukutamu.php" class="group bg-white hover:bg-purple-50/80 p-3.5 rounded-2xl border-2 border-purple-500 shadow-md hover:shadow-lg transition-all duration-300 flex flex-col items-center text-center space-y-2 relative">
                <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-purple-600 to-indigo-600 text-white font-black text-sm flex items-center justify-center shadow-md shadow-purple-500/30 group-hover:scale-110 transition">
                  5
                </div>
                <div>
                  <div class="text-xs font-extrabold text-slate-900 group-hover:text-purple-700">5. Datang Ke Loket</div>
                  <div class="text-[10px] font-semibold text-purple-700 mt-0.5">Scan QR di PST</div>
                </div>
                <div class="inline-flex items-center gap-1 text-[10px] font-bold text-purple-700 bg-purple-100/80 px-2 py-0.5 rounded-full mt-1">
                  <span>Petunjuk Loket</span>
                  <span class="material-icons text-xs">arrow_forward</span>
                </div>
              </a>

            </div>
          </div>
        </div>

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
      <a href="index.php" class="bps-nav-item active"><span class="material-icons">home</span> Beranda</a>
      <a href="bukutamu.php" class="bps-nav-item"><span class="material-icons">groups</span> Buku Tamu</a>
      <a href="antrian.php" class="bps-nav-item"><span class="material-icons">summarize</span> Daftar Antrian</a>
      <a href="login.php" class="bps-nav-item"><span class="material-icons">login</span> Login Petugas</a>
    </div>
  </div>

  <!-- Script Helpers -->
  <script src="js/app.js"></script>
  <script src="js/tts.js"></script>
</body>
</html>
