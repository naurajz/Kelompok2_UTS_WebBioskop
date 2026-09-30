<?php
/**
 * File     : classes/Ticket.php
 * Card     : Ticket-01 Ticket Backend + Ticket-03 Kode Booking
 * Tugas    : Class Ticket extends BaseModel. Isi: buat tiket berkode unik per pesanan, generate kode booking.
 * PIC      : Shafrie Alvito Wimala Rasendrya
 * NIM      : 434251142
 * Deadline : 3 Oktober 2026
 */

// Panggil file class induk (BaseModel.php) karena class Ticket adalah turunan (anak) dari BaseModel
require_once __DIR__ . '/BaseModel.php';

/**
 * Class Ticket
 * Mengelola data tiket bioskop, generate kode booking pesanan, dan kode tiket individual.
 */
class Ticket extends BaseModel {
    // Nama tabel database yang dihubungkan ke class ini
    protected $table = 'tickets';

    // Properti-properti yang mewakili kolom di tabel MySQL 'tickets'
    // Dibikin 'protected' (Encapsulation) supaya datanya aman dan nggak bisa sembarangan diubah dari luar
    protected $id;          // ID unik primary key tiket
    protected $order_id;     // ID pesanan yang punya tiket ini (relasi ke tabel orders)
    protected $ticket_code;  // Kode unik lembar tiket, contoh: BK7F3A2-1
    protected $seat_number;  // Nomor kursi bioskop yang dipilih, contoh: A1 atau Kursi 1
    protected $created_at;   // Tanggal & jam kapan tiket ini dibuat

    /**
     * Constructor Ticket
     * Dijalankan otomatis saat kita manggil: new Ticket($db, ...)
     * 
     * @param PDO|null $db Instance koneksi database PDO
     * @param int|null $order_id ID pesanan (foreign key ke orders.id)
     * @param string|null $ticket_code Kode unik tiket (misal: BK7F3A2-1)
     * @param string|null $seat_number Nomor kursi (opsional, misal: A1)
     */
    public function __construct($db = null, $order_id = null, $ticket_code = null, $seat_number = null) {
        // Panggil constructor milik class induk (BaseModel) biar koneksi $db tersimpan rapi di parent
        parent::__construct($db);

        // Masukkan data awal ke properti objek saat pertama kali dibuat
        $this->order_id = $order_id;
        $this->ticket_code = $ticket_code;
        $this->seat_number = $seat_number;
    }

    // ==============================================================================
    // GETTER & SETTER (Penerapan Konsep Encapsulation OOP)
    // ==============================================================================

    // Mengambil ID pesanan (order_id)
    public function getOrderId() {
        return $this->order_id;
    }

    // Mengisi atau mengubah ID pesanan (order_id)
    public function setOrderId($order_id) {
        $this->order_id = $order_id;
        return $this; // Return $this biar bisa chain method (pemanggilan berantai)
    }

    // Mengambil kode unik tiket (ticket_code)
    public function getTicketCode() {
        return $this->ticket_code;
    }

    // Mengisi atau mengubah kode unik tiket (ticket_code)
    public function setTicketCode($ticket_code) {
        $this->ticket_code = $ticket_code;
        return $this;
    }

    // Mengambil nomor kursi tiket (seat_number)
    public function getSeatNumber() {
        return $this->seat_number;
    }

    // Mengisi atau mengubah nomor kursi tiket (seat_number)
    public function setSeatNumber($seat_number) {
        $this->seat_number = $seat_number;
        return $this;
    }

    // Mengambil waktu pembuatan tiket (created_at)
    public function getCreatedAt() {
        return $this->created_at;
    }

    // Mengisi atau mengubah waktu pembuatan tiket (created_at)
    public function setCreatedAt($created_at) {
        $this->created_at = $created_at;
        return $this;
    }

    // ==============================================================================
    // JOBDESK TICKET-03: GENERATOR KODE BOOKING UNIK
    // ==============================================================================

    /**
     * Membuat kode booking unik untuk transaksi pesanan (Ticket-03)
     * Format contoh: BK7F3A2 (Awalan 'BK' + 6 huruf/angka unik acak)
     * 
     * @param string $prefix Awalan kode booking (default: 'BK')
     * @return string Kode booking unik
     */
    public static function generateBookingCode($prefix = 'BK') {
        // 1. uniqid() menghasilkan ID unik berdasarkan waktu sistem saat ini
        // 2. substr(..., -6) memotong dan hanya mengambil 6 karakter paling belakang biar kodenya ringkas
        // 3. strtoupper(...) mengubah teks jadi huruf kapital semua biar terlihat resmi
        // 4. Digabung dengan awalan $prefix ('BK') sehingga menghasilkan format seperti: BK7F3A2
        return $prefix . strtoupper(substr(uniqid(), -6));
    }

