
CREATE TABLE IF NOT EXISTS users (
	user_id serial primary key,
	username varchar(50) NOT NULL,
	email varchar(100) NOT NULL UNIQUE,
	password varchar(255) NOT NULL,
	role varchar(20) default 'customer',
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS genres (
	genre_id SERIAL PRIMARY KEY,
	genre_name VARCHAR(30) NOT NULL
);

CREATE TABLE IF NOT EXISTS studios (
	studio_id SERIAL PRIMARY KEY,
	studio_name VARCHAR(40) NOT NULL,
	capacity INT NOT NULL
);

CREATE TABLE IF NOT EXISTS movies (
	movie_id SERIAL PRIMARY KEY,
	title varchar(150) NOT NULL,
	description TEXT,
	duration INT NOT NULL,
	release_date DATE,
	poster VARCHAR(255),
	genre_id INT references genres(genre_id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS showtimes (
	showtime_id SERIAL PRIMARY KEY,
	movie_id INT REFERENCES movies(movie_id) ON DELETE CASCADE,
    studio_id INT REFERENCES studios(studio_id) ON DELETE CASCADE,
    show_date DATE NOT NULL,
    show_time TIME NOT NULL,
    price DECIMAL(10,2) NOT NULL
);

CREATE TABLE IF NOT EXISTS orders (
	order_id serial primary key,
	user_id INT REFERENCES users(user_id) ON DELETE CASCADE,
    showtime_id INT REFERENCES showtimes(showtime_id) ON DELETE CASCADE,
    total_price DECIMAL(10,2) NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS tickets (
	ticket_id SERIAL PRIMARY KEY,
    order_id INT REFERENCES orders(order_id) ON DELETE CASCADE,
    seat_number VARCHAR(10) NOT NULL
);

-- ==========================================================
-- 2. DATA AWAL
-- ==========================================================

-- Genre
INSERT INTO genres (genre_name)
SELECT t.v
FROM (VALUES
    ('Aksi'), ('Horor'), ('Komedi'), ('Drama'),
    ('Romantis'), ('Fiksi Ilmiah'), ('Animasi'), ('Thriller')
) AS t(v)
WHERE NOT EXISTS (
    SELECT 1 FROM genres g WHERE LOWER(g.genre_name) = LOWER(t.v)
);

-- Studio
INSERT INTO studios (studio_name, capacity)
SELECT t.n, t.c
FROM (VALUES
    ('Studio 1', 100),
    ('Studio 2', 80),
    ('Studio 3', 60),
    ('Studio 4 (Premiere)', 40)
) AS t(n, c)
WHERE NOT EXISTS (
    SELECT 1 FROM studios s WHERE LOWER(s.studio_name) = LOWER(t.n)
);

-- Film contoh (2 film). Genre dicari berdasarkan nama, jadi tidak
-- bergantung pada nomor ID.
INSERT INTO movies (title, description, duration, release_date, genre_id)
SELECT
    t.title,
    t.descr,
    t.dur,
    t.rel,
    (SELECT g.genre_id FROM genres g WHERE LOWER(g.genre_name) = LOWER(t.genre) LIMIT 1)
FROM (VALUES
    (
        'Avengers: Endgame',
        'The Avengers must come together one final time to reverse the devastating actions of Thanos and save the universe.',
        181,
        DATE '2019-04-26',
        'Aksi'
    ),
    (
        'Inside Out 2',
        'Riley enters her teenage years and experiences new emotions that change the way she sees the world.',
        96,
        DATE '2024-06-14',
        'Animasi'
    )
) AS t(title, descr, dur, rel, genre)
WHERE NOT EXISTS (
    SELECT 1 FROM movies m WHERE LOWER(m.title) = LOWER(t.title)
);

-- Jadwal tayang: 4 sesi per hari selama 7 hari (5-11 Oktober 2026) untuk
-- kedua film di atas, bergantian di Studio 1-3.
-- Harga: weekend (Sabtu/Minggu) 45.000, weekday 35.000.
INSERT INTO showtimes (movie_id, studio_id, show_date, show_time, price)
SELECT
    m.movie_id,
    st.studio_id,
    d.show_date,
    t.show_time,
    CASE
        WHEN EXTRACT(ISODOW FROM d.show_date) IN (6, 7)
        THEN 45000
        ELSE 35000
    END AS price
FROM movies m
CROSS JOIN (
    SELECT DATE '2026-10-05' + i AS show_date
    FROM generate_series(0, 6) AS i
) d
CROSS JOIN (
    VALUES
        (0, '10:00:00'::time),
        (1, '13:30:00'::time),
        (2, '17:00:00'::time),
        (3, '20:00:00'::time)
) t(slot, show_time)
JOIN (
    SELECT
        studio_id,
        ROW_NUMBER() OVER (ORDER BY studio_id) - 1 AS rn
    FROM studios
    WHERE studio_name IN ('Studio 1', 'Studio 2', 'Studio 3')
) st
    ON st.rn = (m.movie_id + t.slot) % 3
WHERE m.title IN ('Avengers: Endgame', 'Inside Out 2')
  AND NOT EXISTS (
        SELECT 1
        FROM showtimes s
        WHERE s.movie_id = m.movie_id
          AND s.show_date = d.show_date
          AND s.show_time = t.show_time
  );