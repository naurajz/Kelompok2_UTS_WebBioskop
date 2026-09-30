<?php
require_once "bootstrap.php";

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';
$role = $_POST['role'] ?? 'customer'; // Default ke customer

// ===== VALIDASI INPUT =====
if (empty($username) || empty($email) || empty($password) || empty($confirm) || empty($role)) {
    $_SESSION['error'] = "Semua kolom wajib diisi!";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Format email tidak valid!";
} elseif (strlen($password) < 6) {
    $_SESSION['error'] = "Password minimal 6 karakter!";
} elseif ($password !== $confirm) {
    $_SESSION['error'] = "Konfirmasi password tidak sama!";
} elseif (!in_array($role, ['admin', 'customer'])) {
    $_SESSION['error'] = "Pilihan role tidak valid!";
} else {
    try {
        $db = new DBConnection();

        // ===== CEK EMAIL SUDAH TERDAFTAR ATAU BELUM =====
        $cek = $db->send_query("SELECT user_id FROM users WHERE email = $1", [$email]);

        if (!$cek['success']) {
            $_SESSION['error'] = "Terjadi kesalahan pada database. Coba lagi nanti.";
        } elseif (!empty($cek['data'])) {
            $_SESSION['error'] = "Email sudah terdaftar, silakan gunakan email lain!";
        } else {
            // ===== SIMPAN AKUN BARU =====
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $simpan = $db->send_query(
                "INSERT INTO users (username, email, password, role) VALUES ($1, $2, $3, $4)",
                [$username, $email, $hash, $role]
            );

            if ($simpan['success']) {
                // Berhasil daftar, arahkan ke login
                header("Location: login.php");
                exit;
            } else {
                $_SESSION['error'] = "Pendaftaran gagal, silakan coba lagi!";
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
    }
}

// Jika ada error atau gagal, kembali ke form register
header("Location: register.php");
exit;
