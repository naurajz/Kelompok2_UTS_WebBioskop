<?php
// checkout.php - halaman beli tiket
// dibuat oleh Davientyo Arifius Putra (434251115)

// mulai session biar bisa akses data login user
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// kalau belum login, lempar ke halaman login dulu
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// ambil showtime_id dari URL, kalau gak ada langsung stop
$showtimeId = (int)($_GET['showtime_id'] ?? $_POST['showtime_id'] ?? 0);
if ($showtimeId <= 0) {
    exit;
}

require_once __DIR__ . '/classes/Order.php';

// data user yang lagi login (dari session)
$currentUserId    = (int)$_SESSION['user_id'];
$currentUserName  = $_SESSION['username'] ?? 'Pengguna';
$currentUserEmail = $_SESSION['email'] ?? '';

// buat objek Order, lalu ambil info jadwal tayangnya
$orderModel = new Order();
$showtime   = $orderModel->getShowtimeInfo($showtimeId);

// kalau jadwal gak ketemu di database, stop
if (!$showtime) {
    exit;
}

// cek sisa kursi dan harga tiket
$quotaInfo     = $orderModel->checkQuota($showtimeId, 1);
$ticketPrice   = (float)($showtime['price'] ?? 50000);
$maxSelectable = $quotaInfo ? min(6, (int)$quotaInfo['remaining']) : 6;

// proses kalau user klik tombol pesan
$checkoutError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_checkout'])) {
    $quantity = (int)($_POST['quantity'] ?? 1);

    // validasi jumlah tiket, maksimal 6
    if ($quantity >= 1 && $quantity <= 6) {
        try {
            // buat order + tiket di database
            $result = $orderModel->createOrderWithTickets($currentUserId, $showtimeId, $quantity);

            // kalau berhasil, redirect ke halaman konfirmasi
            if ($result && isset($result['order_id'])) {
                header("Location: confirm.php?order_id=" . $result['order_id']);
                exit;
            }
        } catch (Exception $e) {
            $checkoutError = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout – HIMTI MOVIE</title>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background: #080808;
    color: white;
    font-family: Arial, Helvetica, sans-serif;
}

a {
    text-decoration: none;
    color: inherit;
}

/* navbar */
.navbar {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 72px;
    background: rgba(8,8,8,0.96);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 55px;
    z-index: 9999;
    border-bottom: 1px solid #222;
}

.logo {
    font-size: 25px;
    font-weight: 900;
}

.logo span {
    color: #e50914;
}

.nav-menu {
    display: flex;
    align-items: center;
    gap: 32px;
}

.nav-menu a {
    color: #ddd;
    font-size: 14px;
}

.nav-menu a:hover {
    color: #e50914;
}

/* area konten utama, kasih jarak dari navbar */
.page-wrap {
    margin-top: 72px;
    padding: 60px 7%;
    min-height: calc(100vh - 72px);
}

.back-link {
    display: inline-block;
    color: #888;
    font-size: 14px;
    margin-bottom: 30px;
    transition: color .2s;
}

.back-link:hover {
    color: #e50914;
}

.page-title {
    font-size: 32px;
    font-weight: 900;
    margin-bottom: 40px;
}

.page-title span {
    color: #e50914;
}

/* layout dua kolom: kiri info film, kanan form pesan */
.checkout-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 28px;
    max-width: 900px;
}

/* di hp jadi satu kolom */
@media (max-width: 700px) {
    .checkout-grid { grid-template-columns: 1fr; }
    .navbar { padding: 0 20px; }
    .page-wrap { padding: 50px 5%; }
}

/* card gelap buat tiap bagian */
.card {
    background: #121212;
    border: 1px solid #252525;
    border-radius: 12px;
    padding: 28px;
}

.card-title {
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 20px;
    color: #e50914;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.poster-img {
    width: 100%;
    max-height: 200px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 16px;
}

.movie-title {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 6px;
}

.movie-sub {
    color: #888;
    font-size: 13px;
    margin-bottom: 18px;
}

/* tabel info film */
.info-table {
    width: 100%;
    border-collapse: collapse;
}

.info-table tr td {
    padding: 9px 4px;
    font-size: 14px;
    border-bottom: 1px solid #1e1e1e;
}

.info-table td.lbl {
    color: #888;
    width: 40%;
}

