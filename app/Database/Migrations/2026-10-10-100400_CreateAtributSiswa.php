<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tables `atribut_siswa` and `nilai_atribut_siswa` (docs/06 §6.8, §6.9).
 */
class CreateAtributSiswa extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'kode'       => ['type' => 'VARCHAR', 'constraint' => 30],
            'label'      => ['type' => 'VARCHAR', 'constraint' => 60],
            'tipe'       => ['type' => 'VARCHAR', 'constraint' => 10],
            'pilihan'    => ['type' => 'JSON', 'null' => true],
            'wajib'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'urutan'     => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 0],
            'aktif'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('kode', 'uq_atribut_siswa_kode');
        $this->forge->createTable('atribut_siswa', false, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'siswa_id'   => ['type' => 'INT', 'unsigned' => true],
            'atribut_id' => ['type' => 'INT', 'unsigned' => true],
            'nilai'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['siswa_id', 'atribut_id'], 'uq_nilai_atribut_siswa');
        $this->forge->addKey('atribut_id', false, false, 'ix_nilai_atribut_atribut');
        $this->forge->addForeignKey('siswa_id', 'siswa', 'id', 'RESTRICT', 'RESTRICT', 'fk_nilai_atribut_siswa');
        $this->forge->addForeignKey('atribut_id', 'atribut_siswa', 'id', 'RESTRICT', 'RESTRICT', 'fk_nilai_atribut_atribut');
        $this->forge->createTable('nilai_atribut_siswa', false, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        $this->forge->dropTable('nilai_atribut_siswa');
        $this->forge->dropTable('atribut_siswa');
    }
}
