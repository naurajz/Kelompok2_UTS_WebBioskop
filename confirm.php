<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ambil order_id dari URL
$orderId = (int)($_GET['order_id'] ?? 0);
if ($orderId <= 0) {
    exit;
}

require_once __DIR__ . '/classes/Order.php';

// ambil detail pesanan dari database
$orderModel = new Order();
$order      = $orderModel->getOrderDetail($orderId);

// kalau order gak ada, stop
if (!$order) {
    exit;
}

// pastiin yang buka halaman ini adalah pemilik pesanannya
// admin boleh akses semua, tapi user biasa cuma boleh lihat punyanya sendiri
$currentUserId   = $_SESSION['user_id'] ?? null;
$currentUserRole = $_SESSION['role'] ?? 'customer';
if ($currentUserId && isset($order['user_id']) && $currentUserRole !== 'admin') {
    if ((int)$order['user_id'] !== (int)$currentUserId) {
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Konfirmasi Pesanan – HIMTI MOVIE</title>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background: #080808;
    color: white;
    font-family: Arial, Helvetica, sans-serif;
}

a {
    text-decoration: none;
    color: inherit;
}

/* navbar sama persis kayak halaman lain */
.navbar {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 72px;
    background: rgba(8,8,8,0.96);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 55px;
    z-index: 9999;
    border-bottom: 1px solid #222;
}

.logo {
    font-size: 25px;
    font-weight: 900;
}

.logo span {
    color: #e50914;
}

.nav-menu {
    display: flex;
    align-items: center;
    gap: 32px;
}

.nav-menu a {
    color: #ddd;
    font-size: 14px;
}

.nav-menu a:hover {
    color: #e50914;
}

/* konten ditaruh di tengah, max lebar 560px */
.page-wrap {
    margin-top: 72px;
    padding: 60px 7%;
    min-height: calc(100vh - 72px);
    display: flex;
    align-items: flex-start;
    justify-content: center;
}

.confirm-box {
    width: 100%;
    max-width: 560px;
}

.page-title {
    font-size: 32px;
    font-weight: 900;
    margin-bottom: 30px;
}

.page-title span {
    color: #e50914;
}

/* ikon centang hijau di atas */
.success-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: #122212;
    border: 2px solid #4caf50;
    color: #4caf50;
    font-size: 26px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
}

.success-heading {
    text-align: center;
    font-size: 22px;
    font-weight: 700;
    margin-bottom: 6px;
}

.success-sub {
    text-align: center;
    color: #888;
    font-size: 14px;
    margin-bottom: 32px;
}

/* kotak kode booking dengan border putus merah */
.booking-card {
    background: #0e0e0e;
    border: 1px dashed #e50914;
    border-radius: 10px;
    text-align: center;
    padding: 22px;
    margin-bottom: 24px;
}

.booking-label {
    color: #888;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 2px;
    margin-bottom: 10px;
}

/* kode booking pakai font monospace biar keliatan kaya kode tiket beneran */
.booking-code {
    font-family: 'Courier New', Courier, monospace;
    font-size: 2.2rem;
    font-weight: 900;
    color: #e50914;
    letter-spacing: 5px;
}

.btn-copy {
    margin-top: 12px;
    background: transparent;
    border: 1px solid #333;
    color: #888;
    padding: 5px 16px;
    border-radius: 20px;
    font-size: 12px;
    cursor: pointer;
    font-family: Arial, Helvetica, sans-serif;
    transition: all .2s;
}

.btn-copy:hover {
    border-color: #888;
    color: #ddd;
}

/* kartu rincian pesanan */
.summary-card {
    background: #121212;
    border: 1px solid #252525;
    border-radius: 12px;
    padding: 22px;
    margin-bottom: 24px;
}

.summary-card-title {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #e50914;
    font-weight: 700;
    margin-bottom: 16px;
}

/* tiap baris rincian pake flex buat rata kanan-kiri */
.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #1c1c1c;
    font-size: 14px;
}

.summary-row:last-child {
    border-bottom: none;
}

