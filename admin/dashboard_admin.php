<?php
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
?>
<?php
$page_title = 'Dashboard Admin';
$base_url   = '../';
$body_class = 'theme-dark';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-container">

        <div class="dash-hero">
            <div class="dash-small">ADMIN PANEL</div>
            <h1 class="dash-title">Dashboard <span>Admin</span></h1>
            <p class="dash-text">
                Kelola film, genre, jadwal tayang, studio, dan pesanan HIMTI MOVIE dari satu tempat.
            </p>
        </div>

        <h2 class="section-title sm">Ringkasan <span>Data</span></h2>

        <div class="stat-grid">
            <?php foreach ($cards as [$label, $icon, $key, $href]): ?>
                <div class="stat-card">
                    <div class="stat-icon"><i class="bi <?= $icon ?>"></i></div>
                    <div class="stat-number"><?= (int)$stat[$key] ?></div>
                    <div class="stat-label"><?= htmlspecialchars($label) ?></div>
                    <a href="<?= htmlspecialchars($href) ?>" class="btn-manage">Kelola</a>
                </div>
            <?php endforeach; ?>
        </div>

    </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>