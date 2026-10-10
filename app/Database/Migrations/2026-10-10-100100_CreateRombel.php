<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Table `rombel` (docs/06 §6.4). The screen calls it "Kelas".
 */
class CreateRombel extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'tahun_ajaran_id' => ['type' => 'INT', 'unsigned' => true],
            'nama'            => ['type' => 'VARCHAR', 'constraint' => 20],
            'tingkat'         => ['type' => 'TINYINT', 'unsigned' => true],
            'wali_kelas_id'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'      => ['type' => 'DATETIME'],
            'updated_at'      => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['tahun_ajaran_id', 'nama'], 'uq_rombel_nama');
        $this->forge->addKey('wali_kelas_id', false, false, 'ix_rombel_wali_kelas');
        $this->forge->addKey(['tahun_ajaran_id', 'tingkat'], false, false, 'ix_rombel_tingkat');
        $this->forge->addForeignKey('tahun_ajaran_id', 'tahun_ajaran', 'id', 'RESTRICT', 'RESTRICT', 'fk_rombel_tahun_ajaran');
        $this->forge->addForeignKey('wali_kelas_id', 'akun', 'id', 'RESTRICT', 'RESTRICT', 'fk_rombel_wali_kelas');
        $this->forge->createTable('rombel', false, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        $this->forge->dropTable('rombel');
    }
}
