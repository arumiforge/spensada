<?php

use App\Services\Akun\DiLuarHak;
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
 * Student accounts and slips (docs/04 FS-AKN-05 B to D, AC-AKN-05-02 to 05,
 * docs/09 HAL-AKN-05, HAL-AKN-06, RT-09, docs/12 SEC-05, SEC-11, SEC-59).
 *
 * @internal
 */
final class AkunSiswaTest extends CIUnitTestCase
{
    use AkunTrait;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use MasterDataTrait;

    protected $refresh   = true;
    protected $namespace = 'App';

    private const TOKEN_TERPAKAI = 'Tindakan ini sudah dijalankan. Password baru tidak dibuat lagi. Bila password belum tercatat, reset sekali lagi.';

    /** docs/12 SEC-05: 8 characters from a–z without i, l, o, plus 2–9. */
    private const POLA_PASSWORD = '/^[a-hjkmnp-z2-9]{8}$/';

    /** @var array<string, mixed> */
    private array $admin;

    /** @var array<string, mixed> */
    private array $wali7a;

    /** @var array<string, mixed> */
    private array $rombel7a;

    /** @var array<string, mixed> */
    private array $rombel7b;

    protected function setUp(): void
    {
        Services::resetSingle('routes');
        Services::resetSingle('router');
        Services::resetSingle('request');
        Services::resetSingle('response');
        parent::setUp();
        $_SESSION = [];
        Time::setTestNow('2026-10-13 07:00:00', 'Asia/Jakarta');

        $this->admin    = $this->buatAkun(['username' => 'admin.tu', 'nama' => 'Admin Tata Usaha'], ['admin']);
        $this->wali7a   = $this->buatAkun(['username' => 'wali.7a', 'nama' => 'Rina Wulandari']);
        $wali7b         = $this->buatAkun(['username' => 'wali.7b', 'nama' => 'Budi Santoso']);
        $ta             = $this->buatTahunAjaran();
        $this->rombel7a = $this->buatRombel(['tahun_ajaran_id' => $ta['id'], 'nama' => '7A', 'wali_kelas_id' => $this->wali7a['id']]);
        $this->rombel7b = $this->buatRombel(['tahun_ajaran_id' => $ta['id'], 'nama' => '7B', 'wali_kelas_id' => $wali7b['id']]);
    }

    protected function tearDown(): void
    {
        Time::setTestNow();
        parent::tearDown();
    }

    public function testWaliKelasSeesOnlyOwnClasses(): void
    {
        $pilih = $this->kirim($this->wali7a, 'GET', 'panel/akun-siswa');
        $pilih->assertOK();
        $pilih->assertSee('?kelas=' . $this->rombel7a['id']);
        $pilih->assertDontSee('?kelas=' . $this->rombel7b['id']);

        $this->kirim($this->admin, 'GET', 'panel/akun-siswa')->assertSee('?kelas=' . $this->rombel7b['id']);

        $this->ditolak($this->wali7a, 'GET', 'panel/akun-siswa?kelas=' . $this->rombel7b['id']);
    }

    public function testListShowsAccountsWithCheckboxOnlyForNotYetActive(): void
    {
        $aktif = $this->siswa('Andi Pratama', 'aktif', ['login_terakhir_at' => '2026-10-12 07:32:00', 'slip_dibuat_at' => '2026-09-01 08:00:00']);
        $belum = $this->siswa('Bela Kusuma');
        $this->buatSiswa(['nama' => 'Siswa Kelas Lain', 'rombel_id' => $this->rombel7b['id']]);

        $result = $this->kirim($this->wali7a, 'GET', 'panel/akun-siswa?kelas=' . $this->rombel7a['id']);

        $result->assertOK();
        $result->assertSee('Akun siswa kelas 7A');
        $result->assertSee($aktif['nisn']);
        $result->assertSee('12 Okt 2026, 07.32');
        $result->assertSee('1 Sep 2026, 08.00');
        $result->assertSee('Belum aktif');
        $result->assertSee('Belum pernah');
        $result->assertDontSee('Siswa Kelas Lain');
        $result->assertSee('Buat slip akun');
        $result->assertSee('name="siswa[]" value="' . $belum['id'] . '"');
        $result->assertDontSee('name="siswa[]" value="' . $aktif['id'] . '"');
    }

