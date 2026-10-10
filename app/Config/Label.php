<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Screen labels, written once and used by views, exports, PDF, and flyers
 * (docs/08 UI-06, UI-75). Labels for admin-only codes (docs/09 §14) are added
 * by the step that first shows them.
 */
class Label extends BaseConfig
{
    /**
     * Daily status (docs/08 §4.2): label, short letter, and Lucide icon.
     * The empty key is an empty `status_harian.status` shown as "Belum hadir";
     * services decide when an empty status reads as Alpa (docs/06 §11.3).
     *
     * @var array<string, array{label: string, letter: string, icon: string}>
     */
    public array $status = [
        'hadir'      => ['label' => 'Hadir', 'letter' => 'H', 'icon' => 'circle-check'],
        'terlambat'  => ['label' => 'Terlambat', 'letter' => 'T', 'icon' => 'clock-alert'],
        'izin'       => ['label' => 'Izin', 'letter' => 'I', 'icon' => 'file-text'],
        'sakit'      => ['label' => 'Sakit', 'letter' => 'S', 'icon' => 'thermometer'],
        'dispensasi' => ['label' => 'Dispensasi', 'letter' => 'D', 'icon' => 'award'],
        'alpa'       => ['label' => 'Alpa', 'letter' => 'A', 'icon' => 'circle-x'],
        ''           => ['label' => 'Belum hadir', 'letter' => '–', 'icon' => 'circle-dashed'],
    ];

    /**
     * Value codes per `table.column` (docs/08 §9.2).
     *
     * @var array<string, array<string, string>>
     */
    public array $codes = [
        'status_harian.masuk_sumber' => [
            'scan'    => 'Scan',
            'manual'  => 'Manual',
            'darurat' => 'Darurat',
        ],
        'scan.jenis_kiosk' => [
            'masuk'  => 'Masuk',
            'pulang' => 'Pulang',
        ],
        'presensi_manual.jenis' => [
            'masuk'  => 'Masuk',
            'pulang' => 'Pulang',
        ],
        'scan.hasil' => [
            'dipakai'       => 'Dipakai',
            'ganda'         => 'Ganda',
            'tidak_dipakai' => 'Tidak dipakai',
            'ditolak'       => 'Ditolak',
        ],
        'scan_tinjauan.keputusan' => [
            'terima' => 'Diterima',
            'tolak'  => 'Ditolak',
        ],
        'presensi_manual.alasan' => [
            'lupa_kartu'         => 'Lupa kartu',
            'kartu_rusak'        => 'Kartu rusak',
            'qr_tidak_terbaca'   => 'QR tidak terbaca',
            'kiosk_terganggu'    => 'Kiosk terganggu',
            'tiba_setelah_tutup' => 'Tiba setelah sesi masuk ditutup',
            'pulang_sakit'       => 'Pulang karena sakit',
            'pulang_izin'        => 'Pulang dengan izin',
            'darurat'            => 'Darurat',
            'lainnya'            => 'Lainnya',
        ],
        'koreksi_status.kehadiran' => [
            'hadir'       => 'Hadir',
            'terlambat'   => 'Terlambat',
            'tidak_hadir' => 'Tidak hadir',
        ],
        'izin.jenis' => [
            'izin'       => 'Izin',
            'sakit'      => 'Sakit',
            'dispensasi' => 'Dispensasi',
        ],
        'izin.status' => [
            'menunggu'   => 'Menunggu',
            'disetujui'  => 'Disetujui',
            'ditolak'    => 'Ditolak',
            'dibatalkan' => 'Dibatalkan',
        ],
        'masa_aktif.alasan_nonaktif' => [
            'lulus'           => 'Lulus',
            'pindah_sekolah'  => 'Pindah sekolah',
            'keluar'          => 'Keluar',
            'meninggal_dunia' => 'Meninggal dunia',
            'salah_input'     => 'Salah input',
            'lainnya'         => 'Lainnya',
        ],
        'akun.jenis' => [
            'staf'    => 'Staf',
            'siswa'   => 'Siswa',
            'stasiun' => 'Stasiun',
        ],
        'akun.status' => [
            'belum_aktif' => 'Belum aktif',
            'aktif'       => 'Aktif',
            'nonaktif'    => 'Nonaktif',
        ],
        'akun_role.role' => [
            'admin'      => 'Admin',
            'guru_piket' => 'Guru piket',
            'guru_bk'    => 'Guru BK',
            'pimpinan'   => 'Pimpinan',
        ],
        'semester.jenis' => [
            'ganjil' => 'Ganjil',
            'genap'  => 'Genap',
        ],
        'libur.cakupan' => [
            'semua'   => 'Semua siswa',
            'tingkat' => 'Tingkat',
            'rombel'  => 'Kelas',
        ],
        'atribut_siswa.tipe' => [
            'teks'    => 'Teks',
            'angka'   => 'Angka',
            'tanggal' => 'Tanggal',
            'pilihan' => 'Pilihan',
        ],
    ];

    /**
     * Doc terms to screen labels (docs/08 §9.1, UI-51).
     *
     * @var array<string, string>
     */
    public array $terms = [
        'rombel'                 => 'Kelas',
        'tingkat'                => 'Tingkat',
        'daftar_presensi_rombel' => 'Daftar presensi kelas',
        'rekap_rombel'           => 'Rekap per kelas',
        'rekap_semua_rombel'     => 'Rekap semua kelas',
        'presensi_per_rombel'    => 'Presensi per kelas',
        'libur_tingkat_rombel'   => 'Libur tingkat atau kelas',
        'wali_kelas'             => 'Wali kelas',
        'akun_stasiun'           => 'Akun stasiun',
        'stasiun'                => 'Stasiun',
        'penanda'                => 'Perlu diperiksa',
        'scan_tinjauan'          => 'Scan bertanda',
        'batas_mundur'           => 'Batas mundur',
    ];
}
