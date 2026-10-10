<?php

namespace App\Services\Berkas;

use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Images\Exceptions\ImageException;
use Config\Services;
use ErrorException;
use finfo;

/**
 * Image uploads: checks (docs/12 SEC-49, SEC-50, docs/11 VAL-28, GAL-11)
 * and re-saving (docs/07 ARS-53, docs/12 SEC-51). Re-saving drops metadata
 * and anything hidden in the file.
 */
class Gambar
{
    /** 10 MB per photo or logo (SEC-49). */
    public const UKURAN_MAKS = 10 * 1024 * 1024;

    /** 24 megapixels (SEC-49, ARS-53 step 1). */
    public const PIKSEL_MAKS = 24_000_000;

    /** Logo fits in 512×512 px (SEC-51). */
    private const SISI_LOGO = 512;

    /** Student photo sizes by folder, standard first (ARS-51, ARS-53, docs/08 UI-22). */
    private const UKURAN_FOTO = [
        'foto/'       => [600, 800],
        'foto/kiosk/' => [300, 400],
        'foto/kecil/' => [120, 160],
    ];

    /** Accepted types and their file extensions (SEC-50 `mime_in` + `ext_in`). */
    private const EKSTENSI = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png'  => ['png'],
        'image/webp' => ['webp'],
    ];

    /** getimagesize() type per accepted MIME type, so content and header agree. */
    private const TIPE = ['image/jpeg' => IMAGETYPE_JPEG, 'image/png' => IMAGETYPE_PNG, 'image/webp' => IMAGETYPE_WEBP];

    private const PESAN_SERVER = 'File tidak dapat disimpan karena gangguan server. Coba lagi beberapa saat lagi.';

    /**
     * Error message for an optional image upload, '' when no file was chosen,
     * or null when the file may be processed.
     */
    public function periksaUnggahan(UploadedFile $file): ?string
    {
        $nama = $file->getClientName();

        // docs/11 GAL-11 item 3. Not isValid(): it needs is_uploaded_file(), and the checks below read the content anyway.
        switch ($file->getError()) {
            case UPLOAD_ERR_OK:
                return $this->periksa($file->getTempName(), $nama);

            case UPLOAD_ERR_NO_FILE:
                return '';

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
     * VAL-28 message for a photo or logo file, or null when it may be processed.
     *
     * @param string $path Uploaded file on disk
     * @param string $nama Original file name, for the extension and the message
     */
    public function periksa(string $path, string $nama): ?string
    {
        $ukuran = (int) filesize($path);

        if ($ukuran > self::UKURAN_MAKS) {
            // Rounded up, so a file just over the limit never reads "10 MB".
            return "Ukuran file {$nama} " . format_number(ceil($ukuran / 104857.6) / 10, 1) . ' MB. Paling besar ' . format_number(self::UKURAN_MAKS / 1048576) . ' MB.';
        }

        $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($path);

        if (! in_array(strtolower(pathinfo($nama, PATHINFO_EXTENSION)), self::EKSTENSI[$mime] ?? [], true)) {
            return 'File harus berupa gambar JPG, PNG, atau WebP.';
        }

        // Read the size before GD loads the image (ARS-53 step 1).
        $info = @getimagesize($path);

        if ($info === false || $info[2] !== self::TIPE[$mime] || $info[0] < 1 || $info[1] < 1) {
            return "File {$nama} tidak dapat dibaca sebagai gambar.";
        }
        if ($info[0] * $info[1] > self::PIKSEL_MAKS) {
            return "Gambar {$nama} terlalu besar (" . format_number($info[0]) . '×' . format_number($info[1]) . ' piksel). Perkecil dulu, lalu unggah lagi.';
        }

        return null;
    }

    /**
     * Re-saves a checked image as the school logo: turned by its EXIF
     * orientation, shrunk to fit 512×512 px, PNG with its transparency
     * (ARS-53 logo rule). Returns the path relative to writable/uploads/,
     * or null when GD cannot read the image.
     */
    public function simpanLogo(string $sumber): ?string
    {
        $relatif = 'logo/' . bin2hex(random_bytes(16)) . '.png';
        $tujuan  = WRITEPATH . 'uploads/' . $relatif;
        $antara  = tempnam(sys_get_temp_dir(), 'logo');

        if (! is_dir(dirname($tujuan))) {
            mkdir(dirname($tujuan), 0755, true);
        }

        try {
            $image = Services::image('gd', null, false);
            // ARS-53 step 2: save and reload after reorient(), before resizing.
            $image->withFile($sumber)->reorient(true)->save($antara);
            $image->withFile($antara);

            if ($image->getWidth() > self::SISI_LOGO || $image->getHeight() > self::SISI_LOGO) {
                $image->resize(self::SISI_LOGO, self::SISI_LOGO, true);
            }

            $image->convert(IMAGETYPE_PNG)->save($tujuan);
        } catch (ErrorException|ImageException) {
            // Damaged data that getimagesize() accepted.
            @unlink($tujuan);

            return null;
        } finally {
            @unlink($antara);
        }

        return $relatif;
    }

    /**
     * Re-saves a checked image as a student photo in three sizes (ARS-53,
     * ARS-51): `foto/` 600×800, `foto/kiosk/` 300×400, and `foto/kecil/`
     * 120×160 px, JPEG quality 85, never cropped or enlarged, on a white
     * background, without metadata. Returns the standard photo path
     * relative to writable/uploads/ (`siswa.foto_file`), or null when GD
     * cannot read the image; nothing is left behind then.
     */
    public function simpanFoto(string $sumber): ?string
    {
        $nama   = bin2hex(random_bytes(16)) . '.jpg';
        $tujuan  = [];
        $antara  = tempnam(sys_get_temp_dir(), 'foto');

        foreach (self::UKURAN_FOTO as $folder => $ukuran) {
            $tujuan[$folder] = WRITEPATH . 'uploads/' . $folder . $nama;

            if (! is_dir(dirname($tujuan[$folder]))) {
                mkdir(dirname($tujuan[$folder]), 0755, true);
            }
        }

        try {
            $image = Services::image('gd', null, false);
            // Step 2: save and reload after reorient(), before resizing (CI4 4.7.4 portrait bug).
            $image->withFile($sumber)->reorient(true)->save($antara);
            $image->withFile($antara);

            foreach (self::UKURAN_FOTO as $folder => [$lebar, $tinggi]) {
                // Step 6: the smaller sizes come from the standard photo of step 5.
                $image->withFile($folder === 'foto/' ? $antara : $tujuan['foto/']);
                // Step 3: transparent areas become white.
                $image->flatten(255, 255, 255);

                // Step 4: only shrink.
                if ($image->getWidth() > $lebar || $image->getHeight() > $tinggi) {
                    $image->resize($lebar, $tinggi, true);
                }

                // Step 5: JPEG, also when the source was PNG or WebP.
                $image->convert(IMAGETYPE_JPEG)->save($tujuan[$folder], 85);
            }
        } catch (ErrorException|ImageException) {
            array_map(static fn (string $path) => @unlink($path), $tujuan);

            return null;
        } finally {
            @unlink($antara);
        }

        return 'foto/' . $nama;
    }

    /**
     * Removes the three sizes of a student photo (ARS-53: the old photo is
     * removed after its replacement is saved).
     *
     * @param string $relatif `siswa.foto_file`
     */
    public function hapusFoto(string $relatif): void
    {
        $nama = basename($relatif);

        if (preg_match('/^[0-9a-f]{32}\.jpg$/', $nama) !== 1) {
            return;
        }

        foreach (array_keys(self::UKURAN_FOTO) as $folder) {
            @unlink(WRITEPATH . 'uploads/' . $folder . $nama);
        }
    }
}
