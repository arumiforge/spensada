<?php

namespace App\Services\MasterData;

use CodeIgniter\Database\BaseConnection;

/**
 * Read-only lookups shared by the master data pages, the scope checks, and
 * later phases: the active school year, a student's rombel and status on a
 * date, and the rombel a staff account is wali kelas of.
 *
 * Dates are WIB `Y-m-d` strings passed in by the caller (docs/07 ARS-14).
 */
class Rujukan
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * The active school year, or null when none is active yet (docs/06 §6.2).
     *
     * @return array<string, mixed>|null
     */
    public function tahunAjaranAktif(): ?array
    {
        return $this->db->table('tahun_ajaran')->where('aktif', 1)->get()->getRowArray();
    }

    /**
     * IDs of the rombel in the active school year that the account is wali
     * kelas of (docs/02 §3 item 2). Empty means the account is not wali kelas.
     *
     * @return list<int>
     */
    public function rombelWaliKelas(int $akunId): array
    {
        $rows = $this->db->table('rombel r')->select('r.id')
            ->join('tahun_ajaran ta', 'ta.id = r.tahun_ajaran_id')
            ->where('ta.aktif', 1)->where('r.wali_kelas_id', $akunId)
            ->orderBy('r.id')->get()->getResultArray();

        return array_map('intval', array_column($rows, 'id'));
    }

    /**
     * The student's rombel on a date: the placement covering it, where an
     * empty end means the end of the rombel's school year (docs/06 §6.7
     * rules 1 and 4). Null when the student has no rombel that day.
     *
     * @return array<string, mixed>|null Row of `rombel` plus `penempatan_id`
     */
    public function rombelSiswa(int $siswaId, string $tanggal): ?array
    {
        return $this->db->table('penempatan p')
            ->select('r.*, p.id AS penempatan_id')
            ->join('rombel r', 'r.id = p.rombel_id')
            ->join('tahun_ajaran ta', 'ta.id = r.tahun_ajaran_id')
            ->where('p.siswa_id', $siswaId)
            ->where('p.tanggal_mulai <=', $tanggal)
            ->where('COALESCE(p.tanggal_selesai, ta.tanggal_selesai) >=', $tanggal)
            ->orderBy('p.tanggal_mulai', 'DESC')
            ->get()->getRowArray();
    }

    /**
     * Student status shown in lists (docs/06 §6.6 rules 2 and 5): `aktif`
     * when a counted period covers today, `akan_aktif` when one starts
     * later, else `nonaktif`. Labels: Label::$codes['siswa.status'].
     */
    public function statusSiswa(int $siswaId, string $hariIni): string
    {
        $periode = $this->db->table('masa_aktif')
            ->select('tanggal_mulai')
            ->where('siswa_id', $siswaId)->where('dibatalkan', 0)
            ->groupStart()->where('tanggal_selesai', null)->orWhere('tanggal_selesai >=', $hariIni)->groupEnd()
            ->orderBy('tanggal_mulai')
            ->get()->getResultArray();

        foreach ($periode as $row) {
            if ($row['tanggal_mulai'] <= $hariIni) {
                return 'aktif';
            }
        }

        return $periode === [] ? 'nonaktif' : 'akan_aktif';
    }
}
