<?php

use App\Services\Akun\Login as LoginService;
use App\Services\Akun\PercobaanLogin;
use App\Services\Sistem\NamedLock;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;
use Config\Services;
use Tests\Support\AkunTrait;

/**
 * Login and logout (docs/04 FS-AKN-01, AC-AKN-01-01 to 08 for staff accounts,
 * docs/09 HAL-AKN-01, HAL-AKN-02, RT-18, docs/12 SEC-01, SEC-07 to SEC-16, SEC-29).
 *
 * @internal
 */
final class LoginTest extends CIUnitTestCase
{
    use AkunTrait;
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $refresh   = true;
    protected $namespace = 'App';

    private const SALAH = 'NISN/username atau password salah.';

    protected function setUp(): void
    {
        Services::resetSingle('routes');
        Services::resetSingle('router');
        Services::resetSingle('request');
        Services::resetSingle('response');
        parent::setUp();
        $_SESSION = [];
        Time::setTestNow('2026-10-13 06:30:00', 'Asia/Jakarta');
    }

    protected function tearDown(): void
    {
        Time::setTestNow();
        parent::tearDown();
    }

    // AC-AKN-01-01 (staff part), SEC-01 items 1 and 5, SEC-16, SEC-29

    public function testStaffLoginOpensDashboardWithFreshSession(): void
    {
        $akun  = $this->buatAkun(['username' => 'rina.w', 'updated_at' => '2026-10-01 08:00:00']);
        $token = service('security')->getHash();

        $result = $this->login('  RINA.W ', $this->passwordUji);

        $result->assertStatus(303);
        $result->assertRedirectTo(site_url('panel'));
        $this->assertTrue(session()->didRegenerate);
        $this->assertNotSame($token, service('security')->getHash());

        $now = Time::now('Asia/Jakarta')->getTimestamp();
        $this->assertSame((int) $akun['id'], $_SESSION['akun_id']);
        $this->assertSame('staf', $_SESSION['jenis']);
        $this->assertSame($now, $_SESSION['login_at']);
        $this->assertSame($now, $_SESSION['aktif_at']);
        $this->assertArrayNotHasKey('tanpa_password_lama', $_SESSION);
        $this->assertArrayNotHasKey('login_stasiun_id', $_SESSION);

        $baris = $this->akunRow((int) $akun['id']);
        $this->assertSame('2026-10-13 06:30:00', $baris['login_terakhir_at']);
        $this->assertSame('2026-10-01 08:00:00', $baris['updated_at'], 'ARS-40: login does not touch updated_at');

        $log = $this->logs('login_berhasil');
        $this->assertCount(1, $log);
        $this->assertSame([(int) $akun['id'], (int) $akun['id']], [(int) $log[0]['pelaku_id'], (int) $log[0]['akun_id']]);
        $this->assertSame(['jenis' => 'staf'], json_decode($log[0]['data'], true));

        // The new session passes the `sesi` filter of the panel.
        $this->assertFalse($this->withSession()->get('panel')->isRedirect());
    }

    public function testFailedLoginKeepsIdentityButNeverThePassword(): void
    {
        $this->buatAkun(['username' => 'rina.w']);

        $result = $this->login('rina.w', 'Rahasia Salah 1');

        $result->assertStatus(303);
        $result->assertRedirectTo(site_url('login'));
        $this->assertSame(self::SALAH, $_SESSION['galat']);
        $this->assertSame(['identitas' => 'rina.w'], $_SESSION['_ci_old_input']['post']);
        $this->assertStringNotContainsString('Rahasia Salah 1', serialize($_SESSION));
        $this->assertArrayNotHasKey('akun_id', $_SESSION);
    }

    public function testEmptyFieldsAskForThem(): void
    {
        $this->login('  ', 'apa saja');
        $this->assertSame('Isi NISN atau username.', $_SESSION['galat']);

        $this->login('rina.w', '');
        $this->assertSame('Isi password.', $_SESSION['galat']);
        $this->assertSame(0, $this->db->table('percobaan_login')->countAllResults());
    }

    // AC-AKN-01-02

