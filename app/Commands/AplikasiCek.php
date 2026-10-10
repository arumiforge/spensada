<?php

namespace App\Commands;

use App\Services\Sistem\PemeriksaanSistem;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * `php spark aplikasi:cek` (docs/07 ARS-57). Exits non-zero when a check
 * needs action.
 */
class AplikasiCek extends BaseCommand
{
    protected $group       = 'Spensada';
    protected $name        = 'aplikasi:cek';
    protected $description = 'Memeriksa kesiapan server untuk Spensada.';
    protected $usage       = 'aplikasi:cek';

    public function run(array $params): int
    {
        $results = (new PemeriksaanSistem())->run();

        CLI::table(
            array_map(static fn (array $r) => [$r['butir'], PemeriksaanSistem::LABEL[$r['status']], $r['keterangan']], $results),
            ['Pemeriksaan', 'Hasil', 'Keterangan'],
        );

        $failed = count(array_filter($results, static fn (array $r) => $r['status'] === PemeriksaanSistem::PERLU_TINDAKAN));

        if ($failed > 0) {
            CLI::error("Ada {$failed} pemeriksaan yang perlu tindakan. Perbaiki lalu jalankan perintah ini lagi.");

            return EXIT_ERROR;
        }

        CLI::write('Semua pemeriksaan baik.', 'green');

        return EXIT_SUCCESS;
    }
}
