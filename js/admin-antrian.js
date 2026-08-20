/**
 * SPST BPS Kota Tegal - Penangan Loket Antrean Admin (Sinkronisasi Otomatis Real-Time)
 */

let fetchedAntrianData = [];
let lastAntrianJson = '';

document.addEventListener('DOMContentLoaded', async () => {
  const user = await checkAuth(['petugas', 'admin', 'kepala']);
  if (!user) return;

  const filterLayananSelect = document.getElementById('filter_layanan_antrian');
  if (filterLayananSelect) {
    if (user.role === 'petugas' && user.layanan_tugas) {
      filterLayananSelect.value = user.layanan_tugas;
      filterLayananSelect.disabled = true;
    } else if (!filterLayananSelect.value) {
      filterLayananSelect.value = 'all';
    }
  }

  const walkinLayananSelect = document.getElementById('walkin_layanan');
  if (walkinLayananSelect && user.layanan_tugas) {
    walkinLayananSelect.value = user.layanan_tugas;
    if (user.role === 'petugas') {
      walkinLayananSelect.disabled = true;
    }
  }

  const filterTanggalSelect = document.getElementById('filter_tanggal_antrian');
  if (filterTanggalSelect) {
    filterTanggalSelect.addEventListener('change', () => renderAntrianDashboard());
  }

  if (filterLayananSelect) {
    filterLayananSelect.addEventListener('change', () => renderAntrianDashboard());
  }

  await renderAntrianDashboard();

  // Pemanggilan Berkelanjutan Real-Time (Sinkronisasi latar belakang setiap 3 detik)
  setInterval(async () => {
    // Jika modal terbuka, jangan ganggu pengetikan aktif atau aksi pengguna
    if (document.querySelector('.modal.show')) return;
    await renderAntrianDashboard(true);
  }, 3000);

  const btnCallNext = document.getElementById('btn_panggil_berikutnya');
  if (btnCallNext) {
    btnCallNext.addEventListener('click', panggilAntrianBerikutnya);
  }

  const btnCallRepeat = document.getElementById('btn_panggil_ulang_aktif');
  if (btnCallRepeat) {
    btnCallRepeat.addEventListener('click', panggilUlangAktif);
  }

  const formWalkin = document.getElementById('formWalkin');
  if (formWalkin) {
    formWalkin.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = document.getElementById('btnSubmitWalkin');
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Menyimpan...';

      const formData = new FormData();
      formData.append('is_walkin', '1');
      formData.append('nama', document.getElementById('walkin_nama').value.trim());
      formData.append('nik', document.getElementById('walkin_nik') ? document.getElementById('walkin_nik').value.trim() : '');
      formData.append('nohp', document.getElementById('walkin_nohp').value.trim());
      formData.append('email', document.getElementById('walkin_email') ? document.getElementById('walkin_email').value.trim() : '');
      formData.append('jenis_kelamin', document.getElementById('walkin_jk').value);
      formData.append('umur', document.getElementById('walkin_umur').value);
      formData.append('pendidikan', document.getElementById('walkin_pendidikan').value);
      formData.append('pekerjaan', document.getElementById('walkin_pekerjaan').value.trim());
      formData.append('instansi', document.getElementById('walkin_instansi').value.trim());
      formData.append('kategori_instansi', document.getElementById('walkin_kategori_instansi') ? document.getElementById('walkin_kategori_instansi').value : 'Sekolah/Universitas');
      formData.append('layanan', document.getElementById('walkin_layanan').value);
      formData.append('pemanfaatan', document.getElementById('walkin_pemanfaatan').value);
      formData.append('monev', document.getElementById('walkin_monev') ? document.getElementById('walkin_monev').value : 'Ya');
      formData.append('data_diinginkan', document.getElementById('walkin_data_diinginkan').value.trim());
      formData.append('csrf_token', getCsrfToken());

      try {
        const res = await fetch('../api.php?action=save_antrian', { method: 'POST', body: formData });
        const json = await res.json();
        if (json.status === 'success') {
          Swal.fire('Berhasil!', `Antrian walk-in ${json.data.nomor} untuk ${json.data.nama} telah didaftarkan.`, 'success');
          formWalkin.reset();
          const modalElem = document.getElementById('modalWalkin');
          const modalBS = bootstrap.Modal.getInstance(modalElem);
          if (modalBS) modalBS.hide();
          await renderAntrianDashboard();
        } else {
          Swal.fire('Gagal', json.message || 'Terjadi kesalahan saat pendaftaran walk-in', 'error');
        }
      } catch (err) {
        Swal.fire('Error', 'Gagal terhubung ke server', 'error');
      } finally {
        btn.disabled = false;
        btn.innerHTML = '<span class="material-icons text-sm">save</span> Simpan & Buat Antrian Walk-In';
      }
    });
  }
});

