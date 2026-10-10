<?php

namespace App\Services\Akun;

use App\Models\AkunModel;
use App\Models\AkunRoleModel;
use App\Services\Sistem\Jam;
use App\Services\Sistem\NamedLock;
use App\Services\Sistem\Transaction;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Normalizer;

/**
 * Staff accounts and their roles (docs/04 FS-AKN-03, docs/12 SEC-27).
 *
 * Changes return `['galat' => [field => message]]` for field errors,
 * `['pesan' => text]` for a refused action, `['konflik' => true]` when the
 * version token no longer matches (docs/07 ARS-40), or the result.
 * Role and status changes run under the named lock `admin`, so at least
 * one active admin always remains (ARS-42, docs/02 §4 item 6).
 */
class AkunStaf
{
    /** Staff password length (docs/12 SEC-05). */
    private const PANJANG_PASSWORD = 12;

    private const KUNCI = 'admin';

    private const PESAN_ADMIN_TERAKHIR = 'Harus ada minimal satu admin aktif.';

    /** Log kinds this page writes, for "changed by whom" (docs/04 §4.6). */
    private const JENIS_PERUBAHAN = ['akun_dibuat', 'akun_diubah', 'role_diubah', 'akun_dinonaktifkan', 'akun_diaktifkan', 'password_direset', 'admin_pertama_dibuat', 'admin_dipulihkan'];

    private Kredensial $kredensial;
    private Jam $jam;

    public function __construct(?Kredensial $kredensial = null, ?Jam $jam = null)
    {
        $this->kredensial = $kredensial ?? new Kredensial();
        $this->jam        = $jam ?? new Jam();
    }

    /**
     * Role codes an admin may give (docs/04 FS-AKN-03 input; Staf is implicit).
     *
     * @return list<string>
     */
    public static function roles(): array
    {
        return array_keys(config('Label')->codes['akun_role.role']);
    }

    /**
     * One page of staff accounts, each with `roles`.
     *
     * @param array{cari?: string, role?: string, status?: string} $saringan Already-valid filters
     *
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function daftar(array $saringan, int $page, int $perPage = 50): array
    {
        $builder = db_connect()->table('akun')->where('jenis', 'staf');

        if (($saringan['cari'] ?? '') !== '') {
            $builder->groupStart()->like('nama', $saringan['cari'])->orLike('username', $saringan['cari'])->groupEnd();
        }
        if (isset($saringan['role'])) {
            $builder->whereIn('id', static fn ($sub) => $sub->select('akun_id')->from('akun_role')->where('role', $saringan['role']));
        }
        if (isset($saringan['status'])) {
            $builder->where('status', $saringan['status']);
        }

        $total = $builder->countAllResults(false);
        $rows  = $builder->orderBy('nama')->orderBy('id')->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();
        $roles = [];

        if ($rows !== []) {
            foreach (model(AkunRoleModel::class)->whereIn('akun_id', array_column($rows, 'id'))->orderBy('role')->findAll() as $r) {
                $roles[$r['akun_id']][] = $r['role'];
            }
        }

        foreach ($rows as &$row) {
            $row['roles'] = $roles[$row['id']] ?? [];
        }

        return ['rows' => $rows, 'total' => $total];
    }

    /**
     * Staff account with `roles`, or null when there is no staff account with this ID.
     *
     * @return array<string, mixed>|null
     */
    public function cari(int $id): ?array
    {
        $akun = model(AkunModel::class)->where('jenis', 'staf')->find($id);

        if ($akun !== null) {
            $akun['roles'] = model(AkunRoleModel::class)->rolesOf($id);
        }

        return $akun;
    }

