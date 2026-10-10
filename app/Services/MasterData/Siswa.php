<?php

namespace App\Services\MasterData;

use App\Services\Sistem\Jam;
use App\Services\Sistem\Transaction;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use DateTimeImmutable;
use Normalizer;

/**
 * Student data changes (docs/04 FS-MD-04, FS-AKN-05 part A, docs/06 §6.5
 * to §6.9, §16). Every change writes `log_data_siswa` in its transaction.
 *
 * Results: `['galat' => [field => message]]` for field errors, `['pesan' =>
 * text]` for an action the data state no longer allows (docs/11 GAL-09),
 * `['konflik' => true]` when the version token no longer matches (docs/07
 * ARS-40), else `['ok' => true]` or `['id' => …]`.
 *
 * Field names: nisn, nama, nis, jenis_kelamin, tanggal_lahir, alamat,
 * nama_ortu, wa_ortu, atribut (array by atribut_siswa.id; errors under
 * `atribut_<id>`), rombel_id, tanggal_mulai, tanggal_selesai,
 * alasan_nonaktif, keterangan_nonaktif.
 */
class Siswa
{
    private const NAMA_SAH = "/^(?=.*\\p{L})[\\p{L}\\p{M} .,'’-]+$/u";

    private BaseConnection $db;
    private Jam $jam;
    private LogDataSiswa $log;

    public function __construct(?BaseConnection $db = null, ?Jam $jam = null)
    {
        $this->db  = $db ?? db_connect();
        $this->jam = $jam ?? new Jam();
        $this->log = new LogDataSiswa();
    }

    /**
     * WA number in the stored form 62… (docs/11 VAL-23), '' for empty, or
     * null when invalid.
     */
    public static function bakukanWa(string $wa): ?string
    {
        $wa = preg_replace('/[\s.()-]/u', '', trim($wa));

        if ($wa === '') {
            return '';
        }
        $wa = preg_replace(['/^\+62/', '/^0/'], ['62', '62'], $wa);

        return preg_match('/^628\d{7,12}$/', $wa) === 1 ? $wa : null;
    }

    /**
     * Adds a student with the initial active period and placement, and a
     * `belum_aktif` account with username NISN and no password (FS-MD-04
     * item 1, FS-AKN-05 A1).
     *
     * @param array<string, mixed> $isian
     *
     * @return array{galat: array<string, string>}|array{id: int}
     */
    public function buat(array $isian, int $pelakuId): array
    {
        [$data, $atribut, $galat] = $this->periksa($isian, null);
        $tanggal = $this->tanggal($isian['tanggal_mulai'] ?? '');
        $rombel  = null;

        if ($tanggal === null) {
            $galat['tanggal_mulai'] = 'Tanggal mulai wajib diisi dengan tanggal yang sah.';
        }
        if (($pesan = $this->periksaRombel($isian['rombel_id'] ?? '', $tanggal, $rombel)) !== null) {
            $galat['rombel_id'] = $pesan;
        }
        if ($galat !== []) {
            return ['galat' => $galat];
        }

        try {
            $id = (new Transaction($this->db))->run(function () use ($data, $atribut, $tanggal, $rombel, $pelakuId): int {
                $now = $this->sekarang();
                $this->db->table('siswa')->insert($data + ['created_at' => $now, 'updated_at' => $now]);
                $id = (int) $this->db->insertID();

                $this->db->table('masa_aktif')->insert(['siswa_id' => $id, 'tanggal_mulai' => $tanggal, 'dibatalkan' => 0, 'dibuat_oleh' => $pelakuId, 'created_at' => $now, 'updated_at' => $now]);
                // A new student has no other placement, so it cannot overlap (docs/06 §6.7 rule 1).
                $this->db->table('penempatan')->insert(['siswa_id' => $id, 'rombel_id' => $rombel['id'], 'tanggal_mulai' => $tanggal, 'dibuat_oleh' => $pelakuId, 'created_at' => $now, 'updated_at' => $now]);
                $this->db->table('akun')->insert([
                    'jenis' => 'siswa', 'username' => $data['nisn'], 'siswa_id' => $id, 'password_hash' => null,
                    'status' => 'belum_aktif', 'wajib_ganti_password' => 1, 'created_at' => $now, 'updated_at' => $now,
                ]);
                $berubah = $this->simpanAtribut($id, $atribut, []);
                // HOOK L04-02: write antrean_hitung_ulang for the new period and placement (docs/06 §11.4).

                $this->log->catat($id, 'siswa_dibuat', null, array_filter($data, static fn ($v) => $v !== null)
                    + ['kelas' => $rombel['nama'], 'tanggal_mulai' => $tanggal] + ($berubah['baru'] === [] ? [] : ['atribut' => $berubah['baru']]), $pelakuId);

                return $id;
            });
        } catch (DatabaseException $e) {
            return $this->bentrok($e);
        }

        return ['id' => $id];
    }

