<?php

namespace App\Controllers\Panel;

use App\Controllers\BaseController;
use App\Libraries\AppExceptionHandler;
use App\Services\Akun\DiLuarHak;
use App\Services\Akun\HakAkses;
use App\Services\Akun\AkunSiswa;
use App\Services\MasterData\DaftarSiswa;
use App\Services\MasterData\Rujukan;
use App\Services\MasterData\Siswa as SiswaService;
use App\Services\Sistem\Jam;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Router\Attributes\Filter;

/**
 * Student data (docs/09 HAL-MD-04 to HAL-MD-07, HAL-MD-10, docs/10
 * EP-MD-01, docs/04 FS-MD-04). The WA number page is Panel\SiswaWa.
 */
class Siswa extends BaseController
{
    /** Fields of the add and edit forms (docs/04 FS-MD-04). */
    private const ISIAN = ['nisn', 'nama', 'nis', 'jenis_kelamin', 'tanggal_lahir', 'alamat', 'nama_ortu', 'wa_ortu'];

    private SiswaService $siswa;
    private DaftarSiswa $daftar;

    public function __construct()
    {
        $this->siswa  = new SiswaService();
        $this->daftar = new DaftarSiswa();
    }

    #[Filter(by: 'hak', having: ['HA-MD-05'])]
    public function index(): string
    {
        $cakupan  = self::cakupan('HA-MD-05');
        $kelas    = $this->daftar->rombelAktif($cakupan);
        $saringan = [];
        $abaikan  = false;
        $cari     = $this->get('cari');
        $kelasId  = $this->get('kelas');
        $status   = $this->get('status');

        // docs/11 VAL-29: under 2 characters does not search and is not an error.
        if (mb_strlen($cari) >= 2) {
            $saringan['cari'] = mb_substr($cari, 0, 50);
        }
        // docs/11 VAL-13: unknown filter values are ignored, with a note.
        if (in_array($kelasId, array_map('strval', array_column($kelas, 'id')), true)) {
            $saringan['kelas'] = (int) $kelasId;
        } else {
            $abaikan = $kelasId !== '';
        }
        if (in_array($status, DaftarSiswa::STATUS, true)) {
            $saringan['status'] = $status;
        } elseif ($status !== '') {
            $abaikan = true;
        }
        foreach (['tanpa_wa', 'tanpa_foto', 'tanpa_kelas'] as $tanda) {
            if ($this->get($tanda) === '1') {
                $saringan[$tanda] = true;
            }
        }

        $page    = max(1, (int) $this->get('page'));
        $hariIni = (new Jam())->today();
        $hasil   = $this->daftar->daftar($saringan, $cakupan, $hariIni, $page);
        // E6: "Belum ada siswa" only when the scope holds no student at all.
        $kosong = $hasil['total'] === 0 && $this->daftar->daftar(['status' => 'semua'], $cakupan, $hariIni, 1, 1)['total'] === 0;

        return view('panel/siswa/index', [
            'title'    => 'Data siswa',
            'saringan' => $saringan,
            'cari'     => $cari,
            'abaikan'  => $abaikan,
            'kelas'    => $kelas,
            'page'     => $page,
            'kosong'   => $kosong,
            'admin'    => service('akunAktif')->has('HA-MD-03'),
        ] + $hasil);
    }

    /**
     * Search fragment (docs/10 EP-MD-01, docs/09 RT-14). Only `untuk=profil`
     * exists in FASE-02; the other targets come with their pages.
     */
    #[Filter(by: 'hak', having: ['HA-MD-05', 'HA-PRS-03', 'HA-IZN-02', 'HA-LAP-04'])]
    public function cari(): ResponseInterface
    {
        $cari     = $this->get('cari');
        $akun     = service('akunAktif');
        $panjang  = mb_strlen($cari);

        if ($this->get('untuk') !== 'profil' || $panjang < 2 || $panjang > 50) {
            return AppExceptionHandler::prepare(400, $this->request, $this->response);
        }
        if (! $akun->has('HA-MD-05')) {
            throw new DiLuarHak();
        }

        // docs/12 SEC-54: panel form helpers, 120 requests per minute per account.
        $throttler = service('throttler');
        if (! $throttler->check('cari-siswa-' . $akun->id(), 120, MINUTE)) {
            return AppExceptionHandler::prepare(429, $this->request, $this->response)
                ->setHeader('Retry-After', (string) max(1, $throttler->getTokenTime()));
        }

        $hasil = $this->daftar->cari($cari, self::cakupan('HA-MD-05'), (new Jam())->today());

        return $this->response->setContentType('text/html')
            ->setBody(view('panel/siswa/_hasil_cari', $hasil, ['saveData' => false]));
    }

