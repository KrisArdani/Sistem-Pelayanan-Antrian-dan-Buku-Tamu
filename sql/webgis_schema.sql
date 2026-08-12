-- WebGIS Data Schema & Seed Data for SPST BPS Kota Tegal
USE `db_spst`;

CREATE TABLE IF NOT EXISTS `webgis_data` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kecamatan_code` VARCHAR(10) NOT NULL,
  `kecamatan_name` VARCHAR(100) NOT NULL,
  `main_category` VARCHAR(50) NOT NULL,
  `indicator_code` VARCHAR(50) NOT NULL,
  `indicator_name` VARCHAR(150) NOT NULL,
  `unit` VARCHAR(30) DEFAULT '',
  `year` INT NOT NULL,
  `value` DECIMAL(12,2) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `idx_kec_cat_ind_yr` (`kecamatan_code`, `main_category`, `indicator_code`, `year`),
  KEY `idx_lookup` (`main_category`, `indicator_code`, `year`, `kecamatan_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
