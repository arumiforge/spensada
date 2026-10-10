<?php

namespace App\Services\Berkas;

use FilesystemIterator;
use JsonException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Temporary folders between preview and confirmation (docs/07 ARS-55):
 * import siswa, import penempatan, and foto massal. Each folder is
 * `writable/uploads/tmp/<token>/`, with a 32-character hex token from
 * random_bytes(16), bound to the account that made it and to one flow, so
 * a token from another account or another page opens nothing.
 *
 * Folders are removed after confirmation or cancellation; bersihkan()
 * removes leftovers older than 24 hours (ARS-56 `tugas:harian`).
 */
class BerkasSementara
{
    /** Owner file inside each folder; names starting with a dot are never user files. */
    private const PEMILIK = '.pemilik.json';

    private string $akar;

    /**
     * @param string|null $akar Base folder, for tests; default writable/uploads/tmp/
     */
    public function __construct(?string $akar = null)
    {
        $this->akar = rtrim($akar ?? WRITEPATH . 'uploads/tmp', '/\\') . DIRECTORY_SEPARATOR;
    }

    /**
     * Creates a folder for one flow and returns its token.
     *
     * @param string $jenis Flow name, e.g. `import_siswa`, `import_penempatan`, `foto_massal`
     */
    public function buat(int $akunId, string $jenis): string
    {
        do {
            $token = bin2hex(random_bytes(16));
        } while (is_dir($this->akar . $token));

        mkdir($this->akar . $token, 0755, true);
        file_put_contents($this->akar . $token . DIRECTORY_SEPARATOR . self::PEMILIK, json_encode([
            'akun_id' => $akunId,
            'jenis'   => $jenis,
            'dibuat'  => time(),
        ], JSON_THROW_ON_ERROR));

        return $token;
    }

    /**
     * Absolute folder path with a trailing separator, or null when the token
     * is malformed, gone, or belongs to another account or flow.
     */
    public function folder(string $token, int $akunId, string $jenis): ?string
    {
        if (preg_match('/^[0-9a-f]{32}$/', $token) !== 1) {
            return null;
        }

        $dir     = $this->akar . $token . DIRECTORY_SEPARATOR;
        $pemilik = $this->bacaJson($dir . self::PEMILIK);

        if ($pemilik === null || ($pemilik['akun_id'] ?? null) !== $akunId || ($pemilik['jenis'] ?? null) !== $jenis) {
            return null;
        }

        return $dir;
    }

    /**
     * Stores preview data as JSON next to the uploaded file. Returns false
     * when the folder is not the caller's.
     *
     * @param string $nama File name inside the folder, letters, digits, `_`, `-`, and `.` only
     * @param array<mixed> $data
     */
    public function simpanData(string $token, int $akunId, string $jenis, string $nama, array $data): bool
    {
        $dir = $this->folder($token, $akunId, $jenis);

        if ($dir === null || ! $this->namaSah($nama)) {
            return false;
        }

        $sementara = $dir . $nama . '.' . bin2hex(random_bytes(4));
        file_put_contents($sementara, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        // Replace in one step, so a reader never sees half a file.
        return rename($sementara, $dir . $nama);
    }

    /**
     * Preview data stored by simpanData(), or null.
     *
     * @return array<mixed>|null
     */
    public function bacaData(string $token, int $akunId, string $jenis, string $nama): ?array
    {
        $dir = $this->folder($token, $akunId, $jenis);

        return $dir === null || ! $this->namaSah($nama) ? null : $this->bacaJson($dir . $nama);
    }

    /**
     * Removes the folder after confirmation or cancellation. Returns false
     * when the folder is not the caller's.
     */
    public function hapus(string $token, int $akunId, string $jenis): bool
    {
        $dir = $this->folder($token, $akunId, $jenis);

        if ($dir === null) {
            return false;
        }

        $this->hapusFolder($dir);

        return true;
    }

    /**
     * Removes folders older than $umurDetik (ARS-55, ARS-56). Returns the
     * number removed.
     */
    public function bersihkan(int $umurDetik = 86400): int
    {
        if (! is_dir($this->akar)) {
            return 0;
        }

        $batas  = time() - $umurDetik;
        $jumlah = 0;

        foreach (new FilesystemIterator($this->akar, FilesystemIterator::SKIP_DOTS) as $item) {
            if (! $item->isDir() || $item->isLink() || preg_match('/^[0-9a-f]{32}$/', $item->getFilename()) !== 1) {
                continue;
            }

            $pemilik = $this->bacaJson($item->getPathname() . DIRECTORY_SEPARATOR . self::PEMILIK);
            $dibuat  = (int) ($pemilik['dibuat'] ?? $item->getMTime());

            if ($dibuat < $batas) {
                $this->hapusFolder($item->getPathname() . DIRECTORY_SEPARATOR);
                $jumlah++;
            }
        }

        return $jumlah;
    }

    private function namaSah(string $nama): bool
    {
        return preg_match('/^[A-Za-z0-9_-][A-Za-z0-9_.-]{0,99}$/', $nama) === 1 && ! str_contains($nama, '..');
    }

    /**
     * @return array<mixed>|null
     */
    private function bacaJson(string $path): ?array
    {
        if (! is_file($path)) {
            return null;
        }

        try {
            $data = json_decode((string) file_get_contents($path), true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return is_array($data) ? $data : null;
    }

    private function hapusFolder(string $dir): void
    {
        $isi = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($isi as $item) {
            // A link is removed itself; its target is never followed.
            $item->isDir() && ! $item->isLink() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($dir);
    }
}
