<?php

use App\Services\Akun\AkunSiswa;
use App\Services\Akun\Kredensial;
use App\Services\Akun\PercobaanLogin;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;
use Config\Services;
use Tests\Support\AkunTrait;
use Tests\Support\MasterDataTrait;

/**
 * Login and change password retested for student accounts (docs/15 L02-09:
 * AC-AKN-01-* and AC-AKN-02-* with student accounts), from a slip password
 * to the portal (docs/04 FS-AKN-05, AC-AKN-02-01, AC-AKN-05-03, AC-AKN-05-04).
 *
 * @internal
 */
final class LoginSiswaTest extends CIUnitTestCase
{
    use AkunTrait;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use MasterDataTrait;

    protected $refresh   = true;
    protected $namespace = 'App';

    private const SALAH = 'NISN/username atau password salah.';
    private const BARU  = 'sepeda biru di teras';

    /** @var array<string, mixed> */
    private array $admin;

    /** @var array<string, mixed> */
    private array $wali;

    /** @var array<string, mixed> */
    private array $rombel;

    /** @var array<string, mixed> */
    private array $siswa;

    protected function setUp(): void
    {
        Services::resetSingle('routes');
        Services::resetSingle('router');
        Services::resetSingle('request');
        Services::resetSingle('response');
        parent::setUp();
        $_SESSION = [];
        Time::setTestNow('2026-10-13 06:30:00', 'Asia/Jakarta');

        $this->admin  = $this->buatAkun(['username' => 'admin.tu'], ['admin']);
        $this->wali   = $this->buatAkun(['username' => 'wali.7a', 'nama' => 'Rina Wulandari']);
        $ta           = $this->buatTahunAjaran();
        $this->rombel = $this->buatRombel(['tahun_ajaran_id' => $ta['id'], 'nama' => '7A', 'wali_kelas_id' => $this->wali['id']]);
        $this->siswa  = $this->buatSiswa(['nisn' => '0012345678', 'nama' => 'Bela Kusuma', 'tanggal_lahir' => '2013-05-02', 'rombel_id' => $this->rombel['id']]);
    }

    protected function tearDown(): void
    {
        Time::setTestNow();
        parent::tearDown();
    }

    public function testSlipPasswordOpensPortalAfterChangingIt(): void
    {
        // AC-AKN-05-01 (no password before the slip), AC-AKN-02-01, AC-AKN-01-04
        $this->login('0012345678', 'sembarang')->assertRedirectTo(site_url('login'));
        $this->assertSame(self::SALAH, $_SESSION['galat']);

        $lain = $this->buatSiswa(['nama' => 'Siswa Lain', 'nisn' => '0099999999', 'rombel_id' => $this->rombel['id']]);
        $slip = $this->slip();

        $this->login(' 0012345678 ', $slip)->assertRedirectTo(site_url('akun/password'));
        $this->assertSame('siswa', $_SESSION['jenis']);
        $this->assertTrue($_SESSION['tanpa_password_lama']);

        // Every other page leads back to the change-password page.
        $this->buka('portal')->assertRedirectTo(site_url('akun/password'));
        $this->buka('portal/akun')->assertRedirectTo(site_url('akun/password'));

        $this->ganti(['password_baru' => self::BARU, 'password_ulang' => self::BARU])->assertRedirectTo(site_url('portal'));
        $this->assertSame('aktif', $this->akun()['status']);
        $this->assertSame('0', (string) $this->akun()['wajib_ganti_password']);

        $this->buka('portal')->assertSee('Halaman ini sedang disiapkan.');
        $akun = $this->buka('portal/akun');
        $akun->assertSee('Bela Kusuma');
        $akun->assertSee('0012345678');
        $akun->assertDontSee('Siswa Lain');
        $akun->assertDontSee($lain['nisn']);

        // The slip password no longer works; the new one does.
        $_SESSION = [];
        $this->login('0012345678', $slip)->assertRedirectTo(site_url('login'));
        $this->login('0012345678', self::BARU)->assertRedirectTo(site_url('portal'));
    }

    public function testActiveStudentGoesToPortalAndCannotOpenPanel(): void
    {
        // AC-AKN-01-01 (student part), AC-AKN-01-05 (area separation)
        $this->aktifkan();

        $this->login('0012345678', $this->passwordUji)->assertRedirectTo(site_url('portal'));
        $this->assertSame('2026-10-13 06:30:00', $this->akun()['login_terakhir_at']);

        $this->buka('portal')->assertOK();
        $this->buka('panel')->assertRedirectTo(site_url('portal'));
    }

