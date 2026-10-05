<?php
// Muat dependensi sendiri supaya halaman mana pun yang memakai Order tidak error
require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/Ticket.php';

class Order extends BaseModel {

    // Batas jumlah tiket per pesanan
    private const MIN_TICKETS = 1;
    private const MAX_TICKETS = 6;

    // Properti sesuai kolom tabel orders di database/bioskop.sql
    private $order_id;     // Primary key pesanan (SERIAL)
    private $user_id;      // ID user pembeli (FK ke users)
    private $showtime_id;  // ID jadwal tayang (FK ke showtimes)
    private $total_price;  // Total harga transaksi

    public function __construct($user_id = null, $showtime_id = null, $total_price = null) {
        parent::__construct('orders', 'order_id');

        $this->user_id     = $user_id;
        $this->showtime_id = $showtime_id;
        $this->total_price = $total_price;
    }

    public function getOrderId()    { return $this->order_id; }
    public function getUserId()     { return $this->user_id; }
    public function getShowtimeId() { return $this->showtime_id; }
    public function getTotalPrice() { return $this->total_price; }

    public function setOrderId($order_id)       { $this->order_id = $order_id;       return $this; }
    public function setUserId($user_id)         { $this->user_id = $user_id;         return $this; }
    public function setShowtimeId($showtime_id) { $this->showtime_id = $showtime_id; return $this; }
    public function setTotalPrice($total_price) { $this->total_price = $total_price; return $this; }

    // ==========================================
    // PERHITUNGAN & KUOTA
    // ==========================================

    /**
     * Total harga = harga per tiket x jumlah tiket.
     */
    public static function calculateTotal($ticketPrice, $quantity) {
        return (float)$ticketPrice * (int)$quantity;
    }

    /**
     * Cek sisa kursi untuk satu jadwal tayang.
     * Sisa = kapasitas studio - jumlah baris di tabel tickets untuk jadwal itu.
     *
     * @return array available, remaining, capacity, sold, price, message
     */
    public function checkQuota($showtimeId, $requestedTickets = 1) {
        // 1. Kapasitas studio dan harga tiket dari jadwal tayang
        $query = "SELECT st.showtime_id, st.price, COALESCE(s.capacity, 50) AS capacity
                  FROM showtimes st
                  LEFT JOIN studios s ON st.studio_id = s.studio_id
                  WHERE st.showtime_id = $1
                  LIMIT 1";
        $response = $this->db->send_query($query, [(int)$showtimeId]);

        if (!$response['success'] || empty($response['data'])) {
            return [
                'available' => false,
                'remaining' => 0,
                'capacity'  => 0,
                'sold'      => 0,
                'price'     => 0,
                'message'   => 'Jadwal tayang tidak ditemukan.'
            ];
        }

        $capacity = (int)$response['data'][0]['capacity'];
        $price    = (float)$response['data'][0]['price'];

        // 2. Jumlah tiket yang sudah terjual untuk jadwal ini
        $querySold = "SELECT COUNT(t.ticket_id) AS total_sold
                      FROM tickets t
                      JOIN orders o ON t.order_id = o.order_id
                      WHERE o.showtime_id = $1";
        $responseSold = $this->db->send_query($querySold, [(int)$showtimeId]);

        $sold = 0;
        if ($responseSold['success'] && !empty($responseSold['data'])) {
            $sold = (int)$responseSold['data'][0]['total_sold'];
        }

        // 3. Sisa kursi
        $remaining   = max(0, $capacity - $sold);
        $isAvailable = ($remaining >= $requestedTickets);

        return [
            'available' => $isAvailable,
            'remaining' => $remaining,
            'capacity'  => $capacity,
            'sold'      => $sold,
            'price'     => $price,
            'message'   => $isAvailable
                ? 'Kuota tersedia.'
                : "Sisa tiket tidak mencukupi (tersisa {$remaining} tiket)."
        ];
    }

    public function getShowtimeInfo($showtimeId) {
        $query = "SELECT
                    st.showtime_id,
                    st.price,
                    st.show_date,
                    st.show_time,
                    m.movie_id,
                    m.title AS movie_title,
                    m.poster AS movie_poster,
                    m.duration AS movie_duration,
                    g.genre_name,
                    s.studio_name,
                    COALESCE(s.capacity, 50) AS studio_capacity
                  FROM showtimes st
                  LEFT JOIN movies m ON st.movie_id = m.movie_id
                  LEFT JOIN genres g ON m.genre_id = g.genre_id
                  LEFT JOIN studios s ON st.studio_id = s.studio_id
                  WHERE st.showtime_id = $1
                  LIMIT 1";

        $response = $this->db->send_query($query, [(int)$showtimeId]);
        return ($response['success'] && !empty($response['data'])) ? $response['data'][0] : null;
    }

