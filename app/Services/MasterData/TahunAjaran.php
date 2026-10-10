<?php

namespace App\Services\MasterData;

use App\Models\SemesterModel;
use App\Models\TahunAjaranModel;
use App\Services\Akun\LogAktivitas;
use App\Services\Sistem\Jam;
use App\Services\Sistem\NamedLock;
use App\Services\Sistem\Transaction;
use CodeIgniter\Database\Exceptions\DatabaseException;
use DateTimeImmutable;

/**
 * School years and their two semesters (docs/04 FS-MD-02, docs/06 §6.2, §6.3).
 *
 * Every change runs under the named lock `tahun_ajaran` (docs/07 ARS-42), so
 * the range overlap check and the single active year hold under concurrent
 * requests. Changes return `['galat' => [field => message]]` for field
 * errors, `['pesan' => text]` for a refused action, `['konflik' => true]`
 * when the version token no longer matches (ARS-40), `['periksa' => ranges]`
 * when a semester change removes dates and needs confirmation (docs/09
 * RT-07), or the result.
 *
 * Form fields: nama, tanggal_mulai, tanggal_selesai, ganjil_mulai,
 * ganjil_selesai, genap_mulai, genap_selesai (`Y-m-d`).
 */
class TahunAjaran
{
    private const KUNCI = 'tahun_ajaran';

    public const LABEL = [
        'nama'            => 'Nama tahun ajaran',
        'tanggal_mulai'   => 'Tanggal mulai',
        'tanggal_selesai' => 'Tanggal selesai',
        'ganjil_mulai'    => 'Mulai semester ganjil',
        'ganjil_selesai'  => 'Selesai semester ganjil',
        'genap_mulai'     => 'Mulai semester genap',
        'genap_selesai'   => 'Selesai semester genap',
    ];

    private const PESAN_TERKUNCI = 'Data tahun ajaran sedang diubah di tempat lain. Coba lagi beberapa saat lagi.';
    private const PESAN_ADA_KELAS = 'Tahun ajaran ini sudah memiliki kelas, sehingga tidak dapat dihapus.';

    private Jam $jam;

    public function __construct(?Jam $jam = null)
    {
        $this->jam = $jam ?? new Jam();
    }

    /**
     * All school years, newest first, each with `ganjil`, `genap` and `jumlah_rombel`.
     *
     * @return list<array<string, mixed>>
     */
    public function daftar(): array
    {
        $rows = db_connect()->table('tahun_ajaran ta')
            ->select('ta.*, (SELECT COUNT(*) FROM rombel r WHERE r.tahun_ajaran_id = ta.id) AS jumlah_rombel')
            ->orderBy('ta.tanggal_mulai', 'DESC')->get()->getResultArray();

        return array_map(fn (array $row): array => $this->denganSemester($row), $rows);
    }

    /**
     * One school year with `ganjil`, `genap` and `jumlah_rombel`, or null.
     *
     * @return array<string, mixed>|null
     */
    public function cari(int $id): ?array
    {
        $row = model(TahunAjaranModel::class)->find($id);

        if ($row === null) {
            return null;
        }

        $row['jumlah_rombel'] = db_connect()->table('rombel')->where('tahun_ajaran_id', $id)->countAllResults();

        return $this->denganSemester($row);
    }

    /**
     * The form fields of a stored year, in the shape the form posts.
     *
     * @param array<string, mixed> $ta Row from cari()
     *
     * @return array<string, string>
     */
    public static function isian(array $ta): array
    {
        return [
            'nama'            => $ta['nama'],
            'tanggal_mulai'   => $ta['tanggal_mulai'],
            'tanggal_selesai' => $ta['tanggal_selesai'],
            'ganjil_mulai'    => $ta['ganjil']['tanggal_mulai'] ?? '',
            'ganjil_selesai'  => $ta['ganjil']['tanggal_selesai'] ?? '',
            'genap_mulai'     => $ta['genap']['tanggal_mulai'] ?? '',
            'genap_selesai'   => $ta['genap']['tanggal_selesai'] ?? '',
        ];
    }

