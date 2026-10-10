<?php

namespace App\Services\MasterData;

use App\Services\Akun\LogAktivitas;
use App\Services\Sistem\Jam;
use App\Services\Sistem\Transaction;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Normalizer;

/**
 * Rombel and their wali kelas (docs/04 FS-MD-03, docs/06 §6.4). The screen
 * calls a rombel "Kelas".
 *
 * Changes return `['galat' => [field => message]]` for field errors,
 * `['pesan' => text]` for a refused action, `['konflik' => true]` when the
 * version token no longer matches (docs/07 ARS-40), or the result. The
 * wali kelas role follows `rombel.wali_kelas_id` on the next request
 * (Services\Akun\Peran), so a change moves the rights at once (docs/02 §3 item 2).
 */
class Rombel
{
    public const TINGKAT = ['7', '8', '9'];

    private const PESAN_PUNYA_SISWA = 'Kelas ini sudah memiliki siswa, sehingga tidak dapat dihapus.';

    private BaseConnection $db;
    private Jam $jam;

    public function __construct(?Jam $jam = null)
    {
        $this->db  = db_connect();
        $this->jam = $jam ?? new Jam();
    }

    /**
     * All school years for the selector, newest first.
     *
     * @return list<array<string, mixed>>
     */
    public function tahunAjaran(): array
    {
        return $this->db->table('tahun_ajaran')->orderBy('tanggal_mulai', 'DESC')->get()->getResultArray();
    }

    /**
     * Rombel of one school year with wali kelas name and status, the number
     * of students, and whether it may be deleted (FS-MD-03 items 1, 5, 6).
     * Students are counted on today, kept inside the year so a past or
     * future year still shows its students.
     *
     * @param array<string, mixed> $tahunAjaran Row of `tahun_ajaran`
     *
     * @return list<array<string, mixed>>
     */
    public function daftar(array $tahunAjaran, string $hariIni): array
    {
        $tanggal = min(max($hariIni, $tahunAjaran['tanggal_mulai']), $tahunAjaran['tanggal_selesai']);
        $tanggal = $this->db->escape($tanggal);
        $akhir   = $this->db->escape($tahunAjaran['tanggal_selesai']);

        return $this->db->table('rombel r')
            ->select('r.*, w.nama AS wali_nama, w.status AS wali_status')
            ->select("(SELECT COUNT(DISTINCT p.siswa_id) FROM penempatan p WHERE p.rombel_id = r.id AND p.tanggal_mulai <= {$tanggal} AND COALESCE(p.tanggal_selesai, {$akhir}) >= {$tanggal}) AS jumlah_siswa", false)
            ->select('(EXISTS (SELECT 1 FROM penempatan p WHERE p.rombel_id = r.id) OR EXISTS (SELECT 1 FROM log_aktivitas l WHERE l.rombel_id = r.id)) AS terkunci', false)
            ->join('akun w', 'w.id = r.wali_kelas_id', 'left')
            ->where('r.tahun_ajaran_id', $tahunAjaran['id'])
            ->orderBy('r.tingkat')->orderBy('r.nama')
            ->get()->getResultArray();
    }

    /**
     * Rombel with `tahun_ajaran_nama`, `wali_nama`, `wali_status` and
     * `punya_penempatan`, or null.
     *
     * @return array<string, mixed>|null
     */
    public function cari(int $id): ?array
    {
        return $this->db->table('rombel r')
            ->select('r.*, ta.nama AS tahun_ajaran_nama, w.nama AS wali_nama, w.status AS wali_status')
            ->select('EXISTS (SELECT 1 FROM penempatan p WHERE p.rombel_id = r.id) AS punya_penempatan', false)
            ->join('tahun_ajaran ta', 'ta.id = r.tahun_ajaran_id')
            ->join('akun w', 'w.id = r.wali_kelas_id', 'left')
            ->where('r.id', $id)
            ->get()->getRowArray();
    }

