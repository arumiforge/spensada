<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Table `penempatan` (docs/06 §6.7).
 */
class PenempatanModel extends Model
{
    protected $table         = 'penempatan';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $allowedFields = ['siswa_id', 'rombel_id', 'tanggal_mulai', 'tanggal_selesai', 'dibuat_oleh'];
}
