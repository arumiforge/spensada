<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class FormatHelperTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        helper('format');
    }

    public function testDateStyles(): void
    {
        $date = '2026-10-13 07:32:05';

        $this->assertSame('13 Oktober 2026', format_date($date));
        $this->assertSame('Selasa, 13 Oktober 2026', format_date($date, 'full'));
        $this->assertSame('13 Okt 2026', format_date($date, 'short'));
        $this->assertSame('13 Okt', format_date($date, 'day'));
        $this->assertSame('13/10/2026', format_date($date, 'numeric'));
        $this->assertSame('05/08/2026', format_date('2026-08-05', 'numeric'));
        $this->assertSame('5 Agu 2026', format_date('2026-08-05', 'short'));
    }

    public function testEmptyInputReturnsEmptyString(): void
    {
        $this->assertSame('', format_date(null));
        $this->assertSame('', format_time(''));
        $this->assertSame('', format_rupiah(null));
    }

    public function testUnknownDateStyleThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        format_date('2026-10-13', 'iso');
    }

    public function testTimeUsesDotsAndJakartaTimeZone(): void
    {
        $this->assertSame('07.32', format_time('2026-10-13 07:32:05'));
        $this->assertSame('07.32.05', format_time('2026-10-13 07:32:05', true));

        // 00:32 UTC is 07:32 WIB.
        $utc = new DateTimeImmutable('2026-10-13 00:32:00', new DateTimeZone('UTC'));
        $this->assertSame('13 Okt 2026, 07.32', format_datetime($utc));
    }

    public function testNumbersMoneyAndPercent(): void
    {
        $this->assertSame('1.024', format_number(1024));
        $this->assertSame('12,5', format_number(12.5, 1));
        $this->assertSame('Rp 1.250.000', format_rupiah(1250000));
        $this->assertSame('-Rp 1.250.000', format_rupiah(-1250000));
        $this->assertSame('88%', format_percent(87.5));
        $this->assertSame('87%', format_percent(87.4));
    }

    public function testWaNumberShowsLocalPrefixWithHyphens(): void
    {
        $this->assertSame('0812-3456-7890', format_wa('6281234567890'));
        $this->assertSame('0812-3456-78', format_wa('62812345678'));
        $this->assertSame('0812-3456-789012', format_wa('628123456789012'));
        $this->assertSame('', format_wa(null));
    }
}
