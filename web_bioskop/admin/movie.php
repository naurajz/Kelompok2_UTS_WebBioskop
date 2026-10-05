<?php
require_once __DIR__ . '/../includes/admin_guard.php';
require_once __DIR__ . '/../classes/Movie.php';
require_once __DIR__ . '/../classes/Genre.php';

// getAll(), getById(), delete() adalah method INSTANCE dari BaseModel,
// jadi harus dipanggil lewat object, bukan Movie::getAll().
$movieModel = new Movie();
$genreModel = new Genre();

$pesan = '';
$error = '';


// ==========================
// FUNCTION UNTUK OUTPUT AMAN
// ==========================

if (!function_exists('aman')) {
    function aman($data)
    {
        return htmlspecialchars((string) $data, ENT_QUOTES, 'UTF-8');
    }
}


// ==========================
// PROSES TAMBAH / EDIT FILM
// ==========================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['aksi'])
    && $_POST['aksi'] === 'simpan'
) {
    try {

        $movie_id = !empty($_POST['movie_id'])
            ? (int) $_POST['movie_id']
            : null;

        $title        = trim($_POST['title'] ?? '');
        $description  = trim($_POST['description'] ?? '');
        $duration     = $_POST['duration'] ?? '';
        $release_date = $_POST['release_date'] ?? '';
        $genre_id     = $_POST['genre_id'] ?? null;
        $poster       = null;


        // ----- Ambil poster lama saat edit -----
        if ($movie_id !== null) {
            $dataLama = $movieModel->getById($movie_id);

            if (!$dataLama) {
                throw new Exception('Data film tidak ditemukan.');
            }

            $poster = $dataLama['poster'] ?? null;
        }


        // ----- Upload poster -----
        if (
            isset($_FILES['poster'])
            && $_FILES['poster']['error'] === UPLOAD_ERR_OK
        ) {

            $namaFile = $_FILES['poster']['name'];
            $tmpFile  = $_FILES['poster']['tmp_name'];

            $extension = strtolower(pathinfo($namaFile, PATHINFO_EXTENSION));

            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
                throw new Exception(
                    'Poster harus berformat JPG, JPEG, PNG, atau WEBP.'
                );
            }

            $folderPoster = __DIR__ . '/../uploads/posters/';

            if (!is_dir($folderPoster)) {
                mkdir($folderPoster, 0777, true);
            }

            $namaBaru = time() . '_' . uniqid() . '.' . $extension;

            if (!move_uploaded_file($tmpFile, $folderPoster . $namaBaru)) {
                throw new Exception('Poster gagal diupload.');
            }

            $poster = 'uploads/posters/' . $namaBaru;
        }


        // ----- Buat object Movie -----
        // Urutan constructor:
        // genre_id, title, duration, description, release_date, poster, movie_id
        $movie = new Movie(
            $genre_id,
            $title,
            $duration,
            $description,
            $release_date,
            $poster,
            $movie_id
        );

        // Cek hasil save(), jangan langsung dianggap berhasil
        if (!$movie->save()) {
            throw new Exception(
                'Film gagal disimpan ke database. Pastikan genre_id ada di tabel genres.'
            );
        }

        $pesan = ($movie_id === null)
            ? 'Film berhasil ditambahkan.'
            : 'Data film berhasil diubah.';

    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}