    public function testSameMessageForWrongPasswordInactiveAndStudentWithoutPassword(): void
    {
        $a = $this->buatAkun(['username' => 'akun.a']);
        $b = $this->buatAkun(['username' => 'akun.b', 'status' => 'nonaktif']);
        $c = $this->buatAkun(['jenis' => 'siswa', 'username' => '0012345678', 'password_hash' => null, 'status' => 'belum_aktif']);

        foreach ([['akun.a', 'bukan-ini'], ['akun.b', $this->passwordUji], ['0012345678', 'sembarang'], ['tidak.ada', 'sembarang']] as [$identitas, $password]) {
            $this->login($identitas, $password)->assertRedirectTo(site_url('login'));
            $this->assertSame(self::SALAH, $_SESSION['galat'], $identitas);
        }

        $alasan = array_map(
            static fn (array $row): array => [$row['akun_id'] === null ? null : (int) $row['akun_id'], json_decode($row['data'], true)['alasan']],
            $this->logs('login_gagal'),
        );
        $this->assertSame([
            [(int) $a['id'], 'password_salah'],
            [(int) $b['id'], 'akun_nonaktif'],
            [(int) $c['id'], 'belum_aktif'],
            [null, 'akun_tidak_ada'],
        ], $alasan);
        $this->assertSame(4, $this->db->table('percobaan_login')->countAllResults());
    }

    // AC-AKN-01-03, SEC-01 item 2, SEC-08, docs/11 §5.1

    public function testFiveFailuresLockForFifteenMinutes(): void
    {
        $this->buatAkun(['username' => 'rina.w']);
        Time::setTestNow('2026-10-13 06:30:20', 'Asia/Jakarta');

        for ($i = 1; $i <= 5; $i++) {
            $this->login('rina.w', "salah-{$i}");
        }

        // The fifth failure starts the lock; 06.45.20 rounds up to 06.46.
        $pesan = 'Terlalu banyak percobaan login. Coba lagi pukul 06.46.';
        $this->assertSame($pesan, $_SESSION['galat']);
        $kunci = $this->logs('login_dikunci');
        $this->assertCount(1, $kunci);
        $this->assertSame(['jenis' => 'pendek', 'sampai' => '2026-10-13 06:45:20'], json_decode($kunci[0]['data'], true));

        // Locked: the right password is refused and the attempt is not recorded.
        Time::setTestNow('2026-10-13 06:40:00', 'Asia/Jakarta');
        $this->login('rina.w', $this->passwordUji)->assertRedirectTo(site_url('login'));
        $this->assertSame($pesan, $_SESSION['galat']);
        $this->assertSame(5, $this->db->table('percobaan_login')->countAllResults());
        $this->assertCount(5, $this->logs('login_gagal'));

        // The window has passed.
        Time::setTestNow('2026-10-13 06:45:21', 'Asia/Jakarta');
        $this->login('rina.w', $this->passwordUji)->assertRedirectTo(site_url('panel'));
        $this->assertSame(0, $this->db->table('percobaan_login')->countAllResults(), 'SEC-09: success clears the failures');
    }

    public function testIpLockMessage(): void
    {
        $this->buatAkun(['username' => 'rina.w']);
        $rows = [];
        for ($i = 0; $i < 100; $i++) {
            $rows[] = ['identitas_hash' => hash('sha256', (string) $i), 'ip' => '0.0.0.0', 'created_at' => '2026-10-13 06:20:00'];
        }
        $this->db->table('percobaan_login')->insertBatch($rows);

        $this->login('rina.w', $this->passwordUji)->assertRedirectTo(site_url('login'));

        $this->assertSame('Terlalu banyak percobaan login dari jaringan ini. Coba lagi pukul 06.35.', $_SESSION['galat']);
    }

    // AC-AKN-01-04

    public function testMustChangePasswordOpensOnlyTheChangePage(): void
    {
        $this->buatAkun(['username' => 'baru.staf', 'wajib_ganti_password' => 1]);

        $this->login('baru.staf', $this->passwordUji)->assertRedirectTo(site_url('akun/password'));
        $this->assertTrue($_SESSION['tanpa_password_lama']);

        $this->withSession()->get('panel/akun-staf')->assertRedirectTo(site_url('akun/password'));
    }

    // AC-AKN-01-05 (staff part; the station part is tested in L06-01)

    public function testStaffOpeningKioskGoesToDashboard(): void
    {
        $this->buatAkun(['username' => 'admin.dawe'], ['admin']);
        $this->login('admin.dawe', $this->passwordUji);

        // The kiosk group has no routes before FASE-06; same group filters as Config/Routes/Kiosk.php.
        $this->withRoutes([['GET', 'kiosk', static fn (): string => 'kiosk', ['filter' => ['sesi', 'area:stasiun']]]]);

        $this->withSession()->get('kiosk')->assertRedirectTo(site_url('panel'));
    }

    // AC-AKN-01-06

