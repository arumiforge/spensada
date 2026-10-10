<?php

namespace Config;

/**
 * Staff panel menu, R1 (docs/09 §4.1, docs/08 UI-31).
 *
 * Items show only when the page route exists (pages arrive phase by phase)
 * and one of the account's roles holds one of the listed rights (docs/09
 * RT-21). `role` additionally requires that role, e.g. "Kelas saya" for
 * homeroom teachers. Empty groups are hidden. Badges and the emergency-only
 * item conditions are added with their features (FASE-05, FASE-06, FASE-07).
 *
 * Plain class, not BaseConfig, so .env cannot change it.
 */
final class Menu
{
    /**
     * @var list<array{grup: string|null, label: string, alamat: string, hak: list<string>, ikon: string, role?: string}>
     */
    public array $panel = [
        ['grup' => null, 'label' => 'Dashboard hari ini', 'alamat' => 'panel', 'hak' => ['HA-LAP-01'], 'ikon' => 'house'],
        ['grup' => null, 'label' => 'Kelas saya', 'alamat' => 'panel/kelas-saya', 'hak' => ['HA-LAP-02'], 'role' => 'wali_kelas', 'ikon' => 'clipboard-list'],

        ['grup' => 'Presensi', 'label' => 'Daftar presensi kelas', 'alamat' => 'panel/presensi/kelas', 'hak' => ['HA-LAP-02', 'HA-LAP-03'], 'ikon' => 'clipboard-list'],
        ['grup' => 'Presensi', 'label' => 'Presensi manual', 'alamat' => 'panel/presensi-manual', 'hak' => ['HA-PRS-03'], 'ikon' => 'pencil'],
        ['grup' => 'Presensi', 'label' => 'Presensi per kelas', 'alamat' => 'panel/mode-darurat/kelas', 'hak' => ['HA-PRS-03'], 'ikon' => 'pencil'],
        ['grup' => 'Presensi', 'label' => 'Jadwal hari ini', 'alamat' => 'panel/jadwal-hari-ini', 'hak' => ['HA-PRS-07'], 'ikon' => 'clock-3'],
        ['grup' => 'Presensi', 'label' => 'Mode darurat', 'alamat' => 'panel/mode-darurat', 'hak' => ['HA-PRS-08'], 'ikon' => 'siren'],
        ['grup' => 'Presensi', 'label' => 'Scan bertanda', 'alamat' => 'panel/scan-bertanda', 'hak' => ['HA-KIO-03'], 'ikon' => 'scan-line'],
        ['grup' => 'Presensi', 'label' => 'Log perubahan presensi', 'alamat' => 'panel/log-presensi', 'hak' => ['HA-PRS-06'], 'ikon' => 'file-text'],

        ['grup' => 'Izin', 'label' => 'Pengajuan menunggu', 'alamat' => 'panel/izin/menunggu', 'hak' => ['HA-IZN-03'], 'ikon' => 'file-text'],
        ['grup' => 'Izin', 'label' => 'Izin, sakit, dispensasi', 'alamat' => 'panel/izin', 'hak' => ['HA-IZN-04'], 'ikon' => 'file-text'],
        ['grup' => 'Izin', 'label' => 'Input izin', 'alamat' => 'panel/izin/tambah', 'hak' => ['HA-IZN-02'], 'ikon' => 'pencil'],
        ['grup' => 'Izin', 'label' => 'Dispensasi massal', 'alamat' => 'panel/dispensasi-massal', 'hak' => ['HA-IZN-04', 'HA-IZN-02'], 'ikon' => 'award'],

        ['grup' => 'Laporan', 'label' => 'Rekap per kelas', 'alamat' => 'panel/laporan/rekap-kelas', 'hak' => ['HA-LAP-03'], 'ikon' => 'chart-column'],
        ['grup' => 'Laporan', 'label' => 'Riwayat siswa', 'alamat' => 'panel/laporan/riwayat-siswa', 'hak' => ['HA-LAP-04'], 'ikon' => 'chart-column'],

        ['grup' => 'Siswa', 'label' => 'Data siswa', 'alamat' => 'panel/siswa', 'hak' => ['HA-MD-05'], 'ikon' => 'users'],
        ['grup' => 'Siswa', 'label' => 'Import siswa', 'alamat' => 'panel/siswa/import', 'hak' => ['HA-MD-04'], 'ikon' => 'upload'],
        ['grup' => 'Siswa', 'label' => 'Foto massal', 'alamat' => 'panel/siswa/foto-massal', 'hak' => ['HA-MD-08'], 'ikon' => 'camera'],
        ['grup' => 'Siswa', 'label' => 'Penempatan kelas', 'alamat' => 'panel/penempatan', 'hak' => ['HA-MD-03'], 'ikon' => 'users'],
        ['grup' => 'Siswa', 'label' => 'Akun siswa', 'alamat' => 'panel/akun-siswa', 'hak' => ['HA-AKN-06'], 'ikon' => 'key-round'],
        ['grup' => 'Siswa', 'label' => 'Atribut tambahan', 'alamat' => 'panel/atribut-siswa', 'hak' => ['HA-MD-11'], 'ikon' => 'settings'],

        ['grup' => 'Sekolah', 'label' => 'Identitas sekolah', 'alamat' => 'panel/sekolah', 'hak' => ['HA-MD-09'], 'ikon' => 'school'],
        ['grup' => 'Sekolah', 'label' => 'Tahun ajaran', 'alamat' => 'panel/tahun-ajaran', 'hak' => ['HA-MD-01'], 'ikon' => 'calendar-days'],
        ['grup' => 'Sekolah', 'label' => 'Kelas dan wali kelas', 'alamat' => 'panel/kelas', 'hak' => ['HA-MD-02'], 'ikon' => 'users'],
        ['grup' => 'Sekolah', 'label' => 'Pola mingguan', 'alamat' => 'panel/pola-mingguan', 'hak' => ['HA-PRS-01'], 'ikon' => 'calendar-days'],
        ['grup' => 'Sekolah', 'label' => 'Jadwal khusus', 'alamat' => 'panel/jadwal-khusus', 'hak' => ['HA-PRS-01'], 'ikon' => 'calendar-days'],
        ['grup' => 'Sekolah', 'label' => 'Libur', 'alamat' => 'panel/libur', 'hak' => ['HA-PRS-02'], 'ikon' => 'calendar-days'],
        ['grup' => 'Sekolah', 'label' => 'Batas mundur', 'alamat' => 'panel/batas-mundur', 'hak' => ['HA-PRS-09'], 'ikon' => 'settings'],

        ['grup' => 'Akun dan stasiun', 'label' => 'Akun staf', 'alamat' => 'panel/akun-staf', 'hak' => ['HA-AKN-02'], 'ikon' => 'key-round'],
        ['grup' => 'Akun dan stasiun', 'label' => 'Status stasiun', 'alamat' => 'panel/stasiun', 'hak' => ['HA-KIO-02', 'HA-AKN-03'], 'ikon' => 'scan-line'],
        ['grup' => 'Akun dan stasiun', 'label' => 'Log aktivitas', 'alamat' => 'panel/log-aktivitas', 'hak' => ['HA-AKN-08'], 'ikon' => 'file-text'],
        ['grup' => 'Akun dan stasiun', 'label' => 'Pemeriksaan sistem', 'alamat' => 'panel/sistem', 'hak' => ['HA-AKN-07'], 'ikon' => 'settings'],
    ];

    /**
     * Student portal menu, R1 (docs/09 §4.2, docs/08 UI-35): bottom menu on
     * phones and tablets, top menu from 1024 px. Same rules as $panel.
     *
     * @var list<array{label: string, alamat: string, hak: list<string>, ikon: string}>
     */
    public array $portal = [
        ['label' => 'Riwayat', 'alamat' => 'portal', 'hak' => ['HA-LAP-04'], 'ikon' => 'calendar-days'],
        ['label' => 'Izin', 'alamat' => 'portal/izin', 'hak' => ['HA-IZN-04'], 'ikon' => 'file-text'],
        ['label' => 'Akun', 'alamat' => 'portal/akun', 'hak' => ['HA-MD-05'], 'ikon' => 'user'],
    ];
}
