// SPST BPS Kota Tegal - Stepper Bar Real-time Polling & Interactions

let lastStepperTicketStatus = (window.stepperConfig && window.stepperConfig.lastTicketStatus) || '';
let lastStepperTicketId = (window.stepperConfig && window.stepperConfig.lastTicketId) || 0;

function startNewQueueFlow() {
  sessionStorage.setItem('stepper_user_reset_new', '1');
  
  // Hide stage 5 banner
  const banner = document.getElementById('completed-stage5-banner');
  if (banner) banner.classList.add('hidden');

  // Update badge
  const stageNum = document.getElementById('current-stage-num');
  if (stageNum) stageNum.innerText = '3';

  // Render Step 3 as Active (Pesan Sekarang)
  const step3Box = document.getElementById('step-card-3');
  if (step3Box) {
    step3Box.innerHTML = `
      <a href="antrian.php" class="group bg-amber-100 p-3.5 rounded-2xl border-2 border-amber-500 shadow-xl transition-all duration-300 flex flex-col items-center text-center space-y-2 relative ring-4 ring-amber-400/40 hover:scale-105">
        <div class="w-10 h-10 rounded-full bg-amber-500 text-white font-black text-sm flex items-center justify-center shadow-md shadow-amber-500/40 group-hover:scale-110 transition animate-pulse">3</div>
        <div>
          <div class="text-xs font-black text-amber-950">3. Pesan Antrean</div>
          <div class="text-[10px] font-bold text-amber-800 mt-0.5">Langkah Saat Ini</div>
        </div>
        <div class="inline-flex items-center gap-1 text-[10px] font-extrabold text-white bg-amber-600 px-2.5 py-0.5 rounded-full mt-1 shadow">
          <span>Pesan Sekarang</span>
          <span class="material-icons text-xs">arrow_forward</span>
        </div>
        <div class="hidden lg:flex absolute -right-4 top-1/2 -translate-y-1/2 z-20 w-8 h-8 rounded-full bg-amber-500 text-white shadow-lg items-center justify-center border-2 border-white font-extrabold">
          <span class="material-icons text-sm">east</span>
        </div>
      </a>`;
  }

  // Render Step 4 as Locked
  const step4Box = document.getElementById('step-card-4');
  if (step4Box) {
    step4Box.innerHTML = `
      <div class="bg-slate-100 p-3.5 rounded-2xl border-2 border-slate-300 shadow-sm flex flex-col items-center text-center space-y-2 relative pointer-events-none select-none">
        <div class="w-10 h-10 rounded-full bg-slate-400 text-white font-bold text-sm flex items-center justify-center">
          <span class="material-icons text-base">lock</span>
        </div>
        <div>
          <div class="text-xs font-bold text-slate-700">4. Tiket Digital QR</div>
          <div class="text-[10px] font-medium text-slate-500 mt-0.5">Belum Ada Tiket</div>
        </div>
        <div class="inline-flex items-center gap-1 text-[10px] font-bold text-slate-600 bg-slate-200 px-2 py-0.5 rounded-full mt-1 border border-slate-300">
          <span>🔒 Terkunci</span>
        </div>
        <div class="hidden lg:flex absolute -right-4 top-1/2 -translate-y-1/2 z-20 w-8 h-8 rounded-full bg-slate-400 text-white shadow items-center justify-center border-2 border-white font-bold">
          <span class="material-icons text-sm">east</span>
        </div>
      </div>`;
  }

  // Render Step 5 as Locked
  const step5Box = document.getElementById('step-card-5');
  if (step5Box) {
    step5Box.innerHTML = `
      <div class="bg-slate-100 p-3.5 rounded-2xl border-2 border-slate-300 shadow-sm flex flex-col items-center text-center space-y-2 relative pointer-events-none select-none">
        <div class="w-10 h-10 rounded-full bg-slate-400 text-white font-bold text-sm flex items-center justify-center">
          <span class="material-icons text-base">lock</span>
        </div>
        <div>
          <div class="text-xs font-bold text-slate-700">5. Datang Ke Loket</div>
          <div class="text-[10px] font-medium text-slate-500 mt-0.5">Belum Antre</div>
        </div>
        <div class="inline-flex items-center gap-1 text-[10px] font-bold text-slate-600 bg-slate-200 px-2 py-0.5 rounded-full mt-1 border border-slate-300">
          <span>🔒 Terkunci</span>
        </div>
      </div>`;
  }

  // If on antrian.php, smooth scroll to reservation form
  const antrianForm = document.getElementById('formAntrian');
  if (antrianForm) {
    antrianForm.scrollIntoView({ behavior: 'smooth' });
  } else {
    window.location.href = 'antrian.php';
  }
}

