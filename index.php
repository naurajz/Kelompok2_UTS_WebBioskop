<?php

require_once "bootstrap.php";

$page_title = "HIMTI MOVIE";

$movies = [];
$genres = [];
$schedules = [];
$coming_soon = [];
$upcoming_showtimes = [];

$selected_date = $_GET['date'] ?? date('Y-m-d');
$selected_genre = $_GET['genre'] ?? '';

$error_msg = '';

try {

    $db = new DBConnection();


    /* =====================================================
       MOVIES
    ===================================================== */

    $movie_query = "
        SELECT
            m.*,
            g.genre_name
        FROM movies m
        LEFT JOIN genres g
            ON m.genre_id = g.genre_id
        ORDER BY m.movie_id DESC
    ";

    $movie_result = $db->send_query($movie_query);

    if ($movie_result['success']) {
        $movies = $movie_result['data'];
    }


    /* =====================================================
       GENRES
    ===================================================== */

    $genre_query = "
        SELECT *
        FROM genres
        ORDER BY genre_name ASC
    ";

    $genre_result = $db->send_query($genre_query);

    if ($genre_result['success']) {
        $genres = $genre_result['data'];
    }


    /* =====================================================
       SCHEDULE SESUAI TANGGAL
    ===================================================== */

    $schedule_query = "
        SELECT
            s.showtime_id,
            s.show_date,
            s.show_time,
            s.price,
            m.movie_id,
            m.title,
            m.poster,
            st.studio_name,
            st.capacity
        FROM showtimes s
        JOIN movies m
            ON s.movie_id = m.movie_id
        JOIN studios st
            ON s.studio_id = st.studio_id
        WHERE s.show_date = $1
        ORDER BY s.show_time ASC
    ";

    $schedule_result = $db->send_query(
        $schedule_query,
        [$selected_date]
    );

    if ($schedule_result['success']) {
        $schedules = $schedule_result['data'];
    }


    /* =====================================================
       COMING SOON
    ===================================================== */

    $coming_query = "
        SELECT
            m.*,
            g.genre_name
        FROM movies m
        LEFT JOIN genres g
            ON m.genre_id = g.genre_id
        WHERE m.release_date > CURRENT_DATE
        ORDER BY m.release_date ASC
        LIMIT 4
    ";

    $coming_result = $db->send_query($coming_query);

    if ($coming_result['success']) {
        $coming_soon = $coming_result['data'];
    }


    /* =====================================================
       UPCOMING SHOWTIMES
    ===================================================== */

    $upcoming_query = "
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
        WHERE s.show_date >= CURRENT_DATE
        ORDER BY
            s.show_date ASC,
            s.show_time ASC
        LIMIT 6
    ";

    $upcoming_result = $db->send_query($upcoming_query);

    if ($upcoming_result['success']) {
        $upcoming_showtimes = $upcoming_result['data'];
    }

} catch (Exception $e) {

    $error_msg = $e->getMessage();

}


/* =====================================================
   LOGIN
===================================================== */

$is_logged_in = isset($_SESSION['user_id']);

