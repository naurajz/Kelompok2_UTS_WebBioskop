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

require_once __DIR__ . '/classes/Ticket.php';

// Tangkap parameter order_id dari URL browser (?order_id=... atau ?code=...)
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($orderId <= 0 && isset($_GET['code'])) {
    $orderId = (int)preg_replace('/[^0-9]/', '', $_GET['code']);
}

// Jika parameter tidak ada, akan putih tampilannya
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
$sessionUserId = $_SESSION['user_id'] ?? null;
$sessionRole   = $_SESSION['role'] ?? 'customer';
if ($sessionUserId && isset($ticketData['user_id']) && $sessionRole !== 'admin') {
    if ((int)$sessionUserId !== (int)$ticketData['user_id']) {
        exit;
    }
}

$page_title = 'E-Ticket - ' . htmlspecialchars($ticketData['booking_code']);
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>
<style>
        .kartu-tiket {
            background: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 24px;
        }
        .header-tiket {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #212529;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .header-tiket h2 { margin: 0; font-size: 18px; }
        .kode-booking-box {
            background-color: #f8f9fa;
            border: 1px dashed #6c757d;
            border-radius: 6px;
            text-align: center;
            padding: 12px;
            margin-bottom: 20px;
        }
        .kode-booking-label { font-size: 12px; color: #6c757d; margin-bottom: 4px; }
        .kode-booking-nilai { font-size: 24px; font-weight: 700; letter-spacing: 2px; }
        .tabel-rincian { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .tabel-rincian td { padding: 8px 6px; font-size: 14px; border-bottom: 1px solid #f0f0f0; }
        .tabel-rincian td.label { color: #6c757d; width: 40%; }
        .tabel-rincian td.nilai { font-weight: 600; }
        .catatan { font-size: 12px; color: #aaa; text-align: center; margin-top: 16px; line-height: 1.5; }
    </style>

<div class="container py-4" style="max-width:680px;">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <a href="history.php" class="text-decoration-none text-secondary small">&larr; Riwayat Pesanan</a>
            <span class="text-secondary small"> | </span>
            <a href="index.php" class="text-decoration-none text-secondary small">Beranda</a>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary" disabled>Cetak E-Ticket</button>
    </div>

    <div class="kartu-tiket shadow-sm">
        <div class="header-tiket">
            <h2>Cinema XXI / Bioskop</h2>
            <span class="badge bg-primary text-uppercase" style="font-size:11px;">Tiket Sah</span>
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
