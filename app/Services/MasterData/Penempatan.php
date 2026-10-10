<?php

namespace App\Services\MasterData;

use App\Models\PenempatanModel;
use App\Services\Akun\LogAktivitas;
use App\Services\Sistem\Jam;
use App\Services\Sistem\Transaction;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;
use DateTimeImmutable;

/**
 * Student placements in rombel (docs/04 FS-MD-05 without item 6, docs/06 §6.7).
 *
 * A student's placements never overlap by effective end date (an empty end
 * is the rombel's school-year end). A new placement that starts inside the
 * student's placement in another rombel closes that one on the day before
 * (a move, item 2); any other overlap is refused (E1). Checks run again
 * inside the transaction after locking the `siswa` rows (docs/06 §16).
 *
 * Changes return `['galat' => [field => message]]` for field errors, or the result.
 */
class Penempatan
{
    private BaseConnection $db;
    private Jam $jam;

    public function __construct(?Jam $jam = null)
    {
        $this->db  = db_connect();
        $this->jam = $jam ?? new Jam();
    }

    /**
     * All rombel with their school year (`tahun_ajaran`, `ta_mulai`,
     * `ta_selesai`), newest year first, for the class pickers.
     *
     * @return list<array<string, mixed>>
     */
    public function daftarRombel(): array
    {
        return $this->rombelQuery()->orderBy('ta.tanggal_mulai', 'DESC')->orderBy('r.tingkat')->orderBy('r.nama')->get()->getResultArray();
    }

    /**
     * @return array<string, mixed>|null Same columns as daftarRombel()
     */
    public function rombel(int $id): ?array
    {
        return $this->rombelQuery()->where('r.id', $id)->get()->getRowArray();
    }

    /**
     * Active students placed in the rombel on its reference date: today,
     * kept inside the rombel's school year, so a past year shows the class
     * as it ended (FS-MD-05 item 3.2).
     *
     * @param array<string, mixed> $asal Row of rombel()
     *
     * @return list<array<string, mixed>> id, nisn, nama
     */
    public function siswaRombel(array $asal): array
    {
        $tanggal = min(max($this->jam->today(), $asal['ta_mulai']), $asal['ta_selesai']);

        return $this->db->table('siswa s')->select('s.id, s.nisn, s.nama')
            ->join('penempatan p', 'p.siswa_id = s.id')
            ->where('p.rombel_id', $asal['id'])
            ->where('p.tanggal_mulai <=', $tanggal)
            ->groupStart()->where('p.tanggal_selesai', null)->orWhere('p.tanggal_selesai >=', $tanggal)->groupEnd()
            ->whereIn('s.id', static fn (BaseBuilder $sub) => $sub->select('siswa_id')->from('masa_aktif')
                ->where('dibatalkan', 0)->where('tanggal_mulai <=', $tanggal)
                ->groupStart()->where('tanggal_selesai', null)->orWhere('tanggal_selesai >=', $tanggal)->groupEnd())
            ->orderBy('s.nama')->orderBy('s.id')
            ->get()->getResultArray();
    }

    /**
     * Places one student from $tanggalMulai (HAL-MD-11), closing the current
     * placement in another rombel on the day before.
     *
     * @return array{galat: array<string, string>}|array{rombel: array<string, mixed>}
     */
    public function tempatkan(int $siswaId, string $rombelId, string $tanggalMulai, int $pelakuId): array
    {
        [$rombel, $galat] = $this->periksaIsian($rombelId, $tanggalMulai, 'rombel_id');

        if ($galat !== []) {
            return ['galat' => $galat];
        }

        return (new Transaction())->run(function () use ($siswaId, $rombel, $tanggalMulai, $pelakuId): array {
            $this->kunci([$siswaId]);
            $siswa = $this->db->table('siswa')->where('id', $siswaId)->get()->getRowArray();
            $pesan = $this->pasang($siswa, $rombel, $tanggalMulai, $pelakuId, null);

            return $pesan === null ? ['rombel' => $rombel] : ['galat' => ['tanggal_mulai' => $pesan]];
        });
    }

    /**
     * Per-class placement check without saving, for the check page (docs/09
     * RT-07): which selected students can be placed, and why the others not.
     *
     * @param list<mixed> $siswaIds
     *
     * @return array{galat: array<string, string>}|array{asal: array<string, mixed>, tujuan: array<string, mixed>, siap: list<array<string, mixed>>, gagal: list<string>}
     */
    public function periksaKelas(string $asalId, string $tujuanId, string $tanggalMulai, array $siswaIds): array
    {
        [$asal, $tujuan, $siswa, $galat] = $this->isianKelas($asalId, $tujuanId, $tanggalMulai, $siswaIds);

        if ($galat !== []) {
            return ['galat' => $galat];
        }

        $siap  = [];
        $gagal = [];

        foreach ($siswa as $row) {
            $pesan = $this->periksa($row, $tujuan, $tanggalMulai)[0];
            if ($pesan === null) {
                $siap[] = $row;
            } else {
                $gagal[] = $pesan;
            }
        }

        return ['asal' => $asal, 'tujuan' => $tujuan, 'siap' => $siap, 'gagal' => $gagal];
    }

