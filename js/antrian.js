/**
 * SPST BPS Kota Tegal - Penangan Antrean Digital & Generator Kode QR
 */

function getLocalTodayDate() {
  const d = new Date();
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

document.addEventListener('DOMContentLoaded', () => {
  // Setel tanggal minimal ke hari ini (Zona Waktu Lokal)
  const dateInput = document.getElementById('ant_tanggal');
  if (dateInput) {
    const today = getLocalTodayDate();
    dateInput.min = today;
    if (!dateInput.value) dateInput.value = today;
  }

  // Penanganan Webcam & Foto
  // Penangan Kamera Pintar: Modal Stream Kamera Langsung Mandiri
  const btnTriggerCam = document.getElementById('btn_trigger_camera');
  const inputCamera = document.getElementById('input_camera_snap');
  const inputGallery = document.getElementById('input_gallery_file');
  const hiddenFoto = document.getElementById('ant_foto');
  const photoPreview = document.getElementById('photo_preview');
  const photoIcon = document.getElementById('photo_icon_placeholder');

  const modalWebcamEl = document.getElementById('modalWebcam');
  const modalVideo = document.getElementById('modal_webcam_video');
  const modalCanvas = document.getElementById('modal_webcam_canvas');
  const btnModalSnap = document.getElementById('btn_modal_snap');
  const btnCloseWebcam = document.getElementById('btn_close_webcam_modal');
  let modalStream = null;

  function openWebcamModal() {
    if (!modalWebcamEl) return;
    modalWebcamEl.style.display = 'block';
    modalWebcamEl.classList.add('show');
    document.body.classList.add('modal-open');

    if (!document.getElementById('webcam_backdrop')) {
      const backdrop = document.createElement('div');
      backdrop.id = 'webcam_backdrop';
      backdrop.className = 'modal-backdrop fade show';
      document.body.appendChild(backdrop);
    }
  }

  function closeWebcamModal() {
    if (modalStream) {
      modalStream.getTracks().forEach(track => track.stop());
      modalStream = null;
    }
    if (modalVideo) {
      modalVideo.srcObject = null;
    }
    if (modalWebcamEl) {
      modalWebcamEl.style.display = 'none';
      modalWebcamEl.classList.remove('show');
    }
    document.body.classList.remove('modal-open');
    const backdrop = document.getElementById('webcam_backdrop');
    if (backdrop) backdrop.remove();
  }

  if (btnCloseWebcam) {
    btnCloseWebcam.addEventListener('click', closeWebcamModal);
  }

  if (modalWebcamEl) {
    const cancelBtns = modalWebcamEl.querySelectorAll('[data-bs-dismiss="modal"]');
    cancelBtns.forEach(btn => btn.addEventListener('click', closeWebcamModal));
  }

  if (btnTriggerCam) {
    btnTriggerCam.addEventListener('click', async () => {
      // Pada perangkat HP/Mobile, gunakan kamera perangkat langsung
      if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
        if (inputCamera) inputCamera.click();
        return;
      }

      // Pada Laptop/PC, buka stream modal webcam langsung dengan batasan video standar
      if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        try {
          // Konfigurasi { video: true } memiliki kompatibilitas maksimal untuk webcam laptop
          modalStream = await navigator.mediaDevices.getUserMedia({ video: true });
          if (modalVideo) {
            modalVideo.srcObject = modalStream;
            await modalVideo.play();
          }
          openWebcamModal();
          return;
        } catch (err) {
          console.warn('Webcam stream error:', err);
          
          let title = 'Izin Kamera Dibutuhkan';
          let msg = 'Silakan izinkan akses kamera pada browser Anda (klik ikon gembok/setelan di bilah URL), atau gunakan opsi upload file.';
          
          if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
            title = 'Koneksi HTTP Dibatasi';
            msg = 'Browser membatasi akses webcam pada jaringan HTTP IP. Silakan buka web via http://localhost/A/spst/antrian.php atau upload foto dari file.';
          }

          Swal.fire({
            icon: 'info',
            title: title,
            text: msg,
            confirmButtonText: 'Pilih File Foto',
            confirmButtonColor: '#0284c7'
          }).then(() => {
            if (inputGallery) inputGallery.click();
          });
          return;
        }
      }

      // Alternatif Cadangan (Fallback)
      if (inputCamera) inputCamera.click();
    });
  }

  // Kompresor Gambar Otomatis Sisi Klien (Maks 500px, Kualitas JPEG 0.75, ~30KB)
  function compressImage(source, maxWidth = 500, maxHeight = 500, quality = 0.75) {
    return new Promise((resolve) => {
      const img = new Image();
      img.crossOrigin = 'Anonymous';
      img.onload = () => {
        let width = img.width;
        let height = img.height;

        if (width > height) {
          if (width > maxWidth) {
            height = Math.round((height * maxWidth) / width);
            width = maxWidth;
          }
        } else {
          if (height > maxHeight) {
            width = Math.round((width * maxHeight) / height);
            height = maxHeight;
          }
        }

        const compCanvas = document.createElement('canvas');
        compCanvas.width = width;
        compCanvas.height = height;
        const ctx = compCanvas.getContext('2d');
        ctx.drawImage(img, 0, 0, width, height);
        resolve(compCanvas.toDataURL('image/jpeg', quality));
      };
      img.onerror = () => resolve(source);
      img.src = source;
    });
  }

  if (btnModalSnap) {
    btnModalSnap.addEventListener('click', async () => {
      if (!modalVideo || !modalCanvas) return;
      const w = modalVideo.videoWidth || 640;
      const h = modalVideo.videoHeight || 480;
      modalCanvas.width = w;
      modalCanvas.height = h;

      const ctx = modalCanvas.getContext('2d');
      ctx.drawImage(modalVideo, 0, 0, w, h);
      const rawBase64 = modalCanvas.toDataURL('image/jpeg', 0.9);

      // Compress to lightweight 500px image
      const compressedBase64 = await compressImage(rawBase64, 500, 500, 0.75);

      if (hiddenFoto) hiddenFoto.value = compressedBase64;
      if (photoPreview) {
        photoPreview.src = compressedBase64;
        photoPreview.classList.remove('hidden');
      }
      if (photoIcon) photoIcon.classList.add('hidden');

      closeWebcamModal();
    });
  }

  const handlePhotoSelect = (e) => {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = async (evt) => {
        const rawBase64 = evt.target.result;
        // Kompres file foto berukuran besar dari kamera/galeri
        const compressedBase64 = await compressImage(rawBase64, 500, 500, 0.75);

        if (hiddenFoto) hiddenFoto.value = compressedBase64;
        if (photoPreview) {
          photoPreview.src = compressedBase64;
          photoPreview.classList.remove('hidden');
        }
        if (photoIcon) photoIcon.classList.add('hidden');
      };
      reader.readAsDataURL(file);
    }
  };

  if (inputCamera) inputCamera.addEventListener('change', handlePhotoSelect);
  if (inputGallery) inputGallery.addEventListener('change', handlePhotoSelect);

  // Perlindungan Validasi Tanggal & Akhir Pekan Dinamis
  if (dateInput) {
    dateInput.addEventListener('change', () => {
      const selectedDate = new Date(dateInput.value);
      const day = selectedDate.getUTCDay(); // 0: Minggu, 6: Sabtu

      // Periksa Hari Libur Akhir Pekan (Sabtu / Minggu)
      if (day === 0 || day === 6) {
        Swal.fire({
          icon: 'warning',
          title: 'Hari Libur Pelayanan',
          text: 'PST BPS Kota Tegal hanya melayani konsultasi pada hari kerja (Senin s.d. Jumat). Silakan pilih tanggal lain.',
          confirmButtonColor: '#003366'
        });
        const today = getLocalTodayDate();
        dateInput.value = today;
      }
    });
  }

  const formAntrian = document.getElementById('formAntrian');
  if (formAntrian) {
    formAntrian.addEventListener('submit', async (e) => {
      e.preventDefault();

      const nama = document.getElementById('ant_nama') ? document.getElementById('ant_nama').value.trim() : '';
      const tanggal = document.getElementById('ant_tanggal').value;
      const waktu = document.getElementById('ant_waktu').value;
      const layanan = document.getElementById('ant_layanan').value;
      const fasilitas = document.getElementById('ant_fasilitas') ? document.getElementById('ant_fasilitas').value : 'Datang Langsung Ke PST BPS Kota Tegal';
      const pemanfaatan = document.getElementById('ant_pemanfaatan') ? document.getElementById('ant_pemanfaatan').value : '';
      const monev = document.getElementById('ant_monev') ? document.getElementById('ant_monev').value : 'Ya';
      const data_diinginkan = document.getElementById('ant_data_diinginkan') ? document.getElementById('ant_data_diinginkan').value.trim() : '';
      const foto = hiddenFoto ? hiddenFoto.value : '';

      if (!tanggal || !waktu || layanan === '') {
        Swal.fire({
          icon: 'warning',
          title: 'Form Belum Lengkap',
          text: 'Harap lengkapi semua kolom antrean yang tersedia.',
          confirmButtonColor: '#003366'
        });
        return;
      }

      // Check Weekend (Sat/Sun)
      const selectedDate = new Date(tanggal);
      const dayOfWeek = selectedDate.getDay();
      if (dayOfWeek === 0 || dayOfWeek === 6) {
        Swal.fire({
          icon: 'warning',
          title: 'Hari Libur (Weekend)',
          text: 'Reservasi antrean hanya tersedia pada hari kerja (Senin s.d. Jumat).',
          confirmButtonColor: '#003366'
        });
        return;
      }

      // Check Working Hours (08:00 - 15:30)
      if (waktu < '08:00' || waktu > '15:30') {
        Swal.fire({
          icon: 'warning',
          title: 'Di Luar Jam Kerja',
          text: 'Jam pelayanan PST BPS Kota Tegal hanya tersedia dari pukul 08:00 hingga 15:30 WIB.',
          confirmButtonColor: '#003366'
        });
        return;
      }

      let newTicket = null;

      try {
        const formData = new FormData();
        formData.append('nama', nama);
        formData.append('tanggal', tanggal);
        formData.append('waktu', waktu);
        formData.append('layanan', layanan);
        formData.append('fasilitas', fasilitas);
        formData.append('pemanfaatan', pemanfaatan);
        formData.append('monev', monev);
        formData.append('data_diinginkan', data_diinginkan);
        formData.append('foto', foto);
        formData.append('csrf_token', getCsrfToken());

        const res = await fetch('api.php?action=save_antrian', { method: 'POST', body: formData });
        const text = await res.text();
        let json = {};
        try {
          json = JSON.parse(text);
        } catch (e) {
          console.error('Non-JSON response save_antrian:', text);
          Swal.fire({
            icon: 'error',
            title: 'Respon Server Tidak Valid',
            text: 'Server mengembalikan kesalahan: ' + text.substring(0, 150),
            confirmButtonColor: '#003366'
          });
          return;
        }

        if (json.status === 'success') {
          newTicket = json.data;
          renderDigitalTicket(newTicket);
        } else {
          if (json.data && json.data.active_queue) {
            Swal.fire({
              icon: 'warning',
              title: 'Antrean Masih Aktif',
              html: `<p class="text-xs text-slate-600 leading-relaxed mb-3">${escapeHtml(json.message)}</p>`,
              showCancelButton: true,
              confirmButtonText: 'Ke Riwayat Tiket Saya',
              cancelButtonText: 'Tutup',
              confirmButtonColor: '#003366'
            }).then((res) => {
              if (res.isConfirmed) {
                window.location.href = 'bukutamu.php';
              }
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Gagal Mengambil Tiket',
              text: json.message || 'Terjadi kesalahan sistem saat membuat tiket antrian.',
              confirmButtonColor: '#003366'
            });
          }
        }
      } catch (err) {
        console.error('API save_antrian failed:', err);
        Swal.fire({
          icon: 'error',
          title: 'Kesalahan Jaringan',
          text: 'Gagal terhubung ke server antrian: ' + (err.message || 'Koneksi terputus'),
          confirmButtonColor: '#003366'
        });
      }
    });
  }

  const btnPrint = document.getElementById('btn_print_ticket');
  if (btnPrint) {
    btnPrint.addEventListener('click', () => {
      window.print();
    });
  }
});

