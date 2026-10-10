<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
// Before R3, `/` sends visitors to the login page (docs/09 RT-18 item 5).
$routes->addRedirect('/', 'login');

// Login stays outside the Akun group: it has no `sesi` filter (docs/09 RT-01).
$routes->get('login', 'Akun\Login::index', ['as' => 'akun.login.index']);
$routes->post('login', 'Akun\Login::masuk', ['as' => 'akun.login.masuk']);

// School logo for every page, including the login page (docs/09 HAL-MD-01, §13).
$routes->get('logo', 'Publik\Logo::index', ['as' => 'publik.logo.index']);
