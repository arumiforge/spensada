<?php

namespace App\Controllers\Panel;

use App\Controllers\BaseController;
use App\Services\Akun\DaftarLogAktivitas;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Router\Attributes\Filter;

/**
 * Activity log, read only (docs/09 HAL-AKN-09, docs/12 SEC-62).
 */
class LogAktivitas extends BaseController
{
    #[Filter(by: 'hak', having: ['HA-AKN-08'])]
    public function index(): RedirectResponse|string
    {
        $daftar = new DaftarLogAktivitas();
        $query  = $this->request->getGet();
        ['saringan' => $saringan, 'tidakSah' => $tidakSah] = $daftar->saring($query);

        // Empty or invalid filters are dropped from the address (docs/09 RT-13, RT-04 item 6).
        if (array_diff_key(array_intersect_key($query, DaftarLogAktivitas::SARINGAN), $saringan) !== []) {
            $redirect = redirect()->to(url_to('panel.log_aktivitas.index') . ($saringan === [] ? '' : '?' . http_build_query($saringan)), 303);
            if ($tidakSah !== []) {
                $nama = implode(', ', array_map(static fn (string $n): string => DaftarLogAktivitas::SARINGAN[$n], $tidakSah));
                $redirect->with('galat', "Saringan {$nama} tidak sesuai, jadi tidak dipakai. Daftar memakai saringan lainnya.");
            }

            return $redirect;
        }

        // docs/11 VAL-30
        $page  = $query['page'] ?? '1';
        $page  = is_string($page) && ctype_digit($page) && (int) $page > 0 ? (int) $page : 1;
        $hasil = $daftar->cari($saringan, $page);

        return view('panel/log_aktivitas/index', [
            'title'    => 'Log aktivitas',
            'saringan' => $saringan,
            'page'     => $page,
            'total'    => $hasil['total'],
            'rows'     => $hasil['rows'],
            'daftar'   => $daftar,
            'akun'     => $daftar->pilihanAkun(array_map('intval', array_values(array_intersect_key($saringan, ['pelaku' => 1, 'akun' => 1])))),
        ]);
    }

    #[Filter(by: 'hak', having: ['HA-AKN-08'])]
    public function lihat(int $id): string
    {
        $daftar = new DaftarLogAktivitas();
        // Admin has scope Semua, so an unknown ID is 404 (docs/09 RT-11).
        $entri = $daftar->satu($id) ?? throw PageNotFoundException::forPageNotFound();

        return view('panel/log_aktivitas/lihat', [
            'title'    => 'Rincian log aktivitas',
            'entri'    => $entri,
            'rincian'  => $daftar->rincian($entri),
        ]);
    }
}
