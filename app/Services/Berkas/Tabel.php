<?php

namespace App\Services\Berkas;

use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\HTTP\ResponseInterface;
use finfo;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Throwable;
use ZipArchive;

/**
 * Import files and spreadsheet downloads shared by import siswa and import
 * penempatan (docs/13 IM-04 to IM-07, IM-16, IE-07 to IE-09, docs/12
 * SEC-46, SEC-49, docs/11 VAL-28, §5.2 FS-MD-06 E2).
 *
 * baca() returns every non-empty data row with its row number in the file.
 * Cell values are trimmed strings; the row also says which columns held a
 * formula, a number, or a date, so the import rules can tell "NISN lost its
 * leading zero" from "NISN too short" (FS-MD-06, R-12).
 */
class Tabel
{
    /** 5 MB per import file (SEC-49, IM-16 item 1). */
    public const UKURAN_MAKS = 5 * 1024 * 1024;

    /** 2,000 data rows per file (IM-16 item 2). */
    public const BARIS_MAKS = 2000;

    /** 50 MB of XLSX content after unzipping (IM-16 item 3). */
    public const ISI_XLSX_MAKS = 50 * 1024 * 1024;

    public const PESAN_FORMAT = 'File harus berupa XLSX atau CSV. Unduh template bila perlu.';

    public const PESAN_RUMUS = 'Sel berisi rumus. Salin sebagai nilai, lalu unggah lagi.';

    private const PESAN_SERVER = 'File tidak dapat disimpan karena gangguan server. Coba lagi beberapa saat lagi.';

    /** Starts of text that a spreadsheet may run as a formula (SEC-46). */
    private const AWAL_RUMUS = ['=', '+', '-', '@', "\t", "\r"];

    /**
     * VAL-28 / GAL-11 message for the import file field, or null when the
     * file may be read.
     */
    public function periksaUnggahan(?UploadedFile $file): ?string
    {
        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return 'Pilih file XLSX atau CSV lebih dulu.';
        }

        $nama = $file->getClientName();

