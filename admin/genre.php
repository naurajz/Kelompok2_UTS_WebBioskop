<?php
require_once __DIR__ . '/../bootstrap.php';          // memulai session + koneksi database
require_once __DIR__ . '/auth_check.php';            // proteksi admin (card Auth-02)
require_once __DIR__ . '/../classes/Genre.php';

// Pengaman tambahan: hanya admin yang boleh membuka halaman ini,
// supaya tetap aman walaupun auth_check.php belum selesai.
if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Mencegah karakter HTML bermasalah saat ditampilkan (XSS)
function aman($data)
{
    return htmlspecialchars((string) $data, ENT_QUOTES, 'UTF-8');
}

$genreModel = new Genre();

// PROSES FORM (POST)
// Pola Post-Redirect-Get: setelah diproses, redirect agar refresh tidak mengirim ulang form.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    try {
        if ($aksi === 'simpan') {
            // id kosong = tambah baru, id terisi = ubah genre
            $id   = !empty($_POST['id']) ? (int) $_POST['id'] : null;
            $nama = $_POST['genre_name'] ?? '';

            $genre = new Genre($nama, $id);

            if ($genre->save()) {
                $_SESSION['flash_genre'] = [
                    'type' => 'success',
                    'text' => $id === null ? 'Genre berhasil ditambahkan.' : 'Genre berhasil diperbarui.',
                ];
            } else {
                throw new Exception('Gagal menyimpan genre ke database.');
            }
        } elseif ($aksi === 'hapus') {
            $id = (int) ($_POST['id'] ?? 0);

            if ($id <= 0) {
                throw new InvalidArgumentException('ID genre tidak valid.');
            }

            if ($genreModel->delete($id)) {
                $_SESSION['flash_genre'] = ['type' => 'success', 'text' => 'Genre berhasil dihapus.'];
            } else {
                throw new Exception('Gagal menghapus genre.');
            }
        }
    } catch (Throwable $e) {
        $_SESSION['flash_genre'] = ['type' => 'danger', 'text' => $e->getMessage()];

        // Saat gagal simpan, kembalikan ke form agar admin tidak mengetik ulang
        if (($aksi ?? '') === 'simpan') {
            $_SESSION['old_genre'] = [
                'id'   => $_POST['id'] ?? '',
                'name' => $_POST['genre_name'] ?? '',
            ];
        }
    }

    header('Location: genre.php');
    exit;
}

// SIAPKAN DATA TAMPILAN

// Pesan sekali tampil (flash)
$flash = $_SESSION['flash_genre'] ?? null;
unset($_SESSION['flash_genre']);

// Input lama jika validasi gagal
$old = $_SESSION['old_genre'] ?? null;
unset($_SESSION['old_genre']);

// Mode edit: ?edit=ID mengisi form dengan data genre tersebut
$editData = null;
if (isset($_GET['edit'])) {
    $editData = $genreModel->getById((int) $_GET['edit']);
}

$formId   = $old['id']   ?? ($editData['genre_id']   ?? '');
$formNama = $old['name'] ?? ($editData['genre_name'] ?? '');
$modeEdit = $formId !== '';

