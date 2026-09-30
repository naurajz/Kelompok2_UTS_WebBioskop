<?php
/**
 * File     : classes/Ticket.php
 * Card     : Ticket-01 Ticket Backend + Ticket-03 Kode Booking
 * Tugas    : Class Ticket extends BaseModel. Isi: buat tiket berkode unik per pesanan, generate kode booking.
 * PIC      : Shafrie Alvito Wimala Rasendrya
 * NIM      : 434251142
 * Deadline : 3 Oktober 2026
 */

require_once __DIR__ . '/BaseModel.php';

// Fallback jika BaseModel belum diisi oleh tim Core-02 agar tidak terjadi fatal error saat testing
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
 * Class Ticket
 * Mengelola data tiket bioskop, generate kode booking pesanan, kode tiket individual, dan QR Code.
 */
class Ticket extends BaseModel {
    protected $table = 'tickets';

    // Properti kolom tabel tickets
    protected $id;
    protected $order_id;
    protected $ticket_code;
    protected $seat_number;
    protected $created_at;

    /**
     * Constructor Ticket
     * @param PDO|null $db Instance koneksi database PDO
     * @param int|null $order_id ID pesanan (foreign key ke orders.id)
     * @param string|null $ticket_code Kode unik tiket (misal: BK7F3A2-1)
     * @param string|null $seat_number Nomor kursi (opsional, misal: A1)
     */
    public function __construct($db = null, $order_id = null, $ticket_code = null, $seat_number = null) {
        parent::__construct($db);
        $this->order_id = $order_id;
        $this->ticket_code = $ticket_code;
        $this->seat_number = $seat_number;
    }

    // ==========================================
    // GETTER & SETTER (Encapsulation)
    // ==========================================

    public function getOrderId() {
        return $this->order_id;
    }

    public function setOrderId($order_id) {
        $this->order_id = $order_id;
        return $this;
    }

    public function getTicketCode() {
        return $this->ticket_code;
    }

    public function setTicketCode($ticket_code) {
        $this->ticket_code = $ticket_code;
        return $this;
    }

    public function getSeatNumber() {
        return $this->seat_number;
    }

