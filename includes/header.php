<?php
/**
 * File     : includes/header.php
 * Card     : Core-03 Layout & Navbar
 * Tugas    : Header + navbar. Menu berubah sesuai status login (belum login: Masuk/Daftar, sudah login: Riwayat/Keluar).
 * PIC      : Syahrisham Rafif Thufail
 * Deadline : 1 Oktober 2026
 */

// Pastikan session sudah aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$base_url = $base_url ?? '';
$is_logged_in = isset($_SESSION['user_id']);
$username = $_SESSION['user']['username'] ?? 'Pengguna';
$role = $_SESSION['role'] ?? 'customer';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . " - Bioskop" : "Bioskop - Pesan Tiket Bioskop Online"; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .navbar-brand {
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .main-content {
            flex: 1;
        }
    </style>
</head>
<body>

<!-- Navbar Utama -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand text-warning" href="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8') ?>index.php">
            <i class="bi bi-film me-2"></i>Cinema XXI / Bioskop
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-nav-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : '' ?>" href="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8') ?>index.php">
                        <i class="bi bi-house-door me-1"></i>Beranda
                    </a>
                </li>
                <?php if ($is_logged_in): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'history.php') ? 'active' : '' ?>" href="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8') ?>history.php">
                            <i class="bi bi-ticket-perforated me-1"></i>Riwayat Pesanan
                        </a>
                    </li>
                    <?php if ($role === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link text-warning" href="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8') ?>admin/genre.php">
                                <i class="bi bi-speedometer2 me-1"></i>Panel Admin
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav ms-auto align-items-center">
                <?php if ($is_logged_in): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-light" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i>
                            <strong><?= htmlspecialchars($username) ?></strong>
                            <span class="badge bg-<?= $role === 'admin' ? 'danger' : 'info' ?> ms-1 text-uppercase" style="font-size: 0.7rem;">
                                <?= htmlspecialchars($role) ?>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                            <li><h6 class="dropdown-header">Masuk sebagai: <?= htmlspecialchars($username) ?></h6></li>
                            <?php if ($role === 'admin'): ?>
                                <li><a class="dropdown-item" href="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8') ?>admin/genre.php"><i class="bi bi-gear me-2"></i>Kelola Bioskop (Admin)</a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li>
                                <a class="dropdown-item text-danger" href="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8') ?>logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>Keluar (Logout)
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8') ?>login.php">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Masuk
                        </a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-warning btn-sm px-3" href="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8') ?>register.php">
                            <i class="bi bi-person-plus me-1"></i>Daftar
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="main-content">
