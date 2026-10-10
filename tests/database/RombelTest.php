<?php

use App\Services\Akun\Peran;
use App\Services\MasterData\Rombel;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;
use Config\Services;
use Tests\Support\AkunTrait;
use Tests\Support\MasterDataTrait;

/**
 * Rombel and wali kelas (docs/04 FS-MD-03, AC-MD-03-*, docs/09 HAL-MD-03,
 * docs/02 §3 item 2, docs/11 §5.2, docs/12 SEC-59). The screen says "Kelas".
 *
 * @internal
 */
final class RombelTest extends CIUnitTestCase
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
        Time::setTestNow('2026-10-13 07:00:00', 'Asia/Jakarta');
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
        // HA-MD-02
        $rombel = $this->buatRombel(['tahun_ajaran_id' => $this->ta['id']]);
        $piket  = $this->buatAkun(['username' => 'piket'], ['guru_piket']);
        $wali   = $this->buatAkun(['username' => 'wali'], []);
        $this->db->table('rombel')->where('id', $rombel['id'])->update(['wali_kelas_id' => $wali['id']]);

        foreach ([$piket, $wali] as $akun) {
            foreach (['GET panel/kelas', 'GET panel/kelas/tambah', "GET panel/kelas/{$rombel['id']}/ubah", 'POST panel/kelas', "DELETE panel/kelas/{$rombel['id']}"] as $permintaan) {
                [$method, $uri] = explode(' ', $permintaan);
                $isian          = ['_method' => $method, 'nama' => '7Z', 'tingkat' => '7', 'tahun_ajaran_id' => $this->ta['id']];
                $this->resetRouter();
                $result = $this->withSession($this->sesiAkun($akun))
                    ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json', 'X-CSRF-TOKEN' => service('security')->getHash()])
                    ->call($method === 'GET' ? 'GET' : 'POST', $uri, $isian);

                $result->assertStatus(403);
            }
        }

        $this->seeNumRecords(1, 'rombel', []);
    }

    public function testListShowsStudentsAndWaliKelasMarks(): void
    {
        $rina = $this->buatAkun(['username' => 'rina', 'nama' => 'Rina Wulandari']);
        $andi = $this->buatAkun(['username' => 'andi', 'nama' => 'Andi Saputra', 'status' => 'nonaktif']);
        $a7   = $this->buatRombel(['tahun_ajaran_id' => $this->ta['id'], 'nama' => '7A', 'wali_kelas_id' => $rina['id']]);
        $b7   = $this->buatRombel(['tahun_ajaran_id' => $this->ta['id'], 'nama' => '7B']);
        $a8   = $this->buatRombel(['tahun_ajaran_id' => $this->ta['id'], 'nama' => '8A', 'tingkat' => 8, 'wali_kelas_id' => $andi['id']]);
        $lama = $this->buatTahunAjaran(['nama' => '2025/2026', 'tanggal_mulai' => '2025-07-14', 'tanggal_selesai' => '2026-06-30', 'aktif' => 0]);
        $this->buatRombel(['tahun_ajaran_id' => $lama['id'], 'nama' => '9Z', 'tingkat' => 9]);

        $this->buatSiswa(['rombel_id' => $a7['id']]);
        $this->buatSiswa(['rombel_id' => $a7['id']]);
        // Moved out of 7A before today: counted in 8A only.
        $pindah = $this->buatSiswa();
        $this->tempatkan((int) $pindah['id'], (int) $a7['id'], '2026-07-13', '2026-09-30');
        $this->tempatkan((int) $pindah['id'], (int) $a8['id'], '2026-10-01');

        $result = $this->kirim('GET', 'panel/kelas');

        $result->assertOK();
        $result->assertSee('Rina Wulandari');
        $result->assertSee('Belum ada wali kelas');
        $result->assertSee('Akun nonaktif, tetapkan pengganti');
        $result->assertDontSee('9Z');
        $this->assertMatchesRegularExpression('#<td>7A</td>.*?<td class="text-end">2</td>#s', $result->response()->getBody());
        $this->assertMatchesRegularExpression('#<td>7B</td>.*?<td class="text-end">0</td>#s', $result->response()->getBody());
        $this->assertMatchesRegularExpression('#<td>8A</td>.*?<td class="text-end">1</td>#s', $result->response()->getBody());
        // Delete only for a rombel without placements (E3).
        $result->assertSee("panel/kelas/{$b7['id']}/hapus");
        $result->assertDontSee("panel/kelas/{$a7['id']}/hapus");
        $result->assertDontSee("panel/kelas/{$a8['id']}/hapus");

        $tahunLalu = $this->kirim('GET', "panel/kelas?tahun_ajaran={$lama['id']}");
        $tahunLalu->assertSee('9Z');
        $tahunLalu->assertDontSee('Rina Wulandari');
    }

    public function testWaliKelasChoicesAreActiveStaffOnly(): void
    {
        $this->buatAkun(['username' => 'rina', 'nama' => 'Rina Wulandari']);
        $this->buatAkun(['username' => 'andi', 'nama' => 'Andi Saputra', 'status' => 'nonaktif']);
        $this->buatSiswa(['nama' => 'Budi Siswa']);

        $result = $this->kirim('GET', 'panel/kelas/tambah');

        $result->assertOK();
        $result->assertSee('Rina Wulandari (rina)');
        $result->assertDontSee('Andi Saputra');
        $result->assertDontSee('Budi Siswa');
    }

    public function testAddRombelWithWaliKelasAndLogs(): void
    {
        $rina = $this->buatAkun(['username' => 'rina', 'nama' => 'Rina Wulandari']);

        $result = $this->kirim('POST', 'panel/kelas', ['tahun_ajaran_id' => $this->ta['id'], 'nama' => '  7   A ', 'tingkat' => '7', 'wali_kelas_id' => $rina['id']]);

        $result->assertStatus(303);
        $result->assertRedirectTo("https://example.com/panel/kelas?tahun_ajaran={$this->ta['id']}");
        $this->assertSame('Kelas 7 A disimpan.', session('sukses'));
        $rombel = $this->db->table('rombel')->get()->getRowArray();
        $this->assertSame(['7 A', '7', (string) $rina['id']], [$rombel['nama'], (string) $rombel['tingkat'], (string) $rombel['wali_kelas_id']]);

        $this->assertEquals(['rombel_id' => (int) $rombel['id'], 'aksi' => 'dibuat', 'tahun_ajaran' => '2026/2027', 'nama' => '7 A', 'tingkat' => 7], $this->logData('rombel_diubah'));
        $this->seeInDatabase('log_aktivitas', ['jenis' => 'wali_kelas_diubah', 'pelaku_id' => $this->admin['id'], 'akun_id' => $rina['id'], 'rombel_id' => $rombel['id']]);
        $this->assertEquals(['rombel' => '7 A', 'lama' => null, 'baru' => 'Rina Wulandari', 'lama_id' => null, 'baru_id' => (int) $rina['id']], $this->logData('wali_kelas_diubah'));
    }

    public function testDuplicateNameInSameYearIsRejected(): void
    {
        // AC-MD-03-02, E1: case does not matter (docs/11 VAL-27).
        $this->buatRombel(['tahun_ajaran_id' => $this->ta['id'], 'nama' => '7A']);
        $lain = $this->buatTahunAjaran(['nama' => '2027/2028', 'tanggal_mulai' => '2027-07-12', 'tanggal_selesai' => '2028-06-30', 'aktif' => 0]);

        $result = $this->kirim('POST', 'panel/kelas', ['tahun_ajaran_id' => $this->ta['id'], 'nama' => '7a', 'tingkat' => '7']);

        $result->assertRedirectTo('https://example.com/panel/kelas/tambah');
        $this->assertSame(['nama' => 'Nama kelas sudah dipakai di tahun ajaran ini.'], session('_ci_validation_errors'));
        $this->seeNumRecords(1, 'rombel', []);

        // The same name in another school year is fine.
        $this->assertArrayHasKey('id', (new Rombel())->buat(['tahun_ajaran_id' => $lain['id'], 'nama' => '7A', 'tingkat' => '7'], (int) $this->admin['id'], null));
    }

    public function testFieldErrors(): void
    {
        $andi = $this->buatAkun(['username' => 'andi', 'nama' => 'Andi Saputra', 'status' => 'nonaktif']);

        $hasil = (new Rombel())->buat(['tahun_ajaran_id' => '999', 'nama' => '7A!', 'tingkat' => '10', 'wali_kelas_id' => $andi['id']], (int) $this->admin['id'], null);

        $this->assertSame([
            'tahun_ajaran_id' => 'Tahun ajaran yang dipilih sudah tidak ada. Pilih lagi.',
            'nama'            => 'Nama kelas hanya boleh berisi huruf, angka, spasi, dan tanda hubung.',
            'tingkat'         => 'Pilih tingkat 7, 8, atau 9.',
            'wali_kelas_id'   => 'Akun Andi Saputra nonaktif. Pilih staf yang aktif.',
        ], $hasil['galat']);
    }

    public function testInactiveWaliKelasRejectedButKeptWhenUnchanged(): void
    {
        // E2 only applies to a new choice; FS-AKN-03 item 9 keeps the assignment.
        $andi   = $this->buatAkun(['username' => 'andi', 'nama' => 'Andi Saputra']);
        $rombel = $this->buatRombel(['tahun_ajaran_id' => $this->ta['id'], 'wali_kelas_id' => $andi['id']]);
        $this->db->table('akun')->where('id', $andi['id'])->update(['status' => 'nonaktif']);

        $form = $this->kirim('GET', "panel/kelas/{$rombel['id']}/ubah");
        $form->assertSee('Andi Saputra (andi) – nonaktif');

        $hasil = (new Rombel())->perbarui((int) $rombel['id'], $rombel['updated_at'], ['nama' => '7A Baru', 'tingkat' => '7', 'wali_kelas_id' => $andi['id']], (int) $this->admin['id'], null);
        $this->assertSame(['ok' => true], $hasil);

        $lain  = $this->buatRombel(['tahun_ajaran_id' => $this->ta['id'], 'nama' => '7B']);
        $hasil = (new Rombel())->perbarui((int) $lain['id'], $lain['updated_at'], ['nama' => '7B', 'tingkat' => '7', 'wali_kelas_id' => $andi['id']], (int) $this->admin['id'], null);
        $this->assertSame(['galat' => ['wali_kelas_id' => 'Akun Andi Saputra nonaktif. Pilih staf yang aktif.']], $hasil);
    }

    public function testWaliKelasRightsFollowTheAssignment(): void
    {
        // AC-MD-03-01, docs/02 §3 item 2, FS-MD-03 items 3 and 4.
        $rina   = $this->buatAkun(['username' => 'rina', 'nama' => 'Bu Rina']);
        $budi   = $this->buatAkun(['username' => 'budi', 'nama' => 'Pak Budi']);
        $a7     = $this->buatRombel(['tahun_ajaran_id' => $this->ta['id'], 'nama' => '7A', 'wali_kelas_id' => $rina['id']]);
        $b7     = $this->buatRombel(['tahun_ajaran_id' => $this->ta['id'], 'nama' => '7B']);
        $siswaA = $this->buatSiswa(['rombel_id' => $a7['id']]);
        $siswaB = $this->buatSiswa(['rombel_id' => $b7['id']]);

        $this->assertTrue($this->boleh($rina, 'HA-AKN-05', (int) $siswaA['id']));
        $this->assertTrue($this->boleh($rina, 'HA-MD-05', (int) $siswaA['id']));
        $this->assertFalse($this->boleh($rina, 'HA-AKN-05', (int) $siswaB['id']));
        $this->assertFalse($this->boleh($budi, 'HA-AKN-05', (int) $siswaA['id']));

        $result = $this->kirim('PATCH', "panel/kelas/{$a7['id']}", ['versi' => $a7['updated_at'], 'nama' => '7A', 'tingkat' => '7', 'wali_kelas_id' => $budi['id']]);
        $result->assertStatus(303);
        $this->assertSame('Perubahan kelas disimpan.', session('sukses'));

        $this->assertTrue($this->boleh($budi, 'HA-AKN-05', (int) $siswaA['id']));
        $this->assertTrue($this->boleh($budi, 'HA-MD-05', (int) $siswaA['id']));
        $this->assertFalse($this->boleh($budi, 'HA-AKN-05', (int) $siswaB['id']));
        $this->assertFalse($this->boleh($rina, 'HA-AKN-05', (int) $siswaA['id']));
        $this->assertFalse($this->boleh($rina, 'HA-MD-05', (int) $siswaA['id']));
        $this->assertNotContains('wali_kelas', (new Peran())->untuk($rina));

        $this->seeInDatabase('log_aktivitas', ['jenis' => 'wali_kelas_diubah', 'akun_id' => $budi['id'], 'rombel_id' => $a7['id']]);
        $this->assertEquals(['rombel' => '7A', 'lama' => 'Bu Rina', 'baru' => 'Pak Budi', 'lama_id' => (int) $rina['id'], 'baru_id' => (int) $budi['id']], $this->logData('wali_kelas_diubah'));
        // Name and tingkat did not change: no rombel_diubah entry.
        $this->seeNumRecords(0, 'log_aktivitas', ['jenis' => 'rombel_diubah']);

        // A rombel in a school year that is not active yet gives no rights.
        $depan = $this->buatTahunAjaran(['nama' => '2027/2028', 'tanggal_mulai' => '2027-07-12', 'tanggal_selesai' => '2028-06-30', 'aktif' => 0]);
        $this->buatRombel(['tahun_ajaran_id' => $depan['id'], 'nama' => '8A', 'tingkat' => 8, 'wali_kelas_id' => $rina['id']]);
        $this->assertNotContains('wali_kelas', (new Peran())->untuk($rina));
    }

    public function testRemovingWaliKelasLogsAndShowsMark(): void
    {
        $rina   = $this->buatAkun(['username' => 'rina', 'nama' => 'Bu Rina']);
        $rombel = $this->buatRombel(['tahun_ajaran_id' => $this->ta['id'], 'wali_kelas_id' => $rina['id']]);

        $this->kirim('PATCH', "panel/kelas/{$rombel['id']}", ['versi' => $rombel['updated_at'], 'nama' => '7A', 'tingkat' => '7', 'wali_kelas_id' => '']);

        $this->seeInDatabase('rombel', ['id' => $rombel['id'], 'wali_kelas_id' => null]);
        $this->seeInDatabase('log_aktivitas', ['jenis' => 'wali_kelas_diubah', 'akun_id' => $rina['id'], 'rombel_id' => $rombel['id']]);
        $this->kirim('GET', 'panel/kelas')->assertSee('Belum ada wali kelas');
    }

    public function testEditLogsChangedFields(): void
    {
        $rombel = $this->buatRombel(['tahun_ajaran_id' => $this->ta['id']]);

        $this->kirim('PATCH', "panel/kelas/{$rombel['id']}", ['versi' => $rombel['updated_at'], 'nama' => '8A', 'tingkat' => '8', 'wali_kelas_id' => '']);

        $this->seeInDatabase('rombel', ['id' => $rombel['id'], 'nama' => '8A', 'tingkat' => 8]);
        $this->assertEquals(['rombel_id' => (int) $rombel['id'], 'aksi' => 'diubah', 'nama' => ['lama' => '7A', 'baru' => '8A'], 'tingkat' => ['lama' => '7', 'baru' => 8]], $this->logData('rombel_diubah'));
    }

    public function testTingkatLockedOncePlaced(): void
    {
        // E4, FS-MD-03 item 7.
        $rombel = $this->buatRombel(['tahun_ajaran_id' => $this->ta['id']]);
        $this->buatSiswa(['rombel_id' => $rombel['id']]);

        $form = $this->kirim('GET', "panel/kelas/{$rombel['id']}/ubah");
        $form->assertSee('Tingkat tidak dapat diubah karena kelas ini sudah memiliki siswa.');
        $form->assertSee('id="tingkat" disabled');

        $result = $this->kirim('PATCH', "panel/kelas/{$rombel['id']}", ['versi' => $rombel['updated_at'], 'nama' => '7A', 'tingkat' => '8', 'wali_kelas_id' => '']);

        $result->assertRedirectTo("https://example.com/panel/kelas/{$rombel['id']}/ubah");
        $this->assertSame(['tingkat' => 'Tingkat kelas yang sudah memiliki siswa tidak dapat diubah.'], session('_ci_validation_errors'));
        $this->seeInDatabase('rombel', ['id' => $rombel['id'], 'tingkat' => 7]);

        // The name can still change.
        $this->assertSame(['ok' => true], (new Rombel())->perbarui((int) $rombel['id'], $rombel['updated_at'], ['nama' => '7 Unggulan', 'tingkat' => '7'], (int) $this->admin['id'], null));
    }

    public function testDeleteOnlyWithoutPlacements(): void
    {
        // E3, FS-MD-03 item 5.
        $placed = $this->buatRombel(['tahun_ajaran_id' => $this->ta['id'], 'nama' => '7A']);
        $kosong = $this->buatRombel(['tahun_ajaran_id' => $this->ta['id'], 'nama' => '7B']);
        $this->buatSiswa(['rombel_id' => $placed['id']]);

        $form = $this->kirim('GET', "panel/kelas/{$placed['id']}/hapus");
        $form->assertRedirectTo("https://example.com/panel/kelas?tahun_ajaran={$this->ta['id']}");
        $this->assertSame('Kelas ini sudah memiliki siswa, sehingga tidak dapat dihapus.', session('galat'));

        $result = $this->kirim('DELETE', "panel/kelas/{$placed['id']}");
        $this->assertSame('Kelas ini sudah memiliki siswa, sehingga tidak dapat dihapus.', session('galat'));
        $this->seeInDatabase('rombel', ['id' => $placed['id']]);

        $this->kirim('GET', "panel/kelas/{$kosong['id']}/hapus")->assertSee('Hapus kelas 7B?');
        $result = $this->kirim('DELETE', "panel/kelas/{$kosong['id']}");

        $result->assertRedirectTo("https://example.com/panel/kelas?tahun_ajaran={$this->ta['id']}");
        $this->assertSame('Kelas 7B sudah dihapus.', session('sukses'));
        $this->dontSeeInDatabase('rombel', ['id' => $kosong['id']]);
        $this->assertEquals(['rombel_id' => (int) $kosong['id'], 'aksi' => 'dihapus', 'tahun_ajaran' => '2026/2027', 'nama' => '7B', 'tingkat' => 7], $this->logData('rombel_diubah'));
    }

    public function testStaleVersionIsRejected(): void
    {
        // docs/04 §4.6, docs/06 DB-11, docs/11 GAL-05.
        $rombel = $this->buatRombel(['tahun_ajaran_id' => $this->ta['id']]);
        $lain   = $this->buatAkun(['username' => 'admin.dua', 'nama' => 'Rina Wulandari'], ['admin']);

        $form = $this->kirim('GET', "panel/kelas/{$rombel['id']}/ubah");
        $form->assertSee('name="versi" value="' . $rombel['updated_at'] . '"');

        Time::setTestNow('2026-10-13 07:40:00', 'Asia/Jakarta');
        $this->assertSame(['ok' => true], (new Rombel())->perbarui((int) $rombel['id'], $rombel['updated_at'], ['nama' => '7 Baru', 'tingkat' => '7'], (int) $lain['id'], null));

        $result = $this->kirim('PATCH', "panel/kelas/{$rombel['id']}", ['versi' => $rombel['updated_at'], 'nama' => '7 Lama', 'tingkat' => '7', 'wali_kelas_id' => '']);

        $result->assertRedirectTo("https://example.com/panel/kelas/{$rombel['id']}/ubah");
        $this->assertSame('Data ini sudah diubah oleh Rina Wulandari pukul 07.40. Periksa data terbaru, lalu simpan lagi bila perlu.', session('galat'));
        $this->assertNull(session('_ci_old_input'));
        $this->seeInDatabase('rombel', ['id' => $rombel['id'], 'nama' => '7 Baru']);
    }

    public function testUnknownIdIs404(): void
    {
        $this->kirim('GET', 'panel/kelas/999/ubah')->assertStatus(404);
    }

    public function testNoSchoolYearShowsHint(): void
    {
        $this->db->table('semester')->emptyTable();
        $this->db->table('tahun_ajaran')->emptyTable();

        $this->kirim('GET', 'panel/kelas')->assertSee('Belum ada tahun ajaran.');
        $this->kirim('GET', 'panel/kelas/tambah')->assertRedirectTo('https://example.com/panel/kelas');
    }

    /**
     * Whether the staff account, loaded as on a fresh request, holds the right for the student.
     *
     * @param array<string, mixed> $akun
     */
    private function boleh(array $akun, string $hak, int $siswaId): bool
    {
        $aktif = service('akunAktif');
        $akun  = $this->db->table('akun')->where('id', $akun['id'])->get()->getRowArray();
        $aktif->set($akun, (new Peran())->untuk($akun));

        return $aktif->boleh($hak, $aktif->dataSiswa($siswaId));
    }

    /**
     * @param array<string, mixed> $isian
     */
    private function kirim(string $method, string $uri, array $isian = []): TestResponse
    {
        $this->resetRouter();

        // A browser form sends PATCH and DELETE as POST with `_method`.
        if (in_array($method, ['PATCH', 'DELETE'], true)) {
            [$method, $isian] = ['POST', ['_method' => $method] + $isian];
        }

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

    /**
     * @return array<string, mixed>
     */
    private function logData(string $jenis): array
    {
        $row = $this->db->table('log_aktivitas')->where('jenis', $jenis)->orderBy('id', 'DESC')->get()->getRowArray();

        return json_decode($row['data'], true);
    }
}
