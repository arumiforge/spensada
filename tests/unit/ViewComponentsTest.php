<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Renders the view components and area layouts of docs/08 UI-28 and §6.
 *
 * @internal
 */
final class ViewComponentsTest extends CIUnitTestCase
{
    private function render(string $view, array $data = []): string
    {
        $html = view($view, $data, ['saveData' => false]);

        // The debug toolbar wraps every view in comments while testing.
        return preg_replace('/<!-- DEBUG-VIEW (START|ENDED) [^>]*-->\R?/', '', $html);
    }

    public function testChipStatusShowsColorIconAndText(): void
    {
        $html = $this->render('komponen/chip_status', ['status' => 'terlambat']);

        $this->assertStringContainsString('class="chip-status status-terlambat"', $html);
        $this->assertStringContainsString('ikon.svg?v=' . config('Spensada')->versi . '#clock-alert', $html);
        $this->assertStringContainsString('aria-hidden="true"', $html);
        $this->assertStringContainsString('Terlambat</span>', $html);
    }

    public function testChipStatusForEmptyStatusIsBelumHadir(): void
    {
        $html = $this->render('komponen/chip_status', ['status' => null]);

        $this->assertStringContainsString('status-belum', $html);
        $this->assertStringContainsString('#circle-dashed', $html);
        $this->assertStringContainsString('Belum hadir', $html);
    }

    public function testFlashMessageRoles(): void
    {
        $html = $this->render('komponen/pesan_kilat', ['sukses' => 'Data disimpan.', 'galat' => '<b>Gagal</b>']);

        $this->assertStringContainsString('role="status"', $html);
        $this->assertStringContainsString('role="alert"', $html);
        $this->assertStringContainsString('&lt;b&gt;Gagal&lt;/b&gt;', $html);
        $this->assertSame('', trim($this->render('komponen/pesan_kilat', ['sukses' => null, 'galat' => null])));
    }

    public function testStudentPhotoWithFile(): void
    {
        $html = $this->render('komponen/foto_siswa', ['name' => 'Dewi Lestari', 'src' => '/foto/1.jpg', 'size' => 'daftar', 'decorative' => true]);

        $this->assertStringContainsString('class="foto-siswa foto-siswa-daftar"', $html);
        $this->assertStringContainsString('alt=""', $html);
        $this->assertStringContainsString('width="36" height="48" loading="lazy"', $html);
    }

    public function testStudentPhotoFallbackShowsInitials(): void
    {
        $html = $this->render('komponen/foto_siswa', ['name' => ' ahmad  fauzi rahman ', 'src' => null, 'size' => 'profil', 'decorative' => false]);

        $this->assertStringContainsString('class="foto-siswa foto-siswa-profil"', $html);
        $this->assertStringContainsString('role="img"', $html);
        $this->assertStringContainsString('>AF</span>', $html);

        $single = $this->render('komponen/foto_siswa', ['name' => 'Ésa', 'src' => '', 'size' => 'daftar', 'decorative' => true]);
        $this->assertStringContainsString('aria-hidden="true">É</span>', $single);
    }

    public function testPageHeader(): void
    {
        $html = $this->render('komponen/kepala_halaman', ['title' => 'Data siswa', 'context' => 'Kelas 7A', 'actions' => '<a class="btn btn-primary" href="#">Tambah</a>']);

        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertStringContainsString('<h1>Data siswa</h1>', $html);
        $this->assertStringContainsString('Kelas 7A', $html);
        $this->assertStringContainsString('class="kepala-halaman-aksi"><a class="btn btn-primary"', $html);
    }

    public function testListPageShowsTotalAndPageLinks(): void
    {
        $html = $this->render('komponen/halaman_daftar', ['total' => 1024, 'page' => 2, 'perPage' => 50, 'table' => '<table class="table"></table>', 'empty' => 'Belum ada siswa.']);

        $this->assertStringContainsString('Menampilkan 51–100 dari 1.024 data', $html);
        $this->assertStringContainsString('page=1', $html);
        $this->assertStringContainsString('rel="prev"', $html);
        $this->assertStringContainsString('page=3', $html);
        $this->assertStringContainsString('rel="next"', $html);
        $this->assertStringNotContainsString('page=', (string) current_url(true), 'Request URI must not change');

        $last = $this->render('komponen/halaman_daftar', ['total' => 120, 'page' => 3, 'perPage' => 50, 'table' => '', 'empty' => '']);
        $this->assertStringContainsString('101–120 dari 120', $last);
        $this->assertStringNotContainsString('rel="next"', $last);
    }

    public function testListPageEmptyState(): void
    {
        $html = $this->render('komponen/halaman_daftar', ['total' => 0, 'page' => 1, 'perPage' => 50, 'table' => '', 'empty' => 'Belum ada siswa.']);

        $this->assertStringContainsString('Belum ada siswa.', $html);
        $this->assertStringNotContainsString('pagination', $html);
    }

    public function testAreaLayoutsShareHeadAndLandmarks(): void
    {
        foreach (['layout/panel', 'layout/portal', 'layout/akun'] as $layout) {
            $html = $this->render($layout, ['title' => 'Contoh']);

            $this->assertStringContainsString('<html lang="id">', $html, $layout);
            $this->assertStringContainsString('<title>Contoh · Spensada</title>', $html, $layout);
            $this->assertStringContainsString('href="#isi">Lewati ke isi</a>', $html, $layout);
            $this->assertStringContainsString('<main id="isi"', $html, $layout);
            $this->assertStringContainsString('<header', $html, $layout);
            $this->assertCssOrder($html);
        }
    }

    public function testViewsUseNoStyleAttributesOrInlineScripts(): void
    {
        $files = array_merge(glob(APPPATH . 'Views/layout/*.php'), glob(APPPATH . 'Views/komponen/*.php'), glob(APPPATH . 'Views/akun/*.php'));

        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $source = file_get_contents($file);
            $this->assertDoesNotMatchRegularExpression('/\sstyle\s*=/i', $source, $file);
            $this->assertDoesNotMatchRegularExpression('/<script(?![^>]*\ssrc=)/i', $source, $file);
            $this->assertDoesNotMatchRegularExpression('/\son[a-z]+\s*=/i', $source, $file);
        }
    }

    /**
     * Bootstrap, then token.css, then spensada.css (docs/08 UI-73).
     */
    private function assertCssOrder(string $html): void
    {
        preg_match_all('/<link rel="stylesheet" href="([^"]+)"/', $html, $matches);

        $version = '?v=' . config('Spensada')->versi;
        $this->assertSame([
            base_url('aset/vendor/bootstrap/5.3.8/css/bootstrap.min.css') . $version,
            base_url('aset/css/token.css') . $version,
            base_url('aset/css/spensada.css') . $version,
        ], $matches[1]);
    }
}
