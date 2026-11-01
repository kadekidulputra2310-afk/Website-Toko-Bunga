<?php
session_start();
require 'config.php';
require 'helpers.php';

if (!isset($_SESSION['admin_login'])) {
    header("Location: login.php");
    exit;
}

// Tabel pesanan dibuat lewat migrasi_pesanan.sql / db_tokobunga.sql terbaru
$tabel_pesanan_ada = mysqli_num_rows(mysqli_query($conn, "SHOW TABLES LIKE 'pesanan'")) > 0;
pastikan_skema($conn); // tambah kolom baru (opsi kustom kategori, ucapan per produk) bila database masih versi lama

// ---------------- PESANAN ----------------
if ($tabel_pesanan_ada && isset($_POST['ubah_status'])) {
    $id_pesanan = (int)$_POST['id_pesanan'];
    $status_baru = $_POST['status'] ?? '';
    $filter_kembali = $_POST['filter'] ?? '';
    if (in_array($status_baru, STATUS_PESANAN, true)) {
        $sekarang = date('Y-m-d H:i:s');
        $st = mysqli_prepare($conn, "UPDATE pesanan SET status = ?, diperbarui_pada = ? WHERE id_pesanan = ? AND status <> ?");
        mysqli_stmt_bind_param($st, 'ssis', $status_baru, $sekarang, $id_pesanan, $status_baru);
        mysqli_stmt_execute($st);
        mysqli_stmt_close($st);
    }
    $q = in_array($filter_kembali, STATUS_PESANAN, true) ? '&status=' . urlencode($filter_kembali) : '';
    header("Location: admin.php?msg=status_updated$q#pesanan-panel"); exit;
}

if ($tabel_pesanan_ada && isset($_POST['hapus_pesanan'])) {
    $id_pesanan = (int)$_POST['id_pesanan'];
    $filter_kembali = $_POST['filter'] ?? '';
    $rf = mysqli_prepare($conn, "SELECT foto_referensi FROM detail_pesanan WHERE id_pesanan = ? AND foto_referensi IS NOT NULL");
    mysqli_stmt_bind_param($rf, 'i', $id_pesanan);
    mysqli_stmt_execute($rf);
    $hrf = mysqli_stmt_get_result($rf);
    while ($br = mysqli_fetch_assoc($hrf)) { $fh = foto_referensi_valid($br['foto_referensi']); if ($fh !== '') @unlink(DIR_REFERENSI . $fh); }
    mysqli_stmt_close($rf);
    $st = mysqli_prepare($conn, "DELETE FROM pesanan WHERE id_pesanan = ?"); // detail ikut terhapus (ON DELETE CASCADE)
    mysqli_stmt_bind_param($st, 'i', $id_pesanan);
    mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    $q = in_array($filter_kembali, STATUS_PESANAN, true) ? '&status=' . urlencode($filter_kembali) : '';
    header("Location: admin.php?msg=pesanan_deleted$q#pesanan-panel"); exit;
}

// ---------------- PRODUK ----------------
if (isset($_POST['tambah'])) {
    $id_kategori = $_POST['id_kategori'];
    $nama = $_POST['nama_produk'];
    $harga = $_POST['harga'];
    $stok = $_POST['stok'];
    $deskripsi = $_POST['deskripsi'];
    $foto = $_FILES['foto']['name'];
    $tmp = $_FILES['foto']['tmp_name'];
    $foto_baru = time() . '_' . $foto; 
    $path = "uploads/" . $foto_baru;
    
    if (move_uploaded_file($tmp, $path)) {
        mysqli_query($conn, "INSERT INTO produk (id_kategori, nama_produk, deskripsi, harga, foto, stok) VALUES ('$id_kategori', '$nama', '$deskripsi', '$harga', '$foto_baru', '$stok')");
        header("Location: admin.php?msg=jadwal_success#produk-panel"); exit;
    } else {
        header("Location: admin.php?msg=error#produk-panel"); exit;
    }
}

if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $cek = mysqli_query($conn, "SELECT foto FROM produk WHERE id_produk='$id'");
    $data = mysqli_fetch_assoc($cek);
    if(file_exists("uploads/".$data['foto'])) { unlink("uploads/".$data['foto']); }
    mysqli_query($conn, "DELETE FROM produk WHERE id_produk='$id'");
    header("Location: admin.php?msg=jadwal_deleted#produk-panel"); exit;
}

// ---------------- FAQ ----------------
if (isset($_POST['tambah_faq'])) {
    $pertanyaan = mysqli_real_escape_string($conn, $_POST['pertanyaan']);
    $jawaban = mysqli_real_escape_string($conn, $_POST['jawaban']);
    mysqli_query($conn, "INSERT INTO faq (pertanyaan, jawaban) VALUES ('$pertanyaan', '$jawaban')");
    header("Location: admin.php?msg=sesi_opened#faq-panel"); exit;
}

if (isset($_POST['edit_faq'])) {
    $id_faq = $_POST['id_faq'];
    $pertanyaan = mysqli_real_escape_string($conn, $_POST['pertanyaan']);
    $jawaban = mysqli_real_escape_string($conn, $_POST['jawaban']);
    mysqli_query($conn, "UPDATE faq SET pertanyaan='$pertanyaan', jawaban='$jawaban' WHERE id_faq='$id_faq'");
    header("Location: admin.php?msg=faq_updated#faq-panel"); exit;
}

if (isset($_GET['hapus_faq'])) {
    $id_faq = $_GET['hapus_faq'];
    mysqli_query($conn, "DELETE FROM faq WHERE id_faq='$id_faq'");
    header("Location: admin.php?msg=sesi_closed#faq-panel"); exit;
}

// ---------------- ULASAN ----------------
if (isset($_GET['hapus_ulasan'])) {
    $id_ulasan = $_GET['hapus_ulasan'];
    mysqli_query($conn, "DELETE FROM ulasan WHERE id_ulasan='$id_ulasan'");
    header("Location: admin.php?msg=ulasan_deleted#ulasan-panel"); exit;
}

// ---------------- MAPS ----------------
if (isset($_POST['update_maps'])) {
    $link_baru = mysqli_real_escape_string($conn, $_POST['link_maps']);
    $cek_kunci = mysqli_query($conn, "SELECT * FROM pengaturan WHERE kunci='link_maps'");
    if(mysqli_num_rows($cek_kunci) > 0) {
        mysqli_query($conn, "UPDATE pengaturan SET nilai='$link_baru' WHERE kunci='link_maps'");
    } else {
        mysqli_query($conn, "INSERT INTO pengaturan (kunci, nilai) VALUES ('link_maps', '$link_baru')");
    }
    header("Location: admin.php?msg=jadwal_updated#pengaturan-panel"); exit;
}

// ---------------- SOSIAL MEDIA ----------------
if (isset($_POST['tambah_sosmed'])) {
    $nama_sosmed = mysqli_real_escape_string($conn, $_POST['nama_sosmed']);
    $icon_sosmed = mysqli_real_escape_string($conn, $_POST['icon_sosmed']);
    $link_sosmed = mysqli_real_escape_string($conn, $_POST['link_sosmed']);
    mysqli_query($conn, "INSERT INTO sosmed (nama_sosmed, icon_sosmed, link_sosmed) VALUES ('$nama_sosmed', '$icon_sosmed', '$link_sosmed')");
    header("Location: admin.php?msg=sosmed_added#sosmed-panel"); exit;
}

