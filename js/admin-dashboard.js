/**
 * SPST BPS Kota Tegal - Pengontrol Grafik & KPI Dashboard Eksekutif Chart.js
 */

document.addEventListener('DOMContentLoaded', async () => {
  const isAuth = await checkAuth(['admin', 'kepala', 'petugas']);
  if (!isAuth) return;

  // Tampilkan peran & nama pengguna
  const curUser = getCurrentUser();
  if (curUser) {
    const nameElem = document.getElementById('user_display_name');
    const roleElem = document.getElementById('user_display_role');
    if (nameElem) nameElem.textContent = curUser.name;
    if (roleElem) roleElem.textContent = `Aktor: ${String(curUser.role).toUpperCase()}`;
  }

  const filterSelect = document.getElementById('filter_tanggal_skm');
  if (filterSelect) {
    filterSelect.addEventListener('change', async () => {
      const kpiData = await calculateKPIs();
      if (kpiData) initCharts(kpiData);
    });
  }

  const kpiData = await calculateKPIs();
  if (kpiData) initCharts(kpiData);
});

async function calculateKPIs() {
  const kpiTotalPengunjung = document.getElementById('kpi_total_pengunjung');
  const kpiTotalAntrian = document.getElementById('kpi_total_antrian');
  const kpiTodayAntrian = document.getElementById('kpi_today_antrian');
  const kpiTotalSelesai = document.getElementById('kpi_total_selesai');
  const kpiTotalAktif = document.getElementById('kpi_total_aktif');
  const kpiPuas = document.getElementById('kpi_puas_persen');
  const kpiSkmSkor = document.getElementById('kpi_skm_skor');
  const kpiSkmMutu = document.getElementById('kpi_skm_mutu');
  const kpiTotalMenunggu = document.getElementById('kpi_total_menunggu');
  const kpiTotalOnline = document.getElementById('kpi_total_online');
  const kpiTotalWalkin = document.getElementById('kpi_total_walkin');
  const kpiTotalPengaduan = document.getElementById('kpi_total_pengaduan');

  try {
    const filterVal = document.getElementById('filter_tanggal_skm')?.value || 'all';
    const res = await fetch(`../api.php?action=get_dashboard_kpi&tanggal=${encodeURIComponent(filterVal)}`);
    const json = await res.json();
    if (json.status === 'success') {
      if (kpiTotalPengunjung) kpiTotalPengunjung.textContent = json.data.total_pengunjung || 0;
      if (kpiTotalAntrian) kpiTotalAntrian.textContent = json.data.total_antrian || 0;
      if (kpiTodayAntrian) kpiTodayAntrian.textContent = json.data.total_antrian_today || 0;
      if (kpiTotalSelesai) kpiTotalSelesai.textContent = json.data.total_selesai || 0;
      if (kpiTotalAktif) kpiTotalAktif.textContent = json.data.total_aktif || 0;
      if (kpiPuas) kpiPuas.textContent = `${json.data.skm_puas_persen}%`;
      if (kpiSkmSkor) kpiSkmSkor.textContent = json.data.skm_skor ? json.data.skm_skor.toFixed(2) : '4.00';
      if (kpiSkmMutu) kpiSkmMutu.textContent = `Mutu: ${json.data.skm_mutu || 'A (Sangat Baik)'} • (${json.data.skm_nilai || 100}/100)`;
      if (kpiTotalMenunggu) kpiTotalMenunggu.textContent = json.data.total_menunggu || 0;
      if (kpiTotalOnline) kpiTotalOnline.textContent = json.data.total_online || 0;
      if (kpiTotalWalkin) kpiTotalWalkin.textContent = json.data.total_walkin || 0;
      if (kpiTotalPengaduan) kpiTotalPengaduan.textContent = json.data.total_pengaduan || 0;

      renderRecentActivity(json.data.recent_antrian || []);
      renderRecentFeedback(json.data.recent_feedback || []);
      renderRecentPengaduan(json.data.recent_pengaduan || []);

      return json.data;
    }
  } catch (err) {
    console.error('API get_dashboard_kpi failed:', err);
  }
  return null;
}