$username =
    $_SESSION['user']['username']
    ?? 'Pengguna';

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        HIMTI MOVIE - Surabaya
    </title>


    <style>

        /* =====================================================
           RESET
        ===================================================== */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        html {
            scroll-behavior: smooth;
        }


        body {
            background: #070707 !important;
            color: #ffffff !important;
            font-family: Arial, Helvetica, sans-serif;
        }


        a {
            text-decoration: none;
        }


        /* =====================================================
           NAVBAR
        ===================================================== */

        .top-bar {
            background: #050505;
            border-bottom: 1px solid #252525;
            padding: 18px 6%;
        }


        .navbar-content {
            display: flex;
            align-items: center;
            gap: 25px;
        }


        .logo {
            color: #ffffff;
            font-size: 26px;
            font-weight: 900;
            letter-spacing: 1px;
            white-space: nowrap;
        }


        .logo span {
            color: #e50914;
        }


        .location {
            color: #999999;
            font-size: 13px;
            white-space: nowrap;
        }


        .location strong {
            color: #ffffff;
        }


        .search-box {
            flex: 1;
            max-width: 430px;
            margin-left: auto;
        }


        .search-box input {
            width: 100%;
            background: #151515;
            border: 1px solid #333333;
            border-radius: 25px;
            padding: 11px 20px;
            color: #ffffff;
            outline: none;
        }


        .search-box input:focus {
            border-color: #e50914;
        }


        .search-box input::placeholder {
            color: #777777;
        }


        .account {
            display: flex;
            align-items: center;
            gap: 12px;
            white-space: nowrap;
        }


        .account a {
            color: #ffffff;
            font-size: 13px;
            font-weight: bold;
        }


        .login-btn {
            border: 1px solid #e50914;
            border-radius: 5px;
            padding: 8px 16px;
        }


        .login-btn:hover {
            background: #e50914;
        }


        /* =====================================================
           MENU
        ===================================================== */

        .menu-bar {
            background: #0d0d0d;
            border-bottom: 1px solid #222222;
            padding: 0 6%;
        }


        .menu {
            display: flex;
            list-style: none;
            gap: 40px;
            margin: 0;
            padding: 0;
        }


        .menu a {
            display: block;
            color: #cccccc;
            font-size: 13px;
            font-weight: bold;
            padding: 16px 0;
        }


        .menu a:hover {
            color: #e50914;
        }


        /* =====================================================
           HERO
        ===================================================== */

        .hero {
            height: 620px;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
        }


        .hero-image {
            position: absolute;
            inset: 0;

            width: 100%;
            height: 100%;

            object-fit: cover;
            object-position: center;

            display: block;
        }


        .hero-overlay {
            position: absolute;
            inset: 0;

            background:
                linear-gradient(
                    90deg,
                    rgba(0,0,0,.96) 0%,
                    rgba(0,0,0,.82) 30%,
                    rgba(0,0,0,.30) 68%,
                    rgba(0,0,0,.60) 100%
                );
        }


        .hero-content {
            position: relative;
            z-index: 2;

            width: 88%;
            max-width: 1250px;

            margin: auto;
        }


        .hero-label {
            color: #e50914;

            font-size: 14px;
            font-weight: bold;

            letter-spacing: 3px;

            margin-bottom: 18px;
        }


        .hero h1 {
            font-size: 60px;

            font-weight: 900;

            line-height: 1;

            margin-bottom: 22px;

            max-width: 700px;
        }


        .hero h1 span {
            color: #e50914;
        }


        .hero p {
            color: #dddddd;

            line-height: 1.7;

            max-width: 550px;

            margin-bottom: 28px;

            font-size: 15px;
        }


        .hero-buttons {
            display: flex;
            gap: 12px;
        }


        .btn-red {
            display: inline-block;

            background: #e50914;

            color: #ffffff;

            padding: 12px 24px;

            border-radius: 4px;

            font-size: 13px;

            font-weight: bold;
        }


        .btn-red:hover {
            background: #b20710;
            color: #ffffff;
        }


        .btn-outline {
            display: inline-block;

            border: 1px solid #777777;

            color: #ffffff;

            padding: 11px 24px;

            border-radius: 4px;

            font-size: 13px;

            font-weight: bold;
        }


        .btn-outline:hover {
            border-color: #ffffff;
            color: #ffffff;
        }


        /* =====================================================
           GENERAL SECTION
        ===================================================== */

        .section {
            width: 88%;
            max-width: 1250px;

            margin: 70px auto;
        }


        .section-heading {
            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 25px;
        }


        .section-heading h2 {
            font-size: 27px;

            font-weight: 900;
        }


        .section-heading h2::before {
            content: "";

            display: inline-block;

            width: 5px;
            height: 27px;

            background: #e50914;

            margin-right: 12px;

            vertical-align: middle;
        }


        .small-red-label {
            color: #e50914;

            font-size: 11px;

            font-weight: bold;

            letter-spacing: 3px;

            margin-bottom: 7px;
        }


        .view-all {
            color: #e50914;

            font-size: 13px;

            font-weight: bold;
        }


        .view-all:hover {
            color: #ffffff;
        }


        /* =====================================================
           DATE
        ===================================================== */

        .date-section {
            margin-top: -30px;

            position: relative;

            z-index: 5;
        }


        .date-box {
            background: #111111;

            border: 1px solid #282828;

            border-radius: 8px;

            padding: 24px;
        }


        .date-title {
            color: #aaaaaa;

            font-size: 12px;

            font-weight: bold;

            letter-spacing: 2px;

            margin-bottom: 17px;
        }


        .date-list {
            display: flex;

            gap: 10px;

            overflow-x: auto;
        }


        .date-item {
            min-width: 105px;

            text-align: center;

            color: #bbbbbb;

            background: #191919;

            border: 1px solid #292929;

            border-radius: 6px;

            padding: 13px 10px;
        }


        .date-item:hover {
            border-color: #e50914;

            color: #ffffff;
        }


        .date-item.active {
            background: #e50914;

            border-color: #e50914;

            color: #ffffff;
        }


        .date-day {
            display: block;

            font-size: 11px;

            margin-bottom: 5px;
        }


        .date-number {
            display: block;

            font-size: 21px;

            font-weight: bold;
        }


        .date-month {
            display: block;

            font-size: 10px;
        }


        /* =====================================================
           GENRE
        ===================================================== */

        .genre-filter {
            display: flex;

            gap: 9px;

            flex-wrap: wrap;

            margin-bottom: 25px;
        }


        .genre-btn {
            color: #aaaaaa;

            border: 1px solid #333333;

            background: #111111;

            border-radius: 20px;

            padding: 8px 17px;

            font-size: 12px;
        }


        .genre-btn:hover,
        .genre-btn.active {
            color: #ffffff;

            background: #e50914;

            border-color: #e50914;
        }


        /* =====================================================
           MOVIE GRID
        ===================================================== */

        .movie-grid {
            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 22px;
        }


        .movie-card {
            background: #111111;

            border: 1px solid #242424;

            border-radius: 7px;

            overflow: hidden;

            transition: .3s;
        }


        .movie-card:hover {
            transform: translateY(-7px);

            border-color: #e50914;

            box-shadow:
                0 15px 35px
                rgba(229,9,20,.12);
        }


        .poster,
        .no-poster {
            width: 100%;
            height: 350px;
        }


        .poster {
            display: block;

            object-fit: cover;
        }


        .no-poster {
            display: flex;

            align-items: center;

            justify-content: center;

            background:
                linear-gradient(
                    145deg,
                    #222222,
                    #080808
                );

            color: #555555;

            font-weight: bold;
        }


        .movie-info {
            padding: 17px;
        }


        .movie-title {
            font-size: 17px;

            font-weight: bold;

            margin-bottom: 7px;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        .genre {
            color: #888888;

            font-size: 12px;

            margin-bottom: 15px;
        }


        .movie-buttons {
            display: flex;

            gap: 7px;
        }


        .movie-buttons a {
            flex: 1;

            text-align: center;

            padding: 9px 5px;

            border-radius: 4px;

            font-size: 10px;

            font-weight: bold;
        }


        .synopsis-btn {
            color: #ffffff;

            border: 1px solid #444444;
        }


        .synopsis-btn:hover {
            border-color: #aaaaaa;

            color: #ffffff;
        }


        .ticket-btn {
            background: #e50914;

            color: #ffffff;
        }


        .ticket-btn:hover {
            background: #b20710;

            color: #ffffff;
        }


        /* =====================================================
           SCHEDULE
        ===================================================== */

        .schedule-container {
            background: #101010;

            border: 1px solid #252525;

            border-radius: 8px;

            overflow: hidden;
        }


        .schedule-card {
            display: flex;

            align-items: center;

            gap: 22px;

            padding: 20px 24px;

            border-bottom: 1px solid #252525;
        }


        .schedule-card:last-child {
            border-bottom: none;
        }


        .schedule-poster {
            width: 65px;

            height: 88px;

            object-fit: cover;

            border-radius: 4px;

            background: #222222;

            flex-shrink: 0;
        }


        .schedule-info {
            flex: 1;
        }


        .schedule-title {
            font-size: 17px;

            font-weight: bold;

            margin-bottom: 6px;
        }


        .schedule-studio {
            color: #888888;

            font-size: 12px;
        }


        .schedule-times {
            display: flex;

            gap: 8px;

            align-items: center;
        }


        .time-box {
            background: #191919;

            border: 1px solid #3a3a3a;

            border-radius: 4px;

            padding: 9px 13px;

            text-align: center;

            min-width: 95px;
        }


        .time {
            display: block;

            font-weight: bold;

            font-size: 13px;
        }


        .price {
            display: block;

            color: #e50914;

            font-size: 10px;

            margin-top: 3px;
        }


        .empty-state {
            padding: 45px;

            text-align: center;

            color: #777777;
        }


        /* =====================================================
           UPCOMING SHOWTIMES
        ===================================================== */

        .upcoming-grid {
            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 20px;
        }


        .upcoming-card {
            background: #101010;

            border: 1px solid #292929;

            border-radius: 10px;

            overflow: hidden;

            transition: .3s;
        }


        .upcoming-card:hover {
            transform: translateY(-7px);

            border-color: #e50914;

            box-shadow:
                0 15px 35px
                rgba(229,9,20,.14);
        }


        .upcoming-poster {
            height: 280px;

            position: relative;

            overflow: hidden;

            background: #181818;
        }


        .upcoming-poster img {
            width: 100%;

            height: 100%;

            object-fit: cover;
        }


        .upcoming-no-poster {
            width: 100%;

            height: 100%;

            display: flex;

            align-items: center;

            justify-content: center;

            text-align: center;

            color: #555555;

            font-weight: 900;

            background:
                linear-gradient(
                    145deg,
                    #222222,
                    #080808
                );
        }


        .date-badge {
            position: absolute;

            top: 14px;
            left: 14px;

            background: #e50914;

            color: white;

            padding: 8px 12px;

            border-radius: 5px;

            font-size: 11px;

            font-weight: 800;
        }


        .upcoming-content {
            padding: 18px;
        }


        .upcoming-content h3 {
            font-size: 18px;

            font-weight: 800;

            margin-bottom: 5px;
        }


        .upcoming-studio {
            color: #777777;

            font-size: 12px;

            margin-bottom: 17px;
        }


        .upcoming-bottom {
            display: flex;

            justify-content: space-between;

            align-items: center;
        }


        .upcoming-time {
            display: block;

            font-size: 18px;

            font-weight: 800;
        }


        .upcoming-price {
            display: block;

            color: #e50914;

            font-size: 11px;

            margin-top: 3px;
        }


        .small-buy {
            background: #e50914;

            color: white;

            padding: 9px 17px;

            border-radius: 4px;

            font-size: 11px;

            font-weight: 800;
        }


        .small-buy:hover {
            background: #b20710;

            color: white;
        }


        /* =====================================================
           COMING SOON
        ===================================================== */

        .coming-movie-grid {
            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 20px;
        }


        .coming-movie {
            background: #111111;

            border: 1px solid #292929;

            border-radius: 9px;

            overflow: hidden;

            transition: .3s;
        }


        .coming-movie:hover {
            transform: translateY(-6px);

            border-color: #e50914;
        }


        .coming-poster {
            height: 320px;

            background: #181818;
        }


        .coming-poster img {
            width: 100%;

            height: 100%;

            object-fit: cover;
        }


        .coming-info {
            padding: 17px;
        }


        .coming-date {
            color: #e50914;

            font-size: 11px;

            font-weight: 800;
        }


        .coming-info h3 {
            font-size: 17px;

            margin: 8px 0 6px;
        }


        .coming-info p {
            color: #777777;

            font-size: 12px;

            margin-bottom: 15px;
        }


        .coming-link {
            color: white;

            font-size: 11px;

            font-weight: 800;
        }


        .coming-link:hover {
            color: #e50914;
        }


        /* =====================================================
           CINEMA
        ===================================================== */

        .cinema-box {
            background:
                linear-gradient(
                    120deg,
                    #1a0000,
                    #0d0d0d 65%
                );

            border: 1px solid #391010;

            border-radius: 10px;

            padding: 40px;
        }


        .cinema-label {
            color: #e50914;

            font-size: 11px;

            font-weight: 800;

            letter-spacing: 3px;

            margin-bottom: 10px;
        }


        .cinema-box h3 {
            font-size: 32px;

            font-weight: 900;

            margin-bottom: 10px;
        }


        .cinema-box p {
            color: #999999;

            line-height: 1.7;

            margin: 0;

            max-width: 600px;
        }


        /* =====================================================
           FOOTER
        ===================================================== */

        footer {
            background: #050505;

            border-top: 1px solid #222222;

            padding: 45px 6%;

            margin-top: 80px;
        }


        .footer-content {
            display: flex;

            justify-content: space-between;

            gap: 30px;
        }


        .footer-logo {
            font-size: 25px;

            font-weight: 900;
        }


        .footer-logo span {
            color: #e50914;
        }


        .footer-text {
            color: #777777;

            font-size: 12px;

            margin-top: 9px;
        }


        .footer-right {
            color: #777777;

            font-size: 12px;

            line-height: 1.8;

            text-align: right;
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 950px) {

            .navbar-content {
                flex-wrap: wrap;
            }


            .search-box {
                order: 5;

                flex-basis: 100%;

                max-width: 100%;
            }


            .movie-grid {
                grid-template-columns:
                    repeat(2, 1fr);
            }


            .coming-movie-grid {
                grid-template-columns:
                    repeat(2, 1fr);
            }


            .upcoming-grid {
                grid-template-columns:
                    repeat(2, 1fr);
            }


            .hero h1 {
                font-size: 45px;
            }

        }


        @media (max-width: 650px) {

            .movie-grid,
            .coming-movie-grid,
            .upcoming-grid {

                grid-template-columns: 1fr;

            }


            .hero {
                height: 500px;
            }


            .hero h1 {
                font-size: 36px;
            }


            .schedule-card {
                align-items: flex-start;

                flex-wrap: wrap;
            }


            .schedule-times {
                width: 100%;

                justify-content: flex-start;
            }


            .footer-content {
                flex-direction: column;
            }


            .footer-right {
                text-align: left;
            }

        }

    </style>

</head>


<body>


<!-- =====================================================
     HEADER
===================================================== -->

<header class="top-bar">

    <div class="navbar-content">


        <a
            href="index.php"
            class="logo"
        >

            HIMTI<span>MOVIE</span>

        </a>


        <div class="location">

            <strong>
                SURABAYA
            </strong>

        </div>


        <div class="search-box">

            <input
                type="text"
                id="movieSearch"
                placeholder="Search movies"
            >

        </div>


        <div class="account">


            <?php if ($is_logged_in): ?>


                <span
                    style="
                        color:#aaa;
                        font-size:13px;
                    "
                >

                    Hi,
                    <?= htmlspecialchars($username) ?>

                </span>


                <a
                    href="history.php"
                    class="login-btn"
                >

                    MY ACCOUNT

                </a>


            <?php else: ?>


                <a
                    href="login.php"
                    class="login-btn"
                >

                    LOGIN

                </a>


                <a href="register.php">

                    REGISTER

                </a>


            <?php endif; ?>


        </div>

    </div>

</header>


<!-- =====================================================
     MENU
===================================================== -->

<nav class="menu-bar">

    <ul class="menu">


        <li>

            <a href="#cinemas">

                CINEMAS

            </a>

        </li>


        <li>

            <a href="#movies">

                MOVIES

            </a>

        </li>


        <li>

            <a href="#schedule">

                SHOWTIMES

            </a>

        </li>


    </ul>

</nav>


<!-- =====================================================
     HERO
===================================================== -->

<section class="hero">


    <img
        src="assets/hero-cinema.png"
        class="hero-image"
        alt="Cinema auditorium"
    >


    <div class="hero-overlay"></div>


    <div class="hero-content">


        <div class="hero-label">

            HIMTI MOVIE • SURABAYA

        </div>


        <h1>

            YOUR MOVIE.
            <br>

            YOUR <span>MOMENT.</span>

        </h1>


        <p>

            Discover the latest movies,
            explore showtimes,
            and book your favorite movie
            with HIMTI MOVIE.

        </p>


        <div class="hero-buttons">


            <a
                href="#movies"
                class="btn-red"
            >

                NOW SHOWING

            </a>


            <a
                href="#schedule"
                class="btn-outline"
            >

                VIEW SCHEDULE

            </a>


        </div>


    </div>

</section>


<!-- =====================================================
     SELECT DATE
===================================================== -->

<section class="section date-section">


    <div class="date-box">


        <div class="date-title">

            SELECT DATE

        </div>


        <div class="date-list">


            <?php for ($i = 0; $i < 7; $i++): ?>


                <?php

                $date = date(
                    'Y-m-d',
                    strtotime("+$i day")
                );


                $day = date(
                    'D',
                    strtotime($date)
                );


                $number = date(
                    'd',
                    strtotime($date)
                );


                $month = date(
                    'M',
                    strtotime($date)
                );


                $active =
                    ($selected_date === $date)
                    ? 'active'
                    : '';

                ?>


                <a
                    href="?date=<?= $date ?>"
                    class="date-item <?= $active ?>"
                >


                    <span class="date-day">

                        <?= $day ?>

                    </span>


                    <span class="date-number">

                        <?= $number ?>

                    </span>


                    <span class="date-month">

                        <?= $month ?>

                    </span>


                </a>


            <?php endfor; ?>


        </div>

    </div>

</section>


<!-- =====================================================
     NOW SHOWING
===================================================== -->

<section
    class="section"
    id="movies"
>


    <div class="section-heading">


        <h2>

            NOW SHOWING

        </h2>


        <a
            href="#schedule"
            class="view-all"
        >

            VIEW SCHEDULE →

        </a>


    </div>


    <!-- GENRE -->

    <div class="genre-filter">


        <a
            href="index.php#movies"
            class="genre-btn
            <?= empty($selected_genre) ? 'active' : '' ?>"
        >

            ALL

        </a>


        <?php foreach ($genres as $genre): ?>


            <a
                href="?genre=<?= urlencode($genre['genre_id']) ?>#movies"
                class="genre-btn
                <?= ($selected_genre == $genre['genre_id'])
                    ? 'active'
                    : '' ?>"
            >

                <?= htmlspecialchars(
                    $genre['genre_name']
                ) ?>

            </a>


        <?php endforeach; ?>


    </div>


    <!-- MOVIES -->

    <div
        class="movie-grid"
        id="movieGrid"
    >


        <?php

        $visible_movies = [];


        foreach ($movies as $movie) {


            if (
                !empty($selected_genre) &&
                $movie['genre_id'] != $selected_genre
            ) {

                continue;

            }


            $visible_movies[] = $movie;

        }

        ?>


        <?php if (!empty($visible_movies)): ?>


            <?php foreach ($visible_movies as $movie): ?>


                <?php

                $poster =
                    $movie['poster']
                    ?? '';


                $isUrl =
                    filter_var(
                        $poster,
                        FILTER_VALIDATE_URL
                    );


                $isLocal =
                    !$isUrl &&
                    !empty($poster) &&
                    file_exists(
                        __DIR__ . '/' . $poster
                    );

                ?>


                <div
                    class="movie-card"
                    data-title="<?= htmlspecialchars(
                        strtolower($movie['title'])
                    ) ?>"
                >


                    <?php if ($isUrl || $isLocal): ?>


                        <img
                            src="<?= htmlspecialchars($poster) ?>"
                            class="poster"
                            alt="<?= htmlspecialchars(
                                $movie['title']
                            ) ?>"
                        >


                    <?php else: ?>


                        <div class="no-poster">

                            HIMTI MOVIE

                        </div>


                    <?php endif; ?>


                    <div class="movie-info">


                        <div class="movie-title">

                            <?= htmlspecialchars(
                                $movie['title']
                            ) ?>

                        </div>


                        <div class="genre">

                            <?= htmlspecialchars(
                                $movie['genre_name']
                                ?? 'Movie'
                            ) ?>


                            <?php if (
                                !empty($movie['duration'])
                            ): ?>

                                •
                                <?= htmlspecialchars(
                                    $movie['duration']
                                ) ?>
                                min

                            <?php endif; ?>


                        </div>


                        <div class="movie-buttons">


                            <a
                                href="movie_detail.php?id=<?= $movie['movie_id'] ?>"
                                class="synopsis-btn"
                            >

                                READ SYNOPSIS

                            </a>


                            <a
                                href="movie_detail.php?id=<?= $movie['movie_id'] ?>"
                                class="ticket-btn"
                            >

                                BUY TICKET

                            </a>


                        </div>


                    </div>


                </div>


            <?php endforeach; ?>


        <?php else: ?>


            <div
                class="empty-state"
                style="grid-column:1/-1;"
            >

                No movies found.

            </div>


        <?php endif; ?>


    </div>

