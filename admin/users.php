<?php
// SPST BPS Kota Tegal - Kelola Pengguna (User Management)
$allowed_roles = ['admin'];
require_once __DIR__ . '/../auth_check.php';
$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
  <title>Kelola Pengguna - SPST Admin BPS Kota Tegal</title>

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
          <a href="antrian.php" class="bps-nav-item flex items-center justify-between">
            <div class="flex items-center gap-3">
              <span class="material-icons">summarize</span>
              <span>Kelola Loket Antrian</span>
            </div>
            <span id="admin_sidebar_waiting_badge" class="hidden px-2 py-0.5 bg-amber-500 text-slate-950 font-extrabold text-[10px] rounded-full shadow-sm animate-pulse" title="Antrean Menunggu Hari Ini">0</span>
          </a>
          <a href="users.php" class="bps-nav-item active"><span class="material-icons">manage_accounts</span> Kelola Pengguna</a>
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
            <div id="user_display_name" class="font-extrabold text-white text-xs truncate leading-tight" title="<?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Administrator'); ?>">
              <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Administrator'); ?>
            </div>
            <div id="user_display_role" class="text-[10px] font-bold text-sky-400 uppercase tracking-wider mt-0.5 flex items-center gap-1">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
              <span>Aktor: <?php echo htmlspecialchars($_SESSION['user_role'] ?? 'Admin'); ?></span>
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
            <a href="antrian.php" class="bps-nav-item flex items-center justify-between">
              <div class="flex items-center gap-3">
                <span class="material-icons">summarize</span>
                <span>Kelola Loket Antrian</span>
              </div>
              <span id="admin_mobile_waiting_badge" class="hidden px-2 py-0.5 bg-amber-500 text-slate-950 font-extrabold text-[10px] rounded-full shadow-sm animate-pulse">0</span>
            </a>
            <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
            <a href="users.php" class="bps-nav-item active"><span class="material-icons">manage_accounts</span> Kelola Pengguna</a>
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
              <div class="font-extrabold text-white text-xs truncate leading-tight" title="<?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Administrator'); ?>">
                <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Administrator'); ?>
              </div>
              <div class="text-[10px] font-bold text-sky-400 uppercase tracking-wider mt-0.5 flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>Aktor: <?php echo htmlspecialchars($_SESSION['user_role'] ?? 'Admin'); ?></span>
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
          <span class="text-xs font-bold px-3 py-1 bg-purple-100 text-purple-700 rounded-full uppercase tracking-wider">Manajemen Pengguna System</span>
        </div>

        <div class="flex items-center gap-3">
          <button onclick="openModalAddUser()" class="btn btn-sm btn-primary bg-sky-600 hover:bg-sky-500 border-none flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold shadow-sm">
            <span class="material-icons text-base">person_add</span>
            <span>Tambah Akun Baru</span>
          </button>
          <button onclick="logoutUser()" class="btn btn-sm btn-outline-danger text-xs flex items-center gap-1">
            <span class="material-icons text-sm">logout</span> Logout
          </button>
        </div>
      </header>

      <!-- Main Container -->
      <div class="p-6 md:p-10 max-w-7xl mx-auto w-full space-y-8">
        
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <h1 class="text-2xl font-extrabold text-slate-900 brand-font">Kelola Akun Pengguna</h1>
            <p class="text-slate-500 text-xs">Manajemen akun pengguna internal (Petugas, Admin, Kepala) & pengunjung eksternal.</p>
          </div>
          <button onclick="openModalAddUser()" class="btn btn-primary bg-sky-600 hover:bg-sky-500 border-none font-bold text-xs flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl shadow-md">
            <span class="material-icons text-base">person_add</span>
            <span>Tambah User Baru</span>
          </button>
        </div>

        <!-- 4 KPI Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
          <div class="glass-card p-5 flex items-center gap-4 border-l-4 border-l-sky-500">
            <div class="w-12 h-12 bg-sky-100 text-sky-700 rounded-xl flex items-center justify-center font-bold">
              <span class="material-icons text-2xl">people</span>
            </div>
            <div>
              <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total User</div>
              <div class="text-2xl font-extrabold text-slate-900 brand-font" id="kpi_total_user">0</div>
            </div>
          </div>

          <div class="glass-card p-5 flex items-center gap-4 border-l-4 border-l-emerald-500">
            <div class="w-12 h-12 bg-emerald-100 text-emerald-700 rounded-xl flex items-center justify-center font-bold">
              <span class="material-icons text-2xl">badge</span>
            </div>
            <div>
              <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Petugas PST</div>
              <div class="text-2xl font-extrabold text-slate-900 brand-font" id="kpi_petugas">0</div>
            </div>
          </div>

          <div class="glass-card p-5 flex items-center gap-4 border-l-4 border-l-purple-500">
            <div class="w-12 h-12 bg-purple-100 text-purple-700 rounded-xl flex items-center justify-center font-bold">
              <span class="material-icons text-2xl">admin_panel_settings</span>
            </div>
            <div>
              <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Admin & Kepala</div>
              <div class="text-2xl font-extrabold text-slate-900 brand-font" id="kpi_admin_kepala">0</div>
            </div>
          </div>

          <div class="glass-card p-5 flex items-center gap-4 border-l-4 border-l-amber-500">
            <div class="w-12 h-12 bg-amber-100 text-amber-700 rounded-xl flex items-center justify-center font-bold">
              <span class="material-icons text-2xl">person_pin</span>
            </div>
            <div>
              <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Pengunjung</div>
              <div class="text-2xl font-extrabold text-slate-900 brand-font" id="kpi_pengunjung">0</div>
            </div>
          </div>
        </div>

        <!-- Filter & Search Controls Card -->
        <div class="glass-card p-5 space-y-4">
          <div class="flex flex-col md:flex-row items-center justify-between gap-4">
            
            <!-- Filter Role Tabs -->
            <div class="flex flex-wrap items-center gap-1.5 bg-slate-100 p-1.5 rounded-xl text-xs font-semibold w-full md:w-auto">
              <button onclick="filterByRole('all')" id="tab_role_all" class="px-3 py-1.5 rounded-lg bg-white shadow-sm text-slate-800 font-bold transition">Semua</button>
              <button onclick="filterByRole('petugas')" id="tab_role_petugas" class="px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 transition">Petugas</button>
              <button onclick="filterByRole('admin')" id="tab_role_admin" class="px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 transition">Admin</button>
              <button onclick="filterByRole('kepala')" id="tab_role_kepala" class="px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 transition">Kepala BPS</button>
              <button onclick="filterByRole('pengunjung')" id="tab_role_pengunjung" class="px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 transition">Pengunjung</button>
            </div>

            <!-- Search Input -->
            <div class="relative w-full md:w-72">
              <span class="material-icons absolute left-3 top-2.5 text-slate-400 text-base">search</span>
              <input type="text" id="searchInput" onkeyup="handleSearchKey(event)" placeholder="Cari nama, username, email..." class="form-control pl-9 pr-4 py-2 text-xs rounded-xl border-slate-300">
            </div>

          </div>
        </div>

        <!-- Users Table Card -->
        <div class="glass-card overflow-hidden">
          <div class="table-responsive">
            <table class="table table-hover align-middle text-xs mb-0">
              <thead class="bg-slate-100 text-slate-600 uppercase font-bold text-[11px] border-b border-slate-200">
                <tr>
                  <th class="py-3 px-4 text-center">No</th>
                  <th class="py-3 px-4">Pengguna</th>
                  <th class="py-3 px-4">Role / Akses</th>
                  <th class="py-3 px-4">Kontak</th>
                  <th class="py-3 px-4">Instansi</th>
                  <th class="py-3 px-4 text-center">Tanggal Dibuat</th>
                  <th class="py-3 px-4 text-center">Aksi</th>
                </tr>
              </thead>
              <tbody id="tableUsersBody" class="divide-y divide-slate-100">
                <tr>
                  <td colspan="7" class="text-center py-8 text-slate-400">
                    <span class="material-icons animate-spin text-2xl mb-1">sync</span>
                    <div>Memuat data pengguna...</div>
                  </td>
                </tr>
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

  <!-- Modal Form Add/Edit User -->
  <div class="modal fade" id="modalUser" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content rounded-2xl border-none shadow-2xl overflow-hidden">
        <div class="modal-header bg-slate-900 text-white p-5">
          <h5 class="modal-title font-bold brand-font text-base flex items-center gap-2" id="modalUserTitle">
            <span class="material-icons text-sky-400">person_add</span>
            <span>Tambah User Baru</span>
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="formUser" onsubmit="saveUser(event)">
          <div class="modal-body p-6 space-y-4 max-h-[75vh] overflow-y-auto">
            <input type="hidden" id="user_id" value="0">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="form-label text-xs font-bold text-slate-600 uppercase">Username <span class="text-red-500">*</span></label>
                <input type="text" id="user_username" class="form-control rounded-xl text-xs" placeholder="Username unik..." required>
              </div>

              <div>
                <label class="form-label text-xs font-bold text-slate-600 uppercase" id="lblPassword">Password <span class="text-red-500">*</span></label>
                <input type="password" id="user_password" class="form-control rounded-xl text-xs" placeholder="••••••••">
                <span class="text-[10px] text-slate-400 hidden" id="hintPasswordEdit">* Kosongkan jika tidak ingin mengubah password</span>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="form-label text-xs font-bold text-slate-600 uppercase">Nama Lengkap <span class="text-red-500">*</span></label>
                <input type="text" id="user_name" class="form-control rounded-xl text-xs" placeholder="Nama lengkap..." required>
              </div>

              <div>
                <label class="form-label text-xs font-bold text-slate-600 uppercase">Role / Hak Akses <span class="text-red-500">*</span></label>
                <select id="user_role" class="form-select rounded-xl text-xs" required>
                  <option value="petugas">Petugas PST (Loket)</option>
                  <option value="admin">Admin System</option>
                  <option value="kepala">Kepala BPS</option>
                  <option value="pengunjung">Pengunjung (Pemohon)</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="form-label text-xs font-bold text-slate-600 uppercase">Jenis Kelamin</label>
                <select id="user_jenis_kelamin" class="form-select rounded-xl text-xs">
                  <option value="Laki Laki">Laki-Laki</option>
                  <option value="Perempuan">Perempuan</option>
                </select>
              </div>

              <div>
                <label class="form-label text-xs font-bold text-slate-600 uppercase">No. HP / WhatsApp</label>
                <input type="text" id="user_nohp" class="form-control rounded-xl text-xs" placeholder="08xxxxxxxxxx">
              </div>

              <div>
                <label class="form-label text-xs font-bold text-slate-600 uppercase">Email</label>
                <input type="email" id="user_email" class="form-control rounded-xl text-xs" placeholder="nama@email.com">
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="form-label text-xs font-bold text-slate-600 uppercase">Instansi</label>
                <input type="text" id="user_instansi" class="form-control rounded-xl text-xs" placeholder="Nama instansi / universitas...">
              </div>

              <div>
                <label class="form-label text-xs font-bold text-slate-600 uppercase">Kategori Instansi</label>
                <select id="user_kategori_instansi" class="form-select rounded-xl text-xs">
                  <option value="Instansi Pemerintah">Instansi Pemerintah</option>
                  <option value="Sekolah/Universitas">Sekolah / Universitas</option>
                  <option value="Pemda">Pemda</option>
                  <option value="Swasta">Swasta / Perusahaan</option>
                  <option value="Perorangan">Perorangan / Peneliti</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="form-label text-xs font-bold text-slate-600 uppercase">Tingkat Pendidikan</label>
                <select id="user_pendidikan" class="form-select rounded-xl text-xs">
                  <option value="D4-S1">D4 / S1</option>
                  <option value="S2-S3">S2 / S3</option>
                  <option value="SMA Ke Bawah">SMA Ke Bawah</option>
                  <option value="D1/D2/D3">D1 / D2 / D3</option>
                </select>
              </div>

              <div>
                <label class="form-label text-xs font-bold text-slate-600 uppercase">Pekerjaan</label>
                <input type="text" id="user_pekerjaan" class="form-control rounded-xl text-xs" placeholder="Pegawai / Mahasiswa...">
              </div>
            </div>

          </div>
          <div class="modal-footer bg-slate-50 p-4 border-t border-slate-100 flex items-center justify-end gap-2">
            <button type="button" class="btn btn-light text-slate-600 btn-sm font-semibold rounded-xl" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary bg-sky-600 hover:bg-sky-500 border-none btn-sm font-bold rounded-xl px-4 flex items-center gap-1" id="btnSaveUser">
              <span class="material-icons text-sm">save</span>
              <span>Simpan Data</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal Reset Password -->
  <div class="modal fade" id="modalResetPassword" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
      <div class="modal-content rounded-2xl border-none shadow-2xl overflow-hidden">
        <div class="modal-header bg-slate-900 text-white p-4">
          <h5 class="modal-title font-bold text-sm flex items-center gap-2">
            <span class="material-icons text-amber-400">key</span>
            <span>Reset Password</span>
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form onsubmit="submitResetPassword(event)">
          <div class="modal-body p-5 space-y-3">
            <input type="hidden" id="reset_user_id">
            <div class="text-xs text-slate-600">
              Ubah password untuk akun: <span id="reset_user_username" class="font-bold text-slate-900"></span>
            </div>
            <div>
              <label class="form-label text-xs font-bold text-slate-600 uppercase">Password Baru <span class="text-red-500">*</span></label>
              <input type="password" id="reset_new_password" class="form-control rounded-xl text-xs" placeholder="Minimal 6 karakter..." required minlength="6">
            </div>
          </div>
          <div class="modal-footer bg-slate-50 p-3 border-t border-slate-100 flex items-center justify-end gap-2">
            <button type="button" class="btn btn-light text-slate-600 btn-sm font-semibold rounded-xl" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-warning bg-amber-500 hover:bg-amber-400 text-white border-none btn-sm font-bold rounded-xl px-4 flex items-center gap-1">
              <span class="material-icons text-sm">vpn_key</span>
              <span>Reset Password</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="../js/app.js"></script>
  <script src="../js/admin-users.js"></script>
</body>
</html>
