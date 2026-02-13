-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.4.3 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for absensi_labor
CREATE DATABASE IF NOT EXISTS `absensi_labor` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `absensi_labor`;

-- Dumping structure for table absensi_labor.admin
CREATE TABLE IF NOT EXISTS `admin` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table absensi_labor.admin: ~1 rows (approximately)
INSERT INTO `admin` (`id`, `nama`, `username`, `password`) VALUES
	(1, 'admin', 'admin', '$2a$12$j9Q0477gv5CcqOFloYyOp.CyMnJGFkk9W2HcRS6TvW0XHtVR36jnm');

--password: admintefa123

-- Dumping structure for table absensi_labor.attendance_logs
CREATE TABLE IF NOT EXISTS `attendance_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `labor_id` int DEFAULT NULL,
  `status` enum('IN','OUT') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'IN',
  `confidence_score` float DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `stored_user_nama` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `keterangan` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'normal' COMMENT 'normal, terlambat, lembur',
  PRIMARY KEY (`id`),
  KEY `fk_att_user` (`user_id`),
  KEY `idx_labor_id` (`labor_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_att_labor` FOREIGN KEY (`labor_id`) REFERENCES `labor` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_att_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=222 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table absensi_labor.attendance_logs: ~0 rows (approximately)

-- Dumping structure for table absensi_labor.face_embeddings
CREATE TABLE IF NOT EXISTS `face_embeddings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `embedding_index` int DEFAULT '1',
  `embedding` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `photo_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_face_user` (`user_id`),
  KEY `idx_user_embedding` (`user_id`,`embedding_index`),
  CONSTRAINT `fk_face_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=117 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table absensi_labor.face_embeddings: ~0 rows (approximately)

-- Dumping structure for table absensi_labor.labor
CREATE TABLE IF NOT EXISTS `labor` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `deskripsi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `jam_masuk_standar` time DEFAULT '09:30:00' COMMENT 'Jam masuk standar (batas akhir masuk: 09:30)',
  `jam_pulang_standar` time DEFAULT '18:30:00' COMMENT 'Jam pulang standar (batas pulang: 18:30, jika lewat = lembur)',
  `toleransi_terlambat` int DEFAULT '1' COMMENT 'Toleransi terlambat dalam menit (default: 1 menit)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table absensi_labor.labor: ~3 rows (approximately)
INSERT INTO `labor` (`id`, `nama`, `deskripsi`, `created_at`, `jam_masuk_standar`, `jam_pulang_standar`, `toleransi_terlambat`) VALUES
	(1, 'Labor Desain', 'Fasilitas lengkap untuk pembuatan animasi 2D dan 3D dengan perangkat profesional', '2026-01-09 09:29:01', '09:30:00', '18:30:00', 1),
	(2, 'Labor Game', 'Studio pengembangan game dengan perangkat gaming dan development tools', '2026-01-09 09:29:01', '09:30:00', '18:30:00', 1),
	(3, 'Labor Tefa', 'Studio Project', '2026-01-09 09:29:01', '09:30:00', '18:30:00', 1);