    /**
     * Saves the edit form when `updated_at` still equals $versi (FS-MD-04
     * item 2). A new NISN also becomes the account username (FS-AKN-05 A3).
     *
     * @param array<string, mixed> $isian
     *
     * @return array{galat: array<string, string>}|array{konflik: true}|array{ok: true}
     */
    public function perbarui(int $id, string $versi, array $isian, int $pelakuId): array
    {
        [$data, $atribut, $galat] = $this->periksa($isian, $id);

        if ($galat !== []) {
            return ['galat' => $galat];
        }

        try {
            return (new Transaction($this->db))->run(function () use ($id, $versi, $data, $atribut, $pelakuId): array {
                $lama = $this->db->table('siswa')->where('id', $id)->get()->getRowArray();

                if ($lama === null || ! $this->ubahSiswa($id, $versi, $data)) {
                    return ['konflik' => true];
                }

                $ubah = [];
                foreach ($data as $kolom => $baru) {
                    if ($lama[$kolom] !== $baru) {
                        $ubah[$kolom] = [$lama[$kolom], $baru];
                    }
                }
                $atributBerubah = $this->simpanAtribut($id, $atribut, $this->nilaiAtribut($id));

                if (isset($ubah['nisn'])) {
                    $this->db->table('akun')->where(['jenis' => 'siswa', 'siswa_id' => $id])->update(['username' => $data['nisn'], 'updated_at' => $this->sekarang()]);
                    $this->log->catat($id, 'nisn_diubah', ['nisn' => $ubah['nisn'][0]], ['nisn' => $ubah['nisn'][1]], $pelakuId);
                    unset($ubah['nisn']);
                }
                if (isset($ubah['wa_ortu'])) {
                    $this->catatWa($id, ...$ubah['wa_ortu'], pelakuId: $pelakuId);
                    unset($ubah['wa_ortu']);
                }

                $dataLama = array_map(static fn (array $p) => $p[0], $ubah) + ($atributBerubah['lama'] === [] ? [] : ['atribut' => $atributBerubah['lama']]);
                $dataBaru = array_map(static fn (array $p) => $p[1], $ubah) + ($atributBerubah['baru'] === [] ? [] : ['atribut' => $atributBerubah['baru']]);

                if ($ubah !== [] || $atributBerubah['lama'] !== [] || $atributBerubah['baru'] !== []) {
                    // The name gives context when only other fields changed (docs/06 DB-14).
                    $this->log->catat($id, 'data_diubah', $dataLama + ['nama' => $lama['nama']], $dataBaru + ['nama' => $data['nama']], $pelakuId);
                }

                return ['ok' => true];
            });
        } catch (DatabaseException $e) {
            return $this->bentrok($e);
        }
    }

    /**
     * Sets or clears the WA number when `updated_at` still equals $versi
     * (FS-MD-04 item 3). The caller checks scope (HA-MD-06).
     *
     * @return array{galat: array{wa_ortu: string}}|array{konflik: true}|array{ok: true, wa_ortu: string|null}
     */
    public function gantiWa(int $id, string $versi, string $wa, int $pelakuId): array
    {
        $wa = self::bakukanWa($wa);

        if ($wa === null) {
            return ['galat' => ['wa_ortu' => 'Nomor WA tidak valid. Tulis nomor ponsel yang diawali 08, misalnya 081234567890.']];
        }
        $wa = $wa === '' ? null : $wa;

        return (new Transaction($this->db))->run(function () use ($id, $versi, $wa, $pelakuId): array {
            $lama = $this->db->table('siswa')->select('wa_ortu')->where('id', $id)->get()->getRow();

            if ($lama === null || ! $this->ubahSiswa($id, $versi, ['wa_ortu' => $wa])) {
                return ['konflik' => true];
            }
            if ($lama->wa_ortu !== $wa) {
                $this->catatWa($id, $lama->wa_ortu, $wa, $pelakuId);
            }

            return ['ok' => true, 'wa_ortu' => $wa];
        });
    }

