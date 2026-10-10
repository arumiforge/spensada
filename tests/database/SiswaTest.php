<?php

use App\Services\Akun\DiLuarHak;
use App\Services\MasterData\Rujukan;
use App\Services\MasterData\Siswa;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;
use Config\Services;
use Tests\Support\AkunTrait;
use Tests\Support\MasterDataTrait;

/**
 * Student data (docs/04 FS-MD-04, AC-MD-04-01 to 03, 05, 07, FS-AKN-05
 * A1 to A3, docs/09 HAL-MD-04 to HAL-MD-07, HAL-MD-10, docs/10 EP-MD-01,
 * docs/06 §6.6, §12.2). The WA page is in SiswaWaTest, the log page in
 * LogDataSiswaPageTest.
 *
 * @internal
 */
final class SiswaTest extends CIUnitTestCase
{
    use AkunTrait;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use MasterDataTrait;

    protected $refresh   = true;
    protected $namespace = 'App';

    private const XHR = ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'];

    /** @var array<string, mixed> */
    private array $admin;

    /** @var array<string, mixed> */
    private array $ta;

    /** @var array<string, mixed> */
    private array $rombel7a;

    protected function setUp(): void
    {
        Services::resetSingle('routes');
        Services::resetSingle('router');
        Services::resetSingle('request');
        Services::resetSingle('response');
        parent::setUp();
        Time::setTestNow('2026-10-13 07:00:00', 'Asia/Jakarta');
        $this->admin    = $this->buatAkun(['username' => 'admin.tu', 'nama' => 'Admin Tata Usaha'], ['admin']);
        $this->ta       = $this->buatTahunAjaran();
        $this->rombel7a = $this->buatRombel(['tahun_ajaran_id' => $this->ta['id'], 'nama' => '7A']);
    }

    protected function tearDown(): void
    {
        Time::setTestNow();
        service('akunAktif')->clear();
        parent::tearDown();
    }

    public function testOnlyAdminAddsEditsAndDeactivates(): void
    {
        // HA-MD-03 for the forms; HA-MD-05 for list and profile.
        $siswa = $this->buatSiswa(['rombel_id' => $this->rombel7a['id']]);
        $piket = $this->buatAkun(['username' => 'piket'], ['guru_piket']);

        foreach (['panel/siswa/tambah', "panel/siswa/{$siswa['id']}/ubah", "panel/siswa/{$siswa['id']}/nonaktifkan", "panel/siswa/{$siswa['id']}/aktifkan"] as $uri) {
            $this->ditolak('GET', $uri, $piket);
        }

        $list = $this->kirim('GET', 'panel/siswa', [], $piket);
        $list->assertOK();
        $list->assertSee('Siswa Uji');
        $list->assertDontSee('Tambah siswa');

        $profil = $this->kirim('GET', "panel/siswa/{$siswa['id']}", [], $piket);
        $profil->assertOK();
        $profil->assertDontSee('Ubah data');
        $profil->assertDontSee('Ubah nomor WA');
        $profil->assertDontSee('Status akun');

        $this->kirim('GET', 'panel/siswa/99999')->assertStatus(404);
    }

    public function testAccountButtonsFollowHaAkn04(): void
    {
        // docs/09 HAL-MD-06, HAL-AKN-06, docs/12 SEC-11
        $siswa  = $this->buatSiswa(['nisn' => '0012345678', 'rombel_id' => $this->rombel7a['id']]);
        $piket  = $this->buatAkun(['username' => 'piket'], ['guru_piket']);
        $reset  = "https://example.com/panel/siswa/{$siswa['id']}/reset-password";
        $kunci  = "https://example.com/panel/siswa/{$siswa['id']}/buka-kunci";
        $gagal  = new \App\Services\Akun\PercobaanLogin();
        Time::setTestNow('2026-10-13 06:58:00', 'Asia/Jakarta');
        for ($i = 0; $i < 5; $i++) {
            $gagal->catatGagal($gagal->identitasHash('0012345678'), '10.0.0.9');
        }
        Time::setTestNow('2026-10-13 07:00:00', 'Asia/Jakarta');

        $admin = $this->kirim('GET', "panel/siswa/{$siswa['id']}");
        $admin->assertSee('href="' . $reset . '"', null);
        $admin->assertSee('action="' . $kunci . '"', null);
        $admin->assertSee('Buka kunci login');

        $lain = $this->kirim('GET', "panel/siswa/{$siswa['id']}", [], $piket);
        $lain->assertDontSee('Reset password');
        $lain->assertDontSee('Buka kunci login');

        // No reset for a nonaktif account (FS-AKN-05 E2); no unlock when not locked.
        $this->db->table('akun')->where('id', $siswa['akun_id'])->update(['status' => 'nonaktif']);
        $gagal->hapus($gagal->identitasHash('0012345678'));
        $nonaktif = $this->kirim('GET', "panel/siswa/{$siswa['id']}");
        $nonaktif->assertDontSee('href="' . $reset . '"', null);
        $nonaktif->assertDontSee('Buka kunci login');
    }

