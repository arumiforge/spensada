<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Filter `wajib-ganti`: while the account must change its password, only the
 * change-password page and logout are open (docs/02 §2 item 1, docs/07 ARS-13,
 * AC-AKN-01-04). Runs after `sesi`, which loads the account.
 */
class WajibGanti implements FilterInterface
{
    use BackgroundResponse;

    public function before(RequestInterface $request, $arguments = null)
    {
        if ((int) (service('akunAktif')->akun()['wajib_ganti_password'] ?? 0) !== 1) {
            return null;
        }

        if ($this->isBackground($request)) {
            return $this->jsonError(403, 'ditolak', 'Password change required.');
        }

        return redirect()->to('/akun/password');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
