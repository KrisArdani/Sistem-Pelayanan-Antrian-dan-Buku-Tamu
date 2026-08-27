<?php
// SPST BPS Kota Tegal - Smart Dynamic Progress Stepper Bar (Solid Colors)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/koneksi.php';

$isUserLoggedIn = isset($_SESSION['user_id']);
$userId = (int)($_SESSION['user_id'] ?? 0);
$userNik = trim($_SESSION['user_nik'] ?? '');
$userEmail = trim($_SESSION['user_email'] ?? '');
$userNohp = trim($_SESSION['user_nohp'] ?? '');
$userName = trim($_SESSION['user_name'] ?? '');

// Query ticket status from database
$hasActiveTicket = false;
$activeTicket = null;
$hasCompletedTicket = false;
$completedTicket = null;

if ($isUserLoggedIn && isset($conn) && $conn instanceof mysqli) {
    // 1. Check for active ticket (Menunggu, Dipanggil, Dilayani) strictly for this account
    $sqlActive = "SELECT id, nomor, kode_antrian, status, tanggal, waktu, layanan, pendapat, catatan FROM antrian 
                  WHERE status IN ('Menunggu', 'Dipanggil', 'Dilayani') 
                    AND (
                      (? > 0 AND user_id = ?) 
                      OR (? != '' AND LENGTH(?) = 16 AND nik = ?) 
                    ) 
                  ORDER BY id DESC LIMIT 1";
    $stmt = $conn->prepare($sqlActive);
    if ($stmt) {
        $stmt->bind_param("iisss", $userId, $userId, $userNik, $userNik, $userNik);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $hasActiveTicket = true;
            $activeTicket = $row;
        }
        $stmt->close();
    }

    // 2. Check for completed ticket TODAY for current visit SKM review prompt
    $sqlCompleted = "SELECT id, nomor, kode_antrian, status, tanggal, waktu, layanan, pendapat, catatan FROM antrian 
                     WHERE status = 'Selesai' 
                       AND DATE(tanggal) = CURDATE()
                       AND (
                         (? > 0 AND user_id = ?) 
                         OR (? != '' AND LENGTH(?) = 16 AND nik = ?) 
                       ) 
                     ORDER BY id DESC LIMIT 1";
    $stmt = $conn->prepare($sqlCompleted);
    if ($stmt) {
        $stmt->bind_param("iisss", $userId, $userId, $userNik, $userNik, $userNik);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $hasCompletedTicket = true;
            $completedTicket = $row;
        }
        $stmt->close();
    }
}

// Stages status calculation
$latestTicket = $hasActiveTicket ? $activeTicket : $completedTicket;

$step1Done = $isUserLoggedIn;
$step2Done = $isUserLoggedIn;

// If user has active ticket -> Step 3 & 4 done, currently at Step 4/5
// If user has completed ticket and has NOT clicked "Pesan Antrean Baru" yet -> Stage 5 (Kunjungan Selesai & Ulasan)
// If user has NO active ticket and wants new reservation -> Stage 3 (Pesan Antrean Baru)
if ($hasActiveTicket) {
    $step3Done = true;
    $step4Done = true;
    $step5Done = false;
    $currentStage = 4;
} else if ($hasCompletedTicket) {
    $step3Done = true;
    $step4Done = true;
    $step5Done = true;
    $currentStage = 5;
} else {
    $step3Done = false;
    $step4Done = false;
    $step5Done = false;
    $currentStage = $isUserLoggedIn ? 3 : 1;
}
?>

<!-- ----------------------------------------------------
     SMART DYNAMIC PROGRESS TIMELINE STEPPER BAR (SOLID COLORS)
     ---------------------------------------------------- -->
