<?php
$isUserLoggedIn = isset($_SESSION['user_id']);
$sidebarUserName = $_SESSION['user_name'] ?? 'Pengunjung';
$sidebarUserNik = $_SESSION['user_nik'] ?? '';
$sidebarUserRole = $_SESSION['user_role'] ?? 'guest';
?>
<!-- Sidebar Navigation Left (Fixed Desktop) -->
<aside class="w-72 bps-sidebar hidden lg:flex flex-col justify-between p-6 fixed inset-y-0 left-0 z-30 overflow-y-auto">
  <div>
    <!-- Logo & Title Header -->
    <div class="flex items-center gap-3 mb-6 pb-6 border-b border-slate-700/60">
      <img src="img/Logo_BPS.png" alt="Logo BPS Kota Tegal" class="w-12 h-12 object-contain filter drop-shadow">
      <div>
        <h1 class="text-white font-extrabold text-lg tracking-wide leading-tight brand-font">SPST</h1>
        <p class="text-[11px] text-sky-400 font-semibold tracking-wider uppercase">BPS KOTA TEGAL</p>
      </div>
    </div>

    <!-- Kategori 1: Alur Utama Pengunjung -->
    <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-3 mb-2">Alur Utama Pengunjung</div>
    <nav class="space-y-1">
      <a href="index.php" class="bps-nav-item <?php echo ($activeMenu == 'home') ? 'active' : ''; ?>">
        <span class="material-icons">home</span>
        <span>Beranda</span>
      </a>

      <a href="register.php" class="bps-nav-item <?php echo ($activeMenu == 'register') ? 'active' : ''; ?>">
        <span class="material-icons">how_to_reg</span>
        <span>Daftar Akun Pengunjung</span>
      </a>

      <a href="antrian.php" class="bps-nav-item <?php echo ($activeMenu == 'antrian') ? 'active' : ''; ?>">
        <span class="material-icons">confirmation_number</span>
        <span>Reservasi Antrean & Layanan</span>
      </a>

      <a href="bukutamu.php" class="bps-nav-item <?php echo ($activeMenu == 'bukutamu') ? 'active' : ''; ?>">
        <span class="material-icons">receipt_long</span>
        <span>Riwayat & Tiket Saya</span>
      </a>

      <!-- Kategori 2: Portal Layanan Data & Konsultasi -->
      <div class="pt-4 pb-2 text-[10px] font-bold text-slate-500 uppercase tracking-widest px-3">Layanan & Portal Data</div>

      <!-- Dropdown Ruang Konsultasi -->
      <div class="space-y-1">
        <button class="bps-nav-item w-full flex items-center justify-between" type="button" data-bs-toggle="collapse" data-bs-target="#menuKonsultasi">
          <div class="flex items-center gap-3">
            <span class="material-icons">person_pin</span>
            <span>Ruang Konsultasi</span>
          </div>
          <span class="material-icons text-sm">expand_more</span>
        </button>
        <div class="collapse pl-9 space-y-1" id="menuKonsultasi">
          <a href="#" class="block py-1.5 px-3 text-xs text-slate-400 hover:text-white rounded-lg transition">Layanan Konsultasi Statistik</a>
          <a href="#" class="block py-1.5 px-3 text-xs text-slate-400 hover:text-white rounded-lg transition">Ruang Chat Admin</a>
          <a href="#" class="block py-1.5 px-3 text-xs text-slate-400 hover:text-white rounded-lg transition">Halaman Chat Chatbot</a>
        </div>
      </div>

      <a href="#" class="bps-nav-item opacity-75">
        <span class="material-icons">dashboard</span>
        <span>Ruang Data</span>
      </a>

      <a href="#" class="bps-nav-item opacity-75">
        <span class="material-icons">insert_chart</span>
        <span>Ruang Infografis</span>
      </a>

      <a href="webgis.php" class="bps-nav-item <?php echo ($activeMenu == 'webgis') ? 'active' : ''; ?>">
        <span class="material-icons">map</span>
        <span>Webgis Kota Tegal</span>
      </a>

      <?php if (in_array($sidebarUserRole, ['admin', 'kepala', 'petugas'])): ?>
      <a href="admin/bps_data.php" class="bps-nav-item <?php echo ($activeMenu == 'bps_data') ? 'active' : ''; ?>">
        <span class="material-icons text-amber-400">cloud_sync</span>
        <span>Integrasi Web API BPS</span>
      </a>
      <a href="display.php" target="_blank" class="bps-nav-item">
        <span class="material-icons text-amber-400">tv</span>
        <span>Layar Display TV (Ruang Tunggu)</span>
      </a>
      <?php endif; ?>

      <a href="#" class="bps-nav-item opacity-75">
        <span class="material-icons">support_agent</span>
        <span>Hubungi Kami</span>
      </a>

      <!-- Kategori 3: Akses Internal & Sesi -->
      <?php if (!$isUserLoggedIn): ?>
        <div class="pt-4 pb-2 text-[10px] font-bold text-slate-500 uppercase tracking-widest px-3">Akses Internal</div>
        <a href="login.php" class="bps-nav-item <?php echo ($activeMenu == 'login') ? 'active' : ''; ?>">
          <span class="material-icons">login</span>
          <span>Login Akun / Petugas</span>
        </a>
      <?php endif; ?>
    </nav>
  </div>

  <!-- User Profile & Session Footer Card -->
  <?php if ($isUserLoggedIn): ?>
    <div class="mt-6 p-3 bg-slate-800/90 rounded-2xl border border-slate-700/70 space-y-2 shadow-inner">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-sky-600 via-indigo-600 to-blue-700 text-white font-extrabold text-sm flex items-center justify-center shrink-0 shadow-md">
          <span class="material-icons text-xl">account_circle</span>
        </div>
        <div class="min-w-0 flex-1">
          <div class="font-extrabold text-white text-xs truncate leading-tight" title="<?php echo htmlspecialchars($sidebarUserName); ?>">
            <?php echo htmlspecialchars($sidebarUserName); ?>
          </div>
          <div class="text-[10px] font-bold text-sky-400 uppercase tracking-wider mt-0.5 flex items-center gap-1">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
            <span>Aktor: <?php echo htmlspecialchars($sidebarUserRole); ?></span>
          </div>
        </div>
      </div>
      <button onclick="logoutUser()" type="button" class="w-full py-2 px-3 bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 hover:text-rose-300 border border-rose-500/30 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-sm mt-1">
        <span class="material-icons text-sm">logout</span>
        <span>Keluar / Logout Sesi</span>
      </button>
    </div>
  <?php else: ?>
    <div class="mt-6 p-4 bg-slate-800/90 rounded-xl border border-slate-700/50 text-xs text-slate-300 space-y-2">
      <div class="flex items-center gap-2 font-semibold text-sky-400">
        <span class="material-icons text-sm">location_city</span>
        <span>BPS Kota Tegal</span>
      </div>
      <p class="text-[11px] leading-relaxed text-slate-400">
        Sistem Pelayanan Statistik Terpadu BPS Kota Tegal.
      </p>
    </div>
  <?php endif; ?>
