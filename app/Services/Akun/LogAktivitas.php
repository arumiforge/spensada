<?php

namespace App\Services\Akun;

use App\Models\LogAktivitasModel;

/**
 * Writes `log_aktivitas` entries (docs/06 §5.3, docs/12 SEC-57, SEC-59).
 *
 * Call it inside the transaction of the change it records, except for
 * `login_gagal`, `login_dikunci` and `lampiran_dibuka` (SEC-57). `data`
 * never holds passwords, PINs, tokens, hashes or file contents.
 */
class LogAktivitas
{
    /** Kinds of docs/12 SEC-59; labels in Config\Label::$codes['log_aktivitas.jenis']. */
    public const JENIS = [
        'login_berhasil', 'login_gagal', 'login_dikunci', 'kunci_login_dibuka', 'password_diganti',
        'password_direset', 'slip_dicetak', 'akun_dibuat', 'akun_diubah', 'role_diubah',
        'akun_dinonaktifkan', 'akun_diaktifkan', 'kredensial_stasiun_diganti', 'login_stasiun_berpindah',
        'pin_kiosk_diubah', 'admin_pertama_dibuat', 'admin_dipulihkan', 'pengaturan_diubah',
        'tahun_ajaran_diubah', 'rombel_diubah', 'wali_kelas_diubah', 'atribut_siswa_diubah',
        'import_siswa', 'import_penempatan', 'penempatan_massal', 'foto_massal', 'lampiran_dibuka',
        'scan_ditolak_server',
    ];

    private LogAktivitasModel $model;

    public function __construct(?LogAktivitasModel $model = null)
    {
        $this->model = $model ?? model(LogAktivitasModel::class);
    }

    /**
     * Adds one entry and returns its ID.
     *
     * @param int|null             $pelakuId Acting account; null for the system, CLI commands and failed logins
     * @param int|null             $akunId   Account affected
     * @param array<string, mixed> $data     Details for this kind (SEC-59)
     */
    public function catat(string $jenis, ?int $pelakuId, ?int $akunId = null, array $data = [], ?int $rombelId = null, ?string $ip = null): int
    {
        if (! in_array($jenis, self::JENIS, true)) {
            throw new \InvalidArgumentException("Unknown log_aktivitas kind: {$jenis}");
        }

        return (int) $this->model->insert([
            'jenis'     => $jenis,
            'pelaku_id' => $pelakuId,
            'akun_id'   => $akunId,
            'rombel_id' => $rombelId,
            'data'      => $data === [] ? null : json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'ip'        => $ip,
        ]);
    }
}
