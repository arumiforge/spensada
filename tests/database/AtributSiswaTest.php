<?php

use App\Services\MasterData\AtributSiswa;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;
use Config\Services;
use Tests\Support\AkunTrait;
use Tests\Support\MasterDataTrait;

/**
 * Extra student attributes (docs/04 FS-MD-09, AC-MD-09-*, docs/09
 * HAL-MD-16, docs/06 §6.8, §6.9, docs/11 §5.2, docs/13 §6.1, docs/12 SEC-59).
 *
 * @internal
 */
final class AtributSiswaTest extends CIUnitTestCase
{
    use AkunTrait;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use MasterDataTrait;

    protected $refresh   = true;
    protected $namespace = 'App';

    private const DAFTAR = 'https://example.com/panel/atribut-siswa';

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
        // HA-MD-11
        $piket   = $this->buatAkun(['username' => 'piket'], ['guru_piket', 'guru_bk', 'pimpinan']);
        $atribut = $this->buatAtribut();

        foreach (['GET panel/atribut-siswa', 'GET panel/atribut-siswa/tambah', "GET panel/atribut-siswa/{$atribut['id']}/ubah", 'POST panel/atribut-siswa', "POST panel/atribut-siswa/{$atribut['id']}/sembunyikan", "DELETE panel/atribut-siswa/{$atribut['id']}"] as $permintaan) {
            [$method, $uri] = explode(' ', $permintaan);
            $this->resetRouter();
            $result = $this->withSession($this->sesiAkun($piket))
                ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json', 'X-CSRF-TOKEN' => service('security')->getHash()])
                ->call($method === 'GET' ? 'GET' : 'POST', $uri, ['_method' => $method, 'label' => 'Hobi', 'tipe' => 'teks']);

            $result->assertStatus(403);
        }

