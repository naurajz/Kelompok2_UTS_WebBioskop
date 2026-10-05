<?php
require_once __DIR__ . '/BaseModel.php';
class Genre extends BaseModel
{
    // Panjang maksimal sesuai kolom genre_name VARCHAR(30) di database
    private const MAX_NAME_LENGTH = 30;
 
    // Property sesuai kolom tabel genres
    private $genre_id;
    private $genre_name;
 
    
    // CONSTRUCTOR
 
    /**
     * @param string|null $genre_name Nama genre, contoh: 'Horor'
     * @param int|null    $genre_id   Diisi hanya jika ingin mengubah genre yang sudah ada
     */
    public function __construct($genre_name = null, $genre_id = null)
    {
        // Kirim nama tabel dan primary key ke BaseModel
        parent::__construct('genres', 'genre_id');
 
        if ($genre_id !== null) {
            $this->setGenreId($genre_id);
        }
 
        if ($genre_name !== null) {
            $this->setGenreName($genre_name);
        }
    }
 
    // GETTER
 
    public function getGenreId()
    {
        return $this->genre_id;
    }
 
    public function getGenreName()
    {
        return $this->genre_name;
    }
 
    // SETTER + VALIDASI
 
    public function setGenreId($genre_id)
    {
        if (!is_numeric($genre_id) || $genre_id <= 0) {
            throw new InvalidArgumentException('Genre ID harus berupa angka lebih dari 0.');
        }
 
        $this->genre_id = (int) $genre_id;
    }
 
    public function setGenreName($genre_name)
    {
        // Rapikan spasi di awal/akhir dan spasi ganda di tengah
        $genre_name = preg_replace('/\s+/', ' ', trim((string) $genre_name));
 
        if ($genre_name === '') {
            throw new InvalidArgumentException('Nama genre tidak boleh kosong.');
        }
 
        if (mb_strlen($genre_name) > self::MAX_NAME_LENGTH) {
            throw new InvalidArgumentException(
                'Nama genre maksimal ' . self::MAX_NAME_LENGTH . ' karakter.'
            );
        }
 
        $this->genre_name = $genre_name;
    }
 
    // PENGECEKAN DUPLIKAT
 
    /**
     * Cek apakah nama genre ini sudah dipakai genre lain (tidak peduli huruf besar/kecil).
     * Saat update, genre dengan ID yang sama tidak dihitung sebagai duplikat.
     */
    public function isDuplicate(): bool
    {
        $query = "SELECT genre_id FROM genres WHERE LOWER(genre_name) = LOWER($1)";
        $params = [$this->genre_name];
 
        if ($this->genre_id !== null) {
            $query .= " AND genre_id <> $2";
            $params[] = $this->genre_id;
        }
 
        $response = $this->db->send_query($query, $params);
 
        return $response['success'] && !empty($response['data']);
    }
 
    // SAVE
 
    /**
     * Simpan genre ke database.
     * - genre_id kosong  -> INSERT (genre baru)
     * - genre_id terisi  -> UPDATE (ubah nama genre)
     *
     * @throws InvalidArgumentException jika nama kosong atau sudah dipakai
     */
    public function save(): bool
    {
        if ($this->genre_name === null || $this->genre_name === '') {
            throw new InvalidArgumentException('Nama genre tidak boleh kosong.');
        }
 
        if ($this->isDuplicate()) {
            throw new InvalidArgumentException(
                'Genre "' . $this->genre_name . '" sudah ada.'
            );
        }
 
        $data = ['genre_name' => $this->genre_name];
 
        if ($this->genre_id === null) {
            return parent::create($data);
        }
 
        return parent::update($this->genre_id, $data);
    }
 
    // QUERY TAMBAHAN UNTUK UI
 
    /**
     * Ambil semua genre beserta jumlah film di tiap genre, urut abjad.
     * Dipakai tabel di admin/genre.php supaya admin tahu berapa film yang terdampak saat menghapus.
     */
    public function getAllWithMovieCount(): array
    {
        $query = "SELECT g.genre_id, g.genre_name, COUNT(m.movie_id) AS total_film
                  FROM genres g
                  LEFT JOIN movies m ON m.genre_id = g.genre_id
                  GROUP BY g.genre_id, g.genre_name
                  ORDER BY g.genre_name ASC";
 
        $response = $this->db->send_query($query);
 
        return $response['success'] ? $response['data'] : [];
    }
}
