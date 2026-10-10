<?php

namespace App\Services\Akun;

use App\Services\MasterData\Rujukan;
use App\Services\Sistem\Jam;
use App\Services\Sistem\Transaction;
use CodeIgniter\Database\BaseConnection;

/**
 * Student accounts: list per rombel, account slips, password reset and
 * login unlock (docs/04 FS-AKN-05 B to D), and the student's own profile
 * in the portal (FS-MD-04 item 6). Scope checks stay in the controller.
 *
 * A rombel's students are those with a placement in it that has not ended
 * by today, so slips can be printed before the school year starts.
 */
class AkunSiswa
{
    /** Student password length (docs/12 SEC-05). */
    private const PANJANG_PASSWORD = 8;

    private Kredensial $kredensial;
    private Jam $jam;
    private BaseConnection $db;

    public function __construct(?Kredensial $kredensial = null, ?Jam $jam = null)
    {
        $this->kredensial = $kredensial ?? new Kredensial();
        $this->jam        = $jam ?? new Jam();
        $this->db         = db_connect();
    }

    /**
     * Rombel of the active school year, by level and name.
     *
     * @return list<array<string, mixed>>
     */
    public function daftarRombel(): array
    {
        return $this->db->table('rombel r')->select('r.*')
            ->join('tahun_ajaran ta', 'ta.id = r.tahun_ajaran_id')
            ->where('ta.aktif', 1)
            ->orderBy('r.tingkat')->orderBy('r.nama')
            ->get()->getResultArray();
    }

    /**
     * A rombel of the active school year, or null.
     *
     * @return array<string, mixed>|null
     */
    public function rombel(int $id): ?array
    {
        foreach ($this->daftarRombel() as $rombel) {
            if ((int) $rombel['id'] === $id) {
                return $rombel;
            }
        }

        return null;
    }

    /**
     * Student accounts of a rombel (FS-AKN-05 A4): siswa_id, nisn, nama,
     * akun_id, status, slip_dibuat_at, login_terakhir_at.
     *
     * @return list<array<string, mixed>>
     */
    public function daftar(int $rombelId): array
    {
        return $this->db->table('siswa s')
            ->select('s.id AS siswa_id, s.nisn, s.nama, a.id AS akun_id, a.status, a.slip_dibuat_at, a.login_terakhir_at')
            ->join('akun a', 'a.siswa_id = s.id')
            ->whereIn('s.id', $this->siswaRombel($rombelId))
            ->orderBy('s.nama')->orderBy('s.id')
            ->get()->getResultArray();
    }

    /**
     * New 8-character passwords for the chosen `belum_aktif` accounts of the
     * rombel, which must be changed at first login (FS-AKN-05 B3, B6). Other
     * IDs are ignored. Returns one slip per account, empty when none was
     * eligible (E1). Old slips of these accounts stop working.
     *
     * @param array<string, mixed> $rombel
     * @param list<int>            $siswaIds
     *
     * @return list<array{nama: string, nisn: string, kelas: string, password: string}>
     */
    public function buatSlip(array $rombel, array $siswaIds, int $pelakuId, ?string $ip): array
    {
        $siswaIds = array_values(array_intersect(array_map('intval', $siswaIds), $this->siswaRombel((int) $rombel['id'])));

        if ($siswaIds === []) {
            return [];
        }

        return (new Transaction())->run(function () use ($rombel, $siswaIds, $pelakuId, $ip): array {
            // Row locks: a second slip or a password change at the same time waits (docs/06 §16).
            $rows = $this->db->query(
                'SELECT a.id, s.nama, s.nisn FROM akun a JOIN siswa s ON s.id = a.siswa_id'
                . ' WHERE a.jenis = ? AND a.status = ? AND a.siswa_id IN ? ORDER BY s.nama, s.id FOR UPDATE',
                ['siswa', 'belum_aktif', $siswaIds],
            )->getResultArray();

            if ($rows === []) {
                return [];
            }

            $now   = $this->jam->now()->toDateTimeString();
            $slips = [];

            foreach ($rows as $row) {
                $password = $this->kredensial->acak(self::PANJANG_PASSWORD);
                $this->db->table('akun')->where('id', $row['id'])->update([
                    'password_hash'        => $this->kredensial->hash($password),
                    'wajib_ganti_password' => 1,
                    'slip_dibuat_at'       => $now,
                    'updated_at'           => $now,
                ]);
                $slips[] = ['nama' => $row['nama'], 'nisn' => $row['nisn'], 'kelas' => $rombel['nama'], 'password' => $password];
            }

            $akunIds = array_map('intval', array_column($rows, 'id'));
            (new LogAktivitas())->catat('slip_dicetak', $pelakuId, null, ['jumlah' => count($akunIds), 'akun_ids' => $akunIds], (int) $rombel['id'], $ip);

            return $slips;
        });
    }

