<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Table `penempatan` (docs/06 §6.7): a student's rombel over a date range.
 */
class CreatePenempatan extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'siswa_id'        => ['type' => 'INT', 'unsigned' => true],
            'rombel_id'       => ['type' => 'INT', 'unsigned' => true],
            'tanggal_mulai'   => ['type' => 'DATE'],
            'tanggal_selesai' => ['type' => 'DATE', 'null' => true],
            'dibuat_oleh'     => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'      => ['type' => 'DATETIME'],
            'updated_at'      => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['siswa_id', 'tanggal_mulai'], false, false, 'ix_penempatan_siswa');
        $this->forge->addKey(['rombel_id', 'tanggal_mulai'], false, false, 'ix_penempatan_rombel');
        $this->forge->addForeignKey('siswa_id', 'siswa', 'id', 'RESTRICT', 'RESTRICT', 'fk_penempatan_siswa');
        $this->forge->addForeignKey('rombel_id', 'rombel', 'id', 'RESTRICT', 'RESTRICT', 'fk_penempatan_rombel');
        $this->forge->addForeignKey('dibuat_oleh', 'akun', 'id', 'RESTRICT', 'RESTRICT', 'fk_penempatan_dibuat_oleh');
        $this->forge->createTable('penempatan', false, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        $this->forge->dropTable('penempatan');
    }
}
