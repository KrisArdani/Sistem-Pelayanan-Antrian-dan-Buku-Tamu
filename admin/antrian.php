<?php
// TOBASA BPS Kota Tegal - Admin Panel Loket Antrian
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
  <title>Kelola Loket Antrian - TOBASA Admin BPS Kota Tegal</title>

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
            <h1 class="text-white font-extrabold text-lg tracking-wide leading-tight brand-font">PANEL TOBASA</h1>
            <p class="text-xs text-sky-400 font-semibold tracking-wider uppercase">BPS KOTA TEGAL</p>
          </div>
        </div>

        <nav class="space-y-1">
          <a href="dashboard.php" class="bps-nav-item"><span class="material-icons">dashboard</span> Executive Dashboard</a>
          <a href="bukutamu.php" class="bps-nav-item"><span class="material-icons">groups</span> Kelola Buku Tamu</a>
          <a href="antrian.php" class="bps-nav-item active"><span class="material-icons">summarize</span> Kelola Loket Antrian</a>
          <div class="pt-4 pb-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Akses Utama</div>
          <a href="../index.php" class="bps-nav-item"><span class="material-icons">open_in_new</span> Portal Publik</a>
        </nav>
      </div>

      <div class="p-4 bg-slate-800/80 rounded-xl border border-slate-700/50 text-xs text-slate-300 space-y-2">
        <button onclick="logoutUser()" class="btn btn-outline-danger btn-sm w-full flex items-center justify-center gap-1">
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
        
        <div>
          <h1 class="text-2xl font-extrabold text-slate-900 brand-font">Kelola & Pemanggilan Antrian Loket</h1>
          <p class="text-slate-500 text-xs">Panggil nomor antrian yang menunggu secara teratur.</p>
        </div>

        <!-- Call Board Header -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          
          <!-- Board Active Card -->
          <div class="md:col-span-2 bg-gradient-to-r from-[#003366] to-[#0055A5] rounded-3xl p-8 text-white shadow-xl flex flex-col justify-between space-y-4">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-sky-300 uppercase tracking-widest">LOKET PST 1 (AKTIF)</span>
              <span class="badge bg-amber-400 text-slate-900 font-extrabold text-xs uppercase">Panggilan Aktif</span>
            </div>
            
            <div class="text-center py-4 space-y-2">
              <div class="text-6xl md:text-7xl font-extrabold tracking-wider brand-font" id="board_active_number">---</div>
              <div class="text-lg font-bold text-sky-200" id="board_active_name">Belum Ada Panggilan</div>
              <div class="text-xs text-sky-300" id="board_active_service">-</div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-2">
              <button id="btn_panggil_berikutnya" class="btn btn-warning btn-lg w-full py-3 bg-[#FF6B35] border-none text-white font-bold flex items-center justify-center gap-2 shadow-lg hover:bg-[#E85A24] text-sm">
                <span class="material-icons text-xl">volume_up</span>
                <span>Panggil Berikutnya</span>
              </button>

              <button id="btn_panggil_ulang_aktif" class="btn btn-sky btn-lg w-full py-3 bg-sky-600 border-none text-white font-bold flex items-center justify-center gap-2 shadow-lg hover:bg-sky-500 text-sm">
                <span class="material-icons text-xl">replay</span>
                <span>Panggil Ulang (Re-Call)</span>
              </button>
            </div>
          </div>

          <!-- Quick Stats Card -->
          <div class="glass-card p-6 flex flex-col justify-between space-y-4">
            <h3 class="text-base font-bold text-slate-900 brand-font">Status Pelayanan PST</h3>
            
            <div class="space-y-3 text-sm">
              <div class="p-3 bg-slate-100 rounded-xl flex items-center justify-between">
                <span class="text-slate-600 font-medium">Sistem Pemanggilan:</span>
                <span class="font-bold text-sky-700">Audio Bell & Visual</span>
              </div>
              <div class="p-3 bg-slate-100 rounded-xl flex items-center justify-between">
                <span class="text-slate-600 font-medium">Jam Pelayanan:</span>
                <span class="font-bold text-slate-800">Senin - Jumat</span>
              </div>
            </div>

            <div class="p-4 bg-sky-50 rounded-xl border border-sky-200 text-xs text-sky-900">
              Setiap kali tombol "Panggil Berikutnya" diklik, sistem akan memainkan bunyi bell notifikasi panggilan.
            </div>
          </div>

        </div>

        <!-- Antrian Table Header & Walk-In Button -->
        <div class="glass-card overflow-hidden">
          <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
              <h3 class="text-lg font-bold text-slate-900 brand-font">Daftar Seluruh Antrian</h3>
              <p class="text-xs text-slate-500">Antrian terintegrasi online dan pengunjung walk-in offline.</p>
            </div>
            <button type="button" class="btn btn-success bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl flex items-center gap-2 shadow-md" data-bs-toggle="modal" data-bs-target="#modalWalkin">
              <span class="material-icons text-sm">person_add_alt_1</span>
              <span>Input Pengunjung Walk-In (Offline)</span>
            </button>
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
        </div>

      </div>

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
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="form-label text-xs font-bold text-slate-700 uppercase">Nama Lengkap Pengunjung <span class="text-red-500">*</span></label>
                    <input type="text" id="walkin_nama" class="form-control text-sm rounded-xl" placeholder="Nama pengunjung..." required>
                  </div>
                  <div>
                    <label class="form-label text-xs font-bold text-slate-700 uppercase">Nomor HP / WA <span class="text-red-500">*</span></label>
                    <input type="tel" id="walkin_nohp" class="form-control text-sm rounded-xl" placeholder="0812..." required>
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                  <div>
                    <label class="form-label text-xs font-bold text-slate-700 uppercase">Pendidikan</label>
                    <select id="walkin_pendidikan" class="form-select text-sm rounded-xl">
                      <option value="D4-S1">D4-S1</option>
                      <option value="SMA Ke Bawah">SMA Ke Bawah</option>
                      <option value="D1/D2/D3">D1/D2/D3</option>
                      <option value="S2-S3">S2-S3</option>
                    </select>
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                  <div>
                    <label class="form-label text-xs font-bold text-slate-700 uppercase">Nama Instansi <span class="text-red-500">*</span></label>
                    <input type="text" id="walkin_instansi" class="form-control text-sm rounded-xl" placeholder="UPS Tegal, Pemda, umum..." required>
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="form-label text-xs font-bold text-slate-700 uppercase">Jenis Layanan PST <span class="text-red-500">*</span></label>
                    <select id="walkin_layanan" class="form-select text-sm rounded-xl" required>
                      <option value="Konsultasi Statistik">Konsultasi Statistik</option>
                      <option value="Perpustakaan & Diseminasi Data">Perpustakaan & Diseminasi Data</option>
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

      <!-- Footer -->
      <footer class="bg-slate-900 text-slate-400 py-4 px-6 text-center text-xs border-t border-slate-800">
        Panel Admin TOBASA BPS Kota Tegal © 2026
      </footer>

    </main>
  </div>

  <script src="../js/app.js?v=<?php echo time(); ?>"></script>
  <script src="../js/admin-antrian.js?v=<?php echo time(); ?>"></script>
</body>
</html>