    /**
     * Membuat kode unik per lembar tiket berdasarkan kode booking induknya (Ticket-01 & Ticket-03)
     * Format contoh: Tiket ke-1 dari pesanan BK7F3A2 menjadi BK7F3A2-1, tiket ke-2 jadi BK7F3A2-2
     * 
     * @param string $bookingCode Kode booking induk transaksi
     * @param int|string $seatOrIndex Nomor urutan tiket (1, 2, dst) atau nomor kursi
     * @return string Kode tiket unik
     */
    public static function generateTicketCode($bookingCode, $seatOrIndex = 1) {
        // trim() buat bersihkan spasi yang nggak sengaja nempel
        // strtoupper() memastikan kode booking selalu berhuruf besar
        // Lalu disambungkan dengan tanda strip '-' dan nomor tiketnya
        return strtoupper(trim($bookingCode)) . '-' . $seatOrIndex;
    }

    // ==============================================================================
    // JOBDESK TICKET-01: TICKET BACKEND & OPERASI DATABASE (CRUD)
    // ==============================================================================

    /**
     * Menyimpan data tiket ke database MySQL (Wajib ada karena turunan abstract save() dari BaseModel)
     * Kalau tiket sudah punya ID, otomatis UPDATE. Kalau belum punya ID, otomatis INSERT baru.
     * 
     * @return bool True kalau simpan berhasil, False kalau gagal
     */
    public function save() {
        // Cek dulu apakah koneksi database ada, kalau nggak ada langsung stop
        if (!$this->db) {
            return false;
        }

        // KONDISI 1: Kalau properti $this->id sudah ada isinya, berarti data ini mau DI-UPDATE
        if ($this->id) {
            // Tulis query SQL UPDATE dengan prepared statement (:nama_param) biar kebal dari SQL Injection
            $sql = "UPDATE {$this->table} 
                    SET order_id = :order_id, 
                        ticket_code = :ticket_code, 
                        seat_number = :seat_number 
                    WHERE id = :id";
            
            // Siapkan statement query ke database
            $stmt = $this->db->prepare($sql);

            // Eksekusi query dengan mengirim data asli yang aman
            return $stmt->execute([
                ':order_id'    => $this->order_id,
                ':ticket_code' => $this->ticket_code,
                ':seat_number' => $this->seat_number,
                ':id'          => $this->id
            ]);
        } 
        // KONDISI 2: Kalau properti $this->id masih kosong (null), berarti ini TIKET BARU yang mau DI-INSERT
        else {
            // Tulis query SQL INSERT INTO untuk memasukkan baris baru ke tabel tickets
            // Fungsi NOW() di MySQL otomatis mencatat tanggal dan jam transaksi saat ini
            $sql = "INSERT INTO {$this->table} (order_id, ticket_code, seat_number, created_at) 
                    VALUES (:order_id, :ticket_code, :seat_number, NOW())";
            
            // Siapkan query dengan PDO prepare
            $stmt = $this->db->prepare($sql);

            // Eksekusi query INSERT
            $success = $stmt->execute([
                ':order_id'    => $this->order_id,
                ':ticket_code' => $this->ticket_code,
                ':seat_number' => $this->seat_number
            ]);

            // Kalau proses INSERT berhasil, kita ambil nomor ID auto-increment yang baru dibuat oleh MySQL
            // lalu kita simpan ke properti $this->id milik objek ini
            if ($success) {
                $this->id = (int)$this->db->lastInsertId();
            }

            return $success;
        }
    }

