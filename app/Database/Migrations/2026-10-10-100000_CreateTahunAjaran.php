<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tables `tahun_ajaran` and `semester` (docs/06 §6.2, §6.3).
 *
 * `aktif_kunci` is a generated column (DB-10) written in SQL, because Forge
 * has no generated columns: its unique key allows at most one active year.
 */
class CreateTahunAjaran extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nama'            => ['type' => 'VARCHAR', 'constraint' => 20],
            'tanggal_mulai'   => ['type' => 'DATE'],
            'tanggal_selesai' => ['type' => 'DATE'],
            'aktif'           => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at'      => ['type' => 'DATETIME'],
            'updated_at'      => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('nama', 'uq_tahun_ajaran_nama');
        $this->forge->createTable('tahun_ajaran', false, ['ENGINE' => 'InnoDB']);
        $this->db->query('ALTER TABLE tahun_ajaran ADD COLUMN aktif_kunci TINYINT AS (IF(aktif = 1, 1, NULL)) STORED AFTER aktif, ADD UNIQUE KEY uq_tahun_ajaran_aktif (aktif_kunci)');

        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'tahun_ajaran_id' => ['type' => 'INT', 'unsigned' => true],
            'jenis'           => ['type' => 'VARCHAR', 'constraint' => 6],
            'tanggal_mulai'   => ['type' => 'DATE'],
            'tanggal_selesai' => ['type' => 'DATE'],
            'created_at'      => ['type' => 'DATETIME'],
            'updated_at'      => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['tahun_ajaran_id', 'jenis'], 'uq_semester_jenis');
        $this->forge->addKey(['tanggal_mulai', 'tanggal_selesai'], false, false, 'ix_semester_tanggal');
        $this->forge->addForeignKey('tahun_ajaran_id', 'tahun_ajaran', 'id', 'RESTRICT', 'RESTRICT', 'fk_semester_tahun_ajaran');
        $this->forge->createTable('semester', false, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        $this->forge->dropTable('semester');
        $this->forge->dropTable('tahun_ajaran');
    }
}