    /**
     * Closes the open active period (FS-MD-04 item 4, docs/06 §6.6 rules 3
     * and 4) and deactivates the account (FS-AKN-05 A2). $periodeId is the
     * open period the form was opened for (docs/07 ARS-41).
     *
     * @param array<string, mixed> $isian tanggal_selesai, alasan_nonaktif, keterangan_nonaktif
     *
     * @return array{galat: array<string, string>}|array{pesan: string}|array{ok: true, dibatalkan: bool}
     */
    public function nonaktifkan(int $id, int $periodeId, array $isian, int $pelakuId): array
    {
        $alasan     = (string) ($isian['alasan_nonaktif'] ?? '');
        $keterangan = $this->teks($isian['keterangan_nonaktif'] ?? '', true);
        $galat      = [];

        if (! isset(config('Label')->codes['masa_aktif.alasan_nonaktif'][$alasan])) {
            $galat['alasan_nonaktif'] = 'Pilih alasan dari daftar.';
        }
        if ($alasan === 'lainnya') {
            $pesan = $this->periksaPanjang($keterangan, 'Keterangan', 5, 255, true);
            if ($pesan !== null) {
                $galat['keterangan_nonaktif'] = $pesan;
            }
        } else {
            $keterangan = '';
        }

        return (new Transaction($this->db))->run(function () use ($id, $periodeId, $isian, $alasan, $keterangan, $galat, $pelakuId): array {
            $this->kunciSiswa($id);
            $periode = $this->db->table('masa_aktif')->where(['siswa_id' => $id, 'tanggal_selesai' => null])->get()->getRowArray();

            if ($periode === null || (int) $periode['id'] !== $periodeId) {
                return ['pesan' => $periode === null ? 'Siswa ini sudah nonaktif.' : 'Masa aktif siswa ini sudah berubah. Periksa data terbaru, lalu ulangi.'];
            }

            $hariIni = $this->jam->today();
            // docs/06 §6.6 rule 4: wrong input, or a period not started yet, is cancelled.
            $batal   = $alasan === 'salah_input' || $periode['tanggal_mulai'] > $hariIni;
            $selesai = $batal ? $periode['tanggal_mulai'] : $this->tanggal($isian['tanggal_selesai'] ?? '');

            if (! $batal) {
                if ($selesai === null) {
                    $galat['tanggal_selesai'] = 'Tanggal terakhir aktif wajib diisi dengan tanggal yang sah.';
                } elseif ($selesai > $hariIni) {
                    $galat['tanggal_selesai'] = 'Tanggal terakhir aktif tidak boleh setelah hari ini.';
                } elseif ($selesai < $periode['tanggal_mulai']) {
                    $galat['tanggal_selesai'] = 'Tanggal terakhir aktif tidak boleh sebelum tanggal mulai aktif, ' . format_date($periode['tanggal_mulai']) . '.';
                }
            }
            if ($galat !== []) {
                return ['galat' => $galat];
            }

            $now = $this->sekarang();
            $this->db->table('masa_aktif')->where('id', $periode['id'])->update([
                'tanggal_selesai'     => $selesai,
                'alasan_nonaktif'     => $alasan,
                'keterangan_nonaktif' => $keterangan === '' ? null : $keterangan,
                'dibatalkan'          => $batal ? 1 : 0,
                'dinonaktifkan_oleh'  => $pelakuId,
                'updated_at'          => $now,
            ]);
            // HOOK L04-02: write antrean_hitung_ulang for the dates the period no longer covers (docs/06 §11.4).

            $akun = $this->db->table('akun')->select('status')->where(['jenis' => 'siswa', 'siswa_id' => $id])->get()->getRow();
            $this->db->table('akun')->where(['jenis' => 'siswa', 'siswa_id' => $id])->update(['status' => 'nonaktif', 'updated_at' => $now]);

            $label = config('Label')->codes['masa_aktif.alasan_nonaktif'][$alasan];
            $this->log->catat(
                $id,
                'dinonaktifkan',
                ['tanggal_mulai' => $periode['tanggal_mulai'], 'akun_status' => $akun?->status],
                ['tanggal_mulai' => $periode['tanggal_mulai'], 'tanggal_selesai' => $selesai, 'alasan_nonaktif' => $alasan, 'dibatalkan' => $batal],
                $pelakuId,
                $keterangan === '' ? $label : "{$label}: {$keterangan}",
            );

            return ['ok' => true, 'dibatalkan' => $batal];
        });
    }

