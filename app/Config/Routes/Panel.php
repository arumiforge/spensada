<?php

use CodeIgniter\Router\RouteCollection;

/**
 * Staff and admin panel (docs/09 RT-01, §4.1).
 *
 * @var RouteCollection $routes
 */
$routes->group('panel', ['namespace' => 'App\Controllers\Panel', 'filter' => ['sesi', 'area:staf', 'wajib-ganti']], static function (RouteCollection $routes): void {
});
