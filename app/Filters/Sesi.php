<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Filter `sesi`: the user must be logged in (docs/07 ARS-13, ARS-47).
 *
 * Skeleton until L01-02 (docs/15 §2.2): it only checks that the session
 * holds `akun_id`, which login writes together with `jenis`.
 */
class Sesi implements FilterInterface
{
    use BackgroundResponse;

    public function before(RequestInterface $request, $arguments = null)
    {
        // L01-02: load akun and roles, check status, credential stamp, 8 h / 7 d limits, station login ID, and save `tujuan`.
        if (session('akun_id') !== null) {
            return null;
        }

        if ($this->isBackground($request)) {
            return $this->jsonError(401, 'login_ulang', 'Login required.')
                ->setHeader('WWW-Authenticate', 'Spensada');
        }

        $redirect = redirect()->to('/login');

        // docs/11 GAL-07
        return $request->getMethod() === 'GET'
            ? $redirect
            : $redirect->with('galat', 'Sesi berakhir sebelum formulir terkirim. Login lagi, lalu kirim ulang formulir.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
