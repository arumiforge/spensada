<?php

use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

/**
 * Session settings of the derived MySQLi driver (docs/07 ARS-45, L00-02).
 *
 * @internal
 */
final class KoneksiDatabaseTest extends CIUnitTestCase
{
    public function testSessionUsesWibCollationAndStrictMode(): void
    {
        $row = Database::connect()
            ->query('SELECT @@session.time_zone AS tz, @@collation_connection AS collation, @@session.sql_mode AS sql_mode')
            ->getRow();

        $this->assertSame('+07:00', $row->tz);
        $this->assertSame('utf8mb4_general_ci', $row->collation);
        $this->assertContains('STRICT_ALL_TABLES', explode(',', $row->sql_mode));
    }

    public function testPlatformIsStillMySQLi(): void
    {
        $this->assertSame('MySQLi', Database::connect()->getPlatform());
    }
}
