<!-- Floating Widgets (Beri Penilaian & Form Pengaduan) - Identik dengan Web Asli -->
<div class="fixed bottom-6 right-6 z-40 flex flex-col gap-3">
  <!-- Floating Widget 1: Penilaian Kepuasan -->
  <div class="dropdown dropup">
    <button class="w-12 h-12 bg-amber-500 hover:bg-amber-600 text-white rounded-full flex items-center justify-center shadow-xl transition transform hover:scale-105" type="button" data-bs-toggle="dropdown">
      <span class="material-icons">star</span>
    </button>
    <div class="dropdown-menu dropdown-menu-end p-4 glass-card w-72 shadow-2xl border-none text-slate-800 space-y-3">
      <div class="flex items-center gap-2 font-bold text-amber-600">
        <span class="material-icons">star</span>
        <span>Penilaian Kepuasan</span>
      </div>
      <p class="text-xs text-slate-600 leading-relaxed">
        Seberapa puas Anda dengan pelayanan yang kami berikan? Sampaikan penilaian Anda melalui formulir berikut.
      </p>
      <a href="#" onclick="
        Swal.fire({
          title: 'Penilaian Kepuasan',
          input: 'textarea',
          inputPlaceholder: 'Sampaikan ulasan atau masukan Anda...',
          showCancelButton: true,
          confirmButtonText: 'Kirim Penilaian',
          confirmButtonColor: '#003366'
        }).then(res => {
          if (res.isConfirmed && res.value) {
            const fd = new FormData();
            fd.append('tipe', 'penilaian');
            fd.append('pesan', res.value);
            fd.append('csrf_token', getCsrfToken());
            fetch('api.php?action=save_widget_feedback', { method: 'POST', body: fd })
              .then(r => r.json())
              .then(json => {
                if (json.status === 'success') {
                  Swal.fire('Terima Kasih', 'Penilaian Anda telah tersimpan di Database.', 'success');
                } else {
                  Swal.fire('Gagal', json.message || 'Gagal menyimpan penilaian.', 'error');
                }
              });
          }
        }); return false;
      " class="btn btn-warning btn-sm w-full font-bold text-[#003366]">Beri Penilaian</a>
    </div>
  </div>

  <!-- Floating Widget 2: Form Pengaduan -->
  <div class="dropdown dropup">
    <button class="w-12 h-12 bg-sky-600 hover:bg-sky-700 text-white rounded-full flex items-center justify-center shadow-xl transition transform hover:scale-105" type="button" data-bs-toggle="dropdown">
      <span class="material-icons">mail</span>
    </button>
    <div class="dropdown-menu dropdown-menu-end p-4 glass-card w-72 shadow-2xl border-none text-slate-800 space-y-3">
      <div class="flex items-center gap-2 font-bold text-sky-600">
        <span class="material-icons">mail</span>
        <span>Form Pengaduan</span>
      </div>
      <p class="text-xs text-slate-600 leading-relaxed">
        Sampaikan pengaduan atau masukan Anda melalui formulir berikut. Kami siap membantu!
      </p>
      <a href="#" onclick="
        Swal.fire({
          title: 'Form Pengaduan',
          input: 'textarea',
          inputPlaceholder: 'Jelaskan pengaduan atau keluhan Anda...',
          showCancelButton: true,
          confirmButtonText: 'Kirim Pengaduan',
          confirmButtonColor: '#003366'
        }).then(res => {
          if (res.isConfirmed && res.value) {
            const fd = new FormData();
            fd.append('tipe', 'pengaduan');
            fd.append('pesan', res.value);
            fd.append('csrf_token', getCsrfToken());
            fetch('api.php?action=save_widget_feedback', { method: 'POST', body: fd })
              .then(r => r.json())
              .then(json => {
                if (json.status === 'success') {
                  Swal.fire('Pengaduan Terkirim', 'Pengaduan Anda telah tercatat di Database.', 'info');
                } else {
                  Swal.fire('Gagal', json.message || 'Gagal menyimpan pengaduan.', 'error');
                }
              });
          }
        }); return false;
      " class="btn btn-primary btn-sm w-full font-bold bg-[#003366] border-[#003366]">Isi Pengaduan</a>
    </div>
  </div>
</div>

<!-- Main Footer Component (Identik dengan Web Asli spst.web.bps.go.id) -->
<footer class="bg-slate-900 text-slate-300 pt-12 pb-8 border-t border-slate-800 mt-16">
  <div class="max-w-7xl mx-auto px-6 grid grid-cols-1 md:grid-cols-3 gap-10 text-sm pb-10 border-b border-slate-800">
    
    <!-- Kolom 1: Profil Instansi -->
    <div class="space-y-4">
      <div class="flex items-center gap-2 font-bold text-xs text-sky-400 uppercase tracking-widest">
        <span class="material-icons text-base">apartment</span>
        <span>PROFIL INSTANSI</span>
      </div>

      <div class="flex items-center gap-4">
        <img src="img/Logo_BPS.png" alt="Logo BPS Kota Tegal" class="w-12 h-12 object-contain filter drop-shadow">
        <div>
          <h4 class="text-white font-extrabold text-base leading-snug brand-font">BADAN PUSAT STATISTIK</h4>
          <h5 class="text-sky-400 font-bold text-sm brand-font">KOTA TEGAL</h5>
        </div>
      </div>

      <p class="text-xs text-slate-400 leading-relaxed">
        Badan Pusat Statistik Kota Tegal (BPS-Statistics of Tegal City)<br>
        Jl. Perintis Kemerdekaan No. 2 Kota Tegal<br>
        Telp: (0283) 351190<br>
        E-Mail : <a href="mailto:bps3376@bps.go.id" class="text-sky-400 hover:underline">bps3376@bps.go.id</a>
      </p>

      <div class="pt-2 flex items-center gap-3">
        <img src="img/berakhlak.webp" alt="BerAKHLAK" class="h-8 object-contain">
        <span class="text-[10px] text-slate-400 italic">Bangga Melayani Bangsa</span>
      </div>
    </div>

    <!-- Kolom 2: Tentang Kami -->
    <div class="space-y-4">
      <div class="flex items-center gap-2 font-bold text-xs text-sky-400 uppercase tracking-widest">
        <span class="material-icons text-base">info</span>
        <span>TENTANG KAMI</span>
      </div>

      <ul class="space-y-2.5 text-xs text-slate-300">
        <li>
          <a href="https://tegalkota.bps.go.id/id" target="_blank" class="hover:text-sky-400 transition flex items-center gap-1.5">
            <span class="text-sky-400 font-bold">↗</span> Web BPS Kota Tegal
          </a>
        </li>
        <li>
          <a href="https://ppid.bps.go.id/?mfd=3376&" target="_blank" class="hover:text-sky-400 transition flex items-center gap-1.5">
            <span class="text-sky-400 font-bold">↗</span> PPID Kota Tegal
          </a>
        </li>
        <li>
          <a href="https://ppid.bps.go.id/app/konten/0000/Layanan-BPS.html#pills-3" target="_blank" class="hover:text-sky-400 transition flex items-center gap-1.5">
            <span class="text-sky-400 font-bold">↗</span> Kebijakan Diseminasi
          </a>
        </li>
      </ul>
    </div>

    <!-- Kolom 3: Tautan Lainnya -->
    <div class="space-y-4">
      <div class="flex items-center gap-2 font-bold text-xs text-sky-400 uppercase tracking-widest">
        <span class="material-icons text-base">link</span>
        <span>TAUTAN LAINNYA</span>
      </div>

      <ul class="space-y-2.5 text-xs text-slate-300">
        <li>
          <a href="https://fmsindonesia.id/" target="_blank" class="hover:text-sky-400 transition flex items-center gap-1.5">
            <span class="text-sky-400 font-bold">↗</span> Forum Masyarakat Statistik
          </a>
        </li>
        <li>
          <a href="https://rb.bps.go.id/" target="_blank" class="hover:text-sky-400 transition flex items-center gap-1.5">
            <span class="text-sky-400 font-bold">↗</span> Reformasi Birokrasi
          </a>
        </li>
        <li>
          <a href="https://www.stis.ac.id/" target="_blank" class="hover:text-sky-400 transition flex items-center gap-1.5">
            <span class="text-sky-400 font-bold">↗</span> Politeknik Statistika STIS
          </a>
        </li>
        <li>
          <a href="https://pusdiklat.bps.go.id/" target="_blank" class="hover:text-sky-400 transition flex items-center gap-1.5">
            <span class="text-sky-400 font-bold">↗</span> Pusdiklat BPS
          </a>
        </li>
        <li>
          <a href="https://jdih.bps.go.id/" target="_blank" class="hover:text-sky-400 transition flex items-center gap-1.5">
            <span class="text-sky-400 font-bold">↗</span> JDIH BPS
          </a>
        </li>
      </ul>
    </div>

  </div>

  <!-- Bottom Bar: Copyright & Social Media Icons -->
  <div class="max-w-7xl mx-auto px-6 pt-6 flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-slate-400">
    <div>
      Hak Cipta © 2026 Badan Pusat Statistik Kota Tegal
    </div>

    <!-- Social Media Icons (Identik dengan Web Asli) -->
    <div class="flex items-center gap-3">
      <a href="https://wa.me/628123456789" target="_blank" class="w-8 h-8 rounded-full bg-slate-800 hover:bg-emerald-600 text-white flex items-center justify-center transition" title="WhatsApp Chat PST">
        <i class="fa-brands fa-whatsapp"></i>
      </a>
      <a href="https://facebook.com" target="_blank" class="w-8 h-8 rounded-full bg-slate-800 hover:bg-blue-600 text-white flex items-center justify-center transition" title="Facebook BPS Kota Tegal">
        <i class="fa-brands fa-facebook-f"></i>
      </a>
      <a href="https://tiktok.com" target="_blank" class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-white flex items-center justify-center transition" title="TikTok BPS Kota Tegal">
        <i class="fa-brands fa-tiktok"></i>
      </a>
      <a href="https://instagram.com" target="_blank" class="w-8 h-8 rounded-full bg-slate-800 hover:bg-pink-600 text-white flex items-center justify-center transition" title="Instagram BPS Kota Tegal">
        <i class="fa-brands fa-instagram"></i>
      </a>
      <a href="https://twitter.com" target="_blank" class="w-8 h-8 rounded-full bg-slate-800 hover:bg-sky-500 text-white flex items-center justify-center transition" title="Twitter BPS Kota Tegal">
        <i class="fa-brands fa-x-twitter"></i>
      </a>
      <a href="https://youtube.com" target="_blank" class="w-8 h-8 rounded-full bg-slate-800 hover:bg-red-600 text-white flex items-center justify-center transition" title="YouTube BPS Kota Tegal">
        <i class="fa-brands fa-youtube"></i>
      </a>
    </div>
  </div>
</footer>