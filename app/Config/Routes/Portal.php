<?php

use CodeIgniter\Router\RouteCollection;

/**
 * Student portal (docs/09 RT-01, §4.2).
 *
 * @var RouteCollection $routes
 */
$routes->group('portal', ['namespace' => 'App\Controllers\Portal', 'filter' => ['sesi', 'area:siswa', 'wajib-ganti']], static function (RouteCollection $routes): void {
    // Temporary home until FASE-08 (docs/15 §2.2, L02-09).
    $routes->get('/', 'Riwayat::index', ['as' => 'portal.riwayat.index']);

    // HAL-AKN-08
    $routes->get('akun', 'Akun::index', ['as' => 'portal.akun.index']);
    $routes->get('foto', 'Akun::foto', ['as' => 'portal.akun.foto']);
});
