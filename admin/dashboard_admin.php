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

$page_title = 'Dashboard Admin';
$base_url = '../';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
    /* Tema gelap + aksen merah, disamakan dengan halaman utama HIMTI MOVIE */
    body {
        background: #080808 !important;
        color: #fff;
        font-family: Arial, Helvetica, sans-serif;
    }

    .admin-dash {
        padding-top: 40px;
        padding-bottom: 60px;
    }

    /* Banner judul */
    .dash-hero {
        position: relative;
        overflow: hidden;
        background: linear-gradient(90deg, rgba(0,0,0,.9), rgba(20,20,20,.9));
        border: 1px solid #252525;
        border-radius: 16px;
        padding: 38px 40px;
        margin-bottom: 40px;
    }

    .dash-hero::after {
        content: "";
        position: absolute;
        width: 220px;
        height: 220px;
        border-radius: 50%;
        background: rgba(229, 9, 20, .12);
        top: -110px;
        right: -70px;
    }

    .dash-small {
        color: #e50914;
        font-size: 13px;
        font-weight: bold;
        letter-spacing: 4px;
        margin-bottom: 10px;
    }

    .dash-title {
        font-size: 40px;
        font-weight: 900;
        margin: 0 0 10px;
    }

    .dash-title span { color: #e50914; }

    .dash-text {
        color: #aaa;
        margin: 0;
        position: relative;
        z-index: 1;
    }

    .section-title-dash {
        font-size: 26px;
        font-weight: 900;
        margin-bottom: 24px;
    }

    .section-title-dash span { color: #e50914; }

    /* Kartu statistik */
    .stat-card {
        background: #121212;
        border: 1px solid #252525;
        border-radius: 14px;
        height: 100%;
        padding: 28px 18px 24px;
        text-align: center;
        transition: .3s;
    }

    .stat-card:hover {
        transform: translateY(-7px);
        border-color: #e50914;
        box-shadow: 0 15px 35px rgba(229, 9, 20, .18);
    }

    .stat-icon {
        width: 62px;
        height: 62px;
        margin: 0 auto 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: rgba(229, 9, 20, .12);
        border: 1px solid rgba(229, 9, 20, .35);
        color: #e50914;
        font-size: 28px;
    }

    .stat-number {
        font-size: 44px;
        font-weight: 900;
        line-height: 1;
        margin-bottom: 6px;
    }

    .stat-label {
        color: #888;
        font-size: 13px;
        letter-spacing: .5px;
        margin-bottom: 18px;
    }

    .btn-manage {
        display: inline-block;
        background: #e50914;
        color: #fff !important;
        font-size: 12px;
        font-weight: bold;
        padding: 9px 24px;
        border-radius: 7px;
        text-decoration: none;
        transition: .2s;
    }

    .btn-manage:hover {
        background: #b80710;
    }

    @media (max-width: 550px) {
        .dash-hero { padding: 28px 22px; }
        .dash-title { font-size: 30px; }
        .stat-number { font-size: 36px; }
    }
</style>

<div class="container admin-dash">

    <div class="dash-hero">
        <div class="dash-small">ADMIN PANEL</div>
        <h1 class="dash-title">Dashboard <span>Admin</span></h1>
        <p class="dash-text">
            Kelola film, genre, jadwal tayang, studio, dan pesanan HIMTI MOVIE dari satu tempat.
        </p>
    </div>

    <h2 class="section-title-dash">Ringkasan <span>Data</span></h2>

    <div class="row row-cols-2 row-cols-md-3 row-cols-xl-5 g-3">
        <?php foreach ($cards as [$label, $icon, $key, $href]): ?>
            <div class="col">
                <div class="stat-card">
                    <div class="stat-icon"><i class="bi <?= $icon ?>"></i></div>
                    <div class="stat-number"><?= (int)$stat[$key] ?></div>
                    <div class="stat-label"><?= htmlspecialchars($label) ?></div>
                    <?php if ($href): ?>
                        <a href="<?= htmlspecialchars($href) ?>" class="btn-manage">Kelola</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>