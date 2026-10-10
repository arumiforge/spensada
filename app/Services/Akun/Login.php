<?php

namespace App\Services\Akun;

use App\Models\AkunModel;
use App\Services\Sistem\Jam;
use App\Services\Sistem\NamedLock;
use App\Services\Sistem\Transaction;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * Login steps of docs/12 SEC-01 (FS-AKN-01): lock check, password check,
 * status check, failure records and logs. The caller builds the session.
 */
class Login
{
    /** Seconds to wait for the identity's named lock (SEC-10). */
    private const TUNGGU_KUNCI = 5;

    private BaseConnection $db;
    private AkunModel $akunModel;
    private PercobaanLogin $percobaan;
    private Kredensial $kredensial;
    private LogAktivitas $log;
    private NamedLock $kunci;
    private Jam $jam;

    public function __construct(?NamedLock $kunci = null)
    {
        $this->db         = Database::connect();
        $this->akunModel  = model(AkunModel::class);
        $this->percobaan  = new PercobaanLogin();
        $this->kredensial = new Kredensial();
        $this->log        = new LogAktivitas();
        $this->kunci      = $kunci ?? new NamedLock($this->db);
        $this->jam        = new Jam();
    }

    /**
     * Tries one login.
     *
     * - `berhasil`: `akun` is the account row after login.
     * - `gagal`: the generic message of FS-AKN-01 E1/E2.
     * - `dikunci`: `jenis` (pendek, panjang, ip) and `sampai` (Time) of the lock (E3).
     *
     * @return array<string, mixed>
     */
    public function masuk(string $identitas, #[\SensitiveParameter] string $password, string $ip): array
    {
        $identitas = (new Username())->bersihkan($identitas);
        $hash      = $this->percobaan->identitasHash($identitas);
        $namaKunci = $this->percobaan->namaKunci($hash);

        if (! $this->kunci->acquire($namaKunci, self::TUNGGU_KUNCI)) {
            // SEC-10: another attempt for this identity is running; answer as locked.
            return ['hasil' => 'dikunci', 'jenis' => 'pendek', 'sampai' => $this->jam->now()];
        }

        try {
            // SEC-01 item 2: a locked attempt checks no password and is not recorded.
            $kunci = $this->percobaan->keadaan($hash, $ip);

            if ($kunci !== null) {
                return ['hasil' => 'dikunci'] + $kunci;
            }

            $akun   = $this->akunModel->byUsername($identitas);
            $alasan = $this->alasanGagal($akun, $password);

            if ($alasan !== null) {
                $kunci = $this->catatGagal($hash, $ip, $akun, $alasan);

                return $kunci === null ? ['hasil' => 'gagal'] : ['hasil' => 'dikunci'] + $kunci;
            }

            return ['hasil' => 'berhasil', 'akun' => $this->berhasil($akun, $hash, $password, $ip)];
        } finally {
            $this->kunci->release($namaKunci);
        }
    }

    /**
     * Official school name for the login page (docs/08 UI-38), or ''.
     */
    public function namaSekolah(): string
    {
        return $this->pengaturan('sekolah_nama');
    }

    /**
     * Privacy notice under the login form (docs/12 SEC-68); empty when the school cleared it.
     */
    public function teksPrivasi(): string
    {
        return $this->pengaturan('privasi_teks');
    }

    private function pengaturan(string $kunci): string
    {
        return (string) ($this->db->table('pengaturan')->select('nilai')->where('kunci', $kunci)->get()->getRow()->nilai ?? '');
    }

    /**
     * Reason code of docs/12 SEC-59 `login_gagal`, or null when the login may go on.
     * The password is always verified first, so timing does not tell accounts apart (SEC-01 items 3, 4).
     *
     * @param array<string, mixed>|null $akun
     */
    private function alasanGagal(?array $akun, #[\SensitiveParameter] string $password): ?string
    {
        $cocok = $this->kredensial->cocok($password, $akun['password_hash'] ?? null);

        return match (true) {
            $akun === null                        => 'akun_tidak_ada',
            empty($akun['password_hash'])         => 'belum_aktif',
            ! $cocok                              => 'password_salah',
            $akun['status'] === 'aktif',
            $akun['jenis'] === 'siswa' && $akun['status'] === 'belum_aktif' => null,
            $akun['status'] === 'nonaktif'        => 'akun_nonaktif',
            default                               => 'belum_aktif',
        };
    }

    /**
     * Records the failure and logs it outside any transaction (SEC-57).
     * Returns the lock this failure started, or null.
     *
     * @param array<string, mixed>|null $akun
     *
     * @return array<string, mixed>|null
     */
    private function catatGagal(string $hash, string $ip, ?array $akun, string $alasan): ?array
    {
        $akunId = $akun === null ? null : (int) $akun['id'];
        $kunci  = $this->percobaan->catatGagal($hash, $ip);

        $this->log->catat('login_gagal', null, $akunId, ['alasan' => $alasan], null, $ip);

        if ($kunci !== null) {
            // An IP lock is not about this account.
            $this->log->catat('login_dikunci', null, $kunci['jenis'] === 'ip' ? null : $akunId, [
                'jenis'  => $kunci['jenis'],
                'sampai' => $kunci['sampai']->toDateTimeString(),
            ], null, $ip);
        }

        return $kunci;
    }

    /**
     * SEC-01 item 5 and SEC-19: clears failures, rehashes, writes the login
     * time (and a new station login ID) without touching `updated_at` (ARS-40).
     *
     * @param array<string, mixed> $akun
     *
     * @return array<string, mixed>
     */
    private function berhasil(array $akun, string $hash, #[\SensitiveParameter] string $password, string $ip): array
    {
        $id   = (int) $akun['id'];
        $ubah = ['login_terakhir_at' => $this->jam->now()->toDateTimeString()];

        if ($this->kredensial->perluHashUlang($akun['password_hash'])) {
            $ubah['password_hash'] = $this->kredensial->hash($password);
        }

        if ($akun['jenis'] === 'stasiun') {
            $ubah['login_stasiun_id'] = bin2hex(random_bytes(16));
        }

        (new Transaction($this->db))->run(function () use ($akun, $id, $ubah, $hash, $ip): void {
            $this->percobaan->hapus($hash);
            $this->db->table('akun')->where('id', $id)->update($ubah);

            $baru = $ubah['login_stasiun_id'] ?? null;
            $this->log->catat('login_berhasil', $id, $id, ['jenis' => $akun['jenis']] + ($baru === null ? [] : ['login_stasiun_id' => $baru]), null, $ip);

            if ($baru !== null && ! empty($akun['login_stasiun_id'])) {
                $this->log->catat('login_stasiun_berpindah', $id, $id, ['lama' => $akun['login_stasiun_id'], 'baru' => $baru], null, $ip);
            }
        });

        return $ubah + $akun;
    }
}
