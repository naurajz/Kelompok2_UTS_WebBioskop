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

// Proteksi akses: pastikan user hanya bisa melihat tiket miliknya sendiri (kecuali role admin)
$currentUserId = $_SESSION['user_id'] ?? ($_SESSION['user']['user_id'] ?? null);
$currentUserRole = $_SESSION['role'] ?? ($_SESSION['user']['role'] ?? 'customer');
if ($currentUserId && !empty($ticketData['user_id']) && $currentUserRole !== 'admin' && (int)$currentUserId !== (int)$ticketData['user_id']) {
    exit;
}

// Judul halaman diselaraskan dengan header dan nama bioskop kelompok
$page_title = "E-Ticket #" . $ticketData['booking_code'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4" style="max-width: 720px;">

    <!-- Baris Navigasi & Aksi -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <a href="history.php" class="btn btn-outline-secondary btn-sm me-2">
                <i class="bi bi-arrow-left me-1"></i>Riwayat Pesanan
            </a>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-house me-1"></i>Beranda
            </a>
        </div>
        <button type="button" class="btn btn-secondary btn-sm" disabled>
            <i class="bi bi-printer me-1"></i>Cetak E-Ticket
        </button>
    </div>

    <!-- Kartu E-Ticket Sesuai Standar Modul & Kelompok -->
    <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
        <!-- Header Kartu: Judul Bioskop & Status -->
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0 text-warning">
                <i class="bi bi-film me-2"></i>Cinema XXI / Bioskop
            </h5>
            <span class="badge bg-success px-3 py-2 text-uppercase">
                <i class="bi bi-check-circle me-1"></i>Tiket Sah
            </span>
        </div>

        <div class="card-body p-4 bg-white">
            <!-- Box Kode Booking -->
            <div class="alert alert-light border border-secondary text-center py-3 mb-4 rounded-3">
                <div class="text-secondary small fw-semibold text-uppercase mb-1">Kode Booking</div>
                <div class="h3 fw-bold text-dark mb-0 font-monospace" style="letter-spacing: 2px;">
                    <?= htmlspecialchars($ticketData['booking_code']) ?>
                </div>
            </div>

            <!-- Tabel Rincian Tiket & Jadwal Tayang -->
            <div class="table-responsive">
                <table class="table table-borderless align-middle mb-0">
                    <tbody>
                        <tr class="border-bottom">
                            <td class="text-secondary" style="width: 35%;">Judul Film</td>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($ticketData['movie_title']) ?></td>
                        </tr>
                        <tr class="border-bottom">
                            <td class="text-secondary">Studio</td>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($ticketData['studio_name']) ?></td>
                        </tr>
                        <tr class="border-bottom">
                            <td class="text-secondary">Tanggal Tayang</td>
                            <td class="fw-semibold text-dark"><?= date('d F Y', strtotime($ticketData['show_date'])) ?></td>
                        </tr>
                        <tr class="border-bottom">
                            <td class="text-secondary">Jam Tayang</td>
                            <td class="fw-semibold text-dark"><?= date('H:i', strtotime($ticketData['show_time'])) ?> WIB</td>
                        </tr>
                        <tr class="border-bottom">
                            <td class="text-secondary">Nama Pemesan</td>
                            <td class="fw-semibold text-dark"><?= htmlspecialchars($ticketData['username']) ?></td>
                        </tr>
                        <tr class="border-bottom">
                            <td class="text-secondary">Jumlah Tiket</td>
                            <td class="fw-semibold text-dark"><?= (int)$ticketData['total_tickets'] ?> Tiket</td>
                        </tr>
                        <?php if (!empty($ticketData['tickets'])): ?>
                        <tr class="border-bottom">
                            <td class="text-secondary">Nomor Kursi</td>
                            <td class="fw-bold text-dark">
                                <?php 
                                    $seats = array_map(function($t) { return $t['seat_number']; }, $ticketData['tickets']);
                                    echo htmlspecialchars(implode(', ', $seats));
                                ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <tr class="border-bottom">
                            <td class="text-secondary">Total Pembayaran</td>
                            <td class="fw-bold text-success fs-5">Rp <?= number_format((float)$ticketData['total_price'], 0, ',', '.') ?></td>
                        </tr>
                        <tr>
                            <td class="text-secondary">Waktu Transaksi</td>
                            <td class="text-secondary small"><?= date('d/m/Y H:i', strtotime($ticketData['order_date'])) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Catatan Penggunaan -->
            <div class="text-center text-muted small mt-4 pt-3 border-top">
                <i class="bi bi-info-circle me-1"></i>Tunjukkan kode booking ini kepada petugas bioskop saat memasuki studio.
            </div>
        </div>
    </div>

</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