    /**
     * Opens a new active period and places the student (FS-MD-04 item 5).
     * A placement still running on the new start date ends the day before,
     * as when moving class (docs/06 §6.7 rule 2). The account gets back the
     * status it had before deactivation (FS-AKN-05 A2).
     *
     * @param array<string, mixed> $isian tanggal_mulai, rombel_id
     *
     * @return array{galat: array<string, string>}|array{pesan: string}|array{ok: true}
     */
    public function aktifkan(int $id, array $isian, int $pelakuId): array
    {
        $tanggal = $this->tanggal($isian['tanggal_mulai'] ?? '');
        $rombel  = null;
        $galat   = [];

        if ($tanggal === null) {
            $galat['tanggal_mulai'] = 'Tanggal mulai aktif wajib diisi dengan tanggal yang sah.';
        }
        if (($pesan = $this->periksaRombel($isian['rombel_id'] ?? '', $tanggal, $rombel)) !== null) {
            $galat['rombel_id'] = $pesan;
        }
        if ($galat !== []) {
            return ['galat' => $galat];
        }

        return (new Transaction($this->db))->run(function () use ($id, $tanggal, $rombel, $pelakuId): array {
            $this->kunciSiswa($id);

            if ($this->db->table('masa_aktif')->where(['siswa_id' => $id, 'tanggal_selesai' => null])->countAllResults() > 0) {
                return ['pesan' => 'Siswa ini sudah aktif.'];
            }

            // Counted periods must not overlap (docs/06 §6.6 rule 1).
            $akhir = $this->db->table('masa_aktif')->selectMax('tanggal_selesai')->where(['siswa_id' => $id, 'dibatalkan' => 0])->get()->getRow()->tanggal_selesai;
            if ($akhir !== null && $tanggal <= $akhir) {
                return ['galat' => ['tanggal_mulai' => 'Tanggal mulai aktif harus setelah tanggal terakhir aktif sebelumnya, ' . format_date($akhir) . '.']];
            }

            $siswa     = $this->db->table('siswa')->select('nama')->where('id', $id)->get()->getRow();
            $penempatan = (new DaftarSiswa($this->db))->riwayatPenempatan($id);
            foreach ($penempatan as $p) {
                if ($p['selesai'] >= $tanggal && $p['tanggal_mulai'] >= $tanggal) {
                    return ['galat' => ['rombel_id' => "Penempatan ini tumpang tindih dengan penempatan {$siswa->nama} di {$p['rombel_nama']}, "
                        . format_date($p['tanggal_mulai']) . ' s.d. ' . format_date($p['selesai']) . '.']];
                }
            }

            $now = $this->sekarang();
            $this->db->table('masa_aktif')->insert(['siswa_id' => $id, 'tanggal_mulai' => $tanggal, 'dibatalkan' => 0, 'dibuat_oleh' => $pelakuId, 'created_at' => $now, 'updated_at' => $now]);

            foreach ($penempatan as $p) {
                if ($p['selesai'] >= $tanggal) {
                    $sebelum = (new DateTimeImmutable($tanggal))->modify('-1 day')->format('Y-m-d');
                    $this->db->table('penempatan')->where('id', $p['id'])->update(['tanggal_selesai' => $sebelum, 'updated_at' => $now]);
                    $this->log->catat($id, 'penempatan_diubah', ['kelas' => $p['rombel_nama'], 'tanggal_mulai' => $p['tanggal_mulai'], 'tanggal_selesai' => $p['tanggal_selesai']], ['kelas' => $p['rombel_nama'], 'tanggal_mulai' => $p['tanggal_mulai'], 'tanggal_selesai' => $sebelum], $pelakuId);
                }
            }
            $this->db->table('penempatan')->insert(['siswa_id' => $id, 'rombel_id' => $rombel['id'], 'tanggal_mulai' => $tanggal, 'dibuat_oleh' => $pelakuId, 'created_at' => $now, 'updated_at' => $now]);
            // HOOK L04-02: write antrean_hitung_ulang for the new period and placement changes (docs/06 §11.4).

            $status = $this->statusAkunSebelum($id);
            $this->db->table('akun')->where(['jenis' => 'siswa', 'siswa_id' => $id, 'status' => 'nonaktif'])->update(['status' => $status, 'updated_at' => $now]);

            $this->log->catat($id, 'diaktifkan_kembali', null, ['tanggal_mulai' => $tanggal, 'akun_status' => $status], $pelakuId);
            $this->log->catat($id, 'penempatan_dibuat', null, ['kelas' => $rombel['nama'], 'tahun_ajaran' => $rombel['tahun_ajaran_nama'], 'tanggal_mulai' => $tanggal], $pelakuId);

            return ['ok' => true];
        });
    }

