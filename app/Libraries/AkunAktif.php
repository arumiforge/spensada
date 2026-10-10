<?php

namespace App\Libraries;

use App\Services\Akun\HakAkses;
use App\Services\MasterData\Rujukan;
use App\Services\Sistem\Jam;

/**
 * The logged-in account for this request, loaded by the `sesi` filter
 * (docs/07 ARS-47). Controllers read the actor here and pass it to services;
 * services never read it themselves (ARS-14). Get it with service('akunAktif').
 */
class AkunAktif
{
    /** @var array<string, mixed>|null */
    private ?array $akun = null;

    /** @var list<string> */
    private array $roles = [];

    /** @var array<string, mixed>|null Built on first use by aktor() */
    private ?array $aktor = null;

    /**
     * @param array<string, mixed> $akun  Row of `akun`
     * @param list<string>         $roles Roles from Services\Akun\Peran
     */
    public function set(array $akun, array $roles): void
    {
        $this->akun  = $akun;
        $this->roles = $roles;
        $this->aktor = null;
    }

    public function clear(): void
    {
        $this->akun  = null;
        $this->roles = [];
        $this->aktor = null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function akun(): ?array
    {
        return $this->akun;
    }

    public function id(): ?int
    {
        return $this->akun === null ? null : (int) $this->akun['id'];
    }

    /**
     * @return list<string>
     */
    public function roles(): array
    {
        return $this->roles;
    }

    /**
     * Whether one of the roles holds the right, e.g. to show a menu item (docs/09 RT-21).
     */
    public function has(string $hak): bool
    {
        return (new HakAkses())->has($this->roles, $hak);
    }

    /**
     * The actor array of Services\Akun\HakAkses::allows(): roles, the rombel
     * the account is wali kelas of, and for a student account its own
     * student and today's rombel (docs/04 §4.1 items 4–5).
     *
     * @return array{roles: list<string>, homeroom_rombel_ids: list<int>, siswa_id: int|null, rombel_id: int|null}
     */
    public function aktor(): array
    {
        if ($this->aktor !== null) {
            return $this->aktor;
        }

        $rujukan  = new Rujukan();
        $siswaId  = isset($this->akun['siswa_id']) ? (int) $this->akun['siswa_id'] : null;
        $rombel   = $siswaId === null ? null : $rujukan->rombelSiswa($siswaId, (new Jam())->today());

        return $this->aktor = [
            'roles'               => $this->roles,
            'homeroom_rombel_ids' => in_array('wali_kelas', $this->roles, true) ? $rujukan->rombelWaliKelas((int) $this->id()) : [],
            'siswa_id'            => $siswaId,
            'rombel_id'           => $rombel === null ? null : (int) $rombel['id'],
        ];
    }

    /**
     * Whether the account may use the right on this data, by scope and batas
     * mundur (docs/07 ARS-15, docs/04 §4.1, §4.2). For one student's data,
     * build $data with dataSiswa().
     *
     * @param array<string, mixed> $data Keys of Services\Akun\HakAkses::allows()
     */
    public function boleh(string $hak, array $data): bool
    {
        $batasMundur = (int) (db_connect()->table('pengaturan')->select('nilai')->where('kunci', 'batas_mundur_hari')->get()->getRow()->nilai ?? 0);

        return (new HakAkses())->allows($this->aktor(), $hak, $data, (new Jam())->today(), $batasMundur);
    }

    /**
     * Scope data for one student: the student and their rombel today, so a
     * moved student follows the new wali kelas (docs/04 §4.1 item 4).
     *
     * @return array{siswa_id: int, rombel_id: int|null}
     */
    public function dataSiswa(int $siswaId, ?string $tanggal = null): array
    {
        $rombel = (new Rujukan())->rombelSiswa($siswaId, (new Jam())->today());
        $data   = ['siswa_id' => $siswaId, 'rombel_id' => $rombel === null ? null : (int) $rombel['id']];

        return $tanggal === null ? $data : $data + ['tanggal' => $tanggal];
    }
}
