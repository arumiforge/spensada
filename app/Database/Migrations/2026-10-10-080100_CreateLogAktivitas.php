<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Table `log_aktivitas` (docs/06 §5.3, docs/12 SEC-57, SEC-59).
 *
 * `rombel_id` has no foreign key yet: `rombel` arrives in FASE-02, whose
 * migration adds the key (docs/15 §2.2).
 */
class CreateLogAktivitas extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'jenis'      => ['type' => 'VARCHAR', 'constraint' => 40],
            'pelaku_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'akun_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'rombel_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'data'       => ['type' => 'JSON', 'null' => true],
            'ip'         => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['jenis', 'created_at'], false, false, 'ix_log_aktivitas_jenis');
        $this->forge->addKey(['akun_id', 'created_at'], false, false, 'ix_log_aktivitas_akun');
        $this->forge->addKey(['pelaku_id', 'created_at'], false, false, 'ix_log_aktivitas_pelaku');
        $this->forge->addForeignKey('pelaku_id', 'akun', 'id', 'RESTRICT', 'RESTRICT', 'fk_log_aktivitas_pelaku');
        $this->forge->addForeignKey('akun_id', 'akun', 'id', 'RESTRICT', 'RESTRICT', 'fk_log_aktivitas_akun');
        $this->forge->createTable('log_aktivitas', false, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        $this->forge->dropTable('log_aktivitas');
    }
}
