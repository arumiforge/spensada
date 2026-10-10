<?php

use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;
use Config\Services;
use Tests\Support\AkunTrait;
use Tests\Support\MasterDataTrait;

/**
 * School years and semesters (docs/04 FS-MD-02, AC-MD-02-*, docs/09
 * HAL-MD-02, RT-07, RT-12, docs/11 §5.2).
 *
 * @internal
 */
final class TahunAjaranTest extends CIUnitTestCase
{
    use AkunTrait;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use MasterDataTrait;

    protected $refresh   = true;
    protected $namespace = 'App';

    private const DAFTAR = 'https://example.com/panel/tahun-ajaran';

    /** @var array<string, mixed> */
    private array $admin;

    protected function setUp(): void
    {
        Services::resetSingle('routes');
        Services::resetSingle('router');
        Services::resetSingle('request');
        Services::resetSingle('response');
        parent::setUp();
        Time::setTestNow('2026-10-13 07:00:00', 'Asia/Jakarta');
        $this->admin = $this->buatAkun(['username' => 'admin.tu', 'nama' => 'Admin Tata Usaha'], ['admin']);
    }

    protected function tearDown(): void
    {
        Time::setTestNow();
        parent::tearDown();
    }

    public function testOnlyAdminReachesThePages(): void
    {
        // HA-MD-01
        $piket = $this->buatAkun(['username' => 'piket'], ['guru_piket']);
        $ta    = $this->buatTahunAjaran();

        foreach (['GET panel/tahun-ajaran', 'GET panel/tahun-ajaran/tambah', "GET panel/tahun-ajaran/{$ta['id']}/aktifkan", 'POST panel/tahun-ajaran'] as $permintaan) {
            [$method, $uri] = explode(' ', $permintaan);
            $this->resetRouter();
            $result = $this->withSession($this->sesiAkun($piket))
                ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json', 'X-CSRF-TOKEN' => service('security')->getHash()])
                ->call($method, $uri, $this->isian());

            $result->assertStatus(403);
        }

        $this->seeNumRecords(1, 'tahun_ajaran', []);
    }

    public function testCreateSavesYearWithBothSemesters(): void
    {
        $this->kirim('GET', 'panel/tahun-ajaran')->assertSee('Belum ada tahun ajaran.');
        $this->kirim('GET', 'panel/tahun-ajaran/tambah')->assertSee('Semester genap');

        $result = $this->kirim('POST', 'panel/tahun-ajaran', $this->isian(['nama' => ' 2026/2027 ']));

        $result->assertStatus(303);
        $result->assertRedirectTo(self::DAFTAR);
        $this->assertSame('Tahun ajaran 2026/2027 disimpan.', session('sukses'));

        $ta = $this->db->table('tahun_ajaran')->where('nama', '2026/2027')->get()->getRowArray();
        $this->assertSame(['2026-07-13', '2027-06-30', '0'], [$ta['tanggal_mulai'], $ta['tanggal_selesai'], (string) $ta['aktif']]);
        $this->seeInDatabase('semester', ['tahun_ajaran_id' => $ta['id'], 'jenis' => 'ganjil', 'tanggal_mulai' => '2026-07-13', 'tanggal_selesai' => '2026-12-19']);
        $this->seeInDatabase('semester', ['tahun_ajaran_id' => $ta['id'], 'jenis' => 'genap', 'tanggal_mulai' => '2027-01-04', 'tanggal_selesai' => '2027-06-30']);
        $this->assertSame('2026/2027', $this->logData()['nama']);

        $daftar = $this->kirim('GET', 'panel/tahun-ajaran');
        $daftar->assertSee('2026/2027');
        $daftar->assertSee('19 Des 2026');
        $daftar->assertSee('Belum ada tahun ajaran aktif');
    }

    public function testFieldErrorsAndSemesterOrder(): void
    {
        // AC-MD-02-03: semester genap starts before semester ganjil ends.
        $result = $this->kirim('POST', 'panel/tahun-ajaran', $this->isian([
            'nama'           => '2026-2027',
            'ganjil_mulai'   => '2026-07-01',
            'genap_mulai'    => '2026-12-01',
            'genap_selesai'  => '2027-02-30',
        ]));

        $result->assertStatus(303);
        $result->assertRedirectTo('https://example.com/panel/tahun-ajaran/tambah');
        $this->assertSame([
            'genap_selesai' => 'Selesai semester genap tidak valid.',
            'nama'          => 'Tulis tahun ajaran seperti 2026/2027.',
            'ganjil_mulai'  => 'Semester ganjil harus berada di dalam tahun ajaran.',
        ], session('_ci_validation_errors'));

        $this->kirim('POST', 'panel/tahun-ajaran', $this->isian(['genap_mulai' => '2026-12-15']));
        $this->assertSame(['genap_mulai' => 'Semester genap harus setelah semester ganjil dan berada di dalam tahun ajaran.'], session('_ci_validation_errors'));

        $this->kirim('POST', 'panel/tahun-ajaran', $this->isian(['tanggal_selesai' => '2026-07-01', 'ganjil_mulai' => '']));
        $this->assertSame([
            'ganjil_mulai'    => 'Mulai semester ganjil wajib diisi.',
            'tanggal_selesai' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ], session('_ci_validation_errors'));

        $this->seeNumRecords(0, 'tahun_ajaran', []);
    }