    public function testWrongPasswordGivesTheCommonMessage(): void
    {
        // AC-AKN-01-02
        $this->aktifkan();

        $this->login('0012345678', 'bukan-ini')->assertRedirectTo(site_url('login'));
        $this->assertSame(self::SALAH, $_SESSION['galat']);
        $this->assertArrayNotHasKey('akun_id', $_SESSION);
    }

    public function testFiveFailuresLockUntilTheWindowPasses(): void
    {
        // AC-AKN-01-03
        $this->aktifkan();

        for ($i = 0; $i < 5; $i++) {
            $this->login('0012345678', 'salah-' . $i);
        }

        $this->login('0012345678', $this->passwordUji)->assertRedirectTo(site_url('login'));
        $this->assertSame('Terlalu banyak percobaan login. Coba lagi pukul 06.45.', $_SESSION['galat']);

        Time::setTestNow('2026-10-13 06:46:00', 'Asia/Jakarta');
        $this->login('0012345678', $this->passwordUji)->assertRedirectTo(site_url('portal'));
    }

    public function testDayLockIsOpenedByTheWaliKelas(): void
    {
        // AC-AKN-01-07
        $this->aktifkan();
        $hash = (new PercobaanLogin())->identitasHash('0012345678');
        $rows = [];
        for ($i = 0; $i < 19; $i++) {
            $rows[] = ['identitas_hash' => $hash, 'ip' => '10.0.0.' . $i, 'created_at' => Time::parse('2026-10-12 19:00:00', 'Asia/Jakarta')->addMinutes(20 * $i)->toDateTimeString()];
        }
        $this->db->table('percobaan_login')->insertBatch($rows);

        Time::setTestNow('2026-10-13 01:20:00', 'Asia/Jakarta');
        $this->login('0012345678', 'salah-lagi');
        $this->seeInDatabase('log_aktivitas', ['jenis' => 'login_dikunci', 'akun_id' => $this->siswa['akun_id']]);

        Time::setTestNow('2026-10-13 07:30:00', 'Asia/Jakarta');
        $this->login('0012345678', $this->passwordUji)->assertRedirectTo(site_url('login'));
        $this->assertSame('Login akun ini dikunci karena terlalu banyak percobaan. Hubungi wali kelas atau admin.', $_SESSION['galat']);

        Services::resetSingle('response');
        $buka = $this->withSession($this->sesiAkun($this->wali))
            ->withHeaders(['X-CSRF-TOKEN' => service('security')->getHash()])
            ->post("panel/siswa/{$this->siswa['id']}/buka-kunci");
        $buka->assertRedirectTo(site_url("panel/siswa/{$this->siswa['id']}"));
        $this->assertSame('Kunci login Bela Kusuma sudah dibuka.', session('sukses'));
        $this->seeInDatabase('log_aktivitas', ['jenis' => 'kunci_login_dibuka', 'pelaku_id' => $this->wali['id'], 'akun_id' => $this->siswa['akun_id']]);

        $_SESSION = [];
        $this->login('0012345678', $this->passwordUji)->assertRedirectTo(site_url('portal'));
    }

    public function testDeactivatedStudentLosesSessionAndCannotLogIn(): void
    {
        // AC-AKN-01-06, AC-AKN-05-06 (account part; deactivating the student is FS-MD-04)
        $this->aktifkan();
        $this->login('0012345678', $this->passwordUji);

        $this->db->table('akun')->where('id', $this->siswa['akun_id'])->update(['status' => 'nonaktif']);

        $this->buka('portal')->assertRedirectTo(site_url('login'));
        $this->assertArrayNotHasKey('akun_id', $_SESSION);
        $this->login('0012345678', $this->passwordUji)->assertRedirectTo(site_url('login'));
        $this->assertSame(self::SALAH, $_SESSION['galat']);
    }

    public function testIdleSessionEndsAndLoginReturnsToThePage(): void
    {
        // AC-AKN-01-08 (student part)
        $this->aktifkan();
        $this->login('0012345678', $this->passwordUji);

        Time::setTestNow('2026-10-13 14:31:00', 'Asia/Jakarta');
        $this->buka('portal/akun')->assertRedirectTo(site_url('login'));
        $this->assertSame('Sesi berakhir. Login lagi untuk melanjutkan.', $_SESSION['galat']);

        $this->login('0012345678', $this->passwordUji)->assertRedirectTo(site_url('portal/akun'));
    }

