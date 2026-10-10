<?php

namespace Tests\Support;

use App\Services\Sistem\Jam;

/**
 * School years, rombel, students and placements for database and feature
 * tests. Use together with AkunTrait and DatabaseTestTrait ($refresh = true,
 * $namespace = 'App'). Rows are inserted directly, without the services, so
 * a test only depends on the code it checks.
 */
trait MasterDataTrait
{
    /**
     * Inserts a school year with both semesters; returns the `tahun_ajaran` row.
     *
     * @param array<string, mixed> $data Columns that differ from an active 2026/2027
     *
     * @return array<string, mixed>
     */
    protected function buatTahunAjaran(array $data = []): array
    {
        $now = $this->sekarang();
        $row = $data + [
            'nama'            => '2026/2027',
            'tanggal_mulai'   => '2026-07-13',
            'tanggal_selesai' => '2027-06-30',
            'aktif'           => 1,
            'created_at'      => $now,
            'updated_at'      => $now,
        ];
        $this->db->table('tahun_ajaran')->insert($row);
        $id = (int) $this->db->insertID();

        $tengah = (new \DateTimeImmutable($row['tanggal_mulai']))->modify('+5 months')->format('Y-m-d');
        $this->db->table('semester')->insertBatch([
            ['tahun_ajaran_id' => $id, 'jenis' => 'ganjil', 'tanggal_mulai' => $row['tanggal_mulai'], 'tanggal_selesai' => (new \DateTimeImmutable($tengah))->modify('-1 day')->format('Y-m-d'), 'created_at' => $now, 'updated_at' => $now],
            ['tahun_ajaran_id' => $id, 'jenis' => 'genap', 'tanggal_mulai' => $tengah, 'tanggal_selesai' => $row['tanggal_selesai'], 'created_at' => $now, 'updated_at' => $now],
        ]);

        return $this->db->table('tahun_ajaran')->where('id', $id)->get()->getRowArray();
    }

    /**
     * Inserts a rombel; returns the `rombel` row.
     *
     * @param array<string, mixed> $data Must hold `tahun_ajaran_id`; e.g. `wali_kelas_id`
     *
     * @return array<string, mixed>
     */
    protected function buatRombel(array $data): array
    {
        $now = $this->sekarang();
        $this->db->table('rombel')->insert($data + [
            'nama'       => '7A',
            'tingkat'    => 7,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->db->table('rombel')->where('id', $this->db->insertID())->get()->getRowArray();
    }

    /**
     * Inserts a student, one open active period, and a `belum_aktif` student
     * account with username NISN; with `rombel_id`, also a placement.
     * Returns the `siswa` row plus `akun_id`.
     *
     * @param array<string, mixed> $data Columns of `siswa`, plus optional `rombel_id` and `tanggal_mulai`
     *
     * @return array<string, mixed>
     */
    protected function buatSiswa(array $data = []): array
    {
        $now          = $this->sekarang();
        $rombelId     = $data['rombel_id'] ?? null;
        $tanggalMulai = $data['tanggal_mulai'] ?? '2026-07-13';
        unset($data['rombel_id'], $data['tanggal_mulai']);

        $row = $data + [
            'nisn'       => (string) random_int(1_000_000_000, 9_999_999_999),
            'nama'       => 'Siswa Uji',
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $this->db->table('siswa')->insert($row);
        $id = (int) $this->db->insertID();

        $this->db->table('masa_aktif')->insert(['siswa_id' => $id, 'tanggal_mulai' => $tanggalMulai, 'dibatalkan' => 0, 'created_at' => $now, 'updated_at' => $now]);
        $this->db->table('akun')->insert([
            'jenis' => 'siswa', 'username' => $row['nisn'], 'siswa_id' => $id, 'status' => 'belum_aktif',
            'wajib_ganti_password' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $akunId = (int) $this->db->insertID();

        if ($rombelId !== null) {
            $this->tempatkan($id, (int) $rombelId, $tanggalMulai);
        }

        return $this->db->table('siswa')->where('id', $id)->get()->getRowArray() + ['akun_id' => $akunId];
    }

    /**
     * Inserts a placement; returns its ID.
     */
    protected function tempatkan(int $siswaId, int $rombelId, string $tanggalMulai, ?string $tanggalSelesai = null): int
    {
        $now = $this->sekarang();
        $this->db->table('penempatan')->insert([
            'siswa_id' => $siswaId, 'rombel_id' => $rombelId, 'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai, 'created_at' => $now, 'updated_at' => $now,
        ]);

        return (int) $this->db->insertID();
    }

    private function sekarang(): string
    {
        return (new Jam())->now()->toDateTimeString();
    }
}