    /**
     * Active staff accounts to choose as wali kelas, plus the current one
     * when it is no longer active, so the form still shows it.
     *
     * @return list<array<string, mixed>>
     */
    public function pilihanWaliKelas(?int $sekarangId = null): array
    {
        $builder = $this->db->table('akun')->select('id, nama, username, status')->where('jenis', 'staf');
        $builder->groupStart()->where('status', 'aktif');
        if ($sekarangId !== null) {
            $builder->orWhere('id', $sekarangId);
        }

        return $builder->groupEnd()->orderBy('nama')->orderBy('id')->get()->getResultArray();
    }

    /**
     * Who made the latest change to the rombel and when, if logged (docs/04 §4.6).
     *
     * @return array{nama: string|null, waktu: string}|null
     */
    public function perubahanTerakhir(int $id): ?array
    {
        $row = $this->db->table('log_aktivitas l')
            ->select('p.nama, l.created_at')
            ->join('akun p', 'p.id = l.pelaku_id', 'left')
            ->whereIn('l.jenis', ['rombel_diubah', 'wali_kelas_diubah'])
            ->groupStart()->where('l.rombel_id', $id)->orWhere("JSON_EXTRACT(l.data, '$.rombel_id') = {$id}", null, false)->groupEnd()
            ->orderBy('l.id', 'DESC')
            ->limit(1)->get()->getRowArray();

        return $row === null ? null : ['nama' => $row['nama'], 'waktu' => $row['created_at']];
    }

    /**
     * Adds a rombel (FS-MD-03 item 1).
     *
     * @param array<string, mixed> $isian tahun_ajaran_id, nama, tingkat, wali_kelas_id
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
                $this->db->table('rombel')->insert($data + ['created_at' => $now, 'updated_at' => $now]);
                $id = (int) $this->db->insertID();

                $log = new LogAktivitas();
                $log->catat('rombel_diubah', $pelakuId, null, ['rombel_id' => $id, 'aksi' => 'dibuat'] + $this->ringkas($data), null, $ip);

                if ($data['wali_kelas_id'] !== null) {
                    $this->catatWaliKelas($log, $id, $data['nama'], null, $data['wali_kelas_id'], $pelakuId, $ip);
                }

                return $id;
            });
        } catch (DatabaseException $e) {
            return $this->namaBentrok($e);
        }

        return ['id' => $id];
    }

    /**
     * Saves name, tingkat and wali kelas when `updated_at` still equals
     * $versi (ARS-40). The school year stays as created.
     *
     * @param array<string, mixed> $isian nama, tingkat, wali_kelas_id
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

        try {
            return (new Transaction())->run(function () use ($id, $versi, $lama, $data, $pelakuId, $ip): array {
                // E4 again inside the transaction: a placement may have been added since the check.
                if ((string) $data['tingkat'] !== (string) $lama['tingkat'] && $this->punyaPenempatan($id)) {
                    return ['galat' => ['tingkat' => 'Tingkat kelas yang sudah memiliki siswa tidak dapat diubah.']];
                }

                $this->db->table('rombel')->where('id', $id)->where('updated_at', $versi)
                    ->update($data + ['updated_at' => $this->jam->now()->toDateTimeString()]);

                // foundRows = true: matched rows, even when no value changed (ARS-40).
                if ($this->db->affectedRows() === 0) {
                    return ['konflik' => true];
                }

                $berubah = [];
                foreach (['nama', 'tingkat'] as $kolom) {
                    if ((string) $lama[$kolom] !== (string) $data[$kolom]) {
                        $berubah[$kolom] = ['lama' => $lama[$kolom], 'baru' => $data[$kolom]];
                    }
                }

                $log = new LogAktivitas();

                if ($berubah !== []) {
                    $log->catat('rombel_diubah', $pelakuId, null, ['rombel_id' => $id, 'aksi' => 'diubah'] + $berubah, null, $ip);
                }

                $waliLama = $lama['wali_kelas_id'] === null ? null : (int) $lama['wali_kelas_id'];
                if ($waliLama !== $data['wali_kelas_id']) {
                    $this->catatWaliKelas($log, $id, $data['nama'], $waliLama, $data['wali_kelas_id'], $pelakuId, $ip);
                }

                return ['ok' => true];
            });
        } catch (DatabaseException $e) {
            return $this->namaBentrok($e);
        }
    }

    /**
     * Deletes a rombel that never had a placement (FS-MD-03 item 5, E3).
     *
     * @return array{pesan: string}|array{ok: true}
     */
    public function hapus(int $id, int $pelakuId, ?string $ip): array
    {
        try {
            return (new Transaction())->run(function () use ($id, $pelakuId, $ip): array {
                $rombel = $this->cari($id);

                if ($rombel === null) {
                    return ['ok' => true];
                }
                if ($this->punyaPenempatan($id)) {
                    return ['pesan' => self::PESAN_PUNYA_SISWA];
                }
                if ($this->db->table('log_aktivitas')->where('rombel_id', $id)->countAllResults() > 0) {
                    // log_aktivitas.rombel_id (e.g. wali_kelas_diubah) keeps the rombel: the log is never changed (docs/06 §5.3).
                    return ['pesan' => 'Kelas ini sudah tercatat di log aktivitas, sehingga tidak dapat dihapus. Ubah namanya bila perlu.'];
                }

                $this->db->table('rombel')->where('id', $id)->delete();
                (new LogAktivitas())->catat('rombel_diubah', $pelakuId, null, ['rombel_id' => $id, 'aksi' => 'dihapus'] + $this->ringkas($rombel), null, $ip);

                return ['ok' => true];
            });
        } catch (DatabaseException $e) {
            // 1451: a placement was added after the check; the foreign key refused the delete.
            if ($e->getCode() !== 1451) {
                throw $e;
            }

            return ['pesan' => self::PESAN_PUNYA_SISWA];
        }
    }

