<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Table `pengaturan` (docs/06 §6.1).
 *
 * `diubah_oleh` has no foreign key yet: `akun` arrives in FASE-01, whose
 * migration adds the key (docs/15 §2.2). It is UNSIGNED to match `akun.id`
 * (docs/06 DB-03).
 */
class CreatePengaturan extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'kunci'       => ['type' => 'VARCHAR', 'constraint' => 60],
            'nilai'       => ['type' => 'TEXT', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME'],
            'diubah_oleh' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);
        $this->forge->addPrimaryKey('kunci');
        $this->forge->createTable('pengaturan', false, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        $this->forge->dropTable('pengaturan');
    }
}
