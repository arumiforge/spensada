<?php

namespace App\Services\Berkas;

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;
use finfo;

/**
 * Sends stored files through a controller (docs/07 ARS-52, docs/12 SEC-52,
 * docs/09 §13). Callers check rights first; this class only reads the file.
 * `X-Content-Type-Options: nosniff` comes from Nginx (SEC-34).
 */
class Penyaji
{
    /** CSP for photos and the logo: no scripts, never framed (SEC-52). */
    private const CSP = "sandbox; default-src 'none'; frame-ancestors 'none'";

    /** Bundled school emblem, used while `sekolah_logo` is empty (docs/06 §6.1). */
    private const LOGO_BAWAAN = 'aset/logo/logo-sekolah.png';

    /**
     * Shows a file under writable/uploads/ inline with `Cache-Control: no-store`.
     *
     * @param string $relativePath Path relative to writable/uploads/, from the database (DB-15)
     *
     * @throws PageNotFoundException When the path is missing or points outside writable/uploads/
     */
    public function tampilkan(string $relativePath): ResponseInterface
    {
        return $this->kirim($this->path($relativePath) ?? throw PageNotFoundException::forPageNotFound(), 'no-store');
    }

    /**
     * Shows the school logo, cached for one day (`/logo?v=`, docs/09 §13), or
     * the bundled emblem when no logo is uploaded or its file is gone.
     *
     * @param string|null $relativePath `pengaturan.sekolah_logo`
     */
    public function logo(?string $relativePath): ResponseInterface
    {
        $path = ($relativePath ?? '') === '' ? null : $this->path($relativePath);

        return $this->kirim($path ?? FCPATH . self::LOGO_BAWAAN, 'public, max-age=86400');
    }

    /**
     * Absolute path of an existing file inside writable/uploads/, or null.
     * Paths come from the database, but are still checked (docs/12 SEC-45).
     */
    private function path(string $relativePath): ?string
    {
        if ($relativePath === '' || preg_match('#(^|[/\\\\])\.\.([/\\\\]|$)|^[/\\\\]|:|\x00#', $relativePath) === 1) {
            return null;
        }

        $base = realpath(WRITEPATH . 'uploads');
        $path = realpath(WRITEPATH . 'uploads/' . $relativePath);

        return $base !== false && $path !== false && str_starts_with($path, $base . DIRECTORY_SEPARATOR) && is_file($path) ? $path : null;
    }

    private function kirim(string $path, string $cacheControl): ResponseInterface
    {
        $response = service('response');
        $response->removeHeader('Cache-Control');

        // The type comes from the content, never from the file name (SEC-52).
        return $response->setStatusCode(200)
            ->setHeader('Content-Type', (string) (new finfo(FILEINFO_MIME_TYPE))->file($path))
            ->setHeader('Content-Disposition', 'inline')
            ->setHeader('Cache-Control', $cacheControl)
            ->setHeader('Content-Security-Policy', self::CSP)
            ->setBody((string) file_get_contents($path));
    }
}
