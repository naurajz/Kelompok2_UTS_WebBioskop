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
    // Data film
    private $id;
    private $genreId;
    private $judul;
    private $sinopsis;
    private $durasi;
    private $poster;


    // =========================
    // CONSTRUCTOR
    // =========================

    /**
     * Contoh sesuai PPL:
     * $movie = new Movie(1, 'Avengers', 150);
     */
    public function __construct(
        $genreId,
        $judul,
        $durasi,
        $sinopsis = '',
        $poster = null,
        $id = null
    ) {
        $this->id = $id;

        // Memakai setter supaya data langsung divalidasi
        $this->setGenreId($genreId);
        $this->setJudul($judul);
        $this->setDurasi($durasi);
        $this->setSinopsis($sinopsis);
        $this->setPoster($poster);
    }


    // =========================
    // GETTER
    // =========================

    public function getId()
    {
        return $this->id;
    }

    public function getGenreId()
    {
        return $this->genreId;
    }

    public function getJudul()
    {
        return $this->judul;
    }

    public function getSinopsis()
    {
        return $this->sinopsis;
    }

    public function getDurasi()
    {
        return $this->durasi;
    }

    public function getPoster()
    {
        return $this->poster;
    }


    // =========================
    // SETTER + VALIDASI
    // =========================

    public function setGenreId($genreId)
    {
        // Genre harus berupa angka dan lebih dari 0
        if (!is_numeric($genreId) || $genreId <= 0) {
            throw new InvalidArgumentException(
                'Genre film harus dipilih.'
            );
        }

        $this->genreId = (int) $genreId;
    }


    public function setJudul($judul)
    {
        $judul = trim($judul);

        // Judul tidak boleh kosong
        if ($judul == '') {
            throw new InvalidArgumentException(
                'Judul film tidak boleh kosong.'
            );
        }

        $this->judul = $judul;
    }


    public function setSinopsis($sinopsis)
    {
        $this->sinopsis = trim($sinopsis);
    }


    public function setDurasi($durasi)
    {
        // Durasi harus angka dan lebih dari 0
        if (!is_numeric($durasi) || $durasi <= 0) {
            throw new InvalidArgumentException(
                'Durasi film harus lebih dari 0 menit.'
            );
        }

        $this->durasi = (int) $durasi;
    }


    public function setPoster($poster)
    {
        // Poster boleh kosong
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
        $database = new Database();

        // Kalau Database.php memakai getConnection()
        if (method_exists($database, 'getConnection')) {
            return $database->getConnection();
        }

        // Kalau Database.php memakai connect()
        if (method_exists($database, 'connect')) {
            return $database->connect();
        }

        throw new RuntimeException(
            'Method koneksi database belum tersedia.'
        );
    }


    // =========================
    // CREATE
    // Menambahkan film baru
    // =========================

    public function create()
    {
        $db = self::getDatabaseConnection();

        $sql = "INSERT INTO movies
                (genre_id, title, synopsis, duration, poster)
                VALUES
                (:genre_id, :title, :synopsis, :duration, :poster)";

        $stmt = $db->prepare($sql);

        $hasil = $stmt->execute([
            ':genre_id' => $this->genreId,
            ':title' => $this->judul,
            ':synopsis' => $this->sinopsis,
            ':duration' => $this->durasi,
            ':poster' => $this->poster
        ]);

        // Mengambil ID film baru kalau berhasil ditambahkan
        if ($hasil) {
            $this->id = $db->lastInsertId();
        }

        return $hasil;
    }


    // =========================
    // READ
    // Mengambil semua film
    // =========================

    public static function getAll()
    {
        $db = self::getDatabaseConnection();

        $sql = "SELECT *
                FROM movies
                ORDER BY id DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    // =========================
    // READ BY ID
    // Mengambil satu film
    // =========================

    public static function findById($id)
    {
        $db = self::getDatabaseConnection();

        $sql = "SELECT *
                FROM movies
                WHERE id = :id";

        $stmt = $db->prepare($sql);

        $stmt->execute([
            ':id' => $id
        ]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        return new Movie(
            $data['genre_id'],
            $data['title'],
            $data['duration'],
            $data['synopsis'],
            $data['poster'],
            $data['id']
        );
    }


    // =========================
    // UPDATE
    // Mengubah data film
    // =========================

    public function update()
    {
        if ($this->id === null) {
            throw new RuntimeException(
                'Film belum memiliki ID.'
            );
        }

        $db = self::getDatabaseConnection();

        $sql = "UPDATE movies
                SET
                    genre_id = :genre_id,
                    title = :title,
                    synopsis = :synopsis,
                    duration = :duration,
                    poster = :poster
                WHERE id = :id";

        $stmt = $db->prepare($sql);

        return $stmt->execute([
            ':genre_id' => $this->genreId,
            ':title' => $this->judul,
            ':synopsis' => $this->sinopsis,
            ':duration' => $this->durasi,
            ':poster' => $this->poster,
            ':id' => $this->id
        ]);
    }


    // =========================
    // DELETE
    // Menghapus film
    // =========================

    public function delete()
    {
        if ($this->id === null) {
            throw new RuntimeException(
                'Film belum memiliki ID.'
            );
        }

        $db = self::getDatabaseConnection();

        $sql = "DELETE FROM movies
                WHERE id = :id";

        $stmt = $db->prepare($sql);

        return $stmt->execute([
            ':id' => $this->id
        ]);
    }


    // =========================
    // SAVE
    // CREATE / UPDATE otomatis
    // =========================

    public function save()
    {
        // Kalau belum punya ID berarti film baru
        if ($this->id === null) {
            return $this->create();
        }

        // Kalau sudah punya ID berarti update film
        return $this->update();
    }
}