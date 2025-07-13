-- Database: depictworks
CREATE DATABASE IF NOT EXISTS `depictworks` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `depictworks`;

-- Tabel admin
CREATE TABLE IF NOT EXISTS `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert admin default (username: admin, password: admin123)
-- Password hash yang benar untuk 'admin123'
INSERT INTO `admin` (`username`, `password`, `nama_lengkap`, `email`) VALUES 
('admin', '$2y$10$Ew1ELmHzH5.q5zwTnlKYYOqH2W0L5B2m7hTt0vEkh8gTGRp2.VqZi', 'Administrator', 'admin@depictworks.com');

-- Tabel paket
CREATE TABLE IF NOT EXISTS `paket` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kategori` enum('outdoor','indoor') NOT NULL,
  `nama_paket` varchar(100) NOT NULL,
  `keterangan` text NOT NULL,
  `harga` int(11) NOT NULL,
  `foto_preview` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_kategori` (`kategori`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data paket default
INSERT INTO `paket` (`kategori`, `nama_paket`, `keterangan`, `harga`, `foto_preview`) VALUES 
('outdoor', 'Outdoor Hemat', '2 jam sesi foto + unlimited softfile + 36 edited file', 350000, 'gambar/our gallery/IMG_2273.jpg'),
('outdoor', 'Outdoor Lengkap', '4 jam sesi foto + unlimited softfile + 72 edited file', 750000, 'gambar/our gallery/IMG_2769.jpg'),
('outdoor', 'Outdoor Premium', 'Fullday sampai acara selesai + unlimited softfile + 120 edited file', 1990000, 'gambar/our gallery/IMG_2605.jpg'),
('indoor', 'Indoor Classic', '10 menit 1-2 orang (lebih dari 2 orang tambahan Rp 15.000/orang, tambahan waktu Rp 20.000/5 menit, jasa fotografer Rp 50.000/10 menit)', 50000, 'gambar/our gallery/20250707_130246_0000.png');

-- Tabel bookings (simplified - no foreign key constraint)
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `booking_date` date NOT NULL,
  `package` varchar(100) NOT NULL,
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_booking_date` (`booking_date`),
  KEY `idx_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;