<?php

namespace App\Filters;

use App\Services\Akun\DiLuarHak;
use App\Services\Akun\HakAkses;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Filter `hak:<ID>,…`: one of the account's roles must hold one of the listed
 * rights (docs/07 ARS-13, docs/09 RT-02). Scope, batas mundur and data state
 * are checked in services. Pages get the 403 page via DiLuarHak; background
 * requests get 403 `ditolak`.
 */
class Hak implements FilterInterface
{
    use BackgroundResponse;

    public function before(RequestInterface $request, $arguments = null)
    {
        $hakAkses = new HakAkses();
        $roles    = $this->roles();

        foreach ((array) $arguments as $hak) {
            if ($hakAkses->has($roles, $hak)) {
                return null;
            }
        }

        // docs/11 GAL-08
        log_message('info', 'Access denied: {path} needs {hak}.', ['path' => $request->getUri()->getPath(), 'hak' => implode(',', (array) $arguments)]);

        if ($this->isBackground($request)) {
            return $this->jsonError(403, 'ditolak', 'Right not held.');
        }

        throw new DiLuarHak('Right not held.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }

    /**
     * Roles of the logged-in account, loaded by the `sesi` filter.
     *
     * @return list<string>
     */
    protected function roles(): array
    {
        return service('akunAktif')->roles();
    }
}
