<?php
require_once __DIR__ . '/../includes/admin_guard.php';

$showtime = new Showtime();
$message = null;
$isSuccess = false;
$old = ['movie_id' => '', 'studio_id' => '', 'show_date' => '', 'show_time' => '', 'price' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'delete') {
        $isSuccess = $showtime->delete((int)($_POST['showtime_id'] ?? 0));
        $message = $isSuccess ? 'Jadwal berhasil dihapus.' : 'Gagal menghapus jadwal.';
    } else {
        foreach ($old as $key => $_) {
            $old[$key] = trim($_POST[$key] ?? '');
        }

        if ($showtime->create($old)) {
            $isSuccess = true;
            $message = 'Jadwal tayang berhasil ditambahkan.';
            $old = array_fill_keys(array_keys($old), '');
        } else {
            $message = $showtime->getError();
        }
    }
}

$movies    = $showtime->getMovieOptions();
$studios   = $showtime->getStudioOptions();
$schedules = $showtime->getAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Jadwal Tayang - HIMTI MOVIE</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #080808;
            color: #fff;
            font-family: Arial, Helvetica, sans-serif;
            padding-top: 72px;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* ===== NAVBAR (sama seperti halaman utama) ===== */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 72px;
            background: rgba(8, 8, 8, .96);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 55px;
            z-index: 1000;
            border-bottom: 1px solid #222;
        }

        .logo {
            font-size: 25px;
            font-weight: 900;
        }

        .logo span {
            color: #e50914;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-menu a {
            color: #ddd;
            font-size: 14px;
        }

        .nav-menu a:hover,
        .nav-menu a.active {
            color: #e50914;
        }

        .nav-button {
            background: #e50914;
            color: #fff !important;
            padding: 10px 20px;
            border-radius: 7px;
        }

        .nav-button:hover {
            background: #b80710;
        }

        /* ===== LAYOUT ===== */
        .container {
            max-width: 1200px;
            margin: auto;
            padding: 40px 24px 70px;
        }

        .dash-hero {
            position: relative;
            overflow: hidden;
            background: linear-gradient(90deg, rgba(0, 0, 0, .9), rgba(20, 20, 20, .9));
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

        .dash-title {
            font-size: 38px;
            font-weight: 900;
            margin-bottom: 8px;
        }

        .dash-title span {
            color: #e50914;
        }

        .dash-text {
            color: #aaa;
            position: relative;
            z-index: 1;
        }

        .card {
            background: #121212;
            border: 1px solid #252525;
            border-radius: 14px;
            padding: 28px;
            margin-bottom: 28px;
        }

        .card h2 {
            font-size: 22px;
            font-weight: 900;
            margin-bottom: 22px;
        }

        .card h2 span {
            color: #e50914;
        }

        /* ===== ALERT ===== */
        .alert {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 24px;
            font-size: 14px;
            border: 1px solid;
        }

        .alert-success {
            background: rgba(46, 160, 67, .12);
            border-color: rgba(46, 160, 67, .5);
            color: #7ee287;
        }

        .alert-danger {
            background: rgba(229, 9, 20, .12);
            border-color: rgba(229, 9, 20, .5);
            color: #ff7b82;
        }

        .alert-close {
            background: none;
            border: none;
            color: inherit;
            font-size: 20px;
            cursor: pointer;
            line-height: 1;
        }

        /* ===== FORM ===== */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 4px 22px;
        }

        .col-half { grid-column: span 3; }
        .col-third { grid-column: span 2; }
        .col-full { grid-column: 1 / -1; }

        label {
            display: block;
            margin: 10px 0 7px;
            color: #bbb;
            font-size: 13px;
            font-weight: bold;
        }

        input,
        select {
            width: 100%;
            background: #181818;
            color: #fff;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 11px 13px;
            font-family: inherit;
            font-size: 14px;
            outline: none;
            transition: .2s;
            color-scheme: dark;
        }

        input:focus,
        select:focus {
            border-color: #e50914;
            box-shadow: 0 0 0 3px rgba(229, 9, 20, .15);
        }

        select option {
            background: #181818;
        }

        .btn-simpan {
            background: #e50914;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 12px 26px;
            margin-top: 18px;
            font-size: 14px;
            font-weight: bold;
            font-family: inherit;
            cursor: pointer;
            transition: .2s;
        }

        .btn-simpan:hover {
            background: #b80710;
        }

        /* ===== TABLE ===== */
        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 620px;
        }

        th {
            color: #e50914;
            font-size: 12px;
            letter-spacing: 1px;
            text-transform: uppercase;
            background: #181818;
        }

        th,
        td {
            padding: 14px 12px;
            border-bottom: 1px solid #252525;
            text-align: left;
            vertical-align: middle;
            font-size: 14px;
        }

        tbody tr:hover {
            background: #171717;
        }

        .text-end { text-align: right; }

        .movie-name {
            font-weight: bold;
        }

        .studio-badge {
            display: inline-block;
            background: rgba(229, 9, 20, .12);
            border: 1px solid rgba(229, 9, 20, .4);
            color: #ff6a72;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 12px;
            white-space: nowrap;
        }

        .price {
            color: #e50914;
            font-weight: bold;
            white-space: nowrap;
        }

        .waktu {
            white-space: nowrap;
            color: #ccc;
        }

        .empty {
            text-align: center;
            color: #777;
            padding: 40px !important;
        }

        .aksi form {
            display: inline;
        }

        .btn-hapus {
            display: inline-block;
            font-family: inherit;
            font-size: 12px;
            font-weight: bold;
            padding: 8px 14px;
            border-radius: 7px;
            cursor: pointer;
            transition: .2s;
            background: transparent;
            border: 1px solid #e50914;
            color: #e50914;
        }

        .btn-hapus:hover {
            background: #e50914;
            color: #fff;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 850px) {
            .navbar { padding: 0 20px; }
            .nav-menu { gap: 12px; }
            .nav-menu a { font-size: 12px; }
            .nav-button { padding: 8px 12px; }
            .dash-hero { padding: 26px 22px; }
            .dash-title { font-size: 30px; }
            .card { padding: 20px; }
            .col-half,
            .col-third { grid-column: 1 / -1; }
        }

        @media (max-width: 650px) {
            .nav-menu > a:not(.nav-button) { display: none; }
        }
    </style>
