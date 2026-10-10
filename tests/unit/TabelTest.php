<?php

use App\Services\Berkas\Tabel;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Import file reading and spreadsheet writing (docs/13 IM-04 to IM-07,
 * IM-16, IE-08, IE-09, docs/12 SEC-46, SEC-49, docs/11 VAL-28).
 *
 * @internal
 */
final class TabelTest extends CIUnitTestCase
{
    private string $dir;
    private Tabel $tabel;

    protected function setUp(): void
    {
        parent::setUp();
        Services::resetSingle('response');
        $this->dir   = sys_get_temp_dir() . '/tabel-test-' . bin2hex(random_bytes(4));
        $this->tabel = new Tabel();
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->dir));
        parent::tearDown();
    }

    public function testXlsxCellsKeepTypesAndRowNumbers(): void
    {
        $book  = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->fromArray(['NISN', 'Nama Lengkap', 'Tanggal Lahir', 'Catatan'], null, 'A1');
        $sheet->setCellValueExplicit('A2', '0012345678', DataType::TYPE_STRING);
        $sheet->setCellValue('B2', '  Budi Santoso  ');
        $sheet->setCellValue('C2', Date::PHPToExcel(new DateTime('2011-03-14')));
        $sheet->getStyle('C2')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        $sheet->setCellValueExplicit('D2', '=bukan rumus', DataType::TYPE_STRING);
        // Row 3 is empty and skipped; row 4 keeps its number.
        $sheet->setCellValue('A4', 12345678);
        $sheet->setCellValue('B4', '=CONCAT("A","B")');
        $book->createSheet()->setTitle('Lain')->setCellValue('A1', 'tidak dibaca');
        $path = $this->simpanXlsx($book);

        $hasil = $this->tabel->baca($path, 'siswa.xlsx');

        $this->assertSame(['NISN', 'Nama Lengkap', 'Tanggal Lahir', 'Catatan'], $hasil['judul']);
        $this->assertCount(2, $hasil['baris']);

        [$satu, $dua] = $hasil['baris'];
        $this->assertSame(2, $satu['nomor']);
        $this->assertSame(['0012345678', 'Budi Santoso', '2011-03-14', '=bukan rumus'], $satu['sel']);
        $this->assertSame([], $satu['rumus']);
        $this->assertSame([], $satu['angka']);
        $this->assertSame([2 => '2011-03-14'], $satu['tanggal']);

        $this->assertSame(4, $dua['nomor']);
        $this->assertSame('12345678', $dua['sel'][0]);
        $this->assertSame([0], $dua['angka']);
        $this->assertSame([1], $dua['rumus']);
    }

    public function testCsvWithBomAndSemicolon(): void
    {
        $path = $this->dir . '/siswa.csv';
        file_put_contents($path, "\xEF\xBB\xBFNISN;Nama Lengkap;Kelas\r\n0012345678;\"Siti; Aminah\";7A\r\n\r\n;;\r\n0012345679;Budi;7B\r\n");

        $hasil = $this->tabel->baca($path, 'siswa.csv');

        $this->assertSame(['NISN', 'Nama Lengkap', 'Kelas'], $hasil['judul']);
        $this->assertSame([['0012345678', 'Siti; Aminah', '7A'], ['0012345679', 'Budi', '7B']], array_column($hasil['baris'], 'sel'));
        $this->assertSame([2, 5], array_column($hasil['baris'], 'nomor'));
    }

    public function testCsvWithCommaAndWindows1252(): void
    {
        $path = $this->dir . '/siswa.csv';
        file_put_contents($path, "NISN,Nama Lengkap\n0012345678,Andr\xE9\n");

        $hasil = $this->tabel->baca($path, 'siswa.csv');

        $this->assertSame(['0012345678', 'André'], $hasil['baris'][0]['sel']);
    }

    public function testMoreThan2000RowsIsRejectedWithTheCount(): void
    {
        $path = $this->dir . '/siswa.csv';
        file_put_contents($path, "NISN,Nama Lengkap\n" . str_repeat("0012345678,Budi\n", 2150));

        $this->assertSame(
            ['galat' => 'File berisi 2.150 baris data. Paling banyak 2.000 baris per file. Bagi file menjadi beberapa bagian.'],
            $this->tabel->baca($path, 'siswa.csv'),
        );
    }

    public function testRenamedFileIsNotAnImportFile(): void
    {
        $path = $this->dir . '/siswa.xlsx';
        file_put_contents($path, "<?php echo 'x';");

        $this->assertSame(Tabel::PESAN_FORMAT, $this->tabel->periksa($path, 'siswa.xlsx'));
        $this->assertSame(Tabel::PESAN_FORMAT, $this->tabel->periksa($path, 'siswa.xls'));
        $this->assertSame(['galat' => Tabel::PESAN_FORMAT], $this->tabel->baca($path, 'siswa.xlsx'));
    }

    public function testZipWithoutWorkbookIsNotXlsx(): void
    {
        $path = $this->dir . '/siswa.xlsx';
        $zip  = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('a.txt', 'x');
        $zip->close();

        $this->assertSame(Tabel::PESAN_FORMAT, $this->tabel->periksa($path, 'siswa.xlsx'));
    }

    public function testFileOver5MbIsRejected(): void
    {
        $path = $this->dir . '/siswa.csv';
        file_put_contents($path, "NISN\n" . str_repeat('0', 5 * 1024 * 1024));

        $this->assertSame('Ukuran file siswa.csv 5,1 MB. Paling besar 5 MB.', $this->tabel->periksa($path, 'siswa.csv'));
    }

    public function testXlsxOver50MbUnzippedIsRejected(): void
    {
        $path = $this->dir . '/siswa.xlsx';
        $zip  = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('xl/workbook.xml', '<workbook/>');
        $zip->addFromString('xl/worksheets/sheet1.xml', str_repeat(' ', 51 * 1024 * 1024));
        $zip->close();

        $this->assertSame('File XLSX terlalu besar setelah dibuka. Simpan ulang hanya lembar data, atau pakai CSV.', $this->tabel->periksa($path, 'siswa.xlsx'));
    }

    public function testCocokkanKolomIgnoresCaseSpacesAndOrder(): void
    {
        $hasil = $this->tabel->cocokkanKolom([' kelas ', 'NISN', 'nama lengkap'], $this->kolom());

        $this->assertSame(['peta' => ['kelas' => 0, 'nisn' => 1, 'nama' => 2]], $hasil);
        $this->assertSame(['peta' => ['nisn' => 0, 'nama' => 1, 'kelas' => 2]], $this->tabel->cocokkanKolom(['NISN', 'Nama Lengkap', 'Rombel'], $this->kolom()));
    }

    public function testCocokkanKolomReportsMissingUnknownAndDuplicate(): void
    {
        $hasil = $this->tabel->cocokkanKolom(['NISN', 'Nisn', 'Hobi', ''], $this->kolom());

        $this->assertSame([
            'Kolom wajib tidak ada: Nama Lengkap, Kelas.',
            'Kolom tidak dikenal: Hobi, (kolom tanpa judul). Hapus kolom itu, atau periksa ejaannya.',
            'Kolom ganda: NISN.',
        ], $hasil['galat']);
    }

    public function testCsvOutputEscapesFormulaText(): void
    {
        $csv = $this->tabel->csv(['baris', 'Nama', 'alasan'], [[2, '=HYPERLINK("x")', 'NISN kosong.'], [3, '-5', '@a']]);

        $this->assertSame("\xEF\xBB\xBFbaris,Nama,alasan\n2,\"'=HYPERLINK(\"\"x\"\")\",\"NISN kosong.\"\n3,'-5,'@a\n", $csv);
    }

    public function testIsiTeksWritesTextWithQuotePrefix(): void
    {
        $book  = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        Tabel::isiTeks($sheet, 'A1', '=1+1');
        Tabel::isiTeks($sheet, 'A2', '0012345678');
        $path = $this->dir . '/x.xlsx';
        file_put_contents($path, $this->tabel->xlsx($book));

        $hasil = $this->tabel->baca($path, 'x.xlsx');
        $this->assertSame(['=1+1'], $hasil['judul']);
        $this->assertSame(['0012345678'], $hasil['baris'][0]['sel']);
        $this->assertSame([], $hasil['baris'][0]['rumus']);
    }

    public function testUnduhIsAnUncachedAttachment(): void
    {
        $response = Tabel::unduh('x', 'spensada_template-siswa_2026/2027.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->assertSame('attachment; filename="spensada_template-siswa_2026-2027.xlsx"', $response->getHeaderLine('Content-Disposition'));
        $this->assertSame('no-store', $response->getHeaderLine('Cache-Control'));
        $this->assertSame('x', $response->getBody());
    }

    /**
     * @return array<string, array{judul: list<string>, wajib: bool}>
     */
    private function kolom(): array
    {
        return [
            'nisn'  => ['judul' => ['NISN'], 'wajib' => true],
            'nama'  => ['judul' => ['Nama Lengkap'], 'wajib' => true],
            'kelas' => ['judul' => ['Kelas', 'Rombel'], 'wajib' => true],
        ];
    }

    private function simpanXlsx(Spreadsheet $book): string
    {
        $path = $this->dir . '/' . bin2hex(random_bytes(4)) . '.xlsx';
        (new Xlsx($book))->save($path);

        return $path;
    }
}