    public function testRangeMayNotOverlapAnotherYear(): void
    {
        $this->buatTahunAjaran();

        $this->kirim('POST', 'panel/tahun-ajaran', $this->isian(['nama' => '2027/2028', 'tanggal_mulai' => '2027-06-01', 'ganjil_mulai' => '2027-07-12', 'ganjil_selesai' => '2027-12-18', 'genap_mulai' => '2028-01-03', 'tanggal_selesai' => '2028-06-30', 'genap_selesai' => '2028-06-30']));
        $this->assertSame(['tanggal_mulai' => 'Tanggal tahun ajaran tumpang tindih dengan 2026/2027.'], session('_ci_validation_errors'));

        $this->kirim('POST', 'panel/tahun-ajaran', $this->isian(['tanggal_mulai' => '2027-07-12', 'ganjil_mulai' => '2027-07-12', 'ganjil_selesai' => '2027-12-18', 'genap_mulai' => '2028-01-03', 'tanggal_selesai' => '2028-06-30', 'genap_selesai' => '2028-06-30']));
        $this->assertSame(['nama' => 'Tahun ajaran 2026/2027 sudah ada.'], session('_ci_validation_errors'));

        $this->seeNumRecords(1, 'tahun_ajaran', []);
    }

    public function testActivateSwitchesExactlyOneActiveYear(): void
    {
        // AC-MD-02-01
        $lama = $this->buatTahunAjaran();
        $baru = $this->buatTahunAjaran(['nama' => '2027/2028', 'tanggal_mulai' => '2027-07-12', 'tanggal_selesai' => '2028-06-30', 'aktif' => 0]);
        Time::setTestNow('2027-07-12 07:00:00', 'Asia/Jakarta');

        $form = $this->kirim('GET', "panel/tahun-ajaran/{$baru['id']}/aktifkan");
        $form->assertOK();
        $form->assertSee('Hak wali kelas berpindah');
        $form->assertSee('rekap dan riwayatnya tetap dapat dibuka');

        $result = $this->kirim('POST', "panel/tahun-ajaran/{$baru['id']}/aktifkan");
        $result->assertRedirectTo(self::DAFTAR);
        $this->assertSame('Tahun ajaran 2027/2028 sekarang aktif.', session('sukses'));
        $this->assertSame([(string) $baru['id']], array_column($this->db->table('tahun_ajaran')->select('id')->where('aktif', 1)->get()->getResultArray(), 'id'));
        $this->seeInDatabase('tahun_ajaran', ['id' => $lama['id'], 'aktif' => 0]);
        // MySQL JSON does not keep key order.
        $this->assertEquals(['tahun_ajaran_id' => (int) $baru['id'], 'nama' => '2027/2028', 'diaktifkan' => true, 'sebelumnya' => '2026/2027'], $this->logData());

        // A second click changes nothing and logs nothing.
        $this->kirim('POST', "panel/tahun-ajaran/{$baru['id']}/aktifkan")->assertRedirectTo(self::DAFTAR);
        $this->seeNumRecords(1, 'log_aktivitas', ['jenis' => 'tahun_ajaran_diubah']);
    }

    public function testActivateOutsideTheYearIsRefused(): void
    {
        // FS-MD-02 E3
        $this->buatTahunAjaran();
        $belum   = $this->buatTahunAjaran(['nama' => '2027/2028', 'tanggal_mulai' => '2027-07-12', 'tanggal_selesai' => '2028-06-30', 'aktif' => 0]);
        $selesai = $this->buatTahunAjaran(['nama' => '2025/2026', 'tanggal_mulai' => '2025-07-14', 'tanggal_selesai' => '2026-06-30', 'aktif' => 0]);

        $this->kirim('GET', "panel/tahun-ajaran/{$belum['id']}/aktifkan")->assertRedirectTo(self::DAFTAR);
        $this->assertSame('Tahun ajaran 2027/2028 belum dimulai.', session('galat'));

        $this->kirim('POST', "panel/tahun-ajaran/{$selesai['id']}/aktifkan")->assertRedirectTo(self::DAFTAR);
        $this->assertSame('Tahun ajaran 2025/2026 sudah selesai.', session('galat'));

        $this->seeInDatabase('tahun_ajaran', ['nama' => '2026/2027', 'aktif' => 1]);
        $this->seeNumRecords(0, 'log_aktivitas', ['jenis' => 'tahun_ajaran_diubah']);
    }

