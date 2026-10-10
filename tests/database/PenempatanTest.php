<?php

use App\Services\MasterData\Rujukan;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;
use Config\Services;
use Tests\Support\AkunTrait;
use Tests\Support\MasterDataTrait;

/**
 * Placements (docs/04 FS-MD-05 without item 6, AC-MD-05-01 to 03,
 * docs/09 HAL-MD-11, HAL-MD-12, RT-07, docs/06 §6.7).
 *
 * @internal
 */
final class PenempatanTest extends CIUnitTestCase
{
    use AkunTrait;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use MasterDataTrait;

    protected $refresh   = true;
    protected $namespace = 'App';

    /** @var array<string, mixed> */
    private array $admin;

    /** @var array<string, mixed> */
    private array $ta;

    protected function setUp(): void
    {
        Services::resetSingle('routes');
        Services::resetSingle('router');
        Services::resetSingle('request');
        Services::resetSingle('response');
        parent::setUp();
        Time::setTestNow('2026-10-12 07:00:00', 'Asia/Jakarta');
        $this->admin = $this->buatAkun(['username' => 'admin.tu', 'nama' => 'Admin Tata Usaha'], ['admin']);
        $this->ta    = $this->buatTahunAjaran();
    }

    protected function tearDown(): void
    {
        Time::setTestNow();
        service('akunAktif')->clear();
        parent::tearDown();
    }

    public function testOnlyAdminReachesThePages(): void
    {
        // HA-MD-03
        $rombel = $this->buatRombel(['tahun_ajaran_id' => $this->ta['id']]);
        $siswa  = $this->buatSiswa(['rombel_id' => $rombel['id']]);
        $piket  = $this->buatAkun(['username' => 'piket'], ['guru_piket']);
        $wali   = $this->buatAkun(['username' => 'wali']);
        $this->db->table('rombel')->where('id', $rombel['id'])->update(['wali_kelas_id' => $wali['id']]);

        foreach ([$piket, $wali] as $akun) {
            foreach (['panel/penempatan', "panel/siswa/{$siswa['id']}/penempatan/tambah"] as $uri) {
                $this->resetRouter();
                $this->withSession($this->sesiAkun($akun))
                    ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
                    ->get($uri)->assertStatus(403);
            }
        }

        $this->kirim('GET', 'panel/penempatan')->assertOK();
        $this->kirim('GET', 'panel/siswa/99999/penempatan/tambah')->assertStatus(404);
    }

    public function testMoveMidYearClosesOldPlacementTheDayBefore(): void
    {
        // AC-MD-05-01
        $waliA = $this->buatAkun(['username' => 'wali.a']);
        $waliB = $this->buatAkun(['username' => 'wali.b']);
        $a     = $this->buatRombel(['tahun_ajaran_id' => $this->ta['id'], 'nama' => '7A', 'wali_kelas_id' => $waliA['id']]);
        $b     = $this->buatRombel(['tahun_ajaran_id' => $this->ta['id'], 'nama' => '7B', 'wali_kelas_id' => $waliB['id']]);
        $i     = $this->buatSiswa(['nama' => 'Indah Lestari', 'rombel_id' => $a['id']]);

        $form = $this->kirim('GET', "panel/siswa/{$i['id']}/penempatan/tambah");
        $form->assertOK();
        $form->assertSee('Kelas saat ini 7A');
        $form->assertSee('value="2026-10-12"');

        $result = $this->kirim('POST', "panel/siswa/{$i['id']}/penempatan", ['rombel_id' => (string) $b['id'], 'tanggal_mulai' => '2026-10-12']);
        $result->assertStatus(303);
        $result->assertRedirectTo("https://example.com/panel/siswa/{$i['id']}");
        $this->assertSame('Indah Lestari ditempatkan di kelas 7B mulai 12 Oktober 2026.', session('sukses'));

        $this->seeInDatabase('penempatan', ['siswa_id' => $i['id'], 'rombel_id' => $a['id'], 'tanggal_mulai' => '2026-07-13', 'tanggal_selesai' => '2026-10-11']);
        $this->seeInDatabase('penempatan', ['siswa_id' => $i['id'], 'rombel_id' => $b['id'], 'tanggal_mulai' => '2026-10-12', 'tanggal_selesai' => null, 'dibuat_oleh' => $this->admin['id']]);

        $rujukan = new Rujukan();
        $this->assertSame('7A', $rujukan->rombelSiswa((int) $i['id'], '2026-10-11')['nama']);
        $this->assertSame('7B', $rujukan->rombelSiswa((int) $i['id'], '2026-10-12')['nama']);

        $log = $this->db->table('log_data_siswa')->where('siswa_id', $i['id'])->orderBy('id')->get()->getResultArray();
        $this->assertSame(['penempatan_diubah', 'penempatan_dibuat'], array_column($log, 'jenis'));
        $this->assertSame('2026-10-11', json_decode($log[0]['data_baru'], true)['tanggal_selesai']);
        $this->assertSame('7B', json_decode($log[1]['data_baru'], true)['kelas']);
        $this->assertSame((string) $this->admin['id'], (string) $log[1]['pelaku_id']);

        // The student follows the new wali kelas (docs/04 §4.1 item 4).
        $akunAktif = service('akunAktif');
        $akunAktif->set($waliB, ['staf', 'wali_kelas']);
        $this->assertTrue($akunAktif->boleh('HA-MD-05', $akunAktif->dataSiswa((int) $i['id'])));
        $akunAktif->set($waliA, ['staf', 'wali_kelas']);
        $this->assertFalse($akunAktif->boleh('HA-MD-05', $akunAktif->dataSiswa((int) $i['id'])));
    }

