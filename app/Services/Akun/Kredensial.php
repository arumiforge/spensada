<?php

namespace App\Services\Akun;

use RuntimeException;

/**
 * Password hashing, random passwords and the credential stamp
 * (docs/12 SEC-02, SEC-05, docs/07 ARS-30, ARS-47).
 */
class Kredensial
{
    /** bcrypt cost (SEC-02); change only with a release, since it ends sessions. */
    public const COST = 11;

    /** 31 characters without i, l, o, 0 and 1, which are easy to misread (SEC-05). */
    private const ALFABET = 'abcdefghjkmnpqrstuvwxyz23456789';

    /** Hash of a random password with the same cost, for accounts without one (SEC-01 item 3). */
    private const HASH_PENGGANTI = '$2y$11$VGoMGo4oE2u0VwzHrTuYY.QHRW.sF501lbeF1rFUQLKDQWCIkB2Ty';

    /**
     * Random password: 8 characters for students, 12 for staff and stations (SEC-05).
     */
    public function acak(int $panjang): string
    {
        $password = '';

        for ($i = 0; $i < $panjang; $i++) {
            $password .= self::ALFABET[random_int(0, strlen(self::ALFABET) - 1)];
        }

        return $password;
    }

    public function hash(#[\SensitiveParameter] string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => self::COST]);
    }

    /**
     * Checks a password. Without a hash it still verifies against a stand-in
     * hash, so the answer takes the same time (SEC-01 item 3).
     */
    public function cocok(#[\SensitiveParameter] string $password, ?string $hash): bool
    {
        if ($hash === null || $hash === '') {
            password_verify($password, self::HASH_PENGGANTI);

            return false;
        }

        return password_verify($password, $hash);
    }

    public function perluHashUlang(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => self::COST]);
    }

    /**
     * Credential stamp kept in the session: changes whenever the password
     * hash changes, so other sessions end (ARS-47 item 2). The hash itself
     * never goes into the session.
     */
    public function cap(?string $hash): string
    {
        return hash_hmac('sha256', (string) $hash, self::kunci('spensada-cap-kredensial'));
    }

    /**
     * Key derived from the app encryption key for one purpose (SEC-09, SEC-18).
     */
    public static function kunci(string $konteks): string
    {
        $key = config('Encryption')->key;

        if ($key === '') {
            throw new RuntimeException('encryption.key is not set; run `php spark key:generate`.');
        }

        return hash_hkdf('sha256', $key, 32, $konteks);
    }
}
