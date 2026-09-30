<?php
/**
 * File     : register.php
 * Card     : Auth-03 Register Pengguna
 * Tugas    : Form daftar akun pembeli. password_hash(), role otomatis customer.
 * PIC      : (michael ganteng)
 * Deadline : 2 Oktober 2026
 */

// TODO: tulis kode di sini. Beri comment penjelasan di tiap bagian penting.


// Memulai session untuk menyimpan data login
session_start();

// Memanggil file koneksi database
require_once 'config/Database.php';

// Jika user sudah login, tidak perlu daftar lagi, arahkan sesuai role
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/genre.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

// Variabel untuk menampung pesan error dan nilai input (supaya form tidak kosong lagi saat error)
$error = '';
$name = '';
$email = '';

// Proses form register jika tombol Daftar diklik (metode POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form, trim() untuk menghapus spasi di awal dan akhir
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // ===== VALIDASI INPUT =====
    if (empty($name) || empty($email) || empty($password) || empty($confirm)) {
        $error = "Semua kolom wajib diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // filter_var mengecek apakah format email valid (ada @ dan domain)
        $error = "Format email tidak valid!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } elseif ($password !== $confirm) {
        $error = "Konfirmasi password tidak sama!";
    } else {
        $db = new DBConnection();

        // ===== CEK EMAIL SUDAH TERDAFTAR ATAU BELUM =====
        $cek = $db->send_query("SELECT id FROM users WHERE email = $1", [$email]);

        if (!$cek['success']) {
            // Query gagal (misalnya tabel users belum ada)
            $error = "Terjadi kesalahan pada database. Coba lagi nanti.";
        } elseif (!empty($cek['data'])) {
            $error = "Email sudah terdaftar, silakan gunakan email lain!";
        } else {
            // ===== SIMPAN AKUN BARU =====
            // password di-hash dulu supaya password asli tidak tersimpan di database
            $hash = password_hash($password, PASSWORD_DEFAULT);

            // Role diisi 'customer' langsung di kode (bukan dari form),
            // jadi pendaftar tidak bisa memilih sendiri menjadi admin
            $role = 'customer';

            $simpan = $db->send_query(
                "INSERT INTO users (name, email, password, role) VALUES ($1, $2, $3, $4)",
                [$name, $email, $hash, $role]
            );

            if ($simpan['success']) {
                // Berhasil daftar, arahkan ke halaman login
                header("Location: login.php");
                exit;
            } else {
                $error = "Pendaftaran gagal, silakan coba lagi!";
            }
        }
    }
}
?>
