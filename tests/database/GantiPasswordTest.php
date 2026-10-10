<?php

use App\Services\Akun\Kredensial;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;
use Config\Services;
use Tests\Support\AkunTrait;

/**
 * Change password (docs/04 FS-AKN-02, AC-AKN-02-*, docs/09 HAL-AKN-03,
 * docs/12 SEC-06, SEC-14, docs/07 ARS-47 item 2).
 *
 * @internal
 */
final class GantiPasswordTest extends CIUnitTestCase
{
    use AkunTrait;
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $refresh   = true;
    protected $namespace = 'App';

    private const BARU = 'sepeda biru di teras';

    protected function setUp(): void
    {
        Services::resetSingle('routes');
        Services::resetSingle('router');
        Services::resetSingle('request');
        Services::resetSingle('response');
        parent::setUp();
        Time::setTestNow('2026-10-13 06:30:00', 'Asia/Jakarta');
    }

    protected function tearDown(): void
    {
        Time::setTestNow();
        parent::tearDown();
    }

    public function testFirstStudentLoginChangesPasswordWithoutOldOne(): void
    {
        // AC-AKN-02-01
        $siswa   = $this->buatAkun(['jenis' => 'siswa', 'username' => '0012345678', 'status' => 'belum_aktif', 'wajib_ganti_password' => 1]);
        $session = ['tanpa_password_lama' => true] + $this->sesiAkun($siswa);

        $page = $this->withSession($session)->get('akun/password');
        $page->assertOK();
        $page->assertSee('Ganti password sebelum melanjutkan.');
        $page->assertSee('mudah kamu ingat');
        $body = $page->response()->getBody();
        $this->assertStringNotContainsString('name="password_lama"', $body);
        $this->assertStringContainsString('autocomplete="new-password"', $body);
        $this->assertStringContainsString('name="_method" value="PUT"', $body);
        $this->assertStringNotContainsString('panel-samping', $body);
        $this->assertStringNotContainsString('portal-menu', $body);

        $result = $this->ganti($session, ['password_baru' => self::BARU, 'password_ulang' => self::BARU]);

        $result->assertStatus(303);
        $result->assertRedirectTo('https://example.com/portal');
        $this->assertSame('Password sudah diganti.', session('sukses'));

        $akun = $this->akun($siswa['id']);
        $this->assertSame('aktif', $akun['status']);
        $this->assertSame('0', (string) $akun['wajib_ganti_password']);
        $this->assertSame('2026-10-13 06:30:00', $akun['password_diganti_at']);
        $this->assertSame($siswa['updated_at'], $akun['updated_at']);
        $this->assertTrue((new Kredensial())->cocok(self::BARU, $akun['password_hash']));
        $this->assertFalse((new Kredensial())->cocok($this->passwordUji, $akun['password_hash']));
        $this->assertNull(session('tanpa_password_lama'));

        $this->seeInDatabase('log_aktivitas', ['jenis' => 'password_diganti', 'pelaku_id' => $siswa['id'], 'akun_id' => $siswa['id']]);
        $this->assertSame(['wajib' => true], $this->logData('password_diganti'));
    }

    public function testWrongOldPasswordIsRejectedAndCountsAsFailedLogin(): void
    {
        // AC-AKN-02-02, docs/12 SEC-06
        $staf    = $this->buatAkun(['username' => 'rina']);
        $session = $this->sesiAkun($staf);

        $page = $this->withSession($session)->get('akun/password');
        $body = $page->response()->getBody();
        $this->assertStringContainsString('name="password_lama"', $body);
        $this->assertStringContainsString('autocomplete="current-password"', $body);
        $this->assertStringContainsString('mudah Anda ingat', $body);
        $this->assertStringContainsString('panel-samping', $body);

        $result = $this->ganti($session, ['password_lama' => 'bukan-ini-ya', 'password_baru' => self::BARU, 'password_ulang' => self::BARU]);

        $result->assertRedirectTo('https://example.com/akun/password');
        $this->assertSame(['password_lama' => 'Password lama salah.'], session('_ci_validation_errors'));
        $this->assertArrayNotHasKey('_ci_old_input', $_SESSION);
        $this->assertSame(1, session('salah_password_lama'));
        $this->assertSame($staf['password_hash'], $this->akun($staf['id'])['password_hash']);
        $this->seeNumRecords(1, 'percobaan_login', []);
        $this->seeInDatabase('log_aktivitas', ['jenis' => 'login_gagal', 'pelaku_id' => null, 'akun_id' => $staf['id']]);
        $this->assertSame(['alasan' => 'password_salah'], $this->logData('login_gagal'));
        $this->dontSeeInDatabase('log_aktivitas', ['jenis' => 'password_diganti']);
    }

    public function testErrorsShowOnTheFormWithoutPasswords(): void
    {
        $session = $this->sesiAkun($this->buatAkun());
        $this->ganti($session, ['password_lama' => $this->passwordUji, 'password_baru' => 'abc12', 'password_ulang' => 'xyz-99']);

        $page = $this->withSession($_SESSION)->get('akun/password');

        $page->assertSee('Periksa 2 isian yang ditandai.');
        $page->assertSee('Password paling sedikit 8 karakter.');
        $page->assertSee('Ulangan password tidak sama.');
        $this->assertStringNotContainsString('abc12', $page->response()->getBody());
        $this->assertStringNotContainsString('xyz-99', $page->response()->getBody());
    }