    /**
     * Who made the latest logged change to the year and when (docs/04 §4.6).
     *
     * @return array{nama: string|null, waktu: string}|null
     */
    public function perubahanTerakhir(int $id): ?array
    {
        $row = db_connect()->table('log_aktivitas l')
            ->select('p.nama, l.created_at')
            ->join('akun p', 'p.id = l.pelaku_id', 'left')
            ->where('l.jenis', 'tahun_ajaran_diubah')
            ->where("JSON_EXTRACT(l.data, '$.tahun_ajaran_id') =", $id)
            ->orderBy('l.id', 'DESC')
            ->limit(1)->get()->getRowArray();

        return $row === null ? null : ['nama' => $row['nama'], 'waktu' => $row['created_at']];
    }

    /**
     * Adds an inactive school year with both semesters (FS-MD-02 item 1).
     *
     * @param array<string, mixed> $isian
     *
     * @return array{galat: array<string, string>}|array{pesan: string}|array{id: int}
     */
    public function buat(array $isian, int $pelakuId, ?string $ip): array
    {
        [$data, $galat] = $this->periksa($isian);

        if ($galat !== []) {
            return ['galat' => $galat];
        }

        return $this->denganKunci(fn (): array => (new Transaction())->run(function () use ($data, $pelakuId, $ip): array {
            if (($galat = $this->periksaData($data, null)) !== []) {
                return ['galat' => $galat];
            }

            $id = (int) model(TahunAjaranModel::class)->insert([
                'nama'            => $data['nama'],
                'tanggal_mulai'   => $data['tanggal_mulai'],
                'tanggal_selesai' => $data['tanggal_selesai'],
                'aktif'           => 0,
            ]);

            foreach (['ganjil', 'genap'] as $jenis) {
                model(SemesterModel::class)->insert([
                    'tahun_ajaran_id' => $id,
                    'jenis'           => $jenis,
                    'tanggal_mulai'   => $data["{$jenis}_mulai"],
                    'tanggal_selesai' => $data["{$jenis}_selesai"],
                ]);
            }

            (new LogAktivitas())->catat('tahun_ajaran_diubah', $pelakuId, null, ['tahun_ajaran_id' => $id, 'dibuat' => true] + $data, null, $ip);

            return ['id' => $id];
        }));
    }

