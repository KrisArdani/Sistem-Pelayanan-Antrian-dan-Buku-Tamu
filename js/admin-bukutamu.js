/**
 * TOBASA BPS Kota Tegal - Penangan Tabel & Ekspor Buku Tamu Admin (Sinkronisasi Otomatis Real-Time)
 */

let fetchedBukuTamuData = [];
let lastBukuTamuJson = '';

let activeTypeFilter = 'all';
let activeStatusFilter = 'all';

document.addEventListener('DOMContentLoaded', async () => {
  // Periksa autentikasi
  const isAuth = await checkAuth(['petugas', 'admin', 'kepala']);
  if (!isAuth) return;

  await renderBukuTamuTable();

  // Input pencarian
  const searchInput = document.getElementById('search_bukutamu');
  if (searchInput) {
    searchInput.addEventListener('input', () => renderBukuTamuTable());
  }

  // Dropdown filter
  const selectInstansi = document.getElementById('filter_kategori_instansi');
  if (selectInstansi) {
    selectInstansi.addEventListener('change', () => renderBukuTamuTable());
  }

  const selectLayanan = document.getElementById('filter_kategori_layanan');
  if (selectLayanan) {
    selectLayanan.addEventListener('change', () => renderBukuTamuTable());
  }

  // Listener Filter Waktu / Tanggal
  const filterWaktu = document.getElementById('filter_waktu');
  const customDateBox = document.getElementById('custom_date_range_box');
  const btnApplyDate = document.getElementById('btn_apply_date_filter');
  const btnResetDate = document.getElementById('btn_reset_date_filter');
  const inputDateMulai = document.getElementById('filter_tanggal_mulai');
  const inputDateSelesai = document.getElementById('filter_tanggal_selesai');

  if (filterWaktu) {
    filterWaktu.addEventListener('change', () => {
      if (filterWaktu.value === 'custom') {
        if (customDateBox) customDateBox.classList.remove('hidden'), customDateBox.classList.add('flex');
      } else {
        if (customDateBox) customDateBox.classList.add('hidden'), customDateBox.classList.remove('flex');
        renderBukuTamuTable();
      }
    });
  }

  if (btnApplyDate) {
    btnApplyDate.addEventListener('click', () => renderBukuTamuTable());
  }

  if (btnResetDate) {
    btnResetDate.addEventListener('click', () => {
      if (filterWaktu) filterWaktu.value = 'all';
      if (inputDateMulai) inputDateMulai.value = '';
      if (inputDateSelesai) inputDateSelesai.value = '';
      if (customDateBox) customDateBox.classList.add('hidden'), customDateBox.classList.remove('flex');
      renderBukuTamuTable();
    });
  }

  // Listener Tab Tipe
  document.querySelectorAll('.filter-type-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.filter-type-btn').forEach(b => {
        b.classList.remove('btn-primary', 'bg-sky-600', 'border-sky-600', 'active');
        b.classList.add('btn-light', 'border');
      });
      btn.classList.remove('btn-light', 'border');
      btn.classList.add('btn-primary', 'bg-sky-600', 'border-sky-600', 'active');

      activeTypeFilter = btn.getAttribute('data-type');
      renderBukuTamuTable();
    });
  });

  // Listener Tab Status
  document.querySelectorAll('.filter-status-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.filter-status-btn').forEach(b => {
        b.classList.remove('btn-primary', 'bg-sky-600', 'border-sky-600', 'active');
        b.classList.add('btn-light', 'border');
      });
      btn.classList.remove('btn-light', 'border');
      btn.classList.add('btn-primary', 'bg-sky-600', 'border-sky-600', 'active');

      activeStatusFilter = btn.getAttribute('data-status');
      renderBukuTamuTable();
    });
  });

  // Pemanggilan Berkelanjutan Real-Time (Sinkronisasi latar belakang setiap 3 detik)
  setInterval(async () => {
    // Jika modal terbuka, jangan ganggu pengetikan aktif atau aksi pengguna
    if (document.querySelector('.modal.show')) return;
    await renderBukuTamuTable(true);
  }, 3000);

  // Ekspor CSV
  const btnExport = document.getElementById('btn_export_csv');
  if (btnExport) {
    btnExport.addEventListener('click', exportToCSV);
  }
});

