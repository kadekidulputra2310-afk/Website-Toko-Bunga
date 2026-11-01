<?php
session_start();
require 'config.php';
require 'helpers.php';

pastikan_skema($conn); // pastikan kolom kustomisasi ada di database lama

$jumlah_keranjang = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

// ---------------------------------------------------------
// Pesanan milik perangkat ini (dikenali lewat cookie, tanpa login)
// ---------------------------------------------------------
$pesanan_saya = [];
$token = token_pemesan();
if ($token) {
    $st = mysqli_prepare($conn, "SELECT * FROM pesanan WHERE token_pemesan = ? ORDER BY id_pesanan DESC LIMIT 50");
    mysqli_stmt_bind_param($st, 's', $token);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);
    while ($r = mysqli_fetch_assoc($rs)) $pesanan_saya[] = $r;
    mysqli_stmt_close($st);
}

// ---------------------------------------------------------
// Lacak pesanan dengan kode (bisa dari perangkat lain)
// ---------------------------------------------------------
$kode_cari = strtoupper(potong($_GET['kode'] ?? '', 20));
$hasil_cari = null;
if ($kode_cari !== '') {
    $st = mysqli_prepare($conn, "SELECT * FROM pesanan WHERE kode_pesanan = ? LIMIT 1");
    mysqli_stmt_bind_param($st, 's', $kode_cari);
    mysqli_stmt_execute($st);
    $hasil_cari = mysqli_fetch_assoc(mysqli_stmt_get_result($st)) ?: false;
    mysqli_stmt_close($st);
}

// Pesanan yang baru saja dibuat (dikirim dari keranjang.php)
$pesanan_baru = null;
if (!empty($_GET['baru'])) {
    foreach ($pesanan_saya as $p) {
        if ($p['kode_pesanan'] === $_GET['baru']) { $pesanan_baru = $p; break; }
    }
}

// Ambil semua item sekaligus
$semua_id = array_column($pesanan_saya, 'id_pesanan');
if ($hasil_cari) $semua_id[] = $hasil_cari['id_pesanan'];
$detail = ambil_detail_pesanan($conn, $semua_id);