</head>

<body>

    <!-- ===== NAVBAR ===== -->
    <nav class="navbar">
        <div class="logo">HIMTI <span>MOVIE</span></div>

        <div class="nav-menu">
            <a href="dashboard_admin.php">Dashboard</a>
            <a href="movie.php">Film</a>
            <a href="genre.php">Genre</a>
            <a href="showtime.php" class="active">Jadwal</a>
            <a href="orders.php">Pesanan</a>
            <a href="../index.php">Website</a>
            <a href="../logout.php" class="nav-button">Logout</a>
        </div>
    </nav>

    <div class="container">

        <div class="dash-hero">
            <div class="dash-small">ADMIN PANEL</div>
            <h1 class="dash-title">Kelola <span>Jadwal Tayang</span></h1>
            <p class="dash-text">Tentukan film, studio, tanggal, jam, dan harga tiket untuk setiap sesi tayang.</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $isSuccess ? 'success' : 'danger' ?>" role="alert">
                <span><?= htmlspecialchars($message) ?></span>
                <button type="button" class="alert-close" aria-label="Tutup"
                    onclick="this.parentElement.remove()">&times;</button>
            </div>
        <?php endif; ?>

        <!-- ===== FORM TAMBAH JADWAL ===== -->
        <div class="card">
            <h2>Tambah <span>Jadwal</span></h2>

            <form method="post">
                <div class="form-grid">

                    <div class="col-half">
                        <label for="movie_id">Film</label>
                        <select name="movie_id" id="movie_id" required>
                            <option value="">-- Pilih film --</option>
                            <?php foreach ($movies as $m): ?>
                                <option value="<?= (int)$m['movie_id'] ?>" <?= $old['movie_id'] == $m['movie_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($m['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-half">
                        <label for="studio_id">Studio</label>
                        <select name="studio_id" id="studio_id" required>
                            <option value="">-- Pilih studio --</option>
                            <?php foreach ($studios as $s): ?>
                                <option value="<?= (int)$s['studio_id'] ?>" <?= $old['studio_id'] == $s['studio_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['studio_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-third">
                        <label for="show_date">Tanggal</label>
                        <input type="date" name="show_date" id="show_date"
                               min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($old['show_date']) ?>" required>
                    </div>

                    <div class="col-third">
                        <label for="show_time">Jam</label>
                        <input type="time" name="show_time" id="show_time"
                               value="<?= htmlspecialchars($old['show_time']) ?>" required>
                    </div>

                    <div class="col-third">
                        <label for="price">Harga (Rp)</label>
                        <input type="number" name="price" id="price" min="1000" step="1000"
                               value="<?= htmlspecialchars($old['price']) ?>" required>
                    </div>

                    <div class="col-full">
                        <button type="submit" class="btn-simpan">
                            <i class="bi bi-save"></i> Simpan Jadwal
                        </button>
                    </div>

                </div>
            </form>
        </div>

        <!-- ===== DAFTAR JADWAL ===== -->
        <div class="card">
            <h2>Jadwal <span>Saat Ini</span> (<?= count($schedules) ?>)</h2>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Film</th>
                            <th>Studio</th>
                            <th>Waktu</th>
                            <th>Harga</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedules as $row): ?>
                            <tr>
                                <td class="movie-name"><?= htmlspecialchars($row['movie_title']) ?></td>
                                <td><span class="studio-badge"><?= htmlspecialchars($row['studio_name']) ?></span></td>
                                <td class="waktu"><?= date('d M Y', strtotime($row['show_date'])) ?>, <?= date('H:i', strtotime($row['show_time'])) ?> WIB</td>
                                <td class="price">Rp <?= number_format((float)$row['price'], 0, ',', '.') ?></td>
                                <td class="text-end aksi">
                                    <form method="post" onsubmit="return confirm('Hapus jadwal ini? Pesanan dan tiket terkait ikut terhapus.')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="showtime_id" value="<?= (int)$row['showtime_id'] ?>">
                                        <button type="submit" class="btn-hapus"><i class="bi bi-trash"></i> Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$schedules): ?>
                            <tr><td colspan="5" class="empty">Belum ada jadwal tayang.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</body>

</html>