async function renderBukuTamuTable(isSilent = false) {
  const tableBody = document.getElementById('tbody_bukutamu');
  if (!tableBody) return;

  const searchInput = document.getElementById('search_bukutamu');
  const selectInstansi = document.getElementById('filter_kategori_instansi');
  const selectLayanan = document.getElementById('filter_kategori_layanan');

  const filterQuery = searchInput ? searchInput.value.trim() : '';
  const katInstansiVal = selectInstansi ? selectInstansi.value : '';
  const katLayananVal = selectLayanan ? selectLayanan.value : '';

  let data = [];
  try {
    const res = await fetch(`../api.php?action=get_bukutamu&search=${encodeURIComponent(filterQuery)}`);
    const json = await res.json();
    if (json.status === 'success') {
      data = json.data || [];
    } else {
      if (!isSilent) console.error("API error:", json.message);
    }
  } catch (err) {
    if (!isSilent) console.error('API get_bukutamu failed:', err);
    return;
  }

  // Filter Kategori Instansi
  if (katInstansiVal) {
    data = data.filter(item => item.kategori_instansi === katInstansiVal);
  }

  // Filter Kategori Layanan
  if (katLayananVal) {
    data = data.filter(item => item.layanan && item.layanan.includes(katLayananVal));
  }

  // Filter Tipe Pendaftaran
  if (activeTypeFilter !== 'all') {
    data = data.filter(item => item.tipe_pendaftaran === activeTypeFilter);
  }

  // Filter Status
  if (activeStatusFilter !== 'all') {
    data = data.filter(item => item.status === activeStatusFilter);
  }

  // Filter Berdasarkan Waktu / Tanggal
  const filterWaktuSelect = document.getElementById('filter_waktu');
  const waktuVal = filterWaktuSelect ? filterWaktuSelect.value : 'all';
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  const todayStr = `${year}-${month}-${day}`;
  const thisMonthStr = `${year}-${month}`;

  if (waktuVal === 'today') {
    data = data.filter(item => {
      const itemDate = item.tanggal || (item.timestamp ? item.timestamp.substring(0, 10) : '') || (item.created_at ? item.created_at.substring(0, 10) : '');
      return itemDate === todayStr;
    });
  } else if (waktuVal === 'this_month') {
    data = data.filter(item => {
      const itemDate = item.tanggal || (item.timestamp ? item.timestamp.substring(0, 10) : '') || (item.created_at ? item.created_at.substring(0, 10) : '');
      return itemDate.startsWith(thisMonthStr);
    });
  } else if (waktuVal === 'custom') {
    const inputDateMulai = document.getElementById('filter_tanggal_mulai');
    const inputDateSelesai = document.getElementById('filter_tanggal_selesai');
    const startDate = inputDateMulai ? inputDateMulai.value : '';
    const endDate = inputDateSelesai ? inputDateSelesai.value : '';

    if (startDate) {
      data = data.filter(item => {
        const itemDate = item.tanggal || (item.timestamp ? item.timestamp.substring(0, 10) : '') || (item.created_at ? item.created_at.substring(0, 10) : '');
        return itemDate >= startDate;
      });
    }
    if (endDate) {
      data = data.filter(item => {
        const itemDate = item.tanggal || (item.timestamp ? item.timestamp.substring(0, 10) : '') || (item.created_at ? item.created_at.substring(0, 10) : '');
        return itemDate <= endDate;
      });
    }
  }

  // Bandingkan Hash JSON untuk menghindari kedipan tampilan jika tidak ada perubahan data
  const currentJson = JSON.stringify(data);
  if (isSilent && currentJson === lastBukuTamuJson) {
    return; // Data tidak berubah, tidak ada kedipan DOM!
  }
  lastBukuTamuJson = currentJson;
  fetchedBukuTamuData = data;

  tableBody.innerHTML = '';

  if (!data || data.length === 0) {
    tableBody.innerHTML = `<tr><td colspan="8" class="text-center py-8 text-slate-400">Tidak ada data pengunjung ditemukan untuk kategori ini.</td></tr>`;
    return;
  }

  data.forEach((item, idx) => {
    const tr = document.createElement('tr');
    tr.className = 'border-b border-slate-100 hover:bg-slate-50 transition';

    let statusBadge = '';
    if (item.status === 'Selesai' || item.status === 'Terverifikasi') {
      statusBadge = `<span class="badge bg-emerald-100 text-emerald-800 font-bold px-2.5 py-1 rounded-full text-[11px]">✅ Selesai</span>`;
    } else if (item.status === 'Dilayani') {
      statusBadge = `<span class="badge bg-sky-100 text-sky-800 font-bold px-2.5 py-1 rounded-full text-[11px]">🗣️ Dilayani</span>`;
    } else if (item.status === 'Menunggu') {
      statusBadge = `<span class="badge bg-amber-100 text-amber-800 font-bold px-2.5 py-1 rounded-full text-[11px]">⏳ Menunggu</span>`;
    } else if (item.status === 'Terlewat') {
      statusBadge = `<span class="badge bg-rose-100 text-rose-800 font-bold px-2.5 py-1 rounded-full text-[11px]">⏭️ Terlewat</span>`;
    } else {
      statusBadge = `<span class="badge bg-slate-100 text-slate-600 font-bold px-2.5 py-1 rounded-full text-[11px]">❌ Dibatalkan</span>`;
    }

    const typeBadge = item.tipe_pendaftaran === 'walkin'
      ? `<span class="badge bg-purple-100 text-purple-800 border border-purple-200 font-semibold px-2 py-0.5 rounded text-[10px]">Walk-In</span>`
      : `<span class="badge bg-blue-100 text-blue-800 border border-blue-200 font-semibold px-2 py-0.5 rounded text-[10px]">Online</span>`;

    // Gambar avatar foto jika tersedia
    let avatarHtml = `<div class="w-7 h-7 rounded-full bg-slate-200 text-slate-500 flex items-center justify-center shrink-0 font-bold text-xs border"><span class="material-icons text-sm">person</span></div>`;
    if (item.foto && item.foto.length > 50) {
      avatarHtml = `<img src="${item.foto}" class="w-7 h-7 rounded-full object-cover shrink-0 border border-sky-300 shadow-sm" alt="Foto">`;
    }

    const rowId = item.id;
    const queueNo = item.nomor || ('BT-' + String(item.id).padStart(2, '0'));
    const bookingCode = item.kode_bt || item.kode_antrian || ('ANT-' + item.id);
    const katInstansiBadge = item.kategori_instansi
      ? `<span class="badge bg-slate-100 text-slate-700 border font-semibold text-[10px] whitespace-nowrap ml-1">${escapeHtml(item.kategori_instansi)}</span>`
      : '';

    tr.innerHTML = `
      <td class="py-2.5 px-3 font-bold text-xs text-slate-500 text-center">${idx + 1}</td>
      <td class="py-2.5 px-3 whitespace-nowrap">
        <div class="flex flex-col gap-0.5 items-start">
          <span class="font-extrabold text-sky-900 bg-sky-100 px-2 py-0.5 rounded text-xs border border-sky-300/60 whitespace-nowrap inline-block">${escapeHtml(queueNo)}</span>
          <span class="text-[10px] font-mono text-slate-400 font-semibold tracking-wider whitespace-nowrap inline-block">${escapeHtml(bookingCode)}</span>
        </div>
      </td>
      <td class="py-2.5 px-3">
        <div class="flex items-center gap-2">
          ${avatarHtml}
          <span class="font-bold text-slate-800 text-xs">${escapeHtml(item.nama)}</span>
        </div>
      </td>
      <td class="py-2.5 px-3 text-xs font-medium text-slate-700">
        <div>${escapeHtml(item.instansi || '-')}${katInstansiBadge}</div>
        <div class="text-[11px] text-slate-400">${escapeHtml(item.pekerjaan || '-')}</div>
      </td>
      <td class="py-2.5 px-3 text-xs font-semibold text-slate-800">
        ${escapeHtml(item.layanan || '-')}
      </td>
      <td class="py-2.5 px-3 text-xs text-slate-600 font-medium whitespace-nowrap">
        ${escapeHtml(formatTanggalIndo(item.timestamp || item.created_at))}
      </td>
      <td class="py-2.5 px-3">
        <div class="flex items-center gap-1.5">
          ${typeBadge}
          ${statusBadge}
        </div>
      </td>
      <td class="py-2.5 px-3 text-center">
        <div class="flex items-center justify-center gap-1.5">
          <button onclick="showVisitorDetail(${rowId})" class="btn btn-sm btn-outline-primary text-xs py-1 px-2.5 font-semibold flex items-center gap-1 rounded-lg" title="Lihat Detail">
            <span class="material-icons text-xs">visibility</span> Detail
          </button>
          ${(item.status === 'Menunggu' || item.status === 'Dilayani' || item.status === 'Dipanggil') ? `
            <button onclick="verifyVisitor(${rowId}, 'Selesai')" class="btn btn-sm btn-success text-xs py-1 px-2.5 font-bold flex items-center gap-1 rounded-lg" title="Tandai Selesai">
              <span class="material-icons text-xs">check_circle</span> Selesai
            </button>
            <button onclick="verifyVisitor(${rowId}, 'Dibatalkan')" class="btn btn-sm btn-outline-danger text-xs py-1 px-2 font-semibold flex items-center gap-1 rounded-lg" title="Batalkan">
              <span class="material-icons text-xs">cancel</span> Batal
            </button>
          ` : item.status === 'Terlewat' ? `
            <button onclick="verifyVisitor(${rowId}, 'Dilayani')" class="btn btn-sm btn-sky bg-sky-600 text-white text-xs py-1 px-2.5 font-bold flex items-center gap-1 rounded-lg" title="Layani Ulang">
              <span class="material-icons text-xs">replay</span> Layani Ulang
            </button>
            <button onclick="verifyVisitor(${rowId}, 'Dibatalkan')" class="btn btn-sm btn-outline-danger text-xs py-1 px-2 font-semibold flex items-center gap-1 rounded-lg" title="Batalkan">
              <span class="material-icons text-xs">cancel</span> Batal
            </button>
          ` : item.status === 'Dibatalkan' ? `
            <button onclick="verifyVisitor(${rowId}, 'Menunggu')" class="btn btn-sm btn-outline-secondary text-xs py-1 px-2 font-semibold flex items-center gap-1 rounded-lg" title="Pulihkan Antrean ke Status Menunggu">
              <span class="material-icons text-xs">restore</span> Pulihkan
            </button>
          ` : ''}
        </div>
      </td>
    `;
    tableBody.appendChild(tr);
  });
}

