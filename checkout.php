<?php
/**
 * File     : checkout.php
 * Card     : Trx-02 Checkout UI
 * Tugas    : Pilih jumlah tiket (1-6), total harga. Wajib login. Data pemesan dari session.
 * PIC      : Davientyo Arifius Putra
 * NIM      : 434251115
 * Deadline : 3 Oktober 2026
 */

// Mulai session agar kita bisa mengakses data login user
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi: wajib login (Trx-02)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Ambil ID jadwal tayang (showtime_id) dari parameter URL atau POST
$showtimeId = (int)($_GET['showtime_id'] ?? $_POST['showtime_id'] ?? 0);

// Jika parameter showtime_id belum ada dari modul film, buat putih saja
if ($showtimeId <= 0) {
    exit;
}

// Inisialisasi koneksi database PDO
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

// Hubungkan ke class Order dan Ticket di folder classes
require_once __DIR__ . '/classes/Order.php';
require_once __DIR__ . '/classes/Ticket.php';

// Ambil data pemesan otomatis dari sesi yang aktif (Trx-02)
$currentUserId   = $_SESSION['user_id'];
$currentUserName = $_SESSION['name'] ?? $_SESSION['user_name'] ?? 'Pengguna Bioskop';
$currentUserEmail = $_SESSION['email'] ?? '';

// Jika email belum ada di session, kita coba query dari tabel users
if (empty($currentUserEmail) && $db) {
    try {
        $stmtUser = $db->prepare("SELECT email, name FROM users WHERE id = :id LIMIT 1");
        $stmtUser->execute([':id' => $currentUserId]);
        $userData = $stmtUser->fetch();
        if ($userData) {
            $currentUserEmail = $userData['email'];
            if (!empty($userData['name'])) {
                $currentUserName = $userData['name'];
            }
        }
    } catch (Exception $e) {
    }
}

$orderModel = new Order($db);

// Ambil rincian film dan jadwal tayang dari database
$sqlShowtime = "SELECT 
                    st.id AS showtime_id,
                    st.price,
                    st.show_date,
                    st.show_time,
                    m.id AS movie_id,
                    m.title AS movie_title,
                    m.poster AS movie_poster,
                    m.duration AS movie_duration,
                    g.name AS genre_name,
                    s.name AS studio_name,
                    COALESCE(s.capacity, 50) AS studio_capacity
                FROM showtimes st
                LEFT JOIN movies m ON st.movie_id = m.id
                LEFT JOIN genres g ON m.genre_id = g.id
                LEFT JOIN studios s ON st.studio_id = s.id
                WHERE st.id = :showtime_id
                LIMIT 1";
$stmtST = $db->prepare($sqlShowtime);
$stmtST->execute([':showtime_id' => $showtimeId]);
$showtime = $stmtST->fetch();

// Jika data jadwal dari modul lain belum ada di database, buat putih saja
if (!$showtime) {
    exit;
}

// Cek kuota kursi yang masih tersisa
$quotaInfo = $orderModel->checkQuota($showtimeId, 1);

// ==============================================================================
// PENANGANAN FORM SUBMIT (PROSES CHECKOUT)
// ==============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_checkout'])) {
    $quantity = (int)($_POST['quantity'] ?? 1);

    // Validasi jumlah tiket: hanya boleh 1 sampai 6 lembar (Jobdesk Trx-02)
    if ($quantity >= 1 && $quantity <= 6) {
        try {
            // Jalankan transaksi database atomik (Jobdesk Trx-01)
            $result = $orderModel->createOrderWithTickets($currentUserId, $showtimeId, $quantity);

            if ($result && isset($result['order_id'])) {
                header("Location: confirm.php?order_id=" . $result['order_id']);
                exit;
            }
        } catch (Exception $e) {
        }
    }
}

