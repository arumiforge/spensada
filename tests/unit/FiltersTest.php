<?php

use App\Filters\Keamanan;
use App\Services\Akun\DiLuarHak;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\FilterTestTrait;
use Config\Services;
use Tests\Support\Filters\HakWithRoles;

/**
 * Filters of docs/07 ARS-13 and their JSON codes (docs/10 API-03).
 *
 * @internal
 */
final class FiltersTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use FilterTestTrait;

    private const AJAX = ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'];

    protected function setUp(): void
    {
        // Request and response are shared services; start each test clean.
        Services::resetSingle('request');
        Services::resetSingle('response');
        parent::setUp();

        $ok = static fn (): string => 'ok';
        $this->withRoutes([
            ['GET', 'panel/uji', $ok, ['filter' => ['sesi', 'area:staf', 'wajib-ganti']]],
            ['GET', 'kiosk/uji', $ok],
            ['POST', 'panel/simpan', $ok],
            ['GET', 'panel/hak', $ok, ['filter' => 'hak:HA-AKN-02']],
            ['GET', 'akun/uji', $ok, ['filter' => ['sesi', 'area:staf,siswa']]],
        ]);
    }

    public function testCsrfRejectionAnswersJsonWithNewToken(): void
    {
        $result = $this->withHeaders(self::AJAX)->post('panel/simpan');

        $result->assertStatus(403);
        $body = json_decode($result->getJSON(), true);
        $this->assertSame('csrf', $body['kode']);
        $this->assertSame(config('Security')->headerName, $body['csrf']['header']);
        $this->assertNotEmpty($body['csrf']['token']);
    }

    public function testCsrfRejectionOfFormRedirectsBackWithInputExceptSecrets(): void
    {
        $result = $this->withHeaders(['Referer' => 'http://example.com/panel/siswa/tambah?x=1'])
            ->post('panel/simpan', ['nama' => 'Budi', 'password' => 'rahasia', 'pin_ulang' => '123456']);

        $result->assertStatus(303);
        $result->assertRedirectTo('http://example.com/panel/siswa/tambah?x=1');
        $this->assertStringContainsString('terlalu lama dibuka', session('galat'));
        $this->assertSame(['nama' => 'Budi'], session('_ci_old_input')['post']);
    }

    public function testCsrfRejectionIgnoresForeignReferer(): void
    {
        $result = $this->withSession(['akun_id' => 1, 'jenis' => 'siswa'])
            ->withHeaders(['Referer' => 'https://evil.test/panel'])
            ->post('panel/simpan');

        $result->assertRedirectTo('https://example.com/portal');
    }

    public function testTooLargeBodyAnswersTerlaluBesar(): void
    {
        $server  = service('superglobals')->getServerArray();
        $request = service('request')->withMethod('POST');
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');
        $request->setGlobal('server', ['CONTENT_LENGTH' => (string) PHP_INT_MAX] + $server);
        $this->request = $request;

        try {
            $result = $this->getFilterCaller('csrf', 'before')();
        } finally {
            service('superglobals')->setGlobalArray('server', $server);
        }

        $this->assertSame(413, $result->getStatusCode());
        $this->assertSame('terlalu_besar', json_decode($result->getBody(), true)['kode']);
    }

    public function testSesiWithoutLoginAnswersLoginUlang(): void
    {
        $result = $this->withHeaders(self::AJAX)->get('panel/uji');

        $result->assertStatus(401);
        $result->assertHeader('WWW-Authenticate', 'Spensada');
        $this->assertSame('login_ulang', json_decode($result->getJSON(), true)['kode']);
    }

    public function testSesiWithoutLoginRedirectsPageToLogin(): void
    {
        $this->get('panel/uji')->assertRedirectTo('https://example.com/login');
    }

    public function testAreaMismatchAnswersDitolakOrRedirectsHome(): void
    {
        $siswa = ['akun_id' => 5, 'jenis' => 'siswa'];

        $result = $this->withSession($siswa)->withHeaders(self::AJAX)->get('panel/uji');
        $result->assertStatus(403);
        $this->assertSame('ditolak', json_decode($result->getJSON(), true)['kode']);

        $this->withSession($siswa)->withHeaders([])->get('panel/uji')->assertRedirectTo('https://example.com/portal');
        $this->withSession(['akun_id' => 9, 'jenis' => 'stasiun'])->get('akun/uji')->assertRedirectTo('https://example.com/kiosk');
    }

    public function testAreaMatchPasses(): void
    {
        $this->withSession(['akun_id' => 1, 'jenis' => 'staf'])->get('panel/uji')->assertOK();
        $this->withSession(['akun_id' => 5, 'jenis' => 'siswa'])->get('akun/uji')->assertOK();
    }

    public function testHakDeniedAnswersDitolak(): void
    {
        $result = $this->withHeaders(self::AJAX)->get('panel/hak');

        $result->assertStatus(403);
        $this->assertSame('ditolak', json_decode($result->getJSON(), true)['kode']);
    }

    public function testHakDeniedPageThrowsDiLuarHak(): void
    {
        $this->expectException(DiLuarHak::class);
        $this->expectExceptionCode(403);

        $this->getFilterCaller('hak', 'before')(['HA-AKN-02']);
    }

    public function testHakChecksEveryListedRight(): void
    {
        $filter = new HakWithRoles();

        HakWithRoles::$roles = ['staf', 'guru_piket'];
        $this->assertNull($this->getFilterCaller($filter, 'before')(['HA-AKN-02', 'HA-KIO-02']));

        HakWithRoles::$roles = ['staf'];
        $this->expectException(DiLuarHak::class);
        $this->getFilterCaller($filter, 'before')(['HA-AKN-02', 'HA-KIO-02']);
    }

    public function testCspPerArea(): void
    {
        $kiosk = $this->get('kiosk/uji')->response();
        $this->assertStringContainsString("script-src 'self' 'wasm-unsafe-eval'", $kiosk->getHeaderLine('Content-Security-Policy'));
        $this->assertStringContainsString("worker-src 'self'", $kiosk->getHeaderLine('Content-Security-Policy'));
        $this->assertStringContainsString("manifest-src 'self'", $kiosk->getHeaderLine('Content-Security-Policy'));
        $this->assertSame('camera=(self), microphone=(), geolocation=(), payment=(), usb=()', $kiosk->getHeaderLine('Permissions-Policy'));

        Services::resetSingle('response');
        $result = $this->withSession(['akun_id' => 1, 'jenis' => 'staf'])->get('panel/uji');
        $result->assertHeader(
            'Content-Security-Policy',
            "default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' blob: data:; font-src 'self'; connect-src 'self'; frame-src 'self'; object-src 'none'; worker-src 'none'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'",
        );
        $result->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
    }

    public function testCspOnRejectedRequests(): void
    {
        $this->withHeaders(self::AJAX)->get('panel/hak')->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
    }

    public function testApplyHeadersKeepsControllerCsp(): void
    {
        $response = service('response');
        $response->setHeader('Content-Security-Policy', "sandbox; default-src 'none'; frame-ancestors 'none'");

        Keamanan::applyHeaders(service('request'), $response);

        $this->assertSame("sandbox; default-src 'none'; frame-ancestors 'none'", $response->getHeaderLine('Content-Security-Policy'));
        $this->assertSame('camera=(), microphone=(), geolocation=(), payment=(), usb=()', $response->getHeaderLine('Permissions-Policy'));
    }
}
