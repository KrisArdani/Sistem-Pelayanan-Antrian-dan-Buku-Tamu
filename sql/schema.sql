-- ==============================================================================
-- SPST BPS Kota Tegal - Skema Basis Data Bersih & Akun Sistem Awal
-- Sistem Pelayanan Statistik Terpadu (SPST)
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";

-- ------------------------------------------------------------------------------
-- 1. Struktur Tabel `users` (Pengguna & Petugas Internal)
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `nik` varchar(16) DEFAULT NULL,
  `role` enum('petugas','admin','kepala','pengunjung') NOT NULL,
  `layanan_tugas` varchar(150) DEFAULT NULL,
  `jenis_kelamin` enum('Laki Laki','Perempuan') DEFAULT 'Laki Laki',
  `umur` varchar(30) DEFAULT NULL,
  `nohp` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `pendidikan` varchar(50) DEFAULT NULL,
  `pekerjaan` varchar(100) DEFAULT NULL,
  `instansi` varchar(150) DEFAULT NULL,
  `kategori_instansi` varchar(100) DEFAULT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_user_nik` (`nik`),
  KEY `idx_user_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Awal Akun Sistem (Password produksi ter-hash Bcrypt)
LOCK TABLES `users` WRITE;
INSERT INTO `users` (`id`, `username`, `password`, `name`, `nik`, `role`, `layanan_tugas`, `jenis_kelamin`, `umur`, `nohp`, `email`, `pendidikan`, `pekerjaan`, `instansi`, `kategori_instansi`) VALUES
(1, 'petugas', '$2y$10$nLJhf2.9FtrWyiBZ0YDBEONo96/LIUo2qhG2lI9BJh.r9JqqALLj2', 'Petugas PST Loket Utama', NULL, 'petugas', 'Pelayanan Terpadu', 'Laki Laki', '26-34 tahun', '081200000001', 'petugas@bps.go.id', 'D4-S1', 'Pegawai BPS', 'BPS Kota Tegal', 'Instansi Pemerintah'),
(2, 'admin', '$2y$10$6gCk63rNl/q6q7sCDxeZieeF4LZXRhxnfa94DYehzNz5oFE5xgYeG', 'Admin Back Office', NULL, 'admin', NULL, 'Laki Laki', '26-34 tahun', '081200000002', 'admin@bps.go.id', 'D4-S1', 'Pegawai BPS', 'BPS Kota Tegal', 'Instansi Pemerintah'),
(3, 'kepala', '$2y$10$sLi6mt6BvED7js.iyjBw/edmYxfrBia5oUTQP7L0ItMSlapj.2vI6', 'Kepala BPS Kota Tegal', NULL, 'kepala', NULL, 'Laki Laki', '45+ tahun', '081200000003', 'kepala@bps.go.id', 'S2-S3', 'Kepala BPS', 'BPS Kota Tegal', 'Instansi Pemerintah'),
(4, 'petugas_konsultasi', '$2y$10$IPUqxF4BwIM2VPkkZt93/OthXu/4VqwMMsv6lJxHkkEXVsnWGLvva', 'Petugas Loket Konsultasi', NULL, 'petugas', 'Konsultasi Statistik', 'Laki Laki', '26-34 tahun', '081200000004', 'petugas.konsultasi@bps.go.id', 'D4-S1', 'Pegawai BPS', 'BPS Kota Tegal', 'Instansi Pemerintah'),
(5, 'petugas_perpustakaan', '$2y$10$VXzkugaB42KYjf3gAxQO9uExVOzd0OgGNrTmUffAuveTksaOQ0mLu', 'Petugas Loket Perpustakaan', NULL, 'petugas', 'Perpustakaan', 'Laki Laki', '26-34 tahun', '081200000005', 'petugas.perpustakaan@bps.go.id', 'D4-S1', 'Pegawai BPS', 'BPS Kota Tegal', 'Instansi Pemerintah'),
(6, 'petugas_rekomendasi', '$2y$10$KMcuhcOmJgAaiXEBSL/MIeqHYnXufmN1RUjxspa2gDlMnO1rqSt3e', 'Petugas Loket Rekomendasi', NULL, 'petugas', 'Rekomendasi Kegiatan Statistik', 'Laki Laki', '26-34 tahun', '081200000006', 'petugas.rekomendasi@bps.go.id', 'D4-S1', 'Pegawai BPS', 'BPS Kota Tegal', 'Instansi Pemerintah'),
(7, 'petugas_pengaduan', '$2y$10$JEWLNp8bF5sLzVqIDv/cZebZQOW.Jd/taIDrcPuQrKzgCj6LXV9gO', 'Petugas Loket Pengaduan', NULL, 'petugas', 'Layanan Pengaduan', 'Laki Laki', '26-34 tahun', '081200000007', 'petugas.pengaduan@bps.go.id', 'D4-S1', 'Pegawai BPS', 'BPS Kota Tegal', 'Instansi Pemerintah'),
(8, 'kris', '$2y$10$mYyrEm1O8yhBa7Ef7jXiMu4nuFOHym9mTHYsPU/CAkzsvFAC0uM0K', 'Kris Ardani', NULL, 'pengunjung', NULL, 'Laki Laki', '17-25 tahun', '0882828282', 'krispro195@gmail.com', 'D4-S1', 'Mahasiswa', 'Politeknik Purbaya', 'Sekolah/Universitas');
UNLOCK TABLES;

