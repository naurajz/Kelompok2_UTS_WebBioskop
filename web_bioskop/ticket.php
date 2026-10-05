<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/classes/Ticket.php';

// ambil order_id dari URL
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// kalau order_id gak ada, coba dari parameter 'code' (jaga-jaga)
if ($orderId <= 0 && isset($_GET['code'])) {
    $orderId = (int)preg_replace('/[^0-9]/', '', $_GET['code']);
}

if ($orderId <= 0) {
    exit;
}

// ambil data tiket dari database, pakai try-catch biar gak error fatal
try {
    $ticketModel = new Ticket();
    $ticketData  = $ticketModel->getOrderTicketDetails($orderId);
} catch (Exception $e) {
    $ticketData = null;
}

if (!$ticketData) {
    exit;
}

// cek apakah user yang buka adalah pemilik tiket ini
// admin bisa akses semua, user biasa cuma punyanya sendiri
$sessionUserId = $_SESSION['user_id'] ?? null;
$sessionRole   = $_SESSION['role'] ?? 'customer';
if ($sessionUserId && isset($ticketData['user_id']) && $sessionRole !== 'admin') {
    if ((int)$sessionUserId !== (int)$ticketData['user_id']) {
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>E-Ticket – HIMTI MOVIE</title>

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


.page-wrap {
    margin-top: 72px;
    padding: 60px 7%;
    min-height: calc(100vh - 72px);
    display: flex;
    flex-direction: column;
    align-items: center;
}

.ticket-wrap {
    width: 100%;
    max-width: 600px;
}


.nav-area {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 28px;
}

.back-link {
    color: #888;
    font-size: 14px;
    transition: color .2s;
}

.back-link:hover {
    color: #e50914;
}


.btn-cetak {
    background: #1a1a1a;
    border: 1px solid #333;
    color: #555;
    padding: 8px 16px;
    border-radius: 7px;
    font-size: 13px;
    cursor: not-allowed;
    font-family: Arial, Helvetica, sans-serif;
}


.ticket-card {
    background: #121212;
    border: 1px solid #252525;
    border-radius: 14px;
    overflow: hidden;
}


.ticket-header {
    background: #0e0e0e;
    border-bottom: 3px solid #e50914;
    padding: 18px 28px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.ticket-brand {
    font-size: 18px;
    font-weight: 900;
    letter-spacing: .5px;
}

.ticket-brand span {
    color: #e50914;
}


.ticket-badge {
    background: #1c3520;
    color: #4caf50;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    padding: 4px 12px;
    border-radius: 4px;
    border: 1px solid #2a5030;
}

.ticket-body {
    padding: 28px;
}


.booking-box {
    background: #0a0a0a;
    border: 1px dashed #e50914;
    border-radius: 10px;
    text-align: center;
    padding: 20px;
    margin-bottom: 28px;
}

.booking-label {
    color: #888;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 2px;
    margin-bottom: 10px;
}


.booking-code {
    font-family: 'Courier New', Courier, monospace;
    font-size: 2.4rem;
    font-weight: 900;
    color: #e50914;
    letter-spacing: 6px;
}


.detail-table {
    width: 100%;
    border-collapse: collapse;
}

.detail-table tr td {
    padding: 11px 4px;
    font-size: 14px;
    border-bottom: 1px solid #1c1c1c;
}

.detail-table tr:last-child td {
    border-bottom: none;
}

.detail-table td.lbl {
    color: #888;
    width: 42%;
}

.detail-table td.val {
    font-weight: 600;
}

.detail-table td.val-red {
    font-weight: 700;
    color: #e50914;
    font-size: 16px;
}


.ticket-divider {
    position: relative;
    height: 28px;
    margin: 0;
}


.ticket-divider::before,
.ticket-divider::after {
    content: '';
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 18px;
    height: 18px;
    background: #080808;
    border-radius: 50%;
    z-index: 2;
}

.ticket-divider::before { left: -9px; }
.ticket-divider::after  { right: -9px; }

.ticket-divider-line {
    position: absolute;
    top: 50%;
    left: 10px;
    right: 10px;
    border-top: 2px dashed #2a2a2a;
}


.ticket-footer {
    background: #0a0a0a;
    padding: 14px 28px;
    font-size: 12px;
    color: #555;
    text-align: center;
    border-top: 1px solid #1e1e1e;
}

@media (max-width: 600px) {
    .navbar { padding: 0 20px; }
    .page-wrap { padding: 50px 5%; }
    .booking-code { font-size: 1.7rem; letter-spacing: 3px; }
    .ticket-body { padding: 20px; }
    .ticket-header { padding: 14px 20px; }
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
    <div class="ticket-wrap">

        <div class="nav-area">
            <div>
                <a href="history.php" class="back-link">&#8592; Riwayat Pesanan</a>
                <span style="color:#333;margin:0 8px;">|</span>
                <a href="index.php" class="back-link">Beranda</a>
            </div>
            <button type="button" class="btn-cetak" disabled>Cetak E-Ticket</button>
        </div>

        <div class="ticket-card">

            <div class="ticket-header">
                <div class="ticket-brand">
                    HIMTI <span>MOVIE</span>
                </div>
                <div class="ticket-badge">Tiket Sah</div>
            </div>

            <div class="ticket-body">

                <!-- kode unik per pesanan, wajib tunjuk ke petugas -->
                <div class="booking-box">
                    <div class="booking-label">Kode Booking</div>
                    <div class="booking-code"><?= htmlspecialchars($ticketData['booking_code']) ?></div>
                </div>

                <!-- detail lengkap film dan waktu tayang -->
                <table class="detail-table">
                    <tr>
                        <td class="lbl">Judul Film</td>
                        <td class="val"><?= htmlspecialchars($ticketData['movie_title'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td class="lbl">Studio</td>
                        <td class="val"><?= htmlspecialchars($ticketData['studio_name'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td class="lbl">Tanggal Tayang</td>
                        <td class="val"><?= !empty($ticketData['show_date']) ? date('d F Y', strtotime($ticketData['show_date'])) : '-' ?></td>
                    </tr>
                    <tr>
                        <td class="lbl">Jam Tayang</td>
                        <td class="val"><?= !empty($ticketData['show_time']) ? date('H:i', strtotime($ticketData['show_time'])) . ' WIB' : '-' ?></td>
                    </tr>
                    <tr>
                        <td class="lbl">Nama Pemesan</td>
                        <td class="val"><?= htmlspecialchars($ticketData['username'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td class="lbl">Jumlah Tiket</td>
                        <td class="val"><?= (int)$ticketData['total_tickets'] ?> Tiket</td>
                    </tr>
                    <?php if (!empty($ticketData['tickets'])): ?>
                    <tr>
                        <td class="lbl">Nomor Kursi</td>
                        <td class="val">
                            <?php
                                // gabungin semua nomor kursi jadi satu string
                                $seats = array_map(function($t) { return $t['seat_number']; }, $ticketData['tickets']);
                                echo htmlspecialchars(implode(', ', $seats));
                            ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td class="lbl">Total Pembayaran</td>
                        <td class="val val-red">Rp <?= number_format((float)$ticketData['total_price'], 0, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <td class="lbl">Waktu Transaksi</td>
                        <td class="val" style="color:#666;font-size:13px;">
                            <?= !empty($ticketData['order_date']) ? date('d/m/Y H:i', strtotime($ticketData['order_date'])) : '-' ?>
                        </td>
                    </tr>
                </table>

            </div>

            <!-- garis putus-putus dengan efek lubang, kaya tiket fisik -->
            <div class="ticket-divider">
                <div class="ticket-divider-line"></div>
            </div>

            <div class="ticket-footer">
                Tunjukkan kode booking ini kepada petugas bioskop saat memasuki studio.
            </div>

        </div>
    </div>
</div>

</body>
</html>