</section>


<!-- =====================================================
     MOVIE SCHEDULE
===================================================== -->

<section
    class="section"
    id="schedule"
>


    <div class="section-heading">


        <h2>

            MOVIE SCHEDULE

        </h2>


        <span
            style="
                color:#777;
                font-size:12px;
            "
        >

            <?= date(
                'd M Y',
                strtotime($selected_date)
            ) ?>

        </span>


    </div>


    <div class="schedule-container">


        <?php if (!empty($schedules)): ?>


            <?php foreach ($schedules as $schedule): ?>


                <?php

                $poster =
                    $schedule['poster']
                    ?? '';


                $isUrl =
                    filter_var(
                        $poster,
                        FILTER_VALIDATE_URL
                    );


                $isLocal =
                    !$isUrl &&
                    !empty($poster) &&
                    file_exists(
                        __DIR__ . '/' . $poster
                    );

                ?>


                <div class="schedule-card">


                    <?php if ($isUrl || $isLocal): ?>


                        <img
                            src="<?= htmlspecialchars($poster) ?>"
                            class="schedule-poster"
                            alt="<?= htmlspecialchars(
                                $schedule['title']
                            ) ?>"
                        >


                    <?php else: ?>


                        <div
                            class="schedule-poster"
                            style="
                                display:flex;
                                align-items:center;
                                justify-content:center;
                                color:#555;
                                font-size:10px;
                            "
                        >

                            HIMTI

                        </div>


                    <?php endif; ?>


                    <div class="schedule-info">


                        <div class="schedule-title">

                            <?= htmlspecialchars(
                                $schedule['title']
                            ) ?>

                        </div>


                        <div class="schedule-studio">

                            <?= htmlspecialchars(
                                $schedule['studio_name']
                            ) ?>

                            • Capacity

                            <?= htmlspecialchars(
                                $schedule['capacity']
                            ) ?>

                        </div>


                    </div>


                    <div class="schedule-times">


                        <div class="time-box">


                            <span class="time">

                                <?= date(
                                    'H:i',
                                    strtotime(
                                        $schedule['show_time']
                                    )
                                ) ?>

                            </span>


                            <span class="price">

                                Rp
                                <?= number_format(
                                    $schedule['price'],
                                    0,
                                    ',',
                                    '.'
                                ) ?>

                            </span>


                        </div>


                        <a
                            href="movie_detail.php?id=<?= $schedule['movie_id'] ?>"
                            class="btn-red"
                            style="
                                padding:9px 14px;
                            "
                        >

                            BUY

                        </a>


                    </div>


                </div>


            <?php endforeach; ?>


        <?php else: ?>


            <div class="empty-state">

                No showtimes available
                for this date.

            </div>


        <?php endif; ?>


    </div>