    public function testPromotionToNewSchoolYear(): void
    {
        // AC-MD-05-02, with 3 students instead of 32.
        Time::setTestNow('2027-06-20 07:00:00', 'Asia/Jakarta');
        $baru  = $this->buatTahunAjaran(['nama' => '2027/2028', 'tanggal_mulai' => '2027-07-12', 'tanggal_selesai' => '2028-06-30', 'aktif' => 0]);
        $a     = $this->buatRombel(['tahun_ajaran_id' => $this->ta['id'], 'nama' => '7A']);
        $delapan = $this->buatRombel(['tahun_ajaran_id' => $baru['id'], 'nama' => '8A', 'tingkat' => 8]);
        $ani   = $this->buatSiswa(['nama' => 'Ani', 'rombel_id' => $a['id']]);
        $budi  = $this->buatSiswa(['nama' => 'Budi', 'rombel_id' => $a['id']]);
        $citra = $this->buatSiswa(['nama' => 'Citra', 'rombel_id' => $a['id']]);
        $pindah = $this->buatSiswa(['nama' => 'Dodi']);
        $this->tempatkan((int) $pindah['id'], (int) $a['id'], '2026-07-13', '2026-09-30');

        $page = $this->kirim('GET', "panel/penempatan/kelas?asal={$a['id']}&tujuan={$delapan['id']}");
        $page->assertOK();
        $page->assertSee('Ani');
        $page->assertSee('Citra');
        $page->assertDontSee('Dodi');
        $page->assertSee('value="2027-07-12"');
        $this->assertSame(3, substr_count($page->response()->getBody(), ' checked'));

        $isian = ['asal' => (string) $a['id'], 'tujuan' => (string) $delapan['id'], 'tanggal_mulai' => '2027-07-12', 'siswa' => [(string) $ani['id'], (string) $citra['id']]];

        // RT-07: the first request only shows the impact.
        $periksa = $this->kirim('POST', 'panel/penempatan/kelas', $isian);
        $periksa->assertOK();
        $periksa->assertSee('Tempatkan 2 siswa di kelas 8A?');
        $periksa->assertSee('name="konfirmasi" value="1"');
        $this->seeNumRecords(0, 'penempatan', ['rombel_id' => $delapan['id']]);

        $result = $this->kirim('POST', 'panel/penempatan/kelas', $isian + ['konfirmasi' => '1']);
        $result->assertStatus(303);
        $result->assertRedirectTo('https://example.com/panel/penempatan');
        $this->assertSame('2 siswa ditempatkan di kelas 8A mulai 12 Juli 2027.', session('sukses'));

        foreach ([$ani, $citra] as $s) {
            $this->seeInDatabase('penempatan', ['siswa_id' => $s['id'], 'rombel_id' => $delapan['id'], 'tanggal_mulai' => '2027-07-12', 'tanggal_selesai' => null]);
            // The old year's placement ends by itself (docs/06 §6.7 rule 3).
            $this->seeInDatabase('penempatan', ['siswa_id' => $s['id'], 'rombel_id' => $a['id'], 'tanggal_selesai' => null]);
        }
        $this->seeNumRecords(0, 'penempatan', ['siswa_id' => $budi['id'], 'rombel_id' => $delapan['id']]);
        $this->assertSame('7A', (new Rujukan())->rombelSiswa((int) $ani['id'], '2027-06-30')['nama']);
        $this->assertSame('8A', (new Rujukan())->rombelSiswa((int) $ani['id'], '2027-07-12')['nama']);

        $log = $this->db->table('log_data_siswa')->where('jenis', 'penempatan_dibuat')->get()->getResultArray();
        $this->assertCount(2, $log);
        $this->assertNotNull($log[0]['kelompok']);
        $this->assertSame($log[0]['kelompok'], $log[1]['kelompok']);

        $aktivitas = $this->db->table('log_aktivitas')->where('jenis', 'penempatan_massal')->get()->getRowArray();
        $this->assertSame((string) $delapan['id'], (string) $aktivitas['rombel_id']);
        $this->assertEquals(['kelas_asal' => '7A (2026/2027)', 'kelas_tujuan' => '8A (2027/2028)', 'tanggal_mulai' => '2027-07-12', 'jumlah_siswa' => 2], json_decode($aktivitas['data'], true));
    }

