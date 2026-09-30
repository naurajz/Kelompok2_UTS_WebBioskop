<?php
/**
 * File     : classes/Movie.php
 * Card     : Movie-01 Film Backend
 * Tugas    : Class Movie extends BaseModel. Isi: constructor, getter/setter (dengan validasi), save().
 * PIC      : (Zayyan Ahmad Dzaki W)
 * Deadline : 2 Oktober 2026
 */

require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/../config/Database.php';

class Movie extends BaseModel
{
    // Data film sesuai dengan database
    private $movie_id;
    private $genre_id;
    private $title;
    private $description;
    private $duration;
    private $release_date;
    private $poster;

    // =========================
    // CONSTRUCTOR
    // =========================
    public function __construct(
        $genre_id,
        $title,
        $duration,
        $description = '',
        $release_date = null,
        $poster = null,
        $movie_id = null
    ) {
        $this->movie_id = $movie_id;
        $this->setGenreId($genre_id);
        $this->setTitle($title);
        $this->setDuration($duration);
        $this->setDescription($description);
        $this->setReleaseDate($release_date);
        $this->setPoster($poster);
    }

    // =========================
    // GETTER
    // =========================
    public function getMovieId()
    {
        return $this->movie_id;
    }

    public function getGenreId()
    {
        return $this->genre_id;
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

    // =========================
    // SETTER + VALIDASI
    // =========================
    public function setGenreId($genre_id)
    {
        if (!is_numeric($genre_id) || $genre_id <= 0) {
            throw new InvalidArgumentException('Genre film harus dipilih.');
        }
        $this->genre_id = (int) $genre_id;
    }

    public function setTitle($title)
    {
        $title = trim($title);
        if ($title == '') {
            throw new InvalidArgumentException('Judul film tidak boleh kosong.');
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
            throw new InvalidArgumentException('Durasi film harus lebih dari 0 menit.');
        }
        $this->duration = (int) $duration;
    }

    public function setReleaseDate($release_date)
    {
        if ($release_date === null || $release_date === '') {
            $this->release_date = null;
        } else {
            $this->release_date = trim($release_date);
        }
    }

    public function setPoster($poster)
    {
        if ($poster === null || $poster === '') {
            $this->poster = null;
        } else {
            $this->poster = trim($poster);
        }
    }

    // =========================
    // KONEKSI DATABASE
    // =========================
    private static function getDatabaseConnection()
    {
        return new DBConnection();
    }

    // =========================
    // CREATE
    // =========================
    public function create()
    {
        $db = self::getDatabaseConnection();
        $sql = "INSERT INTO movies (genre_id, title, description, duration, release_date, poster) VALUES ($1, $2, $3, $4, $5, $6) RETURNING movie_id";
        
        $params = [$this->genre_id, $this->title, $this->description, $this->duration, $this->release_date, $this->poster];
        $result = $db->send_query($sql, $params);

        if ($result['success'] && !empty($result['data'])) {
            $this->movie_id = $result['data'][0]['movie_id'];
            return true;
        }
        return false;
    }

    // =========================
    // READ
    // =========================
    public static function getAll()
    {
        $db = self::getDatabaseConnection();
        $sql = "SELECT * FROM movies ORDER BY movie_id DESC";
        $result = $db->send_query($sql);

        if ($result['success']) {
            return $result['data'];
        }
        return [];
    }

    // =========================
    // READ BY ID
    // =========================
    public static function findById($movie_id)
    {
        $db = self::getDatabaseConnection();
        $sql = "SELECT * FROM movies WHERE movie_id = $1";
        $result = $db->send_query($sql, [$movie_id]);

        if ($result['success'] && !empty($result['data'])) {
            $data = $result['data'][0];
            return new Movie(
                $data['genre_id'],
                $data['title'],
                $data['duration'],
                $data['description'],
                $data['release_date'],
                $data['poster'],
                $data['movie_id']
            );
        }
        return null;
    }

    // =========================
    // UPDATE
    // =========================
    public function update()
    {
        if ($this->movie_id === null) {
            throw new RuntimeException('Film belum memiliki ID.');
        }

        $db = self::getDatabaseConnection();
        $sql = "UPDATE movies SET genre_id = $1, title = $2, description = $3, duration = $4, release_date = $5, poster = $6 WHERE movie_id = $7";
        
        $params = [
            $this->genre_id,
            $this->title,
            $this->description,
            $this->duration,
            $this->release_date,
            $this->poster,
            $this->movie_id
        ];
        
        $result = $db->send_query($sql, $params);
        return $result['success'];
    }

    // =========================
    // DELETE
    // =========================
    public function delete()
    {
        if ($this->movie_id === null) {
            throw new RuntimeException('Film belum memiliki ID.');
        }

        $db = self::getDatabaseConnection();
        $sql = "DELETE FROM movies WHERE movie_id = $1";
        
        $result = $db->send_query($sql, [$this->movie_id]);
        return $result['success'];
    }

    // =========================
    // SAVE
    // =========================
    public function save()
    {
        if ($this->movie_id === null) {
            return $this->create();
        }
        return $this->update();
    }
}