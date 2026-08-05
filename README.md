# SPST - Sistem Pelayanan Statistik Terpadu BPS Kota Tegal

Aplikasi web portal **SPST** (Sistem Pelayanan Statistik Terpadu) yang digunakan oleh **BPS Kota Tegal** untuk mengelola antrean layanan dan buku tamu pengunjung secara efisien, modern, dan terintegrasi.

---

## 🚀 Fitur Utama

- **Antrean Online (Queue Management)**: Pengambilan dan pengelolaan antrean pengunjung pelayanan statistik.
- **Buku Tamu (Guestbook System)**: Pencatatan identitas, maksud/tujuan kedatangan, dan riwayat kunjungan tamu.
- **Admin Dashboard**: Panel kontrol interaktif untuk pengelola layanan dalam memantau, memfilter, dan mengelola antrean serta laporan buku tamu.
- **Keamanan & Proteksi**: Dilengkapi proteksi CSRF, pembersihan input, rate limiting login, dan manajemen sesi yang aman.
- **Tampilan Modern & Responsif**: Menggunakan Tailwind CSS & Bootstrap 5 dengan desain yang user-friendly dan responsif.

---

## 🛠️ Teknologi & Dependensi

- **Backend**: PHP (Native)
- **Database**: MySQL / MariaDB
- **Frontend**: HTML5, JavaScript (ES6), Tailwind CSS, Bootstrap 5.3
- **Komponen UI/UX**: FontAwesome, Material Icons, SweetAlert2

---

## 📋 Persyaratan Sistem

- **Web Server**: Apache (XAMPP / Laragon / Nginx)
- **PHP**: versi 7.4 / 8.0 ke atas
- **Database**: MySQL / MariaDB

---

## ⚙️ Cara Instalasi & Penggunaan

1. **Clone / Download Repository**
   ```bash
   git clone https://github.com/KrisArdani/spst.git
   ```
   Atau ekstrak folder proyek ini ke dalam direktori web server Anda (misalnya `C:/xampp/htdocs/A/spst` atau `C:/xampp/htdocs/spst`).

2. **Database Auto-Setup / Import Manual**
   - **Otomatis**: Sistem dilengkapi *Auto-Setup Database*. Cukup pastikan MySQL pada XAMPP sudah berjalan, lalu buka aplikasi di browser. Sistem akan otomatis membuat database `db_spst` dan mengimpor struktur/tabel awal jika belum ada.
   - **Manual**: Jika ingin mengimpor manual, buka **phpMyAdmin** (`http://localhost/phpmyadmin`), buat database `db_spst`, lalu import file `db_spst.sql`.

3. **Konfigurasi Koneksi**
   - Buka file `config.php` untuk mengkonfigurasi kredensial MySQL (`DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`) jika tidak menggunakan `root` tanpa password.

4. **Jalankan Aplikasi**
   - Pastikan Apache dan MySQL pada XAMPP sudah aktif.
   - Buka browser dan akses:
     ```text
     http://localhost/A/spst/
     ```
     *(atau sesuaikan dengan folder htdocs Anda)*

---

## 🔑 Akun Bawaan (Default Accounts)

Gunakan kredensial berikut untuk menguji aplikasi:

| Role | Username | Password | Deskripsi |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin` | `admin123` | Akses penuh dashboard back-office |
| **Petugas** | `petugas` | `petugas123` | Akses loket pelayanan antrean & buku tamu |
| **Kepala BPS** | `kepala` | `kepala123` | Akses pemantauan & laporan |
| **Pengunjung** | `ahmad_fauzi` | `user123` | Akun contoh pengunjung |

---

## 📁 Struktur Direktori

```text
spst/
├── admin/            # Panel manajemen admin (dashboard, antrean, buku tamu)
├── css/              # Stylesheet & stylesheet kustom
├── js/               # Script JavaScript (dashboard, antrean, tts, dll)
├── img/              # Asset gambar & logo BPS
├── antrian.php       # Modul manajemen & pengambilan antrean
├── bukutamu.php      # Modul pencatatan buku tamu
├── config.php        # Konfigurasi aplikasi & database
├── db_spst.sql       # Schema & data awal database MySQL
├── footer.php        # Header/Footer komponen UI
├── index.php         # Halaman utama (Landing Page SPST)
├── koneksi.php       # Koneksi ke database MySQL & auto-migration
├── login.php         # Halaman login petugas/admin/pengunjung
├── logout.php        # Proses logout
├── register.php      # Halaman pendaftaran akun pengunjung
├── security.php      # Fungsi keamanan (CSRF & Security Headers)
└── sidebar.php       # Komponen navigasi sidebar
```

---

## 📝 Lisensi

Hak Cipta © BPS Kota Tegal. Seluruh hak cipta dilindungi undang-undang.
