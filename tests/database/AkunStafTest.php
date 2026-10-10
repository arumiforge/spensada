<?php

use App\Services\Akun\AkunStaf;
use App\Services\Akun\Kredensial;
use App\Services\Akun\Peran;
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
 * Staff accounts (docs/04 FS-AKN-03, AC-AKN-03-*, docs/09 HAL-AKN-04,
 * RT-09, RT-12, docs/12 SEC-11, SEC-27).
 *
 * @internal
 */
final class AkunStafTest extends CIUnitTestCase
{
    use AkunTrait;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use MasterDataTrait;

    protected $refresh   = true;
    protected $namespace = 'App';

    private const TOKEN_TERPAKAI = 'Tindakan ini sudah dijalankan. Password baru tidak dibuat lagi. Bila password belum tercatat, reset sekali lagi.';

    /** @var array<string, mixed> */
    private array $admin;

    protected function setUp(): void
    {
        Services::resetSingle('routes');
        Services::resetSingle('router');
        Services::resetSingle('request');
        Services::resetSingle('response');
        parent::setUp();
        Time::setTestNow('2026-10-13 07:00:00', 'Asia/Jakarta');
        $this->admin = $this->buatAkun(['username' => 'admin.tu', 'nama' => 'Admin Tata Usaha'], ['admin']);
    }

    protected function tearDown(): void
    {
        Time::setTestNow();
        parent::tearDown();
    }

    public function testHomeroomClassesShowOnListDetailAndDeactivation(): void
    {
        // FS-AKN-03 items 3 and 9: only rombel of the active school year count.
        $rina = $this->buatAkun(['username' => 'rina', 'nama' => 'Rina'], ['guru_piket']);
        $lama = $this->buatTahunAjaran(['nama' => '2025/2026', 'tanggal_mulai' => '2025-07-14', 'tanggal_selesai' => '2026-06-30', 'aktif' => 0]);
        $ta   = $this->buatTahunAjaran();
        $this->buatRombel(['tahun_ajaran_id' => $ta['id'], 'nama' => '7B', 'wali_kelas_id' => $rina['id']]);
        $this->buatRombel(['tahun_ajaran_id' => $lama['id'], 'nama' => '9C', 'wali_kelas_id' => $rina['id']]);

        $list = $this->kirim('GET', 'panel/akun-staf');
        $list->assertSee('Kelas yang diampu');
        $list->assertSee('7B');
        $list->assertDontSee('9C');

        $this->kirim('GET', "panel/akun-staf/{$rina['id']}")->assertSee('Wali kelas');
        $this->kirim('GET', "panel/akun-staf/{$rina['id']}/nonaktifkan")->assertSee('Rina adalah wali kelas 7B. Penugasannya tetap.');
    }

    public function testNonAdminStaffGets403(): void
    {
        $piket = $this->buatAkun(['username' => 'piket'], ['guru_piket']);

        $result = $this->withSession($this->sesiAkun($piket))
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
            ->get('panel/akun-staf');

        $result->assertStatus(403);
        $this->assertSame('ditolak', json_decode($result->getJSON(), true)['kode']);
    }

    public function testListShowsAccountsAndFilters(): void
    {
        $this->buatAkun(['username' => 'rina.w', 'nama' => 'Rina Wulandari'], ['guru_piket']);
        $this->buatAkun(['username' => 'andi.bk', 'nama' => 'Andi Saputra', 'status' => 'nonaktif', 'login_terakhir_at' => '2026-10-12 07:32:00'], ['guru_bk']);

        $all = $this->kirim('GET', 'panel/akun-staf');
        $all->assertOK();
        $all->assertSee('Rina Wulandari');
        $all->assertSee('Staf, Guru piket');
        $all->assertSee('12 Okt 2026, 07.32');
        $all->assertSee('Belum pernah');

        $bk = $this->kirim('GET', 'panel/akun-staf?role=guru_bk&status=nonaktif');
        $bk->assertSee('Andi Saputra');
        $bk->assertDontSee('Rina Wulandari');

        $cari = $this->kirim('GET', 'panel/akun-staf?cari=rina');
        $cari->assertSee('Rina Wulandari');
        $cari->assertDontSee('Andi Saputra');

        $salah = $this->kirim('GET', 'panel/akun-staf?status=hilang');
        $salah->assertSee('Sebagian saringan tidak dikenali');
        $salah->assertSee('Andi Saputra');
    }