    /**
     * Mengambil semua lembar tiket yang dimiliki oleh suatu transaksi pesanan (berdasarkan order_id)
     * Contoh: Pesanan dengan order_id = 5 punya 3 tiket, maka fungsi ini balikin array 3 tiket itu
     * 
     * @param int $orderId ID pesanan yang mau dicari tiketnya
     * @return array Daftar baris tiket dalam bentuk array
     */
    public function getByOrderId($orderId) {
        // Cek koneksi database
        if (!$this->db) {
            return [];
        }

        // Query SQL: Ambil semua tiket yang order_id nya cocok, urutkan dari tiket pertama (ASC)
        $sql = "SELECT * FROM {$this->table} WHERE order_id = :order_id ORDER BY id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':order_id' => $orderId]);

        // fetchAll(PDO::FETCH_ASSOC) mengembalikan semua baris data sebagai array asosiatif
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Membuat dan menyimpan daftar tiket berkode unik secara borongan (Jobdesk Ticket-01)
     * Fungsi ini dipanggil pas proses checkout di Order.php selesai, misal user beli 3 tiket sekaligus
     * 
     * @param int $orderId ID pesanan baru yang baru saja di-insert
     * @param string $bookingCode Kode booking transaksi (contoh: BK7F3A2)
     * @param int $quantity Berapa lembar tiket yang dibeli (contoh: 3)
     * @param array $seatNumbers Daftar nomor kursi (opsional, contoh: ['A1', 'A2', 'A3'])
     * @return array Daftar tiket yang berhasil dibuat dan disimpan
     */
    public function createTicketsForOrder($orderId, $bookingCode, $quantity, array $seatNumbers = []) {
        // Kalau database tidak ada atau jumlah beli kurang dari 1, batalkan
        if (!$this->db || $quantity <= 0) {
            return [];
        }

        // Wadah array untuk menampung tiket-tiket yang berhasil dibikin
        $createdTickets = [];

        // Lakukan looping sebanyak jumlah tiket yang dibeli ($quantity)
        for ($i = 1; $i <= $quantity; $i++) {
            // 1. Bikin kode unik lembar tiket dengan memanggil method static tadi: BK7F3A2-1, BK7F3A2-2, dst.
            $ticketCode = self::generateTicketCode($bookingCode, $i);
            
            // 2. Tentukan nomor kursi: pakai kursi dari pilihan user kalau ada, kalau nggak ada kasih nama otomatis 'Kursi 1', dst.
            $seatNumber = isset($seatNumbers[$i - 1]) && !empty($seatNumbers[$i - 1]) 
                ? $seatNumbers[$i - 1] 
                : 'Kursi ' . $i;

            // 3. Simpan tiket lembar ke-$i ini ke tabel tickets di MySQL
            $sql = "INSERT INTO {$this->table} (order_id, ticket_code, seat_number, created_at) 
                    VALUES (:order_id, :ticket_code, :seat_number, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':order_id'    => $orderId,
                ':ticket_code' => $ticketCode,
                ':seat_number' => $seatNumber
            ]);

            // 4. Masukkan ringkasan tiket yang baru disimpan ke dalam array tampungan
            $createdTickets[] = [
                'id'          => (int)$this->db->lastInsertId(),
                'order_id'    => $orderId,
                'ticket_code' => $ticketCode,
                'seat_number' => $seatNumber
            ];
        }

        // Kembalikan seluruh daftar tiket yang sudah tersimpan rapi di database
        return $createdTickets;
    }

    /**
     * Mengambil detail super lengkap pesanan dan tiket untuk ditampilkan di layar e-ticket (ticket.php)
     * Menggunakan teknik SQL JOIN untuk menggabungkan 5 tabel sekaligus dalam 1 kali query:
     * - orders (data pesanan)
     * - showtimes (jadwal tayang bioskop)
     * - movies (judul & poster film)
     * - genres (kategori genre film)
     * - studios (nama studio bioskop)
     * - users (nama customer si pembeli)
     * 
     * @param int|string $identifier Bisa berupa order_id angka (contoh: 5) atau kode booking teks (contoh: 'BK7F3A2')
     * @return array|null Data gabungan lengkap pesanan + film + daftar tiket, atau null jika tidak ketemu
     */
    public function getOrderTicketDetails($identifier) {
        // Pastikan koneksi database ada
        if (!$this->db) {
            return null;
        }

        // Susun query JOIN multi-tabel:
        // Kita beri alias (o, st, m, g, s, u) biar penulisan query lebih rapi dan gampang dibaca
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

        // Siapkan prepared statement
        $stmt = $this->db->prepare($sql);

        // Eksekusi query dengan parameter identifier (bisa dicari pakai ID angka ataupun kode booking teks)
        $stmt->execute([
            ':id_or_code'     => is_numeric($identifier) ? (int)$identifier : 0,
            ':id_or_code_str' => (string)$identifier
        ]);

        // Ambil data satu pesanan tersebut
        $orderData = $stmt->fetch(PDO::FETCH_ASSOC);

        // Kalau pesanan dengan ID/kode tersebut tidak ada di database, balikan null
        if (!$orderData) {
            return null;
        }

        // Kalau pesanannya ketemu, panggil fungsi getByOrderId() untuk mengambil semua lembar tiket anaknya,
        // lalu kita tempelkan ke dalam array pesanan dengan key 'tickets'
        $orderData['tickets'] = $this->getByOrderId($orderData['order_id']);

        // Kembalikan seluruh paket data lengkap siap pakai ke halaman UI (ticket.php)
        return $orderData;
    }
}
