<?php

namespace App\Services\MasterData;

use App\Services\Berkas\Gambar;
use App\Services\Sistem\Jam;
use App\Services\Sistem\Transaction;
use CodeIgniter\Database\BaseConnection;
use Throwable;

/**
 * Replaces a student photo (docs/04 FS-MD-07, FS-MD-08, docs/07 ARS-53).
 * Used by the single upload (HAL-MD-09) and by foto massal (HAL-MD-15).
 * Callers check rights and scope first.
 */
class FotoSiswa
{
    private BaseConnection $db;
    private Jam $jam;
    private Gambar $gambar;
    private LogDataSiswa $log;

    public function __construct(?BaseConnection $db = null, ?Jam $jam = null, ?Gambar $gambar = null)
    {
        $this->db     = $db ?? db_connect();
        $this->jam    = $jam ?? new Jam();
        $this->gambar = $gambar ?? new Gambar();
        $this->log    = new LogDataSiswa();
    }

    /**
     * Checks the image, saves its three sizes, points `siswa.foto_file` at
     * them, sets `foto_diganti_at` (the kiosk reloads by it), and writes
     * `foto_diganti` to the student log in the same transaction. The old
     * files are removed only after the commit; on any failure the old
     * photo stays and the new files are removed.
     *
     * @param string      $sumber   Image file on disk (upload or temporary folder)
     * @param string      $nama     Original file name, for the extension check and messages
     * @param string|null $kelompok UUID shared by one foto massal run (docs/06 §12.2)
     *
     * @return array{galat: string}|array{ok: true, foto_file: string, ada_foto_lama: bool} `galat` is a VAL-28 message
     */
    public function ganti(int $siswaId, string $sumber, string $nama, int $pelakuId, ?string $kelompok = null): array
    {
        if (($pesan = $this->gambar->periksa($sumber, $nama)) !== null) {
            return ['galat' => $pesan];
        }

        $baru = $this->gambar->simpanFoto($sumber);

        if ($baru === null) {
            return ['galat' => "File {$nama} tidak dapat dibaca sebagai gambar."];
        }

        try {
            $lama = (new Transaction($this->db))->run(function () use ($siswaId, $baru, $pelakuId, $kelompok): ?array {
                // Row lock on the student, like every student change (docs/06 §16).
                $row = $this->db->query('SELECT foto_file FROM siswa WHERE id = ? FOR UPDATE', [$siswaId])->getRowArray();

                if ($row === null) {
                    return null;
                }

                $this->db->table('siswa')->where('id', $siswaId)->update([
                    'foto_file'       => $baru,
                    'foto_diganti_at' => $this->jam->now()->toDateTimeString(),
                ]);
                $this->log->catat($siswaId, 'foto_diganti', null, null, $pelakuId, null, $kelompok);

                return ['foto_file' => $row['foto_file']];
            });
        } catch (Throwable $e) {
            $this->gambar->hapusFoto($baru);

            throw $e;
        }

        if ($lama === null) {
            $this->gambar->hapusFoto($baru);

            return ['galat' => 'Data siswa tidak ditemukan.'];
        }

        $lama = $lama['foto_file'];

        if (($lama ?? '') !== '') {
            $this->gambar->hapusFoto($lama);
        }

        return ['ok' => true, 'foto_file' => $baru, 'ada_foto_lama' => ($lama ?? '') !== ''];
    }
}
