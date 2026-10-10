<?php

namespace Tests\Support\Filters;

use App\Filters\Hak;

/**
 * `hak` filter with roles set by the test, without a logged-in account.
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