</section>


<!-- =====================================================
     UPCOMING SHOWTIMES
===================================================== -->

<section
    class="section"
    id="upcoming"
>


    <div class="section-heading">


        <div>


            <div class="small-red-label">

                NEXT SCREENING

            </div>


            <h2>

                UPCOMING SHOWTIMES

            </h2>


        </div>


        <a
            href="#schedule"
            class="view-all"
        >

            VIEW SCHEDULE →

        </a>


    </div>


    <?php if (!empty($upcoming_showtimes)): ?>


        <div class="upcoming-grid">


            <?php foreach (
                $upcoming_showtimes
                as $show
            ): ?>


                <?php

                $poster =
                    $show['poster']
                    ?? '';


                $isUrl =
                    filter_var(
                        $poster,
                        FILTER_VALIDATE_URL
                    );


                $isLocal =
                    !$isUrl &&
                    !empty($poster) &&
                    file_exists(
                        __DIR__ . '/' . $poster
                    );

                ?>


                <div class="upcoming-card">


                    <div class="upcoming-poster">


                        <?php if ($isUrl || $isLocal): ?>


                            <img
                                src="<?= htmlspecialchars($poster) ?>"
                                alt="<?= htmlspecialchars(
                                    $show['title']
                                ) ?>"
                            >


                        <?php else: ?>


                            <div class="upcoming-no-poster">

                                HIMTI
                                <br>
                                MOVIE

                            </div>


                        <?php endif; ?>


                        <div class="date-badge">

                            <?= date(
                                'd M',
                                strtotime(
                                    $show['show_date']
                                )
                            ) ?>

                        </div>


                    </div>


                    <div class="upcoming-content">


                        <h3>

                            <?= htmlspecialchars(
                                $show['title']
                            ) ?>

                        </h3>


                        <div class="upcoming-studio">

                            <?= htmlspecialchars(
                                $show['studio_name']
                            ) ?>

                        </div>


                        <div class="upcoming-bottom">


                            <div>


                                <span class="upcoming-time">

                                    <?= date(
                                        'H:i',
                                        strtotime(
                                            $show['show_time']
                                        )
                                    ) ?>

                                </span>


                                <span class="upcoming-price">

                                    Rp
                                    <?= number_format(
                                        $show['price'],
                                        0,
                                        ',',
                                        '.'
                                    ) ?>

                                </span>


                            </div>


                            <a
                                href="movie_detail.php?id=<?= $show['movie_id'] ?>"
                                class="small-buy"
                            >

                                BUY

                            </a>


                        </div>


                    </div>


                </div>


            <?php endforeach; ?>


        </div>


    <?php else: ?>


        <div class="empty-state">

            No upcoming showtimes available.

        </div>


    <?php endif; ?>


