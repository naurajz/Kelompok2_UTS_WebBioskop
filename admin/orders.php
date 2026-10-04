<?php
/**
 * File  : admin/orders.php
 * Tugas : Daftar pesanan masuk untuk admin (lihat dan cari).
 *
 * ASUMSI NAMA TABEL/KOLOM (sesuaikan di bagian QUERY kalau berbeda):
 *   orders    : order_id, user_id, showtime_id, total_price, created_at
 *   users     : user_id, name
 *   showtimes : showtime_id, movie_id, studio_id, show_date, show_time
 *   movies    : movie_id, title
 *   studios   : studio_id, studio_name
 */

require_once __DIR__ . '/../includes/admin_guard.php';

$db = new DBConnection();

// ==========================
// FILTER DARI URL (GET)
// ==========================
$keyword = trim($_GET['q'] ?? '');

$where  = [];
$params = [];

if ($keyword !== '') {
    $params[] = '%' . $keyword . '%';
    $n        = count($params);
    $where[]  = "(u.name ILIKE \$$n OR m.title ILIKE \$$n)";
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ==========================
// QUERY
// ==========================
$res = $db->send_query(
    "SELECT o.order_id,
            o.total_price,
            o.created_at,
            u.name       AS customer_name,
            m.title      AS movie_title,
            st.studio_name,
            s.show_date,
            s.show_time
     FROM orders o
     LEFT JOIN users u      ON o.user_id = u.user_id
     LEFT JOIN showtimes s  ON o.showtime_id = s.showtime_id
     LEFT JOIN movies m     ON s.movie_id = m.movie_id
     LEFT JOIN studios st   ON s.studio_id = st.studio_id
     $whereSql
     ORDER BY o.order_id DESC
     LIMIT 200",
    $params
);

$orders = $res['data'] ?? [];
$error  = $res['success'] ? null : $res['message'];

// Ringkasan (tanpa filter)
$sumRes = $db->send_query(
    "SELECT COUNT(*) AS total, COALESCE(SUM(total_price), 0) AS revenue FROM orders"
);
$sum = $sumRes['data'][0] ?? ['total' => 0, 'revenue' => 0];

$page_title = 'Kelola Pesanan';
$base_url = '../';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
    /* Tema gelap + aksen merah, disamakan dengan dashboard admin */
    body {
        background: #080808 !important;
        color: #fff;
        font-family: Arial, Helvetica, sans-serif;
    }

    .admin-dash { padding-top: 40px; padding-bottom: 60px; }

    .dash-hero {
        position: relative;
        overflow: hidden;
        background: linear-gradient(90deg, rgba(0,0,0,.9), rgba(20,20,20,.9));
        border: 1px solid #252525;
        border-radius: 16px;
        padding: 34px 40px;
        margin-bottom: 30px;
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

    .dash-title { font-size: 38px; font-weight: 900; margin: 0 0 8px; }
    .dash-title span { color: #e50914; }
    .dash-text { color: #aaa; margin: 0; position: relative; z-index: 1; }

    .btn-manage {
        display: inline-block;
        background: #e50914;
        color: #fff !important;
        font-size: 13px;
        font-weight: bold;
        padding: 10px 24px;
        border: none;
        border-radius: 8px;
        text-decoration: none;
        cursor: pointer;
        transition: .2s;
    }

    .btn-manage:hover { background: #b80710; }

    .btn-ghost {
        display: inline-block;
        background: #252525;
        border: 1px solid #404040;
        color: #fff !important;
        font-size: 13px;
        font-weight: bold;
        padding: 10px 20px;
        border-radius: 8px;
        text-decoration: none;
    }

    .btn-ghost:hover { background: #333; }

    /* Kartu statistik */
    .stat-card {
        background: #121212;
        border: 1px solid #252525;
        border-radius: 14px;
        height: 100%;
        padding: 24px 18px;
        text-align: center;
        transition: .3s;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        border-color: #e50914;
        box-shadow: 0 15px 35px rgba(229, 9, 20, .18);
    }

    .stat-number { font-size: 34px; font-weight: 900; line-height: 1.1; margin-bottom: 6px; }
    .stat-label { color: #888; font-size: 13px; letter-spacing: .5px; }

    /* Panel */
    .panel {
        background: #121212;
        border: 1px solid #252525;
        border-radius: 14px;
        padding: 26px;
        margin-top: 28px;
    }

    .panel h2 { font-size: 22px; font-weight: 900; margin: 0 0 20px; }
    .panel h2 span { color: #e50914; }

    .alert-box {
        padding: 14px 18px;
        border-radius: 10px;
        margin-bottom: 20px;
        font-size: 14px;
        border: 1px solid rgba(229, 9, 20, .5);
        background: rgba(229, 9, 20, .12);
        color: #ff7b82;
    }

    /* Filter */
    .filter-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
        margin-bottom: 22px;
    }

    .filter-bar input,
    .filter-bar select {
        background: #181818;
        color: #fff;
        border: 1px solid #333;
        border-radius: 8px;
        padding: 10px 13px;
        font-size: 14px;
        outline: none;
    }

    .filter-bar input { flex: 1; min-width: 220px; }
    .filter-bar select option { background: #181818; }

    .filter-bar input:focus,
    .filter-bar select:focus {
        border-color: #e50914;
        box-shadow: 0 0 0 3px rgba(229, 9, 20, .15);
    }

    /* Tabel */
    .table-wrap { overflow-x: auto; }

    .order-table { width: 100%; border-collapse: collapse; min-width: 880px; }

    .order-table th {
        color: #e50914;
        font-size: 12px;
        letter-spacing: 1px;
        text-transform: uppercase;
        background: #181818;
    }

    .order-table th,
    .order-table td {
        padding: 14px 12px;
        border-bottom: 1px solid #252525;
        text-align: left;
        vertical-align: middle;
        font-size: 14px;
        color: #fff;
    }

    .order-table tbody tr:hover { background: #171717; }

    .order-id { color: #888; font-weight: bold; }
    .movie-name { font-weight: bold; }
    .muted { color: #888; font-size: 12px; }
    .price { color: #e50914; font-weight: bold; white-space: nowrap; }
    .nowrap { white-space: nowrap; }

    .badge-studio {
        display: inline-block;
        background: rgba(229, 9, 20, .12);
        border: 1px solid rgba(229, 9, 20, .4);
        color: #ff6a72;
        border-radius: 20px;
        padding: 4px 12px;
        font-size: 12px;
        white-space: nowrap;
    }

    .empty { text-align: center; color: #777; padding: 40px !important; }

    @media (max-width: 550px) {
        .dash-hero { padding: 26px 22px; }
        .dash-title { font-size: 30px; }
        .panel { padding: 18px; }
    }
</style>

<div class="container admin-dash">

    <div class="dash-hero">
        <div class="dash-small">ADMIN PANEL</div>
        <h1 class="dash-title">Pesanan <span>Masuk</span></h1>
        <p class="dash-text mb-3">Daftar semua pesanan tiket yang masuk dari pelanggan HIMTI MOVIE.</p>
        <a href="dashboard_admin.php" class="btn-ghost" style="position:relative;z-index:1;">&larr; Dashboard</a>
    </div>

    <!-- Ringkasan -->
    <div class="row row-cols-1 row-cols-md-3 g-3">
        <div class="col">
            <div class="stat-card">
                <div class="stat-number"><?= (int)$sum['total'] ?></div>
                <div class="stat-label">Total Pesanan</div>
            </div>
        </div>
        <div class="col">
            <div class="stat-card">
                <div class="stat-number">Rp <?= number_format((float)$sum['revenue'], 0, ',', '.') ?></div>
                <div class="stat-label">Total Nilai Pesanan</div>
            </div>
        </div>
        <div class="col">
            <div class="stat-card">
                <div class="stat-number"><?= count($orders) ?></div>
                <div class="stat-label">Hasil Ditampilkan</div>
            </div>
        </div>
    </div>

    <!-- Daftar pesanan -->
    <div class="panel">
        <h2>Daftar <span>Pesanan</span></h2>

        <?php if ($error): ?>
            <div class="alert-box">
                Query gagal: <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="get" class="filter-bar">
            <input type="text" name="q" placeholder="Cari nama pelanggan atau judul film..."
                   value="<?= htmlspecialchars($keyword) ?>">

            <button type="submit" class="btn-manage">Cari</button>

            <?php if ($keyword !== ''): ?>
                <a href="orders.php" class="btn-ghost">Reset</a>
            <?php endif; ?>
        </form>

        <div class="table-wrap">
            <table class="order-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Pelanggan</th>
                        <th>Film</th>
                        <th>Studio</th>
                        <th>Jadwal</th>
                        <th>Total</th>
                        <th>Dipesan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td class="order-id">#<?= (int)$o['order_id'] ?></td>
                            <td><?= htmlspecialchars($o['customer_name'] ?? '-') ?></td>
                            <td class="movie-name"><?= htmlspecialchars($o['movie_title'] ?? '-') ?></td>
                            <td>
                                <?php if (!empty($o['studio_name'])): ?>
                                    <span class="badge-studio"><?= htmlspecialchars($o['studio_name']) ?></span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="nowrap">
                                <?php if (!empty($o['show_date'])): ?>
                                    <?= date('d M Y', strtotime($o['show_date'])) ?>
                                    <div class="muted"><?= date('H:i', strtotime($o['show_time'])) ?> WIB</div>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="price">Rp <?= number_format((float)$o['total_price'], 0, ',', '.') ?></td>
                            <td class="nowrap muted">
                                <?= !empty($o['created_at']) ? date('d M Y, H:i', strtotime($o['created_at'])) : '-' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$orders): ?>
                        <tr><td colspan="7" class="empty">Belum ada pesanan masuk.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>