    /**
     * Who made the latest change to the account and when, if logged.
     *
     * @return array{nama: string|null, waktu: string}|null
     */
    public function perubahanTerakhir(int $id): ?array
    {
        $row = db_connect()->table('log_aktivitas l')
            ->select('p.nama, l.created_at')
            ->join('akun p', 'p.id = l.pelaku_id', 'left')
            ->where('l.akun_id', $id)
            ->whereIn('l.jenis', self::JENIS_PERUBAHAN)
            ->orderBy('l.id', 'DESC')
            ->limit(1)->get()->getRowArray();

        return $row === null ? null : ['nama' => $row['nama'], 'waktu' => $row['created_at']];
    }

    /**
     * Adds a staff account with a random password that must be changed
     * at first login (FS-AKN-03 item 1).
     *
     * @param list<string> $roles
     *
     * @return array{galat: array<string, string>}|array{id: int, password: string}
     */
    public function buat(string $nama, string $username, array $roles, int $pelakuId, ?string $ip): array
    {
        [$nama, $username, $roles, $galat] = $this->periksa($nama, $username, $roles, null);

        if ($galat !== []) {
            return ['galat' => $galat];
        }

        $password = $this->kredensial->acak(self::PANJANG_PASSWORD);

        try {
            $id = (new Transaction())->run(function () use ($nama, $username, $roles, $password, $pelakuId, $ip): int {
                $id = (int) model(AkunModel::class)->insert([
                    'jenis'                => 'staf',
                    'username'             => $username,
                    'nama'                 => $nama,
                    'password_hash'        => $this->kredensial->hash($password),
                    'status'               => 'aktif',
                    'wajib_ganti_password' => 1,
                ]);
                $this->tambahRoles($id, $roles, $pelakuId);
                (new LogAktivitas())->catat('akun_dibuat', $pelakuId, $id, ['nama' => $nama, 'username' => $username, 'roles' => $roles], null, $ip);

                return $id;
            });
        } catch (DatabaseException $e) {
            return $this->usernameBentrok($e);
        }

        return ['id' => $id, 'password' => $password];
    }

    /**
     * Saves name, username and roles when `updated_at` still equals $versi
     * (FS-AKN-03 item 2, ARS-40). Refuses to drop the last active admin, and
     * an admin dropping their own Admin role (SEC-27).
     *
     * @param list<string> $roles
     *
     * @return array{galat: array<string, string>}|array{pesan: string}|array{konflik: true}|array{ok: true}
     */
    public function perbarui(int $id, string $versi, string $nama, string $username, array $roles, int $pelakuId, ?string $ip): array
    {
        [$nama, $username, $roles, $galat] = $this->periksa($nama, $username, $roles, $id);

        if ($galat !== []) {
            return ['galat' => $galat];
        }

        try {
            return $this->denganKunci(fn (): array => (new Transaction())->run(function () use ($id, $versi, $nama, $username, $roles, $pelakuId, $ip): array {
                $lama = $this->cari($id);

                if ($lama === null) {
                    return ['konflik' => true];
                }

                $dicabut  = array_values(array_diff($lama['roles'], $roles));
                $ditambah = array_values(array_diff($roles, $lama['roles']));

                if (in_array('admin', $dicabut, true)) {
                    if ($lama['status'] === 'aktif' && $this->jumlahAdminAktifLain($id) === 0) {
                        return ['pesan' => self::PESAN_ADMIN_TERAKHIR];
                    }
                    if ($id === $pelakuId) {
                        // docs/12 SEC-27; docs/11 §5.1 has no text for this case.
                        return ['pesan' => 'Anda tidak dapat mencabut role Admin dari akun sendiri.'];
                    }
                }

                $db = db_connect();
                $db->table('akun')->where('id', $id)->where('updated_at', $versi)->update([
                    'nama'       => $nama,
                    'username'   => $username,
                    'updated_at' => $this->jam->now()->toDateTimeString(),
                ]);

                // foundRows = true: matched rows, even when no value changed (ARS-40).
                if ($db->affectedRows() === 0) {
                    return ['konflik' => true];
                }

                $berubah = [];
                foreach (['nama' => $nama, 'username' => $username] as $kolom => $baru) {
                    if ($lama[$kolom] !== $baru) {
                        $berubah[$kolom] = ['lama' => $lama[$kolom], 'baru' => $baru];
                    }
                }

                $log = new LogAktivitas();

                if ($berubah !== []) {
                    $log->catat('akun_diubah', $pelakuId, $id, $berubah, null, $ip);
                }
                if ($dicabut !== [] || $ditambah !== []) {
                    if ($dicabut !== []) {
                        model(AkunRoleModel::class)->where('akun_id', $id)->whereIn('role', $dicabut)->delete();
                    }
                    $this->tambahRoles($id, $ditambah, $pelakuId);
                    $log->catat('role_diubah', $pelakuId, $id, ['ditambah' => $ditambah, 'dicabut' => $dicabut], null, $ip);
                }

                return ['ok' => true];
            }));
        } catch (DatabaseException $e) {
            return $this->usernameBentrok($e);
        }
    }

