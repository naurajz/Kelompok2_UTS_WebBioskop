<?php
/**
 * File     : admin/studio.php
 * Card     : [Kode card]
 * Tugas    : Halaman admin untuk kelola studio: tambah, ubah, hapus (nama dan kapasitas).
 * PIC      : [Nama PIC]
 * NIM      : [NIM]
 * Deadline : [Tanggal deadline]
 */

require_once __DIR__ . '/../includes/admin_guard.php';

$studio = new Studio();
$message = null;
$isSuccess = false;
$editId = (int)($_GET['edit'] ?? 0);
$form = ['studio_name' => '', 'capacity' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';
    $id = (int)($_POST['studio_id'] ?? 0);

    if ($action === 'delete') {
        $isSuccess = $studio->delete($id);
        $message = $isSuccess ? 'Studio berhasil dihapus.' : 'Gagal menghapus studio.';
        $editId = 0;
    } else {
        $form = [
            'studio_name' => trim($_POST['studio_name'] ?? ''),
            'capacity'    => trim($_POST['capacity'] ?? ''),
        ];
        $isSuccess = $id ? $studio->update($id, $form) : $studio->create($form);

        if ($isSuccess) {
            $message = $id ? 'Studio berhasil diubah.' : 'Studio berhasil ditambahkan.';
            $form = ['studio_name' => '', 'capacity' => ''];
            $editId = 0;
        } else {
            $message = $studio->getError();
            $editId = $id;
        }
    }
} elseif ($editId) {
    $current = $studio->getById($editId);
    if ($current) {
        $form = ['studio_name' => $current['studio_name'], 'capacity' => $current['capacity']];
    } else {
        $editId = 0;
    }
}

$studios = $studio->getAll();

$page_title = 'Kelola Studio';
$base_url = '../';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 mb-0"><i class="bi bi-display me-2"></i>Kelola Studio</h1>
        <a href="dashboard_admin.php" class="btn btn-outline-secondary btn-sm">&larr; Dashboard</a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $isSuccess ? 'success' : 'danger' ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Form tambah / ubah (kiri) -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header fw-bold"><?= $editId ? 'Ubah Studio' : 'Tambah Studio' ?></div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="studio_id" value="<?= $editId ?>">
                        <div class="mb-3">
                            <label for="studio_name" class="form-label">Nama Studio</label>
                            <input type="text" name="studio_name" id="studio_name" class="form-control" maxlength="40"
                                   placeholder="Contoh: Studio 1"
                                   value="<?= htmlspecialchars($form['studio_name']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="capacity" class="form-label">Kapasitas (kursi)</label>
                            <input type="number" name="capacity" id="capacity" class="form-control" min="1"
                                   placeholder="Contoh: 100"
                                   value="<?= htmlspecialchars($form['capacity']) ?>" required>
                        </div>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-save me-1"></i><?= $editId ? 'Simpan' : 'Tambah' ?>
                        </button>
                        <?php if ($editId): ?>
                            <a href="studio.php" class="btn btn-outline-secondary">Batal</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <!-- Daftar studio (kanan) -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header fw-bold">Daftar Studio (<?= count($studios) ?>)</div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:60px">No</th>
                                <th>Nama Studio</th>
                                <th class="text-center">Kapasitas</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($studios as $i => $row): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= htmlspecialchars($row['studio_name']) ?></td>
                                    <td class="text-center"><?= (int)$row['capacity'] ?> kursi</td>
                                    <td class="text-end">
                                        <a href="studio.php?edit=<?= (int)$row['studio_id'] ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil"></i></a>
                                        <form method="post" class="d-inline" onsubmit="return confirm('Hapus studio ini? Jadwal, pesanan, dan tiket terkait ikut terhapus.')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="studio_id" value="<?= (int)$row['studio_id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$studios): ?>
                                <tr><td colspan="4" class="text-center text-muted py-4">Belum ada studio.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>