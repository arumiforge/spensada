<?php

namespace App\Controllers\Panel;

use App\Controllers\BaseController;
use App\Services\MasterData\DaftarLogDataSiswa;
use CodeIgniter\Router\Attributes\Filter;

/**
 * Student data log, the "Log data" tab of the profile (docs/09 HAL-MD-17,
 * docs/04 FS-MD-04 item 8). Scope of HA-MD-10: Semua, or the wali kelas's rombel.
 */
class LogDataSiswa extends BaseController
{
    #[Filter(by: 'hak', having: ['HA-MD-10'])]
    public function index(int $id): string
    {
        $siswa = Siswa::siswaDalamCakupan($id, 'HA-MD-10');
        $page  = max(1, (int) $this->request->getGet('page'));
        $log   = new DaftarLogDataSiswa();

        return view('panel/log_data_siswa/index', [
            'title' => 'Log data ' . $siswa['nama'],
            'siswa' => $siswa,
            'page'  => $page,
            'log'   => $log,
        ] + $log->daftar($id, $page));
    }
}
