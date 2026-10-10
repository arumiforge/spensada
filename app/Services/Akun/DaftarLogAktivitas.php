<?php

namespace App\Services\Akun;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;
use DateTimeImmutable;

/**
 * Activity log page (docs/09 HAL-AKN-09, docs/12 SEC-62): filters, the
 * paged list, and the `data` details as labelled rows. Read only.
 */
class DaftarLogAktivitas
{
    public const PER_PAGE = 50;

    /** Quick filters `cepat` and their kinds (HAL-AKN-09). */
    public const CEPAT = [
        'lampiran'     => ['lampiran_dibuka'],
        'login'        => ['login_berhasil', 'login_gagal', 'login_dikunci', 'kunci_login_dibuka'],
        'scan_ditolak' => ['scan_ditolak_server'],
    ];

    /** Filter names in query order, with their screen labels for the flash note. */
    public const SARINGAN = [
        'cepat'   => 'Saringan cepat',
        'jenis'   => 'Jenis',
        'pelaku'  => 'Pelaku',
        'akun'    => 'Akun terdampak',
        'mulai'   => 'Tanggal mulai',
        'selesai' => 'Tanggal selesai',
    ];

    /** `data` keys whose humanized name reads badly. Other keys fall back to their own text. */
    private const KEY_LABELS = [
        'sampai' => 'Berlaku sampai',
        'wajib'  => 'Penggantian wajib',
        'unduh'  => 'Diunduh',
        'uuid'   => 'UUID',
        'nisn'   => 'NISN',
        'sha1'   => 'SHA-1',
    ];

    /** `data` keys whose values are codes with screen labels (docs/09 §14). */
    private const KEY_CODES = [
        'alasan'   => 'log_aktivitas.alasan_login_gagal',
        'role'     => 'akun_role.role',
        'ditambah' => 'akun_role.role',
        'dicabut'  => 'akun_role.role',
        'status'   => 'akun.status',
    ];

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * Keeps the valid, non-empty filters of a GET query (docs/09 RT-04 item 6).
     *
     * @param array<string, mixed> $query
     *
     * @return array{saringan: array<string, string>, tidakSah: list<string>}
     */
    public function saring(array $query): array
    {
        $saringan = [];
        $tidakSah = [];

        foreach (array_keys(self::SARINGAN) as $nama) {
            $nilai = $query[$nama] ?? '';
            if ($nilai === '') {
                continue;
            }
            if (is_string($nilai) && $this->sah($nama, $nilai)) {
                $saringan[$nama] = $nilai;
            } else {
                $tidakSah[] = $nama;
            }
        }

        // docs/11 VAL-26: an end before the start is dropped.
        if (isset($saringan['mulai'], $saringan['selesai']) && $saringan['selesai'] < $saringan['mulai']) {
            unset($saringan['selesai']);
            $tidakSah[] = 'selesai';
        }

        return ['saringan' => $saringan, 'tidakSah' => $tidakSah];
    }

    /**
     * One page of entries, newest first, with actor and affected account names.
     *
     * @param array<string, string> $saringan Output of saring()
     *
     * @return array{total: int, rows: list<array<string, mixed>>}
     */
    public function cari(array $saringan, int $page): array
    {
        $builder = $this->query();

        if (isset($saringan['cepat'])) {
            $builder->whereIn('la.jenis', self::CEPAT[$saringan['cepat']]);
        }
        if (isset($saringan['jenis'])) {
            $builder->where('la.jenis', $saringan['jenis']);
        }
        if (isset($saringan['pelaku'])) {
            $builder->where('la.pelaku_id', (int) $saringan['pelaku']);
        }
        if (isset($saringan['akun'])) {
            $builder->where('la.akun_id', (int) $saringan['akun']);
        }
        // Dates are inclusive WIB days; created_at is stored in WIB.
        if (isset($saringan['mulai'])) {
            $builder->where('la.created_at >=', $saringan['mulai'] . ' 00:00:00');
        }
        if (isset($saringan['selesai'])) {
            $builder->where('la.created_at <', (new DateTimeImmutable($saringan['selesai']))->modify('+1 day')->format('Y-m-d') . ' 00:00:00');
        }

        $total = $builder->countAllResults(false);
        $rows  = $builder->orderBy('la.created_at', 'DESC')->orderBy('la.id', 'DESC')
            ->limit(self::PER_PAGE, ($page - 1) * self::PER_PAGE)->get()->getResultArray();

        return ['total' => $total, 'rows' => $rows];
    }

    /**
     * One entry with names, or null when the ID does not exist.
     *
     * @return array<string, mixed>|null
     */
    public function satu(int $id): ?array
    {
        return $this->query()->where('la.id', $id)->get()->getRowArray();
    }

