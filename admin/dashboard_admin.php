<?php
require_once __DIR__ . '/../includes/admin_guard.php'; // memastikan cuma admin yang bisa akses

// Membuat koneksi ke database lewat class DBConnection
$db = new DBConnection();

// Satu query untuk menghitung jumlah data di lima tabel sekaligus (COUNT(*) per tabel).
// Hasilnya satu baris dengan lima kolom: movies, genres, showtimes, studios, orders.
$res = $db->send_query(
    "SELECT (SELECT COUNT(*) FROM movies) AS movies,
            (SELECT COUNT(*) FROM genres) AS genres,
            (SELECT COUNT(*) FROM showtimes) AS showtimes,
            (SELECT COUNT(*) FROM studios) AS studios,
            (SELECT COUNT(*) FROM orders) AS orders"
);

// Ambil baris pertama hasil query. Kalau query gagal atau kosong, semua angka dianggap 0
// supaya halaman tidak error.
$stat = $res['data'][0] ?? ['movies' => 0, 'genres' => 0, 'showtimes' => 0, 'studios' => 0, 'orders' => 0];

// Daftar kartu yang akan ditampilkan. Setiap kartu berisi:
// [label tampilan, ikon Bootstrap Icons, kunci di $stat, halaman tujuan tombol Kelola]
$cards = [
    ['Film',          'bi-film',           'movies',    'movie.php'],
    ['Genre',         'bi-tags',           'genres',    'genre.php'],
    ['Jadwal Tayang', 'bi-calendar-event', 'showtimes', 'showtime.php'],
    ['Pesanan',       'bi-receipt',        'orders',    'orders.php'],
    ['Studio',        'bi-display',        'studios',   'studio.php'],
];
?>
<?php
// Pengaturan halaman untuk header.php (judul tab, tema gelap, path dasar)
$page_title = 'Dashboard Admin';
$base_url   = '../';
$body_class = 'theme-dark';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-container">

        <!-- Judul dan deskripsi halaman -->
        <div class="dash-hero">
            <div class="dash-small">ADMIN PANEL</div>
            <h1 class="dash-title">Dashboard <span>Admin</span></h1>
            <p class="dash-text">
                Kelola film, genre, jadwal tayang, studio, dan pesanan HIMTI MOVIE dari satu tempat.
            </p>
        </div>

        <h2 class="section-title sm">Ringkasan <span>Data</span></h2>

        <!-- Kartu statistik dibuat dengan perulangan dari array $cards,
             jadi menambah kartu baru cukup menambah satu baris di array -->
        <div class="stat-grid">
            <?php foreach ($cards as [$label, $icon, $key, $href]): ?>
                <div class="stat-card">
                    <!-- Ikon kartu -->
                    <div class="stat-icon"><i class="bi <?= $icon ?>"></i></div>
                    <!-- Jumlah data diambil dari $stat sesuai kuncinya. (int) memastikan hasilnya angka -->
                    <div class="stat-number"><?= (int)$stat[$key] ?></div>
                    <!-- Nama data. htmlspecialchars mencegah XSS -->
                    <div class="stat-label"><?= htmlspecialchars($label) ?></div>
                    <!-- Tombol menuju halaman pengelolaan data tersebut -->
                    <a href="<?= htmlspecialchars($href) ?>" class="btn-manage">Kelola</a>
                </div>
            <?php endforeach; ?>
        </div>

    </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>