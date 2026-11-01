-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jul 04, 2026 at 05:52 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_tokobunga`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id_admin` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id_admin`, `username`, `password`) VALUES
(1, 'admin', '0192023a7bbd73250516f069df18b500');

-- --------------------------------------------------------

--
-- Table structure for table `faq`
--

CREATE TABLE `faq` (
  `id_faq` int(11) NOT NULL,
  `pertanyaan` text NOT NULL,
  `jawaban` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faq`
--

INSERT INTO `faq` (`id_faq`, `pertanyaan`, `jawaban`) VALUES
(1, 'Bagaimana cara memesan bunga di Kami Florist?', 'Sangat mudah! Anda cukup memilih bunga dari katalog kami, menambahkannya ke keranjang, lalu mengisi form detail pesanan. Setelah klik \"Kirim Order\", Anda akan otomatis terhubung dengan WhatsApp Admin kami untuk konfirmasi dan pembayaran.'),
(2, 'Apakah pesanan bisa dikirimkan ke alamat penerima?', 'Tentu! Kami melayani pengiriman menggunakan layanan Gosend (bisa dipesankan dari pihak pembeli) maupun opsi pengambilan langsung ke toko fisik kami (Self-pickup).'),
(3, 'Apakah melayani pesanan mendadak atau Same Day Delivery?', 'Ya, kami melayani pesanan Same Day untuk produk yang ready stock. Namun, untuk ketersediaan bunga terbaik dan desain yang maksimal, kami sangat menyarankan Anda memesan minimal H-1 atau H-2.'),
(4, 'Bagaimana sistem pembayarannya? Apakah wajib DP?', 'Pembayaran dapat dilakukan via QRIS, Transfer BCA, atau Cash (jika ke toko). Perlu diperhatikan, wajib melakukan DP (Down Payment) atau Full Transfer untuk total pesanan di atas Rp 100.000 serta untuk semua jenis pemesanan Custom Bouquet.'),
(5, 'Bisakah saya custom warna bunga atau warna kertas (wrapping)?', 'Sangat bisa! Anda dapat menuliskan tema warna bunga dan warna wrapping impian Anda pada form keranjang saat checkout. Namun untuk beberapa jenis bunga tidak bisa di pilih'),
(6, 'Apakah harga sudah termasuk kartu ucapan (Greeting Card)?', 'Ya, setiap pembelian produk bunga di Kami Florist sudah mendapatkan fasilitas kartu ucapan eksklusif secara GRATIS. Anda bisa mengetik isi pesannya langsung di form pesanan, dan tim kami akan mencetakkannya untuk Anda.'),
(7, 'Berapa lama buket bunga segar (fresh flowers) bisa bertahan?', 'Bunga segar kami umumnya bertahan 3-5 hari tergantung pada suhu ruangan dan perawatannya. Kami menyarankan untuk menjauhkan bunga dari sinar matahari langsung dan meletakkannya di ruangan sejuk atau ber-AC.'),
(8, 'Apakah saya bisa mengubah detail pesanan atau membatalkannya?', 'Perubahan detail pesanan (seperti isi ucapan atau jam ambil) maksimal dilakukan H-1 sebelum waktu pengambilan. Pesanan custom yang sudah terproses atau dibatalkan sepihak pada hari H tidak dapat dibatalkan atau direfund (DP hangus).'),
(9, 'Apakah Kami Florist melayani pengiriman bunga ke luar kota?', 'Demi menjaga kualitas, kesegaran, dan keutuhan rangkaian bunga, untuk saat ini kami hanya melayani pengiriman di dalam kota saja (menggunakan kurir instan/mobil).'),
(10, 'Jam berapa saja toko dan admin Kami Florist beroperasi?', 'Toko fisik dan layanan pelanggan WhatsApp kami beroperasi setiap hari mulai pukul 09:00 hingga 19:00 waktu setempat. Pengambilan pesanan paling awal dapat dilakukan pada pukul 09:00 pagi.');

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id_kategori` int(11) NOT NULL,
  `nama_kategori` varchar(100) NOT NULL,
  `opsi_kustom` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`id_kategori`, `nama_kategori`, `opsi_kustom`) VALUES
(1, 'Bunga Papan', 'ucapan,catatan'),
(2, 'Buket Bunga', 'warna_bunga,warna_wrap,ucapan,catatan'),
(3, 'Bunga Meja', 'warna_bunga,ucapan,catatan');

-- --------------------------------------------------------

--
-- Table structure for table `pengaturan`
--

CREATE TABLE `pengaturan` (
  `kunci` varchar(50) NOT NULL,
  `nilai` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengaturan`
--

