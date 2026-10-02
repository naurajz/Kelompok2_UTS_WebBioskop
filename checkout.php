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
$currentUserId    = (int)$_SESSION['user_id'];
$currentUserName  = $_SESSION['username'] ?? 'Pengguna';
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
$page_title = 'Checkout - ' . ($showtime['movie_title'] ?? 'Bioskop');
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<style>
    body { background-color: #141414 !important; color: #e5e5e5 !important; }
    .main-content { background-color: #141414; }

    .co-card {
        background-color: #1e1e1e;
        border: 1px solid #2a2a2a;
        border-radius: 10px;
    }
    .co-label { color: #888; font-size: 0.8rem; margin-bottom: 4px; }

    .form-control, .form-control:focus {
        background-color: #2a2a2a !important;
        border: 1px solid #3a3a3a !important;
        color: #e5e5e5 !important;
        box-shadow: none !important;
    }
    .form-control[readonly] { opacity: 0.65; }

    .table { color: #ccc; --bs-table-bg: transparent; }
    .table td { border-color: #2a2a2a; }

    .qty-btn {
        width: 44px; height: 44px;
        border: 1px solid #3a3a3a;
        background-color: #2a2a2a;
        color: #ccc;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        transition: all .15s;
    }
    .qty-btn.active {
        background-color: #e50914;
        border-color: #e50914;
        color: #fff;
    }
    .qty-btn:disabled { opacity: 0.3; cursor: not-allowed; }
    .qty-btn:not(.active):not(:disabled):hover {
        border-color: #e50914;
        color: #e50914;
    }

    .price-box {
        background-color: #111;
        border: 1px solid #2a2a2a;
        border-radius: 8px;
        padding: 1rem 1.25rem;
    }
    .price-box hr { border-color: #333; }
    .total-val { color: #e50914; font-size: 1.05rem; }

    .btn-pesan {
        background-color: #e50914;
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 12px;
        font-weight: 700;
        width: 100%;
        transition: background .2s;
        cursor: pointer;
    }
    .btn-pesan:hover { background-color: #c1070f; }

    .btn-back-link { color: #888; text-decoration: none; font-size: .875rem; }
    .btn-back-link:hover { color: #e50914; }

    .page-heading { color: #fff; font-weight: 700; }
    .co-title { color: #fff; }
    .text-genre { color: #888; font-size: .85rem; }
    .alert-danger { background-color: #2d0707; border-color: #7a1010; color: #f88; border-radius: 8px; }
</style>

<div class="container py-4">

    <div class="mb-3">
        <a href="javascript:history.back()" class="btn-back-link">&larr; Kembali</a>
    </div>

    <h2 class="page-heading mb-4">Checkout Tiket</h2>

    <?php if ($checkoutError): ?>
        <div class="alert alert-danger mb-4"><?= htmlspecialchars($checkoutError) ?></div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- Kolom Kiri: Info Film & Jadwal -->
        <div class="col-md-6">
            <div class="co-card h-100 p-3">
                <?php if (!empty($showtime['movie_poster'])): ?>
                    <img src="<?= htmlspecialchars($showtime['movie_poster']) ?>"
                         alt="Poster" class="img-fluid rounded mb-3"
                         style="max-height:200px;object-fit:cover;width:100%;"
                         onerror="this.style.display='none'">
                <?php endif; ?>

                <h5 class="co-title fw-bold mb-1"><?= htmlspecialchars($showtime['movie_title'] ?? 'Judul Film') ?></h5>
                <p class="text-genre mb-3">
                    <?= htmlspecialchars($showtime['genre_name'] ?? 'General') ?> &bull;
                    <?= (int)($showtime['movie_duration'] ?? 120) ?> Menit
                </p>

                <table class="table table-sm mb-0">
                    <tbody>
                        <tr>
                            <td class="co-label">Studio</td>
                            <td class="fw-semibold"><?= htmlspecialchars($showtime['studio_name'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td class="co-label">Tanggal</td>
                            <td class="fw-semibold"><?= !empty($showtime['show_date']) ? date('d M Y', strtotime($showtime['show_date'])) : '-' ?></td>
                        </tr>
                        <tr>
                            <td class="co-label">Jam Tayang</td>
                            <td class="fw-semibold"><?= !empty($showtime['show_time']) ? date('H:i', strtotime($showtime['show_time'])) . ' WIB' : '-' ?></td>
                        </tr>
                        <tr>
                            <td class="co-label">Harga / Tiket</td>
                            <td class="fw-semibold" style="color:#e50914;">Rp <?= number_format($ticketPrice, 0, ',', '.') ?></td>
                        </tr>
                        <?php if ($quotaInfo): ?>
                        <tr>
                            <td class="co-label">Sisa Kursi</td>
                            <td>
                                <span style="font-size:.8rem;padding:2px 8px;border-radius:4px;background:<?= $quotaInfo['remaining'] > 0 ? '#1a3d22' : '#3d1a1a' ?>;color:<?= $quotaInfo['remaining'] > 0 ? '#4caf50' : '#f44336' ?>;">
                                    <?= $quotaInfo['remaining'] ?> tersedia
                                </span>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Kolom Kanan: Form Pemilihan Tiket -->
        <div class="col-md-6">
            <div class="co-card h-100 p-3">
                <h5 class="co-title fw-bold mb-4">Rincian Pemesanan</h5>

                <form method="POST" action="checkout.php?showtime_id=<?= $showtimeId ?>">
                    <input type="hidden" name="showtime_id" value="<?= $showtimeId ?>">
                    <input type="hidden" name="quantity" id="inputQuantity" value="1">

                    <div class="mb-3">
                        <div class="co-label">Nama Pemesan</div>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($currentUserName) ?>" readonly>
                    </div>
                    <div class="mb-4">
                        <div class="co-label">Email</div>
                        <input type="email" class="form-control" value="<?= htmlspecialchars($currentUserEmail) ?>" readonly>
                    </div>

                    <!-- Pilih Jumlah Tiket 1–6 (Trx-02) -->
                    <div class="mb-4">
                        <div class="co-label mb-2">Jumlah Tiket (Maks. 6 Lembar)</div>
                        <div class="d-flex gap-2 flex-wrap">
                            <?php for ($i = 1; $i <= 6; $i++): ?>
                                <?php $disabled = ($i > $maxSelectable); ?>
                                <button type="button"
                                        class="qty-btn <?= $i === 1 ? 'active' : '' ?>"
                                        data-qty="<?= $i ?>"
                                        <?= $disabled ? 'disabled' : '' ?>>
                                    <?= $i ?>
                                </button>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <!-- Rincian Harga -->
                    <div class="price-box mb-4">
                        <div class="d-flex justify-content-between mb-2" style="font-size:.875rem;">
                            <span class="co-label">Harga / Tiket</span>
                            <span>Rp <?= number_format($ticketPrice, 0, ',', '.') ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2" style="font-size:.875rem;">
                            <span class="co-label">Jumlah</span>
                            <span id="displayQty">1 Tiket</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Total</span>
                            <span id="displayTotal" class="total-val">Rp <?= number_format($ticketPrice, 0, ',', '.') ?></span>
                        </div>
                    </div>

                    <button type="submit" name="btn_checkout" class="btn-pesan">
                        Konfirmasi &amp; Pesan
                    </button>
                </form>
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
            document.querySelectorAll('.qty-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const qty = parseInt(this.getAttribute('data-qty'), 10);
            inputQty.value = qty;
            displayQty.textContent = qty + ' Tiket';
            displayTotal.textContent = 'Rp ' + (qty * unitPrice).toLocaleString('id-ID');
        });
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
