<?php

namespace App\Controllers\Panel;

use App\Controllers\BaseController;
use App\Services\MasterData\AtributSiswa as AtributSiswaService;
use App\Services\Sistem\Jam;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Router\Attributes\Filter;

/**
 * Extra student attributes (docs/09 HAL-MD-16, docs/04 FS-MD-09). Admin only.
 */
class AtributSiswa extends BaseController
{
    private AtributSiswaService $atribut;

    public function __construct()
    {
        $this->atribut = new AtributSiswaService();
    }

    #[Filter(by: 'hak', having: ['HA-MD-11'])]
    public function index(): string
    {
        return view('panel/atribut_siswa/index', ['title' => 'Atribut tambahan', 'rows' => $this->atribut->daftar()]);
    }

    #[Filter(by: 'hak', having: ['HA-MD-11'])]
    public function tambah(): string
    {
        helper('form');

        return view('panel/atribut_siswa/form', ['title' => 'Tambah atribut', 'atribut' => null]);
    }

    #[Filter(by: 'hak', having: ['HA-MD-11'])]
    public function simpan(): RedirectResponse
    {
        $isian = $this->isian(['label', 'kode', 'tipe', 'pilihan', 'wajib', 'urutan']);
        $hasil = $this->atribut->buat($isian, service('akunAktif')->id(), $this->request->getIPAddress());

        if (isset($hasil['galat'])) {
            return $this->kembaliKeForm(url_to('panel.atribut_siswa.tambah'), $hasil['galat'], $isian);
        }

        return redirect()->to(url_to('panel.atribut_siswa.index'), 303)->with('sukses', 'Atribut ' . $this->atribut->cari($hasil['id'])['label'] . ' disimpan.');
    }

    #[Filter(by: 'hak', having: ['HA-MD-11'])]
    public function ubah(int $id): string
    {
        helper('form');

        return view('panel/atribut_siswa/form', ['title' => 'Ubah atribut', 'atribut' => $this->cariAtribut($id)]);
    }

    #[Filter(by: 'hak', having: ['HA-MD-11'])]
    public function perbarui(int $id): RedirectResponse
    {
        $this->cariAtribut($id);
        $isian = $this->isian(['label', 'tipe', 'pilihan', 'wajib', 'urutan']);
        $form  = url_to('panel.atribut_siswa.ubah', $id);
        $hasil = $this->atribut->perbarui($id, (string) $this->request->getPost('versi'), $isian, service('akunAktif')->id(), $this->request->getIPAddress());

        if (isset($hasil['galat'])) {
            return $this->kembaliKeForm($form, $hasil['galat'], $isian);
        }
        if (isset($hasil['konflik'])) {
            // docs/11 GAL-05: show the latest data, without the user's input.
            return redirect()->to($form, 303)->with('galat', $this->pesanKonflik($id));
        }

        return redirect()->to(url_to('panel.atribut_siswa.index'), 303)->with('sukses', 'Perubahan atribut disimpan.');
    }

    #[Filter(by: 'hak', having: ['HA-MD-11'])]
    public function sembunyikan(int $id): RedirectResponse
    {
        $atribut = $this->cariAtribut($id);
        $this->atribut->ubahAktif($id, false, service('akunAktif')->id(), $this->request->getIPAddress());

        return redirect()->to(url_to('panel.atribut_siswa.index'), 303)->with('sukses', "Atribut {$atribut['label']} disembunyikan. Nilainya tetap tersimpan.");
    }

    #[Filter(by: 'hak', having: ['HA-MD-11'])]
    public function tampilkan(int $id): RedirectResponse
    {
        $atribut = $this->cariAtribut($id);
        $this->atribut->ubahAktif($id, true, service('akunAktif')->id(), $this->request->getIPAddress());

        return redirect()->to(url_to('panel.atribut_siswa.index'), 303)->with('sukses', "Atribut {$atribut['label']} tampil kembali.");
    }

    #[Filter(by: 'hak', having: ['HA-MD-11'])]
    public function formHapus(int $id): RedirectResponse|string
    {
        $atribut = $this->cariAtribut($id);

        if ((int) $atribut['jumlah_nilai'] > 0) {
            return redirect()->to(url_to('panel.atribut_siswa.index'), 303)->with('galat', 'Atribut ini sudah memiliki nilai. Sembunyikan atribut bila tidak dipakai lagi.');
        }

        return view('panel/atribut_siswa/hapus', ['title' => 'Hapus atribut', 'atribut' => $atribut]);
    }

    #[Filter(by: 'hak', having: ['HA-MD-11'])]
    public function hapus(int $id): RedirectResponse
    {
        $atribut = $this->cariAtribut($id);
        $hasil   = $this->atribut->hapus($id, service('akunAktif')->id(), $this->request->getIPAddress());
        $daftar  = redirect()->to(url_to('panel.atribut_siswa.index'), 303);

        return isset($hasil['pesan']) ? $daftar->with('galat', $hasil['pesan']) : $daftar->with('sukses', "Atribut {$atribut['label']} sudah dihapus.");
    }

    /**
     * Attribute or 404 (docs/09 RT-11).
     *
     * @return array<string, mixed>
     */
    private function cariAtribut(int $id): array
    {
        return $this->atribut->cari($id) ?? throw PageNotFoundException::forPageNotFound();
    }

    /**
     * @param list<string> $kolom
     *
     * @return array<string, string>
     */
    private function isian(array $kolom): array
    {
        return array_map(fn (string $k): string => (string) ($this->request->getPost($k) ?? ''), array_combine($kolom, $kolom));
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
        $info = $this->atribut->perubahanTerakhir($id);

        if ($info === null || $info['nama'] === null) {
            return 'Data ini sudah diubah orang lain. Periksa data terbaru, lalu simpan lagi bila perlu.';
        }

        $waktu = substr($info['waktu'], 0, 10) === (new Jam())->today()
            ? 'pukul ' . format_time($info['waktu'])
            : 'pada ' . format_datetime($info['waktu']);

        return "Data ini sudah diubah oleh {$info['nama']} {$waktu}. Periksa data terbaru, lalu simpan lagi bila perlu.";
    }
}