        $this->seeNumRecords(1, 'atribut_siswa', []);
        $this->seeInDatabase('atribut_siswa', ['id' => $atribut['id'], 'aktif' => 1]);
    }

    public function testEmptyListOffersAddButton(): void
    {
        // E4
        $result = $this->kirim('GET', 'panel/atribut-siswa');

        $result->assertOK();
        $result->assertSee('Belum ada atribut tambahan.');
        $this->assertSame(2, substr_count($result->response()->getBody(), ' Tambah atribut</a>'));
    }

    public function testRequiredChoiceAttribute(): void
    {
        // AC-MD-09-01 (definition and value check; the student form and
        // import template use aktif() and periksaNilai()).
        $form = $this->kirim('GET', 'panel/atribut-siswa/tambah');
        $form->assertOK();
        $form->assertSee('name="kode"');

        $result = $this->kirim('POST', 'panel/atribut-siswa', [
            'label' => ' Agama ', 'kode' => 'agama', 'tipe' => 'pilihan', 'pilihan' => "Islam\r\nKristen\n\n Katolik \nHindu\nBuddha\nKonghucu", 'wajib' => '1', 'urutan' => '',
        ]);

        $result->assertStatus(303);
        $result->assertRedirectTo(self::DAFTAR);
        $this->assertSame('Atribut Agama disimpan.', session('sukses'));

        $row = $this->db->table('atribut_siswa')->where('kode', 'agama')->get()->getRowArray();
        $this->assertSame(['Agama', 'pilihan', '1', '1', '1'], [$row['label'], $row['tipe'], (string) $row['wajib'], (string) $row['aktif'], (string) $row['urutan']]);
        $this->assertSame(['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'], json_decode($row['pilihan'], true));
        $this->assertEquals([
            'atribut_id' => (int) $row['id'], 'aksi' => 'dibuat', 'kode' => 'agama', 'label' => 'Agama', 'tipe' => 'pilihan',
            'pilihan'    => ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'], 'wajib_diisi' => true, 'urutan' => 1,
        ], $this->logData());
        $this->seeInDatabase('log_aktivitas', ['jenis' => 'atribut_siswa_diubah', 'pelaku_id' => $this->admin['id']]);

        $layanan = new AtributSiswa();
        $aktif   = $layanan->aktif();
        $this->assertSame(['agama'], array_column($aktif, 'kode'));
        $this->assertSame([null, 'Agama wajib diisi.'], $layanan->periksaNilai($aktif[0], '  '));
        $this->assertSame([null, 'Pilih salah satu Agama.'], $layanan->periksaNilai($aktif[0], 'Lainnya'));
        $this->assertSame(['Islam', null], $layanan->periksaNilai($aktif[0], 'islam'));

        $daftar = $this->kirim('GET', 'panel/atribut-siswa');
        $daftar->assertSee('Agama');
        $daftar->assertSee('<code>agama</code>');
        $daftar->assertSee('Pilihan: Islam, Kristen, Katolik, Hindu, Buddha, Konghucu');
    }

    public function testValueChecksPerType(): void
    {
        $layanan = new AtributSiswa();
        $angka   = ['label' => 'Tinggi', 'tipe' => 'angka', 'wajib' => 0, 'pilihan' => []];
        $tanggal = ['label' => 'Tanggal masuk', 'tipe' => 'tanggal', 'wajib' => 0, 'pilihan' => []];
        $teks    = ['label' => 'Hobi', 'tipe' => 'teks', 'wajib' => 0, 'pilihan' => []];

        $this->assertSame([null, null], $layanan->periksaNilai($teks, ''));
        $this->assertSame(['152.5', null], $layanan->periksaNilai($angka, '152,5'));
        $this->assertSame([null, 'Tinggi harus berupa angka.'], $layanan->periksaNilai($angka, '1,5 m'));
        $this->assertSame(['2026-07-13', null], $layanan->periksaNilai($tanggal, '2026-07-13'));
        $this->assertSame([null, 'Tanggal masuk harus berupa tanggal.'], $layanan->periksaNilai($tanggal, '2026-02-30'));
        $this->assertSame([null, 'Hobi paling panjang 255 karakter.'], $layanan->periksaNilai($teks, str_repeat('a', 256)));
    }

    public function testKodeRules(): void
    {
        // E1: unique and not a built-in import column title, case-insensitive (docs/13 §6.1).
        $this->buatAtribut(['kode' => 'agama', 'label' => 'Agama']);
        $layanan = new AtributSiswa();
        $pakai   = 'Kode ini sudah dipakai, atau sama dengan judul kolom bawaan template import.';
        $pola    = 'Kode diawali huruf, dan hanya boleh berisi huruf kecil, angka, dan garis bawah.';

        foreach (['agama' => $pakai, 'NISN' => $pakai, 'Kelas' => $pakai, 'rombel' => $pakai, 'alamat' => $pakai, 'nis' => $pakai, '1hobi' => $pola, 'hobi-ku' => $pola, 'a' => $pola] as $kode => $pesan) {
            $hasil = $layanan->buat(['label' => 'Coba', 'kode' => $kode, 'tipe' => 'teks'], (int) $this->admin['id'], null);
            $this->assertSame(['kode' => $pesan], $hasil['galat'], $kode);
        }

        // Empty kode is made from the label.
        $hasil = $layanan->buat(['label' => 'Asal Sekolah (SD/MI)', 'kode' => '', 'tipe' => 'teks'], (int) $this->admin['id'], null);
        $this->assertSame('asal_sekolah_sd_mi', $layanan->cari($hasil['id'])['kode']);
    }

    public function testFieldErrorsGoBackToTheForm(): void
    {
        $result = $this->kirim('POST', 'panel/atribut-siswa', ['label' => '', 'kode' => 'agama', 'tipe' => 'pilihan', 'pilihan' => "Islam\nislam", 'urutan' => 'satu']);

        $result->assertRedirectTo('https://example.com/panel/atribut-siswa/tambah');
        $this->assertSame([
            'label'   => 'Label wajib diisi.',
            'pilihan' => 'Pilihan islam ditulis lebih dari sekali.',
            'urutan'  => 'Urutan harus berupa angka 0 sampai 999.',
        ], session('_ci_validation_errors'));
        $this->seeNumRecords(0, 'atribut_siswa', []);

        $hasil = (new AtributSiswa())->buat(['label' => 'Agama', 'tipe' => 'pilihan', 'pilihan' => 'Islam'], (int) $this->admin['id'], null);
        $this->assertSame(['pilihan' => 'Tulis paling sedikit 2 pilihan, satu pilihan per baris.'], $hasil['galat']);
        $hasil = (new AtributSiswa())->buat(['label' => str_repeat('a', 61), 'tipe' => 'warna'], (int) $this->admin['id'], null);
        $this->assertSame(['label' => 'Label paling panjang 60 karakter.', 'tipe' => 'Pilih tipe atribut.'], $hasil['galat']);
    }

    public function testTypeLockedOnceStudentsHaveValues(): void
    {
        // E2
        $atribut = $this->buatAtribut();
        $this->nilai($atribut, 'SD 1 Dawe');

        $form = $this->kirim('GET', "panel/atribut-siswa/{$atribut['id']}/ubah");
        $form->assertSee('Tipe tidak dapat diubah karena sudah ada siswa yang memiliki nilai.');
        $form->assertSee('id="tipe" disabled');
        $form->assertDontSee('name="kode"');

        $hasil = (new AtributSiswa())->perbarui((int) $atribut['id'], $atribut['updated_at'], ['label' => 'Asal sekolah', 'tipe' => 'angka'], (int) $this->admin['id'], null);
        $this->assertSame(['galat' => ['tipe' => 'Tipe atribut tidak dapat diubah karena sudah ada siswa yang memiliki nilai.']], $hasil);

        // Without values the type may change; the kode never does.
        $lain  = $this->buatAtribut(['kode' => 'hobi', 'label' => 'Hobi']);
        $hasil = (new AtributSiswa())->perbarui((int) $lain['id'], $lain['updated_at'], ['label' => 'Hobi', 'kode' => 'lain', 'tipe' => 'angka'], (int) $this->admin['id'], null);
        $this->assertSame(['ok' => true], $hasil);
        $this->seeInDatabase('atribut_siswa', ['id' => $lain['id'], 'kode' => 'hobi', 'tipe' => 'angka']);
        $this->assertEquals(['atribut_id' => (int) $lain['id'], 'kode' => 'hobi', 'aksi' => 'diubah', 'tipe' => ['lama' => 'teks', 'baru' => 'angka']], $this->logData());
    }

    public function testUsedChoiceCannotBeRemoved(): void
    {
        // E3, second message.
        $atribut = $this->buatAtribut(['kode' => 'agama', 'label' => 'Agama', 'tipe' => 'pilihan', 'pilihan' => json_encode(['Islam', 'Kristen', 'Hindu'])]);
        $this->nilai($atribut, 'Islam');
        $this->nilai($atribut, 'Islam');

        $result = $this->kirim('PATCH', "panel/atribut-siswa/{$atribut['id']}", ['versi' => $atribut['updated_at'], 'label' => 'Agama', 'tipe' => 'pilihan', 'pilihan' => "Kristen\nHindu", 'urutan' => '1']);

        $result->assertRedirectTo("https://example.com/panel/atribut-siswa/{$atribut['id']}/ubah");
        $this->assertSame(['pilihan' => 'Pilihan Islam sudah dipakai 2 siswa, sehingga tidak dapat dihapus.'], session('_ci_validation_errors'));

        // Removing an unused choice is fine.
        $result = $this->kirim('PATCH', "panel/atribut-siswa/{$atribut['id']}", ['versi' => $atribut['updated_at'], 'label' => 'Agama', 'tipe' => 'pilihan', 'pilihan' => "Islam\nKristen", 'urutan' => '1']);
        $result->assertRedirectTo(self::DAFTAR);
        $this->assertSame(['Islam', 'Kristen'], (new AtributSiswa())->cari((int) $atribut['id'])['pilihan']);
    }

    public function testHideKeepsValuesAndDeleteIsRefused(): void
    {
        // AC-MD-09-02, E3, FS-MD-09 item 4.
        $atribut = $this->buatAtribut();
        for ($i = 0; $i < 3; $i++) {
            $this->nilai($atribut, 'SD 1 Dawe');
        }

        $daftar = $this->kirim('GET', 'panel/atribut-siswa');
        $daftar->assertDontSee("panel/atribut-siswa/{$atribut['id']}/hapus");

        $form = $this->kirim('GET', "panel/atribut-siswa/{$atribut['id']}/hapus");
        $form->assertRedirectTo(self::DAFTAR);
        $this->assertSame('Atribut ini sudah memiliki nilai. Sembunyikan atribut bila tidak dipakai lagi.', session('galat'));

        $this->kirim('DELETE', "panel/atribut-siswa/{$atribut['id']}");
        $this->assertSame('Atribut ini sudah memiliki nilai. Sembunyikan atribut bila tidak dipakai lagi.', session('galat'));
        $this->seeInDatabase('atribut_siswa', ['id' => $atribut['id']]);

        $result = $this->kirim('POST', "panel/atribut-siswa/{$atribut['id']}/sembunyikan");
        $result->assertRedirectTo(self::DAFTAR);
        $this->assertSame('Atribut Asal sekolah disembunyikan. Nilainya tetap tersimpan.', session('sukses'));
        $this->seeInDatabase('atribut_siswa', ['id' => $atribut['id'], 'aktif' => 0]);
        $this->seeNumRecords(3, 'nilai_atribut_siswa', ['atribut_id' => $atribut['id']]);
        $this->assertSame([], (new AtributSiswa())->aktif());
        $this->assertEquals(['atribut_id' => (int) $atribut['id'], 'kode' => 'asal_sekolah', 'aksi' => 'disembunyikan'], $this->logData());
        $this->kirim('GET', 'panel/atribut-siswa')->assertSee('Disembunyikan');

        // A second click changes and logs nothing (docs/11 GAL-09).
        $this->kirim('POST', "panel/atribut-siswa/{$atribut['id']}/sembunyikan");
        $this->seeNumRecords(1, 'log_aktivitas', ['jenis' => 'atribut_siswa_diubah']);

        $this->kirim('POST', "panel/atribut-siswa/{$atribut['id']}/tampilkan");
        $this->assertSame('Atribut Asal sekolah tampil kembali.', session('sukses'));
        $this->assertSame(['asal_sekolah'], array_column((new AtributSiswa())->aktif(), 'kode'));
        $this->assertSame(3, (int) (new AtributSiswa())->cari((int) $atribut['id'])['jumlah_nilai']);
    }

    public function testDeleteWithoutValues(): void
    {
        $atribut = $this->buatAtribut();

        $this->kirim('GET', 'panel/atribut-siswa')->assertSee("panel/atribut-siswa/{$atribut['id']}/hapus");
        $this->kirim('GET', "panel/atribut-siswa/{$atribut['id']}/hapus")->assertSee('Hapus atribut Asal sekolah?');

        $result = $this->kirim('DELETE', "panel/atribut-siswa/{$atribut['id']}");

        $result->assertRedirectTo(self::DAFTAR);
        $this->assertSame('Atribut Asal sekolah sudah dihapus.', session('sukses'));
        $this->dontSeeInDatabase('atribut_siswa', ['id' => $atribut['id']]);
        $this->assertSame('dihapus', $this->logData()['aksi']);
    }

    public function testStaleVersionIsRejected(): void
    {
        // docs/04 §4.6, docs/06 DB-11, docs/11 GAL-05.
        $atribut = $this->buatAtribut();
        $lain    = $this->buatAkun(['username' => 'admin.dua', 'nama' => 'Rina Wulandari'], ['admin']);

        $this->kirim('GET', "panel/atribut-siswa/{$atribut['id']}/ubah")->assertSee('name="versi" value="' . $atribut['updated_at'] . '"');

        Time::setTestNow('2026-10-13 07:40:00', 'Asia/Jakarta');
        $this->assertSame(['ok' => true], (new AtributSiswa())->perbarui((int) $atribut['id'], $atribut['updated_at'], ['label' => 'Sekolah asal', 'tipe' => 'teks'], (int) $lain['id'], null));

        $result = $this->kirim('PATCH', "panel/atribut-siswa/{$atribut['id']}", ['versi' => $atribut['updated_at'], 'label' => 'Asal SD', 'tipe' => 'teks']);

        $result->assertRedirectTo("https://example.com/panel/atribut-siswa/{$atribut['id']}/ubah");
        $this->assertSame('Data ini sudah diubah oleh Rina Wulandari pukul 07.40. Periksa data terbaru, lalu simpan lagi bila perlu.', session('galat'));
        $this->assertNull(session('_ci_old_input'));
        $this->seeInDatabase('atribut_siswa', ['id' => $atribut['id'], 'label' => 'Sekolah asal']);

        // A missing token is stale as well.
        $this->assertSame(['konflik' => true], (new AtributSiswa())->perbarui((int) $atribut['id'], '', ['label' => 'Asal SD', 'tipe' => 'teks'], (int) $this->admin['id'], null));
    }

    public function testUnknownIdIs404(): void
    {
        $this->kirim('GET', 'panel/atribut-siswa/999/ubah')->assertStatus(404);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function buatAtribut(array $data = []): array
    {
        $now = '2026-10-13 06:00:00';
        $this->db->table('atribut_siswa')->insert($data + [
            'kode' => 'asal_sekolah', 'label' => 'Asal sekolah', 'tipe' => 'teks', 'wajib' => 0, 'urutan' => 1, 'aktif' => 1,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        return $this->db->table('atribut_siswa')->where('id', $this->db->insertID())->get()->getRowArray();
    }

    /**
     * Gives a new student a value for the attribute.
     *
     * @param array<string, mixed> $atribut
     */
    private function nilai(array $atribut, string $nilai): void
    {
        $siswa = $this->buatSiswa();
        $this->db->table('nilai_atribut_siswa')->insert([
            'siswa_id' => $siswa['id'], 'atribut_id' => $atribut['id'], 'nilai' => $nilai, 'created_at' => '2026-10-13 06:00:00', 'updated_at' => '2026-10-13 06:00:00',
        ]);
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
    private function logData(): array
    {
        $row = $this->db->table('log_aktivitas')->where('jenis', 'atribut_siswa_diubah')->orderBy('id', 'DESC')->get()->getRowArray();

        return json_decode($row['data'], true);
    }
}
