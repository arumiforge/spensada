<?php

namespace App\Controllers\Akun;

use App\Controllers\BaseController;

/**
 * Login page (docs/09 HAL-AKN-01). L00-06 only shows the page; the POST handler,
 * redirects for signed-in users, and school settings come in L01-02.
 */
class Login extends BaseController
{
    public function index(): string
    {
        return view('akun/login', [
            'title' => 'Login',
            // shortcut: pengaturan (L00-07) is not read yet, so name and privacy notice are empty; read them in L01-02.
            'schoolName'    => '',
            'privacyNotice' => '',
        ]);
    }
}
