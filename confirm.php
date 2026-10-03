<?php
/**
 * File     : confirm.php
 * Card     : Trx-03 Konfirmasi Pesanan
 * Tugas    : Ringkasan pesanan + kode booking setelah checkout berhasil.
 * PIC      : Davientyo Arifius Putra
 * NIM      : 434251115
 * Deadline : 3 Oktober 2026
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$orderId = (int)($_GET['order_id'] ?? 0);
if ($orderId <= 0) {
    exit;
}

require_once __DIR__ . '/classes/Order.php';

$orderModel = new Order();
$order = $orderModel->getOrderDetail($orderId);

if (!$order) {
    exit;
}

// Validasi: hanya pemilik pesanan atau admin yang boleh akses
$currentUserId   = $_SESSION['user_id'] ?? null;
$currentUserRole = $_SESSION['role'] ?? 'customer';

if ($currentUserId && isset($order['user_id']) && $currentUserRole !== 'admin'
    && (int)$order['user_id'] !== (int)$currentUserId) {
    exit;
}

$page_title = 'Konfirmasi Pesanan';
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<style>
    body { background-color: #141414 !important; color: #e5e5e5 !important; }
    .main-content { background-color: #141414; }

    .confirm-wrap {
        max-width: 580px;
        margin: 0 auto;
    }
    .confirm-card {
        background-color: #1e1e1e;
        border: 1px solid #2a2a2a;
        border-radius: 12px;
        padding: 2rem;
    }

    .success-circle {
        width: 64px; height: 64px;
        border-radius: 50%;
        background-color: #1a3d22;
        color: #4caf50;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.8rem;
        margin: 0 auto 1rem;
    }

    .confirm-title { color: #fff; font-weight: 700; }
    .confirm-subtitle { color: #888; font-size: .9rem; }

    .booking-box {
        background-color: #111;
        border: 1px dashed #3a3a3a;
        border-radius: 8px;
        padding: 1rem;
        text-align: center;
    }
    .booking-label { color: #888; font-size: .75rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
    .booking-code  { font-family: monospace; font-size: 1.8rem; font-weight: 700; color: #e50914; letter-spacing: 3px; }

    .btn-copy {
        margin-top: 8px;
        background: transparent;
        border: 1px solid #3a3a3a;
        color: #888;
        padding: 4px 14px;
        border-radius: 20px;
        font-size: .8rem;
        cursor: pointer;
        transition: all .15s;
    }
    .btn-copy:hover { border-color: #888; color: #e5e5e5; }

    .summary-box {
        background-color: #111;
        border: 1px solid #2a2a2a;
        border-radius: 8px;
        padding: 1rem 1.25rem;
    }
    .summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #222;
        font-size: .9rem;
    }
    .summary-row:last-child { border-bottom: none; }
    .summary-label { color: #888; }
    .summary-val   { color: #e5e5e5; font-weight: 600; }
    .summary-total { color: #e50914; font-size: 1.05rem; }

    .btn-ticket {
        display: block; width: 100%;
        background-color: #e50914;
        color: #fff; text-align: center;
        padding: 12px; border-radius: 8px;
        font-weight: 700; text-decoration: none;
        transition: background .2s;
    }
    .btn-ticket:hover { background-color: #c1070f; color: #fff; }

    .btn-sec {
        display: block;
        background-color: #2a2a2a;
        color: #ccc; text-align: center;
        padding: 10px; border-radius: 8px;
        font-weight: 600; text-decoration: none;
        transition: background .2s;
    }
    .btn-sec:hover { background-color: #333; color: #fff; }
</style>

<div class="container py-4">
    <div class="confirm-wrap">
        <div class="confirm-card">

            <div class="success-circle">&#10003;</div>
            <h3 class="confirm-title text-center mb-1">Pesanan Berhasil!</h3>
            <p class="confirm-subtitle text-center mb-4">Tiket bioskop Anda telah berhasil dipesan.</p>

            <!-- Kode Booking (Trx-03) -->
            <div class="booking-box mb-4">
                <div class="booking-label">Kode Booking</div>
                <div class="booking-code" id="bookingCodeText">
                    <?= htmlspecialchars($order['booking_code'] ?? '-') ?>
                </div>
                <button type="button" class="btn-copy" onclick="salinKode()">Salin Kode</button>
            </div>

            <!-- Rincian Pesanan (Trx-03) -->
            <div class="summary-box mb-4">
                <div class="summary-row">
                    <span class="summary-label">Film</span>
                    <span class="summary-val"><?= htmlspecialchars($order['movie_title'] ?? '-') ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Studio</span>
                    <span class="summary-val"><?= htmlspecialchars($order['studio_name'] ?? '-') ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Jadwal</span>
                    <span class="summary-val">
                        <?= !empty($order['show_date']) ? date('d M Y', strtotime($order['show_date'])) : '-' ?>
                        &bull;
                        <?= !empty($order['show_time']) ? date('H:i', strtotime($order['show_time'])) . ' WIB' : '-' ?>
                    </span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Jumlah Tiket</span>
                    <span class="summary-val"><?= (int)($order['total_tickets'] ?? 1) ?> Lembar</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Total Bayar</span>
                    <span class="summary-val summary-total">
                        Rp <?= number_format((float)($order['total_price'] ?? 0), 0, ',', '.') ?>
                    </span>
                </div>
            </div>

            <!-- Tombol Aksi -->
            <div class="d-grid gap-2">
                <a href="ticket.php?order_id=<?= $order['order_id'] ?>" class="btn-ticket">
                    Lihat E-Tiket
                </a>
                <div class="row g-2">
                    <div class="col">
                        <a href="history.php" class="btn-sec">Riwayat Pesanan</a>
                    </div>
                    <div class="col">
                        <a href="index.php" class="btn-sec">Beranda</a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    function salinKode() {
        const kode = document.getElementById('bookingCodeText').innerText.trim();
        navigator.clipboard.writeText(kode).then(() => {
            alert('Kode booking disalin: ' + kode);
        }).catch(() => {
            alert('Kode booking: ' + kode);
        });
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
