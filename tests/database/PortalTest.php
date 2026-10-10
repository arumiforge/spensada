<?php

use App\Services\Akun\Kredensial;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;
use Config\Services;
use Tests\Support\AkunTrait;
use Tests\Support\MasterDataTrait;

/**
 * Student portal frame, temporary home and account page (docs/08 UI-35,
 * docs/15 §2.2, docs/09 HAL-AKN-08, docs/04 FS-MD-04 item 6, AC-MD-04-06,
 * docs/12 SEC-68).
 *
 * @internal
 */
final class PortalTest extends CIUnitTestCase
{
    use AkunTrait;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use MasterDataTrait;

    protected $refresh   = true;
    protected $namespace = 'App';

    /** @var array<string, mixed> */
    private array $siswa;

    /** @var array<string, mixed> */
    private array $akun;

    private ?string $foto = null;

    protected function setUp(): void
    {
        Services::resetSingle('routes');
        Services::resetSingle('router');
        Services::resetSingle('request');
        Services::resetSingle('response');
        parent::setUp();
        $_SESSION = [];
        Time::setTestNow('2026-10-13 07:00:00', 'Asia/Jakarta');

        $ta          = $this->buatTahunAjaran();
        $rombel      = $this->buatRombel(['tahun_ajaran_id' => $ta['id'], 'nama' => '7A']);
        $this->siswa = $this->buatSiswa([
            'nisn' => '0012345678', 'nis' => '2026001', 'nama' => 'Bela Kusuma', 'jenis_kelamin' => 'P',
            'tanggal_lahir' => '2013-05-02', 'wa_ortu' => '6281234567890', 'rombel_id' => $rombel['id'],
        ]);
        $this->db->table('akun')->where('id', $this->siswa['akun_id'])->update([
            'status' => 'aktif', 'wajib_ganti_password' => 0, 'password_hash' => (new Kredensial())->hash($this->passwordUji),
        ]);
        $this->akun = $this->db->table('akun')->where('id', $this->siswa['akun_id'])->get()->getRowArray();
    }

    protected function tearDown(): void
    {
        if ($this->foto !== null) {
            @unlink(WRITEPATH . 'uploads/' . $this->foto);
        }
        Time::setTestNow();
        parent::tearDown();
    }

    public function testHomeIsTemporaryPageWithBottomMenu(): void
    {
        $result = $this->buka('portal');

        $result->assertOK();
        $result->assertSee('Halaman ini sedang disiapkan.');
        $body = $result->response()->getBody();
        $this->assertStringContainsString('class="portal-menu"', $body);
        // Links are attribute-escaped: "/" is written as &#x2F;.
        $this->assertMatchesRegularExpression('#&\#x2F;portal" aria-current="page">.*?Riwayat</a>#s', $body);
        $this->assertStringContainsString('&#x2F;portal&#x2F;akun"', $body);
        // Izin has no route before FASE-07, so it is not in the menu yet.
        $this->assertStringNotContainsString('Izin', $body);
        $this->assertStringNotContainsString('panel-samping', $body);
    }

    public function testAccountPageShowsOwnDataReadOnly(): void
    {
        // AC-MD-04-06, HAL-AKN-08
        $now = '2026-10-01 08:00:00';
        $this->db->table('atribut_siswa')->insertBatch([
            ['kode' => 'golongan_darah', 'label' => 'Golongan darah', 'tipe' => 'teks', 'urutan' => 1, 'aktif' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['kode' => 'catatan_lama', 'label' => 'Catatan lama', 'tipe' => 'teks', 'urutan' => 2, 'aktif' => 0, 'created_at' => $now, 'updated_at' => $now],
        ]);
        $atribut = $this->db->table('atribut_siswa')->orderBy('id')->get()->getResultArray();
        foreach ([[$atribut[0]['id'], 'O'], [$atribut[1]['id'], 'Rahasia lama']] as [$id, $nilai]) {
            $this->db->table('nilai_atribut_siswa')->insert(['siswa_id' => $this->siswa['id'], 'atribut_id' => $id, 'nilai' => $nilai, 'created_at' => $now, 'updated_at' => $now]);
        }
        $this->buatSiswa(['nama' => 'Siswa Lain', 'nisn' => '0099999999']);
        $this->db->table('pengaturan')->insert(['kunci' => 'privasi_teks', 'nilai' => "Data kamu dipakai untuk presensi.\nTanyakan ke sekolah bila ada pertanyaan.", 'updated_at' => $now]);

        $result = $this->buka('portal/akun');

        $result->assertOK();
        foreach (['Bela Kusuma', '0012345678', '2026001', '7A', '0812-3456-7890', 'Perempuan', '2 Mei 2013', 'Golongan darah', 'Ganti password', 'Logout'] as $teks) {
            $result->assertSee($teks);
        }
        $result->assertDontSee('Catatan lama');
        $result->assertDontSee('Rahasia lama');
        $result->assertDontSee('Siswa Lain');
        $result->assertDontSee('0099999999');
        // Initials stand in for the missing photo.
        $result->assertSee('Bela Kusuma, belum ada foto');
        $result->assertSee('Data kamu dipakai untuk presensi.');
        $result->assertSee('Tanyakan ke sekolah bila ada pertanyaan.');

        // Read only: the only form is logout, with no visible fields.
        $body = $result->response()->getBody();
        $this->assertStringNotContainsString('form-control', $body);
        $this->assertStringNotContainsString('<textarea', $body);
        $this->assertSame(1, substr_count($body, '<form'));
    }

    public function testPhotoIsOwnOr404(): void
    {
        $this->buka('portal/foto')->assertStatus(404);

        $this->foto = 'foto/' . bin2hex(random_bytes(16)) . '.png';
        // A fresh checkout has no photo folder until the first upload.
        is_dir(WRITEPATH . 'uploads/foto') || mkdir(WRITEPATH . 'uploads/foto', 0755, true);
        // 1×1 transparent PNG.
        file_put_contents(WRITEPATH . 'uploads/' . $this->foto, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=', true));
        $this->db->table('siswa')->where('id', $this->siswa['id'])->update(['foto_file' => $this->foto]);

        $result = $this->buka('portal/foto');
        $result->assertOK();
        $this->assertSame(file_get_contents(WRITEPATH . 'uploads/' . $this->foto), $result->response()->getBody());
        $this->assertStringContainsString('no-store', $result->response()->getHeaderLine('Cache-Control'));

        $this->buka('portal/akun')->assertSee('src="https://example.com/portal/foto"');
    }

    public function testStaffCannotOpenThePortal(): void
    {
        $staf = $this->buatAkun(['username' => 'rina.w'], ['admin']);

        $this->withSession($this->sesiAkun($staf))->get('portal/akun')->assertRedirectTo(site_url('panel'));
    }

    private function buka(string $uri): TestResponse
    {
        Services::resetSingle('response');

        return $this->withSession($this->sesiAkun($this->akun))->get($uri);
    }
}
