<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Table `nilai_atribut_siswa` (docs/06 §6.9).
 */
class NilaiAtributSiswaModel extends Model
{
    protected $table         = 'nilai_atribut_siswa';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $allowedFields = ['siswa_id', 'atribut_id', 'nilai'];
}
