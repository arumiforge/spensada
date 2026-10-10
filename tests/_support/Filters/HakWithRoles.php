<?php

namespace Tests\Support\Filters;

use App\Filters\Hak;

/**
 * `hak` filter with roles set by the test, until `sesi` loads them (L01-02).
 */
final class HakWithRoles extends Hak
{
    /** @var list<string> */
    public static array $roles = [];

    protected function roles(): array
    {
        return self::$roles;
    }
}