// Check session reset on page load
if (sessionStorage.getItem('stepper_user_reset_new') === '1') {
  const hasActive = (window.stepperConfig && window.stepperConfig.hasActiveTicket) || false;
  if (!hasActive) {
    document.addEventListener('DOMContentLoaded', () => {
      startNewQueueFlow();
    });
  } else {
    sessionStorage.removeItem('stepper_user_reset_new');
  }
}

function selectStepperSKMCard(el) {
  const radio = el.querySelector('input[type="radio"]');
  if (radio) radio.checked = true;

  document.querySelectorAll('.stepper-skm-card').forEach(card => {
    const input = card.querySelector('input[type="radio"]');
    const badge = card.querySelector('.stepper-skm-badge');
    const emoji = card.querySelector('.stepper-skm-emoji');
    const label = card.querySelector('.stepper-skm-label');

    if (emoji) {
      emoji.className = 'stepper-skm-emoji text-3xl mb-1 transition-all duration-200 inline-block';
    }

    if (input && input.checked) {
      card.className = 'stepper-skm-card cursor-pointer border-2 border-amber-500 bg-amber-100/90 shadow-md ring-2 ring-amber-500/30 scale-105 rounded-2xl p-3.5 text-center transition-all duration-200 relative overflow-hidden select-none';
      if (badge) badge.classList.remove('hidden');
      if (label) label.className = 'text-xs font-extrabold text-amber-950 stepper-skm-label';

      if (emoji) {
        const val = input.value;
        if (val === 'Sangat Puas') emoji.classList.add('anim-heartbeat');
        else if (val === 'Puas') emoji.classList.add('anim-nod');
        else if (val === 'Cukup Puas') emoji.classList.add('anim-sway');
        else if (val === 'Tidak Puas') emoji.classList.add('anim-shake');
        else emoji.classList.add('anim-heartbeat');
      }
    } else {
      card.className = 'stepper-skm-card cursor-pointer border-2 border-slate-200 rounded-2xl p-3.5 text-center transition-all duration-200 relative overflow-hidden select-none hover:border-amber-400 hover:bg-amber-50/50';
      if (badge) badge.classList.add('hidden');
      if (label) label.className = 'text-xs font-bold text-slate-700 stepper-skm-label';
    }
  });
}

function openSKMModalFromStepper(id, currentPendapat, currentCatatan) {
  const modalLoketEl = document.getElementById('modalPetunjukLoket');
  if (modalLoketEl) {
    const bsLoket = bootstrap.Modal.getInstance(modalLoketEl);
    if (bsLoket) bsLoket.hide();
  }

  const idInput = document.getElementById('stepper_skm_antrian_id');
  if (idInput && id) idInput.value = id;

  const notesInput = document.getElementById('stepper_skm_catatan');
  if (notesInput) notesInput.value = currentCatatan || '';

  const targetVal = currentPendapat || 'Sangat Puas';
  const radio = document.querySelector(`input[name="stepper_skm_pendapat"][value="${targetVal}"]`);
  if (radio) {
    radio.checked = true;
    if (radio.closest('.stepper-skm-card')) {
      selectStepperSKMCard(radio.closest('.stepper-skm-card'));
    }
  } else {
    const firstCard = document.querySelector('.stepper-skm-card');
    if (firstCard) selectStepperSKMCard(firstCard);
  }

  const modalSKMEl = document.getElementById('modalSKMStepper');
  if (modalSKMEl) {
    const bsSKM = bootstrap.Modal.getOrCreateInstance(modalSKMEl);
    bsSKM.show();
  }
}

// REAL-TIME AUTO-POLLING STEPPER STATUS (WITHOUT MANUAL REFRESH)
let pollingInterval = null;

