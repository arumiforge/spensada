<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Filter `keamanan` in `$required['after']`: per-area Content-Security-Policy
 * and Permissions-Policy (docs/12 SEC-35 to SEC-37). The exception handler
 * calls applyHeaders() for 403 and 500 pages.
 */
class Keamanan implements FilterInterface
{
    private const CSP = [
        'default-src' => "'self'",
        'script-src'  => "'self'",
        'style-src'   => "'self'",
        // data: for Bootstrap's inline SVG icons, blob: for photo previews (SEC-36).
        'img-src'         => "'self' blob: data:",
        'font-src'        => "'self'",
        'connect-src'     => "'self'",
        'frame-src'       => "'self'",
        'object-src'      => "'none'",
        'worker-src'      => "'none'",
        'base-uri'        => "'none'",
        'form-action'     => "'self'",
        'frame-ancestors' => "'none'",
    ];

    /** Kiosk: QR reader WebAssembly, Service Worker and manifest (SEC-36). */
    private const CSP_KIOSK = [
        'script-src'   => "'self' 'wasm-unsafe-eval'",
        'worker-src'   => "'self'",
        'manifest-src' => "'self'",
    ];

    public function before(RequestInterface $request, $arguments = null)
    {
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        self::applyHeaders($request, $response);

        return $response;
    }

    /**
     * Writes CSP (unless the controller already wrote one, e.g. for files)
     * and Permissions-Policy for the request's area.
     */
    public static function applyHeaders(RequestInterface $request, ResponseInterface $response): void
    {
        $path    = $request instanceof IncomingRequest ? trim($request->getPath(), '/') : '';
        $isKiosk = $path === 'kiosk' || str_starts_with($path, 'kiosk/');

        if (! $response->hasHeader('Content-Security-Policy') && ! $response->hasHeader('Content-Security-Policy-Report-Only')) {
            $policy = $isKiosk ? array_merge(self::CSP, self::CSP_KIOSK) : self::CSP;
            $value  = implode('; ', array_map(static fn ($name, $source) => "{$name} {$source}", array_keys($policy), $policy));

            // Report-only in development: the Debug Toolbar uses inline scripts (SEC-36).
            $response->setHeader(ENVIRONMENT === 'development' ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy', $value);
        }

        if (! $response->hasHeader('Permissions-Policy')) {
            $response->setHeader('Permissions-Policy', ($isKiosk ? 'camera=(self)' : 'camera=()') . ', microphone=(), geolocation=(), payment=(), usb=()');
        }
    }
}
