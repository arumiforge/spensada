<?php

namespace App\Services\MasterData;

use App\Services\Akun\LogAktivitas;
use App\Services\Sistem\Jam;
use App\Services\Sistem\Transaction;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use DateTimeImmutable;
use Normalizer;

/**
 * Extra student attributes defined by the admin (docs/04 FS-MD-09,
 * docs/06 §6.8, §6.9). They never drive attendance, recaps, or report
 * filters (FS-MD-09 item 6).
 *
 * Changes return `['galat' => [field => message]]` for field errors,
 * `['pesan' => text]` for a refused action, `['konflik' => true]` when the
 * version token no longer matches (docs/07 ARS-40), or the result.
 * The student form, profile and import template read aktif() and check
 * values with periksaNilai().
 */
class AtributSiswa
{
    /** Built-in import column titles, lowercased (docs/13 §6.1); a kode may not equal one. */
    public const JUDUL_BAWAAN = ['nisn', 'nama lengkap', 'kelas', 'rombel', 'nis', 'jenis kelamin', 'tanggal lahir', 'alamat', 'nama orang tua/wali', 'nomor wa orang tua/wali'];

    private const PESAN_PUNYA_NILAI = 'Atribut ini sudah memiliki nilai. Sembunyikan atribut bila tidak dipakai lagi.';

    private BaseConnection $db;
    private Jam $jam;

    public function __construct(?Jam $jam = null)
    {
        $this->db  = db_connect();
        $this->jam = $jam ?? new Jam();
    }

    /**
     * Every attribute in display order, each with `jumlah_nilai` and decoded `pilihan`.
     *
     * @return list<array<string, mixed>>
     */
    public function daftar(): array
    {
        $rows = $this->db->table('atribut_siswa a')
            ->select('a.*, (SELECT COUNT(*) FROM nilai_atribut_siswa n WHERE n.atribut_id = a.id) AS jumlah_nilai', false)
            ->orderBy('a.urutan')->orderBy('a.label')->orderBy('a.id')
            ->get()->getResultArray();

        return array_map($this->baca(...), $rows);
    }

    /**
     * Shown attributes in display order, for the student form, profile and
     * import template (FS-MD-09 items 2, 4).
     *
     * @return list<array<string, mixed>>
     */
    public function aktif(): array
    {
        return array_values(array_filter($this->daftar(), static fn (array $a): bool => (int) $a['aktif'] === 1));
    }

    /**
     * @return array<string, mixed>|null With `jumlah_nilai` and decoded `pilihan`
     */
    public function cari(int $id): ?array
    {
        foreach ($this->daftar() as $atribut) {
            if ((int) $atribut['id'] === $id) {
                return $atribut;
            }
        }

        return null;
    }

    /**
     * Cleans and checks one value (FS-MD-09 item 3, docs/11 §5.2 FS-MD-04 E8).
     * Returns the stored form (`06` §6.9: date `Y-m-d`, number with a dot, the
     * choice's own text) or null for empty, and the error or null.
     *
     * @param array<string, mixed> $atribut From aktif() or cari()
     *
     * @return array{string|null, string|null}
     */
    public function periksaNilai(array $atribut, mixed $nilai): array
    {
        $nilai = trim((string) Normalizer::normalize((string) $nilai));
        $label = $atribut['label'];

        if ($nilai === '') {
            return [null, (int) $atribut['wajib'] === 1 ? "{$label} wajib diisi." : null];
        }

        switch ($atribut['tipe']) {
            case 'angka':
                // docs/11 VAL-10: comma or dot as decimal mark, stored with a dot.
                return preg_match('/^\d{1,20}([.,]\d{1,10})?$/', $nilai) === 1 ? [str_replace(',', '.', $nilai), null] : [null, "{$label} harus berupa angka."];

            case 'tanggal':
                $tanggal = DateTimeImmutable::createFromFormat('!Y-m-d', $nilai);

                return $tanggal !== false && $tanggal->format('Y-m-d') === $nilai ? [$nilai, null] : [null, "{$label} harus berupa tanggal."];

            case 'pilihan':
                foreach ($atribut['pilihan'] as $pilihan) {
                    if (mb_strtolower($pilihan) === mb_strtolower($nilai)) {
                        return [$pilihan, null];
                    }
                }

                return [null, "Pilih salah satu {$label}."];

            default:
                return mb_strlen($nilai) > 255 ? [null, "{$label} paling panjang 255 karakter."] : [$nilai, null];
        }
    }

