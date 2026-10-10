<?php

use App\Services\Akun\Kredensial;
use App\Services\Akun\LogAktivitas;
use App\Services\Akun\PercobaanLogin;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Account tables and services of L01-01 (docs/06 §5, docs/12 SEC-02,
 * SEC-05, SEC-08, SEC-09, SEC-59, docs/07 ARS-47).
 *
 * @internal
 */
final class AkunLayananTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh   = true;
    protected $namespace = 'App';

    private const TABLES = ['akun', 'akun_role', 'log_aktivitas', 'percobaan_login'];

    protected function setUp(): void
    {
        parent::setUp();
        Time::setTestNow('2026-10-13 07:00:00', 'Asia/Jakarta');
    }

    protected function tearDown(): void
    {
        Time::setTestNow();
        parent::tearDown();
    }

    public function testMigrationsGoDownAndUp(): void
    {
        $this->assertAccountTables(true);

        $runner = service('migrations');
        $runner->regress(0);
        $this->assertAccountTables(false);

        $runner->setNamespace('App')->latest();
        $this->assertAccountTables(true);
    }

    public function testShortLockAfterFiveFailuresSlidesOut(): void
    {
        $percobaan = new PercobaanLogin();
        $hash      = $percobaan->identitasHash('rina.w');

        for ($i = 0; $i < 4; $i++) {
            $this->assertNull($percobaan->catatGagal($hash, '10.0.0.1'));
        }

        $kunci = $percobaan->catatGagal($hash, '10.0.0.1');
        $this->assertSame('pendek', $kunci['jenis']);
        $this->assertSame('2026-10-13 07:15:00', $kunci['sampai']->toDateTimeString());
        $this->assertSame('pendek', $percobaan->keadaanAkun('rina.w')['jenis']);

        Time::setTestNow('2026-10-13 07:15:00', 'Asia/Jakarta');
        $this->assertNull($percobaan->keadaan($hash, '10.0.0.1'));
    }

    public function testLongLockAfterTwentyFailuresInADay(): void
    {
        $percobaan = new PercobaanLogin();
        $hash      = $percobaan->identitasHash('rina.w');
        $this->insertFailures($hash, 19, '2026-10-12 08:00:00', 60);

        $kunci = $percobaan->catatGagal($hash, '10.0.0.1');

        $this->assertSame('panjang', $kunci['jenis']);
        $this->assertSame('2026-10-13 08:00:00', $kunci['sampai']->toDateTimeString());
    }

    public function testIpLockOnlyWhenTheIpIsGiven(): void
    {
        $percobaan = new PercobaanLogin();

        for ($i = 0; $i < 100; $i++) {
            $this->db->table('percobaan_login')->insert(['identitas_hash' => hash('sha256', (string) $i), 'ip' => '10.0.0.9', 'created_at' => '2026-10-13 06:50:00']);
        }

        $hash = $percobaan->identitasHash('rina.w');
        $this->assertSame('ip', $percobaan->keadaan($hash, '10.0.0.9')['jenis']);
        $this->assertNull($percobaan->keadaan($hash, '10.0.0.8'));
        $this->assertNull($percobaan->keadaan($hash));
    }

    public function testHapusClearsOnlyThatIdentity(): void
    {
        $percobaan = new PercobaanLogin();
        $hash      = $percobaan->identitasHash('rina.w');
        $this->insertFailures($hash, 5, '2026-10-13 06:55:00', 0);
        $this->insertFailures($percobaan->identitasHash('budi.s'), 2, '2026-10-13 06:55:00', 0);

        $percobaan->hapus($hash);

        $this->assertNull($percobaan->keadaanAkun('rina.w'));
        $this->assertSame(2, $this->db->table('percobaan_login')->countAllResults());
    }

    public function testIdentityHashIsKeyedAndNotTheText(): void
    {
        $hash = (new PercobaanLogin())->identitasHash('rina.w');

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $hash);
        $this->assertNotSame(hash('sha256', 'rina.w'), $hash);
        $this->assertSame('login:' . substr($hash, 0, 40), (new PercobaanLogin())->namaKunci($hash));
    }

    public function testRandomPasswordUsesTheReadableAlphabet(): void
    {
        $kredensial = new Kredensial();

        foreach ([8, 12] as $panjang) {
            for ($i = 0; $i < 20; $i++) {
                $this->assertMatchesRegularExpression('/^[a-hjkmnp-z2-9]{' . $panjang . '}$/', $kredensial->acak($panjang));
            }
        }
    }

    public function testHashAndCredentialStamp(): void
    {
        $kredensial = new Kredensial();
        $hash       = $kredensial->hash('kopi pagi ');

        $this->assertStringStartsWith('$2y$11$', $hash);
        $this->assertTrue($kredensial->cocok('kopi pagi ', $hash));
        $this->assertFalse($kredensial->cocok('kopi pagi', $hash), 'spaces are part of the password');
        $this->assertFalse($kredensial->cocok('kopi pagi ', null));
        $this->assertFalse($kredensial->perluHashUlang($hash));
        $this->assertTrue($kredensial->perluHashUlang(password_hash('x', PASSWORD_BCRYPT, ['cost' => 10])));

        $cap = $kredensial->cap($hash);
        $this->assertSame($cap, $kredensial->cap($hash));
        $this->assertNotSame($cap, $kredensial->cap($kredensial->hash('kopi pagi ')));
        $this->assertStringNotContainsString($hash, $cap);
    }

    public function testLogRejectsUnknownKind(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new LogAktivitas())->catat('login_entah', null);
    }

    public function testLogStoresDataAsJson(): void
    {
        $log = new LogAktivitas();

        $id     = $log->catat('pengaturan_diubah', null, null, ['kunci' => 'sekolah_nama', 'baru' => 'SMP 1 Dawe – Kudus'], null, '10.0.0.1');
        $kosong = $log->catat('login_gagal', null);

        $row = $this->db->table('log_aktivitas')->where('id', $id)->get()->getRowArray();
        $this->assertEquals(['kunci' => 'sekolah_nama', 'baru' => 'SMP 1 Dawe – Kudus'], json_decode($row['data'], true));
        $this->assertSame('10.0.0.1', $row['ip']);
        $this->assertSame('2026-10-13 07:00:00', $row['created_at']);
        $this->assertNull($this->db->table('log_aktivitas')->where('id', $kosong)->get()->getRow()->data);
    }

    private function assertAccountTables(bool $exist): void
    {
        foreach (self::TABLES as $table) {
            $this->assertSame($exist, $this->db->tableExists($table, false), $table);
        }

        if ($exist) {
            $fk = $this->db->query(
                'SELECT COUNT(*) AS n FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?',
                ['pengaturan', 'fk_pengaturan_diubah_oleh'],
            )->getRow()->n;
            $this->assertSame(1, (int) $fk);
        }
    }

    private function insertFailures(string $hash, int $count, string $from, int $minutesApart): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->db->table('percobaan_login')->insert([
                'identitas_hash' => $hash,
                'ip'             => '10.0.0.1',
                'created_at'     => Time::parse($from, 'Asia/Jakarta')->addMinutes($minutesApart * $i)->toDateTimeString(),
            ]);
        }
    }
}
