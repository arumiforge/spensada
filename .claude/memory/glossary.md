# Glossary — shared UI words and URL slugs

Every agent uses exactly these words. Full definitions: `docs/00` §9.
Screen labels: `docs/08` §9.1–9.2. Routes: `docs/09` §4–§13.
If `docs/` and this file disagree, `docs/` wins — fix this file.
Need a word or slug that is not here? Ask the owner, then add it here.

## Doc term → screen label

Docs and code use the **technical term**; screens, exports, PDF, and messages use the **screen label**.

| Technical term (code, docs) | Screen label (UI) | URL slug |
|---|---|---|
| `rombel` | Kelas | `kelas` |
| `tingkat` | Tingkat | `tingkat` |
| `siswa` | Siswa | `siswa` |
| `akun` staf / siswa / stasiun | Akun staf / Akun siswa / Akun stasiun | `akun-staf`, `akun-siswa`, `stasiun` |
| `wali_kelas` | Wali kelas | — |
| `penempatan` | Penempatan kelas | `penempatan` |
| `presensi_manual` | Presensi manual | `presensi-manual` |
| `koreksi_status` | Koreksi | `koreksi` |
| `izin` (jenis izin/sakit/dispensasi) | Izin, Sakit, Dispensasi | `izin` |
| `penanda` | Perlu diperiksa | — |
| `scan_tinjauan` | Scan bertanda | `scan-bertanda` |
| `batas_mundur` | Batas mundur | `batas-mundur` |
| `mode_darurat` | Mode darurat | `mode-darurat` |
| `jadwal_hari_ini` | Jadwal hari ini | `jadwal-hari-ini` |
| `pola_mingguan` | Pola mingguan | `pola-mingguan` |
| `jadwal_khusus` | Jadwal khusus | `jadwal-khusus` |
| `libur` | Libur | `libur` |
| `tahun_ajaran` | Tahun ajaran | `tahun-ajaran` |
| `atribut_siswa` | Atribut tambahan | `atribut-siswa` |
| `log_presensi` | Log perubahan presensi | `log-presensi` |
| `log_aktivitas` | Log aktivitas | `log-aktivitas` |

## Roles (`akun_role.role` → label)

`admin` Admin · `guru_piket` Guru piket · `guru_bk` Guru BK · `pimpinan` Pimpinan ·
plus Staf, Wali kelas, Siswa, Stasiun.

## Daily status (always color + icon + text, `docs/08` §4.2)

| Code | Label | Letter |
|---|---|---|
| `hadir` | Hadir | H |
| `terlambat` | Terlambat | T |
| `izin` | Izin | I |
| `sakit` | Sakit | S |
| `dispensasi` | Dispensasi | D |
| `alpa` | Alpa | A |
| (empty) | Belum hadir | – |

Request status: Menunggu · Disetujui · Ditolak · Dibatalkan.
Account status: Belum aktif · Aktif · Nonaktif.

## Common button and action words

| Action | Word | URL verb |
|---|---|---|
| Add form | Tambah | `/tambah` |
| Edit form | Ubah | `/ubah` |
| Save | Simpan | — |
| Delete | Hapus | `/hapus` |
| Cancel an action | Batal | — |
| Cancel a record | Batalkan | `/batalkan` |
| Deactivate / activate | Nonaktifkan / Aktifkan | `/nonaktifkan`, `/aktifkan` |
| Verify | Verifikasi | `/verifikasi` |
| Search | Cari | `?cari=` |
| Upload / download | Unggah / Unduh | — |
| Print | Cetak | — |
| Import / export | Import / Export | `/import` |
| Log in / log out | Login / Logout | `/login`, `/logout` |
| Change password | Ganti password | `/akun/password` |

## Area prefixes

| Area | Prefix | Home |
|---|---|---|
| Staff panel | `/panel` | `/panel` |
| Student portal (mobile-first) | `/portal` | `/portal` |
| Kiosk | `/kiosk` | `/kiosk` |
| Account | `/login`, `/logout`, `/akun/…` | — |
| Public | `/` | `/` |

## Panel menu slugs (R1)

`/panel` Dashboard hari ini · `/panel/kelas-saya` Kelas saya ·
`/panel/presensi/kelas` Daftar presensi kelas · `/panel/presensi-manual` ·
`/panel/mode-darurat/kelas` Presensi per kelas · `/panel/jadwal-hari-ini` ·
`/panel/mode-darurat` · `/panel/scan-bertanda` · `/panel/log-presensi` ·
`/panel/izin/menunggu` Pengajuan menunggu · `/panel/izin` · `/panel/izin/tambah` Input izin ·
`/panel/dispensasi-massal` · `/panel/laporan/rekap-kelas` · `/panel/laporan/riwayat-siswa` ·
`/panel/siswa` · `/panel/siswa/import` · `/panel/siswa/foto-massal` · `/panel/penempatan` ·
`/panel/akun-siswa` · `/panel/atribut-siswa` · `/panel/sekolah` Identitas sekolah ·
`/panel/tahun-ajaran` · `/panel/kelas` Kelas dan wali kelas · `/panel/pola-mingguan` ·
`/panel/jadwal-khusus` · `/panel/libur` · `/panel/batas-mundur` · `/panel/akun-staf` ·
`/panel/stasiun` Status stasiun · `/panel/log-aktivitas` · `/panel/sistem` Pemeriksaan sistem

## Portal menu slugs (R1)

`/portal` Riwayat · `/portal/izin` Izin · `/portal/akun` Akun

## URL rules (`docs/09` RT-03, RT-04)

- Lowercase Indonesian words, hyphens, singular nouns: `/panel/siswa`, not `/panel/students` or `/panel/data-siswa-siswa`.
- IDs are numeric database IDs, never NISN or names.
- Query params `snake_case` Indonesian (`tahun_ajaran`, `kelas`, `urut`, `arah`); `page` is the only English one.
