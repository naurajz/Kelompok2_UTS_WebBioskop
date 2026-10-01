<?php
/**
 * File     : classes/Movie.php
 * Card     : Movie-01 Film Backend
 * Tugas    : Class Movie extends BaseModel. Isi: constructor, getter/setter (dengan validasi), save().
 * PIC      : (Zayyan Ahmad Dzaki W)
 * Deadline : 2 Oktober 2026
 */

require_once __DIR__ . '/BaseModel.php';

class Movie extends BaseModel
{
    // Property sesuai kolom tabel movies
    private $movie_id;
    private $title;
    private $description;
    private $duration;
    private $release_date;
    private $poster;
    private $genre_id;


    // =========================
    // CONSTRUCTOR
    // =========================

    /**
     * Contoh sesuai PPL:
     * $movie = new Movie(1, 'Avengers', 150);
     */
    public function __construct(
        $genre_id = null,
        $title = null,
        $duration = null,
        $description = null,
        $release_date = null,
        $poster = null,
        $movie_id = null
    ) {
        // Nama tabel dan primary key
        parent::__construct('movies', 'movie_id');

        if ($movie_id !== null) {
            $this->setMovieId($movie_id);
        }

        if ($genre_id !== null) {
            $this->setGenreId($genre_id);
        }

        if ($title !== null) {
            $this->setTitle($title);
        }

        if ($duration !== null) {
            $this->setDuration($duration);
        }

        if ($description !== null) {
            $this->setDescription($description);
        }

        if ($release_date !== null && $release_date !== '') {
            $this->setReleaseDate($release_date);
        }

        if ($poster !== null) {
            $this->setPoster($poster);
        }
    }


    // =========================
    // GETTER
    // =========================

    public function getMovieId()
    {
        return $this->movie_id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getDuration()
    {
        return $this->duration;
    }

    public function getReleaseDate()
    {
        return $this->release_date;
    }

    public function getPoster()
    {
        return $this->poster;
    }

    public function getGenreId()
    {
        return $this->genre_id;
    }


    // =========================
    // SETTER + VALIDASI
    // =========================

    public function setMovieId($movie_id)
    {
        if (!is_numeric($movie_id) || $movie_id <= 0) {
            throw new InvalidArgumentException(
                'Movie ID harus berupa angka lebih dari 0.'
            );
        }

        $this->movie_id = (int) $movie_id;
    }


    public function setTitle($title)
    {
        $title = trim($title);

        if ($title === '') {
            throw new InvalidArgumentException(
                'Judul film tidak boleh kosong.'
            );
        }

        if (strlen($title) > 150) {
            throw new InvalidArgumentException(
                'Judul film maksimal 150 karakter.'
            );
        }

        $this->title = $title;
    }


    public function setDescription($description)
    {
        $this->description = trim($description);
    }


    public function setDuration($duration)
    {
        if (!is_numeric($duration) || $duration <= 0) {
            throw new InvalidArgumentException(
                'Durasi film harus lebih dari 0 menit.'
            );
        }

        $this->duration = (int) $duration;
    }


    public function setReleaseDate($release_date)
    {
        $tanggal = DateTime::createFromFormat(
            'Y-m-d',
            $release_date
        );

        if (
            !$tanggal ||
            $tanggal->format('Y-m-d') !== $release_date
        ) {
            throw new InvalidArgumentException(
                'Format tanggal rilis harus YYYY-MM-DD.'
            );
        }

        $this->release_date = $release_date;
    }


    public function setPoster($poster)
    {
        if ($poster === null || $poster === '') {
            $this->poster = null;
            return;
        }

        if (strlen($poster) > 255) {
            throw new InvalidArgumentException(
                'Nama atau path poster terlalu panjang.'
            );
        }

        $this->poster = trim($poster);
    }


    public function setGenreId($genre_id)
    {
        // genre_id boleh NULL sesuai database
        if ($genre_id === null || $genre_id === '') {
            $this->genre_id = null;
            return;
        }

        if (!is_numeric($genre_id) || $genre_id <= 0) {
            throw new InvalidArgumentException(
                'Genre ID harus berupa angka lebih dari 0.'
            );
        }

        $this->genre_id = (int) $genre_id;
    }


    // =========================
    // SAVE
    // =========================

    public function save(): bool
    {
        // title wajib sesuai database NOT NULL
        if (empty($this->title)) {
            throw new InvalidArgumentException(
                'Judul film tidak boleh kosong.'
            );
        }

        // duration wajib sesuai database NOT NULL
        if ($this->duration === null || $this->duration <= 0) {
            throw new InvalidArgumentException(
                'Durasi film harus diisi.'
            );
        }

        // Nama field disesuaikan dengan tabel movies
        $data = [
            'title' => $this->title,
            'description' => $this->description,
            'duration' => $this->duration,
            'release_date' => $this->release_date,
            'poster' => $this->poster,
            'genre_id' => $this->genre_id
        ];

        // Belum punya movie_id = tambah film baru
        if ($this->movie_id === null) {
            return parent::create($data);
        }

        // Sudah punya movie_id = update film
        return parent::update(
            $this->movie_id,
            $data
        );
    }
}