</section>


<!-- =====================================================
     COMING SOON
===================================================== -->

<section class="section">


    <div class="section-heading">


        <div>


            <div class="small-red-label">

                COMING SOON

            </div>


            <h2>

                UPCOMING MOVIES

            </h2>


        </div>


    </div>


    <?php if (!empty($coming_soon)): ?>


        <div class="coming-movie-grid">


            <?php foreach (
                $coming_soon
                as $movie
            ): ?>


                <?php

                $poster =
                    $movie['poster']
                    ?? '';


                $isUrl =
                    filter_var(
                        $poster,
                        FILTER_VALIDATE_URL
                    );


                $isLocal =
                    !$isUrl &&
                    !empty($poster) &&
                    file_exists(
                        __DIR__ . '/' . $poster
                    );

                ?>


                <div class="coming-movie">


                    <div class="coming-poster">


                        <?php if ($isUrl || $isLocal): ?>


                            <img
                                src="<?= htmlspecialchars($poster) ?>"
                                alt="<?= htmlspecialchars(
                                    $movie['title']
                                ) ?>"
                            >


                        <?php else: ?>


                            <div class="upcoming-no-poster">

                                HIMTI
                                <br>
                                MOVIE

                            </div>


                        <?php endif; ?>


                    </div>


                    <div class="coming-info">


                        <span class="coming-date">

                            <?= date(
                                'd M Y',
                                strtotime(
                                    $movie['release_date']
                                )
                            ) ?>

                        </span>


                        <h3>

                            <?= htmlspecialchars(
                                $movie['title']
                            ) ?>

                        </h3>


                        <p>

                            <?= htmlspecialchars(
                                $movie['genre_name']
                                ?? 'Movie'
                            ) ?>


                            <?php if (
                                !empty($movie['duration'])
                            ): ?>

                                •
                                <?= htmlspecialchars(
                                    $movie['duration']
                                ) ?>
                                min

                            <?php endif; ?>


                        </p>


                        <a
                            href="movie_detail.php?id=<?= $movie['movie_id'] ?>"
                            class="coming-link"
                        >

                            VIEW MOVIE →

                        </a>


                    </div>


                </div>


            <?php endforeach; ?>


        </div>


    <?php else: ?>


        <div class="empty-state">

            No upcoming movies available.

        </div>


    <?php endif; ?>