if (isset($_GET['hapus_sosmed'])) {
    $id_sosmed = $_GET['hapus_sosmed'];
    mysqli_query($conn, "DELETE FROM sosmed WHERE id_sosmed='$id_sosmed'");
    header("Location: admin.php?msg=sosmed_deleted#sosmed-panel"); exit;
}

// ---------------- KATEGORI ----------------
if (isset($_POST['tambah_kategori'])) {
    $nama_kat = rapikan_teks($_POST['nama_kategori'] ?? '', 50);
    $pesan = 'kategori_kosong';
    if ($nama_kat !== '') {
        $st = mysqli_prepare($conn, "SELECT 1 FROM kategori WHERE LOWER(nama_kategori) = LOWER(?) LIMIT 1");
        mysqli_stmt_bind_param($st, 's', $nama_kat);
        mysqli_stmt_execute($st);
        mysqli_stmt_store_result($st);
        $sudah_ada = mysqli_stmt_num_rows($st) > 0;
        mysqli_stmt_close($st);
        if ($sudah_ada) {
            $pesan = 'kategori_ada';
        } else {
            $opsi_baru = opsi_dari_post($_POST['opsi'] ?? []);
            $st = mysqli_prepare($conn, "INSERT INTO kategori (nama_kategori, opsi_kustom) VALUES (?, ?)");
            mysqli_stmt_bind_param($st, 'ss', $nama_kat, $opsi_baru);
            mysqli_stmt_execute($st);
            mysqli_stmt_close($st);
            $pesan = 'kategori_added';
        }
    }
    header("Location: admin.php?msg=$pesan#produk-panel"); exit;
}
if (isset($_POST['simpan_opsi_kategori'])) {
    $id_kat = (int)($_POST['id_kategori'] ?? 0);
    $opsi_baru = opsi_dari_post($_POST['opsi'] ?? []);
    $st = mysqli_prepare($conn, "UPDATE kategori SET opsi_kustom = ? WHERE id_kategori = ?");
    mysqli_stmt_bind_param($st, 'si', $opsi_baru, $id_kat);
    mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    header("Location: admin.php?msg=kategori_opsi_saved#produk-panel"); exit;
}
if (isset($_GET['hapus_kategori'])) {
    $id_kat = (int)$_GET['hapus_kategori'];
    // Hapus kategori yang masih punya produk akan ikut menghapus produknya (ON DELETE CASCADE) -> diblokir
    $st = mysqli_prepare($conn, "SELECT COUNT(*) FROM produk WHERE id_kategori = ?");
    mysqli_stmt_bind_param($st, 'i', $id_kat);
    mysqli_stmt_execute($st);
    mysqli_stmt_bind_result($st, $jml_produk_kat);
    mysqli_stmt_fetch($st);
    mysqli_stmt_close($st);
    if ($jml_produk_kat > 0) {
        header("Location: admin.php?msg=kategori_dipakai#produk-panel"); exit;
    }
    $st = mysqli_prepare($conn, "DELETE FROM kategori WHERE id_kategori = ?");
    mysqli_stmt_bind_param($st, 'i', $id_kat);
    mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    header("Location: admin.php?msg=kategori_deleted#produk-panel"); exit;
}

// Kotak centang pilihan kustom untuk satu kategori ($awalan = awalan id agar unik di tiap form)
function kotak_opsi($awalan, array $aktif) {
    $h = '<div class="opsi-grid">';
    foreach (OPSI_KUSTOM as $kunci => $o) {
        $id = $awalan . '_' . $kunci;
        $h .= '<div class="opsi-item"><input class="form-check-input" type="checkbox" name="opsi[]" value="' . e($kunci) . '" id="' . e($id) . '"' . (in_array($kunci, $aktif, true) ? ' checked' : '') . '>'
            . '<label for="' . e($id) . '"><i class="bi ' . e($o['ikon']) . '"></i><span><strong>' . e($o['label']) . '</strong><small>' . e($o['ket']) . '</small></span></label></div>';
    }
    return $h . '</div>';
}

// ---------------- FETCH DATA ----------------
$produk = mysqli_query($conn, "SELECT p.*, k.nama_kategori FROM produk p JOIN kategori k ON p.id_kategori = k.id_kategori ORDER BY p.id_produk DESC");
$kategori = mysqli_query($conn, "SELECT * FROM kategori");
$kategori_kelola = mysqli_query($conn, "SELECT k.id_kategori, k.nama_kategori, k.opsi_kustom, COUNT(p.id_produk) AS jml FROM kategori k LEFT JOIN produk p ON p.id_kategori = k.id_kategori GROUP BY k.id_kategori, k.nama_kategori, k.opsi_kustom ORDER BY k.id_kategori ASC");
$ulasan = mysqli_query($conn, "SELECT * FROM ulasan ORDER BY id_ulasan DESC");
$faq = mysqli_query($conn, "SELECT * FROM faq ORDER BY id_faq DESC");
$sosmed = mysqli_query($conn, "SELECT * FROM sosmed ORDER BY id_sosmed DESC");

$query_maps_admin = mysqli_query($conn, "SELECT nilai FROM pengaturan WHERE kunci='link_maps'");
$data_maps_admin = mysqli_fetch_assoc($query_maps_admin);
$current_maps = ($data_maps_admin) ? $data_maps_admin['nilai'] : '';

$total_produk = mysqli_num_rows($produk);
$total_ulasan = mysqli_num_rows($ulasan);
$total_faq = mysqli_num_rows($faq);