.summary-lbl { color: #888; }
.summary-val { font-weight: 600; }
.summary-total { color: #e50914; font-size: 16px; font-weight: 700; }

/* tombol aksi */
.btn-primary {
    display: block;
    width: 100%;
    background: #e50914;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 13px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    font-family: Arial, Helvetica, sans-serif;
    text-align: center;
    margin-bottom: 12px;
    transition: background .2s;
}

.btn-primary:hover {
    background: #c1070f;
    color: #fff;
}

/* dua tombol sekunder berdampingan */
.btn-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.btn-sec {
    display: block;
    background: #181818;
    border: 1px solid #333;
    border-radius: 8px;
    color: #ccc;
    padding: 11px;
    font-size: 14px;
    font-weight: 600;
    text-align: center;
    transition: all .2s;
}

.btn-sec:hover {
    border-color: #e50914;
    color: #fff;
}

@media (max-width: 600px) {
    .navbar { padding: 0 20px; }
    .page-wrap { padding: 50px 5%; }
    .booking-code { font-size: 1.6rem; letter-spacing: 3px; }
    .btn-row { grid-template-columns: 1fr; }
}

</style>
</head>
<body>

<nav class="navbar">
    <div class="logo">
        HIMTI
        <span>MOVIE</span>
    </div>
    <div class="nav-menu">
        <a href="index.php">Home</a>
        <a href="index.php#movies">Movies</a>
        <a href="history.php">Pesanan Saya</a>
        <a href="logout.php" style="background:#e50914;padding:10px 20px;border-radius:7px;color:white;">Keluar</a>
    </div>
</nav>

<div class="page-wrap">
    <div class="confirm-box">

        <h2 class="page-title">Konfirmasi <span>Pesanan</span></h2>

        <!-- notifikasi berhasil -->
        <div class="success-icon">&#10003;</div>
        <div class="success-heading">Pesanan Berhasil!</div>
        <div class="success-sub">Tiket bioskop kamu sudah berhasil dipesan.</div>

        <!-- kode booking yang bisa disalin -->
        <div class="booking-card">
            <div class="booking-label">Kode Booking</div>
            <div class="booking-code" id="bookingCode">
                <?= htmlspecialchars($order['booking_code'] ?? '-') ?>
            </div>
            <button type="button" class="btn-copy" onclick="salinKode()">Salin Kode</button>
        </div>

        <!-- ringkasan detail pesanan -->
        <div class="summary-card">
            <div class="summary-card-title">Rincian Pesanan</div>

            <div class="summary-row">
                <span class="summary-lbl">Film</span>
                <span class="summary-val"><?= htmlspecialchars($order['movie_title'] ?? '-') ?></span>
            </div>
            <div class="summary-row">
                <span class="summary-lbl">Studio</span>
                <span class="summary-val"><?= htmlspecialchars($order['studio_name'] ?? '-') ?></span>
            </div>
            <div class="summary-row">
                <span class="summary-lbl">Jadwal</span>
                <span class="summary-val">
                    <?= !empty($order['show_date']) ? date('d M Y', strtotime($order['show_date'])) : '-' ?>
                    &bull;
                    <?= !empty($order['show_time']) ? date('H:i', strtotime($order['show_time'])) . ' WIB' : '-' ?>
                </span>
            </div>
            <div class="summary-row">
                <span class="summary-lbl">Jumlah Tiket</span>
                <span class="summary-val"><?= (int)($order['total_tickets'] ?? 1) ?> Lembar</span>
            </div>
            <div class="summary-row">
                <span class="summary-lbl">Total Bayar</span>
                <span class="summary-val summary-total">
                    Rp <?= number_format((float)($order['total_price'] ?? 0), 0, ',', '.') ?>
                </span>
            </div>
        </div>

        <!-- tombol navigasi setelah selesai -->
        <a href="ticket.php?order_id=<?= $order['order_id'] ?>" class="btn-primary">
            Lihat E-Tiket
        </a>
        <div class="btn-row">
            <a href="history.php" class="btn-sec">Riwayat Pesanan</a>
            <a href="index.php" class="btn-sec">Beranda</a>
        </div>

    </div>
</div>

<script>
    // fungsi salin kode booking ke clipboard
    function salinKode() {
        const kode = document.getElementById('bookingCode').innerText.trim();
        navigator.clipboard.writeText(kode).then(function() {
            alert('Kode booking disalin: ' + kode);
        }).catch(function() {
            // fallback kalau clipboard API gak jalan
            alert('Kode booking: ' + kode);
        });
    }
</script>

</body>
</html>
