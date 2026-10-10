<?php

use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Runs the shared cases in tests/kasus/format.json, which
 * tests/js/format.test.js also runs against public/aset/js/format.js.
 *
 * @internal
 */
final class FormatCasesTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        helper('format');
    }

    public static function provideCases(): iterable
    {
        $data = json_decode(file_get_contents(__DIR__ . '/../kasus/format.json'), true, 512, JSON_THROW_ON_ERROR);

        foreach ($data['cases'] as $case) {
            yield $case['fn'] . '(' . substr(json_encode($case['args']), 1, -1) . ')' => [$case];
        }
    }

    #[DataProvider('provideCases')]
    public function testSharedCase(array $case): void
    {
        if ($case['throws'] ?? false) {
            $this->expectException(Exception::class);
        }

        $this->assertSame($case['expected'] ?? null, $case['fn'](...$case['args']));
    }
}
