<?php

/**
 * File     : login.php
 * Card     : Auth-01 Admin Login + Auth-04 Login Pengguna
 * Tugas    : Satu halaman login untuk semua.
 * PIC      : Syahrisham rafif thufail
 * Deadline : 1 Oktober 2026
 */

require_once "bootstrap.php";

// Jika user sudah login, langsung arahkan ke halaman yang sesuai berdasarkan role
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard_admin.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

// Ambil error dari session (jika ada dari login_post.php)
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']); // Hapus error setelah ditampilkan
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bioskop</title>
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

        .login-container {
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

        .register-link {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
            color: #bbb;
        }

        .register-link a {
            color: #e50914;
            text-decoration: none;
            font-weight: bold;
        }

        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <div class="login-container">
        <div class="brand">HIMTI <span>MOVIE</span></div>
        <h2>Login Akun</h2>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="login_post.php" method="POST">
            <div class="form-group">
                <label for="email">Email</label>
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