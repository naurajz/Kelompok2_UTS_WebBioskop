<?php
/**
 * File     : logout.php
 * Card     : Auth-02 Logout & Proteksi Halaman
 * Tugas    : session_destroy() lalu redirect ke login.php. Tanpa tampilan.
 * PIC      : Syahrisham Rafif Thufail
 * Deadline : 1 Oktober 2026
 */

// Inisialisasi session lewat bootstrap
require_once "bootstrap.php";

// Kosongkan array session
$_SESSION = [];

// Hapus cookie session jika ada
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Hancurkan session
session_destroy();

// Redirect kembali ke halaman login
header("Location: login.php");
exit();
