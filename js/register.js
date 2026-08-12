// SPST BPS Kota Tegal - JavaScript Registrasi Pengunjung

document.addEventListener('DOMContentLoaded', () => {
  const formRegister = document.getElementById('formRegister');
  if (formRegister) {
    formRegister.addEventListener('submit', async function(e) {
      e.preventDefault();

      const nikInput = document.getElementById('reg_nik');
      const nik = nikInput ? nikInput.value.trim() : '';
      if (!/^[0-9]{16}$/.test(nik)) {
        Swal.fire('Format NIK Salah', 'NIK harus terdiri dari tepat 16 digit angka sesuai KTP.', 'warning');
        return;
      }

      const btn = document.getElementById('btnRegister');
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Memproses...';
      }

      const csrfMeta = document.querySelector('meta[name="csrf-token"]');
      const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
      const payload = {
        action: 'register_pengunjung',
        username: document.getElementById('reg_username').value.trim(),
        password: document.getElementById('reg_password').value,
        name: document.getElementById('reg_name').value.trim(),
        nik: nik,
        nohp: document.getElementById('reg_nohp').value.trim(),
        email: document.getElementById('reg_email').value.trim(),
        jenis_kelamin: document.getElementById('reg_jk').value,
        umur: document.getElementById('reg_umur').value,
        pendidikan: document.getElementById('reg_pendidikan').value,
        pekerjaan: document.getElementById('reg_pekerjaan').value,
        instansi: document.getElementById('reg_instansi').value.trim(),
        kategori_instansi: document.getElementById('reg_kategori_instansi').value,
        csrf_token: csrfToken
      };

      try {
        const res = await fetch('api.php?action=register_pengunjung', {
          method: 'POST',
          headers: { 
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
          },
          body: JSON.stringify(payload)
        });

        const text = await res.text();
        let data = {};
        try {
          data = JSON.parse(text);
        } catch (err) {
          console.error('Non-JSON server response:', text);
          Swal.fire('Error Server', 'Respon server tidak valid: ' + text.substring(0, 150), 'error');
          return;
        }

        if (data.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'Registrasi Berhasil!',
            text: 'Akun Anda berhasil dibuat. Silakan login untuk melakukan reservasi antrean.',
            confirmButtonText: 'Masuk Sekarang',
            confirmButtonColor: '#0284c7'
          }).then(() => {
            window.location.href = 'login.php';
          });
        } else {
          Swal.fire('Gagal', data.message || 'Terjadi kesalahan saat registrasi', 'error');
        }
      } catch (err) {
        console.error('Fetch error:', err);
        Swal.fire('Error Jaringan', 'Gagal terhubung ke server: ' + (err.message || 'Koneksi terputus'), 'error');
      } finally {
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = '<span class="material-icons">how_to_reg</span><span>Daftar Akun Pengunjung</span>';
        }
      }
    });
  }
});
