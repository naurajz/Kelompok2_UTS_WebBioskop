<?php
// Muat dependensi sendiri supaya halaman mana pun yang memakai Order tidak error
require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/Ticket.php';

class Order extends BaseModel {

    // Properti sesuai kolom tabel orders di database/bioskop.sql
    private $order_id;     // Primary key pesanan (SERIAL)
    private $user_id;      // ID user pembeli (FK ke users)
    private $showtime_id;  // ID jadwal tayang (FK ke showtimes)
    private $total_price;  // Total harga transaksi
    private $order_date;   // Tanggal pesanan (DEFAULT CURRENT_TIMESTAMP)

    /**
     * Constructor Order
     * Memanggil BaseModel dengan nama tabel 'orders' dan primary key 'order_id'.
     *
     * @param int|null    $user_id      ID pembeli
     * @param int|null    $showtime_id  ID jadwal tayang
     * @param float|null  $total_price  Total harga
     */
    public function __construct($user_id = null, $showtime_id = null, $total_price = null) {
        parent::__construct('orders', 'order_id');

        $this->user_id     = $user_id;
        $this->showtime_id = $showtime_id;
        $this->total_price = $total_price;
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

    public function getTotalPrice() {
        return $this->total_price;
    }

    public function setTotalPrice($total_price) {
        $this->total_price = $total_price;
        return $this;
    }

    // ==========================================
    // LOGIKA PERHITUNGAN & VALIDASI KUOTA
    // ==========================================

    /**
     * Menghitung total harga pesanan (Jobdesk Trx-01)
     * Contoh: 3 tiket x Rp 50.000 = Rp 150.000
     *
     * @param float|int $ticketPrice  Harga per tiket
     * @param int       $quantity     Jumlah tiket
     * @return float
     */
    public static function calculateTotal($ticketPrice, $quantity) {
        return (float)$ticketPrice * (int)$quantity;
    }

    /**
     * Memeriksa sisa kuota kursi yang masih tersedia untuk jadwal tayang tertentu (Jobdesk Trx-01)
     * Menghitung kapasitas studio dikurangi tiket dari order yang sudah ada.
     *
     * @param int $showtimeId        ID jadwal tayang
     * @param int $requestedTickets  Jumlah tiket yang ingin dibeli
     * @return array Status ketersediaan kuota beserta rincian angka
     */
    public function checkQuota($showtimeId, $requestedTickets = 1) {
        // 1. Ambil kapasitas studio dan harga tiket dari showtime
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

        $showtime = $response['data'][0];
        $capacity = (int)$showtime['capacity'];
        $price    = (float)$showtime['price'];

        // 2. Hitung jumlah tiket yang sudah terjual: COUNT ticket rows per showtime
        $querySold = "SELECT COUNT(t.ticket_id) AS total_sold
                      FROM tickets t
                      JOIN orders o ON t.order_id = o.order_id
                      WHERE o.showtime_id = $1";
        $responseSold = $this->db->send_query($querySold, [(int)$showtimeId]);
        $sold = 0;
        if ($responseSold['success'] && !empty($responseSold['data'])) {
            $sold = (int)$responseSold['data'][0]['total_sold'];
        }

        // 3. Hitung sisa kuota kursi yang masih kosong
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

    /**
     * Mengambil info jadwal tayang lengkap untuk halaman checkout (Trx-02)
     * Data meliputi film, genre, studio, harga, dan kapasitas kursi.
     *
     * @param int $showtimeId ID jadwal tayang
     * @return array|null
     */
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

    // ==========================================
    // TRANSAKSI DATABASE (ATOMIK: ORDERS + TICKETS)
    // ==========================================

    /**
     * Menyimpan data pesanan dan tiket dalam satu transaksi database (Jobdesk Trx-01)
     * Menggunakan mulai_transaksi(), commit(), rollback() dari DBConnection.
     *
     * @param int   $userId       ID akun pembeli yang login
     * @param int   $showtimeId   ID jadwal tayang yang dipilih
     * @param int   $quantity     Jumlah tiket yang dibeli (1–6 tiket)
     * @param array $seatNumbers  Array nomor kursi pilihan (opsional)
     * @return array Hasil transaksi: order_id, booking_code, total_price, tickets
     * @throws Exception jika kuota habis atau database gagal
     */
    public function createOrderWithTickets($userId, $showtimeId, $quantity, array $seatNumbers = []) {
        // Validasi batasan jumlah tiket (harus 1 sampai 6)
        if ($quantity < 1 || $quantity > 6) {
            throw new Exception("Jumlah tiket yang dapat dibeli adalah 1 sampai 6 tiket.");
        }

        // 1. Cek sisa kuota kursi terlebih dahulu
        $quota = $this->checkQuota($showtimeId, $quantity);
        if (!$quota['available']) {
            throw new Exception("Maaf, kuota kursi tidak mencukupi. Sisa: {$quota['remaining']} tiket.");
        }

        // 2. Hitung total harga (Harga Satuan x Jumlah Tiket)
        $totalPrice = self::calculateTotal($quota['price'], $quantity);

        // ==========================================
        // MULAI DATABASE TRANSACTION (ATOMISITAS)
        // ==========================================
        $this->db->mulai_transaksi();

        try {
            // A. Simpan data transaksi ke tabel 'orders'
            // Kolom sesuai bioskop.sql: order_id(serial), user_id, showtime_id, total_price, order_date
            $queryOrder = "INSERT INTO orders (user_id, showtime_id, total_price)
                           VALUES ($1, $2, $3)
                           RETURNING order_id";
            $responseOrder = $this->db->send_query($queryOrder, [
                (int)$userId,
                (int)$showtimeId,
                $totalPrice
            ]);

            if (!$responseOrder['success'] || empty($responseOrder['data'])) {
                throw new Exception("Gagal menyimpan pesanan ke database.");
            }

            // Ambil ID pesanan yang baru di-generate oleh database
            $orderId = (int)$responseOrder['data'][0]['order_id'];

            // Generate kode booking berbasis order_id (Ticket-03)
            $bookingCode = Ticket::generateBookingCode($orderId);

            // B. Simpan lembar tiket ke tabel 'tickets' (Ticket-01)
<<<<<<< HEAD
            // PENTING: kirim $this->db supaya Ticket memakai koneksi yang SAMA dengan
            // transaksi ini. Kalau tidak, Ticket membuka koneksi baru (FORCE_NEW) yang
            // tidak bisa melihat order yang belum di-commit, dan insert tiket gagal (FK).
=======
>>>>>>> 3cf05609b34152010c68e25ef0da3ee168fadbeb
            $ticketModel = new Ticket($this->db);
            $createdTickets = $ticketModel->createTicketsForOrder($orderId, $quantity, $seatNumbers);

            // Pastikan semua tiket benar-benar tersimpan, kalau tidak batalkan seluruh transaksi
            if (count($createdTickets) !== (int)$quantity) {
                throw new Exception("Tiket gagal disimpan ke database.");
            }

            // C. Jika semua berhasil, lakukan COMMIT
            $this->db->commit();

            // Update properti objek
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
            // D. Jika terjadi error, lakukan ROLLBACK untuk membatalkan semua perubahan
            $this->db->rollback();
            throw new Exception("Gagal memproses pesanan: " . $e->getMessage());
        }
    }

    /**
     * Mengambil rincian data satu pesanan (JOIN dengan showtime, movie, studio, user)
     * Digunakan oleh halaman konfirmasi pesanan (confirm.php - Trx-03)
     *
     * @param int $orderId ID pesanan
     * @return array|null Rincian pesanan
     */
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

        // Tambahkan kode booking yang digenerate dari order_id (Ticket-03)
        $order['booking_code'] = Ticket::generateBookingCode($order['order_id']);

        // Hitung jumlah tiket dari tabel tickets
        $ticketModel = new Ticket();
        $tickets = $ticketModel->getByOrderId($orderId);
        $order['tickets']       = $tickets;
        $order['total_tickets'] = !empty($tickets) ? count($tickets) : (
            (float)($order['ticket_price'] ?? 0) > 0
            ? (int)round((float)$order['total_price'] / (float)$order['ticket_price'])
            : 1
        );

        return $order;
    }

    /**
     * Mengambil seluruh daftar pesanan milik akun user tertentu
     * Digunakan oleh halaman riwayat pesanan (history.php - User-03)
     *
     * @param int $userId ID pengguna
     * @return array Daftar riwayat pesanan
     */
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

    /**
     * Implementasi method abstract save() dari Crudable (via BaseModel)
     * Menyimpan atau memperbarui data pesanan.
     *
     * @return bool
     */
    public function save(): bool {
        if ($this->order_id) {
            return $this->update($this->order_id, [
                'total_price' => $this->total_price
            ]);
        } else {
            return $this->create([
                'user_id'     => $this->user_id,
                'showtime_id' => $this->showtime_id,
                'total_price' => $this->total_price
            ]);
        }
    }
}