    #[Filter(by: 'hak', having: ['HA-MD-03'])]
    public function tambah(): string
    {
        helper('form');
        $hariIni = (new Jam())->today();

        return view('panel/siswa/form', [
            'title'   => 'Tambah siswa',
            'siswa'   => null,
            'atribut' => $this->daftar->atribut(null),
            'rombel'  => $this->daftar->pilihanRombel($hariIni),
            'hariIni' => $hariIni,
        ]);
    }

    #[Filter(by: 'hak', having: ['HA-MD-03'])]
    public function simpan(): RedirectResponse
    {
        $isian = $this->isian() + [
            'rombel_id'     => $this->post('rombel_id'),
            'tanggal_mulai' => $this->post('tanggal_mulai'),
        ];
        $hasil = $this->siswa->buat($isian, service('akunAktif')->id());

        if (isset($hasil['galat'])) {
            return $this->kembaliKeForm(url_to('panel.siswa.tambah'), $hasil['galat'], $isian);
        }

        $nama = $this->daftar->satu($hasil['id'])['nama'];

        return redirect()->to(url_to('panel.siswa.lihat', $hasil['id']), 303)
            ->with('sukses', "{$nama} sudah ditambahkan. Akun siswanya belum aktif sampai slip akun dicetak.");
    }

    #[Filter(by: 'hak', having: ['HA-MD-05'])]
    public function lihat(int $id): string
    {
        $siswa   = $this->siswaDalamCakupan($id, 'HA-MD-05');
        $akun    = service('akunAktif');
        $data    = $akun->dataSiswa($id);
        $hariIni = (new Jam())->today();
        $rujukan = new Rujukan();
        $akunSiswa = $akun->boleh('HA-AKN-06', $data) ? $this->daftar->akun($id) : null;
        $masaAktif = $this->daftar->masaAktif($id);
        // HA-AKN-04: reset password (not for a nonaktif account) and unlock (docs/09 HAL-AKN-06).
        $layananAkun = new AkunSiswa();
        $akunReset   = $akun->boleh('HA-AKN-04', $data) ? $layananAkun->cariSiswa($id) : null;

        return view('panel/siswa/lihat', [
            'title'      => $siswa['nama'],
            'siswa'      => $siswa,
            'status'     => $rujukan->statusSiswa($id, $hariIni),
            'rombel'     => $rujukan->rombelSiswa($id, $hariIni),
            'masaAktif'  => $masaAktif,
            'terbuka'    => in_array(null, array_column($masaAktif, 'tanggal_selesai'), true),
            'penempatan' => $this->daftar->riwayatPenempatan($id),
            'atribut'    => $this->daftar->atribut($id),
            'akunSiswa'  => $akunSiswa,
            'kunci'      => $akunReset === null ? null : $layananAkun->kunciLogin($akunReset),
            'hak'        => [
                'ubah'  => $akun->has('HA-MD-03'),
                'wa'    => $akun->boleh('HA-MD-06', $data),
                'log'   => $akun->boleh('HA-MD-10', $data),
                'reset' => $akunReset !== null && $akunReset['status'] !== 'nonaktif',
            ],
        ]);
    }

    #[Filter(by: 'hak', having: ['HA-MD-03'])]
    public function ubah(int $id): string
    {
        helper('form');

        return view('panel/siswa/form', [
            'title'   => 'Ubah data siswa',
            'siswa'   => $this->siswaDalamCakupan($id, 'HA-MD-03'),
            'atribut' => $this->daftar->atribut($id),
            'rombel'  => [],
            'hariIni' => (new Jam())->today(),
        ]);
    }

