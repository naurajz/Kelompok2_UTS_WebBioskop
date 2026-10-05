<?php

/**
 * File     : admin/auth_check.php
 * Card     : Auth-02 Logout & Proteksi Halaman
 * Tugas    : Cek session + role admin. Di-include di awal semua halaman admin.
 * PIC      : Syahrisham Rafif Thufail
 * Deadline : 1 Oktober 2026
 */

// Gunakan pengaman bersama agar aturan autentikasi admin tidak diduplikasi.
// require_once: Perintah mutlak untuk menyisipkan dan menjalankan file eksternal. 
// Jika file tujuan tidak ditemukan, PHP akan memicu Fatal Error dan 
// menghentikan seluruh program secara paksa (halaman gagal dimuat total). 
// Imbuhan _once memastikan file tersebut hanya dipanggil maksimal satu kali 
// dalam satu siklus eksekusi untuk mencegah error duplikasi fungsi.
// __DIR__: Konstanta ajaib (magic constant) di PHP yang mendeteksi jalur/lokasi 
// folder tempat file yang sedang dieksekusi berada (dalam hal ini merujuk ke folder admin).
// /../includes/admin_guard.php: Perintah navigasi path. 
// Simbol .. berarti mundur/keluar satu tingkat dari folder saat ini (admin), 
// kemudian masuk ke dalam folder includes untuk mengeksekusi admin_guard.php.
require_once __DIR__ . '/../includes/admin_guard.php';
