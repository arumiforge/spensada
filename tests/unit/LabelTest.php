<?php

use CodeIgniter\Test\CIUnitTestCase;
use Config\Label;

/**
 * @internal
 */
final class LabelTest extends CIUnitTestCase
{
    public function testDailyStatusesMatchDocs08(): void
    {
        $status = config(Label::class)->status;

        $this->assertSame(['hadir', 'terlambat', 'izin', 'sakit', 'dispensasi', 'alpa', ''], array_keys($status));
        $this->assertSame(['label' => 'Belum hadir', 'letter' => '–', 'icon' => 'circle-dashed'], $status['']);
        $this->assertSame('H', $status['hadir']['letter']);
        $this->assertSame('clock-alert', $status['terlambat']['icon']);
    }

    public function testEveryStatusIconIsInTheSprite(): void
    {
        $sprite = file_get_contents(FCPATH . 'aset/ikon/ikon.svg');

        foreach (config(Label::class)->status as $code => $label) {
            $this->assertStringContainsString('<symbol id="' . $label['icon'] . '"', $sprite, "Icon for status '{$code}'");
        }
    }

    public function testValueCodesCoverDocs08Section92(): void
    {
        $codes = config(Label::class)->codes;

        $this->assertCount(16, $codes);
        $this->assertSame('Kelas', $codes['libur.cakupan']['rombel']);
        $this->assertSame('Tiba setelah sesi masuk ditutup', $codes['presensi_manual.alasan']['tiba_setelah_tutup']);
        $this->assertSame('Guru BK', $codes['akun_role.role']['guru_bk']);
        $this->assertSame('Diterima', $codes['scan_tinjauan.keputusan']['terima']);
    }

    public function testRombelShowsAsKelas(): void
    {
        $this->assertSame('Kelas', config(Label::class)->terms['rombel']);
        $this->assertSame('Perlu diperiksa', config(Label::class)->terms['penanda']);
    }
}