-- ------------------------------------------------------------------------------
-- 2. Struktur Tabel `antrian` (Transaksi Antrean & Reservasi Layanan)
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `antrian`;
CREATE TABLE `antrian` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `kode_antrian` varchar(20) NOT NULL,
  `nomor` varchar(20) NOT NULL,
  `nama` varchar(150) NOT NULL,
  `nik` varchar(16) DEFAULT NULL,
  `jenis_kelamin` enum('Laki Laki','Perempuan') DEFAULT 'Laki Laki',
  `umur` varchar(30) DEFAULT NULL,
  `nohp` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `pendidikan` varchar(50) DEFAULT NULL,
  `pekerjaan` varchar(100) DEFAULT NULL,
  `instansi` varchar(150) DEFAULT NULL,
  `kategori_instansi` varchar(100) DEFAULT NULL,
  `fasilitas` varchar(150) DEFAULT 'Datang Langsung Ke PST BPS Kota Tegal',
  `layanan` varchar(150) NOT NULL,
  `pemanfaatan` varchar(150) DEFAULT NULL,
  `data_diinginkan` text DEFAULT NULL,
  `foto` longtext DEFAULT NULL,
  `monev` enum('Ya','Tidak') DEFAULT 'Ya',
  `tanggal` date NOT NULL,
  `waktu` time NOT NULL,
  `called_at` datetime DEFAULT NULL,
  `panggil_count` int(11) DEFAULT 0,
  `pendapat` enum('Sangat Puas','Puas','Cukup Puas','Tidak Puas') DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `catatan_petugas` text DEFAULT NULL,
  `tipe_pendaftaran` enum('online','walkin') DEFAULT 'online',
  `status` enum('Menunggu','Dipanggil','Dilayani','Selesai','Terlewat','Dibatalkan') DEFAULT 'Menunggu',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_antrian` (`kode_antrian`),
  KEY `user_id` (`user_id`),
  KEY `idx_tanggal_status` (`tanggal`, `status`),
  CONSTRAINT `antrian_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 3. Struktur Tabel `buku_tamu` (Buku Tamu Kunjungan PST)
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `buku_tamu`;
CREATE TABLE `buku_tamu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode_bt` varchar(20) NOT NULL,
  `timestamp` datetime NOT NULL,
  `nama` varchar(150) NOT NULL,
  `nik` varchar(16) DEFAULT NULL,
  `jenis_kelamin` enum('Laki Laki','Perempuan') NOT NULL,
  `umur` varchar(30) NOT NULL,
  `nohp` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `pendidikan` varchar(50) NOT NULL,
  `pekerjaan` varchar(100) NOT NULL,
  `instansi` varchar(150) NOT NULL,
  `kategori_instansi` varchar(100) NOT NULL,
  `fasilitas` varchar(150) NOT NULL,
  `layanan` varchar(150) NOT NULL,
  `pemanfaatan` varchar(150) NOT NULL,
  `data_diinginkan` text DEFAULT NULL,
  `foto` longtext DEFAULT NULL,
  `pendapat` enum('Sangat Puas','Puas','Cukup Puas','Tidak Puas') DEFAULT NULL,
  `monev` enum('Ya','Tidak') NOT NULL DEFAULT 'Ya',
  `catatan` text DEFAULT NULL,
  `catatan_petugas` text DEFAULT NULL,
  `status` enum('Menunggu','Dipanggil','Dilayani','Selesai','Terlewat','Dibatalkan') DEFAULT 'Menunggu',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_bt` (`kode_bt`),
  KEY `idx_bt_timestamp` (`timestamp`),
  KEY `idx_bt_nik` (`nik`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 4. Struktur Tabel `login_attempts` (Rate Limiting Keamanan Login)
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ip_time` (`ip_address`,`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 5. Struktur Tabel `security_log` (Pencatatan Audit Keamanan Sistem)
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `security_log`;
CREATE TABLE `security_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_type` varchar(50) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sec_event` (`event_type`),
  KEY `idx_sec_time` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 6. Struktur Tabel `skm_pengaduan` (Survei Kepuasan Masyarakat & Pengaduan)
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `skm_pengaduan`;
CREATE TABLE `skm_pengaduan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipe` enum('penilaian','pengaduan') NOT NULL,
  `nama` varchar(150) DEFAULT NULL,
  `kontak` varchar(100) DEFAULT NULL,
  `rating_atau_kategori` varchar(100) DEFAULT NULL,
  `pesan` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_skm_tipe` (`tipe`),
  KEY `idx_skm_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 7. Struktur Tabel `webgis_data` (Data Statistik Wilayah WebGIS Kota Tegal)
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `webgis_data`;
CREATE TABLE `webgis_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kecamatan_code` varchar(10) NOT NULL,
  `kecamatan_name` varchar(100) NOT NULL,
  `main_category` varchar(50) NOT NULL,
  `indicator_code` varchar(50) NOT NULL,
  `indicator_name` varchar(150) NOT NULL,
  `unit` varchar(30) DEFAULT '',
  `year` int(11) NOT NULL,
  `value` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_kec_cat_ind_yr` (`kecamatan_code`, `main_category`, `indicator_code`, `year`),
  KEY `idx_lookup` (`main_category`, `indicator_code`, `year`, `kecamatan_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
