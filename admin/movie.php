<?php
/**
 * File     : admin/movie.php
 * Card     : Movie-02 Film UI
 * Tugas    : Halaman admin kelola film: tabel + form + upload poster.
 * PIC      : (isi nama)
 * Deadline : 2 Oktober 2026
 */

require_once __DIR__ . '/../classes/Movie.php';

// Genre.php dipakai kalau class Genre sudah dibuat oleh anggota lain
$genreFile = __DIR__ . '/../classes/Genre.php';

if (file_exists($genreFile)) {
    require_once $genreFile;
}

$pesan = '';
$error = '';


// =========================
// FUNCTION SEDERHANA
// =========================

// Mencegah karakter HTML bermasalah saat ditampilkan
function aman($data)
{
    return htmlspecialchars((string) $data, ENT_QUOTES, 'UTF-8');
}


// Mengambil daftar genre kalau class Genre sudah tersedia
function ambilGenre()
{
    if (class_exists('Genre') && method_exists('Genre', 'getAll')) {
        try {
            return Genre::getAll();
        } catch (Throwable $e) {
            return [];
        }
    }

    return [];
}


// Mengambil ID genre dari beberapa kemungkinan nama kolom
function idGenre($genre)
{
    return $genre['id']
        ?? $genre['genre_id']
        ?? $genre['id_genre']
        ?? null;
}


// Mengambil nama genre
function namaGenre($genre)
{
    return $genre['name']
        ?? $genre['nama']
        ?? $genre['genre_name']
        ?? $genre['nama_genre']
        ?? 'Genre';
}


// =========================
// PROSES FORM
// =========================

try {

    // =========================
    // TAMBAH / UPDATE FILM
    // =========================

    if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && isset($_POST['aksi'])
        && $_POST['aksi'] === 'simpan') {

        $id = !empty($_POST['id'])
            ? (int) $_POST['id']
            : null;

        $judul = trim($_POST['judul'] ?? '');
        $sinopsis = trim($_POST['sinopsis'] ?? '');
        $durasi = $_POST['durasi'] ?? '';
        $genreId = $_POST['genre_id'] ?? '';

        $poster = null;


        // =========================
        // UPLOAD POSTER
        // =========================

        if (
            isset($_FILES['poster'])
            && $_FILES['poster']['error'] === UPLOAD_ERR_OK
        ) {

            $namaFile = $_FILES['poster']['name'];
            $tmpFile = $_FILES['poster']['tmp_name'];

            $ext = strtolower(
                pathinfo($namaFile, PATHINFO_EXTENSION)
            );

            $extDiizinkan = [
                'jpg',
                'jpeg',
                'png',
                'webp'
            ];

            if (!in_array($ext, $extDiizinkan)) {
                throw new Exception(
                    'Poster harus berformat JPG, JPEG, PNG, atau WEBP.'
                );
            }

            // Folder penyimpanan poster
            $folderPoster = __DIR__ . '/../uploads/posters/';

            // Buat folder kalau belum tersedia
            if (!is_dir($folderPoster)) {
                mkdir(
                    $folderPoster,
                    0777,
                    true
                );
            }

            // Nama file dibuat unik
            $namaBaru = time()
                . '_'
                . uniqid()
                . '.'
                . $ext;

            $tujuan = $folderPoster . $namaBaru;

            if (!move_uploaded_file($tmpFile, $tujuan)) {
                throw new Exception(
                    'Poster gagal diupload.'
                );
            }

            // Yang disimpan ke database cukup path-nya
            $poster = 'uploads/posters/' . $namaBaru;
        }


        // =========================
        // UPDATE
        // =========================

        if ($id !== null) {

            $movie = Movie::findById($id);

            if (!$movie) {
                throw new Exception(
                    'Data film tidak ditemukan.'
                );
            }

            $movie->setGenreId($genreId);
            $movie->setJudul($judul);
            $movie->setSinopsis($sinopsis);
            $movie->setDurasi($durasi);

            // Kalau upload poster baru, ganti poster
            if ($poster !== null) {
                $movie->setPoster($poster);
            }

            $movie->save();

            $pesan = 'Data film berhasil diubah.';


        // =========================
        // CREATE
        // =========================

        } else {

            $movie = new Movie(
                $genreId,
                $judul,
                $durasi,
                $sinopsis,
                $poster
            );

            $movie->save();

            $pesan = 'Film berhasil ditambahkan.';
        }
    }


    // =========================
    // DELETE FILM
    // =========================

    if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && isset($_POST['aksi'])
        && $_POST['aksi'] === 'hapus') {

        $id = (int) ($_POST['id'] ?? 0);

        $movie = Movie::findById($id);

        if (!$movie) {
            throw new Exception(
                'Data film tidak ditemukan.'
            );
        }

        $movie->delete();

        $pesan = 'Film berhasil dihapus.';
    }

} catch (Throwable $e) {

    $error = $e->getMessage();
}