function showVisitorDetail(id) {
  const item = fetchedBukuTamuData.find(d => d.id == id);
  if (!item) return;

  const content = document.getElementById('detail_visitor_content');
  if (!content) return;

  let photoBlock = '';
  if (item.foto && item.foto.length > 50) {
    photoBlock = `
      <div class="text-center p-3 bg-slate-900 rounded-xl">
        <div class="text-[11px] font-bold text-slate-400 mb-2 uppercase tracking-wider">Foto Swafoto Kunjungan</div>
        <img src="${item.foto}" class="max-h-48 mx-auto rounded-lg border border-slate-700 object-contain shadow-lg" alt="Swafoto">
      </div>
    `;
  }

  content.innerHTML = `
    ${photoBlock}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl border">
      <div><b>Kode Tiket / BT:</b> ${escapeHtml(item.kode_bt || item.nomor || '-')}</div>
      <div><b>Nomor Antrian:</b> <span class="font-extrabold text-sky-700">${escapeHtml(item.nomor || '-')}</span></div>
      <div><b>Nama Lengkap:</b> ${escapeHtml(item.nama)}</div>
      <div><b>Jenis Kelamin / Usia:</b> ${escapeHtml(item.jenis_kelamin || '-')} (${escapeHtml(item.umur || '-')})</div>
      <div><b>Nomor HP / WhatsApp:</b> ${escapeHtml(item.nohp || '-')}</div>
      <div><b>Alamat Email:</b> ${escapeHtml(item.email || '-')}</div>
      <div><b>Pendidikan Terakhir:</b> ${escapeHtml(item.pendidikan || '-')}</div>
      <div><b>Pekerjaan:</b> ${escapeHtml(item.pekerjaan || '-')}</div>
      <div><b>Nama Instansi:</b> ${escapeHtml(item.instansi || '-')}</div>
      <div><b>Kategori Instansi:</b> <span class="font-bold text-sky-700">${escapeHtml(item.kategori_instansi || '-')}</span></div>
      <div><b>Jenis Layanan PST:</b> <span class="font-bold text-slate-900">${escapeHtml(item.layanan || '-')}</span></div>
      <div><b>Fasilitas Layanan:</b> ${escapeHtml(item.fasilitas || '-')}</div>
      <div><b>Tujuan Pemanfaatan:</b> ${escapeHtml(item.pemanfaatan || '-')}</div>
      <div><b>Digunakan untuk Monev:</b> <span class="font-bold text-emerald-700">${escapeHtml(item.monev || 'Ya')}</span></div>
      <div><b>Tipe Pendaftaran:</b> <span class="uppercase font-bold">${escapeHtml(item.tipe_pendaftaran || 'online')}</span></div>
      <div><b>Status Layanan:</b> <span class="font-bold text-sky-700">${escapeHtml(item.status || 'Menunggu')}</span></div>
    </div>

    <div class="bg-amber-50 p-4 rounded-xl border border-amber-200 space-y-1">
      <div class="font-bold text-amber-900">Rincian Data / Informasi yang Dicari:</div>
      <p class="text-slate-700">${escapeHtml(item.data_diinginkan || 'Tidak ada catatan rincian data khusus.')}</p>
    </div>

    ${item.pendapat ? `
      <div class="bg-sky-50 p-4 rounded-xl border border-sky-200 space-y-1">
        <div class="font-bold text-sky-900">Ulasan Kepuasan Layanan (SKM): <span class="text-sky-700 font-extrabold">${escapeHtml(item.pendapat)}</span></div>
        <p class="text-slate-700">${escapeHtml(item.catatan || 'Tidak ada catatan ulasan tambahan.')}</p>
      </div>
    ` : `
      <div class="bg-slate-100 p-3 rounded-xl border border-slate-200 text-xs text-slate-500 italic flex items-center gap-1.5">
        <span class="material-icons text-sm text-slate-400">info</span>
        <span>Pengunjung belum memberikan penilaian ulasan kepuasan (SKM).</span>
      </div>
    `}
  `;

  const modal = new bootstrap.Modal(document.getElementById('modalVisitorDetail'));
  modal.show();
}