    /**
     * Staff and station accounts for the actor and account selects, plus the
     * given IDs when they are other accounts (students).
     *
     * @param list<int> $termasuk
     *
     * @return list<array{id: string, nama: string|null, username: string}>
     */
    public function pilihanAkun(array $termasuk = []): array
    {
        $builder = $this->db->table('akun')->select('id, nama, username')->whereIn('jenis', ['staf', 'stasiun']);
        if ($termasuk !== []) {
            $builder->orWhereIn('id', $termasuk);
        }

        return $builder->orderBy('nama')->orderBy('username')->get()->getResultArray();
    }

    /**
     * The `data` JSON as screen rows (HAL-AKN-09: a labelled table, not raw JSON).
     *
     * @param array<string, mixed> $entri Row with `jenis` and `data`
     *
     * @return list<array{label: string, nilai: string}>
     */
    public function rincian(array $entri): array
    {
        $data = json_decode((string) ($entri['data'] ?? ''), true);
        if (! is_array($data)) {
            return [];
        }

        $rows = [];

        foreach ($data as $key => $value) {
            $rows[] = ['label' => $this->label((string) $key, $entri['jenis']), 'nilai' => $this->nilai((string) $key, $value, $entri['jenis'])];
        }

        return $rows;
    }

    /**
     * Short one-line summary of `data` for the list.
     *
     * @param array<string, mixed> $entri
     */
    public function ringkasan(array $entri): string
    {
        $parts = array_map(static fn (array $r): string => $r['label'] . ': ' . $r['nilai'], $this->rincian($entri));

        return mb_strimwidth(implode(' · ', $parts), 0, 90, '…');
    }

    /**
     * Screen name of a joined account: name, else username; "Sistem" for an empty actor.
     */
    public static function namaAkun(?string $nama, ?string $username, string $kosong): string
    {
        return $nama ?: ($username ?? $kosong);
    }

    private function query(): BaseBuilder
    {
        return $this->db->table('log_aktivitas la')
            ->select('la.*, p.nama AS pelaku_nama, p.username AS pelaku_username, a.nama AS akun_nama, a.username AS akun_username')
            ->join('akun p', 'p.id = la.pelaku_id', 'left')
            ->join('akun a', 'a.id = la.akun_id', 'left');
    }

    private function sah(string $nama, string $nilai): bool
    {
        return match ($nama) {
            'cepat'   => isset(self::CEPAT[$nilai]),
            'jenis'   => in_array($nilai, LogAktivitas::JENIS, true),
            'pelaku', 'akun' => ctype_digit($nilai) && (int) $nilai > 0
                && $this->db->table('akun')->where('id', (int) $nilai)->countAllResults() === 1,
            'mulai', 'selesai' => ($d = DateTimeImmutable::createFromFormat('!Y-m-d', $nilai)) !== false && $d->format('Y-m-d') === $nilai,
        };
    }

    private function label(string $key, string $jenis): string
    {
        if ($key === 'jenis') {
            return $jenis === 'login_dikunci' ? 'Jenis kunci' : 'Jenis akun';
        }
        if (isset(self::KEY_LABELS[$key])) {
            return self::KEY_LABELS[$key];
        }
        if (str_ends_with($key, '_id')) {
            return 'ID ' . str_replace('_', ' ', substr($key, 0, -3));
        }

        return ucfirst(str_replace('_', ' ', $key));
    }

    private function nilai(string $key, mixed $value, string $jenis): string
    {
        if ($value === null || $value === '' || $value === []) {
            return '—';
        }
        if (is_bool($value)) {
            return $value ? 'Ya' : 'Tidak';
        }
        if (is_array($value)) {
            $parts = [];

            foreach ($value as $k => $v) {
                $teks    = $this->nilai(is_int($k) ? $key : (string) $k, $v, $jenis);
                $parts[] = is_int($k) ? $teks : $this->label((string) $k, $jenis) . ': ' . $teks;
            }

            return implode(array_is_list($value) ? ', ' : '; ', $parts);
        }
        if (is_int($value) || is_float($value)) {
            // IDs are shown as they are, without thousands separators.
            return str_ends_with($key, '_id') || str_ends_with($key, '_ids') ? (string) $value : format_number($value);
        }

        $value = (string) $value;
        $kode  = $key === 'jenis' ? ($jenis === 'login_dikunci' ? 'log_aktivitas.jenis_kunci' : 'akun.jenis') : (self::KEY_CODES[$key] ?? null);
        if ($kode !== null && isset(config('Label')->codes[$kode][$value])) {
            return config('Label')->codes[$kode][$value];
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(:\d{2})?/', $value) === 1) {
            return format_datetime($value);
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return format_date($value);
        }

        return $value;
    }
}
