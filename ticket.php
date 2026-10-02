<?php
/**
 * File     : ticket.php
 * Card     : Ticket-02 Ticket UI
 * Tugas    : E-ticket: film, jam, studio, jumlah tiket, kode booking.
 * PIC      : Shafrie Alvito Wimala Rasendrya
 * NIM      : 434251142
 * Deadline : 3 Oktober 2026
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/classes/Ticket.php';

// Tangkap parameter order_id dari URL browser
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($orderId <= 0 && isset($_GET['code'])) {
    $orderId = (int)preg_replace('/[^0-9]/', '', $_GET['code']);
}

// Jika parameter tidak ada, tampil kosong
if ($orderId <= 0) {
    exit;
}

try {
    $ticketModel = new Ticket();
    $ticketData  = $ticketModel->getOrderTicketDetails($orderId);
} catch (Exception $e) {
    $ticketData = null;
}

// Jika data tiket belum ada di database, tampil kosong
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
    body { background-color: #141414 !important; color: #e5e5e5 !important; }
    .main-content { background-color: #141414; }

    .ticket-wrap { max-width: 620px; margin: 0 auto; }

    .ticket-card {
        background-color: #1e1e1e;
        border: 1px solid #2a2a2a;
        border-radius: 12px;
        overflow: hidden;
    }

    /* Header kartu tiket */
    .ticket-header {
        background-color: #111;
        border-bottom: 2px solid #e50914;
        padding: 1.25rem 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .ticket-brand { color: #e50914; font-weight: 800; font-size: 1.1rem; letter-spacing: .5px; }
    .ticket-badge {
        background-color: #1a3d22;
        color: #4caf50;
        font-size: .7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 3px 10px;
        border-radius: 4px;
    }

    .ticket-body { padding: 1.5rem; }

    /* Kode booking */
    .booking-box {
        background-color: #111;
        border: 1px dashed #e50914;
        border-radius: 8px;
        text-align: center;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }
    .booking-label { color: #888; font-size: .7rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
    .booking-code  { font-family: monospace; font-size: 2rem; font-weight: 700; color: #e50914; letter-spacing: 4px; }

    /* Tabel rincian */
    .detail-table { width: 100%; border-collapse: collapse; }
    .detail-table tr td { padding: 10px 6px; font-size: .9rem; border-bottom: 1px solid #252525; }
    .detail-table tr:last-child td { border-bottom: none; }
    .detail-table td.lbl { color: #888; width: 40%; }
    .detail-table td.val { font-weight: 600; color: #e5e5e5; }
    .detail-table td.val-price { font-weight: 700; color: #e50914; font-size: 1rem; }

    /* Divider berlubang khas tiket bioskop */
    .ticket-divider {
        position: relative;
        height: 24px;
        margin: 0 -0px;
    }
    .ticket-divider::before,
    .ticket-divider::after {
        content: '';
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 16px; height: 16px;
        background-color: #141414;
        border-radius: 50%;
        z-index: 2;
    }
    .ticket-divider::before { left: -8px; }
    .ticket-divider::after  { right: -8px; }
    .ticket-divider-line {
        position: absolute;
        top: 50%;
        left: 8px; right: 8px;
        border-top: 2px dashed #2a2a2a;
    }

    /* Footer tiket */
    .ticket-footer {
        background-color: #111;
        padding: .875rem 1.5rem;
        font-size: .78rem;
        color: #555;
        text-align: center;
    }

    /* Nav links */
    .nav-area { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
    .nav-area a { color: #888; text-decoration: none; font-size: .875rem; }
    .nav-area a:hover { color: #e50914; }
    .btn-cetak {
        background-color: #2a2a2a;
        border: 1px solid #3a3a3a;
        color: #888;
        padding: 5px 14px;
        border-radius: 6px;
        font-size: .8rem;
        cursor: not-allowed;
    }
</style>

<div class="container py-4">
    <div class="ticket-wrap">

        <div class="nav-area">
            <div>
                <a href="history.php">&larr; Riwayat Pesanan</a>
                <span style="color:#444;margin:0 6px;">|</span>
                <a href="index.php">Beranda</a>
            </div>
            <button type="button" class="btn-cetak" disabled>Cetak E-Ticket</button>
        </div>

        <div class="ticket-card">

            <!-- Header -->
            <div class="ticket-header">
                <span class="ticket-brand">&#127916; Cinema XXI / Bioskop</span>
                <span class="ticket-badge">Tiket Sah</span>
            </div>

            <div class="ticket-body">

                <!-- Kode Booking -->
                <div class="booking-box">
                    <div class="booking-label">Kode Booking</div>
                    <div class="booking-code"><?= htmlspecialchars($ticketData['booking_code']) ?></div>
                </div>

                <!-- Rincian Tiket -->
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
                                $seats = array_map(function($t) { return $t['seat_number']; }, $ticketData['tickets']);
                                echo htmlspecialchars(implode(', ', $seats));
                            ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td class="lbl">Total Pembayaran</td>
                        <td class="val val-price">Rp <?= number_format((float)$ticketData['total_price'], 0, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <td class="lbl">Waktu Transaksi</td>
                        <td class="val" style="color:#666;font-size:.85rem;">
                            <?= !empty($ticketData['order_date']) ? date('d/m/Y H:i', strtotime($ticketData['order_date'])) : '-' ?>
                        </td>
                    </tr>
                </table>

            </div>

            <!-- Divider khas tiket -->
            <div class="ticket-divider"><div class="ticket-divider-line"></div></div>

            <!-- Footer tiket -->
            <div class="ticket-footer">
                Tunjukkan kode booking ini kepada petugas bioskop saat memasuki studio.
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
