<?php

namespace App\Controllers\Panel;

use App\Controllers\BaseController;
use App\Services\MasterData\TahunAjaran as TahunAjaranService;
use App\Services\Sistem\Jam;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Router\Attributes\Filter;

/**
 * School years and semesters (docs/09 HAL-MD-02, docs/04 FS-MD-02). Admin only.
 */
class TahunAjaran extends BaseController
{
    private TahunAjaranService $tahunAjaran;

    public function __construct()
    {
        $this->tahunAjaran = new TahunAjaranService();
    }

    #[Filter(by: 'hak', having: ['HA-MD-01'])]
    public function index(): string
    {
        return view('panel/tahun_ajaran/index', ['title' => 'Tahun ajaran', 'rows' => $this->tahunAjaran->daftar()]);
    }

    #[Filter(by: 'hak', having: ['HA-MD-01'])]
    public function tambah(): string
    {
        helper('form');

        return view('panel/tahun_ajaran/form', ['title' => 'Tambah tahun ajaran', 'ta' => null]);
    }

    #[Filter(by: 'hak', having: ['HA-MD-01'])]
    public function simpan(): RedirectResponse
    {
        $isian = $this->isian();
        $form  = url_to('panel.tahun_ajaran.tambah');
        $hasil = $this->tahunAjaran->buat($isian, service('akunAktif')->id(), $this->request->getIPAddress());

        if (isset($hasil['galat'])) {
            return $this->kembaliKeForm($form, $hasil['galat'], $isian);
        }
        if (isset($hasil['pesan'])) {
            return redirect()->to($form, 303)->with('galat', $hasil['pesan']);
        }

        return redirect()->to(url_to('panel.tahun_ajaran.index'), 303)->with('sukses', 'Tahun ajaran ' . trim($isian['nama']) . ' disimpan.');
    }

    #[Filter(by: 'hak', having: ['HA-MD-01'])]
    public function ubah(int $id): string
    {
        helper('form');
        $ta = $this->ta($id);

        return view('panel/tahun_ajaran/form', ['title' => 'Ubah tahun ajaran', 'ta' => $ta]);
    }

    #[Filter(by: 'hak', having: ['HA-MD-01'])]
    public function perbarui(int $id): RedirectResponse|string
    {
        $this->ta($id);
        $isian = $this->isian();
        $versi = (string) $this->request->getPost('versi');
        $form  = url_to('panel.tahun_ajaran.ubah', $id);
        $hasil = $this->tahunAjaran->perbarui($id, $versi, $isian, $this->request->getPost('konfirmasi') === '1', service('akunAktif')->id(), $this->request->getIPAddress());

        if (isset($hasil['galat'])) {
            return $this->kembaliKeForm($form, $hasil['galat'], $isian);
        }
        if (isset($hasil['pesan'])) {
            return redirect()->to($form, 303)->with('galat', $hasil['pesan']);
        }
        if (isset($hasil['konflik'])) {
            // docs/11 GAL-05: show the latest data, without the user's input.
            return redirect()->to($form, 303)->with('galat', $this->pesanKonflik($id));
        }
        if (isset($hasil['periksa'])) {
            // docs/09 RT-07: nothing saved yet; the check page re-sends the same fields.
            return view('panel/tahun_ajaran/periksa', [
                'title'   => 'Periksa perubahan semester',
                'id'      => $id,
                'versi'   => $versi,
                'isian'   => $isian,
                'rentang' => $hasil['periksa'],
                'jumlah'  => $hasil['jumlah'],
            ]);
        }

        return redirect()->to(url_to('panel.tahun_ajaran.index'), 303)->with('sukses', 'Perubahan tahun ajaran disimpan.');
    }

    #[Filter(by: 'hak', having: ['HA-MD-01'])]
    public function formAktifkan(int $id): RedirectResponse|string
    {
        $ta    = $this->ta($id);
        $daftar = redirect()->to(url_to('panel.tahun_ajaran.index'), 303);

        if ((int) $ta['aktif'] === 1) {
            return $daftar;
        }
        if (($pesan = $this->tahunAjaran->galatAktifkan($ta)) !== null) {
            return $daftar->with('galat', $pesan);
        }

        return view('panel/tahun_ajaran/aktifkan', ['title' => 'Aktifkan tahun ajaran', 'ta' => $ta]);
    }

    #[Filter(by: 'hak', having: ['HA-MD-01'])]
    public function aktifkan(int $id): RedirectResponse
    {
        $ta     = $this->ta($id);
        $hasil  = $this->tahunAjaran->aktifkan($id, service('akunAktif')->id(), $this->request->getIPAddress());
        $daftar = redirect()->to(url_to('panel.tahun_ajaran.index'), 303);

        return isset($hasil['pesan']) ? $daftar->with('galat', $hasil['pesan']) : $daftar->with('sukses', "Tahun ajaran {$ta['nama']} sekarang aktif.");
    }

    #[Filter(by: 'hak', having: ['HA-MD-01'])]
    public function formHapus(int $id): RedirectResponse|string
    {
        $ta = $this->ta($id);

        if ($ta['jumlah_rombel'] > 0) {
            return redirect()->to(url_to('panel.tahun_ajaran.index'), 303)->with('galat', 'Tahun ajaran ini sudah memiliki kelas, sehingga tidak dapat dihapus.');
        }

        return view('panel/tahun_ajaran/hapus', ['title' => 'Hapus tahun ajaran', 'ta' => $ta]);
    }

    #[Filter(by: 'hak', having: ['HA-MD-01'])]
    public function hapus(int $id): RedirectResponse
    {
        $ta     = $this->ta($id);
        $hasil  = $this->tahunAjaran->hapus($id, (string) $this->request->getPost('versi'), service('akunAktif')->id(), $this->request->getIPAddress());
        $daftar = redirect()->to(url_to('panel.tahun_ajaran.index'), 303);

        if (isset($hasil['pesan'])) {
            return $daftar->with('galat', $hasil['pesan']);
        }
        if (isset($hasil['konflik'])) {
            return redirect()->to(url_to('panel.tahun_ajaran.form_hapus', $id), 303)->with('galat', $this->pesanKonflik($id));
        }

        return $daftar->with('sukses', "Tahun ajaran {$ta['nama']} dihapus.");
    }

    /**
     * School year or 404.
     *
     * @return array<string, mixed>
     */
    private function ta(int $id): array
    {
        return $this->tahunAjaran->cari($id) ?? throw PageNotFoundException::forPageNotFound();
    }

    /**
     * Only the form fields go to the service (docs/11 VAL-04).
     *
     * @return array<string, string>
     */
    private function isian(): array
    {
        $isian = [];

        foreach (array_keys(TahunAjaranService::LABEL) as $kolom) {
            $isian[$kolom] = (string) $this->request->getPost($kolom);
        }

        return $isian;
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
        $info = $this->tahunAjaran->perubahanTerakhir($id);

        if ($info === null || $info['nama'] === null) {
            return 'Data ini sudah diubah orang lain. Periksa data terbaru, lalu simpan lagi bila perlu.';
        }

        $waktu = substr($info['waktu'], 0, 10) === (new Jam())->today()
            ? 'pukul ' . format_time($info['waktu'])
            : 'pada ' . format_datetime($info['waktu']);

        return "Data ini sudah diubah oleh {$info['nama']} {$waktu}. Periksa data terbaru, lalu simpan lagi bila perlu.";
    }
}