    public function testAddGuruPiketShowsPasswordOnce(): void
    {
        // AC-AKN-03-01
        $token  = $this->token('panel/akun-staf/tambah');
        $isian  = ['token_sekali' => $token, 'nama' => '  Rina   Wulandari ', 'username' => ' Rina.W ', 'role' => ['guru_piket']];
        $result = $this->kirim('POST', 'panel/akun-staf', $isian, ['token_sekali' => $_SESSION['token_sekali']]);
        $sesi   = $_SESSION;

        $result->assertOK();
        $this->assertStringContainsString('no-store', $result->response()->getHeaderLine('Cache-Control'));
        $result->assertSee('hanya tampil sekali');
        $password = $this->password($result);
        $this->assertSame(12, strlen($password));

        $akun = $this->db->table('akun')->where('username', 'rina.w')->get()->getRowArray();
        $this->assertSame('Rina Wulandari', $akun['nama']);
        $this->assertSame('aktif', $akun['status']);
        $this->assertSame('1', (string) $akun['wajib_ganti_password']);
        $this->assertTrue((new Kredensial())->cocok($password, $akun['password_hash']));
        $this->assertSame(['staf', 'guru_piket'], (new Peran())->untuk($akun));
        $this->seeInDatabase('akun_role', ['akun_id' => $akun['id'], 'role' => 'guru_piket', 'diberikan_oleh' => $this->admin['id']]);
        $this->seeInDatabase('log_aktivitas', ['jenis' => 'akun_dibuat', 'pelaku_id' => $this->admin['id'], 'akun_id' => $akun['id'], 'ip' => '0.0.0.0']);
        $this->assertStringNotContainsString($password, (string) $this->db->table('log_aktivitas')->get()->getRow()->data);
        $this->assertStringNotContainsString($password, serialize($sesi));

        // Reload sends the same token again: no second account, no new password (RT-09).
        $ulang = $this->kirim('POST', 'panel/akun-staf', $isian, ['token_sekali' => $sesi['token_sekali']]);
        $ulang->assertStatus(303);
        $ulang->assertRedirectTo('https://example.com/panel/akun-staf');
        $this->assertSame(self::TOKEN_TERPAKAI, session('galat'));
        $this->seeNumRecords(1, 'akun', ['username' => 'rina.w']);
    }

    public function testUsernameRejectedWithAllFieldErrors(): void
    {
        // AC-AKN-03-02
        $this->buatAkun(['username' => 'rina.w']);

        foreach (['rina.w' => 'Username sudah dipakai.', '1987654321' => 'Username diawali huruf, dan hanya boleh berisi huruf kecil, angka, titik, dan garis bawah.'] as $username => $pesan) {
            $token  = $this->token('panel/akun-staf/tambah');
            $result = $this->kirim('POST', 'panel/akun-staf', ['token_sekali' => $token, 'nama' => '', 'username' => $username, 'role' => ['bukan_role']], ['token_sekali' => $_SESSION['token_sekali']]);

            $result->assertStatus(303);
            $result->assertRedirectTo('https://example.com/panel/akun-staf/tambah');
            $this->assertSame([
                'nama'     => 'Nama lengkap wajib diisi.',
                'username' => $pesan,
                'role'     => 'Role yang dipilih tidak tersedia. Pilih dari daftar.',
            ], session('_ci_validation_errors'));
            $this->assertArrayNotHasKey('token_sekali', session('_ci_old_input')['post']);
        }

        $this->seeNumRecords(2, 'akun', []);
    }