async function renderAntrianDashboard(isSilent = false) {
  const tableBody = document.getElementById('tbody_antrian_admin');
  const boardActiveNumber = document.getElementById('board_active_number');
  const boardActiveName = document.getElementById('board_active_name');
  const boardActiveService = document.getElementById('board_active_service');

  if (!tableBody) return;

  const filterTanggalSelect = document.getElementById('filter_tanggal_antrian');
  const filterLayananSelect = document.getElementById('filter_layanan_antrian');
  const dateVal = filterTanggalSelect ? filterTanggalSelect.value : 'today';
  const layananVal = filterLayananSelect ? filterLayananSelect.value : 'all';

  let data = [];
  try {
    const res = await fetch(`../api.php?action=get_antrian&tanggal=${encodeURIComponent(dateVal)}&layanan=${encodeURIComponent(layananVal)}&_t=${Date.now()}`, { cache: 'no-store' });
    const json = await res.json();
    if (json.status === 'success') {
      data = json.data || [];
    }
  } catch (err) {
    if (!isSilent) console.error('API get_antrian failed:', err);
    return;
  }

  // Bandingkan Hash JSON untuk menghindari kedipan tampilan (flickering) jika tidak ada perubahan data
  const currentJson = JSON.stringify(data);
  if (isSilent && currentJson === lastAntrianJson) {
    return; 
  }
  lastAntrianJson = currentJson;
  fetchedAntrianData = data;

  tableBody.innerHTML = '';

  const boardNextNumber = document.getElementById('board_next_number');
  const boardNextName = document.getElementById('board_next_name');
  const boardNextService = document.getElementById('board_next_service');
  const boardNextInfo = document.getElementById('board_next_info');
  const boardNextBadge = document.getElementById('board_next_badge');

  // 1. Cari antrean yang sedang dipanggil / aktif
  const activeItem = data.find(i => i.status === 'Dipanggil');
  if (activeItem) {
    if (boardActiveNumber) boardActiveNumber.textContent = activeItem.nomor;
    if (boardActiveName) boardActiveName.textContent = activeItem.nama;
    if (boardActiveService) boardActiveService.textContent = activeItem.layanan;
  } else {
    if (boardActiveNumber) boardActiveNumber.textContent = '---';
    if (boardActiveName) boardActiveName.textContent = 'Belum Ada Panggilan';
    if (boardActiveService) boardActiveService.textContent = '-';
  }

  // 2. Cari antrean berikutnya yang berstatus 'Menunggu' (Akan Dipanggil)
  const nextItem = data.find(i => i.status === 'Menunggu');
  const totalMenunggu = data.filter(i => i.status === 'Menunggu').length;

  if (nextItem) {
    if (boardNextNumber) boardNextNumber.textContent = nextItem.nomor;
    if (boardNextName) boardNextName.textContent = nextItem.nama;
    if (boardNextService) boardNextService.textContent = nextItem.layanan;
    if (boardNextInfo) boardNextInfo.textContent = `${nextItem.tipe_pendaftaran ? nextItem.tipe_pendaftaran.toUpperCase() : 'ONLINE'} • Jam ${nextItem.waktu ? nextItem.waktu.substring(0,5) : ''} WIB`;
    if (boardNextBadge) {
      boardNextBadge.textContent = `Akan Dipanggil (${totalMenunggu} Menunggu)`;
      boardNextBadge.className = 'badge bg-emerald-500 text-white font-bold text-[10px] uppercase px-2.5 py-1 rounded-full shadow-sm animate-pulse';
    }
  } else {
    if (boardNextNumber) boardNextNumber.textContent = '---';
    if (boardNextName) boardNextName.textContent = 'Tidak Ada Antrean Menunggu';
    if (boardNextService) boardNextService.textContent = 'Semua antrean telah dipanggil / selesai';
    if (boardNextInfo) boardNextInfo.textContent = '-';
    if (boardNextBadge) {
      boardNextBadge.textContent = 'Antrean Kosong (0)';
      boardNextBadge.className = 'badge bg-slate-700 text-slate-300 font-bold text-[10px] uppercase px-2.5 py-1 rounded-full shadow-sm';
    }
  }

  if (data.length === 0) {
    const isTodayFilter = dateVal === 'today';
    const filterLabel = dateVal === 'today' ? 'Hari Ini' : (dateVal === 'tomorrow' ? 'Besok' : 'Semua Tanggal');
    tableBody.innerHTML = `
      <tr>
        <td colspan="7" class="text-center py-8 text-slate-500 bg-slate-50/50">
          <div class="max-w-md mx-auto space-y-2">
            <span class="material-icons text-4xl text-sky-500/80">event_note</span>
            <div class="font-extrabold text-slate-800 text-sm">Tidak Ada Antrean Diproses Untuk Filter (${filterLabel})</div>
            <p class="text-xs text-slate-600 leading-relaxed">
              ${isTodayFilter 
                ? 'Pengunjung mungkin melakukan reservasi online untuk <b>tanggal mendatang (Besok atau Tanggal Lain)</b> atau memilih loket layanan yang berbeda.<br>Cobalah ubah filter <b>Tanggal</b> di atas ke <b>"🌐 Semua Tanggal"</b> atau <b>"🗓️ Besok"</b>.' 
                : 'Belum ada data antrean yang terdaftar untuk filter ini.'}
            </p>
          </div>
        </td>
      </tr>`;
    return;
  }

  data.forEach((item, idx) => {
    const tr = document.createElement('tr');
    const isNext = (nextItem && item.id === nextItem.id);
    
    if (isNext) {
      tr.className = 'border-b border-emerald-200 bg-emerald-50/70 hover:bg-emerald-100/80 transition font-medium';
    } else {
      tr.className = 'border-b border-slate-100 hover:bg-slate-50 transition';
    }

    let badgeClass = 'bg-amber-100 text-amber-700';
    if (item.status === 'Dipanggil') badgeClass = 'bg-sky-100 text-sky-700 animate-pulse font-bold';
    if (item.status === 'Dilayani') badgeClass = 'bg-cyan-100 text-cyan-700 font-bold';
    if (item.status === 'Selesai') badgeClass = 'bg-emerald-100 text-emerald-700';
    if (item.status === 'Terlewat') badgeClass = 'bg-rose-100 text-rose-700';
    if (item.status === 'Dibatalkan') badgeClass = 'bg-slate-100 text-slate-500';

    const rowId = item.id;

    tr.innerHTML = `
      <td class="py-3 px-4 font-bold text-xs">${idx + 1}</td>
      <td class="py-3 px-4 font-extrabold text-sky-800 text-sm whitespace-nowrap">${escapeHtml(item.nomor)}</td>
      <td class="py-3 px-4 font-semibold text-slate-800 text-sm">${escapeHtml(item.nama)}</td>
      <td class="py-3 px-4 text-xs font-medium text-slate-600">${escapeHtml(item.layanan)}</td>
      <td class="py-3 px-4 text-xs text-slate-500">${escapeHtml(item.tanggal)} (${escapeHtml(item.waktu)})</td>
      <td class="py-3 px-4">
        <span class="badge ${badgeClass} px-2.5 py-1 rounded-md text-xs">${escapeHtml(item.status)}</span>
        ${isNext ? '<span class="badge bg-emerald-600 text-white px-2 py-1 rounded-md text-[10px] ml-1 font-bold shadow-sm animate-pulse">⏳ Berikutnya</span>' : ''}
      </td>
      <td class="py-3 px-4 flex items-center gap-1">
        <button onclick="showVisitorDetail(${rowId})" class="btn btn-sm btn-outline-primary text-xs py-1 px-2 font-semibold flex items-center gap-1 rounded-lg" title="Lihat Detail">
          <span class="material-icons text-xs">visibility</span> Detail
        </button>
        ${item.status === 'Dipanggil' ? `
          <button onclick="panggilSpesifik(${rowId}, true)" class="btn btn-sm btn-sky bg-sky-600 hover:bg-sky-500 text-white text-xs py-1 px-2 font-bold flex items-center gap-1 rounded-lg" title="Panggil Ulang Antrean Ini">
            <span class="material-icons text-xs">replay</span> Panggil Ulang
          </button>
          <button onclick="updateAntrianStatus(${rowId}, 'Dilayani')" class="btn btn-sm btn-info bg-cyan-600 text-white text-xs py-1 px-2 font-bold flex items-center gap-1 rounded-lg" title="Tandai Sedang Dilayani">
            <span class="material-icons text-xs">support_agent</span> Dilayani
          </button>
          <button onclick="updateAntrianStatus(${rowId}, 'Selesai')" class="btn btn-sm btn-success bg-emerald-600 text-white text-xs py-1 px-2 font-bold flex items-center gap-1 rounded-lg" title="Selesaikan & Catat Data">
            <span class="material-icons text-xs">check_circle</span> Selesai
          </button>
          <button onclick="updateAntrianStatus(${rowId}, 'Terlewat')" class="btn btn-sm btn-secondary text-xs py-1 px-2 font-semibold flex items-center gap-1 rounded-lg" title="Tandai Terlewat">
            <span class="material-icons text-xs">skip_next</span> Lewati
          </button>
        ` : item.status === 'Dilayani' ? `
          <button onclick="updateAntrianStatus(${rowId}, 'Selesai')" class="btn btn-sm btn-success bg-emerald-600 text-white text-xs py-1 px-2 font-bold flex items-center gap-1 rounded-lg" title="Selesaikan & Catat Data">
            <span class="material-icons text-xs">check_circle</span> Selesai
          </button>
          <button onclick="updateAntrianStatus(${rowId}, 'Terlewat')" class="btn btn-sm btn-secondary text-xs py-1 px-2 font-semibold flex items-center gap-1 rounded-lg" title="Tandai Terlewat">
            <span class="material-icons text-xs">skip_next</span> Lewati
          </button>
        ` : item.status === 'Menunggu' ? `
          <button onclick="panggilSpesifik(${rowId}, false)" class="btn btn-sm btn-primary bg-[#003366] text-xs py-1 px-2 font-bold flex items-center gap-1 rounded-lg" title="Panggil Antrean Ini">
            <span class="material-icons text-xs">volume_up</span> Panggil
          </button>
          <button onclick="updateAntrianStatus(${rowId}, 'Terlewat')" class="btn btn-sm btn-secondary text-xs py-1 px-2 font-semibold flex items-center gap-1 rounded-lg" title="Tandai Terlewat">Lewati</button>
        ` : item.status === 'Terlewat' ? `
          <button onclick="panggilSpesifik(${rowId}, true)" class="btn btn-sm btn-sky bg-sky-600 text-white text-xs py-1 px-2 font-bold flex items-center gap-1 rounded-lg" title="Panggil Kembali Antrean Terlewat Ini">
            <span class="material-icons text-xs">replay</span> Panggil Lagi
          </button>
          <button onclick="updateAntrianStatus(${rowId}, 'Dibatalkan')" class="btn btn-sm btn-outline-danger text-xs py-1 px-2 font-semibold flex items-center gap-1 rounded-lg" title="Batalkan Antrean Ini">
            <span class="material-icons text-xs">cancel</span> Batalkan
          </button>
        ` : ''}
      </td>
    `;
    tableBody.appendChild(tr);
  });
}

