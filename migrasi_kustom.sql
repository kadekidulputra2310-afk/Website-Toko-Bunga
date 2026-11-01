-- ------------------------------------------------------------
-- MIGRASI: Kustomisasi pesanan (warna bunga, warna wrapping, catatan untuk penjual)
-- Jalankan di phpMyAdmin (tab SQL) pada database `db_tokobunga` yang SUDAH ada
-- dan sudah memiliki tabel `pesanan` / `detail_pesanan`.
--
-- OPSIONAL: aplikasi sudah menambahkan kolom ini otomatis saat halaman
-- keranjang / riwayat / admin pertama kali dibuka. File ini disediakan kalau
-- kamu lebih suka menjalankannya manual.
-- Jalankan SEKALI saja (kalau kolom sudah ada, MySQL akan menampilkan error
-- "Duplicate column name" dan itu aman diabaikan).
-- ------------------------------------------------------------

ALTER TABLE `detail_pesanan`
  ADD COLUMN `warna_bunga`    varchar(150) DEFAULT NULL AFTER `qty`,
  ADD COLUMN `warna_wrapping` varchar(150) DEFAULT NULL AFTER `warna_bunga`,
  ADD COLUMN `catatan`        varchar(500) DEFAULT NULL AFTER `warna_wrapping`;

ALTER TABLE `pesanan`
  ADD COLUMN `catatan_penjual` text DEFAULT NULL AFTER `metode_bayar`;

-- ------------------------------------------------------------
-- TAHAP 2: pilihan isian modal per kategori + ucapan per produk
-- (juga otomatis ditambahkan aplikasi; jalankan manual hanya jika mau)
-- opsi_kustom berisi kunci dipisah koma: warna_bunga, warna_wrap, ucapan, catatan
-- NULL = pakai bawaan (warna_bunga, warna_wrap, catatan); kosong = tanpa pilihan kustom
-- ------------------------------------------------------------
ALTER TABLE `kategori`
  ADD COLUMN `opsi_kustom` varchar(100) DEFAULT NULL;

ALTER TABLE `detail_pesanan`
  ADD COLUMN `ucapan` varchar(500) DEFAULT NULL AFTER `warna_wrapping`;

-- TAHAP 3: foto referensi (karangan bunga custom)
ALTER TABLE `detail_pesanan`
  ADD COLUMN `foto_referensi` varchar(150) DEFAULT NULL AFTER `ucapan`;

-- TAHAP 4: jenis ucapan (ulang tahun, duka cita, grand opening, dll.)
ALTER TABLE `detail_pesanan`
  ADD COLUMN `jenis_ucapan` varchar(150) DEFAULT NULL AFTER `foto_referensi`;
