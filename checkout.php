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

// checkout hanya untuk user biasa, admin tidak boleh memesan tiket
// (admin dikembalikan ke dashboard admin)
if (($_SESSION['role'] ?? '') === 'admin') {
    header("Location: admin/dashboard_admin.php");
    exit;
}

// ambil showtime_id dari URL, kalau gak ada langsung stop
$showtimeId = (int)($_GET['showtime_id'] ?? $_POST['showtime_id'] ?? 0);
if ($showtimeId <= 0) {
    exit;
}

// muat dependensi dulu, urutannya penting:
// BaseModel (dan Crudable) harus ada sebelum Order, karena Order extends BaseModel
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/classes/BaseModel.php';
require_once __DIR__ . '/classes/Ticket.php';
require_once __DIR__ . '/classes/Order.php';

// data user yang lagi login (dari session)
$currentUserId    = (int)$_SESSION['user_id'];

// session cuma menyimpan user_id dan role (lihat login_post.php),
// jadi nama dan email diambil langsung dari tabel users
$currentUserName  = 'Pengguna';
$currentUserEmail = '';

try {
    $db  = new DBConnection();
    $res = $db->send_query("SELECT username, email FROM users WHERE user_id = $1", [$currentUserId]);

    if (!empty($res['success']) && !empty($res['data'])) {
        $row = $res['data'][0];
        if (!empty($row['username'])) {
            $currentUserName = $row['username'];
        }
        $currentUserEmail = $row['email'] ?? '';
    }
} catch (Exception $e) {
    // kalau query gagal, form tetap tampil dengan nilai default
}

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