    public function testAddCreatesStudentPeriodPlacementAndInactiveAccount(): void
    {
        // AC-MD-04-01, FS-AKN-05 A1
        $form = $this->kirim('GET', 'panel/siswa/tambah');
        $form->assertOK();
        $form->assertSee('value="2026-10-13"', null);

        $result = $this->tambah(['nisn' => '0012345678', 'nama' => 'Rani  Putri']);

        $siswa = $this->db->table('siswa')->where('nisn', '0012345678')->get()->getRowArray();
        $result->assertStatus(303);
        $result->assertRedirectTo("https://example.com/panel/siswa/{$siswa['id']}");
        $this->assertSame('Rani Putri sudah ditambahkan. Akun siswanya belum aktif sampai slip akun dicetak.', session('sukses'));
        $this->assertSame('Rani Putri', $siswa['nama']);
        $this->assertNull($siswa['wa_ortu']);
        $this->assertNull($siswa['nis']);

        $akun = $this->db->table('akun')->where('siswa_id', $siswa['id'])->get()->getRowArray();
        $this->assertSame(['siswa', '0012345678', 'belum_aktif', null], [$akun['jenis'], $akun['username'], $akun['status'], $akun['password_hash']]);
        $this->seeInDatabase('masa_aktif', ['siswa_id' => $siswa['id'], 'tanggal_mulai' => '2026-10-13', 'tanggal_selesai' => null, 'dibatalkan' => 0, 'dibuat_oleh' => $this->admin['id']]);
        $this->seeInDatabase('penempatan', ['siswa_id' => $siswa['id'], 'rombel_id' => $this->rombel7a['id'], 'tanggal_mulai' => '2026-10-13', 'tanggal_selesai' => null]);
        $this->assertSame('aktif', (new Rujukan())->statusSiswa((int) $siswa['id'], '2026-10-13'));

        $log = $this->db->table('log_data_siswa')->where('siswa_id', $siswa['id'])->get()->getResultArray();
        $this->assertSame(['siswa_dibuat'], array_column($log, 'jenis'));
        $this->assertSame((string) $this->admin['id'], (string) $log[0]['pelaku_id']);
        $this->assertEquals(['nisn' => '0012345678', 'nama' => 'Rani Putri', 'kelas' => '7A', 'tanggal_mulai' => '2026-10-13'], json_decode($log[0]['data_baru'], true));
    }

    public function testNisnMustBeTenDigitsAndUnique(): void
    {
        // AC-MD-04-02, E1, E2, VAL-18
        $this->buatSiswa(['nisn' => '0012345678', 'nama' => 'Budi Santoso', 'rombel_id' => $this->rombel7a['id']]);

        $pendek = $this->tambah(['nisn' => '012345678']);
        $pendek->assertRedirectTo('https://example.com/panel/siswa/tambah');
        $this->assertSame('NISN harus 10 digit angka.', session('_ci_validation_errors')['nisn']);

        $ganda = $this->tambah(['nisn' => '0012345678']);
        $this->assertSame('NISN sudah terdaftar atas nama Budi Santoso (kelas 7A).', session('_ci_validation_errors')['nisn']);
        $this->assertSame(1, $this->db->table('siswa')->countAllResults());
        $this->dontSeeInDatabase('log_data_siswa', ['jenis' => 'siswa_dibuat']);

        // The form keeps the input and marks the field (VAL-07).
        $form = $this->kirim('GET', 'panel/siswa/tambah', [], null, [], ['_ci_old_input' => ['get' => [], 'post' => ['nisn' => '0012345678', 'nama' => 'Coba']], '_ci_validation_errors' => ['nisn' => 'NISN sudah terdaftar.']]);
        $form->assertSee('Periksa 1 isian yang ditandai.');
        $form->assertSee('value="0012345678"', null);
    }

    public function testWaIsOptionalNormalizedAndMarkedInTheList(): void
    {
        // AC-MD-04-03, VAL-23
        $this->tambah(['nisn' => '1111111111', 'nama' => 'Siswa E']);
        $this->tambah(['nisn' => '2222222222', 'nama' => 'Siswa F', 'wa_ortu' => '0812-3456-7890']);

        $this->seeInDatabase('siswa', ['nisn' => '1111111111', 'wa_ortu' => null]);
        $this->seeInDatabase('siswa', ['nisn' => '2222222222', 'wa_ortu' => '6281234567890']);

        $tanpa = $this->kirim('GET', 'panel/siswa', ['tanpa_wa' => '1']);
        $tanpa->assertSee('Siswa E');
        $tanpa->assertSee('Tanpa nomor WA');
        $tanpa->assertDontSee('Siswa F');

        // E3: an invalid number keeps everything unsaved.
        $this->tambah(['nisn' => '3333333333', 'nama' => 'Siswa G', 'wa_ortu' => '12345']);
        $this->assertSame('Nomor WA tidak valid. Tulis nomor ponsel yang diawali 08, misalnya 081234567890.', session('_ci_validation_errors')['wa_ortu']);
        $this->dontSeeInDatabase('siswa', ['nisn' => '3333333333']);

        foreach (['+62 812 3456 7890' => '6281234567890', '6281234567' => '6281234567', '081234' => null, '0712345678' => null] as $masuk => $baku) {
            $this->assertSame($baku, Siswa::bakukanWa($masuk), $masuk);
        }
    }

