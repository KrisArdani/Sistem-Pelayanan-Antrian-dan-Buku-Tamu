// SPST BPS Kota Tegal - JavaScript Buku Tamu & Riwayat Kunjungan

document.addEventListener('DOMContentLoaded', () => {
  let allTickets = [];
  let currentFilter = 'all';

  const container = document.getElementById('tickets-container');
  const searchInput = document.getElementById('search_ticket');

  async function loadTickets() {
    try {
      const res = await fetch('api.php?action=get_my_antrian');
      const json = await res.json();
      if (json.status === 'success') {
        allTickets = json.data || [];
        renderTickets();
      } else {
        container.innerHTML = `
          <div class="p-8 bg-white rounded-2xl text-center space-y-2 border border-slate-200">
            <span class="material-icons text-amber-500 text-3xl">info</span>
            <p class="text-xs text-slate-600 font-semibold">${json.message || 'Gagal memuat data antrean.'}</p>
          </div>
        `;
      }
    } catch (err) {
      container.innerHTML = `
        <div class="p-8 bg-white rounded-2xl text-center space-y-2 border border-slate-200">
          <span class="material-icons text-red-500 text-3xl">wifi_off</span>
          <p class="text-xs text-slate-600 font-semibold">Gagal terhubung ke server.</p>
        </div>
      `;
    }
  }

  function renderTickets() {
    let filtered = allTickets;

    // Filter Status
    if (currentFilter !== 'all') {
      filtered = filtered.filter(t => t.status === currentFilter);
    }

    // Filter Berdasarkan Waktu / Tanggal
    const userWaktuSelect = document.getElementById('user_filter_waktu');
    const waktuVal = userWaktuSelect ? userWaktuSelect.value : 'all';
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const todayStr = `${year}-${month}-${day}`;
    const thisMonthStr = `${year}-${month}`;

    if (waktuVal === 'today') {
      filtered = filtered.filter(t => t.tanggal && t.tanggal === todayStr);
    } else if (waktuVal === 'this_week') {
      const dayOfWeek = now.getDay();
      const diffToMon = now.getDate() - dayOfWeek + (dayOfWeek === 0 ? -6 : 1);
      const monDate = new Date(now.getFullYear(), now.getMonth(), diffToMon);
      const sunDate = new Date(monDate);
      sunDate.setDate(monDate.getDate() + 6);
      
      const monStr = `${monDate.getFullYear()}-${String(monDate.getMonth() + 1).padStart(2, '0')}-${String(monDate.getDate()).padStart(2, '0')}`;
      const sunStr = `${sunDate.getFullYear()}-${String(sunDate.getMonth() + 1).padStart(2, '0')}-${String(sunDate.getDate()).padStart(2, '0')}`;
      
      filtered = filtered.filter(t => t.tanggal && t.tanggal >= monStr && t.tanggal <= sunStr);
    } else if (waktuVal === 'this_month') {
      filtered = filtered.filter(t => t.tanggal && t.tanggal.startsWith(thisMonthStr));
    } else if (waktuVal === 'custom') {
      const userDateMulai = document.getElementById('user_date_mulai');
      const userDateSelesai = document.getElementById('user_date_selesai');
      const startDate = userDateMulai ? userDateMulai.value : '';
      const endDate = userDateSelesai ? userDateSelesai.value : '';

      if (startDate) {
        filtered = filtered.filter(t => t.tanggal && t.tanggal >= startDate);
      }
      if (endDate) {
        filtered = filtered.filter(t => t.tanggal && t.tanggal <= endDate);
      }
    }

    // Search Query
    const q = searchInput ? searchInput.value.toLowerCase().trim() : '';
    if (q) {
      filtered = filtered.filter(t => 
        (t.nomor && t.nomor.toLowerCase().includes(q)) ||
        (t.kode_antrian && t.kode_antrian.toLowerCase().includes(q)) ||
        (t.layanan && t.layanan.toLowerCase().includes(q)) ||
        (t.data_diinginkan && t.data_diinginkan.toLowerCase().includes(q))
      );
    }

    if (filtered.length === 0) {
      container.innerHTML = `
        <div class="p-10 bg-white rounded-2xl text-center space-y-3 border border-slate-200">
          <span class="material-icons text-slate-300 text-4xl">inbox</span>
          <h4 class="text-sm font-bold text-slate-700">Belum Ada Tiket Kunjungan</h4>
          <p class="text-xs text-slate-400 max-w-sm mx-auto">Anda belum memiliki reservasi antrean dengan status ini. Silakan buat reservasi baru.</p>
          <a href="antrian.php" class="btn btn-sky btn-sm bg-sky-600 text-white font-bold text-xs px-4 py-2 rounded-xl border-none">Buat Reservasi Baru</a>
        </div>
      `;
      return;
    }

    container.innerHTML = filtered.map((t, idx) => {
      let statusBadge = '';
      if (t.status === 'Menunggu') {
        statusBadge = '<span class="badge bg-amber-100 text-amber-800 border border-amber-300 px-3 py-1 text-[11px] font-bold rounded-full">⏳ Menunggu</span>';
      } else if (t.status === 'Dipanggil') {
        statusBadge = '<span class="badge bg-amber-500 text-slate-950 border border-amber-400 px-3 py-1 text-[11px] font-extrabold rounded-full animate-bounce shadow-sm">📢 Dipanggil di Loket</span>';
      } else if (t.status === 'Dilayani') {
        statusBadge = '<span class="badge bg-sky-100 text-sky-800 border border-sky-300 px-3 py-1 text-[11px] font-bold rounded-full">🗣️ Sedang Dilayani</span>';
      } else if (t.status === 'Selesai') {
        statusBadge = '<span class="badge bg-emerald-100 text-emerald-800 border border-emerald-300 px-3 py-1 text-[11px] font-bold rounded-full">✅ Selesai</span>';
      } else if (t.status === 'Terlewat') {
        statusBadge = '<span class="badge bg-rose-100 text-rose-800 border border-rose-300 px-3 py-1 text-[11px] font-bold rounded-full">⏭️ Terlewat</span>';
      } else {
        statusBadge = '<span class="badge bg-slate-100 text-slate-600 border border-slate-300 px-3 py-1 text-[11px] font-bold rounded-full">❌ Dibatalkan</span>';
      }

      const formattedDate = formatTanggalIndo(t.tanggal + ' ' + t.waktu);

      // Card Live Estimasi Antrean (jika antrean aktif/menunggu hari ini)
      let queueEstimationCard = '';
      if (t.antrian_di_depan !== null && t.antrian_di_depan !== undefined) {
        const isSelfActive = t.status === 'Dipanggil' || t.status === 'Dilayani';
        const isNext = t.antrian_di_depan === 0;

        queueEstimationCard = `
          <div class="p-4 bg-gradient-to-r from-[#002B5B] via-[#003366] to-[#0284c7] rounded-xl text-white space-y-3 shadow-md border border-sky-400/30">
            <div class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-sky-200 border-b border-white/10 pb-2">
              <span class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full ${isSelfActive ? 'bg-emerald-400 animate-ping' : 'bg-amber-400 animate-pulse'}"></span>
                <span>${isSelfActive ? 'Status: Sedang Dilayani Di Meja Loket' : 'Status Antrean & Estimasi Waktu Tunggu'}</span>
              </span>
              <span class="text-[10px] bg-white/15 px-2 py-0.5 rounded text-sky-100 font-mono">Live Sync</span>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 text-center">
              <div class="p-2.5 bg-white/10 rounded-lg backdrop-blur-sm border border-white/10">
                <div class="text-[10px] text-sky-200 uppercase font-semibold">Sedang Dipanggil</div>
                <div class="text-base font-black text-amber-300 brand-font mt-0.5">${t.antrean_aktif_saat_ini && t.antrean_aktif_saat_ini !== '-' ? t.antrean_aktif_saat_ini : 'Belum Ada'}</div>
              </div>

              <div class="p-2.5 bg-white/10 rounded-lg backdrop-blur-sm border border-white/10">
                <div class="text-[10px] text-sky-200 uppercase font-semibold">Antrean Di Depan Anda</div>
                <div class="text-base font-black ${isNext ? 'text-emerald-300 animate-bounce' : 'text-white'} brand-font mt-0.5">
                  ${isSelfActive ? '0 (Giliran Anda)' : (isNext ? '🎉 Giliran Anda!' : t.antrian_di_depan + ' Antrean')}
                </div>
              </div>

              <div class="p-2.5 bg-white/10 rounded-lg backdrop-blur-sm border border-white/10">
                <div class="text-[10px] text-sky-200 uppercase font-semibold">Estimasi Waktu Tunggu</div>
                <div class="text-xs font-extrabold ${isNext || isSelfActive ? 'text-emerald-300' : 'text-sky-100'} mt-1">
                  ${t.estimasi_waktu || '-'}
                </div>
              </div>
            </div>
          </div>
        `;
      }

      return `
        <div class="p-5 md:p-6 bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b pb-4 border-slate-100">
            <div class="flex items-center gap-3.5">
              <div class="px-4 py-2.5 bg-gradient-to-br from-[#002B5B] via-[#003366] to-[#0284c7] text-white rounded-2xl border border-sky-400/30 shadow-md flex flex-col items-center justify-center shrink-0 min-w-[95px] relative overflow-hidden">
                <span class="text-[9px] font-black tracking-widest uppercase text-sky-200 leading-none mb-1 opacity-90">NO. ANTREAN</span>
                <span class="text-xl md:text-2xl font-black brand-font text-white tracking-wider leading-none drop-shadow">${escapeHtml(t.nomor)}</span>
              </div>
              <div>
                <div class="text-[11px] font-extrabold text-sky-700 uppercase tracking-wider flex items-center gap-1">
                  <span class="material-icons text-xs text-sky-600">confirmation_number</span>
                  <span>Kode Tiket: ${escapeHtml(t.kode_antrian)}</span>
                </div>
                <h3 class="text-base font-bold text-slate-900 mt-0.5">${escapeHtml(t.layanan)}</h3>
              </div>
            </div>
            <div class="shrink-0 flex items-center gap-2">
              ${statusBadge}
            </div>
          </div>

          ${queueEstimationCard}

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs text-slate-600 bg-slate-50 p-4 rounded-xl border border-slate-100">
            <div><b>Jadwal Rencana:</b> ${formattedDate}</div>
            <div><b>Fasilitas:</b> ${escapeHtml(t.fasilitas || '-')}</div>
            <div><b>Tujuan Pemanfaatan:</b> ${escapeHtml(t.pemanfaatan || '-')}</div>
            <div><b>Rincian Data:</b> ${escapeHtml(t.data_diinginkan || '-')}</div>
            <div><b>Evaluasi Pembangunan (Monev):</b> ${escapeHtml(t.monev || 'Ya')}</div>
            ${t.pendapat ? `<div><b>Ulasan SKM Anda:</b> <span class="font-bold text-sky-700">${escapeHtml(t.pendapat)}</span> ${t.catatan ? `("${escapeHtml(t.catatan)}")` : ''}</div>` : ''}
          </div>

          ${!t.pendapat && t.status === 'Selesai' ? `
            <div class="p-2.5 bg-gradient-to-r from-amber-500/10 via-amber-400/5 to-amber-500/10 border border-amber-300 rounded-xl text-xs text-amber-900 flex items-center gap-1.5 font-semibold">
              <span class="material-icons text-amber-600 text-sm shrink-0">stars</span>
              <span>Pelayanan Anda telah selesai. Mohon luangkan waktu sejenak untuk menekan tombol <b>⭐ Beri Ulasan SKM</b> di bawah.</span>
            </div>
          ` : ''}

          <div class="flex flex-wrap items-center justify-end gap-2 pt-1">
            <button type="button" onclick="showTicketModal('${t.nomor}', '${t.kode_antrian}', '${escapeHtml(t.nama)}', '${escapeHtml(t.layanan)}', '${t.tanggal}', '${t.waktu}', '${t.antrean_aktif_saat_ini || '-'}', '${t.antrian_di_depan !== null ? t.antrian_di_depan : '-'}', '${escapeHtml(t.estimasi_waktu || '-')}')" class="btn btn-sm btn-primary bg-sky-600 border-sky-600 font-bold text-xs rounded-xl flex items-center gap-1">
              <span class="material-icons text-sm">qr_code_2</span>
              <span>Lihat & Cetak Tiket</span>
            </button>

            ${t.status === 'Selesai' ? `
              <button type="button" onclick="openSKMModal(${t.id}, '${t.pendapat || ''}', '${escapeHtml(t.catatan || '')}')" class="btn btn-sm ${t.pendapat ? 'btn-outline-sky border-sky-300 text-sky-700 font-semibold' : 'btn-warning bg-amber-500 text-slate-900 border-amber-400 font-extrabold shadow-sm'} text-xs rounded-xl flex items-center gap-1">
                <span class="material-icons text-sm">${t.pendapat ? 'edit_note' : 'star'}</span>
                <span>${t.pendapat ? 'Edit Ulasan SKM' : '⭐ Beri Ulasan SKM'}</span>
              </button>
            ` : ''}

            ${t.status === 'Menunggu' ? `
              <button type="button" onclick="confirmCancelTicket(${t.id})" class="btn btn-sm btn-outline-danger font-semibold text-xs rounded-xl flex items-center gap-1">
                <span class="material-icons text-sm">cancel</span>
                <span>Batalkan</span>
              </button>
            ` : ''}
          </div>
        </div>
      `;
    }).join('');
  }

  // Filter Buttons Listener
  document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.filter-btn').forEach(b => {
        b.classList.remove('btn-primary', 'bg-sky-600', 'border-sky-600', 'active');
        b.classList.add('btn-light', 'border');
      });
      btn.classList.remove('btn-light', 'border');
      btn.classList.add('btn-primary', 'bg-sky-600', 'border-sky-600', 'active');

      currentFilter = btn.getAttribute('data-filter');
      renderTickets();
    });
  });

  // Filter Waktu Listener
  const userWaktuSelect = document.getElementById('user_filter_waktu');
  const userCustomBox = document.getElementById('user_custom_date_box');
  const btnApplyUserDate = document.getElementById('btn_apply_user_date');
  const btnResetUserDate = document.getElementById('btn_reset_user_date');
  const userDateMulai = document.getElementById('user_date_mulai');
  const userDateSelesai = document.getElementById('user_date_selesai');

  if (userWaktuSelect) {
    userWaktuSelect.addEventListener('change', () => {
      if (userWaktuSelect.value === 'custom') {
        if (userCustomBox) {
          userCustomBox.style.display = 'flex';
          userCustomBox.classList.remove('hidden');
          userCustomBox.classList.add('flex');
        }
      } else {
        if (userCustomBox) {
          userCustomBox.style.display = 'none';
          userCustomBox.classList.add('hidden');
          userCustomBox.classList.remove('flex');
        }
        renderTickets();
      }
    });
  }

  if (btnApplyUserDate) {
    btnApplyUserDate.addEventListener('click', () => renderTickets());
  }

  if (btnResetUserDate) {
    btnResetUserDate.addEventListener('click', () => {
      if (userWaktuSelect) userWaktuSelect.value = 'all';
      if (userDateMulai) userDateMulai.value = '';
      if (userDateSelesai) userDateSelesai.value = '';
      if (userCustomBox) {
        userCustomBox.style.display = 'none';
        userCustomBox.classList.add('hidden');
        userCustomBox.classList.remove('flex');
      }
      renderTickets();
    });
  }

  if (searchInput) searchInput.addEventListener('input', renderTickets);

  loadTickets();

  // Auto-sync antrean real-time setiap 8 detik
  setInterval(() => {
    loadTickets();
  }, 8000);
});

