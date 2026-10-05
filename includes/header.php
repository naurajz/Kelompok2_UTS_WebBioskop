<?php
// Pastikan session sudah aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$base_url     = $base_url ?? '';
$body_class   = $body_class ?? '';
$page_links   = $page_links ?? [];
$is_logged_in = isset($_SESSION['user_id']);
$username     = $_SESSION['user']['username'] ?? 'Pengguna';
$role         = $_SESSION['role'] ?? 'customer';

$current_page = basename($_SERVER['PHP_SELF']);
$in_admin     = strpos(str_replace('\\', '/', $_SERVER['PHP_SELF']), '/admin/') !== false;
$base         = htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . " - HIMTI MOVIE" : "HIMTI MOVIE - Pesan Tiket Bioskop Online"; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- CSS bersama (dimuat paling akhir supaya bisa menimpa Bootstrap) -->
    <link rel="stylesheet" href="<?= $base ?>assets/css/style.css">
</head>
<body class="<?= htmlspecialchars($body_class) ?>">

<!-- Navbar Utama -->
<nav class="navbar navbar-expand-lg navbar-dark site-navbar sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="<?= $base ?>index.php">
            <i class="bi bi-film me-2"></i>HIMTIX <span> MOVIE<span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'index.php') ? 'active' : '' ?>" href="<?= $base ?>index.php">
                        <i class="bi bi-house-door me-1"></i>Beranda
                    </a>
                </li>

                <?php foreach ($page_links as $href => $label): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= htmlspecialchars($href) ?>"><?= htmlspecialchars($label) ?></a>
                    </li>
                <?php endforeach; ?>

                <?php if ($is_logged_in): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'history.php') ? 'active' : '' ?>" href="<?= $base ?>history.php">
                            <i class="bi bi-ticket-perforated me-1"></i>Riwayat Pesanan
                        </a>
                    </li>
                    <?php if ($role === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $in_admin ? 'active' : '' ?>" href="<?= $base ?>admin/dashboard_admin.php">
                                <i class="bi bi-speedometer2 me-1"></i>Panel Admin
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav ms-auto align-items-center">
                <?php if ($is_logged_in): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link user-dropdown dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i>
                            <strong><?= htmlspecialchars($username) ?></strong>
                            <span class="badge user-role-badge ms-1 text-uppercase">
                                <?= htmlspecialchars($role) ?>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                            <li><h6 class="dropdown-header">Masuk sebagai: <?= htmlspecialchars($username) ?></h6></li>
                            <?php if ($role === 'admin'): ?>
                                <li><a class="dropdown-item" href="<?= $base ?>admin/dashboard_admin.php"><i class="bi bi-gear me-2"></i>Kelola Bioskop (Admin)</a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li>
                                <a class="dropdown-item text-danger" href="<?= $base ?>logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>Keluar (Logout)
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link btn-login px-3 py-2" href="<?= $base ?>login.php">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Masuk
                        </a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-sm btn-register px-3" href="<?= $base ?>register.php">
                            <i class="bi bi-person-plus me-1"></i>Daftar
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="main-content">