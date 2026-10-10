<?php

namespace Config;

/**
 * R1 rights map (docs/02 §5, §6; docs/07 ARS-15).
 *
 * Key: right ID. Value: role => scope. A role that is missing has no right.
 * Roles: admin, staf, wali_kelas, guru_piket, guru_bk, pimpinan, siswa, stasiun.
 * Scopes: semua, rombel, hari_ini, sendiri, rombelnya, angka, ya (docs/02 §5).
 *
 * Not included: HA-PRS-05 (deprecated), R2/R3 rights (HA-LAP-05, HA-LAP-06,
 * HA-WA-*, HA-INF-*, HA-KRT-*). HA-AKN-01 for stasiun is "login only": the
 * login page has no `hak` filter, and kiosk logout uses HA-KIO-01.
 *
 * Plain class, not BaseConfig, so .env cannot change rights.
 */
final class HakAkses
{
    /**
     * @var array<string, array<string, string>>
     */
    public array $hak = [
        // 6.1 Akun dan akses
        'HA-AKN-01' => ['admin' => 'ya', 'staf' => 'ya', 'wali_kelas' => 'ya', 'guru_piket' => 'ya', 'guru_bk' => 'ya', 'pimpinan' => 'ya', 'siswa' => 'ya'],
        'HA-AKN-02' => ['admin' => 'ya'],
        'HA-AKN-03' => ['admin' => 'ya'],
        'HA-AKN-04' => ['admin' => 'semua', 'wali_kelas' => 'rombel'],
        'HA-AKN-05' => ['admin' => 'semua', 'wali_kelas' => 'rombel'],
        'HA-AKN-06' => ['admin' => 'semua', 'wali_kelas' => 'rombel'],
        'HA-AKN-07' => ['admin' => 'ya'],
        'HA-AKN-08' => ['admin' => 'ya'],

        // 6.2 Master data
        'HA-MD-01' => ['admin' => 'ya'],
        'HA-MD-02' => ['admin' => 'ya'],
        'HA-MD-03' => ['admin' => 'ya'],
        'HA-MD-04' => ['admin' => 'ya'],
        'HA-MD-05' => ['admin' => 'semua', 'wali_kelas' => 'rombel', 'guru_piket' => 'semua', 'guru_bk' => 'semua', 'pimpinan' => 'semua', 'siswa' => 'sendiri'],
        'HA-MD-06' => ['admin' => 'semua', 'wali_kelas' => 'rombel'],
        'HA-MD-07' => ['admin' => 'semua', 'wali_kelas' => 'rombel'],
        'HA-MD-08' => ['admin' => 'ya'],
        'HA-MD-09' => ['admin' => 'ya'],
        'HA-MD-10' => ['admin' => 'semua', 'wali_kelas' => 'rombel', 'guru_bk' => 'semua', 'pimpinan' => 'semua'],
        'HA-MD-11' => ['admin' => 'ya'],

        // 6.3 Kiosk dan stasiun
        'HA-KIO-01' => ['stasiun' => 'ya'],
        'HA-KIO-02' => ['admin' => 'ya', 'guru_piket' => 'ya'],
        'HA-KIO-03' => ['admin' => 'semua', 'guru_piket' => 'hari_ini'],

        // 6.4 Presensi
        'HA-PRS-01' => ['admin' => 'ya'],
        'HA-PRS-02' => ['admin' => 'ya'],
        'HA-PRS-03' => ['admin' => 'semua', 'wali_kelas' => 'rombel', 'guru_piket' => 'hari_ini', 'guru_bk' => 'semua'],
        'HA-PRS-04' => ['admin' => 'semua', 'wali_kelas' => 'rombel', 'guru_piket' => 'hari_ini', 'guru_bk' => 'semua'],
        'HA-PRS-06' => ['admin' => 'semua', 'wali_kelas' => 'rombel', 'guru_piket' => 'hari_ini', 'guru_bk' => 'semua', 'pimpinan' => 'semua'],
        'HA-PRS-07' => ['admin' => 'hari_ini', 'guru_piket' => 'hari_ini'],
        'HA-PRS-08' => ['admin' => 'hari_ini', 'guru_piket' => 'hari_ini'],
        'HA-PRS-09' => ['admin' => 'ya'],

        // 6.5 Izin, sakit, dan dispensasi
        'HA-IZN-01' => ['siswa' => 'sendiri'],
        'HA-IZN-02' => ['admin' => 'semua', 'wali_kelas' => 'rombel', 'guru_piket' => 'semua', 'guru_bk' => 'semua'],
        'HA-IZN-03' => ['admin' => 'semua', 'wali_kelas' => 'rombel', 'guru_piket' => 'semua', 'guru_bk' => 'semua'],
        'HA-IZN-04' => ['admin' => 'semua', 'wali_kelas' => 'rombel', 'guru_piket' => 'semua', 'guru_bk' => 'semua', 'pimpinan' => 'semua', 'siswa' => 'sendiri'],
        'HA-IZN-05' => ['admin' => 'semua', 'wali_kelas' => 'rombel', 'guru_piket' => 'semua', 'guru_bk' => 'semua', 'pimpinan' => 'semua', 'siswa' => 'sendiri'],
        'HA-IZN-06' => ['admin' => 'semua', 'wali_kelas' => 'rombel', 'guru_piket' => 'semua', 'guru_bk' => 'semua'],

        // 6.6 Dashboard dan laporan (R1 rows)
        'HA-LAP-01' => ['admin' => 'ya', 'staf' => 'ya', 'wali_kelas' => 'ya', 'guru_piket' => 'ya', 'guru_bk' => 'ya', 'pimpinan' => 'ya'],
        'HA-LAP-02' => ['admin' => 'semua', 'wali_kelas' => 'rombel', 'guru_piket' => 'semua', 'guru_bk' => 'semua', 'pimpinan' => 'semua'],
        'HA-LAP-03' => ['admin' => 'semua', 'wali_kelas' => 'rombel', 'guru_bk' => 'semua', 'pimpinan' => 'semua'],
        'HA-LAP-04' => ['admin' => 'semua', 'wali_kelas' => 'rombel', 'guru_bk' => 'semua', 'pimpinan' => 'semua', 'siswa' => 'sendiri'],
    ];

    /**
     * Rights limited by batas mundur for every role except admin
     * (docs/05 BR-MUN-02, BR-MUN-03; docs/04 §4.2).
     *
     * @var list<string>
     */
    public array $batasMundur = ['HA-PRS-03', 'HA-PRS-04', 'HA-IZN-01', 'HA-IZN-02', 'HA-IZN-03', 'HA-IZN-06'];
}
