<?php
/**
 * Admin-01 Laporan Transaksi (taruh di folder admin/)
 * SESUAIKAN koneksi ($conn) dan key session role jika beda dengan kode tim.
 */
require_once __DIR__ . '/../bootstrap.php';   // sudah berisi session & class DBConnection
require_once __DIR__ . '/../core/Validator.php';

if (($_SESSION['role'] ?? '') !== 'admin') {
    Validator::flash('Halaman ini hanya untuk admin.');
    header('Location: ../login.php');
    exit;
}

$db = new DBConnection();

$sql = "SELECT o.order_id, o.order_date, o.total_price, u.username, u.email,
               m.title, s.studio_name, sh.show_date, sh.show_time,
               COUNT(t.ticket_id) AS jumlah_tiket
        FROM orders o
        JOIN users u      ON o.user_id     = u.user_id
        JOIN showtimes sh ON o.showtime_id = sh.showtime_id
        JOIN movies m     ON sh.movie_id   = m.movie_id
        JOIN studios s    ON sh.studio_id  = s.studio_id
        LEFT JOIN tickets t ON t.order_id  = o.order_id
        GROUP BY o.order_id, o.order_date, o.total_price, u.username, u.email,
                 m.title, s.studio_name, sh.show_date, sh.show_time
        ORDER BY o.order_date DESC";
$orders = Validator::rows($db, $sql, [], 'Laporan tidak dapat dimuat saat ini.');

$rowTotal = Validator::rows($db, "SELECT COALESCE(SUM(total_price),0) AS total FROM orders");
$total    = (float)($rowTotal[0]['total'] ?? 0);

$e      = fn($v) => Validator::e($v);
$rupiah = fn($n) => 'Rp' . number_format((float)$n, 0, ',', '.');
?>
<?php
$page_title = 'Laporan Transaksi';
$base_url   = '../';
$body_class = 'theme-dark';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-container">

        <div class="dash-hero">
            <div class="dash-small">ADMIN PANEL</div>
            <h1 class="dash-title">Laporan <span>Transaksi</span></h1>
            <p class="dash-text">Daftar semua pesanan tiket beserta total pendapatan.</p>
        </div>

        <?= Validator::renderFlash() ?>

        <div class="panel">
            <?php if (!$orders): ?>
                <p class="empty">Belum ada transaksi.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="wide">
                        <thead>
                            <tr>
                                <th>No. Pesanan</th>
                                <th>Pemesan</th>
                                <th>Film</th>
                                <th>Studio</th>
                                <th>Jadwal</th>
                                <th>Tiket</th>
                                <th>Total</th>
                                <th>Tanggal Order</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $o): ?>
                                <tr>
                                    <td>#<?= $e($o['order_id']) ?></td>
                                    <td><?= $e($o['username']) ?><br><small class="muted"><?= $e($o['email']) ?></small></td>
                                    <td class="movie-name"><?= $e($o['title']) ?></td>
                                    <td><span class="pill"><?= $e($o['studio_name']) ?></span></td>
                                    <td class="waktu"><?= $e(date('d M Y', strtotime($o['show_date']))) ?>, <?= $e(substr($o['show_time'], 0, 5)) ?></td>
                                    <td><?= $e($o['jumlah_tiket']) ?></td>
                                    <td class="td-price"><?= $e($rupiah($o['total_price'])) ?></td>
                                    <td class="waktu"><?= $e(date('d M Y H:i', strtotime($o['order_date']))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="6" class="text-end">Total pendapatan</th>
                                <th colspan="2" class="text-start td-price"><?= $e($rupiah($total)) ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>