<?php

namespace App\Services\MasterData;

use CodeIgniter\Database\BaseConnection;

/**
 * Student list, search and profile reads (docs/04 FS-MD-04 items 6–7,
 * docs/09 HAL-MD-04, HAL-MD-06, RT-14, docs/10 EP-MD-01). Read only.
 *
 * Scope comes in as $rombelIds: null for scope Semua, else the rombel IDs
 * the actor is wali kelas of. A student is in scope when their placement
 * TODAY is in that list (docs/04 §4.1 item 4), so out-of-scope students never
 * appear (§4.1 item 3). Dates are WIB `Y-m-d` strings from the caller.
 */
class DaftarSiswa
{
    public const PER_PAGE = 50;

    /** Search results shown at most (docs/09 RT-14 item 4). */
    public const BATAS_CARI = 20;

    /** Filter `status` values; `aktif` is the default (HAL-MD-04). */
    public const STATUS = ['aktif', 'akan_aktif', 'nonaktif', 'semua'];

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * One page of students, by name.
     *
     * @param array{cari?: string, kelas?: int, status?: string, tanpa_wa?: bool, tanpa_foto?: bool, tanpa_kelas?: bool} $saringan Already-valid filters
     * @param list<int>|null $rombelIds Scope, see the class docblock
     *
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function daftar(array $saringan, ?array $rombelIds, string $hariIni, int $page, int $perPage = self::PER_PAGE): array
    {
        return $this->query($saringan, $rombelIds, $hariIni, $perPage, ($page - 1) * $perPage);
    }

    /**
     * Search by part of the name, or the start of the NISN when $cari is
     * digits only, over every status (EP-MD-01 `untuk=profil`).
     *
     * @param list<int>|null $rombelIds
     *
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function cari(string $cari, ?array $rombelIds, string $hariIni): array
    {
        return $this->query(['cari' => $cari, 'status' => 'semua'], $rombelIds, $hariIni, self::BATAS_CARI, 0);
    }

    /**
     * @return array<string, mixed>|null Row of `siswa`
     */
    public function satu(int $id): ?array
    {
        return $this->db->table('siswa')->where('id', $id)->get()->getRowArray();
    }

    /**
     * The student account, or null.
     *
     * @return array<string, mixed>|null
     */
    public function akun(int $siswaId): ?array
    {
        return $this->db->table('akun')->where(['jenis' => 'siswa', 'siswa_id' => $siswaId])->get()->getRowArray();
    }

    /**
     * Active periods, newest first (docs/06 §6.6).
     *
     * @return list<array<string, mixed>>
     */
    public function masaAktif(int $siswaId): array
    {
        return $this->db->table('masa_aktif')->where('siswa_id', $siswaId)
            ->orderBy('tanggal_mulai', 'DESC')->orderBy('id', 'DESC')->get()->getResultArray();
    }

    /**
     * Placements with rombel and school year names, newest first. `selesai`
     * is the effective end (docs/06 §6.7 rule 1).
     *
     * @return list<array<string, mixed>>
     */
    public function riwayatPenempatan(int $siswaId): array
    {
        return $this->db->table('penempatan p')
            ->select('p.*, r.nama AS rombel_nama, ta.nama AS tahun_ajaran_nama, COALESCE(p.tanggal_selesai, ta.tanggal_selesai) AS selesai')
            ->join('rombel r', 'r.id = p.rombel_id')
            ->join('tahun_ajaran ta', 'ta.id = r.tahun_ajaran_id')
            ->where('p.siswa_id', $siswaId)
            ->orderBy('p.tanggal_mulai', 'DESC')->orderBy('p.id', 'DESC')->get()->getResultArray();
    }

    /**
     * Active extra attributes in display order, each with the student's
     * `nilai` (null when empty) (docs/04 FS-MD-09 item 2).
     *
     * @return list<array<string, mixed>>
     */
    public function atribut(?int $siswaId): array
    {
        $rows = $this->db->table('atribut_siswa')->where('aktif', 1)->orderBy('urutan')->orderBy('id')->get()->getResultArray();
        $nilai = [];

        if ($siswaId !== null && $rows !== []) {
            foreach ($this->db->table('nilai_atribut_siswa')->where('siswa_id', $siswaId)->get()->getResultArray() as $n) {
                $nilai[$n['atribut_id']] = $n['nilai'];
            }
        }

        foreach ($rows as &$row) {
            $row['pilihan'] = $row['pilihan'] === null ? [] : (array) json_decode($row['pilihan'], true);
            $row['nilai']   = $nilai[$row['id']] ?? null;
        }

        return $rows;
    }

    /**
     * Rombel a student can be placed in from $hariIni: those of school years
     * that have not ended, by year, grade and name.
     *
     * @return list<array<string, mixed>> Rows of `rombel` plus `tahun_ajaran` (its name), as panel/penempatan/_pilih_kelas expects
     */
    public function pilihanRombel(string $hariIni): array
    {
        return $this->db->table('rombel r')
            ->select('r.*, ta.nama AS tahun_ajaran')
            ->join('tahun_ajaran ta', 'ta.id = r.tahun_ajaran_id')
            ->where('ta.tanggal_selesai >=', $hariIni)
            ->orderBy('ta.tanggal_mulai')->orderBy('r.tingkat')->orderBy('r.nama')->get()->getResultArray();
    }

