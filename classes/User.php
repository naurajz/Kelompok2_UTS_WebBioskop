<?php
class User extends BaseModel
{
    private int $user_id;
    private string $username;
    private string $email;
    private array $role = [];

    public function __construct(int $user_id, string $username, string $email)
    {
        $this->user_id = $user_id;
        $this->username = $username;
        $this->email = strtolower(trim($email));
    }

    public function get_user(): array
    {
        $role_aktif = $this->get_role_aktif();
        return [
            'user_id'  => $this->user_id,
            'username' => $this->username,
            'email'    => $this->email,
            'role'     => $role_aktif === null ? '-' : $role_aktif->get_data()['nama_role'],
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
        return $this->username . " <" . $this->email . ">";
    }
}
