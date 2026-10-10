<?php

use App\Services\Sistem\Transaction;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

/**
 * @internal
 */
final class TransactionTest extends CIUnitTestCase
{
    private BaseConnection $conn;

    protected function setUp(): void
    {
        parent::setUp();

        $this->conn = Database::connect();
        $this->conn->query('CREATE TEMPORARY TABLE uji_transaksi (id INT PRIMARY KEY) ENGINE = InnoDB');
    }

    protected function tearDown(): void
    {
        $this->conn->query('DROP TEMPORARY TABLE IF EXISTS uji_transaksi');

        parent::tearDown();
    }

    public function testCommitsAndReturnsResult(): void
    {
        $result = (new Transaction($this->conn))->run(function (): string {
            $this->conn->query('INSERT INTO uji_transaksi VALUES (1)');

            return 'ok';
        });

        $this->assertSame('ok', $result);
        $this->assertSame(1, $this->countRows());
    }

    public function testRollsBackAndDeletesFilesOnException(): void
    {
        $file = tempnam(WRITEPATH, 'tx');

        try {
            (new Transaction($this->conn))->run(function (Transaction $tx) use ($file): void {
                $this->conn->query('INSERT INTO uji_transaksi VALUES (1)');
                $tx->addFile($file);

                throw new RuntimeException('stop');
            });
            $this->fail('Exception was not rethrown.');
        } catch (RuntimeException $e) {
            $this->assertSame('stop', $e->getMessage());
        }

        $this->assertSame(0, $this->countRows());
        $this->assertFileDoesNotExist($file);
        $this->assertSame(0, $this->conn->transDepth);
    }

    public function testRollsBackOnQueryError(): void
    {
        $this->expectException(DatabaseException::class);

        try {
            (new Transaction($this->conn))->run(function (): void {
                $this->conn->query('INSERT INTO uji_transaksi VALUES (1)');
                $this->conn->query('INSERT INTO uji_transaksi VALUES (1)');
            });
        } finally {
            $this->assertSame(0, $this->countRows());
        }
    }

    public function testRetriesOnceAfterDeadlock(): void
    {
        $attempts = 0;

        $result = (new Transaction($this->conn))->run(function () use (&$attempts): int {
            $attempts++;
            $this->conn->query('INSERT INTO uji_transaksi VALUES (?)', [$attempts]);

            if ($attempts === 1) {
                throw new DatabaseException('Deadlock found', 1213);
            }

            return $attempts;
        });

        $this->assertSame(2, $result);
        $this->assertSame([2], array_map('intval', array_column($this->conn->query('SELECT id FROM uji_transaksi')->getResultArray(), 'id')));
    }

    public function testGivesUpAfterSecondDeadlock(): void
    {
        $attempts = 0;

        try {
            (new Transaction($this->conn))->run(static function () use (&$attempts): void {
                $attempts++;

                throw new DatabaseException('Lock wait timeout', 1205);
            });
            $this->fail('Exception was not rethrown.');
        } catch (DatabaseException $e) {
            $this->assertSame(1205, $e->getCode());
        }

        $this->assertSame(2, $attempts);
    }

    private function countRows(): int
    {
        return (int) $this->conn->query('SELECT COUNT(*) AS n FROM uji_transaksi')->getRow()->n;
    }
}
