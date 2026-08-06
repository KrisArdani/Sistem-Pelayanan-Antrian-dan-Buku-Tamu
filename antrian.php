<?php
// SPST BPS Kota Tegal - Form Antrian & Keperluan Layanan Terintegrasi
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
$activeMenu = 'antrian';

$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['user_role'] ?? 'guest';
$userName = $_SESSION['user_name'] ?? '';
$userNik = $_SESSION['user_nik'] ?? '';
$userNoHp = $_SESSION['user_nohp'] ?? '';
$userInstansi = $_SESSION['user_instansi'] ?? '';
$userEmail = $_SESSION['user_email'] ?? '';
$userPendidikan = $_SESSION['user_pendidikan'] ?? '';
$userPekerjaan = $_SESSION['user_pekerjaan'] ?? '';
$userKategoriInstansi = $_SESSION['user_kategori_instansi'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
  <title>Layanan & Antrian Online - SPST BPS Kota Tegal</title>

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
          <button class="lg:hidden p-2 rounded-lg bg-slate-100 text-slate-700" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
            <span class="material-icons">menu</span>
          </button>
          <img src="img/Logo_BPS.png" alt="Logo BPS" class="w-8 h-8 object-contain">
          <div>
            <div class="text-xs font-extrabold text-[#003366] leading-none brand-font">BPS KOTA TEGAL</div>
            <div class="text-[10px] font-semibold text-sky-600">Pelayanan Statistik Terpadu</div>
          </div>
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

      <!-- Form Container -->
      <div class="p-6 md:p-10 max-w-4xl mx-auto w-full space-y-8">
        
        <!-- Premium Header Banner -->
        <div class="relative overflow-hidden bg-gradient-to-br from-[#002B5B] via-[#003366] to-[#0284c7] rounded-3xl p-8 md:p-10 text-white shadow-2xl border border-sky-400/20 text-center space-y-5">
          <!-- Decorative Background Ambient Glow -->
          <div class="absolute -right-16 -top-16 w-72 h-72 bg-sky-400/20 rounded-full blur-3xl pointer-events-none"></div>
          <div class="absolute -left-16 -bottom-16 w-72 h-72 bg-blue-600/20 rounded-full blur-3xl pointer-events-none"></div>

          <!-- Header BPS Logo Only -->
          <div class="relative z-10 flex items-center justify-center">
            <div class="w-20 h-20 bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 p-3 flex items-center justify-center shadow-xl ring-4 ring-white/10">
              <img src="img/Logo_BPS.png" alt="Logo BPS Kota Tegal" class="w-full h-full object-contain filter drop-shadow">
            </div>
          </div>

          <!-- Tag Subtitle -->
          <div class="relative z-10">
            <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/10 text-sky-200 text-xs font-extrabold tracking-wider border border-white/15 uppercase backdrop-blur-md shadow-inner">
              <span class="w-2 h-2 rounded-full bg-sky-400 animate-pulse"></span>
              BADAN PUSAT STATISTIK KOTA TEGAL
            </span>
          </div>

          <!-- Main Title & Description -->
          <div class="relative z-10 space-y-2">
            <h1 class="text-2xl md:text-4xl font-black text-white brand-font tracking-tight leading-tight drop-shadow-md">
              RESERVASI ANTREAN & LAYANAN PST
            </h1>
            <p class="text-slate-200 text-xs md:text-sm max-w-2xl mx-auto leading-relaxed">
              Isi keperluan layanan dan pilih jadwal kunjungan secara online agar proses konsultasi statistik di BPS Kota Tegal menjadi tertib, cepat, dan nyaman.
            </p>
          </div>

          <!-- Feature Highlights Badges -->
          <div class="relative z-10 pt-2 flex flex-wrap items-center justify-center gap-2.5">
            <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-white/10 backdrop-blur-md rounded-full text-xs text-sky-100 font-semibold border border-white/10 shadow-sm">
              <span class="material-icons text-sm text-amber-300">bolt</span> Respon Cepat
            </span>
            <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-white/10 backdrop-blur-md rounded-full text-xs text-sky-100 font-semibold border border-white/10 shadow-sm">
              <span class="material-icons text-sm text-emerald-300">event_available</span> Jadwal Fleksibel
            </span>
            <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-white/10 backdrop-blur-md rounded-full text-xs text-sky-100 font-semibold border border-white/10 shadow-sm">
              <span class="material-icons text-sm text-sky-300">verified</span> Layanan Gratis
            </span>
          </div>
        </div>

        <!-- ----------------------------------------------------
             DYNAMIC PROGRESS TIMELINE STEPPER BAR
             ---------------------------------------------------- -->
        <?php include __DIR__ . '/stepper.php'; ?>

        <?php if (!$isLoggedIn): ?>
        <!-- Login Prompt Notice for Visitors -->
        <div class="p-6 bg-sky-50 rounded-2xl border border-sky-200 text-sky-900 space-y-3 flex flex-col md:flex-row items-center justify-between gap-4">
          <div class="space-y-1">
            <div class="font-bold text-base text-sky-950 flex items-center gap-2">
              <span class="material-icons text-sky-600">account_circle</span>
              <span>Silakan Login / Registrasi Akun Dahulu</span>
            </div>
            <p class="text-xs text-sky-800 leading-relaxed">
              Registrasi akun cukup dilakukan <b>1x saja</b>. Setelah terdaftar, Anda tidak perlu lagi mengisi ulang data profil setiap kali memesan antrean.
            </p>
          </div>
          <div class="flex gap-2 shrink-0">
            <a href="login.php" class="btn btn-primary bg-[#003366] border-[#003366] btn-sm text-xs font-bold px-4 py-2.5 rounded-xl">Masuk</a>
            <a href="register.php" class="btn btn-outline-sky border-sky-600 text-sky-700 btn-sm text-xs font-bold px-4 py-2.5 rounded-xl hover:bg-sky-100">Daftar Akun</a>
          </div>
        </div>
        <?php else: ?>

        <!-- Form Pemesanan Antrian & Keperluan Layanan -->
        <div class="glass-card p-6 md:p-8 space-y-6">
          <form id="formAntrian" class="space-y-6">
            
            <!-- SECTION 1: Profil Teridentifikasi -->
            <div class="p-4 bg-sky-50/80 rounded-xl border border-sky-200 space-y-2">
              <div class="text-xs font-bold text-sky-800 uppercase tracking-wider flex items-center gap-1">
                <span class="material-icons text-sm">verified_user</span> Profil Pemohon (Tersimpan dari Akun)
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 text-xs text-slate-700">
                <div><b>Nama:</b> <?php echo htmlspecialchars($userName); ?></div>
                <div><b>NIK:</b> <?php echo htmlspecialchars($userNik ?: '-'); ?></div>
                <div><b>No HP:</b> <?php echo htmlspecialchars($userNoHp ?: '-'); ?></div>
                <div><b>Instansi:</b> <?php echo htmlspecialchars($userInstansi ?: '-'); ?></div>
              </div>
              <input type="hidden" id="ant_nama" value="<?php echo htmlspecialchars($userName); ?>">
            </div>

            <!-- SECTION 2: Jadwal & Jenis Layanan -->
            <div class="space-y-4">
              <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2 border-b pb-2">
                <span class="material-icons text-sky-600 text-base">event</span> 1. Jadwal & Jenis Layanan PST
              </h3>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="form-label text-xs font-bold text-slate-700 uppercase">Rencana Tanggal Kunjungan <span class="text-red-500">*</span></label>
                  <input type="date" id="ant_tanggal" class="form-control form-control-lg text-sm rounded-xl" min="<?php echo date('Y-m-d'); ?>" required>
                  <span class="text-[11px] text-sky-700 font-semibold mt-1 block">📅 Hari Kerja: <b>Senin s.d. Jumat</b></span>
                </div>
                <div>
                  <label class="form-label text-xs font-bold text-slate-700 uppercase">Rencana Waktu Kunjungan <span class="text-red-500">*</span></label>
                  <select id="ant_waktu" class="form-select form-select-lg text-sm rounded-xl" required>
                    <option value="08:00">08:00 WIB (Pagi)</option>
                    <option value="08:30">08:30 WIB</option>
                    <option value="09:00" selected>09:00 WIB</option>
                    <option value="09:30">09:30 WIB</option>
                    <option value="10:00">10:00 WIB</option>
                    <option value="10:30">10:30 WIB</option>
                    <option value="11:00">11:00 WIB</option>
                    <option value="11:30">11:30 WIB</option>
                    <option value="13:00">13:00 WIB (Siang)</option>
                    <option value="13:30">13:30 WIB</option>
                    <option value="14:00">14:00 WIB</option>
                    <option value="14:30">14:30 WIB</option>
                    <option value="15:00">15:00 WIB</option>
                    <option value="15:30">15:30 WIB (Batas Jam Kerja)</option>
                  </select>
                  <span class="text-[11px] text-sky-700 font-semibold mt-1 block">⏰ Jam Kerja: <b>08:00 s.d. 15:30 WIB</b></span>
                </div>
              </div>

              <div>
                <label class="form-label text-xs font-bold text-slate-700 uppercase">Jenis Layanan PST <span class="text-red-500">*</span></label>
                <select id="ant_layanan" class="form-select form-select-lg text-sm rounded-xl" required>
                  <option value="">-- Pilih Layanan --</option>
                  <option value="Konsultasi Statistik">Konsultasi Statistik</option>
                  <option value="Perpustakaan">Perpustakaan</option>
                  <option value="Rekomendasi Kegiatan Statistik">Rekomendasi Kegiatan Statistik</option>
                  <option value="Layanan Pengaduan">Layanan Pengaduan</option>
                </select>
              </div>
            </div>

            <!-- SECTION 3: Detail Keperluan Data -->
            <div class="space-y-4">
              <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2 border-b pb-2">
                <span class="material-icons text-sky-600 text-base">assignment</span> 2. Keperluan & Rincian Data
              </h3>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="form-label text-xs font-bold text-slate-700 uppercase">Fasilitas Pelayanan <span class="text-red-500">*</span></label>
                  <select id="ant_fasilitas" class="form-select text-sm rounded-xl" required>
                    <option value="Datang Langsung Ke PST BPS Kota Tegal">Datang Langsung Ke PST BPS Kota Tegal</option>
                    <option value="Konsultasi Online via Live Chat">Konsultasi Online via Live Chat</option>
                  </select>
                </div>
                <div>
                  <label class="form-label text-xs font-bold text-slate-700 uppercase">Tujuan Pemanfaatan Data <span class="text-red-500">*</span></label>
                  <select id="ant_pemanfaatan" class="form-select text-sm rounded-xl" required>
                    <option value="Tugas Sekolah / Kuliah">Tugas Sekolah / Kuliah</option>
                    <option value="Penelitian / Skripsi / Tesis">Penelitian / Skripsi / Tesis</option>
                    <option value="Perencanaan / Kebijakan Pemerintah">Perencanaan / Kebijakan Pemerintah</option>
                    <option value="Komersial / Usaha Bisnis">Komersial / Usaha Bisnis</option>
                    <option value="Lainnya">Lainnya</option>
                  </select>
                </div>
              </div>

              <div>
                <label class="form-label text-xs font-bold text-slate-700 uppercase">
                  Apakah Data Digunakan untuk Perencanaan, Monitoring & Evaluasi Pembangunan? <span class="text-red-500">*</span>
                </label>
                <select id="ant_monev" class="form-select text-sm rounded-xl" required>
                  <option value="Ya">Ya (Untuk Perencanaan, Monitoring & Evaluasi Pembangunan)</option>
                  <option value="Tidak">Tidak</option>
                </select>
              </div>

              <div>
                <label class="form-label text-xs font-bold text-slate-700 uppercase">Rincian Data / Informasi Yang Dibutuhkan</label>
                <textarea id="ant_data_diinginkan" class="form-control text-sm rounded-xl" rows="3" placeholder="Contoh: Data Inflasi Kota Tegal tahun 2024-2025, Publikasi Kota Tegal Dalam Angka..."></textarea>
              </div>

              <!-- Foto Swafoto Kunjungan (Dual Option: Kamera vs Galeri) -->
              <div class="space-y-1.5">
                <label class="form-label text-xs font-bold text-slate-700 uppercase">
                  Foto Swafoto Kunjungan <span class="text-slate-400 font-normal lowercase">(opsional)</span>
                </label>
                
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 p-3 bg-slate-50 rounded-xl border border-slate-200">
                  <!-- Thumbnail Pratinjau Foto -->
                  <div class="flex items-center gap-3">
                    <div class="w-14 h-14 rounded-lg bg-slate-200 border border-slate-300 flex items-center justify-center shrink-0 overflow-hidden relative" id="photo_box_container">
                      <img id="photo_preview" class="w-full h-full object-cover hidden" alt="Pratinjau Foto">
                      <span id="photo_icon_placeholder" class="material-icons text-slate-400 text-xl">add_a_photo</span>
                    </div>
                  </div>

                  <!-- Tombol Aksi: Dua Opsi Terpisah -->
                  <div class="space-y-1 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                      <!-- Opsi 1: Kamera HP / Live Capture Laptop -->
                      <button type="button" id="btn_trigger_camera" class="btn btn-sm text-xs font-semibold rounded-lg flex items-center gap-1.5 cursor-pointer bg-sky-50 shadow-sm border border-sky-300 text-sky-700 hover:bg-sky-100">
                        <span class="material-icons text-sm text-sky-600">photo_camera</span>
                        <span>Ambil Foto (Kamera)</span>
                      </button>
                      <input type="file" id="input_camera_snap" accept="image/*" capture="user" class="hidden">

                      <!-- Opsi 2: Upload File / Galeri Berkas -->
                      <label for="input_gallery_file" class="btn btn-sm text-xs font-semibold rounded-lg flex items-center gap-1.5 cursor-pointer bg-white shadow-sm border border-slate-300 text-slate-700 hover:bg-slate-100">
                        <span class="material-icons text-sm text-amber-600">folder_open</span>
                        <span>Pilih dari Galeri / Berkas</span>
                      </label>
                      <input type="file" id="input_gallery_file" accept="image/*" class="hidden">
                    </div>

                    <p class="text-[11px] text-slate-500">Pilih jepret langsung via kamera HP/Laptop atau ambil file gambar dari galeri/berkas.</p>
                  </div>

                  <input type="hidden" id="ant_foto" value="">
                </div>
              </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary btn-lg w-full py-4 bg-[#003366] border-[#003366] hover:bg-[#002244] font-bold text-base flex items-center justify-center gap-2 shadow-xl rounded-xl">
              <span class="material-icons">qr_code_2</span>
              <span>Cetak Tiket & QRCode Antrian</span>
            </button>

          </form>
        </div>
        <?php endif; ?>

        <!-- Warning Card / Ketentuan Layanan (Identik dengan Web Asli) -->
        <div class="p-6 bg-amber-50 rounded-2xl border border-amber-200 text-amber-900 space-y-3 text-sm">
          <div class="flex items-center gap-2 font-bold text-amber-800 text-base">
            <span class="material-icons text-amber-600">warning</span>
            <span>Perhatian!</span>
          </div>
          <ol class="list-decimal list-inside space-y-1.5 text-xs md:text-sm text-amber-800/90 leading-relaxed">
            <li>Antrian Online ini berlaku untuk seluruh layanan yang diselenggarakan oleh PST BPS Kota Tegal.</li>
            <li>Pemohon memastikan tidak salah memilih tanggal kunjungan dan datang sesuai jadwal yang dipilih.</li>
            <li>Antrian tidak dapat diwakilkan.</li>
            <li>Pastikan mengisi data pengisian dengan benar.</li>
            <li>Pemohon harus dalam keadaan SEHAT saat datang. Terima kasih.</li>
            <li><b>Setelah pelayanan selesai</b>, mohon luangkan waktu sejenak untuk mengisi <b>Ulasan Kepuasan Layanan (SKM)</b> di menu <b><a href="bukutamu.php" class="underline text-amber-900 font-bold">Riwayat & Tiket Saya</a></b>.</li>
          </ol>
        </div>

      </div>

      <!-- Include Footer Component -->
      <?php include 'footer.php'; ?>

    </main>
  </div>

  <!-- Modal Live Stream Kamera Webcam -->
  <div class="modal fade" id="modalWebcam" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered max-w-md">
      <div class="modal-content rounded-2xl border-0 shadow-2xl overflow-hidden bg-slate-900 text-white">
        <div class="modal-header border-b border-slate-800 p-4 flex items-center justify-between">
          <h5 class="modal-title text-sm font-bold flex items-center gap-2 text-white">
            <span class="material-icons text-sky-400 text-base">photo_camera</span>
            <span>Ambil Swafoto Kamera</span>
          </h5>
          <button type="button" class="text-slate-400 hover:text-white text-lg font-bold" id="btn_close_webcam_modal">✕</button>
        </div>
        <div class="modal-body p-4 bg-black text-center space-y-3">
          <div class="relative w-full aspect-video bg-slate-950 rounded-xl overflow-hidden border border-slate-800 flex items-center justify-center">
            <video id="modal_webcam_video" class="w-full h-full object-cover" autoplay playsinline muted></video>
            <canvas id="modal_webcam_canvas" class="hidden"></canvas>
          </div>
          <p class="text-xs text-slate-400">Posisikan wajah Anda di dalam bingkai lalu klik tombol "Jepret Foto".</p>
        </div>
        <div class="modal-footer border-t border-slate-800 p-3 flex justify-between bg-slate-900">
          <button type="button" class="btn btn-sm btn-secondary text-xs rounded-lg px-4" data-bs-dismiss="modal">Batal</button>
          <button type="button" id="btn_modal_snap" class="btn btn-sm btn-primary bg-sky-600 border-sky-600 font-bold rounded-lg flex items-center gap-1.5 px-4 text-xs">
            <span class="material-icons text-sm">camera_alt</span>
            <span>Jepret Foto</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Display Tiket Digital & Print Area -->
  <div class="modal fade" id="modalTicket" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-2xl overflow-hidden border-none shadow-2xl">
        <div class="modal-header bg-[#003366] text-white p-4">
          <h5 class="modal-title font-bold text-base brand-font">Tiket Antrian Digital PST</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-6 text-center space-y-4 bg-white" id="printable-ticket">
          <!-- Header Instansi -->
          <div class="flex items-center justify-center gap-3 border-b-2 border-slate-200 pb-3">
            <img src="img/Logo_BPS.png" alt="Logo BPS" class="w-10 h-10 object-contain">
            <div class="text-left">
              <div class="text-[11px] font-extrabold text-[#003366] leading-tight brand-font">BADAN PUSAT STATISTIK</div>
              <div class="text-[10px] font-bold text-sky-600 tracking-wider">KOTA TEGAL</div>
              <div class="text-[9px] text-slate-400">Pelayanan Statistik Terpadu (PST)</div>
            </div>
          </div>

          <!-- Title Tiket -->
          <div class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">NOMOR ANTRIAN LOKET</div>
          
          <!-- Big Queue Number Box -->
          <div class="py-3 px-4 bg-sky-50 rounded-2xl border-2 border-sky-200 inline-block w-full">
            <h2 class="text-5xl font-black text-[#003366] brand-font tracking-tight" id="ticket_number">KS-01</h2>
            <div class="text-xs font-bold text-sky-700 mt-1" id="ticket_service_badge">Konsultasi Statistik</div>
          </div>

          <!-- QR Code Container (Centered & High-Contrast) -->
          <div class="py-2 flex flex-col items-center justify-center">
            <div id="qrcode_box" class="p-2 bg-white rounded-xl border-2 border-slate-200 shadow-sm flex items-center justify-center min-h-[150px] min-w-[150px]"></div>
            <div class="text-[10px] font-mono text-slate-400 mt-1.5" id="ticket_code_id">ANT-123456</div>
          </div>

          <!-- Detail Table Grid -->
          <div class="bg-slate-50 p-4 rounded-xl text-xs space-y-1.5 text-slate-700 text-left border border-slate-200">
            <div class="flex justify-between border-b border-slate-200 pb-1">
              <span class="text-slate-500">Nama Pemohon:</span>
              <span class="font-bold text-slate-900" id="ticket_name">-</span>
            </div>
            <div class="flex justify-between border-b border-slate-200 pb-1">
              <span class="text-slate-500">Tanggal Kunjungan:</span>
              <span class="font-bold text-slate-900" id="ticket_date">-</span>
            </div>
            <div class="flex justify-between border-b border-slate-200 pb-1">
              <span class="text-slate-500">Waktu Rencana:</span>
              <span class="font-bold text-slate-900" id="ticket_time">-</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Fasilitas:</span>
              <span class="font-bold text-slate-900" id="ticket_facility">Datang Langsung</span>
            </div>
          </div>

          <!-- Footer Instruction -->
          <div class="border-t border-slate-200 pt-3 text-[10px] text-slate-500 space-y-1">
            <p class="font-semibold text-slate-700">Harap datang 10 menit sebelum waktu kunjungan.</p>
            <p>Tunjukkan Kode QR ini kepada petugas meja PST BPS Kota Tegal.</p>
          </div>
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

  <!-- Mobile Offcanvas Sidebar -->
  <div class="offcanvas offcanvas-start bps-sidebar" tabindex="-1" id="mobileSidebar">
    <div class="offcanvas-header border-b border-slate-700">
      <h5 class="offcanvas-title text-white font-bold brand-font">SPST BPS KOTA TEGAL</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6 space-y-3">
      <a href="index.php" class="bps-nav-item"><span class="material-icons">home</span> Beranda Utama</a>
      <a href="register.php" class="bps-nav-item"><span class="material-icons">how_to_reg</span> Daftar Akun Pengunjung</a>
      <a href="antrian.php" class="bps-nav-item active"><span class="material-icons">confirmation_number</span> Reservasi Antrean & Layanan</a>
      <a href="bukutamu.php" class="bps-nav-item"><span class="material-icons">receipt_long</span> Riwayat & Tiket Saya</a>
      
      <?php if ($isLoggedIn): ?>
        <a href="logout.php" class="bps-nav-item text-rose-400 hover:text-rose-300 font-bold border border-rose-500/20 rounded-xl my-2">
          <span class="material-icons text-rose-400">logout</span> Keluar / Logout Sesi
        </a>
      <?php else: ?>
        <a href="login.php" class="bps-nav-item"><span class="material-icons">login</span> Masuk / Login</a>
      <?php endif; ?>
    </div>
  </div>

  <!-- DEDICATED 2-PAGE PRINT LAYOUT (EXACTLY 2 PAGES IN PDF / PRINT) -->
  <div id="print-2page-container" class="hidden print:block text-black bg-white">
    
    <!-- PAGE 1: DATA KUNJUNGAN & FORM REGISTRASI (A4 PAGE 1) -->
    <div class="print-page-1-wrapper" style="page-break-after: always; break-after: page; padding: 10mm 12mm;">
      <!-- Kop Header BPS Kota Tegal -->
      <div style="display: flex; align-items: center; gap: 12px; border-bottom: 2px solid #003366; padding-bottom: 10px; margin-bottom: 14px;">
        <img src="img/Logo_BPS.png" alt="Logo BPS" style="width: 45px; height: 45px; object-fit: contain;">
        <div>
          <div style="font-size: 14px; font-weight: 800; color: #003366; line-height: 1.2;">BADAN PUSAT STATISTIK KOTA TEGAL</div>
          <div style="font-size: 11px; font-weight: 700; color: #0284c7;">PELAYANAN STATISTIK TERPADU (PST)</div>
          <div style="font-size: 9px; color: #64748b;">Jl. Kemuning No. 34 Kota Tegal | Telp: (0283) 351881</div>
        </div>
      </div>

      <div style="text-align: center; margin-bottom: 16px;">
        <h2 style="font-size: 13px; font-weight: 800; color: #003366; text-transform: uppercase; margin: 0;">FORMULIR REGISTRASI & DATA KUNJUNGAN</h2>
        <div style="font-size: 9.5px; color: #64748b;">Sistem Pelayanan Statistik Terpadu (SPST) BPS Kota Tegal</div>
      </div>

      <!-- Ringkasan Data Tables -->
      <table style="width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 14px;">
        <thead>
          <tr style="background-color: #003366; color: #ffffff;">
            <th colspan="2" style="padding: 6px 10px; text-align: left; font-size: 11px;">I. PROFIL PEMOHON LAYANAN</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td style="padding: 6px 10px; border: 1px solid #cbd5e1; width: 35%; font-weight: bold; background-color: #f8fafc;">Nama Lengkap:</td>
            <td style="padding: 6px 10px; border: 1px solid #cbd5e1;" id="p1_nama"><?php echo htmlspecialchars($userName); ?></td>
          </tr>
          <tr>
            <td style="padding: 6px 10px; border: 1px solid #cbd5e1; font-weight: bold; background-color: #f8fafc;">NIK (KTP):</td>
            <td style="padding: 6px 10px; border: 1px solid #cbd5e1;" id="p1_nik"><?php echo htmlspecialchars($userNik ?: '-'); ?></td>
          </tr>
          <tr>
            <td style="padding: 6px 10px; border: 1px solid #cbd5e1; font-weight: bold; background-color: #f8fafc;">No. Telepon / HP:</td>
            <td style="padding: 6px 10px; border: 1px solid #cbd5e1;" id="p1_nohp"><?php echo htmlspecialchars($userNoHp ?: '-'); ?></td>
          </tr>
          <tr>
            <td style="padding: 6px 10px; border: 1px solid #cbd5e1; font-weight: bold; background-color: #f8fafc;">Instansi / Lembaga:</td>
            <td style="padding: 6px 10px; border: 1px solid #cbd5e1;" id="p1_instansi"><?php echo htmlspecialchars($userInstansi ?: '-'); ?></td>
          </tr>
        </tbody>
      </table>

      <table style="width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 14px;">
        <thead>
          <tr style="background-color: #003366; color: #ffffff;">
            <th colspan="2" style="padding: 6px 10px; text-align: left; font-size: 11px;">II. JADWAL & JENIS LAYANAN PST</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td style="padding: 6px 10px; border: 1px solid #cbd5e1; width: 35%; font-weight: bold; background-color: #f8fafc;">Jenis Layanan:</td>
            <td style="padding: 6px 10px; border: 1px solid #cbd5e1; font-weight: bold; color: #0284c7;" id="p1_layanan">Konsultasi Statistik</td>
          </tr>
          <tr>
            <td style="padding: 6px 10px; border: 1px solid #cbd5e1; font-weight: bold; background-color: #f8fafc;">Tanggal Kunjungan:</td>
            <td style="padding: 6px 10px; border: 1px solid #cbd5e1;" id="p1_tanggal">-</td>
          </tr>
          <tr>
            <td style="padding: 6px 10px; border: 1px solid #cbd5e1; font-weight: bold; background-color: #f8fafc;">Waktu Rencana:</td>
            <td style="padding: 6px 10px; border: 1px solid #cbd5e1;" id="p1_waktu">09:00 WIB</td>
          </tr>
          <tr>
            <td style="padding: 6px 10px; border: 1px solid #cbd5e1; font-weight: bold; background-color: #f8fafc;">Fasilitas Pelayanan:</td>
            <td style="padding: 6px 10px; border: 1px solid #cbd5e1;" id="p1_fasilitas">Datang Langsung Ke PST BPS Kota Tegal</td>
          </tr>
        </tbody>
      </table>

      <table style="width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 18px;">
        <thead>
          <tr style="background-color: #003366; color: #ffffff;">
            <th colspan="2" style="padding: 6px 10px; text-align: left; font-size: 11px;">III. KEPERLUAN & RINCIAN DATA</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td style="padding: 6px 10px; border: 1px solid #cbd5e1; width: 35%; font-weight: bold; background-color: #f8fafc;">Tujuan Pemanfaatan:</td>
            <td style="padding: 6px 10px; border: 1px solid #cbd5e1;" id="p1_pemanfaatan">Penelitian / Skripsi</td>
          </tr>
          <tr>
            <td style="padding: 6px 10px; border: 1px solid #cbd5e1; font-weight: bold; background-color: #f8fafc;">Perencanaan / Monev:</td>
            <td style="padding: 6px 10px; border: 1px solid #cbd5e1;" id="p1_monev">Ya</td>
          </tr>
          <tr>
            <td style="padding: 6px 10px; border: 1px solid #cbd5e1; font-weight: bold; background-color: #f8fafc;">Rincian Data Dibutuhkan:</td>
            <td style="padding: 6px 10px; border: 1px solid #cbd5e1;" id="p1_rincian_data">-</td>
          </tr>
        </tbody>
      </table>

      <div style="font-size: 9px; color: #64748b; border-top: 1px solid #cbd5e1; padding-top: 6px; text-align: right;">
        Dokumen Resmi Terverifikasi SPST BPS Kota Tegal | Tanggal Cetak: <?php echo date('d-m-Y H:i'); ?> WIB
      </div>
    </div>

    <!-- PAGE 2: TIKET ANTRIAN DIGITAL & QR CODE (A4 PAGE 2) -->
    <div class="print-page-2-wrapper" style="padding: 15mm 12mm 10mm 12mm;">
      <div style="max-width: 310px; margin: 0 auto; text-align: center; border: 2px dashed #003366; padding: 18px; border-radius: 12px; background: #ffffff;">
        
        <!-- Header Instansi -->
        <div style="display: flex; align-items: center; justify-content: center; gap: 10px; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 10px;">
          <img src="img/Logo_BPS.png" alt="Logo BPS" style="width: 38px; height: 38px; object-fit: contain;">
          <div style="text-align: left;">
            <div style="font-size: 11px; font-weight: 800; color: #003366; line-height: 1.1;">BADAN PUSAT STATISTIK</div>
            <div style="font-size: 10px; font-weight: 700; color: #0284c7;">KOTA TEGAL</div>
            <div style="font-size: 8.5px; color: #64748b;">Pelayanan Statistik Terpadu (PST)</div>
          </div>
        </div>

        <div style="font-size: 9.5px; font-weight: 700; color: #64748b; letter-spacing: 1px; margin-bottom: 4px;">NOMOR ANTRIAN LOKET</div>

        <div style="background-color: #f0f9ff; border: 2px solid #bae6fd; border-radius: 10px; padding: 10px; margin-bottom: 10px;">
          <h1 style="font-size: 40px; font-weight: 900; color: #003366; margin: 0; line-height: 1;" id="p2_ticket_number">KS-01</h1>
          <div style="font-size: 10.5px; font-weight: 700; color: #0369a1; margin-top: 3px;" id="p2_ticket_service">Konsultasi Statistik</div>
        </div>

        <div style="margin-bottom: 10px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
          <div id="p2_qrcode_box" style="padding: 5px; background: #ffffff; border: 2px solid #e2e8f0; border-radius: 8px; min-width: 125px; min-height: 125px; display: flex; align-items: center; justify-content: center;"></div>
          <div style="font-size: 9px; font-family: monospace; color: #94a3b8; margin-top: 3px;" id="p2_ticket_code_id">ANT-123456</div>
        </div>

        <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 10px; font-size: 9.5px; text-align: left; margin-bottom: 10px;">
          <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #e2e8f0; padding-bottom: 3px; margin-bottom: 3px;">
            <span style="color: #64748b;">Nama Pemohon:</span>
            <span style="font-weight: bold; color: #0f172a;" id="p2_ticket_name">-</span>
          </div>
          <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #e2e8f0; padding-bottom: 3px; margin-bottom: 3px;">
            <span style="color: #64748b;">Tanggal Kunjungan:</span>
            <span style="font-weight: bold; color: #0f172a;" id="p2_ticket_date">-</span>
          </div>
          <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #e2e8f0; padding-bottom: 3px; margin-bottom: 3px;">
            <span style="color: #64748b;">Waktu Rencana:</span>
            <span style="font-weight: bold; color: #0f172a;" id="p2_ticket_time">-</span>
          </div>
          <div style="display: flex; justify-content: space-between;">
            <span style="color: #64748b;">Fasilitas:</span>
            <span style="font-weight: bold; color: #0f172a;" id="p2_ticket_facility">Datang Langsung</span>
          </div>
        </div>

        <div style="font-size: 8.5px; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 6px;">
          <div style="font-weight: 700; color: #334155;">Harap datang 10 menit sebelum waktu kunjungan.</div>
          <div>Tunjukkan Kode QR ini kepada petugas meja PST BPS Kota Tegal.</div>
        </div>

      </div>
    </div>

  </div>

  <!-- Script Helpers -->
  <script src="js/app.js"></script>
  <script src="js/tts.js"></script>
  <script src="js/antrian.js"></script>
</body>
</html>
