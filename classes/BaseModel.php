<?php
//dibuat oleh Rafalen Labibsani Farisqha (434251132)

require_once __DIR__ . '/../config/database.php';
require_once 'Crudable.php';

abstract class BaseModel implements Crudable {
    protected $db;
    protected string $table;
    protected string $primaryKey = 'id';

    public function __construct(string $table_name, string $primaryKey = 'id') {
        $this->db = new DBConnection();
        $this->table = $table_name;
        $this->primaryKey = $primaryKey;
    }

    /**
     * 1. Read: Mengambil seluruh data dari tabel
     */
    public function getAll(): array {
        $query = "SELECT * FROM " . $this->table;
        $response = $this->db->send_query($query);
        return $response['success'] ? $response['data'] : [];
    }

    /**
     * 2. Read: Mengambil data spesifik berdasarkan Primary Key (ID)
     */
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE " . $this->primaryKey . " = $1";
        $response = $this->db->send_query($query, [$id]);
        return ($response['success'] && !empty($response['data'])) ? $response['data'][0] : null;
    }

    /**
     * 3. Create: Menambah data baru ke tabel menggunakan implode()
     */
    public function create(array $data): bool {
        $columns = implode(", ", array_keys($data));
        $placeholders = [];
        $values = array_values($data);

        // Membuat placeholder parameter aman ($1, $2, dst) untuk PostgreSQL
        for ($i = 1; $i <= count($values); $i++) {
            $placeholders[] = "$" . $i;
        }
        $placeholdersStr = implode(", ", $placeholders);

        $query = "INSERT INTO " . $this->table . " (" . $columns . ") VALUES (" . $placeholdersStr . ")";
        $response = $this->db->send_query($query, $values);
        
        return $response['success'];
    }

    /**
     * 4. Update: Mengubah data yang sudah ada berdasarkan Primary Key
     */
    public function update($id, array $data): bool {
        $setClauses = [];
        $values = [];
        $i = 1;

        foreach ($data as $column => $value) {
            $setClauses[] = "{$column} = $" . $i;
            $values[] = $value;
            $i++;
        }

        // Parameter terakhir untuk klausa WHERE ID
        $values[] = $id;
        $setStr = implode(", ", $setClauses);

        $query = "UPDATE " . $this->table . " SET " . $setStr . " WHERE " . $this->primaryKey . " = $" . $i;
        $response = $this->db->send_query($query, $values);

        return $response['success'];
    }

    /**
     * 5. Delete: Menghapus data berdasarkan Primary Key
     */
    public function delete($id): bool {
        $query = "DELETE FROM " . $this->table . " WHERE " . $this->primaryKey . " = $1";
        $response = $this->db->send_query($query, [$id]);
        return $response['success'];
    }
}
?>