    /**
     * Saves the year and semester dates when `updated_at` still equals $versi
     * (FS-MD-02 item 3, ARS-40). Without $konfirmasi, a change that takes
     * dates out of the semesters returns `periksa` and saves nothing (RT-07).
     *
     * @param array<string, mixed> $isian
     *
     * @return array{galat: array<string, string>}|array{pesan: string}|array{konflik: true}|array{periksa: list<array{mulai: string, selesai: string}>, jumlah: int}|array{ok: true}
     */
    public function perbarui(int $id, string $versi, array $isian, bool $konfirmasi, int $pelakuId, ?string $ip): array
    {
        [$data, $galat] = $this->periksa($isian);

        if ($galat !== []) {
            return ['galat' => $galat];
        }

        return $this->denganKunci(fn (): array => (new Transaction())->run(function () use ($id, $versi, $data, $konfirmasi, $pelakuId, $ip): array {
            $lama = $this->cari($id);

            if ($lama === null) {
                return ['konflik' => true];
            }
            if (($galat = $this->periksaData($data, $id)) !== []) {
                return ['galat' => $galat];
            }
            if ($lama['updated_at'] !== $versi) {
                return ['konflik' => true];
            }

            $tanggalLama = $this->tanggalSemester(self::isian($lama));
            $tanggalBaru = $this->tanggalSemester($data);
            $keluar      = array_keys(array_diff_key($tanggalLama, $tanggalBaru));
            $masuk       = array_keys(array_diff_key($tanggalBaru, $tanggalLama));

            if ($keluar !== [] && ! $konfirmasi) {
                return ['periksa' => $this->rentang($keluar), 'jumlah' => count($keluar)];
            }

            $db  = db_connect();
            $now = $this->jam->now()->toDateTimeString();
            $db->table('tahun_ajaran')->where('id', $id)->where('updated_at', $versi)->update([
                'nama'            => $data['nama'],
                'tanggal_mulai'   => $data['tanggal_mulai'],
                'tanggal_selesai' => $data['tanggal_selesai'],
                'updated_at'      => $now,
            ]);

            // foundRows = true: matched rows, even when no value changed (ARS-40).
            if ($db->affectedRows() === 0) {
                return ['konflik' => true];
            }

            foreach (['ganjil', 'genap'] as $jenis) {
                $db->table('semester')->where(['tahun_ajaran_id' => $id, 'jenis' => $jenis])->update([
                    'tanggal_mulai'   => $data["{$jenis}_mulai"],
                    'tanggal_selesai' => $data["{$jenis}_selesai"],
                    'updated_at'      => $now,
                ]);
            }

            // L04-02 hook: $keluar and $masuk are the dates leaving and entering a
            // semester. Write antrean_hitung_ulang and log_presensi for them here,
            // in this transaction (FS-MD-02 item 3, docs/06 §11.4, BR-KAL-07).

            $berubah = [];
            foreach (self::isian($lama) as $kolom => $nilaiLama) {
                if ($nilaiLama !== $data[$kolom]) {
                    $berubah[$kolom] = ['lama' => $nilaiLama, 'baru' => $data[$kolom]];
                }
            }

            (new LogAktivitas())->catat('tahun_ajaran_diubah', $pelakuId, null, ['tahun_ajaran_id' => $id, 'nama' => $data['nama']] + $berubah, null, $ip);

            return ['ok' => true];
        }));
    }

    /**
     * Why the year cannot be activated today (FS-MD-02 E3), or null.
     *
     * @param array<string, mixed> $ta
     */
    public function galatAktifkan(array $ta): ?string
    {
        $hariIni = $this->jam->today();

        if ($hariIni < $ta['tanggal_mulai']) {
            return "Tahun ajaran {$ta['nama']} belum dimulai.";
        }
        if ($hariIni > $ta['tanggal_selesai']) {
            return "Tahun ajaran {$ta['nama']} sudah selesai.";
        }

        return null;
    }

    /**
     * Makes the year the only active one, deactivating the previous one in
     * the same transaction (FS-MD-02 item 2, docs/06 §6.2). A second click
     * on an already active year changes nothing.
     *
     * @return array{pesan: string}|array{ok: true}
     */
    public function aktifkan(int $id, int $pelakuId, ?string $ip): array
    {
        return $this->denganKunci(fn (): array => (new Transaction())->run(function () use ($id, $pelakuId, $ip): array {
            $ta = model(TahunAjaranModel::class)->find($id);

            if ($ta === null) {
                return ['pesan' => 'Tahun ajaran ini sudah dihapus.'];
            }
            if ((int) $ta['aktif'] === 1) {
                return ['ok' => true];
            }
            if (($pesan = $this->galatAktifkan($ta)) !== null) {
                return ['pesan' => $pesan];
            }

            $db      = db_connect();
            $now     = $this->jam->now()->toDateTimeString();
            $sebelum = (new Rujukan($db))->tahunAjaranAktif();

            // Deactivate first: the unique key on aktif_kunci allows one active row.
            $db->table('tahun_ajaran')->where('aktif', 1)->update(['aktif' => 0, 'updated_at' => $now]);
            $db->table('tahun_ajaran')->where('id', $id)->update(['aktif' => 1, 'updated_at' => $now]);

            (new LogAktivitas())->catat('tahun_ajaran_diubah', $pelakuId, null, [
                'tahun_ajaran_id' => $id,
                'nama'            => $ta['nama'],
                'diaktifkan'      => true,
                'sebelumnya'      => $sebelum['nama'] ?? null,
            ], null, $ip);

            return ['ok' => true];
        }));
    }