    public function testLastActiveAdminKeepsAdminRole(): void
    {
        // AC-AKN-03-03
        $result = $this->ubah($this->admin, ['admin.tu', 'Admin Tata Usaha', []]);

        $result->assertRedirectTo('https://example.com/panel/akun-staf/' . $this->admin['id'] . '/ubah');
        $this->assertSame('Harus ada minimal satu admin aktif.', session('galat'));
        $this->seeInDatabase('akun_role', ['akun_id' => $this->admin['id'], 'role' => 'admin']);
    }

    public function testAdminCannotDropOwnAdminRole(): void
    {
        $this->buatAkun(['username' => 'admin.dua'], ['admin']);

        $this->ubah($this->admin, ['admin.tu', 'Admin Tata Usaha', ['pimpinan']]);

        $this->assertSame('Anda tidak dapat mencabut role Admin dari akun sendiri.', session('galat'));
        $this->seeInDatabase('akun_role', ['akun_id' => $this->admin['id'], 'role' => 'admin']);
        $this->dontSeeInDatabase('akun_role', ['akun_id' => $this->admin['id'], 'role' => 'pimpinan']);
    }

    public function testEditSavesWithVersionAndLogsChanges(): void
    {
        $andi = $this->buatAkun(['username' => 'andi', 'nama' => 'Andi'], ['guru_bk']);
        $form = $this->kirim('GET', "panel/akun-staf/{$andi['id']}/ubah");
        $this->assertStringContainsString('name="versi" value="' . $andi['updated_at'] . '"', html_entity_decode($form->response()->getBody()));
        $this->assertStringContainsString('name="_method" value="PATCH"', html_entity_decode($form->response()->getBody()));

        Time::setTestNow('2026-10-13 07:05:00', 'Asia/Jakarta');
        $result = $this->ubah($andi, ['andi.s', 'Andi Saputra', ['guru_piket', 'pimpinan']]);

        $result->assertStatus(303);
        $result->assertRedirectTo("https://example.com/panel/akun-staf/{$andi['id']}");
        $baru = $this->db->table('akun')->where('id', $andi['id'])->get()->getRowArray();
        $this->assertSame(['andi.s', 'Andi Saputra', '2026-10-13 07:05:00'], [$baru['username'], $baru['nama'], $baru['updated_at']]);
        $this->assertSame(['guru_piket', 'pimpinan'], model('AkunRoleModel')->rolesOf((int) $andi['id']));
        $this->assertEquals(['nama' => ['lama' => 'Andi', 'baru' => 'Andi Saputra'], 'username' => ['lama' => 'andi', 'baru' => 'andi.s']], $this->logData('akun_diubah'));
        $this->assertEquals(['ditambah' => ['guru_piket', 'pimpinan'], 'dicabut' => ['guru_bk']], $this->logData('role_diubah'));
    }

    public function testVersionConflictShowsWhoChangedIt(): void
    {
        $andi = $this->buatAkun(['username' => 'andi', 'nama' => 'Andi'], ['guru_bk']);
        $lain = $this->buatAkun(['username' => 'admin.dua', 'nama' => 'Rina Wulandari'], ['admin']);

        // Another admin saved first.
        Time::setTestNow('2026-10-13 07:40:00', 'Asia/Jakarta');
        $this->assertSame(['ok' => true], (new AkunStaf())->perbarui((int) $andi['id'], $andi['updated_at'], 'Andi Baru', 'andi', ['guru_bk'], (int) $lain['id'], null));

        $result = $this->ubah($andi, ['andi', 'Andi Lama', []]);

        $result->assertRedirectTo("https://example.com/panel/akun-staf/{$andi['id']}/ubah");
        $this->assertSame('Data ini sudah diubah oleh Rina Wulandari pukul 07.40. Periksa data terbaru, lalu simpan lagi bila perlu.', session('galat'));
        $this->assertNull(session('_ci_old_input'));
        $this->seeInDatabase('akun', ['id' => $andi['id'], 'nama' => 'Andi Baru']);
        $this->seeInDatabase('akun_role', ['akun_id' => $andi['id'], 'role' => 'guru_bk']);
    }

