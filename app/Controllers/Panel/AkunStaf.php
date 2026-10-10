<?php

namespace App\Controllers\Panel;

use App\Controllers\BaseController;
use App\Services\Akun\AkunStaf as AkunStafService;
use App\Services\Sistem\Jam;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Router\Attributes\Filter;

/**
 * Staff accounts (docs/09 HAL-AKN-04, docs/04 FS-AKN-03). Admin only.
 */
class AkunStaf extends BaseController
{
    /** Session key: one-time tokens of forms that show a password (docs/09 RT-09), token => purpose. */
    private const TOKEN = 'token_sekali';

    private const PESAN_TOKEN_TERPAKAI = 'Tindakan ini sudah dijalankan. Password baru tidak dibuat lagi. Bila password belum tercatat, reset sekali lagi.';

    private AkunStafService $akunStaf;

    public function __construct()
    {
        $this->akunStaf = new AkunStafService();
    }

    #[Filter(by: 'hak', having: ['HA-AKN-02'])]
    public function index(): string
    {
        $saringan = [];
        $abaikan  = false;
        $cari     = trim((string) $this->request->getGet('cari'));
        $role     = (string) $this->request->getGet('role');
        $status   = (string) $this->request->getGet('status');
        $page     = (int) $this->request->getGet('page');

        if ($cari !== '') {
            $saringan['cari'] = mb_substr($cari, 0, 100);
        }
        // docs/09 §3.2 item 6: unknown filter values are ignored, with a note.
        if (in_array($role, AkunStafService::roles(), true)) {
            $saringan['role'] = $role;
        } else {
            $abaikan = $role !== '';
        }
        if (in_array($status, ['aktif', 'nonaktif'], true)) {
            $saringan['status'] = $status;
        } elseif ($status !== '') {
            $abaikan = true;
        }

        $page = max(1, $page);

        return view('panel/akun_staf/index', [
            'title'    => 'Akun staf',
            'saringan' => $saringan,
            'abaikan'  => $abaikan,
            'page'     => $page,
            'akunId'   => service('akunAktif')->id(),
        ] + $this->akunStaf->daftar($saringan, $page));
    }

    #[Filter(by: 'hak', having: ['HA-AKN-02'])]
    public function tambah(): string
    {
        helper('form');

        return view('panel/akun_staf/form', [
            'title' => 'Tambah akun staf',
            'akun'  => null,
            'token' => $this->buatToken('tambah'),
        ]);
    }

    #[Filter(by: 'hak', having: ['HA-AKN-02'])]
    public function simpan(): RedirectResponse|string
    {
        if (! $this->pakaiToken('tambah')) {
            return redirect()->to(url_to('panel.akun_staf.index'), 303)->with('galat', self::PESAN_TOKEN_TERPAKAI);
        }

        [$nama, $username, $roles] = $this->isian();
        $hasil = $this->akunStaf->buat($nama, $username, $roles, service('akunAktif')->id(), $this->request->getIPAddress());

        if (isset($hasil['galat'])) {
            return $this->kembaliKeForm(url_to('panel.akun_staf.tambah'), $hasil['galat'], $nama, $username, $roles);
        }

        return $this->halamanPassword($this->akunStaf->cari($hasil['id']), $hasil['password'], true);
    }

    #[Filter(by: 'hak', having: ['HA-AKN-02'])]
    public function lihat(int $id): string
    {
        $akun = $this->akun($id);

        return view('panel/akun_staf/lihat', [
            'title'  => $akun['nama'],
            'akun'   => $akun,
            'kunci'  => $this->akunStaf->kunciLogin($akun),
            'diriku' => $id === service('akunAktif')->id(),
        ]);
    }

    #[Filter(by: 'hak', having: ['HA-AKN-02'])]
    public function ubah(int $id): string
    {
        helper('form');

        return view('panel/akun_staf/form', [
            'title' => 'Ubah akun staf',
            'akun'  => $this->akun($id),
            'token' => null,
        ]);
    }

    #[Filter(by: 'hak', having: ['HA-AKN-02'])]
    public function perbarui(int $id): RedirectResponse
    {
        $this->akun($id);
        [$nama, $username, $roles] = $this->isian();
        $form  = url_to('panel.akun_staf.ubah', $id);
        $hasil = $this->akunStaf->perbarui($id, (string) $this->request->getPost('versi'), $nama, $username, $roles, service('akunAktif')->id(), $this->request->getIPAddress());

        if (isset($hasil['galat'])) {
            return $this->kembaliKeForm($form, $hasil['galat'], $nama, $username, $roles);
        }
        if (isset($hasil['pesan'])) {
            return redirect()->to($form, 303)->with('galat', $hasil['pesan']);
        }
        if (isset($hasil['konflik'])) {
            // docs/11 GAL-05: show the latest data, without the user's input.
            return redirect()->to($form, 303)->with('galat', $this->pesanKonflik($id));
        }

        return redirect()->to(url_to('panel.akun_staf.lihat', $id), 303)->with('sukses', 'Perubahan akun disimpan.');
    }

    #[Filter(by: 'hak', having: ['HA-AKN-02'])]
    public function formNonaktifkan(int $id): RedirectResponse|string
    {
        $akun = $this->akun($id);

        if ($id === service('akunAktif')->id()) {
            return redirect()->to(url_to('panel.akun_staf.lihat', $id), 303)->with('galat', 'Anda tidak dapat menonaktifkan akun sendiri.');
        }
        if ($akun['status'] !== 'aktif') {
            return redirect()->to(url_to('panel.akun_staf.lihat', $id), 303);
        }

        return view('panel/akun_staf/nonaktifkan', ['title' => 'Nonaktifkan akun', 'akun' => $akun]);
    }

