<?php
require 'config/Database.php';
try {
    $db = new DBConnection();
    echo "KONEKSI BERHASIL\n";
} catch (Exception $e) {
    echo "GAGAL: " . $e->getMessage() . "\n";
}
