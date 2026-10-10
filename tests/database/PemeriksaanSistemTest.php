<?php

use App\Commands\AplikasiCek;
use App\Services\Sistem\PemeriksaanSistem;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\StreamFilterTrait;
use Config\Database;

/**
 * `aplikasi:cek` and its service (docs/07 ARS-57, L00-07).
 *
 * @internal
 */
final class PemeriksaanSistemTest extends CIUnitTestCase
{
    use StreamFilterTrait;

    public function testReportsEveryCheck(): void
    {
        $results = array_column((new PemeriksaanSistem())->run(), null, 'butir');

        $this->assertSame(PemeriksaanSistem::BAIK, $results['Zona waktu MySQL']['status']);
        $this->assertSame(PemeriksaanSistem::BAIK, $results['Collation koneksi']['status']);
        $this->assertSame(PemeriksaanSistem::BAIK, $results['sql_mode']['status']);
        $this->assertSame(PemeriksaanSistem::BAIK, $results['Zona waktu PHP']['status']);

        // phpunit.dist.xml sets an http:// baseURL.
        $this->assertSame(PemeriksaanSistem::PERLU_TINDAKAN, $results['baseURL HTTPS']['status']);

        foreach (['Cron terakhir berjalan', 'Antrean hitung ulang', 'Awal status (status_mulai)'] as $name) {
            $this->assertSame(PemeriksaanSistem::BELUM_TERSEDIA, $results[$name]['status']);
        }
    }

    public function testUnreachableDatabaseIsReportedNotThrown(): void
    {
        $config = ['hostname' => '127.0.0.1', 'port' => 1, 'username' => 'x', 'password' => 'x', 'database' => 'x'] + config(Database::class)->tests;
        $results = array_column((new PemeriksaanSistem(Database::connect($config, false)))->run(), null, 'butir');

        foreach (['Versi MySQL', 'Zona waktu MySQL', 'Collation koneksi', 'sql_mode'] as $name) {
            $this->assertSame(PemeriksaanSistem::PERLU_TINDAKAN, $results[$name]['status']);
        }
    }

    public function testCommandFailsWhenACheckNeedsAction(): void
    {
        $exit = (new AplikasiCek(service('logger'), service('commands')))->run([]);

        $this->assertSame(EXIT_ERROR, $exit);
        $this->assertStringContainsString('perlu tindakan', $this->getStreamFilterBuffer());
    }
}
