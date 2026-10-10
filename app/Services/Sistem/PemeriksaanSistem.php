<?php

namespace App\Services\Sistem;

use CodeIgniter\Database\ConnectionInterface;
use Config\App;
use Config\Database;
use Throwable;

/**
 * System checks behind `php spark aplikasi:cek` and, from L01-07, the
 * HAL-AKN-07 page (docs/07 ARS-03, ARS-45, ARS-57).
 *
 * Results describe the PHP process that runs them: CLI for the command,
 * php-cgi for the web page.
 */
class PemeriksaanSistem
{
    public const BAIK           = 'baik';
    public const PERLU_TINDAKAN = 'perlu_tindakan';
    public const BELUM_TERSEDIA = 'belum_tersedia';

    public const LABEL = [
        self::BAIK           => 'Baik',
        self::PERLU_TINDAKAN => 'Perlu tindakan',
        self::BELUM_TERSEDIA => 'Belum tersedia',
    ];

    private const PHP_MIN   = '8.3.0';
    private const MYSQL_MIN = '8.4.0';
    private const ZONE      = 'Asia/Jakarta';
    private const COLLATION = 'utf8mb4_general_ci';

    /**
     * docs/07 ARS-03, by extension_loaded() name.
     */
    private const EXTENSIONS = [
        'intl', 'mbstring', 'mysqli', 'mysqlnd', 'openssl', 'curl', 'gd', 'exif', 'zip', 'fileinfo',
        'dom', 'xml', 'xmlreader', 'xmlwriter', 'simplexml', 'libxml', 'iconv', 'ctype', 'filter', 'zlib',
        'Zend OPcache',
    ];

    public function __construct(private ?ConnectionInterface $db = null)
    {
    }

    /**
     * @return list<array{butir: string, status: string, keterangan: string}>
     */
    public function run(): array
    {
        return [
            $this->php(),
            ...$this->mysql(),
            $this->extensions(),
            $this->item('Zona waktu PHP', date_default_timezone_get() === self::ZONE, date_default_timezone_get(), date_default_timezone_get() . '. Seharusnya Asia/Jakarta. Atur appTimezone di app/Config/App.php.'),
            $this->writable(),
            $this->baseUrl(),
            // Filled in by L05-12 (docs/15).
            $this->notYet('Cron terakhir berjalan'),
            $this->notYet('Antrean hitung ulang'),
            $this->notYet('Awal status (status_mulai)'),
        ];
    }

    /**
     * PHP settings of the web process (docs/07 ARS-04), shown on the system
     * check page only: the CLI reads php.ini, not the php-web.ini of php-cgi.
     *
     * @return list<array{butir: string, status: string, keterangan: string}>
     */
    public function phpWeb(): array
    {
        $checks = [];

        foreach (['upload_max_filesize' => '100M', 'post_max_size' => '110M', 'memory_limit' => '256M'] as $key => $min) {
            $value    = (string) ini_get($key);
            $checks[] = $this->item($key, $value === '-1' || self::bytes($value) >= self::bytes($min), $value, "{$value}. Paling sedikit {$min}. Atur di php-web.ini.");
        }

        $opcache  = extension_loaded('Zend OPcache') && filter_var(ini_get('opcache.enable'), FILTER_VALIDATE_BOOLEAN);
        $checks[] = $this->item('opcache.enable', $opcache, 'aktif', 'Belum aktif. Atur opcache.enable=1 di php-web.ini.');

        return $checks;
    }

    /**
     * Bytes of a php.ini size such as `100M`.
     */
    private static function bytes(string $size): int
    {
        $number = (int) $size;

        return match (strtoupper(substr(trim($size), -1))) {
            'G'     => $number * 1024 ** 3,
            'M'     => $number * 1024 ** 2,
            'K'     => $number * 1024,
            default => $number,
        };
    }