    #[Filter(by: 'hak', having: ['HA-MD-03'])]
    public function perbarui(int $id): RedirectResponse
    {
        $lama  = $this->siswaDalamCakupan($id, 'HA-MD-03');
        $isian = $this->isian();
        $form  = url_to('panel.siswa.ubah', $id);
        $hasil = $this->siswa->perbarui($id, $this->post('versi'), $isian, service('akunAktif')->id());

        if (isset($hasil['galat'])) {
            return $this->kembaliKeForm($form, $hasil['galat'], $isian);
        }
        if (isset($hasil['konflik'])) {
            // docs/11 GAL-05: show the latest data, without the user's input.
            return redirect()->to($form, 303)->with('galat', $this->pesanKonflik($id));
        }

        $pesan = 'Perubahan data siswa disimpan.';
        if (trim($isian['nisn']) !== $lama['nisn']) {
            // docs/04 FS-MD-04 item 2, C-04.
            $pesan .= ' Username akun siswa ikut berganti ke NISN baru. Cetak ulang kartu, karena QR di kartu harus berisi NISN baru.';
        }

        return redirect()->to(url_to('panel.siswa.lihat', $id), 303)->with('sukses', $pesan);
    }

    #[Filter(by: 'hak', having: ['HA-MD-03'])]
    public function formNonaktifkan(int $id): RedirectResponse|string
    {
        helper('form');
        $siswa   = $this->siswaDalamCakupan($id, 'HA-MD-03');
        $periode = $this->periodeTerbuka($id);

        if ($periode === null) {
            return redirect()->to(url_to('panel.siswa.lihat', $id), 303)->with('galat', 'Siswa ini sudah nonaktif.');
        }

        $hariIni = (new Jam())->today();

        return view('panel/siswa/nonaktifkan', [
            'title'      => 'Nonaktifkan siswa',
            'siswa'      => $siswa,
            'periode'    => $periode,
            'belumMulai' => $periode['tanggal_mulai'] > $hariIni,
            'hariIni'    => $hariIni,
        ]);
    }

    #[Filter(by: 'hak', having: ['HA-MD-03'])]
    public function nonaktifkan(int $id): RedirectResponse
    {
        $siswa = $this->siswaDalamCakupan($id, 'HA-MD-03');
        $isian = [
            'tanggal_selesai'     => $this->post('tanggal_selesai'),
            'alasan_nonaktif'     => $this->post('alasan_nonaktif'),
            'keterangan_nonaktif' => $this->post('keterangan_nonaktif'),
        ];
        $hasil  = $this->siswa->nonaktifkan($id, (int) $this->post('periode_id'), $isian, service('akunAktif')->id());
        $profil = redirect()->to(url_to('panel.siswa.lihat', $id), 303);

        if (isset($hasil['galat'])) {
            return $this->kembaliKeForm(url_to('panel.siswa.form_nonaktifkan', $id), $hasil['galat'], $isian);
        }
        if (isset($hasil['pesan'])) {
            return $profil->with('galat', $hasil['pesan']);
        }

        return $profil->with('sukses', $hasil['dibatalkan']
            ? "Masa aktif {$siswa['nama']} dibatalkan, dan akunnya nonaktif."
            : "{$siswa['nama']} sudah dinonaktifkan, dan akunnya nonaktif.");
    }

    #[Filter(by: 'hak', having: ['HA-MD-03'])]
    public function formAktifkan(int $id): RedirectResponse|string
    {
        helper('form');
        $siswa = $this->siswaDalamCakupan($id, 'HA-MD-03');

        if ($this->periodeTerbuka($id) !== null) {
            return redirect()->to(url_to('panel.siswa.lihat', $id), 303)->with('galat', 'Siswa ini sudah aktif.');
        }

        $hariIni = (new Jam())->today();

        return view('panel/siswa/aktifkan', [
            'title'   => 'Aktifkan kembali',
            'siswa'   => $siswa,
            'rombel'  => $this->daftar->pilihanRombel($hariIni),
            'hariIni' => $hariIni,
        ]);
    }