    #[Filter(by: 'hak', having: ['HA-AKN-02'])]
    public function nonaktifkan(int $id): RedirectResponse
    {
        $akun  = $this->akun($id);
        $hasil = $this->akunStaf->nonaktifkan($id, service('akunAktif')->id(), $this->request->getIPAddress());
        $detail = redirect()->to(url_to('panel.akun_staf.lihat', $id), 303);

        return isset($hasil['pesan']) ? $detail->with('galat', $hasil['pesan']) : $detail->with('sukses', "Akun {$akun['nama']} sudah dinonaktifkan.");
    }

    #[Filter(by: 'hak', having: ['HA-AKN-02'])]
    public function aktifkan(int $id): RedirectResponse
    {
        $akun  = $this->akun($id);
        $hasil = $this->akunStaf->aktifkan($id, service('akunAktif')->id(), $this->request->getIPAddress());
        $detail = redirect()->to(url_to('panel.akun_staf.lihat', $id), 303);

        return isset($hasil['pesan']) ? $detail->with('galat', $hasil['pesan']) : $detail->with('sukses', "Akun {$akun['nama']} sudah aktif kembali.");
    }

    #[Filter(by: 'hak', having: ['HA-AKN-02'])]
    public function formResetPassword(int $id): string
    {
        $akun = $this->akun($id);

        return view('panel/akun_staf/reset_password', [
            'title' => 'Reset password',
            'akun'  => $akun,
            'token' => $this->buatToken("reset:{$id}"),
        ]);
    }

    #[Filter(by: 'hak', having: ['HA-AKN-02'])]
    public function resetPassword(int $id): RedirectResponse|string
    {
        $akun = $this->akun($id);

        if (! $this->pakaiToken("reset:{$id}")) {
            return redirect()->to(url_to('panel.akun_staf.lihat', $id), 303)->with('galat', self::PESAN_TOKEN_TERPAKAI);
        }

        $password = $this->akunStaf->resetPassword($id, service('akunAktif')->id(), $this->request->getIPAddress());

        return $this->halamanPassword($akun, $password, false);
    }

    #[Filter(by: 'hak', having: ['HA-AKN-02'])]
    public function bukaKunci(int $id): RedirectResponse
    {
        $akun = $this->akun($id);
        $this->akunStaf->bukaKunci($akun, service('akunAktif')->id(), $this->request->getIPAddress());

        return redirect()->to(url_to('panel.akun_staf.lihat', $id), 303)->with('sukses', "Kunci login {$akun['nama']} sudah dibuka.");
    }

    /**
     * Staff account or 404: admins have scope Semua (docs/09 RT-11).
     *
     * @return array<string, mixed>
     */
    private function akun(int $id): array
    {
        return $this->akunStaf->cari($id) ?? throw PageNotFoundException::forPageNotFound();
    }

    /**
     * @return array{string, string, list<mixed>}
     */
    private function isian(): array
    {
        $roles = $this->request->getPost('role');

        return [(string) $this->request->getPost('nama'), (string) $this->request->getPost('username'), is_array($roles) ? $roles : []];
    }

    /**
     * Back to the form with the field errors and old input, without the token
     * (docs/09 RT-08 item 2, docs/12 SEC-07).
     *
     * @param array<string, string> $galat
     * @param list<mixed>           $roles
     */
    private function kembaliKeForm(string $form, array $galat, string $nama, string $username, array $roles): RedirectResponse
    {
        return redirect()->to($form, 303)
            ->with('_ci_old_input', ['get' => [], 'post' => ['nama' => $nama, 'username' => $username, 'role' => $roles]])
            ->with('_ci_validation_errors', $galat);
    }

    /**
     * Password shown once as the direct answer of the write (docs/09 RT-09).
     *
     * @param array<string, mixed> $akun
     */
    private function halamanPassword(array $akun, #[\SensitiveParameter] string $password, bool $baru): string
    {
        // CI4 responses already send `Cache-Control: no-store` (Response::noCache()); the test checks it.
        return view('panel/akun_staf/password', [
            'title'    => $baru ? 'Akun staf dibuat' : 'Password direset',
            'akun'     => $akun,
            'password' => $password,
            'baru'     => $baru,
        ]);
    }

    private function pesanKonflik(int $id): string
    {
        $info = $this->akunStaf->perubahanTerakhir($id);

        if ($info === null || $info['nama'] === null) {
            return 'Data ini sudah diubah orang lain. Periksa data terbaru, lalu simpan lagi bila perlu.';
        }

        $waktu = substr($info['waktu'], 0, 10) === (new Jam())->today()
            ? 'pukul ' . format_time($info['waktu'])
            : 'pada ' . format_datetime($info['waktu']);

        return "Data ini sudah diubah oleh {$info['nama']} {$waktu}. Periksa data terbaru, lalu simpan lagi bila perlu.";
    }

    private function buatToken(string $tujuan): string
    {
        $token  = bin2hex(random_bytes(16));
        $tokens = (array) session(self::TOKEN);
        $tokens[$token] = $tujuan;
        // Keep only the latest few, so abandoned forms do not grow the session.
        session()->set(self::TOKEN, array_slice($tokens, -20, null, true));

        return $token;
    }

    private function pakaiToken(string $tujuan): bool
    {
        $token  = (string) $this->request->getPost(self::TOKEN);
        $tokens = (array) session(self::TOKEN);

        if (($tokens[$token] ?? null) !== $tujuan) {
            return false;
        }

        unset($tokens[$token]);
        session()->set(self::TOKEN, $tokens);

        return true;
    }
}
