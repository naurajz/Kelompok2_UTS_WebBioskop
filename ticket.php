<?php
/**
 * File     : ticket.php
 * Card     : Ticket-02 Ticket UI
 * Tugas    : E-ticket: film, jam, studio, jumlah tiket, kode booking. Bisa dicetak.
 * PIC      : Shafrie Alvito Wimala Rasendrya
 * NIM      : 434251142
 * Deadline : 3 Oktober 2026
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

// Fallback koneksi PDO jika class Database belum diisi oleh tim DB-03
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

// Tangkap parameter order_id atau booking_code dari query string
$orderIdentifier = $_GET['order_id'] ?? $_GET['code'] ?? null;
$ticketModel = new Ticket($db);
$ticketData = null;
$errorMessage = null;

// Cek autentikasi login (Customer atau Admin)
$isLoggedIn = isset($_SESSION['user_id']);
$currentUserRole = $_SESSION['role'] ?? 'customer';
$currentUserId = $_SESSION['user_id'] ?? null;

if (!$orderIdentifier) {
    $errorMessage = "Parameter order ID atau kode booking tidak ditemukan. Silakan buka tiket melalui halaman Riwayat Pesanan.";
} elseif ($dbError) {
    $errorMessage = "Koneksi database belum tersedia ($dbError). Pastikan MySQL XAMPP aktif dan database 'bioskop' telah diimport.";
} else {
    // Ambil data lengkap tiket, film, jadwal, dan studio (Ticket-01 & Ticket-02)
    $ticketData = $ticketModel->getOrderTicketDetails($orderIdentifier);

    if (!$ticketData) {
        $errorMessage = "Pesanan dengan ID / Kode Booking \"$orderIdentifier\" tidak ditemukan.";
    } else {
        // Validasi kepemilikan pesanan: pastikan pesanan milik user yang login atau role admin
        if ($isLoggedIn && $ticketData['user_id'] && $currentUserRole !== 'admin' && $ticketData['user_id'] != $currentUserId) {
            $errorMessage = "Akses ditolak: Anda tidak memiliki hak untuk melihat e-ticket akun lain.";
            $ticketData = null;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $ticketData ? 'E-Ticket - ' . htmlspecialchars($ticketData['booking_code']) : 'E-Ticket Bioskop' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Space+Mono:wght@700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #e50914;
            --primary-hover: #b80710;
            --dark-bg: #0f1015;
            --card-bg: #181920;
            --ticket-bg: #ffffff;
            --text-dark: #1e2029;
            --text-muted: #6b7280;
            --accent: #f59e0b;
            --border-color: #e5e7eb;
            --success: #10b981;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Outfit', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--dark-bg);
            color: #ffffff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 20px;
        }

        .container {
            width: 100%;
            max-width: 820px;
            margin: 0 auto;
        }

        /* Top Action Bar (No Print) */
        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
        }

        .btn-print {
            background-color: var(--primary);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(229, 9, 20, 0.4);
        }

        .btn-print:hover {
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

        /* Alert Box */
        .alert {
            padding: 24px;
            border-radius: 12px;
            background: #232530;
            border-left: 4px solid var(--accent);
            margin-bottom: 30px;
            text-align: center;
        }

        .alert-error {
            border-left-color: var(--primary);
        }

        .alert h3 {
            font-size: 18px;
            margin-bottom: 8px;
        }

        .alert p {
            color: #9ca3af;
            font-size: 14px;
            margin-bottom: 16px;
        }

        /* E-Ticket Main Card */
        .ticket-wrapper {
            background: var(--ticket-bg);
            color: var(--text-dark);
            border-radius: 18px;
            overflow: hidden;
            display: flex;
            flex-direction: row;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
            position: relative;
        }

        /* Left Side: Main Movie & Screening Details */
        .ticket-main {
            flex: 1 1 65%;
            padding: 32px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
        }

        .ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px dashed var(--border-color);
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        .cinema-brand {
            display: flex;
            flex-direction: column;
        }

        .cinema-logo {
            font-size: 22px;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .cinema-sub {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .badge-status {
            background-color: #e0f2fe;
            color: #0369a1;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .movie-info-section {
            display: flex;
            gap: 20px;
            margin-bottom: 24px;
        }

        .movie-poster {
            width: 90px;
            height: 125px;
            border-radius: 8px;
            object-fit: cover;
            background-color: #e5e7eb;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .movie-details {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .movie-title {
            font-size: 24px;
            font-weight: 800;
            color: #111827;
            margin-bottom: 6px;
            line-height: 1.2;
        }

        .movie-meta {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 8px;
            font-weight: 500;
        }

        /* Grid Information */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            padding: 18px;
            background: #f9fafb;
            border-radius: 12px;
            border: 1px solid #f3f4f6;
            margin-bottom: 24px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .info-value {
            font-size: 15px;
            font-weight: 700;
            color: #1f2937;
        }

        /* Tickets List Section (Ticket-01) */
        .tickets-list-section {
            margin-top: auto;
        }

        .tickets-list-title {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            margin-bottom: 10px;
        }

        .tickets-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .ticket-chip {
            background-color: #f3f4f6;
            border: 1px solid #e5e7eb;
            padding: 6px 12px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
        }

        .ticket-chip strong {
            font-family: 'Space Mono', monospace;
            color: var(--primary);
        }

        .ticket-chip span {
            color: #4b5563;
        }

        /* Perforated Divider (Notches) */
        .ticket-divider {
            width: 2px;
            background-image: linear-gradient(to bottom, #d1d5db 60%, rgba(255, 255, 255, 0) 0%);
            background-position: left;
            background-size: 2px 14px;
            background-repeat: repeat-y;
            position: relative;
        }

        .ticket-divider::before,
        .ticket-divider::after {
            content: '';
            position: absolute;
            width: 28px;
            height: 28px;
            background-color: var(--dark-bg);
            border-radius: 50%;
            left: -14px;
            z-index: 2;
        }

        .ticket-divider::before {
            top: -14px;
        }

        .ticket-divider::after {
            bottom: -14px;
        }

        /* Right Side: Stub & QR Code (Ticket-03) */
        .ticket-stub {
            flex: 0 0 35%;
            background-color: #fafafa;
            padding: 32px 24px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            text-align: center;
        }

        .booking-code-box {
            margin-bottom: 16px;
        }

        .booking-code-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .booking-code-val {
            font-family: 'Space Mono', monospace;
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            letter-spacing: 2px;
            background: #fef2f2;
            padding: 6px 14px;
            border-radius: 8px;
            border: 1px dashed #fca5a5;
            display: inline-block;
        }

        .qr-section {
            background: #ffffff;
            padding: 12px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
            margin: 16px 0;
        }

        .qr-image {
            width: 130px;
            height: 130px;
            display: block;
        }

        .qr-caption {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 6px;
        }

        .total-price-box {
            border-top: 1px solid #e5e7eb;
            width: 100%;
            padding-top: 14px;
            margin-top: auto;
        }

        .price-label {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 600;
        }

        .price-value {
            font-size: 18px;
            font-weight: 800;
            color: #111827;
        }

        /* Footer Notes */
        .ticket-notes {
            margin-top: 24px;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
        }

        /* ====================================================
           PRINT STYLES (@media print)
           Menjamin tiket tercetak rapi, bersih, tanpa navbar/tombol
           ==================================================== */
        @media print {
            body {
                background-color: #ffffff !important;
                color: #000000 !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .no-print,
            .action-bar,
            .ticket-notes,
            header,
            footer,
            nav {
                display: none !important;
            }

            .container {
                max-width: 100% !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .ticket-wrapper {
                box-shadow: none !important;
                border: 2px solid #333333 !important;
                border-radius: 12px !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            .ticket-divider::before,
            .ticket-divider::after {
                background-color: #ffffff !important;
                border: 2px solid #333333 !important;
            }

            .booking-code-val {
                border: 1px solid #333333 !important;
                color: #000000 !important;
            }

            .qr-image {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }

        /* Responsive on Mobile Devices */
        @media (max-width: 680px) {
            .ticket-wrapper {
                flex-direction: column;
            }

            .ticket-divider {
                width: 100%;
                height: 2px;
                background-image: linear-gradient(to right, #d1d5db 60%, rgba(255, 255, 255, 0) 0%);
                background-size: 14px 2px;
                background-repeat: repeat-x;
            }

            .ticket-divider::before,
            .ticket-divider::after {
                top: -14px;
            }

            .ticket-divider::before {
                left: -14px;
            }

            .ticket-divider::after {
                right: -14px;
                left: auto;
            }

            .info-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>

<div class="container">

    <!-- Tombol Navigasi & Cetak (Disembunyikan saat dicetak) -->
    <div class="action-bar no-print">
        <div style="display: flex; gap: 8px;">
            <a href="history.php" class="btn btn-secondary">
                &larr; Riwayat Pesanan
            </a>
            <a href="index.php" class="btn btn-secondary">
                Beranda
            </a>
        </div>
        <?php if ($ticketData): ?>
            <button onclick="window.print()" class="btn btn-print" id="btnPrint">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                Cetak E-Ticket
            </button>
        <?php endif; ?>
    </div>

    <?php if ($errorMessage): ?>
        <!-- Kotak Error / Pemberitahuan jika tiket tidak ditemukan -->
        <div class="alert alert-error">
            <h3>Informasi E-Ticket</h3>
            <p><?= htmlspecialchars($errorMessage) ?></p>
            <div style="display: flex; justify-content: center; gap: 10px;">
                <a href="history.php" class="btn btn-secondary">Lihat Daftar Riwayat</a>
                <a href="index.php" class="btn btn-print">Pesan Tiket Film</a>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($ticketData): ?>
        <!-- Kartu E-Ticket Siap Cetak (Ticket-02) -->
        <div class="ticket-wrapper" id="eTicketCard">
            
            <!-- Sisi Kiri: Detail Film, Jadwal, & Lembar Tiket -->
            <div class="ticket-main">
                
                <div class="ticket-header">
                    <div class="cinema-brand">
                        <span class="cinema-logo">CINESTAR CINEMA</span>
                        <span class="cinema-sub">Official Digital Pass &amp; Entry Ticket</span>
                    </div>
                    <span class="badge-status">
                        <?= htmlspecialchars($ticketData['order_status'] ?? 'CONFIRMED') ?>
                    </span>
                </div>

                <div class="movie-info-section">
                    <?php if (!empty($ticketData['movie_poster'])): ?>
                        <img src="<?= htmlspecialchars($ticketData['movie_poster']) ?>" alt="Poster" class="movie-poster" onerror="this.style.display='none'">
                    <?php endif; ?>
                    <div class="movie-details">
                        <h1 class="movie-title"><?= htmlspecialchars($ticketData['movie_title'] ?? 'Judul Film') ?></h1>
                        <p class="movie-meta">
                            <?= htmlspecialchars($ticketData['genre_name'] ?? 'General') ?> &bull; 
                            <?= (int)($ticketData['movie_duration'] ?? 120) ?> Menit
                        </p>
                        <p style="font-size: 12px; color: #4b5563;">
                            Pemesan: <strong><?= htmlspecialchars($ticketData['customer_name'] ?? 'Customer') ?></strong>
                        </p>
                    </div>
                </div>

                <!-- Grid Rincian Studio, Tanggal, Jam, dan Jumlah Tiket -->
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Studio</span>
                        <span class="info-value"><?= htmlspecialchars($ticketData['studio_name'] ?? 'Studio 1') ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Tanggal Tayang</span>
                        <span class="info-value">
                            <?= !empty($ticketData['show_date']) ? date('d M Y', strtotime($ticketData['show_date'])) : '-' ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Jam Tayang</span>
                        <span class="info-value">
                            <?= !empty($ticketData['show_time']) ? date('H:i', strtotime($ticketData['show_time'])) . ' WIB' : '-' ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Jumlah Tiket</span>
                        <span class="info-value"><?= (int)($ticketData['total_tickets'] ?? count($ticketData['tickets'])) ?> Tiket</span>
                    </div>
                    <div class="info-item" style="grid-column: span 2;">
                        <span class="info-label">Waktu Transaksi</span>
                        <span class="info-value" style="font-size: 13px;">
                            <?= !empty($ticketData['order_created_at']) ? date('d/m/Y H:i', strtotime($ticketData['order_created_at'])) : '-' ?>
                        </span>
                    </div>
                </div>

                <!-- Rincian Tiap Lembar Tiket Berkode Unik (Ticket-01) -->
                <div class="tickets-list-section">
                    <div class="tickets-list-title">Rincian Kode Tiket Masuk:</div>
                    <div class="tickets-chips">
                        <?php if (!empty($ticketData['tickets'])): ?>
                            <?php foreach ($ticketData['tickets'] as $index => $t): ?>
                                <div class="ticket-chip">
                                    <span>#<?= $index + 1 ?></span>
                                    <strong><?= htmlspecialchars($t['ticket_code']) ?></strong>
                                    <span>(<?= htmlspecialchars($t['seat_number'] ?? 'Kursi') ?>)</span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Fallback jika belum di-generate ke tabel tickets -->
                            <div class="ticket-chip">
                                <strong><?= htmlspecialchars(Ticket::generateTicketCode($ticketData['booking_code'], 1)) ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- Garis Sobekan Tiket (Perforation Divider) -->
            <div class="ticket-divider"></div>

            <!-- Sisi Kanan: Stub Kode Booking & QR Code (Ticket-03) -->
            <div class="ticket-stub">
                <div class="booking-code-box">
                    <div class="booking-code-label">Kode Booking</div>
                    <div class="booking-code-val"><?= htmlspecialchars($ticketData['booking_code']) ?></div>
                </div>

                <!-- QR Code Generator Otomatis (Ticket-03) -->
                <div class="qr-section">
                    <img src="<?= Ticket::getQrCodeUrl($ticketData['booking_code']) ?>" 
                         alt="QR Code Tiket" 
                         class="qr-image"
                         title="Scan di pintu masuk bioskop">
                    <div class="qr-caption">Scan di Pintu Masuk</div>
                </div>

                <div class="total-price-box">
                    <div class="price-label">Total Pembayaran</div>
                    <div class="price-value">
                        Rp <?= number_format((float)($ticketData['total_price'] ?? 0), 0, ',', '.') ?>
                    </div>
                </div>
            </div>

        </div>

        <div class="ticket-notes no-print">
            Tunjukkan e-ticket ini (pada layar ponsel atau hasil cetak) kepada petugas bioskop di pintu masuk studio.
        </div>
    <?php endif; ?>

</div>

</body>
</html>
