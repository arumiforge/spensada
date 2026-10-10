<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Foreign key `pengaturan.diubah_oleh` → `akun.id`, added now that `akun`
 * exists (docs/15 §2.2, docs/06 DB-08).
 */
class AddPengaturanDiubahOlehForeignKey extends Migration
{
    public function up(): void
    {
        $this->db->query('ALTER TABLE pengaturan ADD CONSTRAINT fk_pengaturan_diubah_oleh FOREIGN KEY (diubah_oleh) REFERENCES akun (id) ON DELETE RESTRICT ON UPDATE RESTRICT');
    }

    public function down(): void
    {
        $this->forge->dropForeignKey('pengaturan', 'fk_pengaturan_diubah_oleh');
    }
}
