<?php
// SPST BPS Kota Tegal - WebGIS Interaktif Kota Tegal
require_once __DIR__ . '/security.php';
setSecurityHeaders();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Wajibkan login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$csrf_token = generateCsrfToken();
$activeMenu = 'webgis';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
  <title>WebGIS Kota Tegal - Sistem Pelayanan Statistik Terpadu BPS Kota Tegal</title>

  <!-- Tailwind CSS Play CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Bootstrap 5.3.3 CSS & Bundle -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Icons & Fonts -->
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
  <!-- Leaflet CSS & JS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- Custom CSS -->
  <link rel="stylesheet" href="css/custom.css">
  
  <style>
    .select-control {
      width: 100%;
      background-color: #f8fafc;
      border: 1px solid #cbd5e1;
      color: #1e293b;
      font-size: 0.875rem;
      border-radius: 0.75rem;
      padding: 0.625rem 0.875rem;
      font-weight: 500;
      box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
      transition: all 0.2s ease-in-out;
      outline: none;
      cursor: pointer;
    }
    .select-control:hover {
      border-color: #38bdf8;
      background-color: #ffffff;
    }
    .select-control:focus {
      border-color: #0284c7;
      background-color: #ffffff;
      box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.25);
    }
    .filter-card {
      background: #ffffff;
      border-radius: 1rem;
      border: 1px solid #e2e8f0;
      padding: 1rem 1.125rem;
      box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.03);
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    .filter-card:hover {
      border-color: #bae6fd;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }
    .filter-label {
      font-size: 0.75rem;
      font-weight: 700;
      color: #334155;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      margin-bottom: 0.375rem;
    }
    .leaflet-popup-content-wrapper {
      border-radius: 1rem !important;
      box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1) !important;
      border: 1px solid #e2e8f0 !important;
      padding: 0 !important;
      overflow: hidden !important;
    }
    .leaflet-popup-content {
      margin: 0 !important;
    }
  </style>
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
          <!-- Mobile Toggle Button -->
          <button class="lg:hidden p-2 rounded-lg bg-slate-100 text-slate-700" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
            <span class="material-icons">menu</span>
          </button>
          <span class="text-xs font-bold px-3 py-1 bg-sky-100 text-sky-700 rounded-full uppercase tracking-wider flex items-center gap-1">
            <span class="material-icons text-sm">map</span>
            WebGIS Kota Tegal
          </span>
        </div>

        <div class="flex items-center gap-3">
          <!-- TTS Toggle Button -->
          <button id="btn-tts-toggle" class="btn btn-secondary btn-sm flex items-center gap-1 shadow-sm" title="Aktifkan Pembacaan Suara (Text-To-Speech)">
            <span class="material-icons text-base">volume_off</span>
            <span class="hidden sm:inline text-xs font-semibold">Suara</span>
          </button>

          <?php if (isset($_SESSION['user_id'])): ?>
            <div class="flex items-center gap-2 px-3 py-1.5 bg-sky-50 border border-sky-200 rounded-full text-xs font-semibold text-slate-700 shadow-sm">
              <span class="material-icons text-sky-600 text-sm">account_circle</span>
              <span class="hidden sm:inline">Pengunjung: <b class="text-sky-900"><?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?></b></span>
            </div>
            <a href="logout.php" class="btn btn-outline-danger btn-sm text-xs flex items-center gap-1 border-rose-300 text-rose-700 hover:bg-rose-600 hover:text-white font-bold px-3 rounded-xl shadow-sm" title="Keluar">
              <span class="material-icons text-sm">logout</span>
              <span class="hidden sm:inline">Keluar</span>
            </a>
          <?php endif; ?>
        </div>
      </header>

      <!-- Main WebGIS Layout Grid -->
      <div class="p-6 md:p-8 space-y-8 max-w-7xl mx-auto w-full">
        
        <!-- Header Banner -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gradient-to-r from-[#003366] via-[#004080] to-[#00A3E0] rounded-3xl p-6 md:p-8 text-white shadow-lg relative overflow-hidden">
          <div class="space-y-2 z-10">
            <span class="inline-block bg-white/20 backdrop-blur-md px-3 py-1 rounded-full text-[11px] font-bold text-sky-100 uppercase tracking-widest">DASHBOARD ANALITIK SPASIAL</span>
            <h1 class="text-2xl md:text-4xl font-extrabold brand-font leading-tight">WebGIS Interaktif Kota Tegal</h1>
            <p class="text-sky-100 text-xs md:text-sm max-w-3xl leading-relaxed">
              Eksplorasi peta wilayah 4 Kecamatan di Kota Tegal berbasis indikator statistik BPS (Kependudukan, Geografi, Ekonomi, Lingkungan, Pendidikan, & Sosial Budaya).
            </p>
          </div>
          <div class="z-10 shrink-0 bg-white/10 backdrop-blur-md border border-white/20 p-4 rounded-2xl text-center min-w-[150px]">
            <div class="text-xs text-sky-200 uppercase font-semibold">Total Kota Tegal</div>
            <div id="statTotalVal" class="text-2xl font-extrabold text-white">301.000</div>
            <div id="statTotalUnit" class="text-xs text-amber-300 font-bold">Jiwa</div>
          </div>
          <div class="absolute -right-8 -bottom-8 opacity-10 text-white pointer-events-none">
            <span class="material-icons" style="font-size: 200px;">map</span>
          </div>
        </div>

        <!-- WebGIS Core Grid (Sidebar Filter + Map & Charts) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
          
          <!-- Left Column: Filter Sidebar Panel (4 cols) -->
          <div class="lg:col-span-4 space-y-5">
            <div class="bg-white p-6 rounded-3xl border border-slate-200/90 shadow-sm space-y-5 sticky top-24">
              
              <!-- Panel Header -->
              <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                  <div class="w-9 h-9 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center shadow-inner">
                    <span class="material-icons text-xl">tune</span>
                  </div>
                  <div>
                    <h2 class="text-slate-900 font-bold text-base brand-font leading-none">Panel Filter</h2>
                    <span class="text-[11px] text-slate-500 font-medium">BPS Kota Tegal</span>
                  </div>
                </div>
                <button id="btnResetFilter" type="button" class="text-xs font-bold text-slate-500 hover:text-sky-700 bg-slate-100 hover:bg-sky-50 px-2.5 py-1.5 rounded-lg transition flex items-center gap-1">
                  <span class="material-icons text-sm">refresh</span>
                  <span>Reset</span>
                </button>
              </div>

              <!-- Group 1: Pilihan Indikator Data -->
              <div class="space-y-3">
                <div class="text-[11px] font-extrabold text-sky-800 uppercase tracking-wider flex items-center gap-1.5 bg-sky-50/80 px-3 py-1.5 rounded-lg border border-sky-100">
                  <span class="material-icons text-sm text-sky-600">bar_chart</span>
                  <span>PILIHAN INDIKATOR DATA</span>
                </div>

                <!-- Filter 1: Kategori Utama -->
                <div class="filter-card">
                  <label for="mainCategorySelector" class="filter-label">
                    <span class="material-icons text-sm text-sky-600">category</span>
                    <span>Kategori Utama</span>
                  </label>
                  <select id="mainCategorySelector" class="select-control">
                    <optgroup label="--Data Spasial Mikro (Per Kecamatan)--">
                      <option value="penduduk" selected>Data Penduduk (BPS 519)</option>
                      <option value="geografi">Data Geografi</option>
                      <option value="ekonomi">Sarana & Ekonomi Wilayah</option>
                      <option value="lingkungan">Lingkungan & Sanitasi</option>
                      <option value="pendidikan">Sarana Pendidikan</option>
                      <option value="sosial_budaya">Sosial, Budaya & Faskes</option>
                    </optgroup>
                    <optgroup label="--Data Strategis Makro (Web API BPS Live)--">
                      <option value="bps_kemiskinan">Kemiskinan & Kesejahteraan (BPS Live)</option>
                      <option value="bps_ipm">Indeks Pembangunan Manusia (BPS Live)</option>
                      <option value="bps_ketenagakerjaan">Ketenagakerjaan (BPS Live)</option>
                      <option value="bps_ekonomi">PDRB & Pertumbuhan Ekonomi (BPS Live)</option>
                    </optgroup>
                  </select>
                </div>

                <!-- Filter 2: Data Spesifik -->
                <div class="filter-card">
                  <label for="dataCategorySelector" class="filter-label">
                    <span class="material-icons text-sm text-sky-600">analytics</span>
                    <span>Indikator Spesifik</span>
                  </label>
                  <select id="dataCategorySelector" class="select-control">
                    <option value="penduduk_total">Jumlah Penduduk Total (Jiwa)</option>
                  </select>
                </div>
              </div>

              <!-- Group 2: Cakupan Spasial & Waktu -->
              <div class="space-y-3 pt-2">
                <div class="text-[11px] font-extrabold text-sky-800 uppercase tracking-wider flex items-center gap-1.5 bg-sky-50/80 px-3 py-1.5 rounded-lg border border-sky-100">
                  <span class="material-icons text-sm text-sky-600">travel_explore</span>
                  <span>CAKUPAN SPASIAL & WAKTU</span>
                </div>

                <!-- Filter 3: Wilayah Kecamatan -->
                <div class="filter-card">
                  <label for="areaSelector" class="filter-label">
                    <span class="material-icons text-sm text-sky-600">location_on</span>
                    <span>Wilayah Kecamatan</span>
                  </label>
                  <select id="areaSelector" class="select-control">
                    <option value="">-- Semua Kecamatan --</option>
                    <option value="030">TEGAL BARAT</option>
                    <option value="020">TEGAL TIMUR</option>
                    <option value="010">TEGAL SELATAN</option>
                    <option value="040">MARGADANA</option>
                  </select>
                </div>

                <!-- Filter 4: Tahun Single (Peta & Pie Chart) -->
                <div class="filter-card">
                  <label for="yearSelector" class="filter-label">
                    <span class="material-icons text-sm text-sky-600">event</span>
                    <span>Tahun Peta & Pie Chart</span>
                  </label>
                  <select id="yearSelector" class="select-control">
                    <option value="2026">2026</option>
                    <option value="2025">2025</option>
                    <option value="2024" selected>2024</option>
                    <option value="2023">2023</option>
                    <option value="2022">2022</option>
                    <option value="2021">2021</option>
                    <option value="2020">2020</option>
                  </select>
                </div>

                <!-- Filter 5: Rentang Tahun Tren -->
                <div class="filter-card">
                  <label class="filter-label mb-2">
                    <span class="material-icons text-sm text-sky-600">date_range</span>
                    <span>Rentang Tahun Tren</span>
                  </label>
                  <div class="grid grid-cols-2 gap-2">
                    <div>
                      <span class="text-[10px] font-bold text-slate-500 uppercase block mb-1">Tahun Awal:</span>
                      <select id="startYearSelector" class="select-control text-xs p-2">
                        <option value="2020" selected>2020</option>
                        <option value="2021">2021</option>
                        <option value="2022">2022</option>
                        <option value="2023">2023</option>
                        <option value="2024">2024</option>
                      </select>
                    </div>
                    <div>
                      <span class="text-[10px] font-bold text-slate-500 uppercase block mb-1">Tahun Akhir:</span>
                      <select id="endYearSelector" class="select-control text-xs p-2">
                        <option value="2026" selected>2026</option>
                        <option value="2025">2025</option>
                        <option value="2024">2024</option>
                        <option value="2023">2023</option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="pt-2 text-center text-[11px] text-slate-400 font-medium">
                Peta & Grafik akan diperbarui secara otomatis ketika filter diubah.
              </div>
            </div>
          </div>

          <!-- Right Column: Interactive Map & Analytics (8 cols) -->
          <div class="lg:col-span-8 space-y-8">
            
            <!-- Map Card -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-md p-4 space-y-3">
              <div class="flex items-center justify-between px-2 pt-1">
                <div class="flex items-center gap-2">
                  <div id="mapStatusPulse" class="w-3 h-3 rounded-full bg-sky-500 animate-pulse"></div>
                  <h3 id="mapCardTitle" class="text-lg font-extrabold text-slate-900 brand-font">Peta Spasial Choropleth Kota Tegal</h3>
                </div>
                <div class="flex items-center gap-2">
                  <span id="mapSourceBadge" class="text-[11px] font-bold px-2.5 py-1 rounded-lg bg-sky-50 text-sky-700 border border-sky-200 flex items-center gap-1">
                    <span class="material-icons text-xs">tune</span>
                    <span id="mapSourceText">Data Spasial Kecamatan</span>
                  </span>
                  <span class="text-xs text-slate-400 font-semibold hidden md:inline">| Zoom & klik wilayah</span>
                </div>
              </div>
              <div id="webgisMap" class="w-full h-[480px] rounded-2xl border border-slate-200 overflow-hidden shadow-inner z-10"></div>
            </div>

            <!-- Analytics Cards Grid (Pie Chart + Trend Chart) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              
              <!-- Pie Chart / Comparison Card -->
              <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-4 flex flex-col justify-between">
                <div>
                  <div class="flex items-center justify-between mb-1">
                    <div class="flex items-center gap-2">
                      <span id="distributionIcon" class="material-icons text-amber-500 text-xl">pie_chart</span>
                      <h4 id="distributionTitle" class="text-base font-bold text-slate-900 brand-font">Proporsi Data Wilayah</h4>
                    </div>
                    <span id="distributionBadge" class="text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 uppercase">Kecamatan</span>
                  </div>
                  <p id="distributionDesc" class="text-xs text-slate-500">Proporsi distribusi data antar kecamatan pada tahun terpilih.</p>
                </div>
                
                <!-- Canvas Pie Chart Container -->
                <div id="pieChartContainer" class="relative h-60 w-full flex items-center justify-center">
                  <canvas id="pieChartCanvas"></canvas>
                </div>

                <!-- BPS Comparison Cards Container (Hidden by default, shown for BPS Live) -->
                <div id="bpsComparisonContainer" class="hidden space-y-3 py-2">
                  <div class="p-3.5 rounded-2xl bg-sky-50/80 border border-sky-200 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                      <div class="w-8 h-8 rounded-xl bg-sky-600 text-white flex items-center justify-center font-bold text-xs shadow-sm">
                        TG
                      </div>
                      <div>
                        <div class="text-[11px] font-bold text-sky-800 uppercase tracking-wider">Kota Tegal</div>
                        <div class="text-xs text-slate-500">Capaian Kota</div>
                      </div>
                    </div>
                    <div class="text-right">
                      <div id="bpsValKota" class="text-lg font-black text-sky-900">0</div>
                      <div id="bpsUnitKota" class="text-[10px] font-bold text-sky-700">-</div>
                    </div>
                  </div>

                  <div id="bpsJatengCard" class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                      <div class="w-8 h-8 rounded-xl bg-slate-500 text-white flex items-center justify-center font-bold text-xs shadow-sm">
                        JT
                      </div>
                      <div>
                        <div class="text-[11px] font-bold text-slate-700 uppercase tracking-wider">Provinsi Jawa Tengah</div>
                        <div class="text-xs text-slate-500">Rata-rata Provinsi</div>
                      </div>
                    </div>
                    <div class="text-right">
                      <div id="bpsValJateng" class="text-lg font-black text-slate-800">0</div>
                      <div id="bpsUnitJateng" class="text-[10px] font-bold text-slate-600">-</div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Trend Chart Card -->
              <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-4 flex flex-col justify-between">
                <div>
                  <div class="flex items-center justify-between mb-1">
                    <div class="flex items-center gap-2">
                      <span class="material-icons text-sky-600 text-xl">show_chart</span>
                      <h4 class="text-base font-bold text-slate-900 brand-font">Tren Data per Tahun</h4>
                    </div>
                    <span id="trendBadge" class="text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-sky-100 text-sky-700 uppercase">Multi-Tahun</span>
                  </div>
                  <p id="trendDesc" class="text-xs text-slate-500">Perkembangan indikator pada rentang tahun yang dipilih.</p>
                </div>
                <div class="relative h-60 w-full flex items-center justify-center">
                  <canvas id="trendChartCanvas"></canvas>
                </div>
              </div>

            </div>

            <!-- Auto Summary Card -->
            <div class="bg-gradient-to-br from-slate-900 to-sky-950 p-6 md:p-8 rounded-3xl text-white shadow-xl space-y-4 border border-slate-800">
              <div class="flex items-center gap-3 border-b border-slate-800 pb-3">
                <div class="w-10 h-10 rounded-xl bg-sky-500/20 text-sky-400 flex items-center justify-center font-bold">
                  <span class="material-icons text-2xl">auto_awesome</span>
                </div>
                <div>
                  <h3 class="text-lg font-extrabold brand-font text-white">Rangkuman Analitik Otomatis</h3>
                  <p class="text-xs text-sky-300">Generasi narasi hasil pengolahan data statistik aktif BPS Kota Tegal</p>
                </div>
              </div>
              <div id="autoSummaryContent" class="text-sm md:text-base leading-relaxed text-slate-200 font-normal">
                Memuat narasi analitik otomatis...
              </div>
            </div>

            <!-- Data Source Citation Card -->
            <div class="bg-sky-50 border border-sky-200 p-5 rounded-2xl flex items-start gap-4 text-xs text-slate-700 shadow-sm">
              <span class="material-icons text-sky-600 text-2xl shrink-0">menu_book</span>
              <div class="space-y-1">
                <div class="font-extrabold text-sky-900 text-sm">Sumber Data yang Digunakan</div>
                <p class="text-slate-600 leading-relaxed">
                  Data statistik disajikan berbasis rujukan resmi **Badan Pusat Statistik (BPS) Kota Tegal** (Subjek 519: Kependudukan dan Migrasi) serta seri data publikasi *Kota Tegal Dalam Angka* & *Kecamatan Dalam Angka*. Akses portal data BPS Kota Tegal melalui:
                  <a href="https://tegalkota.bps.go.id/id/statistics-table?subject=519&sortBy=date%2Ctitle&sortOrder=desc%2Casc" target="_blank" class="text-sky-700 font-bold underline hover:text-sky-900 ml-1">
                    tegalkota.bps.go.id
                  </a>
                </p>
              </div>
            </div>

          </div>

        </div>

      </div>

      <!-- Include Footer Component -->
      <?php include 'footer.php'; ?>

    </main>
  </div>

  <!-- Script Helpers -->
  <script src="js/app.js"></script>
  <script src="js/tts.js"></script>
  <script src="js/webgis.js"></script>
</body>
</html>
