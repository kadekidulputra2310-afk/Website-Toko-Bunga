<?php
session_start();
require 'config.php';
require 'helpers.php';

pastikan_skema($conn); // tambah kolom kustomisasi di database lama bila belum ada

// ---------------------------------------------------------
// Hapus item / ubah jumlah (satu baris keranjang = produk + pilihan kustomnya)
// ---------------------------------------------------------
if (isset($_GET['remove'])) {
    $foto_hapus = foto_referensi_valid($_SESSION['cart'][(string)$_GET['remove']]['foto_referensi'] ?? '');
    if ($foto_hapus !== '') @unlink(DIR_REFERENSI . $foto_hapus); // foto belum dipesan -> bersihkan
    unset($_SESSION['cart'][(string)$_GET['remove']]);
    header('Location: keranjang.php');
    exit;
}
if (isset($_GET['plus']) || isset($_GET['minus'])) {
    $kunci = (string)($_GET['plus'] ?? $_GET['minus']);
    if (isset($_SESSION['cart'][$kunci])) {
        $idp = (int)($_SESSION['cart'][$kunci]['id_produk'] ?? $kunci);
        $st = mysqli_prepare($conn, "SELECT stok FROM produk WHERE id_produk = ?");
        mysqli_stmt_bind_param($st, 'i', $idp);
        mysqli_stmt_execute($st);
        $r = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
        mysqli_stmt_close($st);
        $maks = $r ? max(1, min(99, (int)$r['stok'])) : 99;

        $qty = (int)$_SESSION['cart'][$kunci]['qty'] + (isset($_GET['plus']) ? 1 : -1);
        $_SESSION['cart'][$kunci]['qty'] = max(1, min($maks, $qty));
    }
    header('Location: keranjang.php');
    exit;
}