// Show Ticket QR Modal
function showTicketModal(nomor, kode, nama, layanan, tanggal, waktu, activeCalled = '-', queueAhead = '-', estWait = '-') {
  document.getElementById('ticket_number').textContent = nomor;
  document.getElementById('ticket_name').textContent = nama;
  document.getElementById('ticket_service').textContent = layanan;
  document.getElementById('ticket_date').textContent = tanggal;
  document.getElementById('ticket_time').textContent = waktu + ' WIB';

  const liveStatusRow = document.getElementById('ticket_live_status_row');
  if (liveStatusRow) {
    if (queueAhead !== '-' && queueAhead !== 'null' && queueAhead !== undefined) {
      liveStatusRow.classList.remove('hidden');
      const activeElem = document.getElementById('ticket_active_called');
      const aheadElem = document.getElementById('ticket_queue_ahead');
      const waitElem = document.getElementById('ticket_estimated_wait');
      
      if (activeElem) activeElem.textContent = activeCalled;
      if (aheadElem) aheadElem.textContent = queueAhead === '0' ? '0 (Giliran Anda)' : queueAhead + ' Antrean';
      if (waitElem) waitElem.textContent = estWait;
    } else {
      liveStatusRow.classList.add('hidden');
    }
  }

  const qrContainer = document.getElementById('qrcode_box');
  qrContainer.innerHTML = '';
  if (typeof QRCode !== 'undefined') {
    new QRCode(qrContainer, {
      text: JSON.stringify({ id: kode, nomor: nomor, nama: nama }),
      width: 140,
      height: 140,
      colorDark: '#003366',
      colorLight: '#ffffff',
      correctLevel: QRCode.CorrectLevel.H
    });
  }

  const bsModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalTicket'));
  bsModal.show();
}

