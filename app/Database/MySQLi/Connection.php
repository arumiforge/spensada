<?php

namespace App\Database\MySQLi;

use CodeIgniter\Database\MySQLi\Connection as BaseMySQLiConnection;

/**
 * MySQLi connection that fixes the session time zone and collation right
 * after connecting (docs/07 ARS-45, docs/06 DB-04, DB-05).
 *
 * CI4 only calls set_charset(), which leaves collation_connection at the
 * server default (utf8mb4_0900_ai_ci on MySQL 8.4).
 */
class Connection extends BaseMySQLiConnection
{
    public function connect(bool $persistent = false)
    {
        $mysqli = parent::connect($persistent);

        if ($mysqli !== false) {
            // A fixed offset: the server may not have time-zone tables loaded.
            $mysqli->query("SET time_zone = '+07:00'");
            $mysqli->query(sprintf(
                "SET NAMES '%s' COLLATE '%s'",
                $mysqli->real_escape_string($this->charset),
                $mysqli->real_escape_string($this->DBCollat),
            ));
        }

        return $mysqli;
    }

    /**
     * DBDriver holds this class's namespace; CI4 code that checks the
     * platform must still see a MySQLi connection.
     */
    public function getPlatform(): string
    {
        return 'MySQLi';
    }
}