    public function createOrderWithTickets($userId, $showtimeId, $quantity, array $seatNumbers = []) {
        if ($quantity < self::MIN_TICKETS || $quantity > self::MAX_TICKETS) {
            throw new Exception(
                "Jumlah tiket yang dapat dibeli adalah " . self::MIN_TICKETS . " sampai " . self::MAX_TICKETS . " tiket."
            );
        }

        $quota = $this->checkQuota($showtimeId, $quantity);
        if (!$quota['available']) {
            throw new Exception("Maaf, kuota kursi tidak mencukupi. Sisa: {$quota['remaining']} tiket.");
        }

        $totalPrice = self::calculateTotal($quota['price'], $quantity);

        $this->db->mulai_transaksi();

        try {
            $responseOrder = $this->db->send_query(
                "INSERT INTO orders (user_id, showtime_id, total_price)
                 VALUES ($1, $2, $3)
                 RETURNING order_id",
                [(int)$userId, (int)$showtimeId, $totalPrice]
            );

            if (!$responseOrder['success'] || empty($responseOrder['data'])) {
                throw new Exception("Gagal menyimpan pesanan ke database.");
            }

            $orderId     = (int)$responseOrder['data'][0]['order_id'];
            $bookingCode = Ticket::generateBookingCode($orderId);

            $ticketModel    = new Ticket($this->db);
            $createdTickets = $ticketModel->createTicketsForOrder($orderId, $quantity, $seatNumbers);

            if (count($createdTickets) !== (int)$quantity) {
                throw new Exception("Tiket gagal disimpan ke database.");
            }

            $this->db->commit();

            $this->order_id    = $orderId;
            $this->user_id     = $userId;
            $this->showtime_id = $showtimeId;
            $this->total_price = $totalPrice;

            return [
                'success'      => true,
                'order_id'     => $orderId,
                'booking_code' => $bookingCode,
                'total_price'  => $totalPrice,
                'tickets'      => $createdTickets
            ];

        } catch (Exception $e) {
            // D. Ada yang gagal, batalkan semua perubahan
            $this->db->rollback();
            throw new Exception("Gagal memproses pesanan: " . $e->getMessage());
        }
    }

    public function getOrderDetail($orderId) {
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
                    m.poster AS movie_poster,
                    m.duration AS movie_duration,
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

        $order = $response['data'][0];
        $order['booking_code'] = Ticket::generateBookingCode($order['order_id']);

        $tickets = (new Ticket($this->db))->getByOrderId($orderId);
        $order['tickets'] = $tickets;

        // Jumlah tiket: dari tabel tickets, atau perkiraan total / harga satuan kalau kosong
        if (!empty($tickets)) {
            $order['total_tickets'] = count($tickets);
        } elseif ((float)($order['ticket_price'] ?? 0) > 0) {
            $order['total_tickets'] = (int)round((float)$order['total_price'] / (float)$order['ticket_price']);
        } else {
            $order['total_tickets'] = 1;
        }

        return $order;
    }

    public function getByUserId($userId) {
        $query = "SELECT
                    o.order_id,
                    o.total_price,
                    o.order_date,
                    m.title AS movie_title,
                    m.poster AS movie_poster,
                    s.studio_name,
                    st.show_date,
                    st.show_time
                  FROM orders o
                  LEFT JOIN showtimes st ON o.showtime_id = st.showtime_id
                  LEFT JOIN movies m ON st.movie_id = m.movie_id
                  LEFT JOIN studios s ON st.studio_id = s.studio_id
                  WHERE o.user_id = $1
                  ORDER BY o.order_id DESC";

        $response = $this->db->send_query($query, [(int)$userId]);
        return ($response['success'] && !empty($response['data'])) ? $response['data'] : [];
    }

    public function save(): bool {
        if ($this->order_id) {
            return $this->update($this->order_id, [
                'total_price' => $this->total_price
            ]);
        }

        return $this->create([
            'user_id'     => $this->user_id,
            'showtime_id' => $this->showtime_id,
            'total_price' => $this->total_price
        ]);
    }
}