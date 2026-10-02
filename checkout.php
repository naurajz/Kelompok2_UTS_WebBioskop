<?php
/**
 * File     : checkout.php
 * Card     : Trx-02 Checkout UI
 * Tugas    : Pilih jumlah tiket (1-6), total harga. Wajib login. Data pemesan dari session.
 * PIC      : Davientyo Arifius Putra
 * NIM      : 434251115
 * Deadline : 3 Oktober 2026
 */

// Pastikan session aktif agar data login user tersedia
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Wajib login (Trx-02): redirect ke login jika belum masuk
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Ambil showtime_id dari query string URL atau POST
$showtimeId = (int)($_GET['showtime_id'] ?? $_POST['showtime_id'] ?? 0);

// Jika belum ada parameter dari modul film, halaman tampil kosong
if ($showtimeId <= 0) {
    exit;
}

// Muat class Order (yang otomatis membawa DBConnection via BaseModel)
require_once __DIR__ . '/classes/Order.php';

// Data pemesan diambil otomatis dari sesi login (Trx-02)
$currentUserId   = (int)$_SESSION['user_id'];
$currentUserName = $_SESSION['username'] ?? 'Pengguna';
$currentUserEmail = $_SESSION['email'] ?? '';

// Buat instance Order (koneksi PostgreSQL sudah dibuat oleh BaseModel)
$orderModel = new Order();

// Ambil rincian jadwal tayang menggunakan method Order::getShowtimeInfo (Trx-02)
$showtime = $orderModel->getShowtimeInfo($showtimeId);

// Jika data jadwal dari modul film belum ada di database, tampil kosong
if (!$showtime) {
    exit;
}


// Cek sisa kuota kursi untuk jadwal ini
$quotaInfo     = $orderModel->checkQuota($showtimeId, 1);
$ticketPrice   = (float)($showtime['price'] ?? 50000);
$maxSelectable = $quotaInfo ? min(6, (int)$quotaInfo['remaining']) : 6;

// ==============================================================================
// PROSES FORM SUBMIT CHECKOUT
// ==============================================================================
$checkoutError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_checkout'])) {
    $quantity = (int)($_POST['quantity'] ?? 1);

    // Validasi jumlah tiket: 1 sampai 6 lembar (Trx-02)
    if ($quantity >= 1 && $quantity <= 6) {
        try {
            // Simpan order + tiket dalam satu transaksi atomik (Trx-01)
            $result = $orderModel->createOrderWithTickets($currentUserId, $showtimeId, $quantity);

            if ($result && isset($result['order_id'])) {
                header("Location: confirm.php?order_id=" . $result['order_id']);
                exit;
            }
        } catch (Exception $e) {
            $checkoutError = $e->getMessage();
        }
    }
}

