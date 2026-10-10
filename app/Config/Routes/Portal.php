<?php

use CodeIgniter\Router\RouteCollection;

/**
 * Student portal (docs/09 RT-01, §4.2).
 *
 * @var RouteCollection $routes
 */
$routes->group('portal', ['namespace' => 'App\Controllers\Portal', 'filter' => ['sesi', 'area:siswa', 'wajib-ganti']], static function (RouteCollection $routes): void {
});