    public function testSlipOnlyForChosenNotYetActiveAccounts(): void
    {
        // AC-AKN-05-02, FS-AKN-05 B2 to B6
        $aktif1 = $this->siswa('Andi Pratama', 'aktif');
        $aktif2 = $this->siswa('Cici Lestari', 'aktif');
        $belum1 = $this->siswa('Bela Kusuma');
        $belum2 = $this->siswa('Dodi Setiawan');
        $lain   = $this->buatSiswa(['nama' => 'Siswa Kelas Lain', 'rombel_id' => $this->rombel7b['id']]);
        $semua  = [$aktif1['id'], $aktif2['id'], $belum1['id'], $belum2['id'], $lain['id']];

        $konfirmasi = $this->kirim($this->wali7a, 'GET', $this->alamatFormSlip($this->rombel7a, $semua));
        $konfirmasi->assertOK();
        $konfirmasi->assertSee('2 akun akan mendapat password baru.');
        $konfirmasi->assertSee('Slip lama untuk akun tersebut tidak berlaku lagi.');
        $konfirmasi->assertDontSee('Andi Pratama');
        $token = $this->tokenDari($konfirmasi);

        // The form posts the IDs shown; extra IDs sent by hand are ignored.
        $isian  = ['token_sekali' => $token, 'kelas' => $this->rombel7a['id'], 'siswa' => $semua];
        $result = $this->kirim($this->wali7a, 'POST', 'panel/akun-siswa/slip', $isian, ['token_sekali' => $_SESSION['token_sekali']]);
        $sesi   = $_SESSION;

        $result->assertOK();
        $this->assertStringContainsString('no-store', $result->response()->getHeaderLine('Cache-Control'));
        $result->assertSee('Password hanya tampil sekali. Cetak atau simpan sekarang.');
        $result->assertSee('Cetak dengan skala 100% dan tanpa header atau footer browser.');
        $result->assertSee('Akun Spensada');
        $result->assertSee('Login dengan NISN dan password di atas.');
        $result->assertSee('Lupa password? Hubungi wali kelas.');
        $passwords = $this->passwords($result);
        $this->assertCount(2, $passwords);
        $result->assertSee('Bela Kusuma');
        $result->assertSee($belum2['nisn']);
        $result->assertDontSee('Andi Pratama');
        $result->assertDontSee('Siswa Kelas Lain');

        foreach ([$belum1, $belum2] as $i => $siswa) {
            $akun = $this->akun($siswa['akun_id']);
            $this->assertMatchesRegularExpression(self::POLA_PASSWORD, $passwords[$i]);
            $this->assertTrue((new Kredensial())->cocok($passwords[$i], $akun['password_hash']));
            $this->assertSame('1', (string) $akun['wajib_ganti_password']);
            $this->assertSame('2026-10-13 07:00:00', $akun['slip_dibuat_at']);
            $this->assertSame('belum_aktif', $akun['status']);
        }
        foreach ([$aktif1, $aktif2, $lain] as $siswa) {
            $this->assertSame($siswa['akun_hash'] ?? null, $this->akun($siswa['akun_id'])['password_hash']);
            $this->assertNull($this->akun($siswa['akun_id'])['slip_dibuat_at']);
        }

        $log = $this->db->table('log_aktivitas')->where('jenis', 'slip_dicetak')->get()->getResultArray();
        $this->assertCount(1, $log);
        $this->assertSame([(int) $this->wali7a['id'], (int) $this->rombel7a['id']], [(int) $log[0]['pelaku_id'], (int) $log[0]['rombel_id']]);
        $this->assertEquals(['jumlah' => 2, 'akun_ids' => [$belum1['akun_id'], $belum2['akun_id']]], json_decode($log[0]['data'], true));
        foreach ($passwords as $password) {
            $this->assertStringNotContainsString($password, $log[0]['data']);
            $this->assertStringNotContainsString($password, serialize($sesi));
        }

        // Reload sends the same token again: no new passwords (RT-09).
        $hash   = $this->akun($belum1['akun_id'])['password_hash'];
        $ulang  = $this->kirim($this->wali7a, 'POST', 'panel/akun-siswa/slip', $isian, ['token_sekali' => $sesi['token_sekali']]);
        $ulang->assertStatus(303);
        $ulang->assertRedirectTo(site_url('panel/akun-siswa') . '?kelas=' . $this->rombel7a['id']);
        $this->assertSame(self::TOKEN_TERPAKAI, session('galat'));
        $this->assertSame($hash, $this->akun($belum1['akun_id'])['password_hash']);
        $this->seeNumRecords(1, 'log_aktivitas', ['jenis' => 'slip_dicetak']);
    }