function renderRecentActivity(data) {
  const tbody = document.getElementById('tbody_recent_antrian');
  if (!tbody) return;

  if (data.length === 0) {
    tbody.innerHTML = `<tr><td colspan="5" class="py-6 text-center text-slate-400 font-medium text-sm">Belum ada transaksi kunjungan.</td></tr>`;
    return;
  }

  tbody.innerHTML = data.map(item => {
    let statusClass = 'bg-amber-100 text-amber-900 border border-amber-200';
    if (item.status === 'Dipanggil') statusClass = 'bg-sky-100 text-sky-900 animate-pulse font-bold border border-sky-300';
    if (item.status === 'Dilayani') statusClass = 'bg-cyan-100 text-cyan-900 font-bold border border-cyan-300';
    if (item.status === 'Selesai') statusClass = 'bg-emerald-100 text-emerald-900 font-bold border border-emerald-300';
    if (item.status === 'Dibatalkan') statusClass = 'bg-slate-100 text-slate-600 border border-slate-200';

    const typeBadge = item.tipe_pendaftaran === 'walkin'
      ? `<span class="px-2 py-1 rounded-lg bg-purple-100 text-purple-800 font-bold text-xs">Walk-In</span>`
      : `<span class="px-2 py-1 rounded-lg bg-sky-100 text-sky-800 font-bold text-xs">Online</span>`;

    return `
      <tr class="border-b border-slate-100 hover:bg-sky-50/50 transition">
        <td class="py-3.5 px-2 font-black text-sky-800 text-sm">${escapeHtml(item.nomor || '-')}</td>
        <td class="py-3.5 px-2 font-bold text-slate-900 text-sm">${escapeHtml(item.nama)}</td>
        <td class="py-3.5 px-2 text-slate-700 text-sm font-medium">${escapeHtml(item.layanan)}</td>
        <td class="py-3.5 px-2">${typeBadge}</td>
        <td class="py-3.5 px-2 text-right"><span class="px-3 py-1 rounded-full text-xs ${statusClass}">${escapeHtml(item.status)}</span></td>
      </tr>
    `;
  }).join('');
}

function renderRecentFeedback(data) {
  const container = document.getElementById('container_recent_skm');
  if (!container) return;

  if (data.length === 0) {
    container.innerHTML = `<div class="text-center py-8 text-slate-400 text-sm font-medium italic">Belum ada ulasan SKM dari pengunjung.</div>`;
    return;
  }

  container.innerHTML = data.map(item => {
    let emoji = '😍';
    if (item.pendapat === 'Puas') emoji = '😊';
    if (item.pendapat === 'Cukup Puas') emoji = '😐';
    if (item.pendapat === 'Tidak Puas') emoji = '🙁';

    return `
      <div class="p-4.5 bg-white rounded-2xl border border-slate-200 shadow-sm space-y-2 hover:border-amber-400 hover:shadow transition">
        <div class="flex items-center justify-between">
          <span class="font-bold text-sm text-slate-900">${escapeHtml(item.nama)} <span class="text-xs font-normal text-slate-400">(${escapeHtml(item.nomor || '-')})</span></span>
          <span class="text-sm font-extrabold text-amber-700 bg-amber-50 px-2.5 py-0.5 rounded-lg border border-amber-200 flex items-center gap-1">${emoji} ${escapeHtml(item.pendapat)}</span>
        </div>
        <p class="text-sm text-slate-700 italic leading-relaxed font-medium bg-slate-50 p-2.5 rounded-xl border border-slate-100">"${escapeHtml(item.catatan || 'Tidak ada catatan ulasan.')}"</p>
        <div class="text-xs text-sky-700 font-semibold flex items-center gap-1"><span class="material-icons text-xs">local_offer</span> ${escapeHtml(item.layanan)}</div>
      </div>
    `;
  }).join('');
}

