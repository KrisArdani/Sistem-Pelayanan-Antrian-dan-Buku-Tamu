// SPST BPS Kota Tegal - JavaScript Login & Forgot Password

let modalForgotObj = null;

function openModalForgotPassword() {
  const form = document.getElementById('formForgotPassword');
  if (form) form.reset();
  const notice = document.getElementById('resetDevNotice');
  if (notice) notice.classList.add('hidden');
  if (!modalForgotObj) {
    const modalEl = document.getElementById('modalForgotPassword');
    if (modalEl) modalForgotObj = new bootstrap.Modal(modalEl);
  }
  if (modalForgotObj) modalForgotObj.show();
}

async function handleRequestPasswordReset(e) {
  e.preventDefault();
  const email = document.getElementById('reset_email_input').value.trim();
  const btn = document.getElementById('btnSubmitResetReq');
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  if (btn) {
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status"></span> <span>Mengirim...</span>`;
  }

  try {
    const formData = new FormData();
    formData.append('action', 'request_password_reset');
    formData.append('email', email);
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
        title: 'Permintaan Terkirim!',
        text: data.message,
        confirmButtonColor: '#003366'
      });

      if (data.data && data.data.reset_url) {
        const notice = document.getElementById('resetDevNotice');
        const link = document.getElementById('resetDevLink');
        if (notice) notice.classList.remove('hidden');
        if (link) link.href = data.data.reset_url;
      }
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Gagal Memproses',
        text: data.message || 'Email tidak ditemukan.',
        confirmButtonColor: '#003366'
      });
    }
  } catch (err) {
    console.error("Reset request error:", err);
    Swal.fire({
      icon: 'error',
      title: 'Kesalahan Sistem',
      text: 'Gagal terhubung ke server.',
      confirmButtonColor: '#003366'
    });
  } finally {
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = `<span class="material-icons text-sm">send</span> <span>Kirim Link Reset</span>`;
    }
  }
}

function fillDemoLogin(username, password) {
  const uInput = document.getElementById('login_username');
  const pInput = document.getElementById('login_password');
  if (uInput) uInput.value = username;
  if (pInput) pInput.value = password;
  
  if (typeof Swal !== 'undefined') {
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'info',
      title: `Form terisi: ${username}`,
      showConfirmButton: false,
      timer: 1800
    });
  }
}

document.addEventListener('DOMContentLoaded', () => {
  // Toggle Password Visibility
  const btnToggle = document.getElementById('btnTogglePassword');
  const inputPass = document.getElementById('login_password');
  const iconPass = document.getElementById('iconPassword');

  if (btnToggle && inputPass && iconPass) {
    btnToggle.addEventListener('click', () => {
      if (inputPass.type === 'password') {
        inputPass.type = 'text';
        iconPass.textContent = 'visibility';
      } else {
        inputPass.type = 'password';
        iconPass.textContent = 'visibility_off';
      }
    });
  }

  // Handle Login Form Submit
  const formLogin = document.getElementById('formLogin');
  const btnSubmit = document.getElementById('btnSubmitLogin');

  if (formLogin) {
    formLogin.addEventListener('submit', async (e) => {
      e.preventDefault();
      const u = document.getElementById('login_username').value.trim();
      const p = document.getElementById('login_password').value.trim();

      if (btnSubmit) {
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> <span>Memproses...</span>`;
      }

      try {
        const result = await loginUser(u, p);
        if (result.success) {
          Swal.fire({
            icon: 'success',
            title: 'Login Berhasil!',
            text: `Selamat datang kembali, ${result.user.name}.`,
            showConfirmButton: false,
            timer: 1400
          }).then(() => {
            window.location.href = result.user.redirect;
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Gagal Login',
            text: result.message || 'Username atau password salah',
            confirmButtonColor: '#003366'
          });
          if (btnSubmit) {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = `<span class="material-icons text-base">login</span> <span>Masuk Sekarang</span>`;
          }
        }
      } catch (err) {
        Swal.fire({
          icon: 'error',
          title: 'Terjadi Kesalahan',
          text: 'Gagal terhubung ke server login.',
          confirmButtonColor: '#003366'
        });
        if (btnSubmit) {
          btnSubmit.disabled = false;
          btnSubmit.innerHTML = `<span class="material-icons text-base">login</span> <span>Masuk Sekarang</span>`;
        }
      }
    });
  }
});
