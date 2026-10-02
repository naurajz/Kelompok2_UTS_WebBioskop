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
 * Mengimplementasikan konsep OOP dasar sesuai Modul 4:
 * - Inheritance dari BaseModel (menggunakan koneksi DBConnection PostgreSQL)
 * - Encapsulation (properti private dengan getter dan setter)
 * - Static method untuk generate kode booking unik (Ticket-03)
 * - Magic method __toString()
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
     * @param int|null    $order_id    ID pesanan (relasi ke tabel orders)
     * @param string|null $seat_number Nomor kursi (contoh: 'A1')
     * @param int|null    $ticket_id   Primary key tiket (jika update)
     */
    public function __construct($order_id = null, $seat_number = null, $ticket_id = null)
    {
        parent::__construct('tickets', 'ticket_id');

        $this->order_id = $order_id;
        $this->seat_number = $seat_number;
        $this->ticket_id = $ticket_id;
    }

    // ==========================================
    // GETTER & SETTER (Encapsulation - Modul 4)
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
    // STATIC METHOD (Modul 4)
    // ==========================================

    /**
     * Generate kode booking pesanan (Ticket-03)
     * Format resmi berbasis order_id: BK + 5 digit angka (contoh: BK00101)
     *
     * @param int|string $orderId
     * @return string
     */
    public static function generateBookingCode($orderId): string
    {
        return 'BK' . str_pad((string)$orderId, 5, '0', STR_PAD_LEFT);
    }

    // ==========================================
    // MAGIC METHOD (Modul 4)
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
     * Menyimpan data tiket ke database PostgreSQL
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
        $response = $this->db->send_query($query, [(int)$orderId]);
        return ($response['success'] && !empty($response['data'])) ? $response['data'] : [];
    }

    /**
     * Menyimpan daftar tiket sekaligus untuk pesanan yang baru dibuat (Ticket-01)
     *
     * @param int   $orderId
     * @param int   $quantity
     * @param array $seatNumbers
     * @return array
     */
    public function createTicketsForOrder($orderId, int $quantity, array $seatNumbers = []): array
    {
        $created = [];
        for ($i = 1; $i <= $quantity; $i++) {
            $seat = $seatNumbers[$i - 1] ?? ('A' . $i);
            $query = "INSERT INTO " . $this->table . " (order_id, seat_number) VALUES ($1, $2)";
            $response = $this->db->send_query($query, [(int)$orderId, $seat]);
            if ($response['success']) {
                $created[] = [
                    'order_id'    => $orderId,
                    'seat_number' => $seat
                ];
            }
        }
        return $created;
    }

    /**
     * Mengambil detail lengkap tiket dan pesanan untuk ditampilkan di halaman ticket.php
     * Menggabungkan data dari tabel orders, showtimes, movies, genres, studios, dan users.
     * Logika query ditempatkan di Model agar View tetap bersih (Prinsip MVC Modul 2).
     *
     * @param int $orderId
     * @return array|null
     */
    public function getOrderTicketDetails($orderId): ?array
    {
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
        $orderData['tickets'] = $this->getByOrderId($orderId);
        $orderData['total_tickets'] = count($orderData['tickets']);
        $orderData['booking_code'] = self::generateBookingCode($orderData['order_id']);

        return $orderData;
    }
}
