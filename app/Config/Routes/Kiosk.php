<?php

use CodeIgniter\Router\RouteCollection;

/**
 * Kiosk pages and the kiosk JSON API /kiosk/api/v1/... (docs/09 RT-01, §7).
 *
 * @var RouteCollection $routes
 */
$routes->group('kiosk', ['namespace' => 'App\Controllers\Kiosk', 'filter' => ['sesi', 'area:stasiun']], static function (RouteCollection $routes): void {
});