$daftarGenre = $genreModel->getAllWithMovieCount();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Genre - HIMTI MOVIE</title>
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

        .layout {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 28px;
            align-items: start;
        }

        .card {
            background: #121212;
            border: 1px solid #252525;
            border-radius: 14px;
            padding: 28px;
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
        label {
            display: block;
            margin-bottom: 7px;
            color: #bbb;
            font-size: 13px;
            font-weight: bold;
        }

        input[type="text"] {
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
            margin-bottom: 18px;
        }

        input[type="text"]:focus {
            border-color: #e50914;
            box-shadow: 0 0 0 3px rgba(229, 9, 20, .15);
        }

        .form-actions {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .btn-simpan {
            background: #e50914;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            font-size: 14px;
            font-weight: bold;
            font-family: inherit;
            cursor: pointer;
            transition: .2s;
        }

        .btn-simpan:hover {
            background: #b80710;
        }

        .btn-batal {
            color: #aaa;
            font-size: 14px;
        }

        .btn-batal:hover {
            color: #e50914;
        }

        /* ===== TABLE ===== */
        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 480px;
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

        .text-center { text-align: center; }
        .text-end { text-align: right; }

        .genre-name {
            font-weight: bold;
        }

        .count-badge {
            display: inline-block;
            min-width: 34px;
            background: rgba(229, 9, 20, .12);
            border: 1px solid rgba(229, 9, 20, .4);
            color: #ff6a72;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .empty {
            text-align: center;
            color: #777;
            padding: 40px !important;
        }

        .aksi {
            white-space: nowrap;
        }

        .aksi form {
            display: inline;
        }

        .btn-edit,
        .btn-hapus {
            display: inline-block;
            font-family: inherit;
            font-size: 12px;
            font-weight: bold;
            padding: 8px 14px;
            border-radius: 7px;
            cursor: pointer;
            transition: .2s;
        }

        .btn-edit {
            background: #252525;
            border: 1px solid #404040;
            color: #fff;
        }

        .btn-edit:hover {
            background: #333;
        }

        .btn-hapus {
            background: transparent;
            border: 1px solid #e50914;
            color: #e50914;
            margin-left: 6px;
        }

        .btn-hapus:hover {
            background: #e50914;
            color: #fff;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 950px) {
            .layout { grid-template-columns: 1fr; }
        }

        @media (max-width: 850px) {
            .navbar { padding: 0 20px; }
            .nav-menu { gap: 12px; }
            .nav-menu a { font-size: 12px; }
            .nav-button { padding: 8px 12px; }
            .dash-hero { padding: 26px 22px; }
            .dash-title { font-size: 30px; }
            .card { padding: 20px; }
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
            <a href="genre.php" class="active">Genre</a>
            <a href="showtime.php">Jadwal</a>
            <a href="orders.php">Pesanan</a>
            <a href="../index.php">Website</a>
            <a href="../logout.php" class="nav-button">Logout</a>
        </div>
    </nav>

    <div class="container">

        <div class="dash-hero">
            <div class="dash-small">ADMIN PANEL</div>
            <h1 class="dash-title">Kelola <span>Genre</span></h1>
            <p class="dash-text">Atur kategori genre yang dipakai untuk mengelompokkan film di website HIMTI MOVIE.</p>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?= aman($flash['type']) ?>" role="alert">
                <span><?= aman($flash['text']) ?></span>
                <button type="button" class="alert-close" aria-label="Tutup"
                    onclick="this.parentElement.remove()">&times;</button>
            </div>
        <?php endif; ?>

        <div class="layout">

            <!-- Form tambah / edit -->
            <div class="card">
                <h2><?= $modeEdit ? 'Edit <span>Genre</span>' : 'Tambah <span>Genre</span>' ?></h2>

                <form method="post" action="genre.php">
                    <input type="hidden" name="aksi" value="simpan">
                    <input type="hidden" name="id" value="<?= aman($formId) ?>">

                    <label for="genre_name">Nama Genre</label>
                    <input type="text" id="genre_name" name="genre_name"
                        maxlength="30" required placeholder="Contoh: Horor"
                        value="<?= aman($formNama) ?>">

                    <div class="form-actions">
                        <button type="submit" class="btn-simpan">
                            <i class="bi bi-save me-1"></i> <?= $modeEdit ? 'Simpan Perubahan' : 'Tambah' ?>
                        </button>
                        <?php if ($modeEdit): ?>
                            <a href="genre.php" class="btn-batal">Batal</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Tabel genre -->
            <div class="card">
                <h2>Daftar <span>Genre</span> (<?= count($daftarGenre) ?>)</h2>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:60px">No</th>
                                <th>Nama Genre</th>
                                <th class="text-center">Jumlah Film</th>
                                <th class="text-end" style="width:190px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($daftarGenre)): ?>
                                <tr>
                                    <td colspan="4" class="empty">Belum ada genre.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($daftarGenre as $i => $g): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td class="genre-name"><?= aman($g['genre_name']) ?></td>
                                        <td class="text-center"><span class="count-badge"><?= (int) $g['total_film'] ?></span></td>
                                        <td class="text-end aksi">
                                            <a href="genre.php?edit=<?= (int) $g['genre_id'] ?>" class="btn-edit">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            <!-- Hapus memakai POST (bukan link) supaya tidak terhapus lewat klik tak sengaja/crawler -->
                                            <form method="post" action="genre.php"
                                                onsubmit="return confirm('Hapus genre &quot;<?= aman(addslashes($g['genre_name'])) ?>&quot;?<?= (int) $g['total_film'] > 0 ? ' ' . (int) $g['total_film'] . ' film akan kehilangan genre-nya.' : '' ?>');">
                                                <input type="hidden" name="aksi" value="hapus">
                                                <input type="hidden" name="id" value="<?= (int) $g['genre_id'] ?>">
                                                <button type="submit" class="btn-hapus">
                                                    <i class="bi bi-trash"></i> Hapus
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

</body>

</html>