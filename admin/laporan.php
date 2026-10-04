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

$header = __DIR__ . '/../includes/header.php';
$footer = __DIR__ . '/../includes/footer.php';
if (file_exists($header)) include $header;
else echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Laporan Transaksi</title></head><body>';
?>
<style>
body{background:#0a0a0a;color:#fff}
.pg-wrap{max-width:1100px;margin:32px auto;padding:0 24px;color:#fff}
.pg-wrap h1{font-size:2rem;margin:0 0 20px}
.pg-wrap table{width:100%;border-collapse:collapse;background:#111}
.pg-wrap th,.pg-wrap td{padding:12px 14px;border-bottom:1px solid #2a2a2a;text-align:left;vertical-align:top}
.pg-wrap thead th{background:#1a1a1a}
.pg-wrap tfoot th{background:#1a1a1a;color:#e50914;font-size:1.05rem}
.pg-wrap a{color:#e50914;font-weight:600}
.pg-wrap small{color:#aaa}
.pg-wrap p{color:#ccc}
</style>
<main class="pg-wrap">
  <h1>Laporan Transaksi</h1>
  <?= Validator::renderFlash() ?>
  <?php if (!$orders): ?>
    <p>Belum ada transaksi.</p>
  <?php else: ?>
    <div style="overflow-x:auto">
    <table>
      <thead><tr>
        <th>No. Pesanan</th><th>Pemesan</th><th>Film</th><th>Studio</th><th>Jadwal</th><th>Tiket</th><th>Total</th><th>Tanggal Order</th>
      </tr></thead>
      <tbody>
      <?php foreach ($orders as $o): ?>
        <tr>
          <td>#<?= $e($o['order_id']) ?></td>
          <td><?= $e($o['username']) ?><br><small><?= $e($o['email']) ?></small></td>
          <td><?= $e($o['title']) ?></td>
          <td><?= $e($o['studio_name']) ?></td>
          <td><?= $e(date('d M Y', strtotime($o['show_date']))) ?>, <?= $e(substr($o['show_time'], 0, 5)) ?></td>
          <td><?= $e($o['jumlah_tiket']) ?></td>
          <td><?= $e($rupiah($o['total_price'])) ?></td>
          <td><?= $e(date('d M Y H:i', strtotime($o['order_date']))) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot><tr>
        <th colspan="6" style="text-align:right">Total pendapatan</th>
        <th colspan="2" style="text-align:left"><?= $e($rupiah($total)) ?></th>
      </tr></tfoot>
    </table>
    </div>
  <?php endif; ?>
</main>
<?php
if (file_exists($footer)) include $footer; else echo '</body></html>';
