<?php

namespace App\Libraries;

use App\Services\Akun\HakAkses;

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

    /**
     * @param array<string, mixed> $akun  Row of `akun`
     * @param list<string>         $roles Roles from Services\Akun\Peran
     */
    public function set(array $akun, array $roles): void
    {
        $this->akun  = $akun;
        $this->roles = $roles;
    }

    public function clear(): void
    {
        $this->akun  = null;
        $this->roles = [];
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
}
