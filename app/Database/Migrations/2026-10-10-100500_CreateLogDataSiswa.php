<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Table `log_data_siswa` (docs/06 §12.2). Rows are only added.
 */
class CreateLogDataSiswa extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'siswa_id'   => ['type' => 'INT', 'unsigned' => true],
            'jenis'      => ['type' => 'VARCHAR', 'constraint' => 30],
            'data_lama'  => ['type' => 'JSON', 'null' => true],
            'data_baru'  => ['type' => 'JSON', 'null' => true],
            'alasan'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'pelaku_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'kelompok'   => ['type' => 'CHAR', 'constraint' => 36, 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['siswa_id', 'created_at'], false, false, 'ix_log_data_siswa_siswa');
        $this->forge->addKey('kelompok', false, false, 'ix_log_data_siswa_kelompok');
        $this->forge->addForeignKey('siswa_id', 'siswa', 'id', 'RESTRICT', 'RESTRICT', 'fk_log_data_siswa_siswa');
        $this->forge->addForeignKey('pelaku_id', 'akun', 'id', 'RESTRICT', 'RESTRICT', 'fk_log_data_siswa_pelaku');
        $this->forge->createTable('log_data_siswa', false, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        $this->forge->dropTable('log_data_siswa');
    }
}
