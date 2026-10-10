<?php

namespace App\Services\Sistem;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Config\Database;
use Throwable;

/**
 * Transaction pattern for services (docs/07 ARS-43, docs/11 GAL-12).
 *
 * One user action is one transaction: data change, log entry, and recount
 * queue. Lock order is always named locks first, then `siswa` rows by
 * ascending ID:
 *
 *     if (! $lock->acquire('kalender', 5)) { ... }
 *     try {
 *         $result = $transaction->run(function (Transaction $tx) {
 *             // queries; $tx->addFile($path) for each upload written
 *         });
 *     } finally {
 *         $lock->release('kalender');
 *     }
 *     // delete replaced old files here, after commit
 *
 * On any exception the transaction is rolled back, registered upload files
 * are deleted, and the exception is rethrown. A deadlock (1213) or lock wait
 * timeout (1205) reruns the whole action once after 100–300 ms. Callers map
 * other MySQL codes, e.g. 1062 on a known unique key, from the exception.
 * Do not nest run() calls.
 */
class Transaction
{
    /**
     * MySQL error codes that rerun the action once.
     */
    private const RETRY_CODES = [1205, 1213];

    private BaseConnection $db;

    /**
     * @var list<string> Upload files written during the current attempt
     */
    private array $files = [];

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    /**
     * Runs $action(Transaction) in one transaction and returns its result.
     */
    public function run(callable $action): mixed
    {
        for ($attempt = 1; ; $attempt++) {
            $this->files = [];
            $this->db->resetTransStatus()->transException(true)->transStart();

            try {
                $result = $action($this);

                if (! $this->db->transComplete()) {
                    throw new DatabaseException('Transaction commit failed.');
                }

                return $result;
            } catch (Throwable $e) {
                foreach ($this->files as $path) {
                    if (is_file($path)) {
                        unlink($path);
                    }
                }

                // CI4 only rolls back on query errors; other exceptions leave it open.
                while ($this->db->transDepth > 0 && $this->db->transRollback()) {
                    // Each call closes one level.
                }

                if ($attempt === 1 && $e instanceof DatabaseException && in_array($e->getCode(), self::RETRY_CODES, true)) {
                    log_message('warning', 'Transaction retried after MySQL error {code}: {message}', [
                        'code'    => $e->getCode(),
                        'message' => $e->getMessage(),
                    ]);
                    usleep(random_int(100_000, 300_000));

                    continue;
                }

                throw $e;
            } finally {
                $this->db->transException(false);
            }
        }
    }

    /**
     * Registers an upload file written to its final place before commit,
     * so it is deleted if the transaction fails.
     */
    public function addFile(string $path): void
    {
        $this->files[] = $path;
    }
}
