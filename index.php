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
$dbPassword = getenv("DB_PASSWORD") ?: "1111";

$conn = pg_connect(
    "host=$dbHost port=$dbPort dbname=$dbName user=$dbUser password=$dbPassword"
);

if (!$conn) {
    die("Database connection failed.");
}

// Menentukan poster film dan gambar cadangan yang digunakan pada halaman.
// Poster TMDB hanya dipakai kalau film tidak punya poster hasil upload admin.
$posters = [
    "Avengers: Endgame" =>
        "https://image.tmdb.org/t/p/w500/or06FN3Dka5tukK1e9sl16pB3iy.jpg",
  
    "Inside Out 2" =>
        "https://image.tmdb.org/t/p/w500/vpnVM9B6NMmQpWeZvzLvDESb2QY.jpg",
];

// Gambar cadangan lokal (tidak butuh internet) kalau poster tidak tersedia.
$fallback = "assets/img/no-poster.svg";

// Menentukan poster yang dipakai untuk satu film.
// Urutan: 1) poster hasil upload admin, 2) poster TMDB berdasarkan judul, 3) gambar cadangan.
function posterUrl($movie, $posters, $fallback)
{
    $path = trim((string) ($movie["poster"] ?? ""));

    if ($path !== "") {
        // poster berupa link lengkap (http/https), langsung dipakai
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        // poster hasil upload (misal uploads/posters/xxx.jpg), dipakai kalau filenya ada
        if (is_file(__DIR__ . "/" . $path)) {
            return $path;
        }
    }

    return $posters[$movie["title"]] ?? $fallback;
}

// Mengambil data film dari database untuk ditampilkan pada halaman utama.
$movieResult = pg_query($conn, "
    SELECT
        m.movie_id,
        m.title,
        m.duration,
        m.description,
        m.poster,
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
        "genre_name" => $row["genre_name"],
        // alamat poster final yang sudah dipilih (dipakai PHP dan JavaScript)
        "poster" => posterUrl($row, $posters, $fallback)
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

// Pengaturan halaman untuk header.php (judul tab, tema gelap, path dasar).
$page_title = 'HIMTI MOVIE';
$body_class = 'theme-dark';
$base_url   = '';
require_once __DIR__ . '/includes/header.php';
?>

<section class="hero" id="home">

    <div class="hero-image">

        <img
            src="assets/cinema.jpg"
            alt="Cinema"
            onerror="
                this.onerror = null;
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
            data-genre="all"
            onclick="filterMovies(this)"
        >
            All
        </button>

        <?php foreach ($genres as $genre): ?>

            <button
                class="genre-btn"
                data-genre="<?= htmlspecialchars($genre["genre_name"], ENT_QUOTES) ?>"
                onclick="filterMovies(this)"
            >
                <?= htmlspecialchars($genre["genre_name"]) ?>
            </button>

        <?php endforeach; ?>

    </div>

    <div class="movie-grid">

        <?php foreach ($movies as $movie): ?>

            <?php

            $title = $movie["title"];

            $poster = $movie["poster"];

            $genre =
                $movie["genre_name"]
                ?? "Movie";

            $description =
                $movie["description"]
                ?? "Synopsis not available.";

            ?>

            <div
                class="movie-card"
                data-genre="<?= htmlspecialchars($genre, ENT_QUOTES) ?>"
                data-title="<?= htmlspecialchars($title, ENT_QUOTES) ?>"
                data-description="<?= htmlspecialchars($description, ENT_QUOTES) ?>"
                onclick="openMovie(this)"
            >

                <img
                    src="<?= htmlspecialchars($poster) ?>"
                    class="movie-poster"
                    alt="<?= htmlspecialchars($title) ?>"
                    onerror="
                        this.onerror = null;
                        this.src='<?= htmlspecialchars($fallback, ENT_QUOTES) ?>';
                    "
                >

                <div class="movie-info">

                    <div class="movie-title">
                        <?= htmlspecialchars($title) ?>
                    </div>

                    <div class="movie-meta">

                        <?= htmlspecialchars($genre) ?>

                        •

                        <?= (int) $movie["duration"] ?>

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
                class="calendar-card <?= $index === 0 ? "active" : "" ?>"
                data-date="<?= $date ?>"
                onclick="selectShowtimeDate('<?= $date ?>', this)"
            >

                <div class="calendar-day">
                    <?= dayName($date) ?>
                </div>

                <div class="calendar-number">
                    <?= dayNumber($date) ?>
                </div>

                <div class="calendar-month">
                    <?= monthName($date) ?>
                </div>

                <div class="calendar-type">
                    <?= dayType($date) ?>
                </div>

                <div class="calendar-price">
                    Rp
                    <?= number_format(ticketPrice($date), 0, ",", ".") ?>
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
            class="selected-date-text"
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
    class="movie-modal"
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

// Menentukan apakah pengguna sedang login atau belum, dan apakah dia admin.
const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;
const isAdmin = <?= $isAdmin ? 'true' : 'false' ?>;

// Mengirim data jadwal tayang dari PHP ke JavaScript.
const showtimes =
    <?= json_encode(
        $showtimeData,
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    ) ?>;

// Mengirim data film (termasuk alamat poster) dari PHP ke JavaScript.
const movies =
    <?= json_encode(
        $movies,
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    ) ?>;

// Gambar cadangan kalau poster tidak bisa dimuat.
const fallbackPoster =
    <?= json_encode($fallback) ?>;

// Mengamankan teks dari database sebelum dimasukkan ke innerHTML.
function esc(text) {

    return String(text ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");

}

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

        // poster sudah dipilih oleh PHP (upload admin, TMDB, atau cadangan)
        const poster =
            movie.poster
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
                        onclick="buyTicket('${esc(item.showtime_id)}')"
                    >

                        <div class="clock"></div>

                        <div class="time">
                            ${esc(item.time)}
                        </div>

                        <div class="studio">
                            ${esc(item.studio)}
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
                src="${esc(poster)}"
                class="schedule-poster"
                alt="${esc(movie.title)}"
                onerror="
                    this.onerror = null;
                    this.src='${esc(fallbackPoster)}';
                "
            >

            <div class="schedule-info">

                <div class="schedule-title">
                    ${esc(movie.title)}
                </div>

                <div class="schedule-genre">
                    ${esc(movie.genre_name || "Movie")}
                    •
                    ${esc(movie.duration)} min
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

    // admin tidak boleh membeli tiket, hanya akun user biasa
    if (isAdmin) {

        alert("Admin tidak dapat memesan tiket. Gunakan akun user.");

        return;
    }

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
function filterMovies(button) {

    const genre = button.dataset.genre;

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
// Judul dan sinopsis diambil dari atribut data-* kartu film yang diklik.
function openMovie(card) {

    document.getElementById(
        "modalTitle"
    ).textContent =
        card.dataset.title;

    document.getElementById(
        "modalDescription"
    ).textContent =
        card.dataset.description;

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
                firstCard.dataset.date,
                firstCard
            );

        }

    }
);

// Meneruskan user (bukan admin) ke checkout setelah login berhasil.
if (isLoggedIn && !isAdmin) {

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

<?php require_once __DIR__ . '/includes/footer.php'; ?>