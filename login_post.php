<?php
// Memanggil konfigurasi awal, autoload class, dan memastikan session aktif
require_once "bootstrap.php";

// Menangkap inputan email dan password dari form (metode POST). 
// Fungsi trim() digunakan untuk menghapus spasi yang tidak disengaja terketik di awal/akhir email.
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Pengecekan validasi dasar: Jika form kosong, buat pesan error dan tendang kembali (redirect) ke halaman login
if (empty($email) || empty($password)) {
    $_SESSION['error'] = "Email dan password wajib diisi!";
    header("Location: login.php");
    exit(); // Wajib ada exit() setelah header untuk menghentikan paksa eksekusi sisa kode di bawahnya
}

try {
    // Membuka jalur koneksi ke database
    $db = new DBConnection();

    // Menyiapkan perintah pencarian data user di database. 
    // Penggunaan $1 (parameter binding) sangat penting untuk mencegah hacker melakukan injeksi kode (SQL Injection).
    $query = "SELECT * FROM users WHERE email = $1";
    $result = $db->send_query($query, [$email]);

    // Mengecek apakah query berhasil dan baris data email tersebut benar-benar ditemukan di tabel
    if ($result['success'] && !empty($result['data'])) {
        $userData = $result['data'][0]; // Mengambil baris data pertama hasil pencarian

        // Memverifikasi kecocokan password. 
        // password_verify() mengecek password yang sudah diacak (hash), sedangkan kondisi sebelahnya (===) bertindak sebagai cadangan jika password di DB masih berupa teks murni (plaintext).
        if (password_verify($password, $userData['password']) || $password === $userData['password']) {

            // Membungkus data mentah dari database menjadi cetakan Objek User (penerapan konsep OOP)
            $userObj = new User((int) $userData['user_id'], $userData['username'] ?? 'User', $userData['email']);

            // Membaca status role pengguna, jika tidak ditemukan maka anggap saja sebagai 'customer'
            $roleName = $userData['role'] ?? 'customer';
            $roleObj = new Role(1, $roleName, true);
            $userObj->set_role($roleObj);

            // MENYIMPAN SESI (SESSION): 
            // Data identitas disimpan ke memori server. Inilah kunci utama yang membuat pengguna berstatus "Sedang Login" sehingga mereka tidak perlu mengetikkan sandi lagi saat berpindah halaman.
            $_SESSION['user_id'] = $userData['user_id'];
            $_SESSION['role'] = $roleName;
            $_SESSION['user'] = $userObj->get_user();

            // Logika pengarah jalur (Routing) setelah sukses login
            if ($roleName === 'admin') {
                header("Location: admin/dashboard_admin.php"); // Jika admin, lempar ke ruang kontrol
            } else {
                header("Location: index.php"); // Jika customer biasa, lempar ke halaman depan bioskop
            }
            exit();

        } else {
            // Skenario jika email ditemukan, namun password yang diketik salah
            $_SESSION['error'] = "Password salah!";
            header("Location: login.php");
            exit();
        }
    } else {
        // Skenario jika email yang diketik sama sekali tidak ada di dalam tabel database
        $_SESSION['error'] = "Email tidak terdaftar!";
        header("Location: login.php");
        exit();
    }
} catch (Exception $e) {
    // Menangkap error sistem (misalnya server database mati) agar layar tidak error blank putih
    $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
    header("Location: login.php");
    exit();
}