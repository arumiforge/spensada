<?php

namespace App\Services\Sistem;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * MySQL named locks (GET_LOCK) for checks that a unique key cannot enforce
 * (docs/07 ARS-42).
 *
 * Lock names are server-wide, so every name gets the database name as a
 * prefix: `status` becomes `spensada:status` (`spensada_test:status` in
 * tests). One connection may take the same lock again; each take needs its
 * own release, so this helper counts what it holds. Take locks before the
 * transaction and release them after commit (ARS-43). MySQL releases them
 * itself when the connection ends.
 */
class NamedLock
{
    private BaseConnection $db;

    /**
     * @var array<string, int> Full lock name => times taken
     */
    private array $held = [];

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    /**
     * Full server-wide name of a lock, e.g. `spensada:status`.
     */
    public function fullName(string $name): string
    {
        return $this->db->getDatabase() . ':' . $name;
    }

    /**
     * Takes the lock, waiting at most $timeout seconds.
     * Returns false when another connection still holds it.
     */
    public function acquire(string $name, int $timeout): bool
    {
        $fullName = $this->fullName($name);
        $row      = $this->db->query('SELECT GET_LOCK(?, ?) AS result', [$fullName, $timeout])->getRow();

        if ((int) $row->result !== 1) {
            return false;
        }

        $this->held[$fullName] = ($this->held[$fullName] ?? 0) + 1;

        return true;
    }

    /**
     * Releases one take of the lock. Returns false when this connection
     * did not hold it.
     */
    public function release(string $name): bool
    {
        $fullName = $this->fullName($name);
        $row      = $this->db->query('SELECT RELEASE_LOCK(?) AS result', [$fullName])->getRow();

        if ((int) $row->result !== 1) {
            return false;
        }

        $left = ($this->held[$fullName] ?? 1) - 1;

        if ($left > 0) {
            $this->held[$fullName] = $left;
        } else {
            unset($this->held[$fullName]);
        }

        return true;
    }

    /**
     * Releases every take this helper still holds, e.g. after an error.
     */
    public function releaseAll(): void
    {
        foreach ($this->held as $fullName => $count) {
            for ($i = 0; $i < $count; $i++) {
                $this->db->query('SELECT RELEASE_LOCK(?)', [$fullName]);
            }
        }

        $this->held = [];
    }
}
