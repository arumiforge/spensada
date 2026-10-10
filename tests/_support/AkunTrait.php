<?php

namespace Tests\Support;

use App\Services\Akun\Kredensial;
use App\Services\Sistem\Jam;

/**
 * Accounts and logged-in sessions for database and feature tests.
 * Use together with DatabaseTestTrait ($refresh = true, $namespace = 'App').
 */
trait AkunTrait
{
    /** Password of every account made by buatAkun(), unless given. */
    protected string $passwordUji = 'kopi-pagi-dawe';

    /**
     * Inserts an account and its stored roles; returns the `akun` row.
     *
     * @param array<string, mixed> $data  Columns that differ from an active staff account
     * @param list<string>         $roles akun_role roles, e.g. ['admin']
     *
     * @return array<string, mixed>
     */
    protected function buatAkun(array $data = [], array $roles = []): array
    {
        static $hash = null;
        $hash ??= (new Kredensial())->hash($this->passwordUji);
        $now = (new Jam())->now()->toDateTimeString();

        $row = $data + [
            'jenis'                => 'staf',
            'username'             => 'staf' . bin2hex(random_bytes(3)),
            'nama'                 => 'Staf Uji',
            'password_hash'        => $hash,
            'status'               => 'aktif',
            'wajib_ganti_password' => 0,
            'created_at'           => $now,
            'updated_at'           => $now,
        ];
        $this->db->table('akun')->insert($row);
        $id = (int) $this->db->insertID();

        foreach ($roles as $role) {
            $this->db->table('akun_role')->insert(['akun_id' => $id, 'role' => $role, 'created_at' => $now]);
        }

        return $this->db->table('akun')->where('id', $id)->get()->getRowArray();
    }

    /**
     * Session data of a fresh login for this account, for withSession().
     *
     * @param array<string, mixed> $akun
     *
     * @return array<string, mixed>
     */
    protected function sesiAkun(array $akun): array
    {
        $now = (new Jam())->now()->getTimestamp();

        return [
            'akun_id'          => (int) $akun['id'],
            'jenis'            => $akun['jenis'],
            'login_at'         => $now,
            'aktif_at'         => $now,
            'cap'              => (new Kredensial())->cap($akun['password_hash']),
            'login_stasiun_id' => $akun['login_stasiun_id'] ?? null,
        ];
    }
}
