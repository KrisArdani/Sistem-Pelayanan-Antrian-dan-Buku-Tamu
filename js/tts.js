/**
 * SPST BPS Kota Tegal - Modul Aksesibilitas Text To Speech
 */

let ttsActive = false;
let currentUtterance = null;

function initTTS() {
  const ttsBtn = document.getElementById('btn-tts-toggle');
  if (!ttsBtn) return;

  ttsBtn.addEventListener('click', () => {
    ttsActive = !ttsActive;
    if (ttsActive) {
      ttsBtn.classList.remove('btn-secondary');
      ttsBtn.classList.add('btn-warning');
      ttsBtn.querySelector('.material-icons').textContent = 'volume_up';
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'info',
          title: 'Mode Pembacaan Suara Aktif',
          text: 'Klik teks apa saja untuk mendengarkan pembacaan halaman.',
          showConfirmButton: false,
          timer: 3000
        });
      }
    } else {
      ttsBtn.classList.remove('btn-warning');
      ttsBtn.classList.add('btn-secondary');
      ttsBtn.querySelector('.material-icons').textContent = 'volume_off';
      if ('speechSynthesis' in window) {
        window.speechSynthesis.cancel();
      }
    }
  });

  document.body.addEventListener('click', (e) => {
    if (!ttsActive) return;

    // Jangan tangkap jika elemen berupa tombol submit/input form
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'BUTTON') {
      return;
    }

    const textContent = e.target.innerText || e.target.textContent;
    if (textContent && textContent.trim().length > 0) {
      speakText(textContent.trim());
      e.target.classList.add('tts-highlight');
      setTimeout(() => e.target.classList.remove('tts-highlight'), 1000);
    }
  });
}

function speakText(text) {
  if (!('speechSynthesis' in window)) {
    console.warn('Speech synthesis tidak didukung oleh browser ini.');
    return;
  }

  window.speechSynthesis.cancel(); // Hentikan ucapan sebelumnya

  const utterance = new SpeechSynthesisUtterance(text);
  utterance.lang = 'id-ID';
  utterance.rate = 1.0;
  utterance.pitch = 1.0;

  window.speechSynthesis.speak(utterance);
}

document.addEventListener('DOMContentLoaded', initTTS);
