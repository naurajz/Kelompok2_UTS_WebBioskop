<?php
/**
 * Core-04 Validasi & Error Handling (versi PostgreSQL / pg_*)
 *
 * $v = new Validator($_POST);
 * $v->required('email','Email')->email('email')->unique('email','Email',$conn,'users','email');
 * $v->ticaketQuantity('jumlah');
 * if ($v->fails()) { Validator::flash($v->errors()); header('Location: register.php'); exit; }
 * Di halaman form:  <?= Validator::renderFlash() ?>
 */
class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data) { $this->data = $data; }

    private function value(string $f): string { return trim((string)($this->data[$f] ?? '')); }

    private function fail(string $f, string $msg): void
    {
        if (!isset($this->errors[$f])) $this->errors[$f] = $msg;
    }

    public function required(string $f, string $label): self
    {
        if ($this->value($f) === '') $this->fail($f, "$label wajib diisi.");
        return $this;
    }

    public function minLength(string $f, string $label, int $min): self
    {
        $v = $this->value($f);
        if ($v !== '' && mb_strlen($v) < $min) $this->fail($f, "$label minimal $min karakter.");
        return $this;
    }

    public function maxLength(string $f, string $label, int $max): self
    {
        if (mb_strlen($this->value($f)) > $max) $this->fail($f, "$label maksimal $max karakter.");
        return $this;
    }

    public function email(string $f, string $label = 'Email'): self
    {
        $v = $this->value($f);
        if ($v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) $this->fail($f, "Format $label tidak valid.");
        return $this;
    }

    public function integerBetween(string $f, string $label, int $min, int $max): self
    {
        $v = $this->value($f);
        if ($v === '') return $this;
        if (filter_var($v, FILTER_VALIDATE_INT) === false) {
            $this->fail($f, "$label harus berupa angka bulat.");
        } elseif ((int)$v < $min || (int)$v > $max) {
            $this->fail($f, "$label harus antara $min sampai $max.");
        }
        return $this;
    }

    /** Jumlah tiket 1-6 (0 akan ditolak) */
    public function ticketQuantity(string $f = 'jumlah'): self
    {
        return $this->required($f, 'Jumlah tiket')->integerBetween($f, 'Jumlah tiket', 1, 6);
    }

    public function numeric(string $f, string $label): self
    {
        $v = $this->value($f);
        if ($v !== '' && !is_numeric($v)) $this->fail($f, "$label harus berupa angka.");
        return $this;
    }

    public function matches(string $f, string $other, string $label): self
    {
        if ($this->value($f) !== $this->value($other)) $this->fail($f, "$label tidak sama.");
        return $this;
    }

    /** Nilai belum boleh ada di tabel. $db = objek DBConnection (pakai send_query) */
    public function unique(string $f, string $label, $db, string $table, string $column): self
    {
        $v = $this->value($f);
        if ($v === '') return $this;
        $r = $db->send_query("SELECT 1 FROM $table WHERE $column = $1 LIMIT 1", [$v]);
        if (empty($r['success'])) {
            $this->fail($f, 'Terjadi kesalahan pada database. Coba lagi nanti.');
        } elseif (!empty($r['data'])) {
            $this->fail($f, "$label sudah terdaftar.");
        }
        return $this;
    }

    public function fails(): bool { return !empty($this->errors); }
    public function errors(): array { return $this->errors; }
    public function first(): ?string { return $this->errors ? reset($this->errors) : null; }

    public static function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

    public static function flash($errors): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['flash_errors'] = (array)$errors;
    }

    public static function renderFlash(): string
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['flash_errors'])) return '';
        $html = '<div class="alert-error" role="alert" style="background:#fdecea;color:#7a1f16;padding:10px 14px;border-radius:6px;margin:12px 0"><ul style="margin:0;padding-left:18px">';
        foreach ($_SESSION['flash_errors'] as $m) $html .= '<li>' . self::e($m) . '</li>';
        unset($_SESSION['flash_errors']);
        return $html . '</ul></div>';
    }

    /** Jalankan query lewat DBConnection. Gagal -> pesan rapi di flash, return array kosong. */
    public static function rows($db, string $sql, array $params = [], string $pesan = 'Terjadi kesalahan. Silakan coba lagi.'): array
    {
        $r = $db->send_query($sql, $params);
        if (empty($r['success'])) {
            self::flash($pesan);
            return [];
        }
        return $r['data'] ?? [];
    }
}