let pendingAutoCallNext = false;

async function panggilAntrianBerikutnya() {
  const filterLayananSelect = document.getElementById('filter_layanan_antrian');
  const layananVal = filterLayananSelect ? filterLayananSelect.value : 'all';

  // Jika sedang ada antrean aktif yang Dipanggil atau Dilayani pada loket/layanan ini, minta petugas mencatat pelayanan terlebih dahulu
  const activeItem = fetchedAntrianData.find(i => {
    const isServiceMatch = (layananVal === 'all' || (i.layanan && i.layanan.toLowerCase().includes(layananVal.toLowerCase())));
    return isServiceMatch && (i.status === 'Dipanggil' || i.status === 'Dilayani');
  });

  if (activeItem) {
    openModalSelesaiPelayanan(activeItem.id, true);
    return;
  }

  await executePanggilBerikutnya();
}

async function executePanggilBerikutnya() {
  try {
    const filterLayananSelect = document.getElementById('filter_layanan_antrian');
    const layananVal = filterLayananSelect ? filterLayananSelect.value : 'all';

    const formData = new FormData();
    formData.append('layanan', layananVal);
    formData.append('csrf_token', getCsrfToken());

    const res = await fetch('../api.php?action=panggil_antrian', { method: 'POST', body: formData });
    const json = await res.json();
    if (json.status === 'success') {
      await renderAntrianDashboard();
      const activeItem = fetchedAntrianData.find(i => {
        const isServiceMatch = (layananVal === 'all' || (i.layanan && i.layanan.toLowerCase().includes(layananVal.toLowerCase())));
        return isServiceMatch && i.status === 'Dipanggil';
      });
      const calledNumber = activeItem ? activeItem.nomor : '';
      
      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: `Nomor ${calledNumber} Dipanggil!`,
        text: 'Suara bel & panggilan otomatis disiarkan di Layar TV.',
        timer: 3000,
        showConfirmButton: false
      });
    } else {
      Swal.fire('Informasi', json.message || 'Gagal memanggil antrian.', 'info');
    }
  } catch (err) {
    console.error('API panggil_antrian failed:', err);
    Swal.fire('Error', 'Gagal memproses panggilan antrian.', 'error');
  }
}

