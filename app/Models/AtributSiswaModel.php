<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Table `atribut_siswa` (docs/06 §6.8).
 */
class AtributSiswaModel extends Model
{
    protected $table         = 'atribut_siswa';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $allowedFields = ['kode', 'label', 'tipe', 'pilihan', 'wajib', 'urutan', 'aktif'];
}
