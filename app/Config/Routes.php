<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
// Before R3, `/` sends visitors to the login page (docs/09 RT-18 item 5).
$routes->addRedirect('/', 'akun.login.index');

// Login stays outside the Akun group: it has no `sesi` filter (docs/09 RT-01).
$routes->get('login', 'Akun\Login::index', ['as' => 'akun.login.index']);
