<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Filter `wajib-ganti`: while the account must change its password, only the
 * change-password page and logout are open (docs/02 §2 item 1, docs/07 ARS-13).
 *
 * Skeleton until L01-02 (docs/15 §2.2): `akun.wajib_ganti_password` does not exist yet.
 */
class WajibGanti implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // L01-02: redirect to /akun/password while akun.wajib_ganti_password is 1.
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
