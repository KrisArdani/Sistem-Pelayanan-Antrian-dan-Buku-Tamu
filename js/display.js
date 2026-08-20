/**
 * SPST BPS Kota Tegal - TV Display Screen Real-Time 4-Loket Handler (Light Theme)
 */

let isAudioEnabled = true;
let isAudioUnlocked = false;
let lastCalledId = null;
let lastCalledAt = null;
let lastPanggilCount = null;
let isFirstFetch = true;

document.addEventListener('DOMContentLoaded', () => {
  // 1. Inisialisasi Jam Digital Real-Time
  updateDigitalClock();
  setInterval(updateDigitalClock, 1000);

  // 2. Muat Data Display Pertama Kali
  fetchDisplayData();

  // 3. Polling Real-Time setiap 2 detik
  setInterval(fetchDisplayData, 2000);

  // 4. Keyboard Shortcuts: F11 (Fullscreen), M / Space (Toggle Audio / Dismiss Overlay)
  document.addEventListener('keydown', (e) => {
    if (!isAudioUnlocked) {
      unlockAudio();
    }
    if (e.key === 'm' || e.key === 'M') {
      toggleAudio();
    }
  });

  // 5. Muat daftar suara browser
  if ('speechSynthesis' in window) {
    window.speechSynthesis.onvoiceschanged = () => {
      window.speechSynthesis.getVoices();
    };
  }
});

/**
 * Update Jam Digital & Tanggal Bahasa Indonesia
 */
function updateDigitalClock() {
  const now = new Date();
  const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
  const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

  const dayName = days[now.getDay()];
  const dateNum = String(now.getDate()).padStart(2, '0');
  const monthName = months[now.getMonth()];
  const year = now.getFullYear();

  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');
  const seconds = String(now.getSeconds()).padStart(2, '0');

  const clockTimeElem = document.getElementById('display_clock_time');
  const clockDateElem = document.getElementById('display_clock_date');

  if (clockTimeElem) {
    clockTimeElem.textContent = `${hours}:${minutes}:${seconds} WIB`;
  }
  if (clockDateElem) {
    clockDateElem.textContent = `${dayName}, ${dateNum} ${monthName} ${year}`;
  }
}

/**
 * Ambil Data Antrean Terbaru dari Endpoint Publik
 */
async function fetchDisplayData() {
  try {
    const res = await fetch(`api.php?action=get_display_antrian&_t=${Date.now()}`, {
      cache: 'no-store'
    });
    if (!res.ok) return;
    const json = await res.json();
    if (json.status !== 'success' || !json.data) return;

    renderDisplayUI(json.data);
  } catch (err) {
    console.error('Fetch display data error:', err);
  }
}

/**
 * Render Semua Komponen Tampilan Layar TV (4 Loket)
 */
