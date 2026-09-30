<?php

/**
 * File     : classes/User.php
 * Card     : Auth-01 Admin Login
 * Tugas    : Class User extends BaseModel. Isi: constructor, getter/setter, login($email, $password), register(). Role: admin / customer.
 * PIC      : (isi nama)
 * Deadline : 1 Oktober 2026
 */

// TODO: tulis kode di sini. Beri comment penjelasan di tiap bagian penting.
class User
{
    private int $iduser;
    private string $nama;
    private string $email;
    private array $role = [];

    public function __construct(int $iduser, string $nama, string $email)
    {
        $this->iduser = $iduser;
        $this->nama = $nama;
        $this->email = strtolower(trim($email));
    }

    public function get_user(): array
    {
        $role_aktif = $this->get_role_aktif();
        return [
            'iduser' => $this->iduser,
            'nama'   => $this->nama,
            'email'  => $this->email,
            'role'   => $role_aktif === null ? '-' : $role_aktif->get_data()['nama_role'],
        ];
    }

    public function set_role(Role $role): void
    {
        if ($role->get_status() === true) {
            foreach ($this->role as $r) {
                $r->set_status(false);
            }
        }
        $this->role[] = $role;
    }

    public function get_role_aktif(): ?Role
    {
        foreach ($this->role as $r) {
            if ($r->get_status() === true) {
                return $r;
            }
        }
        return null;
    }

    // Penambahan Method dari Tugas 3
    public function hapus_role(int $idrole): void
    {
        foreach ($this->role as $key => $r) {
            if ($r->get_data()['idrole'] === $idrole) {
                unset($this->role[$key]);
            }
        }
        $this->role = array_values($this->role); // Re-index array
    }

    public function set_role_aktif(int $idrole): void
    {
        foreach ($this->role as $r) {
            if ($r->get_data()['idrole'] === $idrole) {
                $r->set_status(true);
            } else {
                $r->set_status(false);
            }
        }
    }

    public function __toString(): string
    {
        return $this->nama . " <" . $this->email . ">";
    }
}