// ==========================
// PROSES HAPUS FILM
// ==========================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['aksi'])
    && $_POST['aksi'] === 'hapus'
) {
    try {

        $movie_id = (int) ($_POST['movie_id'] ?? 0);

        if ($movie_id <= 0) {
            throw new Exception('Movie ID tidak valid.');
        }

        if (!$movieModel->getById($movie_id)) {
            throw new Exception('Data film tidak ditemukan.');
        }

        if (!$movieModel->delete($movie_id)) {
            throw new Exception(
                'Film gagal dihapus. Mungkin masih dipakai di jadwal tayang.'
            );
        }

        $pesan = 'Film berhasil dihapus.';

    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}


// ==========================
// DATA UNTUK FORM EDIT
// ==========================

$movieEdit = null;

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    try {
        $movieEdit = $movieModel->getById((int) $_GET['edit']);

        if (!$movieEdit) {
            $error = 'Data film tidak ditemukan.';
        }

    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}


// ==========================
// AMBIL DATA FILM DAN GENRE
// ==========================

$movies = [];
$genres = [];

try {
    $movies = $movieModel->getAll();
} catch (Throwable $e) {
    $error = $e->getMessage();
}

try {
    $genres = $genreModel->getAll();
} catch (Throwable $e) {
    $error = $e->getMessage();
}


// ==========================
// MAP GENRE (id => nama)
// ==========================

$genreMap = [];

foreach ($genres as $genre) {
    $genreMap[$genre['genre_id']] = $genre['genre_name'];
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Film - HIMTI MOVIE</title>
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
            font-size: 24px;
            font-weight: 900;
            margin-bottom: 22px;
        }

        .card h2 span {
            color: #e50914;
        }

        /* ===== ALERT ===== */
        .pesan,
        .error {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 22px;
            font-size: 14px;
            border: 1px solid;
        }

        .pesan {
            background: rgba(46, 160, 67, .12);
            border-color: rgba(46, 160, 67, .5);
            color: #7ee287;
        }

        .error {
            background: rgba(229, 9, 20, .12);
            border-color: rgba(229, 9, 20, .5);
            color: #ff7b82;
        }

        /* ===== FORM ===== */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px 22px;
        }

        .form-grid .full {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin: 14px 0 7px;
            color: #bbb;
            font-size: 13px;
            font-weight: bold;
        }

        input,
        textarea,
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
        }

        input:focus,
        textarea:focus,
        select:focus {
            border-color: #e50914;
            box-shadow: 0 0 0 3px rgba(229, 9, 20, .15);
        }

        input[type="file"] {
            padding: 9px;
            color: #aaa;
        }

        input[type="file"]::file-selector-button {
            background: #e50914;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 7px 14px;
            margin-right: 12px;
            cursor: pointer;
        }

        textarea {
            min-height: 110px;
            resize: vertical;
        }

        select option {
            background: #181818;
        }

        .current-poster {
            margin-top: 12px;
            color: #888;
            font-size: 12px;
        }

        .current-poster img {
            display: block;
            margin-top: 8px;
        }

        .form-actions {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-top: 24px;
        }

        .btn-simpan {
            background: #e50914;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 12px 26px;
            font-size: 14px;
            font-weight: bold;
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
            min-width: 820px;
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

        .poster {
            width: 64px;
            height: 92px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #333;
        }

        .no-poster {
            color: #666;
            font-size: 12px;
        }

        .movie-name {
            font-weight: bold;
        }

        .genre-badge {
            display: inline-block;
            background: rgba(229, 9, 20, .12);
            border: 1px solid rgba(229, 9, 20, .4);
            color: #ff6a72;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 12px;
            white-space: nowrap;
        }

        .desc {
            color: #888;
            max-width: 280px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
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
        @media (max-width: 850px) {
            .navbar { padding: 0 20px; }
            .nav-menu { gap: 12px; }
            .nav-menu a { font-size: 12px; }
            .nav-button { padding: 8px 12px; }
            .form-grid { grid-template-columns: 1fr; }
            .dash-hero { padding: 26px 22px; }
            .dash-title { font-size: 30px; }
            .card { padding: 20px; }
        }
    </style>
</head>

<body>

<!-- ===== NAVBAR ===== -->
<nav class="navbar">
    <div class="logo">HIMTI <span>MOVIE</span></div>

    <div class="nav-menu">
        <a href="dashboard_admin.php">Dashboard</a>
        <a href="movie.php" class="active">Film</a>
        <a href="../index.php">Website</a>
        <a href="../logout.php" class="nav-button">Logout</a>
    </div>
</nav>

<div class="container">

    <div class="dash-hero">
        <div class="dash-small">ADMIN PANEL</div>
        <h1 class="dash-title">Kelola <span>Film</span></h1>
        <p class="dash-text">Tambah, ubah, dan hapus data film yang tampil di website HIMTI MOVIE.</p>
    </div>

    <?php if ($pesan !== '') : ?>
        <div class="pesan"><?= aman($pesan); ?></div>
    <?php endif; ?>

    <?php if ($error !== '') : ?>
        <div class="error"><?= aman($error); ?></div>
    <?php endif; ?>


    <!-- ===== FORM TAMBAH / EDIT FILM ===== -->
    <div class="card">
        <h2><?= $movieEdit ? 'Edit <span>Film</span>' : 'Tambah <span>Film</span>'; ?></h2>

        <form method="POST" enctype="multipart/form-data">

            <input type="hidden" name="aksi" value="simpan">

            <input type="hidden" name="movie_id"
                   value="<?= $movieEdit ? aman($movieEdit['movie_id']) : ''; ?>">

            <div class="form-grid">

                <div class="full">
                    <label>Judul Film</label>
                    <input type="text" name="title" maxlength="150" required
                           value="<?= $movieEdit ? aman($movieEdit['title']) : ''; ?>">
                </div>

                <div class="full">
                    <label>Deskripsi / Sinopsis</label>
                    <textarea name="description"><?= $movieEdit ? aman($movieEdit['description'] ?? '') : ''; ?></textarea>
                </div>

                <div>
                    <label>Durasi (menit)</label>
                    <input type="number" name="duration" min="1" required
                           value="<?= $movieEdit ? aman($movieEdit['duration']) : ''; ?>">
                </div>

                <div>
                    <label>Tanggal Rilis (opsional)</label>
                    <input type="date" name="release_date"
                           value="<?= $movieEdit ? aman($movieEdit['release_date'] ?? '') : ''; ?>">
                </div>

                <div>
                    <label>Genre</label>
                    <select name="genre_id" required>
                        <option value="">Pilih Genre</option>

                        <?php foreach ($genres as $genre) : ?>
                            <option value="<?= aman($genre['genre_id']); ?>"
                                <?= ($movieEdit && $movieEdit['genre_id'] == $genre['genre_id']) ? 'selected' : ''; ?>>
                                <?= aman($genre['genre_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label>Poster Film</label>
                    <input type="file" name="poster" accept=".jpg,.jpeg,.png,.webp">
                </div>

            </div>

            <?php if ($movieEdit && !empty($movieEdit['poster'])) : ?>
                <div class="current-poster">
                    Poster saat ini:
                    <img class="poster" src="../<?= aman($movieEdit['poster']); ?>" alt="Poster Film">
                </div>
            <?php endif; ?>

            <div class="form-actions">
                <button type="submit" class="btn-simpan">
                    <?= $movieEdit ? 'Simpan Perubahan' : 'Tambah Film'; ?>
                </button>

                <?php if ($movieEdit) : ?>
                    <a href="movie.php" class="btn-batal">Batal Edit</a>
                <?php endif; ?>
            </div>
        </form>
    </div>


    <!-- ===== DAFTAR FILM ===== -->
    <div class="card">
        <h2>Daftar <span>Film</span></h2>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Poster</th>
                    <th>Judul</th>
                    <th>Genre</th>
                    <th>Durasi</th>
                    <th>Tanggal Rilis</th>
                    <th>Deskripsi</th>
                    <th>Aksi</th>
                </tr>
                </thead>
                <tbody>

                <?php if (empty($movies)) : ?>
                    <tr>
                        <td colspan="8" class="empty">Belum ada data film.</td>
                    </tr>
                <?php else : ?>

                    <?php foreach ($movies as $movie) : ?>
                        <tr>
                            <td><?= aman($movie['movie_id']); ?></td>

                            <td>
                                <?php if (!empty($movie['poster'])) : ?>
                                    <img class="poster" src="../<?= aman($movie['poster']); ?>" alt="Poster">
                                <?php else : ?>
                                    <span class="no-poster">Tidak ada</span>
                                <?php endif; ?>
                            </td>

                            <td class="movie-name"><?= aman($movie['title']); ?></td>

                            <td>
                                <span class="genre-badge">
                                    <?= aman($genreMap[$movie['genre_id']] ?? '-'); ?>
                                </span>
                            </td>

                            <td><?= aman($movie['duration']); ?> menit</td>

                            <td><?= aman($movie['release_date'] ?? '-'); ?></td>

                            <td><div class="desc"><?= aman($movie['description'] ?? ''); ?></div></td>

                            <td class="aksi">
                                <a class="btn-edit"
                                   href="movie.php?edit=<?= aman($movie['movie_id']); ?>">
                                    Edit
                                </a>

                                <form method="POST"
                                      onsubmit="return confirm('Yakin ingin menghapus film ini?');">
                                    <input type="hidden" name="aksi" value="hapus">
                                    <input type="hidden" name="movie_id"
                                           value="<?= aman($movie['movie_id']); ?>">
                                    <button type="submit" class="btn-hapus">Hapus</button>
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
</body>
</html>