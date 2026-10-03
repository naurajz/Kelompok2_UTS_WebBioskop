<?php
/**
 * File     : admin/movie.php
 * Card     : Movie-02 Film UI
 * Tugas    : Halaman admin kelola film: tabel + form + upload poster.
 * PIC      : (Zayyan Ahmad Dzaki W)
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

// getAll(), getById(), delete() adalah method INSTANCE dari BaseModel,
// jadi harus dipanggil lewat object, bukan Movie::getAll().
$movieModel = new Movie();


// =========================
// FUNCTION SEDERHANA
// =========================

if (!function_exists('aman')) {
    function aman($data)
    {
        return htmlspecialchars((string) $data, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('ambilGenre')) {
    function ambilGenre()
    {
        if (class_exists('Genre')) {
            try {
                return (new Genre())->getAll();
            } catch (Throwable $e) {
                return [];
            }
        }
        return [];
    }
}

if (!function_exists('idGenre')) {
    function idGenre($genre)
    {
        return $genre['genre_id'] ?? $genre['id'] ?? $genre['id_genre'] ?? null;
    }
}

if (!function_exists('namaGenre')) {
    function namaGenre($genre)
    {
        return $genre['genre_name']
            ?? $genre['name']
            ?? $genre['nama']
            ?? $genre['nama_genre']
            ?? 'Genre';
    }
}


// =========================
// PROSES FORM
// =========================

try {

    // ---------- TAMBAH / UPDATE ----------
    if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && ($_POST['aksi'] ?? '') === 'simpan') {

        $id = !empty($_POST['id']) ? (int) $_POST['id'] : null;

        $judul    = trim($_POST['judul'] ?? '');
        $sinopsis = trim($_POST['sinopsis'] ?? '');
        $durasi   = $_POST['durasi'] ?? '';
        $genreId  = $_POST['genre_id'] ?? '';
        $tanggal  = trim($_POST['tanggal_rilis'] ?? '');
        $poster   = null;

        // ----- Upload poster -----
        if (isset($_FILES['poster'])
            && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {

            $ext = strtolower(pathinfo($_FILES['poster']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                throw new Exception('Poster harus berformat JPG, JPEG, PNG, atau WEBP.');
            }

            $folderPoster = __DIR__ . '/../uploads/posters/';
            if (!is_dir($folderPoster)) {
                mkdir($folderPoster, 0777, true);
            }

            $namaBaru = time() . '_' . uniqid() . '.' . $ext;

            if (!move_uploaded_file($_FILES['poster']['tmp_name'], $folderPoster . $namaBaru)) {
                throw new Exception('Poster gagal diupload.');
            }

            $poster = 'uploads/posters/' . $namaBaru;
        }

        // Urutan constructor:
        // genre_id, title, duration, description, release_date, poster, movie_id
        if ($id !== null) {

            $lama = $movieModel->getById($id);
            if (!$lama) {
                throw new Exception('Data film tidak ditemukan.');
            }

            // Tidak upload poster baru = pakai poster lama
            if ($poster === null) {
                $poster = $lama['poster'];
            }

            $movie = new Movie($genreId, $judul, $durasi, $sinopsis, $tanggal, $poster, $id);
            $pesanSukses = 'Data film berhasil diubah.';

        } else {

            $movie = new Movie($genreId, $judul, $durasi, $sinopsis, $tanggal, $poster);
            $pesanSukses = 'Film berhasil ditambahkan.';
        }

        // Cek hasil save(), jangan langsung dianggap berhasil
        if (!$movie->save()) {
            throw new Exception(
                'Film gagal disimpan ke database. Pastikan genre_id ada di tabel genres.'
            );
        }

        $pesan = $pesanSukses;
    }


    // ---------- HAPUS ----------
    if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && ($_POST['aksi'] ?? '') === 'hapus') {

        $id = (int) ($_POST['id'] ?? 0);

        if (!$movieModel->getById($id)) {
            throw new Exception('Data film tidak ditemukan.');
        }

        if (!$movieModel->delete($id)) {
            throw new Exception(
                'Film gagal dihapus. Mungkin masih dipakai di jadwal tayang.'
            );
        }

        $pesan = 'Film berhasil dihapus.';
    }

} catch (Throwable $e) {
    $error = $e->getMessage();
}


// =========================
// DATA EDIT (berupa array dari database)
// =========================

$movieEdit = null;

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $movieEdit = $movieModel->getById((int) $_GET['edit']);
}


// =========================
// AMBIL SEMUA DATA
// =========================

$movies = $movieModel->getAll();
$genres = ambilGenre();

// Peta id genre => nama genre, supaya tabel menampilkan nama
$petaGenre = [];
foreach ($genres as $g) {
    $petaGenre[idGenre($g)] = namaGenre($g);
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Film</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 30px; }
        .container { max-width: 1100px; margin: auto; }
        h1 { margin-bottom: 20px; }
        .card { background: white; padding: 20px; margin-bottom: 25px; border-radius: 8px; }
        label { display: block; margin-top: 12px; margin-bottom: 5px; }
        input, textarea, select { width: 100%; padding: 10px; box-sizing: border-box; }
        textarea { min-height: 100px; }
        button { margin-top: 15px; padding: 10px 18px; cursor: pointer; }
        .btn-simpan { background: #222; color: white; border: none; }
        .btn-hapus { background: #c62828; color: white; border: none; margin-top: 0; }
        .btn-edit { display: inline-block; padding: 8px 12px; text-decoration: none; background: #e0e0e0; color: black; }
        .pesan { background: #dff0d8; padding: 12px; margin-bottom: 20px; }
        .error { background: #f2dede; padding: 12px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #eeeeee; }
        .poster { width: 80px; height: 110px; object-fit: cover; }
        .aksi { white-space: nowrap; }
        .aksi form { display: inline; }
    </style>
</head>
<body>
<div class="container">

    <h1>Kelola Film</h1>

    <?php if ($pesan != '') : ?>
        <div class="pesan"><?= aman($pesan); ?></div>
    <?php endif; ?>

    <?php if ($error != '') : ?>
        <div class="error"><?= aman($error); ?></div>
    <?php endif; ?>


    <!-- FORM TAMBAH / EDIT FILM -->
    <div class="card">
        <h2><?= $movieEdit ? 'Edit Film' : 'Tambah Film'; ?></h2>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="aksi" value="simpan">
            <input type="hidden" name="id" value="<?= $movieEdit ? aman($movieEdit['movie_id']) : ''; ?>">

            <label>Judul Film</label>
            <input type="text" name="judul" required maxlength="150"
                   value="<?= $movieEdit ? aman($movieEdit['title']) : ''; ?>">

            <label>Sinopsis</label>
            <textarea name="sinopsis"><?= $movieEdit ? aman($movieEdit['description']) : ''; ?></textarea>

            <label>Durasi (menit)</label>
            <input type="number" name="durasi" min="1" required
                   value="<?= $movieEdit ? aman($movieEdit['duration']) : ''; ?>">

            <label>Tanggal Rilis (opsional)</label>
            <input type="date" name="tanggal_rilis"
                   value="<?= $movieEdit ? aman($movieEdit['release_date']) : ''; ?>">

            <label>Genre</label>
            <?php if (!empty($genres)) : ?>
                <select name="genre_id" required>
                    <option value="">Pilih Genre</option>
                    <?php foreach ($genres as $genre) : ?>
                        <?php $gid = idGenre($genre); ?>
                        <option value="<?= aman($gid); ?>"
                            <?= ($movieEdit && $movieEdit['genre_id'] == $gid) ? 'selected' : ''; ?>>
                            <?= aman(namaGenre($genre)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php else : ?>
                <!-- Dipakai kalau daftar genre belum bisa dimuat -->
                <input type="number" name="genre_id" min="1" required
                       placeholder="Masukkan ID genre (harus sudah ada di tabel genres)"
                       value="<?= $movieEdit ? aman($movieEdit['genre_id']) : ''; ?>">
            <?php endif; ?>

            <label>Poster Film</label>
            <input type="file" name="poster" accept=".jpg,.jpeg,.png,.webp">

            <?php if ($movieEdit && $movieEdit['poster']) : ?>
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


    <!-- DAFTAR FILM -->
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
                    <td colspan="7" style="text-align:center;">Belum ada data film.</td>
                </tr>
            <?php else : ?>
                <?php foreach ($movies as $m) : ?>
                    <?php
                    $mid   = $m['movie_id'] ?? '';
                    $gid   = $m['genre_id'] ?? '';
                    $gnama = $petaGenre[$gid] ?? ($gid !== null && $gid !== '' ? 'Genre #' . $gid : '-');
                    ?>
                    <tr>
                        <td><?= aman($mid); ?></td>
                        <td>
                            <?php if (!empty($m['poster'])) : ?>
                                <img class="poster" src="../<?= aman($m['poster']); ?>" alt="Poster">
                            <?php else : ?>
                                Tidak ada
                            <?php endif; ?>
                        </td>
                        <td><?= aman($m['title'] ?? ''); ?></td>
                        <td><?= aman($gnama); ?></td>
                        <td><?= aman($m['duration'] ?? ''); ?> menit</td>
                        <td><?= aman($m['description'] ?? ''); ?></td>
                        <td class="aksi">
                            <a class="btn-edit" href="movie.php?edit=<?= aman($mid); ?>">Edit</a>

                            <form method="POST"
                                  onsubmit="return confirm('Yakin ingin menghapus film ini?');">
                                <input type="hidden" name="aksi" value="hapus">
                                <input type="hidden" name="id" value="<?= aman($mid); ?>">
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