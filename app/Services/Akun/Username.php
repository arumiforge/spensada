<?php

namespace App\Services\Akun;

use App\Models\AkunModel;

/**
 * Username rule for staff and station accounts (docs/11 VAL-20, docs/02 §2 item 4).
 */
class Username
{
    /**
     * Lowercase and trimmed, as login cleans identities (docs/12 SEC-01 item 1).
     */
    public function bersihkan(string $username): string
    {
        return strtolower(trim($username));
    }

    /**
     * VAL-20 message for a cleaned username, or null when it is valid.
     *
     * @param int|null $kecualiId Account being edited, so its own username is not "already used"
     */
    public function galat(string $username, ?int $kecualiId = null): ?string
    {
        if (mb_strlen($username) < 3 || mb_strlen($username) > 30) {
            return 'Username 3 sampai 30 karakter.';
        }

        if (preg_match('/^[a-z][a-z0-9._]{2,29}$/', $username) !== 1) {
            return 'Username diawali huruf, dan hanya boleh berisi huruf kecil, angka, titik, dan garis bawah.';
        }

        $model = model(AkunModel::class)->where('username', $username);

        if ($kecualiId !== null) {
            $model->where('id !=', $kecualiId);
        }

        return $model->countAllResults() > 0 ? 'Username sudah dipakai.' : null;
    }
}
