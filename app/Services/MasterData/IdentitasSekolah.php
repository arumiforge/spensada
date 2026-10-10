<?php

namespace App\Services\MasterData;

use App\Services\Akun\LogAktivitas;
use App\Services\Berkas\Gambar;
use App\Services\Sistem\Jam;
use App\Services\Sistem\Transaction;
use CodeIgniter\HTTP\Files\UploadedFile;
use Normalizer;

/**
 * School identity in `pengaturan` (docs/04 FS-MD-01, docs/06 §6.1): official
 * name, address, logo and privacy notice. The version token is the latest
 * `updated_at` of these keys (docs/04 §4.6).
 */
class IdentitasSekolah
{
    public const KUNCI = ['sekolah_nama', 'sekolah_alamat', 'sekolah_logo', 'privasi_teks'];

    /** Text limits; `nilai` is TEXT, so these only keep the screens readable (VAL-05). */
    public const PANJANG = ['sekolah_nama' => 150, 'sekolah_alamat' => 255, 'privasi_teks' => 2000];

    private Jam $jam;

    public function __construct(?Jam $jam = null)
    {
        $this->jam = $jam ?? new Jam();
    }

    /**
     * The four keys (null when empty) plus `versi`, the version token for the form.
     *
     * @return array{sekolah_nama: string|null, sekolah_alamat: string|null, sekolah_logo: string|null, privasi_teks: string|null, versi: string}
     */
    public function ambil(bool $kunci = false): array
    {
        $db   = db_connect();
        $sql  = $db->table('pengaturan')->whereIn('kunci', self::KUNCI)->getCompiledSelect();
        // Row locks keep two saves from both passing the version check (docs/07 ARS-40).
        $rows = $db->query($kunci ? $sql . ' FOR UPDATE' : $sql)->getResultArray();
        $hasil = array_fill_keys(self::KUNCI, null);
        $versi = '';

        foreach ($rows as $row) {
            $hasil[$row['kunci']] = $row['nilai'] === '' ? null : $row['nilai'];
            $versi                = max($versi, (string) $row['updated_at']);
        }

        return $hasil + ['versi' => $versi];
    }

    /**
     * Path of the uploaded logo relative to writable/uploads/, or null for the bundled emblem.
     */
    public function logo(): ?string
    {
        return $this->ambil()['sekolah_logo'];
    }

    /**
     * Logo address for <img>: `/logo?v=<versi>`; `v` changes whenever the logo
     * changes, because each upload gets a new random file name (docs/09 §13).
     */
    public function urlLogo(): string
    {
        $logo = $this->logo();

        return url_to('publik.logo.index') . '?v=' . ($logo === null ? 'bawaan' : substr(pathinfo($logo, PATHINFO_FILENAME), 0, 8));
    }

