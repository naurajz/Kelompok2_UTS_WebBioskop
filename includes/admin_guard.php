<?php
/**
 * File     : includes/admin_guard.php
 * Tugas    : Memuat bootstrap dan membatasi halaman admin hanya untuk role admin.
 */

require_once __DIR__ . '/../bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak: halaman ini khusus admin.');
}