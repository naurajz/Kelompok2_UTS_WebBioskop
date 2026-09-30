<?php
/**
 * File     : confirm.php
 * Card     : Trx-03 Konfirmasi Pesanan
 * Tugas    : Ringkasan pesanan + kode booking setelah checkout berhasil.
 * PIC      : Davientyo Arifius Putra
 * NIM      : 434251115
 * Deadline : 3 Oktober 2026
 */

// Mulai session agar kita bisa mengakses data login user
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hubungkan ke class Order dan Ticket di folder classes
require_once __DIR__ . '/classes/Order.php';
require_once __DIR__ . '/classes/Ticket.php';

// Inisialisasi koneksi database PDO
$db = null;
$dbError = null;

if (file_exists(__DIR__ . '/config/Database.php')) {
    require_once __DIR__ . '/config/Database.php';
    if (class_exists('Database')) {
        $db = (new Database())->getConnection();
    }
}

// Fallback koneksi PDO jika class Database belum tersedia
if (!$db) {
    try {
        $db = new PDO("mysql:host=localhost;dbname=bioskop;charset=utf8mb4", "root", "", [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } catch (PDOException $e) {
        $dbError = $e->getMessage();
    }
}

// Tangkap parameter order_id dari query string URL
$orderId = (int)($_GET['order_id'] ?? 0);
$orderModel = new Order($db);
$order = null;
$errorMessage = null;

// Ambil data pesanan jika database dan ID tersedia
if ($db && $orderId > 0) {
    $order = $orderModel->getOrderDetail($orderId);

    if (!$order) {
        $errorMessage = "Pesanan dengan ID #{$orderId} tidak ditemukan.";
    } else {
        // Validasi keamanan: pastikan pesanan ini milik user yang sedang login atau role admin
        $currentUserId = $_SESSION['user_id'] ?? null;
        $currentUserRole = $_SESSION['role'] ?? 'customer';

        if ($currentUserId && $order['user_id'] && $currentUserRole !== 'admin' && $order['user_id'] != $currentUserId) {
            $errorMessage = "Akses ditolak: Anda tidak memiliki akses ke rincian pesanan akun lain.";
            $order = null;
        }
    }
} else {
    $errorMessage = "Nomor pesanan tidak valid atau koneksi database belum tersedia.";
}

// Fallback data demo untuk keperluan pratinjau antarmuka (UI Preview) jika dibuka dengan ?preview=1
if (!$order && (isset($_GET['preview']) || empty($db) || $orderId === 0)) {
    $order = [
        'id'            => 101,
        'user_id'       => 1,
        'booking_code'  => 'BK7F3A2',
        'total_tickets' => 3,
        'total_price'   => 150000,
        'status'        => 'CONFIRMED',
        'movie_title'   => 'Avengers: Endgame',
        'studio_name'   => 'Studio 1 Premiere',
        'show_date'     => date('Y-m-d', strtotime('+1 day')),
        'show_time'     => '19:00:00',
        'created_at'    => date('Y-m-d H:i:s')
    ];
    $errorMessage = null;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $order ? 'Konfirmasi Pesanan - ' . htmlspecialchars($order['booking_code']) : 'Konfirmasi Pesanan' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Space+Mono:wght@700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #e50914;
            --primary-hover: #b80710;
            --dark-bg: #0f1015;
            --card-bg: #181920;
            --border-color: #2e303d;
            --text-muted: #9ca3af;
            --success: #10b981;
            --success-bg: rgba(16, 185, 129, 0.12);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--dark-bg);
            color: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
        }

        .confirm-card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 36px 32px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            text-align: center;
        }

        /* Ikon Sukses Animasi Ringan */
        .success-icon-box {
            width: 72px;
            height: 72px;
            background-color: var(--success-bg);
            color: var(--success);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .success-title {
            font-size: 24px;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 8px;
        }

        .success-subtitle {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 24px;
        }

        /* Kotak Kode Booking Menjolok (Jobdesk Trx-03) */
        .booking-badge-container {
            background: #14151b;
            border: 1px dashed var(--border-color);
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 28px;
        }

        .badge-caption {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        .booking-code-text {
            font-family: 'Space Mono', monospace;
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
            letter-spacing: 3px;
        }

        .btn-copy {
            margin-top: 10px;
            background: transparent;
            border: 1px solid #374151;
            color: #d1d5db;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-copy:hover {
            border-color: #9ca3af;
            color: #ffffff;
        }

        /* Rincian Ringkasan Pesanan */
        .order-summary-box {
            background: #191a22;
            border-radius: 12px;
            padding: 20px;
            text-align: left;
            margin-bottom: 28px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #232530;
            font-size: 14px;
        }

        .summary-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .summary-label {
            color: var(--text-muted);
        }

        .summary-val {
            font-weight: 700;
            color: #ffffff;
            text-align: right;
        }

        /* Tombol Aksi */
        .actions-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 24px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }

        .btn-primary {
            background-color: var(--primary);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(229, 9, 20, 0.4);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background-color: #272833;
            color: #d1d5db;
        }

        .btn-secondary:hover {
            background-color: #373949;
            color: #ffffff;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border-left: 4px solid var(--primary);
            color: #fca5a5;
            padding: 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="container">

    <div class="confirm-card">
        
        <?php if ($errorMessage): ?>
            <div class="alert-error">
                <?= htmlspecialchars($errorMessage) ?>
            </div>
            <a href="index.php" class="btn btn-secondary" style="width: 100%;">Kembali ke Beranda</a>
        <?php elseif ($order): ?>
            
            <!-- Ikon Sukses -->
            <div class="success-icon-box">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>

            <h1 class="success-title">Pesanan Berhasil!</h1>
            <p class="success-subtitle">Tiket bioskop Anda telah berhasil dipesan dan dikonfirmasi.</p>

            <!-- Kotak Kode Booking Unik (Jobdesk Trx-03) -->
            <div class="booking-badge-container">
                <div class="badge-caption">Kode Booking Anda</div>
                <div class="booking-code-text" id="bookingCodeText"><?= htmlspecialchars($order['booking_code']) ?></div>
                <button type="button" class="btn-copy" onclick="copyBookingCode()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                    </svg>
                    <span id="copyBtnText">Salin Kode</span>
                </button>
            </div>

            <!-- Rincian Ringkasan Pesanan (Trx-03) -->
            <div class="order-summary-box">
                <div class="summary-item">
                    <span class="summary-label">Judul Film</span>
                    <span class="summary-val"><?= htmlspecialchars($order['movie_title'] ?? 'Film Bioskop') ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Studio</span>
                    <span class="summary-val"><?= htmlspecialchars($order['studio_name'] ?? 'Studio 1') ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Jadwal Tayang</span>
                    <span class="summary-val">
                        <?= !empty($order['show_date']) ? date('d M Y', strtotime($order['show_date'])) : '-' ?> &bull; 
                        <?= !empty($order['show_time']) ? date('H:i', strtotime($order['show_time'])) . ' WIB' : '-' ?>
                    </span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Jumlah Tiket</span>
                    <span class="summary-val"><?= (int)$order['total_tickets'] ?> Lembar</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Total Pembayaran</span>
                    <span class="summary-val" style="color: var(--primary); font-size: 16px;">
                        Rp <?= number_format((float)$order['total_price'], 0, ',', '.') ?>
                    </span>
                </div>
            </div>

            <!-- Tombol Navigasi Aksi Langsung ke Ticket-02 -->
            <div class="actions-group">
                <a href="ticket.php?order_id=<?= $order['id'] ?>" class="btn btn-primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                    </svg>
                    Buka &amp; Cetak E-Ticket
                </a>
                <div style="display: flex; gap: 10px;">
                    <a href="history.php" class="btn btn-secondary" style="flex: 1;">
                        Riwayat Pesanan
                    </a>
                    <a href="index.php" class="btn btn-secondary" style="flex: 1;">
                        Beranda
                    </a>
                </div>
            </div>

        <?php endif; ?>

    </div>

</div>

<script>
    // Fungsi untuk menyalin kode booking ke papan klip (clipboard)
    function copyBookingCode() {
        const codeText = document.getElementById('bookingCodeText').innerText.trim();
        const copyBtnText = document.getElementById('copyBtnText');

        navigator.clipboard.writeText(codeText).then(() => {
            copyBtnText.innerText = 'Tersalin!';
            setTimeout(() => {
                copyBtnText.innerText = 'Salin Kode';
            }, 2000);
        }).catch(err => {
            alert('Kode booking: ' + codeText);
        });
    }
</script>

</body>
</html>