<div id="spst-stepper-container" class="bg-white p-6 rounded-2xl border border-slate-200 shadow-md space-y-6 transition-all duration-300">
  
  <!-- Header Bar -->
  <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
    <div>
      <h2 class="text-base md:text-lg font-black text-slate-900 brand-font flex items-center gap-2">
        <span class="material-icons text-sky-600 text-2xl">alt_route</span>
        <span>Alur & Langkah Pendaftaran Layanan PST</span>
      </h2>
      <p class="text-xs text-slate-500">
        Status alur otomatis terdeteksi secara langsung (Real-Time).
      </p>
    </div>
    
    <div class="flex items-center gap-2 shrink-0">
      <span id="stepper-stage-badge" class="text-xs font-extrabold text-sky-800 bg-sky-100 px-3.5 py-1.5 rounded-full border border-sky-300 shadow-sm">
        Progres: Tahap <span id="current-stage-num"><?php echo $currentStage; ?></span> dari 5
      </span>
    </div>
  </div>

  <!-- Completed Stage 5 Prompt Banner -->
  <div id="completed-stage5-banner" class="<?php echo ($step5Done && !$hasActiveTicket) ? '' : 'hidden'; ?> p-4 bg-emerald-50 rounded-2xl border border-emerald-300 flex flex-col sm:flex-row items-center justify-between gap-3 text-emerald-950 shadow-sm">
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold text-lg shrink-0 shadow-md">
        <span class="material-icons">task_alt</span>
      </div>
      <div class="text-xs space-y-0.5">
        <div class="font-extrabold text-sm text-emerald-900">
          Kunjungan Anda (No: #<span id="banner-ticket-num"><?php echo htmlspecialchars($completedTicket['nomor'] ?? $completedTicket['kode_antrian'] ?? ''); ?></span>) Telah Selesai!
        </div>
        <div class="text-emerald-800">
          Terima kasih atas kunjungan Anda di PST BPS Kota Tegal. Silakan beri ulasan SKM atau tekan tombol pesan antrean baru untuk kunjungan berikutnya.
        </div>
      </div>
    </div>
    <div class="flex items-center gap-2 shrink-0">
      <button type="button" onclick="openSKMModalFromStepper(<?php echo (int)($completedTicket['id'] ?? 0); ?>, '<?php echo htmlspecialchars($completedTicket['pendapat'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($completedTicket['catatan'] ?? '', ENT_QUOTES); ?>')" class="btn btn-warning bg-amber-500 hover:bg-amber-600 text-slate-900 btn-sm text-xs font-black px-3.5 py-2 rounded-xl border-none shadow flex items-center gap-1">
        <span class="material-icons text-sm">star</span> ⭐ Ulasan SKM
      </button>
      <button type="button" onclick="startNewQueueFlow()" class="btn btn-emerald bg-emerald-600 hover:bg-emerald-700 text-white btn-sm text-xs font-black px-4 py-2 rounded-xl border-none shadow flex items-center gap-1">
        <span class="material-icons text-sm">add_circle</span> ➕ Pesan Antrean Baru
      </button>
    </div>
  </div>

  <!-- Connected Timeline Container -->
  <div class="relative px-2 py-2">
    
    <!-- Continuous Connecting Background Line (Desktop - Solid) -->
    <div class="hidden lg:block absolute top-1/2 left-10 right-10 -translate-y-1/2 h-2 bg-gradient-to-r from-sky-400 via-amber-400 to-purple-500 rounded-full z-0"></div>

    <!-- 5 Steps Flow Grid -->
    <div id="stepper-grid-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 relative z-10">
      
      <!-- ================= STEP 1: REGISTRASI AKUN ================= -->
      <div id="step-card-1">
      <?php if ($step1Done): ?>
        <div class="bg-emerald-50 p-3.5 rounded-2xl border-2 border-emerald-500 shadow-sm flex flex-col items-center text-center space-y-2 relative pointer-events-none select-none">
          <div class="w-10 h-10 rounded-full bg-emerald-600 text-white font-black text-sm flex items-center justify-center shadow-md">
            <span class="material-icons text-base font-extrabold">check</span>
          </div>
          <div>
            <div class="text-xs font-extrabold text-emerald-950">1. Registrasi Akun</div>
            <div class="text-[10px] font-bold text-emerald-700 mt-0.5">✓ Akun Terdaftar</div>
          </div>
          <div class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-900 bg-emerald-200 px-2 py-0.5 rounded-full mt-1 border border-emerald-300">
            <span>✓ Selesai</span>
          </div>
          <div class="hidden lg:flex absolute -right-4 top-1/2 -translate-y-1/2 z-20 w-8 h-8 rounded-full bg-emerald-600 text-white shadow-lg items-center justify-center border-2 border-white font-extrabold">
            <span class="material-icons text-sm">east</span>
          </div>
        </div>
      <?php else: ?>
        <a href="register.php" class="group bg-white hover:bg-sky-50 p-3.5 rounded-2xl border-2 border-sky-500 shadow-md hover:shadow-lg transition-all duration-300 flex flex-col items-center text-center space-y-2 relative">
          <div class="w-10 h-10 rounded-full bg-sky-600 text-white font-black text-sm flex items-center justify-center shadow-md shadow-sky-500/30 group-hover:scale-110 transition">1</div>
          <div>
            <div class="text-xs font-extrabold text-slate-900 group-hover:text-sky-700">1. Registrasi Akun</div>
            <div class="text-[10px] font-semibold text-sky-700 mt-0.5">NIK KTP 16 Digit</div>
          </div>
          <div class="inline-flex items-center gap-1 text-[10px] font-bold text-sky-700 bg-sky-100 px-2 py-0.5 rounded-full mt-1 border border-sky-200">
            <span>Daftar Akun</span>
            <span class="material-icons text-xs">arrow_forward</span>
          </div>
          <div class="hidden lg:flex absolute -right-4 top-1/2 -translate-y-1/2 z-20 w-8 h-8 rounded-full bg-sky-600 text-white shadow-lg items-center justify-center border-2 border-white font-extrabold">
            <span class="material-icons text-sm">east</span>
          </div>
        </a>
      <?php endif; ?>
      </div>

      <!-- ================= STEP 2: MASUK / LOGIN ================= -->
      <div id="step-card-2">
      <?php if ($step2Done): ?>
        <div class="bg-emerald-50 p-3.5 rounded-2xl border-2 border-emerald-500 shadow-sm flex flex-col items-center text-center space-y-2 relative pointer-events-none select-none">
          <div class="w-10 h-10 rounded-full bg-emerald-600 text-white font-black text-sm flex items-center justify-center shadow-md">
            <span class="material-icons text-base font-extrabold">check</span>
          </div>
          <div>
            <div class="text-xs font-extrabold text-emerald-950">2. Masuk / Login</div>
            <div class="text-[10px] font-bold text-emerald-700 mt-0.5">✓ Sesi Login Aktif</div>
          </div>
          <div class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-900 bg-emerald-200 px-2 py-0.5 rounded-full mt-1 border border-emerald-300">
            <span>✓ Selesai</span>
          </div>
          <div class="hidden lg:flex absolute -right-4 top-1/2 -translate-y-1/2 z-20 w-8 h-8 rounded-full bg-emerald-600 text-white shadow-lg items-center justify-center border-2 border-white font-extrabold">
            <span class="material-icons text-sm">east</span>
          </div>
        </div>
      <?php else: ?>
        <a href="login.php" class="group bg-white hover:bg-blue-50 p-3.5 rounded-2xl border-2 border-blue-500 shadow-md hover:shadow-lg transition-all duration-300 flex flex-col items-center text-center space-y-2 relative">
          <div class="w-10 h-10 rounded-full bg-blue-600 text-white font-black text-sm flex items-center justify-center shadow-md shadow-blue-500/30 group-hover:scale-110 transition">2</div>
          <div>
            <div class="text-xs font-extrabold text-slate-900 group-hover:text-blue-700">2. Masuk / Login</div>
            <div class="text-[10px] font-semibold text-blue-700 mt-0.5">Username & Password</div>
          </div>
          <div class="inline-flex items-center gap-1 text-[10px] font-bold text-blue-700 bg-blue-100 px-2 py-0.5 rounded-full mt-1 border border-blue-200">
            <span>Masuk Portal</span>
            <span class="material-icons text-xs">arrow_forward</span>
          </div>
          <div class="hidden lg:flex absolute -right-4 top-1/2 -translate-y-1/2 z-20 w-8 h-8 rounded-full bg-blue-600 text-white shadow-lg items-center justify-center border-2 border-white font-extrabold">
            <span class="material-icons text-sm">east</span>
          </div>
        </a>
      <?php endif; ?>
      </div>

      <!-- ================= STEP 3: PESAN ANTREAN ================= -->
      <div id="step-card-3">
      <?php if ($step3Done && $hasActiveTicket): ?>
        <div class="bg-emerald-50 p-3.5 rounded-2xl border-2 border-emerald-500 shadow-sm flex flex-col items-center text-center space-y-2 relative pointer-events-none select-none">
          <div class="w-10 h-10 rounded-full bg-emerald-600 text-white font-black text-sm flex items-center justify-center shadow-md">
            <span class="material-icons text-base font-extrabold">check</span>
          </div>
          <div>
            <div class="text-xs font-extrabold text-emerald-950">3. Pesan Antrean</div>
            <div class="text-[10px] font-bold text-emerald-700 mt-0.5 truncate max-w-[120px]">
              No. #<?php echo htmlspecialchars($activeTicket['nomor'] ?? $activeTicket['kode_antrian'] ?? 'OK'); ?>
            </div>
          </div>
          <div class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-900 bg-emerald-200 px-2 py-0.5 rounded-full mt-1 border border-emerald-300">
            <span>✓ Dipesan</span>
          </div>
          <div class="hidden lg:flex absolute -right-4 top-1/2 -translate-y-1/2 z-20 w-8 h-8 rounded-full bg-emerald-600 text-white shadow-lg items-center justify-center border-2 border-white font-extrabold">
            <span class="material-icons text-sm">east</span>
          </div>
        </div>
      <?php elseif ($step3Done && $step5Done): ?>
        <!-- Completed Step 3 for Stage 5 View -->
        <div class="bg-emerald-50 p-3.5 rounded-2xl border-2 border-emerald-500 shadow-sm flex flex-col items-center text-center space-y-2 relative pointer-events-none select-none">
          <div class="w-10 h-10 rounded-full bg-emerald-600 text-white font-black text-sm flex items-center justify-center shadow-md">
            <span class="material-icons text-base font-extrabold">check</span>
          </div>
          <div>
            <div class="text-xs font-extrabold text-emerald-950">3. Pesan Antrean</div>
            <div class="text-[10px] font-bold text-emerald-700 mt-0.5 truncate max-w-[120px]">
              No. #<?php echo htmlspecialchars($completedTicket['nomor'] ?? $completedTicket['kode_antrian'] ?? 'OK'); ?>
            </div>
          </div>
          <div class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-900 bg-emerald-200 px-2 py-0.5 rounded-full mt-1 border border-emerald-300">
            <span>✓ Selesai</span>
          </div>
          <div class="hidden lg:flex absolute -right-4 top-1/2 -translate-y-1/2 z-20 w-8 h-8 rounded-full bg-emerald-600 text-white shadow-lg items-center justify-center border-2 border-white font-extrabold">
            <span class="material-icons text-sm">east</span>
          </div>
        </div>
      <?php elseif ($isUserLoggedIn): ?>
        <!-- Current Active Step (Pesan Sekarang) -->
        <a href="antrian.php" class="group bg-amber-100 p-3.5 rounded-2xl border-2 border-amber-500 shadow-xl transition-all duration-300 flex flex-col items-center text-center space-y-2 relative ring-4 ring-amber-400/40 hover:scale-105">
          <div class="w-10 h-10 rounded-full bg-amber-500 text-white font-black text-sm flex items-center justify-center shadow-md shadow-amber-500/40 group-hover:scale-110 transition animate-pulse">3</div>
          <div>
            <div class="text-xs font-black text-amber-950">3. Pesan Antrean</div>
            <div class="text-[10px] font-bold text-amber-800 mt-0.5">Langkah Saat Ini</div>
          </div>
          <div class="inline-flex items-center gap-1 text-[10px] font-extrabold text-white bg-amber-600 px-2.5 py-0.5 rounded-full mt-1 shadow">
            <span>Pesan Sekarang</span>
            <span class="material-icons text-xs">arrow_forward</span>
          </div>
          <div class="hidden lg:flex absolute -right-4 top-1/2 -translate-y-1/2 z-20 w-8 h-8 rounded-full bg-amber-500 text-white shadow-lg items-center justify-center border-2 border-white font-extrabold">
            <span class="material-icons text-sm">east</span>
          </div>
        </a>
      <?php else: ?>
        <div class="bg-slate-100 p-3.5 rounded-2xl border-2 border-slate-300 shadow-sm flex flex-col items-center text-center space-y-2 relative pointer-events-none select-none">
          <div class="w-10 h-10 rounded-full bg-slate-400 text-white font-bold text-sm flex items-center justify-center">
            <span class="material-icons text-base">lock</span>
          </div>
          <div>
            <div class="text-xs font-bold text-slate-700">3. Pesan Antrean</div>
            <div class="text-[10px] font-medium text-slate-500 mt-0.5">Login Dulu</div>
          </div>
          <div class="inline-flex items-center gap-1 text-[10px] font-bold text-slate-600 bg-slate-200 px-2 py-0.5 rounded-full mt-1 border border-slate-300">
            <span>🔒 Terkunci</span>
          </div>
          <div class="hidden lg:flex absolute -right-4 top-1/2 -translate-y-1/2 z-20 w-8 h-8 rounded-full bg-slate-400 text-white shadow items-center justify-center border-2 border-white font-bold">
            <span class="material-icons text-sm">east</span>
          </div>
        </div>
      <?php endif; ?>
      </div>

      <!-- ================= STEP 4: TIKET DIGITAL QR ================= -->
      <div id="step-card-4">
      <?php if ($step5Done): ?>
        <div class="bg-emerald-50 p-3.5 rounded-2xl border-2 border-emerald-500 shadow-sm flex flex-col items-center text-center space-y-2 relative pointer-events-none select-none">
          <div class="w-10 h-10 rounded-full bg-emerald-600 text-white font-black text-sm flex items-center justify-center shadow-md">
            <span class="material-icons text-base font-extrabold">check</span>
          </div>
          <div>
            <div class="text-xs font-extrabold text-emerald-950">4. Tiket Digital QR</div>
            <div class="text-[10px] font-bold text-emerald-700 mt-0.5">✓ QR Terverifikasi</div>
          </div>
          <div class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-900 bg-emerald-200 px-2 py-0.5 rounded-full mt-1 border border-emerald-300">
            <span>✓ Selesai</span>
          </div>
          <div class="hidden lg:flex absolute -right-4 top-1/2 -translate-y-1/2 z-20 w-8 h-8 rounded-full bg-emerald-600 text-white shadow-lg items-center justify-center border-2 border-white font-extrabold">
            <span class="material-icons text-sm">east</span>
          </div>
        </div>
      <?php elseif ($step4Done && $hasActiveTicket): ?>
        <a href="bukutamu.php" class="group bg-emerald-100 p-3.5 rounded-2xl border-2 border-emerald-500 shadow-xl transition-all duration-300 flex flex-col items-center text-center space-y-2 relative ring-4 ring-emerald-400/40 hover:scale-105">
          <div class="w-10 h-10 rounded-full bg-emerald-600 text-white font-black text-sm flex items-center justify-center shadow-md shadow-emerald-500/40 group-hover:scale-110 transition animate-pulse">4</div>
          <div>
            <div class="text-xs font-black text-emerald-950">4. Tiket Digital QR</div>
            <div class="text-[10px] font-bold text-emerald-800 mt-0.5">Resi & QR Siap</div>
          </div>
          <div class="inline-flex items-center gap-1 text-[10px] font-extrabold text-white bg-emerald-600 px-2.5 py-0.5 rounded-full mt-1 shadow">
            <span>Lihat Tiket QR</span>
            <span class="material-icons text-xs">qr_code_2</span>
          </div>
          <div class="hidden lg:flex absolute -right-4 top-1/2 -translate-y-1/2 z-20 w-8 h-8 rounded-full bg-emerald-600 text-white shadow-lg items-center justify-center border-2 border-white font-extrabold">
            <span class="material-icons text-sm">east</span>
          </div>
        </a>
      <?php else: ?>
        <div class="bg-slate-100 p-3.5 rounded-2xl border-2 border-slate-300 shadow-sm flex flex-col items-center text-center space-y-2 relative pointer-events-none select-none">
          <div class="w-10 h-10 rounded-full bg-slate-400 text-white font-bold text-sm flex items-center justify-center">
            <span class="material-icons text-base">lock</span>
          </div>
          <div>
            <div class="text-xs font-bold text-slate-700">4. Tiket Digital QR</div>
            <div class="text-[10px] font-medium text-slate-500 mt-0.5">Belum Ada Tiket</div>
          </div>
          <div class="inline-flex items-center gap-1 text-[10px] font-bold text-slate-600 bg-slate-200 px-2 py-0.5 rounded-full mt-1 border border-slate-300">
            <span>🔒 Terkunci</span>
          </div>
          <div class="hidden lg:flex absolute -right-4 top-1/2 -translate-y-1/2 z-20 w-8 h-8 rounded-full bg-slate-400 text-white shadow items-center justify-center border-2 border-white font-bold">
            <span class="material-icons text-sm">east</span>
          </div>
        </div>
      <?php endif; ?>
      </div>

      <!-- ================= STEP 5: DATANG KE LOKET / ULASAN SKM ================= -->
      <div id="step-card-5">
      <?php if ($step5Done): ?>
        <button type="button" onclick="openSKMModalFromStepper(<?php echo (int)($latestTicket['id'] ?? 0); ?>, '<?php echo htmlspecialchars($latestTicket['pendapat'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($latestTicket['catatan'] ?? '', ENT_QUOTES); ?>')" class="group bg-amber-100 hover:bg-amber-200 p-3.5 rounded-2xl border-2 border-amber-500 shadow-xl transition-all duration-300 flex flex-col items-center text-center space-y-2 relative ring-4 ring-amber-400/40 hover:scale-105 cursor-pointer w-full">
          <div class="w-10 h-10 rounded-full bg-amber-500 text-white font-black text-sm flex items-center justify-center shadow-md shadow-amber-500/40 group-hover:scale-110 transition animate-pulse">
            <span class="material-icons text-lg font-bold">star</span>
          </div>
          <div>
            <div class="text-xs font-black text-amber-950">5. Ulasan SKM</div>
            <div class="text-[10px] font-bold text-amber-800 mt-0.5">
              <?php echo !empty($latestTicket['pendapat']) ? '✓ ' . htmlspecialchars($latestTicket['pendapat']) : 'Kunjungan Selesai'; ?>
            </div>
          </div>
          <div class="inline-flex items-center gap-1 text-[10px] font-extrabold text-slate-900 bg-amber-400 px-2.5 py-0.5 rounded-full mt-1 shadow">
            <span><?php echo !empty($latestTicket['pendapat']) ? 'Edit Ulasan SKM' : '⭐ Beri Ulasan SKM'; ?></span>
          </div>
        </button>
      <?php elseif ($hasActiveTicket): ?>
        <button type="button" data-bs-toggle="modal" data-bs-target="#modalPetunjukLoket" class="group bg-white hover:bg-purple-50 p-3.5 rounded-2xl border-2 border-purple-500 shadow-md hover:shadow-lg transition-all duration-300 flex flex-col items-center text-center space-y-2 relative cursor-pointer w-full">
          <div class="w-10 h-10 rounded-full bg-purple-600 text-white font-black text-sm flex items-center justify-center shadow-md shadow-purple-500/30 group-hover:scale-110 transition">5</div>
          <div>
            <div class="text-xs font-extrabold text-slate-900 group-hover:text-purple-700">5. Datang Ke Loket</div>
            <div class="text-[10px] font-semibold text-purple-700 mt-0.5">Scan QR di PST</div>
          </div>
          <div class="inline-flex items-center gap-1 text-[10px] font-bold text-purple-800 bg-purple-100 px-2 py-0.5 rounded-full mt-1 border border-purple-200">
            <span>Petunjuk Loket</span>
            <span class="material-icons text-xs">info</span>
          </div>
        </button>
      <?php else: ?>
        <div class="bg-slate-100 p-3.5 rounded-2xl border-2 border-slate-300 shadow-sm flex flex-col items-center text-center space-y-2 relative pointer-events-none select-none">
          <div class="w-10 h-10 rounded-full bg-slate-400 text-white font-bold text-sm flex items-center justify-center">
            <span class="material-icons text-base">lock</span>
          </div>
          <div>
            <div class="text-xs font-bold text-slate-700">5. Datang Ke Loket</div>
            <div class="text-[10px] font-medium text-slate-500 mt-0.5">Belum Antre</div>
          </div>
          <div class="inline-flex items-center gap-1 text-[10px] font-bold text-slate-600 bg-slate-200 px-2 py-0.5 rounded-full mt-1 border border-slate-300">
            <span>🔒 Terkunci</span>
          </div>
        </div>
      <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<!-- ================= MODAL PETUNJUK DATANG KE LOKET PST ================= -->
<div class="modal fade" id="modalPetunjukLoket" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-3xl border-0 shadow-2xl overflow-hidden">
      <div class="modal-header bg-gradient-to-r from-purple-800 via-purple-900 to-indigo-900 text-white p-5">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-white/10 backdrop-blur-md flex items-center justify-center border border-white/20">
            <span class="material-icons text-purple-300 text-2xl">location_on</span>
          </div>
          <div>
            <h5 class="modal-title text-base font-extrabold brand-font">Petunjuk Datang Ke Loket PST</h5>
            <p class="text-xs text-purple-200">BPS Kota Tegal - Pelayanan Statistik Terpadu</p>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body p-6 space-y-5 text-slate-800">
        <?php if (!empty($latestTicket)): ?>
          <div class="p-4 bg-purple-50 rounded-2xl border border-purple-200 space-y-2">
            <div class="flex items-center justify-between text-xs font-bold text-purple-950">
              <span class="flex items-center gap-1"><span class="material-icons text-purple-600 text-sm">confirmation_number</span> Tiket Aktif Anda:</span>
              <span class="px-2.5 py-0.5 bg-purple-600 text-white rounded-full font-mono text-[11px]">#<?php echo htmlspecialchars($latestTicket['nomor'] ?? $latestTicket['kode_antrian']); ?></span>
            </div>
            <div class="text-xs text-purple-900 space-y-1">
              <div><b>Jadwal Kunjungan:</b> <?php echo date('d-m-Y', strtotime($latestTicket['tanggal'])); ?> pukul <?php echo htmlspecialchars(substr($latestTicket['waktu'], 0, 5)); ?> WIB</div>
              <div><b>Keperluan Layanan:</b> <?php echo htmlspecialchars($latestTicket['layanan']); ?></div>
            </div>
            <?php if (in_array($latestTicket['status'] ?? '', ['Menunggu', 'Dipanggil'])): ?>
              <div class="pt-2 border-t border-purple-200/80 flex justify-end">
                <button type="button" onclick="cancelVisitorQueue(<?php echo (int)($latestTicket['id'] ?? 0); ?>)" class="btn btn-outline-danger btn-sm text-[11px] font-bold rounded-xl px-3 py-1 flex items-center gap-1">
                  <span class="material-icons text-xs">cancel</span> Batalkan Tiket Aktif Ini
                </button>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 space-y-1">
            <div class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
              <span class="material-icons text-sky-600 text-base">place</span>
              <span>Alamat Kantor</span>
            </div>
            <p class="text-xs text-slate-600 leading-relaxed">Jl. Nakula No. 36A, Kota Tegal, Jawa Tengah 52124</p>
          </div>
          <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 space-y-1">
            <div class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
              <span class="material-icons text-amber-600 text-base">schedule</span>
              <span>Jam Layanan</span>
            </div>
            <p class="text-xs text-slate-600 leading-relaxed">Senin - Jumat: 08.00 - 15.30 WIB</p>
          </div>
        </div>

        <div class="space-y-2">
          <div class="text-xs font-bold text-slate-900 uppercase tracking-wider">Langkah-Langkah Saat Tiba Di Loket:</div>
          <div class="space-y-2 text-xs text-slate-700">
            <div class="flex items-start gap-3 p-3 bg-white rounded-xl border border-slate-200 shadow-sm">
              <span class="w-6 h-6 rounded-full bg-purple-600 text-white font-extrabold flex items-center justify-center shrink-0 text-xs">1</span>
              <div>Datang 10 menit sebelum waktu reservasi dan temui Petugas Meja PST BPS Kota Tegal.</div>
            </div>
            <div class="flex items-start gap-3 p-3 bg-white rounded-xl border border-slate-200 shadow-sm">
              <span class="w-6 h-6 rounded-full bg-purple-600 text-white font-extrabold flex items-center justify-center shrink-0 text-xs">2</span>
              <div>Tunjukkan <b>QR Code Tiket Antrean</b> dari HP Anda (atau cetakan kertas 2 halaman).</div>
            </div>
            <div class="flex items-start gap-3 p-3 bg-white rounded-xl border border-slate-200 shadow-sm">
              <span class="w-6 h-6 rounded-full bg-purple-600 text-white font-extrabold flex items-center justify-center shrink-0 text-xs">3</span>
              <div>Petugas memindai QR Code dan melayani kebutuhan data / konsultasi statistik Anda hingga selesai.</div>
            </div>
          </div>
        </div>

      </div>

      <div class="modal-footer bg-slate-50 p-4 flex flex-wrap items-center justify-between gap-2">
        <a href="https://maps.google.com/?q=BPS+Kota+Tegal+Jl+Nakula+No+36A+Tegal" target="_blank" class="btn btn-outline-secondary btn-sm text-xs font-bold flex items-center gap-1 rounded-xl">
          <span class="material-icons text-sm">map</span> Google Maps
        </a>
        
        <div class="flex items-center gap-2">
          <?php if ($step5Done): ?>
            <button type="button" onclick="openSKMModalFromStepper(<?php echo (int)($latestTicket['id'] ?? 0); ?>, '<?php echo htmlspecialchars($latestTicket['pendapat'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($latestTicket['catatan'] ?? '', ENT_QUOTES); ?>')" class="btn btn-warning bg-amber-500 hover:bg-amber-600 text-slate-900 btn-sm text-xs font-black flex items-center gap-1 px-4 py-2 rounded-xl border-none shadow">
              <span class="material-icons text-sm">star</span> ⭐ Beri Ulasan SKM
            </button>
          <?php else: ?>
            <a href="bukutamu.php" class="btn btn-purple bg-purple-600 hover:bg-purple-700 text-white btn-sm text-xs font-bold flex items-center gap-1 px-4 py-2 rounded-xl border-none shadow">
              <span class="material-icons text-sm">qr_code_2</span> Buka Tiket QR Saya
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ================= MODAL SKM / ULASAN KEPUASAN LAYANAN ================= -->
<div class="modal fade" id="modalSKMStepper" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-3xl border-0 shadow-2xl overflow-hidden">
      <div class="modal-header bg-gradient-to-r from-amber-600 via-amber-700 to-amber-800 text-white p-5">
        <h5 class="modal-title text-base font-extrabold flex items-center gap-2 brand-font">
          <span class="material-icons text-amber-300">star</span>
          <span>Penilaian Kepuasan Layanan (SKM)</span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form id="formSKMStepper" class="modal-body p-6 space-y-5 text-slate-800">
        <input type="hidden" id="stepper_skm_antrian_id" value="<?php echo (int)($latestTicket['id'] ?? 0); ?>">
        
        <div class="space-y-3 text-center">
          <label class="form-label text-xs font-extrabold text-slate-700 uppercase tracking-wider">Seberapa Puas Anda Dengan Layanan Kami? <span class="text-red-500">*</span></label>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-3" id="stepper-skm-options">
            
            <label class="stepper-skm-card cursor-pointer border-2 border-slate-200 rounded-2xl p-3.5 text-center transition-all duration-200 relative overflow-hidden select-none hover:border-amber-400" onclick="selectStepperSKMCard(this)">
              <input type="radio" name="stepper_skm_pendapat" value="Sangat Puas" class="hidden">
              <div class="stepper-skm-badge absolute top-1.5 right-1.5 hidden bg-amber-600 text-white rounded-full w-5 h-5 flex items-center justify-center text-[10px] font-bold shadow-sm">✓</div>
              <div class="stepper-skm-emoji text-3xl mb-1 transition-all duration-200 inline-block">😍</div>
              <div class="text-xs font-bold text-slate-700 stepper-skm-label">Sangat Puas</div>
            </label>

            <label class="stepper-skm-card cursor-pointer border-2 border-slate-200 rounded-2xl p-3.5 text-center transition-all duration-200 relative overflow-hidden select-none hover:border-amber-400" onclick="selectStepperSKMCard(this)">
              <input type="radio" name="stepper_skm_pendapat" value="Puas" class="hidden">
              <div class="stepper-skm-badge absolute top-1.5 right-1.5 hidden bg-amber-600 text-white rounded-full w-5 h-5 flex items-center justify-center text-[10px] font-bold shadow-sm">✓</div>
              <div class="stepper-skm-emoji text-3xl mb-1 transition-all duration-200 inline-block">😊</div>
              <div class="text-xs font-bold text-slate-700 stepper-skm-label">Puas</div>
            </label>

            <label class="stepper-skm-card cursor-pointer border-2 border-slate-200 rounded-2xl p-3.5 text-center transition-all duration-200 relative overflow-hidden select-none hover:border-amber-400" onclick="selectStepperSKMCard(this)">
              <input type="radio" name="stepper_skm_pendapat" value="Cukup Puas" class="hidden">
              <div class="stepper-skm-badge absolute top-1.5 right-1.5 hidden bg-amber-600 text-white rounded-full w-5 h-5 flex items-center justify-center text-[10px] font-bold shadow-sm">✓</div>
              <div class="stepper-skm-emoji text-3xl mb-1 transition-all duration-200 inline-block">😐</div>
              <div class="text-xs font-bold text-slate-700 stepper-skm-label">Cukup Puas</div>
            </label>

            <label class="stepper-skm-card cursor-pointer border-2 border-slate-200 rounded-2xl p-3.5 text-center transition-all duration-200 relative overflow-hidden select-none hover:border-amber-400" onclick="selectStepperSKMCard(this)">
              <input type="radio" name="stepper_skm_pendapat" value="Tidak Puas" class="hidden">
              <div class="stepper-skm-badge absolute top-1.5 right-1.5 hidden bg-amber-600 text-white rounded-full w-5 h-5 flex items-center justify-center text-[10px] font-bold shadow-sm">✓</div>
              <div class="stepper-skm-emoji text-3xl mb-1 transition-all duration-200 inline-block">🙁</div>
              <div class="text-xs font-bold text-slate-700 stepper-skm-label">Tidak Puas</div>
            </label>

          </div>
        </div>

        <div class="space-y-1.5">
          <label for="stepper_skm_catatan" class="form-label text-xs font-bold text-slate-700 uppercase">Saran & Masukan (Opsional)</label>
          <textarea id="stepper_skm_catatan" class="form-control text-xs rounded-xl border-slate-300" rows="3" placeholder="Tuliskan masukan atau kritik membangun untuk pelayanan PST BPS Kota Tegal..."></textarea>
        </div>

        <div class="pt-2 flex justify-end gap-2 border-t border-slate-100">
          <button type="button" class="btn btn-secondary text-xs font-bold rounded-xl" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-warning bg-amber-500 hover:bg-amber-600 text-slate-900 font-extrabold text-xs rounded-xl px-5 border-none shadow">Kirim Penilaian SKM</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
window.stepperConfig = {
  lastTicketStatus: '<?php echo htmlspecialchars($latestTicket['status'] ?? ''); ?>',
  lastTicketId: <?php echo (int)($latestTicket['id'] ?? 0); ?>,
  hasActiveTicket: <?php echo $hasActiveTicket ? 'true' : 'false'; ?>
};
</script>
<script src="js/stepper.js?v=<?php echo time(); ?>"></script>
