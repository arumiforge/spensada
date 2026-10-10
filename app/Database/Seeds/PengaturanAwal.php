<?php

namespace App\Database\Seeds;

use App\Services\Sistem\Jam;
use CodeIgniter\Database\Seeder;

/**
 * Fills every R1 key of `pengaturan` with its docs/06 §6.1 default
 * (docs/07 ARS-18). Runs on every install, so existing values are kept.
 */
class PengaturanAwal extends Seeder
{
    /**
     * R1 keys and defaults; null means "Kosong".
     */
    private const DEFAULTS = [
        'sekolah_nama'           => 'SMP 1 DAWE',
        'sekolah_alamat'         => 'Dawe, Kabupaten Kudus, Jawa Tengah',
        'sekolah_logo'           => null,
        'batas_mundur_hari'      => '7',
        'status_dibangun_sampai' => null,
        'status_mulai'           => null,
        'cron_terakhir_at'       => null,
        'kiosk_pin'              => null,
    ];

    public function run(): void
    {
        $now  = (new Jam())->now()->toDateTimeString();
        $rows = [];

        foreach (self::DEFAULTS as $kunci => $nilai) {
            $rows[] = ['kunci' => $kunci, 'nilai' => $nilai, 'updated_at' => $now, 'diubah_oleh' => null];
        }

        // IGNORE keeps keys that already exist, e.g. values set by the admin.
        $this->db->table('pengaturan')->ignore()->insertBatch($rows);
    }
}
