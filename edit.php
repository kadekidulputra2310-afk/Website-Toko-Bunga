<?php
session_start();
require 'config.php';

// Cek apakah sudah login
if (!isset($_SESSION['admin_login'])) {
    header("Location: login.php");
    exit;
}

// Cek apakah ada ID yang dikirim
if (!isset($_GET['id'])) {
    header("Location: admin.php");
    exit;
}

$id_produk = $_GET['id'];

// Ambil data produk berdasarkan ID
$query_produk = mysqli_query($conn, "SELECT * FROM produk WHERE id_produk='$id_produk'");
$data = mysqli_fetch_assoc($query_produk);

// Jika data tidak ditemukan di database
if (!$data) {
    echo "<script>alert('Data tidak ditemukan!'); window.location='admin.php';</script>";
    exit;
}

// Ambil data kategori untuk pilihan di form
$kategori = mysqli_query($conn, "SELECT * FROM kategori");

// Logika ketika tombol update ditekan
if (isset($_POST['update'])) {
    $id_kategori = $_POST['id_kategori'];
    $nama = $_POST['nama_produk'];
    $harga = $_POST['harga'];
    $stok = $_POST['stok'];
    $deskripsi = $_POST['deskripsi'];

    // Cek apakah user mengupload foto baru
    if ($_FILES['foto']['name'] != '') {
        $foto = $_FILES['foto']['name'];
        $tmp = $_FILES['foto']['tmp_name'];
        $foto_baru = time() . '_' . $foto;
        $path = "uploads/" . $foto_baru;

        if (move_uploaded_file($tmp, $path)) {
            // Hapus foto lama dari folder agar tidak menumpuk
            if (file_exists("uploads/" . $data['foto'])) {
                unlink("uploads/" . $data['foto']);
            }
            
            // Query update dengan foto baru
            $query = "UPDATE produk SET id_kategori='$id_kategori', nama_produk='$nama', deskripsi='$deskripsi', harga='$harga', foto='$foto_baru', stok='$stok' WHERE id_produk='$id_produk'";
            mysqli_query($conn, $query);
            echo "<script>alert('Produk beserta foto berhasil diupdate!'); window.location='admin.php';</script>";
        } else {
            echo "<script>alert('Gagal mengupload foto baru!');</script>";
        }
    } else {
        // Query update TANPA mengganti foto
        $query = "UPDATE produk SET id_kategori='$id_kategori', nama_produk='$nama', deskripsi='$deskripsi', harga='$harga', stok='$stok' WHERE id_produk='$id_produk'";
        mysqli_query($conn, $query);
        echo "<script>alert('Produk berhasil diupdate!'); window.location='admin.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Produk - Kami Florist</title>
    <link rel="icon" type="image/svg+xml" href="assets/favicon.svg">
    <link rel="alternate icon" href="assets/favicon.ico">
    <link rel="apple-touch-icon" href="assets/apple-touch-icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,500;0,600;1,500&family=Nunito+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/tema.css">
    <style>
        .btn-batal { background: #f1ece6; color: var(--text-dark); border: 0; padding: 12px 26px; font-weight: 600; border-radius: 50px; text-decoration: none; transition: all .2s; }
        .btn-batal:hover { background: #e6dfd7; color: var(--text-dark); }
        .foto-sekarang { width: 120px; height: 120px; object-fit: cover; border-radius: 14px; border: 1px solid var(--bg-cream); box-shadow: var(--shadow-soft); }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand brand-font" href="admin.php#produk-panel"><i class="bi bi-arrow-left-short me-1"></i> Kembali ke Panel Admin</a>
        <span class="small d-none d-sm-inline" style="color: var(--text-muted);"><i class="bi bi-flower1 me-1 text-primary-theme"></i>Kami Florist</span>
    </div>
</nav>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card-premium">
                <div class="card-header-form">
                    <h5 class="fw-bold mb-0 brand-font text-white"><i class="bi bi-pencil-square me-2"></i>Edit Data Produk</h5>
                    <small style="color: rgba(255,255,255,0.85);">Perbarui informasi bunga yang tampil di katalog</small>
                </div>
                <div class="card-body p-4">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Bunga / Buket</label>
                                <input type="text" name="nama_produk" class="form-control" value="<?= htmlspecialchars($data['nama_produk']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kategori</label>
                                <select name="id_kategori" class="form-select" required>
                                    <?php while($k = mysqli_fetch_assoc($kategori)): ?>
                                        <option value="<?= $k['id_kategori'] ?>" <?= ($data['id_kategori'] == $k['id_kategori']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($k['nama_kategori']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Harga (Rp)</label>
                                <input type="number" name="harga" class="form-control" value="<?= (int)$data['harga'] ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Stok</label>
                                <input type="number" name="stok" class="form-control" value="<?= (int)$data['stok'] ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Deskripsi Singkat</label>
                            <textarea name="deskripsi" class="form-control" rows="3" required><?= htmlspecialchars($data['deskripsi']) ?></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label d-block">Foto Produk Saat Ini</label>
                            <img src="uploads/<?= htmlspecialchars($data['foto']) ?>" alt="Foto" class="foto-sekarang mb-3">
                            <label class="form-label d-block">Ganti Foto <span class="fw-normal" style="color: var(--text-muted);">(kosongkan jika tidak ingin ganti)</span></label>
                            <input type="file" name="foto" class="form-control" accept="image/*">
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="admin.php#produk-panel" class="btn-batal">Batal</a>
                            <button type="submit" name="update" class="btn btn-premium px-4"><i class="bi bi-check2-circle me-2"></i>Update Produk</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
