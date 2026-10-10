<?php

namespace App\Services\Akun;

use App\Models\AkunRoleModel;

/**
 * Roles of an account, loaded on every request and never kept in the session
 * (docs/07 §5.2, ARS-47 item 3, docs/06 §5.1): Staf, Siswa and Stasiun come
 * from `akun.jenis`, the other staff roles from `akun_role`.
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

        // FASE-02 (L02-01) adds `wali_kelas` from rombel.wali_kelas_id in the active school year.
        return ['staf', ...model(AkunRoleModel::class)->rolesOf((int) $akun['id'])];
    }
}
