<?php

namespace App\Services\MasterData;

use App\Models\LogDataSiswaModel;
use InvalidArgumentException;

/**
 * Writes `log_data_siswa` (docs/06 §12.2). Call it inside the transaction of
 * the change it records (docs/06 §16), so both are saved or neither is.
 */
class LogDataSiswa
{
    /** Kinds in docs/06 §12.2; labels in Label::$codes['log_data_siswa.jenis']. */
    public const JENIS = [
        'siswa_dibuat', 'data_diubah', 'nisn_diubah', 'wa_diubah', 'foto_diganti',
        'dinonaktifkan', 'diaktifkan_kembali', 'penempatan_dibuat', 'penempatan_diubah',
    ];

    private LogDataSiswaModel $model;

    public function __construct(?LogDataSiswaModel $model = null)
    {
        $this->model = $model ?? model(LogDataSiswaModel::class);
    }

    /**
     * $lama and $baru hold the changed fields plus the context needed to read
     * them without other tables, e.g. the rombel name (docs/06 DB-14). Empty
     * arrays are stored as NULL. $kelompok marks one bulk action (UUID).
     *
     * @param array<string, mixed>|null $lama
     * @param array<string, mixed>|null $baru
     */
    public function catat(int $siswaId, string $jenis, ?array $lama, ?array $baru, ?int $pelakuId, ?string $alasan = null, ?string $kelompok = null): int
    {
        if (! in_array($jenis, self::JENIS, true)) {
            throw new InvalidArgumentException("Unknown log_data_siswa kind: {$jenis}");
        }

        $json = static fn (?array $data): ?string => $data === null || $data === [] ? null : json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        return (int) $this->model->insert([
            'siswa_id'  => $siswaId,
            'jenis'     => $jenis,
            'data_lama' => $json($lama),
            'data_baru' => $json($baru),
            'alasan'    => $alasan,
            'pelaku_id' => $pelakuId,
            'kelompok'  => $kelompok,
        ]);
    }
}