</aside>

<!-- Mobile Navigation Offcanvas Drawer (Visitor Portal) -->
<div class="offcanvas offcanvas-start bps-sidebar text-slate-100 w-80 lg:hidden" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
  <div class="offcanvas-header p-6 border-b border-slate-700/60">
    <div class="flex items-center gap-3">
      <img src="img/Logo_BPS.png" alt="Logo BPS Kota Tegal" class="w-10 h-10 object-contain filter drop-shadow">
      <div>
        <h5 class="offcanvas-title font-extrabold text-base tracking-wide leading-tight brand-font text-white" id="mobileSidebarLabel">SPST BPS</h5>
        <p class="text-[10px] text-sky-400 font-semibold tracking-wider uppercase">BPS KOTA TEGAL</p>
      </div>
    </div>
    <button type="button" class="btn-close btn-close-white text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body p-6 flex flex-col justify-between overflow-y-auto">
    <div>
      <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-3 mb-2">Alur Utama Pengunjung</div>
      <nav class="space-y-1">
        <a href="index.php" class="bps-nav-item <?php echo ($activeMenu == 'home') ? 'active' : ''; ?>">
          <span class="material-icons">home</span>
          <span>Beranda</span>
        </a>

        <a href="register.php" class="bps-nav-item <?php echo ($activeMenu == 'register') ? 'active' : ''; ?>">
          <span class="material-icons">how_to_reg</span>
          <span>Daftar Akun Pengunjung</span>
        </a>

        <a href="antrian.php" class="bps-nav-item <?php echo ($activeMenu == 'antrian') ? 'active' : ''; ?>">
          <span class="material-icons">confirmation_number</span>
          <span>Reservasi Antrean & Layanan</span>
        </a>

        <a href="bukutamu.php" class="bps-nav-item <?php echo ($activeMenu == 'bukutamu') ? 'active' : ''; ?>">
          <span class="material-icons">receipt_long</span>
          <span>Riwayat & Tiket Saya</span>
        </a>

        <!-- Kategori 2: Portal Layanan Data & Konsultasi -->
        <div class="pt-4 pb-2 text-[10px] font-bold text-slate-500 uppercase tracking-widest px-3">Layanan & Portal Data</div>

        <!-- Dropdown Ruang Konsultasi -->
        <div class="space-y-1">
          <button class="bps-nav-item w-full flex items-center justify-between" type="button" data-bs-toggle="collapse" data-bs-target="#mobileMenuKonsultasi">
            <div class="flex items-center gap-3">
              <span class="material-icons">person_pin</span>
              <span>Ruang Konsultasi</span>
            </div>
            <span class="material-icons text-sm">expand_more</span>
          </button>
          <div class="collapse pl-9 space-y-1" id="mobileMenuKonsultasi">
            <a href="#" class="block py-1.5 px-3 text-xs text-slate-400 hover:text-white rounded-lg transition">Layanan Konsultasi Statistik</a>
            <a href="#" class="block py-1.5 px-3 text-xs text-slate-400 hover:text-white rounded-lg transition">Ruang Chat Admin</a>
            <a href="#" class="block py-1.5 px-3 text-xs text-slate-400 hover:text-white rounded-lg transition">Halaman Chat Chatbot</a>
          </div>
        </div>

        <a href="#" class="bps-nav-item opacity-75">
          <span class="material-icons">dashboard</span>
          <span>Ruang Data</span>
        </a>

        <a href="#" class="bps-nav-item opacity-75">
          <span class="material-icons">insert_chart</span>
          <span>Ruang Infografis</span>
        </a>

        <a href="webgis.php" class="bps-nav-item <?php echo ($activeMenu == 'webgis') ? 'active' : ''; ?>">
          <span class="material-icons">map</span>
          <span>Webgis Kota Tegal</span>
        </a>

        <?php if (in_array($sidebarUserRole, ['admin', 'kepala', 'petugas'])): ?>
        <a href="admin/bps_data.php" class="bps-nav-item <?php echo ($activeMenu == 'bps_data') ? 'active' : ''; ?>">
          <span class="material-icons text-amber-400">cloud_sync</span>
          <span>Integrasi Web API BPS</span>
        </a>
        <a href="display.php" target="_blank" class="bps-nav-item">
          <span class="material-icons text-amber-400">tv</span>
          <span>Layar Display TV (Ruang Tunggu)</span>
        </a>
        <?php endif; ?>

        <a href="#" class="bps-nav-item opacity-75">
          <span class="material-icons">support_agent</span>
          <span>Hubungi Kami</span>
        </a>

        <!-- Kategori 3: Akses Internal & Sesi -->
        <?php if (!$isUserLoggedIn): ?>
          <div class="pt-4 pb-2 text-[10px] font-bold text-slate-500 uppercase tracking-widest px-3">Akses Internal</div>
          <a href="login.php" class="bps-nav-item <?php echo ($activeMenu == 'login') ? 'active' : ''; ?>">
            <span class="material-icons">login</span>
            <span>Login Akun / Petugas</span>
          </a>
        <?php endif; ?>
      </nav>
    </div>

    <!-- User Profile & Session Footer Card (Mobile) -->
    <?php if ($isUserLoggedIn): ?>
      <div class="p-3 bg-slate-800/90 rounded-2xl border border-slate-700/70 space-y-2 shadow-inner mt-6">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-sky-600 via-indigo-600 to-blue-700 text-white font-extrabold text-sm flex items-center justify-center shrink-0 shadow-md">
            <span class="material-icons text-xl">account_circle</span>
          </div>
          <div class="min-w-0 flex-1">
            <div class="font-extrabold text-white text-xs truncate leading-tight" title="<?php echo htmlspecialchars($sidebarUserName); ?>">
              <?php echo htmlspecialchars($sidebarUserName); ?>
            </div>
            <div class="text-[10px] font-bold text-sky-400 uppercase tracking-wider mt-0.5 flex items-center gap-1">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
              <span>Aktor: <?php echo htmlspecialchars($sidebarUserRole); ?></span>
            </div>
          </div>
        </div>
        <button onclick="logoutUser()" type="button" class="w-full py-2 px-3 bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 hover:text-rose-300 border border-rose-500/30 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-sm mt-1">
          <span class="material-icons text-sm">logout</span>
          <span>Keluar / Logout Sesi</span>
        </button>
      </div>
    <?php else: ?>
      <div class="p-4 bg-slate-800/90 rounded-xl border border-slate-700/50 text-xs text-slate-300 space-y-2 mt-6">
        <div class="flex items-center gap-2 font-semibold text-sky-400">
          <span class="material-icons text-sm">location_city</span>
          <span>BPS Kota Tegal</span>
        </div>
        <p class="text-[11px] leading-relaxed text-slate-400">
          Sistem Pelayanan Statistik Terpadu BPS Kota Tegal.
        </p>
      </div>
    <?php endif; ?>
  </div>
</div>