    public function testSlipForASubsetLeavesTheOthersUntouched(): void
    {
        // FS-AKN-05 B1: the user may narrow the choice, e.g. new students only.
        $belum1 = $this->siswa('Bela Kusuma');
        $belum2 = $this->siswa('Dodi Setiawan');

        $result = $this->buatSlip($this->admin, $this->rombel7a, [$belum2['id']]);

        $this->assertCount(1, $this->passwords($result));
        $this->assertNull($this->akun($belum1['akun_id'])['password_hash']);
        $this->assertNotNull($this->akun($belum2['akun_id'])['password_hash']);
    }

    public function testAllActiveShowsMessageAndNoSlip(): void
    {
        // E1
        $aktif = $this->siswa('Andi Pratama', 'aktif');

        $daftar = $this->kirim($this->wali7a, 'GET', 'panel/akun-siswa?kelas=' . $this->rombel7a['id']);
        $daftar->assertSee('Semua akun di kelas ini sudah aktif.');
        $daftar->assertDontSee('Buat slip akun');

        $form = $this->kirim($this->wali7a, 'GET', $this->alamatFormSlip($this->rombel7a, [$aktif['id']]));
        $form->assertStatus(303);
        $form->assertRedirectTo(site_url('panel/akun-siswa') . '?kelas=' . $this->rombel7a['id']);
        $this->assertSame('Semua akun di kelas ini sudah aktif.', session('galat'));
    }

    public function testNoStudentChosenAsksToChoose(): void
    {
        $this->siswa('Bela Kusuma');

        $this->kirim($this->wali7a, 'GET', 'panel/akun-siswa/slip?kelas=' . $this->rombel7a['id'])->assertStatus(303);
        $this->assertSame('Pilih siswa yang akan dibuatkan slip akun.', session('galat'));
    }

    public function testReprintMakesOldSlipInvalid(): void
    {
        // AC-AKN-05-03
        $belum = $this->siswa('Bela Kusuma');

        [$lama] = $this->passwords($this->buatSlip($this->wali7a, $this->rombel7a, [$belum['id']]));
        [$baru] = $this->passwords($this->buatSlip($this->wali7a, $this->rombel7a, [$belum['id']]));

        $hash = $this->akun($belum['akun_id'])['password_hash'];
        $this->assertNotSame($lama, $baru);
        $this->assertFalse((new Kredensial())->cocok($lama, $hash));
        $this->assertTrue((new Kredensial())->cocok($baru, $hash));
    }

    public function testWaliKelasResetsOwnStudentOnce(): void
    {
        // AC-AKN-05-04, FS-AKN-05 C
        $siswa = $this->siswa('Cici Lestari', 'aktif');

        $form = $this->kirim($this->wali7a, 'GET', "panel/siswa/{$siswa['id']}/reset-password");
        $form->assertOK();
        $form->assertSee('Reset password Cici Lestari?');

        $isian  = ['token_sekali' => $this->tokenDari($form)];
        $result = $this->kirim($this->wali7a, 'POST', "panel/siswa/{$siswa['id']}/reset-password", $isian, ['token_sekali' => $_SESSION['token_sekali']]);
        $sesi   = $_SESSION;

        $result->assertOK();
        $this->assertStringContainsString('no-store', $result->response()->getHeaderLine('Cache-Control'));
        $passwords = $this->passwords($result);
        $this->assertCount(1, $passwords);
        $this->assertMatchesRegularExpression(self::POLA_PASSWORD, $passwords[0]);
        $result->assertSee($siswa['nisn']);
        $result->assertSee('7A');

        $akun = $this->akun($siswa['akun_id']);
        $this->assertSame('aktif', $akun['status']);
        $this->assertSame('1', (string) $akun['wajib_ganti_password']);
        $this->assertTrue((new Kredensial())->cocok($passwords[0], $akun['password_hash']));
        $this->seeInDatabase('log_aktivitas', ['jenis' => 'password_direset', 'pelaku_id' => $this->wali7a['id'], 'akun_id' => $siswa['akun_id'], 'rombel_id' => $this->rombel7a['id']]);
        $this->assertStringNotContainsString($passwords[0], serialize($sesi));

        $ulang = $this->kirim($this->wali7a, 'POST', "panel/siswa/{$siswa['id']}/reset-password", $isian, ['token_sekali' => $sesi['token_sekali']]);
        $ulang->assertStatus(303);
        $ulang->assertRedirectTo(site_url("panel/siswa/{$siswa['id']}"));
        $this->assertSame(self::TOKEN_TERPAKAI, session('galat'));
        $this->assertSame($akun['password_hash'], $this->akun($siswa['akun_id'])['password_hash']);
    }

