<?php
/**
 * File     : classes/Order.php
 * Card     : Trx-01 Order & CO Backend
 * Tugas    : Class Order extends BaseModel. Isi: cek sisa kuota, hitung total, simpan order + tiket dalam satu transaksi database (beginTransaction, commit, rollBack).
 * PIC      : Davientyo Arifius Putra
 * NIM      : 434251115
 * Deadline : 3 Oktober 2026
 */

// Panggil file class induk (BaseModel.php) dan class Ticket untuk pembuatan kode booking & tiket
require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/Ticket.php';

// Ini ban serep sementara: jika BaseModel belum dibuat oleh tim Core-02, kita sediakan kerangka darurat
if (!class_exists('BaseModel')) {
    abstract class BaseModel {
        protected $db;
        protected $table;
        protected $id;

        public function __construct($db = null) {
            $this->db = $db;
        }

        public function getId() {
            return $this->id;
        }

        public function setId($id) {
            $this->id = $id;
        }

        public function getAll() {
            if (!$this->db || !$this->table) return [];
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} ORDER BY id DESC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function getById($id) {
            if (!$this->db || !$this->table) return null;
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        public function delete($id = null) {
            $targetId = $id ?? $this->id;
            if (!$this->db || !$this->table || !$targetId) return false;
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
            return $stmt->execute([':id' => $targetId]);
        }

        abstract public function save();
    }
}

/**
 * Class Order
 * Mengelola transaksi pesanan tiket, pengecekan sisa kuota studio, kalkulasi total harga,
 * dan penyimpanan atomik (Database Transaction) antara tabel orders dan tickets.
 */
class Order extends BaseModel {
    // Nama tabel database yang dihubungkan ke class ini
    protected $table = 'orders';

    // Properti kolom tabel orders MySQL
    protected $id;            // Primary key ID pesanan
    protected $user_id;       // ID user pembeli (relasi ke tabel users)
    protected $showtime_id;   // ID jadwal tayang yang dipesan (relasi ke tabel showtimes)
    protected $booking_code;  // Kode unik booking pesanan (contoh: BK7F3A2)
    protected $total_tickets; // Jumlah tiket yang dibeli (1-6 lembar)
    protected $total_price;   // Total nominal yang harus dibayar (contoh: 150000)
    protected $status;        // Status pesanan ('PAID' / 'CONFIRMED' / 'CANCELLED')
    protected $created_at;     // Waktu pesanan dibuat

    /**
     * Constructor Order
     * Mendukung fleksibilitas pemanggilan:
     * 1. Dependency Injection DB: new Order($db, $userId, $showtimeId, $quantity, $totalPrice)
     * 2. Format praktis soal UTS : new Order($userId, $showtimeId, $quantity) -> contoh: new Order(1, 5, 3)
     */
    public function __construct($arg1 = null, $arg2 = null, $arg3 = null, $arg4 = null, $arg5 = null) {
        // Cek apakah parameter pertama adalah objek koneksi database (PDO)
        if (is_object($arg1)) {
            parent::__construct($arg1);
            $this->user_id       = $arg2;
            $this->showtime_id   = $arg3;
            $this->total_tickets = $arg4;
            $this->total_price   = $arg5;
        } else {
            // Jika dipanggil dengan format: new Order($userId, $showtimeId, $totalTickets)
            parent::__construct(null);
            $this->user_id       = $arg1;
            $this->showtime_id   = $arg2;
            $this->total_tickets = $arg3;
            $this->total_price   = $arg4;
        }

        // Default status pesanan
        if (!$this->status) {
            $this->status = 'CONFIRMED';
        }
    }

    // ==========================================
    // GETTER & SETTER (Encapsulation)
    // ==========================================

    public function getUserId() {
        return $this->user_id;
    }

    public function setUserId($user_id) {
        $this->user_id = $user_id;
        return $this;
    }

    public function getShowtimeId() {
        return $this->showtime_id;
    }

    public function setShowtimeId($showtime_id) {
        $this->showtime_id = $showtime_id;
        return $this;
    }

    public function getBookingCode() {
        return $this->booking_code;
    }

    public function setBookingCode($booking_code) {
        $this->booking_code = $booking_code;
        return $this;
    }

    public function getTotalTickets() {
        return $this->total_tickets;
    }

    public function setTotalTickets($total_tickets) {
        $this->total_tickets = $total_tickets;
        return $this;
    }

    public function getTotalPrice() {
        return $this->total_price;
    }

    public function setTotalPrice($total_price) {
        $this->total_price = $total_price;
        return $this;
    }

    public function getStatus() {
        return $this->status;
    }

    public function setStatus($status) {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt() {
        return $this->created_at;
    }

    public function setCreatedAt($created_at) {
        $this->created_at = $created_at;
        return $this;
    }

    // ==========================================
    // LOGIKA PERHITUNGAN & VALIDASI KUOTA
    // ==========================================

    /**
     * Menghitung total harga pesanan (Jobdesk Trx-01)
     * Contoh: 3 tiket x Rp 50.000 = Rp 150.000
     * 
     * @param float|int $ticketPrice Harga per satu tiket
     * @param int $quantity Jumlah tiket yang dibeli
     * @return float|int Total nominal harga
     */
    public static function calculateTotal($ticketPrice, $quantity) {
        return (float)$ticketPrice * (int)$quantity;
    }

    /**
     * Memeriksa sisa kuota kursi yang masih tersedia untuk jadwal tayang tertentu (Jobdesk Trx-01)
     * Menghitung kapasitas studio dikurangi tiket yang sudah laku terjual
     * 
     * @param int $showtimeId ID jadwal tayang (showtimes.id)
     * @param int $requestedTickets Jumlah tiket yang ingin dibeli pembeli (default: 1)
     * @return array Status ketersediaan kuota beserta rincian angka
     */
    public function checkQuota($showtimeId, $requestedTickets = 1) {
        if (!$this->db) {
            return [
                'available' => false,
                'remaining' => 0,
                'capacity'  => 0,
                'sold'      => 0,
                'price'     => 0,
                'message'   => 'Koneksi database tidak tersedia.'
            ];
        }

        // 1. Ambil kapasitas studio dan harga tiket dari showtime
        $sqlShowtime = "SELECT st.id, st.price, COALESCE(s.capacity, 50) AS capacity 
                        FROM showtimes st 
                        LEFT JOIN studios s ON st.studio_id = s.id 
                        WHERE st.id = :showtime_id 
                        LIMIT 1";
        $stmtShowtime = $this->db->prepare($sqlShowtime);
        $stmtShowtime->execute([':showtime_id' => $showtimeId]);
        $showtime = $stmtShowtime->fetch(PDO::FETCH_ASSOC);

        if (!$showtime) {
            return [
                'available' => false,
                'remaining' => 0,
                'capacity'  => 0,
                'sold'      => 0,
                'price'     => 0,
                'message'   => 'Jadwal tayang tidak ditemukan.'
            ];
        }

        $capacity = (int)$showtime['capacity'];
        $price = (float)$showtime['price'];

        // 2. Hitung jumlah tiket yang sudah terjual untuk showtime ini (tidak menghitung yang dibatalkan)
        $sqlSold = "SELECT COALESCE(SUM(total_tickets), 0) AS total_sold 
                    FROM orders 
                    WHERE showtime_id = :showtime_id AND status != 'CANCELLED'";
        $stmtSold = $this->db->prepare($sqlSold);
        $stmtSold->execute([':showtime_id' => $showtimeId]);
        $soldRow = $stmtSold->fetch(PDO::FETCH_ASSOC);
        $sold = (int)($soldRow['total_sold'] ?? 0);

        // 3. Hitung sisa kuota kursi yang masih kosong
        $remaining = max(0, $capacity - $sold);
        $isAvailable = ($remaining >= $requestedTickets);

        return [
            'available' => $isAvailable,
            'remaining' => $remaining,
            'capacity'  => $capacity,
            'sold'      => $sold,
            'price'     => $price,
            'message'   => $isAvailable ? 'Kuota tersedia.' : "Sisa tiket tidak mencukupi (tersisa {$remaining} tiket)."
        ];
    }

    // ==========================================
    // TRANSAKSI DATABASE (ATOMIK: ORDERS + TICKETS)
    // ==========================================

    /**
     * Menyimpan data pesanan dan tiket dalam SATU TRANSAKSI DATABASE (Jobdesk Trx-01)
     * Menggunakan beginTransaction, commit, dan rollBack agar data konsisten (tidak korup)
     * 
     * @param int $userId ID akun pembeli yang login
     * @param int $showtimeId ID jadwal tayang yang dipilih
     * @param int $quantity Jumlah tiket yang dibeli (1-6 tiket)
     * @param array $seatNumbers Array nomor kursi pilihan (opsional)
     * @return array Hasil transaksi: status success, order_id, booking_code, dll.
     * @throws Exception jika kuota habis atau database gagal
     */
    public function createOrderWithTickets($userId, $showtimeId, $quantity, array $seatNumbers = []) {
        if (!$this->db) {
            throw new Exception("Koneksi database tidak tersedia.");
        }

        // Validasi batasan jumlah tiket (harus 1 sampai 6)
        if ($quantity < 1 || $quantity > 6) {
            throw new Exception("Jumlah tiket yang dapat dibeli adalah 1 sampai 6 tiket.");
        }

        // 1. Cek sisa kuota kursi terlebih dahulu
        $quota = $this->checkQuota($showtimeId, $quantity);
        if (!$quota['available']) {
            throw new Exception("Maaf, kuota kursi tidak mencukupi. Sisa tiket yang tersedia hanya {$quota['remaining']} lembar.");
        }

        // 2. Hitung total harga transaksi (Harga Satuan x Jumlah Tiket)
        $totalPrice = self::calculateTotal($quota['price'], $quantity);

        // 3. Generate kode booking unik menggunakan Ticket::generateBookingCode() (Ticket-03)
        $bookingCode = Ticket::generateBookingCode('BK');

        // ==========================================
        // MULAI DATABASE TRANSACTION (ATOMISITAS)
        // ==========================================
        $this->db->beginTransaction();

        try {
            // A. Simpan data transaksi ke tabel 'orders'
            $sqlOrder = "INSERT INTO {$this->table} 
                         (user_id, showtime_id, booking_code, total_tickets, total_price, status, created_at) 
                         VALUES (:user_id, :showtime_id, :booking_code, :total_tickets, :total_price, :status, NOW())";
            $stmtOrder = $this->db->prepare($sqlOrder);
            $stmtOrder->execute([
                ':user_id'       => $userId,
                ':showtime_id'   => $showtimeId,
                ':booking_code'  => $bookingCode,
                ':total_tickets' => $quantity,
                ':total_price'   => $totalPrice,
                ':status'        => 'CONFIRMED'
            ]);

            // Ambil ID pesanan yang baru di-generate oleh database
            $orderId = (int)$this->db->lastInsertId();

            // B. Simpan data lembar tiket berkode unik ke tabel 'tickets' (Ticket-01)
            $ticketModel = new Ticket($this->db);
            $createdTickets = $ticketModel->createTicketsForOrder($orderId, $bookingCode, $quantity, $seatNumbers);

            // C. Jika kedua proses di atas berhasil tanpa hambatan, lakukan COMMIT
            $this->db->commit();

            // Set properti objek dengan data pesanan yang baru disimpan
            $this->id            = $orderId;
            $this->user_id       = $userId;
            $this->showtime_id   = $showtimeId;
            $this->booking_code  = $bookingCode;
            $this->total_tickets = $quantity;
            $this->total_price   = $totalPrice;
            $this->status        = 'CONFIRMED';

            return [
                'success'       => true,
                'order_id'      => $orderId,
                'booking_code'  => $bookingCode,
                'total_tickets' => $quantity,
                'total_price'   => $totalPrice,
                'tickets'       => $createdTickets
            ];

        } catch (Exception $e) {
            // D. Jika terjadi error di tengah proses, lakukan ROLLBACK untuk membatalkan semua perubahan
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw new Exception("Gagal memproses pesanan: " . $e->getMessage());
        }
    }

    /**
     * Mengambil rincian data satu pesanan (JOIN dengan showtime, movie, studio)
     * Digunakan oleh halaman konfirmasi pesanan (confirm.php)
     * 
     * @param int|string $identifier ID pesanan (angka) atau kode booking (teks)
     * @return array|null Rincian pesanan
     */
    public function getOrderDetail($identifier) {
        if (!$this->db) {
            return null;
        }

        $sql = "SELECT 
                    o.*,
                    m.title AS movie_title,
                    m.poster AS movie_poster,
                    m.duration AS movie_duration,
                    g.name AS genre_name,
                    s.name AS studio_name,
                    st.show_date,
                    st.show_time,
                    st.price AS ticket_price,
                    u.name AS customer_name,
                    u.email AS customer_email
                FROM orders o
                LEFT JOIN showtimes st ON o.showtime_id = st.id
                LEFT JOIN movies m ON st.movie_id = m.id
                LEFT JOIN genres g ON m.genre_id = g.id
                LEFT JOIN studios s ON st.studio_id = s.id
                LEFT JOIN users u ON o.user_id = u.id
                WHERE (o.id = :id_or_code OR o.booking_code = :id_or_code_str)
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_or_code'     => is_numeric($identifier) ? (int)$identifier : 0,
            ':id_or_code_str' => (string)$identifier
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Mengambil seluruh daftar pesanan milik akun user tertentu
     * Digunakan oleh halaman riwayat pesanan (history.php - User-03)
     * 
     * @param int $userId ID pengguna
     * @return array Daftar riwayat pesanan
     */
    public function getByUserId($userId) {
        if (!$this->db) {
            return [];
        }

        $sql = "SELECT 
                    o.*,
                    m.title AS movie_title,
                    m.poster AS movie_poster,
                    s.name AS studio_name,
                    st.show_date,
                    st.show_time
                FROM orders o
                LEFT JOIN showtimes st ON o.showtime_id = st.id
                LEFT JOIN movies m ON st.movie_id = m.id
                LEFT JOIN studios s ON st.studio_id = s.id
                WHERE o.user_id = :user_id
                ORDER BY o.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Implementasi method abstract save() dari BaseModel
     */
    public function save() {
        if (!$this->db) {
            return false;
        }

        if ($this->id) {
            $sql = "UPDATE {$this->table} 
                    SET status = :status, total_price = :total_price 
                    WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':status'      => $this->status,
                ':total_price' => $this->total_price,
                ':id'          => $this->id
            ]);
        } else {
            $sql = "INSERT INTO {$this->table} 
                    (user_id, showtime_id, booking_code, total_tickets, total_price, status, created_at) 
                    VALUES (:user_id, :showtime_id, :booking_code, :total_tickets, :total_price, :status, NOW())";
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([
                ':user_id'       => $this->user_id,
                ':showtime_id'   => $this->showtime_id,
                ':booking_code'  => $this->booking_code,
                ':total_tickets' => $this->total_tickets,
                ':total_price'   => $this->total_price,
                ':status'        => $this->status ?? 'CONFIRMED'
            ]);

            if ($success) {
                $this->id = (int)$this->db->lastInsertId();
            }

            return $success;
        }
    }
}
