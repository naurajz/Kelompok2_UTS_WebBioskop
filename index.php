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
$selected_genre = $_GET['genre'] ?? '';

$genre_result = $db->send_query("
    SELECT genre_id, genre_name
    FROM genres
    ORDER BY genre_name ASC
");

$genres = $genre_result['data'] ?? [];

// Mengambil data film berdasarkan genre yang dipilih.
if ($selected_genre !== '') {

    $movie_result = $db->send_query("
        SELECT 
            m.*,
            g.genre_name
        FROM movies m
        LEFT JOIN genres g 
            ON m.genre_id = g.genre_id
        WHERE m.genre_id = $1
        ORDER BY m.movie_id DESC
    ", [$selected_genre]);

} else {

    $movie_result = $db->send_query("
        SELECT 
            m.*,
            g.genre_name
        FROM movies m
        LEFT JOIN genres g 
            ON m.genre_id = g.genre_id
        ORDER BY m.movie_id DESC
    ");
}

$movies = $movie_result['data'] ?? [];

// Menentukan tanggal jadwal yang dipilih.
$selected_date = $_GET['date'] ?? date('Y-m-d');

// Mengambil jadwal film berdasarkan tanggal yang dipilih.
$showtime_result = $db->send_query("
    SELECT
        s.showtime_id,
        s.show_date,
        s.show_time,
        s.price,
        m.movie_id,
        m.title,
        m.poster,
        st.studio_name
    FROM showtimes s
    JOIN movies m
        ON s.movie_id = m.movie_id
    JOIN studios st
        ON s.studio_id = st.studio_id
    WHERE s.show_date = $1
    ORDER BY s.show_time ASC
", [$selected_date]);

$showtimes = $showtime_result['data'] ?? [];

// Mengambil film yang tanggal rilisnya masih akan datang.
$coming_result = $db->send_query("
    SELECT
        m.*,
        g.genre_name
    FROM movies m
    LEFT JOIN genres g
        ON m.genre_id = g.genre_id
    WHERE m.release_date > CURRENT_DATE
    ORDER BY m.release_date ASC
");

$coming_movies = $coming_result['data'] ?? [];
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>HIMTI MOVIE - Surabaya</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #080808;
            color: #ffffff;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* Navbar untuk navigasi utama website. */

        .navbar {
            height: 75px;
            background: #050505;
            border-bottom: 1px solid #222;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 60px;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .logo {
            font-size: 25px;
            font-weight: 900;
            letter-spacing: 2px;
            color: #ffffff;
        }

        .logo span {
            color: #e50914;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 35px;
            font-size: 13px;
            font-weight: bold;
        }

        .nav-menu a {
            color: #cccccc;
            transition: 0.2s;
        }

        .nav-menu a:hover {
            color: #ffffff;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .login-btn {
            background: #e50914;
            padding: 11px 20px;
            border-radius: 3px;
            color: white !important;
            font-size: 12px;
            font-weight: bold;
        }

        .login-btn:hover {
            background: #b20710;
        }

        /* Hero menampilkan gambar utama dan informasi singkat website. */

        .hero {
            min-height: 570px;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
        }

        .hero-bg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.45;
        }

        .hero-overlay {
            position: absolute;
            inset: 0;
            background:
                linear-gradient(
                    90deg,
                    rgba(0,0,0,0.95) 0%,
                    rgba(0,0,0,0.75) 45%,
                    rgba(0,0,0,0.35) 100%
                );
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 650px;
            margin-left: 8%;
        }

        .location {
            color: #e50914;
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 2px;
            margin-bottom: 20px;
        }

        .hero h1 {
            font-size: 65px;
            line-height: 0.95;
            margin-bottom: 25px;
            font-weight: 900;
        }

        .hero h1 span {
            color: #e50914;
        }

        .hero p {
            color: #cccccc;
            line-height: 1.7;
            max-width: 520px;
            margin-bottom: 30px;
        }

        .hero-btn {
            display: inline-block;
            background: #e50914;
            padding: 15px 28px;
            font-size: 13px;
            font-weight: bold;
            border-radius: 3px;
        }

        .hero-btn:hover {
            background: #b20710;
        }

        /* Container mengatur lebar konten utama website. */

        .container {
            width: 88%;
            max-width: 1250px;
            margin: auto;
        }

        .section {
            padding: 70px 0;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 30px;
            font-weight: 900;
        }

        .section-title span {
            color: #e50914;
        }

        /* Filter genre digunakan untuk menyaring film berdasarkan genre. */

        .genre-list {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }

        .genre-btn {
            border: 1px solid #333;
            background: #111;
            color: #aaa;
            padding: 9px 16px;
            border-radius: 3px;
            font-size: 12px;
        }

        .genre-btn:hover,
        .genre-btn.active {
            background: #e50914;
            color: white;
            border-color: #e50914;
        }

        /* Movie grid menampilkan daftar film dalam bentuk kartu. */

        .movie-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
        }

        .movie-card {
            background: #111;
            border: 1px solid #222;
            overflow: hidden;
            transition: 0.25s;
        }

        .movie-card:hover {
            transform: translateY(-5px);
            border-color: #e50914;
        }

        .poster {
            width: 100%;
            height: 350px;
            object-fit: cover;
            background: #1b1b1b;
        }

        .movie-info {
            padding: 18px;
        }

        .movie-title {
            font-size: 17px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .movie-genre {
            color: #999;
            font-size: 12px;
            margin-bottom: 15px;
        }

        .movie-btn {
            display: inline-block;
            color: #ffffff;
            border: 1px solid #e50914;
            padding: 9px 13px;
            font-size: 11px;
            font-weight: bold;
        }

        .movie-btn:hover {
            background: #e50914;
        }

        /* Search digunakan untuk mencari film berdasarkan judul. */

        .search-box {
            margin-bottom: 35px;
        }

        .search-box input {
            width: 100%;
            background: #111;
            border: 1px solid #333;
            color: white;
            padding: 15px;
            outline: none;
        }

        .search-box input:focus {
            border-color: #e50914;
        }

        /* Date list digunakan untuk memilih tanggal jadwal film. */

        .date-list {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding-bottom: 10px;
        }

        .date-item {
            min-width: 90px;
            padding: 15px 10px;
            text-align: center;
            background: #111;
            border: 1px solid #333;
        }

        .date-item.active {
            background: #e50914;
            border-color: #e50914;
        }

        .date-day {
            display: block;
            font-size: 11px;
            color: #aaa;
            margin-bottom: 5px;
        }

        .date-number {
            font-size: 20px;
            font-weight: bold;
        }

        /* Schedule menampilkan jadwal, studio, harga, dan waktu film. */

        .schedule-card {
            background: #111;
            border: 1px solid #222;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .schedule-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .schedule-meta {
            color: #888;
            font-size: 12px;
        }

        .time-btn {
            display: inline-block;
            border: 1px solid #e50914;
            padding: 10px 15px;
            color: #ffffff;
            font-size: 12px;
            margin-left: 7px;
        }

        .time-btn:hover {
            background: #e50914;
        }

        .price {
            color: #e50914;
            font-weight: bold;
            margin-top: 7px;
            font-size: 13px;
        }

        /* Cinema section menampilkan informasi lokasi bioskop. */

        .cinema-box {
            background: #111;
            border-left: 4px solid #e50914;
            padding: 30px;
        }

        .cinema-box h3 {
            font-size: 25px;
            margin-bottom: 10px;
        }

        .cinema-box p {
            color: #999;
            line-height: 1.6;
        }

        /* Footer menampilkan informasi penutup website. */

        footer {
            background: #030303;
            border-top: 1px solid #222;
            padding: 50px 0;
            margin-top: 50px;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            gap: 40px;
        }

        .footer-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .footer-text {
            color: #777;
            font-size: 12px;
            line-height: 1.7;
        }

        .copyright {
            margin-top: 35px;
            padding-top: 20px;
            border-top: 1px solid #222;
            color: #555;
            font-size: 11px;
        }

        /* Tampilan website disesuaikan untuk ukuran layar yang lebih kecil. */

        @media (max-width: 900px) {
            .navbar {
                padding: 0 25px;
            }

            .nav-menu {
                display: none;
            }

            .movie-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .hero h1 {
                font-size: 48px;
            }
        }

        @media (max-width: 600px) {
            .movie-grid {
                grid-template-columns: 1fr;
            }

            .hero-content {
                margin-left: 6%;
                margin-right: 6%;
            }

            .hero h1 {
                font-size: 40px;
            }

            .schedule-card {
                display: block;
            }

            .time-btn {
                display: inline-block;
                margin: 10px 5px 0 0;
            }

            .footer-content {
                display: block;
            }
        }
    </style>
</head>

<body>

<nav class="navbar">

    <a href="index.php" class="logo">
        HIMTI<span>MOVIE</span>
    </a>

    <div class="nav-menu">
        <a href="#cinemas">CINEMAS</a>
        <a href="#movies">MOVIES</a>
        <a href="#showtimes">SHOWTIMES</a>
    </div>

    <div class="nav-right">

        <?php if ($is_logged_in): ?>

            <span style="color:#aaa;font-size:13px;">
                Hi, <?= htmlspecialchars($username) ?>
            </span>

            <a href="logout.php" class="login-btn">
                LOG OUT
            </a>

        <?php else: ?>

            <a href="login.php" class="login-btn">
                LOGIN
            </a>

            <a href="register.php">
                REGISTER
            </a>

        <?php endif; ?>

    </div>

</nav>

<section class="hero">

    <img
        src="assets/hero-cinema.png"
        class="hero-bg"
        alt="Cinema"
    >

    <div class="hero-overlay"></div>

    <div class="hero-content">

        <div class="location">
            SURABAYA
        </div>

        <h1>
            EXPERIENCE<br>
            <span>THE MOVIES.</span>
        </h1>

        <p>
            Discover the latest movies, check showtimes,
            and book your favorite seats at HIMTI MOVIE.
        </p>

        <a href="#movies" class="hero-btn">
            EXPLORE MOVIES
        </a>

    </div>

</section>

<section class="section" id="movies">

    <div class="container">

        <div class="section-header">
            <h2 class="section-title">
                NOW <span>SHOWING</span>
            </h2>
        </div>

        <div class="search-box">
            <input
                type="text"
                id="movieSearch"
                placeholder="Search movies..."
            >
        </div>

        <div class="genre-list">

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

        <div class="movie-grid" id="movieGrid">

            <?php if (!empty($movies)): ?>

                <?php foreach ($movies as $movie): ?>

                    <div
                        class="movie-card"
                        data-title="<?= strtolower(htmlspecialchars($movie['title'])) ?>"
                    >

                        <?php if (!empty($movie['poster'])): ?>

                            <img
                                src="<?= htmlspecialchars($movie['poster']) ?>"
                                alt="<?= htmlspecialchars($movie['title']) ?>"
                                class="poster"
                            >

                        <?php else: ?>

                            <div
                                class="poster"
                                style="
                                    display:flex;
                                    align-items:center;
                                    justify-content:center;
                                    color:#555;
                                "
                            >
                                NO POSTER
                            </div>

                        <?php endif; ?>

                        <div class="movie-info">

                            <div class="movie-title">
                                <?= htmlspecialchars($movie['title']) ?>
                            </div>

                            <div class="movie-genre">

                                <?= htmlspecialchars(
                                    $movie['genre_name'] ?? 'Unknown Genre'
                                ) ?>

                                <?php if (!empty($movie['duration'])): ?>

                                    • <?= htmlspecialchars($movie['duration']) ?> min

                                <?php endif; ?>

                            </div>

                            <a
                                href="movie_detail.php?id=<?= $movie['movie_id'] ?>"
                                class="movie-btn"
                            >
                                READ SYNOPSIS
                            </a>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <p style="color:#777;">
                    No movies available.
                </p>

            <?php endif; ?>

        </div>

    </div>

</section>

<section class="section" id="showtimes">

    <div class="container">

        <div class="section-header">

            <h2 class="section-title">
                MOVIE <span>SHOWTIMES</span>
            </h2>

        </div>

        <div class="date-list">

            <?php for ($i = 0; $i < 7; $i++): ?>

                <?php
                $date = date(
                    'Y-m-d',
                    strtotime("+$i days")
                );

                $day = strtoupper(
                    date('D', strtotime($date))
                );

                $number = date(
                    'd',
                    strtotime($date)
                );
                ?>

                <a
                    href="index.php?date=<?= $date ?>#showtimes"
                    class="date-item <?= $selected_date === $date ? 'active' : '' ?>"
                >

                    <span class="date-day">
                        <?= $day ?>
                    </span>

                    <span class="date-number">
                        <?= $number ?>
                    </span>

                </a>

            <?php endfor; ?>

        </div>

        <div style="margin-top:30px;">

            <?php if (!empty($showtimes)): ?>

                <?php foreach ($showtimes as $showtime): ?>

                    <div class="schedule-card">

                        <div>

                            <div class="schedule-title">
                                <?= htmlspecialchars($showtime['title']) ?>
                            </div>

                            <div class="schedule-meta">
                                <?= htmlspecialchars($showtime['studio_name']) ?>
                            </div>

                            <div class="price">
                                Rp <?= number_format(
                                    (float)$showtime['price'],
                                    0,
                                    ',',
                                    '.'
                                ) ?>
                            </div>

                        </div>

                        <div>

                            <a
                                href="checkout.php?showtime_id=<?= $showtime['showtime_id'] ?>"
                                class="time-btn"
                            >
                                <?= date(
                                    'H:i',
                                    strtotime($showtime['show_time'])
                                ) ?>
                            </a>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <p style="color:#777;">
                    No showtimes available for this date.
                </p>

            <?php endif; ?>

        </div>

    </div>

</section>

<?php if (!empty($coming_movies)): ?>

<section class="section">

    <div class="container">

        <div class="section-header">

            <h2 class="section-title">
                COMING <span>SOON</span>
            </h2>

        </div>

        <div class="movie-grid">

            <?php foreach ($coming_movies as $movie): ?>

                <div class="movie-card">

                    <?php if (!empty($movie['poster'])): ?>

                        <img
                            src="<?= htmlspecialchars($movie['poster']) ?>"
                            alt="<?= htmlspecialchars($movie['title']) ?>"
                            class="poster"
                        >

                    <?php else: ?>

                        <div
                            class="poster"
                            style="
                                display:flex;
                                align-items:center;
                                justify-content:center;
                                color:#555;
                            "
                        >
                            NO POSTER
                        </div>

                    <?php endif; ?>

                    <div class="movie-info">

                        <div class="movie-title">
                            <?= htmlspecialchars($movie['title']) ?>
                        </div>

                        <div class="movie-genre">
                            <?= htmlspecialchars(
                                $movie['genre_name'] ?? 'Unknown Genre'
                            ) ?>
                        </div>

                        <div
                            style="
                                color:#e50914;
                                font-size:12px;
                                margin-top:10px;
                            "
                        >
                            Release:
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

<section class="section" id="cinemas">

    <div class="container">

        <div class="section-header">

            <h2 class="section-title">
                OUR <span>CINEMA</span>
            </h2>

        </div>

        <div class="cinema-box">

            <h3>
                HIMTI MOVIE — SURABAYA
            </h3>

            <p>
                Enjoy the latest movies with comfortable studios
                and convenient showtimes in Surabaya.
            </p>

        </div>

    </div>

</section>

<footer>

    <div class="container">

        <div class="footer-content">

            <div>

                <div class="footer-title">
                    HIMTI MOVIE
                </div>

                <div class="footer-text">
                    Your movie experience starts here.
                </div>

            </div>

            <div>

                <div class="footer-title">
                    SURABAYA
                </div>

                <div class="footer-text">
                    Movies • Showtimes • Tickets
                </div>

            </div>

        </div>

        <div class="copyright">
            © <?= date('Y') ?> HIMTI MOVIE. All Rights Reserved.
        </div>

    </div>

</footer>

<script>
    const searchInput = document.getElementById('movieSearch');
    const movieCards = document.querySelectorAll('.movie-card');

    searchInput.addEventListener('input', function () {

        const keyword = this.value.toLowerCase().trim();

        movieCards.forEach(function (card) {

            const title = card.dataset.title || '';

            if (title.includes(keyword)) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }

        });

    });
</script>

</body>
</html>