    public function testOtherFieldsAreValidated(): void
    {
        // VAL-15, VAL-19, VAL-24, docs/11 §5.2 FS-MD-04 E7, FS-MD-05 E3
        $this->buatSiswa(['nisn' => '4444444444', 'nama' => 'Ani', 'nis' => '2026.001']);
        $this->tambah(['nama' => 'X', 'nis' => '2026.001', 'tanggal_lahir' => '2026-10-14', 'jenis_kelamin' => 'Z', 'tanggal_mulai' => '2027-07-01']);
        $galat = session('_ci_validation_errors');

        $this->assertSame('Nama paling sedikit 2 karakter.', $galat['nama']);
        $this->assertSame('NIS sudah terdaftar atas nama Ani.', $galat['nis']);
        $this->assertSame('Tanggal lahir tidak boleh setelah hari ini.', $galat['tanggal_lahir']);
        $this->assertSame('Pilih jenis kelamin dari daftar.', $galat['jenis_kelamin']);
        $this->assertSame('Tanggal mulai 1 Juli 2027 di luar tahun ajaran 2026/2027.', $galat['rombel_id']);
    }

    public function testExtraAttributesAreValidatedSavedAndLogged(): void
    {
        // E8, docs/06 §6.9, FS-MD-09
        $now = '2026-10-13 07:00:00';
        $this->db->table('atribut_siswa')->insertBatch([
            ['kode' => 'agama', 'label' => 'Agama', 'tipe' => 'pilihan', 'pilihan' => json_encode(['Islam', 'Kristen']), 'wajib' => 1, 'urutan' => 1, 'aktif' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['kode' => 'tinggi', 'label' => 'Tinggi badan', 'tipe' => 'angka', 'pilihan' => null, 'wajib' => 0, 'urutan' => 2, 'aktif' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
        [$agama, $tinggi] = array_column($this->db->table('atribut_siswa')->orderBy('urutan')->get()->getResultArray(), 'id');

        $this->tambah(['atribut' => [$tinggi => 'tinggi']]);
        $galat = session('_ci_validation_errors');
        $this->assertSame('Agama wajib diisi.', $galat["atribut_{$agama}"]);
        $this->assertSame('Tinggi badan harus berupa angka.', $galat["atribut_{$tinggi}"]);

        $this->tambah(['nisn' => '5555555555', 'atribut' => [$agama => 'Islam', $tinggi => '150,5']]);
        $id = (int) $this->db->table('siswa')->where('nisn', '5555555555')->get()->getRow()->id;
        $this->seeInDatabase('nilai_atribut_siswa', ['siswa_id' => $id, 'atribut_id' => $tinggi, 'nilai' => '150.5']);

        $profil = $this->kirim('GET', "panel/siswa/{$id}");
        $profil->assertSee('150,5');
        $profil->assertSee('Islam');

        $siswa = $this->db->table('siswa')->where('id', $id)->get()->getRowArray();
        Time::setTestNow('2026-10-13 08:00:00', 'Asia/Jakarta');
        $this->ubah($siswa, ['nama' => 'Siswa Uji', 'atribut' => [$agama => 'Kristen', $tinggi => '']]);
        $this->dontSeeInDatabase('nilai_atribut_siswa', ['siswa_id' => $id, 'atribut_id' => $tinggi]);
        $log = $this->db->table('log_data_siswa')->where(['siswa_id' => $id, 'jenis' => 'data_diubah'])->get()->getRowArray();
        $this->assertEquals(['nama' => 'Siswa Uji', 'atribut' => ['Agama' => 'Islam', 'Tinggi badan' => '150.5']], json_decode($log['data_lama'], true));
        $this->assertEquals(['nama' => 'Siswa Uji', 'atribut' => ['Agama' => 'Kristen', 'Tinggi badan' => null]], json_decode($log['data_baru'], true));
    }

    public function testEditChangesNisnAndUsernameAndLogsEachKind(): void
    {
        // FS-MD-04 item 2, FS-AKN-05 A3, docs/06 §12.2
        $siswa = $this->buatSiswa(['nisn' => '0012345678', 'nama' => 'Rani Putri', 'rombel_id' => $this->rombel7a['id']]);
        $form  = $this->kirim('GET', "panel/siswa/{$siswa['id']}/ubah");
        $form->assertSee('name="versi" value="' . $siswa['updated_at'] . '"', null);
        $form->assertSee('QR di kartu harus dicetak ulang');
        $form->assertDontSee('name="rombel_id"', null);

        Time::setTestNow('2026-10-13 08:00:00', 'Asia/Jakarta');
        $result = $this->ubah($siswa, ['nisn' => '0012345679', 'nama' => 'Rani Putri Lestari', 'wa_ortu' => '081234567890', 'jenis_kelamin' => 'P']);

        $result->assertRedirectTo("https://example.com/panel/siswa/{$siswa['id']}");
        $this->assertStringContainsString('QR di kartu harus berisi NISN baru', session('sukses'));
        $this->seeInDatabase('akun', ['id' => $siswa['akun_id'], 'username' => '0012345679']);
        $this->seeInDatabase('siswa', ['id' => $siswa['id'], 'nisn' => '0012345679', 'updated_at' => '2026-10-13 08:00:00']);

        $log = $this->db->table('log_data_siswa')->where('siswa_id', $siswa['id'])->orderBy('id')->get()->getResultArray();
        $this->assertSame(['nisn_diubah', 'wa_diubah', 'data_diubah'], array_column($log, 'jenis'));
        $this->assertEquals(['nisn' => '0012345678'], json_decode($log[0]['data_lama'], true));
        $this->assertEquals(['wa_ortu' => '6281234567890'], json_decode($log[1]['data_baru'], true));
        $this->assertEquals(['nama' => 'Rani Putri Lestari', 'jenis_kelamin' => 'P'], json_decode($log[2]['data_baru'], true));

        // Saving the same data again writes no log.
        $baru = $this->db->table('siswa')->where('id', $siswa['id'])->get()->getRowArray();
        Time::setTestNow('2026-10-13 08:05:00', 'Asia/Jakarta');
        $this->ubah($baru, ['nisn' => '0012345679', 'nama' => 'Rani Putri Lestari', 'wa_ortu' => '081234567890', 'jenis_kelamin' => 'P']);
        $this->assertSame(3, $this->db->table('log_data_siswa')->where('siswa_id', $siswa['id'])->countAllResults());
    }

    public function testStaleVersionIsRejected(): void
    {
        // docs/04 §4.6, docs/06 DB-11, docs/11 GAL-05
        $siswa = $this->buatSiswa(['nama' => 'Rani Putri']);
        $lain  = $this->buatAkun(['username' => 'admin.dua', 'nama' => 'Rina Wulandari'], ['admin']);

        Time::setTestNow('2026-10-13 07:40:00', 'Asia/Jakarta');
        $this->assertSame(['ok' => true], (new Siswa())->perbarui((int) $siswa['id'], $siswa['updated_at'], ['nisn' => $siswa['nisn'], 'nama' => 'Rani Baru'], (int) $lain['id']));

        Time::setTestNow('2026-10-13 07:45:00', 'Asia/Jakarta');
        $result = $this->ubah($siswa, ['nama' => 'Rani Lama']);

        $result->assertRedirectTo("https://example.com/panel/siswa/{$siswa['id']}/ubah");
        $this->assertSame('Data ini sudah diubah oleh Rina Wulandari pukul 07.40. Periksa data terbaru, lalu simpan lagi bila perlu.', session('galat'));
        $this->seeInDatabase('siswa', ['id' => $siswa['id'], 'nama' => 'Rani Baru']);
        $this->assertSame(1, $this->db->table('log_data_siswa')->where('siswa_id', $siswa['id'])->countAllResults());
    }

    public function testDeactivateClosesPeriodAndAccount(): void
    {
        // AC-MD-04-05, FS-AKN-05 A2, E4
        $h = $this->buatSiswa(['nama' => 'Hadi', 'rombel_id' => $this->rombel7a['id']]);
        $this->db->table('akun')->where('id', $h['akun_id'])->update(['status' => 'aktif']);
        $periode = (int) $this->db->table('masa_aktif')->where('siswa_id', $h['id'])->get()->getRow()->id;

        $form = $this->kirim('GET', "panel/siswa/{$h['id']}/nonaktifkan");
        $form->assertSee('name="periode_id" value="' . $periode . '"', null);
        $form->assertSee('name="tanggal_selesai"', null);

        $this->nonaktifkan($h, $periode, ['tanggal_selesai' => '2026-10-14', 'alasan_nonaktif' => 'pindah_sekolah']);
        $this->assertSame('Tanggal terakhir aktif tidak boleh setelah hari ini.', session('_ci_validation_errors')['tanggal_selesai']);
        $this->nonaktifkan($h, $periode, ['tanggal_selesai' => '2026-07-12', 'alasan_nonaktif' => 'pindah_sekolah']);
        $this->assertSame('Tanggal terakhir aktif tidak boleh sebelum tanggal mulai aktif, 13 Juli 2026.', session('_ci_validation_errors')['tanggal_selesai']);
        $this->nonaktifkan($h, $periode, ['tanggal_selesai' => '2026-10-12', 'alasan_nonaktif' => 'lainnya', 'keterangan_nonaktif' => ' ']);
        $this->assertSame('Keterangan wajib diisi.', session('_ci_validation_errors')['keterangan_nonaktif']);
        $this->seeInDatabase('masa_aktif', ['id' => $periode, 'tanggal_selesai' => null]);

        $result = $this->nonaktifkan($h, $periode, ['tanggal_selesai' => '2026-10-12', 'alasan_nonaktif' => 'pindah_sekolah']);

        $result->assertRedirectTo("https://example.com/panel/siswa/{$h['id']}");
        $this->assertSame('Hadi sudah dinonaktifkan, dan akunnya nonaktif.', session('sukses'));
        $this->seeInDatabase('masa_aktif', ['id' => $periode, 'tanggal_selesai' => '2026-10-12', 'alasan_nonaktif' => 'pindah_sekolah', 'dibatalkan' => 0, 'dinonaktifkan_oleh' => $this->admin['id']]);
        $this->seeInDatabase('akun', ['id' => $h['akun_id'], 'status' => 'nonaktif']);
        $rujukan = new Rujukan();
        $this->assertSame('aktif', $rujukan->statusSiswa((int) $h['id'], '2026-10-12'));
        $this->assertSame('nonaktif', $rujukan->statusSiswa((int) $h['id'], '2026-10-13'));

        $log = $this->db->table('log_data_siswa')->where(['siswa_id' => $h['id'], 'jenis' => 'dinonaktifkan'])->get()->getRowArray();
        $this->assertSame('Pindah sekolah', $log['alasan']);
        $this->assertEquals(['tanggal_mulai' => '2026-07-13', 'akun_status' => 'aktif'], json_decode($log['data_lama'], true));

        // The stale form guard (docs/07 ARS-41) and a second try.
        $this->nonaktifkan($h, $periode, ['tanggal_selesai' => '2026-10-12', 'alasan_nonaktif' => 'keluar']);
        $this->assertSame('Siswa ini sudah nonaktif.', session('galat'));
        $this->kirim('GET', "panel/siswa/{$h['id']}/nonaktifkan")->assertRedirectTo("https://example.com/panel/siswa/{$h['id']}");
    }

    public function testWrongInputOrNotStartedPeriodIsCancelled(): void
    {
        // AC-MD-04-07, docs/06 §6.6 rule 4
        $l = $this->buatSiswa(['nama' => 'Lukman', 'rombel_id' => $this->rombel7a['id'], 'tanggal_mulai' => '2026-10-01']);
        $periode = (int) $this->db->table('masa_aktif')->where('siswa_id', $l['id'])->get()->getRow()->id;

        $result = $this->nonaktifkan($l, $periode, ['tanggal_selesai' => '', 'alasan_nonaktif' => 'salah_input']);

        $this->assertSame('Masa aktif Lukman dibatalkan, dan akunnya nonaktif.', session('sukses'));
        $this->seeInDatabase('masa_aktif', ['id' => $periode, 'tanggal_selesai' => '2026-10-01', 'dibatalkan' => 1, 'alasan_nonaktif' => 'salah_input']);
        foreach (['2026-10-01', '2026-10-05', '2026-10-13'] as $tanggal) {
            $this->assertSame('nonaktif', (new Rujukan())->statusSiswa((int) $l['id'], $tanggal));
        }
        $log = $this->db->table('log_data_siswa')->where(['siswa_id' => $l['id'], 'jenis' => 'dinonaktifkan'])->get()->getRowArray();
        $this->assertSame('Salah input', $log['alasan']);

        // A period starting later is cancelled whatever the reason, without a date field.
        $m = $this->buatSiswa(['nama' => 'Mira', 'tanggal_mulai' => '2026-10-20']);
        $periodeM = (int) $this->db->table('masa_aktif')->where('siswa_id', $m['id'])->get()->getRow()->id;
        $this->kirim('GET', "panel/siswa/{$m['id']}/nonaktifkan")->assertDontSee('name="tanggal_selesai"', null);
        $this->nonaktifkan($m, $periodeM, ['alasan_nonaktif' => 'keluar']);
        $this->seeInDatabase('masa_aktif', ['id' => $periodeM, 'tanggal_selesai' => '2026-10-20', 'dibatalkan' => 1]);
    }

    public function testReactivateOpensNewPeriodAndRestoresAccountStatus(): void
    {
        // FS-MD-04 item 5, FS-AKN-05 A2, docs/06 §6.6 rule 1, §6.7 rule 2
        $rombel7b = $this->buatRombel(['tahun_ajaran_id' => $this->ta['id'], 'nama' => '7B']);
        $aktif    = $this->buatSiswa(['nama' => 'Ayu', 'rombel_id' => $this->rombel7a['id']]);
        $belum    = $this->buatSiswa(['nama' => 'Bayu', 'rombel_id' => $this->rombel7a['id']]);
        $this->db->table('akun')->where('id', $aktif['akun_id'])->update(['status' => 'aktif']);

        Time::setTestNow('2026-10-01 07:00:00', 'Asia/Jakarta');
        foreach ([$aktif, $belum] as $s) {
            $periode = (int) $this->db->table('masa_aktif')->where('siswa_id', $s['id'])->get()->getRow()->id;
            $this->assertSame(['ok' => true, 'dibatalkan' => false], (new Siswa())->nonaktifkan((int) $s['id'], $periode, ['tanggal_selesai' => '2026-09-30', 'alasan_nonaktif' => 'keluar'], (int) $this->admin['id']));
        }

        Time::setTestNow('2026-10-13 07:00:00', 'Asia/Jakarta');
        $this->kirim('GET', "panel/siswa/{$aktif['id']}/aktifkan")->assertOK();

        $this->kirim('POST', "panel/siswa/{$aktif['id']}/aktifkan", ['tanggal_mulai' => '2026-09-30', 'rombel_id' => $rombel7b['id']]);
        $this->assertSame('Tanggal mulai aktif harus setelah tanggal terakhir aktif sebelumnya, 30 September 2026.', session('_ci_validation_errors')['tanggal_mulai']);

        $result = $this->kirim('POST', "panel/siswa/{$aktif['id']}/aktifkan", ['tanggal_mulai' => '2026-10-13', 'rombel_id' => $rombel7b['id']]);
        $result->assertRedirectTo("https://example.com/panel/siswa/{$aktif['id']}");
        $this->assertSame('Ayu aktif kembali mulai 13 Oktober 2026.', session('sukses'));
        $this->kirim('POST', "panel/siswa/{$belum['id']}/aktifkan", ['tanggal_mulai' => '2026-10-13', 'rombel_id' => $this->rombel7a['id']]);

        $this->seeInDatabase('akun', ['id' => $aktif['akun_id'], 'status' => 'aktif']);
        $this->seeInDatabase('akun', ['id' => $belum['akun_id'], 'status' => 'belum_aktif']);
        $this->assertSame(2, $this->db->table('masa_aktif')->where('siswa_id', $aktif['id'])->countAllResults());
        $this->assertSame('7B', (new Rujukan())->rombelSiswa((int) $aktif['id'], '2026-10-13')['nama']);
        $this->assertSame('7A', (new Rujukan())->rombelSiswa((int) $aktif['id'], '2026-10-12')['nama']);
        $this->seeInDatabase('penempatan', ['siswa_id' => $aktif['id'], 'rombel_id' => $this->rombel7a['id'], 'tanggal_selesai' => '2026-10-12']);

        $jenis = array_column($this->db->table('log_data_siswa')->where('siswa_id', $aktif['id'])->orderBy('id')->get()->getResultArray(), 'jenis');
        $this->assertSame(['dinonaktifkan', 'penempatan_diubah', 'diaktifkan_kembali', 'penempatan_dibuat'], $jenis);

        $this->kirim('POST', "panel/siswa/{$aktif['id']}/aktifkan", ['tanggal_mulai' => '2026-10-13', 'rombel_id' => $rombel7b['id']]);
        $this->assertSame('Siswa ini sudah aktif.', session('galat'));
    }

    public function testListFiltersByStatusAndClass(): void
    {
        // HAL-MD-04, docs/06 §6.6 rule 5
        $rombel7b = $this->buatRombel(['tahun_ajaran_id' => $this->ta['id'], 'nama' => '7B']);
        $this->buatSiswa(['nama' => 'Ayu Aktif', 'rombel_id' => $this->rombel7a['id']]);
        $this->buatSiswa(['nama' => 'Bima Baru', 'rombel_id' => $rombel7b['id'], 'tanggal_mulai' => '2026-10-20']);
        $c = $this->buatSiswa(['nama' => "Citra O'Neil", 'rombel_id' => $rombel7b['id']]);
        $this->db->table('masa_aktif')->where('siswa_id', $c['id'])->update(['tanggal_selesai' => '2026-10-01', 'alasan_nonaktif' => 'keluar']);

        $bawaan = $this->kirim('GET', 'panel/siswa');
        $bawaan->assertSee('Ayu Aktif');
        $bawaan->assertDontSee('Bima Baru');
        $bawaan->assertDontSee('Citra');

        $this->kirim('GET', 'panel/siswa', ['status' => 'akan_aktif'])->assertSee('Bima Baru');
        $nonaktif = $this->kirim('GET', 'panel/siswa', ['status' => 'nonaktif', 'cari' => "o'ne"]);
        $nonaktif->assertSee("Citra O'Neil");

        // `kelas` matches the class today, like the Kelas column.
        $kelas = $this->kirim('GET', 'panel/siswa', ['status' => 'semua', 'kelas' => (string) $rombel7b['id']]);
        $kelas->assertSee("Citra O'Neil");
        $kelas->assertDontSee('Bima Baru');
        $kelas->assertDontSee('Ayu Aktif');

        $salah = $this->kirim('GET', 'panel/siswa', ['status' => 'xyz']);
        $salah->assertSee('Sebagian saringan tidak dikenali');
        $salah->assertSee('Ayu Aktif');
    }

    public function testEmptyListLinksToAdd(): void
    {
        // E6
        $page = $this->kirim('GET', 'panel/siswa');
        $page->assertSee('Belum ada siswa.');
        $page->assertSee('Tambah siswa');
    }

    public function testWaliKelasSeesOnlyTheirRombel(): void
    {
        // docs/04 §4.1 items 3–4, HAL-MD-04, docs/09 RT-11
        $wali     = $this->buatAkun(['username' => 'wali.7a', 'nama' => 'Wali 7A']);
        $rombel7b = $this->buatRombel(['tahun_ajaran_id' => $this->ta['id'], 'nama' => '7B']);
        $this->db->table('rombel')->where('id', $this->rombel7a['id'])->update(['wali_kelas_id' => $wali['id']]);
        $g = $this->buatSiswa(['nama' => 'Gita Tujuh A', 'rombel_id' => $this->rombel7a['id']]);
        $b = $this->buatSiswa(['nama' => 'Galih Tujuh B', 'rombel_id' => $rombel7b['id']]);

        $list = $this->kirim('GET', 'panel/siswa', [], $wali);
        $list->assertSee('Gita Tujuh A');
        $list->assertDontSee('Galih Tujuh B');
        $list->assertDontSee('Tambah siswa');
        $this->kirim('GET', 'panel/siswa', ['kelas' => (string) $rombel7b['id']], $wali)->assertSee('Sebagian saringan tidak dikenali');

        $profil = $this->kirim('GET', "panel/siswa/{$g['id']}", [], $wali);
        $profil->assertOK();
        $profil->assertSee('Ubah nomor WA');
        $profil->assertSee('Status akun');
        $profil->assertSee('Log data');
        $profil->assertDontSee('Ubah data');

        $this->ditolak('GET', "panel/siswa/{$b['id']}", $wali);
        $this->ditolak('GET', 'panel/siswa/99999', $wali);

        $cari = $this->kirim('GET', 'panel/siswa/cari', ['cari' => 'Tujuh', 'untuk' => 'profil'], $wali, ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'text/html']);
        $cari->assertSee('Gita Tujuh A');
        $cari->assertDontSee('Galih Tujuh B');
    }

    public function testSearchFragmentFollowsEpMd01(): void
    {
        // docs/10 EP-MD-01, docs/09 RT-14 item 4
        $nisn = '0099887766';
        $this->buatSiswa(['nama' => 'Dewi Lestari', 'nisn' => $nisn, 'rombel_id' => $this->rombel7a['id']]);
        $lama = $this->buatSiswa(['nama' => 'Dewa Nonaktif']);
        $this->db->table('masa_aktif')->where('siswa_id', $lama['id'])->update(['tanggal_selesai' => '2026-10-01', 'alasan_nonaktif' => 'lulus']);
        $html = ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'text/html'];

        $hasil = $this->kirim('GET', 'panel/siswa/cari', ['cari' => 'dew', 'untuk' => 'profil'], null, $html);
        $hasil->assertOK();
        $this->assertStringStartsWith('text/html', $hasil->response()->getHeaderLine('Content-Type'));
        $hasil->assertSee('Dewi Lestari');
        $hasil->assertSee('Kelas 7A');
        $hasil->assertSee('Dewa Nonaktif');
        $hasil->assertSee('Nonaktif');
        $this->assertStringNotContainsString('<html', $hasil->response()->getBody());

        $this->kirim('GET', 'panel/siswa/cari', ['cari' => '00998', 'untuk' => 'profil'], null, $html)->assertSee('Dewi Lestari');
        $this->kirim('GET', 'panel/siswa/cari', ['cari' => '99887', 'untuk' => 'profil'], null, $html)->assertSee('Tidak ada siswa yang cocok.');

        foreach ([['cari' => 'd', 'untuk' => 'profil'], ['cari' => 'dewi', 'untuk' => 'lain'], ['cari' => str_repeat('a', 51), 'untuk' => 'profil'], ['untuk' => 'profil']] as $param) {
            $galat = $this->kirim('GET', 'panel/siswa/cari', $param, null, self::XHR);
            $galat->assertStatus(400);
            $this->assertSame('permintaan_rusak', json_decode($galat->getJSON(), true)['kode']);
        }

        for ($i = 0; $i < 21; $i++) {
            $this->buatSiswa(['nama' => "Eko Banyak {$i}"]);
        }
        $banyak = $this->kirim('GET', 'panel/siswa/cari', ['cari' => 'Eko', 'untuk' => 'profil'], null, $html);
        $banyak->assertSee('Ada 21 siswa yang cocok. Menampilkan 20 yang pertama. Persempit pencarian.');
        $this->assertSame(20, substr_count($banyak->response()->getBody(), 'list-group-item'));
    }

    public function testSearchIsLimitedPerAccount(): void
    {
        // docs/12 SEC-54, SEC-55: 120 per minute, then 429 `terlalu_sering` with Retry-After.
        $kunci = 'cari-siswa-' . $this->admin['id'];
        service('throttler')->remove($kunci);
        for ($i = 0; $i < 120; $i++) {
            service('throttler')->check($kunci, 120, MINUTE);
        }
        foreach (['router', 'response', 'request'] as $service) {
            Services::resetSingle($service);
        }

        $hasil = $this->withSession($this->sesiAkun($this->admin))->withHeaders(self::XHR)
            ->get('panel/siswa/cari', ['cari' => 'dewi', 'untuk' => 'profil']);

        $hasil->assertStatus(429);
        $this->assertSame('terlalu_sering', json_decode($hasil->getJSON(), true)['kode']);
        $this->assertGreaterThanOrEqual(1, (int) $hasil->response()->getHeaderLine('Retry-After'));
        service('throttler')->remove($kunci);
    }

    public function testSearchWithoutHaMd05Is403(): void
    {
        // EP-MD-01: 403 `ditolak` when `untuk` needs a right the account lacks.
        $staf  = $this->buatAkun(['username' => 'staf.biasa']);
        $hasil = $this->kirim('GET', 'panel/siswa/cari', ['cari' => 'dewi', 'untuk' => 'profil'], $staf, self::XHR);
        $hasil->assertStatus(403);
        $this->assertSame('ditolak', json_decode($hasil->getJSON(), true)['kode']);
    }

    /**
     * Asserts the generic 403 (docs/04 §4.1 item 3): the `hak` filter answers
     * a background request with JSON `ditolak`; a scope check in the
     * controller throws DiLuarHak, which the exception handler renders as 403.
     *
     * @param array<string, mixed> $akun
     * @param array<string, mixed> $isian
     */
    private function ditolak(string $method, string $uri, array $akun, array $isian = []): void
    {
        try {
            $result = $this->kirim($method, $uri, $isian, $akun, self::XHR);
        } catch (DiLuarHak) {
            $this->addToAssertionCount(1);

            return;
        }

        $result->assertStatus(403);
        $this->assertSame('ditolak', json_decode($result->getJSON(), true)['kode'], "{$method} {$uri}");
    }

    /**
     * @param array<string, mixed>      $isian
     * @param array<string, mixed>|null $akun    Defaults to the admin
     * @param array<string, string>     $headers
     * @param array<string, mixed>      $sesi    Extra session data
     */
    private function kirim(string $method, string $uri, array $isian = [], ?array $akun = null, array $headers = [], array $sesi = []): TestResponse
    {
        foreach (['router', 'response', 'request'] as $service) {
            Services::resetSingle($service);
        }
        service('akunAktif')->clear();
        $akun ??= $this->admin;
        // Search throttle counts (SEC-54) live in the cache and outlast the test database.
        service('throttler')->remove('cari-siswa-' . $akun['id']);

        return $this->withSession($sesi + $this->sesiAkun($akun))
            ->withHeaders($headers + ['X-CSRF-TOKEN' => service('security')->getHash()])
            ->call($method, $uri, $isian);
    }

    /**
     * @param array<string, mixed> $isian Fields that differ from a valid new student
     */
    private function tambah(array $isian): TestResponse
    {
        return $this->kirim('POST', 'panel/siswa', $isian + [
            'nisn' => '1234567890', 'nama' => 'Siswa Uji', 'rombel_id' => (string) $this->rombel7a['id'], 'tanggal_mulai' => '2026-10-13',
        ]);
    }

    /**
     * @param array<string, mixed> $siswa
     * @param array<string, mixed> $isian
     */
    private function ubah(array $siswa, array $isian): TestResponse
    {
        return $this->kirim('POST', "panel/siswa/{$siswa['id']}", $isian + ['_method' => 'PATCH', 'versi' => $siswa['updated_at'], 'nisn' => $siswa['nisn']]);
    }

    /**
     * @param array<string, mixed> $siswa
     * @param array<string, mixed> $isian
     */
    private function nonaktifkan(array $siswa, int $periodeId, array $isian): TestResponse
    {
        return $this->kirim('POST', "panel/siswa/{$siswa['id']}/nonaktifkan", $isian + ['periode_id' => (string) $periodeId]);
    }
}
