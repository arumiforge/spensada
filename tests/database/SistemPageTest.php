<?php

use App\Services\Akun\DiLuarHak;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;
use Tests\Support\AkunTrait;

/**
 * System check page (docs/09 HAL-AKN-07, docs/11 GAL-22, L01-07).
 *
 * @internal
 */
final class SistemPageTest extends CIUnitTestCase
{
    use AkunTrait;
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $refresh   = true;
    protected $namespace = 'App';

    /** A date with no real log file, so only the files written here count. */
    private const TODAY = '2031-03-02';

    private const LOGS = [
        '2031-03-02' => "ERROR - 2031-03-02 07:00:00 --> rahasia-hari-ini\nStack trace:\nERROR - x\nINFO - y\n",
        '2031-03-01' => "CRITICAL - 2031-03-01 07:00:00 --> rahasia-kemarin\nERROR - z\n",
    ];

    protected function setUp(): void
    {
        Services::resetSingle('routes');
        Services::resetSingle('router');
        Services::resetSingle('request');
        Services::resetSingle('response');
        parent::setUp();
        Time::setTestNow(self::TODAY . ' 09:00:00', 'Asia/Jakarta');
    }

    protected function tearDown(): void
    {
        foreach (array_keys(self::LOGS) as $date) {
            @unlink(WRITEPATH . "logs/log-{$date}.log");
        }
        parent::tearDown();
    }

    public function testAdminSeesEverySection(): void
    {
        foreach (self::LOGS as $date => $content) {
            file_put_contents(WRITEPATH . "logs/log-{$date}.log", $content);
        }

        $result = $this->withSession($this->sesiAkun($this->buatAkun([], ['admin'])))->get('panel/sistem');

        $result->assertOK();
        foreach (['Pemeriksaan sistem', 'Server', 'Versi PHP', 'Aplikasi', 'Versi aplikasi', config('Spensada')->versi, 'CI_ENVIRONMENT', 'Log aplikasi', 'Belum tersedia', 'Perlu tindakan'] as $text) {
            $result->assertSee($text);
        }
        $result->assertSee('Log hari ini (2 Mar 2031)');
        $result->assertSee('0 baris critical, 2 baris error.');
        $result->assertSee('Log kemarin (1 Mar 2031)');
        $result->assertSee('1 baris critical, 1 baris error.');
        $result->assertDontSee('rahasia');
    }

    public function testStaffWithoutAdminIsRefused(): void
    {
        $this->expectException(DiLuarHak::class);

        $this->withSession($this->sesiAkun($this->buatAkun([], ['guru_piket'])))->get('panel/sistem');
    }
}