    public function testResetByWaliKelasForcesChangeAtNextLogin(): void
    {
        // AC-AKN-05-04: the old password and the old session stop working.
        $this->aktifkan();
        $this->login('0012345678', $this->passwordUji);
        $lama = $_SESSION;

        $siswa = (new AkunSiswa())->cariSiswa((int) $this->siswa['id']);
        $hasil = (new AkunSiswa())->resetPassword($siswa, $this->rombel, (int) $this->wali['id'], '10.0.0.1');
        $baru  = $hasil['slip']['password'];
        $this->assertSame(8, strlen($baru));

        $this->withSession($lama)->get('portal')->assertRedirectTo(site_url('login'));

        $_SESSION = [];
        $this->login('0012345678', $this->passwordUji)->assertRedirectTo(site_url('login'));
        $this->login('0012345678', $baru)->assertRedirectTo(site_url('akun/password'));
        $this->assertSame('aktif', $this->akun()['status']);

        $this->ganti(['password_baru' => self::BARU, 'password_ulang' => self::BARU])->assertRedirectTo(site_url('portal'));
        $this->assertTrue((new Kredensial())->cocok(self::BARU, $this->akun()['password_hash']));
    }

    public function testStudentChangingPasswordLaterNeedsTheOldOne(): void
    {
        // AC-AKN-02-02, AC-AKN-02-03, AC-AKN-02-05 (student part)
        $this->aktifkan();
        $this->login('0012345678', $this->passwordUji);

        $this->ganti(['password_lama' => 'salah-lama', 'password_baru' => self::BARU, 'password_ulang' => self::BARU])->assertRedirectTo(site_url('akun/password'));
        $this->assertSame(['password_lama' => 'Password lama salah.'], session('_ci_validation_errors'));

        foreach ([
            'bismillah'       => 'Password ini terlalu umum dan mudah ditebak. Pilih password lain.',
            'zebra0012345678' => 'Password tidak boleh memuat NISN atau username.',
        ] as $baru => $pesan) {
            $this->ganti(['password_lama' => $this->passwordUji, 'password_baru' => $baru, 'password_ulang' => $baru]);
            $this->assertSame(['password_baru' => $pesan], session('_ci_validation_errors'), $baru);
        }
        $this->assertTrue((new Kredensial())->cocok($this->passwordUji, $this->akun()['password_hash']));

        $this->ganti(['password_lama' => $this->passwordUji, 'password_baru' => self::BARU, 'password_ulang' => self::BARU])->assertRedirectTo(site_url('portal'));
        $this->assertTrue((new Kredensial())->cocok(self::BARU, $this->akun()['password_hash']));
    }

    public function testOtherStudentSessionEndsAfterChange(): void
    {
        // AC-AKN-02-04 (student part)
        $this->aktifkan();
        $this->login('0012345678', $this->passwordUji);
        $kedua = $_SESSION;

        $this->ganti(['password_lama' => $this->passwordUji, 'password_baru' => self::BARU, 'password_ulang' => self::BARU]);
        $pertama = $_SESSION;

        $this->withSession($pertama)->get('portal')->assertOK();
        Services::resetSingle('response');
        $this->withSession($kedua)->get('portal')->assertRedirectTo(site_url('login'));
    }

    /**
     * Slip made by the wali kelas through the service; returns the student's password.
     */
    private function slip(): string
    {
        $slips = (new AkunSiswa())->buatSlip($this->rombel, [(int) $this->siswa['id']], (int) $this->wali['id'], '10.0.0.1');

        return $slips[0]['password'];
    }

    /**
     * Active account with the test password, not required to change it.
     */
    private function aktifkan(): void
    {
        $this->db->table('akun')->where('id', $this->siswa['akun_id'])->update([
            'status' => 'aktif', 'wajib_ganti_password' => 0, 'password_hash' => (new Kredensial())->hash($this->passwordUji),
        ]);
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
     * GET with the current session.
     */
    private function buka(string $uri): TestResponse
    {
        Services::resetSingle('response');

        return $this->withSession()->get($uri);
    }

    /**
     * The change-password form as the browser sends it, with the current session.
     *
     * @param array<string, string> $isian
     */
    private function ganti(array $isian): TestResponse
    {
        // The shared router keeps the method of the last request; the form spoofs PUT over POST.
        foreach (['router', 'response'] as $service) {
            Services::resetSingle($service);
        }

        return $this->withSession()
            ->withHeaders(['X-CSRF-TOKEN' => service('security')->getHash()])
            ->post('akun/password', ['_method' => 'PUT'] + $isian);
    }

    /**
     * @return array<string, mixed>
     */
    private function akun(): array
    {
        return $this->db->table('akun')->where('id', $this->siswa['akun_id'])->get()->getRowArray();
    }
}
