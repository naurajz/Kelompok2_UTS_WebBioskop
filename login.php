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
        body {

            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;

            background-color: #f0f2f5;

            display: flex;

            justify-content: center;

            align-items: center;

            height: 100vh;

            margin: 0;

        }



        .login-container {

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



        .register-link {

            text-align: center;

            margin-top: 1.5rem;

            font-size: 0.9rem;

        }



        .register-link a {

            color: #007bff;

            text-decoration: none;

        }



        .register-link a:hover {

            text-decoration: underline;

        }
    </style>

</head>



<body>



    <div class="login-container">

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