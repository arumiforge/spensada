<?php

namespace App\Controllers\Panel;

use App\Controllers\BaseController;
use App\Services\MasterData\Siswa as SiswaService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Router\Attributes\Filter;

/**
 * Parent's WA number of one student (docs/09 HAL-MD-08, docs/04 FS-MD-04
 * item 3). Separate from the edit form, because a wali kelas may change
 * the number of their rombel's students but no other data (E5).
 */
class SiswaWa extends BaseController
{
    #[Filter(by: 'hak', having: ['HA-MD-06'])]
    public function ubah(int $id): string
    {
        helper('form');

        return view('panel/siswa_wa/ubah', [
            'title' => 'Nomor WA orang tua/wali',
            'siswa' => Siswa::siswaDalamCakupan($id, 'HA-MD-06'),
        ]);
    }

    #[Filter(by: 'hak', having: ['HA-MD-06'])]
    public function ganti(int $id): RedirectResponse
    {
        $siswa = Siswa::siswaDalamCakupan($id, 'HA-MD-06');
        $wa    = $this->request->getPost('wa_ortu');
        $wa    = is_string($wa) ? $wa : '';
        $versi = $this->request->getPost('versi');
        $form  = url_to('panel.siswa_wa.ubah', $id);
        $hasil = (new SiswaService())->gantiWa($id, is_string($versi) ? $versi : '', $wa, service('akunAktif')->id());

        if (isset($hasil['galat'])) {
            return redirect()->to($form, 303)
                ->with('_ci_old_input', ['get' => [], 'post' => ['wa_ortu' => $wa]])
                ->with('_ci_validation_errors', $hasil['galat']);
        }
        if (isset($hasil['konflik'])) {
            return redirect()->to($form, 303)->with('galat', Siswa::pesanKonflik($id));
        }

        return redirect()->to(url_to('panel.siswa.lihat', $id), 303)->with('sukses', $hasil['wa_ortu'] === null
            ? "Nomor WA orang tua/wali {$siswa['nama']} dihapus."
            : "Nomor WA orang tua/wali {$siswa['nama']} disimpan: " . format_wa($hasil['wa_ortu']) . '.');
    }
}
