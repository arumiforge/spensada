<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tables `akun` and `akun_role` (docs/06 §5.1, §5.2).
 *
 * `akun.siswa_id` has no foreign key yet: `siswa` arrives in FASE-02, whose
 * migration adds the key (docs/15 §2.2).
 */
class CreateAkun extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                   => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'jenis'                => ['type' => 'VARCHAR', 'constraint' => 10],
            'username'             => ['type' => 'VARCHAR', 'constraint' => 50],
            'nama'                 => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'siswa_id'             => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'password_hash'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'               => ['type' => 'VARCHAR', 'constraint' => 12],
            'wajib_ganti_password' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'password_diganti_at'  => ['type' => 'DATETIME', 'null' => true],
            'slip_dibuat_at'       => ['type' => 'DATETIME', 'null' => true],
            'login_terakhir_at'    => ['type' => 'DATETIME', 'null' => true],
            'login_stasiun_id'     => ['type' => 'CHAR', 'constraint' => 32, 'null' => true],
            'nip'                  => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'created_at'           => ['type' => 'DATETIME'],
            'updated_at'           => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('username', 'uq_akun_username');
        $this->forge->addUniqueKey('siswa_id', 'uq_akun_siswa');
        $this->forge->addKey(['jenis', 'status'], false, false, 'ix_akun_jenis_status');
        $this->forge->createTable('akun', false, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'akun_id'        => ['type' => 'INT', 'unsigned' => true],
            'role'           => ['type' => 'VARCHAR', 'constraint' => 20],
            'diberikan_oleh' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'     => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['akun_id', 'role'], 'uq_akun_role');
        $this->forge->addForeignKey('akun_id', 'akun', 'id', 'RESTRICT', 'RESTRICT', 'fk_akun_role_akun');
        $this->forge->addForeignKey('diberikan_oleh', 'akun', 'id', 'RESTRICT', 'RESTRICT', 'fk_akun_role_diberikan_oleh');
        $this->forge->createTable('akun_role', false, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        $this->forge->dropTable('akun_role');
        $this->forge->dropTable('akun');
    }
}