async function panggilUlangAktif() {
  const filterLayananSelect = document.getElementById('filter_layanan_antrian');
  const layananVal = filterLayananSelect ? filterLayananSelect.value : 'all';

  const activeItem = fetchedAntrianData.find(i => {
    const isServiceMatch = (layananVal === 'all' || (i.layanan && i.layanan.toLowerCase().includes(layananVal.toLowerCase())));
    return isServiceMatch && i.status === 'Dipanggil';
  });

  if (activeItem) {
    await panggilSpesifik(activeItem.id, true);
  } else {
    Swal.fire('Info', 'Tidak ada antrean aktif yang sedang dipanggil pada loket ini.', 'info');
  }
}

async function panggilSpesifik(id, isRepeat = false) {
  try {
    const targetItem = fetchedAntrianData.find(i => i.id == id);
    const formData = new FormData();
    formData.append('id', id);
    if (isRepeat) formData.append('repeat', '1');
    formData.append('csrf_token', getCsrfToken());

    const res = await fetch('../api.php?action=panggil_antrian', { method: 'POST', body: formData });
    const json = await res.json();
    if (json.status === 'success') {
      await renderAntrianDashboard();
      const calledNumber = targetItem ? targetItem.nomor : '';

      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: isRepeat ? `Panggilan Ulang: ${calledNumber}` : `Nomor ${calledNumber} Dipanggil!`,
        text: 'Suara bel & panggilan otomatis disiarkan di Layar TV.',
        timer: 3000,
        showConfirmButton: false
      });
    } else {
      Swal.fire('Gagal', json.message || 'Gagal memanggil antrian.', 'error');
    }
  } catch (err) {
    console.error('API panggilSpesifik failed:', err);
    Swal.fire('Error', 'Gagal memproses panggilan antrian.', 'error');
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const formSelesai = document.getElementById('formSelesaiPelayanan');
  if (formSelesai) {
    formSelesai.addEventListener('submit', async (e) => {
      e.preventDefault();
      const id = document.getElementById('selesai_antrian_id').value;
      const catatanPetugas = document.getElementById('selesai_catatan_petugas').value.trim();
      const btn = document.getElementById('btnConfirmSelesai');
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Menyimpan...';

      try {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('status', 'Selesai');
        formData.append('catatan_petugas', catatanPetugas);
        formData.append('csrf_token', getCsrfToken());

        const res = await fetch('../api.php?action=update_status_antrian', { method: 'POST', body: formData });
        const json = await res.json();
        if (json.status === 'success') {
          const modalElem = document.getElementById('modalSelesaiPelayanan');
          const modalBS = bootstrap.Modal.getInstance(modalElem) || bootstrap.Modal.getOrCreateInstance(modalElem);
          if (modalBS) modalBS.hide();

          Swal.fire({
            icon: 'success',
            title: 'Pelayanan Selesai!',
            text: 'Status antrean diubah menjadi Selesai dan catatan petugas berhasil disimpan.',
            timer: 1500,
            showConfirmButton: false
          });

          if (pendingAutoCallNext) {
            pendingAutoCallNext = false;
            await executePanggilBerikutnya();
          } else {
            await renderAntrianDashboard();
          }
        } else {
          Swal.fire('Gagal', json.message || 'Gagal mengubah status antrian.', 'error');
        }
      } catch (err) {
        Swal.fire('Error', 'Terjadi kesalahan sistem saat menyelesaikan pelayanan.', 'error');
      } finally {
        btn.disabled = false;
        btn.innerHTML = '<span class="material-icons text-sm">check_circle</span> <span>Simpan & Selesaikan Pelayanan</span>';
      }
    });
  }
});

