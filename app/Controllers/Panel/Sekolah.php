<?php

namespace App\Controllers\Panel;

use App\Controllers\BaseController;
use App\Services\MasterData\IdentitasSekolah;
use App\Services\Sistem\Jam;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Router\Attributes\Filter;

/**
 * School identity (docs/09 HAL-MD-01, docs/04 FS-MD-01). Admin only.
 */
class Sekolah extends BaseController
{
    #[Filter(by: 'hak', having: ['HA-MD-09'])]
    public function index(): string
    {
        helper('form');
        $identitas = new IdentitasSekolah();

        return view('panel/sekolah/index', [
            'title'     => 'Identitas sekolah',
            'identitas' => $identitas->ambil(),
            'urlLogo'   => $identitas->urlLogo(),
        ]);
    }

    #[Filter(by: 'hak', having: ['HA-MD-09'])]
    public function perbarui(): RedirectResponse
    {
        $form  = url_to('panel.sekolah.index');
        $isian = [
            'sekolah_nama'   => (string) $this->request->getPost('sekolah_nama'),
            'sekolah_alamat' => (string) $this->request->getPost('sekolah_alamat'),
            'privasi_teks'   => (string) $this->request->getPost('privasi_teks'),
        ];
        $identitas = new IdentitasSekolah();
        $hasil     = $identitas->simpan(
            (string) $this->request->getPost('versi'),
            $isian['sekolah_nama'],
            $isian['sekolah_alamat'],
            $isian['privasi_teks'],
            $this->request->getFile('logo'),
            $this->request->getPost('hapus_logo') === '1',
            service('akunAktif')->id(),
            $this->request->getIPAddress(),
        );

        if (isset($hasil['galat'])) {
            // docs/11 VAL-07: old input back, the file must be chosen again.
            return redirect()->to($form, 303)
                ->with('_ci_old_input', ['get' => [], 'post' => $isian])
                ->with('_ci_validation_errors', $hasil['galat']);
        }
        if (isset($hasil['konflik'])) {
            // docs/11 GAL-05: show the latest data, without the user's input.
            return redirect()->to($form, 303)->with('galat', $this->pesanKonflik($identitas));
        }
        if ($hasil['logo_galat'] !== null) {
            // docs/11 §5.2 FS-MD-01 E2: the other fields are saved.
            return redirect()->to($form, 303)
                ->with('_ci_validation_errors', ['logo' => $hasil['logo_galat']])
                ->with('galat', 'Identitas sekolah disimpan. Logo tidak diganti.');
        }

        return redirect()->to($form, 303)->with('sukses', 'Identitas sekolah disimpan.');
    }

    private function pesanKonflik(IdentitasSekolah $identitas): string
    {
        $info = $identitas->perubahanTerakhir();

        if ($info === null || $info['nama'] === null) {
            return 'Data ini sudah diubah orang lain. Periksa data terbaru, lalu simpan lagi bila perlu.';
        }

        $waktu = substr($info['waktu'], 0, 10) === (new Jam())->today()
            ? 'pukul ' . format_time($info['waktu'])
            : 'pada ' . format_datetime($info['waktu']);

        return "Data ini sudah diubah oleh {$info['nama']} {$waktu}. Periksa data terbaru, lalu simpan lagi bila perlu.";
    }
}