// Confirm Cancel Ticket
function confirmCancelTicket(id) {
  Swal.fire({
    title: 'Batalkan Reservasi Antrean?',
    text: 'Apakah Anda yakin ingin membatalkan reservasi tiket antrean ini?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, Batalkan',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#d33'
  }).then(async (res) => {
    if (res.isConfirmed) {
      try {
        const fd = new FormData();
        fd.append('id', id);
        fd.append('csrf_token', getCsrfToken());

        const resp = await fetch('api.php?action=cancel_antrian', { method: 'POST', body: fd });
        const json = await resp.json();
        if (json.status === 'success') {
          Swal.fire('Berhasil', 'Reservasi antrean berhasil dibatalkan.', 'success').then(() => {
            window.location.reload();
          });
        } else {
          Swal.fire('Gagal', json.message || 'Gagal membatalkan antrean.', 'error');
        }
      } catch (e) {
        Swal.fire('Error', 'Gagal terhubung ke server.', 'error');
      }
    }
  });
}

// Open SKM Modal
function openSKMModal(id, currentPendapat, currentCatatan) {
  document.getElementById('skm_antrian_id').value = id;
  document.getElementById('skm_catatan').value = currentCatatan || '';

  const targetVal = currentPendapat || 'Sangat Puas';
  const radio = document.querySelector(`input[name="skm_pendapat"][value="${targetVal}"]`);
  if (radio) {
    radio.checked = true;
    if (radio.closest('.skm-card')) {
      selectSKMCard(radio.closest('.skm-card'));
    } else {
      updateSKMVisualState();
    }
  } else {
    updateSKMVisualState();
  }

  const bsModal = new bootstrap.Modal(document.getElementById('modalSKM'));
  bsModal.show();
}

