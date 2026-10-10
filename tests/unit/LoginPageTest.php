<?php

use App\Controllers\Akun\Login;
use CodeIgniter\Test\CIUnitTestCase;
use App\Database\Seeds\PengaturanAwal;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * GET /login shows the page with the account layout (docs/15 L00-06, L01-02, docs/09 HAL-AKN-01).
 *
 * @internal
 */
final class LoginPageTest extends CIUnitTestCase
{
    use ControllerTestTrait;
    use DatabaseTestTrait;

    protected $refresh   = true;
    protected $namespace = 'App';

    public function testLoginPageRendersWithAccountLayout(): void
    {
        $result = $this->controller(Login::class)->execute('index');

        $result->assertOK();
        $result->assertSee('<title>Login · Spensada</title>', null);
        $result->assertSeeElement('main#isi');
        $result->assertSeeElement('form[method=post]');
        $result->assertSeeElement('input[name=identitas]');
        $result->assertSeeElement('input[autocomplete=current-password]');
        $result->assertSeeElement('label[for=identitas]');
        $result->assertSeeElement('label[for=password]');
        $result->assertSee('NISN atau username', 'label');
        $result->assertSee('Siswa yang lupa password menghubungi wali kelas. Staf menghubungi admin. Di komputer bersama, logout setelah selesai.');
        $result->assertSee('Login', 'button');
        $result->assertSee('Spensada', '.akun-kepala-produk');
        $result->assertSeeElement('button[data-isian-password=password]');
    }

    public function testLoginPageShowsSchoolNameFromSettings(): void
    {
        $this->seed(PengaturanAwal::class);

        $result = $this->controller(Login::class)->execute('index');

        $result->assertSee('SMP 1 DAWE', '.akun-kepala-sekolah');
    }

    public function testLoginPageShowsPrivacyNoticeFromSettings(): void
    {
        $this->seed(PengaturanAwal::class);

        $result = $this->controller(Login::class)->execute('index');

        $result->assertSee('Pemberitahuan privasi');
        $result->assertSee('Spensada dipakai sekolah untuk mencatat kehadiran siswa.', 'p');
        $result->assertSee('Pertanyaan atau permintaan perbaikan data dapat disampaikan kepada wali kelas atau admin sekolah.', 'p');

        // The school may clear it (docs/06 §6.1).
        $this->db->table('pengaturan')->where('kunci', 'privasi_teks')->update(['nilai' => '']);

        $this->controller(Login::class)->execute('index')->assertDontSee('Pemberitahuan privasi');
    }

    public function testLoginPageHeadHasFaviconsAndCssInOrder(): void
    {
        $html = $this->controller(Login::class)->execute('index')->getBody();

        $this->assertStringContainsString('<link rel="icon" href="' . base_url('favicon.ico') . '" sizes="any">', $html);
        $this->assertStringContainsString('<link rel="icon" href="' . base_url('aset/logo/favicon-32.png') . '" type="image/png">', $html);
        $this->assertStringContainsString('<link rel="apple-touch-icon" href="' . base_url('apple-touch-icon.png') . '">', $html);

        $bootstrap = strpos($html, 'bootstrap.min.css');
        $token     = strpos($html, 'token.css');
        $app       = strpos($html, 'spensada.css');
        $this->assertNotFalse($bootstrap);
        $this->assertTrue($bootstrap < $token && $token < $app);
        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertStringNotContainsString('kamu', strtolower($html));
        // Only the show/hide password script (docs/08 UI-38).
        $this->assertSame(1, substr_count($html, '<script'));
        $this->assertStringContainsString('aset/js/isian-password.js', $html);
    }

    public function testLoginPageAssetsExist(): void
    {
        foreach ([
            'aset/vendor/bootstrap/5.3.8/css/bootstrap.min.css',
            'aset/vendor/bootstrap/5.3.8/js/bootstrap.bundle.min.js',
            'aset/vendor/bootstrap/5.3.8/LICENSE',
            'aset/vendor/plus-jakarta-sans/2.071/plus-jakarta-sans-latin-wght-normal.woff2',
            'aset/vendor/plus-jakarta-sans/2.071/plus-jakarta-sans-latin-ext-wght-normal.woff2',
            'aset/vendor/plus-jakarta-sans/2.071/OFL.txt',
            'aset/ikon/ikon.svg',
            'aset/ikon/LICENSE',
            'aset/css/token.css',
            'aset/css/spensada.css',
            'aset/logo/logo-sekolah.png',
        ] as $path) {
            $this->assertFileExists(FCPATH . $path);
        }
    }
}
