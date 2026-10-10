<?php

namespace App\Services\Akun;

use App\Models\AkunRoleModel;
use App\Services\MasterData\Rujukan;

/**
 * Roles of an account, loaded on every request and never kept in the session
 * (docs/07 §5.2, ARS-47 item 3, docs/06 §5.1): Staf, Siswa and Stasiun come
 * from `akun.jenis`, Wali kelas from `rombel`, the other staff roles from `akun_role`.
 */
class Peran
{
    /**
     * @param array<string, mixed> $akun Row of `akun`
     *
     * @return list<string>
     */
    public function untuk(array $akun): array
    {
        if ($akun['jenis'] !== 'staf') {
            return [$akun['jenis']];
        }

        $roles = ['staf', ...model(AkunRoleModel::class)->rolesOf((int) $akun['id'])];

        // Wali kelas comes from rombel.wali_kelas_id in the active school year (docs/02 §3 item 2).
        if ((new Rujukan())->rombelWaliKelas((int) $akun['id']) !== []) {
            $roles[] = 'wali_kelas';
        }

        return $roles;
    }
}
