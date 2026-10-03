<?php
require_once __DIR__ . '/BaseModel.php';
class Showtime extends BaseModel {
    private const FIELDS = ['movie_id', 'studio_id', 'show_date', 'show_time', 'price'];

    protected $error = '';

    public function __construct() {
        parent::__construct('showtimes', 'showtime_id');
    }

    public function getError() {
        return $this->error;
    }

    public function validate(array $data, $excludeId = null): bool {
        $price = $data['price'] ?? null;
        if (!is_numeric($price) || (float)$price <= 0) {
            $this->error = 'Harga harus lebih dari 0.';
            return false;
        }

        $time  = substr((string)($data['show_time'] ?? ''), 0, 5);
        $start = DateTime::createFromFormat('Y-m-d H:i', ($data['show_date'] ?? '') . ' ' . $time);
        if (!$start) {
            $this->error = 'Format tanggal atau jam tidak valid.';
            return false;
        }
        if ($start < new DateTime()) {
            $this->error = 'Jadwal tidak boleh di masa lalu.';
            return false;
        }

        $movie = $this->db->send_query("SELECT duration FROM movies WHERE movie_id = $1", [(int)($data['movie_id'] ?? 0)]);
        if (empty($movie['data'])) {
            $this->error = 'Film tidak ditemukan.';
            return false;
        }
        $end = (clone $start)->modify('+' . (int)$movie['data'][0]['duration'] . ' minutes');

        $studioId = (int)($data['studio_id'] ?? 0);
        $studio = $this->db->send_query("SELECT 1 FROM studios WHERE studio_id = $1", [$studioId]);
        if (empty($studio['data'])) {
            $this->error = 'Studio tidak ditemukan.';
            return false;
        }

        $sql = "SELECT s.showtime_id
                FROM {$this->table} s
                JOIN movies m ON m.movie_id = s.movie_id
                WHERE s.studio_id = $1
                  AND s.showtime_id <> $4
                  AND (s.show_date + s.show_time) < $3::timestamp
                  AND (s.show_date + s.show_time) + (m.duration * interval '1 minute') > $2::timestamp";
        $clash = $this->db->send_query($sql, [
            $studioId,
            $start->format('Y-m-d H:i:s'),
            $end->format('Y-m-d H:i:s'),
            (int)($excludeId ?? 0)
        ]);
        if (!empty($clash['data'])) {
            $this->error = 'Jadwal bentrok dengan jadwal lain di studio yang sama.';
            return false;
        }

        return true;
    }

    public function create(array $data): bool {
        $data = array_intersect_key($data, array_flip(self::FIELDS));

        if (!$this->validate($data)) {
            return false;
        }
        if (!parent::create($data)) {
            $this->error = 'Gagal menyimpan jadwal ke database.';
            return false;
        }
        return true;
    }

    public function update($id, array $data): bool {
        $current = $this->getById($id);
        if (!$current) {
            $this->error = 'Jadwal tidak ditemukan.';
            return false;
        }

        $data = array_merge(
            array_intersect_key($current, array_flip(self::FIELDS)),
            array_intersect_key($data, array_flip(self::FIELDS))
        );
        $data['show_time'] = substr((string)$data['show_time'], 0, 5);

        if (!$this->validate($data, $id)) {
            return false;
        }
        if (!parent::update($id, $data)) {
            $this->error = 'Gagal mengubah jadwal di database.';
            return false;
        }
        return true;
    }

    public function getAll(): array {
        $sql = "SELECT s.showtime_id, s.movie_id, s.studio_id, s.show_date, s.show_time, s.price,
                       m.title AS movie_title, m.duration AS movie_duration,
                       st.studio_name
                FROM {$this->table} s
                JOIN movies m ON m.movie_id = s.movie_id
                JOIN studios st ON st.studio_id = s.studio_id
                ORDER BY s.show_date ASC, s.show_time ASC";
        $res = $this->db->send_query($sql);
        return $res['success'] ? $res['data'] : [];
    }

    public function getMovieOptions() {
        return $this->db->send_query("SELECT movie_id, title FROM movies ORDER BY title ASC")['data'];
    }
    public function getStudioOptions() {
        return $this->db->send_query("SELECT studio_id, studio_name FROM studios ORDER BY studio_name ASC")['data'];
    }
}