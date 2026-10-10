<?php

use CodeIgniter\Router\RouteCollection;

/**
 * Account area: /login, /logout, /akun/... (docs/09 RT-01, §5).
 *
 * @var RouteCollection $routes
 */

// /login has no group filters; it is in Config/Routes.php.

// Logout and change password: signed-in staff and students only (HAL-AKN-02, HAL-AKN-03).
$routes->group('', ['namespace' => 'App\Controllers\Akun', 'filter' => ['sesi', 'area:staf,siswa']], static function (RouteCollection $routes): void {
    $routes->post('logout', 'Login::keluar', ['as' => 'akun.login.keluar']);
    $routes->get('akun/password', 'Password::ubah', ['as' => 'akun.password.ubah']);
    $routes->put('akun/password', 'Password::ganti', ['as' => 'akun.password.ganti']);
});
