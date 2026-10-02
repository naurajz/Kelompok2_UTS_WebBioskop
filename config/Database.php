<?php
class DBConnection
{
    private string $host = "localhost";
    private string $port = "5432";
    private string $dbname = "bioskop";
    private string $username = "postgres";
    private string $password = "alvito1321";
    private $dbconn = null;

    public function __construct()
    {
        $this->init_connect();
    }

    public function init_connect(): void
    {
        $conn_string = "host={$this->host} port={$this->port} dbname={$this->dbname} "
            . "user={$this->username} password={$this->password}";

        $this->dbconn = @pg_connect($conn_string);

        if (!$this->dbconn) {
            $last_error = error_get_last()['message'] ?? '';
            throw new Exception("Koneksi ke database {$this->dbname} tidak dapat dibentuk: " . $last_error);
        }
    }

    public function getConnection()
    {
        return $this->dbconn;
    }

    public function send_query(string $query, array $params = []): array
    {
        if (!$this->dbconn) {
            return ["success" => false, "message" => "Koneksi belum terbentuk.", "data" => []];
        }

        $result = @pg_query_params($this->dbconn, $query, $params);

        if ($result === false) {
            return ["success" => false, "message" => pg_last_error($this->dbconn), "data" => []];
        }

        $data = [];
        if (@pg_num_rows($result) > 0) {
            $data = pg_fetch_all($result);
        }

        return ["success" => true, "message" => "Query executed successfully", "data" => $data];
    }

    public function close_connection(): void
    {
        if ($this->dbconn) {
            pg_close($this->dbconn);
            $this->dbconn = null;
        }
    }

    public function __destruct()
    {
        $this->close_connection();
    }

    public function mulai_transaksi(): void
    {
        pg_query($this->dbconn, "BEGIN");
    }

    public function commit(): void
    {
        pg_query($this->dbconn, "COMMIT");
    }

    public function rollback(): void
    {
        pg_query($this->dbconn, "ROLLBACK");
    }
}