    public function setSeatNumber($seat_number) {
        $this->seat_number = $seat_number;
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
    // TICKET-03: KODE BOOKING & QR HELPER
    // ==========================================

    /**
     * Membuat kode booking unik untuk pesanan (Ticket-03)
     * Format contoh: BK7F3A2 (Prefix BK + 6 karakter heksadesimal unik acak)
     * 
     * @param string $prefix Awalan kode booking (default: 'BK')
     * @return string Kode booking unik
     */
    public static function generateBookingCode($prefix = 'BK') {
        // Mengambil 6 karakter terakhir dari uniqid() untuk ringkas dan unik
        return $prefix . strtoupper(substr(uniqid(), -6));
    }

    /**
     * Membuat kode unik per lembar tiket berdasarkan kode booking (Ticket-01 & Ticket-03)
     * Format contoh: Tiket ke-1 dari pesanan BK7F3A2 menjadi BK7F3A2-1
     * 
     * @param string $bookingCode Kode booking induk
     * @param int|string $seatOrIndex Nomor urutan tiket (1, 2, dst) atau nomor kursi
     * @return string Kode tiket unik
     */
    public static function generateTicketCode($bookingCode, $seatOrIndex = 1) {
        return strtoupper(trim($bookingCode)) . '-' . $seatOrIndex;
    }

    /**
     * Menghasilkan URL QR Code publik untuk kemudahan cetak / scan e-ticket (Ticket-03 QR Opsional)
     * Menggunakan API QRServer tanpa perlu install library tambahan composer.
     * 
     * @param string $data Teks atau kode booking yang di-encode ke QR
     * @param string $size Dimensi gambar QR (default 160x160)
     * @return string URL gambar QR Code
     */
    public static function getQrCodeUrl($data, $size = '160x160') {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . '&data=' . urlencode($data);
    }

    // ==========================================
    // TICKET-01: TICKET BACKEND & CRUD
    // ==========================================

    /**
     * Menyimpan data tiket baru atau mengupdate jika id sudah ada (Implementasi abstract save)
     * @return bool True jika berhasil, False jika gagal
     */
    public function save() {
        if (!$this->db) {
            return false;
        }

        if ($this->id) {
            // Update tiket yang sudah ada
            $sql = "UPDATE {$this->table} 
                    SET order_id = :order_id, 
                        ticket_code = :ticket_code, 
                        seat_number = :seat_number 
                    WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':order_id'    => $this->order_id,
                ':ticket_code' => $this->ticket_code,
                ':seat_number' => $this->seat_number,
                ':id'          => $this->id
            ]);
        } else {
            // Insert tiket baru
            $sql = "INSERT INTO {$this->table} (order_id, ticket_code, seat_number, created_at) 
                    VALUES (:order_id, :ticket_code, :seat_number, NOW())";
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([
                ':order_id'    => $this->order_id,
                ':ticket_code' => $this->ticket_code,
                ':seat_number' => $this->seat_number
            ]);

            if ($success) {
                $this->id = (int)$this->db->lastInsertId();
            }

            return $success;
        }
    }

    /**
     * Mengambil seluruh tiket yang tergabung dalam satu pesanan (berdasarkan order_id)
     * 
     * @param int $orderId ID dari pesanan
     * @return array Daftar baris tiket
     */
    public function getByOrderId($orderId) {
        if (!$this->db) {
            return [];
        }

        $sql = "SELECT * FROM {$this->table} WHERE order_id = :order_id ORDER BY id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':order_id' => $orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Membuat dan menyimpan daftar tiket berkode unik untuk pesanan yang berhasil di-checkout (Ticket-01)
     * Dipanggil oleh proses checkout/transaksi di Order.php.
     * 
     * @param int $orderId ID pesanan yang baru dibuat
     * @param string $bookingCode Kode booking pesanan (misal: BK7F3A2)
     * @param int $quantity Jumlah tiket yang dibeli
     * @param array $seatNumbers Array opsi nomor kursi pilihan (opsional, misal: ['A1', 'A2'])
     * @return array Daftar objek atau data tiket yang berhasil disimpan
     */
    public function createTicketsForOrder($orderId, $bookingCode, $quantity, array $seatNumbers = []) {
        if (!$this->db || $quantity <= 0) {
            return [];
        }

        $createdTickets = [];

        for ($i = 1; $i <= $quantity; $i++) {
            // Generate kode unik per tiket: BK7F3A2-1, BK7F3A2-2, dst.
            $ticketCode = self::generateTicketCode($bookingCode, $i);
            
            // Penentuan nomor kursi: dari array kursi atau fallback penomoran otomatis
            $seatNumber = isset($seatNumbers[$i - 1]) && !empty($seatNumbers[$i - 1]) 
                ? $seatNumbers[$i - 1] 
                : 'Kursi ' . $i;

            $sql = "INSERT INTO {$this->table} (order_id, ticket_code, seat_number, created_at) 
                    VALUES (:order_id, :ticket_code, :seat_number, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':order_id'    => $orderId,
                ':ticket_code' => $ticketCode,
                ':seat_number' => $seatNumber
            ]);

            $createdTickets[] = [
                'id'          => (int)$this->db->lastInsertId(),
                'order_id'    => $orderId,
                'ticket_code' => $ticketCode,
                'seat_number' => $seatNumber
            ];
        }

        return $createdTickets;
    }

    /**
     * Mengambil detail lengkap e-ticket dan pesanan (JOIN dengan showtime, movie, studio, user)
     * Digunakan oleh halaman e-ticket (ticket.php) dan konfirmasi pesanan (confirm.php)
     * 
     * @param int|string $identifier Bisa berupa order_id (int) atau booking_code (string)
     * @return array|null Data gabungan pesanan + rincian film + daftar tiket
     */
    public function getOrderTicketDetails($identifier) {
        if (!$this->db) {
            return null;
        }

        // Query JOIN untuk mengambil semua informasi kontekstual pemesanan
        $sql = "SELECT 
                    o.id AS order_id,
                    o.user_id,
                    o.booking_code,
                    o.total_tickets,
                    o.total_price,
                    o.status AS order_status,
                    o.created_at AS order_created_at,
                    m.id AS movie_id,
                    m.title AS movie_title,
                    m.poster AS movie_poster,
                    m.duration AS movie_duration,
                    m.synopsis AS movie_synopsis,
                    g.name AS genre_name,
                    s.name AS studio_name,
                    st.id AS showtime_id,
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

        $orderData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$orderData) {
            return null;
        }

        // Ambil daftar setiap lembar tiket berkode unik yang terikat pada order ini
        $orderData['tickets'] = $this->getByOrderId($orderData['order_id']);

        return $orderData;
    }
}