    public function testOverlappingPlacementIsRefused(): void
    {
        // AC-MD-05-03: J is in 7A for the odd semester; 7C from the same start overlaps it.
        $a = $this->buatRombel(['tahun_ajaran_id' => $this->ta['id'], 'nama' => '7A']);
        $c = $this->buatRombel(['tahun_ajaran_id' => $this->ta['id'], 'nama' => '7C']);
        $j = $this->buatSiswa(['nama' => 'Joko']);
        $this->tempatkan((int) $j['id'], (int) $a['id'], '2026-07-13', '2026-12-11');

        $result = $this->kirim('POST', "panel/siswa/{$j['id']}/penempatan", ['rombel_id' => (string) $c['id'], 'tanggal_mulai' => '2026-07-13']);
        $result->assertStatus(303);
        $result->assertRedirectTo("https://example.com/panel/siswa/{$j['id']}/penempatan/tambah");
        $this->assertSame(['tanggal_mulai' => 'Penempatan ini tumpang tindih dengan penempatan Joko di 7A, 13 Juli 2026 s.d. 11 Desember 2026.'], session('_ci_validation_errors'));
        $this->seeNumRecords(1, 'penempatan', ['siswa_id' => $j['id']]);
        $this->seeNumRecords(0, 'log_data_siswa', []);

        // A later placement is not closed by a move either: the open-ended new one would overlap it.
        $b = $this->buatRombel(['tahun_ajaran_id' => $this->ta['id'], 'nama' => '7B']);
        $this->tempatkan((int) $j['id'], (int) $b['id'], '2026-12-12');
        $this->kirim('POST', "panel/siswa/{$j['id']}/penempatan", ['rombel_id' => (string) $c['id'], 'tanggal_mulai' => '2026-10-12'])->assertStatus(303);
        $this->assertSame(['tanggal_mulai' => 'Penempatan ini tumpang tindih dengan penempatan Joko di 7B, 12 Desember 2026 s.d. 30 Juni 2027.'], session('_ci_validation_errors'));
        $this->seeInDatabase('penempatan', ['siswa_id' => $j['id'], 'rombel_id' => $a['id'], 'tanggal_selesai' => '2026-12-11']);
    }

    public function testInactiveStudentAndDateOutsideYearAreRefused(): void
    {
        // FS-MD-05 E2 and E3
        $a    = $this->buatRombel(['tahun_ajaran_id' => $this->ta['id'], 'nama' => '7A']);
        $b    = $this->buatRombel(['tahun_ajaran_id' => $this->ta['id'], 'nama' => '7B']);
        $sari = $this->buatSiswa(['nama' => 'Sari', 'rombel_id' => $a['id']]);
        $this->db->table('masa_aktif')->where('siswa_id', $sari['id'])->update(['tanggal_selesai' => '2026-09-30', 'alasan_nonaktif' => 'pindah_sekolah']);

        $this->kirim('POST', "panel/siswa/{$sari['id']}/penempatan", ['rombel_id' => (string) $b['id'], 'tanggal_mulai' => '2026-10-12']);
        $this->assertSame(['tanggal_mulai' => 'Sari tidak aktif pada 12 Oktober 2026.'], session('_ci_validation_errors'));

        $this->kirim('POST', "panel/siswa/{$sari['id']}/penempatan", ['rombel_id' => (string) $b['id'], 'tanggal_mulai' => '2027-07-01']);
        $this->assertSame(['tanggal_mulai' => 'Tanggal mulai 1 Juli 2027 di luar tahun ajaran 2026/2027.'], session('_ci_validation_errors'));

        $this->kirim('POST', "panel/siswa/{$sari['id']}/penempatan", ['rombel_id' => '', 'tanggal_mulai' => '2026-02-30']);
        $this->assertSame(['rombel_id' => 'Pilih kelas tujuan.', 'tanggal_mulai' => 'Tanggal mulai tidak valid.'], session('_ci_validation_errors'));

        $this->seeNumRecords(1, 'penempatan', ['siswa_id' => $sari['id']]);
    }