function selectSKMCard(el) {
  const radio = el.querySelector('input[type="radio"]');
  if (radio) radio.checked = true;

  document.querySelectorAll('.skm-card').forEach(card => {
    const input = card.querySelector('input[type="radio"]');
    const badge = card.querySelector('.skm-badge');
    const emoji = card.querySelector('.skm-emoji');
    const label = card.querySelector('.skm-label');

    if (emoji) {
      emoji.className = 'skm-emoji text-3xl mb-1 transition-all duration-200 inline-block';
    }

    if (input && input.checked) {
      card.className = 'skm-card cursor-pointer border-2 border-sky-600 bg-sky-100/90 shadow-md ring-2 ring-sky-500/30 scale-105 rounded-2xl p-3.5 text-center transition-all duration-200 relative overflow-hidden select-none';
      if (badge) badge.classList.remove('hidden');
      if (label) label.className = 'text-xs font-extrabold text-sky-900 skm-label';

      if (emoji) {
        const val = input.value;
        if (val === 'Sangat Puas') emoji.classList.add('anim-heartbeat');
        else if (val === 'Puas') emoji.classList.add('anim-nod');
        else if (val === 'Cukup Puas') emoji.classList.add('anim-sway');
        else if (val === 'Tidak Puas') emoji.classList.add('anim-shake');
        else emoji.classList.add('anim-heartbeat');
      }
    } else {
      card.className = 'skm-card cursor-pointer border-2 border-slate-200 rounded-2xl p-3.5 text-center transition-all duration-200 relative overflow-hidden select-none hover:border-sky-400 hover:bg-sky-50/50';
      if (badge) badge.classList.add('hidden');
      if (label) label.className = 'text-xs font-bold text-slate-700 skm-label';
    }
  });
}