// Harga tiket satuan
$ticketPrice = (float)($showtime['price'] ?? 50000);
$maxSelectable = $quotaInfo ? min(6, $quotaInfo['remaining']) : 6;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Tiket - <?= htmlspecialchars($showtime['movie_title'] ?? 'Bioskop') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Space+Mono:wght@700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #e50914;
            --primary-hover: #b80710;
            --dark-bg: #0f1015;
            --card-bg: #181920;
            --input-bg: #22232c;
            --border-color: #2e303d;
            --text-muted: #9ca3af;
            --success: #10b981;
            --accent: #f59e0b;
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
            padding: 40px 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .page-title {
            font-size: 26px;
            font-weight: 800;
            color: #ffffff;
        }

        .btn-back {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s;
        }

        .btn-back:hover {
            color: #ffffff;
        }

        /* Alert Notifikasi */
        .alert {
            padding: 16px 20px;
            border-radius: 10px;
            background: rgba(239, 68, 68, 0.15);
            border-left: 4px solid var(--primary);
            color: #fca5a5;
            margin-bottom: 24px;
            font-size: 14px;
        }

        /* Grid Dua Kolom */
        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 1.3fr;
            gap: 28px;
        }

        /* Kartu Rincian Film */
        .movie-card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .movie-summary {
            display: flex;
            gap: 16px;
        }

        .movie-poster {
            width: 85px;
            height: 120px;
            border-radius: 10px;
            object-fit: cover;
            background-color: #2a2b36;
        }

        .movie-info h2 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 6px;
            line-height: 1.3;
        }

        .movie-meta {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        .badge-studio {
            display: inline-block;
            background: #272733;
            color: #60a5fa;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 6px;
            text-transform: uppercase;
        }

        .schedule-box {
            background-color: #14151b;
            border-radius: 12px;
            padding: 16px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            border: 1px solid #232530;
        }

        .schedule-item span {
            display: block;
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .schedule-item strong {
            font-size: 14px;
            color: #ffffff;
        }

        .quota-badge {
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--success);
            background: rgba(16, 185, 129, 0.1);
            padding: 6px 12px;
            border-radius: 20px;
            width: fit-content;
        }

        /* Kartu Form Pemesanan */
        .form-card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 28px;
            display: flex;
            flex-direction: column;
            gap: 22px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: #d1d5db;
        }

        .form-control {
            background-color: var(--input-bg);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 12px 16px;
            color: #ffffff;
            font-family: inherit;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            border-color: var(--primary);
        }

        .form-control[readonly] {
            background-color: #191a22;
            color: #9ca3af;
            cursor: not-allowed;
        }

        /* Pilihan Jumlah Tiket (Buttons Counter) */
        .quantity-selector {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 8px;
        }

        .qty-btn {
            background-color: var(--input-bg);
            border: 1px solid var(--border-color);
            color: #ffffff;
            padding: 12px 0;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }

        .qty-btn:hover {
            border-color: var(--primary);
            background-color: #2b2c38;
        }

        .qty-btn.active {
            background-color: var(--primary);
            border-color: var(--primary);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(229, 9, 20, 0.4);
        }

        .qty-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
            border-color: #262730;
        }

        /* Ringkasan Total Harga Real-time */
        .price-summary {
            background: #14151b;
            border-radius: 12px;
            padding: 18px;
            border: 1px dashed var(--border-color);
            margin-top: 8px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid var(--border-color);
            padding-top: 12px;
            margin-top: 8px;
        }

        .summary-total span {
            font-size: 14px;
            font-weight: 600;
            color: #ffffff;
        }

        .summary-total strong {
            font-family: 'Space Mono', monospace;
            font-size: 22px;
            color: var(--primary);
        }

        /* Tombol Konfirmasi */
        .btn-submit {
            background-color: var(--primary);
            color: #ffffff;
            border: none;
            padding: 16px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 6px 18px rgba(229, 9, 20, 0.35);
        }

        .btn-submit:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="container">

    <div class="page-header">
        <h1 class="page-title">Checkout Tiket Bioskop</h1>
        <a href="index.php" class="btn-back">
            &larr; Kembali ke Beranda
        </a>
    </div>

    <div class="checkout-grid">
        
        <!-- Kolom Kiri: Informasi Film & Jadwal -->
        <div class="movie-card">
            <div class="movie-summary">
                <?php if (!empty($showtime['movie_poster'])): ?>
                    <img src="<?= htmlspecialchars($showtime['movie_poster']) ?>" alt="Poster" class="movie-poster" onerror="this.style.display='none'">
                <?php endif; ?>
                <div class="movie-info">
                    <h2><?= htmlspecialchars($showtime['movie_title'] ?? 'Judul Film') ?></h2>
                    <p class="movie-meta">
                        <?= htmlspecialchars($showtime['genre_name'] ?? 'General') ?> &bull; 
                        <?= (int)($showtime['movie_duration'] ?? 120) ?> Menit
                    </p>
                    <span class="badge-studio"><?= htmlspecialchars($showtime['studio_name'] ?? 'Studio 1') ?></span>
                </div>
            </div>

            <!-- Rincian Jadwal -->
            <div class="schedule-box">
                <div class="schedule-item">
                    <span>Tanggal</span>
                    <strong><?= !empty($showtime['show_date']) ? date('d M Y', strtotime($showtime['show_date'])) : '-' ?></strong>
                </div>
                <div class="schedule-item">
                    <span>Jam Tayang</span>
                    <strong><?= !empty($showtime['show_time']) ? date('H:i', strtotime($showtime['show_time'])) . ' WIB' : '-' ?></strong>
                </div>
                <div class="schedule-item">
                    <span>Harga Satuan</span>
                    <strong>Rp <?= number_format($ticketPrice, 0, ',', '.') ?></strong>
                </div>
                <div class="schedule-item">
                    <span>Kapasitas Studio</span>
                    <strong><?= (int)($showtime['studio_capacity'] ?? 50) ?> Kursi</strong>
                </div>
            </div>

            <!-- Keterangan Sisa Kuota -->
            <?php if ($quotaInfo): ?>
                <div class="quota-badge">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    Sisa Kursi Tersedia: <strong><?= $quotaInfo['remaining'] ?> Tiket</strong>
                </div>
            <?php endif; ?>
        </div>

        <!-- Kolom Kanan: Form Pemilihan Tiket & Pembayaran -->
        <div class="form-card">
            <form method="POST" action="checkout.php" id="checkoutForm">
                <input type="hidden" name="showtime_id" value="<?= $showtimeId ?>">
                <input type="hidden" name="quantity" id="inputQuantity" value="1">

                <!-- 1. Data Pemesan Otomatis dari Akun Login (Trx-02) -->
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label">Nama Pemesan</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($currentUserName) ?>" readonly>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label">Email Pemesan</label>
                    <input type="email" class="form-control" value="<?= htmlspecialchars($currentUserEmail) ?>" readonly>
                </div>

                <!-- 2. Pilihan Jumlah Tiket 1-6 Lembar (Trx-02) -->
                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label">Pilih Jumlah Tiket (Maksimal 6 Lembar)</label>
                    <div class="quantity-selector">
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                            <?php $disabled = ($i > $maxSelectable); ?>
                            <button type="button" 
                                    class="qty-btn <?= $i === 1 ? 'active' : '' ?>" 
                                    data-qty="<?= $i ?>"
                                    <?= $disabled ? 'disabled title="Kursi tidak mencukupi"' : '' ?>>
                                <?= $i ?>
                            </button>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- 3. Rincian Total Harga Real-time (JavaScript Otomatis) -->
                <div class="price-summary">
                    <div class="summary-row">
                        <span>Harga Tiket Satuan</span>
                        <span>Rp <?= number_format($ticketPrice, 0, ',', '.') ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Jumlah Tiket</span>
                        <span id="displayQty">1 Tiket</span>
                    </div>
                    <div class="summary-total">
                        <span>Total Pembayaran</span>
                        <strong id="displayTotal">Rp <?= number_format($ticketPrice, 0, ',', '.') ?></strong>
                    </div>
                </div>

                <!-- 4. Tombol Submit Pesanan -->
                <div style="margin-top: 24px;">
                    <button type="submit" name="btn_checkout" class="btn-submit" style="width: 100%;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                        </svg>
                        Konfirmasi &amp; Pesan Sekarang
                    </button>
                </div>
            </form>
        </div>

    </div>

