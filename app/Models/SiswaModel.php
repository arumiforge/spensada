<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Table `siswa` (docs/06 §6.5).
 */
class SiswaModel extends Model
{
    protected $table         = 'siswa';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $allowedFields = ['nisn', 'nis', 'nama', 'jenis_kelamin', 'tanggal_lahir', 'alamat', 'nama_ortu', 'wa_ortu', 'foto_file', 'foto_diganti_at'];
}
