<?php

use App\Controllers\Akun\Login;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;

/**
 * GET /login shows the page with the account layout (docs/15 L00-06, docs/09 HAL-AKN-01).
 *
 * @internal
 */
final class LoginPageTest extends CIUnitTestCase
{
    use ControllerTestTrait;

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
        $this->assertStringNotContainsString('<script', $html);
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