    /**
     * Rombel of the active school year, for the `kelas` filter.
     *
     * @param list<int>|null $rombelIds Scope; a wali kelas sees only their own
     *
     * @return list<array<string, mixed>>
     */
    public function rombelAktif(?array $rombelIds): array
    {
        $builder = $this->db->table('rombel r')->select('r.id, r.nama')
            ->join('tahun_ajaran ta', 'ta.id = r.tahun_ajaran_id')->where('ta.aktif', 1);

        if ($rombelIds !== null) {
            $rombelIds === [] ? $builder->where('1 = 0') : $builder->whereIn('r.id', $rombelIds);
        }

        return $builder->orderBy('r.tingkat')->orderBy('r.nama')->get()->getResultArray();
    }

    /**
     * @param array<string, mixed> $saringan
     * @param list<int>|null       $rombelIds
     *
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    private function query(array $saringan, ?array $rombelIds, string $hariIni, int $limit, int $offset): array
    {
        $binds = ['hari' => $hariIni];
        // Counted period covering today / starting later (docs/06 §6.6 rules 1, 2, 5).
        $aktif = 'EXISTS (SELECT 1 FROM masa_aktif m WHERE m.siswa_id = s.id AND m.dibatalkan = 0 AND m.tanggal_mulai <= :hari: AND (m.tanggal_selesai IS NULL OR m.tanggal_selesai >= :hari:))';
        $akan  = 'EXISTS (SELECT 1 FROM masa_aktif m WHERE m.siswa_id = s.id AND m.dibatalkan = 0 AND m.tanggal_mulai > :hari:)';
        // Placements never overlap, so at most one covers today (docs/06 §6.7 rules 1, 4).
        $from = 'FROM siswa s LEFT JOIN (SELECT p.siswa_id, r.id AS rombel_id, r.nama AS rombel_nama'
            . ' FROM penempatan p JOIN rombel r ON r.id = p.rombel_id JOIN tahun_ajaran ta ON ta.id = r.tahun_ajaran_id'
            . ' WHERE p.tanggal_mulai <= :hari: AND COALESCE(p.tanggal_selesai, ta.tanggal_selesai) >= :hari:) k ON k.siswa_id = s.id';
        $where = [];

        if ($rombelIds !== null) {
            $where[] = $rombelIds === [] ? '1 = 0' : 'k.rombel_id IN (' . implode(',', array_map('intval', $rombelIds)) . ')';
        }
        if (($saringan['cari'] ?? '') !== '') {
            // Wildcards only: the bind escapes quotes (escapeLikeString() would escape them twice).
            $binds['nama'] = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $saringan['cari']) . '%';
            $cocok         = "s.nama LIKE :nama: ESCAPE '!'";
            if (ctype_digit($saringan['cari'])) {
                $binds['nisn'] = $saringan['cari'] . '%';
                $cocok         = "({$cocok} OR s.nisn LIKE :nisn:)";
            }
            $where[] = $cocok;
        }
        if (isset($saringan['kelas'])) {
            $binds['kelas'] = (int) $saringan['kelas'];
            $where[]        = 'k.rombel_id = :kelas:';
        }
        $where[] = match ($saringan['status'] ?? 'aktif') {
            'aktif'      => $aktif,
            'akan_aktif' => "NOT {$aktif} AND {$akan}",
            'nonaktif'   => "NOT {$aktif} AND NOT {$akan}",
            default      => '1 = 1',
        };
        if (! empty($saringan['tanpa_wa'])) {
            $where[] = 's.wa_ortu IS NULL';
        }
        if (! empty($saringan['tanpa_foto'])) {
            $where[] = 's.foto_file IS NULL';
        }
        if (! empty($saringan['tanpa_kelas'])) {
            // Active today without a placement today (FS-PRS-05 E1).
            $where[] = "{$aktif} AND k.siswa_id IS NULL";
        }

        $tail  = $from . ' WHERE ' . implode(' AND ', $where);
        $total = (int) $this->db->query("SELECT COUNT(*) AS n {$tail}", $binds)->getRow()->n;
        $rows  = $this->db->query(
            "SELECT s.id, s.nisn, s.nama, s.wa_ortu, s.foto_file, k.rombel_id, k.rombel_nama,"
            . " CASE WHEN {$aktif} THEN 'aktif' WHEN {$akan} THEN 'akan_aktif' ELSE 'nonaktif' END AS status"
            . " {$tail} ORDER BY s.nama, s.id LIMIT {$limit} OFFSET {$offset}",
            $binds,
        )->getResultArray();

        return ['rows' => $rows, 'total' => $total];
    }
}
