<?php

namespace App\Controllers\Akun;

use App\Controllers\BaseController;
use App\Filters\Sesi;
use App\Services\Akun\Kredensial;
use App\Services\Akun\Login as LoginService;
use App\Services\Sistem\Jam;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Router\Attributes\Filter;

/**
 * Login and logout (docs/09 HAL-AKN-01, HAL-AKN-02, FS-AKN-01).
 */
class Login extends BaseController
{
    /** Home page per account type (docs/09 RT-18 item 1). */
    private const HOME = ['staf' => '/panel', 'siswa' => '/portal', 'stasiun' => '/kiosk'];

    /** Area a `tujuan` may point into, per account type; never the kiosk (RT-18 item 3). */
    private const AREA = ['staf' => '/panel', 'siswa' => '/portal'];

    public function index(): RedirectResponse|string
    {
        // RT-18 item 4. The area's `sesi` filter sends an ended session back here.
        if (session('akun_id') !== null && isset(self::HOME[session('jenis')])) {
            return redirect()->to(self::HOME[session('jenis')]);
        }

        return view('akun/login', [
            'title'      => 'Login',
            'schoolName' => (new LoginService())->namaSekolah(),
            // R1 has no pengaturan key for the privacy notice yet (docs/08 UI-38, OQ-18).
            'privacyNotice' => '',
        ]);
    }

    public function masuk(): RedirectResponse
    {
        $identitas = (string) $this->request->getPost('identitas');
        $password  = (string) $this->request->getPost('password');

        // docs/11 §5.1
        if (trim($identitas) === '') {
            return $this->gagal($identitas, 'Isi NISN atau username.');
        }
        if ($password === '') {
            return $this->gagal($identitas, 'Isi password.');
        }

        $hasil = (new LoginService())->masuk($identitas, $password, $this->request->getIPAddress());

        if ($hasil['hasil'] === 'dikunci') {
            return $this->gagal($identitas, $this->pesanKunci($hasil['jenis'], $hasil['sampai']->getTimestamp()));
        }
        if ($hasil['hasil'] === 'gagal') {
            return $this->gagal($identitas, 'NISN/username atau password salah.');
        }

        $akun   = $hasil['akun'];
        $tujuan = session('tujuan');
        $now    = (new Jam())->now()->getTimestamp();

        // SEC-14, SEC-16: new session ID, only the login keys of docs/07 ARS-47.
        session()->regenerate(true);
        session()->remove([...Sesi::KEYS, 'tujuan']);
        session()->set([
            'akun_id'  => (int) $akun['id'],
            'jenis'    => $akun['jenis'],
            'login_at' => $now,
            'aktif_at' => $now,
            'cap'      => (new Kredensial())->cap($akun['password_hash']),
        ]);

        if ($akun['jenis'] === 'stasiun') {
            session()->set('login_stasiun_id', $akun['login_stasiun_id']);
            // The station login cookie (docs/07 ARS-30, SEC-18) comes with FASE-06 L06-01.
        }

        // The change-password page then hides the old-password field (FS-AKN-02).
        $wajibGanti = (int) $akun['wajib_ganti_password'] === 1;
        if ($wajibGanti) {
            session()->set('tanpa_password_lama', true);
        }

        service('security')->generateHash(); // SEC-29

        $ke = $wajibGanti ? '/akun/password' : $this->tujuan($tujuan, $akun['jenis']);

        return redirect()->to($ke, 303)->withCookies();
    }

    #[Filter(by: 'hak', having: ['HA-AKN-01'])]
    public function keluar(): RedirectResponse
    {
        // docs/12 SEC-15, SEC-29
        session()->remove(Sesi::KEYS);
        session()->destroy();
        $this->response->deleteCookie(config('Session')->cookieName);
        service('security')->generateHash();

        return redirect()->to('/login', 303)->withCookies();
    }

    /**
     * Back to /login with the message; keeps the identity, never the password (docs/12 SEC-07).
     */
    private function gagal(string $identitas, string $pesan): RedirectResponse
    {
        session()->setFlashdata('_ci_old_input', ['get' => [], 'post' => ['identitas' => $identitas]]);

        return redirect()->to('/login', 303)->with('galat', $pesan);
    }

    /**
     * Lock message of docs/11 §5.1; the time is rounded up to the next minute.
     */
    private function pesanKunci(string $jenis, int $sampai): string
    {
        $jam = format_time((int) ceil($sampai / 60) * 60);

        return match ($jenis) {
            'panjang' => 'Login akun ini dikunci karena terlalu banyak percobaan. Hubungi wali kelas atau admin.',
            'ip'      => "Terlalu banyak percobaan login dari jaringan ini. Coba lagi pukul {$jam}.",
            default   => "Terlalu banyak percobaan login. Coba lagi pukul {$jam}.",
        };
    }

    /**
     * The saved `tujuan` when it is a path inside the account's own area, else home (RT-18 item 3, SEC-39).
     */
    private function tujuan(mixed $tujuan, string $jenis): string
    {
        $area = self::AREA[$jenis] ?? null;

        if (is_string($tujuan) && $area !== null && preg_match('#^' . $area . '(?:[/?]|$)#', $tujuan) === 1) {
            return $tujuan;
        }

        return self::HOME[$jenis];
    }
}