function renderDisplayUI(data) {
  const activeCall = data.active_call;
  const nextQueue = data.next_queue;
  const counters = data.counters || [];
  const stats = data.stats || {};

  // -------------------------------------------------------------
  // A. UPDATE STATUS 4 LOKET PELAYANAN
  // -------------------------------------------------------------
  counters.forEach(counter => {
    const loketId = counter.loket_id;
    const cardEl = document.getElementById(`card_loket_${loketId}`);
    const numEl = document.getElementById(`number_loket_${loketId}`);
    const nameEl = document.getElementById(`name_loket_${loketId}`);
    const instansiEl = document.getElementById(`instansi_loket_${loketId}`);
    const badgeEl = document.getElementById(`badge_loket_${loketId}`);
    const callingBanner = document.getElementById(`calling_banner_${loketId}`);
    const waitEl = document.getElementById(`wait_loket_${loketId}`);
    const timeEl = document.getElementById(`time_loket_${loketId}`);
    const nextListEl = document.getElementById(`list_next_loket_${loketId}`);

    if (!cardEl || !numEl || !nameEl || !badgeEl) return;

    if (waitEl) {
      waitEl.textContent = `${counter.waiting_count} menunggu`;
    }

    if (counter.active_queue) {
      const q = counter.active_queue;
      numEl.textContent = q.nomor || '---';
      nameEl.textContent = q.nama || 'Pengunjung';
      if (instansiEl) instansiEl.textContent = q.instansi || counter.service;
      if (timeEl) timeEl.textContent = q.waktu ? `Jam ${q.waktu.substring(0, 5)} WIB` : '-';

      if (q.status === 'Dipanggil') {
        // SEDANG DIPANGGIL: Highlight dengan border emas berdenyut
        badgeEl.textContent = 'Dipanggil';
        badgeEl.className = 'px-3 py-1 bg-amber-400 text-slate-950 text-xs font-black rounded-full shadow animate-pulse';
        
        if (callingBanner) callingBanner.classList.remove('hidden');
        cardEl.classList.add('card-calling-active', 'ring-4', 'ring-amber-400', 'bg-amber-50/40');
      } else {
        // SEDANG DILAYANI
        badgeEl.textContent = 'Dilayani';
        badgeEl.className = 'px-3 py-1 bg-emerald-600 text-white text-xs font-bold rounded-full shadow-sm';
        
        if (callingBanner) callingBanner.classList.add('hidden');
        cardEl.classList.remove('card-calling-active', 'ring-4', 'ring-amber-400', 'bg-amber-50/40');
      }
    } else {
      // SIAP MELAYANI / KOSONG
      numEl.textContent = '---';
      nameEl.textContent = 'Siap Melayani';
      if (instansiEl) instansiEl.textContent = counter.service;
      if (timeEl) timeEl.textContent = '-';

      badgeEl.textContent = 'Siap';
      badgeEl.className = 'px-2.5 py-0.5 bg-slate-100 text-slate-600 text-[11px] font-bold rounded-full border border-slate-200';

      if (callingBanner) callingBanner.classList.add('hidden');
      cardEl.classList.remove('card-calling-active', 'ring-4', 'ring-amber-400', 'bg-amber-50/40');
    }

    // Render Daftar Antrean Berikutnya di Loket ini
    if (nextListEl) {
      const nextItems = counter.next_list || [];
      if (nextItems.length > 0) {
        let itemsHtml = '';
        nextItems.forEach((item, idx) => {
          const waktuStr = item.waktu ? item.waktu.substring(0, 5) + ' WIB' : '-';
          const badgeColorClass = getLoketTextColor(loketId);
          itemsHtml += `
            <div class="flex items-center justify-between p-1.5 px-2.5 bg-slate-50/90 hover:bg-slate-100/90 rounded-xl border border-slate-200/70 text-xs transition">
              <div class="flex items-center gap-2 min-w-0">
                <span class="w-4 h-4 rounded-md bg-slate-200 text-slate-700 text-[10px] font-black flex items-center justify-center shrink-0">${idx + 1}</span>
                <span class="font-black ${badgeColorClass} brand-font shrink-0">${escapeHtml(item.nomor)}</span>
                <span class="font-semibold text-slate-700 truncate max-w-[100px] md:max-w-[120px]">${escapeHtml(item.nama || 'Pengunjung')}</span>
              </div>
              <span class="text-[10px] font-bold text-slate-400 shrink-0">${waktuStr}</span>
            </div>
          `;
        });
        nextListEl.innerHTML = itemsHtml;
      } else {
        nextListEl.innerHTML = `
          <div class="text-center py-2 text-[11px] text-slate-400 font-medium italic bg-slate-50/70 rounded-xl border border-dashed border-slate-200">
            Tidak ada antrean menunggu
          </div>
        `;
      }
    }
  });

  // -------------------------------------------------------------
  // B. DETEKSI PANGGILAN SUARA BARU ATAU PANGGIL ULANG
  // -------------------------------------------------------------
  if (activeCall) {
    const callSignature = `${activeCall.id}_${activeCall.panggil_count || 1}_${activeCall.called_at || ''}`;
    const previousSignature = `${lastCalledId}_${lastPanggilCount || 1}_${lastCalledAt || ''}`;

    if (!isFirstFetch && callSignature !== previousSignature) {
      // Picu suara bel lonceng + pembacaan suara manusia
      if (isAudioEnabled && isAudioUnlocked) {
        const loketLabel = getLoketLabel(activeCall.layanan);
        speakQueueAnnouncement(activeCall.nomor, activeCall.nama, loketLabel);
      }
    }

    lastCalledId = activeCall.id;
    lastCalledAt = activeCall.called_at;
    lastPanggilCount = activeCall.panggil_count || 1;
  }

  isFirstFetch = false;

  // -------------------------------------------------------------
  // C. UPDATE AKAN DIPANGGIL BERIKUTNYA & REKAP STATISTIK
  // -------------------------------------------------------------
  const nextNum = document.getElementById('display_next_number');
  const nextName = document.getElementById('display_next_name');
  const nextTimeBadge = document.getElementById('badge_next_time');

  if (nextQueue) {
    if (nextNum) nextNum.textContent = nextQueue.nomor || '---';
    if (nextName) nextName.textContent = `${nextQueue.nama || 'Pengunjung'} • ${nextQueue.layanan || '-'}`;
    if (nextTimeBadge) {
      nextTimeBadge.textContent = `Jam ${nextQueue.waktu ? nextQueue.waktu.substring(0, 5) : '08:00'} WIB`;
    }
  } else {
    if (nextNum) nextNum.textContent = '---';
    if (nextName) nextName.textContent = 'Tidak ada antrean menunggu';
    if (nextTimeBadge) nextTimeBadge.textContent = 'Antrean Kosong';
  }

  const statWait = document.getElementById('stat_waiting_count');
  const statFin = document.getElementById('stat_finished_count');
  if (statWait) statWait.textContent = stats.waiting || 0;
  if (statFin) statFin.textContent = stats.finished || 0;
}