    public function testResetRefusedForInactiveAccount(): void
    {
        // E2
        $siswa = $this->siswa('Dodi Setiawan', 'aktif');
        $pesan = 'Akun Dodi Setiawan nonaktif, sehingga password tidak dapat direset.';

        // The account turns inactive while the confirmation is open.
        $form  = $this->kirim($this->admin, 'GET', "panel/siswa/{$siswa['id']}/reset-password");
        $token = $this->tokenDari($form);
        $this->db->table('akun')->where('id', $siswa['akun_id'])->update(['status' => 'nonaktif']);
        $hash = $this->akun($siswa['akun_id'])['password_hash'];

        $result = $this->kirim($this->admin, 'POST', "panel/siswa/{$siswa['id']}/reset-password", ['token_sekali' => $token], ['token_sekali' => $_SESSION['token_sekali']]);
        $result->assertRedirectTo(site_url("panel/siswa/{$siswa['id']}"));
        $this->assertSame($pesan, session('galat'));
        $this->assertSame($hash, $this->akun($siswa['akun_id'])['password_hash']);
        $this->dontSeeInDatabase('log_aktivitas', ['jenis' => 'password_direset']);

        $this->kirim($this->admin, 'GET', "panel/siswa/{$siswa['id']}/reset-password")->assertStatus(303);
        $this->assertSame($pesan, session('galat'));
    }

    public function testOutOfScopeRequestsAreRefused(): void
    {
        // AC-AKN-05-05
        $siswa7b = $this->buatSiswa(['nama' => 'Siswa 7B', 'rombel_id' => $this->rombel7b['id']]);

        $this->ditolak($this->wali7a, 'GET', $this->alamatFormSlip($this->rombel7b, [$siswa7b['id']]));
        $this->ditolak($this->wali7a, 'POST', 'panel/akun-siswa/slip', ['kelas' => $this->rombel7b['id'], 'siswa' => [$siswa7b['id']]]);
        $this->ditolak($this->wali7a, 'GET', "panel/siswa/{$siswa7b['id']}/reset-password");
        $this->ditolak($this->wali7a, 'POST', "panel/siswa/{$siswa7b['id']}/reset-password");
        $this->ditolak($this->wali7a, 'POST', "panel/siswa/{$siswa7b['id']}/buka-kunci");

        $this->assertNull($this->akun($siswa7b['akun_id'])['password_hash']);
        $this->dontSeeInDatabase('log_aktivitas', ['pelaku_id' => $this->wali7a['id']]);
    }

    public function testStaffWithoutTheRightGets403(): void
    {
        $piket = $this->buatAkun(['username' => 'piket'], ['guru_piket']);

        $this->ditolak($piket, 'GET', 'panel/akun-siswa');
    }

    public function testWaliKelasUnlocksOwnStudent(): void
    {
        // FS-AKN-05 D, SEC-11
        $siswa     = $this->siswa('Bela Kusuma', 'aktif');
        $percobaan = new PercobaanLogin();
        $hash      = $percobaan->identitasHash($siswa['nisn']);
        $rows      = [];
        for ($i = 0; $i < 5; $i++) {
            $rows[] = ['identitas_hash' => $hash, 'ip' => '10.0.0.' . $i, 'created_at' => '2026-10-13 06:5' . $i . ':00'];
        }
        $this->db->table('percobaan_login')->insertBatch($rows);
        $this->assertNotNull($percobaan->keadaanAkun($siswa['nisn']));
        $passwordHash = $this->akun($siswa['akun_id'])['password_hash'];

        $result = $this->kirim($this->wali7a, 'POST', "panel/siswa/{$siswa['id']}/buka-kunci");

        $result->assertStatus(303);
        $result->assertRedirectTo(site_url("panel/siswa/{$siswa['id']}"));
        $this->assertSame('Kunci login Bela Kusuma sudah dibuka.', session('sukses'));
        $this->assertNull($percobaan->keadaanAkun($siswa['nisn']));
        $this->assertSame($passwordHash, $this->akun($siswa['akun_id'])['password_hash']);
        $this->seeInDatabase('log_aktivitas', ['jenis' => 'kunci_login_dibuka', 'pelaku_id' => $this->wali7a['id'], 'akun_id' => $siswa['akun_id'], 'rombel_id' => $this->rombel7a['id']]);
    }

