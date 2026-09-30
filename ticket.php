<?php
/**
 * File     : ticket.php
 * Card     : Ticket-02 Ticket UI
 * Tugas    : E-ticket: film, jam, studio, jumlah tiket, kode booking. Bisa dicetak.
 * PIC      : Shafrie Alvito Wimala Rasendrya
 * NIM      : 434251142
 * Deadline : 3 Oktober 2026
 */

// Mulai sesi PHP kalau belum aktif, supaya kita bisa baca data login user ($_SESSION)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tangkap parameter dari URL browser (?order_id=... atau ?code=...)
$orderIdentifier = $_GET['order_id'] ?? $_GET['code'] ?? null;

// Jika belum ada data dari modul lain atau parameter kosong, buat putih saja
if (!$orderIdentifier) {
    exit;
}

$db = null;

if (file_exists(__DIR__ . '/config/Database.php')) {
    require_once __DIR__ . '/config/Database.php';
    if (class_exists('Database')) {
        $db = (new Database())->getConnection();
    }
}

if (!$db) {
    try {
        $db = new PDO("mysql:host=localhost;dbname=bioskop;charset=utf8mb4", "root", "", [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } catch (PDOException $e) {
        $db = null;
    }
}

if (!$db) {
    exit;
}

// Hubungkan ke class Ticket backend
require_once __DIR__ . '/classes/Ticket.php';

$ticketModel = new Ticket($db);
$ticketData = $ticketModel->getOrderTicketDetails($orderIdentifier);

// Jika data tiket belum ada di database, buat putih saja
if (!$ticketData) {
    exit;
}

// Proteksi keamanan: jika user login sebagai customer, pastikan tiket ini miliknya sendiri
$isLoggedIn = isset($_SESSION['user_id']);
$currentUserRole = $_SESSION['role'] ?? 'customer';
$currentUserId = $_SESSION['user_id'] ?? null;

if ($isLoggedIn && $ticketData['user_id'] && $currentUserRole !== 'admin' && $ticketData['user_id'] != $currentUserId) {
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Ticket - <?= htmlspecialchars($ticketData['booking_code'] ?? 'Bioskop') ?></title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #121418;
            color: #333333;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 15px;
        }

        .container {
            width: 100%;
            max-width: 800px;
        }

        /* Action Bar */
        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            border: none;
        }

        .btn-secondary {
            background-color: #242933;
            color: #e5e7eb;
            cursor: pointer;
        }

        .btn-secondary:hover {
            background-color: #313847;
        }

        .btn-print {
            background-color: #dc2626;
            color: #ffffff;
            opacity: 0.9;
            cursor: default;
            pointer-events: none;
            user-select: none;
        }

        /* Ticket Card */
        .ticket-card {
            background: #ffffff;
            border-radius: 12px;
            display: flex;
            flex-direction: row;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }

        /* Main Section */
        .ticket-main {
            flex: 1 1 65%;
            padding: 28px;
            display: flex;
            flex-direction: column;
        }

        .ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }

        .cinema-title {
            font-size: 20px;
            font-weight: 700;
            color: #dc2626;
            letter-spacing: 0.5px;
        }

        .badge-status {
            background-color: #e0f2fe;
            color: #0369a1;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 700;
        }

        .movie-section {
            display: flex;
            gap: 18px;
            margin-bottom: 22px;
        }

        .movie-poster {
            width: 85px;
            height: 120px;
            border-radius: 6px;
            object-fit: cover;
            background-color: #f3f4f6;
            border: 1px solid #e5e7eb;
        }

        .movie-details {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .movie-title {
            font-size: 22px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 6px;
        }

        .movie-meta {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 8px;
        }

        .movie-customer {
            font-size: 13px;
            color: #374151;
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            padding: 16px;
            background: #f9fafb;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-size: 11px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .info-value {
            font-size: 14px;
            font-weight: 700;
            color: #1f2937;
        }

        /* Stub Section */
        .ticket-stub {
            flex: 0 0 35%;
            background-color: #fafafa;
            border-left: 2px dashed #d1d5db;
            padding: 28px 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            text-align: center;
        }

        .booking-code-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 6px;
        }

        .booking-code-box {
            font-family: Consolas, 'Courier New', monospace;
            font-size: 22px;
            font-weight: 700;
            color: #dc2626;
            letter-spacing: 2px;
            background: #fef2f2;
            padding: 8px 16px;
            border-radius: 6px;
            border: 1px solid #fecaca;
            margin-bottom: 16px;
        }

        .stub-note {
            font-size: 12px;
            color: #6b7280;
            line-height: 1.4;
            margin: 15px 0;
        }

        .total-box {
            border-top: 1px solid #e5e7eb;
            width: 100%;
            padding-top: 14px;
        }

        .total-label {
            font-size: 11px;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: 600;
        }

        .total-value {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
        }

        .ticket-footer {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
        }

        @media (max-width: 640px) {
            .ticket-card {
                flex-direction: column;
            }
            .ticket-stub {
                border-left: none;
                border-top: 2px dashed #d1d5db;
            }
            .info-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>

<div class="container">

    <!-- Tombol Navigasi & Tampilan Tombol Cetak (Non-aktif / Tanpa Fungsi) -->
    <div class="action-bar">
        <div style="display: flex; gap: 8px;">
            <a href="history.php" class="btn btn-secondary">&larr; Riwayat Pesanan</a>
            <a href="index.php" class="btn btn-secondary">Beranda</a>
        </div>
        <button type="button" class="btn btn-print" tabindex="-1">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                <rect x="6" y="14" width="12" height="8"></rect>
            </svg>
            Cetak E-Ticket
        </button>
    </div>

    <!-- Kartu E-Ticket Bioskop -->
    <div class="ticket-card">
            
        <!-- Sisi Kiri: Informasi Film & Jadwal -->
        <div class="ticket-main">
            
            <div class="ticket-header">
                <div class="cinema-title">CINESTAR CINEMA</div>
                <span class="badge-status">
                    <?= htmlspecialchars($ticketData['order_status'] ?? 'CONFIRMED') ?>
                </span>
            </div>

            <div class="movie-section">
                <?php if (!empty($ticketData['movie_poster'])): ?>
                    <img src="<?= htmlspecialchars($ticketData['movie_poster']) ?>" alt="Poster" class="movie-poster" onerror="this.style.display='none'">
                <?php endif; ?>
                <div class="movie-details">
                    <h1 class="movie-title"><?= htmlspecialchars($ticketData['movie_title'] ?? 'Judul Film') ?></h1>
                    <p class="movie-meta">
                        <?= htmlspecialchars($ticketData['genre_name'] ?? 'General') ?> &bull; 
                        <?= (int)($ticketData['movie_duration'] ?? 120) ?> Menit
                    </p>
                    <p class="movie-customer">
                        Pemesan: <strong><?= htmlspecialchars($ticketData['customer_name'] ?? 'Customer') ?></strong>
                    </p>
                </div>
            </div>

            <!-- Grid Rincian Jadwal dan Tiket -->
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
                    <span class="info-value"><?= (int)($ticketData['total_tickets'] ?? 1) ?> Tiket</span>
                </div>
                <div class="info-item" style="grid-column: span 2;">
                    <span class="info-label">Waktu Transaksi</span>
                    <span class="info-value" style="font-size: 13px;">
                        <?= !empty($ticketData['order_created_at']) ? date('d/m/Y H:i', strtotime($ticketData['order_created_at'])) : '-' ?>
                    </span>
                </div>
            </div>

        </div>

        <!-- Sisi Kanan: Stub Kode Booking -->
        <div class="ticket-stub">
            <div>
                <div class="booking-code-label">Kode Booking</div>
                <div class="booking-code-box"><?= htmlspecialchars($ticketData['booking_code'] ?? 'BK00000') ?></div>
            </div>

            <div class="stub-note">
                Tunjukkan kode booking ini kepada petugas di pintu masuk studio.
            </div>

            <div class="total-box">
                <div class="total-label">Total Pembayaran</div>
                <div class="total-value">
                    Rp <?= number_format((float)($ticketData['total_price'] ?? 0), 0, ',', '.') ?>
                </div>
            </div>
        </div>

    </div>

    <div class="ticket-footer">
        Tunjukkan e-ticket ini kepada petugas bioskop di pintu masuk studio.
    </div>

</div>

</body>
</html>
