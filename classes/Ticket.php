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

/**
 * Class Ticket mewakili tabel 'tickets' (ticket_id, order_id, seat_number).
 * Mengimplementasikan konsep OOP dasar sesuai modul perkuliahan:
 * - Inheritance dari BaseModel (menggunakan koneksi DBConnection PostgreSQL)
 * - Encapsulation (properti private dengan getter dan setter)
 * - Static method untuk generate kode booking unik (Ticket-03)
 * - Magic method __toString()
 * - Mendukung fleksibilitas pemanggilan dari Order.php
 */
class Ticket extends BaseModel
{
    // Property sesuai kolom tabel 'tickets' di database/bioskop.sql
    private $ticket_id;
    private $order_id;
    private $seat_number;

    /**
     * Constructor Ticket
     * Memanggil constructor BaseModel dengan nama tabel 'tickets' dan primary key 'ticket_id'
     *
     * @param mixed       $arg1 ID pesanan (int) atau objek koneksi database
     * @param string|null $arg2 Nomor kursi (contoh: 'A1')
     * @param int|null    $arg3 Primary key tiket jika update
     */
    public function __construct($arg1 = null, $arg2 = null, $arg3 = null)
    {
        parent::__construct('tickets', 'ticket_id');

        if (is_object($arg1)) {
            // Jika dipanggil dari Order.php dengan inject db: new Ticket($this->db)
            $this->db = $arg1;
            $this->order_id = $arg2;
            $this->seat_number = $arg3;
        } else {
            $this->order_id = $arg1;
            $this->seat_number = $arg2;
            $this->ticket_id = $arg3;
        }
    }

    // ==========================================
    // GETTER & SETTER (Encapsulation)
    // ==========================================

    public function getTicketId()
    {
        return $this->ticket_id;
    }

    public function setTicketId($ticket_id): self
    {
        $this->ticket_id = $ticket_id;
        return $this;
    }

    public function getOrderId()
    {
        return $this->order_id;
    }

    public function setOrderId($order_id): self
    {
        $this->order_id = $order_id;
        return $this;
    }

    public function getSeatNumber()
    {
        return $this->seat_number;
    }

    public function setSeatNumber($seat_number): self
    {
        $this->seat_number = $seat_number;
        return $this;
    }

    // ==========================================
    // STATIC METHOD (Ticket-03: Generator Kode Booking)
    // ==========================================

