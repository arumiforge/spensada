<?php

use App\Database\Seeds\PengaturanAwal;
use App\Services\MasterData\IdentitasSekolah;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;
use Config\Services;
use Tests\Support\AkunTrait;

/**
 * School identity and the logo route (docs/04 FS-MD-01, AC-MD-01-*,
 * docs/09 HAL-MD-01, §13, docs/12 SEC-52, SEC-59, SEC-81).
 *
 * @internal
 */
final class SekolahTest extends CIUnitTestCase
{
    use AkunTrait;
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $refresh   = true;
    protected $namespace = 'App';

    private const LOGO_GAGAL = 'Identitas sekolah disimpan. Logo tidak diganti.';

    /** @var array<string, mixed> */
    private array $admin;

    /** @var list<string> Logo files that existed before the test */
    private array $logoAwal;

    private string $dir;

    protected function setUp(): void
    {
        Services::resetSingle('routes');
        Services::resetSingle('router');
        Services::resetSingle('request');
        Services::resetSingle('response');
        parent::setUp();
        Time::setTestNow('2026-10-13 07:00:00', 'Asia/Jakarta');
        $this->seed(PengaturanAwal::class);
        $this->admin    = $this->buatAkun(['username' => 'admin.tu', 'nama' => 'Admin Tata Usaha'], ['admin']);
        $this->logoAwal = glob(WRITEPATH . 'uploads/logo/*') ?: [];
        $this->dir      = sys_get_temp_dir() . '/sekolah-test-' . bin2hex(random_bytes(4));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        service('superglobals')->setFilesArray([]);
        array_map('unlink', array_diff(glob(WRITEPATH . 'uploads/logo/*') ?: [], $this->logoAwal));
        array_map('unlink', glob($this->dir . '/*'));
        rmdir($this->dir);
        Time::setTestNow();
        parent::tearDown();
    }

    public function testNonAdminStaffIsRefused(): void
    {
        // AC-MD-01-02
        $piket = $this->buatAkun(['username' => 'piket'], ['guru_piket']);
        $sesi  = $this->sesiAkun($piket);

        $json = ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'];
        $this->resetRouter();
        $this->withSession($sesi)->withHeaders($json)->get('panel/sekolah')->assertStatus(403);
        $this->resetRouter();
        $this->withSession($sesi)->withHeaders($json + ['X-CSRF-TOKEN' => service('security')->getHash()])
            ->post('panel/sekolah', ['_method' => 'PATCH', 'versi' => $this->versi(), 'sekolah_nama' => 'Sekolah Lain'])->assertStatus(403);
        $this->assertSame('SMP 1 DAWE', $this->nilai('sekolah_nama'));
    }

    public function testFormShowsCurrentIdentity(): void
    {
        $page = $this->kirim('GET', 'panel/sekolah');

        $page->assertOK();
        $page->assertSee('Identitas sekolah', 'h1');
        $page->assertSee('value="SMP 1 DAWE"');
        $page->assertSee('value="2026-10-13 07:00:00"');
        $page->assertSee('/logo?v=bawaan"');
        $page->assertSee('Sekarang memakai lambang bawaan.');
        $page->assertDontSeeElement('input[name=hapus_logo]');
    }

    public function testIdentityAndLogoShowOnLoginAndLogoRoute(): void
    {
        // AC-MD-01-01: name and logo shown with the product name.
        $this->later('07:05:00');
        $result = $this->simpan(['sekolah_nama' => '  SMP Negeri 1 Dawe ', 'sekolah_alamat' => 'Jl. Raya Dawe, Kudus'], $this->png('logo.png', 800, 800));

        $result->assertRedirectTo('/panel/sekolah');
        $result->assertSessionHas('sukses', 'Identitas sekolah disimpan.');
        $this->assertSame('SMP Negeri 1 Dawe', $this->nilai('sekolah_nama'));
        $logo = $this->nilai('sekolah_logo');
        $this->assertMatchesRegularExpression('#^logo/[0-9a-f]{32}\.png$#', $logo);
        $this->assertSame([512, 512], array_slice(getimagesize(WRITEPATH . 'uploads/' . $logo), 0, 2));
        $this->assertSame((int) $this->admin['id'], (int) $this->db->table('pengaturan')->where('kunci', 'sekolah_logo')->get()->getRow()->diubah_oleh);

        $this->assertEquals([
            'sekolah_nama'   => ['lama' => 'SMP 1 DAWE', 'baru' => 'SMP Negeri 1 Dawe'],
            'sekolah_alamat' => ['lama' => 'Dawe, Kabupaten Kudus, Jawa Tengah', 'baru' => 'Jl. Raya Dawe, Kudus'],
            'sekolah_logo'   => 'diganti',
        ], $this->logData());

        $this->resetRouter();
        $login = $this->withSession([])->get('login');
        $login->assertSee('SMP Negeri 1 Dawe');
        $login->assertSee('Spensada');
        $login->assertSee(url_to('publik.logo.index') . '?v=' . substr(basename($logo), 0, 8));

        $this->resetRouter();
        $gambar = $this->get('logo?v=' . substr(basename($logo), 0, 8));
        $gambar->assertOK();
        $gambar->assertHeader('Content-Type', 'image/png');
        $gambar->assertHeader('Cache-Control', 'public, max-age=86400');
        $gambar->assertHeader('Content-Security-Policy', "sandbox; default-src 'none'; frame-ancestors 'none'");
        $gambar->assertHeader('Content-Disposition', 'inline');
        $this->assertSame(file_get_contents(WRITEPATH . 'uploads/' . $logo), $gambar->response()->getBody());
        $this->assertSame(url_to('publik.logo.index') . '?v=' . substr(basename($logo), 0, 8), (new IdentitasSekolah())->urlLogo());
    }