    /**
     * Who made the latest data change and when, for the conflict message
     * (docs/04 §4.6).
     *
     * @return array{nama: string|null, waktu: string}|null
     */
    public function perubahanTerakhir(int $id): ?array
    {
        $row = $this->db->table('log_data_siswa l')
            ->select('p.nama, l.created_at')
            ->join('akun p', 'p.id = l.pelaku_id', 'left')
            ->where('l.siswa_id', $id)
            ->whereIn('l.jenis', ['siswa_dibuat', 'data_diubah', 'nisn_diubah', 'wa_diubah'])
            ->orderBy('l.id', 'DESC')->limit(1)->get()->getRowArray();

        return $row === null ? null : ['nama' => $row['nama'], 'waktu' => $row['created_at']];
    }

    /**
     * Cleans (docs/11 VAL-02, VAL-03) and checks the add/edit fields.
     *
     * @param array<string, mixed> $isian
     *
     * @return array{array<string, string|null>, array<int, string>, array<string, string>} Columns, attribute values, errors
     */
    private function periksa(array $isian, ?int $kecualiId): array
    {
        $galat = [];
        $data  = [
            'nisn'          => trim((string) ($isian['nisn'] ?? '')),
            'nama'          => $this->teks($isian['nama'] ?? ''),
            'nis'           => $this->teks($isian['nis'] ?? ''),
            'jenis_kelamin' => (string) ($isian['jenis_kelamin'] ?? ''),
            'tanggal_lahir' => trim((string) ($isian['tanggal_lahir'] ?? '')),
            'alamat'        => $this->teks($isian['alamat'] ?? '', true),
            'nama_ortu'     => $this->teks($isian['nama_ortu'] ?? ''),
        ];

        // VAL-18
        if (preg_match('/^\d{10}$/', $data['nisn']) !== 1) {
            $galat['nisn'] = 'NISN harus 10 digit angka.';
        } elseif (($pemilik = $this->pemilik('nisn', $data['nisn'], $kecualiId)) !== null) {
            $galat['nisn'] = "NISN sudah terdaftar atas nama {$pemilik['nama']} ({$pemilik['keadaan']}).";
        }

        // VAL-15
        if ($data['nama'] === '') {
            $galat['nama'] = 'Nama lengkap wajib diisi.';
        } elseif (($pesan = $this->periksaNama($data['nama'])) !== null) {
            $galat['nama'] = $pesan;
        }

        // VAL-19
        if ($data['nis'] !== '') {
            if (mb_strlen($data['nis']) > 20) {
                $galat['nis'] = 'NIS paling panjang 20 karakter.';
            } elseif (preg_match('#^[A-Za-z0-9./-]+$#', $data['nis']) !== 1) {
                $galat['nis'] = 'NIS hanya boleh berisi huruf, angka, titik, garis miring, dan tanda hubung.';
            } elseif (($pemilik = $this->pemilik('nis', $data['nis'], $kecualiId)) !== null) {
                $galat['nis'] = "NIS sudah terdaftar atas nama {$pemilik['nama']}.";
            }
        }

        if ($data['jenis_kelamin'] !== '' && ! in_array($data['jenis_kelamin'], ['L', 'P'], true)) {
            $galat['jenis_kelamin'] = 'Pilih jenis kelamin dari daftar.';
        }

        // VAL-24
        if ($data['tanggal_lahir'] !== '') {
            $lahir = $this->tanggal($data['tanggal_lahir']);
            if ($lahir === null) {
                $galat['tanggal_lahir'] = 'Tanggal lahir tidak valid.';
            } elseif ($lahir > $this->jam->today()) {
                $galat['tanggal_lahir'] = 'Tanggal lahir tidak boleh setelah hari ini.';
            } elseif ($lahir < '2000-01-01') {
                $galat['tanggal_lahir'] = 'Periksa tahun lahir.';
            }
        }

        // VAL-27
        if (mb_strlen($data['alamat']) > 255) {
            $galat['alamat'] = 'Alamat rumah paling panjang 255 karakter.';
        }
        if ($data['nama_ortu'] !== '' && ($pesan = $this->periksaNama($data['nama_ortu'])) !== null) {
            $galat['nama_ortu'] = $pesan;
        }

        // VAL-23, FS-MD-04 E3
        $wa = self::bakukanWa((string) ($isian['wa_ortu'] ?? ''));
        if ($wa === null) {
            $galat['wa_ortu'] = 'Nomor WA tidak valid. Tulis nomor ponsel yang diawali 08, misalnya 081234567890.';
        }
        $data['wa_ortu'] = $wa;

        [$atribut, $galatAtribut] = $this->periksaAtribut((array) ($isian['atribut'] ?? []));

        // VAL-03: empty optional fields are stored as NULL.
        $data = array_map(static fn (?string $v): ?string => $v === '' ? null : $v, $data);

        return [$data, $atribut, $galat + $galatAtribut];
    }

