<?php

namespace App\Services\Akun;

use App\Models\AkunModel;
use App\Services\Sistem\Jam;
use App\Services\Sistem\NamedLock;
use App\Services\Sistem\Transaction;

/**
 * Change own password (docs/04 FS-AKN-02): password rules (docs/12 SEC-03,
 * SEC-04, docs/11 VAL-21), wrong old passwords as failed logins (SEC-06),
 * and saving. The caller keeps the session in step (ARS-47 item 2, SEC-14).
 */
class GantiPassword
{
    /** Common passwords, one lowercase password per line (SEC-04). */
    public const DAFTAR_UMUM = APPPATH . 'ThirdParty/password-umum/password-umum.txt';

    /** @var array<string, int>|null Loaded once per change request (SEC-04) */
    private ?array $daftarUmum = null;

    /**
     * Checks the form. A wrong old password is recorded as a failed login
     * of the account (SEC-06, SEC-09). $passwordLama is null when it is not
     * asked: the forced change right after login (HAL-AKN-03).
     *
     * @param array<string, mixed> $akun         Row of `akun`
     * @param string|null          $tanggalLahir Student's birth date `YYYY-MM-DD`, or null
     *
     * @return array<string, string> Message per field; empty when everything passes
     */
    public function periksa(
        array $akun,
        #[\SensitiveParameter] ?string $passwordLama,
        #[\SensitiveParameter] string $passwordBaru,
        #[\SensitiveParameter] string $passwordUlang,
        string $ip,
        ?string $tanggalLahir = null,
    ): array {
        $galat     = [];
        $lamaCocok = $passwordLama === null || (new Kredensial())->cocok($passwordLama, $akun['password_hash']);

        if (! $lamaCocok) {
            $this->catatSalah($akun, $ip);
            $galat['password_lama'] = 'Password lama salah.';
        }

        // Compare with the current password only once the old one matched, so this rule cannot test guesses.
        $baru = $this->galat($passwordBaru, $akun['username'], $tanggalLahir, $lamaCocok ? $akun['password_hash'] : null);

        if ($baru !== null) {
            $galat['password_baru'] = $baru;
        }

        if ($passwordUlang !== $passwordBaru) {
            $galat['password_ulang'] = 'Ulangan password tidak sama.';
        }

        return $galat;
    }