// =========================
// DATA EDIT
// =========================

$movieEdit = null;

if (
    isset($_GET['edit'])
    && is_numeric($_GET['edit'])
) {

    try {

        $movieEdit = Movie::findById(
            (int) $_GET['edit']
        );

    } catch (Throwable $e) {

        $error = $e->getMessage();
    }
}


// =========================
// AMBIL SEMUA DATA
// =========================

$movies = [];
$genres = [];

try {

    $movies = Movie::getAll();

} catch (Throwable $e) {

    // Kalau database teman belum selesai,
    // halaman tetap bisa dibuka
    $movies = [];
}

$genres = ambilGenre();

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

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

        h1 {
            margin-bottom: 20px;
        }

        .card {
            background: white;
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 8px;
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
            padding: 10px;
            box-sizing: border-box;
        }

        textarea {
            min-height: 100px;
        }

        button {
            margin-top: 15px;
            padding: 10px 18px;
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
        }

        .btn-edit {
            display: inline-block;
            padding: 8px 12px;
            text-decoration: none;
            background: #e0e0e0;
            color: black;
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
            background: white;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        th {
            background: #eeeeee;
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


    <?php if ($pesan != '') : ?>

        <div class="pesan">

            <?= aman($pesan); ?>

        </div>

    <?php endif; ?>


    <?php if ($error != '') : ?>

        <div class="error">

            <?= aman($error); ?>

        </div>

    <?php endif; ?>


    <!-- ========================= -->
    <!-- FORM TAMBAH / EDIT FILM -->
    <!-- ========================= -->

    <div class="card">

        <h2>
            <?= $movieEdit
                ? 'Edit Film'
                : 'Tambah Film'; ?>
        </h2>


        <form
            method="POST"
            enctype="multipart/form-data"
        >

            <input
                type="hidden"
                name="aksi"
                value="simpan"
            >


            <input
                type="hidden"
                name="id"
                value="<?= $movieEdit
                    ? aman($movieEdit->getId())
                    : ''; ?>"
            >


            <!-- Judul Film -->

            <label>Judul Film</label>

            <input
                type="text"
                name="judul"
                required
                value="<?= $movieEdit
                    ? aman($movieEdit->getJudul())
                    : ''; ?>"
            >


            <!-- Sinopsis -->

            <label>Sinopsis</label>

            <textarea
                name="sinopsis"
            ><?= $movieEdit
                ? aman($movieEdit->getSinopsis())
                : ''; ?></textarea>


            <!-- Durasi -->

            <label>Durasi (menit)</label>

            <input
                type="number"
                name="durasi"
                min="1"
                required
                value="<?= $movieEdit
                    ? aman($movieEdit->getDurasi())
                    : ''; ?>"
            >


            <!-- Genre -->

            <label>Genre</label>


            <?php if (!empty($genres)) : ?>

                <select
                    name="genre_id"
                    required
                >

                    <option value="">
                        Pilih Genre
                    </option>


                    <?php foreach ($genres as $genre) : ?>

                        <?php
                        $genreId = idGenre($genre);
                        $genreNama = namaGenre($genre);
                        ?>

                        <option
                            value="<?= aman($genreId); ?>"

                            <?php
                            if (
                                $movieEdit
                                && $movieEdit->getGenreId()
                                    == $genreId
                            ) {
                                echo 'selected';
                            }
                            ?>
                        >

                            <?= aman($genreNama); ?>

                        </option>

                    <?php endforeach; ?>

                </select>


            <?php else : ?>

                <!--
                Sementara dipakai kalau Genre.php
                teman belum selesai.
                -->

                <input
                    type="number"
                    name="genre_id"
                    min="1"
                    required
                    placeholder="Masukkan ID genre"
                    value="<?= $movieEdit
                        ? aman($movieEdit->getGenreId())
                        : ''; ?>"
                >

            <?php endif; ?>


            <!-- Poster -->

            <label>Poster Film</label>

            <input
                type="file"
                name="poster"
                accept=".jpg,.jpeg,.png,.webp"
            >


            <?php if (
                $movieEdit
                && $movieEdit->getPoster()
            ) : ?>

                <p>Poster saat ini:</p>

                <img
                    class="poster"
                    src="../<?= aman(
                        $movieEdit->getPoster()
                    ); ?>"
                    alt="Poster Film"
                >

            <?php endif; ?>


            <br>


            <button
                type="submit"
                class="btn-simpan"
            >

                <?= $movieEdit
                    ? 'Simpan Perubahan'
                    : 'Tambah Film'; ?>

            </button>


            <?php if ($movieEdit) : ?>

                <a href="movie.php">
                    Batal Edit
                </a>

            <?php endif; ?>

        </form>

    </div>


    <!-- ========================= -->
    <!-- DAFTAR FILM -->
    <!-- ========================= -->

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
                <th>Sinopsis</th>
                <th>Aksi</th>

            </tr>

            </thead>


            <tbody>


            <?php if (empty($movies)) : ?>

                <tr>

                    <td
                        colspan="7"
                        style="text-align:center;"
                    >

                        Belum ada data film.

                    </td>

                </tr>


            <?php else : ?>


                <?php foreach ($movies as $movie) : ?>

                    <?php

                    $id = $movie['id'] ?? '';
                    $judul = $movie['title']
                        ?? $movie['judul']
                        ?? '';

                    $sinopsis = $movie['synopsis']
                        ?? $movie['sinopsis']
                        ?? '';

                    $durasi = $movie['duration']
                        ?? $movie['durasi']
                        ?? '';

                    $poster = $movie['poster']
                        ?? null;

                    $genreId = $movie['genre_id']
                        ?? $movie['id_genre']
                        ?? '';

                    ?>


                    <tr>

                        <td>
                            <?= aman($id); ?>
                        </td>


                        <td>

                            <?php if ($poster) : ?>

                                <img
                                    class="poster"
                                    src="../<?= aman($poster); ?>"
                                    alt="Poster"
                                >

                            <?php else : ?>

                                Tidak ada

                            <?php endif; ?>

                        </td>


                        <td>
                            <?= aman($judul); ?>
                        </td>


                        <td>
                            <?= aman($genreId); ?>
                        </td>


                        <td>
                            <?= aman($durasi); ?>
                            menit
                        </td>


                        <td>
                            <?= aman($sinopsis); ?>
                        </td>


                        <td class="aksi">

                            <!-- Tombol Edit -->

                            <a
                                class="btn-edit"
                                href="movie.php?edit=<?= aman(
                                    $id
                                ); ?>"
                            >
                                Edit
                            </a>


                            <!-- Tombol Hapus -->

                            <form
                                method="POST"
                                onsubmit="
                                    return confirm(
                                        'Yakin ingin menghapus film ini?'
                                    );
                                "
                            >

                                <input
                                    type="hidden"
                                    name="aksi"
                                    value="hapus"
                                >

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= aman($id); ?>"
                                >

                                <button
                                    type="submit"
                                    class="btn-hapus"
                                >
                                    Hapus
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

</body>

</html>