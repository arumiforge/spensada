<?php

use App\Services\Akun\DiLuarHak;
use App\Services\MasterData\LogDataSiswa;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;
use Config\Services;
use Tests\Support\AkunTrait;
use Tests\Support\MasterDataTrait;

/**
 * Student data log page (docs/09 HAL-MD-17, docs/04 FS-MD-04 item 8,
 * HA-MD-10: admin, guru BK and pimpinan for all, wali kelas for their rombel).
 *
 * @internal
 */
final class LogDataSiswaPageTest extends CIUnitTestCase
{
    use AkunTrait;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use MasterDataTrait;

    protected $refresh   = true;
    protected $namespace = 'App';

    /** @var array<string, mixed> */
    private array $admin;

    /** @var array<string, mixed> */
    private array $wali;

    /** @var array<string, mixed> */
    private array $siswa;

    /** @var array<string, mixed> */
    private array $siswa7b;

    protected function setUp(): void
    {
        Services::resetSingle('routes');
        Services::resetSingle('router');
        Services::resetSingle('request');
        Services::resetSingle('response');
        parent::setUp();
        Time::setTestNow('2026-10-13 07:00:00', 'Asia/Jakarta');
        $ta            = $this->buatTahunAjaran();
        $this->admin   = $this->buatAkun(['username' => 'admin.tu', 'nama' => 'Admin Tata Usaha'], ['admin']);
        $this->wali    = $this->buatAkun(['username' => 'wali.7a', 'nama' => 'Wali Tujuh A']);
        $rombel7a      = $this->buatRombel(['tahun_ajaran_id' => $ta['id'], 'nama' => '7A', 'wali_kelas_id' => $this->wali['id']]);
        $rombel7b      = $this->buatRombel(['tahun_ajaran_id' => $ta['id'], 'nama' => '7B']);
        $this->siswa   = $this->buatSiswa(['nama' => 'Gita', 'rombel_id' => $rombel7a['id']]);
        $this->siswa7b = $this->buatSiswa(['nama' => 'Galih', 'rombel_id' => $rombel7b['id']]);
    }

    protected function tearDown(): void
    {
        Time::setTestNow();
        service('akunAktif')->clear();
        parent::tearDown();
    }

    public function testShowsEntriesNewestFirstAsScreenText(): void
    {
        $log = new LogDataSiswa();
        $log->catat((int) $this->siswa['id'], 'siswa_dibuat', null, ['nama' => 'Gita', 'kelas' => '7A', 'tanggal_mulai' => '2026-07-13'], (int) $this->admin['id']);
        Time::setTestNow('2026-10-13 09:15:00', 'Asia/Jakarta');
        $log->catat((int) $this->siswa['id'], 'wa_diubah', ['wa_ortu' => null], ['wa_ortu' => '6281234567890'], (int) $this->wali['id']);
        $log->catat((int) $this->siswa['id'], 'dinonaktifkan', null, ['alasan_nonaktif' => 'pindah_sekolah', 'dibatalkan' => false], null, 'Pindah sekolah');

        $page = $this->kirim($this->admin, "panel/siswa/{$this->siswa['id']}/log");
        $page->assertOK();
        $body = $page->response()->getBody();
        $this->assertLessThan(strpos($body, 'Siswa ditambahkan'), strpos($body, 'Nomor WA diubah'));
        $page->assertSee('13 Okt 2026, 09.15');
        $page->assertSee('Wali Tujuh A');
        $page->assertSee('0812-3456-7890');
        $page->assertSee('13 Juli 2026');
        $page->assertSee('Sistem');
        $page->assertSee('Pindah sekolah');
        $page->assertSee('Log data');
        $page->assertDontSee('alasan_nonaktif');

        $kosong = $this->kirim($this->admin, "panel/siswa/{$this->siswa7b['id']}/log");
        $kosong->assertSee('Belum ada perubahan data yang tercatat.');
    }

    public function testAccessFollowsHaMd10(): void
    {
        $bk       = $this->buatAkun(['username' => 'bk'], ['guru_bk']);
        $pimpinan = $this->buatAkun(['username' => 'kepsek'], ['pimpinan']);
        $piket    = $this->buatAkun(['username' => 'piket'], ['guru_piket']);

        foreach ([$this->admin, $bk, $pimpinan, $this->wali] as $akun) {
            $this->kirim($akun, "panel/siswa/{$this->siswa['id']}/log")->assertOK();
        }

        // Out of the wali kelas's rombel: DiLuarHak, the 403 page.
        try {
            $this->kirim($this->wali, "panel/siswa/{$this->siswa7b['id']}/log");
            $this->fail('Another rombel should be refused');
        } catch (DiLuarHak) {
            $this->addToAssertionCount(1);
        }

        // No HA-MD-10: the filter refuses, and the profile has no "Log data" tab.
        $this->kirim($piket, "panel/siswa/{$this->siswa['id']}/log", ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])->assertStatus(403);
        $this->kirim($piket, "panel/siswa/{$this->siswa['id']}")->assertDontSee('Log data');
        $this->kirim($bk, "panel/siswa/{$this->siswa['id']}")->assertSee('Log data');
    }

    /**
     * @param array<string, mixed>  $akun
     * @param array<string, string> $headers
     */
    private function kirim(array $akun, string $uri, array $headers = []): TestResponse
    {
        foreach (['router', 'response', 'request'] as $service) {
            Services::resetSingle($service);
        }
        service('akunAktif')->clear();

        return $this->withSession($this->sesiAkun($akun))->withHeaders($headers)->get($uri);
    }
}
