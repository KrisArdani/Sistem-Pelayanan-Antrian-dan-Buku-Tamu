/**
 * TOBASA BPS Kota Tegal - Antrian Digital & QRCode Generator Handler
 */

document.addEventListener('DOMContentLoaded', () => {
  // Set min date input to today
  const dateInput = document.getElementById('ant_tanggal');
  if (dateInput) {
    const today = new Date().toISOString().split('T')[0];
    dateInput.min = today;
    dateInput.value = today;
  }

  // Webcam & Photo Handling
  // Smart Camera Handler: Standalone Live Webcam Modal
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
      const isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
      
      // On mobile devices, use native device camera directly
      if (isMobile && inputCamera) {
        inputCamera.click();
        return;
      }

      // On Laptop/PC, try opening live webcam modal stream with generic video constraint
      if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        try {
          // Plain { video: true } is maximum compatibility across all laptop webcams
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
            msg = 'Browser membatasi akses webcam pada jaringan HTTP IP. Silakan buka web via http://localhost/A/tobasa/antrian.php atau upload foto dari file.';
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

      // Fallback
      if (inputGallery) inputGallery.click();
    });
  }

  // Client-Side Image Auto-Compressor (Max 500px, JPEG Quality 0.75, ~30KB)
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
        // Compress large photo files from camera/gallery
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

  // Dynamic Weekend & Date Safeguard
  const antTanggalEl = document.getElementById('ant_tanggal');
  if (antTanggalEl) {
    const today = new Date().toISOString().split('T')[0];
    antTanggalEl.min = today;

    antTanggalEl.addEventListener('change', (e) => {
      const selected = new Date(e.target.value);
      const day = selected.getDay(); // 0 = Sun, 6 = Sat
      if (day === 0 || day === 6) {
        Swal.fire({
          icon: 'warning',
          title: 'Hari Libur (Weekend)',
          text: 'Pelayanan PST BPS Kota Tegal hanya tersedia pada hari kerja (Senin s.d. Jumat). Silakan pilih hari kerja.',
          confirmButtonColor: '#003366'
        });
        e.target.value = '';
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
          Swal.fire({
            icon: 'error',
            title: 'Gagal Mengambil Tiket',
            text: json.message || 'Terjadi kesalahan sistem saat membuat tiket antrian.',
            confirmButtonColor: '#003366'
          });
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
  const ticketContainer = document.getElementById('printable-ticket');
  const qrContainer = document.getElementById('qrcode_box');

  if (!ticketContainer || !qrContainer) return;

  const queueNo = ticket.nomor || 'KS-001';
  const serviceName = ticket.layanan || (document.getElementById('ant_layanan') ? document.getElementById('ant_layanan').value : 'Konsultasi Statistik');
  const ticketId = ticket.id || 'ANT-' + Date.now().toString().slice(-6);
  const personName = ticket.nama || (document.getElementById('ant_nama') ? document.getElementById('ant_nama').value : 'Pengunjung');
  const dateVal = ticket.tanggal || (document.getElementById('ant_tanggal') ? document.getElementById('ant_tanggal').value : '-');
  const timeVal = (ticket.waktu || (document.getElementById('ant_waktu') ? document.getElementById('ant_waktu').value : '-')) + ' WIB';
  const facilityVal = ticket.fasilitas || (document.getElementById('ant_fasilitas') ? document.getElementById('ant_fasilitas').value : 'Datang Langsung Ke PST BPS Kota Tegal');
  let rawPemanfaatan = ticket.pemanfaatan || '';
  const selPemanfaatan = document.getElementById('ant_pemanfaatan');
  if (!rawPemanfaatan && selPemanfaatan && selPemanfaatan.selectedIndex >= 0) {
    rawPemanfaatan = selPemanfaatan.options[selPemanfaatan.selectedIndex].text;
  }
  let pemanfaatanVal = rawPemanfaatan;
  if (pemanfaatanVal === 'Penelitian' || pemanfaatanVal === 'Penelitian/Skripsi/Tesis') pemanfaatanVal = 'Penelitian / Skripsi / Tesis';
  if (pemanfaatanVal === 'Pemerintah' || pemanfaatanVal === 'Perencanaan/Kebijakan Pemerintah') pemanfaatanVal = 'Perencanaan / Kebijakan Pemerintah';
  if (pemanfaatanVal === 'Komersial' || pemanfaatanVal === 'Komersial/Wirausaha' || pemanfaatanVal === 'Komersial/Usaha Bisnis') pemanfaatanVal = 'Komersial / Usaha Bisnis';
  if (pemanfaatanVal === 'Tugas Sekolah/Kuliah') pemanfaatanVal = 'Tugas Sekolah / Kuliah';
  if (!pemanfaatanVal || pemanfaatanVal === '-') pemanfaatanVal = 'Tugas Sekolah / Kuliah';
  const monevVal = ticket.monev || (document.getElementById('ant_monev') ? document.getElementById('ant_monev').value : 'Ya');
  const rincianVal = ticket.data_diinginkan || (document.getElementById('ant_data_diinginkan') ? document.getElementById('ant_data_diinginkan').value.trim() : '-') || '-';
  const phoneVal = ticket.nohp || (document.getElementById('p1_nohp') ? document.getElementById('p1_nohp').textContent : '-') || '-';
  const instansiVal = ticket.instansi || (document.getElementById('p1_instansi') ? document.getElementById('p1_instansi').textContent : '-') || '-';

  // 1. Fill Modal Ticket Fields
  const elNumber = document.getElementById('ticket_number');
  const elBadge = document.getElementById('ticket_service_badge');
  const elCodeId = document.getElementById('ticket_code_id');
  const elName = document.getElementById('ticket_name');
  const elService = document.getElementById('ticket_service');
  const elDate = document.getElementById('ticket_date');
  const elTime = document.getElementById('ticket_time');
  const elFacility = document.getElementById('ticket_facility');

  if (elNumber) elNumber.textContent = queueNo;
  if (elBadge) elBadge.textContent = serviceName;
  if (elCodeId) elCodeId.textContent = ticketId;
  if (elName) elName.textContent = personName;
  if (elService) elService.textContent = serviceName;
  if (elDate) elDate.textContent = dateVal;
  if (elTime) elTime.textContent = timeVal;
  if (elFacility) elFacility.textContent = facilityVal;

  // 2. Fill Dedicated 2-Page Print Layout Fields (PAGE 1 & PAGE 2)
  const p1Nama = document.getElementById('p1_nama');
  const p1NoHp = document.getElementById('p1_nohp');
  const p1Instansi = document.getElementById('p1_instansi');
  const p1Layanan = document.getElementById('p1_layanan');
  const p1Tanggal = document.getElementById('p1_tanggal');
  const p1Waktu = document.getElementById('p1_waktu');
  const p1Fasilitas = document.getElementById('p1_fasilitas');
  const p1Pemanfaatan = document.getElementById('p1_pemanfaatan');
  const p1Monev = document.getElementById('p1_monev');
  const p1Rincian = document.getElementById('p1_rincian_data');

  if (p1Nama) p1Nama.textContent = personName;
  if (p1NoHp && phoneVal) p1NoHp.textContent = phoneVal;
  if (p1Instansi && instansiVal) p1Instansi.textContent = instansiVal;
  if (p1Layanan) p1Layanan.textContent = serviceName;
  if (p1Tanggal) p1Tanggal.textContent = dateVal;
  if (p1Waktu) p1Waktu.textContent = timeVal;
  if (p1Fasilitas) p1Fasilitas.textContent = facilityVal;
  if (p1Pemanfaatan) p1Pemanfaatan.textContent = pemanfaatanVal;
  if (p1Monev) p1Monev.textContent = monevVal;
  if (p1Rincian) p1Rincian.textContent = rincianVal;

  const p2Number = document.getElementById('p2_ticket_number');
  const p2Service = document.getElementById('p2_ticket_service');
  const p2CodeId = document.getElementById('p2_ticket_code_id');
  const p2Name = document.getElementById('p2_ticket_name');
  const p2Date = document.getElementById('p2_ticket_date');
  const p2Time = document.getElementById('p2_ticket_time');
  const p2Facility = document.getElementById('p2_ticket_facility');

  if (p2Number) p2Number.textContent = queueNo;
  if (p2Service) p2Service.textContent = serviceName;
  if (p2CodeId) p2CodeId.textContent = ticketId;
  if (p2Name) p2Name.textContent = personName;
  if (p2Date) p2Date.textContent = dateVal;
  if (p2Time) p2Time.textContent = timeVal;
  if (p2Facility) p2Facility.textContent = facilityVal;

  // Clear & Generate QR Code for Modal and Page 2 Print Container
  qrContainer.innerHTML = '';
  const p2QrContainer = document.getElementById('p2_qrcode_box');
  if (p2QrContainer) p2QrContainer.innerHTML = '';

  const qrDataText = ticket.nomor ? `TOBASA-${ticket.nomor}` : ticketId;

  if (typeof QRCode !== 'undefined') {
    new QRCode(qrContainer, {
      text: qrDataText,
      width: 135,
      height: 135,
      colorDark: '#003366',
      colorLight: '#ffffff',
      correctLevel: QRCode.CorrectLevel.M
    });

    if (p2QrContainer) {
      new QRCode(p2QrContainer, {
        text: qrDataText,
        width: 125,
        height: 125,
        colorDark: '#003366',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.M
      });
    }
  } else {
    qrContainer.innerHTML = `<div class="p-2 border text-slate-700 text-xs font-mono">QR: ${qrDataText}</div>`;
    if (p2QrContainer) p2QrContainer.innerHTML = `<div class="p-2 border text-slate-700 text-xs font-mono">QR: ${qrDataText}</div>`;
  }

  // Show Modal Bootstrap
  if (typeof bootstrap !== 'undefined' && modalTicketElem) {
    let bsModal = bootstrap.Modal.getInstance(modalTicketElem);
    if (!bsModal) {
      bsModal = new bootstrap.Modal(modalTicketElem);
    }
    bsModal.show();
  }
}


