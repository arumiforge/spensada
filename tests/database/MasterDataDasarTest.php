<?php

use App\Libraries\AkunAktif;
use App\Services\Akun\Peran;
use App\Services\MasterData\LogDataSiswa;
use App\Services\MasterData\Rujukan;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\AkunTrait;
use Tests\Support\MasterDataTrait;

/**
 * FASE-02 base (docs/15 L02-01): master data tables, derived unique keys
 * (docs/06 DB-10), shared lookups, and the wali kelas role (docs/02 §3).
 *
 * @internal
 */
final class MasterDataDasarTest extends CIUnitTestCase
{
    use AkunTrait;
    use DatabaseTestTrait;
    use MasterDataTrait;

    protected $refresh   = true;
    protected $namespace = 'App';

    protected function setUp(): void
    {
        parent::setUp();
        Time::setTestNow('2026-10-13 08:00:00', 'Asia/Jakarta');
    }

    protected function tearDown(): void
    {
        Time::setTestNow();
        parent::tearDown();
    }

    public function testOnlyOneSchoolYearIsActive(): void
    {
        $this->buatTahunAjaran();
        $this->buatTahunAjaran(['nama' => '2025/2026', 'tanggal_mulai' => '2025-07-14', 'tanggal_selesai' => '2026-06-30', 'aktif' => 0]);

        $this->expectException(DatabaseException::class);
        $this->db->table('tahun_ajaran')->where('nama', '2025/2026')->update(['aktif' => 1]);
    }

    public function testOnlyOneOpenActivePeriodPerStudent(): void
    {
        $siswa = $this->buatSiswa();
        // A closed period next to the open one is fine.
        $this->db->table('masa_aktif')->insert(['siswa_id' => $siswa['id'], 'tanggal_mulai' => '2025-07-14', 'tanggal_selesai' => '2025-12-01', 'dibatalkan' => 0, 'created_at' => '2026-10-13 08:00:00', 'updated_at' => '2026-10-13 08:00:00']);

        $this->expectException(DatabaseException::class);
        $this->db->table('masa_aktif')->insert(['siswa_id' => $siswa['id'], 'tanggal_mulai' => '2026-09-01', 'dibatalkan' => 0, 'created_at' => '2026-10-13 08:00:00', 'updated_at' => '2026-10-13 08:00:00']);
    }

    public function testAccountForeignKeyToStudent(): void
    {
        $this->expectException(DatabaseException::class);
        $this->buatAkun(['jenis' => 'siswa', 'username' => '0012345678', 'siswa_id' => 999_999, 'status' => 'belum_aktif']);
    }

    public function testRombelOfStudentUsesSchoolYearEndForOpenPlacement(): void
    {
        $ta     = $this->buatTahunAjaran();
        $tujuhA = $this->buatRombel(['tahun_ajaran_id' => $ta['id']]);
        $tujuhB = $this->buatRombel(['tahun_ajaran_id' => $ta['id'], 'nama' => '7B']);
        $siswa  = $this->buatSiswa();
        $this->tempatkan($siswa['id'], $tujuhA['id'], '2026-07-13', '2026-09-30');
        $this->tempatkan($siswa['id'], $tujuhB['id'], '2026-10-01');

        $rujukan = new Rujukan();
        $this->assertSame('7A', $rujukan->rombelSiswa($siswa['id'], '2026-09-30')['nama']);
        $this->assertSame('7B', $rujukan->rombelSiswa($siswa['id'], '2027-06-30')['nama']);
        $this->assertNull($rujukan->rombelSiswa($siswa['id'], '2027-07-01'));
        $this->assertNull($rujukan->rombelSiswa($siswa['id'], '2026-07-12'));
    }