-- Dumping structure for table absensi_labor.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nim` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `role` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'mahasiswa',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nim` (`nim`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table absensi_labor.users: ~0 rows (approximately)
INSERT INTO `users` (`id`, `nama`, `nim`, `is_active`, `role`, `created_at`, `updated_at`) VALUES
	(36, 'Khoirun Nisa', '24342058', 1, 'mahasiswa', '2026-02-13 17:21:23', '2026-02-13 17:21:23'),
	(37, 'Shofi Nurindah', '23342028', 1, 'mahasiswa', '2026-02-13 17:21:36', '2026-02-13 17:21:36'),
	(38, 'Ahmad Daffa Rahmadhan', '23342030', 1, 'mahasiswa', '2026-02-13 17:21:52', '2026-02-13 17:21:52'),
	(39, 'Muhammad Razaq', '23342026', 1, 'mahasiswa', '2026-02-13 17:22:05', '2026-02-13 17:22:05'),
	(40, 'Ihsan Gani', '23342022', 1, 'mahasiswa', '2026-02-13 17:22:20', '2026-02-13 17:22:20'),
	(41, 'Muhammad Rabbil Gazali', '23342036', 1, 'mahasiswa', '2026-02-13 17:22:57', '2026-02-13 17:22:57'),
	(42, 'Muhammad Ridwan', '23342038', 1, 'mahasiswa', '2026-02-13 17:23:07', '2026-02-13 17:23:07'),
	(43, 'Fayruj Shaleh.G', '23342019', 1, 'mahasiswa', '2026-02-13 17:23:25', '2026-02-13 17:23:25'),
	(44, 'Wido Pratama Arulsa', '23342042', 1, 'mahasiswa', '2026-02-13 17:23:44', '2026-02-13 17:23:44'),
	(45, 'Febri Kurnia', '24342024', 1, 'mahasiswa', '2026-02-13 17:24:06', '2026-02-13 17:24:06'),
	(46, 'Syaefinda Thysa Wafiqah', '24342015', 1, 'mahasiswa', '2026-02-13 17:24:22', '2026-02-13 17:24:22'),
	(47, 'Hazel Winanda Attaillah', '24342027', 1, 'mahasiswa', '2026-02-13 17:25:46', '2026-02-13 17:25:46'),
	(48, 'Muhamad Aziz Faqih', '24342029', 1, 'mahasiswa', '2026-02-13 17:25:58', '2026-02-13 17:25:58'),
	(49, 'Cahyo Viswanto', '24342022', 1, 'mahasiswa', '2026-02-13 17:26:09', '2026-02-13 17:26:09'),
	(50, 'Varqa Ali Husein', '24342059', 1, 'mahasiswa', '2026-02-13 17:26:19', '2026-02-13 17:26:19'),
	(51, 'Aisyah Qoni\'ah', '24342043', 1, 'mahasiswa', '2026-02-13 17:29:05', '2026-02-13 17:29:05'),
	(52, 'Muhammad Abdurrachman Suddesh', '24342030', 1, 'mahasiswa', '2026-02-13 17:29:18', '2026-02-13 17:29:18'),
	(53, 'Siti Fadillah Sallamah', '25342015', 1, 'mahasiswa', '2026-02-13 17:29:30', '2026-02-13 17:29:30'),
	(54, 'Aisya Putri Iqbal', '25342017', 1, 'mahasiswa', '2026-02-13 17:29:42', '2026-02-13 17:29:42'),
	(55, 'Diego Darulhuda', '24342023', 1, 'mahasiswa', '2026-02-13 17:29:53', '2026-02-13 17:29:53'),
	(56, 'Devi Afrida', '24342002', 1, 'mahasiswa', '2026-02-13 17:32:56', '2026-02-13 17:32:56'),
	(57, 'Habdul Rhauf', '25342006', 1, 'mahasiswa', '2026-02-13 17:34:20', '2026-02-13 17:34:20'),
	(58, 'Muhammad Ryan Aswin', '25342037', 1, 'mahasiswa', '2026-02-13 17:34:37', '2026-02-13 17:34:37'),
	(59, 'Bagiz Muzaky', '24342048', 1, 'mahasiswa', '2026-02-13 17:35:02', '2026-02-13 17:35:02'),
	(60, 'Mhd.Rahma Putra', '25342032', 1, 'mahasiswa', '2026-02-13 17:35:16', '2026-02-13 17:35:16'),
	(61, 'Danu Yuldi Putra', '25342022', 1, 'mahasiswa', '2026-02-13 17:35:34', '2026-02-13 17:35:34'),
	(62, 'Haris Rahman', '25342009', 1, 'mahasiswa', '2026-02-13 17:35:49', '2026-02-13 17:35:49'),
	(63, 'Reihan Permana Duta', '25342038', 1, 'mahasiswa', '2026-02-13 17:36:02', '2026-02-13 17:36:02'),
	(64, 'Umar Ahmad Mukhlas', '24342016', 1, 'mahasiswa', '2026-02-13 17:36:19', '2026-02-13 17:36:19'),
	(65, 'Ghauts El Yorica', '25342005', 1, 'mahasiswa', '2026-02-13 17:36:39', '2026-02-13 17:36:39'),
	(66, 'Nabila Akhdan Syaharani', '24342012', 1, 'mahasiswa', '2026-02-13 17:36:55', '2026-02-13 17:36:55'),
	(67, 'Azra Anati Lindra', '24342021', 1, 'mahasiswa', '2026-02-13 17:37:10', '2026-02-13 17:37:10'),
	(68, 'Divani Aura Sandi', '24342040', 1, 'mahasiswa', '2026-02-13 17:37:25', '2026-02-13 17:37:25'),
	(69, 'Adelia', '24342001', 1, 'mahasiswa', '2026-02-13 17:37:41', '2026-02-13 17:37:41'),
	(70, 'Selfia', '24342014', 1, 'mahasiswa', '2026-02-13 17:37:56', '2026-02-13 17:37:56'),
	(71, 'M Zuhdi Abdillah', '25342059', 1, 'mahasiswa', '2026-02-13 17:38:10', '2026-02-13 17:38:10'),
	(72, 'Anisa Julita Fitri', '23342013', 1, 'mahasiswa', '2026-02-13 17:38:26', '2026-02-13 17:38:26'),
	(73, 'Dhiya Ulhaq', '24342003', 1, 'mahasiswa', '2026-02-13 17:38:41', '2026-02-13 17:38:41'),
	(74, 'Fajri Hidayat', '25342024', 1, 'mahasiswa', '2026-02-13 17:38:55', '2026-02-13 17:38:55'),
	(75, 'Mohammad Danish Hakim', '24342064', 1, 'mahasiswa', '2026-02-13 17:39:11', '2026-02-13 17:39:11'),
	(76, 'Aurora Titania', '23342031', 1, 'mahasiswa', '2026-02-13 17:39:23', '2026-02-13 17:39:23'),
	(77, 'Alya Nabila Athifa', '24342018', 1, 'mahasiswa', '2026-02-13 17:39:38', '2026-02-13 17:39:38'),
	(78, 'Kevin Yumiko Alfarisi', '25342030', 1, 'mahasiswa', '2026-02-13 17:39:51', '2026-02-13 17:39:51'),
	(79, 'Alfa Bintang Fauzan', '25342019', 1, 'mahasiswa', '2026-02-13 17:40:10', '2026-02-13 17:40:10'),
	(80, 'Humairah Izzah Qonita', '23342021', 1, 'mahasiswa', '2026-02-13 17:40:25', '2026-02-13 17:40:25'),
	(81, 'Imam Maulana Siddiq', '25342028', 1, 'mahasiswa', '2026-02-13 17:40:40', '2026-02-13 17:40:40'),
	(82, 'Khairani Abdul Putri', '23342023', 1, 'mahasiswa', '2026-02-13 17:40:53', '2026-02-13 17:40:53'),
	(83, 'Muhammad Regio', '23342029', 1, 'mahasiswa', '2026-02-13 17:41:10', '2026-02-13 17:41:10'),
	(84, 'Muhammad Fauzan', '23342009', 1, 'mahasiswa', '2026-02-13 17:41:22', '2026-02-13 17:41:22'),
	(85, 'Muhammad Fikri Assakhy', '23342043', 1, 'mahasiswa', '2026-02-13 17:41:41', '2026-02-13 17:41:41'),
	(86, 'Taufik Akbar', '24342068', 1, 'mahasiswa', '2026-02-13 17:41:55', '2026-02-13 17:41:55'),
	(87, 'Faras Aprilsany', '23342017', 1, 'mahasiswa', '2026-02-13 17:42:09', '2026-02-13 17:42:09'),
	(88, 'Muhammad Dani', '25342076', 1, 'mahasiswa', '2026-02-13 17:42:22', '2026-02-13 17:42:22'),
	(89, 'M Ridho Aldian', '25342067', 1, 'mahasiswa', '2026-02-13 17:42:40', '2026-02-13 17:42:40'),
	(90, 'Khindy Adham Syahladi', '25342057', 1, 'mahasiswa', '2026-02-13 17:43:13', '2026-02-13 17:43:13'),
	(91, 'Asyiva Putri Wisna', '24342019', 1, 'mahasiswa', '2026-02-13 17:43:29', '2026-02-13 17:43:29'),
	(92, 'Afifah Nur Fitri', '24342017', 1, 'mahasiswa', '2026-02-13 17:43:45', '2026-02-13 17:43:45'),
	(93, 'Angelica Merfin Gulo', '24342039', 1, 'mahasiswa', '2026-02-13 17:44:00', '2026-02-13 17:44:00'),
	(94, 'Najla Putri Rahmadani', '24342032', 1, 'mahasiswa', '2026-02-13 17:44:17', '2026-02-13 17:44:17');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