// ---------------------------------------------------------
// CHECKOUT: simpan pesanan ke database, kosongkan keranjang,
// lalu kirim link WhatsApp ke browser (dipanggil lewat fetch dari JS)
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'checkout') {
    header('Content-Type: application/json; charset=utf-8');
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // error DB jadi exception supaya bisa di-rollback

    $gagal = function ($pesan) {
        echo json_encode(['ok' => false, 'pesan' => $pesan]);
        exit;
    };

    $cart = $_SESSION['cart'] ?? [];
    if (empty($cart)) $gagal('Keranjang kamu masih kosong.');

    // --- Ambil & validasi isian form ---
    $nama     = potong($_POST['nama'] ?? '', 100);
    $catatan_penjual = rapikan_teks($_POST['catatan_penjual'] ?? '', 500);
    $tanggal  = trim($_POST['tanggal'] ?? '');
    $jam      = trim($_POST['jam'] ?? '');
    $ambil    = $_POST['pengambilan'] ?? '';
    $bayar    = $_POST['pembayaran'] ?? '';

    if ($nama === '') $gagal('Nama pemesan wajib diisi.');

    $dt = DateTime::createFromFormat('Y-m-d', $tanggal);
    if (!$dt || $dt->format('Y-m-d') !== $tanggal) $gagal('Tanggal pengambilan tidak valid.');
    if ($tanggal < date('Y-m-d')) $gagal('Tanggal pengambilan tidak boleh tanggal yang sudah lewat.');

    $tm = DateTime::createFromFormat('H:i', $jam);
    if (!$tm || $tm->format('H:i') !== $jam) $gagal('Jam pengambilan tidak valid.');
    if ($jam < '09:00' || $jam > '19:00') $gagal('Jam pengambilan harus di antara 09:00 - 19:00.');

    if (!in_array($ambil, PILIHAN_PENGAMBILAN, true)) $gagal('Pilih metode pengambilan.');
    if (!in_array($bayar, PILIHAN_PEMBAYARAN, true))  $gagal('Pilih metode pembayaran.');

    try {
        // --- Ambil harga & stok terbaru dari database (jangan percaya data dari browser) ---
        $ids = [];
        foreach ($cart as $kunci => $c) $ids[] = (int)($c['id_produk'] ?? $kunci); // integer -> aman untuk IN()
        $in  = implode(',', array_unique($ids));
        $res = mysqli_query($conn, "SELECT id_produk, nama_produk, harga, stok FROM produk WHERE id_produk IN ($in)");
        $produk_db = [];
        while ($r = mysqli_fetch_assoc($res)) $produk_db[(int)$r['id_produk']] = $r;

        $items = [];
        $total = 0;
        $tidak_tersedia = [];
        $qty_per_produk = [];
        foreach ($cart as $kunci => $c) {
            $id  = (int)($c['id_produk'] ?? $kunci);
            $qty = max(1, min(99, (int)($c['qty'] ?? 1)));
            if (!isset($produk_db[$id]) || (int)$produk_db[$id]['stok'] <= 0) {
                $tidak_tersedia[] = $c['nama'] ?? ('#' . $id);
                continue;
            }
            $qty_per_produk[$id] = ($qty_per_produk[$id] ?? 0) + $qty;
            if ($qty_per_produk[$id] > (int)$produk_db[$id]['stok']) {
                $gagal('Stok ' . $produk_db[$id]['nama_produk'] . ' tinggal ' . (int)$produk_db[$id]['stok'] . '. Kurangi jumlahnya di keranjang ya.');
            }
            $harga = (int)$produk_db[$id]['harga'];
            $items[] = [
                'id_produk' => $id, 'nama_produk' => $produk_db[$id]['nama_produk'], 'harga' => $harga, 'qty' => $qty,
                'warna_bunga'    => rapikan_teks($c['warna_bunga'] ?? '', 150),
                'warna_wrapping' => rapikan_teks($c['warna_wrapping'] ?? '', 150),
                'ucapan'         => rapikan_teks($c['ucapan'] ?? '', 300),
                'foto_referensi' => foto_referensi_valid($c['foto_referensi'] ?? ''),
                'jenis_ucapan'   => rapikan_teks($c['jenis_ucapan'] ?? '', 150),
                'catatan'        => rapikan_teks($c['catatan'] ?? '', 300),
            ];
            $total += $harga * $qty;
        }
        if ($tidak_tersedia) {
            $gagal('Produk ini sudah tidak tersedia: ' . implode(', ', $tidak_tersedia) . '. Hapus dulu dari keranjang ya.');
        }

        // --- Simpan pesanan + detailnya dalam satu transaksi ---
        $token    = token_pemesan(true);
        $sekarang = date('Y-m-d H:i:s');
        $kode     = buat_kode_pesanan($conn);
        $jam_db   = $jam . ':00';

        mysqli_begin_transaction($conn);

        $stmt = mysqli_prepare($conn, "INSERT INTO pesanan
            (kode_pesanan, token_pemesan, nama_pemesan, catatan_penjual, tanggal_ambil, jam_ambil, metode_ambil, metode_bayar, ucapan, total, status, dibuat_pada, diperbarui_pada)
            VALUES (?,?,?,?,?,?,?,?,?,?,'Diterima',?,?)");
        $ucapan = ''; // ucapan kini disimpan per produk (detail_pesanan.ucapan); kolom ini hanya untuk pesanan lama
        mysqli_stmt_bind_param($stmt, 'sssssssssiss',
            $kode, $token, $nama, $catatan_penjual, $tanggal, $jam_db, $ambil, $bayar, $ucapan, $total, $sekarang, $sekarang);
        mysqli_stmt_execute($stmt);
        $id_pesanan = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($conn, "INSERT INTO detail_pesanan (id_pesanan, id_produk, nama_produk, harga, qty, warna_bunga, warna_wrapping, ucapan, foto_referensi, jenis_ucapan, catatan) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        foreach ($items as $it) {
            mysqli_stmt_bind_param($stmt, 'iisiissssss', $id_pesanan, $it['id_produk'], $it['nama_produk'], $it['harga'], $it['qty'], $it['warna_bunga'], $it['warna_wrapping'], $it['ucapan'], $it['foto_referensi'], $it['jenis_ucapan'], $it['catatan']);
            mysqli_stmt_execute($stmt);
        }
        mysqli_stmt_close($stmt);

        mysqli_commit($conn);

        // --- Pesanan aman tersimpan -> kosongkan keranjang ---
        unset($_SESSION['cart']);

        $p = [
            'kode_pesanan' => $kode, 'nama_pemesan' => $nama, 'catatan_penjual' => $catatan_penjual,
            'tanggal_ambil' => $tanggal, 'jam_ambil' => $jam_db, 'metode_ambil' => $ambil, 'metode_bayar' => $bayar,
            'ucapan' => '', 'total' => $total,
        ];
        echo json_encode(['ok' => true, 'kode' => $kode, 'wa_url' => link_wa($p, $items)]);
    } catch (Throwable $ex) {
        try { mysqli_rollback($conn); } catch (Throwable $x) {}
        error_log('Checkout gagal: ' . $ex->getMessage());
        echo json_encode(['ok' => false, 'pesan' => 'Maaf, pesanan belum bisa disimpan. Pastikan tabel pesanan sudah dibuat (lihat migrasi_pesanan.sql) lalu coba lagi.']);
    }
    exit;
}

// ---------------------------------------------------------
// Tampilan halaman keranjang
// ---------------------------------------------------------
$foto_produk = [];
$stok_produk = [];
$opsi_produk = [];
$query_foto = mysqli_query($conn, "SELECT p.id_produk, p.foto, p.stok, k.opsi_kustom FROM produk p LEFT JOIN kategori k ON p.id_kategori = k.id_kategori");
while ($row = mysqli_fetch_assoc($query_foto)) {
    $foto_produk[$row['id_produk']] = $row['foto'];
    $stok_produk[$row['id_produk']] = (int)$row['stok'];
    $opsi_produk[$row['id_produk']] = opsi_aktif($row['opsi_kustom']);
}

// Jumlah pesanan aktif milik perangkat ini (untuk lencana di tombol Riwayat)
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
    <title>Keranjang Pesanan - Kami Florist</title>
    <link rel="icon" type="image/svg+xml" href="assets/favicon.svg">
    <link rel="alternate icon" href="assets/favicon.ico">
    <link rel="apple-touch-icon" href="assets/apple-touch-icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,500;0,600;1,500&family=Nunito+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/tema.css">

    <style>
        .kolom-tengah { max-width: 880px; margin: 0 auto; }
        .item-kr { display: flex; gap: 16px; padding: 18px 0; border-bottom: 1px solid var(--bg-cream); }
        .item-kr:last-child { border-bottom: 0; }
        .item-kr .img-cart { width: 92px; height: 92px; object-fit: cover; border-radius: 14px; box-shadow: 0 4px 10px rgba(0,0,0,0.06); flex: none; }
        .item-kr .info { flex: 1; min-width: 0; }
        .opsi-kr { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
        .opsi-kr .tag { display: inline-flex; align-items: center; gap: 5px; font-size: .78rem; background: var(--primary-soft); color: var(--primary-hover); padding: 3px 10px; border-radius: 50px; font-weight: 600; max-width: 100%; }
        .opsi-kr .tag.polos { background: var(--bg-cream); color: var(--text-muted); font-weight: 400; }
        .catatan-kr { font-size: .8rem; color: var(--text-muted); font-style: italic; margin-top: 6px; display: flex; gap: 6px; }
        .kontrol-kr { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: 12px; flex-wrap: wrap; }
        .stepper-kr { display: inline-flex; align-items: center; border: 1.5px solid #eadfe1; border-radius: 50px; overflow: hidden; background: #fff; }
        .stepper-kr a, .stepper-kr span.nonaktif { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; color: var(--primary); text-decoration: none; font-size: 1.05rem; }
        .stepper-kr a:hover { background: var(--primary-soft); }
        .stepper-kr span.nonaktif { color: #ccc; }
        .stepper-kr b { min-width: 30px; text-align: center; font-size: .92rem; }
        .btn-hapus { background-color: #fff5f5; border: 1px solid #ffe3e3; }
        .empty-cart i { color: var(--primary); opacity: 0.55; }
        .total-kr { display: flex; justify-content: space-between; align-items: center; padding: 18px 0 2px; border-top: 2px solid var(--bg-cream); margin-top: 6px; }
        .daftar-kr { display: grid; grid-template-columns: 1fr 1fr; column-gap: 28px; }
        .grup-form { background: var(--bg-light); border-radius: 14px; padding: 18px 18px 6px; margin-bottom: 18px; }
        .grup-form h6 { font-family: 'Lora', serif; font-weight: 600; font-size: .98rem; margin-bottom: 14px; color: var(--text-dark); }
        .grup-form h6 i { color: var(--primary); margin-right: 6px; }
        .grup-form .form-control, .grup-form .form-select { background-color: #fff; }
        .ringkas-total { display: flex; align-items: center; justify-content: space-between; gap: 12px; background: var(--primary-soft); border-radius: 14px; padding: 14px 18px; margin-bottom: 16px; }
        @media (max-width: 767.98px) { .daftar-kr { grid-template-columns: 1fr; } }
        .langkah { display: flex; gap: 10px; flex-wrap: wrap; font-size: 0.85rem; color: var(--text-muted); margin-bottom: 22px; }
        .langkah span { display: inline-flex; align-items: center; gap: 6px; }
        .langkah .aktif { color: var(--primary); font-weight: 700; }
        .langkah .sep { color: #cfcac4; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand brand-font" href="index.php"><i class="bi bi-arrow-left-short me-1"></i> Kembali ke Katalog</a>
        <a href="riwayat.php" class="btn btn-outline-premium btn-sm d-inline-flex align-items-center">
            <i class="bi bi-clock-history me-2"></i>Riwayat
            <?php if ($jumlah_aktif > 0): ?><span class="badge-tema ms-2"><?= (int)$jumlah_aktif ?></span><?php endif; ?>
        </a>
    </div>
</nav>

<div class="container py-4 py-md-5 kolom-tengah">
    <div class="langkah">
        <span class="aktif"><i class="bi bi-1-circle-fill"></i> Cek keranjang</span><span class="sep">›</span>
        <span class="aktif"><i class="bi bi-2-circle-fill"></i> Isi detail</span><span class="sep">›</span>
        <span><i class="bi bi-3-circle"></i> Kirim ke WhatsApp</span><span class="sep">›</span>
        <span><i class="bi bi-4-circle"></i> Pantau status di Riwayat</span>
    </div>

    <div class="d-flex flex-column gap-4">

        <div>
            <div class="card-premium">
                <div class="card-header-premium">
                    <h4 class="brand-font fw-bold mb-0" style="color: var(--text-dark);"><i class="bi bi-bag-heart text-primary-theme me-2"></i>Keranjang Belanja</h4>
                </div>
                <div class="card-body px-4 py-3">
                <?php
                $total_belanja = 0;
                if (!empty($_SESSION['cart'])):
                    echo '<div class="daftar-kr">';
                    foreach ($_SESSION['cart'] as $kunci => $item):
                        $id_p     = (int)($item['id_produk'] ?? $kunci);
                        $subtotal = $item['harga'] * $item['qty'];
                        $total_belanja += $subtotal;
                        $gambar   = isset($foto_produk[$id_p]) ? "uploads/" . e($foto_produk[$id_p]) : "https://via.placeholder.com/92";
                        $maks_qty = min(99, $stok_produk[$id_p] ?? 99);
                        $kustom   = kustom_item($item);
                        $opsi_p   = $opsi_produk[$id_p] ?? OPSI_DEFAULT;
                        $bisa_warna = in_array('warna_bunga', $opsi_p, true) || in_array('warna_wrap', $opsi_p, true);
                        $ada_tag    = $bisa_warna || !empty($kustom['Jenis ucapan']) || !empty($kustom['Warna bunga']) || !empty($kustom['Warna wrapping']);
                ?>
                    <div class="item-kr">
                        <img src="<?= $gambar ?>" class="img-cart" alt="<?= e($item['nama']) ?>">
                        <div class="info">
                            <div class="d-flex justify-content-between gap-2">
                                <div>
                                    <h6 class="fw-bold mb-1" style="color: var(--text-dark);"><?= e($item['nama']) ?></h6>
                                    <small style="color: var(--text-muted);"><?= rupiah($item['harga']) ?> / pcs</small>
                                </div>
                                <a href="keranjang.php?remove=<?= e($kunci) ?>" class="btn btn-sm btn-light btn-hapus text-danger rounded-circle p-2 shadow-sm align-self-start" title="Hapus dari keranjang" aria-label="Hapus"><i class="bi bi-trash3-fill"></i></a>
                            </div>

                            <div class="opsi-kr<?= $ada_tag ? '' : ' d-none' ?>">
                                <?php if (!empty($kustom['Warna bunga'])): ?><span class="tag"><i class="bi bi-flower2"></i>Bunga: <?= e($kustom['Warna bunga']) ?></span><?php endif; ?>
                                <?php if (!empty($kustom['Warna wrapping'])): ?><span class="tag"><i class="bi bi-gift"></i>Wrap: <?= e($kustom['Warna wrapping']) ?></span><?php endif; ?>
                                <?php if (!empty($kustom['Jenis ucapan'])): ?><span class="tag"><i class="bi bi-balloon-heart"></i><?= e($kustom['Jenis ucapan']) ?></span><?php endif; ?>
                                <?php if ($bisa_warna && empty($kustom['Warna bunga']) && empty($kustom['Warna wrapping'])): ?><span class="tag polos">Warna sesuai rekomendasi toko</span><?php endif; ?>
                            </div>
                            <?php if (!empty($kustom['Foto referensi'])): ?>
                                <div class="catatan-kr" style="font-style: normal;"><i class="bi bi-image"></i><span>Foto referensi<?= foto_html($kustom['Foto referensi'], 72) ?></span></div>
                            <?php endif; ?>
                            <?php if (!empty($kustom['Ucapan'])): ?>
                                <div class="catatan-kr"><i class="bi bi-chat-quote"></i><span>Ucapan: <?= e($kustom['Ucapan']) ?></span></div>
                            <?php endif; ?>
                            <?php if (!empty($kustom['Catatan'])): ?>
                                <div class="catatan-kr"><i class="bi bi-chat-left-text"></i><span><?= e($kustom['Catatan']) ?></span></div>
                            <?php endif; ?>

                            <div class="kontrol-kr">
                                <div class="stepper-kr">
                                    <?php if ($item['qty'] > 1): ?><a href="keranjang.php?minus=<?= e($kunci) ?>" aria-label="Kurangi">&minus;</a><?php else: ?><span class="nonaktif">&minus;</span><?php endif; ?>
                                    <b><?= (int)$item['qty'] ?></b>
                                    <?php if ($item['qty'] < $maks_qty): ?><a href="keranjang.php?plus=<?= e($kunci) ?>" aria-label="Tambah">+</a><?php else: ?><span class="nonaktif">+</span><?php endif; ?>
                                </div>
                                <div class="fw-bold text-primary-theme brand-font"><?= rupiah($subtotal) ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                    <?php echo '</div>'; ?>
                    <div class="total-kr">
                        <span class="fs-6 fw-semibold" style="color: var(--text-dark);">Total Bayar</span>
                        <span class="fs-4 fw-bold brand-font text-primary-theme"><?= rupiah($total_belanja) ?></span>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 empty-cart" style="color: var(--text-muted);">
                        <i class="bi bi-cart-x fs-1 d-block mb-3"></i>
                        Keranjang kamu masih kosong.
                        <div class="mt-3 d-flex justify-content-center gap-2 flex-wrap">
                            <a href="index.php#katalog" class="btn btn-premium btn-sm">Lihat Katalog</a>
                            <a href="riwayat.php" class="btn btn-outline-premium btn-sm">Lihat Riwayat Pesanan</a>
                        </div>
                    </div>
                <?php endif; ?>
                </div>
            </div>
        </div>

        <div>
            <div class="card-premium">
                <div class="card-header-form">
                    <h5 class="fw-bold mb-0 brand-font text-white"><i class="bi bi-clipboard-heart me-2"></i>Detail Pesanan</h5>
                    <small style="color: rgba(255,255,255,0.85);">Mohon lengkapi data untuk proses pesanan</small>
                </div>
                <div class="card-body p-4">
                    <div id="alertCheckout" class="alert alert-danger border-0 small d-none" role="alert"></div>

                    <form id="formCheckout" novalidate>
                        <div class="grup-form">
                            <h6><i class="bi bi-person-heart"></i>Data Pemesan</h6>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="nama">Nama Pemesan</label>
                                    <input type="text" id="nama" name="nama" class="form-control" placeholder="Masukkan nama lengkap" maxlength="100" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="catatan_penjual">Catatan untuk Penjual <span class="fw-normal" style="color: var(--text-muted);">(opsional)</span></label>
                                    <input type="text" id="catatan_penjual" name="catatan_penjual" class="form-control" maxlength="500" placeholder="Mis. dibungkus terpisah, pita warna tertentu">
                                </div>
                            </div>
                            <div class="form-text mb-3" style="font-size: .8rem;">Pilihan tiap produk (warna, ucapan, catatan) sudah tersimpan di daftar keranjang di atas.</div>
                        </div>

                        <div class="grup-form">
                            <h6><i class="bi bi-calendar-heart"></i>Jadwal &amp; Pengambilan</h6>
                            <div class="row g-3 mb-3">
                                <div class="col-6 col-md-3">
                                    <label class="form-label" for="tanggal">Tgl. Pengambilan</label>
                                    <input type="date" id="tanggal" name="tanggal" class="form-control" min="<?= date('Y-m-d') ?>" required>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label" for="jam">Jam Pengambilan</label>
                                    <input type="time" id="jam" name="jam" class="form-control" min="09:00" max="19:00" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="pengambilan">Metode Pengambilan</label>
                                    <select id="pengambilan" name="pengambilan" class="form-select" required>
                                        <option value="" disabled selected>-- Pilih Metode --</option>
                                        <option value="Ke Toko">Ambil Ke Toko</option>
                                        <option value="Gosend Dari Pembeli">Gosend (Dari Pembeli)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="info-box mb-3">
                                <strong>Note:</strong> Buka jam 09:00 - 19:00. Jam diisi perkiraan paling awal pesanan diambil (tidak bisa dimajukan mendadak). Untuk beberapa jenis bouquet, warna wrapping tidak bisa disesuaikan dan akan diinfokan admin via WhatsApp.
                            </div>
                        </div>

                        <div class="grup-form">
                            <h6><i class="bi bi-wallet2"></i>Pembayaran</h6>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="pembayaran">Metode Pembayaran</label>
                                    <select id="pembayaran" name="pembayaran" class="form-select" required>
                                        <option value="" disabled selected>-- Pilih Pembayaran --</option>
                                        <option value="QRIS">QRIS</option>
                                        <option value="BCA">Transfer BCA</option>
                                        <option value="Cash">Cash (Tunai)</option>
                                    </select>
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <div class="form-text m-0" style="font-size: 0.8rem; color: var(--primary);">
                                        <i class="bi bi-info-circle me-1"></i> Wajib DP/Full TF untuk pesanan di atas Rp 100.000<br>
                                        <i class="bi bi-info-circle me-1"></i> Custom bouquet wajib DP
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="ringkas-total">
                            <span class="fw-semibold">Total Bayar</span>
                            <span class="fs-5 fw-bold brand-font text-primary-theme"><?= rupiah($total_belanja) ?></span>
                        </div>

                        <button type="submit" id="btnKirim" class="btn btn-premium w-100 fs-6" <?= ($total_belanja == 0) ? 'disabled' : '' ?>>
                            <i class="bi bi-whatsapp me-2"></i>Kirim Order ke WhatsApp
                        </button>
                        <p class="text-center small mt-3 mb-0" style="color: var(--text-muted);">
                            Pesananmu akan tersimpan di <a href="riwayat.php" class="text-primary-theme fw-semibold text-decoration-none">Riwayat Pesanan</a> agar statusnya bisa dipantau.
                        </p>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
const form = document.getElementById('formCheckout');
const btn = document.getElementById('btnKirim');
const alertBox = document.getElementById('alertCheckout');
const labelAwal = btn.innerHTML;

function tampilkanError(pesan) {
    alertBox.textContent = pesan;
    alertBox.classList.remove('d-none');
    alertBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

form.addEventListener('submit', async function (ev) {
    ev.preventDefault();
    alertBox.classList.add('d-none');

    // Validasi bawaan browser (required, min/max jam & tanggal)
    if (!form.checkValidity()) { form.reportValidity(); return; }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan pesanan...';

    // Buka tab WhatsApp SEKARANG (masih di dalam aksi klik) supaya tidak diblokir pop-up blocker.
    // Alamat WhatsApp-nya baru diisi setelah pesanan berhasil tersimpan.
    const tabWA = window.open('', '_blank');

    try {
        const data = new FormData(form);
        data.append('aksi', 'checkout');
        const res = await fetch('keranjang.php', { method: 'POST', body: data });
        const hasil = await res.json();

        if (!hasil.ok) {
            if (tabWA) tabWA.close();
            tampilkanError(hasil.pesan || 'Pesanan belum bisa disimpan.');
            btn.disabled = false; btn.innerHTML = labelAwal;
            return;
        }

        if (tabWA) tabWA.location.href = hasil.wa_url;   // buka WhatsApp di tab baru
        window.location.href = 'riwayat.php?baru=' + encodeURIComponent(hasil.kode); // tab ini pindah ke Riwayat
    } catch (err) {
        if (tabWA) tabWA.close();
        tampilkanError('Terjadi gangguan koneksi. Silakan coba lagi.');
        btn.disabled = false; btn.innerHTML = labelAwal;
    }
});
</script>

</body>
</html>