    /**
     * Saves the identity when the version still matches. An invalid logo is
     * refused while the other fields still save (FS-MD-01 E2).
     *
     * @return array{galat: array<string, string>}|array{konflik: true}|array{ok: true, logo_galat: string|null}
     */
    public function simpan(string $versi, string $nama, string $alamat, string $privasi, ?UploadedFile $logo, bool $hapusLogo, int $pelakuId, ?string $ip): array
    {
        $baru  = [
            'sekolah_nama'   => $this->bersihkan($nama),
            'sekolah_alamat' => $this->bersihkan($alamat),
            'privasi_teks'   => $this->bersihkan($privasi),
        ];
        $galat = [];

        if ($baru['sekolah_nama'] === null) {
            $galat['sekolah_nama'] = 'Nama resmi sekolah wajib diisi.';
        }
        foreach (['sekolah_nama' => 'Nama resmi sekolah', 'sekolah_alamat' => 'Alamat'] as $kunci => $label) {
            if ($baru[$kunci] !== null && str_contains($baru[$kunci], "\n")) {
                $galat[$kunci] = "{$label} ditulis dalam satu baris.";
            }
        }
        foreach (['sekolah_nama' => 'Nama resmi sekolah', 'sekolah_alamat' => 'Alamat', 'privasi_teks' => 'Pemberitahuan privasi'] as $kunci => $label) {
            if (! isset($galat[$kunci]) && mb_strlen($baru[$kunci] ?? '') > self::PANJANG[$kunci]) {
                $galat[$kunci] = "{$label} paling panjang " . format_number(self::PANJANG[$kunci]) . ' karakter.';
            }
        }
        if ($galat !== []) {
            return ['galat' => $galat];
        }

        $gambar    = new Gambar();
        $logoGalat = $logo === null ? '' : $gambar->periksaUnggahan($logo);

        $hasil = (new Transaction())->run(function (Transaction $tx) use ($versi, $baru, $logo, $logoGalat, $gambar, $hapusLogo, $pelakuId, $ip): array {
            $lama = $this->ambil(true);

            if ($lama['versi'] !== $versi) {
                return ['konflik' => true];
            }

            // Written inside the transaction, so a retry or rollback never keeps a stray file (ARS-43).
            if ($logoGalat === null) {
                $baru['sekolah_logo'] = $gambar->simpanLogo($logo->getTempName());

                if ($baru['sekolah_logo'] === null) {
                    unset($baru['sekolah_logo']);
                    $logoGalat = "File {$logo->getClientName()} tidak dapat dibaca sebagai gambar.";
                } else {
                    $tx->addFile(WRITEPATH . 'uploads/' . $baru['sekolah_logo']);
                }
            } elseif ($hapusLogo && $logoGalat === '') {
                $baru['sekolah_logo'] = null;
            }

            $now  = $this->jam->now()->toDateTimeString();
            $data = [];

            foreach ($baru as $kunci => $nilai) {
                if ($lama[$kunci] === $nilai) {
                    continue;
                }

                db_connect()->table('pengaturan')->upsert(['kunci' => $kunci, 'nilai' => $nilai, 'updated_at' => $now, 'diubah_oleh' => $pelakuId]);
                // docs/12 SEC-59: for the logo only a marker, never the file.
                $data[$kunci] = $kunci === 'sekolah_logo' ? ($nilai === null ? 'dihapus' : 'diganti') : ['lama' => $lama[$kunci], 'baru' => $nilai];
            }

            if ($data !== []) {
                (new LogAktivitas())->catat('pengaturan_diubah', $pelakuId, null, $data, null, $ip);
            }

            return ['ok' => true, 'logo_galat' => $logoGalat === '' ? null : $logoGalat, 'lamaLogo' => isset($data['sekolah_logo']) ? $lama['sekolah_logo'] : null];
        });

        if (isset($hasil['konflik'])) {
            return $hasil;
        }

        // The replaced file goes after commit (docs/07 ARS-43).
        if ($hasil['lamaLogo'] !== null) {
            @unlink(WRITEPATH . 'uploads/' . $hasil['lamaLogo']);
        }

        return ['ok' => true, 'logo_galat' => $hasil['logo_galat']];
    }

    /**
     * Who saved the identity last and when, for the conflict message (docs/04 §4.6).
     *
     * @return array{nama: string|null, waktu: string}|null
     */
    public function perubahanTerakhir(): ?array
    {
        $row = db_connect()->table('pengaturan p')
            ->select('a.nama, p.updated_at')
            ->join('akun a', 'a.id = p.diubah_oleh', 'left')
            ->whereIn('p.kunci', self::KUNCI)
            ->where('p.diubah_oleh IS NOT NULL')
            ->orderBy('p.updated_at', 'DESC')
            ->limit(1)->get()->getRowArray();

        return $row === null ? null : ['nama' => $row['nama'], 'waktu' => $row['updated_at']];
    }

    /**
     * docs/11 VAL-02 and VAL-03: trimmed, `\n` line ends, NFC; empty is null.
     */
    private function bersihkan(string $teks): ?string
    {
        $teks = trim((string) Normalizer::normalize(str_replace("\r\n", "\n", $teks)));

        return $teks === '' ? null : $teks;
    }
}