function openModalSelesaiPelayanan(id, autoCallNext = false) {
  const item = fetchedAntrianData.find(d => d.id == id);
  if (!item) return;

  pendingAutoCallNext = autoCallNext;

  document.getElementById('selesai_antrian_id').value = id;
  document.getElementById('selesai_modal_nomor').textContent = item.nomor || '-';
  document.getElementById('selesai_modal_kode').textContent = item.kode_antrian || '-';
  document.getElementById('selesai_modal_nama').textContent = item.nama || 'Pengunjung';
  document.getElementById('selesai_modal_layanan').innerHTML = `<span class="material-icons text-xs text-emerald-600">storefront</span> ${escapeHtml(item.layanan || '-')}`;
  document.getElementById('selesai_modal_data_diinginkan').textContent = item.data_diinginkan || 'Tidak ada catatan data khusus dari pengunjung.';
  document.getElementById('selesai_catatan_petugas').value = item.catatan_petugas || '';

  const noticeElem = document.getElementById('selesai_modal_notice_autocall');
  if (noticeElem) {
    if (autoCallNext) {
      noticeElem.classList.remove('hidden');
    } else {
      noticeElem.classList.add('hidden');
    }
  }

  const modalElem = document.getElementById('modalSelesaiPelayanan');
  const modal = bootstrap.Modal.getOrCreateInstance(modalElem);
  modal.show();
}

