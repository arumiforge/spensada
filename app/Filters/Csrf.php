<?php

namespace App\Filters;

use CodeIgniter\Filters\CSRF as BaseCsrf;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\Method;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\Security\Exceptions\SecurityException;

/**
 * Filter `csrf`, global before all others (docs/07 ARS-13, docs/12 SEC-30).
 * Token check stays in Security::verify(); this filter only changes how a
 * rejection is answered.
 */
class Csrf extends BaseCsrf
{
    use BackgroundResponse;

    public function before(RequestInterface $request, $arguments = null)
    {
        if (! $request instanceof IncomingRequest) {
            return null;
        }

        // Over post_max_size PHP drops all fields, the token too (docs/11 GAL-11).
        if ($this->isTooLarge($request)) {
            return $this->isBackground($request)
                ? $this->jsonError(413, 'terlalu_besar', 'Request body exceeds post_max_size.')
                : redirect()->to($this->originPage($request), 303)->with('galat', 'Kiriman terlalu besar. Paling besar 100 MB per kiriman.');
        }

        $security = service('security');

        try {
            $security->verify($request);

            return null;
        } catch (SecurityException) {
            log_message('info', 'CSRF token rejected.'); // SEC-30 item 4
        }

        if ($this->isBackground($request)) {
            // docs/10 API-04: send the current token so the client retries once.
            return $this->jsonError(403, 'csrf', 'CSRF token missing or invalid.', [
                'csrf' => ['header' => $security->getHeaderName(), 'token' => $security->getHash()],
            ]);
        }

        // docs/11 GAL-06
        $message = $request->getPath() === 'login'
            ? 'Halaman login sudah terlalu lama dibuka. Login lagi.'
            : 'Kiriman tidak dapat diproses karena halaman sudah terlalu lama dibuka. Periksa isian, lalu kirim lagi.'
                . ($request->getFiles() !== [] ? ' File perlu dipilih ulang.' : '');

        // Same shape as RedirectResponse::withInput(), minus passwords, PINs and tokens (SEC-30 item 3).
        $tokenName = $security->getTokenName();
        $post      = array_filter(
            (array) $request->getPost(),
            static fn ($key): bool => $key !== $tokenName && preg_match('/^(password|pin|token)(_|$)/', (string) $key) !== 1,
            ARRAY_FILTER_USE_KEY,
        );
        session()->setFlashdata('_ci_old_input', ['get' => (array) $request->getGet(), 'post' => $post]);

        return redirect()->to($this->originPage($request), 303)->withCookies()->with('galat', $message);
    }

    private function isTooLarge(IncomingRequest $request): bool
    {
        if (! in_array($request->getMethod(), [Method::POST, Method::PUT, Method::PATCH, Method::DELETE], true)) {
            return false;
        }

        $limit = trim((string) ini_get('post_max_size'));
        $bytes = (int) $limit;
        $bytes = match (strtoupper(substr($limit, -1))) {
            'G'     => $bytes << 30,
            'M'     => $bytes << 20,
            'K'     => $bytes << 10,
            default => $bytes,
        };

        return $bytes > 0 && (int) $request->getServer('CONTENT_LENGTH') > $bytes;
    }

    /**
     * The Referer path when it is a page of this app, else the area home
     * page (docs/12 SEC-30 item 3, SEC-39).
     */
    private function originPage(IncomingRequest $request): string
    {
        $referer = $request->getHeaderLine('Referer');
        $parts   = parse_url($referer) ?: [];
        $path    = $parts['path'] ?? '';

        if (! in_array($parts['scheme'] ?? null, ['http', 'https'], true)
            || ($parts['host'] ?? null) !== $request->getUri()->getHost()
            || ! str_starts_with($path, '/') || str_starts_with($path, '//') || str_starts_with($path, '/\\')) {
            return $this->homePage();
        }

        return $referer;
    }
}
