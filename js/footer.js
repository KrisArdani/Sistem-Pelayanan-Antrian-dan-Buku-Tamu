// SPST BPS Kota Tegal - JavaScript Floating Feedback & Complaint Widget

function triggerWidgetFeedback(tipe) {
  const isPenilaian = tipe === 'penilaian';
  const title = isPenilaian ? 'Penilaian Kepuasan' : 'Form Pengaduan';
  const inputPlaceholder = isPenilaian 
    ? 'Sampaikan ulasan atau masukan Anda...' 
    : 'Jelaskan pengaduan atau keluhan Anda...';
  const confirmButtonText = isPenilaian ? 'Kirim Penilaian' : 'Kirim Pengaduan';

  Swal.fire({
    title: title,
    input: 'textarea',
    inputPlaceholder: inputPlaceholder,
    showCancelButton: true,
    confirmButtonText: confirmButtonText,
    confirmButtonColor: '#003366'
  }).then(res => {
    if (res.isConfirmed && res.value) {
      const fd = new FormData();
      fd.append('tipe', tipe);
      fd.append('pesan', res.value);
      if (typeof getCsrfToken === 'function') {
        fd.append('csrf_token', getCsrfToken());
      }
      fetch('api.php?action=save_widget_feedback', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(json => {
          if (json.status === 'success') {
            if (isPenilaian) {
              Swal.fire('Terima Kasih', 'Penilaian Anda telah tersimpan di Database.', 'success');
            } else {
              Swal.fire('Pengaduan Terkirim', 'Pengaduan Anda telah tercatat di Database.', 'info');
            }
          } else {
            Swal.fire('Gagal', json.message || 'Gagal menyimpan data.', 'error');
          }
        })
        .catch(() => {
          Swal.fire('Error', 'Gagal terhubung ke server.', 'error');
        });
    }
  });
}
