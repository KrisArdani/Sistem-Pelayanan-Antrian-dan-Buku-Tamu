<?php
// SPST BPS Kota Tegal - Templat Laporan Resmi Siap Cetak / PDF
$allowed_roles = ['petugas', 'admin', 'kepala'];
require_once __DIR__ . '/../auth_check.php';
require_once __DIR__ . '/../koneksi.php';

$type = sanitizeInput($_GET['type'] ?? 'antrian');
$filterTanggal = sanitizeInput($_GET['tanggal'] ?? $_GET['waktu'] ?? 'today');
$filterLayanan = sanitizeInput($_GET['layanan'] ?? 'all');
$filterStatus = sanitizeInput($_GET['status'] ?? 'all');
$filterTipe = sanitizeInput($_GET['tipe'] ?? 'all');
$filterKategoriInstansi = sanitizeInput($_GET['kategori_instansi'] ?? '');
$q = sanitizeInput($_GET['q'] ?? '');
$tglMulai = sanitizeInput($_GET['tanggal_mulai'] ?? '');
$tglSelesai = sanitizeInput($_GET['tanggal_selesai'] ?? '');

// Proteksi Layanan Tugas Petugas
$assignedLayanan = trim($_SESSION['user_layanan_tugas'] ?? '');
if ($_SESSION['user_role'] === 'petugas' && !empty($assignedLayanan)) {
    $filterLayanan = $assignedLayanan;
}

$title = "LAPORAN REKAPITULASI ";
$whereClause = [];
$params = [];
$types = "";

// Filter Pencarian Teks
if (!empty($q)) {
    $whereClause[] = "(nama LIKE ? OR instansi LIKE ? OR layanan LIKE ? OR kode_antrian LIKE ? OR nomor LIKE ? OR nohp LIKE ? OR email LIKE ?)";
    $likeQ = '%' . $q . '%';
    for ($i = 0; $i < 7; $i++) {
        $params[] = $likeQ;
        $types .= "s";
    }
}

// Filter Kategori Instansi
if (!empty($filterKategoriInstansi)) {
    $whereClause[] = "kategori_instansi = ?";
    $params[] = $filterKategoriInstansi;
    $types .= "s";
}

// Filter Layanan
if ($filterLayanan !== 'all' && !empty($filterLayanan)) {
    $whereClause[] = "layanan LIKE ?";
    $params[] = '%' . $filterLayanan . '%';
    $types .= "s";
}

// Filter Tipe Pendaftaran
if ($filterTipe !== 'all' && !empty($filterTipe)) {
    $whereClause[] = "tipe_pendaftaran = ?";
    $params[] = $filterTipe;
    $types .= "s";
}

// Filter Status
if ($type !== 'skm' && $filterStatus !== 'all' && !empty($filterStatus)) {
    $whereClause[] = "status = ?";
    $params[] = $filterStatus;
    $types .= "s";
}

// Filter Tanggal / Waktu
if ($filterTanggal === 'today') {
    $whereClause[] = "DATE(tanggal) = CURDATE()";
    $tglLabel = date('d/m/Y');
} else if ($filterTanggal === 'tomorrow') {
    $whereClause[] = "DATE(tanggal) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)";
    $tglLabel = date('d/m/Y', strtotime('+1 day'));
} else if ($filterTanggal === 'this_week') {
    $whereClause[] = "YEARWEEK(tanggal, 1) = YEARWEEK(CURDATE(), 1)";
    $tglLabel = "Minggu Ini";
} else if ($filterTanggal === 'this_month') {
    $whereClause[] = "YEAR(tanggal) = YEAR(CURDATE()) AND MONTH(tanggal) = MONTH(CURDATE())";
    $tglLabel = "Bulan Ini (" . date('F Y') . ")";
} else if ($filterTanggal === 'custom' && !empty($tglMulai) && !empty($tglSelesai)) {
    $whereClause[] = "tanggal BETWEEN ? AND ?";
    $params[] = $tglMulai;
    $params[] = $tglSelesai;
    $types .= "ss";
    $tglLabel = date('d/m/Y', strtotime($tglMulai)) . " - " . date('d/m/Y', strtotime($tglSelesai));
} else if ($filterTanggal !== 'all' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterTanggal)) {
    $whereClause[] = "DATE(tanggal) = ?";
    $params[] = $filterTanggal;
    $types .= "s";
    $tglLabel = date('d/m/Y', strtotime($filterTanggal));
} else {
    $tglLabel = "Semua Periode";
}

if ($type === 'antrian') {
    $title .= "LOKET ANTREAN PST";
} else if ($type === 'bukutamu') {
    $title .= "BUKU TAMU PENGUNJUNG PST";
} else if ($type === 'skm') {
    $title .= "SURVEI KEPUASAN MASYARAKAT (SKM)";
    $whereClause[] = "pendapat IS NOT NULL AND pendapat != ''";
}