    /**
     * Cleans and checks the fields (docs/11 VAL-02, VAL-09, VAL-27, §5.2).
     *
     * @param array<string, mixed>      $isian
     * @param array<string, mixed>|null $lama  Rombel being edited, null when adding
     *
     * @return array{array{tahun_ajaran_id?: int, nama: string, tingkat: int, wali_kelas_id: int|null}, array<string, string>}
     */
    private function periksa(array $isian, ?array $lama): array
    {
        $nama    = preg_replace('/ {2,}/', ' ', trim((string) Normalizer::normalize((string) ($isian['nama'] ?? ''))));
        $tingkat = trim((string) ($isian['tingkat'] ?? ''));
        $wali    = trim((string) ($isian['wali_kelas_id'] ?? ''));
        $galat   = [];
        $tahunId = $lama === null ? $this->idDariIsian($isian['tahun_ajaran_id'] ?? '') : (int) $lama['tahun_ajaran_id'];

        if ($lama === null && ($tahunId === null || $this->db->table('tahun_ajaran')->where('id', $tahunId)->countAllResults() === 0)) {
            $galat['tahun_ajaran_id'] = 'Tahun ajaran yang dipilih sudah tidak ada. Pilih lagi.';
        }

        if ($nama === '') {
            $galat['nama'] = 'Nama kelas wajib diisi.';
        } elseif (mb_strlen($nama) > 20) {
            $galat['nama'] = 'Nama kelas paling panjang 20 karakter.';
        } elseif (preg_match('/^[\p{L}\p{M}\p{N} -]+$/u', $nama) !== 1) {
            $galat['nama'] = 'Nama kelas hanya boleh berisi huruf, angka, spasi, dan tanda hubung.';
        } elseif (! isset($galat['tahun_ajaran_id'])) {
            // The column collation ignores case, as VAL-27 asks.
            $sama = $this->db->table('rombel')->where('tahun_ajaran_id', $tahunId)->where('nama', $nama);
            if ($lama !== null) {
                $sama->where('id !=', $lama['id']);
            }
            if ($sama->countAllResults() > 0) {
                $galat['nama'] = 'Nama kelas sudah dipakai di tahun ajaran ini.';
            }
        }

        if (! in_array($tingkat, self::TINGKAT, true)) {
            $galat['tingkat'] = 'Pilih tingkat 7, 8, atau 9.';
        } elseif ($lama !== null && $tingkat !== (string) $lama['tingkat'] && (bool) $lama['punya_penempatan']) {
            $galat['tingkat'] = 'Tingkat kelas yang sudah memiliki siswa tidak dapat diubah.';
        }

        $waliId = null;
        if ($wali !== '') {
            $waliId = $this->idDariIsian($wali);
            $akun   = $waliId === null ? null : $this->db->table('akun')->where(['id' => $waliId, 'jenis' => 'staf'])->get()->getRowArray();

            if ($akun === null) {
                $galat['wali_kelas_id'] = 'Wali kelas yang dipilih sudah tidak ada. Pilih lagi.';
            } elseif ($akun['status'] !== 'aktif' && $waliId !== (int) ($lama['wali_kelas_id'] ?? 0)) {
                // E2 only for a new choice: keeping an inactive wali kelas is allowed (FS-AKN-03 item 9).
                $galat['wali_kelas_id'] = "Akun {$akun['nama']} nonaktif. Pilih staf yang aktif.";
            }
        }

        $data = ['nama' => $nama, 'tingkat' => (int) $tingkat, 'wali_kelas_id' => $waliId];

        return [$lama === null ? ['tahun_ajaran_id' => (int) $tahunId] + $data : $data, $galat];
    }

