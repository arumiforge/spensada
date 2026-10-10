<?php

use App\Services\Sistem\Jam;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class JamTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        Time::setTestNow();

        parent::tearDown();
    }

    public function testNowFollowsFrozenTimeInWib(): void
    {
        // 23.30 WIB on 12 Oct is still 12 Oct, although UTC says 16.30.
        Time::setTestNow('2026-10-12 16:30:00', 'UTC');

        $jam = new Jam();

        $this->assertSame('2026-10-12 23:30:00', $jam->now()->toDateTimeString());
        $this->assertSame('Asia/Jakarta', $jam->now()->getTimezoneName());
        $this->assertSame('2026-10-12', $jam->today());
    }

    public function testTodayChangesAtMidnightWib(): void
    {
        Time::setTestNow('2026-10-12 17:00:00', 'UTC');

        $this->assertSame('2026-10-13', (new Jam())->today());
    }

    public function testAppTimezoneIsWib(): void
    {
        $this->assertSame('Asia/Jakarta', config('App')->appTimezone);
    }
}