    /**
     * Deactivates an active account (FS-AKN-03 items 4, 6, 7). The `sesi`
     * filter ends its sessions on their next request.
     *
     * @return array{pesan: string}|array{ok: true}
     */
    public function nonaktifkan(int $id, int $pelakuId, ?string $ip): array
    {
        if ($id === $pelakuId) {
            return ['pesan' => 'Anda tidak dapat menonaktifkan akun sendiri.'];
        }

        return $this->denganKunci(fn (): array => (new Transaction())->run(function () use ($id, $pelakuId, $ip): array {
            $akun = $this->cari($id);

            if (in_array('admin', $akun['roles'], true) && $this->jumlahAdminAktifLain($id) === 0) {
                return ['pesan' => self::PESAN_ADMIN_TERAKHIR];
            }

            if ($this->ubahStatus($id, 'aktif', 'nonaktif')) {
                (new LogAktivitas())->catat('akun_dinonaktifkan', $pelakuId, $id, [], null, $ip);
            }

            return ['ok' => true];
        }));
    }

    /**
     * Activates a deactivated account again (FS-AKN-03 item 4).
     *
     * @return array{pesan: string}|array{ok: true}
     */
    public function aktifkan(int $id, int $pelakuId, ?string $ip): array
    {
        return $this->denganKunci(fn (): array => (new Transaction())->run(function () use ($id, $pelakuId, $ip): array {
            if ($this->ubahStatus($id, 'nonaktif', 'aktif')) {
                (new LogAktivitas())->catat('akun_diaktifkan', $pelakuId, $id, [], null, $ip);
            }

            return ['ok' => true];
        }));
    }

    /**
     * New random password that must be changed at next login; the old one
     * stops working and the account's sessions end (FS-AKN-03 item 5).
     */
    public function resetPassword(int $id, int $pelakuId, ?string $ip): string
    {
        $password = $this->kredensial->acak(self::PANJANG_PASSWORD);

        (new Transaction())->run(function () use ($id, $password, $pelakuId, $ip): void {
            model(AkunModel::class)->update($id, [
                'password_hash'        => $this->kredensial->hash($password),
                'wajib_ganti_password' => 1,
            ]);
            (new LogAktivitas())->catat('password_direset', $pelakuId, $id, [], null, $ip);
        });

        return $password;
    }

    /**
     * Lock on the account's login identity, or null (docs/12 SEC-11).
     *
     * @param array<string, mixed> $akun
     *
     * @return array{jenis: string, sampai: \CodeIgniter\I18n\Time}|null
     */
    public function kunciLogin(array $akun): ?array
    {
        return (new PercobaanLogin())->keadaanAkun($akun['username']);
    }