/**
 * Format Label Loket Berdasarkan Nama Layanan
 */
function getLoketLabel(layanan) {
  if (!layanan) return 'Loket Pelayanan PST';
  if (layanan.includes('Konsultasi')) return 'Loket 1 Konsultasi Statistik';
  if (layanan.includes('Perpustakaan')) return 'Loket 2 Pelayanan Perpustakaan';
  if (layanan.includes('Rekomendasi') || layanan.includes('ROMANTIK')) return 'Loket 3 Rekomendasi Kegiatan Statistik';
  if (layanan.includes('Pengaduan')) return 'Loket 4 Layanan Pengaduan dan Informasi';
  return 'Loket ' + layanan;
}

/**
 * Bel Lonceng SFX Chime (Web Audio API - Dual Harmony)
 */
function playChimeBell() {
  try {
    const AudioContext = window.AudioContext || window.webkitAudioContext;
    if (!AudioContext) return;
    const ctx = new AudioContext();

    // 3-tone harmonic chime: E5 (659.25Hz), G#5 (830.61Hz), B5 (987.77Hz)
    const tones = [
      { freq: 659.25, time: 0.0, dur: 0.6 },
      { freq: 830.61, time: 0.25, dur: 0.8 },
      { freq: 987.77, time: 0.55, dur: 1.2 }
    ];

    tones.forEach(t => {
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();

      osc.type = 'sine';
      osc.frequency.setValueAtTime(t.freq, ctx.currentTime + t.time);

      gain.gain.setValueAtTime(0, ctx.currentTime + t.time);
      gain.gain.linearRampToValueAtTime(0.3, ctx.currentTime + t.time + 0.05);
      gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + t.time + t.dur);

      osc.connect(gain);
      gain.connect(ctx.destination);

      osc.start(ctx.currentTime + t.time);
      osc.stop(ctx.currentTime + t.time + t.dur);
    });
  } catch (err) {
    console.warn('Web Audio chime error:', err);
  }
}

/**
 * Pemanggilan Suara Manusia Indonesia (Text-to-Speech)
 */