    /**
     * Values of the active attributes in their stored form (docs/06 §6.9,
     * docs/11 VAL-10, VAL-11, §5.2 FS-MD-04 E8).
     *
     * @param array<array-key, mixed> $isian
     *
     * @return array{array<int, string>, array<string, string>} Values by attribute ID ('' = empty), errors
     */
    private function periksaAtribut(array $isian): array
    {
        $nilai = [];
        $galat = [];

        foreach ((new DaftarSiswa($this->db))->atribut(null) as $a) {
            $id    = (int) $a['id'];
            $label = $a['label'];
            $v     = is_string($isian[$id] ?? null) ? $this->teks($isian[$id]) : '';
            $kunci = "atribut_{$id}";

            if ($v === '') {
                if ($a['wajib']) {
                    $galat[$kunci] = "{$label} wajib diisi.";
                }
                $nilai[$id] = '';

                continue;
            }

            switch ($a['tipe']) {
                case 'angka':
                    if (preg_match('/^-?\d+([.,]\d+)?$/', $v) !== 1) {
                        $galat[$kunci] = "{$label} harus berupa angka.";
                    }
                    $v = str_replace(',', '.', $v);
                    break;

                case 'tanggal':
                    if ($this->tanggal($v) === null) {
                        $galat[$kunci] = "{$label} harus berupa tanggal.";
                    }
                    break;

                case 'pilihan':
                    if (! in_array($v, $a['pilihan'], true)) {
                        $galat[$kunci] = "Pilih salah satu {$label}.";
                    }
                    break;

                default:
                    if (mb_strlen($v) > 255) {
                        $galat[$kunci] = "{$label} paling panjang 255 karakter.";
                    }
            }
            $nilai[$id] = $v;
        }

        return [$nilai, $galat];
    }

