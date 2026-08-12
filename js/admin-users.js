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
      actionButtons = `<span class="px-2.5 py-1 text-[11px] font-semibold text-slate-400 bg-slate-100 rounded-lg italic">Read-Only (Mandiri)</span>`;
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
