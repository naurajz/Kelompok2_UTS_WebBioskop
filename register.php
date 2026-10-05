<?php
/**
 * File     : register.php
 * Card     : Auth-03 Register Pengguna
 * Tugas    : Form daftar akun pembeli. password_hash(), role otomatis customer.
 * PIC      : michael ganteng
 * Deadline : 2 Oktober 2026
 */
// TODO: tulis kode di sini. Beri comment penjelasan di tiap bagian penting.

require_once "bootstrap.php";

// Jika user sudah login, arahkan sesuai role
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/genre.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

// Ambil pesan error dari session (jika ada) lalu hapus
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - Bioskop</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background:
                radial-gradient(circle at top, rgba(229, 9, 20, 0.18), transparent 55%),
                #080808;
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 2rem 1rem;
        }

        .register-container {
            background: #121212;
            padding: 2.5rem;
            border-radius: 12px;
            border: 1px solid #252525;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.6);
            width: 100%;
            max-width: 400px;
        }

        .brand {
            text-align: center;
            font-size: 25px;
            font-weight: 900;
            margin-bottom: .4rem;
        }

        .brand span {
            color: #e50914;
        }

        h2 {
            text-align: center;
            margin: 0 0 1.5rem;
            color: #ddd;
            font-size: 1.1rem;
            font-weight: normal;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        label {
            display: block;
            margin-bottom: .5rem;
            color: #bbb;
            font-weight: bold;
            font-size: 0.9rem;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            width: 100%;
            padding: .75rem;
            background: #181818;
            color: #fff;
            border: 1px solid #333;
            border-radius: 7px;
            font-size: 1rem;
        }

        input::placeholder {
            color: #777;
        }

        input:focus,
        select:focus {
            border-color: #e50914;
            outline: none;
            box-shadow: 0 0 5px rgba(229, 9, 20, 0.45);
        }

        button {
            width: 100%;
            padding: .8rem;
            background: #e50914;
            color: #fff;
            border: none;
            border-radius: 7px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: bold;
            transition: background 0.3s;
        }

        button:hover {
            background: #b80710;
        }

        .error {
            color: #ff8a8f;
            background: rgba(229, 9, 20, 0.12);
            padding: .75rem;
            border-radius: 7px;
            text-align: center;
            margin-bottom: 1.2rem;
            border: 1px solid rgba(229, 9, 20, 0.5);
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
            color: #bbb;
        }

        .login-link a {
            color: #e50914;
            text-decoration: none;
            font-weight: bold;
        }

        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="register-container">
        <div class="brand">HIMTIX <span></div>
        <h2>Daftar Akun</h2>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="register_post.php" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required placeholder="Masukkan username Anda">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required placeholder="Masukkan email Anda">
            </div>
            <div class="form-group">
                <label for="role">Daftar Sebagai</label>
                <select id="role" name="role" required>
                    <option value="customer">Pelanggan (Customer)</option>
                    <option value="admin">Administrator (Admin)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Minimal 6 karakter">
            </div>
            <div class="form-group">
                <label for="confirm_password">Konfirmasi Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required placeholder="Ulangi password Anda">
            </div>
            <button type="submit">Daftar Sekarang</button>
        </form>

        <div class="login-link">
            Sudah punya akun? <a href="login.php">Masuk di sini</a>
        </div>
    </div>

</body>
</html>