function renderRecentPengaduan(data) {
  const tbody = document.getElementById('tbody_recent_pengaduan');
  if (!tbody) return;

  if (data.length === 0) {
    tbody.innerHTML = `<tr><td colspan="6" class="py-6 text-center text-slate-400 font-medium text-sm">Belum ada pengaduan atau masukan publik.</td></tr>`;
    return;
  }

  tbody.innerHTML = data.map(item => {
    const isPengaduan = item.tipe === 'pengaduan';
    const typeBadge = isPengaduan
      ? `<span class="px-2.5 py-1 rounded-lg bg-rose-100 text-rose-800 font-bold text-xs flex items-center gap-1 w-max"><span class="material-icons text-xs">warning</span> Pengaduan</span>`
      : `<span class="px-2.5 py-1 rounded-lg bg-emerald-100 text-emerald-800 font-bold text-xs flex items-center gap-1 w-max"><span class="material-icons text-xs">thumb_up</span> Penilaian</span>`;

    return `
      <tr class="border-b border-slate-100 hover:bg-slate-50 transition text-sm">
        <td class="py-3.5 px-2">${typeBadge}</td>
        <td class="py-3.5 px-2 font-bold text-slate-900">${escapeHtml(item.nama || 'Pengunjung')}</td>
        <td class="py-3.5 px-2 text-slate-600 font-medium">${escapeHtml(item.kontak || '-')}</td>
        <td class="py-3.5 px-2 font-semibold text-amber-700">${escapeHtml(item.rating_atau_kategori || '-')}</td>
        <td class="py-3.5 px-2 text-slate-700 max-w-md italic">"${escapeHtml(item.pesan)}"</td>
        <td class="py-3.5 px-2 text-right text-xs font-semibold text-slate-500">${escapeHtml(item.created_at || '-')}</td>
      </tr>
    `;
  }).join('');
}

