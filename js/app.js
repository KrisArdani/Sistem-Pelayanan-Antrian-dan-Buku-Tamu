/**
 * SPST BPS Kota Tegal - Penangan Utilitas & Penyimpanan Aplikasi Utama
 */

// Konstanta Kunci Penyimpanan
const STORAGE_KEYS = {
  CURRENT_USER: 'spst_current_user'
};

// Pembantu: Ambil Token CSRF dari meta tag DOM
function getCsrfToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.getAttribute('content') : '';
}

// Pembantu: Format Tanggal Indonesia
function formatTanggalIndo(dateStr) {
  if (!dateStr) return '-';
  const d = new Date(dateStr);
  if (isNaN(d)) return dateStr;
  
  const bulan = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
  ];
  const tgl = String(d.getDate()).padStart(2, '0');
  const bln = bulan[d.getMonth()];
  const thn = d.getFullYear();
  const jam = String(d.getHours()).padStart(2, '0');
  const menit = String(d.getMinutes()).padStart(2, '0');
  
  return `${tgl} ${bln} ${thn} Pukul ${jam}:${menit} WIB`;
}

// Pembantu: Sanitasi HTML untuk perataan aman JS (Perlindungan XSS)
function escapeHtml(str) {
  if (str === null || str === undefined) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

// Penangan Sesi Autentikasi
function getCurrentUser() {
  const user = sessionStorage.getItem(STORAGE_KEYS.CURRENT_USER);
  return user ? JSON.parse(user) : null;
}

async function loginUser(username, password) {
  try {
    const formData = new FormData();
    formData.append('username', username);
    formData.append('password', password);
    formData.append('csrf_token', getCsrfToken());

    const apiPath = window.location.pathname.includes('/admin/') ? '../api.php?action=login' : 'api.php?action=login';
    const res = await fetch(apiPath, { method: 'POST', body: formData });
    const json = await res.json();

    if (json.status === 'success') {
      sessionStorage.setItem(STORAGE_KEYS.CURRENT_USER, JSON.stringify(json.data));
      return { success: true, user: json.data };
    } else {
      return { success: false, message: json.message || 'Login gagal.' };
    }
  } catch (e) {
    console.error('API login failed:', e);
    return { success: false, message: 'Gagal terhubung ke server.' };
  }
}

async function logoutUser() {
  try {
    const apiPath = window.location.pathname.includes('/admin/') ? '../api.php?action=logout' : 'api.php?action=logout';
    const formData = new FormData();
    formData.append('csrf_token', getCsrfToken());
    await fetch(apiPath, { method: 'POST', body: formData });
  } catch (e) {
    console.warn('API logout warning:', e);
  } finally {
    sessionStorage.removeItem(STORAGE_KEYS.CURRENT_USER);
    window.location.href = window.location.pathname.includes('/admin/') ? '../login.php' : 'login.php';
  }
}

async function checkAuth(requiredRoles = []) {
  try {
    const apiPath = window.location.pathname.includes('/admin/') ? `../api.php?action=check_session&_t=${Date.now()}` : `api.php?action=check_session&_t=${Date.now()}`;
    const res = await fetch(apiPath, { cache: 'no-store' });
    const json = await res.json();

    if (json.status !== 'success') {
      window.location.href = window.location.pathname.includes('/admin/') ? '../login.php' : 'login.php';
      return false;
    }

    if (requiredRoles.length > 0 && !requiredRoles.includes(json.data.role)) {
      alert('Anda tidak memiliki akses ke halaman ini!');
      window.location.href = window.location.pathname.includes('/admin/') ? '../index.php' : 'index.php';
      return false;
    }

    sessionStorage.setItem(STORAGE_KEYS.CURRENT_USER, JSON.stringify(json.data));
    return json.data;
  } catch (e) {
    console.error('Session check failed:', e);
    return true; // Biarkan autentikasi server-side auth_check PHP menangani pengalihan
  }
}

// Pembantu: Polling Real-Time Jumlah Antrean Menunggu untuk Sidebar Badges
async function updateWaitingBadgeCounter() {
  const adminBadge = document.getElementById('admin_sidebar_waiting_badge');
  const adminMobileBadge = document.getElementById('admin_mobile_waiting_badge');

  if (!adminBadge && !adminMobileBadge) return;

  try {
    const apiPath = window.location.pathname.includes('/admin/') ? `../api.php?action=get_waiting_count&_t=${Date.now()}` : `api.php?action=get_waiting_count&_t=${Date.now()}`;
    const res = await fetch(apiPath, { cache: 'no-store' });
    const json = await res.json();

    if (json.status === 'success') {
      const adminCount = json.data.total_menunggu || 0;

      // Update badge antrean untuk admin/petugas (tampil jika ada antrean berstatus Menunggu hari ini)
      [adminBadge, adminMobileBadge].forEach(b => {
        if (b) {
          if (adminCount > 0) {
            b.textContent = adminCount;
            b.classList.remove('hidden');
          } else {
            b.classList.add('hidden');
          }
        }
      });
    }
  } catch (err) {
    console.warn('Failed to update waiting badge counter:', err);
  }
}

// Fungsi Ekspor Data Universal (Excel / PDF) dengan Sinkronisasi Filter Aktif
function exportData(type, format) {
  const apiPath = window.location.pathname.includes('/admin/') ? '../api.php' : 'api.php';
  
  let q = '';
  let katInstansi = '';
  let layanan = 'all';
  let waktu = 'today';
  let tglMulai = '';
  let tglSelesai = '';
  let tipe = 'all';
  let status = 'all';

  if (type === 'bukutamu') {
    q = document.getElementById('search_bukutamu')?.value.trim() || '';
    katInstansi = document.getElementById('filter_kategori_instansi')?.value || '';
    layanan = document.getElementById('filter_kategori_layanan')?.value || 'all';
    waktu = document.getElementById('filter_waktu')?.value || 'all';
    tglMulai = document.getElementById('filter_tanggal_mulai')?.value || '';
    tglSelesai = document.getElementById('filter_tanggal_selesai')?.value || '';
    
    const activeTypeBtn = document.querySelector('#type-tabs .filter-type-btn.active');
    if (activeTypeBtn) tipe = activeTypeBtn.getAttribute('data-type') || 'all';

    const activeStatusBtn = document.querySelector('#status-tabs .filter-status-btn.active');
    if (activeStatusBtn) status = activeStatusBtn.getAttribute('data-status') || 'all';
  } else if (type === 'skm') {
    waktu = document.getElementById('filter_tanggal_skm')?.value || document.getElementById('filter_tanggal_antrian')?.value || 'all';
    layanan = document.getElementById('filter_layanan_antrian')?.value || 'all';
    status = document.getElementById('filter_status_antrian')?.value || 'all';
  } else {
    waktu = document.getElementById('filter_tanggal_antrian')?.value || 'today';
    layanan = document.getElementById('filter_layanan_antrian')?.value || 'all';
    status = document.getElementById('filter_status_antrian')?.value || 'all';
  }

  const actionName = type === 'antrian' ? 'export_antrian' : (type === 'bukutamu' ? 'export_bukutamu' : 'export_skm');
  const params = new URLSearchParams({
    action: actionName,
    format: format,
    q: q,
    kategori_instansi: katInstansi,
    layanan: layanan,
    waktu: waktu,
    tanggal: waktu,
    tanggal_mulai: tglMulai,
    tanggal_selesai: tglSelesai,
    tipe: tipe,
    status: status
  });

  if (format === 'pdf') {
    params.set('type', type);
    const printUrl = window.location.pathname.includes('/admin/') 
      ? `cetak_laporan.php?${params.toString()}`
      : `admin/cetak_laporan.php?${params.toString()}`;
    window.open(printUrl, '_blank');
  } else {
    // Unduh File Excel / CSV
    window.location.href = `${apiPath}?${params.toString()}`;
  }
}

async function cancelVisitorQueue(id) {
  const result = await Swal.fire({
    title: 'Batalkan Reservasi Antrean?',
    text: 'Apakah Anda yakin ingin membatalkan tiket antrean aktif ini? Setelah dibatalkan, Anda dapat memilih jadwal atau layanan baru.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Ya, Batalkan Tiket',
    cancelButtonText: 'Batal'
  });

  if (!result.isConfirmed) return;

  try {
    const formData = new FormData();
    formData.append('id', id);
    formData.append('csrf_token', getCsrfToken());

    const apiPath = window.location.pathname.includes('/admin/') ? '../api.php?action=batal_ant_pengunjung' : 'api.php?action=batal_ant_pengunjung';
    const res = await fetch(apiPath, { method: 'POST', body: formData });
    const json = await res.json();

    if (json.status === 'success') {
      Swal.fire('Berhasil!', json.message, 'success').then(() => {
        window.location.reload();
      });
    } else {
      Swal.fire('Gagal', json.message || 'Gagal membatalkan tiket.', 'error');
    }
  } catch (err) {
    Swal.fire('Error', 'Gagal terhubung ke server.', 'error');
  }
}

document.addEventListener('DOMContentLoaded', () => {
  updateWaitingBadgeCounter();
  setInterval(updateWaitingBadgeCounter, 5000); // Polling setiap 5 detik
});
