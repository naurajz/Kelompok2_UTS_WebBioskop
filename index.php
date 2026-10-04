<?php
require_once "bootstrap.php";

$db = new DBConnection();

// Mengatur status login pengguna.
$is_logged_in = isset($_SESSION['user']);
$username = '';

if ($is_logged_in) {
    $username = $_SESSION['user']['username'] ?? '';
}

// Mengambil genre dari database.
$genres_result = $db->send_query("
    SELECT genre_id, genre_name
    FROM genres
    ORDER BY genre_name ASC
");

$genres = $genres_result['data'] ?? [];

// Mengambil genre yang dipilih pengguna.
$selected_genre = $_GET['genre'] ?? '';

// Mengambil daftar film beserta poster dan genre.
if ($selected_genre !== '') {
    $movies_result = $db->send_query("
        SELECT
            m.movie_id,
            m.title,
            m.description,
            m.duration,
            m.release_date,
            m.poster,
            g.genre_name
        FROM movies m
        LEFT JOIN genres g ON m.genre_id = g.genre_id
        WHERE m.genre_id = $1
        ORDER BY m.movie_id DESC
    ", [$selected_genre]);
} else {
    $movies_result = $db->send_query("
        SELECT
            m.movie_id,
            m.title,
            m.description,
            m.duration,
            m.release_date,
            m.poster,
            g.genre_name
        FROM movies m
        LEFT JOIN genres g ON m.genre_id = g.genre_id
        ORDER BY m.movie_id DESC
    ");
}

$movies = $movies_result['data'] ?? [];

// Mengatur tanggal jadwal yang sedang dipilih.
$selected_date = $_GET['date'] ?? date('Y-m-d');

// Mengambil jadwal film berdasarkan tanggal.
$showtimes_result = $db->send_query("
    SELECT
        s.showtime_id,
        s.show_date,
        s.show_time,
        s.price,
        st.studio_name,
        m.movie_id,
        m.title,
        m.poster
    FROM showtimes s
    JOIN movies m ON s.movie_id = m.movie_id
    JOIN studios st ON s.studio_id = st.studio_id
    WHERE s.show_date = $1
    ORDER BY s.show_time ASC
", [$selected_date]);

$showtimes = $showtimes_result['data'] ?? [];

// Mengambil film yang tanggal rilisnya belum tiba.
$coming_result = $db->send_query("
    SELECT
        m.movie_id,
        m.title,
        m.description,
        m.duration,
        m.release_date,
        m.poster,
        g.genre_name
    FROM movies m
    LEFT JOIN genres g ON m.genre_id = g.genre_id
    WHERE m.release_date > CURRENT_DATE
    ORDER BY m.release_date ASC
");

$coming_movies = $coming_result['data'] ?? [];

// Membuat daftar tujuh tanggal untuk jadwal.
$dates = [];

for ($i = 0; $i < 7; $i++) {
    $date = date('Y-m-d', strtotime("+$i day"));
    $dates[] = $date;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>HIMTI MOVIE - Surabaya</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #080808;
            color: #fff;
            font-family: Arial, Helvetica, sans-serif;
        }

        a {
            text-decoration: none;
        }

        /* Navbar menampilkan identitas website dan menu utama. */
        .navbar-custom {
            background: #050505;
            border-bottom: 1px solid #252525;
            padding: 18px 5%;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .brand {
            color: #e50914;
            font-size: 25px;
            font-weight: 900;
            letter-spacing: 1px;
        }

        .brand:hover {
            color: #ff1b26;
        }

        .nav-link-custom {
            color: #ddd;
            font-size: 13px;
            font-weight: bold;
            margin-left: 25px;
            transition: .2s;
        }

        .nav-link-custom:hover {
            color: #e50914;
        }

        .login-btn {
            border: 1px solid #e50914;
            color: #fff;
            padding: 8px 17px;
            margin-left: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .login-btn:hover {
            background: #e50914;
            color: #fff;
        }

        .register-btn {
            color: #ddd;
            margin-left: 15px;
            font-size: 12px;
            font-weight: bold;
        }

        .register-btn:hover {
            color: #e50914;
        }

        .admin-btn {
            color: #fff;
            border: 1px solid #444;
            padding: 8px 14px;
            margin-left: 15px;
            font-size: 12px;
            font-weight: bold;
        }

        .admin-btn:hover {
            border-color: #e50914;
            color: #e50914;
        }

        /* Hero menampilkan gambar utama dan informasi singkat website. */
        .hero {
            min-height: 570px;
            position: relative;
            display: flex;
            align-items: center;
            overflow: hidden;
            background: #090909;
        }

        .hero-image {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: .45;
        }

        .hero-overlay {
            position: absolute;
            inset: 0;
            background:
                linear-gradient(
                    90deg,
                    rgba(0,0,0,.95) 0%,
                    rgba(0,0,0,.72) 45%,
                    rgba(0,0,0,.25) 100%
                );
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 650px;
            padding: 80px 5%;
        }

        .hero-location {
            color: #e50914;
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 3px;
            margin-bottom: 15px;
        }

        .hero-title {
            font-size: clamp(48px, 7vw, 90px);
            line-height: .95;
            font-weight: 900;
            margin: 0 0 20px;
        }

        .hero-title span {
            color: #e50914;
        }

        .hero-text {
            color: #ccc;
            max-width: 530px;
            font-size: 16px;
            line-height: 1.7;
        }

        .hero-btn {
            display: inline-block;
            margin-top: 25px;
            background: #e50914;
            color: #fff;
            padding: 13px 25px;
            font-size: 13px;
            font-weight: bold;
        }

        .hero-btn:hover {
            background: #b80710;
            color: #fff;
        }

        /* Container mengatur lebar konten utama website. */
        .container-custom {
            width: 90%;
            max-width: 1250px;
            margin: auto;
        }

        /* Section title menampilkan judul setiap bagian website. */
        .section-title {
            margin-bottom: 30px;
        }

        .section-title h2 {
            font-size: 30px;
            font-weight: 900;
            margin: 0;
        }

        .section-title span {
            color: #e50914;
        }

        .section-title p {
            color: #777;
            margin-top: 8px;
            font-size: 13px;
        }

        /* Filter genre digunakan untuk menyaring film berdasarkan genre. */
        .genre-filter {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 30px;
        }

        .genre-btn {
            border: 1px solid #333;
            color: #aaa;
            padding: 8px 15px;
            font-size: 12px;
            font-weight: bold;
        }

        .genre-btn:hover,
        .genre-btn.active {
            background: #e50914;
            border-color: #e50914;
            color: #fff;
        }

        /* Movie grid menampilkan daftar film dalam bentuk kartu lengkap dengan poster. */
        .movie-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 22px;
        }

        .movie-card {
            background: #111;
            border: 1px solid #222;
            overflow: hidden;
            transition: .25s;
        }

        .movie-card:hover {
            transform: translateY(-5px);
            border-color: #e50914;
        }

        .movie-poster {
            width: 100%;
            height: 350px;
            background: #171717;
            overflow: hidden;
        }

        .movie-poster img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .no-poster {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            font-size: 13px;
        }

        .movie-info {
            padding: 18px;
        }

        .movie-info h3 {
            font-size: 17px;
            font-weight: bold;
            margin-bottom: 9px;
        }

        .movie-genre {
            color: #e50914;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .movie-duration {
            color: #777;
            font-size: 12px;
            margin-bottom: 15px;
        }

        .detail-btn {
            display: inline-block;
            color: #fff;
            border: 1px solid #444;
            padding: 8px 12px;
            font-size: 11px;
            font-weight: bold;
        }

        .detail-btn:hover {
            background: #e50914;
            border-color: #e50914;
        }

        /* Search digunakan untuk mencari film berdasarkan judul. */
        .search-box {
            margin-bottom: 30px;
        }

        .search-input {
            width: 100%;
            max-width: 450px;
            background: #111;
            color: #fff;
            border: 1px solid #333;
            padding: 13px 17px;
            outline: none;
        }

        .search-input:focus {
            border-color: #e50914;
        }

        /* Date list digunakan untuk memilih tanggal jadwal film. */
        .date-list {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding-bottom: 10px;
            margin-bottom: 25px;
        }

        .date-btn {
            min-width: 90px;
            text-align: center;
            border: 1px solid #333;
            color: #aaa;
            padding: 10px;
            font-size: 12px;
        }

        .date-btn strong {
            display: block;
            color: #fff;
            font-size: 15px;
        }

        .date-btn:hover,
        .date-btn.active {
            background: #e50914;
            border-color: #e50914;
            color: #fff;
        }

        .date-btn:hover strong,
        .date-btn.active strong {
            color: #fff;
        }

        /* Schedule menampilkan jadwal, studio, harga, dan waktu film. */
        .schedule-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .schedule-item {
            background: #111;
            border: 1px solid #222;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .schedule-movie {
            display: flex;
            align-items: center;
            gap: 15px;
            min-width: 0;
        }

        .schedule-poster {
            width: 55px;
            height: 75px;
            object-fit: cover;
            flex-shrink: 0;
        }

        .schedule-movie h3 {
            font-size: 16px;
            margin: 0 0 5px;
        }

        .schedule-movie p {
            margin: 0;
            color: #777;
            font-size: 12px;
        }

        .schedule-details {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .studio {
            color: #999;
            font-size: 12px;
        }

        .price {
            color: #fff;
            font-weight: bold;
            font-size: 13px;
        }

        .time-btn {
            display: inline-block;
            background: #e50914;
            color: #fff;
            padding: 10px 15px;
            font-size: 12px;
            font-weight: bold;
            min-width: 70px;
            text-align: center;
        }

        .time-btn:hover {
            background: #b80710;
            color: #fff;
        }

        /* Coming soon menampilkan film yang tanggal rilisnya belum tiba. */
        .coming-section {
            background: #0d0d0d;
        }

        .coming-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 22px;
        }

        .coming-card {
            background: #111;
            border: 1px solid #222;
            overflow: hidden;
        }

        .coming-card img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            display: block;
        }

        .coming-info {
            padding: 17px;
        }

        .coming-info h3 {
            font-size: 16px;
            margin-bottom: 8px;
        }

        .release-date {
            color: #e50914;
            font-size: 12px;
            font-weight: bold;
        }

        /* Cinema section menampilkan identitas lokasi HIMTI MOVIE di Surabaya. */
        .cinema-section {
            padding: 80px 0;
            background: #0a0a0a;
            border-top: 1px solid #1d1d1d;
        }

        .cinema-box {
            border: 1px solid #292929;
            padding: 40px;
            background: #101010;
        }

        .cinema-box h2 {
            font-size: 40px;
            font-weight: 900;
            margin-bottom: 5px;
        }

        .cinema-box h2 span {
            color: #e50914;
        }

        .cinema-location {
            color: #e50914;
            font-weight: bold;
            letter-spacing: 2px;
            font-size: 13px;
        }

        .cinema-box p {
            color: #888;
            max-width: 650px;
            line-height: 1.7;
            margin-top: 20px;
        }

        /* Footer menampilkan informasi penutup website. */
        footer {
            background: #050505;
            border-top: 1px solid #222;
            padding: 35px 0;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .footer-brand {
            color: #e50914;
            font-weight: 900;
        }

        .footer-text {
            color: #666;
            font-size: 12px;
        }

        /* Tampilan website disesuaikan untuk ukuran layar yang lebih kecil. */
        @media (max-width: 992px) {

            .movie-grid,
            .coming-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .schedule-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .schedule-details {
                width: 100%;
                justify-content: space-between;
                flex-wrap: wrap;
            }
        }

        @media (max-width: 768px) {

            .movie-grid,
            .coming-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .movie-poster {
                height: 300px;
            }

            .hero {
                min-height: 500px;
            }

            .hero-content {
                padding: 60px 5%;
            }

            .nav-link-custom {
                margin-left: 0;
                margin-right: 15px;
            }

            .footer-content {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 500px) {

            .movie-grid,
            .coming-grid {
                grid-template-columns: 1fr;
            }

            .movie-poster {
                height: 400px;
            }

            .schedule-movie {
                width: 100%;
            }

            .schedule-details {
                align-items: flex-start;
                flex-direction: column;
            }
        }

    </style>
</head>

<body>

    <!-- Navbar untuk navigasi utama website. -->
    <nav class="navbar-custom">
        <div class="container-fluid">

            <div class="d-flex align-items-center justify-content-between flex-wrap">

                <a href="index.php" class="brand">
                    HIMTI MOVIE
                </a>

                <div class="d-flex align-items-center flex-wrap">

                    <a href="#cinemas" class="nav-link-custom">
                        CINEMAS
                    </a>

                    <a href="#movies" class="nav-link-custom">
                        MOVIES
                    </a>

                    <a href="#showtimes" class="nav-link-custom">
                        SHOWTIMES
                    </a>

                    <?php if ($is_logged_in): ?>

                        <span style="color:#aaa;font-size:13px;margin-left:20px;">
                            Hi, <?= htmlspecialchars($username) ?>
                        </span>

                        <?php if (
                            isset($_SESSION['user']['role']) &&
                            $_SESSION['user']['role'] === 'admin'
                        ): ?>

                            <a href="admin/index.php" class="admin-btn">
                                ADMIN DASHBOARD
                            </a>

                        <?php endif; ?>

                        <a href="logout.php" class="login-btn">
                            LOG OUT
                        </a>

                    <?php else: ?>

                        <a href="login.php" class="login-btn">
                            LOGIN
                        </a>

                        <a href="register.php" class="register-btn">
                            REGISTER
                        </a>

                    <?php endif; ?>

                </div>

            </div>

        </div>
    </nav>


    <!-- Hero menampilkan gambar utama dan informasi singkat website. -->
    <section class="hero">

        <img
            src="assets/hero-cinema.png"
            class="hero-image"
            alt="Cinema"
        >

        <div class="hero-overlay"></div>

        <div class="hero-content">

            <div class="hero-location">
                SURABAYA
            </div>

            <h1 class="hero-title">
                HIMTI<br>
                <span>MOVIE</span>
            </h1>

            <p class="hero-text">
                Your destination for movies, schedules, and cinema experiences.
                Find your favorite movie and book your ticket easily.
            </p>

            <a href="#movies" class="hero-btn">
                EXPLORE MOVIES
            </a>

        </div>

    </section>


    <!-- Container utama menampung seluruh konten film dan jadwal. -->
    <main class="container-custom">


        <!-- Bagian film menampilkan film yang tersedia di database. -->
        <section id="movies" style="padding:80px 0;">

            <div class="section-title">

                <h2>
                    NOW <span>SHOWING</span>
                </h2>

                <p>
                    Discover movies currently available at HIMTI MOVIE.
                </p>

            </div>


            <!-- Search digunakan untuk mencari film berdasarkan judul. -->
            <div class="search-box">

                <input
                    type="text"
                    id="movieSearch"
                    class="search-input"
                    placeholder="Search movies..."
                >

            </div>


            <!-- Filter genre digunakan untuk menyaring film berdasarkan genre. -->
            <div class="genre-filter">

                <a
                    href="index.php"
                    class="genre-btn <?= $selected_genre === '' ? 'active' : '' ?>"
                >
                    ALL
                </a>

                <?php foreach ($genres as $genre): ?>

                    <a
                        href="index.php?genre=<?= $genre['genre_id'] ?>"
                        class="genre-btn <?= $selected_genre == $genre['genre_id'] ? 'active' : '' ?>"
                    >
                        <?= htmlspecialchars($genre['genre_name']) ?>
                    </a>

                <?php endforeach; ?>

            </div>


            <!-- Movie grid menampilkan poster, judul, genre, durasi, dan sinopsis film. -->
            <div class="movie-grid" id="movieGrid">

                <?php if (empty($movies)): ?>

                    <p style="color:#777;">
                        No movies available.
                    </p>

                <?php else: ?>

                    <?php foreach ($movies as $movie): ?>

                        <div
                            class="movie-card"
                            data-title="<?= htmlspecialchars(strtolower($movie['title'])) ?>"
                        >

                            <div class="movie-poster">

                                <?php if (!empty($movie['poster'])): ?>

                                    <img
                                        src="<?= htmlspecialchars($movie['poster']) ?>"
                                        alt="<?= htmlspecialchars($movie['title']) ?>"
                                    >

                                <?php else: ?>

                                    <div class="no-poster">
                                        NO POSTER
                                    </div>

                                <?php endif; ?>

                            </div>


                            <div class="movie-info">

                                <h3>
                                    <?= htmlspecialchars($movie['title']) ?>
                                </h3>

                                <p class="movie-genre">
                                    <?= htmlspecialchars($movie['genre_name'] ?? 'Movie') ?>
                                </p>

                                <p class="movie-duration">
                                    <?= htmlspecialchars($movie['duration']) ?> min
                                </p>

                                <a
                                    href="movie_detail.php?id=<?= $movie['movie_id'] ?>"
                                    class="detail-btn"
                                >
                                    READ SYNOPSIS
                                </a>

                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </section>


        <!-- Jadwal menampilkan tanggal dan jam tayang film dari database. -->
        <section id="showtimes" style="padding:20px 0 90px;">

            <div class="section-title">

                <h2>
                    MOVIE <span>SHOWTIMES</span>
                </h2>

                <p>
                    Choose your date and find available movie schedules.
                </p>

            </div>


            <!-- Date list digunakan untuk memilih tanggal jadwal film. -->
            <div class="date-list">

                <?php foreach ($dates as $date): ?>

                    <a
                        href="index.php?date=<?= $date ?>"
                        class="date-btn <?= $selected_date === $date ? 'active' : '' ?>"
                    >

                        <?= date('D', strtotime($date)) ?>

                        <strong>
                            <?= date('d', strtotime($date)) ?>
                        </strong>

                        <?= date('M', strtotime($date)) ?>

                    </a>

                <?php endforeach; ?>

            </div>


            <!-- Schedule menampilkan poster kecil, film, studio, harga, dan jam tayang. -->
            <div class="schedule-list">

                <?php if (empty($showtimes)): ?>

                    <div style="
                        background:#111;
                        border:1px solid #222;
                        padding:25px;
                        color:#777;
                    ">
                        No showtimes available for
                        <?= date('d M Y', strtotime($selected_date)) ?>.
                    </div>

                <?php else: ?>

                    <?php foreach ($showtimes as $show): ?>

                        <div class="schedule-item">

                            <div class="schedule-movie">

                                <?php if (!empty($show['poster'])): ?>

                                    <img
                                        src="<?= htmlspecialchars($show['poster']) ?>"
                                        class="schedule-poster"
                                        alt="<?= htmlspecialchars($show['title']) ?>"
                                    >

                                <?php endif; ?>

                                <div>

                                    <h3>
                                        <?= htmlspecialchars($show['title']) ?>
                                    </h3>

                                    <p>
                                        <?= htmlspecialchars($show['studio_name']) ?>
                                    </p>

                                </div>

                            </div>


                            <div class="schedule-details">

                                <span class="studio">
                                    <?= htmlspecialchars($show['studio_name']) ?>
                                </span>

                                <span class="price">
                                    Rp <?= number_format($show['price'], 0, ',', '.') ?>
                                </span>

                                <a
                                    href="checkout.php?showtime_id=<?= $show['showtime_id'] ?>"
                                    class="time-btn"
                                >
                                    <?= date('H:i', strtotime($show['show_time'])) ?>
                                </a>

                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </section>


    </main>


    <!-- Coming soon menampilkan film yang belum dirilis. -->
    <?php if (!empty($coming_movies)): ?>

        <section class="coming-section" style="padding:80px 0;">

            <div class="container-custom">

                <div class="section-title">

                    <h2>
                        COMING <span>SOON</span>
                    </h2>

                    <p>
                        Upcoming movies coming to HIMTI MOVIE.
                    </p>

                </div>


                <!-- Coming grid menampilkan poster dan tanggal rilis film mendatang. -->
                <div class="coming-grid">

                    <?php foreach ($coming_movies as $movie): ?>

                        <div class="coming-card">

                            <?php if (!empty($movie['poster'])): ?>

                                <img
                                    src="<?= htmlspecialchars($movie['poster']) ?>"
                                    alt="<?= htmlspecialchars($movie['title']) ?>"
                                >

                            <?php else: ?>

                                <div
                                    class="no-poster"
                                    style="height:300px;"
                                >
                                    NO POSTER
                                </div>

                            <?php endif; ?>


                            <div class="coming-info">

                                <h3>
                                    <?= htmlspecialchars($movie['title']) ?>
                                </h3>

                                <div class="release-date">
                                    RELEASE:
                                    <?= date(
                                        'd M Y',
                                        strtotime($movie['release_date'])
                                    ) ?>
                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        </section>

    <?php endif; ?>


    <!-- Cinema section menampilkan identitas lokasi HIMTI MOVIE di Surabaya. -->
    <section id="cinemas" class="cinema-section">

        <div class="container-custom">

            <div class="cinema-box">

                <div class="cinema-location">
                    SURABAYA
                </div>

                <h2>
                    HIMTI <span>MOVIE</span>
                </h2>

                <p>
                    Experience your favorite movies with HIMTI MOVIE.
                    Find movie schedules, choose your preferred showtime,
                    and book your ticket easily.
                </p>

            </div>

        </div>

    </section>


    <!-- Footer menampilkan informasi penutup website. -->
    <footer>

        <div class="container-custom">

            <div class="footer-content">

                <div class="footer-brand">
                    HIMTI MOVIE
                </div>

                <div class="footer-text">
                    © <?= date('Y') ?> HIMTI MOVIE — SURABAYA
                </div>

            </div>

        </div>

    </footer>


    <!-- Script search digunakan untuk menyaring kartu film berdasarkan judul. -->
    <script>

        const searchInput = document.getElementById('movieSearch');
        const movieCards = document.querySelectorAll('.movie-card');

        if (searchInput) {

            searchInput.addEventListener('input', function () {

                const keyword = this.value.toLowerCase();

                movieCards.forEach(function (card) {

                    const title = card.dataset.title;

                    if (title.includes(keyword)) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }

                });

            });

        }

    </script>

</body>
</html>
