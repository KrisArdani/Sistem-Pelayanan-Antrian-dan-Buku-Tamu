/**
 * TOBASA BPS Kota Tegal - Penangan Utilitas & Penyimpanan Aplikasi Utama
 */

// Konstanta Kunci Penyimpanan
const STORAGE_KEYS = {
  CURRENT_USER: 'tobasa_current_user'
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
    const apiPath = window.location.pathname.includes('/admin/') ? '../api.php?action=check_session' : 'api.php?action=check_session';
    const res = await fetch(apiPath);
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
    return true;
  } catch (e) {
    console.error('Session check failed:', e);
    return true; // Biarkan autentikasi server-side auth_check PHP menangani pengalihan
  }
}