    /**
     * Deletes a year without rombel, with its semesters (FS-MD-02 item 4, E4).
     *
     * @return array{pesan: string}|array{konflik: true}|array{ok: true}
     */
    public function hapus(int $id, string $versi, int $pelakuId, ?string $ip): array
    {
        try {
            return $this->denganKunci(fn (): array => (new Transaction())->run(function () use ($id, $versi, $pelakuId, $ip): array {
                $ta = $this->cari($id);

                if ($ta === null) {
                    return ['ok' => true];
                }
                if ($ta['jumlah_rombel'] > 0) {
                    return ['pesan' => self::PESAN_ADA_KELAS];
                }
                if ($ta['updated_at'] !== $versi) {
                    return ['konflik' => true];
                }

                $db = db_connect();
                $db->table('semester')->where('tahun_ajaran_id', $id)->delete();
                $db->table('tahun_ajaran')->where('id', $id)->delete();
                (new LogAktivitas())->catat('tahun_ajaran_diubah', $pelakuId, null, ['tahun_ajaran_id' => $id, 'nama' => $ta['nama'], 'dihapus' => true], null, $ip);

                return ['ok' => true];
            }));
        } catch (DatabaseException $e) {
            // 1451: a rombel was added after the check (FK RESTRICT).
            if ($e->getCode() !== 1451) {
                throw $e;
            }

            return ['pesan' => self::PESAN_ADA_KELAS];
        }
    }

    /**
     * Cleans the fields (docs/11 VAL-02) and checks their form: name
     * (VAL-27), dates (VAL-11), ranges (VAL-26), semesters inside the year
     * and in order (FS-MD-02 E2).
     *
     * @param array<string, mixed> $isian
     *
     * @return array{array<string, string>, array<string, string>}
     */
    private function periksa(array $isian): array
    {
        $data  = [];
        $galat = [];

        foreach (array_keys(self::LABEL) as $kolom) {
            $data[$kolom] = trim((string) ($isian[$kolom] ?? ''));

            if ($data[$kolom] === '') {
                $galat[$kolom] = self::LABEL[$kolom] . ' wajib diisi.';
            } elseif ($kolom !== 'nama' && ! $this->tanggalSah($data[$kolom])) {
                $galat[$kolom] = self::LABEL[$kolom] . ' tidak valid.';
            }
        }

        if (! isset($galat['nama']) && (preg_match('#^(\d{4})/(\d{4})$#', $data['nama'], $m) !== 1 || (int) $m[2] !== (int) $m[1] + 1)) {
            $galat['nama'] = 'Tulis tahun ajaran seperti 2026/2027.';
        }

        $sah = static function (string ...$kolom) use (&$galat): bool {
            return array_intersect_key($galat, array_flip($kolom)) === [];
        };
        $vsSelesai = 'Tanggal selesai tidak boleh sebelum tanggal mulai.';

        foreach (['tanggal' => ['tanggal_mulai', 'tanggal_selesai'], 'ganjil' => ['ganjil_mulai', 'ganjil_selesai'], 'genap' => ['genap_mulai', 'genap_selesai']] as [$mulai, $selesai]) {
            if ($sah($mulai, $selesai) && $data[$selesai] < $data[$mulai]) {
                $galat[$selesai] = $vsSelesai;
            }
        }

        if ($sah('tanggal_mulai', 'tanggal_selesai')) {
            $diDalam = fn (string $mulai, string $selesai): bool => $data[$mulai] >= $data['tanggal_mulai'] && $data[$selesai] <= $data['tanggal_selesai'];

            if ($sah('ganjil_mulai', 'ganjil_selesai') && ! $diDalam('ganjil_mulai', 'ganjil_selesai')) {
                $galat['ganjil_mulai'] = 'Semester ganjil harus berada di dalam tahun ajaran.';
            }
            if ($sah('genap_mulai', 'genap_selesai') && (! $diDalam('genap_mulai', 'genap_selesai')
                || ($sah('ganjil_mulai', 'ganjil_selesai') && $data['genap_mulai'] <= $data['ganjil_selesai']))) {
                $galat['genap_mulai'] = 'Semester genap harus setelah semester ganjil dan berada di dalam tahun ajaran.';
            }
        }

        return [$data, $galat];
    }

