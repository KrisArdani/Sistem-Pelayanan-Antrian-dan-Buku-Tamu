<?php
// SPST BPS Kota Tegal - Sistem Pelayanan Statistik Terpadu
require_once __DIR__ . '/security.php';
setSecurityHeaders();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Jika pengguna sudah login, alihkan langsung ke beranda / panel sesuai peran
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['user_role'] ?? 'pengunjung';
    if ($role === 'petugas') {
        header("Location: admin/antrian.php");
    } else if ($role === 'admin' || $role === 'kepala') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
  <title>Login - SPST BPS Kota Tegal</title>

  <!-- Tailwind CSS Play CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Bootstrap 5.3.8 CSS & Bundle -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Icons & Fonts -->
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- Custom CSS -->
  <link rel="stylesheet" href="css/custom.css">
</head>
<body class="bg-slate-950 font-['Inter'] text-slate-100 min-h-screen antialiased">

  <!-- Main Split Screen -->
  <div class="min-h-screen grid grid-cols-1 lg:grid-cols-12">
    
    <!-- LEFT COLUMN: Minimalist Brand Showcase -->
    <div class="lg:col-span-6 bg-gradient-to-b from-[#002B5B] to-[#0B132B] p-8 lg:p-14 flex flex-col justify-between hidden lg:flex relative overflow-hidden">
      
      <!-- Subtle Ambient Glow -->
      <div class="absolute top-0 left-0 w-80 h-80 bg-sky-500/10 rounded-full blur-3xl pointer-events-none"></div>

      <!-- Header Logo -->
      <div class="flex items-center gap-3">
        <img src="img/Logo_BPS.png" alt="Logo BPS Kota Tegal" class="w-11 h-11 object-contain filter drop-shadow">
        <div>
          <h2 class="text-white font-extrabold text-lg brand-font leading-none tracking-wide">SPST</h2>
          <p class="text-[11px] text-sky-300 font-semibold uppercase tracking-wider mt-0.5">BPS Kota Tegal</p>
        </div>
      </div>

      <!-- Center Hero Content -->
      <div class="my-auto space-y-6 max-w-md">
        <div class="space-y-3">
          <h1 class="text-3xl lg:text-4xl font-extrabold text-white brand-font leading-tight">
            Sistem Pelayanan Statistik Terpadu
          </h1>
          <p class="text-slate-300 text-sm leading-relaxed">
            Portal layanan data, konsultasi statistik, dan reservasi antrean online BPS Kota Tegal.
          </p>
        </div>

        <!-- Clean Illustration Showcase -->
        <div class="rounded-2xl overflow-hidden border border-white/10 shadow-2xl bg-slate-900/60">
          <img src="img/login_hero_illustration.jpg" alt="Ilustrasi SPST BPS Kota Tegal" class="w-full h-64 object-cover">
        </div>
      </div>

      <!-- Footer Info -->
      <div class="text-xs text-slate-400">
        © 2026 Badan Pusat Statistik Kota Tegal. Seluruh Hak Cipta Dilindungi.
      </div>

    </div>

    <!-- RIGHT COLUMN: Clean Login Form -->
    <div class="lg:col-span-6 bg-slate-900 p-8 sm:p-12 lg:p-16 flex flex-col justify-between min-h-screen">
      
      <!-- Top Navigation -->
      <div class="flex items-center justify-between">
        <a href="index.php" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-400 hover:text-white transition">
          <span class="material-icons text-sm">arrow_back</span>
          <span>Kembali ke Beranda</span>
        </a>

        <!-- Mobile Logo Display -->
        <div class="lg:hidden flex items-center gap-2">
          <img src="img/Logo_BPS.png" alt="Logo BPS" class="w-7 h-7 object-contain">
          <span class="text-xs font-bold text-white brand-font">SPST BPS</span>
        </div>
      </div>

      <!-- Main Login Container -->
      <div class="my-auto max-w-sm mx-auto w-full space-y-8 py-6">
        
        <!-- Header Title -->
        <div class="space-y-2">
          <h2 class="text-2xl sm:text-3xl font-bold text-white brand-font">Masuk ke Akun Anda</h2>
          <p class="text-xs text-slate-400">
            Gunakan username dan password pengguna Anda.
          </p>
        </div>

        <!-- Form Login -->
        <form id="formLogin" class="space-y-5">
          
          <!-- Username Input -->
          <div class="space-y-2">
            <label class="form-label text-xs font-semibold text-slate-300 uppercase tracking-wider">Username / ID Pengguna</label>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <span class="material-icons text-lg">person_outline</span>
              </span>
              <input type="text" id="login_username" class="form-control rounded-xl pl-10 pr-4 py-3 bg-slate-800 border border-slate-700 text-white placeholder-slate-500 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 text-sm transition" placeholder="Username..." required autocomplete="username">
            </div>
          </div>

          <!-- Password Input -->
          <div class="space-y-2">
            <label class="form-label text-xs font-semibold text-slate-300 uppercase tracking-wider">Password</label>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <span class="material-icons text-lg">lock_outline</span>
              </span>
              <input type="password" id="login_password" class="form-control rounded-xl pl-10 pr-11 py-3 bg-slate-800 border border-slate-700 text-white placeholder-slate-500 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 text-sm transition" placeholder="••••••••" required autocomplete="current-password">
              <button type="button" id="btnTogglePassword" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-white transition" title="Tampilkan / Sembunyikan Password">
                <span class="material-icons text-lg" id="iconPassword">visibility_off</span>
              </button>
            </div>
          </div>

          <!-- Submit Button -->
          <button type="submit" id="btnSubmitLogin" class="btn w-full py-3 bg-[#003366] hover:bg-[#002B5B] text-white font-semibold text-sm rounded-xl border-none shadow-md flex items-center justify-center gap-2 transition">
            <span class="material-icons text-base">login</span>
            <span>Masuk Sekarang</span>
          </button>
        </form>

        <!-- Register Link -->
        <div class="pt-2 text-center text-xs text-slate-400">
          Belum punya akun pengunjung? 
          <a href="register.php" class="text-sky-400 hover:text-sky-300 font-semibold underline ml-1">Daftar Akun Baru</a>
        </div>

        <?php if (defined('APP_ENV') && APP_ENV === 'development'): ?>
        <!-- Collapsible Demo Account Box (Dev Mode Only) -->
        <details class="text-xs text-slate-400 bg-slate-800/60 rounded-xl border border-slate-700/60 p-3">
          <summary class="font-semibold cursor-pointer text-slate-300 hover:text-white flex items-center justify-between">
            <span>Akun Simulasi Demo</span>
            <span class="material-icons text-sm">expand_more</span>
          </summary>
          <div class="pt-3 space-y-1.5 font-mono text-[11px] border-t border-slate-700/60 mt-2">
            <div>• <b>Pengunjung:</b> <code>ahmad_fauzi</code> / <code>petugas123</code></div>
            <div>• <b>Petugas PST:</b> <code>petugas</code> / <code>petugas123</code></div>
            <div>• <b>Admin:</b> <code>admin</code> / <code>admin123</code></div>
            <div>• <b>Kepala BPS:</b> <code>kepala</code> / <code>kepala123</code></div>
          </div>
        </details>
        <?php endif; ?>

      </div>

      <!-- Mobile Copyright Footer -->
      <div class="lg:hidden text-center text-xs text-slate-500 pt-4">
        Hak Cipta © 2026 BPS Kota Tegal
      </div>

    </div>

  </div>

  <!-- Scripts -->
  <script src="js/app.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      // Toggle Password Visibility
      const btnToggle = document.getElementById('btnTogglePassword');
      const inputPass = document.getElementById('login_password');
      const iconPass = document.getElementById('iconPassword');

      if (btnToggle && inputPass && iconPass) {
        btnToggle.addEventListener('click', () => {
          if (inputPass.type === 'password') {
            inputPass.type = 'text';
            iconPass.textContent = 'visibility';
          } else {
            inputPass.type = 'password';
            iconPass.textContent = 'visibility_off';
          }
        });
      }

      // Handle Login Form Submit
      const formLogin = document.getElementById('formLogin');
      const btnSubmit = document.getElementById('btnSubmitLogin');

      if (formLogin) {
        formLogin.addEventListener('submit', async (e) => {
          e.preventDefault();
          const u = document.getElementById('login_username').value.trim();
          const p = document.getElementById('login_password').value.trim();

          if (btnSubmit) {
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> <span>Memproses...</span>`;
          }

          try {
            const result = await loginUser(u, p);
            if (result.success) {
              Swal.fire({
                icon: 'success',
                title: 'Login Berhasil!',
                text: `Selamat datang kembali, ${result.user.name}.`,
                showConfirmButton: false,
                timer: 1400
              }).then(() => {
                window.location.href = result.user.redirect;
              });
            } else {
              Swal.fire({
                icon: 'error',
                title: 'Gagal Login',
                text: result.message || 'Username atau password salah',
                confirmButtonColor: '#003366'
              });
              if (btnSubmit) {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = `<span class="material-icons text-base">login</span> <span>Masuk Sekarang</span>`;
              }
            }
          } catch (err) {
            Swal.fire({
              icon: 'error',
              title: 'Terjadi Kesalahan',
              text: 'Gagal terhubung ke server login.',
              confirmButtonColor: '#003366'
            });
            if (btnSubmit) {
              btnSubmit.disabled = false;
              btnSubmit.innerHTML = `<span class="material-icons text-base">login</span> <span>Masuk Sekarang</span>`;
            }
          }
        });
      }
    });
  </script>
</body>
</html>