// ---------------------------------------------------------
// Komponen kartu pesanan
// ---------------------------------------------------------
function kartu_pesanan(array $p, array $items, $tampilkan_wa = true) {
    $info = info_status($p['status']);
    $idx  = array_search($p['status'], ALUR_STATUS, true);
    ob_start(); ?>
    <div class="card-premium kartu-pesanan mb-4">
        <div class="p-4 pb-3 d-flex flex-wrap justify-content-between align-items-start gap-2">
            <div>
                <div class="small text-uppercase fw-bold" style="color: var(--text-muted); letter-spacing: .6px;">Kode Pesanan</div>
                <h5 class="brand-font fw-bold mb-0" style="letter-spacing:.5px;"><?= e($p['kode_pesanan']) ?></h5>
                <small style="color: var(--text-muted);">Dipesan <?= e(tgl_indo($p['dibuat_pada'])) ?></small>
            </div>
            <span class="status-pill s-<?= e($p['status']) ?>"><i class="bi <?= e($info['ikon']) ?>"></i><?= e($p['status']) ?></span>
        </div>

        <div class="px-4">
            <?php if ($p['status'] === 'Dibatalkan'): ?>
                <div class="alert alert-danger border-0 small mb-3"><i class="bi bi-x-circle-fill me-2"></i><?= e($info['teks']) ?></div>
            <?php else: ?>
                <div class="stepper <?= $p['status'] === 'Selesai' ? 'selesai' : '' ?>">
                    <?php foreach (ALUR_STATUS as $i => $nama_step):
                        $kelas = $i < $idx ? 'done' : ($i === $idx ? 'done current' : '');
                        $ikon  = info_status($nama_step)['ikon'];
                    ?>
                    <div class="step <?= $kelas ?>">
                        <div class="dot"><i class="bi <?= e($ikon) ?>"></i></div>
                        <div class="label"><?= e($nama_step) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <p class="small text-center mt-2 mb-3" style="color: var(--text-muted);">
                    <?= e($info['teks']) ?>
                    <span class="d-block" style="opacity:.8;">Diperbarui <?= e(tgl_indo($p['diperbarui_pada'])) ?></span>
                </p>
            <?php endif; ?>
        </div>

        <div class="px-4 pb-2">
            <div class="isi-pesanan">
                <?php foreach ($items as $it): $kustom = kustom_item($it); ?>
                <div class="py-1">
                    <div class="d-flex justify-content-between small">
                        <span><?= e($it['nama_produk']) ?> <span style="color: var(--text-muted);">× <?= (int)$it['qty'] ?></span></span>
                        <span class="fw-semibold"><?= rupiah($it['harga'] * $it['qty']) ?></span>
                    </div>
                    <?php if ($kustom): ?>
                    <div class="kustom-riwayat">
                        <?php foreach ($kustom as $label => $isi): ?>
                            <span><i class="bi <?= e(ikon_kustom($label)) ?>"></i><b><?= e($label) ?>:</b> <?= $label === 'Foto referensi' ? foto_html($isi, 72) : e($isi) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <div class="d-flex justify-content-between pt-2 mt-1 border-top">
                    <span class="fw-bold">Total</span>
                    <span class="fw-bold text-primary-theme"><?= rupiah($p['total']) ?></span>
                </div>
            </div>
        </div>

        <div class="px-4 pb-4 pt-2">
            <button class="btn btn-link p-0 small text-decoration-none text-primary-theme fw-semibold" type="button"
                    data-bs-toggle="collapse" data-bs-target="#detail<?= (int)$p['id_pesanan'] ?>">
                <i class="bi bi-chevron-down me-1"></i>Detail pengambilan
            </button>
            <div class="collapse" id="detail<?= (int)$p['id_pesanan'] ?>">
                <dl class="row small mt-3 mb-0">
                    <dt class="col-5 col-sm-4">Atas nama</dt><dd class="col-7 col-sm-8"><?= e($p['nama_pemesan']) ?></dd>
                    <dt class="col-5 col-sm-4">Diambil</dt><dd class="col-7 col-sm-8"><?= e(tgl_indo($p['tanggal_ambil'], false)) ?>, <?= e(substr($p['jam_ambil'], 0, 5)) ?></dd>
                    <dt class="col-5 col-sm-4">Pengambilan</dt><dd class="col-7 col-sm-8"><?= e($p['metode_ambil']) ?></dd>
                    <dt class="col-5 col-sm-4">Pembayaran</dt><dd class="col-7 col-sm-8"><?= e($p['metode_bayar']) ?></dd>
                    <?php if (!empty($p['tema_warna'])): ?><dt class="col-5 col-sm-4">Tema warna</dt><dd class="col-7 col-sm-8"><?= e($p['tema_warna']) ?></dd><?php endif; ?>
                    <?php if (!empty($p['warna_wrapping'])): ?><dt class="col-5 col-sm-4">Wrapping</dt><dd class="col-7 col-sm-8"><?= e($p['warna_wrapping']) ?></dd><?php endif; ?>
                    <dt class="col-5 col-sm-4">Catatan penjual</dt><dd class="col-7 col-sm-8"><?= nl2br(e(($p['catatan_penjual'] ?? '') ?: '-')) ?></dd>
                    <?php if (!empty($p['ucapan'])): ?><dt class="col-5 col-sm-4">Kartu ucapan</dt><dd class="col-7 col-sm-8"><?= nl2br(e($p['ucapan'])) ?></dd><?php endif; ?>
                </dl>
            </div>

            <?php if ($tampilkan_wa && in_array($p['status'], ['Diterima', 'Diproses'], true)): ?>
            <a href="<?= e(link_wa($p, $items)) ?>" target="_blank" rel="noopener" class="btn btn-outline-premium btn-sm w-100 mt-3">
                <i class="bi bi-whatsapp me-2"></i>Kirim ulang detail ke WhatsApp admin
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan - Kami Florist</title>
    <link rel="icon" type="image/svg+xml" href="assets/favicon.svg">
    <link rel="alternate icon" href="assets/favicon.ico">
    <link rel="apple-touch-icon" href="assets/apple-touch-icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,500;0,600;1,500&family=Nunito+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/tema.css">

    <style>
        .kartu-pesanan { transition: box-shadow .3s; }
        .kartu-pesanan:hover { box-shadow: var(--shadow-lift); }
        .isi-pesanan { background: var(--bg-light); border-radius: 12px; padding: 12px 16px; }
        dt { font-weight: 600; color: var(--text-muted); }
        .kustom-riwayat { display: flex; flex-direction: column; gap: 2px; font-size: .78rem; color: var(--text-muted); padding: 2px 0 6px 2px; }
        .kustom-riwayat i { color: var(--primary); margin-right: 5px; }
        .kustom-riwayat b { font-weight: 600; color: var(--text-dark); }

        /* Stepper status */
        .stepper { display: flex; margin: 6px 0 4px; }
        .step { flex: 1; text-align: center; position: relative; }
        .step::before { content: ""; position: absolute; top: 20px; left: -50%; width: 100%; height: 3px; background: #ebe7e2; z-index: 1; }
        .step:first-child::before { display: none; }
        .step .dot { position: relative; z-index: 2; width: 42px; height: 42px; border-radius: 50%; background: #ebe7e2; color: #b5afa8; display: inline-flex; align-items: center; justify-content: center; font-size: 1.1rem; transition: all .3s; }
        .step .label { font-size: .78rem; font-weight: 700; margin-top: 8px; color: #b5afa8; }
        .step.done .dot { background: var(--primary); color: #fff; }
        .step.done .label { color: var(--text-dark); }
        .step.done::before { background: var(--primary); }
        .step.current .dot { box-shadow: 0 0 0 5px var(--primary-soft); }
        .stepper.selesai .step.done .dot { background: var(--sage); }
        .stepper.selesai .step.done::before { background: var(--sage); }
        .stepper.selesai .step.current .dot { box-shadow: 0 0 0 5px var(--sage-soft); }

        .banner-sukses { background: linear-gradient(135deg, var(--sage-soft), #f4f9f4); border: 1px solid #d5e5d8; border-radius: var(--radius); }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand brand-font" href="index.php"><i class="bi bi-arrow-left-short me-1"></i> Kembali ke Katalog</a>
        <a href="keranjang.php" class="btn btn-outline-premium btn-sm d-inline-flex align-items-center">
            <i class="bi bi-cart3 me-2"></i>Keranjang
            <?php if ($jumlah_keranjang > 0): ?><span class="badge-tema ms-2"><?= (int)$jumlah_keranjang ?></span><?php endif; ?>
        </a>
    </div>
</nav>

<div class="container py-5" style="max-width: 860px;">

    <div class="text-center mb-4">
        <div class="section-ornament"><i class="bi bi-flower1"></i></div>
        <h2 class="section-title mb-1">Riwayat Pesanan</h2>
        <p class="section-sub mb-0" style="margin-bottom:0;">Pantau perkembangan pesananmu mulai dari diterima, diproses, hingga selesai.</p>
    </div>

    <?php if ($pesanan_baru): ?>
    <div class="banner-sukses p-4 mb-4">
        <div class="d-flex align-items-start gap-3">
            <i class="bi bi-check-circle-fill fs-2" style="color: var(--sage);"></i>
            <div class="flex-grow-1">
                <h5 class="brand-font fw-bold mb-1">Pesanan berhasil dibuat!</h5>
                <p class="small mb-2" style="color: var(--text-muted);">
                    Kode pesananmu <strong><?= e($pesanan_baru['kode_pesanan']) ?></strong>. Keranjang sudah dikosongkan dan pesanan tersimpan di bawah ini.
                    Kalau WhatsApp belum terbuka, tekan tombol berikut agar admin menerima detail pesananmu.
                </p>
                <a href="<?= e(link_wa($pesanan_baru, $detail[$pesanan_baru['id_pesanan']] ?? [])) ?>" target="_blank" rel="noopener" class="btn btn-premium btn-sm">
                    <i class="bi bi-whatsapp me-2"></i>Buka WhatsApp Admin
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card-premium p-4 mb-4">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-sm">
                <label class="form-label mb-1" for="kode">Lacak dengan kode pesanan</label>
                <input type="text" id="kode" name="kode" class="form-control" placeholder="Contoh: KF-261026-7QH3M" value="<?= e($kode_cari) ?>" maxlength="20" style="text-transform: uppercase;">
            </div>
            <div class="col-sm-auto">
                <button class="btn btn-premium w-100" type="submit"><i class="bi bi-search me-2"></i>Lacak</button>
            </div>
        </form>

        <?php if ($kode_cari !== '' && $hasil_cari === false): ?>
            <div class="alert alert-warning border-0 small mt-3 mb-0"><i class="bi bi-exclamation-triangle-fill me-2"></i>Kode <strong><?= e($kode_cari) ?></strong> tidak ditemukan. Periksa lagi penulisannya ya.</div>
        <?php endif; ?>
    </div>

    <?php if ($hasil_cari): ?>
        <h6 class="fw-bold mb-3" style="color: var(--text-muted);"><i class="bi bi-search me-2"></i>Hasil pelacakan</h6>
        <?= kartu_pesanan($hasil_cari, $detail[$hasil_cari['id_pesanan']] ?? [], false) ?>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-3 mt-5">
        <h5 class="brand-font fw-bold mb-0">Pesanan dari perangkat ini</h5>
        <a href="riwayat.php" class="btn btn-sm btn-outline-premium"><i class="bi bi-arrow-clockwise me-1"></i>Segarkan</a>
    </div>

    <?php if (empty($pesanan_saya)): ?>
        <div class="card-premium text-center py-5 px-3">
            <i class="bi bi-clock-history fs-1 d-block mb-3" style="color: var(--primary); opacity:.55;"></i>
            <h6 class="fw-bold">Belum ada pesanan</h6>
            <p class="small mb-3" style="color: var(--text-muted);">Pesanan yang sudah kamu kirim lewat WhatsApp akan muncul di sini.</p>
            <div><a href="index.php#katalog" class="btn btn-premium btn-sm">Lihat Katalog</a></div>
        </div>
    <?php else: ?>
        <?php foreach ($pesanan_saya as $p): ?>
            <?= kartu_pesanan($p, $detail[$p['id_pesanan']] ?? []) ?>
        <?php endforeach; ?>
        <p class="small text-center" style="color: var(--text-muted);">Riwayat tersimpan di browser ini. Di perangkat lain, gunakan kolom lacak di atas dengan kode pesananmu.</p>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Rapikan URL: hapus ?baru= supaya banner tidak muncul lagi saat halaman di-refresh
if (window.history.replaceState) {
    const url = new URL(window.location);
    if (url.searchParams.has('baru')) {
        url.searchParams.delete('baru');
        window.history.replaceState(null, '', url.toString());
    }
}
</script>
</body>
</html>
