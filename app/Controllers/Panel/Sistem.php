<?php

namespace App\Controllers\Panel;

use App\Controllers\BaseController;
use App\Services\Sistem\Jam;
use App\Services\Sistem\PemeriksaanSistem;
use CodeIgniter\Router\Attributes\Filter;

/**
 * System check page (docs/09 HAL-AKN-07, docs/07 ARS-57). Read-only: the
 * `aplikasi:cek` checks run here in the web PHP process.
 */
class Sistem extends BaseController
{
    #[Filter(by: 'hak', having: ['HA-AKN-07'])]
    public function index(): string
    {
        $now = (new Jam())->now();

        $aplikasi = [
            ['butir' => 'Versi aplikasi', 'status' => PemeriksaanSistem::BAIK, 'keterangan' => config('Spensada')->versi],
            // Kiosk code version arrives with the kiosk in FASE-06 (docs/15).
            ['butir' => 'Versi kode kiosk terbaru', 'status' => PemeriksaanSistem::BELUM_TERSEDIA, 'keterangan' => 'Pemeriksaan ini ditambahkan di tahap berikutnya.'],
            // docs/12 SEC-77
            ENVIRONMENT === 'production'
                ? ['butir' => 'CI_ENVIRONMENT', 'status' => PemeriksaanSistem::BAIK, 'keterangan' => ENVIRONMENT]
                : ['butir' => 'CI_ENVIRONMENT', 'status' => PemeriksaanSistem::PERLU_TINDAKAN, 'keterangan' => ENVIRONMENT . '. Di server harus production. Atur CI_ENVIRONMENT di .env.'],
        ];

        $sections = [
            'Server'       => [...(new PemeriksaanSistem())->run(), ...(new PemeriksaanSistem())->phpWeb()],
            'Aplikasi'     => $aplikasi,
            'Log aplikasi' => [
                $this->logCheck('Log hari ini', $now->toDateString()),
                $this->logCheck('Log kemarin', $now->subDays(1)->toDateString()),
            ],
        ];

        return view('panel/sistem/index', [
            'title'      => 'Pemeriksaan sistem',
            'checkedAt'  => $now,
            'sections'   => $sections,
            'needAction' => count(array_filter(array_merge(...array_values($sections)), static fn (array $r) => $r['status'] === PemeriksaanSistem::PERLU_TINDAKAN)),
        ]);
    }

    /**
     * Counts CRITICAL and ERROR lines in one day's log without showing its
     * contents (docs/11 GAL-22). Only critical lines need action.
     */
    private function logCheck(string $name, string $date): array
    {
        $counts = ['CRITICAL' => 0, 'ERROR' => 0];
        $path   = WRITEPATH . 'logs/log-' . $date . '.log';

        if (is_file($path) && ($file = fopen($path, 'rb')) !== false) {
            while (($line = fgets($file)) !== false) {
                if (preg_match('/^(CRITICAL|ERROR) - /', $line, $m) === 1) {
                    $counts[$m[1]]++;
                }
            }
            fclose($file);
        }

        return [
            'butir'      => $name . ' (' . format_date($date, 'short') . ')',
            'status'     => $counts['CRITICAL'] > 0 ? PemeriksaanSistem::PERLU_TINDAKAN : PemeriksaanSistem::BAIK,
            'keterangan' => format_number($counts['CRITICAL']) . ' baris critical, ' . format_number($counts['ERROR']) . ' baris error.'
                . ($counts['CRITICAL'] > 0 ? ' Baca log di terminal server.' : ''),
        ];
    }
}