        // docs/11 GAL-11 item 3, as in Gambar::periksaUnggahan().
        switch ($file->getError()) {
            case UPLOAD_ERR_OK:
                return $this->periksa($file->getTempName(), $nama);

            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                log_message('info', 'Upload over the PHP size limit: {name}', ['name' => $nama]);

                return "Ukuran file {$nama} melebihi batas.";

            case UPLOAD_ERR_PARTIAL:
                log_message('info', 'Partial upload: {name}', ['name' => $nama]);

                return "Unggahan {$nama} terputus. Coba lagi.";

            default:
                log_message('critical', 'Upload failed with PHP error {code}: {name}', ['code' => $file->getError(), 'name' => $nama]);

                return self::PESAN_SERVER;
        }
    }

    /**
     * Format and size checks before any row is read (IM-16 items 1, 3, 5).
     *
     * @param string $path File on disk
     * @param string $nama Original file name, for the extension and the message
     */
    public function periksa(string $path, string $nama): ?string
    {
        $ukuran = (int) filesize($path);

        if ($ukuran > self::UKURAN_MAKS) {
            return "Ukuran file {$nama} " . format_number(ceil($ukuran / 104857.6) / 10, 1) . ' MB. Paling besar ' . format_number(self::UKURAN_MAKS / 1048576) . ' MB.';
        }

        return match ($this->jenis($path, $nama)) {
            'xlsx'  => $this->periksaXlsx($path),
            'csv'   => null,
            default => self::PESAN_FORMAT,
        };
    }

    /**
     * `xlsx` or `csv` from the extension, confirmed by the content, or null.
     */
    public function jenis(string $path, string $nama): ?string
    {
        $ekstensi = strtolower(pathinfo($nama, PATHINFO_EXTENSION));
        $mime     = (string) (new finfo(FILEINFO_MIME_TYPE))->file($path);

        if ($ekstensi === 'xlsx' && in_array($mime, ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'], true)) {
            $zip = new ZipArchive();

            if ($zip->open($path, ZipArchive::RDONLY) !== true) {
                return null;
            }
            $sah = $zip->locateName('xl/workbook.xml') !== false;
            $zip->close();

            return $sah ? 'xlsx' : null;
        }

        if ($ekstensi === 'csv' && (str_starts_with($mime, 'text/') || in_array($mime, ['application/csv', 'application/x-empty'], true))) {
            return str_contains((string) file_get_contents($path, false, null, 0, 65536), "\0") ? null : 'csv';
        }

        return null;
    }

    /**
     * Header and data rows of the first sheet (IM-04 to IM-06, IM-16).
     *
     * Rows: `nomor` (row number in the file, the header is row 1), `sel`
     * (trimmed strings, same order as `judul`; a date cell reads Y-m-d), and
     * the column indexes that held a formula (`rumus`), a number (`angka`),
     * or a date cell (`tanggal`, index => Y-m-d). Empty rows are left out.
     *
     * @return array{judul: list<string>, baris: list<array{nomor: int, sel: list<string>, rumus: list<int>, angka: list<int>, tanggal: array<int, string>}>}|array{galat: string}
     */
    public function baca(string $path, string $nama): array
    {
        try {
            $hasil = match ($this->jenis($path, $nama)) {
                'xlsx'  => $this->bacaXlsx($path),
                'csv'   => $this->bacaCsv($path),
                default => null,
            };
        } catch (Throwable $e) {
            log_message('info', 'Import file could not be read: {name} ({message})', ['name' => $nama, 'message' => $e->getMessage()]);
            $hasil = null;
        }

        if ($hasil === null) {
            return ['galat' => self::PESAN_FORMAT];
        }
        if ($hasil['judul'] === []) {
            return ['galat' => 'File kosong. Isi data mulai baris 2, dengan judul kolom di baris 1.'];
        }
        if (count($hasil['baris']) > self::BARIS_MAKS) {
            return ['galat' => 'File berisi ' . format_number(count($hasil['baris'])) . ' baris data. Paling banyak ' . format_number(self::BARIS_MAKS) . ' baris per file. Bagi file menjadi beberapa bagian.'];
        }

        return $hasil;
    }

    /**
     * Matches header titles to known columns (IM-04): case and outer spaces
     * do not matter, the order is free, and optional columns may be missing.
     *
     * @param list<string> $judul Header from baca()
     * @param array<string, array{judul: list<string>, wajib: bool}> $kolom Known columns by key; the first title is the one shown in messages
     *
     * @return array{peta: array<string, int>}|array{galat: list<string>} Column index by key, or docs/11 §5.2 FS-MD-06 E2 messages
     */
    public function cocokkanKolom(array $judul, array $kolom): array
    {
        $kenal = [];

        foreach ($kolom as $kunci => $isi) {
            foreach ($isi['judul'] as $teks) {
                $kenal[mb_strtolower(trim($teks))] = $kunci;
            }
        }

        $peta = $asing = $ganda = [];

        foreach ($judul as $i => $teks) {
            $kunci = $kenal[mb_strtolower(trim($teks))] ?? null;

            if ($kunci === null) {
                $asing[] = $teks === '' ? '(kolom tanpa judul)' : $teks;
            } elseif (isset($peta[$kunci])) {
                $ganda[$kunci] = $kolom[$kunci]['judul'][0];
            } else {
                $peta[$kunci] = $i;
            }
        }

        $hilang = [];

        foreach ($kolom as $kunci => $isi) {
            if ($isi['wajib'] && ! isset($peta[$kunci])) {
                $hilang[] = $isi['judul'][0];
            }
        }

        $galat = [];

        if ($hilang !== []) {
            $galat[] = 'Kolom wajib tidak ada: ' . implode(', ', $hilang) . '.';
        }
        if ($asing !== []) {
            $galat[] = 'Kolom tidak dikenal: ' . implode(', ', $asing) . '. Hapus kolom itu, atau periksa ejaannya.';
        }
        if ($ganda !== []) {
            $galat[] = 'Kolom ganda: ' . implode(', ', $ganda) . '.';
        }

        return $galat === [] ? ['peta' => $peta] : ['galat' => $galat];
    }

    /**
     * CSV text: UTF-8 with BOM, comma, double quotes (IE-09, IM-07). Text
     * that a spreadsheet would run as a formula gets a leading apostrophe
     * (SEC-46).
     *
     * @param list<string> $judul
     * @param iterable<list<int|string|null>> $baris
     */
    public function csv(array $judul, iterable $baris): string
    {
        $out = fopen('php://temp', 'w+b');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, array_map(self::teksCsv(...), $judul), ',', '"', '');

        foreach ($baris as $isi) {
            fputcsv($out, array_map(static fn ($v) => is_int($v) ? $v : self::teksCsv((string) $v), $isi), ',', '"', '');
        }

        rewind($out);
        $teks = (string) stream_get_contents($out);
        fclose($out);

        return $teks;
    }

    /**
     * Writes a text cell that never becomes a formula, also after the user
     * edits it in Excel (IE-08, SEC-46).
     */
    public static function isiTeks(Worksheet $sheet, string $koordinat, string $nilai): void
    {
        $sheet->setCellValueExplicit($koordinat, $nilai, DataType::TYPE_STRING);
        $sheet->getStyle($koordinat)->setQuotePrefix(true);
    }

    /**
     * XLSX bytes of a workbook.
     */
    public function xlsx(Spreadsheet $book): string
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');

        try {
            (new XlsxWriter($book))->save($path);

            return (string) file_get_contents($path);
        } finally {
            @unlink($path);
            $book->disconnectWorksheets();
        }
    }

    /**
     * Download response with an ASCII file name (IE-07) and no caching,
     * because the file holds student data (docs/09 §13).
     */
    public static function unduh(string $isi, string $namaFile, string $mime): ResponseInterface
    {
        $namaFile = preg_replace('/[^A-Za-z0-9._-]/', '-', $namaFile);
        $response = service('response');
        $response->removeHeader('Cache-Control');

        return $response->setStatusCode(200)
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Disposition', 'attachment; filename="' . $namaFile . '"')
            ->setHeader('Cache-Control', 'no-store')
            ->setBody($isi);
    }

    private static function teksCsv(string $nilai): string
    {
        return $nilai !== '' && in_array($nilai[0], self::AWAL_RUMUS, true) ? "'" . $nilai : $nilai;
    }

    private function periksaXlsx(string $path): ?string
    {
        $zip = new ZipArchive();

        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            return self::PESAN_FORMAT;
        }

        $isi = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $isi += (int) ($zip->statIndex($i)['size'] ?? 0);
        }
        $zip->close();

        return $isi > self::ISI_XLSX_MAKS ? 'File XLSX terlalu besar setelah dibuka. Simpan ulang hanya lembar data, atau pakai CSV.' : null;
    }

    /**
     * @return array{judul: list<string>, baris: list<array{nomor: int, sel: list<string>, rumus: list<int>, angka: list<int>, tanggal: array<int, string>}>}
     */
    private function bacaXlsx(string $path): array
    {
        $reader = new XlsxReader();
        $nama   = $reader->listWorksheetNames($path)[0] ?? null;

        if ($nama === null) {
            return ['judul' => [], 'baris' => []];
        }

        // Only the first sheet (IM-16 item 3); cell styles are needed to tell dates from numbers.
        $reader->setLoadSheetsOnly([$nama]);
        $reader->setReadEmptyCells(false);
        $book  = $reader->load($path);
        $sheet = $book->getSheet(0);

        $mentah = [];

        foreach ($sheet->getRowIterator() as $row) {
            $isi = [];

            foreach ($row->getCellIterator() as $cell) {
                $isi[$this->indeksKolom($cell)] = $this->bacaSel($cell);
            }
            $mentah[$row->getRowIndex()] = $isi;
        }
        $book->disconnectWorksheets();

        return $this->susun($mentah);
    }

    /**
     * @return array{nilai: string, rumus: bool, angka: bool, tanggal: string|null}
     */
    private function bacaSel(Cell $cell): array
    {
        $nilai = $cell->getValue();
        $tipe  = $cell->getDataType();

        if ($tipe === DataType::TYPE_FORMULA) {
            return ['nilai' => (string) $nilai, 'rumus' => true, 'angka' => false, 'tanggal' => null];
        }
        if ($nilai instanceof RichText) {
            $nilai = $nilai->getPlainText();
        }

        if (is_int($nilai) || is_float($nilai)) {
            try {
                if (Date::isDateTime($cell)) {
                    $tanggal = Date::excelToDateTimeObject($nilai)->format('Y-m-d');

                    return ['nilai' => $tanggal, 'rumus' => false, 'angka' => false, 'tanggal' => $tanggal];
                }
            } catch (SpreadsheetException) {
                // Not a date after all; read it as a number.
            }

            $teks = is_float($nilai) && floor($nilai) === $nilai && abs($nilai) < 1e15 ? sprintf('%.0f', $nilai) : (string) $nilai;

            return ['nilai' => $teks, 'rumus' => false, 'angka' => true, 'tanggal' => null];
        }

        if (is_bool($nilai)) {
            $nilai = $nilai ? 'TRUE' : 'FALSE';
        }

        return ['nilai' => (string) $nilai, 'rumus' => false, 'angka' => false, 'tanggal' => null];
    }

    private function indeksKolom(Cell $cell): int
    {
        return Coordinate::columnIndexFromString($cell->getColumn()) - 1;
    }

    /**
     * @return array{judul: list<string>, baris: list<array{nomor: int, sel: list<string>, rumus: list<int>, angka: list<int>, tanggal: array<int, string>}>}
     */
    private function bacaCsv(string $path): array
    {
        $teks = (string) file_get_contents($path);
        $teks = str_starts_with($teks, "\xEF\xBB\xBF") ? substr($teks, 3) : $teks;

        // IM-05 asks for UTF-8; a file saved by Excel as "CSV" without UTF-8 is Windows-1252.
        if (! mb_check_encoding($teks, 'UTF-8')) {
            $teks = mb_convert_encoding($teks, 'UTF-8', 'Windows-1252');
        }

        // Separator from the header line: comma, or semicolon from Indonesian Excel (IM-05).
        $judul   = strtok($teks, "\r\n");
        $pemisah = $judul !== false && substr_count($judul, ';') > substr_count($judul, ',') ? ';' : ',';

        $in = fopen('php://temp', 'w+b');
        fwrite($in, $teks);
        rewind($in);

        $mentah = [];
        $nomor  = 0;

        while (($isi = fgetcsv($in, null, $pemisah, '"', '')) !== false) {
            $nomor++;

            if ($isi === [null]) {
                continue;
            }

            $mentah[$nomor] = array_map(static fn ($v) => ['nilai' => (string) $v, 'rumus' => false, 'angka' => false, 'tanggal' => null], $isi);
        }
        fclose($in);

        return $this->susun($mentah);
    }

    /**
     * Header from the first non-empty row, data rows after it, trimmed,
     * without empty rows or empty unnamed columns (IM-06).
     *
     * @param array<int, array<int, array{nilai: string, rumus: bool, angka: bool, tanggal: string|null}>> $mentah Cells by row number and column index
     *
     * @return array{judul: list<string>, baris: list<array{nomor: int, sel: list<string>, rumus: list<int>, angka: list<int>, tanggal: array<int, string>}>}
     */
    private function susun(array $mentah): array
    {
        $kosong = static fn (array $sel): bool => ! $sel['rumus'] && $sel['tanggal'] === null && trim($sel['nilai']) === '';

        // Header: the first row with any value. Rows above it are ignored; row numbers stay as in the file.
        $nomorJudul = null;

        foreach ($mentah as $nomor => $isi) {
            if (array_filter($isi, static fn ($sel) => ! $kosong($sel)) !== []) {
                $nomorJudul = $nomor;
                break;
            }
        }

        if ($nomorJudul === null) {
            return ['judul' => [], 'baris' => []];
        }

        $judulMentah = [];

        foreach ($mentah[$nomorJudul] as $i => $sel) {
            $judulMentah[$i] = trim($sel['nilai']);
        }

        // Columns: every named column, plus unnamed ones that hold data (shown as unknown columns).
        $dipakai = array_keys(array_filter($judulMentah, static fn ($t) => $t !== ''));
        $baris   = [];

        foreach ($mentah as $nomor => $isi) {
            if ($nomor <= $nomorJudul) {
                continue;
            }

            foreach ($isi as $i => $sel) {
                if (! $kosong($sel) && ! in_array($i, $dipakai, true)) {
                    $dipakai[] = $i;
                }
            }
        }
        sort($dipakai);

        $judul = [];

        foreach ($dipakai as $i) {
            $judul[] = $judulMentah[$i] ?? '';
        }

        foreach ($mentah as $nomor => $isi) {
            if ($nomor <= $nomorJudul || array_filter($isi, static fn ($sel) => ! $kosong($sel)) === []) {
                continue;
            }

            $data = ['nomor' => $nomor, 'sel' => [], 'rumus' => [], 'angka' => [], 'tanggal' => []];

            foreach ($dipakai as $posisi => $i) {
                $sel           = $isi[$i] ?? ['nilai' => '', 'rumus' => false, 'angka' => false, 'tanggal' => null];
                $data['sel'][] = trim($sel['nilai']);

                if ($sel['rumus']) {
                    $data['rumus'][] = $posisi;
                }
                if ($sel['angka']) {
                    $data['angka'][] = $posisi;
                }
                if ($sel['tanggal'] !== null) {
                    $data['tanggal'][$posisi] = $sel['tanggal'];
                }
            }
            $baris[] = $data;
        }

        return ['judul' => $judul, 'baris' => $baris];
    }
}