    /**
     * Generate kode booking pesanan (Ticket-03)
     * Format resmi berbasis order_id: BK + 5 digit angka (contoh: BK00001)
     * Juga mendukung prefix string jika dipanggil tanpa order_id spesifik.
     *
     * @param int|string|null $param
     * @return string
     */
    public static function generateBookingCode($param = null): string
    {
        if (is_numeric($param) && (int)$param > 0) {
            return 'BK' . str_pad((string)(int)$param, 5, '0', STR_PAD_LEFT);
        }

        $prefix = (is_string($param) && !empty($param) && $param !== 'BK') ? $param : 'BK';
        return $prefix . strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 5));
    }

    // ==========================================
    // MAGIC METHOD
    // ==========================================

    /**
     * Magic method __toString() dieksekusi ketika objek diperlakukan sebagai string
     */
    public function __toString(): string
    {
        return "Ticket #{$this->ticket_id} (Order: {$this->order_id}, Kursi: {$this->seat_number})";
    }

    // ==========================================
    // OPERASI BASIS DATA (CRUD & Kueri)
    // ==========================================

    /**
     * Menyimpan data tiket ke database
     *
     * @return bool
     */
    public function save(): bool
    {
        if ($this->ticket_id) {
            return $this->update($this->ticket_id, [
                'order_id'    => $this->order_id,
                'seat_number' => $this->seat_number
            ]);
        } else {
            return $this->create([
                'order_id'    => $this->order_id,
                'seat_number' => $this->seat_number
            ]);
        }
    }

    /**
     * Mengambil seluruh baris tiket yang berelasi dengan order_id tertentu
     *
     * @param int $orderId
     * @return array
     */
    public function getByOrderId($orderId): array
    {
        $query = "SELECT * FROM " . $this->table . " WHERE order_id = $1 ORDER BY ticket_id ASC";
        if (method_exists($this->db, 'send_query')) {
            $response = $this->db->send_query($query, [(int)$orderId]);
            return ($response['success'] && !empty($response['data'])) ? $response['data'] : [];
        } elseif ($this->db instanceof PDO) {
            $stmt = $this->db->prepare("SELECT * FROM tickets WHERE order_id = ? ORDER BY ticket_id ASC");
            $stmt->execute([(int)$orderId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }

    /**
     * Menyimpan daftar tiket sekaligus untuk pesanan yang baru dibuat (Ticket-01)
     * Kompatibel dengan pemanggilan dari Order.php:
     * - createTicketsForOrder($orderId, $quantity, $seatNumbers)
     * - createTicketsForOrder($orderId, $bookingCode, $quantity, $seatNumbers)
     *
     * @param int   $orderId
     * @param mixed $param2
     * @param mixed $param3
     * @param array $param4
     * @return array
     */
    public function createTicketsForOrder($orderId, $param2 = 1, $param3 = [], $param4 = []): array
    {
        if (is_numeric($param2)) {
            $quantity = (int)$param2;
            $seatNumbers = is_array($param3) ? $param3 : [];
        } else {
            $quantity = is_numeric($param3) ? (int)$param3 : 1;
            $seatNumbers = is_array($param4) ? $param4 : [];
        }

        $created = [];
        for ($i = 1; $i <= $quantity; $i++) {
            $seat = $seatNumbers[$i - 1] ?? ('A' . $i);

            if (method_exists($this->db, 'send_query')) {
                $query = "INSERT INTO " . $this->table . " (order_id, seat_number) VALUES ($1, $2)";
                $response = $this->db->send_query($query, [(int)$orderId, $seat]);
                if ($response['success']) {
                    $created[] = [
                        'order_id'    => $orderId,
                        'seat_number' => $seat
                    ];
                }
            } elseif ($this->db instanceof PDO) {
                $stmt = $this->db->prepare("INSERT INTO tickets (order_id, seat_number) VALUES (?, ?)");
                if ($stmt->execute([(int)$orderId, $seat])) {
                    $created[] = [
                        'order_id'    => $orderId,
                        'seat_number' => $seat
                    ];
                }
            }
        }
        return $created;
    }

    /**
     * Mengambil detail lengkap tiket dan pesanan untuk ditampilkan di halaman ticket.php
     * Menggabungkan data dari tabel orders, showtimes, movies, genres, studios, dan users.
     * Logika query ditempatkan di Model agar View tetap bersih (Prinsip MVC).
     *
     * @param int $orderId
     * @return array|null
     */
    public function getOrderTicketDetails($orderId): ?array
    {
        if (method_exists($this->db, 'send_query')) {
            $query = "SELECT 
                        o.order_id,
                        o.user_id,
                        o.total_price,
                        o.order_date,
                        u.username,
                        u.email,
                        st.show_date,
                        st.show_time,
                        st.price AS ticket_price,
                        m.title AS movie_title,
                        m.duration AS movie_duration,
                        m.poster AS movie_poster,
                        g.genre_name,
                        s.studio_name
                    FROM orders o
                    JOIN showtimes st ON o.showtime_id = st.showtime_id
                    JOIN movies m ON st.movie_id = m.movie_id
                    LEFT JOIN genres g ON m.genre_id = g.genre_id
                    JOIN studios s ON st.studio_id = s.studio_id
                    JOIN users u ON o.user_id = u.user_id
                    WHERE o.order_id = $1
                    LIMIT 1";

            $response = $this->db->send_query($query, [(int)$orderId]);
            if (!$response['success'] || empty($response['data'])) {
                return null;
            }

            $orderData = $response['data'][0];
        } elseif ($this->db instanceof PDO) {
            $sql = "SELECT 
                        o.order_id,
                        o.user_id,
                        o.total_price,
                        o.order_date,
                        u.username,
                        u.email,
                        st.show_date,
                        st.show_time,
                        st.price AS ticket_price,
                        m.title AS movie_title,
                        m.duration AS movie_duration,
                        m.poster AS movie_poster,
                        g.genre_name,
                        s.studio_name
                    FROM orders o
                    JOIN showtimes st ON o.showtime_id = st.showtime_id
                    JOIN movies m ON st.movie_id = m.movie_id
                    LEFT JOIN genres g ON m.genre_id = g.genre_id
                    JOIN studios s ON st.studio_id = s.studio_id
                    JOIN users u ON o.user_id = u.user_id
                    WHERE o.order_id = ?
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$orderId]);
            $orderData = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$orderData) {
                return null;
            }
        } else {
            return null;
        }

        $orderData['tickets'] = $this->getByOrderId($orderId);
        $orderData['total_tickets'] = !empty($orderData['tickets'])
            ? count($orderData['tickets'])
            : ((float)($orderData['ticket_price'] ?? 0) > 0 ? (int)round((float)$orderData['total_price'] / (float)$orderData['ticket_price']) : 1);
        $orderData['booking_code'] = self::generateBookingCode($orderData['order_id']);

        return $orderData;
    }
}