    public function testDeactivateEndsSessionAndActivateRestores(): void
    {
        // AC-AKN-03-04
        $andi = $this->buatAkun(['username' => 'andi', 'nama' => 'Andi'], ['guru_bk']);

        $this->kirim('GET', "panel/akun-staf/{$andi['id']}/nonaktifkan")->assertSee('sesi yang sedang berjalan langsung berakhir');
        $result = $this->kirim('POST', "panel/akun-staf/{$andi['id']}/nonaktifkan");

        $result->assertStatus(303);
        $result->assertRedirectTo("https://example.com/panel/akun-staf/{$andi['id']}");
        $this->assertSame('Akun Andi sudah dinonaktifkan.', session('sukses'));
        $this->seeInDatabase('akun', ['id' => $andi['id'], 'status' => 'nonaktif']);
        $this->seeInDatabase('log_aktivitas', ['jenis' => 'akun_dinonaktifkan', 'pelaku_id' => $this->admin['id'], 'akun_id' => $andi['id']]);

        // Andi's running session ends on the next request.
        $this->resetRouter();
        $this->withSession($this->sesiAkun($andi))->get('panel')->assertRedirectTo('https://example.com/login');

        $this->kirim('POST', "panel/akun-staf/{$andi['id']}/aktifkan")->assertStatus(303);
        $this->assertSame('Akun Andi sudah aktif kembali.', session('sukses'));
        $this->seeInDatabase('akun', ['id' => $andi['id'], 'status' => 'aktif']);
        $this->seeInDatabase('log_aktivitas', ['jenis' => 'akun_diaktifkan', 'akun_id' => $andi['id']]);
    }

    public function testAdminCannotDeactivateOwnAccount(): void
    {
        $this->kirim('GET', "panel/akun-staf/{$this->admin['id']}")->assertDontSee('/nonaktifkan');

        $this->kirim('POST', "panel/akun-staf/{$this->admin['id']}/nonaktifkan");

        $this->assertSame('Anda tidak dapat menonaktifkan akun sendiri.', session('galat'));
        $this->seeInDatabase('akun', ['id' => $this->admin['id'], 'status' => 'aktif']);
        $this->dontSeeInDatabase('log_aktivitas', ['jenis' => 'akun_dinonaktifkan']);
    }

    public function testLastActiveAdminCannotBeDeactivated(): void
    {
        $piket = $this->buatAkun(['username' => 'piket'], ['guru_piket']);

        $hasil = (new AkunStaf())->nonaktifkan((int) $this->admin['id'], (int) $piket['id'], null);

        $this->assertSame(['pesan' => 'Harus ada minimal satu admin aktif.'], $hasil);
        $this->seeInDatabase('akun', ['id' => $this->admin['id'], 'status' => 'aktif']);
    }

    public function testResetPasswordShowsNewPasswordOnce(): void
    {
        // AC-AKN-03-05
        $andi  = $this->buatAkun(['username' => 'andi', 'nama' => 'Andi'], ['guru_bk']);
        $token = $this->token("panel/akun-staf/{$andi['id']}/reset-password");

        $result = $this->kirim('POST', "panel/akun-staf/{$andi['id']}/reset-password", ['token_sekali' => $token], ['token_sekali' => $_SESSION['token_sekali']]);
        $sesi   = $_SESSION;

        $result->assertOK();
        $this->assertStringContainsString('no-store', $result->response()->getHeaderLine('Cache-Control'));
        $password = $this->password($result);
        $akun     = $this->db->table('akun')->where('id', $andi['id'])->get()->getRowArray();
        $this->assertTrue((new Kredensial())->cocok($password, $akun['password_hash']));
        $this->assertFalse((new Kredensial())->cocok($this->passwordUji, $akun['password_hash']));
        $this->assertSame('1', (string) $akun['wajib_ganti_password']);
        $this->seeInDatabase('log_aktivitas', ['jenis' => 'password_direset', 'pelaku_id' => $this->admin['id'], 'akun_id' => $andi['id']]);

        // Andi's old session ends: the credential stamp changed.
        $this->resetRouter();
        $this->withSession($this->sesiAkun($andi))->get('panel')->assertRedirectTo('https://example.com/login');

        $ulang = $this->kirim('POST', "panel/akun-staf/{$andi['id']}/reset-password", ['token_sekali' => $token], ['token_sekali' => $sesi['token_sekali']]);
        $ulang->assertRedirectTo("https://example.com/panel/akun-staf/{$andi['id']}");
        $this->assertSame(self::TOKEN_TERPAKAI, session('galat'));
        $this->seeNumRecords(1, 'log_aktivitas', ['jenis' => 'password_direset']);
    }

