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

      <a href="#" class="bps-nav-item opacity-75">
        <span class="material-icons">map</span>
        <span>Webgis Kota Tegal</span>
      </a>

      <a href="#" class="bps-nav-item opacity-75">
        <span class="material-icons">support_agent</span>
        <span>Hubungi Kami</span>
      </a>

      <!-- Kategori 3: Akses Internal Petugas -->
      <div class="pt-4 pb-2 text-[10px] font-bold text-slate-500 uppercase tracking-widest px-3">Akses Internal</div>
      
      <a href="login.php" class="bps-nav-item <?php echo ($activeMenu == 'login') ? 'active' : ''; ?>">
        <span class="material-icons">login</span>
        <span>Login Internal Petugas</span>
      </a>
    </nav>
  </div>

  <!-- Info Banner Footer Sidebar -->
  <div class="mt-6 p-4 bg-slate-800/90 rounded-xl border border-slate-700/50 text-xs text-slate-300 space-y-2">
    <div class="flex items-center gap-2 font-semibold text-sky-400">
      <span class="material-icons text-sm">location_city</span>
      <span>BPS Kota Tegal</span>
    </div>
    <p class="text-[11px] leading-relaxed text-slate-400">
      Sistem Pelayanan Statistik Terpadu BPS Kota Tegal.
    </p>
  </div>
</aside>
