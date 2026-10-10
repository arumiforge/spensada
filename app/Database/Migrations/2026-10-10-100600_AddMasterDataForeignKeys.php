<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Foreign keys `akun.siswa_id` → `siswa.id` and `log_aktivitas.rombel_id` →
 * `rombel.id`, added now that both tables exist (docs/15 §2.2, docs/06 DB-08).
 */
class AddMasterDataForeignKeys extends Migration
{
    public function up(): void
    {
        $this->db->query('ALTER TABLE akun ADD CONSTRAINT fk_akun_siswa FOREIGN KEY (siswa_id) REFERENCES siswa (id) ON DELETE RESTRICT ON UPDATE RESTRICT');
        $this->db->query('ALTER TABLE log_aktivitas ADD CONSTRAINT fk_log_aktivitas_rombel FOREIGN KEY (rombel_id) REFERENCES rombel (id) ON DELETE RESTRICT ON UPDATE RESTRICT');
    }

    public function down(): void
    {
        $this->forge->dropForeignKey('log_aktivitas', 'fk_log_aktivitas_rombel');
        $this->forge->dropForeignKey('akun', 'fk_akun_siswa');
    }
}
