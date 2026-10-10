<?php

use App\Services\Berkas\Gambar;
use App\Services\Berkas\Penyaji;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

/**
 * File serving and image checks (docs/07 ARS-52, ARS-53, docs/12 SEC-49 to
 * SEC-52, SEC-81, docs/11 VAL-28).
 *
 * @internal
 */
final class BerkasTest extends CIUnitTestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        Services::resetSingle('response');
        $this->dir = sys_get_temp_dir() . '/berkas-test-' . bin2hex(random_bytes(4));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . '/*'));
        rmdir($this->dir);
        parent::tearDown();
    }

    public function testTampilkanSendsFileInlineWithoutCache(): void
    {
        $relatif = 'foto/' . bin2hex(random_bytes(16)) . '.jpg';
        $path    = WRITEPATH . 'uploads/' . $relatif;
        is_dir(dirname($path)) || mkdir(dirname($path), 0755, true);
        $image = imagecreatetruecolor(4, 4);
        imagejpeg($image, $path);

        try {
            $response = (new Penyaji())->tampilkan($relatif);
        } finally {
            unlink($path);
        }

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('image/jpeg', $response->getHeaderLine('Content-Type'));
        $this->assertSame('inline', $response->getHeaderLine('Content-Disposition'));
        $this->assertSame('no-store', $response->getHeaderLine('Cache-Control'));
        $this->assertSame("sandbox; default-src 'none'; frame-ancestors 'none'", $response->getHeaderLine('Content-Security-Policy'));
        $this->assertStringStartsWith("\xFF\xD8", $response->getBody());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unsafePaths(): iterable
    {
        yield 'empty' => [''];
        yield 'missing' => ['foto/' . str_repeat('a', 32) . '.jpg'];
        yield 'parent' => ['../.env'];
        yield 'nested parent' => ['foto/../../.env'];
        yield 'backslash parent' => ['foto\\..\\..\\.env'];
        yield 'absolute' => ['/etc/passwd'];
        yield 'drive' => ['C:/Windows/win.ini'];
        yield 'folder' => ['logo'];
    }

    /**
     * @dataProvider unsafePaths
     */
    public function testTampilkanAnswers404ForMissingOrUnsafePath(string $path): void
    {
        $this->expectException(PageNotFoundException::class);

        (new Penyaji())->tampilkan($path);
    }

    public function testLogoFallsBackToBundledEmblemWithOneDayCache(): void
    {
        $response = (new Penyaji())->logo(null);

        $this->assertSame('image/png', $response->getHeaderLine('Content-Type'));
        $this->assertSame('public, max-age=86400', $response->getHeaderLine('Cache-Control'));
        $this->assertSame("sandbox; default-src 'none'; frame-ancestors 'none'", $response->getHeaderLine('Content-Security-Policy'));
        $this->assertSame(file_get_contents(FCPATH . 'aset/logo/logo-sekolah.png'), $response->getBody());

        // A stored path whose file is gone also shows the emblem.
        $gone = (new Penyaji())->logo('logo/' . str_repeat('b', 32) . '.png');
        $this->assertSame(file_get_contents(FCPATH . 'aset/logo/logo-sekolah.png'), $gone->getBody());
    }

    public function testPeriksaAcceptsJpgPngWebp(): void
    {
        $image = imagecreatetruecolor(10, 10);
        imagejpeg($image, $this->dir . '/a');
        imagepng($image, $this->dir . '/b');
        imagewebp($image, $this->dir . '/c');

        $gambar = new Gambar();
        $this->assertNull($gambar->periksa($this->dir . '/a', 'Logo.JPEG'));
        $this->assertNull($gambar->periksa($this->dir . '/b', 'logo.png'));
        $this->assertNull($gambar->periksa($this->dir . '/c', 'logo.webp'));
    }

    public function testPeriksaRejectsDangerousAndMismatchedFiles(): void
    {
        $format = 'File harus berupa gambar JPG, PNG, atau WebP.';
        $gambar = new Gambar();

        // docs/12 SEC-81: PHP renamed to an image, and SVG.
        file_put_contents($this->dir . '/php', "<?php echo 'x';");
        $this->assertSame($format, $gambar->periksa($this->dir . '/php', 'logo.png'));
        file_put_contents($this->dir . '/svg', '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
        $this->assertSame($format, $gambar->periksa($this->dir . '/svg', 'logo.svg'));
        $this->assertSame($format, $gambar->periksa($this->dir . '/svg', 'logo.png'));

        // The extension must match the content (SEC-50).
        imagepng(imagecreatetruecolor(4, 4), $this->dir . '/png');
        $this->assertSame($format, $gambar->periksa($this->dir . '/png', 'logo.jpg'));
        $this->assertSame($format, $gambar->periksa($this->dir . '/png', 'logo'));
    }

    public function testPeriksaRejectsLargeFilesAndImages(): void
    {
        $gambar = new Gambar();

        file_put_contents($this->dir . '/besar', str_repeat('x', Gambar::UKURAN_MAKS + 1));
        $this->assertSame('Ukuran file besar.png 10,1 MB. Paling besar 10 MB.', $gambar->periksa($this->dir . '/besar', 'besar.png'));

        // A PNG header claiming 6.000×5.000 px: refused before GD loads it (ARS-53 step 1).
        $ihdr = 'IHDR' . pack('NNCCCCC', 6000, 5000, 8, 6, 0, 0, 0);
        file_put_contents($this->dir . '/mp', "\x89PNG\r\n\x1a\n" . pack('N', 13) . $ihdr . pack('N', crc32($ihdr)));
        $this->assertSame('Gambar poster.png terlalu besar (6.000×5.000 piksel). Perkecil dulu, lalu unggah lagi.', $gambar->periksa($this->dir . '/mp', 'poster.png'));

        $nol = 'IHDR' . pack('NNCCCCC', 0, 0, 8, 6, 0, 0, 0);
        file_put_contents($this->dir . '/rusak', "\x89PNG\r\n\x1a\n" . pack('N', 13) . $nol . pack('N', crc32($nol)));
        $this->assertSame('File rusak.png tidak dapat dibaca sebagai gambar.', $gambar->periksa($this->dir . '/rusak', 'rusak.png'));
    }

    public function testSimpanLogoReturnsNullForDamagedImage(): void
    {
        // Header intact, pixel data cut off: getimagesize() passes, GD does not.
        imagepng(imagecreatetruecolor(300, 300), $this->dir . '/utuh');
        $isi = (string) file_get_contents($this->dir . '/utuh');
        file_put_contents($this->dir . '/potong', substr($isi, 0, 60));
        $sebelum = glob(WRITEPATH . 'uploads/logo/*');

        $this->assertNull((new Gambar())->periksa($this->dir . '/potong', 'potong.png'));
        $this->assertNull((new Gambar())->simpanLogo($this->dir . '/potong'));
        $this->assertSame($sebelum, glob(WRITEPATH . 'uploads/logo/*'));
    }

    public function testSimpanLogoShrinksToPngAndKeepsTransparency(): void
    {
        $image = imagecreatetruecolor(1000, 500);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
        imagepng($image, $this->dir . '/besar');

        $relatif = (new Gambar())->simpanLogo($this->dir . '/besar');
        $path    = WRITEPATH . 'uploads/' . $relatif;

        try {
            $this->assertMatchesRegularExpression('#^logo/[0-9a-f]{32}\.png$#', $relatif);
            [$w, $h, $type] = getimagesize($path);
            $this->assertSame([512, 256, IMAGETYPE_PNG], [$w, $h, $type]);
            $hasil = imagecreatefrompng($path);
            $this->assertSame(127, imagecolorsforindex($hasil, imagecolorat($hasil, 10, 10))['alpha']);
        } finally {
            unlink($path);
        }
    }

    public function testSimpanLogoKeepsSmallImageSizeAndConvertsJpeg(): void
    {
        imagejpeg(imagecreatetruecolor(200, 100), $this->dir . '/kecil');

        $path = WRITEPATH . 'uploads/' . (new Gambar())->simpanLogo($this->dir . '/kecil');

        try {
            $this->assertSame([200, 100, IMAGETYPE_PNG], array_slice(getimagesize($path), 0, 3));
        } finally {
            unlink($path);
        }
    }

    public function testSimpanFotoMakesThreeJpegSizesOnWhite(): void
    {
        $image = imagecreatetruecolor(1200, 1200);
        imagesavealpha($image, true);
        imagealphablending($image, false);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
        imagepng($image, $this->dir . '/foto');

        $gambar  = new Gambar();
        $relatif = $gambar->simpanFoto($this->dir . '/foto');
        $nama    = basename($relatif);

        try {
            $this->assertMatchesRegularExpression('#^foto/[0-9a-f]{32}\.jpg$#', $relatif);

            foreach (['foto/' => 600, 'foto/kiosk/' => 300, 'foto/kecil/' => 120] as $folder => $sisi) {
                $path = WRITEPATH . 'uploads/' . $folder . $nama;
                $this->assertSame([$sisi, $sisi, IMAGETYPE_JPEG], array_slice(getimagesize($path), 0, 3), $folder);
                $warna = imagecolorsforindex($hasil = imagecreatefromjpeg($path), imagecolorat($hasil, 5, 5));
                $this->assertGreaterThan(245, $warna['red'], $folder);
            }
        } finally {
            $gambar->hapusFoto($relatif);
        }

        $this->assertFileDoesNotExist(WRITEPATH . 'uploads/foto/kecil/' . $nama);
    }

    public function testSimpanFotoNeverEnlarges(): void
    {
        imagejpeg(imagecreatetruecolor(100, 80), $this->dir . '/kecil');

        $gambar  = new Gambar();
        $relatif = $gambar->simpanFoto($this->dir . '/kecil');

        try {
            $this->assertSame([100, 80], array_slice(getimagesize(WRITEPATH . 'uploads/' . $relatif), 0, 2));
            $this->assertSame([100, 80], array_slice(getimagesize(WRITEPATH . 'uploads/foto/kecil/' . basename($relatif)), 0, 2));
        } finally {
            $gambar->hapusFoto($relatif);
        }
    }

    public function testSimpanFotoReturnsNullForDamagedImage(): void
    {
        file_put_contents($this->dir . '/rusak', "\xFF\xD8\xFF\xE0" . str_repeat('x', 100));

        $this->assertNull((new Gambar())->simpanFoto($this->dir . '/rusak'));
    }
}