INSERT INTO `pengaturan` (`kunci`, `nilai`) VALUES
('link_maps', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d673.9889795009793!2d115.17196466232565!3d-8.7984672744633!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2dd244bc39fb3da7%3A0x6cae8cb2a990cae5!2sRektorat%20Universitas%20Udayana!5e1!3m2!1sid!2sid!4v1783173130431!5m2!1sid!2sid\" width=\"600\" height=\"450\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"strict-origin-when-cross-origin');

-- --------------------------------------------------------

--
-- Table structure for table `produk`
--

CREATE TABLE `produk` (
  `id_produk` int(11) NOT NULL,
  `id_kategori` int(11) NOT NULL,
  `nama_produk` varchar(150) NOT NULL,
  `deskripsi` text NOT NULL,
  `harga` int(11) NOT NULL,
  `foto` varchar(255) NOT NULL,
  `stok` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `produk`
--

INSERT INTO `produk` (`id_produk`, `id_kategori`, `nama_produk`, `deskripsi`, `harga`, `foto`, `stok`) VALUES
(2, 2, 'thumbelina bouquet', 'thumbelina bouquet untuk pacar', 300000, '1783160291_images-9.jpeg', 1000),
(3, 2, 'Hand-Tied Bouquet', 'bunga simple', 200000, '1783176726_images-10.jpeg', 100),
(4, 2, 'Posy Bouquet', 'bunga kecil', 75000, '1783176795_images-11.jpeg', 100),
(5, 2, 'Cascading (Waterfall) Bouquet', 'bunga yang indah', 400000, '1783176892_images-12.jpeg', 100),
(6, 2, 'Balloon Bouquet', 'bungaisi balon', 120000, '1783177015_images-13.jpeg', 100),
(7, 2, 'Dried Flower / Rustic Bouquet', 'bunga kering', 230000, '1783177328_images-14.jpeg', 100),
(8, 2, 'Artificial / Ribbon Bouquet', 'bunga palsu', 350000, '1783177422_images-15.jpeg', 100),
(9, 2, 'Petite / Mini Bouquet:', 'bunga murah', 50000, '1783177627_images-16.jpeg', 100);

-- --------------------------------------------------------

--
-- Table structure for table `sosmed`
--

CREATE TABLE `sosmed` (
  `id_sosmed` int(11) NOT NULL,
  `nama_sosmed` varchar(50) NOT NULL,
  `icon_sosmed` varchar(50) NOT NULL,
  `link_sosmed` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sosmed`
--

INSERT INTO `sosmed` (`id_sosmed`, `nama_sosmed`, `icon_sosmed`, `link_sosmed`) VALUES
(1, 'Instagram', 'bi-instagram', 'https://instagram.com/'),
(2, 'WhatsApp', 'bi-whatsapp', 'https://wa.me/6287753917059'),
(3, 'TikTok', 'bi-tiktok', 'https://tiktok.com/');

-- --------------------------------------------------------

--
-- Table structure for table `ulasan`
--

CREATE TABLE `ulasan` (
  `id_ulasan` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `isi_masukan` text NOT NULL,
  `tanggal` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ulasan`
--

INSERT INTO `ulasan` (`id_ulasan`, `nama`, `isi_masukan`, `tanggal`) VALUES
(10, 'janata', 'webnya bagus', '2026-07-04 15:08:46'),
(11, 'krishna', 'bagus', '2026-07-04 15:16:04');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id_admin`);

--
-- Indexes for table `faq`
--
ALTER TABLE `faq`
  ADD PRIMARY KEY (`id_faq`);

--
-- Indexes for table `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id_kategori`);

--
-- Indexes for table `pengaturan`
--
ALTER TABLE `pengaturan`
  ADD PRIMARY KEY (`kunci`);

--
-- Indexes for table `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id_produk`),
  ADD KEY `id_kategori` (`id_kategori`);

--
-- Indexes for table `sosmed`
--
ALTER TABLE `sosmed`
  ADD PRIMARY KEY (`id_sosmed`);

--
-- Indexes for table `ulasan`
--
ALTER TABLE `ulasan`
  ADD PRIMARY KEY (`id_ulasan`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `faq`
--
ALTER TABLE `faq`
  MODIFY `id_faq` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id_kategori` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `produk`
--
ALTER TABLE `produk`
  MODIFY `id_produk` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `sosmed`
--
ALTER TABLE `sosmed`
  MODIFY `id_sosmed` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `ulasan`
--
ALTER TABLE `ulasan`
  MODIFY `id_ulasan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `produk`
--
ALTER TABLE `produk`
  ADD CONSTRAINT `produk_ibfk_1` FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`) ON DELETE CASCADE;
-- --------------------------------------------------------

--
-- Table structure for table `pesanan` & `detail_pesanan` (tracking & riwayat pesanan)
--

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

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
