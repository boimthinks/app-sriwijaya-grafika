-- MySQL dump 10.13  Distrib 8.0.30, for Win64 (x86_64)
--
-- Host: localhost    Database: sgrafika
-- ------------------------------------------------------
-- Server version	8.0.30

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `barang`
--

DROP TABLE IF EXISTS `barang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `barang` (
  `id` int NOT NULL AUTO_INCREMENT,
  `entity_id` int DEFAULT NULL,
  `nama` varchar(200) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `counter_dokumen`
--

DROP TABLE IF EXISTS `counter_dokumen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `counter_dokumen` (
  `id` int NOT NULL AUTO_INCREMENT,
  `entity_id` int DEFAULT NULL,
  `jenis` varchar(20) NOT NULL,
  `tahun` varchar(4) NOT NULL,
  `nomor_terakhir` int DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_counter` (`entity_id`,`jenis`,`tahun`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `entity`
--

DROP TABLE IF EXISTS `entity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `entity` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `logo_svg` varchar(255) DEFAULT NULL,
  `alamat` text,
  `no_telp` varchar(30) DEFAULT NULL,
  `kota` varchar(100) DEFAULT NULL,
  `kena_ppn` tinyint(1) DEFAULT '1',
  `direktur` varchar(100) DEFAULT NULL,
  `bank` varchar(100) DEFAULT NULL,
  `atas_nama` varchar(100) DEFAULT NULL,
  `no_rekening` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `klien`
--

DROP TABLE IF EXISTS `klien`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `klien` (
  `id` int NOT NULL AUTO_INCREMENT,
  `entity_id` int DEFAULT NULL,
  `nama_perusahaan` varchar(200) NOT NULL,
  `alamat` text,
  `npwp` varchar(30) DEFAULT NULL,
  `pic` varchar(100) DEFAULT NULL,
  `no_telp` varchar(30) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `proyek`
--

DROP TABLE IF EXISTS `proyek`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `proyek` (
  `id` int NOT NULL AUTO_INCREMENT,
  `entity_id` int DEFAULT NULL,
  `no_referensi` varchar(10) NOT NULL,
  `klien_id` int DEFAULT NULL,
  `tanggal` date NOT NULL DEFAULT (curdate()),
  `berlaku_sampai` date NOT NULL DEFAULT ((curdate() + interval 1 month)),
  `no_sp` varchar(20) DEFAULT NULL,
  `no_sk` varchar(20) DEFAULT NULL,
  `no_inv` varchar(20) DEFAULT NULL,
  `no_sj` varchar(20) DEFAULT NULL,
  `no_ba` varchar(20) DEFAULT NULL,
  `no_proforma` varchar(20) DEFAULT NULL,
  `no_inv_dp` varchar(20) DEFAULT NULL,
  `no_inv_pelunasan` varchar(20) DEFAULT NULL,
  `diskon_persen` decimal(5,2) DEFAULT '0.00',
  `ppn_persen` decimal(5,2) DEFAULT '11.00',
  `sub_total` decimal(15,2) DEFAULT '0.00',
  `dpp` decimal(15,2) DEFAULT '0.00',
  `grand_total` decimal(15,2) DEFAULT '0.00',
  `dibuat_oleh` int DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `dp_persen` decimal(5,1) DEFAULT '50.0',
  `waktu_pelaksanaan_hari` int DEFAULT '7',
  PRIMARY KEY (`id`),
  UNIQUE KEY `no_referensi` (`no_referensi`)
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `proyek_item`
--

DROP TABLE IF EXISTS `proyek_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `proyek_item` (
  `id` int NOT NULL AUTO_INCREMENT,
  `proyek_id` int NOT NULL,
  `no_urut` int NOT NULL,
  `barang_id` int DEFAULT NULL,
  `kategori` varchar(20) DEFAULT 'barang',
  `keterangan` text,
  `harga` decimal(15,2) NOT NULL,
  `qty` decimal(10,2) NOT NULL,
  `satuan` varchar(20) DEFAULT '',
  `jumlah` decimal(15,2) GENERATED ALWAYS AS ((`harga` * `qty`)) STORED,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=326 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `proyek_tahap_pembayaran`
--

DROP TABLE IF EXISTS `proyek_tahap_pembayaran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `proyek_tahap_pembayaran` (
  `id` int NOT NULL AUTO_INCREMENT,
  `proyek_id` int NOT NULL,
  `urutan` int NOT NULL,
  `persentase` decimal(5,1) NOT NULL,
  `deskripsi` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `proyek_id` (`proyek_id`),
  CONSTRAINT `proyek_tahap_pembayaran_ibfk_1` FOREIGN KEY (`proyek_id`) REFERENCES `proyek` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=217 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `proyek_term`
--

DROP TABLE IF EXISTS `proyek_term`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `proyek_term` (
  `id` int NOT NULL AUTO_INCREMENT,
  `proyek_id` int NOT NULL,
  `urutan` int NOT NULL,
  `deskripsi` varchar(200) DEFAULT '',
  `dasar` enum('barang','pekerjaan','grand_total') DEFAULT 'barang',
  `persen` decimal(5,2) DEFAULT '0.00',
  `jumlah` decimal(15,2) DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `proyek_id` (`proyek_id`),
  CONSTRAINT `proyek_term_ibfk_1` FOREIGN KEY (`proyek_id`) REFERENCES `proyek` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `entity_id` int DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL,
  `active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  CONSTRAINT `users_chk_1` CHECK ((`role` in (_utf8mb4'super_admin',_utf8mb4'owner',_utf8mb4'admin',_utf8mb4'karyawan',_utf8mb4'freelance')))
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping routines for database 'sgrafika'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-08 16:56:47
