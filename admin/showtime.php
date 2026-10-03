<?php
/**
 * File     : admin/showtime.php
 * Card     : Show-02 Jadwal Tayang UI
 * Tugas    : Form admin untuk menambah jadwal. Dropdown film dan studio, input tanggal, jam, harga.
 * PIC      : [Nama PIC]
 * NIM      : [NIM]
 * Deadline : [Tanggal deadline]
 */

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

$page_title = 'Kelola Jadwal Tayang';
$base_url = '../';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h4 mb-0"><i class="bi bi-calendar-event me-2"></i>Kelola Jadwal Tayang</h2>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">&larr; Dashboard</a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $isSuccess ? 'success' : 'danger' ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-header fw-bold">Tambah Jadwal</div>
        <div class="card-body">
            <form method="post" class="row g-3">
                <div class="col-md-6">
                    <label for="movie_id" class="form-label">Film</label>
                    <select name="movie_id" id="movie_id" class="form-select" required>
                        <option value="">-- Pilih film --</option>
                        <?php foreach ($movies as $m): ?>
                            <option value="<?= (int)$m['movie_id'] ?>" <?= $old['movie_id'] == $m['movie_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="studio_id" class="form-label">Studio</label>
                    <select name="studio_id" id="studio_id" class="form-select" required>
                        <option value="">-- Pilih studio --</option>
                        <?php foreach ($studios as $s): ?>
                            <option value="<?= (int)$s['studio_id'] ?>" <?= $old['studio_id'] == $s['studio_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['studio_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="show_date" class="form-label">Tanggal</label>
                    <input type="date" name="show_date" id="show_date" class="form-control"
                           min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($old['show_date']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label for="show_time" class="form-label">Jam</label>
                    <input type="time" name="show_time" id="show_time" class="form-control"
                           value="<?= htmlspecialchars($old['show_time']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label for="price" class="form-label">Harga (Rp)</label>
                    <input type="number" name="price" id="price" class="form-control" min="1000" step="1000"
                           value="<?= htmlspecialchars($old['price']) ?>" required>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-warning">Simpan Jadwal</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header fw-bold">Jadwal Saat Ini</div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Film</th><th>Studio</th><th>Waktu</th><th>Harga</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($schedules as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['movie_title']) ?></td>
                            <td><?= htmlspecialchars($row['studio_name']) ?></td>
                            <td><?= date('d M Y', strtotime($row['show_date'])) ?>, <?= date('H:i', strtotime($row['show_time'])) ?> WIB</td>
                            <td>Rp <?= number_format((float)$row['price'], 0, ',', '.') ?></td>
                            <td class="text-end">
                                <form method="post" onsubmit="return confirm('Hapus jadwal ini? Pesanan dan tiket terkait ikut terhapus.')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="showtime_id" value="<?= (int)$row['showtime_id'] ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$schedules): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">Belum ada jadwal tayang.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>