// nama pemesan: default dari akun login, tapi boleh diganti user
// (kalau form gagal dikirim, nilai yang diketik tetap dipertahankan)
$formName = trim($_POST['booker_name'] ?? $currentUserName);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_checkout'])) {
    $quantity = (int)($_POST['quantity'] ?? 1);

    // nama kosong -> pakai nama akun; batasi 100 karakter
    $bookerName = $formName !== '' ? mb_substr($formName, 0, 100) : $currentUserName;

    // validasi jumlah tiket, maksimal 6
    if ($quantity >= 1 && $quantity <= 6) {
        try {
            // buat order + tiket di database
            // (parameter ke-4 di Order.php adalah $seatNumbers, jadi nama pemesan TIDAK dikirim lewat sini)
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

// pengaturan halaman untuk header.php (judul tab, tema gelap, path dasar)
// header.php dipanggil SETELAH semua proses redirect di atas, supaya header() tidak error
$page_title = 'Checkout';
$body_class = 'theme-dark';
$base_url   = '';
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-container">

    <div class="dash-hero">
        <div class="dash-small">PEMESANAN TIKET</div>
        <h1 class="dash-title">Check<span>out</span></h1>
        <p class="dash-text">Pilih jumlah tiket, lalu konfirmasi pesananmu.</p>
    </div>

    <?php if ($checkoutError): ?>
    <div class="flash flash-danger"><?= htmlspecialchars($checkoutError) ?></div>
    <?php endif; ?>

    <!-- kartu atas: info film dan jadwal -->
    <div class="panel">
        <h2>Info <span>Film</span></h2>

        <div class="schedule-list">

            <div class="schedule-movie">
                <?php if (!empty($showtime['movie_poster'])): ?>
                <img src="<?= htmlspecialchars($showtime['movie_poster']) ?>"
                     alt="Poster" class="schedule-poster"
                     onerror="this.style.display='none'">
                <?php endif; ?>

                <div class="schedule-info">
                    <div class="schedule-title"><?= htmlspecialchars($showtime['movie_title'] ?? '-') ?></div>
                    <div class="schedule-genre">
                        <?= htmlspecialchars($showtime['genre_name'] ?? 'General') ?> &bull;
                        <?= (int)($showtime['movie_duration'] ?? 120) ?> Menit
                    </div>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <tbody>
                        <tr>
                            <td class="muted">Studio</td>
                            <td><span class="pill"><?= htmlspecialchars($showtime['studio_name'] ?? '-') ?></span></td>
                        </tr>
                        <tr>
                            <td class="muted">Tanggal</td>
                            <td class="waktu"><?= !empty($showtime['show_date']) ? date('d M Y', strtotime($showtime['show_date'])) : '-' ?></td>
                        </tr>
                        <tr>
                            <td class="muted">Jam Tayang</td>
                            <td class="waktu"><?= !empty($showtime['show_time']) ? date('H:i', strtotime($showtime['show_time'])) . ' WIB' : '-' ?></td>
                        </tr>
                        <tr>
                            <td class="muted">Harga / Tiket</td>
                            <td class="td-price">Rp <?= number_format($ticketPrice, 0, ',', '.') ?></td>
                        </tr>
                        <?php if ($quotaInfo): ?>
                        <tr>
                            <td class="muted">Sisa Kursi</td>
                            <td><span class="pill"><?= $quotaInfo['remaining'] ?> tersedia</span></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <!-- kartu bawah: form pemesanan -->
    <div class="panel">
        <h2>Rincian <span>Pemesanan</span></h2>

        <form method="POST" action="checkout.php?showtime_id=<?= $showtimeId ?>" class="form-stack">
            <!-- showtime_id disimpan di hidden field biar ikut ke POST -->
            <input type="hidden" name="showtime_id" value="<?= $showtimeId ?>">
            <!-- quantity diisi lewat JS saat user klik tombol angka -->
            <input type="hidden" name="quantity" id="inputQuantity" value="1">

            <!-- nama pemesan: terisi otomatis dari akun login, tapi boleh diganti -->
            <label for="booker_name">Nama Pemesan</label>
            <input type="text"
                   id="booker_name"
                   name="booker_name"
                   value="<?= htmlspecialchars($formName) ?>"
                   maxlength="100"
                   required
                   placeholder="Nama pemesan">
            <small class="muted">Terisi otomatis dari akunmu, boleh diganti.</small>

            <!-- email: terisi otomatis dari akun login, dikunci -->
            <label>Email</label>
            <input type="email" value="<?= htmlspecialchars($currentUserEmail) ?>" readonly>

            <label>Jumlah Tiket (Maks. 6)</label>
            <div class="genre-filter">
                <?php for ($i = 1; $i <= 6; $i++): ?>
                    <button type="button"
                            class="genre-btn <?= $i === 1 ? 'active' : '' ?>"
                            data-qty="<?= $i ?>"
                            <?= $i > $maxSelectable ? 'disabled' : '' ?>>
                        <?= $i ?>
                    </button>
                <?php endfor; ?>
            </div>

            <div class="schedule-list">

                <!-- ringkasan harga, diupdate real-time via JS -->
                <div class="table-wrap">
                    <table>
                        <tbody>
                            <tr>
                                <td class="muted">Harga / Tiket</td>
                                <td>Rp <?= number_format($ticketPrice, 0, ',', '.') ?></td>
                            </tr>
                            <tr>
                                <td class="muted">Jumlah</td>
                                <td id="displayQty">1 Tiket</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total</th>
                                <th id="displayTotal" class="td-price">Rp <?= number_format($ticketPrice, 0, ',', '.') ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="form-actions">
                    <button type="submit" name="btn_checkout" class="btn-simpan">
                        Konfirmasi &amp; Pesan
                    </button>
                    <a href="javascript:history.back()" class="btn-batal">&#8592; Kembali</a>
                </div>

            </div>
        </form>
    </div>

</div>

<script>
    // harga per tiket dari PHP, biar JS bisa hitung totalnya
    const unitPrice    = <?= $ticketPrice ?>;
    const inputQty     = document.getElementById('inputQuantity');
    const displayQty   = document.getElementById('displayQty');
    const displayTotal = document.getElementById('displayTotal');

    // setiap tombol angka diklik, update tampilan harga dan isi hidden input
    document.querySelectorAll('.genre-btn[data-qty]').forEach(btn => {
        btn.addEventListener('click', function () {
            if (this.disabled) return;

            // hapus active dari semua tombol, lalu tandai yang diklik
            document.querySelectorAll('.genre-btn[data-qty]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const qty = parseInt(this.getAttribute('data-qty'), 10);
            inputQty.value = qty;
            displayQty.textContent = qty + ' Tiket';
            displayTotal.textContent = 'Rp ' + (qty * unitPrice).toLocaleString('id-ID');
        });
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>