    /**
     * First broken password rule (SEC-03) as its VAL-21 message, or null.
     * Username covers the NISN: a student's username is the NISN.
     *
     * @param string|null $tanggalLahir Birth date `YYYY-MM-DD` (students), or null
     * @param string|null $hashSaatIni  Current hash for rule 7, or null to skip it
     */
    public function galat(#[\SensitiveParameter] string $password, string $username, ?string $tanggalLahir = null, ?string $hashSaatIni = null): ?string
    {
        $panjang = mb_strlen($password);
        $kecil   = mb_strtolower($password);

        return match (true) {
            $panjang < 8                => 'Password paling sedikit 8 karakter.',
            $panjang > 64               => 'Password paling panjang 64 karakter.',
            strlen($password) > 72      => 'Password terlalu panjang. Kurangi huruf beraksen atau simbol.',
            // Before the common list (SEC-03 item 3): "zebra" is common, and AC-AKN-02-05 wants "zebra0012345678" to name the NISN.
            str_contains($kecil, mb_strtolower($username)) => 'Password tidak boleh memuat NISN atau username.',
            $this->umum($kecil)         => 'Password ini terlalu umum dan mudah ditebak. Pilih password lain.',
            $tanggalLahir !== null && $this->memuatTanggal($password, $tanggalLahir) => 'Password tidak boleh memuat tanggal lahir.',
            $this->berpola($kecil)      => 'Password tidak boleh berupa huruf atau angka yang berulang atau berurutan.',
            $hashSaatIni !== null && (new Kredensial())->cocok($password, $hashSaatIni) => 'Password baru harus berbeda dengan password saat ini.',
            default                     => null,
        };
    }

    /**
     * Saves the new password, clears the forced-change flag, activates a
     * new student account (docs/02 §7.2) and logs `password_diganti`, in one
     * transaction. Returns the new hash for the session's credential stamp.
     *
     * @param array<string, mixed> $akun Row of `akun`
     */
    public function simpan(array $akun, #[\SensitiveParameter] string $passwordBaru, string $ip): string
    {
        $hash = (new Kredensial())->hash($passwordBaru);
        $data = [
            'password_hash'        => $hash,
            'wajib_ganti_password' => 0,
            'password_diganti_at'  => (new Jam())->now()->toDateTimeString(),
        ];

        if ($akun['jenis'] === 'siswa' && $akun['status'] === 'belum_aktif') {
            $data['status'] = 'aktif';
        }

        (new Transaction())->run(static function () use ($akun, $data, $ip): void {
            // Own change, not an admin form edit: keep updated_at, the forms' version token (docs/07 ARS-40).
            model(AkunModel::class)->builder()->where('id', $akun['id'])->update($data);
            (new LogAktivitas())->catat('password_diganti', (int) $akun['id'], (int) $akun['id'], [
                'wajib' => (int) $akun['wajib_ganti_password'] === 1,
            ], null, $ip);
        });

        return $hash;
    }

    /**
     * Records a wrong old password like a failed login (SEC-06, SEC-09 to SEC-11);
     * these logs stay outside any transaction (SEC-57).
     *
     * @param array<string, mixed> $akun
     */
    private function catatSalah(array $akun, string $ip): void
    {
        $percobaan = new PercobaanLogin();
        $kunci     = new NamedLock();
        $hash      = $percobaan->identitasHash($akun['username']);
        $nama      = $percobaan->namaKunci($hash);
        $dapat     = $kunci->acquire($nama, 5);

        try {
            $sebelum = $percobaan->keadaan($hash, $ip);
            $baru    = $percobaan->catatGagal($hash, $ip);
        } finally {
            if ($dapat) {
                $kunci->release($nama);
            }
        }

        $log = new LogAktivitas();
        $log->catat('login_gagal', null, (int) $akun['id'], ['alasan' => 'password_salah'], null, $ip);

        if ($sebelum === null && $baru !== null) {
            $log->catat('login_dikunci', null, $baru['jenis'] === 'ip' ? null : (int) $akun['id'], [
                'jenis'  => $baru['jenis'],
                'sampai' => $baru['sampai']->toDateTimeString(),
            ], null, $ip);
        }
    }

    /**
     * In the common list as typed, or after trailing digits, punctuation and
     * symbols are removed, so "Indonesia123!" matches "indonesia" (SEC-04).
     */
    private function umum(string $kecil): bool
    {
        $this->daftarUmum ??= array_flip(file(self::DAFTAR_UMUM, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
        $inti = preg_replace('/[\d\p{P}\p{S}\s]+$/u', '', $kecil);

        return isset($this->daftarUmum[$kecil]) || ($inti !== '' && isset($this->daftarUmum[$inti]));
    }

    /**
     * Birth date as DDMMYYYY, DDMMYY, YYYYMMDD or DD-MM-YYYY (SEC-03 item 5).
     */
    private function memuatTanggal(string $password, string $tanggalLahir): bool
    {
        [$y, $m, $d] = explode('-', $tanggalLahir);

        foreach (["{$d}{$m}{$y}", $d . $m . substr($y, 2), "{$y}{$m}{$d}", "{$d}-{$m}-{$y}"] as $bentuk) {
            if (str_contains($password, $bentuk)) {
                return true;
            }
        }

        return false;
    }

    /**
     * One character repeated, or a run of letters or digits going up or down
     * by one, e.g. "aaaaaaaa", "12345678", "abcdefgh" (SEC-03 item 6).
     */
    private function berpola(string $kecil): bool
    {
        if (preg_match('/^(.)\1*$/us', $kecil) === 1) {
            return true;
        }

        if (preg_match('/^(?:[a-z]+|[0-9]+)$/', $kecil) !== 1) {
            return false;
        }

        $langkah = ord($kecil[1]) - ord($kecil[0]);

        for ($i = 2, $n = strlen($kecil); $i < $n; $i++) {
            if (ord($kecil[$i]) - ord($kecil[$i - 1]) !== $langkah) {
                return false;
            }
        }

        return abs($langkah) === 1;
    }
}