    /**
     * Saves a per-class placement (FS-MD-05 item 3): every student that
     * still passes the checks, in one transaction with one `kelompok` mark,
     * plus one `penempatan_massal` activity entry (docs/12 SEC-59).
     *
     * @param list<mixed> $siswaIds
     *
     * @return array{galat: array<string, string>}|array{tujuan: array<string, mixed>, jumlah: int, gagal: list<string>}
     */
    public function simpanKelas(string $asalId, string $tujuanId, string $tanggalMulai, array $siswaIds, int $pelakuId, ?string $ip): array
    {
        [$asal, $tujuan, $siswa, $galat] = $this->isianKelas($asalId, $tujuanId, $tanggalMulai, $siswaIds);

        if ($galat !== []) {
            return ['galat' => $galat];
        }

        return (new Transaction())->run(function () use ($asal, $tujuan, $siswa, $tanggalMulai, $pelakuId, $ip): array {
            $this->kunci(array_column($siswa, 'id'));
            $kelompok = $this->uuid();
            $gagal    = [];

            foreach ($siswa as $row) {
                if (($pesan = $this->pasang($row, $tujuan, $tanggalMulai, $pelakuId, $kelompok)) !== null) {
                    $gagal[] = $pesan;
                }
            }

            $jumlah = count($siswa) - count($gagal);

            if ($jumlah > 0) {
                (new LogAktivitas())->catat('penempatan_massal', $pelakuId, null, [
                    'kelas_asal'    => "{$asal['nama']} ({$asal['tahun_ajaran']})",
                    'kelas_tujuan'  => "{$tujuan['nama']} ({$tujuan['tahun_ajaran']})",
                    'tanggal_mulai' => $tanggalMulai,
                    'jumlah_siswa'  => $jumlah,
                ], (int) $tujuan['id'], $ip);
            }

            return ['tujuan' => $tujuan, 'jumlah' => $jumlah, 'gagal' => $gagal];
        });
    }

    /**
     * Form checks of the per-class placement; the students are the selected
     * ones that siswaRombel() lists for the source class.
     *
     * @param list<mixed> $siswaIds
     *
     * @return array{array<string, mixed>|null, array<string, mixed>|null, list<array<string, mixed>>, array<string, string>}
     */
    private function isianKelas(string $asalId, string $tujuanId, string $tanggalMulai, array $siswaIds): array
    {
        $asal = ctype_digit($asalId) ? $this->rombel((int) $asalId) : null;
        [$tujuan, $galat] = $this->periksaIsian($tujuanId, $tanggalMulai, 'tujuan');
        $siswa = [];

        if ($asal === null) {
            $galat['asal'] = $asalId === '' ? 'Pilih kelas asal.' : 'Kelas asal yang dipilih sudah tidak ada. Pilih lagi.';
        } elseif ($tujuan !== null && (int) $tujuan['id'] === (int) $asal['id']) {
            $galat['tujuan'] = 'Kelas tujuan harus berbeda dengan kelas asal.';
        } else {
            $dipilih = array_map('strval', $siswaIds);
            $siswa   = array_values(array_filter($this->siswaRombel($asal), static fn (array $row): bool => in_array((string) $row['id'], $dipilih, true)));
            if ($siswa === []) {
                $galat['siswa'] = 'Pilih paling sedikit 1 siswa.';
            }
        }

        return [$asal, $tujuan, $siswa, $galat];
    }

    /**
     * Checks the target class and start date (VAL-09, VAL-11, FS-MD-05 E3).
     *
     * @return array{array<string, mixed>|null, array<string, string>}
     */
    private function periksaIsian(string $rombelId, string $tanggalMulai, string $field): array
    {
        $galat  = [];
        $rombel = ctype_digit($rombelId) ? $this->rombel((int) $rombelId) : null;

        if ($rombel === null) {
            $galat[$field] = $rombelId === '' ? 'Pilih kelas tujuan.' : 'Kelas tujuan yang dipilih sudah tidak ada. Pilih lagi.';
        }

        $tanggal = DateTimeImmutable::createFromFormat('!Y-m-d', $tanggalMulai);

        if ($tanggalMulai === '') {
            $galat['tanggal_mulai'] = 'Tanggal mulai wajib diisi.';
        } elseif ($tanggal === false || $tanggal->format('Y-m-d') !== $tanggalMulai) {
            $galat['tanggal_mulai'] = 'Tanggal mulai tidak valid.';
        } elseif ($rombel !== null && ($tanggalMulai < $rombel['ta_mulai'] || $tanggalMulai > $rombel['ta_selesai'])) {
            $galat['tanggal_mulai'] = 'Tanggal mulai ' . format_date($tanggalMulai) . " di luar tahun ajaran {$rombel['tahun_ajaran']}.";
        }

        return [$rombel, $galat];
    }

