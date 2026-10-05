<?php
require_once __DIR__ . '/../includes/admin_guard.php';

$showtime = new Showtime();
$message = null;
$isSuccess = false;
$old = ['movie_id' => '', 'studio_id' => '', 'show_date' => '', 'show_time' => '', 'price' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') { //
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
<?php
$page_title = 'Kelola Jadwal Tayang';
$base_url   = '../';
$body_class = 'theme-dark';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-container">

        <div class="dash-hero">
            <div class="dash-small">ADMIN PANEL</div>
            <h1 class="dash-title">Kelola <span>Jadwal Tayang</span></h1>
            <p class="dash-text">Tentukan film, studio, tanggal, jam, dan harga tiket untuk setiap sesi tayang.</p>
        </div>

        <?php if ($message): ?>
            <div class="flash flash-<?= $isSuccess ? 'success' : 'danger' ?>" role="alert">
                <span><?= htmlspecialchars($message) ?></span>
                <button type="button" class="flash-close" aria-label="Tutup"
                    onclick="this.parentElement.remove()">&times;</button>
            </div>
        <?php endif; ?>
       
        <div class="panel">
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
        <div class="panel">
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
                                <td><span class="pill"><?= htmlspecialchars($row['studio_name']) ?></span></td>
                                <td class="waktu"><?= date('d M Y', strtotime($row['show_date'])) ?>, <?= date('H:i', strtotime($row['show_time'])) ?> WIB</td>
                                <td class="td-price">Rp <?= number_format((float)$row['price'], 0, ',', '.') ?></td>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>