    public function testPerClassMoveSkipsStudentsThatCannotBePlaced(): void
    {
        $a     = $this->buatRombel(['tahun_ajaran_id' => $this->ta['id'], 'nama' => '7A']);
        $b     = $this->buatRombel(['tahun_ajaran_id' => $this->ta['id'], 'nama' => '7B']);
        $c     = $this->buatRombel(['tahun_ajaran_id' => $this->ta['id'], 'nama' => '7C']);
        $eka   = $this->buatSiswa(['nama' => 'Eka', 'rombel_id' => $a['id']]);
        $fajar = $this->buatSiswa(['nama' => 'Fajar']);
        $this->tempatkan((int) $fajar['id'], (int) $a['id'], '2026-07-13', '2026-10-31');
        $this->tempatkan((int) $fajar['id'], (int) $b['id'], '2026-11-01');

        $isian   = ['asal' => (string) $a['id'], 'tujuan' => (string) $c['id'], 'tanggal_mulai' => '2026-10-20', 'siswa' => [(string) $eka['id'], (string) $fajar['id']]];
        $periksa = $this->kirim('POST', 'panel/penempatan/kelas', $isian);
        $periksa->assertSee('Tempatkan 1 siswa di kelas 7C?');
        $periksa->assertSee('Penempatan ini tumpang tindih dengan penempatan Fajar di 7B, 1 November 2026 s.d. 30 Juni 2027.');

        // A tampered confirmation with Fajar again: he is checked again and skipped.
        $this->kirim('POST', 'panel/penempatan/kelas', $isian + ['konfirmasi' => '1'])->assertStatus(303);
        $this->assertSame('1 siswa ditempatkan di kelas 7C mulai 20 Oktober 2026. 1 siswa dilewati.', session('sukses'));
        $this->seeInDatabase('penempatan', ['siswa_id' => $eka['id'], 'rombel_id' => $a['id'], 'tanggal_selesai' => '2026-10-19']);
        $this->seeInDatabase('penempatan', ['siswa_id' => $eka['id'], 'rombel_id' => $c['id'], 'tanggal_mulai' => '2026-10-20']);
        $this->seeNumRecords(0, 'penempatan', ['siswa_id' => $fajar['id'], 'rombel_id' => $c['id']]);
        $this->seeNumRecords(2, 'log_data_siswa', ['siswa_id' => $eka['id']]);

        // Saving the same again places nobody.
        $this->kirim('POST', 'panel/penempatan/kelas', $isian + ['konfirmasi' => '1'])->assertStatus(303);
        $this->assertStringStartsWith('Tidak ada siswa yang ditempatkan.', session('galat'));
    }

    public function testPerClassFormErrors(): void
    {
        $a = $this->buatRombel(['tahun_ajaran_id' => $this->ta['id'], 'nama' => '7A']);
        $this->buatSiswa(['rombel_id' => $a['id']]);

        $same = $this->kirim('GET', "panel/penempatan/kelas?asal={$a['id']}&tujuan={$a['id']}");
        $same->assertSee('Kelas tujuan harus berbeda dengan kelas asal.');

        $this->kirim('POST', 'panel/penempatan/kelas', ['asal' => (string) $a['id'], 'tujuan' => '99999', 'tanggal_mulai' => ''])->assertStatus(303);
        $this->assertSame([
            'tujuan'        => 'Kelas tujuan yang dipilih sudah tidak ada. Pilih lagi.',
            'tanggal_mulai' => 'Tanggal mulai wajib diisi.',
            'siswa'         => 'Pilih paling sedikit 1 siswa.',
        ], session('_ci_validation_errors'));
    }

    /**
     * @param array<string, mixed> $isian
     */
    private function kirim(string $method, string $uri, array $isian = []): TestResponse
    {
        $this->resetRouter();

        return $this->withSession($this->sesiAkun($this->admin))
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
}
