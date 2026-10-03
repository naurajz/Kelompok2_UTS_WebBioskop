<?php
require_once "bootstrap.php";

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    $_SESSION['error'] = "Email dan password wajib diisi!";
    header("Location: login.php");
    exit();
}

try {
    $db = new DBConnection();

    // Query untuk mengambil data user berdasarkan email
    $query = "SELECT * FROM users WHERE email = $1";
    $result = $db->send_query($query, [$email]);

    if ($result['success'] && !empty($result['data'])) {
        $userData = $result['data'][0];

        // Verifikasi password
        if (password_verify($password, $userData['password']) || $password === $userData['password']) {

            // Inisialisasi object User & Role (Sesuai dengan implementasi OOP)
            $userObj = new User((int) $userData['user_id'], $userData['username'] ?? 'User', $userData['email']);

            // Misal role user diambil dari kolom role
            $roleName = $userData['role'] ?? 'customer';
            // idrole dummy atau disesuaikan dengan DB jika ada tabel role
            $roleObj = new Role(1, $roleName, true);
            $userObj->set_role($roleObj);

            // Set Session
            $_SESSION['user_id'] = $userData['user_id'];
            $_SESSION['role'] = $roleName;
            $_SESSION['user'] = $userObj->get_user(); // menyimpan data dari User Object

            // Redirect berdasarkan role
            if ($roleName === 'admin') {
                header("Location: admin/dashboard_admin.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            $_SESSION['error'] = "Password salah!";
            header("Location: login.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Email tidak terdaftar!";
        header("Location: login.php");
        exit();
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
    header("Location: login.php");
    exit();
}

