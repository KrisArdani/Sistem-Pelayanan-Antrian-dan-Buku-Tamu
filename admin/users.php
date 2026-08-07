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
  <!-- Bootstrap 5.3.8 CSS & Bundle -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
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

      <div class="p-4 bg-slate-800/80 rounded-xl border border-slate-700/50 text-xs text-slate-300 space-y-2">
        <div id="user_display_name" class="font-bold text-white text-sm"><?php echo htmlspecialchars($_SESSION['user_name'] ?? '-'); ?></div>
        <div id="user_display_role" class="text-sky-400 text-xs uppercase font-semibold"><?php echo htmlspecialchars($_SESSION['user_role'] ?? '-'); ?></div>
        <button onclick="logoutUser()" class="btn btn-outline-danger btn-sm w-full mt-2 flex items-center justify-center gap-1">
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

      <!-- Footer -->
      <footer class="bg-slate-900 text-slate-400 py-4 px-6 text-center text-xs border-t border-slate-800">
        Panel Admin SPST BPS Kota Tegal © 2026
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
  <script>
    let currentRoleFilter = 'all';
    let currentUsersData = [];
    let modalUserObj = null;
    let modalResetObj = null;

    document.addEventListener('DOMContentLoaded', () => {
      modalUserObj = new bootstrap.Modal(document.getElementById('modalUser'));
      modalResetObj = new bootstrap.Modal(document.getElementById('modalResetPassword'));
      loadUsersData();
    });

    function filterByRole(role) {
      currentRoleFilter = role;
      const tabs = ['all', 'petugas', 'admin', 'kepala', 'pengunjung'];
      tabs.forEach(t => {
        const btn = document.getElementById(`tab_role_${t}`);
        if (btn) {
          if (t === role) {
            btn.className = 'px-3 py-1.5 rounded-lg bg-white shadow-sm text-slate-800 font-bold transition';
          } else {
            btn.className = 'px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 transition';
          }
        }
      });
      loadUsersData();
    }

    function handleSearchKey(e) {
      if (e.key === 'Enter') {
        loadUsersData();
      }
    }

    async function loadUsersData() {
      const search = document.getElementById('searchInput').value.trim();
      const tbody = document.getElementById('tableUsersBody');
      tbody.innerHTML = `
        <tr>
          <td colspan="7" class="text-center py-8 text-slate-400">
            <span class="material-icons animate-spin text-2xl mb-1">sync</span>
            <div>Memuat data pengguna...</div>
          </td>
        </tr>
      `;

      try {
        const res = await fetch(`../api.php?action=get_users&role=${currentRoleFilter}&search=${encodeURIComponent(search)}`, {
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();

        if (data.status === 'success') {
          currentUsersData = data.data.users || [];
          renderSummaryCounts(data.data.summary || {});
          renderUsersTable(currentUsersData);
        } else {
          tbody.innerHTML = `<tr><td colspan="7" class="text-center py-6 text-red-500">${data.message}</td></tr>`;
        }
      } catch (err) {
        console.error("Failed to load users:", err);
        tbody.innerHTML = `<tr><td colspan="7" class="text-center py-6 text-red-500">Gagal mengambil data dari server.</td></tr>`;
      }
    }

    function renderSummaryCounts(summary) {
      document.getElementById('kpi_total_user').innerText = summary.total || 0;
      document.getElementById('kpi_petugas').innerText = summary.petugas || 0;
      document.getElementById('kpi_admin_kepala').innerText = (summary.admin || 0) + (summary.kepala || 0);
      document.getElementById('kpi_pengunjung').innerText = summary.pengunjung || 0;
    }

    function renderUsersTable(users) {
      const tbody = document.getElementById('tableUsersBody');
      if (!users || users.length === 0) {
        tbody.innerHTML = `
          <tr>
            <td colspan="7" class="text-center py-8 text-slate-400">
              <span class="material-icons text-3xl mb-1 text-slate-300">person_off</span>
              <div>Tidak ada data pengguna yang ditemukan.</div>
            </td>
          </tr>
        `;
        return;
      }

      let html = '';
      users.forEach((u, idx) => {
        let roleBadge = '';
        if (u.role === 'petugas') {
          roleBadge = '<span class="px-2.5 py-1 text-[10px] font-bold bg-emerald-100 text-emerald-800 rounded-full uppercase">Petugas PST</span>';
        } else if (u.role === 'admin') {
          roleBadge = '<span class="px-2.5 py-1 text-[10px] font-bold bg-purple-100 text-purple-800 rounded-full uppercase">Admin System</span>';
        } else if (u.role === 'kepala') {
          roleBadge = '<span class="px-2.5 py-1 text-[10px] font-bold bg-sky-100 text-sky-800 rounded-full uppercase">Kepala BPS</span>';
        } else {
          roleBadge = '<span class="px-2.5 py-1 text-[10px] font-bold bg-amber-100 text-amber-800 rounded-full uppercase">Pengunjung</span>';
        }

        const dateFormatted = u.created_at ? u.created_at.substring(0, 10) : '-';

        let actionButtons = '';
        if (u.role === 'pengunjung') {
          actionButtons = `<span class="px-2.5 py-1 text-[11px] font-semibold text-slate-400 bg-slate-100 rounded-lg italic">Read-Only (Mandiri)</span>`;
        } else {
          actionButtons = `
            <div class="flex items-center justify-center gap-1">
              <button onclick="editUser(${u.id})" title="Edit Staf Internal" class="p-1.5 text-sky-600 hover:bg-sky-50 rounded-lg transition">
                <span class="material-icons text-base">edit</span>
              </button>
              <button onclick="openModalResetPassword(${u.id}, '${escapeHtml(u.username)}')" title="Reset Password Staf" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition">
                <span class="material-icons text-base">key</span>
              </button>
              <button onclick="deleteUser(${u.id}, '${escapeHtml(u.username)}')" title="Hapus Staf Internal" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition">
                <span class="material-icons text-base">delete</span>
              </button>
            </div>
          `;
        }

        html += `
          <tr class="hover:bg-slate-50/80 transition">
            <td class="text-center font-bold text-slate-400 py-3.5 px-4">${idx + 1}</td>
            <td class="py-3.5 px-4">
              <div class="font-bold text-slate-900 text-sm">${escapeHtml(u.name)}</div>
              <div class="text-[11px] text-slate-400 font-mono">@${escapeHtml(u.username)}</div>
            </td>
            <td class="py-3.5 px-4">${roleBadge}</td>
            <td class="py-3.5 px-4 space-y-0.5">
              <div class="text-xs text-slate-700 flex items-center gap-1">
                <span class="material-icons text-[13px] text-slate-400">phone</span> ${escapeHtml(u.nohp || '-')}
              </div>
              <div class="text-[11px] text-slate-500 flex items-center gap-1">
                <span class="material-icons text-[13px] text-slate-400">email</span> ${escapeHtml(u.email || '-')}
              </div>
            </td>
            <td class="py-3.5 px-4">
              <div class="font-semibold text-slate-800">${escapeHtml(u.instansi || '-')}</div>
              <div class="text-[11px] text-slate-400">${escapeHtml(u.kategori_instansi || '')}</div>
            </td>
            <td class="text-center text-slate-500 py-3.5 px-4 text-xs">${dateFormatted}</td>
            <td class="text-center py-3.5 px-4">${actionButtons}</td>
          </tr>
        `;
      });

      tbody.innerHTML = html;
    }

    function openModalAddUser() {
      document.getElementById('formUser').reset();
      document.getElementById('user_id').value = "0";
      document.getElementById('modalUserTitle').innerHTML = `
        <span class="material-icons text-sky-400">person_add</span>
        <span>Tambah Staf Internal Baru</span>
      `;
      document.getElementById('user_password').required = true;
      document.getElementById('lblPassword').innerHTML = 'Password <span class="text-red-500">*</span>';
      document.getElementById('hintPasswordEdit').classList.add('hidden');
      modalUserObj.show();
    }

    function editUser(id) {
      const u = currentUsersData.find(x => x.id == id);
      if (!u) return;

      if (u.role === 'pengunjung') {
        Swal.fire({
          icon: 'info',
          title: 'Akun Pengunjung',
          text: 'Admin tidak dapat mengubah akun pengunjung. Pengunjung mengelola akun dan password secara mandiri.'
        });
        return;
      }

      document.getElementById('user_id').value = u.id;
      document.getElementById('user_username').value = u.username || '';
      document.getElementById('user_password').value = '';
      document.getElementById('user_name').value = u.name || '';
      document.getElementById('user_role').value = u.role || 'petugas';
      document.getElementById('user_jenis_kelamin').value = u.jenis_kelamin || 'Laki Laki';
      document.getElementById('user_nohp').value = u.nohp || '';
      document.getElementById('user_email').value = u.email || '';
      document.getElementById('user_instansi').value = u.instansi || '';
      document.getElementById('user_kategori_instansi').value = u.kategori_instansi || 'Instansi Pemerintah';
      document.getElementById('user_pendidikan').value = u.pendidikan || 'D4-S1';
      document.getElementById('user_pekerjaan').value = u.pekerjaan || '';

      document.getElementById('modalUserTitle').innerHTML = `
        <span class="material-icons text-sky-400">edit_note</span>
        <span>Edit Staf Internal: @${escapeHtml(u.username)}</span>
      `;
      document.getElementById('user_password').required = false;
      document.getElementById('lblPassword').innerHTML = 'Password (Opsional)';
      document.getElementById('hintPasswordEdit').classList.remove('hidden');

      modalUserObj.show();
    }

    async function saveUser(e) {
      e.preventDefault();
      const btn = document.getElementById('btnSaveUser');
      btn.disabled = true;

      const id = document.getElementById('user_id').value;
      const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

      const payload = {
        action: 'save_user',
        csrf_token: csrfToken,
        id: id,
        username: document.getElementById('user_username').value,
        password: document.getElementById('user_password').value,
        name: document.getElementById('user_name').value,
        role: document.getElementById('user_role').value,
        jenis_kelamin: document.getElementById('user_jenis_kelamin').value,
        nohp: document.getElementById('user_nohp').value,
        email: document.getElementById('user_email').value,
        instansi: document.getElementById('user_instansi').value,
        kategori_instansi: document.getElementById('user_kategori_instansi').value,
        pendidikan: document.getElementById('user_pendidikan').value,
        pekerjaan: document.getElementById('user_pekerjaan').value
      };

      try {
        const res = await fetch('../api.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (data.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: data.message,
            timer: 1500,
            showConfirmButton: false
          });
          modalUserObj.hide();
          loadUsersData();
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Gagal Menyimpan',
            text: data.message
          });
        }
      } catch (err) {
        console.error("Save user error:", err);
        Swal.fire({
          icon: 'error',
          title: 'Kesalahan Sistem',
          text: 'Gagal menghubungi server.'
        });
      } finally {
        btn.disabled = false;
      }
    }

    function openModalResetPassword(id, username) {
      document.getElementById('reset_user_id').value = id;
      document.getElementById('reset_user_username').innerText = username;
      document.getElementById('reset_new_password').value = '';
      modalResetObj.show();
    }

    async function submitResetPassword(e) {
      e.preventDefault();
      const id = document.getElementById('reset_user_id').value;
      const newPassword = document.getElementById('reset_new_password').value;
      const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

      try {
        const res = await fetch('../api.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({
            action: 'reset_password_user',
            csrf_token: csrfToken,
            id: id,
            new_password: newPassword
          })
        });
        const data = await res.json();

        if (data.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'Reset Password Berhasil!',
            text: data.message,
            timer: 1500,
            showConfirmButton: false
          });
          modalResetObj.hide();
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Reset Password Gagal',
            text: data.message
          });
        }
      } catch (err) {
        console.error("Reset password error:", err);
        Swal.fire({
          icon: 'error',
          title: 'Kesalahan Sistem',
          text: 'Gagal menghubungi server.'
        });
      }
    }

    function deleteUser(id, username) {
      Swal.fire({
        title: `Hapus Akun @${username}?`,
        text: "Akun yang terhapus tidak dapat dikembalikan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus Akun',
        cancelButtonText: 'Batal'
      }).then(async (result) => {
        if (result.isConfirmed) {
          const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
          try {
            const res = await fetch('../api.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
              },
              body: JSON.stringify({
                action: 'delete_user',
                csrf_token: csrfToken,
                id: id
              })
            });
            const data = await res.json();

            if (data.status === 'success') {
              Swal.fire({
                icon: 'success',
                title: 'Terhapus!',
                text: data.message,
                timer: 1500,
                showConfirmButton: false
              });
              loadUsersData();
            } else {
              Swal.fire({
                icon: 'error',
                title: 'Gagal Menghapus',
                text: data.message
              });
            }
          } catch (err) {
            console.error("Delete user error:", err);
            Swal.fire({
              icon: 'error',
              title: 'Kesalahan Sistem',
              text: 'Gagal menghubungi server.'
            });
          }
        }
      });
    }

    function escapeHtml(str) {
      if (!str) return '';
      return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }
  </script>
</body>
</html>