    public function testStudentNewPasswordRulesAreExplained(): void
    {
        // AC-AKN-02-03, AC-AKN-02-05
        $siswa   = $this->buatAkun(['jenis' => 'siswa', 'username' => '0012345678']);
        $session = $this->sesiAkun($siswa);

        foreach ([
            'indonesia123'    => 'Password ini terlalu umum dan mudah ditebak. Pilih password lain.',
            'bismillah'       => 'Password ini terlalu umum dan mudah ditebak. Pilih password lain.',
            '0012345678'      => 'Password tidak boleh memuat NISN atau username.',
            'zebra0012345678' => 'Password tidak boleh memuat NISN atau username.',
            $this->passwordUji => 'Password baru harus berbeda dengan password saat ini.',
        ] as $baru => $pesan) {
            $this->ganti($session, ['password_lama' => $this->passwordUji, 'password_baru' => $baru, 'password_ulang' => $baru]);
            $this->assertSame(['password_baru' => $pesan], session('_ci_validation_errors'), $baru);
        }

        $this->assertSame($siswa['password_hash'], $this->akun($siswa['id'])['password_hash']);

        $result = $this->ganti($session, ['password_lama' => $this->passwordUji, 'password_baru' => self::BARU, 'password_ulang' => self::BARU]);
        $result->assertRedirectTo('https://example.com/portal');
        $this->assertTrue((new Kredensial())->cocok(self::BARU, $this->akun($siswa['id'])['password_hash']));
        $this->assertSame(['wajib' => false], $this->logData('password_diganti'));
    }

    public function testOtherSessionsEndWhileThisOneContinues(): void
    {
        // AC-AKN-02-04, docs/07 ARS-47 item 2, docs/12 SEC-14
        $staf  = $this->buatAkun();
        $kedua = $this->sesiAkun($staf);

        $result = $this->ganti($this->sesiAkun($staf), ['password_lama' => $this->passwordUji, 'password_baru' => self::BARU, 'password_ulang' => self::BARU]);

        $result->assertRedirectTo('https://example.com/panel');
        $this->assertTrue(session()->didRegenerate);
        $pertama = $_SESSION;

        $this->withSession($pertama)->get('akun/password')->assertOK();
        $this->withSession($kedua)->get('akun/password')->assertRedirectTo('https://example.com/login');
    }

    public function testFiveWrongOldPasswordsEndTheSession(): void
    {
        // docs/11 §5.1 FS-AKN-02 E1; the 5th failure also starts the 15-minute lock (docs/12 SEC-08, SEC-11).
        $staf    = $this->buatAkun(['username' => 'budi']);
        $session = $this->sesiAkun($staf);
        $isian   = ['password_lama' => 'salah-terus', 'password_baru' => self::BARU, 'password_ulang' => self::BARU];

        for ($i = 1; $i <= 4; $i++) {
            $this->ganti($session, $isian)->assertRedirectTo('https://example.com/akun/password');
            $session = $_SESSION;
        }
        $this->dontSeeInDatabase('log_aktivitas', ['jenis' => 'login_dikunci']);

        $result = $this->ganti($session, $isian);

        $result->assertRedirectTo('https://example.com/login');
        $this->assertStringContainsString('Login lagi', session('galat'));
        $this->assertNull(session('akun_id'));
        $this->assertNull(session('salah_password_lama'));
        $this->seeNumRecords(5, 'percobaan_login', []);
        $this->seeInDatabase('log_aktivitas', ['jenis' => 'login_dikunci', 'pelaku_id' => null, 'akun_id' => $staf['id']]);
        $this->assertSame(['jenis' => 'pendek', 'sampai' => '2026-10-13 06:45:00'], $this->logData('login_dikunci'));
        $this->withSession($_SESSION)->get('akun/password')->assertRedirectTo('https://example.com/login');
    }

    public function testCorrectOldPasswordResetsTheWrongCount(): void
    {
        $session = ['salah_password_lama' => 4] + $this->sesiAkun($this->buatAkun());

        $this->ganti($session, ['password_lama' => $this->passwordUji, 'password_baru' => 'pendek', 'password_ulang' => 'pendek']);

        $this->assertNull(session('salah_password_lama'));
    }

    public function testMissingFieldsAreRequired(): void
    {
        $this->ganti($this->sesiAkun($this->buatAkun()), []);

        $this->assertSame([
            'password_lama'  => 'Password lama wajib diisi.',
            'password_baru'  => 'Password baru wajib diisi.',
            'password_ulang' => 'Ulangi password baru wajib diisi.',
        ], session('_ci_validation_errors'));
    }

    /**
     * Sends the form as the browser does: POST with _method=PUT and the CSRF token.
     *
     * @param array<string, mixed> $session
     * @param array<string, string> $isian
     */
    private function ganti(array $session, array $isian): TestResponse
    {
        // The shared router keeps the method of the last request; the form spoofs PUT over POST.
        foreach (['router', 'response'] as $service) {
            Services::resetSingle($service);
        }

        return $this->withSession($session)
            ->withHeaders(['X-CSRF-TOKEN' => service('security')->getHash()])
            ->post('akun/password', ['_method' => 'PUT'] + $isian);
    }

    /**
     * @return array<string, mixed>
     */
    private function akun(int|string $id): array
    {
        return $this->db->table('akun')->where('id', $id)->get()->getRowArray();
    }

    /**
     * @return array<string, mixed>
     */
    private function logData(string $jenis): array
    {
        $row = $this->db->table('log_aktivitas')->where('jenis', $jenis)->orderBy('id', 'DESC')->get()->getRowArray();

        return json_decode($row['data'], true);
    }
}
