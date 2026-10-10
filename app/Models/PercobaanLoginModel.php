<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Table `percobaan_login` (docs/06 §5.4). Write through
 * `Services\Akun\PercobaanLogin`.
 */
class PercobaanLoginModel extends Model
{
    protected $table         = 'percobaan_login';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['identitas_hash', 'ip', 'created_at'];
}