async function verifyVisitor(id, newStatus = 'Selesai') {
  try {
    const formData = new FormData();
    formData.append('id', id);
    formData.append('status', newStatus);
    formData.append('csrf_token', getCsrfToken());

    const res = await fetch('../api.php?action=verify_bukutamu', { method: 'POST', body: formData });
    const json = await res.json();
    if (json.status === 'success') {
      Swal.fire({
        icon: 'success',
        title: 'Status Diperbarui',
        text: json.message,
        confirmButtonColor: '#0284c7'
      }).then(() => {
        renderBukuTamuTable();
      });
    } else {
      Swal.fire('Gagal', json.message || 'Gagal memperbarui status.', 'error');
    }
  } catch (err) {
    Swal.fire('Error', 'Gagal terhubung ke server.', 'error');
  }
}

function exportToCSV() {
  if (!fetchedBukuTamuData || fetchedBukuTamuData.length === 0) {
    Swal.fire('Info', 'Tidak ada data untuk diexport.', 'info');
    return;
  }

  const headers = [
    "No", "Kode Tiket/BT", "Nomor Antrian", "Nama Pengunjung", "Jenis Kelamin",
    "Usia", "No HP", "Email", "Pendidikan", "Pekerjaan", "Instansi",
    "Kategori Instansi", "Fasilitas", "Layanan PST", "Tujuan Pemanfaatan",
    "Data Diinginkan", "Monev", "Ulasan SKM", "Catatan SKM", "Tipe Pendaftaran", "Status", "Tanggal & Waktu"
  ];

  const rows = fetchedBukuTamuData.map((item, idx) => [
    idx + 1,
    `"${item.kode_bt || item.kode_antrian || ''}"`,
    `"${item.nomor || ''}"`,
    `"${(item.nama || '').replace(/"/g, '""')}"`,
    `"${item.jenis_kelamin || ''}"`,
    `"${item.umur || ''}"`,
    `"${item.nohp || ''}"`,
    `"${item.email || ''}"`,
    `"${item.pendidikan || ''}"`,
    `"${item.pekerjaan || ''}"`,
    `"${(item.instansi || '').replace(/"/g, '""')}"`,
    `"${item.kategori_instansi || ''}"`,
    `"${(item.fasilitas || '').replace(/"/g, '""')}"`,
    `"${(item.layanan || '').replace(/"/g, '""')}"`,
    `"${(item.pemanfaatan || '').replace(/"/g, '""')}"`,
    `"${(item.data_diinginkan || '').replace(/"/g, '""')}"`,
    `"${item.monev || 'Ya'}"`,
    `"${item.pendapat || ''}"`,
    `"${(item.catatan || '').replace(/"/g, '""')}"`,
    `"${item.tipe_pendaftaran || 'online'}"`,
    `"${item.status || 'Menunggu'}"`,
    `"${item.timestamp || item.created_at || ''}"`
  ]);

  let csvContent = "data:text/csv;charset=utf-8,\uFEFF"
    + [headers.join(","), ...rows.map(e => e.join(","))].join("\n");

  const encodedUri = encodeURI(csvContent);
  const link = document.getElementById("a");
  const a = document.createElement("a");
  a.setAttribute("href", encodedUri);
  a.setAttribute("download", `Buku_Tamu_PST_BPS_Kota_Tegal_${new Date().toISOString().slice(0,10)}.csv`);
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
}
