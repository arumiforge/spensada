<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Table `semester` (docs/06 §6.3).
 */
class SemesterModel extends Model
{
    protected $table         = 'semester';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $allowedFields = ['tahun_ajaran_id', 'jenis', 'tanggal_mulai', 'tanggal_selesai'];
}
