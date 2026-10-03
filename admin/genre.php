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
    <title>Kelola Genre - Admin Bioskop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">
 
<!-- Navbar admin -->
<nav class="navbar navbar-expand navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand text-warning" href="../index.php"><i class="bi bi-film me-2"></i>Admin Bioskop</a>
        <ul class="navbar-nav me-auto">
            <li class="nav-item"><a class="nav-link" href="movie.php">Film</a></li>
            <li class="nav-item"><a class="nav-link active" href="genre.php">Genre</a></li>
            <li class="nav-item"><a class="nav-link" href="showtime.php">Jadwal</a></li>
            <li class="nav-item"><a class="nav-link" href="orders.php">Pesanan</a></li>
        </ul>
        <a class="btn btn-outline-light btn-sm" href="../index.php">Ke Beranda</a>
    </div>
</nav>
 
<div class="container pb-5">
    <h1 class="h3 mb-4"><i class="bi bi-tags me-2"></i>Kelola Genre</h1>
 
    <?php if ($flash): ?>
        <div class="alert alert-<?= aman($flash['type']) ?> alert-dismissible fade show" role="alert">
            <?= aman($flash['text']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    <?php endif; ?>
 
    <div class="row g-4">
        <!-- Form tambah / edit -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">
                    <?= $modeEdit ? 'Edit Genre' : 'Tambah Genre' ?>
                </div>
                <div class="card-body">
                    <form method="post" action="genre.php">
                        <input type="hidden" name="aksi" value="simpan">
                        <input type="hidden" name="id" value="<?= aman($formId) ?>">
 
                        <label for="genre_name" class="form-label">Nama Genre</label>
                        <input type="text" class="form-control mb-3" id="genre_name" name="genre_name"
                               maxlength="30" required placeholder="Contoh: Horor"
                               value="<?= aman($formNama) ?>">
 
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-save me-1"></i><?= $modeEdit ? 'Simpan Perubahan' : 'Tambah' ?>
                            </button>
                            <?php if ($modeEdit): ?>
                                <a href="genre.php" class="btn btn-outline-secondary">Batal</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
 
        <!-- Tabel genre -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">Daftar Genre (<?= count($daftarGenre) ?>)</div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:60px">No</th>
                                <th>Nama Genre</th>
                                <th class="text-center">Jumlah Film</th>
                                <th class="text-end" style="width:180px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($daftarGenre)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">Belum ada genre.</td></tr>
                        <?php else: ?>
                            <?php foreach ($daftarGenre as $i => $g): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= aman($g['genre_name']) ?></td>
                                    <td class="text-center"><span class="badge bg-secondary"><?= (int) $g['total_film'] ?></span></td>
                                    <td class="text-end">
                                        <a href="genre.php?edit=<?= (int) $g['genre_id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <!-- Hapus memakai POST (bukan link) supaya tidak terhapus lewat klik tak sengaja/crawler -->
                                        <form method="post" action="genre.php" class="d-inline"
                                              onsubmit="return confirm('Hapus genre &quot;<?= aman(addslashes($g['genre_name'])) ?>&quot;?<?= (int) $g['total_film'] > 0 ? ' ' . (int) $g['total_film'] . ' film akan kehilangan genre-nya.' : '' ?>');">
                                            <input type="hidden" name="aksi" value="hapus">
                                            <input type="hidden" name="id" value="<?= (int) $g['genre_id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
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
</div>
 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
 
