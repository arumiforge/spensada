<?php

/**
 * Volume test helper for FASE-03 (docs/15 L03-06, docs/14 FASE-03): writes
 * fictitious files to import 1,000 students and their photos on Laragon,
 * within the docs/12 SEC-49 limits. The files are generated, never
 * committed, and the students do not exist.
 *
 * Usage (Laragon terminal, from the project folder):
 *
 *     php tests/volume/buat-file-volume.php --kelas=7A,7B,7C,7D --keluar=writable/volume
 *
 * Options:
 *   --kelas   Class names of the target school year, comma separated (required)
 *   --jumlah  Number of students, default 1000 (at most 2,000 per import file)
 *   --keluar  Output folder, default writable/volume
 *   --awal    First NISN, default 9900000001 (10 digits)
 *
 * Output:
 *   siswa-volume.xlsx       Import file for /panel/siswa/import (docs/13 §6.1)
 *   foto-volume-NN.zip      Photos named <NISN>_<name>.jpg (docs/13 IM-03),
 *                           600×800 JPEG, split so each ZIP stays under 90 MB
 *                           and 2,000 entries (docs/07 ARS-54)
 *   foto-volume-gagal.zip   Small ZIP with names that must not match (IM-12)
 */

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require __DIR__ . '/../../vendor/autoload.php';

$opsi = getopt('', ['kelas:', 'jumlah::', 'keluar::', 'awal::']);

if (empty($opsi['kelas'])) {
    fwrite(STDERR, "Missing --kelas, e.g. --kelas=7A,7B,7C\n");

    exit(1);
}

$kelas  = array_values(array_filter(array_map('trim', explode(',', $opsi['kelas']))));
$jumlah = (int) ($opsi['jumlah'] ?? 1000);
$keluar = rtrim($opsi['keluar'] ?? 'writable/volume', '/\\');
$awal   = (int) ($opsi['awal'] ?? 9_900_000_001);

if ($jumlah < 1 || $jumlah > 2000 || $kelas === [] || $awal < 1_000_000_000 || $awal + $jumlah > 9_999_999_999) {
    fwrite(STDERR, "Use 1 to 2000 students, at least one class, and a 10-digit first NISN.\n");

    exit(1);
}

is_dir($keluar) || mkdir($keluar, 0755, true);

$depan    = ['Adi', 'Ayu', 'Bagus', 'Bunga', 'Dewi', 'Dimas', 'Eka', 'Fajar', 'Galih', 'Hana', 'Indra', 'Joko', 'Kartika', 'Lestari', 'Made', 'Nur', 'Putri', 'Rizky', 'Sari', 'Wahyu'];
$belakang = ['Pratama', 'Wijaya', 'Saputra', 'Lestari', 'Kusuma', 'Hidayat', 'Rahmawati', 'Nugroho', 'Santoso', 'Utami', 'Setiawan', 'Purnomo'];
$siswa    = [];

for ($i = 0; $i < $jumlah; $i++) {
    $siswa[] = [
        'nisn'  => sprintf('%010d', $awal + $i),
        'nama'  => $depan[$i % count($depan)] . ' ' . $belakang[intdiv($i, count($depan)) % count($belakang)] . ' ' . ($i + 1),
        'kelas' => $kelas[$i % count($kelas)],
        'jk'    => $i % 2 === 0 ? 'L' : 'P',
        'lahir' => sprintf('%02d-%02d-2013', $i % 28 + 1, $i % 12 + 1),
        // Every tenth student has no WA number (FS-MD-06 item 3).
        'wa' => $i % 10 === 0 ? '' : sprintf('0812%08d', $i),
    ];
}

// Import file: NISN and phone numbers as text cells (docs/13 IE-08, IM-01).
$book  = new Spreadsheet();
$sheet = $book->getActiveSheet()->setTitle('Siswa');
$sheet->fromArray(['NISN', 'Nama Lengkap', 'Kelas', 'Jenis Kelamin', 'Tanggal Lahir', 'Nomor WA Orang Tua/Wali'], null, 'A1');

foreach ($siswa as $n => $s) {
    $baris = $n + 2;
    $sheet->setCellValueExplicit("A{$baris}", $s['nisn'], DataType::TYPE_STRING);
    $sheet->setCellValue("B{$baris}", $s['nama']);
    $sheet->setCellValue("C{$baris}", $s['kelas']);
    $sheet->setCellValue("D{$baris}", $s['jk']);
    $sheet->setCellValueExplicit("E{$baris}", $s['lahir'], DataType::TYPE_STRING);
    $sheet->setCellValueExplicit("F{$baris}", $s['wa'], DataType::TYPE_STRING);
}
(new Xlsx($book))->save("{$keluar}/siswa-volume.xlsx");
$book->disconnectWorksheets();

// Photos: a 600×800 JPEG with some texture, so the size is close to a real school photo.
$batasZip = 90 * 1024 * 1024;
$nomorZip = 0;
$zip      = null;
$isiZip   = 0;
$entri    = 0;

foreach ($siswa as $n => $s) {
    if ($zip === null || $isiZip > $batasZip - 400_000 || $entri >= 2000) {
        $zip?->close();
        $zip = new ZipArchive();
        $zip->open(sprintf('%s/foto-volume-%02d.zip', $keluar, ++$nomorZip), ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $isiZip = $entri = 0;
    }

    $gambar = imagecreatetruecolor(600, 800);

    for ($y = 0; $y < 800; $y += 4) {
        imagefilledrectangle($gambar, 0, $y, 599, $y + 3, imagecolorallocate($gambar, ($y + $n) % 256, (3 * $y) % 256, (128 + $n * 7) % 256));
    }
    for ($t = 0; $t < 400; $t++) {
        imagesetpixel($gambar, random_int(0, 599), random_int(0, 799), imagecolorallocate($gambar, random_int(0, 255), random_int(0, 255), random_int(0, 255)));
    }
    imagestring($gambar, 5, 20, 20, $s['nisn'], imagecolorallocate($gambar, 255, 255, 255));

    ob_start();
    imagejpeg($gambar, null, 90);
    $jpeg = (string) ob_get_clean();

    // Some files sit in a folder inside the ZIP; folder names are ignored (IM-13).
    $folder = $n % 3 === 0 ? 'foto/' . $s['kelas'] . '/' : '';
    $nama   = $folder . $s['nisn'] . '_' . $s['nama'] . '.jpg';
    $zip->addFromString($nama, $jpeg);
    // JPEG does not shrink in a ZIP; store it, so the upload size is real.
    $zip->setCompressionName($nama, ZipArchive::CM_STORE);
    $isiZip += strlen($jpeg);
    $entri++;
}
$zip?->close();

// Names that must be reported as not matching (IM-12) plus one duplicate NISN.
$gagal = new ZipArchive();
$gagal->open("{$keluar}/foto-volume-gagal.zip", ZipArchive::CREATE | ZipArchive::OVERWRITE);
$kecil = imagecreatetruecolor(60, 80);
ob_start();
imagejpeg($kecil);
$jpeg = (string) ob_get_clean();

foreach (['012345678.jpg', '00123456789.jpg', 'Budi_0012345678.jpg', '0012345678Budi.jpg', $siswa[0]['nisn'] . '.jpg', $siswa[0]['nisn'] . ' - kedua.jpg'] as $nama) {
    $gagal->addFromString($nama, $jpeg);
}
$gagal->addFromString('catatan.txt', 'bukan gambar');
$gagal->close();

printf("Wrote %d students to %s/siswa-volume.xlsx and %d photo ZIP file(s).\n", $jumlah, $keluar, $nomorZip);