    /**
     * Writes attribute values; empty values are deleted (docs/06 §6.9).
     *
     * @param array<int, string> $nilai Values by attribute ID
     * @param array<int, string> $lama  Stored values by attribute ID
     *
     * @return array{lama: array<string, string|null>, baru: array<string, string|null>} Changes by label, for the log
     */
    private function simpanAtribut(int $siswaId, array $nilai, array $lama): array
    {
        $label   = array_column($this->db->table('atribut_siswa')->select('id, label')->get()->getResultArray(), 'label', 'id');
        $berubah = ['lama' => [], 'baru' => []];
        $now     = $this->sekarang();

        foreach ($nilai as $id => $v) {
            $sebelum = $lama[$id] ?? '';
            if ($v === $sebelum) {
                continue;
            }

            $where = ['siswa_id' => $siswaId, 'atribut_id' => $id];
            if ($v === '') {
                $this->db->table('nilai_atribut_siswa')->where($where)->delete();
            } elseif ($sebelum === '') {
                $this->db->table('nilai_atribut_siswa')->insert($where + ['nilai' => $v, 'created_at' => $now, 'updated_at' => $now]);
            } else {
                $this->db->table('nilai_atribut_siswa')->where($where)->update(['nilai' => $v, 'updated_at' => $now]);
            }

            $berubah['lama'][$label[$id]] = $sebelum === '' ? null : $sebelum;
            $berubah['baru'][$label[$id]] = $v === '' ? null : $v;
        }

        // A new student has no old values; keep only what was filled.
        if ($lama === []) {
            $berubah['baru'] = array_filter($berubah['baru'], static fn ($v) => $v !== null);
            $berubah['lama'] = [];
        }

        return $berubah;
    }

    /**
     * @return array<int, string>
     */
    private function nilaiAtribut(int $siswaId): array
    {
        $rows = $this->db->table('nilai_atribut_siswa')->where('siswa_id', $siswaId)->get()->getResultArray();

        return array_map('strval', array_column($rows, 'nilai', 'atribut_id'));
    }

    /**
     * The rombel must exist and its school year must cover the date
     * (FS-MD-05 E3). Sets $rombel to the row with `tahun_ajaran_nama`.
     *
     * @param array<string, mixed>|null $rombel
     */
    private function periksaRombel(mixed $rombelId, ?string $tanggal, ?array &$rombel): ?string
    {
        $rombelId = (string) $rombelId;

        if ($rombelId === '') {
            return 'Pilih kelas.';
        }

        $rombel = ctype_digit($rombelId) ? $this->db->table('rombel r')
            ->select('r.*, ta.nama AS tahun_ajaran_nama, ta.tanggal_mulai AS ta_mulai, ta.tanggal_selesai AS ta_selesai')
            ->join('tahun_ajaran ta', 'ta.id = r.tahun_ajaran_id')
            ->where('r.id', (int) $rombelId)->get()->getRowArray() : null;

        if ($rombel === null) {
            return 'Kelas yang dipilih sudah tidak ada. Pilih lagi.';
        }
        if ($tanggal !== null && ($tanggal < $rombel['ta_mulai'] || $tanggal > $rombel['ta_selesai'])) {
            return 'Tanggal mulai ' . format_date($tanggal) . " di luar tahun ajaran {$rombel['tahun_ajaran_nama']}.";
        }

        return null;
    }

    /**
     * The student holding this NISN or NIS, with their class today or status.
     *
     * @return array{nama: string, keadaan: string}|null
     */
    private function pemilik(string $kolom, string $nilai, ?int $kecualiId): ?array
    {
        $builder = $this->db->table('siswa')->select('id, nama')->where($kolom, $nilai);
        if ($kecualiId !== null) {
            $builder->where('id !=', $kecualiId);
        }
        $row = $builder->get()->getRowArray();

        if ($row === null) {
            return null;
        }

        $hariIni = $this->jam->today();
        $rujukan = new Rujukan($this->db);
        $rombel  = $rujukan->rombelSiswa((int) $row['id'], $hariIni);
        $status  = config('Label')->codes['siswa.status'][$rujukan->statusSiswa((int) $row['id'], $hariIni)];

        return ['nama' => $row['nama'], 'keadaan' => $rombel === null ? $status : "kelas {$rombel['nama']}"];
    }

