<?php

namespace App\Services\Akun;

use App\Models\AkunModel;
use App\Models\AkunRoleModel;
use App\Services\Sistem\NamedLock;
use App\Services\Sistem\Transaction;

/**
 * First admin and admin recovery behind `admin:pertama` and
 * `admin:pulihkan` (docs/07 ARS-49). Both run from the server terminal, so
 * the log entries have no actor (docs/12 SEC-59).
 *
 * Results are `['password' => ...]` on success or `['galat' => ...]` with
 * text for the operator. The password is returned once and only its hash
 * is stored (docs/12 SEC-05).
 */
class AdminAwal
{
    /** Staff password length (SEC-05). */
    private const PANJANG_PASSWORD = 12;

    /** docs/07 ARS-42: changes to admin role and status. */
    private const KUNCI = 'admin';

    private Kredensial $kredensial;

    public function __construct(?Kredensial $kredensial = null)
    {
        $this->kredensial = $kredensial ?? new Kredensial();
    }

    /**
     * Creates the first admin; refused while an active admin exists.
     *
     * @return array{password: string}|array{galat: string}
     */
    public function buatPertama(string $nama, string $username): array
    {
        $nama     = trim($nama);
        $username = (new Username())->bersihkan($username);

        if (($galat = $this->galatNama($nama)) !== null) {
            return ['galat' => $galat];
        }

        return $this->withLock(function () use ($nama, $username): array {
            if ($this->adaAdminAktif()) {
                return ['galat' => 'Sudah ada admin aktif. Admin pertama hanya dibuat sekali. Bila admin lupa password, pakai php spark admin:pulihkan <username>.'];
            }

            if (($galat = (new Username())->galat($username)) !== null) {
                return ['galat' => $galat];
            }

            $password = $this->kredensial->acak(self::PANJANG_PASSWORD);

            (new Transaction())->run(function () use ($nama, $username, $password): void {
                $akunId = (int) model(AkunModel::class)->insert([
                    'jenis'                => 'staf',
                    'username'             => $username,
                    'nama'                 => $nama,
                    'password_hash'        => $this->kredensial->hash($password),
                    'status'               => 'aktif',
                    'wajib_ganti_password' => 1,
                ]);
                // diberikan_oleh stays empty for the first admin (docs/06 §5.2).
                model(AkunRoleModel::class)->insert(['akun_id' => $akunId, 'role' => 'admin', 'diberikan_oleh' => null]);
                (new LogAktivitas())->catat('admin_pertama_dibuat', null, $akunId, ['username' => $username]);
            });

            return ['password' => $password];
        });
    }

    /**
     * New random password for an active admin; the new hash changes the
     * credential stamp, so the account's sessions end (ARS-47 item 2).
     *
     * @return array{password: string}|array{galat: string}
     */
    public function pulihkan(string $username): array
    {
        $username = (new Username())->bersihkan($username);

        return $this->withLock(function () use ($username): array {
            $akun = model(AkunModel::class)->byUsername($username);

            if ($akun === null || $akun['jenis'] !== 'staf' || $akun['status'] !== 'aktif'
                || ! in_array('admin', model(AkunRoleModel::class)->rolesOf((int) $akun['id']), true)) {
                return ['galat' => "Akun \"{$username}\" bukan akun admin yang aktif. Periksa lagi username-nya."];
            }

            $password = $this->kredensial->acak(self::PANJANG_PASSWORD);

            (new Transaction())->run(function () use ($akun, $username, $password): void {
                model(AkunModel::class)->update($akun['id'], [
                    'password_hash'        => $this->kredensial->hash($password),
                    'wajib_ganti_password' => 1,
                ]);
                (new LogAktivitas())->catat('admin_dipulihkan', null, (int) $akun['id'], ['username' => $username]);
            });

            return ['password' => $password];
        });
    }

    /**
     * docs/11 VAL-15 message for a staff name, or null when it is valid.
     */
    private function galatNama(string $nama): ?string
    {
        if (mb_strlen($nama) < 2) {
            return 'Nama paling sedikit 2 karakter.';
        }

        if (mb_strlen($nama) > 100) {
            return 'Nama paling panjang 100 karakter.';
        }

        if (preg_match("/^(?=.*\\p{L})[\\p{L}\\p{M} .,'’-]+$/u", $nama) !== 1) {
            return 'Nama hanya boleh berisi huruf, spasi, titik, koma, petik, dan tanda hubung.';
        }

        return null;
    }

    private function adaAdminAktif(): bool
    {
        return model(AkunModel::class)
            ->join('akun_role', 'akun_role.akun_id = akun.id')
            ->where(['akun_role.role' => 'admin', 'akun.status' => 'aktif'])
            ->countAllResults() > 0;
    }

    /**
     * @param callable(): array $action
     */
    private function withLock(callable $action): array
    {
        $lock = new NamedLock();

        if (! $lock->acquire(self::KUNCI, 5)) {
            return ['galat' => 'Data admin sedang diubah di tempat lain. Coba lagi beberapa saat lagi.'];
        }

        try {
            return $action();
        } finally {
            $lock->release(self::KUNCI);
        }
    }
}