    public function testDeleteOnlyWithoutRombel(): void
    {
        // FS-MD-02 item 4, E4
        $denganKelas = $this->buatTahunAjaran();
        $this->buatRombel(['tahun_ajaran_id' => $denganKelas['id']]);
        $kosong = $this->buatTahunAjaran(['nama' => '2027/2028', 'tanggal_mulai' => '2027-07-12', 'tanggal_selesai' => '2028-06-30', 'aktif' => 0]);

        $daftar = $this->kirim('GET', 'panel/tahun-ajaran');
        $this->assertStringNotContainsString("tahun-ajaran/{$denganKelas['id']}/hapus", $daftar->response()->getBody());
        $daftar->assertSee("tahun-ajaran/{$kosong['id']}/hapus");

        $this->kirim('GET', "panel/tahun-ajaran/{$denganKelas['id']}/hapus")->assertRedirectTo(self::DAFTAR);
        $this->assertSame('Tahun ajaran ini sudah memiliki kelas, sehingga tidak dapat dihapus.', session('galat'));
        $this->kirim('POST', "panel/tahun-ajaran/{$denganKelas['id']}", ['_method' => 'DELETE', 'versi' => $denganKelas['updated_at']]);
        $this->assertSame('Tahun ajaran ini sudah memiliki kelas, sehingga tidak dapat dihapus.', session('galat'));
        $this->seeInDatabase('tahun_ajaran', ['id' => $denganKelas['id']]);

        $form = $this->kirim('GET', "panel/tahun-ajaran/{$kosong['id']}/hapus");
        $form->assertOK();
        $this->assertStringContainsString('name="_method" value="DELETE"', $form->response()->getBody());

        $result = $this->kirim('POST', "panel/tahun-ajaran/{$kosong['id']}", ['_method' => 'DELETE', 'versi' => $kosong['updated_at']]);
        $result->assertRedirectTo(self::DAFTAR);
        $this->assertSame('Tahun ajaran 2027/2028 dihapus.', session('sukses'));
        $this->dontSeeInDatabase('tahun_ajaran', ['id' => $kosong['id']]);
        $this->dontSeeInDatabase('semester', ['tahun_ajaran_id' => $kosong['id']]);
    }

    public function testSemesterChangeThatRemovesDatesAsksFirst(): void
    {
        // FS-MD-02 item 3, RT-07. Trait year: ganjil 13 Jul–12 Dec 2026, genap from 13 Dec 2026.
        $ta    = $this->buatTahunAjaran();
        $isian = $this->isian(['_method' => 'PATCH', 'versi' => $ta['updated_at']]);

        $form = $this->kirim('GET', "panel/tahun-ajaran/{$ta['id']}/ubah");
        $form->assertOK();
        $this->assertStringContainsString('name="versi" value="' . $ta['updated_at'] . '"', html_entity_decode($form->response()->getBody()));
        $this->assertStringContainsString('value="2026-12-12"', $form->response()->getBody());

        $periksa = $this->kirim('POST', "panel/tahun-ajaran/{$ta['id']}", $isian);
        $periksa->assertOK();
        $periksa->assertSee('mengeluarkan 15 tanggal dari semester');
        $periksa->assertSee('Minggu, 20 Desember 2026 sampai Minggu, 3 Januari 2027');
        $this->assertStringContainsString('name="konfirmasi" value="1"', $periksa->response()->getBody());
        $this->seeInDatabase('semester', ['tahun_ajaran_id' => $ta['id'], 'jenis' => 'genap', 'tanggal_mulai' => '2026-12-13']);
        $this->seeNumRecords(0, 'log_aktivitas', ['jenis' => 'tahun_ajaran_diubah']);

        Time::setTestNow('2026-10-13 07:05:00', 'Asia/Jakarta');
        $result = $this->kirim('POST', "panel/tahun-ajaran/{$ta['id']}", $isian + ['konfirmasi' => '1']);
        $result->assertStatus(303);
        $result->assertRedirectTo(self::DAFTAR);
        $this->assertSame('Perubahan tahun ajaran disimpan.', session('sukses'));
        $this->seeInDatabase('semester', ['tahun_ajaran_id' => $ta['id'], 'jenis' => 'ganjil', 'tanggal_selesai' => '2026-12-19']);
        $this->seeInDatabase('semester', ['tahun_ajaran_id' => $ta['id'], 'jenis' => 'genap', 'tanggal_mulai' => '2027-01-04']);
        $this->seeInDatabase('tahun_ajaran', ['id' => $ta['id'], 'updated_at' => '2026-10-13 07:05:00']);
        $this->assertEquals(['lama' => '2026-12-13', 'baru' => '2027-01-04'], $this->logData()['genap_mulai']);

        // Adding dates back removes none, so it saves at once.
        Time::setTestNow('2026-10-13 07:06:00', 'Asia/Jakarta');
        $this->kirim('POST', "panel/tahun-ajaran/{$ta['id']}", $this->isian(['_method' => 'PATCH', 'versi' => '2026-10-13 07:05:00', 'genap_mulai' => '2026-12-20']))
            ->assertRedirectTo(self::DAFTAR);
        $this->seeInDatabase('semester', ['tahun_ajaran_id' => $ta['id'], 'jenis' => 'genap', 'tanggal_mulai' => '2026-12-20']);
    }