function updateSKMVisualState() {
  const checkedRadio = document.querySelector('input[name="skm_pendapat"]:checked');
  if (checkedRadio && checkedRadio.closest('.skm-card')) {
    selectSKMCard(checkedRadio.closest('.skm-card'));
  }
}

// Submit SKM Rating Handler
const formSKM = document.getElementById('formSKM');
if (formSKM) {
  formSKM.addEventListener('submit', async function(e) {
    e.preventDefault();
    const id = document.getElementById('skm_antrian_id').value;
    const checkedRadio = document.querySelector('input[name="skm_pendapat"]:checked');
    const pendapat = checkedRadio ? checkedRadio.value : 'Sangat Puas';
    const catatan = document.getElementById('skm_catatan').value.trim();

    try {
      const fd = new FormData();
      fd.append('id', id);
      fd.append('pendapat', pendapat);
      fd.append('catatan', catatan);
      fd.append('csrf_token', getCsrfToken());

      const res = await fetch('api.php?action=submit_skm', { method: 'POST', body: fd });
      const json = await res.json();
      if (json.status === 'success') {
        Swal.fire('Terima Kasih', json.message, 'success').then(() => {
          window.location.reload();
        });
      } else {
        Swal.fire('Gagal', json.message || 'Gagal menyimpan penilaian.', 'error');
      }
    } catch (err) {
      Swal.fire('Error', 'Gagal terhubung ke server.', 'error');
    }
  });
}

// Print Ticket Handler
const btnPrintTicket = document.getElementById('btn_print_ticket');
if (btnPrintTicket) {
  btnPrintTicket.addEventListener('click', () => {
    window.print();
  });
}
