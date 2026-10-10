<?php

namespace App\Filters;

use App\Models\AkunModel;
use App\Services\Akun\Kredensial;
use App\Services\Akun\Peran;
use App\Services\Sistem\Jam;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Filter `sesi`: the user must be logged in (docs/07 ARS-13, ARS-47).
 *
 * Loads the account on every request, then checks status, credential stamp,
 * the 8-hour idle and 7-day limits for staff and students (docs/12 SEC-12),
 * and the station login ID (SEC-19). On success the account and its roles
 * are in service('akunAktif').
 *
 * Session keys written at login (Services\Akun\Login): `akun_id`, `jenis`,
 * `login_at` and `aktif_at` (Unix seconds), `cap` (Kredensial::cap()), and
 * `login_stasiun_id` for stations.
 */
class Sesi implements FilterInterface
{
    use BackgroundResponse;

    /** Session keys owned by login; removed when a session ends. */
    public const KEYS = ['akun_id', 'jenis', 'login_at', 'aktif_at', 'cap', 'login_stasiun_id', 'tanpa_password_lama', 'salah_password_lama'];

    private const IDLE_LIMIT  = 8 * 3600;
    private const LOGIN_LIMIT = 7 * 86400;

    public function before(RequestInterface $request, $arguments = null)
    {
        service('akunAktif')->clear();

        if (session('akun_id') === null) {
            return $this->reject($request, false);
        }

        $akun = model(AkunModel::class)->find((int) session('akun_id'));
        $now  = (new Jam())->now()->getTimestamp();

        if ($akun === null || ! $this->valid($akun, $now)) {
            return $this->reject($request, true);
        }

        if ($akun['jenis'] === 'stasiun' && $akun['login_stasiun_id'] !== session('login_stasiun_id')) {
            // FASE-06 (L06-01) rebuilds an expired station session from the station cookie (ARS-30).
            $this->end();

            return $this->jsonError(401, 'login_ulang', 'Station logged in elsewhere.', ['alasan' => 'login_berpindah'])
                ->setHeader('WWW-Authenticate', 'Spensada');
        }

        // Fragment polling is not user activity (SEC-12 item 2); update at most once a minute.
        if ($akun['jenis'] !== 'stasiun' && ! $this->isPolling($request) && $now - (int) session('aktif_at') >= 60) {
            session()->set('aktif_at', $now);
        }

        service('akunAktif')->set($akun, (new Peran())->untuk($akun));

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }

    /**
     * Ends the session data and gives the session a new ID; the session
     * itself stays open, so the next page can still read flash data.
     */
    public static function end(): void
    {
        session()->remove(self::KEYS);
        session()->regenerate(true);
    }

    /**
     * @param array<string, mixed> $akun
     */
    private function valid(array $akun, int $now): bool
    {
        $statusOk = $akun['status'] === 'aktif' || ($akun['jenis'] === 'siswa' && $akun['status'] === 'belum_aktif');

        if (! $statusOk || $akun['jenis'] !== session('jenis')
            || ! hash_equals((new Kredensial())->cap($akun['password_hash']), (string) session('cap'))) {
            return false;
        }

        // Stations follow the 90-day station login instead (ARS-30).
        return $akun['jenis'] === 'stasiun'
            || ($now - (int) session('aktif_at') <= self::IDLE_LIMIT && $now - (int) session('login_at') <= self::LOGIN_LIMIT);
    }

    /**
     * Pages go to /login and keep the address as `tujuan` (docs/09 RT-18 item 2);
     * background requests get 401 `login_ulang`.
     */
    private function reject(RequestInterface $request, bool $ended)
    {
        if ($ended) {
            $this->end();
        }

        if ($this->isBackground($request)) {
            return $this->jsonError(401, 'login_ulang', 'Login required.')
                ->setHeader('WWW-Authenticate', 'Spensada');
        }

        $isGet  = $request->getMethod() === 'GET';
        $tujuan = $isGet ? $this->ownPath((string) $request->getUri()) : $this->ownPath($request->getHeaderLine('Referer'));

        if ($tujuan !== null) {
            session()->set('tujuan', $tujuan);
        }

        // docs/11 §5.1 FS-AKN-01 E4, GAL-07
        $pesan = ! $isGet
            ? 'Sesi berakhir sebelum formulir terkirim. Login lagi, lalu kirim ulang formulir.'
            : ($ended ? 'Sesi berakhir. Login lagi untuk melanjutkan.' : null);

        $redirect = redirect()->to('/login');

        return $pesan === null ? $redirect : $redirect->with('galat', $pesan);
    }

    /**
     * Path and query of an address on this host, or null.
     */
    private function ownPath(string $url): ?string
    {
        $parts = parse_url($url);

        if ($url === '' || $parts === false || ($parts['host'] ?? null) !== parse_url(base_url(), PHP_URL_HOST)) {
            return null;
        }

        return ($parts['path'] ?? '/') . (isset($parts['query']) ? '?' . $parts['query'] : '');
    }

    private function isPolling(RequestInterface $request): bool
    {
        return $this->isBackground($request) && str_ends_with($request->getUri()->getPath(), '/fragmen');
    }
}
