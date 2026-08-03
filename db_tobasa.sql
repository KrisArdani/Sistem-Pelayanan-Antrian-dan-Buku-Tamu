-- TOBASA BPS Kota Tegal - Database Schema
-- Database Name: db_tobasa

CREATE DATABASE IF NOT EXISTS `db_tobasa` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_tobasa`;

-- 1. Table: users (Auth, RBAC & Profil Pengunjung)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `role` ENUM('petugas', 'admin', 'kepala', 'pengunjung') NOT NULL,
  `jenis_kelamin` ENUM('Laki Laki', 'Perempuan') DEFAULT 'Laki Laki',
  `umur` VARCHAR(30) DEFAULT NULL,
  `nohp` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `pendidikan` VARCHAR(50) DEFAULT NULL,
  `pekerjaan` VARCHAR(100) DEFAULT NULL,
  `instansi` VARCHAR(150) DEFAULT NULL,
  `kategori_instansi` VARCHAR(100) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Accounts (Valid Bcrypt Hashes):
-- petugas / petugas123
-- admin / admin123
-- kepala / kepala123
-- ahmad_fauzi / user123 (Sample Pengunjung)
INSERT INTO `users` (`id`, `username`, `password`, `name`, `role`, `jenis_kelamin`, `umur`, `nohp`, `email`, `pendidikan`, `pekerjaan`, `instansi`, `kategori_instansi`) VALUES
(1, 'petugas', '$2y$10$wyF1kIOQij5vyCM.3C4AW.KeurOmtfYFEfJCFHn.vfB4sxDOY086O', 'Petugas PST Loket', 'petugas', 'Laki Laki', '26-34 tahun', '081200000001', 'petugas@bps.go.id', 'D4-S1', 'Pegawai BPS', 'BPS Kota Tegal', 'Instansi Pemerintah'),
(2, 'admin', '$2y$10$kSx1EttrAobm378hbHq.Durj8KM8BOgmohDmOw198yYWcpIiaENvW', 'Admin Back Office', 'admin', 'Laki Laki', '26-34 tahun', '081200000002', 'admin@bps.go.id', 'D4-S1', 'Pegawai BPS', 'BPS Kota Tegal', 'Instansi Pemerintah'),
(3, 'kepala', '$2y$10$oDKByW3T.VAjhzLzRg3SUOfjVWbiZpVTRXVuEmkMNc4FUrqqu/xAW', 'Kepala BPS Kota Tegal', 'kepala', 'Laki Laki', '45+ tahun', '081200000003', 'kepala@bps.go.id', 'S2-S3', 'Kepala BPS', 'BPS Kota Tegal', 'Instansi Pemerintah'),
(4, 'ahmad_fauzi', '$2y$10$wyF1kIOQij5vyCM.3C4AW.KeurOmtfYFEfJCFHn.vfB4sxDOY086O', 'Ahmad Fauzi', 'pengunjung', 'Laki Laki', '26-34 tahun', '081234567890', 'ahmad.fauzi@email.com', 'D4-S1', 'Peneliti/Dosen', 'Universitas Pancasakti Tegal', 'Sekolah/Universitas')
ON DUPLICATE KEY UPDATE `password` = VALUES(`password`), `name` = VALUES(`name`);


-- 1b. Table: login_attempts (Rate Limiting)
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ip_address` VARCHAR(45) NOT NULL,
  `username` VARCHAR(50) DEFAULT NULL,
  `attempted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_ip_time` (`ip_address`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 1c. Table: security_log (Audit Trail)
CREATE TABLE IF NOT EXISTS `security_log` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_type` VARCHAR(50) NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_id` INT DEFAULT NULL,
  `details` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 2. Table: buku_tamu (Guestbook Entries & Verification Status)
CREATE TABLE IF NOT EXISTS `buku_tamu` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kode_bt` VARCHAR(20) NOT NULL UNIQUE,
  `timestamp` DATETIME NOT NULL,
  `nama` VARCHAR(150) NOT NULL,
  `jenis_kelamin` ENUM('Laki Laki', 'Perempuan') NOT NULL,
  `umur` VARCHAR(30) NOT NULL,
  `nohp` VARCHAR(20) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `pendidikan` VARCHAR(50) NOT NULL,
  `pekerjaan` VARCHAR(100) NOT NULL,
  `instansi` VARCHAR(150) NOT NULL,
  `kategori_instansi` VARCHAR(100) NOT NULL,
  `fasilitas` VARCHAR(150) NOT NULL,
  `layanan` VARCHAR(150) NOT NULL,
  `pemanfaatan` VARCHAR(150) NOT NULL,
  `data_diinginkan` TEXT DEFAULT NULL,
  `foto` LONGTEXT DEFAULT NULL,
  `pendapat` ENUM('Sangat Puas', 'Puas', 'Cukup Puas', 'Tidak Puas') DEFAULT NULL,
  `monev` ENUM('Ya', 'Tidak') NOT NULL DEFAULT 'Ya',
  `catatan` TEXT DEFAULT NULL,
  `status` ENUM('Menunggu', 'Dipanggil', 'Dilayani', 'Selesai', 'Terlewat', 'Dibatalkan') DEFAULT 'Menunggu',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Initial Visitor Data
INSERT INTO `buku_tamu` (
  `kode_bt`, `timestamp`, `nama`, `jenis_kelamin`, `umur`, `nohp`, `email`, `pendidikan`,
  `pekerjaan`, `instansi`, `kategori_instansi`, `fasilitas`, `layanan`, `pemanfaatan`,
  `data_diinginkan`, `pendapat`, `monev`, `catatan`, `status`
) VALUES
('BT-001', '2026-07-27 08:30:00', 'Ahmad Fauzi', 'Laki Laki', '26-34 tahun', '081234567890', 'ahmad.fauzi@email.com', 'D4-S1', 'Peneliti/Dosen', 'Universitas Pancasakti Tegal', 'Sekolah/Universitas', 'Datang Langsung Ke PST BPS Kota Tegal', 'Konsultasi Statistik', 'Penelitian', 'Data Inflasi Kota Tegal 2025-2026', 'Sangat Puas', 'Ya', 'Pelayanan ramah dan respon sangat cepat.', 'Selesai'),
('BT-002', '2026-07-27 09:15:00', 'Siti Rahmawati', 'Perempuan', '17-25 tahun', '085712345678', 'siti.rahma@email.com', 'SMA Ke Bawah', 'Mahasiswa', 'Poltek Harber Tegal', 'Sekolah/Universitas', 'Datang Langsung Ke PST BPS Kota Tegal', 'Perpustakaan', 'Tugas Sekolah/Kuliah', 'Publikasi Tegal Kota Dalam Angka 2025', 'Puas', 'Ya', 'Buku publikasi sangat lengkap.', 'Menunggu'),
('BT-003', '2026-07-27 10:00:00', 'Budi Santoso', 'Laki Laki', '35-44 tahun', '081987654321', 'budi.santoso@pemda.go.id', 'D4-S1', 'Pegawai Negeri / TNI POLRI', 'Bappeda Kota Tegal', 'Pemda', 'Datang Langsung Ke PST BPS Kota Tegal', 'Rekomendasi Kegiatan Statistik (ROMANTIK)', 'Pemerintah', 'Rekomendasi Survei Kepuasan Masyarakat Pemda', 'Sangat Puas', 'Ya', 'Penjelasan mengenai prosedur ROMANTIK sangat jelas.', 'Selesai')
ON DUPLICATE KEY UPDATE `nama` = VALUES(`nama`);


-- 3. Table: antrian (Online Queue & Integrated Service Requests)
CREATE TABLE IF NOT EXISTS `antrian` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `kode_antrian` VARCHAR(20) NOT NULL UNIQUE,
  `nomor` VARCHAR(20) NOT NULL,
  `nama` VARCHAR(150) NOT NULL,
  `jenis_kelamin` ENUM('Laki Laki', 'Perempuan') DEFAULT 'Laki Laki',
  `umur` VARCHAR(30) DEFAULT NULL,
  `nohp` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `pendidikan` VARCHAR(50) DEFAULT NULL,
  `pekerjaan` VARCHAR(100) DEFAULT NULL,
  `instansi` VARCHAR(150) DEFAULT NULL,
  `kategori_instansi` VARCHAR(100) DEFAULT NULL,
  `fasilitas` VARCHAR(150) DEFAULT 'Datang Langsung Ke PST BPS Kota Tegal',
  `layanan` VARCHAR(150) NOT NULL,
  `pemanfaatan` VARCHAR(150) DEFAULT NULL,
  `data_diinginkan` TEXT DEFAULT NULL,
  `foto` LONGTEXT DEFAULT NULL,
  `monev` ENUM('Ya', 'Tidak') DEFAULT 'Ya',
  `tanggal` DATE NOT NULL,
  `waktu` TIME NOT NULL,
  `pendapat` ENUM('Sangat Puas', 'Puas', 'Cukup Puas', 'Tidak Puas') DEFAULT NULL,
  `catatan` TEXT DEFAULT NULL,
  `tipe_pendaftaran` ENUM('online', 'walkin') DEFAULT 'online',
  `status` ENUM('Menunggu', 'Dipanggil', 'Dilayani', 'Selesai', 'Terlewat', 'Dibatalkan') DEFAULT 'Menunggu',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Initial Queue Data
INSERT INTO `antrian` (
  `user_id`, `kode_antrian`, `nomor`, `nama`, `jenis_kelamin`, `umur`, `nohp`, `email`, `pendidikan`,
  `pekerjaan`, `instansi`, `kategori_instansi`, `fasilitas`, `layanan`, `pemanfaatan`, `data_diinginkan`,
  `tanggal`, `waktu`, `tipe_pendaftaran`, `status`
) VALUES
(4, 'ANT-001', 'KS-01', 'Ahmad Fauzi', 'Laki Laki', '26-34 tahun', '081234567890', 'ahmad.fauzi@email.com', 'D4-S1', 'Peneliti/Dosen', 'Universitas Pancasakti Tegal', 'Sekolah/Universitas', 'Datang Langsung Ke PST BPS Kota Tegal', 'Konsultasi Statistik', 'Penelitian', 'Data Inflasi Kota Tegal 2025-2026', CURRENT_DATE(), '08:30:00', 'online', 'Selesai'),
(NULL, 'ANT-002', 'PD-01', 'Siti Rahmawati', 'Perempuan', '17-25 tahun', '085712345678', 'siti.rahma@email.com', 'SMA Ke Bawah', 'Mahasiswa', 'Poltek Harber Tegal', 'Sekolah/Universitas', 'Datang Langsung Ke PST BPS Kota Tegal', 'Perpustakaan & Diseminasi Data', 'Tugas Sekolah/Kuliah', 'Publikasi Tegal Kota Dalam Angka 2025', CURRENT_DATE(), '09:15:00', 'walkin', 'Dipanggil'),
(NULL, 'ANT-003', 'RS-01', 'Budi Santoso', 'Laki Laki', '35-44 tahun', '081987654321', 'budi.santoso@pemda.go.id', 'D4-S1', 'Pegawai Negeri / TNI POLRI', 'Bappeda Kota Tegal', 'Pemda', 'Datang Langsung Ke PST BPS Kota Tegal', 'Rekomendasi Kegiatan Statistik (ROMANTIK)', 'Pemerintah', 'Rekomendasi Survei Kepuasan Masyarakat Pemda', CURRENT_DATE(), '10:00:00', 'online', 'Menunggu')
ON DUPLICATE KEY UPDATE `nama` = VALUES(`nama`);


-- 4. Table: skm_pengaduan (Feedback & Complaints)
CREATE TABLE IF NOT EXISTS `skm_pengaduan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tipe` ENUM('penilaian', 'pengaduan') NOT NULL,
  `nama` VARCHAR(150) DEFAULT NULL,
  `kontak` VARCHAR(100) DEFAULT NULL,
  `rating_atau_kategori` VARCHAR(100) DEFAULT NULL,
  `pesan` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Initial Feedback
INSERT INTO `skm_pengaduan` (`tipe`, `nama`, `kontak`, `rating_atau_kategori`, `pesan`) VALUES
('penilaian', 'Ahmad Fauzi', '081234567890', 'Sangat Puas', 'Layanan PST BPS Kota Tegal sangat nyaman dan informatif.'),
('pengaduan', 'Pengunjung', '-', 'Saran Fasilitas', 'Mohon ditambah tempat duduk di area ruang tunggu PST.')
ON DUPLICATE KEY UPDATE `pesan` = VALUES(`pesan`);
