<?php

use App\Services\Sistem\NamedLock;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

/**
 * @internal
 */
final class NamedLockTest extends CIUnitTestCase
{
    private BaseConnection $conn;
    private NamedLock $lock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->conn   = Database::connect();
        $this->lock = new NamedLock($this->conn);
    }

    protected function tearDown(): void
    {
        $this->lock->releaseAll();

        parent::tearDown();
    }

    public function testNameHasDatabasePrefix(): void
    {
        $this->assertSame('spensada_test:status', $this->lock->fullName('status'));
    }

    public function testAcquireReacquireAndRelease(): void
    {
        $this->assertTrue($this->lock->acquire('status', 0));
        $this->assertTrue($this->heldHere('spensada_test:status'));

        // Same connection may take it again; each take needs a release.
        $this->assertTrue($this->lock->acquire('status', 0));
        $this->assertTrue($this->lock->release('status'));
        $this->assertTrue($this->heldHere('spensada_test:status'));

        $this->assertTrue($this->lock->release('status'));
        $this->assertFalse($this->heldHere('spensada_test:status'));

        $this->assertFalse($this->lock->release('status'));
    }

    public function testOtherConnectionWaitsForLock(): void
    {
        $other = Database::connect(null, false);

        $this->assertTrue($this->lock->acquire('kalender', 0));
        $this->assertFalse((new NamedLock($other))->acquire('kalender', 0));

        $this->lock->release('kalender');
        $otherLock = new NamedLock($other);
        $this->assertTrue($otherLock->acquire('kalender', 0));
        $otherLock->releaseAll();
        $other->close();
    }

    public function testReleaseAllFreesEveryTake(): void
    {
        $this->lock->acquire('admin', 0);
        $this->lock->acquire('admin', 0);
        $this->lock->releaseAll();

        $this->assertFalse($this->heldHere('spensada_test:admin'));
    }

    private function heldHere(string $fullName): bool
    {
        return (bool) $this->conn->query('SELECT IS_USED_LOCK(?) = CONNECTION_ID() AS held', [$fullName])->getRow()->held;
    }
}
