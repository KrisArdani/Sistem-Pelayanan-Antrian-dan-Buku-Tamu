# TOBASA - Pelayanan Statistik Terpadu BPS Kota Tegal

Aplikasi web portal **TOBASA** (Pelayanan Statistik Terpadu) yang digunakan oleh **BPS Kota Tegal** untuk mengelola antrean layanan dan buku tamu pengunjung secara efisien, modern, dan terintegrasi.

---

## 🚀 Fitur Utama

- **Antrean Online (Queue Management)**: Pengambilan dan pengelolaan antrean pengunjung pelayanan statistik.
- **Buku Tamu (Guestbook System)**: Pencatatan identitas, maksud/tujuan kedatangan, dan riwayat kunjungan tamu.
- **Admin Dashboard**: Panel kontrol untuk pengelola layanan dalam memantau dan mengelola antrean serta laporan buku tamu.
- **Keamanan & Proteksi**: Dilengkapi proteksi CSRF, pembersihan input, dan manajemen sesi yang aman.
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
   git clone <repository-url>
   ```
   Atau tempatkan folder proyek ini ke dalam direktori web server (misalnya `C:/xampp/htdocs/A/sepat`).

2. **Import Database**
   - Buka **phpMyAdmin** (`http://localhost/phpmyadmin`).
   - Buat database baru dengan nama `db_tobasa` (atau sesuaikan dengan konfigurasi).
   - Import file `db_tobasa.sql` yang ada pada direktori akar proyek.

3. **Konfigurasi Koneksi**
   - Buka file `config.php` atau `koneksi.php`.
   - Sesuaikan kredensial database (host, username, password, dan nama database) jika diperlukan.

4. **Jalankan Aplikasi**
   - Pastikan service Apache dan MySQL pada XAMPP sudah berjalan.
   - Buka browser dan akses:
     ```text
     http://localhost/A/sepat/
     ```

---

## 📁 Struktur Direktori

```text
sepat/
├── admin/            # Panel manajemen admin
├── css/              # Stylesheet & stylesheet kustom
├── js/               # Script JavaScript
├── img/              # Asset gambar & ikon
├── antrian.php       # Modul manajemen & pengambilan antrean
├── bukutamu.php      # Modul pencatatan buku tamu
├── config.php        # Konfigurasi aplikasi
├── db_tobasa.sql     # Schema & data awal database MySQL
├── footer.php        # Header/Footer komponen UI
├── index.php         # Halaman utama (Landing Page TOBASA)
├── koneksi.php       # Koneksi ke database MySQL
├── login.php         # Halaman login petugas/admin
├── logout.php        # Proses logout
├── register.php      # Halaman pendaftaran
├── security.php      # Fungsi keamanan (CSRF & Security Headers)
└── sidebar.php       # Komponen navigasi sidebar
```

---

## 📝 Lisensi

Hak Cipta © BPS Kota Tegal. Seluruh hak cipta dilindungi undang-undang.
