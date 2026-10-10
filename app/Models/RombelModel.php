<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Table `rombel` (docs/06 §6.4). The screen calls it "Kelas".
 */
class RombelModel extends Model
{
    protected $table         = 'rombel';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $allowedFields = ['tahun_ajaran_id', 'nama', 'tingkat', 'wali_kelas_id'];
}
