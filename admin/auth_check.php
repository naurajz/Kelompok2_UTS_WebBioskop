<?php

/**
 * File     : admin/auth_check.php
 * Card     : Auth-02 Logout & Proteksi Halaman
 * Tugas    : Cek session + role admin. Di-include di awal semua halaman admin.
 * PIC      : Syahrisham Rafif Thufail
 * Deadline : 1 Oktober 2026
 */

// Gunakan pengaman bersama agar aturan autentikasi admin tidak diduplikasi.
require_once __DIR__ . '/../includes/admin_guard.php';
