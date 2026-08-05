<?php
// SPST BPS Kota Tegal - Form Registrasi Akun Pengunjung
require_once __DIR__ . '/security.php';
setSecurityHeaders();
$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
  <title>Registrasi Akun Pengunjung - SPST BPS Kota Tegal</title>

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
<body class="bg-slate-900 font-['Inter'] text-slate-100 min-h-screen flex items-center justify-center p-4 md:p-8">

  <div class="w-full max-w-2xl space-y-6">
    
    <!-- Top Nav Back Link -->
    <div class="flex items-center justify-between">
      <a href="index.php" class="text-xs font-semibold text-slate-400 hover:text-white flex items-center gap-1 transition">
        <span class="material-icons text-sm">arrow_back</span>
        <span>Kembali ke Beranda</span>
      </a>
      <span class="text-xs font-bold text-sky-400 uppercase tracking-widest">Portal Pengunjung SPST</span>
    </div>

    <!-- Main Registration Box -->
    <div class="glass-card bg-slate-800/90 border-slate-700/60 p-6 md:p-8 space-y-6 text-slate-200 shadow-2xl rounded-3xl">
      
      <div class="text-center space-y-2">
        <img src="img/Logo_BPS.png" alt="Logo BPS Kota Tegal" class="w-14 h-14 object-contain mx-auto mb-2 filter drop-shadow">
        <h1 class="text-2xl font-extrabold text-white brand-font">Registrasi Akun Pengunjung</h1>
        <p class="text-xs text-slate-400 max-w-md mx-auto">
          Daftarkan identitas Anda satu kali saja untuk kemudahan memesan antrean dan layanan statistik BPS Kota Tegal kapan pun.
        </p>
      </div>

      <form id="formRegister" class="space-y-5">
        
        <!-- Username & Password Row -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="form-label text-xs font-bold text-slate-300 uppercase">Username / ID Pengguna <span class="text-red-400">*</span></label>
            <input type="text" id="reg_username" class="form-control rounded-xl text-sm" placeholder="Contoh: ahmad_fauzi" required>
          </div>
          <div>
            <label class="form-label text-xs font-bold text-slate-300 uppercase">Password <span class="text-red-400">*</span></label>
            <input type="password" id="reg_password" class="form-control rounded-xl text-sm" placeholder="••••••••" required>
          </div>
        </div>

        <!-- NIK & Nama Lengkap -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="form-label text-xs font-bold text-slate-300 uppercase">NIK (Nomor Induk Kependudukan) <span class="text-red-400">*</span></label>
            <input type="text" id="reg_nik" class="form-control rounded-xl text-sm" placeholder="16 Digit NIK Sesuai KTP" maxlength="16" minlength="16" pattern="[0-9]{16}" inputmode="numeric" required>
          </div>
          <div>
            <label class="form-label text-xs font-bold text-slate-300 uppercase">Nama Lengkap <span class="text-red-400">*</span></label>
            <input type="text" id="reg_name" class="form-control rounded-xl text-sm" placeholder="Nama lengkap Anda..." required>
          </div>
        </div>

        <!-- Nomor HP & Email -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="form-label text-xs font-bold text-slate-300 uppercase">Nomor HP / WhatsApp <span class="text-red-400">*</span></label>
            <input type="tel" id="reg_nohp" class="form-control rounded-xl text-sm" placeholder="081234567890" required>
          </div>
          <div>
            <label class="form-label text-xs font-bold text-slate-300 uppercase">Alamat Email</label>
            <input type="email" id="reg_email" class="form-control rounded-xl text-sm" placeholder="email@domain.com">
          </div>
        </div>
          <div>
            <label class="form-label text-xs font-bold text-slate-300 uppercase">Jenis Kelamin <span class="text-red-400">*</span></label>
            <select id="reg_jk" class="form-select rounded-xl text-sm" required>
              <option value="">-- Pilih Jenis Kelamin --</option>
              <option value="Laki Laki">Laki Laki</option>
              <option value="Perempuan">Perempuan</option>
            </select>
          </div>
        </div>

        <!-- Kelompok Umur & Pendidikan -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="form-label text-xs font-bold text-slate-300 uppercase">Kelompok Umur <span class="text-red-400">*</span></label>
            <select id="reg_umur" class="form-select rounded-xl text-sm" required>
              <option value="">-- Pilih Usia --</option>
              <option value="< 17 tahun">&lt; 17 tahun</option>
              <option value="17-25 tahun">17-25 tahun</option>
              <option value="26-34 tahun">26-34 tahun</option>
              <option value="35-44 tahun">35-44 tahun</option>
              <option value="45+ tahun">45+ tahun</option>
            </select>
          </div>
          <div>
            <label class="form-label text-xs font-bold text-slate-300 uppercase">Pendidikan Terakhir <span class="text-red-400">*</span></label>
            <select id="reg_pendidikan" class="form-select rounded-xl text-sm" required>
              <option value="">-- Pilih Pendidikan --</option>
              <option value="SMA Ke Bawah">SMA Ke Bawah</option>
              <option value="D1/D2/D3">D1/D2/D3</option>
              <option value="D4-S1">D4-S1</option>
              <option value="S2-S3">S2-S3</option>
            </select>
          </div>
        </div>

        <!-- Pekerjaan & Instansi -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="form-label text-xs font-bold text-slate-300 uppercase">Pekerjaan <span class="text-red-400">*</span></label>
            <select id="reg_pekerjaan" class="form-select rounded-xl text-sm" required>
              <option value="">-- Pilih Pekerjaan --</option>
              <option value="Mahasiswa">Mahasiswa</option>
              <option value="Peneliti/Dosen">Peneliti/Dosen</option>
              <option value="Pegawai Negeri / TNI POLRI">Pegawai Negeri / TNI POLRI</option>
              <option value="Pegawai Swasta">Pegawai Swasta</option>
              <option value="Wiraswasta">Wiraswasta</option>
              <option value="Lainnya">Lainnya</option>
            </select>
          </div>
          <div>
            <label class="form-label text-xs font-bold text-slate-300 uppercase">Nama Instansi / Perguruan Tinggi <span class="text-red-400">*</span></label>
            <input type="text" id="reg_instansi" class="form-control rounded-xl text-sm" placeholder="Contoh: UPS Tegal, Bappeda, dll." required>
          </div>
        </div>

        <!-- Kategori Instansi -->
        <div>
          <label class="form-label text-xs font-bold text-slate-300 uppercase">Kategori Instansi <span class="text-red-400">*</span></label>
          <select id="reg_kategori_instansi" class="form-select rounded-xl text-sm" required>
            <option value="">-- Pilih Kategori Instansi --</option>
            <option value="Sekolah/Universitas">Sekolah / Universitas</option>
            <option value="Pemda">Pemerintah Daerah (Pemda)</option>
            <option value="Instansi Pemerintah Lainnya">Instansi Pemerintah Lainnya</option>
            <option value="BUMN/BUMD">BUMN / BUMD</option>
            <option value="Perusahaan Swasta">Perusahaan Swasta</option>
            <option value="Masyarakat Umum">Masyarakat Umum</option>
          </select>
        </div>

        <!-- Submit Button -->
        <button type="submit" id="btnRegister" class="w-full btn btn-primary bg-sky-600 hover:bg-sky-500 border-none py-3 font-bold rounded-xl text-white shadow-lg flex items-center justify-center gap-2">
          <span class="material-icons">how_to_reg</span>
          <span>Daftar Akun Pengunjung</span>
        </button>
      </form>

      <div class="text-center pt-2 border-t border-slate-700/60">
        <p class="text-xs text-slate-400">
          Sudah punya akun? 
          <a href="login.php" class="text-sky-400 hover:underline font-semibold">Masuk di sini</a>
        </p>
      </div>

    </div>
  </div>

  <script>
  document.getElementById('formRegister').addEventListener('submit', async function(e) {
    e.preventDefault();

    const nik = document.getElementById('reg_nik').value.trim();
    if (!/^[0-9]{16}$/.test(nik)) {
      Swal.fire('Format NIK Salah', 'NIK harus terdiri dari tepat 16 digit angka sesuai KTP.', 'warning');
      return;
    }

    const btn = document.getElementById('btnRegister');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Memproses...';

    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const payload = {
      action: 'register_pengunjung',
      username: document.getElementById('reg_username').value.trim(),
      password: document.getElementById('reg_password').value,
      name: document.getElementById('reg_name').value.trim(),
      nik: nik,
      nohp: document.getElementById('reg_nohp').value.trim(),
      email: document.getElementById('reg_email').value.trim(),
      jenis_kelamin: document.getElementById('reg_jk').value,
      umur: document.getElementById('reg_umur').value,
      pendidikan: document.getElementById('reg_pendidikan').value,
      pekerjaan: document.getElementById('reg_pekerjaan').value,
      instansi: document.getElementById('reg_instansi').value.trim(),
      kategori_instansi: document.getElementById('reg_kategori_instansi').value,
      csrf_token: csrfToken
    };

    try {
      const res = await fetch('api.php?action=register_pengunjung', {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify(payload)
      });

      const text = await res.text();
      let data = {};
      try {
        data = JSON.parse(text);
      } catch (e) {
        console.error('Non-JSON server response:', text);
        Swal.fire('Error Server', 'Respon server tidak valid: ' + text.substring(0, 150), 'error');
        return;
      }

      if (data.status === 'success') {
        Swal.fire({
          icon: 'success',
          title: 'Registrasi Berhasil!',
          text: 'Akun Anda berhasil dibuat. Silakan login untuk melakukan reservasi antrean.',
          confirmButtonText: 'Masuk Sekarang',
          confirmButtonColor: '#0284c7'
        }).then(() => {
          window.location.href = 'login.php';
        });
      } else {
        Swal.fire('Gagal', data.message || 'Terjadi kesalahan saat registrasi', 'error');
      }
    } catch (err) {
      console.error('Fetch error:', err);
      Swal.fire('Error Jaringan', 'Gagal terhubung ke server: ' + (err.message || 'Koneksi terputus'), 'error');
    } finally {
      btn.disabled = false;
      btn.innerHTML = '<span class="material-icons">how_to_reg</span><span>Daftar Akun Pengunjung</span>';
    }
  });
  </script>

</body>
</html>