// Judul halaman untuk header.php
$page_title = 'Checkout - ' . htmlspecialchars($showtime['movie_title'] ?? 'Bioskop');
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<div class="container py-4">

    <div class="mb-3">
        <a href="javascript:history.back()" class="btn btn-sm btn-outline-secondary">
            &larr; Kembali
        </a>
    </div>

    <h2 class="mb-4">Checkout Tiket</h2>

    <?php if ($checkoutError): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($checkoutError) ?></div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- Kolom Kiri: Info Film & Jadwal -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <?php if (!empty($showtime['movie_poster'])): ?>
                        <img src="<?= htmlspecialchars($showtime['movie_poster']) ?>"
                             alt="Poster" class="img-fluid rounded mb-3"
                             style="max-height:220px;object-fit:cover;width:100%;"
                             onerror="this.style.display='none'">
                    <?php endif; ?>

                    <h5 class="card-title"><?= htmlspecialchars($showtime['movie_title'] ?? 'Judul Film') ?></h5>
                    <p class="text-muted small mb-3">
                        <?= htmlspecialchars($showtime['genre_name'] ?? 'General') ?> &bull;
                        <?= (int)($showtime['movie_duration'] ?? 120) ?> Menit
                    </p>

                    <table class="table table-sm">
                        <tbody>
                            <tr>
                                <td class="text-muted">Studio</td>
                                <td><strong><?= htmlspecialchars($showtime['studio_name'] ?? '-') ?></strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Tanggal</td>
                                <td><strong><?= !empty($showtime['show_date']) ? date('d M Y', strtotime($showtime['show_date'])) : '-' ?></strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Jam Tayang</td>
                                <td><strong><?= !empty($showtime['show_time']) ? date('H:i', strtotime($showtime['show_time'])) . ' WIB' : '-' ?></strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Harga / Tiket</td>
                                <td><strong>Rp <?= number_format($ticketPrice, 0, ',', '.') ?></strong></td>
                            </tr>
                            <?php if ($quotaInfo): ?>
                            <tr>
                                <td class="text-muted">Sisa Kursi</td>
                                <td>
                                    <span class="badge bg-<?= $quotaInfo['remaining'] > 0 ? 'success' : 'danger' ?>">
                                        <?= $quotaInfo['remaining'] ?> tersedia
                                    </span>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Form Pemilihan Tiket -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title mb-4">Rincian Pemesanan</h5>

                    <form method="POST" action="checkout.php?showtime_id=<?= $showtimeId ?>">
                        <input type="hidden" name="showtime_id" value="<?= $showtimeId ?>">
                        <input type="hidden" name="quantity" id="inputQuantity" value="1">

                        <!-- Data Pemesan dari Sesi Login (Trx-02) -->
                        <div class="mb-3">
                            <label class="form-label text-muted small">Nama Pemesan</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($currentUserName) ?>" readonly>
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-muted small">Email</label>
                            <input type="email" class="form-control" value="<?= htmlspecialchars($currentUserEmail) ?>" readonly>
                        </div>

                        <!-- Pilih Jumlah Tiket 1–6 (Trx-02) -->
                        <div class="mb-4">
                            <label class="form-label text-muted small">Jumlah Tiket (Maks. 6 Lembar)</label>
                            <div class="d-flex gap-2 flex-wrap">
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                    <?php $disabled = ($i > $maxSelectable); ?>
                                    <button type="button"
                                            class="btn btn-outline-dark qty-btn <?= $i === 1 ? 'active btn-dark text-white' : '' ?>"
                                            data-qty="<?= $i ?>"
                                            style="width:48px;"
                                            <?= $disabled ? 'disabled' : '' ?>>
                                        <?= $i ?>
                                    </button>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <!-- Rincian Harga (dihitung JS) -->
                        <div class="bg-light rounded p-3 mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Harga / Tiket</span>
                                <span>Rp <?= number_format($ticketPrice, 0, ',', '.') ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Jumlah</span>
                                <span id="displayQty">1 Tiket</span>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between fw-bold">
                                <span>Total</span>
                                <span id="displayTotal">Rp <?= number_format($ticketPrice, 0, ',', '.') ?></span>
                            </div>
                        </div>

                        <button type="submit" name="btn_checkout" class="btn btn-dark w-100">
                            Konfirmasi &amp; Pesan
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    const unitPrice  = <?= $ticketPrice ?>;
    const inputQty   = document.getElementById('inputQuantity');
    const displayQty = document.getElementById('displayQty');
    const displayTotal = document.getElementById('displayTotal');

    document.querySelectorAll('.qty-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            if (this.disabled) return;

            document.querySelectorAll('.qty-btn').forEach(b => {
                b.classList.remove('active', 'btn-dark', 'text-white');
                b.classList.add('btn-outline-dark');
            });

            this.classList.add('active', 'btn-dark', 'text-white');
            this.classList.remove('btn-outline-dark');

            const qty   = parseInt(this.getAttribute('data-qty'), 10);
            inputQty.value        = qty;
            displayQty.textContent = qty + ' Tiket';
            displayTotal.textContent = 'Rp ' + (qty * unitPrice).toLocaleString('id-ID');
        });
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