    public function testStaleFormIsRejected(): void
    {
        // RT-12, docs/11 GAL-05
        $ta  = $this->buatTahunAjaran(['tanggal_selesai' => '2027-06-30']);
        $tua = $ta['updated_at'];

        Time::setTestNow('2026-10-13 07:10:00', 'Asia/Jakarta');
        $this->kirim('POST', "panel/tahun-ajaran/{$ta['id']}", $this->isian(['_method' => 'PATCH', 'versi' => $tua, 'ganjil_selesai' => '2026-12-12', 'genap_mulai' => '2026-12-13']))
            ->assertRedirectTo(self::DAFTAR);

        Time::setTestNow('2026-10-13 07:20:00', 'Asia/Jakarta');
        $result = $this->kirim('POST', "panel/tahun-ajaran/{$ta['id']}", $this->isian(['_method' => 'PATCH', 'versi' => $tua, 'ganjil_selesai' => '2026-12-12', 'genap_mulai' => '2026-12-14']));

        $result->assertRedirectTo("https://example.com/panel/tahun-ajaran/{$ta['id']}/ubah");
        $this->assertSame('Data ini sudah diubah oleh Admin Tata Usaha pukul 07.10. Periksa data terbaru, lalu simpan lagi bila perlu.', session('galat'));
        $this->assertNull(session('_ci_old_input'));
        $this->seeInDatabase('semester', ['tahun_ajaran_id' => $ta['id'], 'jenis' => 'genap', 'tanggal_mulai' => '2026-12-13']);

        $this->kirim('POST', "panel/tahun-ajaran/{$ta['id']}", ['_method' => 'DELETE', 'versi' => $tua])
            ->assertRedirectTo("https://example.com/panel/tahun-ajaran/{$ta['id']}/hapus");
        $this->seeInDatabase('tahun_ajaran', ['id' => $ta['id']]);
    }

    /**
     * A valid 2026/2027 form with a two-week break, with overrides.
     *
     * @param array<string, string> $ubah
     *
     * @return array<string, string>
     */
    private function isian(array $ubah = []): array
    {
        return $ubah + [
            'nama'            => '2026/2027',
            'tanggal_mulai'   => '2026-07-13',
            'tanggal_selesai' => '2027-06-30',
            'ganjil_mulai'    => '2026-07-13',
            'ganjil_selesai'  => '2026-12-19',
            'genap_mulai'     => '2027-01-04',
            'genap_selesai'   => '2027-06-30',
        ];
    }

    /**
     * @param array<string, mixed> $isian
     * @param array<string, mixed> $sesi
     */
    private function kirim(string $method, string $uri, array $isian = [], array $sesi = []): TestResponse
    {
        $this->resetRouter();

        return $this->withSession($sesi + $this->sesiAkun($this->admin))
            ->withHeaders(['X-CSRF-TOKEN' => service('security')->getHash()])
            ->call($method, $uri, $isian);
    }

    private function resetRouter(): void
    {
        // The shared router keeps the method of the last request.
        foreach (['router', 'response'] as $service) {
            Services::resetSingle($service);
        }
    }

    /**
     * Data of the latest `tahun_ajaran_diubah` entry.
     *
     * @return array<string, mixed>
     */
    private function logData(): array
    {
        $row = $this->db->table('log_aktivitas')->where('jenis', 'tahun_ajaran_diubah')->orderBy('id', 'DESC')->get()->getRow();

        return json_decode($row->data, true);
    }
}
