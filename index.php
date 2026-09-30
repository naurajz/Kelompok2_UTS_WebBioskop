<?php
/**
 * File     : index.php
 * Card     : User-01 Homepage
 * Tugas    : Daftar film yang sedang tayang (card poster).
 * PIC      : (isi nama)
 * Deadline : 3 Oktober 2026
 */

require_once "bootstrap.php";

$page_title = "Beranda - Sedang Tayang";
require_once "includes/header.php";

// Ambil daftar film dari database
$movies = [];
try {
    $db = new DBConnection();
    // Query film dengan nama genre
    $query = "SELECT m.*, g.genre_name 
              FROM movies m 
              LEFT JOIN genres g ON m.genre_id = g.genre_id 
              ORDER BY m.movie_id DESC";
    $result = $db->send_query($query);
    if ($result['success']) {
        $movies = $result['data'];
    }
} catch (Exception $e) {
    $error_msg = $e->getMessage();
}
?>

<!-- Hero Banner -->
<div class="bg-dark text-white py-5 mb-5 shadow-sm" style="background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=1600&q=80') center/cover no-repeat;">
    <div class="container text-center py-4">
        <h1 class="display-5 fw-bold text-warning mb-3">Selamat Datang di Cinema Bioskop</h1>
        <p class="lead mb-4 text-light">Pesan tiket bioskop favoritmu dengan cepat, mudah, dan tanpa antre!</p>
        <?php if (!isset($_SESSION['user_id'])): ?>
            <div>
                <a href="login.php" class="btn btn-warning btn-lg me-2 px-4 shadow">Masuk</a>
                <a href="register.php" class="btn btn-outline-light btn-lg px-4">Daftar Akun</a>
            </div>
        <?php else: ?>
            <div class="alert alert-light d-inline-block text-dark py-2 px-4 rounded-pill shadow-sm">
                <i class="bi bi-person-check-fill text-success me-2"></i>
                Halo, <strong><?= htmlspecialchars($_SESSION['user']['username'] ?? 'Pengguna') ?></strong>! Siap menonton hari ini?
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Container Daftar Film -->
<div class="container mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h3 class="fw-bold mb-0">
            <i class="bi bi-film text-danger me-2"></i>Film Sedang Tayang
        </h3>
        <span class="badge bg-secondary"><?= count($movies) ?> Film Tersedia</span>
    </div>

    <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger shadow-sm">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>Terjadi kendala memuat data film: <?= htmlspecialchars($error_msg) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($movies)): ?>
        <div class="card shadow-sm border-0 text-center py-5">
            <div class="card-body">
                <i class="bi bi-camera-reels text-muted" style="font-size: 3.5rem;"></i>
                <h4 class="mt-3 text-secondary">Belum Ada Film yang Tersedia</h4>
                <p class="text-muted">Jadwal penayangan film terbaru akan segera diperbarui.</p>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="admin/movie.php" class="btn btn-warning mt-2">
                        <i class="bi bi-plus-circle me-1"></i>Tambah Film di Panel Admin
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php foreach ($movies as $movie): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm border-0 rounded-3 overflow-hidden movie-card">
                        <!-- Poster Film -->
                        <div class="position-relative" style="height: 340px; background-color: #212529;">
                            <?php if (!empty($movie['poster']) && file_exists(__DIR__ . '/' . $movie['poster'])): ?>
                                <img src="<?= htmlspecialchars($movie['poster']) ?>" class="w-100 h-100 object-fit-cover" alt="<?= htmlspecialchars($movie['title']) ?>">
                            <?php else: ?>
                                <div class="d-flex flex-column justify-content-center align-items-center h-100 text-muted">
                                    <i class="bi bi-film" style="font-size: 3rem;"></i>
                                    <small class="mt-2">No Poster</small>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($movie['genre_name'])): ?>
                                <span class="position-absolute top-0 end-0 badge bg-danger m-2 shadow-sm">
                                    <?= htmlspecialchars($movie['genre_name']) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Info Film -->
                        <div class="card-body d-flex flex-column p-3">
                            <h5 class="card-title fw-bold text-dark text-truncate" title="<?= htmlspecialchars($movie['title']) ?>">
                                <?= htmlspecialchars($movie['title']) ?>
                            </h5>
                            
                            <div class="d-flex justify-content-between text-muted small mb-2">
                                <span><i class="bi bi-clock me-1"></i><?= (int)$movie['duration'] ?> Menit</span>
                                <span><i class="bi bi-calendar-event me-1"></i><?= !empty($movie['release_date']) ? date('d M Y', strtotime($movie['release_date'])) : '-' ?></span>
                            </div>

                            <p class="card-text text-secondary small flex-grow-1" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                <?= !empty($movie['description']) ? htmlspecialchars($movie['description']) : 'Tidak ada sinopsis.' ?>
                            </p>

                            <a href="movie_detail.php?id=<?= $movie['movie_id'] ?>" class="btn btn-warning w-100 fw-bold mt-2 shadow-sm">
                                <i class="bi bi-ticket-perforated me-1"></i>Beli Tiket
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.movie-card {
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}
.movie-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15) !important;
}
</style>

<?php require_once "includes/footer.php"; ?>