    public function testDeactivatedAccountLosesSessionAndCannotLogIn(): void
    {
        $akun = $this->buatAkun(['username' => 'piket.dawe'], ['guru_piket']);
        $this->login('piket.dawe', $this->passwordUji);

        $this->db->table('akun')->where('id', $akun['id'])->update(['status' => 'nonaktif']);

        $this->withSession()->get('panel/akun-staf')->assertRedirectTo(site_url('login'));
        $this->assertArrayNotHasKey('akun_id', $_SESSION);

        $this->login('piket.dawe', $this->passwordUji)->assertRedirectTo(site_url('login'));
        $this->assertSame(self::SALAH, $_SESSION['galat']);
    }

    // AC-AKN-01-07 (unlock through PercobaanLogin::hapus; the button is HAL-AKN-04's)

    public function testTwentyFailuresLockForADayUntilUnlocked(): void
    {
        $akun = $this->buatAkun(['username' => 'rina.w']);
        $hash = (new PercobaanLogin())->identitasHash('rina.w');

        // 19 failures since Monday 19.00, 20 minutes apart so no 15-minute lock holds.
        $rows = [];
        for ($i = 0; $i < 19; $i++) {
            $rows[] = ['identitas_hash' => $hash, 'ip' => '10.0.0.' . $i, 'created_at' => Time::parse('2026-10-12 19:00:00', 'Asia/Jakarta')->addMinutes(20 * $i)->toDateTimeString()];
        }
        $this->db->table('percobaan_login')->insertBatch($rows);

        Time::setTestNow('2026-10-13 01:20:00', 'Asia/Jakarta');
        $this->login('rina.w', 'salah-lagi');

        $kunci = $this->logs('login_dikunci');
        $this->assertCount(1, $kunci);
        $this->assertSame((int) $akun['id'], (int) $kunci[0]['akun_id']);
        $this->assertSame(['jenis' => 'panjang', 'sampai' => '2026-10-13 19:00:00'], json_decode($kunci[0]['data'], true));

        Time::setTestNow('2026-10-13 07:30:00', 'Asia/Jakarta');
        $this->login('rina.w', $this->passwordUji)->assertRedirectTo(site_url('login'));
        $this->assertSame('Login akun ini dikunci karena terlalu banyak percobaan. Hubungi wali kelas atau admin.', $_SESSION['galat']);

        (new PercobaanLogin())->hapus($hash);

        $this->login('rina.w', $this->passwordUji)->assertRedirectTo(site_url('panel'));
    }

    // AC-AKN-01-08, RT-18 items 2 and 3

    public function testIdleSessionEndsAndLoginReturnsToThePage(): void
    {
        $this->buatAkun(['username' => 'rina.w']);
        $this->login('rina.w', $this->passwordUji);

        Time::setTestNow('2026-10-13 14:31:00', 'Asia/Jakarta');
        $this->withSession()->get('panel/akun-staf?halaman=2')->assertRedirectTo(site_url('login'));
        $this->assertSame('Sesi berakhir. Login lagi untuk melanjutkan.', $_SESSION['galat']);

        $this->login('rina.w', $this->passwordUji)->assertRedirectTo(site_url('panel/akun-staf?halaman=2'));
        $this->assertArrayNotHasKey('tujuan', $_SESSION);
    }

