<?php

namespace App\Controllers\Akun;

use App\Controllers\BaseController;
use App\Filters\Sesi;
use App\Models\SiswaModel;
use App\Services\Akun\GantiPassword;
use App\Services\Akun\Kredensial;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Router\Attributes\Filter;

/**
 * Change password (docs/09 HAL-AKN-03, docs/04 FS-AKN-02).
 *
 * Session keys: `tanpa_password_lama` (set by login for a forced change; the
 * old password is then not asked) and `salah_password_lama` (wrong old
 * passwords in a row in this session; 5 ends the session, docs/11 §5.1).
 */
class Password extends BaseController
{
    private const BATAS_SALAH = 5;

    #[Filter(by: 'hak', having: ['HA-AKN-01'])]
    public function ubah(): string
    {
        $akun  = service('akunAktif')->akun();
        $wajib = (int) $akun['wajib_ganti_password'] === 1;

        return view('akun/password', [
            'title'     => 'Ganti password',
            'layout'    => $wajib ? 'layout/akun' : ($akun['jenis'] === 'siswa' ? 'layout/portal' : 'layout/panel'),
            'wajib'     => $wajib,
            'tanpaLama' => $this->tanpaLama($akun),
            'siswa'     => $akun['jenis'] === 'siswa',
            'galat'     => session()->getFlashdata('_ci_validation_errors') ?? [],
        ]);
    }

    #[Filter(by: 'hak', having: ['HA-AKN-01'])]
    public function ganti(): RedirectResponse
    {
        $akun   = service('akunAktif')->akun();
        $ip     = $this->request->getIPAddress();
        $isian  = ($this->tanpaLama($akun) ? [] : ['password_lama' => 'Password lama'])
            + ['password_baru' => 'Password baru', 'password_ulang' => 'Ulangi password baru'];
        $post   = $this->request->getPost(array_keys($isian));
        $aturan = array_map(static fn (string $label): array => ['label' => $label, 'rules' => 'required|string'], $isian);

        // Passwords never go back as old input (docs/12 SEC-07, docs/09 RT-08 item 2).
        if (! $this->validateData($post, $aturan)) {
            return $this->kembali($this->validator->getErrors());
        }

        $service = new GantiPassword();
        // SEC-03 item 5: a student's password must not contain their birth date.
        $tanggalLahir = $akun['siswa_id'] === null ? null : (new SiswaModel())->find($akun['siswa_id'])['tanggal_lahir'] ?? null;
        $galat        = $service->periksa($akun, $post['password_lama'] ?? null, $post['password_baru'], $post['password_ulang'], $ip, $tanggalLahir);

        if (isset($galat['password_lama'])) {
            $salah = (int) session('salah_password_lama') + 1;

            if ($salah >= self::BATAS_SALAH) {
                Sesi::end();

                return redirect()->to('/login', 303)
                    ->with('galat', 'Password lama salah 5 kali, sehingga sesi berakhir. Login lagi untuk melanjutkan.');
            }

            session()->set('salah_password_lama', $salah);
        } else {
            session()->remove('salah_password_lama');
        }

        if ($galat !== []) {
            return $this->kembali($galat);
        }

        $hash = $service->simpan($akun, $post['password_baru'], $ip);

        // New session ID; the new stamp keeps this session while every other one ends (docs/07 ARS-47 item 2, docs/12 SEC-14).
        session()->regenerate(true);
        session()->set('cap', (new Kredensial())->cap($hash));
        session()->remove('tanpa_password_lama');

        return redirect()->to($akun['jenis'] === 'siswa' ? '/portal' : '/panel', 303)
            ->with('sukses', 'Password sudah diganti.');
    }

    /**
     * Old password is skipped only for the forced change right after login (HAL-AKN-03).
     *
     * @param array<string, mixed> $akun
     */
    private function tanpaLama(array $akun): bool
    {
        return session('tanpa_password_lama') === true && (int) $akun['wajib_ganti_password'] === 1;
    }

    /**
     * @param array<string, string> $galat Message per field
     */
    private function kembali(array $galat): RedirectResponse
    {
        return redirect()->to(url_to('akun.password.ubah'), 303)->with('_ci_validation_errors', $galat);
    }
}
