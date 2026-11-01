# 🌷 Kami Florist - E-Commerce & WhatsApp Order System

![PHP](https://img.shields.io/badge/PHP-Native-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)
![Chart.js](https://img.shields.io/badge/Chart.js-Analytics-FF6384?style=for-the-badge&logo=chartdotjs&logoColor=white)

**Kami Florist** adalah aplikasi web e-commerce berbasis katalog yang dirancang khusus untuk UMKM (terutama Toko Bunga/Florist). Aplikasi ini menggunakan sistem **Order-to-WhatsApp**, di mana pelanggan dapat memilih produk, memasukkannya ke keranjang, dan melakukan *checkout* yang akan otomatis merangkum pesanan menjadi format teks rapi dan mengirimkannya langsung ke WhatsApp Admin toko.

Sistem ini sangat ringan, responsif, dan dilengkapi dengan **Panel Admin Premium** untuk manajemen data secara *real-time*.

---

## Fitur Utama

### Halaman Pelanggan (Front-End)
- **Desain Premium & Responsif:** Tampilan modern berestetika *botanical pastel* yang nyaman dilihat di HP maupun Laptop.
- **Katalog Produk Dinamis:** Grid penuh dan rapi (otomatis menyesuaikan lebar layar) dengan pencarian, filter kategori, dan pengurutan harga/nama.
- **Kustomisasi Per Produk:** Klik *Pilih & Pesan* untuk memilih warna bunga (sampai 3 warna dari palet atau tulis sendiri, termasuk color picker), warna wrapping, jumlah, serta *Catatan untuk Penjual* untuk request lebih spesifik. Produk yang sama dengan pilihan berbeda tampil sebagai baris terpisah di keranjang.
- **Sistem Keranjang Belanja:** Menggunakan PHP Session (tanpa perlu pelanggan *login* / *register*).
- **Checkout to WhatsApp:** Formulir pesanan yang detail (Catatan untuk Penjual, Tanggal/Jam Ambil, Pengiriman, Pembayaran; warna & catatan tiap produk ikut terkirim) yang otomatis di- *generate* ke format pesan WhatsApp. Saat tombol *Kirim Order ke WhatsApp* diklik, pesanan **disimpan ke database** dengan kode unik (contoh `KF-261026-7QH3M`), keranjang otomatis dikosongkan, dan pelanggan diarahkan ke halaman Riwayat Pesanan.
- **Riwayat & Tracking Pesanan:** Pelanggan bisa memantau status pesanan (**Diterima → Diproses → Selesai**) lewat halaman `riwayat.php`, tanpa perlu login. Riwayat dikenali lewat cookie browser, dan pesanan juga bisa dilacak dari perangkat lain memakai kode pesanan.
- **FAQ & Google Maps Dinamis:** Pertanyaan umum dan peta lokasi yang bisa diatur langsung oleh Admin.
- **Kirim Masukan:** Formulir bagi pelanggan untuk mengirimkan kritik dan saran.

### Panel Admin (Back-End)
- **Sistem Keamanan:** Login akses khusus admin.
- **Dashboard Analytics:** Visualisasi data ringkasan total produk, FAQ, ulasan, serta grafik omset penjualan dan kategori favorit menggunakan **Chart.js**.
- **Manajemen Pesanan:** Tab *Pesanan* menampilkan semua pesanan masuk beserta detailnya. Admin mengubah status (Diterima / Diproses / Selesai / Dibatalkan) lewat dropdown, dan perubahan langsung terlihat oleh pelanggan. Dilengkapi filter status dan lencana jumlah pesanan baru.
- **Manajemen Katalog (CRUD):** Tambah, Edit, dan Hapus produk bunga beserta *upload* gambar.
- **Manajemen FAQ:** Mengatur pertanyaan yang sering diajukan untuk ditampilkan di halaman depan.
- **Manajemen Sosial Media:** Mengatur *link* dan ikon sosial media secara dinamis pada *footer* website.
- **Manajemen Peta:** Memperbarui tautan *embed iframe* Google Maps lokasi toko fisik.
- **Kotak Masuk Ulasan:** Melihat dan menghapus pesan/saran dari pelanggan.

---

## Teknologi yang Digunakan

- **Backend:** PHP Native (Procedural)
- **Database:** MySQL
- **Frontend:** HTML5, CSS3, Vanilla JavaScript
- **Framework CSS:** Bootstrap 5.3
- **Icons:** Bootstrap Icons
- **Grafik:** Chart.js
- **Font:** Google Fonts (Lora & Nunito Sans)

---

## Panduan Instalasi (Lokal)

Ikuti langkah-langkah berikut untuk menjalankan proyek ini di komputer lokal Anda menggunakan XAMPP, WAMP, atau aplikasi *server local* sejenisnya.

### 1. Persiapan
Pastikan Anda sudah menginstal **XAMPP** dan menjalankan *module* **Apache** serta **MySQL**.

### 2. Clone Repository
Buka terminal/CMD, lalu jalankan perintah berikut di dalam folder `htdocs` (jika menggunakan XAMPP):
```bash
git clone https://github.com/krishnadafaa/Website-Toko-Bunga.git
cd kami-florist
```
*(Atau Anda bisa langsung mengunduh file ZIP dari GitHub dan mengekstraknya ke folder `htdocs`).*

### 3. Setup Folder Upload
**PENTING:** Anda harus membuat sebuah folder kosong bernama `uploads` di dalam direktori proyek ini untuk tempat menyimpan gambar produk.
```bash
mkdir uploads
```

### 4. Setup Database
1. Buka browser dan akses `http://localhost/phpmyadmin`.
2. Buat database baru dengan nama **`db_tokobunga`**.
3. Import file `db_tokobunga.sql` yang telah disediakan di dalam repositori ini.
4. **Sudah pernah import database versi lama?** Cukup jalankan file `migrasi_pesanan.sql` (tab SQL di phpMyAdmin pada database `db_tokobunga`) untuk menambah tabel `pesanan` dan `detail_pesanan`. Data lama tidak terhapus. Kalau import dari awal memakai `db_tokobunga.sql` terbaru, langkah ini tidak perlu.
   Fitur kustomisasi menambah kolom `warna_bunga`, `warna_wrapping`, `catatan` (tabel `detail_pesanan`) dan `catatan_penjual` (tabel `pesanan`). Kolom ini **ditambahkan otomatis** saat halaman keranjang/riwayat/admin dibuka; `migrasi_kustom.sql` disediakan bila ingin menjalankannya manual.
5. Pastikan file `config.php` sudah sesuai dengan kredensial database Anda (secara *default* user: `root` dan password: `(kosong)`).

### 5. Akses Aplikasi
- **Halaman Pelanggan:** Buka `http://localhost/kami-florist/index.php`
- **Riwayat & Tracking Pesanan:** Buka `http://localhost/kami-florist/riwayat.php`
- **Halaman Admin:** Buka `http://localhost/kami-florist/admin.php`

**Akun Login Default Admin:**
- **Username:** `admin`
- **Password:** `admin123`

---

## Struktur Direktori

```text
kami-florist/
│
├── config.php            # Koneksi Database
├── index.php             # Halaman Utama (Katalog & Landing Page)
├── keranjang.php         # Halaman Keranjang & Form Checkout (menyimpan pesanan lalu membuka WhatsApp)
├── riwayat.php           # Riwayat & Tracking Status Pesanan pelanggan
├── helpers.php           # Fungsi bersama (status pesanan, kode pesanan, format pesan WhatsApp)
├── assets/
│   └── tema.css          # Tema tampilan bersama (index, keranjang, riwayat)
├── admin.php             # Panel Dashboard Admin (Manajemen Utama)
├── login.php             # Form Login Admin
├── logout.php            # Proses penghancuran Session
├── db_tokobunga.sql      # File Backup Database (Export)
├── migrasi_pesanan.sql   # Tambahan tabel pesanan untuk database yang sudah ada
├── migrasi_kustom.sql    # Kolom kustomisasi (warna bunga/wrap, catatan) - opsional, otomatis juga
├── README.md             # Dokumentasi Proyek
└── uploads/              # Direktori penyimpanan gambar (Buat manual jika belum ada)
```

---

## Berkontribusi
Kontribusi selalu terbuka! Jika Anda memiliki saran untuk meningkatkan sistem ini, silakan *fork* repositori ini dan buat *pull request*, atau cukup buka *Issue* baru dengan tag "enhancement".

---

## Lisensi
Didistribusikan di bawah Lisensi MIT. Lihat `LICENSE` untuk informasi lebih lanjut.

---
⭐ Jangan lupa berikan bintang (star) jika proyek ini membantu Anda!