    private function idDariIsian(mixed $nilai): ?int
    {
        $nilai = trim((string) $nilai);

        return ctype_digit($nilai) && (int) $nilai > 0 ? (int) $nilai : null;
    }

    private function punyaPenempatan(int $id): bool
    {
        return $this->db->table('penempatan')->where('rombel_id', $id)->countAllResults() > 0;
    }

    /**
     * Log details of a whole rombel (SEC-59: the fields).
     *
     * @param array<string, mixed> $rombel
     *
     * @return array<string, mixed>
     */
    private function ringkas(array $rombel): array
    {
        $tahun = $this->db->table('tahun_ajaran')->select('nama')->where('id', $rombel['tahun_ajaran_id'])->get()->getRow();

        return ['tahun_ajaran' => $tahun->nama ?? null, 'nama' => $rombel['nama'], 'tingkat' => (int) $rombel['tingkat']];
    }

    /**
     * `wali_kelas_diubah` with the old and new wali kelas and `rombel_id` (SEC-59).
     */
    private function catatWaliKelas(LogAktivitas $log, int $rombelId, string $rombel, ?int $lamaId, ?int $baruId, int $pelakuId, ?string $ip): void
    {
        $nama = fn (?int $id): ?string => $id === null ? null : $this->db->table('akun')->select('nama')->where('id', $id)->get()->getRow()->nama;

        $log->catat('wali_kelas_diubah', $pelakuId, $baruId ?? $lamaId, [
            'rombel'  => $rombel,
            'lama'    => $nama($lamaId),
            'baru'    => $nama($baruId),
            'lama_id' => $lamaId,
            'baru_id' => $baruId,
        ], $rombelId, $ip);
    }

    /**
     * @return array{galat: array{nama: string}}
     */
    private function namaBentrok(DatabaseException $e): array
    {
        // 1062 on uq_rombel_nama: another request took the name after the check.
        if ($e->getCode() !== 1062) {
            throw $e;
        }

        return ['galat' => ['nama' => 'Nama kelas sudah dipakai di tahun ajaran ini.']];
    }
}