function speakQueueAnnouncement(nomor, nama, loketLabel) {
  if (!isAudioEnabled || !isAudioUnlocked) return;
  if (!('speechSynthesis' in window)) return;

  // 1. Mainkan bel lonceng
  playChimeBell();

  // 2. Format nomor antrean
  const digitWords = {
    '0': 'nol', '1': 'satu', '2': 'dua', '3': 'tiga', '4': 'empat',
    '5': 'lima', '6': 'enam', '7': 'tujuh', '8': 'delapan', '9': 'sembilan'
  };

  let formattedNomor = '';
  if (nomor && nomor.includes('-')) {
    const parts = nomor.split('-');
    const prefixSpoken = parts[0].split('').join(' ');
    const digitsSpoken = parts[1].split('').map(d => digitWords[d] || d).join(' ');
    formattedNomor = `${prefixSpoken} , ${digitsSpoken}`;
  } else {
    formattedNomor = (nomor || '').split('').map(d => digitWords[d] || d).join(' ');
  }

  const namaPengunjung = (nama && nama.length > 1) ? nama : 'Pengunjung';
  const targetLoket = loketLabel || 'Loket Pelayanan Statistik Terpadu';

  const textToSpeak = `Nomor antrean ${formattedNomor}, atas nama ${namaPengunjung}, silakan menuju ke ${targetLoket}.`;

  // Delay 1.4 detik agar nada bel selesai berbunyi
  setTimeout(() => {
    try {
      window.speechSynthesis.cancel();
      const utterance = new SpeechSynthesisUtterance(textToSpeak);
      utterance.lang = 'id-ID';
      utterance.rate = 0.86;
      utterance.pitch = 1.0;

      const voices = window.speechSynthesis.getVoices();
      const idVoice = voices.find(v => v.lang.includes('id') || v.lang.includes('ID') || v.name.toLowerCase().includes('indonesia'));
      if (idVoice) utterance.voice = idVoice;

      window.speechSynthesis.speak(utterance);
    } catch (e) {
      console.warn('SpeechSynthesis error:', e);
    }
  }, 1400);
}

/**
 * Buka Kunci Audio (User Gesture Unlock Policy)
 */
function unlockAudio() {
  isAudioUnlocked = true;
  const overlay = document.getElementById('audio_unlock_overlay');
  if (overlay) {
    overlay.classList.add('opacity-0', 'pointer-events-none');
    setTimeout(() => overlay.remove(), 400);
  }

  try {
    const AudioContext = window.AudioContext || window.webkitAudioContext;
    if (AudioContext) {
      const ctx = new AudioContext();
      if (ctx.state === 'suspended') ctx.resume();
    }
  } catch (e) {}

  playChimeBell();
  updateAudioButtonUI();
}

/**
 * Toggle Audio ON/OFF
 */
function toggleAudio() {
  isAudioEnabled = !isAudioEnabled;
  updateAudioButtonUI();
}

function updateAudioButtonUI() {
  const icon = document.getElementById('icon_audio_status');
  const text = document.getElementById('text_audio_status');
  const btn = document.getElementById('btn_toggle_audio');

  if (!btn) return;

  if (isAudioEnabled && isAudioUnlocked) {
    if (icon) icon.textContent = 'volume_up';
    if (text) text.textContent = 'Audio Aktif';
    btn.className = 'p-3 rounded-2xl bg-sky-50 hover:bg-sky-100 border border-sky-300 text-sky-800 transition shadow-sm flex items-center gap-1.5 text-xs font-bold';
  } else if (!isAudioUnlocked) {
    if (icon) icon.textContent = 'volume_mute';
    if (text) text.textContent = 'Klik untuk Aktifkan Suara';
    btn.className = 'p-3 rounded-2xl bg-amber-100 hover:bg-amber-200 border border-amber-400 text-amber-900 transition shadow-sm flex items-center gap-1.5 text-xs font-bold animate-pulse';
  } else {
    if (icon) icon.textContent = 'volume_off';
    if (text) text.textContent = 'Audio Dibisukan';
    btn.className = 'p-3 rounded-2xl bg-slate-100 hover:bg-slate-200 border border-slate-300 text-slate-500 transition shadow-sm flex items-center gap-1.5 text-xs font-bold';
  }
}

/**
 * Layar Penuh (Toggle Fullscreen Mode)
 */
function toggleFullscreen() {
  if (!document.fullscreenElement) {
    document.documentElement.requestFullscreen().catch(err => {
      console.warn('Fullscreen request error:', err);
    });
  } else {
    if (document.exitFullscreen) {
      document.exitFullscreen();
    }
  }
}

/**
 * Helper: Ambil Warna Teks Identitas Loket
 */
function getLoketTextColor(loketId) {
  if (loketId == 1) return 'text-sky-700';
  if (loketId == 2) return 'text-emerald-700';
  if (loketId == 3) return 'text-amber-700';
  if (loketId == 4) return 'text-rose-700';
  return 'text-sky-700';
}

/**
 * Helper: Sanitasi Karakter HTML untuk Keamanan XSS
 */
function escapeHtml(str) {
  if (!str) return '';
  return String(str).replace(/[&<>"']/g, function(m) {
    return {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    }[m];
  });
}

