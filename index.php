<?php
session_start();
require 'config.php';
require 'helpers.php';

pastikan_skema($conn); // tambah kolom baru (opsi kustom kategori, ucapan per produk) bila database masih versi lama

if (isset($_POST['add_to_cart'])) {
    $id_produk = (int)($_POST['id_produk'] ?? 0);

    // Harga, nama, dan stok SELALU diambil dari database (bukan dari kiriman browser)
    $st = mysqli_prepare($conn, "SELECT p.id_produk, p.nama_produk, p.harga, p.stok, k.opsi_kustom FROM produk p LEFT JOIN kategori k ON p.id_kategori = k.id_kategori WHERE p.id_produk = ?");
    mysqli_stmt_bind_param($st, 'i', $id_produk);
    mysqli_stmt_execute($st);
    $prod = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);

    if (!$prod || (int)$prod['stok'] <= 0) {
        header("Location: index.php?msg=produk_habis#katalog"); exit;
    }

    $qty = max(1, min(99, (int)($_POST['qty'] ?? 1)));
    $qty = min($qty, (int)$prod['stok']);

    // Kustomisasi: hanya isian yang diaktifkan admin untuk kategori produk ini yang diterima
    $opsi = opsi_aktif($prod['opsi_kustom']);
    $warna_bunga = in_array('warna_bunga', $opsi, true)
        ? gabung_warna($_POST['pal_bunga'] ?? [], PALET_BUNGA, $_POST['bunga_teks'] ?? '', $_POST['bunga_hex'] ?? '', !empty($_POST['bunga_hex_pakai']), MAKS_WARNA_BUNGA) : '';
    $warna_wrap  = in_array('warna_wrap', $opsi, true)
        ? gabung_warna($_POST['pal_wrap'] ?? '',  PALET_WRAP,  $_POST['wrap_teks'] ?? '',  $_POST['wrap_hex'] ?? '',  !empty($_POST['wrap_hex_pakai']), 1) : '';
    $ucapan      = in_array('ucapan', $opsi, true)  ? rapikan_teks($_POST['ucapan_item'] ?? '', 300) : '';
    $catatan     = in_array('catatan', $opsi, true) ? rapikan_teks($_POST['catatan'] ?? '', 300) : '';
    $jenis_ucapan = in_array('jenis', $opsi, true) ? gabung_jenis_ucapan($_POST['jenis_pilih'] ?? '', $_POST['jenis_teks'] ?? '') : '';
    $foto_ref    = '';
    if (in_array('foto', $opsi, true)) {
        [$foto_ref, $err_foto] = simpan_foto_referensi($_FILES['foto_ref'] ?? null);
        if ($err_foto !== '') { header("Location: index.php?msg=foto_gagal#katalog"); exit; }
    }

    $kunci = kunci_keranjang($id_produk, $warna_bunga, $warna_wrap, $catatan, $ucapan, $foto_ref, $jenis_ucapan);

    if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; }
    if (isset($_SESSION['cart'][$kunci])) {
        $_SESSION['cart'][$kunci]['qty'] = min(99, $_SESSION['cart'][$kunci]['qty'] + $qty);
    } else {
        $_SESSION['cart'][$kunci] = [
            'id_produk'      => $id_produk,
            'nama'           => $prod['nama_produk'],
            'harga'          => (int)$prod['harga'],
            'qty'            => $qty,
            'warna_bunga'    => $warna_bunga,
            'warna_wrapping' => $warna_wrap,
            'ucapan'         => $ucapan,
            'foto_referensi' => $foto_ref,
            'jenis_ucapan'   => $jenis_ucapan,
            'catatan'        => $catatan,
        ];
    }
    header("Location: index.php?msg=cart_added#katalog"); exit;
}

if (isset($_POST['kirim_masukan'])) {
    $nama_masukan = mysqli_real_escape_string($conn, $_POST['nama_masukan']);
    $isi_masukan = mysqli_real_escape_string($conn, $_POST['isi_masukan']);
    mysqli_query($conn, "INSERT INTO ulasan (nama, isi_masukan) VALUES ('$nama_masukan', '$isi_masukan')");
    header("Location: index.php?msg=ulasan_success#kontak"); exit;
}

$query = "SELECT p.*, k.nama_kategori, k.opsi_kustom FROM produk p LEFT JOIN kategori k ON p.id_kategori = k.id_kategori ORDER BY p.id_produk ASC";
$result = mysqli_query($conn, $query);
$daftar_produk = [];
while ($r = mysqli_fetch_assoc($result)) { $daftar_produk[] = $r; }

// Kategori diambil dari tabel kategori (urut sesuai id). Kategori baru yang ditambah admin
// otomatis muncul di filter begitu sudah punya produk.
$daftar_kategori = []; // nama kategori => jumlah produk
$rk = mysqli_query($conn, "SELECT k.nama_kategori, COUNT(p.id_produk) AS jml FROM kategori k LEFT JOIN produk p ON p.id_kategori = k.id_kategori GROUP BY k.id_kategori, k.nama_kategori ORDER BY k.id_kategori ASC");
while ($k = mysqli_fetch_assoc($rk)) {
    if ((int)$k['jml'] > 0) $daftar_kategori[$k['nama_kategori']] = (int)$k['jml'];
}
$jumlah_item_keranjang = 0;
foreach (($_SESSION['cart'] ?? []) as $ci) { $jumlah_item_keranjang += (int)($ci['qty'] ?? 1); }

$query_faq = mysqli_query($conn, "SELECT * FROM faq ORDER BY id_faq ASC");

$query_maps = mysqli_query($conn, "SELECT nilai FROM pengaturan WHERE kunci='link_maps'");
$data_maps = mysqli_fetch_assoc($query_maps);
$link_maps = ($data_maps) ? $data_maps['nilai'] : '';

$query_sosmed = mysqli_query($conn, "SELECT * FROM sosmed ORDER BY id_sosmed ASC");

