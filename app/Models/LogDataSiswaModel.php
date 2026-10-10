<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Table `log_data_siswa` (docs/06 §12.2). Append only: write through `Services\MasterData\LogDataSiswa`.
 */
class LogDataSiswaModel extends Model
{
    protected $table         = 'log_data_siswa';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $updatedField  = '';
    protected $allowedFields = ['siswa_id', 'jenis', 'data_lama', 'data_baru', 'alasan', 'pelaku_id', 'kelompok'];
}
