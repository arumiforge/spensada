<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Table `log_aktivitas` (docs/06 §5.3). Append only: the app never updates
 * or deletes rows (docs/12 SEC-57). Write through `Services\Akun\LogAktivitas`.
 */
class LogAktivitasModel extends Model
{
    protected $table         = 'log_aktivitas';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $updatedField  = '';
    protected $allowedFields = ['jenis', 'pelaku_id', 'akun_id', 'rombel_id', 'data', 'ip'];
}