async function checkRealtimeStepperStatus() {
  if (sessionStorage.getItem('stepper_user_reset_new') === '1') return;

  // 1. JANGAN PERNAH reload jika ada modal yang sedang terbuka (misal modal QR tiket, modal SKM, webcam, dll.)
  if (document.querySelector('.modal.show') || document.querySelector('.modal[style*="display: block"]')) {
    return;
  }

  // 2. JANGAN PERNAH reload jika pengguna sedang aktif mengetik di form
  if (document.activeElement && (document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'TEXTAREA' || document.activeElement.tagName === 'SELECT')) {
    return;
  }

  try {
    const res = await fetch('api.php?action=get_stepper_status');
    const json = await res.json();
    if (json.status !== 'success' || !json.data) return;

    const data = json.data;
    if (!data.is_logged_in) return;

    const active = data.active_ticket;
    const completed = data.completed_ticket;

    // Detect status change
    const currentTicket = active || completed;
    const currentId = currentTicket ? parseInt(currentTicket.id) : 0;
    const currentStatus = currentTicket ? currentTicket.status : '';

    // Jika sebelumnya ID tiket adalah 0 (belum ada tiket) dan kini ada tiket baru dibuat (misal dari form antrian),
    // cukup sinkronisasi variabel internal tanpa mereload halaman agar tidak menutup popup tiket QR.
    if (lastStepperTicketId === 0 && currentId > 0) {
      lastStepperTicketId = currentId;
      lastStepperTicketStatus = currentStatus;
      if (window.stepperConfig) {
        window.stepperConfig.lastTicketId = currentId;
        window.stepperConfig.lastTicketStatus = currentStatus;
        window.stepperConfig.hasActiveTicket = !!active;
      }
      return;
    }

    if (currentId !== lastStepperTicketId || currentStatus !== lastStepperTicketStatus) {
      const prevStatus = lastStepperTicketStatus;
      lastStepperTicketId = currentId;
      lastStepperTicketStatus = currentStatus;

      if (window.stepperConfig) {
        window.stepperConfig.lastTicketId = currentId;
        window.stepperConfig.lastTicketStatus = currentStatus;
        window.stepperConfig.hasActiveTicket = !!active;
      }

      // Jangan reload jika status tidak benar-benar berubah
      if (!currentStatus || currentStatus === prevStatus) return;

      // Status updated! Show toast notification and update UI in real-time
      if (typeof Swal !== 'undefined' && currentStatus) {
        let msg = `Status kunjungan Anda telah diperbarui menjadi "${currentStatus}".`;
        if (currentStatus === 'Selesai') {
          msg = `Kunjungan Anda (No: #${currentTicket.nomor || currentTicket.kode_antrian}) telah Selesai! Terima kasih telah menggunakan layanan PST BPS Kota Tegal.`;
        }
        Swal.fire({
          toast: true,
          position: 'top-end',
          icon: currentStatus === 'Selesai' ? 'success' : 'info',
          title: msg,
          showConfirmButton: false,
          timer: 4000,
          timerProgressBar: true
        });
      }

      // Reload stepper component dynamically only when modal is closed
      setTimeout(() => {
        if (!document.querySelector('.modal.show')) {
          window.location.reload();
        }
      }, 1000);
    }
  } catch (err) {
    // Silent fail for polling network hiccups
  }
}

document.addEventListener('DOMContentLoaded', () => {
  // Start polling every 3.5 seconds
  if (!pollingInterval) {
    pollingInterval = setInterval(checkRealtimeStepperStatus, 3500);
  }

  const formSKMStepper = document.getElementById('formSKMStepper');
  if (formSKMStepper) {
    formSKMStepper.addEventListener('submit', async function(e) {
      e.preventDefault();
      const id = document.getElementById('stepper_skm_antrian_id').value;
      const checkedRadio = document.querySelector('input[name="stepper_skm_pendapat"]:checked');
      const pendapat = checkedRadio ? checkedRadio.value : 'Sangat Puas';
      const catatan = document.getElementById('stepper_skm_catatan').value.trim();

      try {
        const fd = new FormData();
        fd.append('id', id);
        fd.append('pendapat', pendapat);
        fd.append('catatan', catatan);
        if (typeof getCsrfToken === 'function') {
          fd.append('csrf_token', getCsrfToken());
        }

        const res = await fetch('api.php?action=submit_skm', { method: 'POST', body: fd });
        const json = await res.json();
        if (json.status === 'success') {
          if (typeof Swal !== 'undefined') {
            Swal.fire('Terima Kasih', json.message, 'success').then(() => {
              window.location.reload();
            });
          } else {
            alert(json.message);
            window.location.reload();
          }
        } else {
          if (typeof Swal !== 'undefined') {
            Swal.fire('Gagal', json.message || 'Gagal menyimpan penilaian.', 'error');
          } else {
            alert(json.message || 'Gagal menyimpan penilaian.');
          }
        }
      } catch (err) {
        if (typeof Swal !== 'undefined') {
          Swal.fire('Error', 'Gagal terhubung ke server.', 'error');
        } else {
          alert('Gagal terhubung ke server.');
        }
      }
    });
  }
});
