<?php
interface Crudable {
    // 1. Read: Mengambil seluruh data
    public function getAll(): array;

    // 2. Read: Mengambil data berdasarkan ID (Primary Key)
    public function getById($id);

    // 3. Create: Menambah data baru
    public function create(array $data): bool;

    // 4. Update: Mengubah data berdasarkan ID
    public function update($id, array $data): bool;

    // 5. Delete: Menghapus data berdasarkan ID
    public function delete($id): bool;
}
?>