    /**
     * A student of 7A with an account in the given state; active accounts get
     * the test password. Returns the `siswa` row plus `akun_id`.
     *
     * @param array<string, mixed> $akun Extra `akun` columns
     *
     * @return array<string, mixed>
     */
    private function siswa(string $nama, string $status = 'belum_aktif', array $akun = []): array
    {
        $siswa = $this->buatSiswa(['nama' => $nama, 'rombel_id' => $this->rombel7a['id']]);

        if ($status !== 'belum_aktif') {
            $akun += ['status' => $status, 'wajib_ganti_password' => 0, 'password_hash' => (new Kredensial())->hash($this->passwordUji)];
        }
        if ($akun !== []) {
            $this->db->table('akun')->where('id', $siswa['akun_id'])->update($akun);
        }

        return $siswa + ['akun_hash' => $akun['password_hash'] ?? null];
    }

    /**
     * Confirmation then slip, as the given account.
     *
     * @param array<string, mixed> $pelaku
     * @param array<string, mixed> $rombel
     * @param list<int|string>     $siswaIds
     */
    private function buatSlip(array $pelaku, array $rombel, array $siswaIds): TestResponse
    {
        $token = $this->tokenDari($this->kirim($pelaku, 'GET', $this->alamatFormSlip($rombel, $siswaIds)));

        return $this->kirim($pelaku, 'POST', 'panel/akun-siswa/slip', ['token_sekali' => $token, 'kelas' => $rombel['id'], 'siswa' => $siswaIds], ['token_sekali' => $_SESSION['token_sekali']]);
    }

    /**
     * @param array<string, mixed> $rombel
     * @param list<int|string>     $siswaIds
     */
    private function alamatFormSlip(array $rombel, array $siswaIds): string
    {
        return 'panel/akun-siswa/slip?' . http_build_query(['kelas' => $rombel['id'], 'siswa' => $siswaIds]);
    }

    /**
     * One request as the given account, with a valid CSRF token.
     *
     * @param array<string, mixed> $akun
     * @param array<string, mixed> $isian
     * @param array<string, mixed> $sesi  Extra session data
     */
    private function kirim(array $akun, string $method, string $uri, array $isian = [], array $sesi = []): TestResponse
    {
        // The shared router keeps the method of the last request.
        foreach (['router', 'response'] as $service) {
            Services::resetSingle($service);
        }

        return $this->withSession($sesi + $this->sesiAkun($akun))
            ->withHeaders(['X-CSRF-TOKEN' => service('security')->getHash()])
            ->call($method, $uri, $isian);
    }

    /**
     * Asserts the generic 403 (docs/04 §4.1 item 3): the `hak` filter answers a
     * background request with JSON; a scope check in the controller throws
     * DiLuarHak, which the exception handler renders as the 403 page.
     *
     * @param array<string, mixed> $akun
     * @param array<string, mixed> $isian
     */
    private function ditolak(array $akun, string $method, string $uri, array $isian = []): void
    {
        foreach (['router', 'response'] as $service) {
            Services::resetSingle($service);
        }

        try {
            $result = $this->withSession($this->sesiAkun($akun))
                ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json', 'X-CSRF-TOKEN' => service('security')->getHash()])
                ->call($method, $uri, $isian);
        } catch (DiLuarHak) {
            $this->addToAssertionCount(1);

            return;
        }

        $result->assertStatus(403);
        $this->assertSame('ditolak', json_decode($result->getJSON(), true)['kode'], "{$method} {$uri}");
    }

    private function tokenDari(TestResponse $page): string
    {
        preg_match('/name="token_sekali" value="([0-9a-f]{32})"/', $page->response()->getBody(), $m);

        return $m[1];
    }

    /**
     * Passwords on a slip page, in slip order, without the display space (UI-59).
     *
     * @return list<string>
     */
    private function passwords(TestResponse $result): array
    {
        preg_match_all('#<div class="slip-password"><span>([a-z0-9]{4})</span><span>([a-z0-9]{4})</span></div>#', $result->response()->getBody(), $m, PREG_SET_ORDER);

        return array_map(static fn (array $match): string => $match[1] . $match[2], $m);
    }

    /**
     * @return array<string, mixed>
     */
    private function akun(int|string $id): array
    {
        return $this->db->table('akun')->where('id', $id)->get()->getRowArray();
    }
}