    private function php(): array
    {
        return $this->item('Versi PHP', version_compare(PHP_VERSION, self::PHP_MIN, '>='), PHP_VERSION, PHP_VERSION . '. Perlu PHP 8.3 atau lebih baru.');
    }

    private function mysql(): array
    {
        $names = ['Versi MySQL', 'Zona waktu MySQL', 'Collation koneksi', 'sql_mode'];

        try {
            $row = ($this->db ?? Database::connect())
                ->query('SELECT VERSION() AS versi, @@session.time_zone AS zona, @@collation_connection AS collation, @@session.sql_mode AS sql_mode')
                ->getRow();
        } catch (Throwable $e) {
            log_message('error', 'System check could not reach the database: {message}', ['message' => $e->getMessage()]);

            return array_map(
                fn (string $name) => $this->item($name, false, '', 'Database tidak dapat dihubungi. Pastikan MySQL berjalan dan pengaturan database di .env benar.'),
                $names,
            );
        }

        // MariaDB reports a 10.x/11.x version but is not a target (docs/07 ARS-02).
        $versionOk = stripos($row->versi, 'mariadb') === false && version_compare($row->versi, self::MYSQL_MIN, '>=');

        return [
            $this->item($names[0], $versionOk, $row->versi, $row->versi . '. Perlu MySQL 8.4 atau lebih baru.'),
            $this->item($names[1], $row->zona === '+07:00', $row->zona, $row->zona . '. Seharusnya +07:00. Pastikan DBDriver di .env bernilai App\Database\MySQLi.'),
            $this->item($names[2], $row->collation === self::COLLATION, $row->collation, $row->collation . '. Seharusnya utf8mb4_general_ci. Pastikan DBDriver di .env bernilai App\Database\MySQLi.'),
            $this->item($names[3], in_array('STRICT_ALL_TABLES', explode(',', $row->sql_mode), true), 'mode strict aktif', 'Mode strict belum aktif. Pastikan strictOn bernilai true di pengaturan database.'),
        ];
    }

    private function extensions(): array
    {
        $missing = array_filter(self::EXTENSIONS, static fn (string $ext) => ! extension_loaded($ext));
        $names   = array_map(static fn (string $ext) => $ext === 'Zend OPcache' ? 'opcache' : $ext, $missing);

        return $this->item('Ekstensi PHP', $missing === [], 'semua aktif', 'Belum aktif: ' . implode(', ', $names) . '. Aktifkan lewat menu Laragon.');
    }

    private function writable(): array
    {
        $folders = [rtrim(WRITEPATH, '\\/'), ...(glob(WRITEPATH . '*', GLOB_ONLYDIR) ?: [])];
        $blocked = array_filter($folders, static fn (string $dir) => ! is_really_writable($dir));
        $names   = array_map(static fn (string $dir) => clean_path($dir), $blocked);

        return $this->item('Hak tulis writable/', $blocked === [], 'dapat ditulis', 'Belum dapat ditulis: ' . implode(', ', $names) . '. Beri hak tulis untuk akun yang menjalankan PHP.');
    }

    private function baseUrl(): array
    {
        $url = config(App::class)->baseURL;

        return $this->item('baseURL HTTPS', str_starts_with($url, 'https://'), $url, $url . '. Alamat aplikasi harus diawali https://. Atur app.baseURL di .env.');
    }

    /**
     * @return array{butir: string, status: string, keterangan: string}
     */
    private function item(string $name, bool $ok, string $whenOk, string $whenNotOk): array
    {
        return [
            'butir'      => $name,
            'status'     => $ok ? self::BAIK : self::PERLU_TINDAKAN,
            'keterangan' => $ok ? $whenOk : $whenNotOk,
        ];
    }

    private function notYet(string $name): array
    {
        return ['butir' => $name, 'status' => self::BELUM_TERSEDIA, 'keterangan' => 'Pemeriksaan ini ditambahkan di tahap berikutnya.'];
    }
}
