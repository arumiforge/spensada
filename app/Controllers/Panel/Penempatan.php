<?php

namespace App\Controllers\Panel;

use App\Controllers\BaseController;
use App\Models\SiswaModel;
use App\Services\MasterData\Penempatan as PenempatanService;
use App\Services\MasterData\Rujukan;
use App\Services\Sistem\Jam;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Router\Attributes\Filter;

/**
 * Placements (docs/09 HAL-MD-11 move one student, HAL-MD-12 per class;
 * docs/04 FS-MD-05). Admin only. Import (HAL-MD-13) comes in FASE-03.
 */
class Penempatan extends BaseController
{
    private PenempatanService $penempatan;

    public function __construct()
    {
        $this->penempatan = new PenempatanService();
    }

    #[Filter(by: 'hak', having: ['HA-MD-03'])]
    public function tambah(int $id): string
    {
        helper('form');
        $siswa = $this->siswa($id);

        return view('panel/penempatan/tambah', [
            'title'    => 'Pindah kelas',
            'siswa'    => $siswa,
            'sekarang' => (new Rujukan())->rombelSiswa($id, (new Jam())->today()),
            'rombel'   => $this->penempatan->daftarRombel(),
            'hariIni'  => (new Jam())->today(),
        ]);
    }

    #[Filter(by: 'hak', having: ['HA-MD-03'])]
    public function simpan(int $id): RedirectResponse
    {
        $siswa   = $this->siswa($id);
        $rombel  = trim((string) $this->request->getPost('rombel_id'));
        $tanggal = trim((string) $this->request->getPost('tanggal_mulai'));
        $hasil   = $this->penempatan->tempatkan($id, $rombel, $tanggal, service('akunAktif')->id());

        if (isset($hasil['galat'])) {
            return $this->kembali(url_to('panel.penempatan.tambah', $id), $hasil['galat'], ['rombel_id' => $rombel, 'tanggal_mulai' => $tanggal]);
        }

        return redirect()->to(url_to('panel.siswa.lihat', $id), 303)
            ->with('sukses', "{$siswa['nama']} ditempatkan di kelas {$hasil['rombel']['nama']} mulai " . format_date($tanggal) . '.');
    }

    #[Filter(by: 'hak', having: ['HA-MD-03'])]
    public function index(): string
    {
        return $this->halamanKelas(null, null);
    }

    #[Filter(by: 'hak', having: ['HA-MD-03'])]
    public function kelas(): string
    {
        // docs/11 VAL-13: unknown IDs are ignored, so the pickers show empty.
        $pilih = fn (string $param): ?array => ctype_digit($v = (string) $this->request->getGet($param)) ? $this->penempatan->rombel((int) $v) : null;

        return $this->halamanKelas($pilih('asal'), $pilih('tujuan'));
    }

    #[Filter(by: 'hak', having: ['HA-MD-03'])]
    public function simpanKelas(): RedirectResponse|string
    {
        $asal    = trim((string) $this->request->getPost('asal'));
        $tujuan  = trim((string) $this->request->getPost('tujuan'));
        $tanggal = trim((string) $this->request->getPost('tanggal_mulai'));
        $siswa   = $this->request->getPost('siswa');
        $siswa   = is_array($siswa) ? array_values($siswa) : [];
        $form    = url_to('panel.penempatan.kelas') . '?' . http_build_query(['asal' => $asal, 'tujuan' => $tujuan]);

        // docs/09 RT-07: the first request only shows the impact; `konfirmasi=1` checks again and saves.
        if ($this->request->getPost('konfirmasi') !== '1') {
            $hasil = $this->penempatan->periksaKelas($asal, $tujuan, $tanggal, $siswa);

            if (isset($hasil['galat'])) {
                return $this->kembali($form, $hasil['galat'], ['tanggal_mulai' => $tanggal, 'siswa' => $siswa]);
            }

            $title = $hasil['siap'] === [] ? 'Tidak ada siswa yang dapat ditempatkan' : 'Tempatkan ' . format_number(count($hasil['siap'])) . " siswa di kelas {$hasil['tujuan']['nama']}?";

            return view('panel/penempatan/periksa', ['title' => $title, 'tanggal' => $tanggal, 'form' => $form] + $hasil);
        }

        $hasil = $this->penempatan->simpanKelas($asal, $tujuan, $tanggal, $siswa, service('akunAktif')->id(), $this->request->getIPAddress());

        if (isset($hasil['galat'])) {
            return $this->kembali($form, $hasil['galat'], ['tanggal_mulai' => $tanggal, 'siswa' => $siswa]);
        }
        if ($hasil['jumlah'] === 0) {
            return redirect()->to($form, 303)->with('galat', 'Tidak ada siswa yang ditempatkan. ' . $hasil['gagal'][0]);
        }

        $pesan = format_number($hasil['jumlah']) . " siswa ditempatkan di kelas {$hasil['tujuan']['nama']} mulai " . format_date($tanggal) . '.';
        if ($hasil['gagal'] !== []) {
            $pesan .= ' ' . format_number(count($hasil['gagal'])) . ' siswa dilewati.';
        }

        return redirect()->to(url_to('panel.penempatan.index'), 303)->with('sukses', $pesan);
    }

    /**
     * Class pickers, and the students of the source class once both classes
     * are chosen (HAL-MD-12).
     *
     * @param array<string, mixed>|null $asal
     * @param array<string, mixed>|null $tujuan
     */
    private function halamanKelas(?array $asal, ?array $tujuan): string
    {
        helper('form');
        $sama = $asal !== null && $tujuan !== null && $asal['id'] === $tujuan['id'];

        return view('panel/penempatan/kelas', [
            'title'  => 'Penempatan kelas',
            'rombel' => $this->penempatan->daftarRombel(),
            'asal'   => $asal,
            'tujuan' => $tujuan,
            'sama'   => $sama,
            'siswa'  => $asal !== null && $tujuan !== null && ! $sama ? $this->penempatan->siswaRombel($asal) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function siswa(int $id): array
    {
        return model(SiswaModel::class)->find($id) ?? throw PageNotFoundException::forPageNotFound();
    }

    /**
     * Back to the form with the field errors and old input (docs/09 RT-08 item 2).
     *
     * @param array<string, string> $galat
     * @param array<string, mixed>  $isian
     */
    private function kembali(string $form, array $galat, array $isian): RedirectResponse
    {
        return redirect()->to($form, 303)
            ->with('_ci_old_input', ['get' => [], 'post' => $isian])
            ->with('_ci_validation_errors', $galat);
    }
}