$sql = "SELECT id, user_id, kode_antrian AS kode_bt, kode_antrian, nomor, nama, nik, jenis_kelamin, umur, nohp, email, pendidikan, pekerjaan, instansi, kategori_instansi, fasilitas, layanan, pemanfaatan, data_diinginkan, foto, pendapat, monev, catatan, tipe_pendaftaran, status, tanggal, waktu, created_at FROM antrian";
if (!empty($whereClause)) {
    $sql .= " WHERE " . implode(" AND ", $whereClause);
}
$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Hitung Ringkasan KPI
$totalRows = count($data);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title><?php echo $title; ?> - BPS Kota Tegal</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@700;800&display=swap" rel="stylesheet">
  <style>
    @media print {
      .no-print { display: none !important; }
      body { background: white !important; color: black !important; padding: 0 !important; }
      .page-break { page-break-after: always; }
      .print-shadow-none { box-shadow: none !important; border: 1px solid #cbd5e1 !important; }
    }
  </style>
</head>
<body class="bg-slate-100 font-['Inter'] text-slate-800 p-6 md:p-10">

  <!-- Control Header (No Print) -->
  <div class="max-w-5xl mx-auto mb-6 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4 no-print bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
    <div class="flex items-center gap-3">
      <a href="javascript:window.history.back()" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl flex items-center gap-1.5 transition shrink-0">
        <span class="material-icons text-sm">arrow_back</span> Kembali
      </a>
      <div>
        <div class="text-xs font-extrabold text-slate-900">Pratinjau Dokumen Resmi PDF / Cetak</div>
        <div class="text-[11px] text-slate-500">Ubah filter waktu & loket langsung di bawah ini lalu tekan Cetak.</div>
      </div>
    </div>

    <!-- Filter Control Form (Interactive Auto-Submit - No Print) -->
    <form method="GET" action="cetak_laporan.php" class="flex items-center gap-2 flex-wrap">
      <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
      
      <!-- Select Filter Tanggal / Waktu -->
      <div class="flex items-center gap-1 bg-slate-50 p-1 rounded-xl border border-slate-200">
        <span class="material-icons text-slate-400 text-sm pl-1.5">date_range</span>
        <select name="tanggal" onchange="this.form.submit()" class="bg-transparent border-none text-xs font-bold text-slate-800 focus:ring-0 cursor-pointer py-1 pr-6">
          <option value="all" <?php echo ($filterTanggal == 'all') ? 'selected' : ''; ?>>Semua Periode</option>
          <option value="today" <?php echo ($filterTanggal == 'today') ? 'selected' : ''; ?>>Hari Ini (<?php echo date('d/m/Y'); ?>)</option>
          <option value="this_week" <?php echo ($filterTanggal == 'this_week') ? 'selected' : ''; ?>>Minggu Ini</option>
          <option value="this_month" <?php echo ($filterTanggal == 'this_month') ? 'selected' : ''; ?>>Bulan Ini (<?php echo date('F Y'); ?>)</option>
        </select>
      </div>

      <!-- Select Filter Loket Layanan -->
      <div class="flex items-center gap-1 bg-slate-50 p-1 rounded-xl border border-slate-200">
        <span class="material-icons text-slate-400 text-sm pl-1.5">storefront</span>
        <select name="layanan" onchange="this.form.submit()" class="bg-transparent border-none text-xs font-bold text-slate-800 focus:ring-0 cursor-pointer py-1 pr-6">
          <option value="all" <?php echo ($filterLayanan == 'all') ? 'selected' : ''; ?>>Semua Loket PST</option>
          <option value="Perpustakaan" <?php echo ($filterLayanan == 'Perpustakaan') ? 'selected' : ''; ?>>Perpustakaan</option>
          <option value="Konsultasi Statistik" <?php echo ($filterLayanan == 'Konsultasi Statistik') ? 'selected' : ''; ?>>Konsultasi Statistik</option>
          <option value="Rekomendasi Statistik" <?php echo ($filterLayanan == 'Rekomendasi Statistik') ? 'selected' : ''; ?>>Rekomendasi Statistik</option>
        </select>
      </div>

      <button type="button" onclick="window.print()" class="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white text-xs font-extrabold rounded-xl flex items-center gap-1.5 shadow transition shrink-0 ml-auto">
        <span class="material-icons text-sm">print</span> Cetak PDF
      </button>
    </form>
  </div>

  <!-- Printable Report Container -->
  <div class="max-w-5xl mx-auto bg-white p-8 md:p-12 rounded-2xl shadow-md border border-slate-200 print-shadow-none space-y-6">

    <!-- Official BPS Kop Surat -->
    <div class="flex items-center justify-between pb-6 border-b-2 border-slate-800">
      <div class="flex items-center gap-4">
        <img src="../img/Logo_BPS.png" alt="Logo BPS" class="w-16 h-16 object-contain">
        <div>
          <h2 class="text-lg font-black text-slate-900 tracking-wider font-['Outfit'] leading-none">BADAN PUSAT STATISTIK KOTA TEGAL</h2>
          <p class="text-xs text-slate-600 font-bold tracking-wide mt-1">SISTEM PELAYANAN STATISTIK TERPADU (SPST)</p>
          <p class="text-[11px] text-slate-500 mt-0.5">Jl. Yos Sudarso No. 1, Kota Tegal, Jawa Tengah • Telp: (0283) 351515</p>
        </div>
      </div>
      <div class="text-right">
        <div class="text-xs font-bold text-slate-700">TANGGAL CETAK</div>
        <div class="text-sm font-extrabold text-sky-800 font-mono"><?php echo date('d/m/Y H:i'); ?> WIB</div>
        <div class="text-[10px] text-slate-500 uppercase mt-0.5">Dicetak Oleh: <?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
      </div>
    </div>

    <!-- Document Title -->
    <div class="text-center space-y-1 py-2">
      <h1 class="text-xl font-extrabold text-slate-900 uppercase tracking-wide font-['Outfit']"><?php echo $title; ?></h1>
      <p class="text-xs font-semibold text-slate-600">
        Periode: <span class="font-bold text-sky-900"><?php echo $tglLabel; ?></span> | 
        Loket Layanan: <span class="font-bold text-purple-900"><?php echo htmlspecialchars($filterLayanan === 'all' ? 'Semua Loket PST' : $filterLayanan); ?></span>
      </p>
    </div>

    <!-- Summary Box -->
    <div class="grid grid-cols-3 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200 text-center">
      <div>
        <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Total Rekor Data</div>
        <div class="text-xl font-black text-slate-900 font-mono"><?php echo $totalRows; ?></div>
      </div>
      <div>
        <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Loket Layanan</div>
        <div class="text-sm font-bold text-purple-900 mt-1"><?php echo htmlspecialchars($filterLayanan === 'all' ? 'Semua Loket' : $filterLayanan); ?></div>
      </div>
      <div>
        <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status Dokumen</div>
        <div class="text-xs font-extrabold text-emerald-700 bg-emerald-100 inline-block px-2.5 py-0.5 rounded-full mt-1">VERIFIKASI TERHUBUNG</div>
      </div>
    </div>

    <!-- Data Table -->
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse text-xs">
        <thead>
          <tr class="bg-slate-800 text-white font-bold uppercase tracking-wider">
            <th class="p-3 border border-slate-700 text-center w-10">No</th>
            <?php if ($type === 'antrian'): ?>
              <th class="p-3 border border-slate-700">Kode / No</th>
              <th class="p-3 border border-slate-700">Nama Pengunjung</th>
              <th class="p-3 border border-slate-700">Pekerjaan & Instansi</th>
              <th class="p-3 border border-slate-700">Layanan PST</th>
              <th class="p-3 border border-slate-700 text-center">Tipe</th>
              <th class="p-3 border border-slate-700 text-center">Status</th>
              <th class="p-3 border border-slate-700 text-center">Waktu</th>
            <?php elseif ($type === 'bukutamu'): ?>
              <th class="p-3 border border-slate-700">Nama Tamu</th>
              <th class="p-3 border border-slate-700">Kontak (HP/Email)</th>
              <th class="p-3 border border-slate-700">Instansi</th>
              <th class="p-3 border border-slate-700">Keperluan / Layanan</th>
              <th class="p-3 border border-slate-700 text-center">Tgl & Waktu</th>
            <?php elseif ($type === 'skm'): ?>
              <th class="p-3 border border-slate-700">Kode Tiket</th>
              <th class="p-3 border border-slate-700">Responden</th>
              <th class="p-3 border border-slate-700">Layanan PST</th>
              <th class="p-3 border border-slate-700 text-center">Tingkat Kepuasan</th>
              <th class="p-3 border border-slate-700">Kritik & Saran</th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
          <?php if (empty($data)): ?>
            <tr>
              <td colspan="8" class="p-6 text-center text-slate-500 font-semibold italic">Tidak ada rekor data yang sesuai dengan filter laporan.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($data as $idx => $row): ?>
              <tr class="hover:bg-slate-50 transition <?php echo ($idx % 2 === 1) ? 'bg-slate-50/50' : 'bg-white'; ?>">
                <td class="p-2.5 border border-slate-300 text-center font-bold text-slate-600"><?php echo $idx + 1; ?></td>
                
                <?php if ($type === 'antrian'): ?>
                  <td class="p-2.5 border border-slate-300 font-mono font-bold text-sky-900"><?php echo htmlspecialchars($row['kode_antrian']); ?></td>
                  <td class="p-2.5 border border-slate-300 font-semibold text-slate-900"><?php echo htmlspecialchars($row['nama']); ?></td>
                  <td class="p-2.5 border border-slate-300 text-slate-600"><?php echo htmlspecialchars($row['pekerjaan'] . ' - ' . $row['instansi']); ?></td>
                  <td class="p-2.5 border border-slate-300 font-semibold text-purple-900"><?php echo htmlspecialchars($row['layanan']); ?></td>
                  <td class="p-2.5 border border-slate-300 text-center">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?php echo ($row['tipe_pendaftaran'] === 'online') ? 'bg-sky-100 text-sky-800' : 'bg-amber-100 text-amber-800'; ?>">
                      <?php echo htmlspecialchars($row['tipe_pendaftaran']); ?>
                    </span>
                  </td>
                  <td class="p-2.5 border border-slate-300 text-center font-bold">
                    <?php echo htmlspecialchars($row['status']); ?>
                  </td>
                  <td class="p-2.5 border border-slate-300 text-center font-mono text-[11px]"><?php echo date('d/m/Y H:i', strtotime($row['tanggal'] . ' ' . $row['waktu'])); ?></td>

                <?php elseif ($type === 'bukutamu'): ?>
                  <td class="p-2.5 border border-slate-300 font-bold text-slate-900"><?php echo htmlspecialchars($row['nama']); ?></td>
                  <td class="p-2.5 border border-slate-300 text-slate-600 font-mono"><?php echo htmlspecialchars($row['nohp'] ?: $row['email']); ?></td>
                  <td class="p-2.5 border border-slate-300 text-slate-700"><?php echo htmlspecialchars($row['instansi']); ?></td>
                  <td class="p-2.5 border border-slate-300 font-semibold text-purple-900"><?php echo htmlspecialchars($row['layanan']); ?></td>
                  <td class="p-2.5 border border-slate-300 text-center font-mono text-[11px]"><?php echo date('d/m/Y H:i', strtotime($row['tanggal'] . ' ' . $row['waktu'])); ?></td>

                <?php elseif ($type === 'skm'): ?>
                  <td class="p-2.5 border border-slate-300 font-mono font-bold text-sky-900"><?php echo htmlspecialchars($row['kode_antrian']); ?></td>
                  <td class="p-2.5 border border-slate-300 font-bold text-slate-900"><?php echo htmlspecialchars($row['nama']); ?></td>
                  <td class="p-2.5 border border-slate-300 font-semibold text-purple-900"><?php echo htmlspecialchars($row['layanan']); ?></td>
                  <td class="p-2.5 border border-slate-300 text-center font-extrabold">
                    <?php
                      $p = $row['pendapat'];
                      if ($p === 'Sangat Puas') echo '<span class="text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded">⭐ Sangat Puas</span>';
                      else if ($p === 'Puas') echo '<span class="text-sky-700 bg-sky-100 px-2 py-0.5 rounded">👍 Puas</span>';
                      else if ($p === 'Cukup Puas') echo '<span class="text-amber-700 bg-amber-100 px-2 py-0.5 rounded">👌 Cukup Puas</span>';
                      else echo '<span class="text-rose-700 bg-rose-100 px-2 py-0.5 rounded">👎 Tidak Puas</span>';
                    ?>
                  </td>
                  <td class="p-2.5 border border-slate-300 text-slate-600 italic"><?php echo htmlspecialchars($row['catatan'] ?: '-'); ?></td>
                <?php endif; ?>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Official Signature Block -->
    <div class="pt-10 grid grid-cols-2 gap-8 text-center text-xs">
      <div>
        <p class="text-slate-500 font-semibold">Mengetahui,</p>
        <p class="font-extrabold text-slate-900 mt-0.5">Kepala BPS Kota Tegal</p>
        <div class="h-20"></div>
        <p class="font-bold text-slate-900 underline uppercase">BPS KOTA TEGAL</p>
        <p class="text-[10px] text-slate-500">NIP. 19780101 200003 1 001</p>
      </div>

      <div>
        <p class="text-slate-500 font-semibold">Tegal, <?php echo date('d F Y'); ?></p>
        <p class="font-extrabold text-slate-900 mt-0.5">Petugas Penanggung Jawab PST</p>
        <div class="h-20"></div>
        <p class="font-bold text-slate-900 underline uppercase"><?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
        <p class="text-[10px] text-slate-500">Petugas SPST BPS Kota Tegal</p>
      </div>
    </div>

  </div>

</body>
</html>