// Data pesanan (untuk tab Pesanan & kartu dashboard)
$hitung_status = array_fill_keys(STATUS_PESANAN, 0);
$daftar_pesanan = [];
$detail_pesanan = [];
$filter_status = $_GET['status'] ?? '';
if (!in_array($filter_status, STATUS_PESANAN, true)) $filter_status = '';
if ($tabel_pesanan_ada) {
    $rs = mysqli_query($conn, "SELECT status, COUNT(*) AS n FROM pesanan GROUP BY status");
    while ($r = mysqli_fetch_assoc($rs)) $hitung_status[$r['status']] = (int)$r['n'];

    $where = $filter_status !== '' ? "WHERE status = '$filter_status'" : ''; // nilai sudah divalidasi whitelist
    $rs = mysqli_query($conn, "SELECT * FROM pesanan $where ORDER BY id_pesanan DESC LIMIT 200");
    while ($r = mysqli_fetch_assoc($rs)) $daftar_pesanan[] = $r;
    $detail_pesanan = ambil_detail_pesanan($conn, array_column($daftar_pesanan, 'id_pesanan'));
}
$total_pesanan = array_sum($hitung_status);
$pesanan_baru = $hitung_status['Diterima'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - Kami Florist</title>
    <link rel="icon" type="image/svg+xml" href="assets/favicon.svg">
    <link rel="alternate icon" href="assets/favicon.ico">
    <link rel="apple-touch-icon" href="assets/apple-touch-icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,500;0,600;1,500&family=Nunito+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/tema.css">
    <style>
        /* Panel admin memakai tema yang sama dengan web utama (assets/tema.css) */
        body { overflow-x: hidden; }
        .wrapper { display: flex; align-items: stretch; min-height: 100vh; }

        #sidebar { min-width: 270px; max-width: 270px; background: #fff; color: var(--text-dark); position: fixed; height: 100vh; z-index: 1035; border-right: 1px solid rgba(0,0,0,0.05); box-shadow: var(--shadow-soft); transition: all 0.3s ease; overflow-y: auto; }
        #sidebar .sidebar-header { padding: 24px 22px; border-bottom: 1px solid var(--bg-cream); }
        #sidebar .brand-admin { font-family: 'Lora', serif; font-weight: 600; font-size: 1.4rem; color: var(--primary); letter-spacing: .5px; margin: 0; }
        #sidebar .brand-sub { font-size: .72rem; letter-spacing: 2px; text-transform: uppercase; color: var(--text-muted); font-weight: 700; }
        #sidebar ul.components { padding: 18px 12px; }
        #sidebar .nav-link { padding: 11px 16px; margin: 0 0 4px !important; font-size: 0.93rem; font-weight: 600; display: flex; align-items: center; color: var(--text-muted) !important; border-radius: 12px; background: none; border: none; width: 100%; text-align: left; transition: all 0.2s ease; }
        #sidebar .nav-link i { margin-right: 13px; font-size: 1.1rem; }
        #sidebar .nav-link:hover { color: var(--primary) !important; background: var(--bg-light); }
        #sidebar .nav-link.active { color: var(--primary-hover) !important; background: var(--primary-soft); }
        #sidebar .nav-link.logout { color: #b03a3a !important; }
        #sidebar .nav-link.logout:hover { background: #fdeaea; }
        #sidebar hr { border-color: var(--bg-cream); opacity: 1; }

        #content { width: calc(100% - 270px); padding: 35px; margin-left: 270px; min-height: 100vh; transition: all 0.3s ease; }
        .judul-halaman { font-family: 'Lora', serif; font-weight: 600; color: var(--text-dark); }

        .mobile-header { display: none; background: rgba(250,249,247,0.95); backdrop-filter: blur(12px); color: var(--text-dark); padding: 14px 20px; position: fixed; top: 0; left: 0; right: 0; z-index: 1025; border-bottom: 1px solid rgba(0,0,0,0.06); }
        .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(55, 59, 68, 0.4); backdrop-filter: blur(4px); z-index: 1030; transition: all 0.3s ease; }

        .card { border: none; border-radius: var(--radius); box-shadow: var(--shadow-soft); background: #fff; }

        /* Kartu akses cepat */
        .stat-card { padding: 22px 20px; border-radius: var(--radius); background: #fff; border: 1px solid rgba(0,0,0,0.04); box-shadow: var(--shadow-soft); display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; cursor: pointer; transition: all 0.3s ease; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lift); }
        .stat-card h3 { font-family: 'Lora', serif; font-weight: 600; }
        .icon-box { width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.7rem; margin-bottom: 14px; }
        .ic-rose  { background: var(--primary-soft); color: var(--primary); }
        .ic-sage  { background: var(--sage-soft);    color: var(--sage); }
        .ic-sand  { background: var(--bg-cream);     color: #a08a6e; }
        .ic-peach { background: #fdeee4;             color: #d98a5f; }

        /* Tabel */
        .table th { background-color: var(--bg-cream); color: var(--text-muted); font-weight: 700; font-size: 0.8rem; letter-spacing: .4px; text-transform: uppercase; padding: 14px 15px; border-bottom: 0; }
        .table th:first-child { border-radius: 10px 0 0 10px; } .table th:last-child { border-radius: 0 10px 10px 0; }
        .table td { padding: 15px; vertical-align: middle; font-size: 0.93rem; border-bottom: 1px solid var(--bg-cream); }
        .table-hover > tbody > tr:hover > * { background-color: var(--bg-light); }
        .img-preview { object-fit: cover; border-radius: 12px; border: 1px solid var(--bg-cream); }
        .harga-admin { font-family: 'Lora', serif; font-weight: 600; color: var(--primary); }

        .sel-status { font-weight: 700; font-size: 0.85rem; min-width: 130px; border-width: 2px; }
        .sel-Diterima   { border-color: #f0c36d; background-color: #fff9ec; color: #a56a00; }
        .sel-Diproses   { border-color: #8ec5e8; background-color: #eef7fd; color: #1e6f9f; }
        .sel-Selesai    { border-color: #9ccaa8; background-color: #eef7f0; color: #2f6b40; }
        .sel-Dibatalkan { border-color: #e8a5a5; background-color: #fdf0f0; color: #b03a3a; }
        .btn-action { width: 34px; height: 34px; padding:0; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 0.9rem; transition: all 0.2s; background: #fff; border-color: #eadfe1 !important; }
        .btn-action:hover { background: var(--primary-soft); }

        /* Tombol Bootstrap disesuaikan ke warna tema */
        .btn-primary { --bs-btn-bg: var(--primary); --bs-btn-border-color: var(--primary); --bs-btn-hover-bg: var(--primary-hover); --bs-btn-hover-border-color: var(--primary-hover); --bs-btn-active-bg: var(--primary-hover); --bs-btn-active-border-color: var(--primary-hover); --bs-btn-disabled-bg: #d9c3c7; --bs-btn-disabled-border-color: #d9c3c7; --bs-btn-focus-shadow-rgb: 196,140,148; border-radius: 50px !important; }
        .btn-outline-secondary { --bs-btn-color: var(--text-muted); --bs-btn-border-color: #eadfe1; --bs-btn-hover-bg: var(--primary-soft); --bs-btn-hover-color: var(--primary-hover); --bs-btn-hover-border-color: var(--primary); --bs-btn-active-bg: var(--primary-soft); --bs-btn-active-color: var(--primary-hover); --bs-btn-active-border-color: var(--primary); }
        .btn-secondary { --bs-btn-bg: #f1ece6; --bs-btn-border-color: #f1ece6; --bs-btn-color: var(--text-dark); --bs-btn-hover-bg: #e6dfd7; --bs-btn-hover-border-color: #e6dfd7; --bs-btn-hover-color: var(--text-dark); border-radius: 50px !important; }
        .text-primary { color: var(--primary) !important; }
        .badge-tema { background: var(--primary); color: #fff; font-size: 0.72rem; min-width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50px; padding: 0 6px; }

        /* Modal */
        .modal-content { border-radius: var(--radius) !important; overflow: hidden; }
        .modal-head-tema { background: linear-gradient(135deg, var(--primary), #b9808a); color: #fff; border: 0; padding: 18px 24px; }
        .modal-head-tema .modal-title { font-family: 'Lora', serif; }

        /* Pilihan kustom per kategori */
        .opsi-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        .opsi-item { position: relative; }
        .opsi-item .form-check-input { position: absolute; top: 13px; left: 13px; margin: 0; z-index: 1; width: 1.1em; height: 1.1em; }
        .opsi-item .form-check-input:checked { background-color: var(--primary); border-color: var(--primary); }
        .opsi-item label { display: flex; gap: 10px; align-items: flex-start; width: 100%; padding: 11px 12px 11px 38px; border: 1.5px solid #eadfe1; border-radius: 12px; background: #fff; cursor: pointer; transition: all .15s; }
        .opsi-item label i { color: var(--primary); font-size: 1.1rem; margin-top: 1px; }
        .opsi-item label strong { display: block; font-size: .88rem; line-height: 1.2; }
        .opsi-item label small { display: block; color: var(--text-muted); font-size: .74rem; line-height: 1.3; margin-top: 2px; }
        .opsi-item .form-check-input:checked + label { border-color: var(--primary); background: var(--primary-soft); }
        .opsi-item .form-check-input:focus-visible + label { outline: 2px solid var(--primary); outline-offset: 2px; }
        .kat-baris { border: 1px solid var(--bg-cream); border-radius: 14px; padding: 12px 14px; margin-bottom: 10px; background: #fff; }
        .chip-opsi { display: inline-flex; align-items: center; gap: 4px; font-size: .72rem; font-weight: 600; background: var(--primary-soft); color: var(--primary-hover); padding: 2px 9px; border-radius: 50px; }
        .chip-opsi.kosong { background: var(--bg-cream); color: var(--text-muted); font-weight: 400; }

        @media (max-width: 991.98px) {
            #sidebar { left: -270px; } #sidebar.active { left: 0; }
            #content { width: 100%; margin-left: 0; padding: 20px; padding-top: 85px; }
            .mobile-header { display: flex; align-items: center; justify-content: space-between; }
            .sidebar-overlay.active { display: block; }
        }
        @media (max-width: 575.98px) { .opsi-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="mobile-header">
    <div class="d-flex align-items-center">
        <button class="btn p-1 px-2 border-0 me-2" id="sidebarCollapseBtn" type="button"><i class="bi bi-list fs-3"></i></button>
        <h5 class="mb-0 brand-font text-primary-theme" style="font-size: 1.2rem;"><i class="bi bi-flower1 me-2"></i>Kami Florist</h5>
    </div>
</div>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="wrapper">
    <nav id="sidebar">
        <div class="sidebar-header d-flex align-items-center justify-content-between">
            <div><h5 class="brand-admin"><i class="bi bi-flower1 me-2"></i>Kami Florist</h5><div class="brand-sub">Panel Admin</div></div>
            <button type="button" class="btn-close d-lg-none" id="sidebarCloseBtn"></button>
        </div>
        <ul class="list-unstyled components nav nav-tabs flex-column border-0" id="adminTab" role="tablist">
            <li class="nav-item"><button class="nav-link active" id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#dashboard" type="button"><i class="bi bi-grid-fill"></i>Dashboard</button></li>
            <li class="nav-item"><button class="nav-link" id="pesanan-tab" data-bs-toggle="tab" data-bs-target="#pesanan-panel" type="button"><i class="bi bi-receipt-cutoff"></i>Pesanan<?php if ($pesanan_baru > 0): ?><span class="badge-tema ms-auto"><?= $pesanan_baru ?></span><?php endif; ?></button></li>
            <li class="nav-item"><button class="nav-link" id="produk-tab" data-bs-toggle="tab" data-bs-target="#produk-panel" type="button"><i class="bi bi-box-seam-fill"></i>Katalog Bunga</button></li>
            <li class="nav-item"><button class="nav-link" id="faq-admin-tab" data-bs-toggle="tab" data-bs-target="#faq-panel" type="button"><i class="bi bi-question-circle-fill"></i>Kelola FAQ</button></li>
            <li class="nav-item"><button class="nav-link" id="sosmed-tab" data-bs-toggle="tab" data-bs-target="#sosmed-panel" type="button"><i class="bi bi-share-fill"></i>Kelola Sosmed</button></li>
            <li class="nav-item"><button class="nav-link" id="ulasan-tab" data-bs-toggle="tab" data-bs-target="#ulasan-panel" type="button"><i class="bi bi-chat-heart-fill"></i>Masukan User</button></li>
            <li class="nav-item"><button class="nav-link" id="pengaturan-tab" data-bs-toggle="tab" data-bs-target="#pengaturan-panel" type="button"><i class="bi bi-geo-alt-fill"></i>Pengaturan Maps</button></li>
            <hr class="mx-3 my-3">
            <li><a href="index.php" class="nav-link text-decoration-none" target="_blank"><i class="bi bi-box-arrow-up-right"></i>Lihat Web Utama</a></li>
            <li><a href="logout.php" class="nav-link logout text-decoration-none" onclick="return confirm('Yakin ingin log out?');"><i class="bi bi-power"></i>Logout</a></li>
        </ul>
    </nav>

    <div id="content">
        <?php if(isset($_GET['msg'])): ?>
            <?php if($_GET['msg'] == 'sesi_opened'): ?><div class="alert alert-success border-0 shadow-sm alert-dismissible fade show p-3 mb-4"><i class="bi bi-check-circle-fill me-2"></i>Berhasil ditambahkan! <button class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php elseif($_GET['msg'] == 'faq_updated'): ?><div class="alert alert-info border-0 shadow-sm alert-dismissible fade show p-3 mb-4"><i class="bi bi-info-circle-fill me-2"></i>Data FAQ berhasil diperbarui! <button class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php elseif($_GET['msg'] == 'sesi_closed'): ?><div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show p-3 mb-4"><i class="bi bi-trash-fill me-2"></i>Data berhasil dihapus. <button class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php elseif($_GET['msg'] == 'ulasan_deleted'): ?><div class="alert alert-warning border-0 shadow-sm alert-dismissible fade show p-3 mb-4"><i class="bi bi-trash3-fill me-2"></i>Ulasan dihapus. <button class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php elseif($_GET['msg'] == 'jadwal_success'): ?><div class="alert alert-success border-0 shadow-sm alert-dismissible fade show p-3 mb-4"><i class="bi bi-check-circle-fill me-2"></i>Data varian bunga baru berhasil disimpan! <button class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php elseif($_GET['msg'] == 'jadwal_updated'): ?><div class="alert alert-info border-0 shadow-sm alert-dismissible fade show p-3 mb-4"><i class="bi bi-info-circle-fill me-2"></i>Konfigurasi Google Maps diperbarui. <button class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php elseif($_GET['msg'] == 'jadwal_deleted'): ?><div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show p-3 mb-4"><i class="bi bi-trash-fill me-2"></i>Data produk berhasil dihapus. <button class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php elseif($_GET['msg'] == 'sosmed_added'): ?><div class="alert alert-success border-0 shadow-sm alert-dismissible fade show p-3 mb-4"><i class="bi bi-share-fill me-2"></i>Sosial Media berhasil ditambahkan! <button class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php elseif($_GET['msg'] == 'kategori_added'): ?><div class="alert alert-success border-0 shadow-sm alert-dismissible fade show p-3 mb-4"><i class="bi bi-check-circle-fill me-2"></i>Kategori ditambahkan beserta pilihan kustomnya. Kategori akan tampil di katalog setelah punya produk. <button class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php elseif($_GET['msg'] == 'kategori_opsi_saved'): ?><div class="alert alert-success border-0 shadow-sm alert-dismissible fade show p-3 mb-4"><i class="bi bi-check-circle-fill me-2"></i>Pilihan kustom kategori disimpan. Modal pesan di katalog langsung mengikuti pengaturan ini. <button class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php elseif($_GET['msg'] == 'kategori_ada'): ?><div class="alert alert-warning border-0 shadow-sm alert-dismissible fade show p-3 mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i>Kategori dengan nama itu sudah ada. <button class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php elseif($_GET['msg'] == 'kategori_kosong'): ?><div class="alert alert-warning border-0 shadow-sm alert-dismissible fade show p-3 mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i>Nama kategori tidak boleh kosong. <button class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php elseif($_GET['msg'] == 'kategori_dipakai'): ?><div class="alert alert-warning border-0 shadow-sm alert-dismissible fade show p-3 mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i>Kategori masih punya produk, jadi tidak bisa dihapus. Pindahkan atau hapus produknya dulu. <button class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php elseif($_GET['msg'] == 'kategori_deleted'): ?><div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show p-3 mb-4"><i class="bi bi-trash-fill me-2"></i>Kategori dihapus. <button class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php elseif($_GET['msg'] == 'status_updated'): ?><div class="alert alert-success border-0 shadow-sm alert-dismissible fade show p-3 mb-4"><i class="bi bi-check-circle-fill me-2"></i>Status pesanan diperbarui. Pelanggan bisa melihatnya di Riwayat Pesanan. <button class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php elseif($_GET['msg'] == 'pesanan_deleted'): ?><div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show p-3 mb-4"><i class="bi bi-trash-fill me-2"></i>Pesanan dihapus. <button class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php elseif($_GET['msg'] == 'sosmed_deleted'): ?><div class="alert alert-warning border-0 shadow-sm alert-dismissible fade show p-3 mb-4"><i class="bi bi-trash-fill me-2"></i>Sosial media berhasil dihapus. <button class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            
            <script>if(window.history.replaceState){const u=new URL(window.location);u.searchParams.delete('msg');window.history.replaceState(null,'',u.toString());}</script>
        <?php endif; ?>

        <div class="tab-content" id="adminTabContent">
            
            <div class="tab-pane fade show active" id="dashboard" role="tabpanel">
                <div class="mb-4">
                    <h3 class="judul-halaman mb-1">Akses Cepat Pengelolaan</h3>
                    <p class="text-muted mb-0">Klik kartu di bawah ini untuk menuju ke menu pengelolaan terkait secara langsung.</p>
                </div>
                
                <div class="row">
                    <div class="col-md-6 col-xl-3 mb-4">
                        <div class="stat-card" onclick="document.getElementById('pesanan-tab').click();">
                            <div class="icon-box ic-rose"><i class="bi bi-receipt-cutoff"></i></div>
                            <h3 class="fw-bold text-dark mb-0"><?= $pesanan_baru ?></h3>
                            <span class="text-muted fw-semibold mt-1">Pesanan Baru <small class="fw-normal">(dari <?= $total_pesanan ?>)</small></span>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3 mb-4">
                        <div class="stat-card" onclick="document.getElementById('produk-tab').click();">
                            <div class="icon-box ic-sage"><i class="bi bi-box-seam"></i></div>
                            <h3 class="fw-bold text-dark mb-0"><?= $total_produk ?></h3>
                            <span class="text-muted fw-semibold mt-1">Total Katalog Bunga</span>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3 mb-4">
                        <div class="stat-card" onclick="document.getElementById('faq-admin-tab').click();">
                            <div class="icon-box ic-sand"><i class="bi bi-question-circle"></i></div>
                            <h3 class="fw-bold text-dark mb-0"><?= $total_faq ?></h3>
                            <span class="text-muted fw-semibold mt-1">Total Pertanyaan FAQ</span>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3 mb-4">
                        <div class="stat-card" onclick="document.getElementById('ulasan-tab').click();">
                            <div class="icon-box ic-peach"><i class="bi bi-chat-heart"></i></div>
                            <h3 class="fw-bold text-dark mb-0"><?= $total_ulasan ?></h3>
                            <span class="text-muted fw-semibold mt-1">Total Masukan Pelanggan</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="pesanan-panel" role="tabpanel">
                <div class="card p-4">
                    <div class="mb-3">
                        <h4 class="judul-halaman mb-1">Pesanan Masuk</h4>
                        <p class="text-muted small mb-0">Ubah status pesanan di kolom Status. Perubahan langsung terlihat oleh pelanggan di halaman Riwayat Pesanan.</p>
                    </div>

                    <?php if (!$tabel_pesanan_ada): ?>
                        <div class="alert alert-warning border-0 mb-0"><i class="bi bi-exclamation-triangle-fill me-2"></i>Tabel <code>pesanan</code> belum ada. Jalankan file <code>migrasi_pesanan.sql</code> di phpMyAdmin (database <code>db_tokobunga</code>) lalu muat ulang halaman ini.</div>
                    <?php else: ?>

                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <a href="admin.php#pesanan-panel" class="btn btn-sm <?= $filter_status === '' ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill px-3">Semua <span class="opacity-75">(<?= $total_pesanan ?>)</span></a>
                        <?php foreach (STATUS_PESANAN as $sp): ?>
                            <a href="admin.php?status=<?= urlencode($sp) ?>#pesanan-panel" class="btn btn-sm <?= $filter_status === $sp ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill px-3"><?= e($sp) ?> <span class="opacity-75">(<?= $hitung_status[$sp] ?>)</span></a>
                        <?php endforeach; ?>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th>Kode</th><th>Pemesan</th><th>Pesanan</th><th>Pengambilan</th><th>Status</th><th class="text-center">Aksi</th></tr></thead>
                            <tbody>
                                <?php if (empty($daftar_pesanan)): ?><tr><td colspan="6" class="text-center py-4 text-muted">Belum ada pesanan<?= $filter_status !== '' ? ' dengan status ' . e($filter_status) : '' ?>.</td></tr><?php endif; ?>
                                <?php foreach ($daftar_pesanan as $ps): ?>
                                <tr>
                                    <td>
                                        <code class="fw-bold text-dark"><?= e($ps['kode_pesanan']) ?></code>
                                        <div class="small text-muted mt-1"><?= e(tgl_indo($ps['dibuat_pada'])) ?></div>
                                    </td>
                                    <td style="min-width: 170px;">
                                        <div class="fw-bold text-dark"><?= e($ps['nama_pemesan']) ?></div>
                                        <?php if (!empty($ps['tema_warna']) || !empty($ps['warna_wrapping'])): ?><div class="small text-muted">Tema: <?= e($ps['tema_warna'] ?: '-') ?> &middot; Wrap: <?= e($ps['warna_wrapping'] ?: '-') ?></div><?php endif; ?>
                                        <?php if (!empty($ps['catatan_penjual'])): ?><div class="small mt-1 p-2 rounded-2" style="background:#fff8e6; border-left:3px solid #f0b429;"><i class="bi bi-sticky me-1"></i><strong>Catatan:</strong> <?= nl2br(e($ps['catatan_penjual'])) ?></div><?php endif; ?>
                                        <?php if (!empty($ps['ucapan'])): ?><div class="small text-secondary fst-italic mt-1" title="<?= e($ps['ucapan']) ?>"><i class="bi bi-chat-quote me-1"></i><?= e(potong($ps['ucapan'], 40)) ?><?= strlen($ps['ucapan']) > 40 ? '...' : '' ?></div><?php endif; ?>
                                    </td>
                                    <td style="min-width: 200px;">
                                        <?php foreach (($detail_pesanan[$ps['id_pesanan']] ?? []) as $it): ?>
                                            <div class="small mb-1">
                                                <strong><?= e($it['nama_produk']) ?></strong> <span class="text-muted">&times; <?= (int)$it['qty'] ?></span>
                                                <?php foreach (kustom_item($it) as $label => $isi): ?>
                                                    <div class="text-muted ps-2" style="font-size:.78rem;"><i class="bi <?= e(ikon_kustom($label)) ?> me-1"></i><?= e($label) ?>: <span class="text-dark"><?= $label === 'Foto referensi' ? foto_html($isi, 72) : e($isi) ?></span></div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endforeach; ?>
                                        <div class="harga-admin mt-1"><?= rupiah($ps['total']) ?></div>
                                    </td>
                                    <td style="min-width: 160px;">
                                        <div class="small fw-semibold text-dark"><?= e(tgl_indo($ps['tanggal_ambil'], false)) ?>, <?= e(substr($ps['jam_ambil'], 0, 5)) ?></div>
                                        <div class="small text-muted"><?= e($ps['metode_ambil']) ?> &middot; <?= e($ps['metode_bayar']) ?></div>
                                    </td>
                                    <td>
                                        <form method="POST" class="m-0">
                                            <input type="hidden" name="id_pesanan" value="<?= (int)$ps['id_pesanan'] ?>">
                                            <input type="hidden" name="filter" value="<?= e($filter_status) ?>">
                                            <input type="hidden" name="ubah_status" value="1">
                                            <select name="status" class="form-select form-select-sm sel-status sel-<?= e($ps['status']) ?>" onchange="this.form.submit()">
                                                <?php foreach (STATUS_PESANAN as $sp): ?><option value="<?= e($sp) ?>" <?= $ps['status'] === $sp ? 'selected' : '' ?>><?= e($sp) ?></option><?php endforeach; ?>
                                            </select>
                                        </form>
                                        <div class="small text-muted mt-1">upd. <?= e(tgl_indo($ps['diperbarui_pada'])) ?></div>
                                    </td>
                                    <td class="text-center">
                                        <form method="POST" class="m-0" onsubmit="return confirm('Hapus pesanan <?= e($ps['kode_pesanan']) ?>? Riwayat pelanggan juga ikut hilang.');">
                                            <input type="hidden" name="id_pesanan" value="<?= (int)$ps['id_pesanan'] ?>">
                                            <input type="hidden" name="filter" value="<?= e($filter_status) ?>">
                                            <button type="submit" name="hapus_pesanan" class="btn btn-sm btn-light border text-danger btn-action" title="Hapus pesanan"><i class="bi bi-trash3-fill"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="tab-pane fade" id="produk-panel" role="tabpanel">
                <div class="card p-4">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
                        <div><h4 class="judul-halaman mb-1">Katalog Produk Bunga</h4></div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="btn btn-outline-secondary fw-bold text-nowrap rounded-3 px-3 py-2" data-bs-toggle="modal" data-bs-target="#modalKategori"><i class="bi bi-tags me-2"></i>Kelola Kategori</button>
                            <button type="button" class="btn btn-primary fw-bold text-nowrap rounded-3 px-3 py-2" data-bs-toggle="modal" data-bs-target="#modalTambahProduk"><i class="bi bi-plus-circle me-2"></i>Tambah Bunga</button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th>Foto</th><th>Produk</th><th>Harga & Stok</th><th class="text-center">Aksi</th></tr></thead>
                            <tbody>
                                <?php mysqli_data_seek($produk, 0); while($row = mysqli_fetch_assoc($produk)): ?>
                                <tr>
                                    <td><img src="uploads/<?= $row['foto'] ?>" width="55" height="55" class="img-preview"></td>
                                    <td><h6 class="mb-1 fw-bold text-dark" style="font-size: 0.95rem;"><?= $row['nama_produk'] ?></h6><span class="badge bg-light text-secondary border"><?= $row['nama_kategori'] ?></span></td>
                                    <td><div class="harga-admin">Rp <?= number_format($row['harga'], 0, ',', '.') ?></div><small class="text-muted fw-medium">Sisa: <?= $row['stok'] ?></small></td>
                                    <td class="text-center">
                                        <a href="edit.php?id=<?= $row['id_produk'] ?>" class="btn btn-sm btn-light border text-primary btn-action"><i class="bi bi-pencil-square"></i></a> 
                                        <a href="admin.php?hapus=<?= $row['id_produk'] ?>" class="btn btn-sm btn-light border text-danger btn-action" onclick="return confirm('Hapus produk ini?');"><i class="bi bi-trash3-fill"></i></a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="faq-panel" role="tabpanel">
                <div class="card p-4">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
                        <div><h4 class="judul-halaman mb-1">Pusat Pengaturan FAQ</h4></div>
                        <button type="button" class="btn btn-primary fw-bold rounded-3 px-3 py-2 text-nowrap" data-bs-toggle="modal" data-bs-target="#modalTambahFAQ"><i class="bi bi-plus-circle me-2"></i>Tambah FAQ</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th width="35%">Pertanyaan Publik</th><th width="50%">Isi Jawaban</th><th width="15%" class="text-center">Aksi</th></tr></thead>
                            <tbody>
                                <?php if(mysqli_num_rows($faq) == 0): ?><tr><td colspan="3" class="text-center py-4 text-muted">Belum ada data FAQ.</td></tr><?php endif; ?>
                                <?php while($f = mysqli_fetch_assoc($faq)): ?>
                                <tr>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($f['pertanyaan']) ?></td>
                                    <td class="text-secondary"><?= nl2br(htmlspecialchars($f['jawaban'])) ?></td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-light border text-primary btn-action" onclick="bukaModalEditFAQ(<?= $f['id_faq'] ?>, '<?= addslashes(htmlspecialchars($f['pertanyaan'])) ?>', '<?= addslashes(htmlspecialchars(preg_replace("/\r|\n/", " ", $f['jawaban']))) ?>')"><i class="bi bi-pencil-square"></i></button>
                                        <a href="admin.php?hapus_faq=<?= $f['id_faq'] ?>" class="btn btn-sm btn-light border text-danger btn-action" onclick="return confirm('Hapus FAQ ini?');"><i class="bi bi-trash-fill"></i></a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="tab-pane fade" id="sosmed-panel" role="tabpanel">
                <div class="card p-4">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
                        <div><h4 class="judul-halaman mb-1">Manajemen Sosial Media</h4></div>
                        <button type="button" class="btn btn-primary fw-bold text-nowrap rounded-3 px-3 py-2" data-bs-toggle="modal" data-bs-target="#modalTambahSosmed"><i class="bi bi-plus-circle me-2"></i>Tambah Sosmed</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th width="10%">Ikon</th><th width="25%">Nama Platform</th><th width="50%">Tautan URL (Link)</th><th width="15%" class="text-center">Aksi</th></tr></thead>
                            <tbody>
                                <?php if(mysqli_num_rows($sosmed) == 0): ?><tr><td colspan="4" class="text-center py-4 text-muted">Belum ada sosmed ditambahkan.</td></tr><?php endif; ?>
                                <?php while($s = mysqli_fetch_assoc($sosmed)): ?>
                                <tr>
                                    <td><i class="bi <?= htmlspecialchars($s['icon_sosmed']) ?> fs-3 text-primary"></i></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($s['nama_sosmed']) ?></td>
                                    <td><a href="<?= htmlspecialchars($s['link_sosmed']) ?>" target="_blank" class="text-primary text-decoration-none"><?= htmlspecialchars($s['link_sosmed']) ?></a></td>
                                    <td class="text-center"><a href="admin.php?hapus_sosmed=<?= $s['id_sosmed'] ?>" class="btn btn-sm btn-light border text-danger btn-action" onclick="return confirm('Hapus Sosmed ini?');"><i class="bi bi-trash-fill"></i></a></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="ulasan-panel" role="tabpanel">
                <div class="card p-4">
                    <h4 class="judul-halaman mb-3">Saran & Masukan Pelanggan</h4>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th width="20%">Pengirim</th><th width="50%">Isi Pesan</th><th width="18%">Waktu Masuk</th><th width="12%" class="text-center">Aksi</th></tr></thead>
                            <tbody>
                                <?php if(mysqli_num_rows($ulasan) == 0): ?><tr><td colspan="4" class="text-center py-4 text-muted">Belum ada masukan.</td></tr><?php endif; ?>
                                <?php while($u = mysqli_fetch_assoc($ulasan)): ?>
                                <tr>
                                    <td class="fw-bold text-dark"><i class="bi bi-person-circle text-secondary me-2"></i><?= htmlspecialchars($u['nama']) ?></td>
                                    <td class="text-secondary"><?= nl2br(htmlspecialchars($u['isi_masukan'])) ?></td>
                                    <td class="small text-muted font-monospace"><?= date('d/m/y H:i', strtotime($u['tanggal'])) ?></td>
                                    <td class="text-center"><a href="admin.php?hapus_ulasan=<?= $u['id_ulasan'] ?>" class="btn btn-sm btn-light border text-danger btn-action" onclick="return confirm('Hapus pesan ini?');"><i class="bi bi-trash3-fill"></i></a></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="pengaturan-panel" role="tabpanel">
                <div class="card p-4">
                    <h4 class="judul-halaman mb-1">Konfigurasi Peta Toko</h4>
                    <p class="text-muted small mb-4">Ganti link embed iframe Google Maps lokasi toko fisik Anda di sini.</p>
                    <form method="POST" action="">
                        <div class="mb-4">
                            <label class="form-label fw-bold text-secondary">URL Atribut `src` Iframe Google Maps</label>
                            <textarea name="link_maps" class="form-control font-monospace bg-light border-0" rows="5" required><?= htmlspecialchars($current_maps) ?></textarea>
                        </div>
                        <button type="submit" name="update_maps" class="btn btn-primary rounded-3 px-4 py-2 fw-bold">Simpan Peta</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalKategori" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content border-0 shadow-lg">
    <div class="modal-header modal-head-tema"><h5 class="modal-title fw-bold"><i class="bi bi-tags me-2"></i>Kelola Kategori</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
    <div class="modal-body p-4">
        <form method="POST" class="mb-4 p-3 rounded-4" style="background: var(--bg-light); border: 1px dashed #e3d6d8;">
            <label class="form-label fw-bold small text-muted mb-1">Tambah kategori baru</label>
            <input type="text" name="nama_kategori" class="form-control rounded-3 mb-3" maxlength="50" placeholder="Nama kategori baru, mis. Karangan Bunga" required>
            <div class="small fw-bold text-muted mb-2"><i class="bi bi-sliders me-1"></i>Isian apa yang tampil di modal pesan untuk kategori ini?</div>
            <?= kotak_opsi('baru', []) ?>
            <div class="d-flex justify-content-between align-items-center mt-3 gap-2 flex-wrap">
                <small class="text-muted">Tidak ada yang dicentang = pembeli hanya memilih jumlah.</small>
                <button type="submit" name="tambah_kategori" class="btn btn-primary fw-bold text-nowrap px-4"><i class="bi bi-plus-lg me-1"></i>Tambah Kategori</button>
            </div>
        </form>

        <div class="small fw-bold text-muted mb-2 text-uppercase" style="letter-spacing:.5px;">Kategori yang ada</div>
        <?php while($kk = mysqli_fetch_assoc($kategori_kelola)): $opsi_kk = opsi_aktif($kk['opsi_kustom']); ?>
        <div class="kat-baris">
            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                <div>
                    <span class="fw-bold"><?= e($kk['nama_kategori']) ?></span>
                    <span class="badge bg-light text-secondary border ms-1"><?= (int)$kk['jml'] ?> produk</span>
                    <div class="d-flex flex-wrap gap-1 mt-2">
                        <?php foreach ($opsi_kk as $ok): ?><span class="chip-opsi"><i class="bi <?= e(OPSI_KUSTOM[$ok]['ikon']) ?>"></i><?= e(OPSI_KUSTOM[$ok]['label']) ?></span><?php endforeach; ?>
                        <?php if (!$opsi_kk): ?><span class="chip-opsi kosong">Tanpa pilihan kustom</span><?php endif; ?>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-bs-toggle="collapse" data-bs-target="#atur<?= (int)$kk['id_kategori'] ?>"><i class="bi bi-sliders me-1"></i>Atur modal</button>
                    <?php if ((int)$kk['jml'] === 0): ?>
                        <a href="admin.php?hapus_kategori=<?= (int)$kk['id_kategori'] ?>" class="btn btn-sm btn-light border text-danger btn-action" onclick="return confirm('Hapus kategori <?= e($kk['nama_kategori']) ?>?');" title="Hapus"><i class="bi bi-trash3-fill"></i></a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="collapse" id="atur<?= (int)$kk['id_kategori'] ?>">
                <form method="POST" class="pt-3">
                    <input type="hidden" name="id_kategori" value="<?= (int)$kk['id_kategori'] ?>">
                    <?= kotak_opsi('kat' . (int)$kk['id_kategori'], $opsi_kk) ?>
                    <div class="text-end mt-3"><button type="submit" name="simpan_opsi_kategori" class="btn btn-sm btn-primary fw-bold px-4">Simpan Pilihan</button></div>
                </form>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div></div></div>

<div class="modal fade" id="modalTambahProduk" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow-lg"><div class="modal-header modal-head-tema"><h5 class="modal-title fw-bold">Registrasi Bunga</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><form method="POST" enctype="multipart/form-data"><div class="modal-body p-4"><div class="mb-3"><label class="form-label fw-bold small text-muted">Nama Produk</label><input type="text" name="nama_produk" class="form-control rounded-3 bg-light" required></div><div class="mb-3"><label class="form-label fw-bold small text-muted">Kategori</label><select name="id_kategori" class="form-select rounded-3 bg-light" required><?php mysqli_data_seek($kategori, 0); while($k = mysqli_fetch_assoc($kategori)): ?><option value="<?= $k['id_kategori'] ?>"><?= $k['nama_kategori'] ?></option><?php endwhile; ?></select></div><div class="row mb-3"><div class="col-7"><label class="form-label fw-bold small text-muted">Harga (Rp)</label><input type="number" name="harga" class="form-control rounded-3 bg-light" required></div><div class="col-5"><label class="form-label fw-bold small text-muted">Stok</label><input type="number" name="stok" class="form-control rounded-3 bg-light" required></div></div><div class="mb-3"><label class="form-label fw-bold small text-muted">Deskripsi</label><textarea name="deskripsi" class="form-control rounded-3 bg-light" rows="3" required></textarea></div><div class="mb-2"><label class="form-label fw-bold small text-muted">Foto Produk</label><input type="file" name="foto" class="form-control rounded-3 bg-light" accept="image/*" required></div></div><div class="modal-footer border-0 p-3 bg-light-subtle"><button type="button" class="btn btn-secondary px-4 rounded-3" data-bs-dismiss="modal">Batal</button><button type="submit" name="tambah" class="btn btn-primary px-4 rounded-3 fw-bold">Simpan</button></div></form></div></div></div>

<div class="modal fade" id="modalTambahFAQ" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow-lg"><div class="modal-header modal-head-tema"><h5 class="modal-title fw-bold">Tanya Jawab Baru</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><form method="POST"><div class="modal-body p-4"><div class="mb-3"><label class="form-label fw-bold small text-muted">Pertanyaan</label><input type="text" name="pertanyaan" class="form-control rounded-3 bg-light" required></div><div class="mb-3"><label class="form-label fw-bold small text-muted">Jawaban</label><textarea name="jawaban" class="form-control rounded-3 bg-light" rows="4" required></textarea></div></div><div class="modal-footer border-0 p-3 bg-light-subtle"><button type="button" class="btn btn-secondary px-4 rounded-3" data-bs-dismiss="modal">Batal</button><button type="submit" name="tambah_faq" class="btn btn-primary px-4 rounded-3 fw-bold">Simpan FAQ</button></div></form></div></div></div>

<div class="modal fade" id="modalEditFAQ" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow-lg"><div class="modal-header modal-head-tema"><h5 class="modal-title fw-bold">Edit FAQ</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><form method="POST"><input type="hidden" name="id_faq" id="edit_id_faq"><div class="modal-body p-4"><div class="mb-3"><label class="form-label fw-bold small text-muted">Pertanyaan</label><input type="text" name="pertanyaan" id="edit_pertanyaan_faq" class="form-control rounded-3 bg-light" required></div><div class="mb-3"><label class="form-label fw-bold small text-muted">Jawaban</label><textarea name="jawaban" id="edit_jawaban_faq" class="form-control rounded-3 bg-light" rows="4" required></textarea></div></div><div class="modal-footer border-0 p-3 bg-light-subtle"><button type="button" class="btn btn-secondary px-4 rounded-3" data-bs-dismiss="modal">Batal</button><button type="submit" name="edit_faq" class="btn btn-primary px-4 rounded-3 fw-bold">Update</button></div></form></div></div></div>

<div class="modal fade" id="modalTambahSosmed" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow-lg"><div class="modal-header modal-head-tema"><h5 class="modal-title fw-bold">Tambah Sosmed</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><form method="POST"><div class="modal-body p-4"><div class="mb-3"><label class="form-label fw-bold small text-muted">Nama Platform</label><input type="text" name="nama_sosmed" class="form-control rounded-3 bg-light" required placeholder="Cth: Instagram"></div><div class="mb-3"><label class="form-label fw-bold small text-muted">Pilih Ikon (Bootstrap Icons)</label><select name="icon_sosmed" class="form-select rounded-3 bg-light" required><option value="bi-instagram">Instagram (bi-instagram)</option><option value="bi-whatsapp">WhatsApp (bi-whatsapp)</option><option value="bi-tiktok">TikTok (bi-tiktok)</option><option value="bi-facebook">Facebook (bi-facebook)</option><option value="bi-twitter-x">Twitter / X (bi-twitter-x)</option><option value="bi-youtube">YouTube (bi-youtube)</option><option value="bi-globe">Web Lainnya (bi-globe)</option></select></div><div class="mb-2"><label class="form-label fw-bold small text-muted">URL / Link Penuh</label><input type="url" name="link_sosmed" class="form-control rounded-3 bg-light" required placeholder="https://..."></div></div><div class="modal-footer border-0 p-3 bg-light-subtle"><button type="button" class="btn btn-secondary px-4 rounded-3" data-bs-dismiss="modal">Batal</button><button type="submit" name="tambah_sosmed" class="btn btn-primary px-4 rounded-3 fw-bold">Simpan</button></div></form></div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Logic URL Hash (Mengingat tab yang aktif setelah form submit)
document.addEventListener("DOMContentLoaded", function() {
    let hash = window.location.hash;
    if (hash) {
        let targetTabButton = document.querySelector('button[data-bs-target="' + hash + '"]');
        if (targetTabButton) { new bootstrap.Tab(targetTabButton).show(); }
    }
    document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(el => {
        el.addEventListener('shown.bs.tab', e => {
            if(history.replaceState) { history.replaceState(null, null, e.target.getAttribute("data-bs-target")); }
        });
    });
});

// Sistem Pembuka Modal Edit FAQ via JS
function bukaModalEditFAQ(id, pertanyaan, jawaban) {
    document.getElementById('edit_id_faq').value = id; 
    document.getElementById('edit_pertanyaan_faq').value = pertanyaan; 
    document.getElementById('edit_jawaban_faq').value = jawaban;
    let modalEl = document.getElementById('modalEditFAQ'); 
    let modalInstance = bootstrap.Modal.getInstance(modalEl);
    if (!modalInstance) { modalInstance = new bootstrap.Modal(modalEl); } 
    modalInstance.show();
}

// Sistem Sidebar Mobile
const sidebar = document.getElementById('sidebar'); const overlay = document.getElementById('sidebarOverlay');
function toggleSidebar() { sidebar.classList.toggle('active'); overlay.classList.toggle('active'); }
document.getElementById('sidebarCollapseBtn').addEventListener('click', toggleSidebar);
document.getElementById('sidebarCloseBtn').addEventListener('click', toggleSidebar);
overlay.addEventListener('click', toggleSidebar);
document.querySelectorAll('#sidebar .nav-link').forEach(link => { link.addEventListener('click', () => { if (window.innerWidth < 992) toggleSidebar(); }); });
</script>
</body>
</html>