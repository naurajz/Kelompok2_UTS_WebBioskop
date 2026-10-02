<?php
/**
 * File     : ticket.php
 * Card     : Ticket-02 Ticket UI
 * Tugas    : E-ticket: film, jam, studio, jumlah tiket, kode booking. Bisa dicetak.
 * PIC      : Shafrie Alvito Wimala Rasendrya
 * NIM      : 434251142
 * Deadline : 3 Oktober 2026
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/classes/Ticket.php';

// Tangkap parameter order_id dari URL browser (?order_id=... atau ?code=...)
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($orderId <= 0 && isset($_GET['code'])) {
    $orderId = (int)preg_replace('/[^0-9]/', '', $_GET['code']);
}

// Jika parameter tidak ada, buat halaman putih saja
if ($orderId <= 0) {
    exit;
}

try {
    $ticketModel = new Ticket();
    $ticketData = $ticketModel->getOrderTicketDetails($orderId);
} catch (Exception $e) {
    $ticketData = null;
}

// Jika data tiket belum ada di database, buat halaman putih saja
if (!$ticketData) {
    exit;
}

// Proteksi akses: pastikan user hanya bisa melihat tiket miliknya sendiri (kecuali admin)
$currentUser = $_SESSION['user'] ?? null;
if ($currentUser && isset($currentUser['iduser'])) {
    $isOwner = ((int)$currentUser['iduser'] === (int)$ticketData['user_id']);
    $isAdmin = (isset($currentUser['role']) && strtolower($currentUser['role']) === 'admin');
    if (!$isOwner && !$isAdmin) {
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Ticket - <?= htmlspecialchars($ticketData['booking_code']) ?></title>
    <style>
        body {
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            background-color: #f7f7f9;
            color: #333333;
            margin: 0;
            padding: 24px 16px;
        }

        .container {
            max-width: 680px;
            margin: 0 auto;
        }

        .nav-links {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .nav-links a {
            color: #1f4e79;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }

        .nav-links a:hover {
            text-decoration: underline;
        }

        .btn-print {
            background-color: #e2e5ea;
            border: 1px solid #d1d5db;
            color: #555555;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 13px;
            cursor: default;
        }

        .kartu-tiket {
            background: #ffffff;
            border: 1px solid #e2e5ea;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
        }

        .header-tiket {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #1f4e79;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }

        .header-tiket h2 {
            margin: 0;
            color: #1f4e79;
            font-size: 20px;
        }

        .badge-status {
            background-color: #e8f4fd;
            color: #1f4e79;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
        }

        .kode-booking-box {
            background-color: #f0f4f8;
            border: 1px dashed #1f4e79;
            border-radius: 6px;
            text-align: center;
            padding: 12px;
            margin-bottom: 20px;
        }

        .kode-booking-label {
            font-size: 12px;
            color: #666666;
            margin-bottom: 4px;
        }

        .kode-booking-nilai {
            font-size: 24px;
            font-weight: 700;
            color: #1f4e79;
            letter-spacing: 2px;
        }

        .tabel-rincian {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .tabel-rincian td {
            padding: 8px 6px;
            font-size: 14px;
            border-bottom: 1px solid #f0f0f0;
        }

        .tabel-rincian td.label {
            color: #666666;
            width: 35%;
        }

        .tabel-rincian td.nilai {
            font-weight: 600;
            color: #222222;
        }

        .total-harga {
            font-size: 16px;
            color: #1f4e79;
            font-weight: 700;
        }

        .catatan {
            font-size: 12px;
            color: #888888;
            text-align: center;
            margin-top: 16px;
            line-height: 1.4;
        }
    </style>
</head>
<body>

<div class="container">

    <div class="nav-links">
        <div>
            <a href="history.php">&larr; Riwayat Pesanan</a> | 
            <a href="index.php">Beranda</a>
        </div>
        <button type="button" class="btn-print" disabled>Cetak E-Ticket</button>
    </div>

    <div class="kartu-tiket">
        <div class="header-tiket">
            <h2>CINESTAR CINEMA</h2>
            <span class="badge-status">TIKET SAH</span>
        </div>

        <div class="kode-booking-box">
            <div class="kode-booking-label">KODE BOOKING</div>
            <div class="kode-booking-nilai"><?= htmlspecialchars($ticketData['booking_code']) ?></div>
        </div>

        <table class="tabel-rincian">
            <tr>
                <td class="label">Judul Film</td>
                <td class="nilai"><?= htmlspecialchars($ticketData['movie_title']) ?></td>
            </tr>
            <tr>
                <td class="label">Studio</td>
                <td class="nilai"><?= htmlspecialchars($ticketData['studio_name']) ?></td>
            </tr>
            <tr>
                <td class="label">Tanggal Tayang</td>
                <td class="nilai"><?= date('d F Y', strtotime($ticketData['show_date'])) ?></td>
            </tr>
            <tr>
                <td class="label">Jam Tayang</td>
                <td class="nilai"><?= date('H:i', strtotime($ticketData['show_time'])) ?> WIB</td>
            </tr>
            <tr>
                <td class="label">Nama Pemesan</td>
                <td class="nilai"><?= htmlspecialchars($ticketData['username']) ?></td>
            </tr>
            <tr>
                <td class="label">Jumlah Tiket</td>
                <td class="nilai"><?= (int)$ticketData['total_tickets'] ?> Tiket</td>
            </tr>
            <?php if (!empty($ticketData['tickets'])): ?>
            <tr>
                <td class="label">Nomor Kursi</td>
                <td class="nilai">
                    <?php 
                        $seats = array_map(function($t) { return $t['seat_number']; }, $ticketData['tickets']);
                        echo htmlspecialchars(implode(', ', $seats));
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            <tr>
                <td class="label">Total Pembayaran</td>
                <td class="nilai total-harga">Rp <?= number_format((float)$ticketData['total_price'], 0, ',', '.') ?></td>
            </tr>
            <tr>
                <td class="label">Waktu Transaksi</td>
                <td class="nilai" style="font-size: 13px; color: #555555;"><?= date('d/m/Y H:i', strtotime($ticketData['order_date'])) ?></td>
            </tr>
        </table>

        <div class="catatan">
            Tunjukkan kode booking ini kepada petugas bioskop saat memasuki studio.
        </div>
    </div>

</div>

</body>
</html>
