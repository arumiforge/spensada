<?php

namespace App\Controllers\Panel;

use App\Controllers\BaseController;
use App\Services\MasterData\Rombel as RombelService;
use App\Services\Sistem\Jam;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Router\Attributes\Filter;

/**
 * Rombel and wali kelas (docs/09 HAL-MD-03, docs/04 FS-MD-03). Admin only.
 * The screen calls a rombel "Kelas".
 */
class Rombel extends BaseController
{
    private RombelService $rombel;

    public function __construct()
    {
        $this->rombel = new RombelService();
    }

    #[Filter(by: 'hak', having: ['HA-MD-02'])]
    public function index(): string
    {
        $semua = $this->rombel->tahunAjaran();
        $tahun = $this->pilihTahun($semua, (string) $this->request->getGet('tahun_ajaran'));

        return view('panel/rombel/index', [
            'title'       => 'Kelas dan wali kelas',
            'tahunAjaran' => $semua,
            'tahun'       => $tahun,
            'rows'        => $tahun === null ? [] : $this->rombel->daftar($tahun, (new Jam())->today()),
        ]);
    }

    #[Filter(by: 'hak', having: ['HA-MD-02'])]
    public function tambah(): RedirectResponse|string
    {
        helper('form');
        $semua = $this->rombel->tahunAjaran();

        if ($semua === []) {
            return redirect()->to(url_to('panel.rombel.index'), 303);
        }

        return view('panel/rombel/form', [
            'title'       => 'Tambah kelas',
            'rombel'      => null,
            'tahunAjaran' => $semua,
            'tahunId'     => (int) $this->pilihTahun($semua, (string) $this->request->getGet('tahun_ajaran'))['id'],
            'wali'        => $this->rombel->pilihanWaliKelas(),
        ]);
    }

    #[Filter(by: 'hak', having: ['HA-MD-02'])]
    public function simpan(): RedirectResponse
    {
        $isian = $this->isian(true);
        $hasil = $this->rombel->buat($isian, service('akunAktif')->id(), $this->request->getIPAddress());

        if (isset($hasil['galat'])) {
            return $this->kembaliKeForm(url_to('panel.rombel.tambah'), $hasil['galat'], $isian);
        }

        return redirect()->to($this->daftar((int) $isian['tahun_ajaran_id']), 303)->with('sukses', 'Kelas ' . $this->rombel->cari($hasil['id'])['nama'] . ' disimpan.');
    }

    #[Filter(by: 'hak', having: ['HA-MD-02'])]
    public function ubah(int $id): string
    {
        helper('form');
        $rombel = $this->cariRombel($id);

        return view('panel/rombel/form', [
            'title'       => 'Ubah kelas',
            'rombel'      => $rombel,
            'tahunAjaran' => [],
            'tahunId'     => (int) $rombel['tahun_ajaran_id'],
            'wali'        => $this->rombel->pilihanWaliKelas($rombel['wali_kelas_id'] === null ? null : (int) $rombel['wali_kelas_id']),
        ]);
    }

    #[Filter(by: 'hak', having: ['HA-MD-02'])]
    public function perbarui(int $id): RedirectResponse
    {
        $rombel = $this->cariRombel($id);
        $isian  = $this->isian(false);
        $form   = url_to('panel.rombel.ubah', $id);
        $hasil  = $this->rombel->perbarui($id, (string) $this->request->getPost('versi'), $isian, service('akunAktif')->id(), $this->request->getIPAddress());

        if (isset($hasil['galat'])) {
            return $this->kembaliKeForm($form, $hasil['galat'], $isian);
        }
        if (isset($hasil['konflik'])) {
            // docs/11 GAL-05: show the latest data, without the user's input.
            return redirect()->to($form, 303)->with('galat', $this->pesanKonflik($id));
        }

        return redirect()->to($this->daftar((int) $rombel['tahun_ajaran_id']), 303)->with('sukses', 'Perubahan kelas disimpan.');
    }

    #[Filter(by: 'hak', having: ['HA-MD-02'])]
    public function formHapus(int $id): RedirectResponse|string
    {
        $rombel = $this->cariRombel($id);

        if ($rombel['punya_penempatan']) {
            return redirect()->to($this->daftar((int) $rombel['tahun_ajaran_id']), 303)->with('galat', 'Kelas ini sudah memiliki siswa, sehingga tidak dapat dihapus.');
        }

        return view('panel/rombel/hapus', ['title' => 'Hapus kelas', 'rombel' => $rombel]);
    }

    #[Filter(by: 'hak', having: ['HA-MD-02'])]
    public function hapus(int $id): RedirectResponse
    {
        $rombel = $this->cariRombel($id);
        $hasil  = $this->rombel->hapus($id, service('akunAktif')->id(), $this->request->getIPAddress());
        $daftar = redirect()->to($this->daftar((int) $rombel['tahun_ajaran_id']), 303);

        return isset($hasil['pesan']) ? $daftar->with('galat', $hasil['pesan']) : $daftar->with('sukses', "Kelas {$rombel['nama']} sudah dihapus.");
    }

    /**
     * Rombel or 404: admins have scope Semua (docs/09 RT-11).
     *
     * @return array<string, mixed>
     */
    private function cariRombel(int $id): array
    {
        return $this->rombel->cari($id) ?? throw PageNotFoundException::forPageNotFound();
    }

    /**
     * The school year asked for in `?tahun_ajaran=`, else the active one,
     * else the newest; an unknown value is ignored (docs/11 VAL-13).
     *
     * @param list<array<string, mixed>> $semua
     *
     * @return array<string, mixed>|null
     */
    private function pilihTahun(array $semua, string $diminta): ?array
    {
        foreach ($semua as $tahun) {
            if ((string) $tahun['id'] === $diminta) {
                return $tahun;
            }
        }
        foreach ($semua as $tahun) {
            if ((int) $tahun['aktif'] === 1) {
                return $tahun;
            }
        }

        return $semua[0] ?? null;
    }

    private function daftar(int $tahunId): string
    {
        return url_to('panel.rombel.index') . '?tahun_ajaran=' . $tahunId;
    }

    /**
     * @return array<string, string>
     */
    private function isian(bool $baru): array
    {
        $kolom = $baru ? ['tahun_ajaran_id', 'nama', 'tingkat', 'wali_kelas_id'] : ['nama', 'tingkat', 'wali_kelas_id'];

        return array_map('strval', array_map(fn ($k) => $this->request->getPost($k) ?? '', array_combine($kolom, $kolom)));
    }

    /**
     * Back to the form with the field errors and old input (docs/09 RT-08 item 2).
     *
     * @param array<string, string> $galat
     * @param array<string, string> $isian
     */
    private function kembaliKeForm(string $form, array $galat, array $isian): RedirectResponse
    {
        return redirect()->to($form, 303)
            ->with('_ci_old_input', ['get' => [], 'post' => $isian])
            ->with('_ci_validation_errors', $galat);
    }

    private function pesanKonflik(int $id): string
    {
        $info = $this->rombel->perubahanTerakhir($id);

        if ($info === null || $info['nama'] === null) {
            return 'Data ini sudah diubah orang lain. Periksa data terbaru, lalu simpan lagi bila perlu.';
        }

        $waktu = substr($info['waktu'], 0, 10) === (new Jam())->today()
            ? 'pukul ' . format_time($info['waktu'])
            : 'pada ' . format_datetime($info['waktu']);

        return "Data ini sudah diubah oleh {$info['nama']} {$waktu}. Periksa data terbaru, lalu simpan lagi bila perlu.";
    }
}