function initCharts(chartData) {
  if (typeof Chart === 'undefined') return;

  // Pembantu perataan grafik
  const createChart = (canvasId, type, labels, data, colors, options = {}) => {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;

    const defaultOptions = {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: type !== 'bar',
          position: 'bottom',
          labels: { boxWidth: 12, font: { size: 11, family: 'Inter' } }
        }
      }
    };

    new Chart(ctx, {
      type: type,
      data: {
        labels: labels,
        datasets: [{
          label: 'Jumlah',
          data: data,
          backgroundColor: colors,
          borderRadius: type === 'bar' ? 6 : 0
        }]
      },
      options: { ...defaultOptions, ...options }
    });
  };

  // 1. Chart Layanan (Doughnut)
  const labelsLayanan = (chartData?.chart_layanan?.length > 0)
    ? chartData.chart_layanan.map(i => i.layanan)
    : ['Konsultasi Statistik', 'Perpustakaan & Diseminasi Data', 'Rekomendasi Kegiatan Statistik', 'Layanan Pengaduan'];
  const valuesLayanan = (chartData?.chart_layanan?.length > 0)
    ? chartData.chart_layanan.map(i => i.jumlah)
    : [1, 1, 1, 0];
  createChart('chartLayanan', 'doughnut', labelsLayanan, valuesLayanan, ['#003366', '#00A3E0', '#FF6B35', '#10B981', '#6366F1']);

  // 2. Chart Status (Bar)
  const labelsStatus = (chartData?.chart_status?.length > 0)
    ? chartData.chart_status.map(i => i.status)
    : ['Selesai', 'Menunggu', 'Dilayani', 'Dipanggil'];
  const valuesStatus = (chartData?.chart_status?.length > 0)
    ? chartData.chart_status.map(i => i.jumlah)
    : [1, 0, 0, 0];
  createChart('chartStatus', 'bar', labelsStatus, valuesStatus, ['#10B981', '#F59E0B', '#06B6D4', '#3B82F6', '#64748B']);

  // 3. Chart Tipe Pendaftaran (Pie)
  const labelsTipe = (chartData?.chart_tipe?.length > 0)
    ? chartData.chart_tipe.map(i => i.tipe_pendaftaran === 'walkin' ? 'Walk-In Offline' : 'Online Booking')
    : ['Online Booking', 'Walk-In Offline'];
  const valuesTipe = (chartData?.chart_tipe?.length > 0)
    ? chartData.chart_tipe.map(i => i.jumlah)
    : [1, 0];
  createChart('chartTipe', 'pie', labelsTipe, valuesTipe, ['#3B82F6', '#A855F7']);

  // 4. Chart Pekerjaan (Bar)
  const labelsPekerjaan = (chartData?.chart_pekerjaan?.length > 0)
    ? chartData.chart_pekerjaan.map(i => i.pekerjaan)
    : ['Mahasiswa', 'Peneliti/Dosen', 'PNS'];
  const valuesPekerjaan = (chartData?.chart_pekerjaan?.length > 0)
    ? chartData.chart_pekerjaan.map(i => i.jumlah)
    : [1, 1, 1];
  createChart('chartPekerjaan', 'bar', labelsPekerjaan, valuesPekerjaan, '#6366F1');

  // 5. Chart Pendidikan (Doughnut)
  const labelsPendidikan = (chartData?.chart_pendidikan?.length > 0)
    ? chartData.chart_pendidikan.map(i => i.pendidikan)
    : ['D4-S1', 'SMA Ke Bawah', 'S2-S3'];
  const valuesPendidikan = (chartData?.chart_pendidikan?.length > 0)
    ? chartData.chart_pendidikan.map(i => i.jumlah)
    : [2, 1, 0];
  createChart('chartPendidikan', 'doughnut', labelsPendidikan, valuesPendidikan, ['#10B981', '#F59E0B', '#3B82F6', '#EC4899']);

  // 6. Chart Instansi (Bar)
  const labelsInstansi = (chartData?.chart_instansi?.length > 0)
    ? chartData.chart_instansi.map(i => i.kategori_instansi)
    : ['Sekolah/Universitas', 'Pemda', 'Swasta'];
  const valuesInstansi = (chartData?.chart_instansi?.length > 0)
    ? chartData.chart_instansi.map(i => i.jumlah)
    : [2, 1, 0];
  createChart('chartInstansi', 'bar', labelsInstansi, valuesInstansi, '#F59E0B');

  // 7. Chart Kelompok Umur (Doughnut)
  const labelsUmur = (chartData?.chart_umur?.length > 0)
    ? chartData.chart_umur.map(i => i.umur)
    : ['17-25 tahun', '26-34 tahun', '35-44 tahun'];
  const valuesUmur = (chartData?.chart_umur?.length > 0)
    ? chartData.chart_umur.map(i => i.jumlah)
    : [1, 1, 1];
  createChart('chartUmur', 'doughnut', labelsUmur, valuesUmur, ['#FF6B35', '#00A3E0', '#10B981', '#8B5CF6', '#F59E0B']);

  // 8. Chart Jenis Kelamin (Pie)
  // 8. Grafik Jenis Kelamin (Pie)
  const labelsJK = (chartData?.chart_jk?.length > 0)
    ? chartData.chart_jk.map(i => i.jenis_kelamin)
    : ['Laki Laki', 'Perempuan'];
  const valuesJK = (chartData?.chart_jk?.length > 0)
    ? chartData.chart_jk.map(i => i.jumlah)
    : [2, 1];
  createChart('chartJK', 'pie', labelsJK, valuesJK, ['#0284C7', '#EC4899']);

  // 9. Grafik Tujuan Pemanfaatan (Bar)
  const labelsPemanfaatan = (chartData?.chart_pemanfaatan?.length > 0)
    ? chartData.chart_pemanfaatan.map(i => i.pemanfaatan)
    : ['Tugas Sekolah/Kuliah', 'Penelitian', 'Pemerintah'];
  const valuesPemanfaatan = (chartData?.chart_pemanfaatan?.length > 0)
    ? chartData.chart_pemanfaatan.map(i => i.jumlah)
    : [1, 1, 1];
  createChart('chartPemanfaatan', 'bar', labelsPemanfaatan, valuesPemanfaatan, '#2563EB');

  // 10. Chart Monev Pembangunan (Pie)
  const labelsMonev = (chartData?.chart_monev?.length > 0)
    ? chartData.chart_monev.map(i => i.monev === 'Ya' ? 'Untuk Perencanaan & Monev' : 'Bukan Untuk Monev')
    : ['Untuk Perencanaan & Monev', 'Bukan Untuk Monev'];
  const valuesMonev = (chartData?.chart_monev?.length > 0)
    ? chartData.chart_monev.map(i => i.jumlah)
    : [2, 1];
  createChart('chartMonev', 'pie', labelsMonev, valuesMonev, ['#059669', '#94A3B8']);

  // 11. Chart Fasilitas Layanan (Doughnut)
  const labelsFasilitas = (chartData?.chart_fasilitas?.length > 0)
    ? chartData.chart_fasilitas.map(i => i.fasilitas)
    : ['Datang Langsung Ke PST BPS Kota Tegal', 'Konsultasi Online'];
  const valuesFasilitas = (chartData?.chart_fasilitas?.length > 0)
    ? chartData.chart_fasilitas.map(i => i.jumlah)
    : [3, 0];
  createChart('chartFasilitas', 'doughnut', labelsFasilitas, valuesFasilitas, ['#0284C7', '#06B6D4']);
}