    #[Filter(by: 'hak', having: ['HA-MD-03'])]
    public function aktifkan(int $id): RedirectResponse
    {
        $siswa  = $this->siswaDalamCakupan($id, 'HA-MD-03');
        $isian  = ['tanggal_mulai' => $this->post('tanggal_mulai'), 'rombel_id' => $this->post('rombel_id')];
        $hasil  = $this->siswa->aktifkan($id, $isian, service('akunAktif')->id());
        $profil = redirect()->to(url_to('panel.siswa.lihat', $id), 303);

        if (isset($hasil['galat'])) {
            return $this->kembaliKeForm(url_to('panel.siswa.form_aktifkan', $id), $hasil['galat'], $isian);
        }
        if (isset($hasil['pesan'])) {
            return $profil->with('galat', $hasil['pesan']);
        }

        return $profil->with('sukses', "{$siswa['nama']} aktif kembali mulai " . format_date($isian['tanggal_mulai']) . '.');
    }

    /**
     * Rombel IDs a list may show for the right: null for scope Semua, else
     * the rombel the account is wali kelas of (docs/04 §4.1 item 3).
     *
     * @return list<int>|null
     */
    public static function cakupan(string $hak): ?array
    {
        $akun = service('akunAktif');

        return array_intersect((new HakAkses())->scopes($akun->roles(), $hak), ['semua', 'ya']) !== []
            ? null
            : $akun->aktor()['homeroom_rombel_ids'];
    }

    /**
     * The student when in the account's scope for the right (their rombel
     * today, docs/04 §4.1 item 4). A missing ID is 404 for scope Semua and
     * 403 for a limited scope, so existence is not revealed (docs/09 RT-11).
     *
     * @return array<string, mixed>
     */
    public static function siswaDalamCakupan(int $id, string $hak): array
    {
        $akun  = service('akunAktif');
        $siswa = (new DaftarSiswa())->satu($id);

        if ($siswa === null) {
            throw self::cakupan($hak) === null ? PageNotFoundException::forPageNotFound() : new DiLuarHak();
        }
        if (! $akun->boleh($hak, $akun->dataSiswa($id))) {
            throw new DiLuarHak();
        }

        return $siswa;
    }

    /**
     * Conflict message with who changed the data and when (docs/04 §4.6, docs/11 GAL-05).
     */
    public static function pesanKonflik(int $id): string
    {
        $info = (new SiswaService())->perubahanTerakhir($id);

        if ($info === null || $info['nama'] === null) {
            return 'Data ini sudah diubah orang lain. Periksa data terbaru, lalu simpan lagi bila perlu.';
        }

        $waktu = substr($info['waktu'], 0, 10) === (new Jam())->today()
            ? 'pukul ' . format_time($info['waktu'])
            : 'pada ' . format_datetime($info['waktu']);

        return "Data ini sudah diubah oleh {$info['nama']} {$waktu}. Periksa data terbaru, lalu simpan lagi bila perlu.";
    }

    /**
     * @return array<string, mixed>|null
     */
    private function periodeTerbuka(int $id): ?array
    {
        foreach ($this->daftar->masaAktif($id) as $periode) {
            if ($periode['tanggal_selesai'] === null) {
                return $periode;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function isian(): array
    {
        $isian = [];
        foreach (self::ISIAN as $field) {
            $isian[$field] = $this->post($field);
        }
        $atribut          = $this->request->getPost('atribut');
        $isian['atribut'] = is_array($atribut) ? $atribut : [];

        return $isian;
    }

    /**
     * Back to the form with the field errors and old input (docs/09 RT-08 item 2).
     *
     * @param array<string, string> $galat
     * @param array<string, mixed>  $isian
     */
    private function kembaliKeForm(string $form, array $galat, array $isian): RedirectResponse
    {
        return redirect()->to($form, 303)
            ->with('_ci_old_input', ['get' => [], 'post' => $isian])
            ->with('_ci_validation_errors', $galat);
    }

    private function post(string $name): string
    {
        $value = $this->request->getPost($name);

        return is_string($value) ? $value : '';
    }

    private function get(string $name): string
    {
        $value = $this->request->getGet($name);

        return is_string($value) ? trim($value) : '';
    }
}
