<?php

namespace App\Services\MasterData;

use CodeIgniter\Database\BaseConnection;

/**
 * Student data log page (docs/09 HAL-MD-17, docs/06 §12.2): one student's
 * entries, newest first, with `data_lama` and `data_baru` as screen text.
 * Read only.
 */
class DaftarLogDataSiswa
{
    public const PER_PAGE = 50;

    /** Screen names of the keys the student services write (docs/06 DB-14). */
    private const LABEL = [
        'nisn'                => 'NISN',
        'nis'                 => 'NIS',
        'nama'                => 'Nama',
        'jenis_kelamin'       => 'Jenis kelamin',
        'tanggal_lahir'       => 'Tanggal lahir',
        'alamat'              => 'Alamat rumah',
        'nama_ortu'           => 'Nama orang tua/wali',
        'wa_ortu'             => 'Nomor WA',
        'kelas'               => 'Kelas',
        'tahun_ajaran'        => 'Tahun ajaran',
        'tanggal_mulai'       => 'Tanggal mulai',
        'tanggal_selesai'     => 'Tanggal selesai',
        'alasan_nonaktif'     => 'Alasan',
        'dibatalkan'          => 'Periode dibatalkan',
        'akun_status'         => 'Status akun',
        'atribut'             => 'Atribut tambahan',
    ];

    /** Internal keys that mean nothing on screen. */
    private const SEMBUNYI = ['penempatan_id'];

    /** Keys whose values are codes with screen labels. */
    private const KODE = [
        'jenis_kelamin'   => 'siswa.jenis_kelamin',
        'alasan_nonaktif' => 'masa_aktif.alasan_nonaktif',
        'akun_status'     => 'akun.status',
    ];

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * @return array{total: int, rows: list<array<string, mixed>>} Rows with `pelaku_nama`
     */
    public function daftar(int $siswaId, int $page): array
    {
        $builder = $this->db->table('log_data_siswa l')
            ->select('l.*, p.nama AS pelaku_nama, p.username AS pelaku_username')
            ->join('akun p', 'p.id = l.pelaku_id', 'left')
            ->where('l.siswa_id', $siswaId);

        $total = $builder->countAllResults(false);
        $rows  = $builder->orderBy('l.created_at', 'DESC')->orderBy('l.id', 'DESC')
            ->limit(self::PER_PAGE, ($page - 1) * self::PER_PAGE)->get()->getResultArray();

        return ['total' => $total, 'rows' => $rows];
    }

    /**
     * A JSON column as labelled rows; empty for NULL.
     *
     * @return list<array{label: string, nilai: string}>
     */
    public function rincian(?string $json): array
    {
        $data = json_decode((string) $json, true);
        $rows = [];

        foreach (is_array($data) ? $data : [] as $key => $value) {
            if (in_array($key, self::SEMBUNYI, true)) {
                continue;
            }
            if ($key === 'atribut' && is_array($value)) {
                // Attribute changes are stored by their label (FS-MD-09 item 7).
                foreach ($value as $label => $v) {
                    $rows[] = ['label' => (string) $label, 'nilai' => $this->nilai('', $v)];
                }

                continue;
            }
            $rows[] = ['label' => self::LABEL[$key] ?? ucfirst(str_replace('_', ' ', (string) $key)), 'nilai' => $this->nilai((string) $key, $value)];
        }

        return $rows;
    }

    private function nilai(string $key, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        if (is_bool($value)) {
            return $value ? 'Ya' : 'Tidak';
        }
        if (is_array($value)) {
            return implode(', ', array_map(fn ($v): string => $this->nilai($key, $v), $value));
        }

        $value = (string) $value;

        if (isset(self::KODE[$key])) {
            return config('Label')->codes[self::KODE[$key]][$value] ?? $value;
        }
        if ($key === 'wa_ortu') {
            return format_wa($value);
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return format_date($value);
        }

        return $value;
    }
}