</div>

<!-- JavaScript Ringan untuk Hitung Total Harga Otomatis (Jobdesk Trx-02) -->
<script>
    // Harga satuan tiket dari PHP
    const unitPrice = <?= $ticketPrice ?>;
    const inputQty = document.getElementById('inputQuantity');
    const displayQty = document.getElementById('displayQty');
    const displayTotal = document.getElementById('displayTotal');
    const qtyButtons = document.querySelectorAll('.qty-btn');

    // Fungsi format angka ke format mata uang Rupiah
    function formatRupiah(number) {
        return 'Rp ' + number.toLocaleString('id-ID');
    }

    // Pasang event listener pada setiap tombol pilihan jumlah tiket (1 sampai 6)
    qtyButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            if (this.disabled) return;

            // Hapus status active dari semua tombol
            qtyButtons.forEach(b => b.classList.remove('active'));

            // Aktifkan tombol yang diklik
            this.classList.add('active');

            const selectedQty = parseInt(this.getAttribute('data-qty'), 10);
            
            // Perbarui nilai input hidden form
            inputQty.value = selectedQty;

            // Perbarui tampilan teks jumlah tiket
            displayQty.textContent = selectedQty + ' Tiket';

            // Hitung dan perbarui total harga secara instan di layar
            const total = selectedQty * unitPrice;
            displayTotal.textContent = formatRupiah(total);
        });
    });
</script>

</body>
</html>
