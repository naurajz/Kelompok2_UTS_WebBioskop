<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = $isLoggedIn && ($_SESSION['role'] ?? '') === 'admin';

$dbHost = getenv("DB_HOST") ?: "localhost";
$dbPort = getenv("DB_PORT") ?: "5432";
$dbName = getenv("DB_NAME") ?: "bioskop";
$dbUser = getenv("DB_USER") ?: "postgres";
$dbPassword = getenv("DB_PASSWORD") ?: "12345678";

$conn = pg_connect(
    "host=$dbHost port=$dbPort dbname=$dbName user=$dbUser password=$dbPassword"
);

if (!$conn) {
    die("Database connection failed.");
}

// Mengambil data film dari database untuk ditampilkan pada halaman utama.
$movieResult = pg_query($conn, "
    SELECT
        m.movie_id,
        m.title,
        m.duration,
        m.description,
        g.genre_name
    FROM public.movies m
    LEFT JOIN public.genres g
        ON m.genre_id = g.genre_id
    ORDER BY m.movie_id ASC
");

if (!$movieResult) {
    die("Movie query failed: " . pg_last_error($conn));
}

$movies = [];

while ($row = pg_fetch_assoc($movieResult)) {
    $movies[] = [
        "movie_id" => $row["movie_id"],
        "title" => $row["title"],
        "duration" => $row["duration"],
        "description" => $row["description"],
        "genre_name" => $row["genre_name"]
    ];
}

// Mengambil daftar genre dari database untuk filter film.
$genreResult = pg_query($conn, "
    SELECT
        MIN(genre_id) AS genre_id,
        genre_name
    FROM public.genres
    GROUP BY genre_name
    ORDER BY genre_name
");

if (!$genreResult) {
    die("Genre query failed: " . pg_last_error($conn));
}

$genres = [];

while ($genre = pg_fetch_assoc($genreResult)) {
    $genres[] = $genre;
}

// Mengambil data jadwal tayang beserta studio dari database.
$showtimeResult = pg_query($conn, "
    SELECT
        s.showtime_id,
        s.movie_id,
        s.show_date,
        s.show_time,
        s.price,
        st.studio_name,
        m.title
    FROM public.showtimes s
    JOIN public.movies m
        ON s.movie_id = m.movie_id
    JOIN public.studios st
        ON s.studio_id = st.studio_id
    ORDER BY
        s.show_date ASC,
        s.show_time ASC
");

if (!$showtimeResult) {
    die("Showtime query failed: " . pg_last_error($conn));
}

// Menentukan poster film dan gambar cadangan yang digunakan pada halaman.
$posters = [
    "Avengers: Endgame" =>
        "https://image.tmdb.org/t/p/w500/or06FN3Dka5tukK1e9sl16pB3iy.jpg",

    "The Amazing Spider-Man 2" =>
        "https://image.tmdb.org/t/p/w500/dGjoPttcbKR5VWg1jQuNFB247KL.jpg",

    "Inside Out 2" =>
        "https://image.tmdb.org/t/p/w500/vpnVM9B6NMmQpWeZvzLvDESb2QY.jpg",

    "Ratatouille" =>
        "https://image.tmdb.org/t/p/w500/t3vaWRPSf6WjDSamIkKDs1iQWna.jpg",

    "Interstellar" =>
        "https://image.tmdb.org/t/p/w500/gEU2QniE6E77NI6lCU6MxlNBvIx.jpg",

    "The Conjuring" =>
        "https://image.tmdb.org/t/p/w500/wVYREutTvI2tmxr6ujrHT704wGF.jpg"
];

$fallback =
    "https://via.placeholder.com/500x750/151515/ffffff?text=HIMTI+MOVIE";

// Mengirim data jadwal tayang dari PHP ke JavaScript.
$showtimeData = [];

while ($row = pg_fetch_assoc($showtimeResult)) {
    $showtimeData[] = [
        "showtime_id" => $row["showtime_id"],
        "movie_id" => $row["movie_id"],
        "title" => $row["title"],
        "date" => $row["show_date"],
        "time" => date("H:i", strtotime($row["show_time"])),
        "studio" => $row["studio_name"],
        "price" => $row["price"]
    ];
}

// Membuat tujuh tanggal tayang secara berurutan mulai dari 5 Oktober 2026.
$dates = [];

$startDate = new DateTime("2026-10-05");

for ($i = 0; $i < 7; $i++) {
    $date = clone $startDate;
    $date->modify("+$i day");
    $dates[] = $date->format("Y-m-d");
}

// Menampilkan format nama hari, bulan, tanggal, dan tanggal lengkap.
function dayName($date)
{
    return strtoupper(date("D", strtotime($date)));
}

function monthName($date)
{
    return strtoupper(date("M", strtotime($date)));
}

function dayNumber($date)
{
    return date("d", strtotime($date));
}

function fullDate($date)
{
    return date("l, F d, Y", strtotime($date));
}

// Menentukan jenis hari dan harga tiket berdasarkan hari kerja atau akhir pekan.
function dayType($date)
{
    $day = (int) date("N", strtotime($date));

    if ($day >= 6) {
        return "WEEKEND";
    }

    return "WEEKDAY";
}

function ticketPrice($date)
{
    $day = (int) date("N", strtotime($date));

    if ($day >= 6) {
        return 45000;
    }

    return 35000;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>HIMTI MOVIE</title>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
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

/* Mengatur navigasi utama dan tombol akun sesuai status pengguna. */
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
    gap: 20px;
}

.nav-menu a {
    color: #ddd;
    font-size: 14px;
}

.nav-menu a:hover {
    color: #e50914;
}

.nav-button {
    background: #e50914;
    padding: 10px 20px;
    border-radius: 7px;
    color: white !important;
    display: inline-block;
    cursor: pointer;
    position: relative;
    z-index: 10001;
    pointer-events: auto;
}

.nav-button:hover {
    background: #b80710;
    color: white !important;
}

/* Menampilkan banner utama dengan identitas HIMTI MOVIE dan deskripsi singkat. */
.hero {
    margin-top: 72px;
}

.hero-image {
    height: 520px;
    position: relative;
    overflow: hidden;
}

.hero-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    filter: brightness(0.42);
}

.hero-image::after {
    content: "";
    position: absolute;
    inset: 0;
    background:
        linear-gradient(
            90deg,
            rgba(0,0,0,.9),
            rgba(0,0,0,.4),
            rgba(0,0,0,.1)
        );
}

.hero-content {
    position: absolute;
    z-index: 2;
    top: 50%;
    left: 8%;
    transform: translateY(-50%);
    max-width: 600px;
}

.hero-small {
    color: #e50914;
    font-size: 14px;
    font-weight: bold;
    letter-spacing: 4px;
    margin-bottom: 15px;
}

.hero-title {
    font-size: 65px;
    font-weight: 900;
    margin-bottom: 20px;
}

.hero-text {
    color: #ddd;
    font-size: 17px;
    line-height: 1.7;
}

.hero-badge {
    position: absolute;
    right: 45px;
    bottom: 30px;
    z-index: 3;
    padding: 10px 17px;
    background: rgba(0,0,0,.6);
    border: 1px solid #555;
    border-radius: 30px;
    font-size: 12px;
}

section.content-section {
    padding: 70px 7%;
}

.section-title {
    font-size: 32px;
    font-weight: 900;
    margin-bottom: 28px;
}

.section-title span {
    color: #e50914;
}

/* Menampilkan daftar film terbaru beserta filter berdasarkan genre. */
.genre-filter {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 30px;
}

.genre-btn {
    background: #181818;
    color: #bbb;
    border: 1px solid #333;
    padding: 9px 17px;
    border-radius: 25px;
    cursor: pointer;
}

.genre-btn.active,
.genre-btn:hover {
    background: #e50914;
    color: white;
    border-color: #e50914;
}

.movie-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 20px;
}

.movie-card {
    background: #121212;
    border: 1px solid #252525;
    border-radius: 12px;
    overflow: hidden;
    cursor: pointer;
    transition: .3s;
}

.movie-card:hover {
    transform: translateY(-7px);
    border-color: #e50914;
    box-shadow:
        0 15px 35px
        rgba(229,9,20,.18);
}

.movie-poster {
    width: 100%;
    aspect-ratio: 2 / 3;
    object-fit: cover;
}

.movie-info {
    padding: 14px;
}

.movie-title {
    font-size: 15px;
    font-weight: bold;
    line-height: 1.4;
    min-height: 42px;
}

.movie-meta {
    margin-top: 8px;
    color: #888;
    font-size: 12px;
}

.movie-button {
    margin-top: 13px;
    background: #e50914;
    text-align: center;
    padding: 9px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: bold;
}

/* Mengatur tampilan bagian jadwal film. */
.showtimes-section {
    background:
        linear-gradient(
            180deg,
            #101010,
            #080808
        );
    padding: 75px 7%;
}

.showtimes-header {
    text-align: center;
    margin-bottom: 35px;
}

.showtimes-header h2 {
    font-size: 38px;
    font-weight: 900;
}

.showtimes-header h2 span {
    color: #e50914;
}

.showtimes-header p {
    color: #888;
    margin-top: 10px;
}

/* Mengatur kalender pemilihan tanggal. */
.calendar {
    display: flex;
    gap: 14px;
    overflow-x: auto;
    padding: 5px 5px 18px;
    margin-bottom: 40px;
}

.calendar::-webkit-scrollbar {
    height: 5px;
}

.calendar::-webkit-scrollbar-thumb {
    background: #e50914;
    border-radius: 20px;
}

.calendar-card {
    min-width: 120px;
    min-height: 145px;
    background: #181818;
    border: 1px solid #333;
    border-radius: 16px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: .25s;
    flex-shrink: 0;
    padding: 10px;
}

.calendar-card:hover {
    border-color: #e50914;
    transform: translateY(-4px);
}

.calendar-card.active {
    background:
        linear-gradient(
            145deg,
            #e50914,
            #9d0008
        );
    border-color: #e50914;
    box-shadow:
        0 10px 30px
        rgba(229,9,20,.3);
}

.calendar-day {
    font-size: 11px;
    font-weight: bold;
    color: #888;
}

.calendar-card.active .calendar-day {
    color: white;
}

.calendar-number {
    font-size: 38px;
    font-weight: 900;
    margin: 4px 0;
}

.calendar-month {
    font-size: 11px;
    font-weight: bold;
    color: #999;
}

.calendar-card.active .calendar-month {
    color: white;
}

.calendar-type {
    margin-top: 7px;
    font-size: 9px;
    font-weight: bold;
    color: #aaa;
    letter-spacing: .5px;
}

.calendar-card.active .calendar-type {
    color: white;
}

.calendar-price {
    margin-top: 4px;
    font-size: 10px;
    font-weight: bold;
    color: #e50914;
}

.calendar-card.active .calendar-price {
    color: white;
}

/* Mengatur informasi tanggal yang sedang dipilih. */
.selected-date-title {
    font-size: 22px;
    font-weight: 900;
    margin-bottom: 28px;
}

.selected-date-title > span {
    color: #e50914;
}

.selected-date-type {
    color: #e50914;
    font-size: 13px;
    font-weight: bold;
    margin-top: 8px;
}

/* Mengatur tampilan daftar jadwal film. */
.schedule-list {
    display: flex;
    flex-direction: column;
    gap: 22px;
}

.schedule-movie {
    background: #111;
    border: 1px solid #282828;
    border-radius: 16px;
    padding: 23px;
    display: flex;
    align-items: center;
    gap: 25px;
}

.schedule-poster {
    width: 75px;
    height: 105px;
    object-fit: cover;
    border-radius: 8px;
    flex-shrink: 0;
}

.schedule-info {
    width: 220px;
    flex-shrink: 0;
}

.schedule-title {
    font-size: 18px;
    font-weight: 900;
    line-height: 1.35;
    margin-bottom: 8px;
}

.schedule-genre {
    color: #888;
    font-size: 12px;
}

.schedule-times {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

/* Mengatur tampilan kartu waktu tayang. */
.time-card {
    min-width: 135px;
    background: #181818;
    border: 1px solid #333;
    border-radius: 13px;
    padding: 13px;
    cursor: pointer;
    transition: .25s;
    text-align: center;
}

.time-card:hover {
    background: #241010;
    border-color: #e50914;
    transform: translateY(-3px);
}

.clock {
    width: 39px;
    height: 39px;
    border: 2px solid #e50914;
    border-radius: 50%;
    margin: 0 auto 9px;
    position: relative;
    background: #0c0c0c;
}

.clock::before {
    content: "";
    position: absolute;
    width: 2px;
    height: 12px;
    background: white;
    left: 17px;
    top: 7px;
    border-radius: 5px;
}

.clock::after {
    content: "";
    position: absolute;
    width: 10px;
    height: 2px;
    background: white;
    left: 18px;
    top: 18px;
    transform: rotate(35deg);
    transform-origin: left;
}

.time {
    font-size: 19px;
    font-weight: 900;
    margin-bottom: 5px;
}

.studio {
    color: #999;
    font-size: 11px;
    margin-bottom: 5px;
}

.price {
    color: #e50914;
    font-size: 11px;
    font-weight: bold;
}

/* Menampilkan pesan jika tidak ada jadwal. */
.no-schedule {
    text-align: center;
    padding: 45px;
    color: #777;
    border: 1px dashed #333;
    border-radius: 15px;
}

/* Menampilkan informasi mengenai HIMTI Movie. */
.about {
    text-align: center;
    background: #101010;
}

.about p {
    max-width: 650px;
    margin: auto;
    color: #888;
    line-height: 1.8;
}

/* Mengatur tampilan modal. */
.modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.85);
    z-index: 10000;
    align-items: center;
    justify-content: center;
    padding: 25px;
}

