<?php
require_once __DIR__ . '/BaseModel.php';

class Ticket extends BaseModel
{
    private $ticket_id;
    private $order_id;
    private $seat_number;

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

    public static function generateBookingCode($param = null): string
    {
        if (is_numeric($param) && (int)$param > 0) {
            return 'BK' . str_pad((string)(int)$param, 5, '0', STR_PAD_LEFT);
        }

        $prefix = (is_string($param) && !empty($param) && $param !== 'BK') ? $param : 'BK';
        return $prefix . strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 5));
    }

    public function __toString(): string
    {
        return "Ticket #{$this->ticket_id} (Order: {$this->order_id}, Kursi: {$this->seat_number})";
    }

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