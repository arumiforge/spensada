<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tables `siswa` and `masa_aktif` (docs/06 §6.5, §6.6).
 *
 * `masa_aktif.terbuka_kunci` is a generated column (DB-10): its unique key
 * allows at most one open period per student.
 */
class CreateSiswa extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nisn'            => ['type' => 'CHAR', 'constraint' => 10],
            'nis'             => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'nama'            => ['type' => 'VARCHAR', 'constraint' => 100],
            'jenis_kelamin'   => ['type' => 'CHAR', 'constraint' => 1, 'null' => true],
            'tanggal_lahir'   => ['type' => 'DATE', 'null' => true],
            'alamat'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'nama_ortu'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'wa_ortu'         => ['type' => 'VARCHAR', 'constraint' => 15, 'null' => true],
            'foto_file'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'foto_diganti_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at'      => ['type' => 'DATETIME'],
            'updated_at'      => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('nisn', 'uq_siswa_nisn');
        $this->forge->addUniqueKey('nis', 'uq_siswa_nis');
        $this->forge->addKey('nama', false, false, 'ix_siswa_nama');
        $this->forge->addKey('wa_ortu', false, false, 'ix_siswa_wa_ortu');
        $this->forge->createTable('siswa', false, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'siswa_id'            => ['type' => 'INT', 'unsigned' => true],
            'tanggal_mulai'       => ['type' => 'DATE'],
            'tanggal_selesai'     => ['type' => 'DATE', 'null' => true],
            'alasan_nonaktif'     => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'keterangan_nonaktif' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'dibatalkan'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'dibuat_oleh'         => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'dinonaktifkan_oleh'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'          => ['type' => 'DATETIME'],
            'updated_at'          => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['siswa_id', 'tanggal_mulai'], false, false, 'ix_masa_aktif_siswa');
        $this->forge->addForeignKey('siswa_id', 'siswa', 'id', 'RESTRICT', 'RESTRICT', 'fk_masa_aktif_siswa');
        $this->forge->addForeignKey('dibuat_oleh', 'akun', 'id', 'RESTRICT', 'RESTRICT', 'fk_masa_aktif_dibuat_oleh');
        $this->forge->addForeignKey('dinonaktifkan_oleh', 'akun', 'id', 'RESTRICT', 'RESTRICT', 'fk_masa_aktif_dinonaktifkan_oleh');
        $this->forge->createTable('masa_aktif', false, ['ENGINE' => 'InnoDB']);
        $this->db->query('ALTER TABLE masa_aktif ADD COLUMN terbuka_kunci TINYINT AS (IF(tanggal_selesai IS NULL, 1, NULL)) STORED AFTER dinonaktifkan_oleh, ADD UNIQUE KEY uq_masa_aktif_terbuka (siswa_id, terbuka_kunci)');
    }

    public function down(): void
    {
        $this->forge->dropTable('masa_aktif');
        $this->forge->dropTable('siswa');
    }
}
