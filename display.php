<?php
// SPST BPS Kota Tegal - Layar TV Display Antrean 4 Loket PST (Tema Terang / Light Theme)
require_once __DIR__ . '/koneksi.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Display Antrean Pelayanan 4 Loket - SPST BPS Kota Tegal</title>

  <!-- Tailwind CSS Play CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Google Fonts: Inter & Outfit -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">
  <!-- Material Icons -->
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

  <style>
    body {
      font-family: 'Inter', sans-serif;
      overflow-x: hidden;
      user-select: none;
      background: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 50%, #dbeafe 100%);
    }
    .brand-font {
      font-family: 'Outfit', sans-serif;
    }
    /* Calling Glow Animation */
    .card-calling-active {
      animation: callPulseLight 1.6s infinite ease-in-out;
      transform: scale(1.02);
      z-index: 20;
    }
    @keyframes callPulseLight {
      0% {
        box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.8), 0 20px 35px -5px rgba(245, 158, 11, 0.4);
      }
      70% {
        box-shadow: 0 0 0 18px rgba(245, 158, 11, 0), 0 25px 50px -10px rgba(245, 158, 11, 0.5);
      }
      100% {
        box-shadow: 0 0 0 0 rgba(245, 158, 11, 0), 0 20px 35px -5px rgba(245, 158, 11, 0.4);
      }
    }
    /* Running Text Marquee Animation */
    .marquee-content {
      display: inline-block;
      white-space: nowrap;
      animation: marquee 35s linear infinite;
    }
    @keyframes marquee {
      0% { transform: translateX(100%); }
      100% { transform: translateX(-100%); }
    }
  </style>
