<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Table `percobaan_login` (docs/06 §5.4, docs/12 SEC-09). Technical data,
 * not a log; no foreign key, since the identity tried may not exist.
 */
class CreatePercobaanLogin extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'identitas_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'ip'             => ['type' => 'VARCHAR', 'constraint' => 45],
            'created_at'     => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['identitas_hash', 'created_at'], false, false, 'ix_percobaan_login_identitas');
        $this->forge->addKey(['ip', 'created_at'], false, false, 'ix_percobaan_login_ip');
        $this->forge->createTable('percobaan_login', false, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        $this->forge->dropTable('percobaan_login');
    }
}
