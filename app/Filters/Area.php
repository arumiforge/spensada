<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Filter `area:<jenis>,…`: the account type must match the area
 * (docs/02 §4 item 4, docs/07 ARS-13). Pages redirect to the account's own
 * home page (docs/09 RT-19 item 5); background requests get 403 `ditolak`.
 */
class Area implements FilterInterface
{
    use BackgroundResponse;

    public function before(RequestInterface $request, $arguments = null)
    {
        if (in_array(session('jenis'), (array) $arguments, true)) {
            return null;
        }

        if ($this->isBackground($request)) {
            return $this->jsonError(403, 'ditolak', 'Account type not allowed in this area.');
        }

        return redirect()->to($this->homePage());
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