    /**
     * Deletes the identity's failed logins so the account can log in again (SEC-11).
     *
     * @param array<string, mixed> $akun
     */
    public function bukaKunci(array $akun, int $pelakuId, ?string $ip): void
    {
        (new Transaction())->run(static function () use ($akun, $pelakuId, $ip): void {
            $percobaan = new PercobaanLogin();
            $percobaan->hapus($percobaan->identitasHash($akun['username']));
            (new LogAktivitas())->catat('kunci_login_dibuka', $pelakuId, (int) $akun['id'], [], null, $ip);
        });
    }

    /**
     * Cleans the fields (docs/11 VAL-02) and checks them all (VAL-15, VAL-20, VAL-09).
     *
     * @param list<mixed> $roles
     *
     * @return array{string, string, list<string>, array<string, string>}
     */
    private function periksa(string $nama, string $username, array $roles, ?int $kecualiId): array
    {
        $nama     = preg_replace('/ {2,}/', ' ', trim((string) Normalizer::normalize($nama)));
        $username = (new Username())->bersihkan($username);
        $roles    = array_values(array_unique(array_map('strval', $roles)));
        $galat    = [];

        if ($nama === '') {
            $galat['nama'] = 'Nama lengkap wajib diisi.';
        } elseif (mb_strlen($nama) < 2) {
            $galat['nama'] = 'Nama paling sedikit 2 karakter.';
        } elseif (mb_strlen($nama) > 100) {
            $galat['nama'] = 'Nama paling panjang 100 karakter.';
        } elseif (preg_match("/^(?=.*\\p{L})[\\p{L}\\p{M} .,'’-]+$/u", $nama) !== 1) {
            $galat['nama'] = 'Nama hanya boleh berisi huruf, spasi, titik, koma, petik, dan tanda hubung.';
        }

        if (($pesan = (new Username())->galat($username, $kecualiId)) !== null) {
            $galat['username'] = $pesan;
        }

        if (array_diff($roles, self::roles()) !== []) {
            $galat['role'] = 'Role yang dipilih tidak tersedia. Pilih dari daftar.';
        }

        sort($roles);

        return [$nama, $username, $roles, $galat];
    }

    /**
     * @param list<string> $roles
     */
    private function tambahRoles(int $akunId, array $roles, int $pelakuId): void
    {
        foreach ($roles as $role) {
            model(AkunRoleModel::class)->insert(['akun_id' => $akunId, 'role' => $role, 'diberikan_oleh' => $pelakuId]);
        }
    }

    private function jumlahAdminAktifLain(int $id): int
    {
        return db_connect()->table('akun')
            ->join('akun_role', 'akun_role.akun_id = akun.id')
            ->where(['akun_role.role' => 'admin', 'akun.status' => 'aktif', 'akun.id !=' => $id])
            ->countAllResults();
    }

    /**
     * Status change guarded by the current status (docs/07 ARS-41); false when
     * the account was not in $dari, e.g. a second click.
     */
    private function ubahStatus(int $id, string $dari, string $ke): bool
    {
        $db = db_connect();
        $db->table('akun')->where(['id' => $id, 'jenis' => 'staf', 'status' => $dari])
            ->update(['status' => $ke, 'updated_at' => $this->jam->now()->toDateTimeString()]);

        return $db->affectedRows() > 0;
    }

    /**
     * @return array{galat: array{username: string}}
     */
    private function usernameBentrok(DatabaseException $e): array
    {
        // 1062 on uq_akun_username: another request took the name after the check.
        if ($e->getCode() !== 1062) {
            throw $e;
        }

        return ['galat' => ['username' => 'Username sudah dipakai.']];
    }

    /**
     * @param callable(): array<string, mixed> $action
     *
     * @return array<string, mixed>
     */
    private function denganKunci(callable $action): array
    {
        $lock = new NamedLock();

        if (! $lock->acquire(self::KUNCI, 5)) {
            return ['pesan' => 'Data admin sedang diubah di tempat lain. Coba lagi beberapa saat lagi.'];
        }

        try {
            return $action();
        } finally {
            $lock->release(self::KUNCI);
        }
    }
}
