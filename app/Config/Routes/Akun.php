<?php

use CodeIgniter\Router\RouteCollection;

/**
 * Account area: /login, /logout, /akun/... (docs/09 RT-01, §5).
 *
 * @var RouteCollection $routes
 */

// /login has no group filters; add it outside the group below.

// Logout and change password: signed-in staff and students only.
$routes->group('', ['namespace' => 'App\Controllers\Akun', 'filter' => ['sesi', 'area:staf,siswa']], static function (RouteCollection $routes): void {
});
