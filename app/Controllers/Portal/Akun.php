<?php

namespace App\Controllers\Portal;

use App\Controllers\BaseController;
use App\Services\Akun\AkunSiswa;
use App\Services\Berkas\Penyaji;
use App\Services\MasterData\IdentitasSekolah;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Router\Attributes\Filter;

/**
 * The student's own account page in the portal (docs/09 HAL-AKN-08,
 * docs/04 FS-MD-04 item 6, AC-MD-04-06). Read only; scope "sendiri" is the
 * logged-in student, so no ID comes from the request. The school's privacy
 * notice shows here too (docs/12 SEC-68, docs/08 UI-38).
 */
class Akun extends BaseController
{
    #[Filter(by: 'hak', having: ['HA-MD-05'])]
    public function index(): string
    {
        return view('portal/akun/index', [
            'title'   => 'Akun',
            'siswa'   => (new AkunSiswa())->profil($this->siswaId()),
            'privasi' => (string) (new IdentitasSekolah())->ambil()['privasi_teks'],
        ]);
    }

    #[Filter(by: 'hak', having: ['HA-MD-05'])]
    public function foto(): ResponseInterface
    {
        $file = (string) (new AkunSiswa())->profil($this->siswaId())['foto_file'];

        if ($file === '') {
            throw PageNotFoundException::forPageNotFound();
        }

        return (new Penyaji())->tampilkan($file);
    }

    private function siswaId(): int
    {
        return (int) service('akunAktif')->akun()['siswa_id'];
    }
}
