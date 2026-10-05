<?php

/**
 * File    : login.php
 * Card    : Auth-01 Admin Login + Auth-04 Login Pengguna
 * Tugas   : Satu halaman login untuk semua.
 * PIC     : Syahrisham rafif thufail
 * Deadline : 1 Oktober 2026
 */

// Memanggil file pengaturan utama (seperti koneksi database atau fungsi dasar) agar bisa digunakan di halaman ini.
require_once "bootstrap.php";

// Mengecek apakah sesi pengguna sudah aktif (berarti pengguna sudah berhasil login sebelumnya).
if (isset($_SESSION['user_id'])) {
    
    // Memeriksa tipe akun dari pengguna tersebut.
    if ($_SESSION['role'] === 'admin') {
        // Jika statusnya admin, langsung arahkan/redirect ke halaman panel admin.
        header("Location: admin/dashboard_admin.php");
    } else {
        // Jika statusnya customer biasa, arahkan ke halaman utama/beranda.
        header("Location: index.php");
    }
    // Menghentikan eksekusi kode di bawah baris ini agar proses perpindahan halaman berjalan seketika.
    exit;
}

// Menangkap pesan kegagalan login (jika ada) yang dikirimkan oleh file 'login_post.php' ke dalam variabel $error.
$error = $_SESSION['error'] ?? '';

// Menghapus data error dari sesi memori. Ini disebut sistem 'Flash Message', tujuannya agar pesan error langsung hilang jika user me-refresh (F5) halaman.
unset($_SESSION['error']); 
?>

<!-- ... Bagian <head> dan tag <style> CSS dilewati karena murni untuk pengaturan visual layout ... -->

<body>

    <div class="login-container">
        <h2>Login Akun</h2>

        <!-- Mengecek apakah variabel $error memiliki isi tulisan (apakah login sebelumnya gagal). -->
        <?php if ($error): ?>
            <!-- Jika iya, tampilkan kotak merah peringatan berisi pesan kegagalan tersebut. Fungsi htmlspecialchars digunakan untuk mencegah serangan injeksi kode (XSS). -->
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Form ini bertugas mengumpulkan ketikan user dan mengirimkannya ke file 'login_post.php' secara tertutup/tersembunyi menggunakan metode POST. -->
        <form action="login_post.php" method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <!-- Atribut 'required' memastikan form tidak bisa di-submit jika kolom ini dibiarkan kosong. -->
                <input type="email" id="email" name="email" required placeholder="Masukkan email Anda">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Masukkan password Anda">
            </div>
            <button type="submit">Masuk</button>
        </form>

        <div class="register-link">
            Belum punya akun? <a href="register.php">Daftar sekarang</a>
        </div>
    </div>

</body>
</html>