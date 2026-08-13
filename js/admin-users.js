// SPST BPS Kota Tegal - JavaScript Admin Users Management

let currentRoleFilter = 'all';
let currentUsersData = [];
let modalUserObj = null;
let modalResetObj = null;

document.addEventListener('DOMContentLoaded', () => {
  const modalUserEl = document.getElementById('modalUser');
  const modalResetEl = document.getElementById('modalResetPassword');
  if (modalUserEl) modalUserObj = new bootstrap.Modal(modalUserEl);
  if (modalResetEl) modalResetObj = new bootstrap.Modal(modalResetEl);
  loadUsersData();

  const formUser = document.getElementById('formUser');
  if (formUser) {
    formUser.addEventListener('submit', saveUser);
  }

  const formResetPassword = document.getElementById('formResetPassword');
  if (formResetPassword) {
    formResetPassword.addEventListener('submit', submitResetPassword);
  }
});

function filterByRole(role) {
  currentRoleFilter = role;
  const tabs = ['all', 'petugas', 'admin', 'kepala', 'pengunjung'];
  tabs.forEach(t => {
    const btn = document.getElementById(`tab_role_${t}`);
    if (btn) {
      if (t === role) {
        btn.className = 'px-3 py-1.5 rounded-lg bg-white shadow-sm text-slate-800 font-bold transition';
      } else {
        btn.className = 'px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 transition';
      }
    }
  });
  loadUsersData();
}

let searchDebounceTimer = null;
function handleSearchInput() {
  clearTimeout(searchDebounceTimer);
  searchDebounceTimer = setTimeout(() => {
    loadUsersData();
  }, 250);
}

function handleSearchKey(e) {
  if (e.key === 'Enter') {
    loadUsersData();
  }
}