</head>
<body class="min-h-screen text-slate-800 flex flex-col justify-between p-3 md:p-5 lg:p-6">

  <!-- ============================================================ -->
  <!-- 1. HEADER SECTION (Tema Terang Resmi BPS Kota Tegal)         -->
  <!-- ============================================================ -->
  <header class="bg-white/95 backdrop-blur-md rounded-2xl border border-slate-200/80 p-3.5 md:p-4 shadow-md flex flex-wrap items-center justify-between gap-4">
    <!-- Brand Logo & Agency Title -->
    <div class="flex items-center gap-3.5">
      <img src="img/Logo_BPS.png" alt="Logo BPS" class="w-12 h-12 md:w-14 md:h-14 object-contain filter drop-shadow">
      <div>
        <div class="flex items-center gap-2">
          <span class="text-[11px] font-black tracking-widest uppercase text-[#003366] bg-sky-100 px-2 py-0.5 rounded-md border border-sky-200">
            BPS KOTA TEGAL
          </span>
          <span class="inline-flex items-center gap-1.5 text-[10px] font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-md border border-emerald-300">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span> Real-Time Live
          </span>
        </div>
        <h1 class="text-lg md:text-xl lg:text-2xl font-black text-[#003366] brand-font tracking-tight mt-0.5">
          PELAYANAN STATISTIK TERPADU (PST)
        </h1>
        <p class="text-xs text-slate-500 font-medium hidden sm:block">
          Papan Informasi Antrean 4 Loket Pelayanan Resmi Badan Pusat Statistik Kota Tegal
        </p>
      </div>
    </div>

    <!-- Live Digital Clock & Control Buttons -->
    <div class="flex items-center gap-3">
      <!-- Digital Clock Card -->
      <div class="text-right bg-slate-50 border border-slate-200 px-3.5 py-1.5 rounded-xl shadow-sm">
        <div id="display_clock_time" class="text-xl md:text-2xl font-black text-[#003366] font-mono tracking-wider">--:--:--</div>
        <div id="display_clock_date" class="text-[11px] md:text-xs font-bold text-slate-600">Memuat Tanggal...</div>
      </div>

      <!-- Controls: Audio & Fullscreen Toggle -->
      <div class="flex items-center gap-2">
        <button id="btn_toggle_audio" onclick="toggleAudio()" class="p-2.5 rounded-xl bg-sky-50 hover:bg-sky-100 border border-sky-300 text-sky-800 transition shadow-sm flex items-center gap-1.5 text-xs font-bold" title="Status Audio Suara Panggilan">
          <span id="icon_audio_status" class="material-icons text-lg">volume_up</span>
          <span id="text_audio_status" class="hidden md:inline">Audio Aktif</span>
        </button>

        <button onclick="toggleFullscreen()" class="p-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 border border-slate-300 text-slate-700 transition shadow-sm" title="Layar Penuh (Fullscreen / F11)">
          <span class="material-icons text-lg">fullscreen</span>
        </button>
      </div>
    </div>
  </header>


  <!-- ============================================================ -->
  <!-- 2. MAIN SECTION: 4 LOKET PELAYANAN PST (EQUAL 4-GRID LAYOUT) -->
  <!-- ============================================================ -->
  <main class="my-4 md:my-5 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 md:gap-5 items-stretch flex-1">

    <!-- ============================================================ -->
    <!-- LOKET 1: Konsultasi Statistik (KS)                           -->
    <!-- ============================================================ -->
    <div id="card_loket_1" class="bg-white rounded-2xl border-2 border-sky-200 shadow-lg p-4 md:p-5 flex flex-col justify-between space-y-3 transition-all duration-300 relative overflow-hidden h-full">
      <!-- Top Loket Bar -->
      <div class="flex items-center justify-between pb-1.5">
        <div class="flex items-center gap-2">
          <span class="w-7 h-7 rounded-lg bg-sky-600 text-white font-black text-xs flex items-center justify-center shadow-sm">1</span>
          <span class="text-xs font-extrabold text-sky-800 uppercase tracking-widest">LOKET 1</span>
        </div>
        <span id="badge_loket_1" class="px-2.5 py-0.5 bg-slate-100 text-slate-600 text-[11px] font-bold rounded-full border border-slate-200">
          Siap
        </span>
      </div>

      <!-- Prominent Service Title Banner (Full Width) -->
      <div class="pb-2 border-b border-slate-100 min-h-[2.5rem] flex items-center justify-center text-center px-1">
        <h2 class="text-base md:text-lg font-black text-slate-900 brand-font tracking-tight leading-snug">
          Konsultasi Statistik
        </h2>
      </div>

      <!-- Calling Banner Indicator (Hidden by default, shown on calling) -->
      <div id="calling_banner_1" class="hidden py-1 px-2.5 bg-amber-400 text-slate-950 font-black text-[11px] uppercase tracking-wider rounded-lg text-center shadow-md animate-pulse">
        🔔 SEDANG MEMANGGIL
      </div>

      <!-- Nomor Antrean & Pengunjung Box (Tiket Aktif) -->
      <div class="bg-gradient-to-b from-sky-50/80 to-slate-50 p-3.5 rounded-xl border border-sky-100 text-center space-y-1 my-auto">
        <div class="text-[10px] font-bold uppercase tracking-widest text-sky-800">Sedang Dilayani / Dipanggil</div>
        <div id="number_loket_1" class="text-4xl md:text-5xl font-black text-sky-900 brand-font tracking-wider py-0.5">
          ---
        </div>
        <div class="pt-1.5 border-t border-sky-200/60">
          <div id="name_loket_1" class="text-sm font-extrabold text-slate-800 truncate">
            Siap Melayani
          </div>
          <div id="instansi_loket_1" class="text-[11px] text-slate-500 truncate">
            Pelayanan Statistik Terpadu
          </div>
        </div>
      </div>

      <!-- List Antrean yang Akan Dipanggil Berikutnya -->
      <div class="space-y-1.5 pt-2 border-t border-slate-100">
        <div class="flex items-center justify-between text-[11px] font-bold text-slate-600 px-0.5">
          <span class="flex items-center gap-1">
            <span class="material-icons text-xs text-sky-600">format_list_numbered</span>
            <span class="uppercase tracking-wider">Antrean Berikutnya</span>
          </span>
          <span id="wait_loket_1" class="text-[10px] px-2 py-0.5 bg-sky-50 text-sky-800 font-extrabold rounded-md border border-sky-200">
            0 menunggu
          </span>
        </div>
        <div id="list_next_loket_1" class="space-y-1 min-h-[4rem]">
          <div class="text-center py-2 text-[11px] text-slate-400 font-medium italic bg-slate-50 rounded-lg border border-dashed border-slate-200">
            Tidak ada antrean menunggu
          </div>
        </div>
      </div>

      <!-- Loket Footer: Jam -->
      <div class="pt-1.5 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-400 font-semibold">
        <span>Waktu Panggilan</span>
        <span id="time_loket_1">-</span>
      </div>
    </div>


    <!-- ============================================================ -->
    <!-- LOKET 2: Perpustakaan Digital & Buku (PD)                    -->
    <!-- ============================================================ -->
    <div id="card_loket_2" class="bg-white rounded-2xl border-2 border-emerald-200 shadow-lg p-4 md:p-5 flex flex-col justify-between space-y-3 transition-all duration-300 relative overflow-hidden h-full">
      <!-- Top Loket Bar -->
      <div class="flex items-center justify-between pb-1.5">
        <div class="flex items-center gap-2">
          <span class="w-7 h-7 rounded-lg bg-emerald-600 text-white font-black text-xs flex items-center justify-center shadow-sm">2</span>
          <span class="text-xs font-extrabold text-emerald-800 uppercase tracking-widest">LOKET 2</span>
        </div>
        <span id="badge_loket_2" class="px-2.5 py-0.5 bg-slate-100 text-slate-600 text-[11px] font-bold rounded-full border border-slate-200">
          Siap
        </span>
      </div>

      <!-- Prominent Service Title Banner (Full Width) -->
      <div class="pb-2 border-b border-slate-100 min-h-[2.5rem] flex items-center justify-center text-center px-1">
        <h2 class="text-base md:text-lg font-black text-slate-900 brand-font tracking-tight leading-snug">
          Pelayanan Perpustakaan
        </h2>
      </div>

      <!-- Calling Banner Indicator -->
      <div id="calling_banner_2" class="hidden py-1 px-2.5 bg-amber-400 text-slate-950 font-black text-[11px] uppercase tracking-wider rounded-lg text-center shadow-md animate-pulse">
        🔔 SEDANG MEMANGGIL
      </div>

      <!-- Nomor Antrean & Pengunjung Box (Tiket Aktif) -->
      <div class="bg-gradient-to-b from-emerald-50/80 to-slate-50 p-3.5 rounded-xl border border-emerald-100 text-center space-y-1 my-auto">
        <div class="text-[10px] font-bold uppercase tracking-widest text-emerald-800">Sedang Dilayani / Dipanggil</div>
        <div id="number_loket_2" class="text-4xl md:text-5xl font-black text-emerald-900 brand-font tracking-wider py-0.5">
          ---
        </div>
        <div class="pt-1.5 border-t border-emerald-200/60">
          <div id="name_loket_2" class="text-sm font-extrabold text-slate-800 truncate">
            Siap Melayani
          </div>
          <div id="instansi_loket_2" class="text-[11px] text-slate-500 truncate">
            Pelayanan Perpustakaan PST
          </div>
        </div>
      </div>

      <!-- List Antrean yang Akan Dipanggil Berikutnya -->
      <div class="space-y-1.5 pt-2 border-t border-slate-100">
        <div class="flex items-center justify-between text-[11px] font-bold text-slate-600 px-0.5">
          <span class="flex items-center gap-1">
            <span class="material-icons text-xs text-emerald-600">format_list_numbered</span>
            <span class="uppercase tracking-wider">Antrean Berikutnya</span>
          </span>
          <span id="wait_loket_2" class="text-[10px] px-2 py-0.5 bg-emerald-50 text-emerald-800 font-extrabold rounded-md border border-emerald-200">
            0 menunggu
          </span>
        </div>
        <div id="list_next_loket_2" class="space-y-1 min-h-[4rem]">
          <div class="text-center py-2 text-[11px] text-slate-400 font-medium italic bg-slate-50 rounded-lg border border-dashed border-slate-200">
            Tidak ada antrean menunggu
          </div>
        </div>
      </div>

      <!-- Loket Footer: Jam -->
      <div class="pt-1.5 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-400 font-semibold">
        <span>Waktu Panggilan</span>
        <span id="time_loket_2">-</span>
      </div>
    </div>


    <!-- ============================================================ -->
    <!-- LOKET 3: Rekomendasi Kegiatan Statistik (RS)                 -->
    <!-- ============================================================ -->
    <div id="card_loket_3" class="bg-white rounded-2xl border-2 border-amber-200 shadow-lg p-4 md:p-5 flex flex-col justify-between space-y-3 transition-all duration-300 relative overflow-hidden h-full">
      <!-- Top Loket Bar -->
      <div class="flex items-center justify-between pb-1.5">
        <div class="flex items-center gap-2">
          <span class="w-7 h-7 rounded-lg bg-amber-500 text-white font-black text-xs flex items-center justify-center shadow-sm">3</span>
          <span class="text-xs font-extrabold text-amber-800 uppercase tracking-widest">LOKET 3</span>
        </div>
        <span id="badge_loket_3" class="px-2.5 py-0.5 bg-slate-100 text-slate-600 text-[11px] font-bold rounded-full border border-slate-200">
          Siap
        </span>
      </div>

      <!-- Prominent Service Title Banner (Full Width) -->
      <div class="pb-2 border-b border-slate-100 min-h-[2.5rem] flex items-center justify-center text-center px-1">
        <h2 class="text-base md:text-lg font-black text-slate-900 brand-font tracking-tight leading-snug">
          Rekomendasi Statistik
        </h2>
      </div>

      <!-- Calling Banner Indicator -->
      <div id="calling_banner_3" class="hidden py-1 px-2.5 bg-amber-400 text-slate-950 font-black text-[11px] uppercase tracking-wider rounded-lg text-center shadow-md animate-pulse">
        🔔 SEDANG MEMANGGIL
      </div>

      <!-- Nomor Antrean & Pengunjung Box (Tiket Aktif) -->
      <div class="bg-gradient-to-b from-amber-50/80 to-slate-50 p-3.5 rounded-xl border border-amber-100 text-center space-y-1 my-auto">
        <div class="text-[10px] font-bold uppercase tracking-widest text-amber-800">Sedang Dilayani / Dipanggil</div>
        <div id="number_loket_3" class="text-4xl md:text-5xl font-black text-amber-900 brand-font tracking-wider py-0.5">
          ---
        </div>
        <div class="pt-1.5 border-t border-amber-200/60">
          <div id="name_loket_3" class="text-sm font-extrabold text-slate-800 truncate">
            Siap Melayani
          </div>
          <div id="instansi_loket_3" class="text-[11px] text-slate-500 truncate">
            Rekomendasi Kegiatan Statistik
          </div>
        </div>
      </div>

      <!-- List Antrean yang Akan Dipanggil Berikutnya -->
      <div class="space-y-1.5 pt-2 border-t border-slate-100">
        <div class="flex items-center justify-between text-[11px] font-bold text-slate-600 px-0.5">
          <span class="flex items-center gap-1">
            <span class="material-icons text-xs text-amber-600">format_list_numbered</span>
            <span class="uppercase tracking-wider">Antrean Berikutnya</span>
          </span>
          <span id="wait_loket_3" class="text-[10px] px-2 py-0.5 bg-amber-50 text-amber-800 font-extrabold rounded-md border border-amber-200">
            0 menunggu
          </span>
        </div>
        <div id="list_next_loket_3" class="space-y-1 min-h-[4rem]">
          <div class="text-center py-2 text-[11px] text-slate-400 font-medium italic bg-slate-50 rounded-lg border border-dashed border-slate-200">
            Tidak ada antrean menunggu
          </div>
        </div>
      </div>

      <!-- Loket Footer: Jam -->
      <div class="pt-1.5 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-400 font-semibold">
        <span>Waktu Panggilan</span>
        <span id="time_loket_3">-</span>
      </div>
    </div>


    <!-- ============================================================ -->
    <!-- LOKET 4: Layanan Pengaduan & Informasi (PG)                  -->
    <!-- ============================================================ -->
    <div id="card_loket_4" class="bg-white rounded-2xl border-2 border-rose-200 shadow-lg p-4 md:p-5 flex flex-col justify-between space-y-3 transition-all duration-300 relative overflow-hidden h-full">
      <!-- Top Loket Bar -->
      <div class="flex items-center justify-between pb-1.5">
        <div class="flex items-center gap-2">
          <span class="w-7 h-7 rounded-lg bg-rose-600 text-white font-black text-xs flex items-center justify-center shadow-sm">4</span>
          <span class="text-xs font-extrabold text-rose-700 uppercase tracking-widest">LOKET 4</span>
        </div>
        <span id="badge_loket_4" class="px-2.5 py-0.5 bg-slate-100 text-slate-600 text-[11px] font-bold rounded-full border border-slate-200">
          Siap
        </span>
      </div>

      <!-- Prominent Service Title Banner (Full Width) -->
      <div class="pb-2 border-b border-slate-100 min-h-[2.5rem] flex items-center justify-center text-center px-1">
        <h2 class="text-base md:text-lg font-black text-slate-900 brand-font tracking-tight leading-snug">
          Layanan Pengaduan & Info
        </h2>
      </div>

      <!-- Calling Banner Indicator -->
      <div id="calling_banner_4" class="hidden py-1 px-2.5 bg-amber-400 text-slate-950 font-black text-[11px] uppercase tracking-wider rounded-lg text-center shadow-md animate-pulse">
        🔔 SEDANG MEMANGGIL
      </div>

      <!-- Nomor Antrean & Pengunjung Box (Tiket Aktif) -->
      <div class="bg-gradient-to-b from-rose-50/80 to-slate-50 p-3.5 rounded-xl border border-rose-100 text-center space-y-1 my-auto">
        <div class="text-[10px] font-bold uppercase tracking-widest text-rose-800">Sedang Dilayani / Dipanggil</div>
        <div id="number_loket_4" class="text-4xl md:text-5xl font-black text-rose-900 brand-font tracking-wider py-0.5">
          ---
        </div>
        <div class="pt-1.5 border-t border-rose-200/60">
          <div id="name_loket_4" class="text-sm font-extrabold text-slate-800 truncate">
            Siap Melayani
          </div>
          <div id="instansi_loket_4" class="text-[11px] text-slate-500 truncate">
            Layanan Pengaduan & Informasi
          </div>
        </div>
      </div>

      <!-- List Antrean yang Akan Dipanggil Berikutnya -->
      <div class="space-y-1.5 pt-2 border-t border-slate-100">
        <div class="flex items-center justify-between text-[11px] font-bold text-slate-600 px-0.5">
          <span class="flex items-center gap-1">
            <span class="material-icons text-xs text-rose-600">format_list_numbered</span>
            <span class="uppercase tracking-wider">Antrean Berikutnya</span>
          </span>
          <span id="wait_loket_4" class="text-[10px] px-2 py-0.5 bg-rose-50 text-rose-800 font-extrabold rounded-md border border-rose-200">
            0 menunggu
          </span>
        </div>
        <div id="list_next_loket_4" class="space-y-1 min-h-[4rem]">
          <div class="text-center py-2 text-[11px] text-slate-400 font-medium italic bg-slate-50 rounded-lg border border-dashed border-slate-200">
            Tidak ada antrean menunggu
          </div>
        </div>
      </div>

      <!-- Loket Footer: Jam -->
      <div class="pt-1.5 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-400 font-semibold">
        <span>Waktu Panggilan</span>
        <span id="time_loket_4">-</span>
      </div>
    </div>

  </main>

  <!-- ============================================================ -->
  <!-- 3. FOOTER: Running Text / Marquee Information Ticker         -->
  <!-- ============================================================ -->
  <footer class="bg-[#003366] text-white rounded-xl p-2.5 overflow-hidden shadow-md flex items-center gap-3">
    <div class="bg-amber-400 text-slate-950 text-[11px] font-black uppercase px-3 py-1 rounded-xl shrink-0 flex items-center gap-1 shadow">
      <span class="material-icons text-sm">campaign</span>
      <span>INFORMASI PST</span>
    </div>

    <div class="overflow-hidden whitespace-nowrap w-full">
      <div class="marquee-content text-xs md:text-sm font-semibold text-sky-100">
        📢 Selamat Datang di Pelayanan Statistik Terpadu (PST) BPS Kota Tegal • ⏰ Jam Pelayanan: Senin s.d. Jumat (08:00 - 15:30 WIB) • ⭐ Seluruh Pelayanan PST Bebas Biaya / Gratis • 🛡️ Melayani dengan SEPAT (Santun, Empati, Profesional, Akuntabel, Transparan) • 📱 Pengunjung dapat memantau estimasi antrean langsung melalui smartphone • 💬 Silakan hubungi petugas loket kami jika memerlukan bantuan.
      </div>
    </div>
  </footer>


  <!-- ============================================================ -->
  <!-- 5. AUDIO ACTIVATION OVERLAY (User Gesture Unlock)           -->
  <!-- ============================================================ -->
  <div id="audio_unlock_overlay" onclick="unlockAudio()" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 cursor-pointer transition-opacity duration-300">
    <div class="bg-white p-8 rounded-3xl border-2 border-amber-400 max-w-lg w-full text-center space-y-4 shadow-2xl">
      <div class="w-20 h-20 mx-auto rounded-full bg-amber-100 border-2 border-amber-400 flex items-center justify-center animate-bounce">
        <span class="material-icons text-4xl text-amber-600">volume_up</span>
      </div>
      <h2 class="text-2xl font-black text-[#003366] brand-font">Aktifkan Suara Layar TV</h2>
      <p class="text-sm text-slate-600 leading-relaxed">
        Klik di mana saja pada layar ini untuk mengaktifkan bel lonceng dan panggilan suara otomatis untuk 4 loket PST.
      </p>
      <button class="w-full py-3.5 bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-sm uppercase tracking-wider rounded-xl shadow-md transition">
        🔊 Mulai Layar Antrean Sekarang
      </button>
    </div>
  </div>

  <!-- JavaScript Handler -->
  <script src="js/display.js?v=<?php echo time(); ?>"></script>
</body>
</html>