    /**
     * Who made the latest change to the attribute and when, if logged (docs/04 §4.6).
     *
     * @return array{nama: string|null, waktu: string}|null
     */
    public function perubahanTerakhir(int $id): ?array
    {
        $row = $this->db->table('log_aktivitas l')
            ->select('p.nama, l.created_at')
            ->join('akun p', 'p.id = l.pelaku_id', 'left')
            ->where('l.jenis', 'atribut_siswa_diubah')
            ->where("JSON_EXTRACT(l.data, '$.atribut_id') = {$id}", null, false)
            ->orderBy('l.id', 'DESC')
            ->limit(1)->get()->getRowArray();

        return $row === null ? null : ['nama' => $row['nama'], 'waktu' => $row['created_at']];
    }

    /**
     * Adds an attribute (FS-MD-09 item 1).
     *
     * @param array<string, mixed> $isian label, kode, tipe, pilihan (one per line), wajib, urutan
     *
     * @return array{galat: array<string, string>}|array{id: int}
     */
    public function buat(array $isian, int $pelakuId, ?string $ip): array
    {
        [$data, $galat] = $this->periksa($isian, null);

        if ($galat !== []) {
            return ['galat' => $galat];
        }

        try {
            $id = (new Transaction())->run(function () use ($data, $pelakuId, $ip): int {
                $now = $this->jam->now()->toDateTimeString();
                $this->db->table('atribut_siswa')->insert($this->kolom($data) + ['aktif' => 1, 'created_at' => $now, 'updated_at' => $now]);
                $id = (int) $this->db->insertID();
                (new LogAktivitas())->catat('atribut_siswa_diubah', $pelakuId, null, ['atribut_id' => $id, 'aksi' => 'dibuat'] + $this->ringkas($data), null, $ip);

                return $id;
            });
        } catch (DatabaseException $e) {
            // 1062 on uq_atribut_siswa_kode: another request took the kode after the check.
            if ($e->getCode() !== 1062) {
                throw $e;
            }

            return ['galat' => ['kode' => 'Kode ini sudah dipakai, atau sama dengan judul kolom bawaan template import.']];
        }

        return ['id' => $id];
    }

    /**
     * Saves label, tipe, pilihan, wajib and urutan when `updated_at` still
     * equals $versi (ARS-40). The kode never changes.
     *
     * @param array<string, mixed> $isian
     *
     * @return array{galat: array<string, string>}|array{konflik: true}|array{ok: true}
     */
    public function perbarui(int $id, string $versi, array $isian, int $pelakuId, ?string $ip): array
    {
        $lama = $this->cari($id);

        // A missing or malformed token is stale too; strict SQL mode would refuse it as a DATETIME.
        if ($lama === null || preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $versi) !== 1) {
            return ['konflik' => true];
        }

        [$data, $galat] = $this->periksa($isian, $lama);

        if ($galat !== []) {
            return ['galat' => $galat];
        }