async function loadUsersData() {
  const searchInput = document.getElementById('searchInput');
  const search = searchInput ? searchInput.value.trim() : '';
  const tbody = document.getElementById('tableUsersBody');
  if (!tbody) return;

  tbody.innerHTML = `
    <tr>
      <td colspan="7" class="text-center py-8 text-slate-400">
        <span class="material-icons animate-spin text-2xl mb-1">sync</span>
        <div>Memuat data pengguna...</div>
      </td>
    </tr>
  `;

  try {
    const res = await fetch(`../api.php?action=get_users&role=${currentRoleFilter}&search=${encodeURIComponent(search)}`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    const data = await res.json();

    if (data.status === 'success') {
      currentUsersData = data.data.users || [];
      renderSummaryCounts(data.data.summary || {});
      renderUsersTable(currentUsersData);
    } else {
      tbody.innerHTML = `<tr><td colspan="7" class="text-center py-6 text-red-500">${data.message}</td></tr>`;
    }
  } catch (err) {
    console.error("Failed to load users:", err);
    tbody.innerHTML = `<tr><td colspan="7" class="text-center py-6 text-red-500">Gagal mengambil data dari server.</td></tr>`;
  }
}

function renderSummaryCounts(summary) {
  const elTotal = document.getElementById('kpi_total_user');
  const elPetugas = document.getElementById('kpi_petugas');
  const elAdminKepala = document.getElementById('kpi_admin_kepala');
  const elPengunjung = document.getElementById('kpi_pengunjung');

  if (elTotal) elTotal.innerText = summary.total || 0;
  if (elPetugas) elPetugas.innerText = summary.petugas || 0;
  if (elAdminKepala) elAdminKepala.innerText = (summary.admin || 0) + (summary.kepala || 0);
  if (elPengunjung) elPengunjung.innerText = summary.pengunjung || 0;
}

function renderUsersTable(users) {
  const tbody = document.getElementById('tableUsersBody');
  if (!tbody) return;

  if (!users || users.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="7" class="text-center py-8 text-slate-400">
          <span class="material-icons text-3xl mb-1 text-slate-300">person_off</span>
          <div>Tidak ada data pengguna yang ditemukan.</div>
        </td>
      </tr>
    `;
    return;
  }

  let html = '';
  users.forEach((u, idx) => {
    let roleBadge = '';
    if (u.role === 'petugas') {
      roleBadge = '<span class="px-2.5 py-1 text-[10px] font-bold bg-emerald-100 text-emerald-800 rounded-full uppercase">Petugas PST</span>';
    } else if (u.role === 'admin') {
      roleBadge = '<span class="px-2.5 py-1 text-[10px] font-bold bg-purple-100 text-purple-800 rounded-full uppercase">Admin System</span>';
    } else if (u.role === 'kepala') {
      roleBadge = '<span class="px-2.5 py-1 text-[10px] font-bold bg-sky-100 text-sky-800 rounded-full uppercase">Kepala BPS</span>';
    } else {
      roleBadge = '<span class="px-2.5 py-1 text-[10px] font-bold bg-amber-100 text-amber-800 rounded-full uppercase">Pengunjung</span>';
    }

    const dateFormatted = u.created_at ? u.created_at.substring(0, 10) : '-';

    let actionButtons = '';
    if (u.role === 'pengunjung') {
      actionButtons = `
        <button onclick="viewVisitorHistory(${u.id}, '${escapeHtml(u.name)}', '${escapeHtml(u.nohp || '')}', '${escapeHtml(u.nik || '')}')" title="Lihat Rekam Jejak Kunjungan" class="btn btn-sm btn-primary bg-sky-600 hover:bg-sky-700 border-sky-600 text-white text-xs font-bold rounded-xl px-3 py-1.5 inline-flex items-center gap-1 shadow-sm">
          <span class="material-icons text-sm">history</span>
          <span>Rekam Jejak</span>
        </button>
      `;
    } else {
      actionButtons = `
        <div class="flex items-center justify-center gap-1">
          <button onclick="editUser(${u.id})" title="Edit Staf Internal" class="p-1.5 text-sky-600 hover:bg-sky-50 rounded-lg transition">
            <span class="material-icons text-base">edit</span>
          </button>
          <button onclick="openModalResetPassword(${u.id}, '${escapeHtml(u.username)}')" title="Reset Password Staf" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition">
            <span class="material-icons text-base">key</span>
          </button>
          <button onclick="deleteUser(${u.id}, '${escapeHtml(u.username)}')" title="Hapus Staf Internal" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition">
            <span class="material-icons text-base">delete</span>
          </button>
        </div>
      `;
    }

    html += `
      <tr class="hover:bg-slate-50/80 transition">
        <td class="text-center font-bold text-slate-400 py-3.5 px-4">${idx + 1}</td>
        <td class="py-3.5 px-4">
          <div class="font-bold text-slate-900 text-sm">${escapeHtml(u.name)}</div>
          <div class="text-[11px] text-slate-400 font-mono">@${escapeHtml(u.username)}</div>
        </td>
        <td class="py-3.5 px-4">${roleBadge}</td>
        <td class="py-3.5 px-4 space-y-0.5">
          <div class="text-xs text-slate-700 flex items-center gap-1">
            <span class="material-icons text-[13px] text-slate-400">phone</span> ${escapeHtml(u.nohp || '-')}
          </div>
          <div class="text-[11px] text-slate-500 flex items-center gap-1">
            <span class="material-icons text-[13px] text-slate-400">email</span> ${escapeHtml(u.email || '-')}
          </div>
        </td>
        <td class="py-3.5 px-4">
          <div class="font-semibold text-slate-800">${escapeHtml(u.instansi || '-')}</div>
          <div class="text-[11px] text-slate-400">${escapeHtml(u.kategori_instansi || '')}</div>
        </td>
        <td class="text-center text-slate-500 py-3.5 px-4 text-xs">${dateFormatted}</td>
        <td class="text-center py-3.5 px-4">${actionButtons}</td>
      </tr>
    `;
  });

  tbody.innerHTML = html;
}

function openModalAddUser() {
  document.getElementById('formUser').reset();
  document.getElementById('user_id').value = "0";
  document.getElementById('modalUserTitle').innerHTML = `
    <span class="material-icons text-sky-400">person_add</span>
    <span>Tambah Staf Internal Baru</span>
  `;
  document.getElementById('user_password').required = true;
  document.getElementById('lblPassword').innerHTML = 'Password <span class="text-red-500">*</span>';
  document.getElementById('hintPasswordEdit').classList.add('hidden');
  if (modalUserObj) modalUserObj.show();
}

function editUser(id) {
  const u = currentUsersData.find(x => x.id == id);
  if (!u) return;

  if (u.role === 'pengunjung') {
    Swal.fire({
      icon: 'info',
      title: 'Akun Pengunjung',
      text: 'Admin tidak dapat mengubah akun pengunjung. Pengunjung mengelola akun dan password secara mandiri.'
    });
    return;
  }

  document.getElementById('user_id').value = u.id;
  document.getElementById('user_username').value = u.username || '';
  document.getElementById('user_password').value = '';
  document.getElementById('user_name').value = u.name || '';
  document.getElementById('user_role').value = u.role || 'petugas';
  document.getElementById('user_jenis_kelamin').value = u.jenis_kelamin || 'Laki Laki';
  document.getElementById('user_nohp').value = u.nohp || '';
  document.getElementById('user_email').value = u.email || '';
  document.getElementById('user_instansi').value = u.instansi || '';
  document.getElementById('user_kategori_instansi').value = u.kategori_instansi || 'Instansi Pemerintah';
  document.getElementById('user_pendidikan').value = u.pendidikan || 'D4-S1';
  document.getElementById('user_pekerjaan').value = u.pekerjaan || '';

  document.getElementById('modalUserTitle').innerHTML = `
    <span class="material-icons text-sky-400">edit_note</span>
    <span>Edit Staf Internal: @${escapeHtml(u.username)}</span>
  `;
  document.getElementById('user_password').required = false;
  document.getElementById('lblPassword').innerHTML = 'Password (Opsional)';
  document.getElementById('hintPasswordEdit').classList.remove('hidden');

  if (modalUserObj) modalUserObj.show();
}

async function saveUser(e) {
  e.preventDefault();
  const btn = document.getElementById('btnSaveUser');
  if (btn) btn.disabled = true;

  const id = document.getElementById('user_id').value;
  const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  const payload = {
    action: 'save_user',
    csrf_token: csrfToken,
    id: id,
    username: document.getElementById('user_username').value,
    password: document.getElementById('user_password').value,
    name: document.getElementById('user_name').value,
    role: document.getElementById('user_role').value,
    jenis_kelamin: document.getElementById('user_jenis_kelamin').value,
    nohp: document.getElementById('user_nohp').value,
    email: document.getElementById('user_email').value,
    instansi: document.getElementById('user_instansi').value,
    kategori_instansi: document.getElementById('user_kategori_instansi').value,
    pendidikan: document.getElementById('user_pendidikan').value,
    pekerjaan: document.getElementById('user_pekerjaan').value
  };

  try {
    const res = await fetch('../api.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify(payload)
    });
    const data = await res.json();

    if (data.status === 'success') {
      Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: data.message,
        timer: 1500,
        showConfirmButton: false
      });
      if (modalUserObj) modalUserObj.hide();
      loadUsersData();
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Gagal Menyimpan',
        text: data.message
      });
    }
  } catch (err) {
    console.error("Save user error:", err);
    Swal.fire({
      icon: 'error',
      title: 'Kesalahan Sistem',
      text: 'Gagal menghubungi server.'
    });
  } finally {
    if (btn) btn.disabled = false;
  }
}

function openModalResetPassword(id, username) {
  document.getElementById('reset_user_id').value = id;
  document.getElementById('reset_user_username').innerText = username;
  document.getElementById('reset_new_password').value = '';
  if (modalResetObj) modalResetObj.show();
}

async function submitResetPassword(e) {
  e.preventDefault();
  const id = document.getElementById('reset_user_id').value;
  const newPassword = document.getElementById('reset_new_password').value;
  const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  try {
    const res = await fetch('../api.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({
        action: 'reset_password_user',
        csrf_token: csrfToken,
        id: id,
        new_password: newPassword
      })
    });
    const data = await res.json();

    if (data.status === 'success') {
      Swal.fire({
        icon: 'success',
        title: 'Reset Password Berhasil!',
        text: data.message,
        timer: 1500,
        showConfirmButton: false
      });
      if (modalResetObj) modalResetObj.hide();
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Reset Password Gagal',
        text: data.message
      });
    }
  } catch (err) {
    console.error("Reset password error:", err);
    Swal.fire({
      icon: 'error',
      title: 'Kesalahan Sistem',
      text: 'Gagal menghubungi server.'
    });
  }
}

function deleteUser(id, username) {
  Swal.fire({
    title: `Hapus Akun @${username}?`,
    text: "Akun yang terhapus tidak dapat dikembalikan!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Ya, Hapus Akun',
    cancelButtonText: 'Batal'
  }).then(async (result) => {
    if (result.isConfirmed) {
      const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
      try {
        const res = await fetch('../api.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({
            action: 'delete_user',
            csrf_token: csrfToken,
            id: id
          })
        });
        const data = await res.json();

        if (data.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'Terhapus!',
            text: data.message,
            timer: 1500,
            showConfirmButton: false
          });
          loadUsersData();
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Gagal Menghapus',
            text: data.message
          });
        }
      } catch (err) {
        console.error("Delete user error:", err);
        Swal.fire({
          icon: 'error',
          title: 'Kesalahan Sistem',
          text: 'Gagal menghubungi server.'
        });
      }
    }
  });
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

async function viewVisitorHistory(userId, name, nohp, nik) {
  const modalEl = document.getElementById('modalVisitorHistory');
  const container = document.getElementById('contentVisitorHistory');
  if (!modalEl || !container) return;

  container.innerHTML = `
    <div class="text-center py-12 text-slate-400 space-y-2">
      <span class="material-icons animate-spin text-4xl text-sky-600">sync</span>
      <div class="font-bold text-slate-700">Mengambil data rekam jejak kunjungan...</div>
    </div>
  `;

  const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
  bsModal.show();

  try {
    const res = await fetch(`../api.php?action=get_visitor_history&user_id=${userId}&nohp=${encodeURIComponent(nohp)}&nik=${encodeURIComponent(nik)}`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    const json = await res.json();

    if (json.status === 'success') {
      const u = json.data.user || {};
      const history = json.data.history || [];
      const totalCount = json.data.total_kunjungan || 0;

      const userName = u.name || name || 'Pengunjung';
      const userNik = u.nik || nik || '-';
      const userNohp = u.nohp || nohp || '-';
      const userEmail = u.email || '-';
      const userPekerjaan = u.pekerjaan || '-';
      const userInstansi = u.instansi || '-';
      const userKatInstansi = u.kategori_instansi || '';
      const userUmur = u.umur || '-';

      // Hitung statistik ulasan SKM
      const ratedHistory = history.filter(h => h.pendapat);
      let skmSummary = 'Belum Ada Ulasan';
      if (ratedHistory.length > 0) {
        const lastRating = ratedHistory[0].pendapat;
        skmSummary = `${lastRating} (${ratedHistory.length} Ulasan)`;
      }

      let historyTimelineHtml = '';
      if (history.length === 0) {
        historyTimelineHtml = `
          <div class="p-8 bg-white rounded-2xl border border-slate-200 text-center text-slate-400 space-y-2">
            <span class="material-icons text-4xl text-slate-300">history_toggle_off</span>
            <div class="font-bold text-slate-600">Belum Ada Riwayat Kunjungan Loket</div>
            <div class="text-xs">Pengunjung ini belum memiliki data reservasi antrean di PST BPS Kota Tegal.</div>
          </div>
        `;
      } else {
        historyTimelineHtml = history.map((h, i) => {
          let badgeStatus = '';
          if (h.status === 'Selesai') badgeStatus = '<span class="badge bg-emerald-100 text-emerald-800 font-bold px-2.5 py-1 rounded-full text-xs">✅ Selesai</span>';
          else if (h.status === 'Dilayani') badgeStatus = '<span class="badge bg-sky-100 text-sky-800 font-bold px-2.5 py-1 rounded-full text-xs">🗣️ Dilayani</span>';
          else if (h.status === 'Dipanggil') badgeStatus = '<span class="badge bg-amber-500 text-slate-950 font-bold px-2.5 py-1 rounded-full text-xs">📢 Dipanggil</span>';
          else if (h.status === 'Menunggu') badgeStatus = '<span class="badge bg-amber-100 text-amber-800 font-bold px-2.5 py-1 rounded-full text-xs">⏳ Menunggu</span>';
          else badgeStatus = '<span class="badge bg-slate-100 text-slate-600 font-bold px-2.5 py-1 rounded-full text-xs">❌ Dibatalkan</span>';

          const formattedDate = typeof formatTanggalIndo === 'function' ? formatTanggalIndo(h.tanggal + ' ' + h.waktu) : (h.tanggal + ' ' + h.waktu);

          return `
            <div class="p-5 bg-white rounded-2xl border border-slate-200 shadow-sm space-y-3 relative overflow-hidden">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b pb-3 border-slate-100">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-xl bg-sky-900 text-white font-black text-xs flex items-center justify-center shrink-0 shadow">
                    #${history.length - i}
                  </div>
                  <div>
                    <div class="text-xs font-bold text-sky-800">No. Antrean: ${escapeHtml(h.nomor)} (${escapeHtml(h.kode_antrian)})</div>
                    <div class="text-sm font-black text-slate-900">${escapeHtml(h.layanan)}</div>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <span class="badge bg-blue-50 text-blue-800 border border-blue-200 text-[10px] uppercase font-bold px-2 py-0.5 rounded">${escapeHtml(h.tipe_pendaftaran || 'online')}</span>
                  ${badgeStatus}
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs text-slate-600 bg-slate-50 p-3.5 rounded-xl border border-slate-100">
                <div><b>Jadwal Kunjungan:</b> ${formattedDate}</div>
                <div><b>Fasilitas:</b> ${escapeHtml(h.fasilitas || 'Datang Langsung')}</div>
                <div><b>Tujuan Pemanfaatan:</b> ${escapeHtml(h.pemanfaatan || '-')}</div>
                <div><b>Monev Pembangunan:</b> ${escapeHtml(h.monev || 'Ya')}</div>
              </div>

              <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-xs space-y-1">
                <div class="font-bold text-amber-900 flex items-center gap-1">
                  <span class="material-icons text-sm text-amber-700">find_in_page</span> Rincian Data Yang Dicari:
                </div>
                <div class="text-slate-800">${escapeHtml(h.data_diinginkan || 'Tidak ada catatan rincian data.')}</div>
              </div>

              ${h.catatan_petugas ? `
                <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-200 text-xs space-y-1">
                  <div class="font-bold text-emerald-900 flex items-center gap-1">
                    <span class="material-icons text-sm text-emerald-700">assignment_turned_in</span> Catatan Pelayanan Petugas Loket:
                  </div>
                  <div class="text-slate-800 font-medium">${escapeHtml(h.catatan_petugas)}</div>
                </div>
              ` : ''}

              ${h.pendapat ? `
                <div class="p-3 bg-sky-50 rounded-xl border border-sky-200 text-xs space-y-1">
                  <div class="font-bold text-sky-900 flex items-center gap-1">
                    <span class="material-icons text-sm text-amber-500">star</span> Penilaian SKM Pengunjung: <span class="text-sky-800 font-extrabold">${escapeHtml(h.pendapat)}</span>
                  </div>
                  ${h.catatan ? `<div class="text-slate-700 italic">"${escapeHtml(h.catatan)}"</div>` : ''}
                </div>
              ` : ''}
            </div>
          `;
        }).join('');
      }

      container.innerHTML = `
        <!-- Profile Header Card -->
        <div class="p-6 bg-gradient-to-r from-[#002B5B] via-[#003366] to-[#0284c7] rounded-2xl text-white shadow-xl space-y-4 border border-sky-400/30">
          <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-white/10 pb-4">
            <div class="flex items-center gap-4">
              <div class="w-14 h-14 rounded-2xl bg-white/15 backdrop-blur border border-white/20 text-white font-black text-xl flex items-center justify-center shadow-lg shrink-0">
                <span class="material-icons text-3xl">person</span>
              </div>
              <div>
                <div class="text-xs font-bold text-sky-200 uppercase tracking-wider flex items-center gap-1.5">
                  <span>PROFIL PENGUNJUNG PST</span>
                  <span class="bg-amber-400/20 text-amber-300 text-[10px] px-2 py-0.5 rounded border border-amber-300/30">Terverifikasi</span>
                </div>
                <h3 class="text-xl md:text-2xl font-black brand-font text-white mt-0.5">${escapeHtml(userName)}</h3>
                <div class="text-xs text-sky-100/90 font-medium">${escapeHtml(userInstansi)} ${userKatInstansi ? `(${escapeHtml(userKatInstansi)})` : ''}</div>
              </div>
            </div>
            
            <div class="flex items-center gap-2 shrink-0">
              <div class="px-4 py-2 bg-white/10 backdrop-blur rounded-xl border border-white/15 text-center">
                <div class="text-[10px] text-sky-200 uppercase font-semibold">Total Kunjungan</div>
                <div class="text-lg font-black text-amber-300 brand-font">${totalCount} Kali</div>
              </div>
              <div class="px-4 py-2 bg-white/10 backdrop-blur rounded-xl border border-white/15 text-center">
                <div class="text-[10px] text-sky-200 uppercase font-semibold">SKM Pengunjung</div>
                <div class="text-xs font-extrabold text-emerald-300 mt-1">${skmSummary}</div>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 text-xs bg-white/10 p-3.5 rounded-xl border border-white/10 text-sky-100">
            <div><span class="text-sky-300">NIK (KTP):</span> <b class="text-white font-mono">${escapeHtml(userNik)}</b></div>
            <div><span class="text-sky-300">No. HP / WA:</span> <b class="text-white">${escapeHtml(userNohp)}</b></div>
            <div><span class="text-sky-300">Email:</span> <b class="text-white">${escapeHtml(userEmail)}</b></div>
            <div><span class="text-sky-300">Pekerjaan / Usia:</span> <b class="text-white">${escapeHtml(userPekerjaan)} (${escapeHtml(userUmur)})</b></div>
          </div>
        </div>

        <!-- Section Title -->
        <div class="flex items-center justify-between pt-2 border-b border-slate-200 pb-2">
          <h4 class="text-sm font-extrabold text-slate-900 brand-font flex items-center gap-2">
            <span class="material-icons text-sky-600">history</span>
            <span>Rekam Jejak Kunjungan & Pelayanan Loket (${totalCount})</span>
          </h4>
          <span class="text-xs text-slate-500 font-medium">Urut dari kunjungan terbaru</span>
        </div>

        <!-- Timeline Cards List -->
        <div class="space-y-4">
          ${historyTimelineHtml}
        </div>
      `;
    } else {
      container.innerHTML = `
        <div class="p-6 bg-rose-50 text-rose-800 rounded-2xl border border-rose-200 text-center text-xs font-bold">
          ${json.message || 'Gagal mengambil data rekam jejak.'}
        </div>
      `;
    }
  } catch (err) {
    console.error("viewVisitorHistory failed:", err);
    container.innerHTML = `
      <div class="p-6 bg-rose-50 text-rose-800 rounded-2xl border border-rose-200 text-center text-xs font-bold">
        Terjadi kesalahan sistem saat mengambil data rekam jejak.
      </div>
    `;
  }
}
