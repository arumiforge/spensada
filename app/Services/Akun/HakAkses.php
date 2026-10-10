<?php

namespace App\Services\Akun;

use Config\HakAkses as Peta;
use DateTimeImmutable;

/**
 * Answers rights and data scope (docs/07 ARS-15, docs/04 §4.1, §4.2).
 *
 * Does not read the session or the clock (ARS-14): roles, the account's
 * rombel, today's date (WIB, Y-m-d) and batas mundur come in as parameters.
 *
 * $actor keys: `roles` (list<string>), `homeroom_rombel_ids` (rombel IDs the
 * account is wali kelas of in the active school year), `siswa_id` and
 * `rombel_id` (student accounts: own student and current rombel).
 *
 * $data keys: `siswa_id`, `rombel_id` (the student's placement today, or the
 * rombel of a class list, docs/04 §4.1 items 4–5) and `tanggal` (Y-m-d; for a
 * date range, the earliest affected date).
 */
final class HakAkses
{
    private Peta $peta;

    public function __construct(?Peta $peta = null)
    {
        $this->peta = $peta ?? new Peta();
    }

    /**
     * Whether any role holds the right. Used by the `hak` filter.
     *
     * @param list<string> $roles
     */
    public function has(array $roles, string $hak): bool
    {
        return $this->scopes($roles, $hak) !== [];
    }

    /**
     * Union of the scopes the roles give for the right (docs/02 §4 item 1),
     * for filtering lists and reports. Empty means no right.
     *
     * @param list<string> $roles
     *
     * @return list<string>
     */
    public function scopes(array $roles, string $hak): array
    {
        $map = $this->peta->hak[$hak] ?? [];

        return array_values(array_unique(array_map(
            static fn (string $role): string => $map[$role],
            array_values(array_filter($roles, static fn ($role): bool => isset($map[$role]))),
        )));
    }

    /**
     * Whether the actor may use the right on this data: one role must allow
     * it by scope and, where it applies, by batas mundur. Fails closed when a
     * key the check needs is missing.
     *
     * @param array<string, mixed> $actor
     * @param array<string, mixed> $data
     */
    public function allows(array $actor, string $hak, array $data, string $hariIni, int $batasMundur): bool
    {
        $map = $this->peta->hak[$hak] ?? [];

        foreach ($actor['roles'] ?? [] as $role) {
            if (isset($map[$role])
                && $this->inScope($map[$role], $actor, $data, $hariIni)
                && $this->inBatasMundur($role, $hak, $data, $hariIni, $batasMundur)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $actor
     * @param array<string, mixed> $data
     */
    private function inScope(string $scope, array $actor, array $data, string $hariIni): bool
    {
        return match ($scope) {
            'ya', 'semua' => true,
            'rombel'      => isset($data['rombel_id']) && in_array((int) $data['rombel_id'], array_map('intval', $actor['homeroom_rombel_ids'] ?? []), true),
            'hari_ini'    => ($data['tanggal'] ?? null) === $hariIni,
            'sendiri'     => isset($data['siswa_id'], $actor['siswa_id']) && (int) $data['siswa_id'] === (int) $actor['siswa_id'],
            'rombelnya'   => isset($data['rombel_id'], $actor['rombel_id']) && (int) $data['rombel_id'] === (int) $actor['rombel_id'],
            // Counts per rombel only, never one student's data.
            'angka'       => ! isset($data['siswa_id']),
            default       => false,
        };
    }

    /**
     * Today and N calendar days before; future dates follow each feature's
     * own rule (docs/04 §4.2, BR-MUN-04). Admin is not limited (BR-MUN-03).
     *
     * @param array<string, mixed> $data
     */
    private function inBatasMundur(string $role, string $hak, array $data, string $hariIni, int $batasMundur): bool
    {
        if ($role === 'admin' || ! in_array($hak, $this->peta->batasMundur, true)) {
            return true;
        }

        if (! isset($data['tanggal'])) {
            return false;
        }

        $earliest = (new DateTimeImmutable($hariIni))->modify("-{$batasMundur} days")->format('Y-m-d');

        return $data['tanggal'] >= $earliest;
    }
}