    /**
     * Student with account columns (akun_id, username, status), or null.
     *
     * @return array<string, mixed>|null
     */
    public function cariSiswa(int $siswaId): ?array
    {
        return $this->db->table('siswa s')
            ->select('s.*, a.id AS akun_id, a.username, a.status')
            ->join('akun a', 'a.siswa_id = s.id')
            ->where('s.id', $siswaId)
            ->get()->getRowArray();
    }

    /**
     * New password for one student, to be changed at next login; the status
     * stays as it is and the old sessions end (FS-AKN-05 C). Refused for a
     * `nonaktif` account (E2).
     *
     * @param array<string, mixed> $siswa  From cariSiswa()
     * @param array<string, mixed> $rombel The student's rombel today, or null
     *
     * @return array{pesan: string}|array{slip: array{nama: string, nisn: string, kelas: string, password: string}}
     */
    public function resetPassword(array $siswa, ?array $rombel, int $pelakuId, ?string $ip): array
    {
        $password = $this->kredensial->acak(self::PANJANG_PASSWORD);

        return (new Transaction())->run(function () use ($siswa, $rombel, $password, $pelakuId, $ip): array {
            $status = $this->db->query('SELECT status FROM akun WHERE id = ? FOR UPDATE', [$siswa['akun_id']])->getRow()->status;

            if ($status === 'nonaktif') {
                return ['pesan' => "Akun {$siswa['nama']} nonaktif, sehingga password tidak dapat direset."];
            }

            $now = $this->jam->now()->toDateTimeString();
            $this->db->table('akun')->where('id', $siswa['akun_id'])->update([
                'password_hash'        => $this->kredensial->hash($password),
                'wajib_ganti_password' => 1,
                'slip_dibuat_at'       => $now,
                'updated_at'           => $now,
            ]);
            (new LogAktivitas())->catat('password_direset', $pelakuId, (int) $siswa['akun_id'], [], $rombel === null ? null : (int) $rombel['id'], $ip);

            return ['slip' => ['nama' => $siswa['nama'], 'nisn' => $siswa['nisn'], 'kelas' => $rombel['nama'] ?? '', 'password' => $password]];
        });
    }

    /**
     * Lock on the student's login identity, or null (docs/12 SEC-11).
     *
     * @param array<string, mixed> $siswa From cariSiswa()
     *
     * @return array{jenis: string, sampai: \CodeIgniter\I18n\Time}|null
     */
    public function kunciLogin(array $siswa): ?array
    {
        return (new PercobaanLogin())->keadaanAkun($siswa['username']);
    }

    /**
     * Deletes the identity's failed logins; the password stays (FS-AKN-05 D, SEC-11).
     *
     * @param array<string, mixed>      $siswa  From cariSiswa()
     * @param array<string, mixed>|null $rombel The student's rombel today, for the log (SEC-59)
     */
    public function bukaKunci(array $siswa, ?array $rombel, int $pelakuId, ?string $ip): void
    {
        (new Transaction())->run(static function () use ($siswa, $rombel, $pelakuId, $ip): void {
            $percobaan = new PercobaanLogin();
            $percobaan->hapus($percobaan->identitasHash($siswa['username']));
            (new LogAktivitas())->catat('kunci_login_dibuka', $pelakuId, (int) $siswa['akun_id'], [], $rombel === null ? null : (int) $rombel['id'], $ip);
        });
    }

    /**
     * The student's own profile for the portal (FS-MD-04 item 6, AC-MD-04-06):
     * the `siswa` row, `kelas` (rombel name today or null), and `atribut`
     * (label, tipe, nilai) of the visible extra attributes that have a value.
     *
     * @return array<string, mixed>
     */
    public function profil(int $siswaId): array
    {
        $siswa  = $this->db->table('siswa')->where('id', $siswaId)->get()->getRowArray();
        $rombel = (new Rujukan($this->db))->rombelSiswa($siswaId, $this->jam->today());

        $siswa['kelas']   = $rombel['nama'] ?? null;
        $siswa['atribut'] = $this->db->table('nilai_atribut_siswa n')
            ->select('a.label, a.tipe, n.nilai')
            ->join('atribut_siswa a', 'a.id = n.atribut_id')
            ->where('n.siswa_id', $siswaId)->where('a.aktif', 1)
            ->orderBy('a.urutan')->orderBy('a.id')
            ->get()->getResultArray();

        return $siswa;
    }

    /**
     * IDs of students with a placement in the rombel that has not ended by today.
     *
     * @return list<int>
     */
    private function siswaRombel(int $rombelId): array
    {
        $rows = $this->db->table('penempatan')->select('siswa_id')->distinct()
            ->where('rombel_id', $rombelId)
            ->groupStart()->where('tanggal_selesai', null)->orWhere('tanggal_selesai >=', $this->jam->today())->groupEnd()
            ->get()->getResultArray();

        // [0] keeps whereIn() valid for an empty rombel.
        return array_map('intval', array_column($rows, 'siswa_id')) ?: [0];
    }
}
