<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Table `akun_role` (docs/06 §5.2): rows are only added or deleted.
 */
class AkunRoleModel extends Model
{
    protected $table         = 'akun_role';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $updatedField  = '';
    protected $allowedFields = ['akun_id', 'role', 'diberikan_oleh'];

    /**
     * Stored roles of one account.
     *
     * @return list<string>
     */
    public function rolesOf(int $akunId): array
    {
        return array_column($this->select('role')->where('akun_id', $akunId)->orderBy('role')->findAll(), 'role');
    }
}