    /**
     * @dataProvider provideTujuanOutsideTheAreaGoesHome
     */
    public function testTujuanOutsideTheAreaGoesHome(string $tujuan): void
    {
        $this->buatAkun(['username' => 'rina.w']);
        $_SESSION['tujuan'] = $tujuan;

        $this->login('rina.w', $this->passwordUji)->assertRedirectTo(site_url('panel'));
        $this->assertArrayNotHasKey('tujuan', $_SESSION);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideTujuanOutsideTheAreaGoesHome(): iterable
    {
        yield 'other host' => ['//evil.test/panel'];
        yield 'backslash' => ['/\\evil.test'];
        yield 'student area' => ['/portal'];
        yield 'kiosk' => ['/kiosk'];
        yield 'prefix only' => ['/panelx'];
        yield 'full url' => ['https://evil.test/panel'];
    }

    // RT-18 item 4

    public function testLoginPageSendsLoggedInUserHome(): void
    {
        $this->buatAkun(['username' => 'rina.w']);
        $this->login('rina.w', $this->passwordUji);

        $this->withSession()->get('login')->assertRedirectTo(site_url('panel'));
    }

    // HAL-AKN-02, SEC-15, SEC-29

    public function testLogoutEndsSessionWithNewToken(): void
    {
        $this->buatAkun(['username' => 'rina.w']);
        $this->login('rina.w', $this->passwordUji);
        $token = service('security')->getHash();

        Services::resetSingle('response');
        $result = $this->withSession()->withHeaders(['X-CSRF-TOKEN' => $token])->post('logout');

        $result->assertStatus(303);
        $result->assertRedirectTo(site_url('login'));
        $this->assertArrayNotHasKey('akun_id', $_SESSION);
        $this->assertArrayNotHasKey('cap', $_SESSION);
        $this->assertNotSame($token, service('security')->getHash());
        // An empty value makes PHP's setcookie() delete the cookie.
        $this->assertSame('', $result->response()->getCookie(config('Session')->cookieName)->getValue());

        $this->withSession()->get('panel')->assertRedirectTo(site_url('login'));
    }

    // Service details: SEC-02 rehash, SEC-10 named lock, SEC-19 station login ID, student first login

    public function testRehashesOldCostOnLogin(): void
    {
        $akun = $this->buatAkun(['username' => 'rina.w', 'password_hash' => password_hash($this->passwordUji, PASSWORD_BCRYPT, ['cost' => 10])]);

        $hasil = (new LoginService())->masuk('rina.w', $this->passwordUji, '10.0.0.1');

        $this->assertSame('berhasil', $hasil['hasil']);
        $hash = $this->akunRow((int) $akun['id'])['password_hash'];
        $this->assertStringStartsWith('$2y$11$', $hash);
        $this->assertSame($hash, $hasil['akun']['password_hash']);
        $this->assertTrue(password_verify($this->passwordUji, $hash));
    }

    public function testLockNotObtainedAnswersAsLocked(): void
    {
        $this->buatAkun(['username' => 'rina.w']);
        $sibuk = new class () extends NamedLock {
            public function acquire(string $name, int $timeout): bool
            {
                return false;
            }
        };

        $hasil = (new LoginService($sibuk))->masuk('rina.w', $this->passwordUji, '10.0.0.1');

        $this->assertSame('dikunci', $hasil['hasil']);
        $this->assertSame('pendek', $hasil['jenis']);
        $this->assertSame(0, $this->db->table('percobaan_login')->countAllResults());
    }

    public function testStationLoginWritesNewLoginIdAndLogsMove(): void
    {
        $akun = $this->buatAkun(['jenis' => 'stasiun', 'username' => 'gerbang.utara', 'login_stasiun_id' => str_repeat('a', 32)]);

        $hasil = (new LoginService())->masuk('gerbang.utara', $this->passwordUji, '10.0.0.1');

        $baru = $this->akunRow((int) $akun['id'])['login_stasiun_id'];
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $baru);
        $this->assertNotSame(str_repeat('a', 32), $baru);
        $this->assertSame($baru, $hasil['akun']['login_stasiun_id']);
        $this->assertSame(['jenis' => 'stasiun', 'login_stasiun_id' => $baru], json_decode($this->logs('login_berhasil')[0]['data'], true));
        // MySQL JSON reorders keys; compare without order.
        $this->assertEquals(['lama' => str_repeat('a', 32), 'baru' => $baru], json_decode($this->logs('login_stasiun_berpindah')[0]['data'], true));
    }

    public function testStudentNotYetActiveWithPasswordMayLogIn(): void
    {
        $this->buatAkun(['jenis' => 'siswa', 'username' => '0012345678', 'status' => 'belum_aktif', 'wajib_ganti_password' => 1]);

        $this->assertSame('berhasil', (new LoginService())->masuk(' 0012345678', $this->passwordUji, '10.0.0.1')['hasil']);
        $this->assertCount(0, $this->logs('login_stasiun_berpindah'));
    }

    /**
     * POST /login with a valid CSRF token, carrying the current session.
     */
    private function login(string $identitas, string $password): TestResponse
    {
        Services::resetSingle('response');

        return $this->withSession()
            ->withHeaders(['X-CSRF-TOKEN' => service('security')->getHash()])
            ->post('login', ['identitas' => $identitas, 'password' => $password]);
    }

    /**
     * @return array<string, mixed>
     */
    private function akunRow(int $id): array
    {
        return $this->db->table('akun')->where('id', $id)->get()->getRowArray();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function logs(string $jenis): array
    {
        return $this->db->table('log_aktivitas')->where('jenis', $jenis)->orderBy('id')->get()->getResultArray();
    }
}
