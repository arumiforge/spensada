<?php

use App\Commands\AdminPertama;
use App\Commands\AdminPulihkan;
use App\Services\Akun\Kredensial;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\StreamFilterTrait;
use Config\Services;
use Tests\Support\AkunTrait;

/**
 * `admin:pertama` and `admin:pulihkan` (docs/07 ARS-49, L01-04).
 *
 * @internal
 */
final class AdminAwalTest extends CIUnitTestCase
{
    use AkunTrait;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use StreamFilterTrait;

    protected $refresh   = true;
    protected $namespace = 'App';

    protected function setUp(): void
    {
        Services::resetSingle('routes');
        Services::resetSingle('router');
        Services::resetSingle('request');
        Services::resetSingle('response');
        parent::setUp();
        Time::setTestNow('2026-10-13 06:30:00', 'Asia/Jakarta');
    }

    public function testPertamaCreatesAdminWithOneTimePassword(): void
    {
        $exit = $this->pertama(['nama' => 'Bu Sari', 'username' => ' Admin.Sari ']);

        $this->assertSame(EXIT_SUCCESS, $exit);
        $akun = $this->db->table('akun')->where('username', 'admin.sari')->get()->getRowArray();
        $this->assertSame(['staf', 'Bu Sari', 'aktif', '1'], [$akun['jenis'], $akun['nama'], $akun['status'], (string) $akun['wajib_ganti_password']]);

        $password = $this->shownPassword();
        $this->assertSame(12, strlen($password));
        $this->assertNotSame($password, $akun['password_hash']);
        $this->assertTrue((new Kredensial())->cocok($password, $akun['password_hash']));
        $this->assertSame(1, substr_count($this->getStreamFilterBuffer(), $password));
        $this->assertStringContainsString('hanya tampil sekali', $this->getStreamFilterBuffer());

        $this->seeInDatabase('akun_role', ['akun_id' => $akun['id'], 'role' => 'admin', 'diberikan_oleh' => null]);
        $log = $this->db->table('log_aktivitas')->get()->getResultArray();
        $this->assertCount(1, $log);
        $this->assertSame(['admin_pertama_dibuat', null, (string) $akun['id']], [$log[0]['jenis'], $log[0]['pelaku_id'], (string) $log[0]['akun_id']]);
        $this->assertSame(['username' => 'admin.sari'], json_decode($log[0]['data'], true));
        $this->assertStringNotContainsString($password, $log[0]['data']);
    }

    public function testPertamaRefusesWhileAnActiveAdminExists(): void
    {
        $this->buatAkun([], ['admin']);

        $this->assertSame(EXIT_ERROR, $this->pertama(['nama' => 'Bu Sari', 'username' => 'sari']));
        $this->assertStringContainsString('Sudah ada admin aktif', $this->getStreamFilterBuffer());
        $this->dontSeeInDatabase('akun', ['username' => 'sari']);
        $this->dontSeeInDatabase('log_aktivitas', ['jenis' => 'admin_pertama_dibuat']);
    }

    public function testPertamaRunsWhenTheOnlyAdminIsInactive(): void
    {
        $this->buatAkun(['status' => 'nonaktif'], ['admin']);

        $this->assertSame(EXIT_SUCCESS, $this->pertama(['nama' => 'Bu Sari', 'username' => 'sari']));
    }

    public function testPertamaRejectsInvalidUsernameAndName(): void
    {
        $this->buatAkun(['username' => 'dipakai']);

        $this->assertSame(EXIT_ERROR, $this->pertama(['nama' => 'Bu Sari', 'username' => '1abc']));
        $this->assertSame(EXIT_ERROR, $this->pertama(['nama' => 'Bu Sari', 'username' => 'dipakai']));
        $this->assertSame(EXIT_ERROR, $this->pertama(['nama' => 'B', 'username' => 'sari']));
        $this->assertStringContainsString('Username diawali huruf', $this->getStreamFilterBuffer());
        $this->assertStringContainsString('Username sudah dipakai.', $this->getStreamFilterBuffer());
        $this->assertStringContainsString('Nama paling sedikit 2 karakter.', $this->getStreamFilterBuffer());
        $this->dontSeeInDatabase('akun_role', ['role' => 'admin']);
    }

    public function testPulihkanSetsNewPasswordAndEndsSessions(): void
    {
        $admin   = $this->buatAkun(['username' => 'admin'], ['admin']);
        $oldSesi = $this->sesiAkun($admin);
        $this->withSession($oldSesi)->get('panel/sistem')->assertOK();

        $this->assertSame(EXIT_SUCCESS, $this->pulihkan(['ADMIN']));

        $akun     = $this->db->table('akun')->where('id', $admin['id'])->get()->getRowArray();
        $password = $this->shownPassword();
        $this->assertSame('1', (string) $akun['wajib_ganti_password']);
        $this->assertTrue((new Kredensial())->cocok($password, $akun['password_hash']));
        $this->assertFalse((new Kredensial())->cocok($this->passwordUji, $akun['password_hash']));
        $this->seeInDatabase('log_aktivitas', ['jenis' => 'admin_dipulihkan', 'pelaku_id' => null, 'akun_id' => $admin['id']]);
        $data = $this->db->table('log_aktivitas')->select('data')->get()->getRow()->data;
        $this->assertSame(['username' => 'admin'], json_decode($data, true));

        // The old credential stamp no longer matches, so the old session ends.
        $this->withSession($oldSesi)->get('panel/sistem')->assertRedirectTo(site_url('login'));
    }

    public function testPulihkanRejectsUnknownNonAdminAndInactiveAccounts(): void
    {
        $this->buatAkun(['username' => 'guru']);
        $this->buatAkun(['username' => 'lama', 'status' => 'nonaktif'], ['admin']);

        foreach (['tidakada', 'guru', 'lama'] as $username) {
            $this->assertSame(EXIT_ERROR, $this->pulihkan([$username]));
            $this->assertStringContainsString("Akun \"{$username}\" bukan akun admin yang aktif", $this->getStreamFilterBuffer());
        }

        $this->dontSeeInDatabase('log_aktivitas', ['jenis' => 'admin_dipulihkan']);
    }

    private function pertama(array $params): int
    {
        return (new AdminPertama(service('logger'), service('commands')))->run($params);
    }

    private function pulihkan(array $params): int
    {
        return (new AdminPulihkan(service('logger'), service('commands')))->run($params);
    }

    private function shownPassword(): string
    {
        // Strip color codes, then read the "Password: ..." line.
        $output = preg_replace('/\e\[[\d;]*m/', '', $this->getStreamFilterBuffer());
        $this->assertSame(1, preg_match('/^Password: (\S+)$/m', $output, $m));

        return $m[1];
    }
}