function renderDigitalTicket(ticket) {
  const modalTicketElem = document.getElementById('modalTicket');
  const qrContainer = document.getElementById('qrcode_box');
  const ticketId = ticket.id || 'ANT-' + Date.now().toString().slice(-6);

  if (!qrContainer) return;

  // 1. Isi Bidang Tiket Modal
  const numElem = document.getElementById('ticket_number');
  const nameElem = document.getElementById('ticket_name');
  const serviceElem = document.getElementById('ticket_service');
  const dateElem = document.getElementById('ticket_date');
  const timeElem = document.getElementById('ticket_time');

  if (numElem) numElem.textContent = ticket.nomor || ticketId;
  if (nameElem) nameElem.textContent = ticket.nama || '-';
  if (serviceElem) serviceElem.textContent = ticket.layanan || '-';
  if (dateElem) dateElem.textContent = ticket.tanggal || '-';
  if (timeElem) timeElem.textContent = ticket.waktu ? `${ticket.waktu} WIB` : '-';

  // 2. Isi Bidang Tata Letak Cetak 2 Halaman Khusus (HALAMAN 1 & HALAMAN 2)
  const p1Nama = document.getElementById('p1_nama');
  const p1Nik = document.getElementById('p1_nik');
  const p1NoHp = document.getElementById('p1_nohp');
  const p1Instansi = document.getElementById('p1_instansi');
  const p1Layanan = document.getElementById('p1_layanan');
  const p1Tanggal = document.getElementById('p1_tanggal');
  const p1Waktu = document.getElementById('p1_waktu');
  const p1Fasilitas = document.getElementById('p1_fasilitas');
  const p1Pemanfaatan = document.getElementById('p1_pemanfaatan');
  const p1Monev = document.getElementById('p1_monev');
  const p1RincianData = document.getElementById('p1_rincian_data');

  if (p1Nama) p1Nama.textContent = ticket.nama || '-';
  if (p1Nik) p1Nik.textContent = ticket.nik || '-';
  if (p1NoHp) p1NoHp.textContent = ticket.nohp || '-';
  if (p1Instansi) p1Instansi.textContent = ticket.instansi || '-';
  if (p1Layanan) p1Layanan.textContent = ticket.layanan || '-';
  if (p1Tanggal) p1Tanggal.textContent = ticket.tanggal || '-';
  if (p1Waktu) p1Waktu.textContent = ticket.waktu ? `${ticket.waktu} WIB` : '-';
  if (p1Fasilitas) p1Fasilitas.textContent = ticket.fasilitas || 'Datang Langsung Ke PST BPS Kota Tegal';
  if (p1Pemanfaatan) p1Pemanfaatan.textContent = ticket.pemanfaatan || '-';
  if (p1Monev) p1Monev.textContent = ticket.monev || 'Ya';
  if (p1RincianData) p1RincianData.textContent = ticket.data_diinginkan || '-';

  // Halaman 2 Bidang Tiket
  const p2Nomor = document.getElementById('p2_ticket_number');
  const p2Nama = document.getElementById('p2_nama');
  const p2Layanan = document.getElementById('p2_layanan');
  const p2Tanggal = document.getElementById('p2_tanggal');
  const p2Waktu = document.getElementById('p2_waktu');
  const p2Facility = document.getElementById('p2_fasilitas');

  const facilityVal = ticket.fasilitas || 'Datang Langsung Ke PST BPS Kota Tegal';
  if (p2Nomor) p2Nomor.textContent = ticket.nomor || ticketId;
  if (p2Nama) p2Nama.textContent = ticket.nama || '-';
  if (p2Layanan) p2Layanan.textContent = ticket.layanan || '-';
  if (p2Tanggal) p2Tanggal.textContent = ticket.tanggal || '-';
  if (p2Waktu) p2Waktu.textContent = ticket.waktu ? `${ticket.waktu} WIB` : '-';
  if (p2Facility) p2Facility.textContent = facilityVal;

  // Bersihkan & Buat Kode QR untuk Modal dan Wadah Cetak Halaman 2
  qrContainer.innerHTML = '';
  const p2QrContainer = document.getElementById('p2_qrcode_box');
  if (p2QrContainer) p2QrContainer.innerHTML = '';

  const qrDataText = ticket.nomor ? `SPST-${ticket.nomor}` : ticketId;

  if (typeof QRCode !== 'undefined') {
    new QRCode(qrContainer, {
      text: qrDataText,
      width: 135,
      height: 135,
      colorDark: '#003366',
      colorLight: '#ffffff',
      correctLevel: QRCode.CorrectLevel.H
    });

    if (p2QrContainer) {
      new QRCode(p2QrContainer, {
        text: qrDataText,
        width: 140,
        height: 140,
        colorDark: '#003366',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.H
      });
    }
  } else {
    qrContainer.innerHTML = `<div class="p-2 border text-slate-700 text-xs font-mono">QR: ${qrDataText}</div>`;
    if (p2QrContainer) p2QrContainer.innerHTML = `<div class="p-2 border text-slate-700 text-xs font-mono">QR: ${qrDataText}</div>`;
  }

  if (typeof bootstrap !== 'undefined' && modalTicketElem) {
    let bsModal = bootstrap.Modal.getInstance(modalTicketElem);
    if (!bsModal) {
      bsModal = new bootstrap.Modal(modalTicketElem);
    }
    bsModal.show();
  }
}


