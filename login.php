<?php
// TOBASA BPS Kota Tegal - Login Panel
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
  <title>Login Internal - TOBASA BPS Kota Tegal</title>

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
  <link rel="stylesheet" href="css/custom.css">
</head>
<body class="bg-slate-900 font-['Inter'] text-slate-100 min-h-screen flex items-center justify-center p-6">

  <div class="w-full max-w-md space-y-6">
    
    <!-- Header Logo & Back link -->
    <div class="flex items-center justify-between">
      <a href="index.php" class="text-xs font-semibold text-slate-400 hover:text-white flex items-center gap-1 transition">
        <span class="material-icons text-sm">arrow_back</span>
        <span>Kembali ke Beranda</span>
      </a>
      <span class="text-xs font-bold text-sky-400 uppercase tracking-widest">Portal Internal</span>
    </div>

    <!-- Login Box -->
    <div class="glass-card bg-slate-800/90 border-slate-700/60 p-8 space-y-6 text-slate-200">
      
      <div class="text-center space-y-2">
        <img src="img/Logo_BPS.png" alt="Logo BPS Kota Tegal" class="w-16 h-16 object-contain mx-auto mb-3 filter drop-shadow">
        <h1 class="text-2xl font-extrabold text-white brand-font">Masuk ke Portal TOBASA</h1>
        <p class="text-xs text-slate-400">Gunakan akun pengunjung atau akun internal BPS Kota Tegal.</p>
      </div>

      <!-- Login Form -->
      <form id="formLogin" class="space-y-4">
        <div>
          <label class="form-label text-xs font-bold text-slate-300 uppercase">Username / ID Pengguna</label>
          <input type="text" id="login_username" class="form-control rounded-xl" placeholder="Username..." required>
        </div>

        <div>
          <label class="form-label text-xs font-bold text-slate-300 uppercase">Password</label>
          <input type="password" id="login_password" class="form-control rounded-xl" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn btn-primary w-full py-3 bg-sky-600 hover:bg-sky-500 border-none font-bold text-sm flex items-center justify-center gap-2 shadow-lg shadow-sky-600/30">
          <span class="material-icons text-base">login</span>
          <span>Masuk Sekarang</span>
        </button>
      </form>

      <div class="text-center pt-3 border-t border-slate-700/60 space-y-2">
        <p class="text-xs text-slate-400">
          Belum punya akun pengunjung?
        </p>
        <a href="register.php" class="btn btn-outline-info w-full py-2.5 text-xs font-bold rounded-xl border-sky-500/50 text-sky-300 hover:bg-sky-500/20">
          <span class="material-icons text-sm align-middle">person_add</span> Daftar Akun Pengunjung Baru
        </a>
      </div>

      <?php if (defined('APP_ENV') && APP_ENV === 'development'): ?>
      <!-- Account Info Hint (Development Mode Only) -->
      <div class="p-4 bg-slate-900/60 rounded-xl border border-slate-700/50 text-xs space-y-2 text-slate-400">
        <div class="font-bold text-sky-400">Akun Simulasi Demo:</div>
        <div class="space-y-1 font-mono text-[11px]">
          <div>• <b>Pengunjung:</b> <code>ahmad_fauzi</code> / <code>petugas123</code></div>
          <div>• <b>Petugas PST:</b> <code>petugas</code> / <code>petugas123</code></div>
          <div>• <b>Admin:</b> <code>admin</code> / <code>admin123</code></div>
          <div>• <b>Kepala BPS:</b> <code>kepala</code> / <code>kepala123</code></div>
        </div>
      </div>
      <?php endif; ?>

    </div>

    <p class="text-center text-xs text-slate-500">
      Hak Cipta © 2026 Badan Pusat Statistik Kota Tegal
    </p>

  </div>

  <script src="js/app.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const formLogin = document.getElementById('formLogin');
      if (formLogin) {
        formLogin.addEventListener('submit', async (e) => {
          e.preventDefault();
          const u = document.getElementById('login_username').value.trim();
          const p = document.getElementById('login_password').value.trim();

          const result = await loginUser(u, p);
          if (result.success) {
            Swal.fire({
              icon: 'success',
              title: 'Login Berhasil!',
              text: `Selamat datang, ${result.user.name}.`,
              showConfirmButton: false,
              timer: 1500
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
          }
        });
      }
    });
  </script>
</body>
</html>
