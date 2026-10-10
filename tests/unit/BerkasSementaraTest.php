<?php

use App\Services\Berkas\BerkasSementara;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Temporary folders between preview and confirmation (docs/07 ARS-55).
 *
 * @internal
 */
final class BerkasSementaraTest extends CIUnitTestCase
{
    private string $akar;
    private BerkasSementara $berkas;

    protected function setUp(): void
    {
        parent::setUp();
        $this->akar   = sys_get_temp_dir() . '/sementara-test-' . bin2hex(random_bytes(4));
        $this->berkas = new BerkasSementara($this->akar);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->akar)) {
            exec('rm -rf ' . escapeshellarg($this->akar));
        }
        parent::tearDown();
    }

    public function testTokenIs32HexCharactersAndOpensForItsOwner(): void
    {
        $token = $this->berkas->buat(7, 'import_siswa');

        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $token);
        $this->assertSame($this->akar . DIRECTORY_SEPARATOR . $token . DIRECTORY_SEPARATOR, $this->berkas->folder($token, 7, 'import_siswa'));
    }

    public function testOtherAccountIsRejected(): void
    {
        $token = $this->berkas->buat(7, 'import_siswa');
        $this->assertTrue($this->berkas->simpanData($token, 7, 'import_siswa', 'hasil.json', ['baris' => 3]));

        $this->assertNull($this->berkas->folder($token, 8, 'import_siswa'));
        $this->assertNull($this->berkas->bacaData($token, 8, 'import_siswa', 'hasil.json'));
        $this->assertFalse($this->berkas->simpanData($token, 8, 'import_siswa', 'hasil.json', []));
        $this->assertFalse($this->berkas->hapus($token, 8, 'import_siswa'));
        $this->assertSame(['baris' => 3], $this->berkas->bacaData($token, 7, 'import_siswa', 'hasil.json'));
    }

    public function testOtherFlowIsRejected(): void
    {
        $token = $this->berkas->buat(7, 'import_siswa');

        $this->assertNull($this->berkas->folder($token, 7, 'foto_massal'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function badTokens(): iterable
    {
        yield 'empty' => [''];
        yield 'short' => ['abc'];
        yield 'upper case' => [str_repeat('A', 32)];
        yield 'traversal' => ['../' . str_repeat('a', 29)];
        yield 'unknown' => [str_repeat('a', 32)];
    }

    /**
     * @dataProvider badTokens
     */
    public function testMalformedOrUnknownTokenOpensNothing(string $token): void
    {
        $this->assertNull($this->berkas->folder($token, 7, 'import_siswa'));
    }

    public function testDataNameCannotLeaveTheFolder(): void
    {
        $token = $this->berkas->buat(7, 'import_siswa');

        $this->assertFalse($this->berkas->simpanData($token, 7, 'import_siswa', '../x.json', []));
        $this->assertFalse($this->berkas->simpanData($token, 7, 'import_siswa', '.pemilik.json', []));
        $this->assertNull($this->berkas->bacaData($token, 7, 'import_siswa', '.pemilik.json'));
    }

    public function testHapusRemovesTheFolder(): void
    {
        $token = $this->berkas->buat(7, 'foto_massal');
        $dir   = $this->berkas->folder($token, 7, 'foto_massal');
        mkdir($dir . 'foto');
        file_put_contents($dir . 'foto/a.jpg', 'x');

        $this->assertTrue($this->berkas->hapus($token, 7, 'foto_massal'));
        $this->assertDirectoryDoesNotExist($dir);
        $this->assertNull($this->berkas->folder($token, 7, 'foto_massal'));
    }

    public function testBersihkanRemovesOnlyOldFolders(): void
    {
        $lama = $this->berkas->buat(7, 'import_siswa');
        $baru = $this->berkas->buat(7, 'import_siswa');
        $file = $this->akar . '/' . $lama . '/.pemilik.json';
        file_put_contents($file, json_encode(['akun_id' => 7, 'jenis' => 'import_siswa', 'dibuat' => time() - 90000]));

        $this->assertSame(1, $this->berkas->bersihkan());
        $this->assertNull($this->berkas->folder($lama, 7, 'import_siswa'));
        $this->assertNotNull($this->berkas->folder($baru, 7, 'import_siswa'));
    }
}
