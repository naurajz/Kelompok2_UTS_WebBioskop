<?php
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
$base_url   = '../';
$body_class = 'theme-dark';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-container">

    <div class="dash-hero">
        <div class="dash-small">ADMIN PANEL</div>
        <h1 class="dash-title">Kelola <span>Studio</span></h1>
        <p class="dash-text">Atur nama dan kapasitas kursi setiap studio yang dipakai untuk jadwal tayang.</p>
    </div>

    <?php if ($message): ?>
        <div class="flash flash-<?= $isSuccess ? 'success' : 'danger' ?>" role="alert">
            <span><?= htmlspecialchars($message) ?></span>
            <button type="button" class="flash-close" aria-label="Tutup"
                onclick="this.parentElement.remove()">&times;</button>
        </div>
    <?php endif; ?>

    <div class="layout">

        <!-- Form tambah / ubah (kiri) -->
        <div class="panel">
            <h2><?= $editId ? 'Ubah <span>Studio</span>' : 'Tambah <span>Studio</span>' ?></h2>

            <form method="post" class="form-stack">
                <input type="hidden" name="studio_id" value="<?= $editId ?>">

                <label for="studio_name">Nama Studio</label>
                <input type="text" name="studio_name" id="studio_name" maxlength="40"
                       placeholder="Contoh: Studio 1"
                       value="<?= htmlspecialchars($form['studio_name']) ?>" required>

                <label for="capacity">Kapasitas (kursi)</label>
                <input type="number" name="capacity" id="capacity" min="1"
                       placeholder="Contoh: 100"
                       value="<?= htmlspecialchars($form['capacity']) ?>" required>

                <div class="form-actions">
                    <button type="submit" class="btn-simpan">
                        <i class="bi bi-save"></i> <?= $editId ? 'Simpan Perubahan' : 'Tambah' ?>
                    </button>
                    <?php if ($editId): ?>
                        <a href="studio.php" class="btn-batal">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Daftar studio (kanan) -->
        <div class="panel">
            <h2>Daftar <span>Studio</span> (<?= count($studios) ?>)</h2>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th style="width:60px">No</th>
                            <th>Nama Studio</th>
                            <th class="text-center">Kapasitas</th>
                            <th class="text-end" style="width:190px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($studios as $i => $row): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td class="genre-name"><?= htmlspecialchars($row['studio_name']) ?></td>
                                <td class="text-center"><span class="pill pill-count"><?= (int)$row['capacity'] ?> kursi</span></td>
                                <td class="text-end aksi">
                                    <a href="studio.php?edit=<?= (int)$row['studio_id'] ?>" class="btn-edit">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <form method="post" onsubmit="return confirm('Hapus studio ini? Jadwal, pesanan, dan tiket terkait ikut terhapus.')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="studio_id" value="<?= (int)$row['studio_id'] ?>">
                                        <button type="submit" class="btn-hapus">
                                            <i class="bi bi-trash"></i> Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$studios): ?>
                            <tr><td colspan="4" class="empty">Belum ada studio.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>