</section>


<!-- =====================================================
     CINEMA
===================================================== -->

<section
    class="section"
    id="cinemas"
>


    <div class="section-heading">


        <h2>

            OUR CINEMA

        </h2>


    </div>


    <div class="cinema-box">


        <div class="cinema-label">

            HIMTI MOVIE

        </div>


        <h3>

            SURABAYA

        </h3>


        <p>

            Your movie destination in Surabaya.
            Choose your movie, select your showtime,
            and enjoy the experience.

        </p>


    </div>


</section>


<!-- =====================================================
     FOOTER
===================================================== -->

<footer>


    <div class="footer-content">


        <div>


            <div class="footer-logo">

                HIMTI<span>MOVIE</span>

            </div>


            <div class="footer-text">

                Your movie destination in Surabaya.

            </div>


        </div>


        <div class="footer-right">

            SURABAYA
            <br>

            © 2026 HIMTI MOVIE

        </div>


    </div>


</footer>


<!-- =====================================================
     SEARCH
===================================================== -->

<script>

const searchInput =
    document.getElementById("movieSearch");


if (searchInput) {

    searchInput.addEventListener(
        "input",
        function () {

            const keyword =
                this.value.toLowerCase();


            const cards =
                document.querySelectorAll(
                    ".movie-card"
                );


            cards.forEach(
                function (card) {

                    const title =
                        card.dataset.title || "";


                    if (
                        title.includes(keyword)
                    ) {

                        card.style.display = "";

                    } else {

                        card.style.display = "none";

                    }

                }
            );

        }
    );

}

</script>


</body>

</html>
