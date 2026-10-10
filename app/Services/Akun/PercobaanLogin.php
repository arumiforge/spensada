<?php

namespace App\Services\Akun;

use App\Models\PercobaanLoginModel;
use App\Services\Sistem\Jam;
use CodeIgniter\I18n\Time;

/**
 * Failed-login records and login locks (docs/06 §5.4, docs/12 SEC-08 to SEC-11).
 *
 * Windows slide: a lock holds while enough failures sit inside the window,
 * so it ends by itself. Callers run check, verify and record under the named
 * lock `login:<first 40 characters of the identity hash>` (SEC-10).
 */
class PercobaanLogin
{
    /** Lock kinds: [failures, window in seconds] (SEC-08). */
    private const BATAS = [
        'panjang' => [20, 86400],
        'pendek'  => [5, 900],
        'ip'      => [100, 900],
    ];

    private PercobaanLoginModel $model;
    private Jam $jam;

    public function __construct(?PercobaanLoginModel $model = null, ?Jam $jam = null)
    {
        $this->model = $model ?? model(PercobaanLoginModel::class);
        $this->jam   = $jam ?? new Jam();
    }

    /**
     * HMAC-SHA256 of the cleaned identity; the identity is never stored as text (SEC-09).
     */
    public function identitasHash(string $identitas): string
    {
        return hash_hmac('sha256', $identitas, Kredensial::kunci('spensada-percobaan-login'));
    }

    /**
     * Named lock for one identity (SEC-10), for NamedLock::acquire().
     */
    public function namaKunci(string $identitasHash): string
    {
        return 'login:' . substr($identitasHash, 0, 40);
    }

    /**
     * Lock in force for this identity or IP, or null.
     * `sampai` is when the lock ends: the oldest failure in the window plus the window.
     *
     * @return array{jenis: string, sampai: Time}|null
     */
    public function keadaan(string $identitasHash, ?string $ip = null): ?array
    {
        foreach (self::BATAS as $jenis => [$batas, $detik]) {
            if ($jenis === 'ip' && $ip === null) {
                continue;
            }

            $kolom  = $jenis === 'ip' ? 'ip' : 'identitas_hash';
            $nilai  = $jenis === 'ip' ? $ip : $identitasHash;
            $sejak  = $this->jam->now()->subSeconds($detik);
            $baris  = $this->model->select('created_at')
                ->where($kolom, $nilai)
                ->where('created_at >', $sejak->toDateTimeString())
                ->orderBy('created_at', 'ASC')
                ->findAll();

            if (count($baris) >= $batas) {
                return [
                    'jenis'  => $jenis,
                    'sampai' => Time::parse($baris[0]['created_at'], 'Asia/Jakarta')->addSeconds($detik),
                ];
            }
        }

        return null;
    }

    /**
     * Lock on the identity only (not the IP), for the unlock button (SEC-11).
     *
     * @return array{jenis: string, sampai: Time}|null
     */
    public function keadaanAkun(string $username): ?array
    {
        return $this->keadaan($this->identitasHash($username));
    }

    /**
     * Records one failure. Returns the lock this failure started, or null.
     * Call only when keadaan() was null, so any lock now is new.
     *
     * @return array{jenis: string, sampai: Time}|null
     */
    public function catatGagal(string $identitasHash, string $ip): ?array
    {
        $this->model->insert([
            'identitas_hash' => $identitasHash,
            'ip'             => $ip,
            'created_at'     => $this->jam->now()->toDateTimeString(),
        ]);

        return $this->keadaan($identitasHash, $ip);
    }

    /**
     * Deletes the identity's failures: after a successful login, or when an
     * admin or homeroom teacher unlocks it (SEC-09, SEC-11). The IP lock stays.
     */
    public function hapus(string $identitasHash): void
    {
        $this->model->where('identitas_hash', $identitasHash)->delete();
    }
}
