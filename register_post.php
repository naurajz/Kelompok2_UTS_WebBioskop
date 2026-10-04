<?php
require_once "bootstrap.php";
require_once __DIR__ . '/core/Validator.php';

$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm  = $_POST['confirm_password'] ?? '';
$role     = $_POST['role'] ?? 'customer'; // Default ke customer

try {
    $db = new DBConnection();

    // ===== VALIDASI INPUT (Core-04) =====
    $v = new Validator($_POST);
    $v->required('username', 'Username')->maxLength('username', 'Username', 50)
      ->required('email', 'Email')->email('email')->maxLength('email', 'Email', 100)
      ->required('password', 'Password')->minLength('password', 'Password', 6)
      ->required('confirm_password', 'Konfirmasi password')
      ->matches('confirm_password', 'password', 'Konfirmasi password')
      ->unique('email', 'Email', $db, 'users', 'email');   // "Email sudah terdaftar."

    if (!$v->fails() && !in_array($role, ['admin', 'customer'])) {
        $_SESSION['error'] = "Pilihan role tidak valid!";
    } elseif ($v->fails()) {
        $_SESSION['error'] = $v->first();
    } else {
        // ===== SIMPAN AKUN BARU =====
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $simpan = $db->send_query(
            "INSERT INTO users (username, email, password, role) VALUES ($1, $2, $3, $4)",
            [$username, $email, $hash, $role]
        );

        if ($simpan['success']) {
            header("Location: login.php");   // berhasil daftar -> ke login
            exit;
        }
        $_SESSION['error'] = "Pendaftaran gagal, silakan coba lagi!";
    }
} catch (Exception $e) {
    error_log('[register_post] ' . $e->getMessage());
    $_SESSION['error'] = "Terjadi kesalahan pada server. Coba lagi nanti.";
}

// Jika ada error atau gagal, kembali ke form register
header("Location: register.php");
exit;