    /**
     * Saves one placement after periksa(); returns the refusal message, or
     * null when saved. Call inside the transaction, after kunci().
     *
     * @param array<string, mixed> $siswa
     * @param array<string, mixed> $rombel
     */
    private function pasang(array $siswa, array $rombel, string $mulai, int $pelakuId, ?string $kelompok): ?string
    {
        [$pesan, $ditutup] = $this->periksa($siswa, $rombel, $mulai);

        if ($pesan !== null) {
            return $pesan;
        }

        $model = model(PenempatanModel::class);
        $log   = new LogDataSiswa();
        $id    = (int) $siswa['id'];

        if ($ditutup !== null) {
            $selesai = (new DateTimeImmutable($mulai))->modify('-1 day')->format('Y-m-d');
            $model->update($ditutup['id'], ['tanggal_selesai' => $selesai]);
            $konteks = ['penempatan_id' => (int) $ditutup['id'], 'kelas' => $ditutup['kelas'], 'tanggal_mulai' => $ditutup['tanggal_mulai']];
            $log->catat($id, 'penempatan_diubah', $konteks + ['tanggal_selesai' => $ditutup['tanggal_selesai']], $konteks + ['tanggal_selesai' => $selesai], $pelakuId, null, $kelompok);
        }

        $baru = (int) $model->insert(['siswa_id' => $id, 'rombel_id' => $rombel['id'], 'tanggal_mulai' => $mulai, 'dibuat_oleh' => $pelakuId]);
        $log->catat($id, 'penempatan_dibuat', null, [
            'penempatan_id' => $baru, 'kelas' => $rombel['nama'], 'tahun_ajaran' => $rombel['tahun_ajaran'], 'tanggal_mulai' => $mulai,
        ], $pelakuId, null, $kelompok);

        // L04-02 hook: queue the status recount of this student from $mulai here (FS-MD-05 item 5, docs/06 §16).

        return null;
    }

    /**
     * Refusal message (E2, E1) for placing the student from $mulai, or null
     * with the placement a move closes (docs/06 §6.7 rules 1 and 2).
     *
     * @param array<string, mixed> $siswa
     * @param array<string, mixed> $rombel
     *
     * @return array{string|null, array<string, mixed>|null}
     */
    private function periksa(array $siswa, array $rombel, string $mulai): array
    {
        $aktif = $this->db->table('masa_aktif')
            ->where('siswa_id', $siswa['id'])->where('dibatalkan', 0)->where('tanggal_mulai <=', $mulai)
            ->groupStart()->where('tanggal_selesai', null)->orWhere('tanggal_selesai >=', $mulai)->groupEnd()
            ->countAllResults() > 0;

        if (! $aktif) {
            return ["{$siswa['nama']} tidak aktif pada " . format_date($mulai) . '.', null];
        }

        // The new placement runs to the end of its school year: find every placement it would overlap.
        $tumpang = $this->db->table('penempatan p')
            ->select('p.id, p.rombel_id, p.tanggal_mulai, p.tanggal_selesai, r.nama AS kelas, COALESCE(p.tanggal_selesai, ta.tanggal_selesai) AS selesai_efektif')
            ->join('rombel r', 'r.id = p.rombel_id')
            ->join('tahun_ajaran ta', 'ta.id = r.tahun_ajaran_id')
            ->where('p.siswa_id', $siswa['id'])
            ->where('p.tanggal_mulai <=', $rombel['ta_selesai'])
            ->where('COALESCE(p.tanggal_selesai, ta.tanggal_selesai) >=', $mulai)
            ->orderBy('p.tanggal_mulai')
            ->get()->getResultArray();
        $ditutup = null;

        foreach ($tumpang as $p) {
            if ($ditutup === null && $p['tanggal_mulai'] < $mulai && (int) $p['rombel_id'] !== (int) $rombel['id']) {
                $ditutup = $p;

                continue;
            }

            return ["Penempatan ini tumpang tindih dengan penempatan {$siswa['nama']} di {$p['kelas']}, " . format_date($p['tanggal_mulai']) . ' s.d. ' . format_date($p['selesai_efektif']) . '.', null];
        }

        return [null, $ditutup];
    }

    /**
     * Locks the students' rows in ascending ID order (docs/06 §16, docs/07 ARS-43).
     *
     * @param list<int|string> $ids
     */
    private function kunci(array $ids): void
    {
        $ids = array_map('intval', $ids);
        sort($ids);
        $this->db->query('SELECT id FROM siswa WHERE id IN ? ORDER BY id FOR UPDATE', [$ids]);
    }

    private function rombelQuery(): BaseBuilder
    {
        return $this->db->table('rombel r')
            ->select('r.id, r.nama, r.tingkat, r.tahun_ajaran_id, ta.nama AS tahun_ajaran, ta.tanggal_mulai AS ta_mulai, ta.tanggal_selesai AS ta_selesai')
            ->join('tahun_ajaran ta', 'ta.id = r.tahun_ajaran_id');
    }

    /** Random UUID v4 for the `kelompok` mark of one bulk action (docs/06 §12.2). */
    private function uuid(): string
    {
        $b    = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0F) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3F) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }
}