function appendQuickTag(tagText) {
  const textarea = document.getElementById('selesai_catatan_petugas');
  if (!textarea) return;
  if (!textarea.value.includes(tagText.trim())) {
    textarea.value = (textarea.value ? textarea.value.trim() + ' ' : '') + tagText;
  }
  textarea.focus();
}

async function updateAntrianStatus(id, newStatus) {
  if (newStatus === 'Selesai') {
    openModalSelesaiPelayanan(id);
    return;
  }

  try {
    const formData = new FormData();
    formData.append('id', id);
    formData.append('status', newStatus);
    formData.append('csrf_token', getCsrfToken());

    const res = await fetch('../api.php?action=update_status_antrian', { method: 'POST', body: formData });
    const json = await res.json();
    if (json.status === 'success') {
      await renderAntrianDashboard();
    } else {
      Swal.fire('Gagal', json.message || 'Gagal mengubah status antrian.', 'error');
    }
  } catch (err) {
    console.error('API updateAntrianStatus failed:', err);
    Swal.fire('Error', 'Terjadi kesalahan sistem saat mengubah status.', 'error');
  }
}

/**
 * Pemanggilan Suara Manusia (Human Voice TTS + Bel Lonceng Chime)
 */
function speakQueueAnnouncement(nomor, nama, layanan) {
  // 1. Bunyikan nada bel lonceng SFX pemanggil
  playBellSound();

  // 2. Generasi Suara Manusia Indonesia via Web Speech Synthesis (Delay 1.2s setelah bel)
  setTimeout(() => {
    if (!('speechSynthesis' in window)) {
      console.warn('Browser ini tidak mendukung fitur pembacaan suara manusia.');
      return;
    }

    // Format nomor antrean agar diucapkan per huruf & kata angka dengan jelas tanpa mengeja "nol" (N-O-L)
    // Contoh: 'KS-01' => 'K S , nol satu'
    const digitWords = {
      '0': 'nol', '1': 'satu', '2': 'dua', '3': 'tiga', '4': 'empat',
      '5': 'lima', '6': 'enam', '7': 'tujuh', '8': 'delapan', '9': 'sembilan'
    };

    let formattedNomor = '';
    if (nomor && nomor.includes('-')) {
      const parts = nomor.split('-');
      const prefixSpoken = parts[0].split('').join(' ');
      const digitsSpoken = parts[1].split('').map(d => digitWords[d] || d).join(' ');
      formattedNomor = `${prefixSpoken} , ${digitsSpoken}`;
    } else {
      formattedNomor = (nomor || '').split('').map(d => digitWords[d] || d).join(' ');
    }

    const namaPengunjung = nama || 'Pengunjung';
    
    // Format nama layanan agar diucapkan sesuai nama loket layanan yang dipilih (misal: "Loket Konsultasi Statistik")
    let namaLayanan = layanan ? layanan.replace(/&/g, 'dan').trim() : 'Pelayanan Statistik Terpadu';
    if (!namaLayanan.toLowerCase().startsWith('loket')) {
      namaLayanan = 'Loket ' + namaLayanan;
    }

    const textToSpeak = `Nomor antrean ${formattedNomor}, atas nama ${namaPengunjung}, silakan menuju ${namaLayanan}.`;

    window.speechSynthesis.cancel(); // Hentikan panggilan sebelumnya jika ada
    const utterance = new SpeechSynthesisUtterance(textToSpeak);
    utterance.lang = 'id-ID';
    utterance.rate = 0.88; // Kecepatan jelas & tidak terburu-buru
    utterance.pitch = 1.0;

    // Pilih suara Bahasa Indonesia jika ada pada sistem browser
    const voices = window.speechSynthesis.getVoices();
    const idVoice = voices.find(v => v.lang.includes('id') || v.lang.includes('ID'));
    if (idVoice) utterance.voice = idVoice;

    window.speechSynthesis.speak(utterance);
  }, 1200);
}

