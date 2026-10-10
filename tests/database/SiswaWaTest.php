<?php

use App\Services\Akun\DiLuarHak;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;
use Config\Services;
use Tests\Support\AkunTrait;
use Tests\Support\MasterDataTrait;

/**
 * Parent's WA number (docs/04 FS-MD-04 item 3, AC-MD-04-04, E3, E5,
 * docs/09 HAL-MD-08, docs/11 VAL-23).
 *
 * @internal
 */
final class SiswaWaTest extends CIUnitTestCase
{
    use AkunTrait;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use MasterDataTrait;

    protected $refresh   = true;
    protected $namespace = 'App';

    /** @var array<string, mixed> */
    private array $wali;

    /** @var array<string, mixed> */
    private array $g;

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
        $ta         = $this->buatTahunAjaran();
        $this->wali = $this->buatAkun(['username' => 'wali.7a', 'nama' => 'Wali Tujuh A']);
        $rombel7a   = $this->buatRombel(['tahun_ajaran_id' => $ta['id'], 'nama' => '7A', 'wali_kelas_id' => $this->wali['id']]);
        $rombel7b   = $this->buatRombel(['tahun_ajaran_id' => $ta['id'], 'nama' => '7B']);
        $this->g       = $this->buatSiswa(['nama' => 'Gita', 'wa_ortu' => '6281234567890', 'rombel_id' => $rombel7a['id']]);
        $this->siswa7b = $this->buatSiswa(['nama' => 'Galih', 'rombel_id' => $rombel7b['id']]);
    }

    protected function tearDown(): void
    {
        Time::setTestNow();
        service('akunAktif')->clear();
        parent::tearDown();
    }

    public function testWaliKelasChangesWaOfTheirStudentAndItIsLogged(): void
    {
        // AC-MD-04-04, HA-MD-06, docs/08 UI-54
        $form = $this->kirim($this->wali, 'GET', "panel/siswa/{$this->g['id']}/wa/ubah");
        $form->assertOK();
        $form->assertSee('value="0812-3456-7890"', null);
        $form->assertSee('name="_method" value="PUT"', null);

        Time::setTestNow('2026-10-13 09:15:00', 'Asia/Jakarta');
        $result = $this->ganti($this->wali, $this->g, '0857 1111 2222');

        $result->assertRedirectTo("https://example.com/panel/siswa/{$this->g['id']}");
        $this->assertSame('Nomor WA orang tua/wali Gita disimpan: 0857-1111-2222.', session('sukses'));
        $this->seeInDatabase('siswa', ['id' => $this->g['id'], 'wa_ortu' => '6285711112222']);

        $log = $this->db->table('log_data_siswa')->where('siswa_id', $this->g['id'])->get()->getResultArray();
        $this->assertCount(1, $log);
        $this->assertSame(['wa_diubah', (string) $this->wali['id'], '2026-10-13 09:15:00'], [$log[0]['jenis'], (string) $log[0]['pelaku_id'], $log[0]['created_at']]);
        $this->assertEquals(['wa_ortu' => '6281234567890'], json_decode($log[0]['data_lama'], true));
        $this->assertEquals(['wa_ortu' => '6285711112222'], json_decode($log[0]['data_baru'], true));
    }

    public function testWaliKelasCannotChangeAnotherRombel(): void
    {
        // AC-MD-04-04, E5, docs/04 §4.1
        foreach ([['GET', "panel/siswa/{$this->siswa7b['id']}/wa/ubah", []], ['POST', "panel/siswa/{$this->siswa7b['id']}/wa", ['_method' => 'PUT', 'versi' => $this->siswa7b['updated_at'], 'wa_ortu' => '081234567890']]] as [$method, $uri, $isian]) {
            try {
                $this->kirim($this->wali, $method, $uri, $isian);
                $this->fail("{$method} {$uri} should be refused");
            } catch (DiLuarHak) {
                $this->addToAssertionCount(1);
            }
        }
        $this->seeInDatabase('siswa', ['id' => $this->siswa7b['id'], 'wa_ortu' => null]);

        // No HA-MD-06 at all: the filter refuses.
        $piket  = $this->buatAkun(['username' => 'piket'], ['guru_piket']);
        $result = $this->kirim($piket, 'GET', "panel/siswa/{$this->g['id']}/wa/ubah", [], ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json']);
        $result->assertStatus(403);
    }

    public function testInvalidNumberIsRejectedAndEmptyClears(): void
    {
        // E3, VAL-23, HAL-MD-08
        $this->ganti($this->wali, $this->g, '0812');
        $this->assertSame('Nomor WA tidak valid. Tulis nomor ponsel yang diawali 08, misalnya 081234567890.', session('_ci_validation_errors')['wa_ortu']);
        $this->seeInDatabase('siswa', ['id' => $this->g['id'], 'wa_ortu' => '6281234567890']);

        $this->ganti($this->wali, $this->g, '  ');
        $this->assertSame('Nomor WA orang tua/wali Gita dihapus.', session('sukses'));
        $this->seeInDatabase('siswa', ['id' => $this->g['id'], 'wa_ortu' => null]);
        $log = $this->db->table('log_data_siswa')->where('siswa_id', $this->g['id'])->get()->getRowArray();
        $this->assertEquals(['wa_ortu' => null], json_decode($log['data_baru'], true));
    }

    public function testStaleVersionIsRejected(): void
    {
        // docs/09 RT-12, docs/11 GAL-05
        $admin = $this->buatAkun(['username' => 'admin.tu', 'nama' => 'Admin Tata Usaha'], ['admin']);
        Time::setTestNow('2026-10-13 08:00:00', 'Asia/Jakarta');
        $this->ganti($admin, $this->g, '081111111111');

        Time::setTestNow('2026-10-13 08:10:00', 'Asia/Jakarta');
        $result = $this->ganti($this->wali, $this->g, '082222222222');

        $result->assertRedirectTo("https://example.com/panel/siswa/{$this->g['id']}/wa/ubah");
        $this->assertSame('Data ini sudah diubah oleh Admin Tata Usaha pukul 08.00. Periksa data terbaru, lalu simpan lagi bila perlu.', session('galat'));
        $this->seeInDatabase('siswa', ['id' => $this->g['id'], 'wa_ortu' => '6281111111111']);
    }

    /**
     * @param array<string, mixed> $akun
     * @param array<string, mixed> $siswa Row as the form was opened
     */
    private function ganti(array $akun, array $siswa, string $wa): TestResponse
    {
        return $this->kirim($akun, 'POST', "panel/siswa/{$siswa['id']}/wa", ['_method' => 'PUT', 'versi' => $siswa['updated_at'], 'wa_ortu' => $wa]);
    }

    /**
     * @param array<string, mixed>  $akun
     * @param array<string, mixed>  $isian
     * @param array<string, string> $headers
     */
    private function kirim(array $akun, string $method, string $uri, array $isian = [], array $headers = []): TestResponse
    {
        foreach (['router', 'response', 'request'] as $service) {
            Services::resetSingle($service);
        }
        service('akunAktif')->clear();

        return $this->withSession($this->sesiAkun($akun))
            ->withHeaders($headers + ['X-CSRF-TOKEN' => service('security')->getHash()])
            ->call($method, $uri, $isian);
    }
}
