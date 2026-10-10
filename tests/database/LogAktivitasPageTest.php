<?php

use App\Services\Akun\DiLuarHak;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;
use Tests\Support\AkunTrait;

/**
 * Activity log page (docs/09 HAL-AKN-09, docs/12 SEC-62).
 *
 * @internal
 */
final class LogAktivitasPageTest extends CIUnitTestCase
{
    use AkunTrait;
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $refresh   = true;
    protected $namespace = 'App';

    /** @var array<string, mixed> */
    private array $admin;

    protected function setUp(): void
    {
        Services::resetSingle('routes');
        Services::resetSingle('router');
        Services::resetSingle('request');
        Services::resetSingle('response');
        parent::setUp();
        Time::setTestNow('2026-10-13 09:00:00', 'Asia/Jakarta');
        $this->admin = $this->buatAkun(['nama' => 'Bu Admin', 'username' => 'admin1'], ['admin']);
    }

    protected function tearDown(): void
    {
        Time::setTestNow();
        parent::tearDown();
    }

    private function log(string $jenis, string $at, ?int $pelaku = null, ?int $akun = null, ?array $data = null, ?string $ip = null): int
    {
        $this->db->table('log_aktivitas')->insert([
            'jenis' => $jenis, 'pelaku_id' => $pelaku, 'akun_id' => $akun,
            'data'  => $data === null ? null : json_encode($data), 'ip' => $ip, 'created_at' => $at,
        ]);

        return (int) $this->db->insertID();
    }

    private function buka(string $uri): CodeIgniter\Test\TestResponse
    {
        return $this->withSession($this->sesiAkun($this->admin))->get($uri);
    }

    public function testStaffWithoutAdminIsRefused(): void
    {
        $staf = $this->buatAkun([], ['pimpinan']);
        $id   = $this->log('login_gagal', '2026-10-13 08:00:00');

        $ajax = ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'];
        $this->withSession($this->sesiAkun($staf))->withHeaders($ajax)->get("panel/log-aktivitas/{$id}")->assertStatus(403);

        // Pages throw DiLuarHak, which the exception handler renders as the 403 page.
        $this->expectException(DiLuarHak::class);
        $this->withHeaders([])->withSession($this->sesiAkun($staf))->get('panel/log-aktivitas');
    }

    public function testListFiltersByKindAndInclusiveDateRange(): void
    {
        $this->log('pengaturan_diubah', '2026-10-09 23:59:59', (int) $this->admin['id'], null, ['kunci' => 'awal']);
        $this->log('pengaturan_diubah', '2026-10-10 00:00:00', (int) $this->admin['id'], null, ['kunci' => 'dalam1']);
        $this->log('pengaturan_diubah', '2026-10-11 23:59:59', (int) $this->admin['id'], null, ['kunci' => 'dalam2']);
        $this->log('pengaturan_diubah', '2026-10-12 00:00:00', (int) $this->admin['id'], null, ['kunci' => 'akhir']);
        $this->log('role_diubah', '2026-10-10 10:00:00', (int) $this->admin['id'], null, ['kunci' => 'lain']);

        $result = $this->buka('panel/log-aktivitas?jenis=pengaturan_diubah&mulai=2026-10-10&selesai=2026-10-11');

        $result->assertOK();
        $result->assertSee('Kunci: dalam1');
        $result->assertSee('Kunci: dalam2');
        $result->assertDontSee('Kunci: awal');
        $result->assertDontSee('Kunci: akhir');
        $result->assertDontSee('Kunci: lain');
        $result->assertSee('Pengaturan diubah');
        $result->assertSee('Bu Admin');
    }

    public function testQuickFilterLogin(): void
    {
        $this->log('login_gagal', '2026-10-13 07:00:00', null, null, ['alasan' => 'password_salah']);
        $this->log('login_dikunci', '2026-10-13 07:01:00', null, null, ['jenis' => 'pendek', 'sampai' => '2026-10-13 07:16:00']);
        $this->log('lampiran_dibuka', '2026-10-13 07:02:00', (int) $this->admin['id'], null, ['lampiran_id' => 4321]);

        $result = $this->buka('panel/log-aktivitas?cepat=login');

        $result->assertOK();
        $result->assertSee('Alasan: Password salah');
        $result->assertSee('Jenis kunci: 15 menit');
        $result->assertSee('Berlaku sampai: 13 Okt 2026, 07.16');
        $result->assertDontSee('ID lampiran');
    }

    public function testInvalidAndEmptyFiltersAreDroppedWithANote(): void
    {
        $result = $this->buka('panel/log-aktivitas?jenis=xyz&mulai=2026-10-10&selesai=&pelaku=');

        $result->assertStatus(303);
        $result->assertRedirectTo(site_url('panel/log-aktivitas') . '?mulai=2026-10-10');
        $this->assertStringContainsString('Jenis', session('galat'));

        $_SESSION = [];
        $result   = $this->buka('panel/log-aktivitas?mulai=2026-10-10&selesai=');
        $result->assertRedirectTo(site_url('panel/log-aktivitas') . '?mulai=2026-10-10');
        $this->assertNull(session('galat'));
    }

    public function testPagination(): void
    {
        for ($i = 0; $i < 51; $i++) {
            $this->log('login_gagal', sprintf('2026-10-13 06:%02d:00', $i), null, null, ['alasan' => $i === 0 ? 'akun_nonaktif' : 'password_salah']);
        }

        $first = $this->buka('panel/log-aktivitas');
        $first->assertSee('Menampilkan 1–50 dari 51 data');
        $first->assertSee('rel="next"');
        $first->assertDontSee('Akun nonaktif');

        $second = $this->buka('panel/log-aktivitas?page=2');
        $second->assertSee('Menampilkan 51–51 dari 51 data');
        $second->assertSee('Alasan: Akun nonaktif');
    }

    public function testDetailShowsLabelledDataAndSystemActor(): void
    {
        $staf = $this->buatAkun(['nama' => 'Pak Guru', 'username' => 'guru1']);
        $id   = $this->log('password_diganti', '2026-10-13 08:30:05', null, (int) $staf['id'], ['wajib' => true, 'role' => 'guru_bk', 'tidak_dikenal' => 'abc'], '10.0.0.7');

        $result = $this->buka("panel/log-aktivitas/{$id}");

        $result->assertOK();
        $result->assertSee('Password diganti');
        $result->assertSee('Sistem');
        $result->assertSee('Pak Guru');
        $result->assertSee('Penggantian wajib');
        $result->assertSee('Ya');
        $result->assertSee('Guru BK');
        $result->assertSee('Tidak dikenal');
        $result->assertSee('10.0.0.7');
        $result->assertSee('13 Okt 2026, 08.30.05');
        $result->assertDontSee('{&quot;wajib');
    }

    public function testUnknownIdIs404(): void
    {
        $this->buka('panel/log-aktivitas/999999')->assertStatus(404);
    }
}
