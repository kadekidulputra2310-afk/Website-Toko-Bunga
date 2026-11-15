-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 14 Nov 2026 pada 20.52
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.0.30

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
-- Struktur dari tabel `admin`
--

CREATE TABLE `admin` (
  `id_admin` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `admin`
--

INSERT INTO `admin` (`id_admin`, `username`, `password`) VALUES
(1, 'admin', '0192023a7bbd73250516f069df18b500');

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_pesanan`
--

CREATE TABLE `detail_pesanan` (
  `id_detail` int(11) NOT NULL,
  `id_pesanan` int(11) NOT NULL,
  `id_produk` int(11) DEFAULT NULL,
  `nama_produk` varchar(150) NOT NULL,
  `harga` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `warna_bunga` varchar(150) DEFAULT NULL,
  `warna_wrapping` varchar(150) DEFAULT NULL,
  `catatan` varchar(500) DEFAULT NULL,
  `ucapan` varchar(500) DEFAULT NULL,
  `foto_referensi` varchar(150) DEFAULT NULL,
  `jenis_ucapan` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `detail_pesanan`
--

INSERT INTO `detail_pesanan` (`id_detail`, `id_pesanan`, `id_produk`, `nama_produk`, `harga`, `qty`, `warna_bunga`, `warna_wrapping`, `catatan`, `ucapan`, `foto_referensi`, `jenis_ucapan`) VALUES
(1, 1, 6, 'Dried Flower / Rustic Bouquet', 230000, 1, 'Merah', 'Putih', 'sass', NULL, NULL, NULL),
(2, 2, 11, 'Karangan Bunga Kustom Sendiri', 3000000, 1, 'Merah', '', 'buat yang cepat', 'selamat wisuda', '14aed53708cf944e.jpg', 'Selamat Wisuda'),
(3, 3, 10, 'Karangan Bunga Style 1', 1000000, 1, 'Biru', '', '', 'Grand Opening ningnangnung', '', 'Selamat Grand Opening');

-- --------------------------------------------------------

--
-- Struktur dari tabel `faq`
--

CREATE TABLE `faq` (
  `id_faq` int(11) NOT NULL,
  `pertanyaan` text NOT NULL,
  `jawaban` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `faq`
--

INSERT INTO `faq` (`id_faq`, `pertanyaan`, `jawaban`) VALUES
(1, 'Bagaimana cara memesan bunga di Kami Florist?', 'Sangat mudah! Anda cukup memilih bunga dari katalog kami, menambahkannya ke keranjang, lalu mengisi form detail pesanan. Setelah klik \"Kirim Order\", Anda akan otomatis terhubung dengan WhatsApp Admin kami untuk konfirmasi dan pembayaran.'),
(2, 'Apakah pesanan bisa dikirimkan ke alamat penerima?', 'Tentu! Kami melayani pengiriman menggunakan layanan Gosend (bisa dipesankan dari pihak pembeli) maupun opsi pengambilan langsung ke toko fisik kami (Self-pickup).'),
(3, 'Apakah melayani pesanan mendadak atau Same Day Delivery?', 'Ya, kami melayani pesanan Same Day untuk produk yang ready stock. Namun, untuk ketersediaan bunga terbaik dan desain yang maksimal, kami sangat menyarankan Anda memesan minimal H-1 atau H-2.'),
(4, 'Bagaimana sistem pembayarannya? Apakah wajib DP?', 'Pembayaran dapat dilakukan via QRIS, Transfer BCA, atau Cash (jika ke toko). Perlu diperhatikan, wajib melakukan DP (Down Payment) atau Full Transfer untuk total pesanan di atas Rp 100.000 serta untuk semua jenis pemesanan Custom Bouquet.'),
(5, 'Bisakah saya custom warna bunga atau warna kertas (wrapping)?', 'Sangat bisa! Anda dapat menuliskan tema warna bunga dan warna wrapping impian Anda pada form keranjang saat checkout. Namun untuk beberapa jenis bunga tidak bisa dipilih.'),
(6, 'Apakah harga sudah termasuk kartu ucapan (Greeting Card)?', 'Ya, setiap pembelian produk bunga di Kami Florist sudah mendapatkan fasilitas kartu ucapan eksklusif secara GRATIS. Anda bisa mengetik isi pesannya langsung di form pesanan, dan tim kami akan mencetakkannya untuk Anda.'),
(7, 'Berapa lama buket bunga segar (fresh flowers) bisa bertahan?', 'Bunga segar kami umumnya bertahan 3-5 hari tergantung pada suhu ruangan dan perawatannya. Kami menyarankan untuk menjauhkan bunga dari sinar matahari langsung dan meletakkannya di ruangan sejuk atau ber-AC.'),
(8, 'Apakah saya bisa mengubah detail pesanan atau membatalkannya?', 'Perubahan detail pesanan (seperti isi ucapan atau jam ambil) maksimal dilakukan H-1 sebelum waktu pengambilan. Pesanan custom yang sudah terproses atau dibatalkan sepihak pada hari H tidak dapat dibatalkan atau direfund (DP hangus).'),
(9, 'Apakah Kami Florist melayani pengiriman bunga ke luar kota?', 'Demi menjaga kualitas, kesegaran, dan keutuhan rangkaian bunga, untuk saat ini kami hanya melayani pengiriman di dalam kota saja (menggunakan kurir instan/mobil).'),
(10, 'Jam berapa saja toko dan admin Kami Florist beroperasi?', 'Toko fisik dan layanan pelanggan WhatsApp kami beroperasi setiap hari mulai pukul 09:00 hingga 19:00 waktu setempat. Pengambilan pesanan paling awal dapat dilakukan pada pukul 09:00 pagi.');

-- --------------------------------------------------------

--
-- Struktur dari tabel `kategori`
--

CREATE TABLE `kategori` (
  `id_kategori` int(11) NOT NULL,
  `nama_kategori` varchar(100) NOT NULL,
  `opsi_kustom` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kategori`
--

INSERT INTO `kategori` (`id_kategori`, `nama_kategori`, `opsi_kustom`) VALUES
(2, 'Handbouquet\r\n', NULL),
(4, 'Bunga Satuan', 'warna_bunga,catatan'),
(5, 'karangan Bunga', 'warna_bunga,jenis,ucapan,foto,catatan'),
(8, 'Standing Flower', 'warna_bunga,jenis,ucapan,foto,catatan'),
(9, 'Rangkaian Bunga Meja', 'warna_bunga,ucapan,foto,catatan'),
(10, 'Kalung Bunga', 'warna_bunga,foto,catatan');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengaturan`
--

CREATE TABLE `pengaturan` (
  `kunci` varchar(50) NOT NULL,
  `nilai` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pengaturan`
--

INSERT INTO `pengaturan` (`kunci`, `nilai`) VALUES
('link_maps', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d673.9889795009793!2d115.17196466232565!3d-8.7984672744633!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2dd244bc39fb3da7%3A0x6cae8cb2a990cae5!2sRektorat%20Universitas%20Udayana!5e1!3m2!1sid!2sid!4v1783173130431!5m2!1sid!2sid');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pesanan`
--

CREATE TABLE `pesanan` (
  `id_pesanan` int(11) NOT NULL,
  `kode_pesanan` varchar(20) NOT NULL,
  `token_pemesan` varchar(64) NOT NULL,
  `nama_pemesan` varchar(100) NOT NULL,
  `tema_warna` varchar(100) DEFAULT NULL,
  `warna_wrapping` varchar(100) DEFAULT NULL,
  `tanggal_ambil` date NOT NULL,
  `jam_ambil` time NOT NULL,
  `metode_ambil` varchar(50) NOT NULL,
  `metode_bayar` varchar(50) NOT NULL,
  `ucapan` text DEFAULT NULL,
  `total` int(11) NOT NULL,
  `status` enum('Diterima','Diproses','Selesai','Dibatalkan') NOT NULL DEFAULT 'Diterima',
  `dibuat_pada` datetime NOT NULL,
  `diperbarui_pada` datetime NOT NULL,
  `catatan_penjual` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pesanan`
--

INSERT INTO `pesanan` (`id_pesanan`, `kode_pesanan`, `token_pemesan`, `nama_pemesan`, `tema_warna`, `warna_wrapping`, `tanggal_ambil`, `jam_ambil`, `metode_ambil`, `metode_bayar`, `ucapan`, `total`, `status`, `dibuat_pada`, `diperbarui_pada`, `catatan_penjual`) VALUES
(1, 'KF-261102-D7J7N', '6082ac7fe1e1725e0f62dda2fd6101f3', 'krishna dafa', NULL, NULL, '2026-11-03', '12:03:00', 'Gosend Dari Pembeli', 'QRIS', 'dfsddfsd', 230000, 'Selesai', '2026-11-02 00:36:56', '2026-11-02 00:51:47', 'dsdasdsadadasdasdasdsa'),
(2, 'KF-261102-AJJK5', '6082ac7fe1e1725e0f62dda2fd6101f3', 'made krishna dafa', NULL, NULL, '2026-11-12', '12:03:00', 'Gosend Dari Pembeli', 'BCA', '', 3000000, 'Selesai', '2026-11-02 01:23:30', '2026-11-02 01:24:49', 'buat yang bagus dan cepat'),
(3, 'KF-261115-TZS3V', 'f6a572bdcaf27a9e3ac5ca9bc36686d0', 'Cristiano Ronaldo', NULL, NULL, '2026-11-23', '15:00:00', 'Ke Toko', 'BCA', '', 1000000, 'Diterima', '2026-11-15 01:12:09', '2026-11-15 01:12:09', '-');

-- --------------------------------------------------------

--
-- Struktur dari tabel `produk`
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
-- Dumping data untuk tabel `produk`
--

INSERT INTO `produk` (`id_produk`, `id_kategori`, `nama_produk`, `deskripsi`, `harga`, `foto`, `stok`) VALUES
(1, 2, 'Thumbelina Bouquet', 'Thumbelina bouquet yang manis, cocok untuk pasangan tercinta.', 300000, '1783160291_images-9.jpeg', 1000),
(2, 2, 'Hand-Tied Bouquet', 'Buket bunga simpel dan elegan.', 200000, '1783176726_images-10.jpeg', 100),
(3, 2, 'Posy Bouquet', 'Buket bunga kecil yang manis.', 75000, '1783176795_images-11.jpeg', 100),
(4, 2, 'Cascading (Waterfall) Bouquet', 'Buket bunga yang indah dengan rangkaian menjuntai.', 400000, '1783176892_images-12.jpeg', 100),
(5, 2, 'Balloon Bouquet', 'Buket bunga dengan isian balon.', 120000, '1783177015_images-13.jpeg', 100),
(6, 2, 'Dried Flower / Rustic Bouquet', 'Buket bunga kering bergaya rustic.', 230000, '1783177328_images-14.jpeg', 100),
(7, 2, 'Artificial / Ribbon Bouquet', 'Buket bunga artifisial dengan pita.', 350000, '1783177422_images-15.jpeg', 100),
(8, 2, 'Petite / Mini Bouquet', 'Buket bunga mini dengan harga terjangkau.', 50000, '1783177627_images-16.jpeg', 100),
(9, 4, 'Bunga Mawar', 'Bunga Mawar Yang Cantik', 4000, '1793551437_Supermarket flowers.jpg', 800),
(10, 5, 'Karangan Bunga Style 1', 'Karangan Bunga keren Dan Mewah', 1000000, '1793553647_PESAN BUNGA PAPAN DENGAN HARGA JUAL TERJANGKAU DI TOKO BUNGA BANDUNG.jpg', 10000),
(11, 5, 'Karangan Bunga Kustom Sendiri', 'Kustomisasi karangan bungamu sesuai keinginanmu', 3000000, '1793553697_images-21.jpeg', 10000),
(12, 5, 'Karangan Bunga Style 2', 'cocok untuk kedukaan', 2000000, '1793554010_Toko Karangan Bunga Murah Menteng Azalea florist Hp-Wa_ 0822-3304-5802 Online 24 jam Open Order Macam2 Karangan Bunga Papan Ucapan Duka Cita, Hanbouqet dll.jpg', 10000),
(13, 4, 'Bunga Sedap Malam', 'Bunga Yang Indah', 5000, '1793554156_ -2.jpg', 10000),
(14, 4, 'Bunga Lily', 'Bunga Cinta', 80000, '1793554185_pretty lily.jpg', 1000),
(16, 8, 'Standing Flower A01', 'Tersedia', 825000, '1794684274_standing-flowers-ulang-tahun.webp', 2),
(17, 8, 'Standing Flower A02', 'Tersedia', 920000, '1794684435_OIP (1).jpeg', 3),
(18, 9, 'Rangkaian Bunga Meja 01', 'Tersedia', 500000, '1794684641_rangkaian-bunga-meja9.jpg', 2),
(19, 9, 'Rangkaian Bunga Meja 02', 'Tersedia', 650000, '1794684783_id-11134207-7r992-lorkifz4exyz70.jpeg', 2),
(20, 10, 'Kalung Bunga Mawar', 'Tersedia', 100000, '1794684923_Screenshot_2026-05-21-17-59-52-63_99c04817c0de5652397fc8b56c3b3817-356x356.jpg', 2),
(21, 10, 'Kalung Bunga Melati', 'Tersedia', 240000, '1794685042_20161222antara-doeloe-bungamelati-001.jpg', 3);

-- --------------------------------------------------------

--
-- Struktur dari tabel `sosmed`
--

CREATE TABLE `sosmed` (
  `id_sosmed` int(11) NOT NULL,
  `nama_sosmed` varchar(50) NOT NULL,
  `icon_sosmed` varchar(50) NOT NULL,
  `link_sosmed` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `sosmed`
--

INSERT INTO `sosmed` (`id_sosmed`, `nama_sosmed`, `icon_sosmed`, `link_sosmed`) VALUES
(1, 'Instagram', 'bi-instagram', 'https://instagram.com/'),
(2, 'WhatsApp', 'bi-whatsapp', 'https://wa.me/6287753917059'),
(3, 'TikTok', 'bi-tiktok', 'https://tiktok.com/');

-- --------------------------------------------------------

--
-- Struktur dari tabel `ulasan`
--

CREATE TABLE `ulasan` (
  `id_ulasan` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `isi_masukan` text NOT NULL,
  `tanggal` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `ulasan`
--

INSERT INTO `ulasan` (`id_ulasan`, `nama`, `isi_masukan`, `tanggal`) VALUES
(1, 'janata', 'webnya bagus', '2026-07-04 15:08:46'),
(2, 'krishna', 'bagus', '2026-07-04 15:16:04'),
(3, 'krishna dafa', 'bunganya indah', '2026-10-26 05:04:48');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `uq_admin_username` (`username`);

--
-- Indeks untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_pesanan` (`id_pesanan`);

--
-- Indeks untuk tabel `faq`
--
ALTER TABLE `faq`
  ADD PRIMARY KEY (`id_faq`);

--
-- Indeks untuk tabel `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id_kategori`);

--
-- Indeks untuk tabel `pengaturan`
--
ALTER TABLE `pengaturan`
  ADD PRIMARY KEY (`kunci`);

--
-- Indeks untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  ADD PRIMARY KEY (`id_pesanan`),
  ADD UNIQUE KEY `kode_pesanan` (`kode_pesanan`),
  ADD KEY `token_pemesan` (`token_pemesan`),
  ADD KEY `status` (`status`);

--
-- Indeks untuk tabel `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id_produk`),
  ADD KEY `idx_produk_kategori` (`id_kategori`);

--
-- Indeks untuk tabel `sosmed`
--
ALTER TABLE `sosmed`
  ADD PRIMARY KEY (`id_sosmed`);

--
-- Indeks untuk tabel `ulasan`
--
ALTER TABLE `ulasan`
  ADD PRIMARY KEY (`id_ulasan`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `admin`
--
ALTER TABLE `admin`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `faq`
--
ALTER TABLE `faq`
  MODIFY `id_faq` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id_kategori` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  MODIFY `id_pesanan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `produk`
--
ALTER TABLE `produk`
  MODIFY `id_produk` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT untuk tabel `sosmed`
--
ALTER TABLE `sosmed`
  MODIFY `id_sosmed` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `ulasan`
--
ALTER TABLE `ulasan`
  MODIFY `id_ulasan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD CONSTRAINT `detail_pesanan_ibfk_1` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `produk`
--
ALTER TABLE `produk`
  ADD CONSTRAINT `produk_ibfk_1` FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
