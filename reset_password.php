<?php
// SPST BPS Kota Tegal - Reset Password Pengunjung
require_once __DIR__ . '/security.php';
setSecurityHeaders();
$token = trim($_GET['token'] ?? '');
$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
  <title>Reset Password Pengunjung - SPST BPS Kota Tegal</title>

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
  <link rel="stylesheet" href="css/custom.css">
</head>
<body class="bg-slate-900 font-['Inter'] text-slate-100 min-h-screen flex flex-col justify-between p-6">

  <!-- Top Bar -->
  <div class="max-w-md mx-auto w-full flex items-center justify-between pt-4">
    <a href="login.php" class="text-xs font-semibold text-slate-400 hover:text-white flex items-center gap-1 transition">
      <span class="material-icons text-sm">arrow_back</span>
      <span>Kembali ke Halaman Login</span>
    </a>
    <span class="text-xs font-bold text-sky-400 uppercase tracking-widest brand-font">SPST BPS</span>
  </div>

  <!-- Center Card -->
  <div class="max-w-md mx-auto w-full my-auto space-y-6 py-8">
    <div class="glass-card bg-slate-800/90 border-slate-700/60 p-8 space-y-6 text-slate-200">
      
      <div class="text-center space-y-2">
        <div class="w-14 h-14 bg-sky-500/10 text-sky-400 rounded-2xl flex items-center justify-center mx-auto mb-3">
          <span class="material-icons text-3xl">key</span>
        </div>
        <h1 class="text-2xl font-extrabold text-white brand-font">Atur Password Baru</h1>
        <p class="text-xs text-slate-400" id="resetInfoSubtitle">
          Verifikasi token pemulihan akun pengunjung...
        </p>
      </div>

      <!-- State: Loading / Checking Token -->
      <div id="sectionChecking" class="text-center py-6 text-slate-400 space-y-2">
        <span class="material-icons animate-spin text-3xl text-sky-400">sync</span>
        <div class="text-xs font-medium">Memeriksa keabsahan token reset password...</div>
      </div>

      <!-- State: Invalid / Expired Token -->
      <div id="sectionInvalid" class="hidden text-center py-6 space-y-4">
        <div class="w-12 h-12 bg-red-500/10 text-red-400 rounded-2xl flex items-center justify-center mx-auto">
          <span class="material-icons text-2xl">error_outline</span>
        </div>
        <div class="text-xs text-red-300 font-semibold" id="errorMessageToken">
          Token reset password tidak valid atau telah kedaluwarsa.
        </div>
        <a href="login.php" class="btn btn-sm btn-outline-light text-xs font-semibold rounded-xl px-4 py-2">
          Kembali & Ajukan Ulang
        </a>
      </div>

      <!-- Form Set New Password -->
      <form id="formSetPassword" class="hidden space-y-4">
        <input type="hidden" id="reset_token" value="<?php echo htmlspecialchars($token); ?>">

        <div class="p-3 bg-slate-900/60 rounded-xl border border-slate-700/60 text-xs text-slate-300 space-y-1">
          <div><span class="text-slate-400">Akun:</span> <span id="display_username" class="font-bold text-white"></span></div>
          <div><span class="text-slate-400">Email:</span> <span id="display_email" class="text-sky-300"></span></div>
        </div>

        <div>
          <label class="form-label text-xs font-bold text-slate-300 uppercase">Password Baru <span class="text-red-500">*</span></label>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
              <span class="material-icons text-base">lock</span>
            </span>
            <input type="password" id="new_password" class="form-control rounded-xl pl-10 pr-10 py-3 bg-slate-900 border border-slate-700 text-white placeholder-slate-500 text-xs focus:border-sky-500" placeholder="Minimal 6 karakter..." required minlength="6" autocomplete="new-password">
            <button type="button" id="toggleNewPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-white" tabindex="-1">
              <span class="material-icons text-base" id="iconNewPassword">visibility_off</span>
            </button>
          </div>
        </div>

        <div>
          <label class="form-label text-xs font-bold text-slate-300 uppercase">Konfirmasi Password Baru <span class="text-red-500">*</span></label>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
              <span class="material-icons text-base">lock_reset</span>
            </span>
            <input type="password" id="confirm_password" class="form-control rounded-xl pl-10 pr-10 py-3 bg-slate-900 border border-slate-700 text-white placeholder-slate-500 text-xs focus:border-sky-500" placeholder="Ketik ulang password baru..." required minlength="6" autocomplete="new-password">
            <button type="button" id="toggleConfirmPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-white" tabindex="-1">
              <span class="material-icons text-base" id="iconConfirmPassword">visibility_off</span>
            </button>
          </div>
        </div>

        <button type="submit" id="btnSavePassword" class="btn btn-primary w-full py-3 bg-sky-600 hover:bg-sky-500 border-none font-bold text-xs rounded-xl shadow-lg flex items-center justify-center gap-2">
          <span class="material-icons text-base">check_circle</span>
          <span>Simpan Password Baru</span>
        </button>
      </form>

    </div>
  </div>

  <!-- Footer -->
  <div class="text-center text-xs text-slate-500 pb-4">
    SPST BPS Kota Tegal © 2026
  </div>

  <script src="js/app.js"></script>
  <script src="js/reset-password.js"></script>
</body>
</html>
