-- ------------------------------------------------------------
-- MIGRASI: Fitur Tracking Pesanan & Riwayat Pesanan
-- Jalankan file ini di phpMyAdmin (tab SQL) pada database `db_tokobunga`
-- yang SUDAH ada. Aman dijalankan berulang kali (IF NOT EXISTS).
-- Kalau kamu import db_tokobunga.sql versi baru, file ini TIDAK perlu dijalankan.
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `pesanan` (
  `id_pesanan` int(11) NOT NULL AUTO_INCREMENT,
  `kode_pesanan` varchar(20) NOT NULL,
  `token_pemesan` varchar(64) NOT NULL,
  `nama_pemesan` varchar(100) NOT NULL,
  `tema_warna` varchar(100) DEFAULT NULL,
  `warna_wrapping` varchar(100) DEFAULT NULL,
  `tanggal_ambil` date NOT NULL,
  `jam_ambil` time NOT NULL,
  `metode_ambil` varchar(50) NOT NULL,
  `metode_bayar` varchar(50) NOT NULL,
  `catatan_penjual` text DEFAULT NULL,
  `ucapan` text DEFAULT NULL,
  `total` int(11) NOT NULL,
  `status` enum('Diterima','Diproses','Selesai','Dibatalkan') NOT NULL DEFAULT 'Diterima',
  `dibuat_pada` datetime NOT NULL,
  `diperbarui_pada` datetime NOT NULL,
  PRIMARY KEY (`id_pesanan`),
  UNIQUE KEY `kode_pesanan` (`kode_pesanan`),
  KEY `token_pemesan` (`token_pemesan`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- id_produk sengaja TIDAK diberi foreign key: kalau produk dihapus admin,
-- riwayat pesanan lama tetap utuh karena nama & harga disimpan sebagai salinan.
CREATE TABLE IF NOT EXISTS `detail_pesanan` (
  `id_detail` int(11) NOT NULL AUTO_INCREMENT,
  `id_pesanan` int(11) NOT NULL,
  `id_produk` int(11) DEFAULT NULL,
  `nama_produk` varchar(150) NOT NULL,
  `harga` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `warna_bunga` varchar(150) DEFAULT NULL,
  `warna_wrapping` varchar(150) DEFAULT NULL,
  `ucapan` varchar(500) DEFAULT NULL,
  `foto_referensi` varchar(150) DEFAULT NULL,
  `jenis_ucapan` varchar(150) DEFAULT NULL,
  `catatan` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id_detail`),
  KEY `id_pesanan` (`id_pesanan`),
  CONSTRAINT `detail_pesanan_ibfk_1` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
