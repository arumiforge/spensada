<?php

namespace App\Filters;

use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Shared helpers for background requests (docs/07 ARS-13, docs/10 API-02, API-03).
 */
trait BackgroundResponse
{
    /** Home page per account type (docs/09 RT-18 item 1). */
    private const HOME = ['staf' => '/panel', 'siswa' => '/portal', 'stasiun' => '/kiosk'];

    /**
     * JavaScript requests send `X-Requested-With: XMLHttpRequest` (API-02).
     */
    protected function isBackground(RequestInterface $request): bool
    {
        return $request instanceof IncomingRequest && $request->isAJAX();
    }

    /**
     * Error body `{"kode": …, "pesan": …}` plus extra fields (API-03).
     *
     * @param array<string, mixed> $extra
     */
    protected function jsonError(int $status, string $kode, string $pesan, array $extra = []): ResponseInterface
    {
        return service('response')
            ->setStatusCode($status)
            ->setJSON(['kode' => $kode, 'pesan' => $pesan] + $extra);
    }

    /**
     * Home page of the logged-in account type, or /login.
     */
    protected function homePage(): string
    {
        return self::HOME[session('jenis')] ?? '/login';
    }
}
