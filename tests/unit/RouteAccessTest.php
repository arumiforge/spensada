<?php

use CodeIgniter\Commands\Utilities\Routes\SampleURIGenerator;
use CodeIgniter\Router\DefinedRouteCollector;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;
use Config\Services;

/**
 * Access test over the real route list and filter stack (docs/12 SEC-81,
 * docs/09 RT-01, RT-19 items 5 and 6, docs/07 ARS-13). Routes are read the
 * way `php spark routes` reads them, so a new route is tested automatically,
 * and a route outside the known areas fails until a rule is added here.
 *
 * @internal
 */
final class RouteAccessTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    /** Account types allowed per area prefix (docs/09 RT-01). */
    private const AREAS = [
        'panel'  => ['staf'],
        'portal' => ['siswa'],
        'kiosk'  => ['stasiun'],
        'akun'   => ['staf', 'siswa'],
        'logout' => ['staf', 'siswa'],
    ];

    /** Routes open without login (docs/09 RT-01 Publik, HAL-AKN-01). */
    private const PUBLIC = ['/', 'login', 'logo'];

    private const HOME = ['staf' => 'panel', 'siswa' => 'portal', 'stasiun' => 'kiosk'];

    private const AJAX = ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'];

    protected function setUp(): void
    {
        // Other tests replace the shared routes with withRoutes(), and the shared
        // router keeps the collection it was built with; load the real ones.
        Services::resetSingle('routes');
        Services::resetSingle('router');
        Services::resetSingle('request');
        Services::resetSingle('response');
        parent::setUp();
    }

    public function testEveryRouteHasAnAccessRule(): void
    {
        $routes = $this->routeList();

        $this->assertNotEmpty($routes, 'The route list could not be read.');

        foreach ($routes as [$method, $uri]) {
            $this->assertTrue(
                in_array($uri, self::PUBLIC, true) || $this->allowedTypes($uri) !== null,
                "{$method} /{$uri} is in no area of docs/09 RT-01; add its access rule to this test.",
            );
        }
    }

    public function testAreaRoutesRejectGuestsAndWrongAccountTypes(): void
    {
        foreach ($this->routeList() as [$method, $uri]) {
            if (($allowed = $this->allowedTypes($uri)) !== null) {
                $this->assertAreaRules($method, $uri, $allowed);
            }
        }

        // Today the area groups may still be empty (docs/15 L00-08); the test still runs.
        $this->addToAssertionCount(1);
    }

    /**
     * Checks the harness itself on fixture routes with the area group filters,
     * so the real-route check above is known to work once routes exist.
     */
    public function testHarnessOnFixtureRoutes(): void
    {
        $ok = static fn (): string => 'ok';
        $this->withRoutes([
            ['GET', 'panel/contoh', $ok, ['filter' => ['sesi', 'area:staf', 'wajib-ganti']]],
            ['POST', 'portal/contoh', $ok, ['filter' => ['sesi', 'area:siswa', 'wajib-ganti']]],
            ['PUT', 'akun/contoh', $ok, ['filter' => ['sesi', 'area:staf,siswa']]],
            ['POST', 'kiosk/api/v1/contoh', $ok, ['filter' => ['sesi', 'area:stasiun']]],
        ]);

        $this->assertAreaRules('GET', 'panel/contoh', ['staf']);
        $this->assertAreaRules('POST', 'portal/contoh', ['siswa']);
        $this->assertAreaRules('PUT', 'akun/contoh', ['staf', 'siswa']);
        $this->assertAreaRules('POST', 'kiosk/api/v1/contoh', ['stasiun']);

        // The allowed account type gets through every filter.
        $this->send('GET', 'panel/contoh', 'staf', false)->assertOK();
        $this->send('POST', 'kiosk/api/v1/contoh', 'stasiun', true)->assertOK();
    }

    /**
     * @param list<string> $allowed
     */
    private function assertAreaRules(string $method, string $uri, array $allowed): void
    {
        $route = "{$method} /{$uri}";

        // Without login: pages go to /login, background requests get 401 login_ulang.
        $this->send($method, $uri, null, false)->assertRedirectTo(site_url('login'));
        $this->assertJsonCode($this->send($method, $uri, null, true), 401, 'login_ulang', $route);

        // Wrong account type: pages go to the account's home, background requests get 403 ditolak.
        foreach (array_diff(array_keys(self::HOME), $allowed) as $jenis) {
            $this->send($method, $uri, $jenis, false)->assertRedirectTo(site_url(self::HOME[$jenis]));
            $this->assertJsonCode($this->send($method, $uri, $jenis, true), 403, 'ditolak', "{$route} as {$jenis}");
        }

        // Writes without a CSRF token are refused before login is checked (docs/10 §4.1).
        if (! in_array($method, ['GET', 'HEAD', 'OPTIONS', 'TRACE', 'CONNECT'], true)) {
            $result = $this->withSession([])->withHeaders(self::AJAX)->call($method, $uri);
            $this->assertJsonCode($result, 403, 'csrf', "{$route} without token");
        }
    }

    /**
     * Sends one request through the full filter stack, with a valid CSRF token.
     */
    private function send(string $method, string $uri, ?string $jenis, bool $background): TestResponse
    {
        Services::resetSingle('response');

        // shortcut: `sesi` only checks akun_id until L01-02; then log in with real akun rows from a seeder.
        $session = $jenis === null ? [] : ['akun_id' => 1, 'jenis' => $jenis];
        $headers = ['X-CSRF-TOKEN' => service('security')->getHash()] + ($background ? self::AJAX : []);

        return $this->withSession($session)->withHeaders($headers)->call($method, $uri);
    }

    private function assertJsonCode(TestResponse $result, int $status, string $kode, string $route): void
    {
        $this->assertSame($status, $result->response()->getStatusCode(), $route);
        $this->assertSame($kode, json_decode($result->getJSON(), true)['kode'] ?? null, $route);
    }

    /**
     * @return list<string>|null account types allowed, or null when the route is in no area
     */
    private function allowedTypes(string $uri): ?array
    {
        return self::AREAS[explode('/', $uri)[0]] ?? null;
    }

    /**
     * Every defined route as [method, sample URI], like `php spark routes`.
     *
     * @return list<array{string, string}>
     */
    private function routeList(): array
    {
        $collection = service('routes')->loadRoutes();
        $generator  = new SampleURIGenerator($collection);
        $list       = [];

        foreach ((new DefinedRouteCollector($collection))->collect() as $route) {
            if ($route['method'] !== 'CLI') {
                $list[] = [strtoupper($route['method']), $generator->get($route['route'])];
            }
        }

        return $list;
    }
}