        return (new Transaction())->run(function () use ($id, $versi, $lama, $data, $pelakuId, $ip): array {
            $this->db->table('atribut_siswa')->where('id', $id)->where('updated_at', $versi)
                ->update($this->kolom($data) + ['updated_at' => $this->jam->now()->toDateTimeString()]);

            // foundRows = true: matched rows, even when no value changed (ARS-40).
            if ($this->db->affectedRows() === 0) {
                return ['konflik' => true];
            }

            $berubah = [];
            foreach ($this->ringkas($data) as $kolom => $baru) {
                $lamaNilai = $this->ringkas($lama)[$kolom];
                if ($lamaNilai !== $baru) {
                    $berubah[$kolom] = ['lama' => $lamaNilai, 'baru' => $baru];
                }
            }

            if ($berubah !== []) {
                (new LogAktivitas())->catat('atribut_siswa_diubah', $pelakuId, null, ['atribut_id' => $id, 'kode' => $lama['kode'], 'aksi' => 'diubah'] + $berubah, null, $ip);
            }

            return ['ok' => true];
        });
    }

    /**
     * Hides or shows an attribute again; values stay (FS-MD-09 items 1, 4).
     * A second click changes nothing and logs nothing (docs/11 GAL-09).
     */
    public function ubahAktif(int $id, bool $aktif, int $pelakuId, ?string $ip): void
    {
        (new Transaction())->run(function () use ($id, $aktif, $pelakuId, $ip): void {
            $this->db->table('atribut_siswa')->where(['id' => $id, 'aktif' => $aktif ? 0 : 1])
                ->update(['aktif' => $aktif ? 1 : 0, 'updated_at' => $this->jam->now()->toDateTimeString()]);

            if ($this->db->affectedRows() > 0) {
                $kode = $this->db->table('atribut_siswa')->select('kode')->where('id', $id)->get()->getRow()->kode;
                (new LogAktivitas())->catat('atribut_siswa_diubah', $pelakuId, null, ['atribut_id' => $id, 'kode' => $kode, 'aksi' => $aktif ? 'ditampilkan' : 'disembunyikan'], null, $ip);
            }
        });
    }

    /**
     * Deletes an attribute no student has a value for (FS-MD-09 item 5, E3).
     *
     * @return array{pesan: string}|array{ok: true}
     */
    public function hapus(int $id, int $pelakuId, ?string $ip): array
    {
        try {
            return (new Transaction())->run(function () use ($id, $pelakuId, $ip): array {
                $atribut = $this->cari($id);

                if ($atribut === null) {
                    return ['ok' => true];
                }
                if ((int) $atribut['jumlah_nilai'] > 0) {
                    return ['pesan' => self::PESAN_PUNYA_NILAI];
                }

                $this->db->table('atribut_siswa')->where('id', $id)->delete();
                (new LogAktivitas())->catat('atribut_siswa_diubah', $pelakuId, null, ['atribut_id' => $id, 'aksi' => 'dihapus'] + $this->ringkas($atribut), null, $ip);

                return ['ok' => true];
            });
        } catch (DatabaseException $e) {
            // 1451: a value was saved after the check; the foreign key refused the delete.
            if ($e->getCode() !== 1451) {
                throw $e;
            }

            return ['pesan' => self::PESAN_PUNYA_NILAI];
        }
    }

    /**
     * Cleans and checks the definition (docs/11 VAL-02, VAL-27, §5.2).
     *
     * @param array<string, mixed>      $isian
     * @param array<string, mixed>|null $lama  Attribute being edited, null when adding
     *
     * @return array{array<string, mixed>, array<string, string>}
     */
    private function periksa(array $isian, ?array $lama): array
    {
        $label  = trim((string) preg_replace('/\s+/u', ' ', (string) Normalizer::normalize((string) ($isian['label'] ?? ''))));
        $tipe   = (string) ($isian['tipe'] ?? '');
        $urutan = trim((string) ($isian['urutan'] ?? ''));
        $galat  = [];

        if ($label === '') {
            $galat['label'] = 'Label wajib diisi.';
        } elseif (mb_strlen($label) > 60) {
            $galat['label'] = 'Label paling panjang 60 karakter.';
        }

        $kode = $lama['kode'] ?? $this->kode((string) ($isian['kode'] ?? ''), $label);
        if ($lama === null) {
            if (preg_match('/^[a-z][a-z0-9_]{1,29}$/', $kode) !== 1) {
                $galat['kode'] = 'Kode diawali huruf, dan hanya boleh berisi huruf kecil, angka, dan garis bawah.';
            } elseif (in_array($kode, self::JUDUL_BAWAAN, true) || $this->db->table('atribut_siswa')->where('kode', $kode)->countAllResults() > 0) {
                $galat['kode'] = 'Kode ini sudah dipakai, atau sama dengan judul kolom bawaan template import.';
            }
        }

        if (! isset(config('Label')->codes['atribut_siswa.tipe'][$tipe])) {
            $galat['tipe'] = 'Pilih tipe atribut.';
        } elseif ($lama !== null && $tipe !== $lama['tipe'] && (int) $lama['jumlah_nilai'] > 0) {
            $galat['tipe'] = 'Tipe atribut tidak dapat diubah karena sudah ada siswa yang memiliki nilai.';
        }

        $pilihan = null;
        if ($tipe === 'pilihan') {
            [$pilihan, $pesan] = $this->periksaPilihan((string) ($isian['pilihan'] ?? ''), $lama);
            if ($pesan !== null) {
                $galat['pilihan'] = $pesan;
            }
        }

        if ($urutan === '') {
            $urutan = $lama['urutan'] ?? (int) ($this->db->table('atribut_siswa')->selectMax('urutan')->get()->getRow()->urutan ?? 0) + 1;
        } elseif (! ctype_digit($urutan) || (int) $urutan > 999) {
            $galat['urutan'] = 'Urutan harus berupa angka 0 sampai 999.';
        }

        return [[
            'kode'    => $kode,
            'label'   => $label,
            'tipe'    => $tipe,
            'pilihan' => $pilihan,
            'wajib'   => in_array((string) ($isian['wajib'] ?? ''), ['1', 'on'], true) ? 1 : 0,
            'urutan'  => min((int) $urutan, 999),
        ], $galat];
    }

    /**
     * One choice per line: 2 to 50 choices of at most 60 characters, no
     * duplicates; a choice a student uses cannot be removed (E3).
     *
     * @param array<string, mixed>|null $lama
     *
     * @return array{list<string>, string|null}
     */
    private function periksaPilihan(string $teks, ?array $lama): array
    {
        $pilihan = [];
        $kecil   = [];

        foreach (preg_split('/\R/u', (string) Normalizer::normalize($teks)) as $baris) {
            $baris = trim((string) preg_replace('/\s+/u', ' ', $baris));
            if ($baris === '') {
                continue;
            }
            if (mb_strlen($baris) > 60) {
                return [[], 'Setiap pilihan paling panjang 60 karakter.'];
            }
            if (in_array(mb_strtolower($baris), $kecil, true)) {
                return [[], "Pilihan {$baris} ditulis lebih dari sekali."];
            }
            $pilihan[] = $baris;
            $kecil[]   = mb_strtolower($baris);
        }

        if (count($pilihan) < 2) {
            return [$pilihan, 'Tulis paling sedikit 2 pilihan, satu pilihan per baris.'];
        }
        if (count($pilihan) > 50) {
            return [$pilihan, 'Pilihan paling banyak 50.'];
        }

        foreach (array_diff($lama['pilihan'] ?? [], $pilihan) as $dihapus) {
            $n = $this->db->table('nilai_atribut_siswa')->where(['atribut_id' => $lama['id'], 'nilai' => $dihapus])->countAllResults();
            if ($n > 0) {
                return [$pilihan, "Pilihan {$dihapus} sudah dipakai " . format_number($n) . ' siswa, sehingga tidak dapat dihapus.'];
            }
        }

        return [$pilihan, null];
    }

    /**
     * The typed kode, or one made from the label when left empty (FS-MD-09 input).
     */
    private function kode(string $kode, string $label): string
    {
        $kode = strtolower(trim($kode));

        if ($kode === '') {
            $kode = trim((string) preg_replace('/[^a-z0-9]+/', '_', strtolower($label)), '_');
            $kode = rtrim(substr($kode, 0, 30), '_');
        }

        return $kode;
    }

    /**
     * @param array<string, mixed> $row Row of `atribut_siswa`
     *
     * @return array<string, mixed>
     */
    private function baca(array $row): array
    {
        $row['pilihan'] = $row['pilihan'] === null ? [] : json_decode($row['pilihan'], true);

        return $row;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function kolom(array $data): array
    {
        return ['pilihan' => $data['pilihan'] === null ? null : json_encode($data['pilihan'], JSON_UNESCAPED_UNICODE)] + $data;
    }

    /**
     * Log details of a definition (SEC-59). `wajib_diisi`, not `wajib`: the
     * log page already labels `wajib` for password changes.
     *
     * @param array<string, mixed> $a
     *
     * @return array<string, mixed>
     */
    private function ringkas(array $a): array
    {
        return [
            'kode'        => $a['kode'],
            'label'       => $a['label'],
            'tipe'        => $a['tipe'],
            'pilihan'     => $a['pilihan'] === [] ? null : $a['pilihan'],
            'wajib_diisi' => (int) $a['wajib'] === 1,
            'urutan'      => (int) $a['urutan'],
        ];
    }
}