    /**
     * Rules that need other years: unique name, no overlapping range (E2).
     * Runs under the lock.
     *
     * @param array<string, string> $data
     *
     * @return array<string, string>
     */
    private function periksaData(array $data, ?int $kecualiId): array
    {
        $galat = [];
        $lain  = db_connect()->table('tahun_ajaran');

        if ($kecualiId !== null) {
            $lain->where('id !=', $kecualiId);
        }

        if ((clone $lain)->where('nama', $data['nama'])->countAllResults() > 0) {
            $galat['nama'] = "Tahun ajaran {$data['nama']} sudah ada.";
        }

        $bentrok = $lain->where('tanggal_mulai <=', $data['tanggal_selesai'])->where('tanggal_selesai >=', $data['tanggal_mulai'])
            ->orderBy('tanggal_mulai')->limit(1)->get()->getRowArray();

        if ($bentrok !== null) {
            $galat['tanggal_mulai'] = "Tanggal tahun ajaran tumpang tindih dengan {$bentrok['nama']}.";
        }

        return $galat;
    }

    private function tanggalSah(string $nilai): bool
    {
        $d = DateTimeImmutable::createFromFormat('!Y-m-d', $nilai);

        return $d !== false && $d->format('Y-m-d') === $nilai;
    }

    /**
     * Every date inside either semester, as keys.
     *
     * @param array<string, string> $isian
     *
     * @return array<string, true>
     */
    private function tanggalSemester(array $isian): array
    {
        $tanggal = [];

        foreach (['ganjil', 'genap'] as $jenis) {
            if ($isian["{$jenis}_mulai"] === '') {
                continue;
            }
            for ($d = new DateTimeImmutable($isian["{$jenis}_mulai"]); $d->format('Y-m-d') <= $isian["{$jenis}_selesai"]; $d = $d->modify('+1 day')) {
                $tanggal[$d->format('Y-m-d')] = true;
            }
        }

        return $tanggal;
    }

    /**
     * Sorted dates grouped into consecutive ranges.
     *
     * @param list<string> $tanggal
     *
     * @return list<array{mulai: string, selesai: string}>
     */
    private function rentang(array $tanggal): array
    {
        sort($tanggal);
        $hasil = [];

        foreach ($tanggal as $t) {
            $i = count($hasil) - 1;

            if ($i >= 0 && (new DateTimeImmutable($hasil[$i]['selesai']))->modify('+1 day')->format('Y-m-d') === $t) {
                $hasil[$i]['selesai'] = $t;
            } else {
                $hasil[] = ['mulai' => $t, 'selesai' => $t];
            }
        }

        return $hasil;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function denganSemester(array $row): array
    {
        $row['jumlah_rombel'] = (int) $row['jumlah_rombel'];
        $row['ganjil']        = null;
        $row['genap']         = null;

        foreach (model(SemesterModel::class)->where('tahun_ajaran_id', $row['id'])->findAll() as $s) {
            $row[$s['jenis']] = $s;
        }

        return $row;
    }

    /**
     * @param callable(): array<string, mixed> $action
     *
     * @return array<string, mixed>
     */
    private function denganKunci(callable $action): array
    {
        $lock = new NamedLock();

        if (! $lock->acquire(self::KUNCI, 5)) {
            return ['pesan' => self::PESAN_TERKUNCI];
        }

        try {
            return $action();
        } finally {
            $lock->release(self::KUNCI);
        }
    }
}