function playBellSound() {
  try {
    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    const osc = audioCtx.createOscillator();
    const gain = audioCtx.createGain();
    osc.type = 'sine';
    osc.frequency.setValueAtTime(587.33, audioCtx.currentTime); // D5
    gain.gain.setValueAtTime(0.3, audioCtx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.00001, audioCtx.currentTime + 1.2);
    osc.connect(gain);
    gain.connect(audioCtx.destination);
    osc.start();
    osc.stop(audioCtx.currentTime + 1.2);
  } catch(e) {
    console.log("Audio not supported", e);
  }
}

function showVisitorDetail(id) {
  const item = fetchedAntrianData.find(d => d.id == id);
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
      <div><b>Kode Tiket / BT:</b> ${escapeHtml(item.kode_antrian || item.nomor || '-')}</div>
      <div><b>Nomor Antrian:</b> <span class="font-extrabold text-sky-700">${escapeHtml(item.nomor || '-')}</span></div>
      <div><b>Nama Lengkap:</b> ${escapeHtml(item.nama)}</div>
      <div><b>NIK (KTP):</b> <span class="font-mono text-slate-900">${escapeHtml(item.nik || '-')}</span></div>
      <div><b>Jenis Kelamin / Usia:</b> ${escapeHtml(item.jenis_kelamin || '-')} (${escapeHtml(item.umur || '-')})</div>
      <div><b>Nomor HP / WhatsApp:</b> ${escapeHtml(item.nohp || '-')}</div>
      <div><b>Alamat Email:</b> ${escapeHtml(item.email || '-')}</div>
      <div><b>Pendidikan Terakhir:</b> ${escapeHtml(item.pendidikan || '-')}</div>
      <div><b>Pekerjaan:</b> ${escapeHtml(item.pekerjaan || '-')}</div>
      <div><b>Nama Instansi:</b> ${escapeHtml(item.instansi || '-')}</div>
      <div><b>Kategori Instansi:</b> ${escapeHtml(item.kategori_instansi || '-')}</div>
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

    <div class="bg-emerald-50 p-4 rounded-xl border border-emerald-200 space-y-2">
      <div class="flex items-center justify-between font-bold text-emerald-900">
        <span class="flex items-center gap-1.5"><span class="material-icons text-sm text-emerald-600">assignment_turned_in</span> Catatan Petugas (Pelayanan & Data Diberikan):</span>
        <button type="button" onclick="openModalSelesaiPelayanan(${item.id})" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[11px] font-bold transition flex items-center gap-1 shadow-sm">
          <span class="material-icons text-xs">edit</span> Edit Catatan
        </button>
      </div>
      <p class="text-slate-800 font-medium leading-relaxed bg-white/80 p-3 rounded-lg border border-emerald-200">${escapeHtml(item.catatan_petugas || 'Belum ada catatan rincian pelayanan dari petugas.')}</p>
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
