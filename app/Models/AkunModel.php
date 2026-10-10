<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Table `akun` (docs/06 §5.1). System writes such as `login_terakhir_at`
 * go through the query builder without touching `updated_at` (docs/07 ARS-40).
 */
class AkunModel extends Model
{
    protected $table          = 'akun';
    protected $returnType     = 'array';
    protected $useTimestamps  = true;
    protected $dateFormat     = 'datetime';
    protected $allowedFields  = [
        'jenis', 'username', 'nama', 'siswa_id', 'password_hash', 'status', 'wajib_ganti_password',
        'password_diganti_at', 'slip_dibuat_at', 'login_terakhir_at', 'login_stasiun_id', 'nip',
    ];

    /**
     * Account by its cleaned login identity (docs/12 SEC-01 item 1).
     *
     * @return array<string, mixed>|null
     */
    public function byUsername(string $username): ?array
    {
        return $this->where('username', $username)->first();
    }
}