.info-table td.val {
    font-weight: 600;
}

.info-table td.val-red {
    font-weight: 700;
    color: #e50914;
}

/* badge sisa kursi */
.seat-badge {
    font-size: 12px;
    padding: 3px 9px;
    border-radius: 4px;
    font-weight: 600;
}

.seat-ok  { background: #122212; color: #4caf50; }
.seat-no  { background: #2d0707; color: #f44; }

/* form input yang read-only (nama & email dari session) */
.field-label {
    color: #888;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 6px;
}

.field-input {
    width: 100%;
    background: #1a1a1a;
    border: 1px solid #333;
    border-radius: 7px;
    color: #aaa;
    padding: 10px 14px;
    font-size: 14px;
    margin-bottom: 18px;
    font-family: Arial, Helvetica, sans-serif;
}

.field-input[readonly] {
    cursor: default;
    opacity: .7;
}

/* pilihan jumlah tiket 1-6 */
.qty-label {
    color: #888;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 10px;
}

.qty-row {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 22px;
}

.qty-btn {
    width: 44px;
    height: 44px;
    background: #1a1a1a;
    border: 1px solid #333;
    border-radius: 7px;
    color: #aaa;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: all .15s;
    font-family: Arial, Helvetica, sans-serif;
}

/* tombol aktif / hover jadi merah */
.qty-btn.active,
.qty-btn:not(:disabled):hover {
    background: #e50914;
    border-color: #e50914;
    color: #fff;
}

/* tombol nonaktif (kursi sudah habis) */
.qty-btn:disabled {
    opacity: .3;
    cursor: not-allowed;
}

/* kotak ringkasan harga */
.price-box {
    background: #0e0e0e;
    border: 1px solid #222;
    border-radius: 8px;
    padding: 16px 18px;
    margin-bottom: 22px;
}

.price-row {
    display: flex;
    justify-content: space-between;
    font-size: 14px;
    padding: 6px 0;
}

.price-lbl { color: #888; }

.price-divider {
    border: none;
    border-top: 1px solid #222;
    margin: 8px 0;
}

.price-total {
    font-size: 16px;
    font-weight: 700;
    color: #e50914;
}

.btn-order {
    width: 100%;
    background: #e50914;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 13px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    font-family: Arial, Helvetica, sans-serif;
    transition: background .2s;
}

.btn-order:hover {
    background: #c1070f;
}

/* kotak error kalau gagal order */
.error-box {
    background: #2d0707;
    border: 1px solid #7a1010;
    color: #f88;
    border-radius: 8px;
    padding: 12px 16px;
    font-size: 14px;
    margin-bottom: 24px;
}

</style>
</head>
<body>

<nav class="navbar">
    <div class="logo">
        HIMTI
        <span>MOVIE</span>
    </div>
    <div class="nav-menu">
        <a href="index.php">Home</a>
        <a href="index.php#movies">Movies</a>
        <a href="history.php">Pesanan Saya</a>
        <a href="logout.php" style="background:#e50914;padding:10px 20px;border-radius:7px;color:white;">Keluar</a>
    </div>
</nav>

<div class="page-wrap">

    <a href="javascript:history.back()" class="back-link">&#8592; Kembali</a>

    <h2 class="page-title">Check<span>out</span></h2>

    <?php if ($checkoutError): ?>
    <div class="error-box"><?= htmlspecialchars($checkoutError) ?></div>
    <?php endif; ?>

    <div class="checkout-grid">

        <!-- kartu kiri: info film dan jadwal -->
        <div class="card">
            <div class="card-title">Info Film</div>

            <?php if (!empty($showtime['movie_poster'])): ?>
            <img src="<?= htmlspecialchars($showtime['movie_poster']) ?>"
                 alt="Poster" class="poster-img"
                 onerror="this.style.display='none'">
            <?php endif; ?>

            <div class="movie-title"><?= htmlspecialchars($showtime['movie_title'] ?? '-') ?></div>
            <div class="movie-sub">
                <?= htmlspecialchars($showtime['genre_name'] ?? 'General') ?> &bull;
                <?= (int)($showtime['movie_duration'] ?? 120) ?> Menit
            </div>

            <table class="info-table">
                <tr>
                    <td class="lbl">Studio</td>
                    <td class="val"><?= htmlspecialchars($showtime['studio_name'] ?? '-') ?></td>
                </tr>
                <tr>
                    <td class="lbl">Tanggal</td>
                    <td class="val"><?= !empty($showtime['show_date']) ? date('d M Y', strtotime($showtime['show_date'])) : '-' ?></td>
                </tr>
                <tr>
                    <td class="lbl">Jam Tayang</td>
                    <td class="val"><?= !empty($showtime['show_time']) ? date('H:i', strtotime($showtime['show_time'])) . ' WIB' : '-' ?></td>
                </tr>
                <tr>
                    <td class="lbl">Harga / Tiket</td>
                    <td class="val val-red">Rp <?= number_format($ticketPrice, 0, ',', '.') ?></td>
                </tr>
                <?php if ($quotaInfo): ?>
                <tr>
                    <td class="lbl">Sisa Kursi</td>
                    <td class="val">
                        <span class="seat-badge <?= $quotaInfo['remaining'] > 0 ? 'seat-ok' : 'seat-no' ?>">
                            <?= $quotaInfo['remaining'] ?> tersedia
                        </span>
                    </td>
                </tr>
                <?php endif; ?>
            </table>
        </div>

        <!-- kartu kanan: form pemesanan -->
        <div class="card">
            <div class="card-title">Rincian Pemesanan</div>

            <form method="POST" action="checkout.php?showtime_id=<?= $showtimeId ?>">
                <!-- showtime_id disimpan di hidden field biar ikut ke POST -->
                <input type="hidden" name="showtime_id" value="<?= $showtimeId ?>">
                <!-- quantity diisi lewat JS saat user klik tombol angka -->
                <input type="hidden" name="quantity" id="inputQuantity" value="1">

                <div class="field-label">Nama Pemesan</div>
                <input type="text" class="field-input" value="<?= htmlspecialchars($currentUserName) ?>" readonly>

                <div class="field-label">Email</div>
                <input type="email" class="field-input" value="<?= htmlspecialchars($currentUserEmail) ?>" readonly>

                <div class="qty-label">Jumlah Tiket (Maks. 6)</div>
                <div class="qty-row">
                    <?php for ($i = 1; $i <= 6; $i++): ?>
                        <button type="button"
                                class="qty-btn <?= $i === 1 ? 'active' : '' ?>"
                                data-qty="<?= $i ?>"
                                <?= $i > $maxSelectable ? 'disabled' : '' ?>>
                            <?= $i ?>
                        </button>
                    <?php endfor; ?>
                </div>

                <!-- ringkasan harga, diupdate real-time via JS -->
                <div class="price-box">
                    <div class="price-row">
                        <span class="price-lbl">Harga / Tiket</span>
                        <span>Rp <?= number_format($ticketPrice, 0, ',', '.') ?></span>
                    </div>
                    <div class="price-row">
                        <span class="price-lbl">Jumlah</span>
                        <span id="displayQty">1 Tiket</span>
                    </div>
                    <hr class="price-divider">
                    <div class="price-row">
                        <span style="font-weight:700;">Total</span>
                        <span id="displayTotal" class="price-total">Rp <?= number_format($ticketPrice, 0, ',', '.') ?></span>
                    </div>
                </div>

                <button type="submit" name="btn_checkout" class="btn-order">
                    Konfirmasi &amp; Pesan
                </button>
            </form>
        </div>

    </div>
</div>

<script>
    // harga per tiket dari PHP, biar JS bisa hitung totalnya
    const unitPrice    = <?= $ticketPrice ?>;
    const inputQty     = document.getElementById('inputQuantity');
    const displayQty   = document.getElementById('displayQty');
    const displayTotal = document.getElementById('displayTotal');

    // setiap tombol angka diklik, update tampilan harga dan isi hidden input
    document.querySelectorAll('.qty-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            if (this.disabled) return;

            // hapus active dari semua tombol, lalu tandai yang diklik
            document.querySelectorAll('.qty-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const qty = parseInt(this.getAttribute('data-qty'), 10);
            inputQty.value = qty;
            displayQty.textContent = qty + ' Tiket';
            displayTotal.textContent = 'Rp ' + (qty * unitPrice).toLocaleString('id-ID');
        });
    });
</script>

</body>
</html>
