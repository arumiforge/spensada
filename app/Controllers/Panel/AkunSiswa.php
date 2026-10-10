<?php

namespace App\Controllers\Panel;

use App\Controllers\BaseController;
use App\Services\Akun\AkunSiswa as AkunSiswaService;
use App\Services\Akun\DiLuarHak;
use App\Services\MasterData\IdentitasSekolah;
use App\Services\MasterData\Rujukan;
use App\Services\Sistem\Jam;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Router\Attributes\Filter;

/**
 * Student accounts and slips (docs/09 HAL-AKN-05, HAL-AKN-06, docs/04 FS-AKN-05).
 * Admin: all rombel; wali kelas: their rombel (docs/04 §4.1).
 */
class AkunSiswa extends BaseController
{
    /** Session key: one-time tokens of forms that show a password (docs/09 RT-09), token => purpose. */
    private const TOKEN = 'token_sekali';

    private const PESAN_TOKEN_TERPAKAI = 'Tindakan ini sudah dijalankan. Password baru tidak dibuat lagi. Bila password belum tercatat, reset sekali lagi.';

    private const PESAN_SEMUA_AKTIF = 'Semua akun di kelas ini sudah aktif.';

    private AkunSiswaService $akunSiswa;

    public function __construct()
    {
        $this->akunSiswa = new AkunSiswaService();
    }

    #[Filter(by: 'hak', having: ['HA-AKN-06'])]
    public function index(): string
    {
        $kelas  = (int) $this->request->getGet('kelas');
        $rombel = $kelas > 0 ? $this->akunSiswa->rombel($kelas) : null;

        if ($rombel === null) {
            return view('panel/akun_siswa/index', [
                'title'       => 'Akun siswa',
                'rombel'      => null,
                'daftarKelas' => array_values(array_filter($this->akunSiswa->daftarRombel(), fn (array $r): bool => $this->boleh('HA-AKN-06', $r))),
            ]);
        }

        $this->wajibBoleh('HA-AKN-06', $rombel);

        return view('panel/akun_siswa/index', [
            'title'     => 'Akun siswa',
            'rombel'    => $rombel,
            'rows'      => $this->akunSiswa->daftar((int) $rombel['id']),
            'bolehSlip' => $this->boleh('HA-AKN-05', $rombel),
        ]);
    }

    #[Filter(by: 'hak', having: ['HA-AKN-05'])]
    public function formSlip(): RedirectResponse|string
    {
        $rombel = $this->rombelDipilih((int) $this->request->getGet('kelas'));
        $pilih  = array_map('intval', (array) $this->request->getGet('siswa'));
        $belum  = array_filter($this->akunSiswa->daftar((int) $rombel['id']), static fn (array $r): bool => $r['status'] === 'belum_aktif');
        $daftar = url_to('panel.akun_siswa.index') . '?kelas=' . $rombel['id'];

        if ($belum === []) {
            return redirect()->to($daftar, 303)->with('galat', self::PESAN_SEMUA_AKTIF);
        }

        $terpilih = array_values(array_filter($belum, static fn (array $r): bool => in_array((int) $r['siswa_id'], $pilih, true)));

        if ($terpilih === []) {
            return redirect()->to($daftar, 303)->with('galat', 'Pilih siswa yang akan dibuatkan slip akun.');
        }

        return view('panel/akun_siswa/form_slip', [
            'title'    => 'Buat slip akun',
            'rombel'   => $rombel,
            'terpilih' => $terpilih,
            'token'    => $this->buatToken("slip:{$rombel['id']}"),
        ]);
    }

    #[Filter(by: 'hak', having: ['HA-AKN-05'])]
    public function slip(): RedirectResponse|string
    {
        $rombel = $this->rombelDipilih((int) $this->request->getPost('kelas'));
        $daftar = url_to('panel.akun_siswa.index') . '?kelas=' . $rombel['id'];

        if (! $this->pakaiToken("slip:{$rombel['id']}")) {
            return redirect()->to($daftar, 303)->with('galat', self::PESAN_TOKEN_TERPAKAI);
        }

        $slips = $this->akunSiswa->buatSlip($rombel, array_map('intval', (array) $this->request->getPost('siswa')), service('akunAktif')->id(), $this->request->getIPAddress());

        if ($slips === []) {
            return redirect()->to($daftar, 303)->with('galat', self::PESAN_SEMUA_AKTIF);
        }

        return $this->halamanSlip($slips, 'Slip akun kelas ' . $rombel['nama'], $daftar);
    }

