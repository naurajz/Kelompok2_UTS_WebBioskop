<?php
/**
 * File     : admin/movie.php
 * Card     : Movie-02 Film UI
 * Tugas    : Halaman admin kelola film: tabel + form + upload poster.
 * PIC      : (Zayyan Ahmad Dzaki W)
 * Deadline : 2 Oktober 2026
 */

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
    <title>Kelola Film</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 30px;
        }

        .container {
            max-width: 1100px;
            margin: auto;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        h1,
        h2 {
            margin-top: 0;
        }

        label {
            display: block;
            margin-top: 12px;
            margin-bottom: 5px;
        }

        input,
        textarea,
        select {
            width: 100%;
            box-sizing: border-box;
            padding: 10px;
        }

        textarea {
            min-height: 100px;
        }

        button {
            padding: 10px 15px;
            margin-top: 15px;
            cursor: pointer;
        }

        .btn-simpan {
            background: #222;
            color: white;
            border: none;
        }

        .btn-hapus {
            background: #c62828;
            color: white;
            border: none;
            margin-top: 0;
        }

        .btn-edit {
            display: inline-block;
            padding: 8px 12px;
            background: #ddd;
            color: black;
            text-decoration: none;
        }

        .pesan {
            background: #dff0d8;
            padding: 12px;
            margin-bottom: 20px;
        }

        .error {
            background: #f2dede;
            padding: 12px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #eee;
        }

        .poster {
            width: 80px;
            height: 110px;
            object-fit: cover;
        }

        .aksi {
            white-space: nowrap;
        }

        .aksi form {
            display: inline;
        }
    </style>
</head>

<body>
<div class="container">

    <h1>Kelola Film</h1>

    <?php if ($pesan !== '') : ?>
        <div class="pesan"><?= aman($pesan); ?></div>
    <?php endif; ?>

    <?php if ($error !== '') : ?>
        <div class="error"><?= aman($error); ?></div>
    <?php endif; ?>


    <!-- ========================== -->
    <!-- FORM TAMBAH / EDIT FILM -->
    <!-- ========================== -->

    <div class="card">
        <h2><?= $movieEdit ? 'Edit Film' : 'Tambah Film'; ?></h2>

        <form method="POST" enctype="multipart/form-data">

            <input type="hidden" name="aksi" value="simpan">

            <input type="hidden" name="movie_id"
                   value="<?= $movieEdit ? aman($movieEdit['movie_id']) : ''; ?>">

            <!-- TITLE -->
            <label>Judul Film</label>
            <input type="text" name="title" maxlength="150" required
                   value="<?= $movieEdit ? aman($movieEdit['title']) : ''; ?>">

            <!-- DESCRIPTION -->
            <label>Deskripsi / Sinopsis</label>
            <textarea name="description"><?= $movieEdit ? aman($movieEdit['description'] ?? '') : ''; ?></textarea>

            <!-- DURATION -->
            <label>Durasi (menit)</label>
            <input type="number" name="duration" min="1" required
                   value="<?= $movieEdit ? aman($movieEdit['duration']) : ''; ?>">

            <!-- RELEASE DATE -->
            <label>Tanggal Rilis (opsional)</label>
            <input type="date" name="release_date"
                   value="<?= $movieEdit ? aman($movieEdit['release_date'] ?? '') : ''; ?>">

            <!-- GENRE -->
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

            <!-- POSTER -->
            <label>Poster Film</label>
            <input type="file" name="poster" accept=".jpg,.jpeg,.png,.webp">

            <?php if ($movieEdit && !empty($movieEdit['poster'])) : ?>
                <p>Poster saat ini:</p>
                <img class="poster" src="../<?= aman($movieEdit['poster']); ?>" alt="Poster Film">
            <?php endif; ?>

            <br>
            <button type="submit" class="btn-simpan">
                <?= $movieEdit ? 'Simpan Perubahan' : 'Tambah Film'; ?>
            </button>

            <?php if ($movieEdit) : ?>
                <a href="movie.php">Batal Edit</a>
            <?php endif; ?>
        </form>
    </div>


    <!-- ========================== -->
    <!-- DAFTAR FILM -->
    <!-- ========================== -->

    <div class="card">
        <h2>Daftar Film</h2>

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
                    <td colspan="8" style="text-align:center;">
                        Belum ada data film.
                    </td>
                </tr>
            <?php else : ?>

                <?php foreach ($movies as $movie) : ?>
                    <tr>
                        <td><?= aman($movie['movie_id']); ?></td>

                        <td>
                            <?php if (!empty($movie['poster'])) : ?>
                                <img class="poster" src="../<?= aman($movie['poster']); ?>" alt="Poster">
                            <?php else : ?>
                                Tidak ada
                            <?php endif; ?>
                        </td>

                        <td><?= aman($movie['title']); ?></td>

                        <td><?= aman($genreMap[$movie['genre_id']] ?? '-'); ?></td>

                        <td><?= aman($movie['duration']); ?> menit</td>

                        <td><?= aman($movie['release_date'] ?? '-'); ?></td>

                        <td><?= aman($movie['description'] ?? ''); ?></td>

                        <td class="aksi">

                            <!-- EDIT -->
                            <a class="btn-edit"
                               href="movie.php?edit=<?= aman($movie['movie_id']); ?>">
                                Edit
                            </a>

                            <!-- DELETE -->
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
</body>
</html>