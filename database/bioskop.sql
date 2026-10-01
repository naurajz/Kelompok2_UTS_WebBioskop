-- File     : database/bioskop.sql
-- Card     : DB-02 Implementasi Database
-- Tugas    : CREATE TABLE users, genres, movies, studios, showtimes, orders, tickets
-- PIC      : (isi nama)
-- Deadline : 1 Oktober 2026

CREATE table users (
	user_id serial primary key,
	username varchar(50) NOT NULL,
	email varchar(100) NOT NULL UNIQUE,
	password varchar(255) NOT NULL,
	role varchar(20) default 'customer',
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

create table genres (
	genre_id SERIAL PRIMARY KEY,
	genre_name VARCHAR(30) NOT NULL
);

create table studios (
	studio_id SERIAL PRIMARY KEY,
	studio_name VARCHAR(40) NOT NULL,
	capacity INT NOT NULL
);

create table movies (
	movie_id SERIAL PRIMARY KEY,
	title varchar(150) NOT NULL,
	description TEXT,
	duration INT NOT NULL,
	release_date DATE,
	poster VARCHAR(255),
	genre_id INT references genres(genre_id) ON DELETE SET NULL
);

create table showtimes (
	showtime_id SERIAL PRIMARY KEY,
	movie_id INT REFERENCES movies(movie_id) ON DELETE CASCADE,
    studio_id INT REFERENCES studios(studio_id) ON DELETE CASCADE,
    show_date DATE NOT NULL,
    show_time TIME NOT NULL,
    price DECIMAL(10,2) NOT NULL
);

create table orders (
	order_id serial primary key,
	user_id INT REFERENCES users(user_id) ON DELETE CASCADE,
    showtime_id INT REFERENCES showtimes(showtime_id) ON DELETE CASCADE,
    total_price DECIMAL(10,2) NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

create table tickets (
	ticket_id SERIAL PRIMARY KEY,
    order_id INT REFERENCES orders(order_id) ON DELETE CASCADE,
    seat_number VARCHAR(10) NOT NULL
);
