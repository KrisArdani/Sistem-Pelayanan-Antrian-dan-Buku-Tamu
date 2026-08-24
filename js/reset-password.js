// SPST BPS Kota Tegal - JavaScript Reset Password Pengunjung

document.addEventListener('DOMContentLoaded', () => {
  const tokenEl = document.getElementById('reset_token');
  const urlToken = tokenEl ? tokenEl.value : '';

  async function verifyToken() {
    if (!urlToken) {
      showInvalidState('Token tidak ditemukan pada tautan.');
      return;
    }

    try {
      const res = await fetch(`api.php?action=verify_reset_token&token=${encodeURIComponent(urlToken)}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const data = await res.json();

      const checking = document.getElementById('sectionChecking');
      if (checking) checking.classList.add('hidden');

      if (data.status === 'success') {
        const uDisp = document.getElementById('display_username');
        const eDisp = document.getElementById('display_email');
        const subDisp = document.getElementById('resetInfoSubtitle');
        const formPass = document.getElementById('formSetPassword');

        if (uDisp) uDisp.innerText = '@' + data.data.username;
        if (eDisp) eDisp.innerText = data.data.email;
        if (subDisp) subDisp.innerText = `Pemulihan password untuk akun @${data.data.username}`;
        if (formPass) formPass.classList.remove('hidden');
      } else {
        showInvalidState(data.message || 'Token tidak valid atau kedaluwarsa.');
      }
    } catch (err) {
      console.error("Verify token error:", err);
      showInvalidState('Gagal terhubung ke server.');
    }
  }

  function showInvalidState(msg) {
    const checking = document.getElementById('sectionChecking');
    const errMsg = document.getElementById('errorMessageToken');
    const secInvalid = document.getElementById('sectionInvalid');

    if (checking) checking.classList.add('hidden');
    if (errMsg) errMsg.innerText = msg;
    if (secInvalid) secInvalid.classList.remove('hidden');
  }

  const formSetPassword = document.getElementById('formSetPassword');
  if (formSetPassword) {
    formSetPassword.addEventListener('submit', async function(e) {
      e.preventDefault();
      const newPass = document.getElementById('new_password').value;
      const confirmPass = document.getElementById('confirm_password').value;

      if (newPass !== confirmPass) {
        Swal.fire({
          icon: 'error',
          title: 'Tidak Cocok',
          text: 'Konfirmasi password baru tidak cocok dengan password baru.',
          confirmButtonColor: '#003366'
        });
        return;
      }

      const btn = document.getElementById('btnSavePassword');
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status"></span> <span>Menyimpan...</span>`;
      }

      try {
        const formData = new FormData();
        formData.append('action', 'reset_password_with_token');
        formData.append('token', urlToken);
        formData.append('new_password', newPass);
        formData.append('csrf_token', csrfToken);

        const res = await fetch('api.php', {
          method: 'POST',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken
          },
          body: formData
        });
        const data = await res.json();

        if (data.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: data.message,
            confirmButtonColor: '#003366'
          }).then(() => {
            window.location.href = 'login.php';
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: data.message || 'Gagal memperbarui password.',
            confirmButtonColor: '#003366'
          });
        }
      } catch (err) {
        console.error("Save password error:", err);
        Swal.fire({
          icon: 'error',
          title: 'Terjadi Kesalahan',
          text: 'Gagal terhubung ke server.',
          confirmButtonColor: '#003366'
        });
      } finally {
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = `<span class="material-icons text-base">check_circle</span> <span>Simpan Password Baru</span>`;
        }
      }
    });
  }

  // Toggle Password Visibility
  function setupToggle(buttonId, inputId, iconId) {
    const btn = document.getElementById(buttonId);
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (btn && input && icon) {
      btn.addEventListener('click', () => {
        if (input.type === 'password') {
          input.type = 'text';
          icon.textContent = 'visibility';
        } else {
          input.type = 'password';
          icon.textContent = 'visibility_off';
        }
      });
    }
  }

  setupToggle('toggleNewPassword', 'new_password', 'iconNewPassword');
  setupToggle('toggleConfirmPassword', 'confirm_password', 'iconConfirmPassword');

  verifyToken();
});

