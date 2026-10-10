<?php

namespace App\Libraries;

use CodeIgniter\Debug\ExceptionHandler;
use CodeIgniter\Debug\ExceptionHandlerInterface;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Exceptions;
use Throwable;

/**
 * Application exception handler (docs/09 RT-19, docs/11 §6).
 *
 * Pages: 400, 403, 404, 429, and 500 (any other status) with friendly
 * Indonesian text, never the exception message or trace. Background
 * requests (X-Requested-With) get the JSON code of docs/10 API-03.
 * CLI requests and 5xx with display_errors on (development) keep the CI4
 * default, so developers still see the trace (GAL-13, GAL-17).
 */
class AppExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * Status => [JSON code (docs/10 API-03), view under errors/html/].
     */
    private const PAGES = [
        400 => ['permintaan_rusak', 'error_400'],
        403 => ['ditolak', 'error_403'],
        404 => ['tidak_ditemukan', 'error_404'],
        429 => ['terlalu_sering', 'error_429'],
    ];

    public function __construct(private readonly Exceptions $config)
    {
    }

    public function handle(
        Throwable $exception,
        RequestInterface $request,
        ResponseInterface $response,
        int $statusCode,
        int $exitCode,
    ): void {
        if ($statusCode === 400) {
            // GAL-02: the message of CI4's SecurityException holds the rejected value, which may be a password.
            log_message('warning', 'Bad request rejected: ' . $exception::class);
        }

        if (! $request instanceof IncomingRequest || ($statusCode >= 500 && self::displayErrors())) {
            (new ExceptionHandler($this->config))->handle($exception, $request, $response, $statusCode, $exitCode);

            return;
        }

        self::prepare($statusCode, $request, $response);
        // Security headers (docs/12 SEC-35): the lead wires Keamanan::applyHeaders($request, $response) here.
        $response->send();

        if (ENVIRONMENT !== 'testing') {
            // @codeCoverageIgnoreStart
            exit($exitCode);
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Fills the response with the error page or JSON code for this status.
     * Also used by the 404 override controller (App\Controllers\Galat).
     */
    public static function prepare(int $statusCode, IncomingRequest $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $response->setStatusCode($statusCode);
        } catch (HTTPException) {
            // Exception codes that are not HTTP statuses.
            $statusCode = 500;
            $response->setStatusCode($statusCode);
        }

        [$kode, $view] = self::PAGES[$statusCode] ?? ['galat_server', 'production'];
        $kodeLaporan   = RequestId::reportCode();
        // 403 text differs for students; until sessions exist (FASE-01) the /portal prefix tells them apart.
        $siswa = explode('/', $request->getPath())[0] === 'portal';

        $response->noCache(); // GAL-13; replaces any cache header a controller set before the error

        if ($request->isAJAX()) {
            return $response->setJSON(['kode' => $kode, 'pesan' => self::message($statusCode, $siswa, $kodeLaporan)]);
        }

        return $response->setContentType('text/html')->setBody(view('errors/html/' . $view, ['siswa' => $siswa, 'kodeLaporan' => $kodeLaporan]));
    }

    /**
     * Screen text of docs/08 UI-56 for a status.
     */
    public static function message(int $statusCode, bool $siswa, string $kodeLaporan): string
    {
        return match ($statusCode) {
            400     => lang('Galat.permintaanRusak'),
            403     => lang($siswa ? 'Galat.diLuarHakSiswa' : 'Galat.diLuarHak'),
            404     => lang('Galat.tidakDitemukan'),
            429     => lang('Galat.terlaluSering'),
            default => lang('Galat.gangguan', [$kodeLaporan]),
        };
    }

    private static function displayErrors(): bool
    {
        return in_array(strtolower((string) ini_get('display_errors')), ['1', 'true', 'on', 'yes'], true);
    }
}