    #[Filter(by: 'hak', having: ['HA-AKN-04'])]
    public function formResetPassword(int $id): RedirectResponse|string
    {
        $siswa = $this->siswa($id);

        if ($siswa['status'] === 'nonaktif') {
            return redirect()->to(url_to('panel.siswa.lihat', $id), 303)->with('galat', "Akun {$siswa['nama']} nonaktif, sehingga password tidak dapat direset.");
        }

        return view('panel/akun_siswa/reset_password', [
            'title' => 'Reset password',
            'siswa' => $siswa,
            'token' => $this->buatToken("reset-siswa:{$id}"),
        ]);
    }

    #[Filter(by: 'hak', having: ['HA-AKN-04'])]
    public function resetPassword(int $id): RedirectResponse|string
    {
        $siswa  = $this->siswa($id);
        $profil = url_to('panel.siswa.lihat', $id);

        if (! $this->pakaiToken("reset-siswa:{$id}")) {
            return redirect()->to($profil, 303)->with('galat', self::PESAN_TOKEN_TERPAKAI);
        }

        $hasil = $this->akunSiswa->resetPassword($siswa, $this->rombelHariIni($id), service('akunAktif')->id(), $this->request->getIPAddress());

        if (isset($hasil['pesan'])) {
            return redirect()->to($profil, 303)->with('galat', $hasil['pesan']);
        }

        return $this->halamanSlip([$hasil['slip']], 'Password direset', $profil);
    }

    #[Filter(by: 'hak', having: ['HA-AKN-04'])]
    public function bukaKunci(int $id): RedirectResponse
    {
        $siswa = $this->siswa($id);
        $this->akunSiswa->bukaKunci($siswa, $this->rombelHariIni($id), service('akunAktif')->id(), $this->request->getIPAddress());

        return redirect()->to(url_to('panel.siswa.lihat', $id), 303)->with('sukses', "Kunci login {$siswa['nama']} sudah dibuka.");
    }

    /**
     * Student in the user's scope for HA-AKN-04 (their rombel today), else 404 or 403.
     *
     * @return array<string, mixed>
     */
    private function siswa(int $id): array
    {
        $siswa = $this->akunSiswa->cariSiswa($id) ?? throw PageNotFoundException::forPageNotFound();

        if (! service('akunAktif')->boleh('HA-AKN-04', service('akunAktif')->dataSiswa($id))) {
            throw new DiLuarHak();
        }

        return $siswa;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function rombelHariIni(int $siswaId): ?array
    {
        return (new Rujukan())->rombelSiswa($siswaId, (new Jam())->today());
    }

    /**
     * Rombel of the active year chosen for a slip, in scope for HA-AKN-05 (AC-AKN-05-05).
     *
     * @return array<string, mixed>
     */
    private function rombelDipilih(int $id): array
    {
        $rombel = $this->akunSiswa->rombel($id) ?? throw PageNotFoundException::forPageNotFound();
        $this->wajibBoleh('HA-AKN-05', $rombel);

        return $rombel;
    }

    /**
     * @param array<string, mixed> $rombel
     */
    private function boleh(string $hak, array $rombel): bool
    {
        return service('akunAktif')->boleh($hak, ['rombel_id' => (int) $rombel['id']]);
    }

    /**
     * @param array<string, mixed> $rombel
     */
    private function wajibBoleh(string $hak, array $rombel): void
    {
        if (! $this->boleh($hak, $rombel)) {
            throw new DiLuarHak();
        }
    }

    /**
     * Slips shown once as the direct answer of the write (docs/09 RT-09,
     * docs/08 UI-57 to UI-59). CI4 responses already send `Cache-Control: no-store`.
     *
     * @param list<array{nama: string, nisn: string, kelas: string, password: string}> $slips
     */
    private function halamanSlip(#[\SensitiveParameter] array $slips, string $title, string $kembali): string
    {
        return view('panel/akun_siswa/slip', [
            'title'       => $title,
            'slips'       => $slips,
            'namaSekolah' => (string) (new IdentitasSekolah())->ambil()['sekolah_nama'],
            'kembali'     => $kembali,
        ]);
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
