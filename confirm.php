<?php
/**
 * File     : confirm.php
 * Card     : Trx-03 Konfirmasi Pesanan
 * Tugas    : Ringkasan pesanan + kode booking setelah checkout berhasil.
 * PIC      : Davientyo Arifius Putra
 * NIM      : 434251115
 * Deadline : 3 Oktober 2026
 */

// Pastikan session aktif agar data login user tersedia
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ambil order_id dari query string URL
$orderId = (int)($_GET['order_id'] ?? 0);

// Jika belum ada parameter dari proses checkout, tampil kosong
if ($orderId <= 0) {
    exit;
}

// Muat class Order (yang otomatis membawa DBConnection via BaseModel)
require_once __DIR__ . '/classes/Order.php';

$orderModel = new Order();

// Ambil rincian lengkap pesanan (JOIN ke showtimes, movies, genres, studios, users)
$order = $orderModel->getOrderDetail($orderId);

// Jika pesanan belum ada di database, tampil kosong
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

// Judul halaman untuk header.php
$page_title = 'Konfirmasi Pesanan';
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<div class="container py-4" style="max-width:680px;">

    <div class="card shadow-sm">
        <div class="card-body p-4 text-center">

            <!-- Ikon Sukses -->
            <div class="mb-3">
                <span class="display-5 text-success">&#10003;</span>
            </div>
            <h3 class="mb-1">Pesanan Berhasil!</h3>
            <p class="text-muted mb-4">Tiket bioskop Anda telah berhasil dipesan.</p>

            <!-- Kode Booking (Trx-03) -->
            <div class="bg-light rounded p-3 mb-4 border">
                <p class="text-muted small mb-1">Kode Booking</p>
                <div class="fs-3 fw-bold font-monospace text-danger" id="bookingCodeText">
                    <?= htmlspecialchars($order['booking_code'] ?? '-') ?>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="salinKode()">
                    Salin Kode
                </button>
            </div>

            <!-- Rincian Pesanan (Trx-03) -->
            <div class="text-start mb-4">
                <table class="table table-sm table-borderless">
                    <tbody>
                        <tr>
                            <td class="text-muted">Film</td>
                            <td class="fw-semibold text-end"><?= htmlspecialchars($order['movie_title'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Studio</td>
                            <td class="fw-semibold text-end"><?= htmlspecialchars($order['studio_name'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Jadwal</td>
                            <td class="fw-semibold text-end">
                                <?= !empty($order['show_date']) ? date('d M Y', strtotime($order['show_date'])) : '-' ?>
                                &bull;
                                <?= !empty($order['show_time']) ? date('H:i', strtotime($order['show_time'])) . ' WIB' : '-' ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Jumlah Tiket</td>
                            <td class="fw-semibold text-end"><?= (int)($order['total_tickets'] ?? 1) ?> Lembar</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Total Bayar</td>
                            <td class="fw-bold text-end text-danger fs-5">
                                Rp <?= number_format((float)($order['total_price'] ?? 0), 0, ',', '.') ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Tombol Aksi -->
            <div class="d-grid gap-2">
                <a href="ticket.php?order_id=<?= $order['order_id'] ?>" class="btn btn-dark">
                    Lihat E-Tiket
                </a>
                <div class="row g-2">
                    <div class="col">
                        <a href="history.php" class="btn btn-outline-secondary w-100">Riwayat Pesanan</a>
                    </div>
                    <div class="col">
                        <a href="index.php" class="btn btn-outline-secondary w-100">Beranda</a>
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
