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
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - Bioskop</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .register-container {
            background: #fff;
            padding: 2.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            box-sizing: border-box;
        }

        h2 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #333;
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        label {
            display: block;
            margin-bottom: .5rem;
            color: #555;
            font-weight: bold;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: .75rem;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 1rem;
        }

        input:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 4px rgba(0, 123, 255, 0.25);
        }

        button {
            width: 100%;
            padding: .8rem;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: bold;
            transition: background 0.3s;
        }

        button:hover {
            background: #0056b3;
        }

        .error {
            color: #dc3545;
            background: #f8d7da;
            padding: .75rem;
            border-radius: 6px;
            text-align: center;
            margin-bottom: 1.2rem;
            border: 1px solid #f5c6cb;
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
        }

        .login-link a {
            color: #007bff;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <div class="register-container">
        <h2>Daftar Akun</h2>

        <?php if ($error): ?>
            <!-- htmlspecialchars mencegah kode berbahaya (XSS) tampil di halaman -->
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <div class="form-group">
                <label for="name">Nama Lengkap</label>
                <input type="text" id="name" name="name" required placeholder="Masukkan nama Anda"
                    value="<?php echo htmlspecialchars($name); ?>">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required placeholder="Masukkan email Anda"
                    value="<?php echo htmlspecialchars($email); ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Minimal 6 karakter">
            </div>
            <div class="form-group">
                <label for="confirm_password">Konfirmasi Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required
                    placeholder="Ulangi password Anda">
            </div>
            <button type="submit">Daftar</button>
        </form>

        <div class="login-link">
            Sudah punya akun? <a href="login.php">Masuk di sini</a>
        </div>
    </div>

</body>

</html>