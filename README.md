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

- **Web Server**: Apache (dengan `mod_rewrite` aktif) / LiteSpeed / Nginx
- **PHP**: versi **8.0 / 8.1 / 8.2 / 8.3** (Wajib PHP >= 8.0)
- **Ekstensi PHP**: `mysqli`, `mbstring`, `json`, `session`, `curl`, `fileinfo`
- **Database**: MySQL 5.7+ / MariaDB 10.3+

---

## ⚙️ Cara Instalasi & Penggunaan

1. **Clone / Download Repository**
   ```bash
   git clone https://github.com/KrisArdani/spst.git
   ```
   Ekstrak folder proyek ini ke dalam direktori web server Anda (misalnya `htdocs/spst` atau `public_html/`).

2. **Konfigurasi Lingkungan (`.env`)**
   Salin template konfigurasi:
   ```bash
   cp .env.example .env
   ```
   Sesuaikan kredensial basis data (`DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`), `APP_ENV`, dan konfigurasi email SMTP.

3. **Inisialisasi Database**
   - **Via CLI (Rekomendasi)**:
     ```bash
     php sql/migrate.php
     ```
   - **Via phpMyAdmin**: Buat database baru (misal `db_spst`), lalu impor file `db_spst.sql` atau `sql/schema.sql`.

4. **Jalankan Aplikasi**
   Buka peramban dan akses alamat domain / URL lokal proyek Anda.

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