    public function testStudentStatus(): void
    {
        $rujukan = new Rujukan();
        $aktif   = $this->buatSiswa();
        $akan    = $this->buatSiswa(['tanggal_mulai' => '2026-11-02']);
        $keluar  = $this->buatSiswa();
        $this->db->table('masa_aktif')->where('siswa_id', $keluar['id'])->update(['tanggal_selesai' => '2026-10-12', 'alasan_nonaktif' => 'pindah_sekolah']);
        $batal = $this->buatSiswa();
        $this->db->table('masa_aktif')->where('siswa_id', $batal['id'])->update(['tanggal_selesai' => '2026-07-13', 'dibatalkan' => 1, 'alasan_nonaktif' => 'salah_input']);

        $this->assertSame('aktif', $rujukan->statusSiswa($aktif['id'], '2026-10-13'));
        $this->assertSame('akan_aktif', $rujukan->statusSiswa($akan['id'], '2026-10-13'));
        $this->assertSame('nonaktif', $rujukan->statusSiswa($keluar['id'], '2026-10-13'));
        $this->assertSame('aktif', $rujukan->statusSiswa($keluar['id'], '2026-10-12'));
        $this->assertSame('nonaktif', $rujukan->statusSiswa($batal['id'], '2026-07-13'));
    }

    public function testWaliKelasRoleComesFromActiveSchoolYearOnly(): void
    {
        $guru = $this->buatAkun([], ['guru_piket']);
        $lama = $this->buatTahunAjaran(['nama' => '2025/2026', 'tanggal_mulai' => '2025-07-14', 'tanggal_selesai' => '2026-06-30', 'aktif' => 0]);
        $this->buatRombel(['tahun_ajaran_id' => $lama['id'], 'wali_kelas_id' => $guru['id']]);

        $this->assertSame(['staf', 'guru_piket'], (new Peran())->untuk($guru));

        $ta     = $this->buatTahunAjaran();
        $rombel = $this->buatRombel(['tahun_ajaran_id' => $ta['id'], 'wali_kelas_id' => $guru['id']]);

        $this->assertSame(['staf', 'guru_piket', 'wali_kelas'], (new Peran())->untuk($guru));
        $this->assertSame([(int) $rombel['id']], (new Rujukan())->rombelWaliKelas($guru['id']));
    }

    public function testScopeFollowsTodaysPlacement(): void
    {
        $ta     = $this->buatTahunAjaran();
        $wali   = $this->buatAkun();
        $tujuhA = $this->buatRombel(['tahun_ajaran_id' => $ta['id'], 'wali_kelas_id' => $wali['id']]);
        $tujuhB = $this->buatRombel(['tahun_ajaran_id' => $ta['id'], 'nama' => '7B']);
        $miliknya = $this->buatSiswa(['rombel_id' => $tujuhA['id']]);
        $lain     = $this->buatSiswa(['rombel_id' => $tujuhB['id']]);

        $aktif = new AkunAktif();
        $aktif->set($wali, (new Peran())->untuk($wali));

        $this->assertTrue($aktif->boleh('HA-MD-06', $aktif->dataSiswa($miliknya['id'])));
        $this->assertFalse($aktif->boleh('HA-MD-06', $aktif->dataSiswa($lain['id'])));
        $this->assertFalse($aktif->boleh('HA-MD-03', $aktif->dataSiswa($miliknya['id'])));

        $siswaAkun = $this->db->table('akun')->where('id', $miliknya['akun_id'])->get()->getRowArray();
        $portal    = new AkunAktif();
        $portal->set($siswaAkun, ['siswa']);
        $this->assertSame((int) $tujuhA['id'], $portal->aktor()['rombel_id']);
        $this->assertTrue($portal->boleh('HA-MD-05', ['siswa_id' => (int) $miliknya['id']]));
        $this->assertFalse($portal->boleh('HA-MD-05', ['siswa_id' => (int) $lain['id']]));
    }

    public function testLogDataSiswaStoresJsonAndRejectsUnknownKinds(): void
    {
        $siswa = $this->buatSiswa();
        $id    = (new LogDataSiswa())->catat($siswa['id'], 'wa_diubah', ['wa_ortu' => null], ['wa_ortu' => '6281234567890'], null);

        $row = $this->db->table('log_data_siswa')->where('id', $id)->get()->getRowArray();
        $this->assertNull($row['data_lama'] === null ? null : json_decode($row['data_lama'], true)['wa_ortu']);
        $this->assertSame('6281234567890', json_decode($row['data_baru'], true)['wa_ortu']);

        $this->expectException(InvalidArgumentException::class);
        (new LogDataSiswa())->catat($siswa['id'], 'tidak_ada', null, null, null);
    }
}
