<?php

use App\Database\Seeds\DataContoh;
use App\Database\Seeds\PengaturanAwal;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Table `pengaturan`, its seeders (docs/06 §6.1, docs/07 ARS-18, L00-07).
 *
 * @internal
 */
final class PengaturanTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh   = true;
    protected $namespace = 'App';

    public function testMigrationGoesDownAndUp(): void
    {
        $this->assertTrue($this->db->tableExists('pengaturan', false));

        $runner = service('migrations');
        $runner->regress(0);
        $this->assertFalse($this->db->tableExists('pengaturan', false));

        $runner->setNamespace('App')->latest();
        $this->assertTrue($this->db->tableExists('pengaturan', false));
    }

    public function testPengaturanAwalFillsEveryR1Key(): void
    {
        $this->seed(PengaturanAwal::class);

        $rows = array_column($this->db->table('pengaturan')->get()->getResultArray(), null, 'kunci');

        // docs/06 §6.1, Rilis R1; null is "Kosong".
        $expected = [
            'sekolah_nama'           => 'SMP 1 DAWE',
            'sekolah_alamat'         => 'Dawe, Kabupaten Kudus, Jawa Tengah',
            'sekolah_logo'           => null,
            'batas_mundur_hari'      => '7',
            'status_dibangun_sampai' => null,
            'status_mulai'           => null,
            'cron_terakhir_at'       => null,
            'kiosk_pin'              => null,
        ];
        $actual = array_column($rows, 'nilai', 'kunci');
        ksort($expected);
        ksort($actual);
        $this->assertSame($expected, $actual);

        foreach ($rows as $row) {
            $this->assertNotEmpty($row['updated_at']);
            $this->assertNull($row['diubah_oleh']);
        }
    }

    public function testPengaturanAwalKeepsExistingValues(): void
    {
        $this->seed(PengaturanAwal::class);
        $this->db->table('pengaturan')->where('kunci', 'batas_mundur_hari')->update(['nilai' => '3']);

        $this->seed(PengaturanAwal::class);

        $this->seeInDatabase('pengaturan', ['kunci' => 'batas_mundur_hari', 'nilai' => '3']);
        $this->seeNumRecords(8, 'pengaturan', []);
    }

    public function testDataContohRefusesProduction(): void
    {
        $previous                = $_ENV['CI_ENVIRONMENT'] ?? null;
        $_ENV['CI_ENVIRONMENT'] = 'production';

        try {
            $this->expectException(RuntimeException::class);
            $this->seed(DataContoh::class);
        } finally {
            if ($previous === null) {
                unset($_ENV['CI_ENVIRONMENT']);
            } else {
                $_ENV['CI_ENVIRONMENT'] = $previous;
            }
        }
    }

    public function testDataContohRunsOutsideProduction(): void
    {
        $this->seed(DataContoh::class);

        $this->assertSame('testing', ENVIRONMENT);
    }
}