    private function periksaNama(string $nama): ?string
    {
        return match (true) {
            mb_strlen($nama) < 2   => 'Nama paling sedikit 2 karakter.',
            mb_strlen($nama) > 100 => 'Nama paling panjang 100 karakter.',
            preg_match(self::NAMA_SAH, $nama) !== 1 => 'Nama hanya boleh berisi huruf, spasi, titik, koma, petik, dan tanda hubung.',
            default => null,
        };
    }

    private function periksaPanjang(string $teks, string $label, int $min, int $max, bool $wajib): ?string
    {
        return match (true) {
            $teks === '' => $wajib ? "{$label} wajib diisi." : null,
            mb_strlen($teks) < $min => "{$label} paling sedikit {$min} karakter.",
            mb_strlen($teks) > $max => "{$label} paling panjang {$max} karakter.",
            default => null,
        };
    }

    /**
     * Trim, NFC, and (single line) collapse double spaces (docs/11 VAL-02).
     */
    private function teks(mixed $teks, bool $multiBaris = false): string
    {
        $teks = trim((string) Normalizer::normalize(str_replace("\r\n", "\n", (string) $teks)));

        return $multiBaris ? $teks : (string) preg_replace('/\s{2,}|\n/u', ' ', $teks);
    }

    /**
     * A real `Y-m-d` date, or null (docs/11 VAL-11).
     */
    private function tanggal(mixed $nilai): ?string
    {
        $nilai = trim((string) $nilai);
        $d     = DateTimeImmutable::createFromFormat('!Y-m-d', $nilai);

        return $d !== false && $d->format('Y-m-d') === $nilai ? $nilai : null;
    }

    /**
     * UPDATE … WHERE updated_at = $versi (docs/06 DB-11); false on a stale token.
     *
     * @param array<string, string|null> $data
     */
    private function ubahSiswa(int $id, string $versi, array $data): bool
    {
        $this->db->table('siswa')->where('id', $id)->where('updated_at', $versi)
            ->update($data + ['updated_at' => $this->sekarang()]);

        // foundRows = true: matched rows, even when no value changed (ARS-40).
        return $this->db->affectedRows() > 0;
    }

    private function catatWa(int $id, ?string $lama, ?string $baru, int $pelakuId): void
    {
        $this->log->catat($id, 'wa_diubah', ['wa_ortu' => $lama], ['wa_ortu' => $baru], $pelakuId);
    }

    /**
     * Row lock on the student before overlap checks (docs/06 §16).
     */
    private function kunciSiswa(int $id): void
    {
        $this->db->query('SELECT id FROM siswa WHERE id = ? FOR UPDATE', [$id]);
    }

    /**
     * Account status before the last deactivation (FS-AKN-05 A2), from the
     * log; else derived from whether the password was ever changed.
     */
    private function statusAkunSebelum(int $id): string
    {
        $log = $this->db->table('log_data_siswa')->select('data_lama')
            ->where(['siswa_id' => $id, 'jenis' => 'dinonaktifkan'])->orderBy('id', 'DESC')->limit(1)->get()->getRow();
        $status = $log === null ? null : (json_decode((string) $log->data_lama, true)['akun_status'] ?? null);

        if (in_array($status, ['aktif', 'belum_aktif'], true)) {
            return $status;
        }

        $akun = $this->db->table('akun')->select('password_diganti_at')->where(['jenis' => 'siswa', 'siswa_id' => $id])->get()->getRow();

        return $akun?->password_diganti_at === null ? 'belum_aktif' : 'aktif';
    }

    /**
     * 1062 on a known unique key: another request took the value after the
     * check (docs/11 GAL-12 item 2).
     *
     * @return array{galat: array<string, string>}
     */
    private function bentrok(DatabaseException $e): array
    {
        $pesan = $e->getMessage();

        return match (true) {
            $e->getCode() === 1062 && (str_contains($pesan, 'uq_siswa_nisn') || str_contains($pesan, 'uq_akun_username')) => ['galat' => ['nisn' => 'NISN sudah terdaftar.']],
            $e->getCode() === 1062 && str_contains($pesan, 'uq_siswa_nis') => ['galat' => ['nis' => 'NIS sudah terdaftar.']],
            default => throw $e,
        };
    }

    private function sekarang(): string
    {
        return $this->jam->now()->toDateTimeString();
    }
}
