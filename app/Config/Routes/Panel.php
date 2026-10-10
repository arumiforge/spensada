<?php

use CodeIgniter\Router\RouteCollection;

/**
 * Staff and admin panel (docs/09 RT-01, §4.1).
 *
 * @var RouteCollection $routes
 */
$routes->group('panel', ['namespace' => 'App\Controllers\Panel', 'filter' => ['sesi', 'area:staf', 'wajib-ganti']], static function (RouteCollection $routes): void {
    // Temporary home until FASE-08 (docs/15 §2.2, L01-08).
    $routes->get('/', 'Dashboard::index', ['as' => 'panel.dashboard.index']);

    // HAL-AKN-04 (docs/09 §5)
    $routes->get('akun-staf', 'AkunStaf::index', ['as' => 'panel.akun_staf.index']);
    $routes->get('akun-staf/tambah', 'AkunStaf::tambah', ['as' => 'panel.akun_staf.tambah']);
    $routes->post('akun-staf', 'AkunStaf::simpan', ['as' => 'panel.akun_staf.simpan']);
    $routes->get('akun-staf/(:num)', 'AkunStaf::lihat/$1', ['as' => 'panel.akun_staf.lihat']);
    $routes->get('akun-staf/(:num)/ubah', 'AkunStaf::ubah/$1', ['as' => 'panel.akun_staf.ubah']);
    $routes->patch('akun-staf/(:num)', 'AkunStaf::perbarui/$1', ['as' => 'panel.akun_staf.perbarui']);
    $routes->get('akun-staf/(:num)/nonaktifkan', 'AkunStaf::formNonaktifkan/$1', ['as' => 'panel.akun_staf.form_nonaktifkan']);
    $routes->post('akun-staf/(:num)/nonaktifkan', 'AkunStaf::nonaktifkan/$1', ['as' => 'panel.akun_staf.nonaktifkan']);
    $routes->post('akun-staf/(:num)/aktifkan', 'AkunStaf::aktifkan/$1', ['as' => 'panel.akun_staf.aktifkan']);
    $routes->get('akun-staf/(:num)/reset-password', 'AkunStaf::formResetPassword/$1', ['as' => 'panel.akun_staf.form_reset_password']);
    $routes->post('akun-staf/(:num)/reset-password', 'AkunStaf::resetPassword/$1', ['as' => 'panel.akun_staf.reset_password']);
    $routes->post('akun-staf/(:num)/buka-kunci', 'AkunStaf::bukaKunci/$1', ['as' => 'panel.akun_staf.buka_kunci']);

    // HAL-AKN-07
    $routes->get('sistem', 'Sistem::index', ['as' => 'panel.sistem.index']);

    // HAL-AKN-09
    $routes->get('log-aktivitas', 'LogAktivitas::index', ['as' => 'panel.log_aktivitas.index']);
    $routes->get('log-aktivitas/(:num)', 'LogAktivitas::lihat/$1', ['as' => 'panel.log_aktivitas.lihat']);
});
