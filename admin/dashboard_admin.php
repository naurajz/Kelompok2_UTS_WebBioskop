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
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - HIMTI MOVIE</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: #080808;
            color: white;
            font-family: Arial, Helvetica, sans-serif;
        }

        a { text-decoration: none; color: inherit; }

        /* NAVBAR */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 72px;
            background: rgba(8, 8, 8, 0.96);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 55px;
            z-index: 9999;
            border-bottom: 1px solid #222;
        }

        .logo { font-size: 25px; font-weight: 900; }
        .logo span { color: #e50914; }
        .logo small {
            font-size: 11px;
            color: #888;
            letter-spacing: 2px;
            margin-left: 10px;
            font-weight: bold;
        }

        .nav-menu { display: flex; align-items: center; gap: 32px; }
        .nav-menu a { color: #ddd; font-size: 14px; }
        .nav-menu a:hover { color: #e50914; }

        .logout {
            background: #e50914;
            padding: 10px 20px;
            border-radius: 7px;
            color: white !important;
        }
        .logout:hover { background: #b20710; }

        /* CONTENT */
        .container {
            max-width: 1100px;
            margin: auto;
            padding: 120px 25px 60px;
        }

        .title {
            font-size: 32px;
            font-weight: 900;
            margin-bottom: 8px;
        }
        .title span { color: #e50914; }

        .subtitle { color: #888; margin-bottom: 35px; }

        .grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .card {
            background: #121212;
            border: 1px solid #252525;
            border-radius: 12px;
            padding: 28px 20px;
            text-align: center;
            transition: .3s;
        }

        .card:hover {
            transform: translateY(-7px);
            border-color: #e50914;
            box-shadow: 0 15px 35px rgba(229, 9, 20, .18);
        }

        .card i { font-size: 38px; color: #e50914; }

        .card .number {
            font-size: 48px;
            font-weight: 900;
            margin: 8px 0 2px;
        }

        .card .label { color: #888; font-size: 14px; margin-bottom: 18px; }

        .card .btn {
            display: inline-block;
            background: #e50914;
            padding: 9px 22px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: bold;
        }
        .card .btn:hover { background: #b20710; }

        @media (max-width: 850px) {
            .navbar { padding: 0 20px; }
            .nav-menu { gap: 15px; }
            .nav-menu a:not(.logout) { display: none; }
            .grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 550px) {
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="logo">
        HIMTI <span>MOVIE</span>
        <small>ADMIN</small>
    </div>

    <div class="nav-menu">
        <a href="index.php">Dashboard</a>
        <a href="../index.php">Lihat Website</a>
        <a href="../logout.php" class="logout"
           onclick="return confirm('Yakin ingin logout?');">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</nav>

<div class="container">
    <h1 class="title">Dashboard <span>Admin</span></h1>
    <p class="subtitle">Ringkasan data dan menu pengelolaan.</p>

    <div class="grid">
        <?php foreach ($cards as [$label, $icon, $key, $href]): ?>
            <div class="card">
                <i class="bi <?= $icon ?>"></i>
                <div class="number"><?= (int) $stat[$key] ?></div>
                <div class="label"><?= htmlspecialchars($label) ?></div>
                <?php if ($href): ?>
                    <a href="<?= $href ?>" class="btn">KELOLA</a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

</body>
</html>