.modal.show {
    display: flex;
}

.modal-box {
    width: min(700px, 95vw);
    background: #111;
    border: 1px solid #333;
    border-radius: 18px;
    padding: 35px;
    position: relative;
}

.close {
    position: absolute;
    right: 18px;
    top: 15px;
    width: 38px;
    height: 38px;
    border: 0;
    border-radius: 50%;
    background: #222;
    color: white;
    font-size: 23px;
    cursor: pointer;
}

.close:hover {
    background: #e50914;
}

.modal h2 {
    font-size: 30px;
    margin-bottom: 12px;
}

.modal p {
    color: #aaa;
    line-height: 1.7;
    margin-bottom: 20px;
}

/* Mengatur tampilan website pada ukuran layar yang berbeda. */
@media(max-width:1200px) {
    .movie-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

@media(max-width:850px) {

    .navbar {
        padding: 0 20px;
    }

    .nav-menu {
        gap: 10px;
    }

    .nav-menu > a:not(.nav-button) {
        display: none;
    }

    .nav-button {
        padding: 9px 12px;
        font-size: 12px;
    }

    .hero-image {
        height: 450px;
    }

    .hero-title {
        font-size: 45px;
    }

    .movie-grid {
        grid-template-columns: repeat(3, 1fr);
    }

    .schedule-movie {
        flex-direction: column;
        align-items: flex-start;
    }

    .schedule-info {
        width: 100%;
    }

    .schedule-times {
        width: 100%;
    }
}

@media(max-width:550px) {

    .movie-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }

    .hero-title {
        font-size: 38px;
    }

    .hero-text {
        font-size: 14px;
    }

    .showtimes-section,
    section.content-section {
        padding-left: 5%;
        padding-right: 5%;
    }

    .calendar-card {
        min-width: 105px;
    }
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

        <a href="#home">
            Home
        </a>

        <a href="#movies">
            Movies
        </a>

        <a href="#showtimes">
            Showtimes
        </a>

        <a href="#about">
            About
        </a>

        <?php if ($isAdmin): ?>

            <a
                href="admin/genre.php"
                class="nav-button"
            >
                Dashboard
            </a>

        <?php endif; ?>

        <?php if ($isLoggedIn): ?>

            <a
                href="logout.php"
                class="nav-button"
            >
                Logout
            </a>

        <?php else: ?>

            <a
                href="register.php"
                class="nav-button"
            >
                Register
            </a>

            <a
                href="login.php"
                class="nav-button"
            >
                Login
            </a>

        <?php endif; ?>

    </div>

</nav>

<section class="hero" id="home">

    <div class="hero-image">

        <img
            src="assets/cinema.jpg"
            alt="Cinema"
            onerror="
                this.src='https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=1600&q=85';
            "
        >

        <div class="hero-content">

            <div class="hero-small">
                WELCOME TO
            </div>

            <div class="hero-title">
                HIMTI MOVIE
            </div>

            <div class="hero-text">
                Find your favorite movies, check showtimes,
                and buy tickets easily.
            </div>

        </div>

        <div class="hero-badge">
            HIMTI MOVIE • SURABAYA
        </div>

    </div>

</section>

<section class="content-section" id="movies">

    <h2 class="section-title">
        Latest
        <span>Movies</span>
    </h2>

    <div class="genre-filter">

        <button
            class="genre-btn active"
            onclick="filterMovies('all', this)"
        >
            All
        </button>

        <?php foreach ($genres as $genre): ?>

            <button
                class="genre-btn"
                onclick="
                    filterMovies(
                        '<?php
                        echo htmlspecialchars(
                            $genre["genre_name"],
                            ENT_QUOTES
                        );
                        ?>',
                        this
                    )
                "
            >
                <?php
                echo htmlspecialchars(
                    $genre["genre_name"]
                );
                ?>
            </button>

        <?php endforeach; ?>

    </div>

    <div class="movie-grid">

        <?php foreach ($movies as $movie): ?>

            <?php

            $title = $movie["title"];

            $poster =
                $posters[$title]
                ?? $fallback;

            $genre =
                $movie["genre_name"]
                ?? "Movie";

            $description =
                $movie["description"]
                ?? "Synopsis not available.";

            ?>

            <div
                class="movie-card"
                data-genre="<?php
                    echo htmlspecialchars(
                        $genre,
                        ENT_QUOTES
                    );
                ?>"
                onclick="
                    openMovie(
                        '<?php
                        echo htmlspecialchars(
                            $title,
                            ENT_QUOTES
                        );
                        ?>',
                        '<?php
                        echo htmlspecialchars(
                            $description,
                            ENT_QUOTES
                        );
                        ?>'
                    )
                "
            >

                <img
                    src="<?php
                        echo htmlspecialchars(
                            $poster
                        );
                    ?>"
                    class="movie-poster"
                    alt="<?php
                        echo htmlspecialchars(
                            $title
                        );
                    ?>"
                    onerror="
                        this.src='<?php
                            echo $fallback;
                        ?>';
                    "
                >

                <div class="movie-info">

                    <div class="movie-title">
                        <?php
                        echo htmlspecialchars(
                            $title
                        );
                        ?>
                    </div>

                    <div class="movie-meta">

                        <?php
                        echo htmlspecialchars(
                            $genre
                        );
                        ?>

                        •

                        <?php
                        echo $movie["duration"];
                        ?>

                        min

                    </div>

                    <div class="movie-button">
                        VIEW DETAILS
                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

</section>

<section
    class="showtimes-section"
    id="showtimes"
>

    <div class="showtimes-header">

        <h2>
            Movie
            <span>Showtimes</span>
        </h2>

        <p>
            Choose your date and find your preferred movie session.
        </p>

    </div>

    <div class="calendar">

        <?php foreach ($dates as $index => $date): ?>

            <div
                class="
                    calendar-card
                    <?php
                    echo $index === 0
                        ? "active"
                        : "";
                    ?>
                "
                data-date="<?php
                    echo $date;
                ?>"
                onclick="
                    selectShowtimeDate(
                        '<?php echo $date; ?>',
                        this
                    )
                "
            >

                <div class="calendar-day">
                    <?php
                    echo dayName($date);
                    ?>
                </div>

                <div class="calendar-number">
                    <?php
                    echo dayNumber($date);
                    ?>
                </div>

                <div class="calendar-month">
                    <?php
                    echo monthName($date);
                    ?>
                </div>

                <div class="calendar-type">
                    <?php
                    echo dayType($date);
                    ?>
                </div>

                <div class="calendar-price">
                    Rp
                    <?php
                    echo number_format(
                        ticketPrice($date),
                        0,
                        ",",
                        "."
                    );
                    ?>
                </div>

            </div>

        <?php endforeach; ?>

    </div>

    <div class="selected-date-title">

        <span>
            SHOWTIMES
        </span>

        <div
            id="selectedDateText"
            style="
                color:white;
                margin-top:7px;
                font-size:24px;
            "
        >

            <?php
            if (!empty($dates)) {
                echo fullDate($dates[0]);
            }
            ?>

        </div>

        <div
            id="selectedDateType"
            class="selected-date-type"
        >
            WEEKDAY • Rp 35.000
        </div>

    </div>

    <div
        class="schedule-list"
        id="scheduleList"
    >
    </div>

</section>

<section
    class="content-section about"
    id="about"
>

    <h2 class="section-title">
        About
        <span>HIMTI MOVIE</span>
    </h2>

    <p>
        HIMTI Movie is a movie information and ticket booking
        website designed to make it easier for users to discover
        movies, check showtimes, and book cinema tickets.
    </p>

</section>

<div
    class="modal"
    id="movieModal"
>

    <div class="modal-box">

        <button
            class="close"
            onclick="closeMovie()"
        >
            ×
        </button>

        <h2 id="modalTitle">
            Movie
        </h2>

        <p id="modalDescription">
            Synopsis
        </p>

    </div>

</div>

<script>

// Menentukan apakah pengguna sedang login atau belum.
const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;

// Mengirim data jadwal tayang dari PHP ke JavaScript.
const showtimes =
    <?php
    echo json_encode(
        $showtimeData,
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );
    ?>;

// Mengirim data film dari PHP ke JavaScript.
const movies =
    <?php
    echo json_encode(
        $movies,
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );
    ?>;

// Mengirim data poster film dari PHP ke JavaScript.
const posters =
    <?php
    echo json_encode(
        $posters,
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );
    ?>;

const fallbackPoster =
    <?php
    echo json_encode($fallback);
    ?>;

// Memformat angka harga menjadi tampilan rupiah.
function rupiah(number) {

    return Number(number)
        .toLocaleString("id-ID");

}

// Memformat tanggal menjadi nama hari, bulan, dan tahun.
function formatFullDate(date) {

    const d =
        new Date(
            date + "T00:00:00"
        );

    return d.toLocaleDateString(
        "en-US",
        {
            weekday: "long",
            month: "long",
            day: "numeric",
            year: "numeric"
        }
    );

}

// Menampilkan jadwal film sesuai tanggal yang dipilih pengguna.
function selectShowtimeDate(
    date,
    clickedCard
) {

    document
        .querySelectorAll(
            ".calendar-card"
        )
        .forEach(card => {

            card.classList.remove(
                "active"
            );

        });

    clickedCard.classList.add(
        "active"
    );

    document.getElementById(
        "selectedDateText"
    ).textContent =
        formatFullDate(date);

    const selectedDate =
        new Date(
            date + "T00:00:00"
        );

    const day =
        selectedDate.getDay();

    const isWeekend =
        day === 0 ||
        day === 6;

    const type =
        isWeekend
            ? "WEEKEND"
            : "WEEKDAY";

    const price =
        isWeekend
            ? 45000
            : 35000;

    document.getElementById(
        "selectedDateType"
    ).textContent =
        type +
        " • Rp " +
        rupiah(price);

    const schedule =
        document.getElementById(
            "scheduleList"
        );

    schedule.innerHTML = "";

    movies.forEach(movie => {

        const movieShowtimes =
            showtimes.filter(
                item =>
                    String(
                        item.movie_id
                    ) ===
                    String(
                        movie.movie_id
                    )
                    &&
                    item.date === date
            );

        const poster =
            posters[movie.title]
            ||
            fallbackPoster;

        const row =
            document.createElement(
                "div"
            );

        row.className =
            "schedule-movie";

        let timesHTML = "";

        movieShowtimes.forEach(
            item => {

                timesHTML += `

                    <div
                        class="time-card"
                        onclick="
                            buyTicket(
                                '${item.showtime_id}'
                            )
                        "
                    >

                        <div class="clock"></div>

                        <div class="time">
                            ${item.time}
                        </div>

                        <div class="studio">
                            ${item.studio}
                        </div>

                        <div class="price">
                            Rp ${rupiah(item.price)}
                        </div>

                    </div>

                `;

            }
        );

        row.innerHTML = `

            <img
                src="${poster}"
                class="schedule-poster"
                alt="${movie.title}"
                onerror="
                    this.src='${fallbackPoster}';
                "
            >

            <div class="schedule-info">

                <div class="schedule-title">
                    ${movie.title}
                </div>

                <div class="schedule-genre">
                    ${movie.genre_name || "Movie"}
                    •
                    ${movie.duration} min
                </div>

            </div>

            <div class="schedule-times">
                ${timesHTML}
            </div>

        `;

        schedule.appendChild(row);

    });

}

// Mengarahkan pengguna ke login atau checkout saat memilih jadwal tiket.
function buyTicket(showtimeId) {

    if (!isLoggedIn) {

        sessionStorage.setItem(
            "pendingShowtimeId",
            showtimeId
        );

        window.location.href = "login.php";

        return;
    }

    window.location.href =
        "checkout.php?showtime_id=" +
        encodeURIComponent(showtimeId);

}

// Menyaring kartu film berdasarkan genre yang dipilih pengguna.
function filterMovies(
    genre,
    button
) {

    document
        .querySelectorAll(
            ".genre-btn"
        )
        .forEach(item => {

            item.classList.remove(
                "active"
            );

        });

    button.classList.add(
        "active"
    );

    document
        .querySelectorAll(
            ".movie-card"
        )
        .forEach(card => {

            const cardGenre =
                card.dataset.genre.trim();

            if (
                genre === "all"
                ||
                cardGenre === genre
            ) {

                card.style.display = "";

            } else {

                card.style.display = "none";

            }

        });

}

// Mengatur tampilan detail dan penutupan jendela informasi film.
function openMovie(
    title,
    description
) {

    document.getElementById(
        "modalTitle"
    ).textContent =
        title;

    document.getElementById(
        "modalDescription"
    ).textContent =
        description;

    document.getElementById(
        "movieModal"
    ).classList.add(
        "show"
    );

}

function closeMovie() {

    document.getElementById(
        "movieModal"
    ).classList.remove(
        "show"
    );

}

// Menutup modal ketika pengguna mengklik area di luar kotak detail.
document
    .getElementById(
        "movieModal"
    )
    .addEventListener(
        "click",
        function(event) {

            if (
                event.target === this
            ) {

                closeMovie();

            }

        }
    );

// Menampilkan jadwal untuk tanggal pertama ketika halaman selesai dimuat.
document.addEventListener(
    "DOMContentLoaded",
    function() {

        const firstCard =
            document.querySelector(
                ".calendar-card"
            );

        if (firstCard) {

            selectShowtimeDate(
                "2026-10-05",
                firstCard
            );

        }

    }
);

// Meneruskan pengguna ke checkout setelah login berhasil.
if (isLoggedIn) {

    const pendingShowtimeId =
        sessionStorage.getItem(
            "pendingShowtimeId"
        );

    if (pendingShowtimeId) {

        sessionStorage.removeItem(
            "pendingShowtimeId"
        );

        window.location.href =
            "checkout.php?showtime_id=" +
            encodeURIComponent(
                pendingShowtimeId
            );

    }

}

</script>

</body>
</html>