    public function testUnlockClearsFailuresAndLogs(): void
    {
        $andi      = $this->buatAkun(['username' => 'andi', 'nama' => 'Andi'], ['guru_bk']);
        $percobaan = new PercobaanLogin();
        $hash      = $percobaan->identitasHash('andi');

        for ($i = 0; $i < 5; $i++) {
            $percobaan->catatGagal($hash, '10.0.0.9');
        }

        $this->kirim('GET', "panel/akun-staf/{$andi['id']}")->assertSee('Buka kunci login');

        $result = $this->kirim('POST', "panel/akun-staf/{$andi['id']}/buka-kunci");

        $result->assertRedirectTo("https://example.com/panel/akun-staf/{$andi['id']}");
        $this->assertSame('Kunci login Andi sudah dibuka.', session('sukses'));
        $this->assertNull($percobaan->keadaanAkun('andi'));
        $this->seeInDatabase('log_aktivitas', ['jenis' => 'kunci_login_dibuka', 'pelaku_id' => $this->admin['id'], 'akun_id' => $andi['id']]);
        $this->kirim('GET', "panel/akun-staf/{$andi['id']}")->assertDontSee('Buka kunci login');
    }

    public function testUnknownIdIs404(): void
    {
        $siswa = $this->buatAkun(['jenis' => 'siswa', 'username' => '0012345678']);

        $this->kirim('GET', "panel/akun-staf/{$siswa['id']}")->assertStatus(404);
    }

    /**
     * One request as the admin, with a valid CSRF token.
     *
     * @param array<string, mixed> $isian
     * @param array<string, mixed> $sesi  Extra session data
     */
    private function kirim(string $method, string $uri, array $isian = [], array $sesi = []): TestResponse
    {
        $this->resetRouter();

        return $this->withSession($sesi + $this->sesiAkun($this->admin))
            ->withHeaders(['X-CSRF-TOKEN' => service('security')->getHash()])
            ->call($method, $uri, $isian);
    }

    /**
     * Opens a form that carries a one-time token and returns the token.
     */
    private function token(string $uri): string
    {
        $page = $this->kirim('GET', $uri);
        preg_match('/name="token_sekali" value="([0-9a-f]{32})"/', $page->response()->getBody(), $m);

        return $m[1];
    }

    /**
     * @param array<string, mixed> $akun
     * @param array{string, string, list<string>} $isian username, nama, roles
     */
    private function ubah(array $akun, array $isian): TestResponse
    {
        [$username, $nama, $roles] = $isian;

        return $this->kirim('POST', "panel/akun-staf/{$akun['id']}", [
            '_method' => 'PATCH', 'versi' => $akun['updated_at'], 'nama' => $nama, 'username' => $username, 'role' => $roles,
        ]);
    }

    private function password(TestResponse $result): string
    {
        preg_match('/id="password-sekali">([a-z0-9]+)</', $result->response()->getBody(), $m);

        return $m[1];
    }

    private function resetRouter(): void
    {
        // The shared router keeps the method of the last request.
        foreach (['router', 'response'] as $service) {
            Services::resetSingle($service);
        }
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
