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
// Dibungkus function_exists karena classes/Genre.php ternyata sudah punya fungsi aman() juga.
if (!function_exists('aman')) {
    function aman($data)
    {
        return htmlspecialchars((string) $data, ENT_QUOTES, 'UTF-8');
    }
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
<?php
$page_title = 'Kelola Genre';
$base_url   = '../';
$body_class = 'theme-dark';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-container">

        <div class="dash-hero">
            <div class="dash-small">ADMIN PANEL</div>
            <h1 class="dash-title">Kelola <span>Genre</span></h1>
            <p class="dash-text">Atur kategori genre yang dipakai untuk mengelompokkan film di website HIMTI MOVIE.</p>
        </div>

        <?php if ($flash): ?>
            <div class="flash flash-<?= aman($flash['type']) ?>" role="alert">
                <span><?= aman($flash['text']) ?></span>
                <button type="button" class="flash-close" aria-label="Tutup"
                    onclick="this.parentElement.remove()">&times;</button>
            </div>
        <?php endif; ?>

        <div class="layout">

            <!-- Form tambah / edit -->
            <div class="panel">
                <h2><?= $modeEdit ? 'Edit <span>Genre</span>' : 'Tambah <span>Genre</span>' ?></h2>

                <form method="post" action="genre.php" class="form-stack">
                    <input type="hidden" name="aksi" value="simpan">
                    <input type="hidden" name="id" value="<?= aman($formId) ?>">

                    <label for="genre_name">Nama Genre</label>
                    <input type="text" id="genre_name" name="genre_name"
                        maxlength="30" required placeholder="Contoh: Horor"
                        value="<?= aman($formNama) ?>">

                    <div class="form-actions">
                        <button type="submit" class="btn-simpan">
                            <i class="bi bi-save"></i> <?= $modeEdit ? 'Simpan Perubahan' : 'Tambah' ?>
                        </button>
                        <?php if ($modeEdit): ?>
                            <a href="genre.php" class="btn-batal">Batal</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Tabel genre -->
            <div class="panel">
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
                                        <td class="text-center"><span class="pill pill-count"><?= (int) $g['total_film'] ?></span></td>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>