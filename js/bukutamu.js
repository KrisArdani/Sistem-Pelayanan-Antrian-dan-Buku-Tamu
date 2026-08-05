/**
 * SPST BPS Kota Tegal - Form Buku Tamu & Penangan Kamera WebRTC
 */

let mediaStream = null;
let capturedBase64Photo = '';

document.addEventListener('DOMContentLoaded', () => {
  // 1. Auto-fill Timestamp
  const timestampInput = document.getElementById('bt_timestamp');
  if (timestampInput) {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    timestampInput.value = now.toISOString().slice(0, 16);
  }

  // 2. Setup Camera vs File Upload Toggle
  const radioUpload = document.getElementById('radio_upload');
  const radioCamera = document.getElementById('radio_camera');
  const boxUpload = document.getElementById('box_upload_file');
  const boxCamera = document.getElementById('box_camera_stream');

  if (radioUpload && radioCamera) {
    radioUpload.addEventListener('change', () => {
      boxUpload.classList.remove('d-none');
      boxCamera.classList.add('d-none');
      stopCamera();
    });

    radioCamera.addEventListener('change', () => {
      boxUpload.classList.add('d-none');
      boxCamera.classList.remove('d-none');
      startCamera();
    });
  }

  // 3. WebRTC Camera Actions
  const btnCapture = document.getElementById('btn_capture_photo');
  const btnRetake = document.getElementById('btn_retake_photo');
  const videoElem = document.getElementById('camera_video');
  const canvasElem = document.getElementById('camera_canvas');
  const photoPreview = document.getElementById('camera_preview_img');

  if (btnCapture) {
    btnCapture.addEventListener('click', () => {
      if (!videoElem) return;
      const context = canvasElem.getContext('2d');
      canvasElem.width = videoElem.videoWidth || 320;
      canvasElem.height = videoElem.videoHeight || 240;
      context.drawImage(videoElem, 0, 0, canvasElem.width, canvasElem.height);
      
      capturedBase64Photo = canvasElem.toDataURL('image/jpeg');
      photoPreview.src = capturedBase64Photo;
      photoPreview.classList.remove('d-none');
      videoElem.classList.add('d-none');

      btnCapture.classList.add('d-none');
      btnRetake.classList.remove('d-none');
    });
  }

  if (btnRetake) {
    btnRetake.addEventListener('click', () => {
      capturedBase64Photo = '';
      photoPreview.classList.add('d-none');
      videoElem.classList.remove('d-none');

      btnCapture.classList.remove('d-none');
      btnRetake.classList.add('d-none');
    });
  }

  // 4. File Upload Input Handler
  const fileInput = document.getElementById('file_photo_input');
  if (fileInput) {
    fileInput.addEventListener('change', (e) => {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = (event) => {
          capturedBase64Photo = event.target.result;
        };
        reader.readAsDataURL(file);
      }
    });
  }

  // 5. Form Submit Handler
  const formBukuTamu = document.getElementById('formBukuTamu');
  if (formBukuTamu) {
    formBukuTamu.addEventListener('submit', async (e) => {
      e.preventDefault();

      // Collect values
      const timestamp = document.getElementById('bt_timestamp').value;
      const nama = document.getElementById('bt_nama').value.trim();
      const jk = document.getElementById('bt_jk').value;
      const umur = document.getElementById('bt_umur').value;
      const nohp = document.getElementById('bt_nohp').value.trim();
      const email = document.getElementById('bt_email').value.trim();
      const pendidikan = document.getElementById('bt_pendidikan').value;
      const pekerjaan = document.getElementById('bt_pekerjaan').value;
      const instansi = document.getElementById('bt_instansi').value.trim();
      const kategori_instansi = document.getElementById('bt_kategori_instansi').value;
      
      const fasilitas = document.getElementById('bt_fasilitas').value;
      const layanan = document.getElementById('bt_layanan').value;
      const pemanfaatan = document.getElementById('bt_pemanfaatan').value;
      const data_diinginkan = document.getElementById('bt_data_diinginkan').value.trim();
      const pendapat = document.getElementById('bt_pendapat').value;
      const is_monev = document.getElementById('bt_monev').value;
      const catatan = document.getElementById('bt_catatan').value.trim();

      // Validasi wajib
      if (!nama || jk === '' || umur === '' || !nohp || pendidikan === '' || pekerjaan === '' || !instansi || fasilitas === '' || layanan === '' || pemanfaatan === '' || pendapat === '' || is_monev === '') {
        Swal.fire({
          icon: 'warning',
          title: 'Form Belum Lengkap',
          text: 'Harap lengkapi seluruh kolom wajib yang bertanda bintang (*).',
          confirmButtonColor: '#003366'
        });
        return;
      }

      // Check photo size client-side if attached (Max 2MB)
      if (capturedBase64Photo && capturedBase64Photo.length > 2.8 * 1024 * 1024) {
        Swal.fire({
          icon: 'error',
          title: 'Foto Terlalu Besar',
          text: 'Ukuran foto maksimal adalah 2 MB.',
          confirmButtonColor: '#003366'
        });
        return;
      }

      // Save to MySQL via API
      try {
        const formData = new FormData();
        formData.append('timestamp', timestamp);
        formData.append('nama', nama);
        formData.append('jenis_kelamin', jk);
        formData.append('umur', umur);
        formData.append('nohp', nohp);
        formData.append('email', email);
        formData.append('pendidikan', pendidikan);
        formData.append('pekerjaan', pekerjaan);
        formData.append('instansi', instansi);
        formData.append('kategori_instansi', kategori_instansi);
        formData.append('fasilitas', fasilitas);
        formData.append('layanan', layanan);
        formData.append('pemanfaatan', pemanfaatan);
        formData.append('data_diinginkan', data_diinginkan);
        formData.append('foto', capturedBase64Photo);
        formData.append('pendapat', pendapat);
        formData.append('monev', is_monev);
        formData.append('catatan', catatan);
        formData.append('csrf_token', getCsrfToken());

        const res = await fetch('api.php?action=save_bukutamu', { method: 'POST', body: formData });
        const json = await res.json();

        if (json.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'Registrasi Berhasil!',
            html: `Terima kasih <b>${escapeHtml(nama)}</b>.<br>Data kunjungan Anda telah tersimpan di Database BPS Kota Tegal. Silakan menuju meja loket PST.`,
            confirmButtonColor: '#003366',
            confirmButtonText: 'Selesai'
          }).then(() => {
            formBukuTamu.reset();
            capturedBase64Photo = '';
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Gagal Menyimpan',
            text: json.message || 'Terjadi kesalahan saat menyimpan data.',
            confirmButtonColor: '#003366'
          });
        }
      } catch (err) {
        console.error('API save_bukutamu failed:', err);
        Swal.fire({
          icon: 'error',
          title: 'Kesalahan Jaringan',
          text: 'Gagal terhubung ke server. Silakan coba lagi.',
          confirmButtonColor: '#003366'
        });
      }
    });
  }

  // Update Visitor Counter On Load
  updateVisitorCounter();
});

// Camera Stream Controllers
async function startCamera() {
  const videoElem = document.getElementById('camera_video');
  try {
    mediaStream = await navigator.mediaDevices.getUserMedia({ video: { width: 640, height: 480 } });
    if (videoElem) {
      videoElem.srcObject = mediaStream;
    }
  } catch (err) {
    console.error("Gagal mengakses kamera:", err);
    Swal.fire({
      icon: 'error',
      title: 'Akses Kamera Gagal',
      text: 'Tidak dapat mengakses perangkat kamera. Silakan pilih opsi Upload File.',
      confirmButtonColor: '#003366'
    });
    document.getElementById('radio_upload').checked = true;
    document.getElementById('box_upload_file').classList.remove('d-none');
    document.getElementById('box_camera_stream').classList.add('d-none');
  }
}

function stopCamera() {
  if (mediaStream) {
    mediaStream.getTracks().forEach(track => track.stop());
    mediaStream = null;
  }
}

function updateVisitorCounter() {
  const counterElem = document.getElementById('visitor_counter_num');
  if (counterElem) {
    const data = getStorage(STORAGE_KEYS.BUKU_TAMU);
    counterElem.textContent = data.length;
  }
}