    public function testLogoRouteServesBundledEmblemWhenEmpty(): void
    {
        $gambar = $this->get('logo');

        $gambar->assertOK();
        $gambar->assertHeader('Cache-Control', 'public, max-age=86400');
        $this->assertSame(file_get_contents(FCPATH . 'aset/logo/logo-sekolah.png'), $gambar->response()->getBody());
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function invalidLogos(): iterable
    {
        $format = 'File harus berupa gambar JPG, PNG, atau WebP.';

        yield 'PHP renamed .png' => ['logo.png', "<?php system(\$_GET['c']);", $format];
        yield 'SVG' => ['logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>', $format];
        yield 'too large' => ['logo.png', str_repeat('x', 11 * 1024 * 1024), 'Ukuran file logo.png 11,0 MB. Paling besar 10 MB.'];
    }

    /**
     * @dataProvider invalidLogos
     */
    public function testInvalidLogoIsRefusedButOtherFieldsSave(string $nama, string $isi, string $pesan): void
    {
        // FS-MD-01 E2, docs/12 SEC-81.
        file_put_contents($this->dir . '/unggah', $isi);
        $this->later('07:05:00');

        $result = $this->simpan(['sekolah_nama' => 'SMP Negeri 1 Dawe', 'privasi_teks' => "Baris satu.\r\nBaris dua."], $this->berkas($nama, $this->dir . '/unggah'));

        $result->assertRedirectTo('/panel/sekolah');
        $result->assertSessionHas('galat', self::LOGO_GAGAL);
        $result->assertSessionHas('_ci_validation_errors', ['logo' => $pesan]);
        $this->assertSame('SMP Negeri 1 Dawe', $this->nilai('sekolah_nama'));
        $this->assertSame("Baris satu.\nBaris dua.", $this->nilai('privasi_teks'));
        $this->assertNull($this->nilai('sekolah_logo'));
        $this->assertSame($this->logoAwal, glob(WRITEPATH . 'uploads/logo/*') ?: []);
        $this->assertArrayNotHasKey('sekolah_logo', $this->logData());
    }

    public function testDeleteLogoGoesBackToEmblemAndRemovesFile(): void
    {
        $this->later('07:05:00');
        $this->simpan([], $this->png('logo.png', 100, 100));
        $logo = $this->nilai('sekolah_logo');
        $this->later('07:10:00');

        $page = $this->kirim('GET', 'panel/sekolah');
        $page->assertSeeElement('input[name=hapus_logo]');
        $this->simpan(['hapus_logo' => '1'])->assertSessionHas('sukses', 'Identitas sekolah disimpan.');

        $this->assertNull($this->nilai('sekolah_logo'));
        $this->assertFileDoesNotExist(WRITEPATH . 'uploads/' . $logo);
        $this->assertSame(['sekolah_logo' => 'dihapus'], $this->logData());
    }

    public function testNewLogoReplacesOldFile(): void
    {
        $this->later('07:05:00');
        $this->simpan([], $this->png('a.png', 100, 100));
        $lama = $this->nilai('sekolah_logo');
        $this->later('07:10:00');
        $this->simpan([], $this->png('b.png', 50, 50));

        $this->assertNotSame($lama, $this->nilai('sekolah_logo'));
        $this->assertFileDoesNotExist(WRITEPATH . 'uploads/' . $lama);
        $this->assertFileExists(WRITEPATH . 'uploads/' . $this->nilai('sekolah_logo'));
    }

    public function testStaleFormIsRejectedWithWhoChangedIt(): void
    {
        $versi = $this->versi();
        $this->later('07:40:00');
        $this->simpan(['sekolah_nama' => 'SMP Negeri 1 Dawe']);
        $this->later('07:45:00');

        $result = $this->simpan(['sekolah_nama' => 'Nama Lama', 'versi' => $versi], $this->png('logo.png', 10, 10));

        $result->assertRedirectTo('/panel/sekolah');
        $result->assertSessionHas('galat', 'Data ini sudah diubah oleh Admin Tata Usaha pukul 07.40. Periksa data terbaru, lalu simpan lagi bila perlu.');
        $this->assertSame('SMP Negeri 1 Dawe', $this->nilai('sekolah_nama'));
        $this->assertNull($this->nilai('sekolah_logo'));
        $this->assertSame($this->logoAwal, glob(WRITEPATH . 'uploads/logo/*') ?: []);
    }

    public function testFieldErrorsSaveNothing(): void
    {
        $result = $this->simpan(['sekolah_nama' => '   ', 'privasi_teks' => str_repeat('a', 2001)], $this->png('logo.png', 10, 10));

        $result->assertSessionHas('_ci_validation_errors', [
            'sekolah_nama' => 'Nama resmi sekolah wajib diisi.',
            'privasi_teks' => 'Pemberitahuan privasi paling panjang 2.000 karakter.',
        ]);
        $this->assertSame('SMP 1 DAWE', $this->nilai('sekolah_nama'));
        $this->assertSame(0, $this->db->table('log_aktivitas')->where('jenis', 'pengaturan_diubah')->countAllResults());
        $this->assertSame($this->logoAwal, glob(WRITEPATH . 'uploads/logo/*') ?: []);
    }

    public function testEmptyPrivacyTextIsStoredAsNull(): void
    {
        $this->later('07:05:00');
        $this->simpan(['privasi_teks' => '  ']);

        $this->assertNull($this->nilai('privasi_teks'));
        $this->assertEquals(['privasi_teks' => ['lama' => PengaturanAwal::PRIVASI, 'baru' => null]], $this->logData());
    }

    /**
     * PATCH /panel/sekolah with the current values, overridden by $isian, and an optional file.
     *
     * @param array<string, string>     $isian
     * @param array<string, mixed>|null $logo  One $_FILES entry
     */
    private function simpan(array $isian, ?array $logo = null): TestResponse
    {
        $nilai = (new IdentitasSekolah())->ambil();
        service('superglobals')->setFilesArray($logo === null ? [] : ['logo' => $logo]);

        return $this->kirim('POST', 'panel/sekolah', $isian + [
            '_method'        => 'PATCH',
            'versi'          => $nilai['versi'],
            'sekolah_nama'   => (string) $nilai['sekolah_nama'],
            'sekolah_alamat' => (string) $nilai['sekolah_alamat'],
            'privasi_teks'   => (string) $nilai['privasi_teks'],
        ]);
    }

    /**
     * @param array<string, mixed> $sesi
     * @param array<string, mixed> $isian
     */
    private function kirim(string $method, string $uri, array $isian = [], array $sesi = []): TestResponse
    {
        $this->resetRouter();

        return $this->withSession($sesi ?: $this->sesiAkun($this->admin))
            ->withHeaders(['X-CSRF-TOKEN' => service('security')->getHash()])
            ->call($method, $uri, $isian);
    }

    /**
     * @return array<string, mixed>
     */
    private function png(string $nama, int $w, int $h): array
    {
        $path = $this->dir . '/' . bin2hex(random_bytes(4));
        imagepng(imagecreatetruecolor($w, $h), $path);

        return $this->berkas($nama, $path);
    }

    /**
     * @return array<string, mixed>
     */
    private function berkas(string $nama, string $path): array
    {
        return ['name' => $nama, 'type' => 'image/png', 'tmp_name' => $path, 'error' => UPLOAD_ERR_OK, 'size' => filesize($path)];
    }

    private function later(string $jam): void
    {
        Time::setTestNow("2026-10-13 {$jam}", 'Asia/Jakarta');
    }

    private function versi(): string
    {
        return (new IdentitasSekolah())->ambil()['versi'];
    }

    private function nilai(string $kunci): ?string
    {
        return $this->db->table('pengaturan')->where('kunci', $kunci)->get()->getRow()->nilai;
    }

    /**
     * @return array<string, mixed>
     */
    private function logData(): array
    {
        $row = $this->db->table('log_aktivitas')->where('jenis', 'pengaturan_diubah')->orderBy('id', 'DESC')->get()->getRowArray();

        return json_decode($row['data'], true);
    }

    private function resetRouter(): void
    {
        // The shared router keeps the method of the last request.
        foreach (['router', 'response'] as $service) {
            Services::resetSingle($service);
        }
    }
}
