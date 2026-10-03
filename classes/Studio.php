<?php
/**
 * File  : classes/Studio.php
 * Tugas : Class Studio extends BaseModel. CRUD tabel studios
 *         (studio_id, studio_name, capacity) + validasi input.
 *
 * getAll(), getById() dan delete() diwarisi dari BaseModel.
 */

require_once __DIR__ . '/BaseModel.php';

class Studio extends BaseModel
{
    private ?string $error = null;

    public function __construct()
    {
        parent::__construct('studios', 'studio_id');
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    /** Daftar studio, diurutkan berdasarkan nama. */
    public function getAll(): array
    {
        $response = $this->db->send_query(
            "SELECT * FROM studios ORDER BY studio_name"
        );
        return $response['success'] ? $response['data'] : [];
    }

    /** Validasi nama dan kapasitas. Return true jika valid. */
    private function validate(array $data, int $ignoreId = 0): bool
    {
        $name = trim($data['studio_name'] ?? '');
        $capacity = $data['capacity'] ?? '';

        if ($name === '') {
            $this->error = 'Nama studio wajib diisi.';
            return false;
        }
        if (mb_strlen($name) > 40) {
            $this->error = 'Nama studio maksimal 40 karakter.';
            return false;
        }
        if (!ctype_digit((string) $capacity) || (int) $capacity < 1) {
            $this->error = 'Kapasitas harus berupa angka minimal 1.';
            return false;
        }

        // Nama studio tidak boleh kembar
        $response = $this->db->send_query(
            "SELECT studio_id FROM studios
             WHERE LOWER(studio_name) = LOWER($1) AND studio_id <> $2",
            [$name, $ignoreId]
        );
        if ($response['success'] && !empty($response['data'])) {
            $this->error = 'Nama studio sudah dipakai.';
            return false;
        }

        return true;
    }

    public function create(array $data): bool
    {
        if (!$this->validate($data)) {
            return false;
        }

        $ok = parent::create([
            'studio_name' => trim($data['studio_name']),
            'capacity'    => (int) $data['capacity'],
        ]);

        if (!$ok) {
            $this->error = 'Gagal menyimpan studio ke database.';
        }
        return $ok;
    }

    public function update($id, array $data): bool
    {
        if (!$this->validate($data, (int) $id)) {
            return false;
        }

        $ok = parent::update($id, [
            'studio_name' => trim($data['studio_name']),
            'capacity'    => (int) $data['capacity'],
        ]);

        if (!$ok) {
            $this->error = 'Gagal mengubah studio.';
        }
        return $ok;
    }
}