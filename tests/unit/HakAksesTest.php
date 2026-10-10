<?php

use App\Services\Akun\HakAkses;
use CodeIgniter\Test\CIUnitTestCase;
use Config\HakAkses as Peta;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Rights and scopes from docs/02 §5 and §6 (R1).
 *
 * @internal
 */
final class HakAksesTest extends CIUnitTestCase
{
    private const TODAY = '2026-10-10';

    private HakAkses $hakAkses;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hakAkses = new HakAkses();
    }

    public function testMapHoldsEveryR1RightWithKnownRolesAndScopes(): void
    {
        $peta     = new Peta();
        $expected = [
            'HA-AKN-01', 'HA-AKN-02', 'HA-AKN-03', 'HA-AKN-04', 'HA-AKN-05', 'HA-AKN-06', 'HA-AKN-07', 'HA-AKN-08',
            'HA-MD-01', 'HA-MD-02', 'HA-MD-03', 'HA-MD-04', 'HA-MD-05', 'HA-MD-06', 'HA-MD-07', 'HA-MD-08', 'HA-MD-09', 'HA-MD-10', 'HA-MD-11',
            'HA-KIO-01', 'HA-KIO-02', 'HA-KIO-03',
            'HA-PRS-01', 'HA-PRS-02', 'HA-PRS-03', 'HA-PRS-04', 'HA-PRS-06', 'HA-PRS-07', 'HA-PRS-08', 'HA-PRS-09',
            'HA-IZN-01', 'HA-IZN-02', 'HA-IZN-03', 'HA-IZN-04', 'HA-IZN-05', 'HA-IZN-06',
            'HA-LAP-01', 'HA-LAP-02', 'HA-LAP-03', 'HA-LAP-04',
        ];
        $this->assertSame($expected, array_keys($peta->hak));

        $roles  = ['admin', 'staf', 'wali_kelas', 'guru_piket', 'guru_bk', 'pimpinan', 'siswa', 'stasiun'];
        $scopes = ['semua', 'rombel', 'hari_ini', 'sendiri', 'rombelnya', 'angka', 'ya'];

        foreach ($peta->hak as $hak => $map) {
            $this->assertSame([], array_diff(array_keys($map), $roles), $hak);
            $this->assertSame([], array_diff($map, $scopes), $hak);
        }

        $this->assertSame([], array_diff($peta->batasMundur, $expected));
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function rightsPerRole(): iterable
    {
        $all = array_keys((new Peta())->hak);

        yield 'admin' => ['admin', array_values(array_diff($all, ['HA-KIO-01', 'HA-IZN-01']))];

        yield 'staf' => ['staf', ['HA-AKN-01', 'HA-LAP-01']];

        yield 'wali_kelas' => ['wali_kelas', [
            'HA-AKN-01', 'HA-AKN-04', 'HA-AKN-05', 'HA-AKN-06',
            'HA-MD-05', 'HA-MD-06', 'HA-MD-07', 'HA-MD-10',
            'HA-PRS-03', 'HA-PRS-04', 'HA-PRS-06',
            'HA-IZN-02', 'HA-IZN-03', 'HA-IZN-04', 'HA-IZN-05', 'HA-IZN-06',
            'HA-LAP-01', 'HA-LAP-02', 'HA-LAP-03', 'HA-LAP-04',
        ]];

        yield 'guru_piket' => ['guru_piket', [
            'HA-AKN-01', 'HA-MD-05', 'HA-KIO-02', 'HA-KIO-03',
            'HA-PRS-03', 'HA-PRS-04', 'HA-PRS-06', 'HA-PRS-07', 'HA-PRS-08',
            'HA-IZN-02', 'HA-IZN-03', 'HA-IZN-04', 'HA-IZN-05', 'HA-IZN-06',
            'HA-LAP-01', 'HA-LAP-02',
        ]];

        yield 'guru_bk' => ['guru_bk', [
            'HA-AKN-01', 'HA-MD-05', 'HA-MD-10',
            'HA-PRS-03', 'HA-PRS-04', 'HA-PRS-06',
            'HA-IZN-02', 'HA-IZN-03', 'HA-IZN-04', 'HA-IZN-05', 'HA-IZN-06',
            'HA-LAP-01', 'HA-LAP-02', 'HA-LAP-03', 'HA-LAP-04',
        ]];

        yield 'pimpinan' => ['pimpinan', [
            'HA-AKN-01', 'HA-MD-05', 'HA-MD-10', 'HA-PRS-06', 'HA-IZN-04', 'HA-IZN-05',
            'HA-LAP-01', 'HA-LAP-02', 'HA-LAP-03', 'HA-LAP-04',
        ]];

        yield 'siswa' => ['siswa', ['HA-AKN-01', 'HA-MD-05', 'HA-IZN-01', 'HA-IZN-04', 'HA-IZN-05', 'HA-LAP-04']];

        yield 'stasiun' => ['stasiun', ['HA-KIO-01']];
    }

    /**
     * @param list<string> $expected
     */
    #[DataProvider('rightsPerRole')]
    public function testRightsPerRole(string $role, array $expected): void
    {
        $held = array_values(array_filter(
            array_keys((new Peta())->hak),
            fn (string $hak): bool => $this->hakAkses->has([$role], $hak),
        ));

        $this->assertSame($expected, $held);
    }

    public function testUnknownRightOrNoRoleIsDenied(): void
    {
        $this->assertFalse($this->hakAkses->has(['admin'], 'HA-PRS-05'));
        $this->assertFalse($this->hakAkses->has(['admin'], 'HA-LAP-05'));
        $this->assertFalse($this->hakAkses->has([], 'HA-LAP-01'));
        $this->assertFalse($this->allows(['admin'], 'HA-XXX-01', []));
    }

    public function testScopeYa(): void
    {
        $this->assertTrue($this->allows(['admin'], 'HA-AKN-02', []));
        $this->assertTrue($this->allows(['staf'], 'HA-LAP-01', []));
    }

    public function testScopeSemua(): void
    {
        $this->assertTrue($this->allows(['guru_bk'], 'HA-MD-05', ['siswa_id' => 9, 'rombel_id' => 99]));
        $this->assertTrue($this->allows(['pimpinan'], 'HA-LAP-03', ['rombel_id' => 99, 'tanggal' => '2025-01-01']));
    }

    public function testScopeRombel(): void
    {
        $actor = ['roles' => ['wali_kelas'], 'homeroom_rombel_ids' => [7]];

        $this->assertTrue($this->hakAkses->allows($actor, 'HA-MD-06', ['siswa_id' => 1, 'rombel_id' => 7], self::TODAY, 7));
        $this->assertTrue($this->hakAkses->allows($actor, 'HA-MD-06', ['siswa_id' => 1, 'rombel_id' => '7'], self::TODAY, 7));
        $this->assertFalse($this->hakAkses->allows($actor, 'HA-MD-06', ['siswa_id' => 1, 'rombel_id' => 8], self::TODAY, 7));
        $this->assertFalse($this->hakAkses->allows($actor, 'HA-MD-06', ['siswa_id' => 1], self::TODAY, 7));
        // Wali kelas role without a rombel this school year sees nothing.
        $this->assertFalse($this->allows(['wali_kelas'], 'HA-MD-06', ['rombel_id' => 7]));
    }

    public function testScopeHariIni(): void
    {
        $this->assertTrue($this->allows(['guru_piket'], 'HA-KIO-03', ['tanggal' => self::TODAY]));
        $this->assertFalse($this->allows(['guru_piket'], 'HA-KIO-03', ['tanggal' => '2026-10-09']));
        $this->assertFalse($this->allows(['guru_piket'], 'HA-KIO-03', []));
        // HA-PRS-07 is Hari ini for admin too.
        $this->assertTrue($this->allows(['admin'], 'HA-PRS-07', ['tanggal' => self::TODAY]));
        $this->assertFalse($this->allows(['admin'], 'HA-PRS-07', ['tanggal' => '2026-10-11']));
    }

    public function testScopeSendiri(): void
    {
        $actor = ['roles' => ['siswa'], 'siswa_id' => 5, 'rombel_id' => 7];

        $this->assertTrue($this->hakAkses->allows($actor, 'HA-LAP-04', ['siswa_id' => 5], self::TODAY, 7));
        $this->assertFalse($this->hakAkses->allows($actor, 'HA-LAP-04', ['siswa_id' => 6], self::TODAY, 7));
        $this->assertFalse($this->hakAkses->allows($actor, 'HA-LAP-04', ['rombel_id' => 7], self::TODAY, 7));
    }

    public function testScopeRombelnyaAndAngka(): void
    {
        // Neither scope is used by an R1 right yet (HA-INF-02 is R3).
        $peta      = new Peta();
        $peta->hak = ['HA-INF-02' => ['siswa' => 'rombelnya'], 'HA-UJI-01' => ['staf' => 'angka']];
        $hakAkses  = new HakAkses($peta);
        $siswa     = ['roles' => ['siswa'], 'siswa_id' => 5, 'rombel_id' => 7];
        $staf      = ['roles' => ['staf']];

        $this->assertTrue($hakAkses->allows($siswa, 'HA-INF-02', ['rombel_id' => 7], self::TODAY, 7));
        $this->assertFalse($hakAkses->allows($siswa, 'HA-INF-02', ['rombel_id' => 8], self::TODAY, 7));
        $this->assertTrue($hakAkses->allows($staf, 'HA-UJI-01', ['rombel_id' => 7], self::TODAY, 7));
        $this->assertFalse($hakAkses->allows($staf, 'HA-UJI-01', ['rombel_id' => 7, 'siswa_id' => 5], self::TODAY, 7));
    }

    public function testNoRight(): void
    {
        $this->assertFalse($this->allows(['staf'], 'HA-PRS-03', ['rombel_id' => 7, 'tanggal' => self::TODAY]));
        $this->assertFalse($this->allows(['pimpinan'], 'HA-PRS-03', ['rombel_id' => 7, 'tanggal' => self::TODAY]));
        $this->assertFalse($this->allows(['stasiun'], 'HA-LAP-01', []));
    }

    public function testBatasMundur(): void
    {
        // Today and 7 days before: 3–10 October (docs/05 BR-MUN-01).
        $this->assertTrue($this->allows(['guru_bk'], 'HA-PRS-03', ['tanggal' => '2026-10-03']));
        $this->assertFalse($this->allows(['guru_bk'], 'HA-PRS-03', ['tanggal' => '2026-10-02']));
        $this->assertFalse($this->allows(['guru_bk'], 'HA-PRS-03', []));
        $this->assertFalse($this->allows(['guru_piket'], 'HA-IZN-02', ['tanggal' => '2026-10-02']));
        $this->assertTrue($this->allows(['guru_piket'], 'HA-IZN-02', ['tanggal' => '2026-10-03']));
        // Admin is not limited (BR-MUN-03).
        $this->assertTrue($this->allows(['admin'], 'HA-PRS-04', ['tanggal' => '2025-01-01']));
        // Viewing is not limited.
        $this->assertTrue($this->allows(['guru_bk'], 'HA-IZN-04', ['tanggal' => '2025-01-01']));
        // The number is set by admin.
        $this->assertFalse($this->hakAkses->allows(['roles' => ['guru_bk']], 'HA-PRS-03', ['tanggal' => '2026-10-06'], self::TODAY, 3));
        $this->assertTrue($this->hakAkses->allows(['roles' => ['guru_bk']], 'HA-PRS-03', ['tanggal' => '2026-10-07'], self::TODAY, 3));
    }

    public function testBatasMundurForStudentRequests(): void
    {
        $actor = ['roles' => ['siswa'], 'siswa_id' => 5];

        $this->assertTrue($this->hakAkses->allows($actor, 'HA-IZN-01', ['siswa_id' => 5, 'tanggal' => '2026-10-20'], self::TODAY, 7));
        $this->assertTrue($this->hakAkses->allows($actor, 'HA-IZN-01', ['siswa_id' => 5, 'tanggal' => '2026-10-03'], self::TODAY, 7));
        $this->assertFalse($this->hakAkses->allows($actor, 'HA-IZN-01', ['siswa_id' => 5, 'tanggal' => '2026-10-02'], self::TODAY, 7));
    }

    public function testRolesAreCombined(): void
    {
        // Wali kelas 7A who is also guru piket (docs/02 §4 item 1).
        $actor = ['roles' => ['staf', 'wali_kelas', 'guru_piket'], 'homeroom_rombel_ids' => [7]];

        $this->assertSame(['rombel', 'hari_ini'], $this->hakAkses->scopes($actor['roles'], 'HA-PRS-03'));
        $this->assertTrue($this->hakAkses->allows($actor, 'HA-PRS-03', ['rombel_id' => 8, 'tanggal' => self::TODAY], self::TODAY, 7));
        $this->assertTrue($this->hakAkses->allows($actor, 'HA-PRS-03', ['rombel_id' => 7, 'tanggal' => '2026-10-05'], self::TODAY, 7));
        $this->assertFalse($this->hakAkses->allows($actor, 'HA-PRS-03', ['rombel_id' => 8, 'tanggal' => '2026-10-05'], self::TODAY, 7));
        $this->assertFalse($this->hakAkses->allows($actor, 'HA-PRS-03', ['rombel_id' => 7, 'tanggal' => '2026-10-01'], self::TODAY, 7));
    }

    public function testScopesForLists(): void
    {
        $this->assertSame(['semua', 'rombel'], $this->hakAkses->scopes(['admin', 'wali_kelas'], 'HA-LAP-03'));
        $this->assertSame(['rombel'], $this->hakAkses->scopes(['staf', 'wali_kelas'], 'HA-LAP-03'));
        $this->assertSame([], $this->hakAkses->scopes(['staf', 'guru_piket'], 'HA-LAP-03'));
    }

    /**
     * @param list<string>         $roles
     * @param array<string, mixed> $data
     */
    private function allows(array $roles, string $hak, array $data): bool
    {
        return $this->hakAkses->allows(['roles' => $roles], $hak, $data, self::TODAY, 7);
    }
}
