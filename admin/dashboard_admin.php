<?php
/**
 * File     : admin/index.php
 * Card     : [Kode card]
 * Tugas    : Dashboard admin: ringkasan data dan menu ke halaman kelola film, genre, jadwal.
 * PIC      : [Nama PIC]
 * NIM      : [NIM]
 * Deadline : [Tanggal deadline]
 */

require_once __DIR__ . '/../includes/admin_guard.php';

$db = new DBConnection();
$res = $db->send_query(
    "SELECT (SELECT COUNT(*) FROM movies) AS movies,
            (SELECT COUNT(*) FROM genres) AS genres,
            (SELECT COUNT(*) FROM showtimes) AS showtimes,
            (SELECT COUNT(*) FROM studios) AS studios,
            (SELECT COUNT(*) FROM orders) AS orders"
);
$stat = $res['data'][0] ?? ['movies' => 0, 'genres' => 0, 'showtimes' => 0, 'studios' => 0, 'orders' => 0];

$cards = [
    ['Film',          'bi-film',           'movies',    'movie.php'],
    ['Genre',         'bi-tags',           'genres',    'genre.php'],
    ['Jadwal Tayang', 'bi-calendar-event', 'showtimes', 'showtime.php'],
    ['Pesanan',       'bi-receipt',        'orders',    'orders.php'],
    ['Studio',        'bi-display',        'studios',   'studio.php'],
];

$page_title = 'Dashboard Admin';
$base_url = '../';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <h2 class="h4 mb-4"><i class="bi bi-speedometer2 me-2"></i>Dashboard Admin</h2>

    <div class="row g-3">
        <?php foreach ($cards as [$label, $icon, $key, $href]): ?>
            <div class="col-6 col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="bi <?= $icon ?> fs-1 text-warning"></i>
                        <div class="display-6 fw-bold"><?= (int)$stat[$key] ?></div>
                        <div class="text-muted mb-3"><?= htmlspecialchars($label) ?></div>
                        <?php if ($href): ?>
                            <a href="<?= $href ?>" class="btn btn-dark btn-sm">Kelola</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>