// Jumlah pesanan yang masih berjalan (Diterima/Diproses) milik perangkat ini
$jumlah_aktif = 0;
if ($tok = token_pemesan()) {
    $st = mysqli_prepare($conn, "SELECT COUNT(*) FROM pesanan WHERE token_pemesan = ? AND status IN ('Diterima','Diproses')");
    mysqli_stmt_bind_param($st, 's', $tok);
    mysqli_stmt_execute($st);
    mysqli_stmt_bind_result($st, $jumlah_aktif);
    mysqli_stmt_fetch($st);
    mysqli_stmt_close($st);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kami Florist - Premium Botanical</title>
    <link rel="icon" type="image/svg+xml" href="assets/favicon.svg">
    <link rel="alternate icon" href="assets/favicon.ico">
    <link rel="apple-touch-icon" href="assets/apple-touch-icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,500;0,600;1,500&family=Nunito+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/tema.css">

    <style>
        /* ---------- Hero ---------- */
        .hero { background: linear-gradient(180deg, rgba(40, 30, 33, 0.38) 0%, rgba(40, 30, 33, 0.58) 100%), url('https://images.unsplash.com/photo-1563241527-3004b7be0ffd?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') center/cover no-repeat; min-height: 100vh; min-height: 100svh; display: flex; align-items: center; text-align: center; color: white; padding-top: 70px; }
        .hero .eyebrow { display: inline-block; letter-spacing: 3px; text-transform: uppercase; font-size: 0.75rem; font-weight: 700; padding: 7px 18px; border: 1px solid rgba(255,255,255,0.5); border-radius: 50px; background: rgba(255,255,255,0.08); backdrop-filter: blur(4px); margin-bottom: 24px; }
        .hero h1 { font-size: 3.6rem; font-weight: 600; letter-spacing: 1px; line-height: 1.15; text-shadow: 0 2px 14px rgba(0,0,0,0.25); }
        .hero p { font-size: 1.15rem; font-weight: 300; max-width: 600px; margin: 0 auto 36px; }
        .btn-hero-ghost { border: 1.5px solid rgba(255,255,255,0.85); color: #fff; background: transparent; padding: 12px 28px; font-weight: 600; border-radius: 50px; transition: all 0.3s; }
        .btn-hero-ghost:hover { background: #fff; color: var(--primary-hover); }

        /* ---------- Cara pesan ---------- */
        .langkah-card { text-align: center; padding: 8px 18px; }
        .langkah-ikon { width: 68px; height: 68px; border-radius: 50%; background: var(--primary-soft); color: var(--primary); display: inline-flex; align-items: center; justify-content: center; font-size: 1.7rem; margin-bottom: 16px; position: relative; }
        .langkah-ikon .no { position: absolute; top: -4px; right: -4px; width: 24px; height: 24px; border-radius: 50%; background: var(--primary); color: #fff; font-size: 0.75rem; font-weight: 700; display: flex; align-items: center; justify-content: center; }
        .langkah-card h6 { font-family: 'Lora', serif; font-weight: 600; }
        .langkah-card p { font-size: 0.9rem; color: var(--text-muted); margin-bottom: 0; }

        /* ---------- Katalog: toolbar + grid penuh ---------- */
        .katalog-wrap { max-width: 1320px; }
        .toolbar-katalog { background: #fff; border-radius: var(--radius); box-shadow: var(--shadow-soft); padding: 14px 16px; margin-bottom: 26px; }
        .cari-box { position: relative; flex: 1 1 260px; }
        .cari-box i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none; }
        .cari-box input { padding-left: 40px; }
        .chip-filter { border: 1px solid #eadfe1; background: #fff; color: var(--text-dark); border-radius: 50px; padding: 7px 16px; font-size: .86rem; font-weight: 600; transition: all .2s; white-space: nowrap; }
        .chip-filter .n { opacity: .6; font-weight: 400; margin-left: 4px; }
        .chip-filter:hover { border-color: var(--primary); color: var(--primary); }
        .chip-filter.aktif { background: var(--primary); border-color: var(--primary); color: #fff; }
        .jumlah-hasil { font-size: .85rem; color: var(--text-muted); }

        .grid-produk { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 24px; }
        .card-produk { border: none; border-radius: var(--radius); background: #fff; overflow: hidden; transition: all 0.3s ease; box-shadow: var(--shadow-soft); display: flex; flex-direction: column; cursor: pointer; }
        .card-produk:hover { transform: translateY(-5px); box-shadow: var(--shadow-lift); }
        .card-produk .thumb { position: relative; overflow: hidden; aspect-ratio: 1 / 1; background: var(--bg-cream); }
        .card-produk img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; display: block; }
        .card-produk:hover img { transform: scale(1.06); }
        .card-produk.habis img { filter: grayscale(0.85); opacity: 0.7; }
        .card-produk.habis { cursor: default; }
        .card-produk.habis:hover { transform: none; box-shadow: var(--shadow-soft); }
        .card-produk .isi { padding: 16px 16px 18px; display: flex; flex-direction: column; flex: 1; }
        .card-produk .nama { font-family: 'Lora', serif; font-weight: 600; font-size: 1.05rem; line-height: 1.3; margin: 0 0 4px; color: var(--text-dark); }
        .card-produk .desk { font-size: .85rem; color: var(--text-muted); margin: 0 0 12px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 2.6em; }
        .card-produk .baris-bawah { margin-top: auto; display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .chip { position: absolute; top: 12px; font-size: 0.72rem; font-weight: 700; padding: 4px 12px; border-radius: 50px; letter-spacing: 0.3px; }
        .chip-kategori { left: 12px; background: rgba(255,255,255,0.93); color: var(--primary-hover); }
        .chip-stok { right: 12px; background: #fff4de; color: #a56a00; }
        .chip-habis { right: 12px; background: #3a3d44; color: #fff; }
        .chip-kustom { left: 12px; bottom: 12px; top: auto; background: rgba(55,59,68,.78); color: #fff; font-weight: 600; backdrop-filter: blur(4px); }
        .harga { color: var(--primary); font-family: 'Lora', serif; font-weight: 600; font-size: 1.1rem; margin: 0; white-space: nowrap; }
        .btn-tambah { display: inline-flex; align-items: center; justify-content: center; gap: 6px; border: 1.5px solid var(--primary); background: #fff; color: var(--primary); border-radius: 50px; padding: 6px 14px; font-size: .82rem; font-weight: 700; line-height: 1.2; white-space: nowrap; transition: all .2s; }
        .btn-tambah:hover { background: var(--primary); color: #fff; }
        .btn-tambah:disabled { border-color: #ddd; color: #aaa; background: #f5f5f5; cursor: not-allowed; }
        .filter-kat { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-top: 14px; }
        .filter-kat .label-kat { font-size: .8rem; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; color: var(--text-muted); margin-right: 4px; }

        /* ---------- Nomor halaman ---------- */
        .paginasi { display: flex; justify-content: center; align-items: center; gap: 6px; flex-wrap: wrap; margin-top: 34px; }
        .paginasi button { min-width: 40px; height: 40px; padding: 0 8px; border-radius: 50px; border: 1px solid #eadfe1; background: #fff; color: var(--text-dark); font-weight: 700; font-size: .9rem; transition: all .2s; }
        .paginasi button:hover:not(:disabled):not(.aktif) { border-color: var(--primary); color: var(--primary); }
        .paginasi button.aktif { background: var(--primary); border-color: var(--primary); color: #fff; cursor: default; }
        .paginasi button:disabled { opacity: .4; cursor: not-allowed; }
        .paginasi .titik { color: var(--text-muted); padding: 0 4px; }
        .kosong-hasil { display: none; text-align: center; padding: 50px 20px; color: var(--text-muted); }

        /* ---------- Modal kustomisasi pesanan ---------- */
        /* Form jadi kolom flex: isi bisa di-scroll, tombol Tambah ke Keranjang selalu terlihat */
        #formPesan { display: flex; flex-direction: column; max-height: calc(100vh - 2rem); max-height: calc(100dvh - 2rem); }
        #formPesan .modal-body { flex: 1 1 auto; min-height: 0; overflow-y: auto; }
        #formPesan .modal-footer { flex: 0 0 auto; }
        .pm-media { background: var(--bg-cream); align-self: flex-start; position: sticky; top: 0; }
        .pm-foto-box { aspect-ratio: 4 / 5; max-height: 62vh; width: 100%; background: #efe9e2; display: flex; align-items: center; justify-content: center; }
        .pm-foto-box img { width: 100%; height: 100%; object-fit: contain; display: block; }
        .pm-info { padding: 16px 20px 20px; background: #fff; }
        .pm-info h5 { font-family: 'Lora', serif; font-weight: 600; margin: 0 0 4px; }
        .pm-info .harga { font-size: 1.15rem; }
        .pm-info #pmDesk { color: var(--text-muted); margin-top: 6px; }
        .pm-judul { font-weight: 700; font-size: .95rem; margin-bottom: 2px; }
        .pm-hint { font-size: .78rem; color: var(--text-muted); margin-bottom: 10px; }
        .pm-kosong { background: var(--bg-cream); border-radius: 12px; padding: 12px 14px; font-size: .88rem; color: var(--text-muted); margin-bottom: 20px; }
        .pm-ringkas { font-size: .8rem; color: var(--primary-hover); font-weight: 600; min-height: 1.2em; margin-top: 8px; }

        .swatches { display: flex; flex-wrap: wrap; gap: 8px; }
        .swatches > span { position: relative; }
        .sw-in { position: absolute; opacity: 0; pointer-events: none; }
        .sw { display: inline-flex; align-items: center; gap: 7px; padding: 5px 12px 5px 6px; border: 1.5px solid #eadfe1; border-radius: 50px; font-size: .82rem; font-weight: 600; cursor: pointer; background: #fff; transition: all .15s; user-select: none; }
        .sw .dot { width: 20px; height: 20px; border-radius: 50%; background: var(--c); box-shadow: inset 0 0 0 1px rgba(0,0,0,.12); flex: none; }
        .sw:hover { border-color: var(--primary); }
        .sw-in:checked + .sw { border-color: var(--primary); background: var(--primary-soft); color: var(--primary-hover); box-shadow: 0 0 0 2px rgba(196,140,148,.2); }
        .sw-in:focus-visible + .sw { outline: 2px solid var(--primary); outline-offset: 2px; }
        .sw-in:disabled + .sw { opacity: .4; cursor: not-allowed; }
        .sw .dot.toko { background: conic-gradient(#f2a7bb, #f3d160, #8fb7e3, #a68ccb, #f2a7bb); }

        .kustom-baris { display: flex; gap: 8px; align-items: center; margin-top: 10px; }
        .kustom-baris .form-control { padding: 9px 12px; font-size: .88rem; }
        .picker-wrap { display: flex; align-items: center; gap: 6px; flex: none; font-size: .78rem; color: var(--text-muted); }
        .picker-wrap input[type=color] { width: 38px; height: 38px; padding: 2px; border: 1px solid #eaeaea; border-radius: 10px; background: #fff; cursor: pointer; }
        .stepper-qty { display: inline-flex; align-items: center; border: 1.5px solid #eadfe1; border-radius: 50px; overflow: hidden; }
        .stepper-qty button { border: 0; background: #fff; width: 36px; height: 36px; font-size: 1.1rem; color: var(--primary); }
        .stepper-qty button:hover { background: var(--primary-soft); }
        .stepper-qty input { width: 44px; text-align: center; border: 0; font-weight: 700; background: #fff; -moz-appearance: textfield; }
        .stepper-qty input::-webkit-outer-spin-button, .stepper-qty input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }

        @media (max-width: 575.98px) {
            .grid-produk { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
            .card-produk .isi { padding: 12px 12px 14px; }
            .card-produk .nama { font-size: .95rem; }
            .card-produk .desk { display: none; }
            .card-produk .baris-bawah { flex-direction: column; align-items: stretch; gap: 8px; }
            .harga { font-size: 1rem; }
            .pm-media { position: static; }
            .pm-foto-box { aspect-ratio: 4 / 3; max-height: 240px; }
            .filter-kat { flex-wrap: nowrap; overflow-x: auto; padding-bottom: 4px; scrollbar-width: none; }
            .filter-kat::-webkit-scrollbar { display: none; }
            .filter-kat .chip-filter, .filter-kat .label-kat { flex: none; }
        }

        /* ---------- FAQ & Form ---------- */
        .accordion-item { border: 1px solid rgba(0,0,0,0.05); margin-bottom: 15px; border-radius: 12px !important; overflow: hidden; }
        .accordion-button { font-weight: 600; color: var(--text-dark); background: white; padding: 18px 20px; box-shadow: none !important; }
        .accordion-button:not(.collapsed) { background-color: var(--bg-cream); color: var(--primary); }

        .map-wrapper { border-radius: var(--radius); overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.04); height: 100%; min-height: 380px; }
        .card-form { border: none; border-radius: var(--radius); background: white; box-shadow: 0 5px 20px rgba(0,0,0,0.04); padding: 40px; }

        .floating-alerts { position: fixed; top: 85px; right: 20px; z-index: 1060; max-width: 380px; width: 100%; }
        footer { background: #2f3136; color: #a1a3a8; padding: 50px 0 25px; }
        .sosmed-link { transition: color 0.3s; color: #a1a3a8; }
        .sosmed-link:hover { color: var(--primary); }

        @media (max-width: 768px) {
            .hero h1 { font-size: 2.4rem; }
            .section-title { font-size: 1.8rem; }
            .card-form { padding: 25px; }
        }
    </style>
</head>
<body>

<div class="floating-alerts">
    <?php if(isset($_GET['msg'])): ?>
        <?php if($_GET['msg'] == 'cart_added'): ?>
            <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show p-3" role="alert">
                <div class="d-flex align-items-center"><i class="bi bi-bag-check-fill fs-4 me-3 text-success"></i><div><strong>Berhasil!</strong> Bunga ditambahkan ke keranjang. <a href="keranjang.php" class="fw-bold text-success">Lihat</a></div></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif($_GET['msg'] == 'foto_gagal'): ?>
            <div class="alert alert-warning border-0 shadow-sm alert-dismissible fade show p-3" role="alert">
                <div class="d-flex align-items-center"><i class="bi bi-image fs-4 me-3 text-warning"></i><div><strong>Foto belum bisa dipakai.</strong> Gunakan JPG/PNG/WEBP maksimal 2 MB lalu coba lagi.</div></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif($_GET['msg'] == 'produk_habis'): ?>
            <div class="alert alert-warning border-0 shadow-sm alert-dismissible fade show p-3" role="alert">
                <div class="d-flex align-items-center"><i class="bi bi-exclamation-triangle-fill fs-4 me-3 text-warning"></i><div><strong>Maaf,</strong> produk itu sedang habis atau tidak ditemukan.</div></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif($_GET['msg'] == 'ulasan_success'): ?>
            <div class="alert alert-primary border-0 shadow-sm alert-dismissible fade show p-3" role="alert">
                <div class="d-flex align-items-center"><i class="bi bi-chat-heart-fill fs-4 me-3 text-primary"></i><div><strong>Terima Kasih!</strong> Kritik & saran telah terkirim.</div></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
        <a class="navbar-brand brand-font" href="index.php"><i class="bi bi-flower1 me-2"></i>Kami Florist</a>
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <i class="bi bi-list fs-2 text-dark"></i>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto text-center mt-3 mt-lg-0">
                <li class="nav-item"><a class="nav-link" href="#beranda">Beranda</a></li>
                <li class="nav-item"><a class="nav-link" href="#cara-pesan">Cara Pesan</a></li>
                <li class="nav-item"><a class="nav-link" href="#katalog">Katalog</a></li>
                <li class="nav-item"><a class="nav-link" href="#faq">FAQ</a></li>
                <li class="nav-item"><a class="nav-link" href="#kontak">Kontak</a></li>
            </ul>
            <div class="d-flex justify-content-center gap-2 mt-3 mt-lg-0">
                <a href="riwayat.php" class="btn btn-outline-premium d-inline-flex align-items-center">
                    <i class="bi bi-clock-history me-2"></i> Riwayat
                    <?php if ($jumlah_aktif > 0): ?><span class="badge-tema ms-2"><?= (int)$jumlah_aktif ?></span><?php endif; ?>
                </a>
                <a href="keranjang.php" class="btn btn-premium d-inline-flex align-items-center py-2">
                    <i class="bi bi-cart3 me-2"></i> Keranjang
                    <span class="badge bg-white text-dark ms-2 rounded-pill"><?= (int)$jumlah_item_keranjang ?></span>
                </a>
            </div>
        </div>
    </div>
</nav>

<section id="beranda" class="hero">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <span class="eyebrow">Fresh Flowers &middot; Handcrafted</span>
                <h1 class="mb-3">Sampaikan Perasaan dengan Bunga</h1>
                <p class="text-light">Rangkaian bunga segar yang dirangkai sepenuh hati untuk menyempurnakan setiap momen berharga Anda.</p>
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <a href="#katalog" class="btn btn-premium btn-lg">Lihat Koleksi Bunga</a>
                    <a href="riwayat.php" class="btn btn-hero-ghost btn-lg"><i class="bi bi-clock-history me-2"></i>Lacak Pesanan</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="cara-pesan" class="container py-5 mt-4">
    <div class="section-ornament"><i class="bi bi-flower1"></i></div>
    <h2 class="section-title">Cara Memesan</h2>
    <p class="section-sub">Mudah dan cepat, tanpa perlu membuat akun.</p>
    <div class="row g-4 justify-content-center">
        <div class="col-6 col-md-3"><div class="langkah-card"><div class="langkah-ikon"><i class="bi bi-flower2"></i><span class="no">1</span></div><h6>Pilih Bunga</h6><p>Telusuri katalog lalu tambahkan favoritmu ke keranjang.</p></div></div>
        <div class="col-6 col-md-3"><div class="langkah-card"><div class="langkah-ikon"><i class="bi bi-pencil-square"></i><span class="no">2</span></div><h6>Isi Detail</h6><p>Tentukan tema warna, jadwal ambil, dan kartu ucapan.</p></div></div>
        <div class="col-6 col-md-3"><div class="langkah-card"><div class="langkah-ikon"><i class="bi bi-whatsapp"></i><span class="no">3</span></div><h6>Kirim ke WhatsApp</h6><p>Pesanan otomatis tersimpan dan dikirim ke admin kami.</p></div></div>
        <div class="col-6 col-md-3"><div class="langkah-card"><div class="langkah-ikon"><i class="bi bi-bag-check"></i><span class="no">4</span></div><h6>Pantau Status</h6><p>Lihat progres diterima, diproses, hingga selesai di Riwayat.</p></div></div>
    </div>
</section>

<section id="katalog" class="container katalog-wrap py-5 my-4">
    <div class="section-ornament"><i class="bi bi-flower1"></i></div>
    <h2 class="section-title">Katalog Eksklusif</h2>
    <p class="section-sub">Pilih rangkaian favoritmu, lalu sesuaikan dengan keinginanmu, mulai dari warna, kata-kata ucapan, sampai catatan khusus untuk penjual.</p>

    <div class="toolbar-katalog">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <div class="cari-box">
                <i class="bi bi-search"></i>
                <input type="search" id="cariProduk" class="form-control" placeholder="Cari bunga, mis. bouquet, kering, mini..." autocomplete="off">
            </div>
            <select id="urutProduk" class="form-select" style="width:auto; flex: 0 0 auto;" aria-label="Urutkan">
                <option value="default">Urutan toko</option>
                <option value="murah">Harga terendah</option>
                <option value="mahal">Harga tertinggi</option>
                <option value="nama">Nama A–Z</option>
            </select>
        </div>
        <?php if (count($daftar_kategori) > 0): ?>
        <div class="filter-kat" id="filterKategori" role="group" aria-label="Filter kategori">
            <span class="label-kat"><i class="bi bi-grid me-1"></i>Kategori</span>
            <button type="button" class="chip-filter aktif" data-kat="">Semua<span class="n"><?= count($daftar_produk) ?></span></button>
            <?php foreach ($daftar_kategori as $nk => $jk): ?>
                <button type="button" class="chip-filter" data-kat="<?= e($nk) ?>"><?= e($nk) ?><span class="n"><?= (int)$jk ?></span></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="jumlah-hasil mb-3" id="jumlahHasil" aria-live="polite"></div>

    <div class="grid-produk" id="gridProduk">
        <?php foreach ($daftar_produk as $urut => $row): $habis = ($row['stok'] <= 0); ?>
        <article class="card-produk <?= $habis ? 'habis' : '' ?>"
                 data-id="<?= (int)$row['id_produk'] ?>"
                 data-nama="<?= e($row['nama_produk']) ?>"
                 data-harga="<?= (int)$row['harga'] ?>"
                 data-stok="<?= (int)$row['stok'] ?>"
                 data-kat="<?= e($row['nama_kategori'] ?? '') ?>"
                 data-opsi="<?= e(implode(',', opsi_aktif($row['opsi_kustom']))) ?>"
                 data-desk="<?= e($row['deskripsi']) ?>"
                 data-foto="uploads/<?= e($row['foto']) ?>"
                 data-urut="<?= (int)$urut ?>"
                 data-cari="<?= e(huruf_kecil($row['nama_produk'] . ' ' . $row['deskripsi'] . ' ' . ($row['nama_kategori'] ?? ''))) ?>">
            <div class="thumb">
                <img src="uploads/<?= e($row['foto']) ?>" alt="<?= e($row['nama_produk']) ?>" loading="lazy">
                <?php if (!empty($row['nama_kategori'])): ?><span class="chip chip-kategori"><?= e($row['nama_kategori']) ?></span><?php endif; ?>
                <?php if ($habis): ?><span class="chip chip-habis">Habis</span>
                <?php elseif ($row['stok'] <= 5): ?><span class="chip chip-stok">Sisa <?= (int)$row['stok'] ?></span><?php endif; ?>
                <?php if (!$habis && opsi_aktif($row['opsi_kustom'])): ?><span class="chip chip-kustom"><i class="bi bi-palette2 me-1"></i>Bisa dikustom</span><?php endif; ?>
            </div>
            <div class="isi">
                <h3 class="nama"><?= e($row['nama_produk']) ?></h3>
                <p class="desk"><?= e($row['deskripsi']) ?></p>
                <div class="baris-bawah">
                    <p class="harga"><?= rupiah($row['harga']) ?></p>
                    <button type="button" class="btn-tambah btn-buka-pesan" <?= $habis ? 'disabled' : '' ?> aria-label="Pesan <?= e($row['nama_produk']) ?>">
                        <?= $habis ? 'Habis' : '<i class="bi bi-bag-plus"></i>Pesan' ?>
                    </button>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <nav class="paginasi" id="paginasi" aria-label="Halaman katalog"></nav>

    <div class="kosong-hasil" id="kosongHasil">
        <i class="bi bi-search fs-1 d-block mb-2" style="color: var(--primary); opacity:.55;"></i>
        <strong>Bunga tidak ditemukan.</strong>
        <div class="small mt-1">Coba kata kunci lain atau pilih kategori &ldquo;Semua&rdquo;.</div>
    </div>
</section>

<!-- ============ Modal: kustomisasi pesanan ============ -->
<div class="modal fade" id="modalPesan" tabindex="-1" aria-labelledby="pmJudul" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0" style="border-radius: var(--radius); overflow: hidden;">
            <form method="POST" action="index.php" id="formPesan" enctype="multipart/form-data">
                <input type="hidden" name="id_produk" id="pmId">
                <button type="button" class="btn-close position-absolute top-0 end-0 m-3 bg-white rounded-circle p-2 shadow-sm" style="z-index: 5;" data-bs-dismiss="modal" aria-label="Tutup"></button>
                <div class="modal-body p-0">
                    <div class="row g-0">
                        <div class="col-md-5 pm-media">
                            <div class="pm-foto-box"><img id="pmFoto" src="" alt=""></div>
                            <div class="pm-info">
                                <h5 id="pmJudul"></h5>
                                <div id="pmHarga" class="harga"></div>
                                <div id="pmDesk" class="small"></div>
                            </div>
                        </div>

                        <div class="col-md-7 p-4">
                            <div class="pm-kosong d-none" id="pmKosong"><i class="bi bi-info-circle me-1"></i>Produk ini tidak memiliki pilihan kustom. Cukup tentukan jumlahnya lalu tambahkan ke keranjang.</div>

                            <!-- Warna bunga -->
                            <div class="mb-4" data-opsi="warna_bunga">
                                <div class="pm-judul"><i class="bi bi-flower2 text-primary-theme me-1"></i>Warna bunga</div>
                                <div class="pm-hint">Pilih sampai <?= MAKS_WARNA_BUNGA ?> warna, atau tulis sendiri. Kosongkan jika ingin rekomendasi toko.</div>
                                <div class="swatches" id="swBunga">
                                    <?php foreach (PALET_BUNGA as $nm => $hx): $idsw = 'b_' . md5($nm); ?>
                                        <span><input class="sw-in" type="checkbox" name="pal_bunga[]" value="<?= e($nm) ?>" id="<?= $idsw ?>"><label class="sw" for="<?= $idsw ?>"><span class="dot" style="--c: <?= e($hx) ?>"></span><?= e($nm) ?></label></span>
                                    <?php endforeach; ?>
                                </div>
                                <div class="kustom-baris">
                                    <input type="text" name="bunga_teks" id="bungaTeks" class="form-control" maxlength="60" placeholder="Warna lain, mis. dusty rose, salem">
                                    <label class="picker-wrap" title="Ambil contoh warna">
                                        <input type="color" name="bunga_hex" id="bungaHex" value="#f2a7bb" aria-label="Pilih kode warna bunga">
                                    </label>
                                    <div class="form-check m-0 small flex-shrink-0">
                                        <input class="form-check-input" type="checkbox" name="bunga_hex_pakai" id="bungaHexPakai" value="1">
                                        <label class="form-check-label" for="bungaHexPakai">Pakai</label>
                                    </div>
                                </div>
                                <div class="pm-ringkas" id="ringkasBunga"></div>
                            </div>

                            <!-- Warna wrap -->
                            <div class="mb-4" data-opsi="warna_wrap">
                                <div class="pm-judul"><i class="bi bi-gift text-primary-theme me-1"></i>Warna wrapping</div>
                                <div class="pm-hint">Pilih satu. Beberapa jenis bouquet wrapping-nya tidak bisa diganti, admin akan memberi tahu lewat WhatsApp.</div>
                                <div class="swatches" id="swWrap">
                                    <span><input class="sw-in" type="radio" name="pal_wrap" value="" id="w_toko" checked><label class="sw" for="w_toko"><span class="dot toko"></span>Rekomendasi toko</label></span>
                                    <?php foreach (PALET_WRAP as $nm => $hx): $idsw = 'w_' . md5($nm); ?>
                                        <span><input class="sw-in" type="radio" name="pal_wrap" value="<?= e($nm) ?>" id="<?= $idsw ?>"><label class="sw" for="<?= $idsw ?>"><span class="dot" style="--c: <?= e($hx) ?>"></span><?= e($nm) ?></label></span>
                                    <?php endforeach; ?>
                                </div>
                                <div class="kustom-baris">
                                    <input type="text" name="wrap_teks" id="wrapTeks" class="form-control" maxlength="60" placeholder="Warna lain, mis. cokelat muda">
                                    <label class="picker-wrap" title="Ambil contoh warna">
                                        <input type="color" name="wrap_hex" id="wrapHex" value="#f1e6d3" aria-label="Pilih kode warna wrapping">
                                    </label>
                                    <div class="form-check m-0 small flex-shrink-0">
                                        <input class="form-check-input" type="checkbox" name="wrap_hex_pakai" id="wrapHexPakai" value="1">
                                        <label class="form-check-label" for="wrapHexPakai">Pakai</label>
                                    </div>
                                </div>
                                <div class="pm-ringkas" id="ringkasWrap"></div>
                            </div>

                            <!-- Jenis ucapan -->
                            <div class="mb-4" data-opsi="jenis">
                                <label class="pm-judul d-block" for="pmJenis"><i class="bi bi-balloon-heart text-primary-theme me-1"></i>Jenis ucapan</label>
                                <div class="pm-hint">Pilih acara yang sesuai, atau pilih &ldquo;Lainnya&rdquo; untuk mengetik sendiri.</div>
                                <select name="jenis_pilih" id="pmJenis" class="form-select">
                                    <option value="">-- Pilih jenis ucapan --</option>
                                    <?php foreach (JENIS_UCAPAN as $ju): ?><option value="<?= e($ju) ?>"><?= e($ju) ?></option><?php endforeach; ?>
                                    <option value="<?= e(JENIS_LAINNYA) ?>">Lainnya (ketik sendiri)</option>
                                </select>
                                <input type="text" name="jenis_teks" id="pmJenisTeks" class="form-control mt-2 d-none" maxlength="80" placeholder="Mis. Selamat atas kelahiran putra">
                            </div>

                            <!-- Kata-kata ucapan -->
                            <div class="mb-4" data-opsi="ucapan">
                                <label class="pm-judul d-block" for="pmUcapan"><i class="bi bi-chat-quote text-primary-theme me-1"></i>Kata-kata ucapan</label>
                                <div class="pm-hint">Tulis ucapan yang ingin dicetak di kartu (gratis). Kosongkan jika tidak perlu kartu.</div>
                                <textarea name="ucapan_item" id="pmUcapan" class="form-control" rows="3" maxlength="300" placeholder="Mis. Selamat ulang tahun, semoga selalu bahagia..."></textarea>
                                <div class="text-end small" style="color: var(--text-muted);"><span id="pmHitungUcapan">0</span>/300</div>
                            </div>

                            <!-- Foto referensi -->
                            <div class="mb-4" data-opsi="foto">
                                <label class="pm-judul d-block" for="pmFotoRef"><i class="bi bi-image text-primary-theme me-1"></i>Foto referensi</label>
                                <div class="pm-hint">Unggah gambar contoh rangkaian yang kamu inginkan (JPG/PNG/WEBP, maks. 2 MB).</div>
                                <input type="file" name="foto_ref" id="pmFotoRef" class="form-control" accept="image/jpeg,image/png,image/webp">
                                <div class="text-danger small mt-1 d-none" id="pmFotoErr"></div>
                                <img id="pmFotoPrev" class="d-none mt-2" alt="Pratinjau foto referensi" style="max-width:100%;max-height:160px;border-radius:12px;border:1px solid #eadfe1;">
                            </div>

                            <!-- Catatan -->
                            <div class="mb-4" data-opsi="catatan">
                                <label class="pm-judul d-block" for="pmCatatan"><i class="bi bi-pencil-square text-primary-theme me-1"></i>Catatan untuk penjual</label>
                                <div class="pm-hint">Request lebih spesifik, mis. tanpa baby breath, tambah pita satin, jenis bunga tertentu.</div>
                                <textarea name="catatan" id="pmCatatan" class="form-control" rows="3" maxlength="300" placeholder="Tulis permintaan khususmu di sini..."></textarea>
                                <div class="text-end small" style="color: var(--text-muted);"><span id="pmHitung">0</span>/300</div>
                            </div>

                            <!-- Jumlah -->
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="pm-judul mb-0">Jumlah</div>
                                <div class="stepper-qty">
                                    <button type="button" id="qtyKurang" aria-label="Kurangi">&minus;</button>
                                    <input type="number" name="qty" id="pmQty" value="1" min="1" max="99" inputmode="numeric" aria-label="Jumlah">
                                    <button type="button" id="qtyTambah" aria-label="Tambah">+</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between bg-white" style="border-top: 1px solid var(--bg-cream);">
                    <div>
                        <div class="small" style="color: var(--text-muted);">Subtotal</div>
                        <div class="harga fs-5" id="pmTotal">Rp 0</div>
                    </div>
                    <button type="submit" name="add_to_cart" value="1" class="btn btn-premium px-4"><i class="bi bi-cart-plus me-2"></i>Tambah ke Keranjang</button>
                </div>
            </form>
        </div>
    </div>
</div>

<section id="faq" class="py-5" style="background-color: var(--bg-cream);">
    <div class="container py-4">
        <div class="section-ornament"><i class="bi bi-flower1"></i></div>
        <h2 class="section-title">Pertanyaan Umum</h2>
        <p class="section-sub">Hal-hal yang sering ditanyakan pelanggan kami.</p>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion" id="accordionFAQ">
                    <?php if(mysqli_num_rows($query_faq) == 0): ?>
                        <div class="text-center p-4 bg-white rounded-3 text-muted">Belum ada informasi FAQ.</div>
                    <?php else: ?>
                        <?php $n = 0; while($f = mysqli_fetch_assoc($query_faq)): $n++; ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header"><button class="accordion-button <?= ($n > 1) ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#fItem<?= $f['id_faq'] ?>"><?= htmlspecialchars($f['pertanyaan']) ?></button></h2>
                            <div id="fItem<?= $f['id_faq'] ?>" class="accordion-collapse collapse <?= ($n == 1) ? 'show' : '' ?>" data-bs-parent="#accordionFAQ">
                                <div class="accordion-body" style="color: var(--text-muted); line-height: 1.7;"><?= nl2br(htmlspecialchars($f['jawaban'])) ?></div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="kontak" class="container py-5 my-4">
    <div class="section-ornament"><i class="bi bi-flower1"></i></div>
    <h2 class="section-title">Hubungi Kami</h2>
    <p class="section-sub">Punya saran atau ingin berkunjung? Kami senang mendengarnya.</p>
    <div class="row align-items-stretch">
        <div class="col-lg-5 mb-4 mb-lg-0">
            <div class="card-form h-100 d-flex flex-column justify-content-center">
                <h4 class="fw-bold mb-2 brand-font">Kirim Masukan</h4>
                <p class="small mb-4" style="color: var(--text-muted);">Saran Anda membantu kami untuk terus memberikan pelayanan terbaik.</p>
                <form method="POST" action="">
                    <div class="mb-3"><label class="form-label small fw-bold">Nama Lengkap</label><input type="text" name="nama_masukan" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label small fw-bold">Pesan / Saran</label><textarea name="isi_masukan" class="form-control" rows="4" required></textarea></div>
                    <button type="submit" name="kirim_masukan" class="btn btn-premium w-100 mt-2">Kirim Pesan</button>
                </form>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="map-wrapper d-flex flex-column">
                <?php if(!empty($link_maps)): ?>
                    <iframe src="<?= $link_maps ?>" class="w-100 flex-grow-1" style="border:0; min-height: 380px;" allowfullscreen="" loading="lazy"></iframe>
                <?php else: ?>
                    <div class="w-100 h-100 d-flex align-items-center justify-content-center" style="background:#eaeaea; color:#999;"><i class="bi bi-geo-alt fs-2"></i></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<footer class="text-center">
    <div class="container">
        <h4 class="brand-font text-white mb-4"><i class="bi bi-flower1 me-2 text-danger"></i>Kami Florist</h4>
        <div class="d-flex justify-content-center gap-4 mb-4">
            <?php if(mysqli_num_rows($query_sosmed) > 0): ?>
                <?php while($s = mysqli_fetch_assoc($query_sosmed)): ?>
                    <a href="<?= htmlspecialchars($s['link_sosmed']) ?>" target="_blank" class="sosmed-link" title="<?= htmlspecialchars($s['nama_sosmed']) ?>">
                        <i class="bi <?= htmlspecialchars($s['icon_sosmed']) ?> fs-4"></i>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <span class="small" style="color: var(--text-muted);">Ikon Sosmed Belum Diatur</span>
            <?php endif; ?>
        </div>
        <p class="small mb-0" style="color: #6c6f75;">© <?= date('Y'); ?> Kami Florist. Crafting Happiness with Flowers.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ---------- Katalog: cari, filter kategori, urutkan, nomor halaman ----------
(function () {
    const PER_HALAMAN = 8; // jumlah bunga per halaman (ubah angka ini kalau mau lebih banyak/sedikit)
    const grid = document.getElementById('gridProduk');
    const kartu = Array.from(grid.querySelectorAll('.card-produk'));
    const cari = document.getElementById('cariProduk');
    const urut = document.getElementById('urutProduk');
    const info = document.getElementById('jumlahHasil');
    const kosong = document.getElementById('kosongHasil');
    const nav = document.getElementById('paginasi');
    const filterBox = document.getElementById('filterKategori');
    let kategori = '', halaman = 1;

    function nomorHalaman(total) {
        // 1 … 4 5 6 … 12  (ringkas bila halaman banyak)
        const set = new Set([1, total, halaman - 1, halaman, halaman + 1]);
        const nomor = Array.from(set).filter(n => n >= 1 && n <= total).sort((a, b) => a - b);
        const hasil = [];
        nomor.forEach((n, i) => { if (i && n - nomor[i - 1] > 1) hasil.push('...'); hasil.push(n); });
        return hasil;
    }

    function gambarPaginasi(total) {
        nav.innerHTML = '';
        if (total <= 1) return;
        const tombol = (isi, hal, opsi = {}) => {
            const b = document.createElement('button');
            b.type = 'button'; b.innerHTML = isi; b.dataset.hal = hal;
            if (opsi.aktif) { b.classList.add('aktif'); b.setAttribute('aria-current', 'page'); }
            if (opsi.mati) b.disabled = true;
            if (opsi.label) b.setAttribute('aria-label', opsi.label);
            nav.appendChild(b);
        };
        tombol('<i class="bi bi-chevron-left"></i>', halaman - 1, { mati: halaman === 1, label: 'Halaman sebelumnya' });
        nomorHalaman(total).forEach(n => {
            if (n === '...') { const t = document.createElement('span'); t.className = 'titik'; t.textContent = '…'; nav.appendChild(t); }
            else tombol(n, n, { aktif: n === halaman, label: 'Halaman ' + n });
        });
        tombol('<i class="bi bi-chevron-right"></i>', halaman + 1, { mati: halaman === total, label: 'Halaman berikutnya' });
    }

    function terapkan(ulang) {
        if (ulang) halaman = 1;
        const q = cari.value.trim().toLowerCase();
        const mode = urut.value;
        const cocok = kartu.filter(k => (!kategori || k.dataset.kat === kategori) && (!q || k.dataset.cari.includes(q)));
        cocok.sort((a, b) => {
            if (mode === 'murah') return a.dataset.harga - b.dataset.harga;
            if (mode === 'mahal') return b.dataset.harga - a.dataset.harga;
            if (mode === 'nama')  return a.dataset.nama.localeCompare(b.dataset.nama, 'id');
            return a.dataset.urut - b.dataset.urut;
        });

        const total = Math.max(1, Math.ceil(cocok.length / PER_HALAMAN));
        halaman = Math.min(halaman, total);
        const mulai = (halaman - 1) * PER_HALAMAN;

        kartu.forEach(k => { k.style.display = 'none'; });
        cocok.forEach((k, i) => {
            grid.appendChild(k);
            k.style.display = (i >= mulai && i < mulai + PER_HALAMAN) ? '' : 'none';
        });

        info.textContent = cocok.length
            ? 'Menampilkan ' + (mulai + 1) + '–' + Math.min(mulai + PER_HALAMAN, cocok.length) + ' dari ' + cocok.length + ' bunga'
            : '';
        kosong.style.display = cocok.length === 0 ? 'block' : 'none';
        gambarPaginasi(total);
    }

    cari.addEventListener('input', () => terapkan(true));
    urut.addEventListener('change', () => terapkan(true));
    if (filterBox) filterBox.addEventListener('click', ev => {
        const b = ev.target.closest('.chip-filter'); if (!b) return;
        filterBox.querySelectorAll('.chip-filter').forEach(x => x.classList.remove('aktif'));
        b.classList.add('aktif'); kategori = b.dataset.kat; terapkan(true);
    });
    nav.addEventListener('click', ev => {
        const b = ev.target.closest('button[data-hal]'); if (!b || b.disabled) return;
        halaman = parseInt(b.dataset.hal, 10);
        terapkan(false);
        document.getElementById('katalog').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
    terapkan(true);
})();

// ---------- Modal kustomisasi pesanan ----------
(function () {
    const modalEl = document.getElementById('modalPesan');
    const modal = new bootstrap.Modal(modalEl);
    const $ = id => document.getElementById(id);
    const form = $('formPesan');
    const qty = $('pmQty');
    const MAKS_BUNGA = <?= (int)MAKS_WARNA_BUNGA ?>;
    let harga = 0, stok = 1, opsiAktif = [];

    const rupiah = n => 'Rp ' + Number(n).toLocaleString('id-ID');
    function hitung() {
        let v = parseInt(qty.value, 10); if (isNaN(v) || v < 1) v = 1;
        v = Math.min(v, stok, 99); qty.value = v; qty.max = Math.min(stok, 99);
        $('pmTotal').textContent = rupiah(harga * v);
    }

    function ringkas() {
        const b = Array.from(form.querySelectorAll('input[name="pal_bunga[]"]:checked')).map(x => x.value);
        if ($('bungaTeks').value.trim()) b.push($('bungaTeks').value.trim());
        if ($('bungaHexPakai').checked) b.push($('bungaHex').value);
        $('ringkasBunga').textContent = b.length ? 'Pilihanmu: ' + b.join(' + ') : '';

        const w = [];
        const rw = form.querySelector('input[name="pal_wrap"]:checked');
        if (rw && rw.value) w.push(rw.value);
        if ($('wrapTeks').value.trim()) w.push($('wrapTeks').value.trim());
        if ($('wrapHexPakai').checked) w.push($('wrapHex').value);
        $('ringkasWrap').textContent = w.length ? 'Pilihanmu: ' + w.join(' + ') : '';
    }

    // Batasi pilihan warna bunga dari palet
    function batasBunga() {
        if (!opsiAktif.includes('warna_bunga')) return;
        const cek = Array.from(form.querySelectorAll('input[name="pal_bunga[]"]'));
        const n = cek.filter(x => x.checked).length;
        cek.forEach(x => { x.disabled = !x.checked && n >= MAKS_BUNGA; });
    }

    form.addEventListener('change', ev => {
        if (ev.target.name === 'pal_bunga[]') batasBunga();
        ringkas();
    });
    form.addEventListener('input', ev => {
        // Memilih kode warna otomatis mencentang "Pakai"
        if (ev.target.id === 'bungaHex') $('bungaHexPakai').checked = true;
        if (ev.target.id === 'wrapHex')  $('wrapHexPakai').checked = true;
        if (ev.target.id === 'pmCatatan') $('pmHitung').textContent = ev.target.value.length;
        if (ev.target.id === 'pmJenis') {
            const lain = ev.target.value === '<?= e(JENIS_LAINNYA) ?>';
            $('pmJenisTeks').classList.toggle('d-none', !lain);
            if (!lain) $('pmJenisTeks').value = '';
        }
        if (ev.target.id === 'pmFotoRef') {
            const f = ev.target.files[0], err = $('pmFotoErr'), prev = $('pmFotoPrev');
            err.classList.add('d-none'); prev.classList.add('d-none');
            if (f && f.size > 2 * 1024 * 1024) { err.textContent = 'Ukuran foto maksimal 2 MB.'; err.classList.remove('d-none'); ev.target.value = ''; }
            else if (f) { prev.src = URL.createObjectURL(f); prev.classList.remove('d-none'); }
        }
        if (ev.target.id === 'pmUcapan')  $('pmHitungUcapan').textContent = ev.target.value.length;
        ringkas();
    });

    $('qtyKurang').onclick = () => { qty.value = (parseInt(qty.value, 10) || 1) - 1; hitung(); };
    $('qtyTambah').onclick = () => { qty.value = (parseInt(qty.value, 10) || 0) + 1; hitung(); };
    qty.addEventListener('input', hitung);

    // Tampilkan hanya bagian isian yang diaktifkan admin untuk kategori produk ini.
    // Bagian yang disembunyikan ikut dinonaktifkan supaya tidak terkirim ke server.
    function terapkanOpsi(daftar) {
        opsiAktif = daftar;
        form.querySelectorAll('[data-opsi]').forEach(bagian => {
            const tampil = daftar.includes(bagian.dataset.opsi);
            bagian.classList.toggle('d-none', !tampil);
            bagian.querySelectorAll('input, textarea, select').forEach(el => { el.disabled = !tampil; });
        });
        $('pmKosong').classList.toggle('d-none', daftar.length > 0);
    }

    function buka(k) {
        form.reset();
        terapkanOpsi(k.dataset.opsi ? k.dataset.opsi.split(',') : []);
        $('pmJenisTeks').classList.add('d-none');
        $('pmFotoPrev').classList.add('d-none'); $('pmFotoErr').classList.add('d-none');
        batasBunga(); ringkas(); $('pmHitung').textContent = '0'; $('pmHitungUcapan').textContent = '0';
        harga = parseInt(k.dataset.harga, 10); stok = parseInt(k.dataset.stok, 10);
        $('pmId').value = k.dataset.id;
        $('pmFoto').src = k.dataset.foto; $('pmFoto').alt = k.dataset.nama;
        $('pmJudul').textContent = k.dataset.nama;
        $('pmHarga').textContent = rupiah(harga);
        $('pmDesk').textContent = k.dataset.desk;
        hitung(); modal.show();
    }

    document.getElementById('gridProduk').addEventListener('click', ev => {
        const k = ev.target.closest('.card-produk');
        if (!k || k.classList.contains('habis')) return;
        buka(k);
    });
})();

if (window.history.replaceState) {
    const url = new URL(window.location);
    if (url.searchParams.has('msg')) {
        url.searchParams.delete('msg');
        // Hapus param dari URL bar tanpa mereload halaman
        window.history.replaceState(null, '', url.toString());
    }
}
</script>
</body>
</html>