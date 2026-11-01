<?php
session_start();
require 'config.php';

// Jika sudah login, langsung arahkan ke admin.php
if (isset($_SESSION['admin_login'])) {
    header("Location: admin.php");
    exit;
}

$error = '';

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = MD5($_POST['password']); // Enkripsi password dengan MD5

    $query = "SELECT * FROM admin WHERE username='$username' AND password='$password'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) === 1) {
        $_SESSION['admin_login'] = true;
        header("Location: admin.php");
        exit;
    } else {
        $error = 'Username atau Password salah!';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Kami Florist</title>
    <link rel="icon" type="image/svg+xml" href="assets/favicon.svg">
    <link rel="alternate icon" href="assets/favicon.ico">
    <link rel="apple-touch-icon" href="assets/apple-touch-icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@500;600&family=Nunito+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/tema.css">
    <style>
        body { background: linear-gradient(160deg, var(--bg-cream), var(--bg-light) 60%); display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        .login-card { width: 100%; max-width: 410px; padding: 36px 32px; border-radius: var(--radius); box-shadow: 0 12px 40px rgba(55,59,68,0.09); background: white; }
        .login-logo { width: 62px; height: 62px; border-radius: 50%; background: var(--primary-soft); color: var(--primary); display: inline-flex; align-items: center; justify-content: center; font-size: 1.8rem; margin-bottom: 14px; }
    </style>
</head>
<body>

<div class="login-card">
    <div class="text-center mb-4">
        <div class="login-logo"><i class="bi bi-flower1"></i></div>
        <h3 class="brand-font fw-semibold mb-1">Login Admin</h3>
        <small style="color: var(--text-muted);">Kami Florist &middot; Panel Pengelolaan</small>
    </div>
    
    <?php if($error != ''): ?>
        <div class="alert alert-danger text-center p-2"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" required autofocus>
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" name="login" class="btn btn-premium w-100">Login</button>
    </form>
    <div class="text-center mt-3">
        <a href="index.php" class="text-decoration-none" style="color: var(--text-muted);"><i class="bi bi-arrow-left-short"></i>Kembali ke Katalog Web</a>
    </div>
</div>

</body>
</html>