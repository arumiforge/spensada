# Spensada — Implementation Phases

| Item | Nilai |
|---|---|
| Versi | 0.2 (draft, menunggu review) |
| Tanggal | 2026-10-10 |
| Sumber | Discovery Session 11 (Implementation Phase Documents), dengan keputusan pemilik proyek 2026-10-10 (`14` §2.4). |
| Bergantung pada | [14-development-roadmap.md](14-development-roadmap.md): fase (`FASE-*`), urutan halaman (§7), cara kerja (`RM-*`), dan definisi selesai (RM-05). [04-feature-specification.md](04-feature-specification.md): fitur (`FS-*`) dan acceptance criteria (`AC-*`). [06-database-design.md](06-database-design.md): tabel. [07-system-architecture.md](07-system-architecture.md): aturan arsitektur (`ARS-*`), struktur folder (§5.3), panduan lokal (§15), dan langkah production (§16). [09-page-and-route-specification.md](09-page-and-route-specification.md): halaman (`HAL-*`), route, dan controller. [10-api-specification.md](10-api-specification.md): endpoint (`EP-*`). |
| Dokumen terkait | [05-business-rules.md](05-business-rules.md): contoh untuk kasus uji (§4, §6, §13). [08-ui-ux-design-system.md](08-ui-ux-design-system.md): tampilan dan aset. [11-validation-and-error-handling.md](11-validation-and-error-handling.md): pesan dan galat. [12-security.md](12-security.md): ketentuan keamanan (`SEC-*`). [13-reporting-import-export.md](13-reporting-import-export.md): import. |

Dokumen ini merinci kerja setiap fase implementasi R1 di `14` §6: langkah berurutan di dalam branch fase, file utama yang dibuat, uji yang menyertainya, dan syarat selesainya. AI implementer mengerjakan satu langkah dalam satu waktu, dan pengembang meninjau branch fase per kelompok langkah (`14` RM-12).

Isi fase, fitur, dan urutan halaman tetap ditetapkan `14`. Dokumen ini tidak menambah fitur. Bila langkah di sini berbeda dengan dokumen lain, dokumen lain itu diperbarui lebih dulu (`00` §11 butir 10, `14` RM-14).

## 1. Cara membaca dokumen ini

- **ID.** Langkah memakai `L<NN>-<MM>`, dengan `NN` nomor fase dan `MM` urutan langkah, misalnya `L05-03` untuk langkah ketiga FASE-05. ID tidak pernah dinomori ulang. Langkah yang batal ditandai `DEPRECATED`, dan langkah baru ditambahkan di akhir fasenya.
- **Langkah dan commit.** Satu langkah berisi satu atau beberapa commit kecil dengan format `.claude/memory/git-workflow.md`, misalnya `feat(prs-06): add manual attendance form`. Setiap commit meninggalkan `composer test` dan `node --test` lulus (`14` RM-11, RM-13).
- **Status.** Seluruh isi dokumen ini berstatus RECOMMENDATION, kecuali yang menyebut DECISION. Keputusan yang dipakai dokumen ini ada di §2.1.
- **File utama.** Kolom ini menyebut file atau folder yang dibuat atau diubah. Nama tabel, route, controller, filter, perintah CLI, dan service yang sudah ditetapkan dokumen lain dipakai apa adanya. Nama teknis baru yang belum ada di dokumen lain, misalnya kelas bantu dan nama berkas uji, ditulis dalam bahasa Inggris oleh implementer (`.claude/memory/language.md`), sehingga dokumen ini menyebutnya menurut perannya saja.
- **Uji.** Kolom ini menyebut acceptance criteria dan uji yang wajib ada di langkah itu. Uji akses route baru (`12` SEC-81) dan pemeriksaan atribut `hak` (`07` §5.2) selalu berlaku, sehingga tidak ditulis ulang di setiap langkah.
- **Folder.** Path mengikuti struktur `07` §5.3 dan `08` §13: controller di `app/Controllers/<Area>/`, service di `app/Services/<Modul>/`, view di `app/Views/<area>/`, migration di `app/Database/Migrations/`, uji PHP di `tests/unit/` dan `tests/database/`, kasus bersama di `tests/kasus/`, dan uji JavaScript di `tests/js/`.

## 2. Keputusan dan temuan Session 11

### 2.1 Keputusan yang dipakai

| Topik | Keputusan | Rujukan | Status |
|---|---|---|---|
| Branch dan PR | Satu branch `fase-<NN>-<slug>` dan satu PR per fase, dengan commit kecil per langkah. `main` satu-satunya branch jangka panjang. | `14` RM-06, RM-11, `.claude/memory/decisions.md` D-04 | DECISION (pemilik, 2026-10-10) |
| Server production | Windows dengan Laragon, Nginx, PHP 8.3, dan MySQL 8.4. | `07` ARS-01, §16, D-01 | DECISION (pemilik, 2026-10-10) |
| Tampilan | Bootstrap 5.3.8 dari server sendiri, `token.css`, dan `spensada.css`. Kiosk memakai CSS sendiri. | `08` §2.6, UI-73, D-02 | DECISION (pemilik, 2026-10-10) |
| Nama teknis | Nama yang sudah ditetapkan dokumen tetap dipakai apa adanya, termasuk yang berbahasa Indonesia. Nama teknis baru memakai bahasa Inggris. | `.claude/memory/language.md`, D-05 | DECISION (pemilik, 2026-10-10) |
| Lisensi proyek | MIT, dengan pemegang hak cipta Arumi Studios. | `07` §2.7, ARS-07 langkah 5 | DECISION (pemilik menyerahkan pilihan, 2026-10-10) |
| Format tanggal, jam, angka, dan uang | Semua yang tampil ke pengguna lewat `app/Helpers/format_helper.php`, dan di browser lewat satu modul JavaScript dengan hasil yang sama. | `08` UI-55, D-09 | DECISION (pemilik, 2026-10-10) |

### 2.2 Temuan yang ditetapkan tanpa ronde diskusi

Penyusunan langkah menemukan kebutuhan berikut. Semuanya RECOMMENDATION, dan perubahannya di `14` tercatat di §13.

| Temuan | Penetapan | Rujukan |
|---|---|---|
| Beberapa foreign key merujuk tabel yang baru dibuat di fase berikutnya: `pengaturan.diubah_oleh` (FASE-00) ke `akun` (FASE-01), serta `akun.siswa_id` dan `log_aktivitas.rombel_id` (FASE-01) ke `siswa` dan `rombel` (FASE-02). | Kolomnya dibuat bersama tabelnya, tanpa foreign key. Foreign key ditambahkan oleh migration yang membuat tabel rujukannya, dengan langkah `down` yang menghapusnya lagi. Semua relasi tetap memiliki foreign key setelah fase rujukannya selesai (DB-08). | §3 butir 4, L01-01, L02-01 |
| Perubahan semester (FASE-02), masa aktif dan penempatan (FASE-02 dan FASE-03), serta kalender (FASE-04) wajib menulis antrean hitung ulang, dan sebagian wajib menulis log perubahan presensi (`04` §4.4, §4.5). `14` menaruh tabel `antrean_hitung_ulang` dan `log_presensi` di FASE-05. | Kedua tabel, service `LogPresensi`, dan penulisan antrean dipindah ke FASE-04, sehingga halaman kalender langsung menulis log dan antrean. Service FASE-02 dan FASE-03 disambungkan ke keduanya di L04-02. Pemrosesan antrean (`HitungUlang`) tetap di FASE-05. Sebelum FASE-05, antrean hanya ditulis dan belum diproses, karena `status_harian` belum ada. | L04-01, L04-02, `14` FASE-04, FASE-05 |
| Filter `sesi` dan `wajib-ganti` membaca tabel `akun` yang baru ada di FASE-01, padahal `14` menaruh semua filter di FASE-00. | FASE-00 membuat filter `area`, `hak`, `csrf`, `invalidchars`, dan `keamanan`, beserta kerangka `sesi` dan `wajib-ganti`. Kedua filter itu dilengkapi di L01-02 bersama tabel `akun`. | L00-04, L01-02, `14` FASE-00 |
| Halaman awal panel (`/panel`, HAL-LAP-01) dan portal (`/portal`, HAL-LAP-07) baru dibuat di FASE-08, padahal login staf dibuka di FASE-01 dan login siswa di FASE-02 (`09` RT-18). | `/panel` dan `/portal` menampilkan halaman sementara dengan layout area dan menu, berisi kalimat "Halaman ini sedang disiapkan." Halaman sementara diganti di L08-01 dan L08-07. | L01-08, L02-09, L08-01, L08-07 |
| Akun stasiun baru dapat dibuat di FASE-06, sehingga bagian AC-AKN-01-05 (pemisahan area) yang menyangkut akun stasiun belum dapat diuji di FASE-01. | FS-AKN-01 selesai menurut RM-05 di FASE-06, seperti FS-MD-05 yang selesai di FASE-03 (`14` FASE-02). | L01-02, L06-01 |
| `.claude/memory/locale-format.md` meminta satu modul JavaScript format yang hasilnya sama persis dengan `format_helper.php`. | Modul dibuat di FASE-00, dengan kasus uji JSON bersama di `tests/kasus/` yang dijalankan PHPUnit dan `node --test`, seperti ARS-59. Kiosk memakai modul yang sama. | L00-06, `08` UI-55 |
| Prototipe kiosk FASE-01 tidak digabung ke `main` (`14` FASE-01), sementara `main` satu-satunya branch jangka panjang. | Prototipe dibuat di branch sementara `prototipe-kiosk` dari `main`. Hasilnya dicatat di laporan FASE-01 (§12), lalu branch itu dihapus tanpa digabung. | L01-09 |
| Skrip production Windows (`07` §16.3) perlu tempat di repository. | Contoh konfigurasi dan skrip PowerShell di `deploy/windows/`, dibuat di FASE-09. Komentar skrip berbahasa Inggris. | L09-04, `07` §5.3 |

## 3. Aturan umum setiap langkah

1. **Baca dulu.** Sebelum langkah dimulai, implementer membaca `CLAUDE.md`, `.claude/memory/`, spesifikasi fitur di `04`, tabel di `06`, halaman dan route di `09`, serta bagian `07`, `08`, `11`, dan `12` yang dirujuk langkah itu.
2. **Definisi selesai.** Setiap fitur memenuhi `14` RM-05: acceptance criteria-nya lulus, uji akses route barunya ada, teks layar mengikuti `08` dan `11`, `composer test` dan `node --test "tests/js/**/*.test.js"` lulus, dan PR fase ditinjau lalu digabung.
3. **Bersama di setiap langkah.** Label layar ditambahkan ke `app/Config/Label.php` (`08` UI-75), menu ke konfigurasi menu panel atau portal (`08` UI-31, `09` §4), data fiktif ke seeder `DataContoh` bila halaman membutuhkannya (ARS-18), dan hak baru tidak ditambahkan karena peta hak R1 sudah lengkap sejak FASE-00 (ARS-15).
4. **Migration.** Satu file per tabel, atau per tabel induk beserta anaknya, dengan urutan foreign key (ARS-18). Kolom turunan (DB-10) dan `CHECK` memakai SQL langsung. Foreign key ke tabel fase berikutnya mengikuti §2.2 butir pertama. Migration yang sudah digabung ke `main` tidak diubah lagi. Perubahan struktur memakai migration baru (`14` RM-12).
5. **Format dan teks.** Tanggal, jam, angka, dan uang di layar memakai `format_helper.php` atau modul format JavaScript. Teks layar berbahasa Indonesia yang ramah: "Anda" untuk staf dan "kamu" untuk siswa (`08` §8).
6. **Tanpa dependensi baru.** Library hanya yang ada di `07` ARS-10. Penambahan apa pun ditanyakan dulu ke pemilik proyek (`.claude/memory/agent-rules.md` butir 3).
7. **Agen paralel.** Bila langkah dibagi ke beberapa agen, file bersama, yaitu file route, `Label.php`, konfigurasi menu, layout, `Config\HakAkses`, dan token CSS, hanya diubah sesi utama (`.claude/memory/agent-rules.md`).
8. **Akhir fase.** Laporan fase diisi di §12 (`14` RM-15), `.claude/memory/progress.md` diperbarui, PR fase dibuka dengan judul `FASE-<NN> <nama fase>`, lalu setelah digabung `main` diberi tag `r1-fase-<NN>` (`14` RM-10).

## 4. FASE-00 — Fondasi

| Item | Nilai |
|---|---|
| Branch dan PR | `fase-00-fondasi`, "FASE-00 Fondasi" |
| Fitur | — (fondasi untuk semua fitur) |
| Perkiraan | 1–2 minggu (`14` §5) |
| Masukan sebelum mulai | Tidak ada. Lisensi proyek sudah ditetapkan: MIT (§2.1). |

| ID | Langkah | File utama | Uji |
|---|---|---|---|
| L00-01 | **Pindah ke Composer appstarter** dengan langkah ARS-07 butir 1–4, sebagai commit pertama fase ini (C-06). `composer.lock` di-commit. Butir 5: `LICENSE` diganti dengan teks lisensi MIT atas nama Arumi Studios, dan README proyek merujuk `docs/` (§2.1). | `composer.json`, `composer.lock`, `app/Config/Paths.php`, `spark`, `public/index.php`, `preload.php`, `phpunit.dist.xml`, `env`, `tests/`, `LICENSE`, `README.md`; hapus `system/` | `composer test` berjalan dengan uji bawaan dan `FormatHelperTest`. |
| L00-02 | **Konfigurasi dasar**: `appTimezone` `Asia/Jakarta` (ARS-44), driver turunan MySQLi dengan group `default` dan `tests` ke MySQL (ARS-45), `strictOn` dan `foundRows` (ARS-40, ARS-43), `Config\Spensada` (ARS-17), `Config\Session` dan `Config\Cookie` (`12` SEC-13, SEC-33), konfigurasi CSRF (`12` SEC-28), dan `Config\Routing` dengan file route per area (`09` RT-01). | `app/Config/App.php`, `Database.php`, `Session.php`, `Cookie.php`, `Security.php`, `Routing.php`, `Spensada.php`, `app/Config/Routes/`, `app/Database/MySQLi/` (enam kelas, ARS-45 butir 1) | Uji database memastikan zona waktu sesi `+07:00`, collation `utf8mb4_general_ci`, dan `sql_mode` strict. |
| L00-03 | **Service dasar**: `Jam` (ARS-44), helper kunci bernama dengan awalan nama database (ARS-42), dan pola transaksi untuk service (ARS-43). | `app/Services/Sistem/` | Uji unit `Jam` dengan `Time::setTestNow()`. Uji database kunci bernama: ambil, ambil ulang, lepas, dan nama berawalan `spensada_test:`. |
| L00-04 | **Filter dan hak akses**: `area`, `hak`, `csrf`, `invalidchars`, dan `keamanan` (ARS-13), beserta kerangka `sesi` dan `wajib-ganti` yang dilengkapi di L01-02 (§2.2). Peta `Config\HakAkses` memuat semua `HA-*` R1 dari `02` §5 dan §6 (ARS-15), dan service `HakAkses` menjawab hak dan cakupan. Jawaban JSON untuk permintaan latar belakang (ARS-13 butir 1–4, `10` API-03). CSP per area dan `Permissions-Policy` (`12` SEC-35 s.d. SEC-37, termasuk `img-src data:`). | `app/Filters/`, `app/Config/Filters.php`, `app/Config/HakAkses.php`, `app/Services/Akun/` | Uji unit `HakAkses` untuk setiap role dan cakupan di `02` §5. Uji filter: kode JSON `ditolak` dan `csrf`, header CSP per area. |
| L00-05 | **Galat, log, dan bahasa validasi**: handler pengecualian dengan halaman 400, 403, 404, 429, dan 500 (`09` RT-19, `11` §6), kode laporan dari `REQUEST_ID` (`11` GAL-13, `12` SEC-58), log aplikasi (`11` §7), halaman statis `public/galat/413.html`, `429.html`, dan `503.html` (`11` GAL-10, GAL-11, GAL-17), dan `app/Language/id/Validation.php` (`11` VAL-06). | `app/Config/Exceptions.php`, `app/Controllers/Galat.php`, `app/Views/errors/html/`, `public/galat/`, `app/Language/id/` | Uji halaman 404 dan 500 production: tanpa jejak, dengan kode laporan 8 karakter. |
| L00-06 | **Aset dan tampilan dasar**: Bootstrap 5.3.8, Plus Jakarta Sans, dan sprite Lucide disalin ke `public/aset/vendor/` dan `public/aset/ikon/` beserta lisensinya, masing-masing satu commit `build(vendor)` (ARS-10, ARS-11). `token.css` dan `spensada.css` (`08` UI-08, UI-73, UI-74). Layout panel, portal, akun, dan galat dengan head tag favicon (`08` §6, `.claude/memory/project.md`). `app/Config/Label.php` berisi label status dan kode nilai `08` §9. View komponen di `app/Views/komponen/`: chip status, pesan kilat, foto siswa dengan pengganti, kepala halaman, dan halaman daftar (`08` UI-28). Modul format JavaScript dengan kasus bersama (§2.2). Halaman `/login` tampil dengan layout, tanpa pemrosesan (diselesaikan di L01-02). | `public/aset/`, `app/Views/layout/`, `app/Views/komponen/`, `app/Config/Label.php`, `public/aset/js/`, `tests/kasus/`, `tests/js/` | Kasus format bersama lulus di PHPUnit dan `node --test`. Ukuran CSS dan JavaScript halaman login setelah gzip di bawah batas UI-71. |
| L00-07 | **Pengaturan dan perintah**: migration `pengaturan` (`06` §6.1, `diubah_oleh` tanpa foreign key), seeder `PengaturanAwal` dan kerangka `DataContoh` yang menolak berjalan di production (ARS-18), serta perintah `aplikasi:cek` (ARS-57). Butir yang bergantung pada fase berikutnya, yaitu cron, antrean, dan `status_mulai`, menampilkan "belum tersedia" sampai L05-12. | `app/Database/Migrations/`, `app/Database/Seeds/`, `app/Commands/` | Uji `PengaturanAwal` mengisi semua kunci R1. Uji `DataContoh` menolak `CI_ENVIRONMENT = production`. |
| L00-08 | **Alat uji**: PHPUnit dengan database `spensada_test` (ARS-58), folder `tests/kasus/` dan `tests/js/` (ARS-59), uji akses otomatis yang membaca daftar route (`12` SEC-81), dan uji bahwa setiap method controller panel, portal, dan kiosk memiliki atribut `hak` (`07` §5.2, `09` RT-02). | `phpunit.dist.xml`, `tests/_support/`, `tests/database/`, `tests/unit/` | Uji akses berjalan, walaupun daftar route aplikasi masih pendek. |
| L00-09 | **Panduan lokal** `07` §15 dicoba ulang di Laragon, lalu diperbaiki bila ada langkah yang berbeda. README proyek merujuk `docs/` dan panduan lokal. | `docs/07-system-architecture.md` §15, `README.md` | Uji manual: langkah §15.2 dari awal di laptop pengembang. |

**Selesai bila:** `https://spensada.test/login` tampil dengan layout `08`, `php spark aplikasi:cek` lulus di Laragon, `composer test` dan `node --test` berjalan, dan semua langkah di atas tergabung (`14` FASE-00).

## 5. FASE-01 — Akun dan akses

| Item | Nilai |
|---|---|
| Branch dan PR | `fase-01-akun-akses`, "FASE-01 Akun dan akses" |
| Fitur | FS-AKN-01 (bagian akun stasiun selesai di FASE-06), FS-AKN-02, FS-AKN-03 |
| Halaman | HAL-AKN-01, HAL-AKN-02, HAL-AKN-03, HAL-AKN-04, HAL-AKN-09, HAL-AKN-07 (`14` §7) |
| Perkiraan | 1–2 minggu |
| Masukan sebelum mulai | Contoh kartu OSIS dan satu laptop stasiun beserta webcam dan scanner USB bila ada, untuk prototipe (L01-09, `14` §4). |

| ID | Langkah | File utama | Uji |
|---|---|---|---|
| L01-01 | **Migration akun**: `akun` (`siswa_id` tanpa foreign key), `akun_role`, `log_aktivitas` (`rombel_id` tanpa foreign key), dan `percobaan_login` (`06` §5). Foreign key `pengaturan.diubah_oleh` ditambahkan (§2.2). Service pencatat log aktivitas dengan jenis di `12` SEC-59. | `app/Database/Migrations/`, `app/Models/`, `app/Services/Akun/` | Uji migration naik dan turun. |
| L01-02 | **Login dan logout** (FS-AKN-01, HAL-AKN-01, HAL-AKN-02): service `Akun\Login` dengan pembatasan percobaan per identitas dan per IP di bawah kunci bernama (`12` SEC-08 s.d. SEC-11, ARS-13), hash password (`12` SEC-02), ID sesi baru dan token CSRF baru saat login (`12` SEC-14, SEC-29). Filter `sesi` dan `wajib-ganti` dilengkapi (ARS-47): status akun, cap kredensial, batas 8 jam dan 7 hari, dan `tujuan` setelah login (`09` RT-18). Pesan di `11` §5.1. | `app/Controllers/Akun/Login.php`, `app/Views/akun/`, `app/Filters/`, `app/Services/Akun/` | AC-AKN-01-01 s.d. AC-AKN-01-08 untuk akun staf. Akun siswa diuji ulang di L02-09, dan akun stasiun di L06-01. |
| L01-03 | **Ganti password** (FS-AKN-02, HAL-AKN-03): aturan password dan daftar password umum (`12` SEC-03, SEC-04, `11` VAL-21), sesi lain berakhir dan sesi ini tetap berjalan (ARS-47 butir 2). | `app/Controllers/Akun/Password.php`, `app/Views/akun/`, dan file daftar password umum di dalam `app/` (`12` SEC-04) | AC-AKN-02-*. |
| L01-04 | **Perintah admin**: `admin:pertama` dan `admin:pulihkan` (ARS-49), dicatat di log aktivitas dengan pelaku kosong. | `app/Commands/` | Uji perintah: menolak bila sudah ada admin aktif; password acak tampil sekali; wajib ganti password. |
| L01-05 | **Akun staf** (FS-AKN-03, HAL-AKN-04): daftar, tambah, ubah, role, nonaktifkan dan aktifkan, reset password, dan buka kunci login, dengan kunci bernama `spensada:admin` agar selalu ada admin aktif (`02` §4 butir 6) dan token versi (ARS-40). | `app/Controllers/Panel/AkunStaf.php`, `app/Views/panel/akun_staf/`, `app/Services/Akun/` | AC-AKN-03-*. |
| L01-06 | **Log aktivitas** (HAL-AKN-09): daftar dengan saringan cepat dan detail (`12` SEC-62). | `app/Controllers/Panel/LogAktivitas.php`, `app/Views/panel/log_aktivitas/` | Uji saringan jenis dan rentang tanggal; hanya admin (`HA-AKN-08`). |
| L01-07 | **Pemeriksaan sistem** (HAL-AKN-07) dengan hasil `aplikasi:cek` yang dijalankan di proses web (ARS-57). Bagian cron, antrean, dan awal status ditambahkan di L05-12. | `app/Controllers/Panel/Sistem.php`, `app/Views/panel/sistem/` | Uji hanya admin (`HA-AKN-07`). |
| L01-08 | **Kerangka panel**: menu samping dan menu lipat sesuai hak (`08` UI-29, UI-31, `09` §4.1, RT-21), bilah atas, dan halaman sementara `/panel` (§2.2). | `app/Views/layout/`, konfigurasi menu, `app/Controllers/Panel/Dashboard.php` | Uji menu: setiap role hanya melihat halaman yang boleh dibuka. |
| L01-09 | **Prototipe kiosk** (`14` FASE-01), di branch sementara `prototipe-kiosk`: halaman yang membuka webcam dan membaca QR dengan zxing-wasm di Web Worker (ARS-25), dicoba di laptop sekolah. Dicatat: resolusi kamera, waktu baca per kartu, kartu yang sulit terbaca, dan scanner USB (ARS-26). | Branch sementara, tidak digabung | Uji manual tercatat di §12 (laporan FASE-01). |

**Selesai bila:** admin pertama dibuat lewat CLI, login dan pembatasan login berjalan, akun staf dan role dapat dikelola, dan hasil prototipe tercatat. Bila waktu baca di laptop sekolah jauh di atas 1 detik (NFR-01), alternatifnya dibahas dengan pemilik proyek sebelum FASE-06 (`14` FASE-01).

## 6. FASE-02 — Sekolah dan siswa

| Item | Nilai |
|---|---|
| Branch dan PR | `fase-02-sekolah-siswa`, "FASE-02 Sekolah dan siswa" |
| Fitur | FS-MD-01, FS-MD-02, FS-MD-03, FS-MD-04, FS-MD-05 (tanpa import penempatan), FS-MD-09, FS-AKN-05 |
| Halaman | HAL-MD-01, 02, 03, 16, 04, 05, 06, 07, 08, 10, 11, 12, 17, HAL-AKN-05, 06, 08 (`14` §7) |
| Perkiraan | 2–3 minggu |

| ID | Langkah | File utama | Uji |
|---|---|---|---|
| L02-01 | **Migration master data**: `tahun_ajaran`, `semester`, `rombel`, `siswa`, `masa_aktif`, `penempatan`, `atribut_siswa`, `nilai_atribut_siswa` (`06` §6.2 s.d. §6.9), dan `log_data_siswa` (`06` §12.2). Foreign key `akun.siswa_id` dan `log_aktivitas.rombel_id` ditambahkan (§2.2). Kolom turunan dan kunci unik DB-10. | `app/Database/Migrations/`, `app/Models/` | Uji migration naik dan turun; uji kunci unik satu tahun ajaran aktif dan satu periode aktif terbuka. |
| L02-02 | **Berkas dan identitas sekolah** (FS-MD-01, HAL-MD-01): penyajian berkas lewat controller dengan header `12` SEC-52 (ARS-52), pemrosesan logo (ARS-53, `12` SEC-51), dan route `/logo`. | `app/Services/Berkas/`, `app/Controllers/Panel/Sekolah.php`, `app/Controllers/Publik/Logo.php` | AC-MD-01-*. Uji file berbahaya: PHP berganti nama `.png`, SVG (`12` SEC-81). |
| L02-03 | **Tahun ajaran dan semester** (FS-MD-02, HAL-MD-02) dengan kunci `spensada:tahun_ajaran` (ARS-42). Perubahan tanggal semester disambungkan ke antrean dan log di L04-02. | `app/Controllers/Panel/TahunAjaran.php`, `app/Services/MasterData/` | AC-MD-02-*. |
| L02-04 | **Rombel dan wali kelas** (FS-MD-03, HAL-MD-03). Role wali kelas diturunkan dari `rombel.wali_kelas_id` di filter `sesi` (ARS-15). | `app/Controllers/Panel/Rombel.php` | AC-MD-03-*. Uji cakupan Rombel di `HakAkses`. |
| L02-05 | **Atribut tambahan** (FS-MD-09, HAL-MD-16). | `app/Controllers/Panel/AtributSiswa.php` | AC-MD-09-*. |
| L02-06 | **Data siswa** (FS-MD-04): daftar dan cari (HAL-MD-04, EP-MD-01), tambah (HAL-MD-05) yang langsung membuat akun siswa `belum_aktif` (FS-AKN-05), profil (HAL-MD-06), ubah (HAL-MD-07), nomor WA (HAL-MD-08), nonaktifkan dan aktifkan (HAL-MD-10) lewat `masa_aktif`, dan log data siswa. Foto tampil dengan pengganti (`08` UI-24) sampai unggah foto ada di FASE-03. | `app/Controllers/Panel/Siswa.php`, `SiswaWa.php`, `app/Services/MasterData/`, `public/aset/js/` (cari siswa) | AC-MD-04-*. Uji EP-MD-01 (`10` §6). |
| L02-07 | **Penempatan** (FS-MD-05 tanpa butir 6): pindah kelas (HAL-MD-11) dan penempatan per kelas (HAL-MD-12), dengan kunci baris `siswa` (`06` §16). | `app/Controllers/Panel/Penempatan.php` | AC-MD-05-* kecuali import penempatan. |
| L02-08 | **Log data siswa** (HAL-MD-17). | `app/Controllers/Panel/LogDataSiswa.php` | Uji `HA-MD-10`. |
| L02-09 | **Akun siswa** (FS-AKN-05): daftar dan slip akun dengan cetak browser (HAL-AKN-05, `08` UI-57, UI-58), reset password dan buka kunci (HAL-AKN-06), layout portal dengan menu bawah (`08` UI-35), halaman sementara `/portal` (§2.2), dan akun di portal (HAL-AKN-08, `/portal/foto`). | `app/Controllers/Panel/AkunSiswa.php`, `app/Controllers/Portal/Akun.php`, `app/Views/portal/` | AC-AKN-05-*. AC-AKN-01-* dan AC-AKN-02-* diulang untuk akun siswa. |

**Selesai bila:** admin dapat menyiapkan satu tahun ajaran lengkap dengan rombel, wali kelas, dan siswa, lalu mencetak slip akun. Siswa dapat login ke portal dan mengganti password (`14` FASE-02).

## 7. FASE-03 — Import dan foto

| Item | Nilai |
|---|---|
| Branch dan PR | `fase-03-import-foto`, "FASE-03 Import dan foto" |
| Fitur | FS-MD-06, FS-MD-07, FS-MD-08, dan import penempatan (FS-MD-05 butir 6, `13` IM-02) |
| Halaman | HAL-MD-14, HAL-MD-13, HAL-MD-09, HAL-MD-15 (`14` §7) |
| Perkiraan | 1–2 minggu |

| ID | Langkah | File utama | Uji |
|---|---|---|---|
| L03-01 | **PhpSpreadsheet dan file sementara**: `composer require phpoffice/phpspreadsheet` sesuai ARS-10, sebagai commit `build(composer)`, lalu service file sementara dengan token terikat akun (ARS-55). | `composer.json`, `composer.lock`, `app/Services/Berkas/` | Uji token: akun lain ditolak. |
| L03-02 | **Import siswa** (FS-MD-06, HAL-MD-14): template (`13` IM-01), pratinjau, konfirmasi, baris gagal sebagai CSV, dan log `import` (`12` SEC-59). Aturan file `13` §6.2 dan IM-16, pesan `11` §5.7, dan pencegahan rumus (`12` SEC-46). | `app/Controllers/Panel/ImportSiswa.php`, `app/Services/MasterData/` | AC-MD-06-*. Uji file dengan baris salah dan NISN sebagai angka. |
| L03-03 | **Import penempatan** (HAL-MD-13, `13` IM-02), sehingga FS-MD-05 lengkap. | `app/Controllers/Panel/ImportPenempatan.php` | AC-MD-05-* yang menyangkut import. |
| L03-04 | **Foto satu per satu** (FS-MD-07, HAL-MD-09): pemrosesan tiga ukuran dengan urutan ARS-53, termasuk simpan dan muat ulang setelah `reorient()`. | `app/Controllers/Panel/SiswaFoto.php`, `app/Services/Berkas/` | AC-MD-07-*. Uji foto potret dengan orientasi EXIF 6 dan PNG transparan. |
| L03-05 | **Foto massal** (FS-MD-08, HAL-MD-15, EP-MD-02): ZIP dan beberapa file, pemeriksaan ARS-54, dan pemrosesan bertahap 20 foto per permintaan. | `app/Controllers/Panel/FotoMassal.php`, `public/aset/js/` (kemajuan) | AC-MD-08-*. Uji ZIP dengan `..`, symlink, dan ZIP bom (`12` SEC-47, SEC-81). |
| L03-06 | **Uji volume**: 1.000 siswa fiktif dan foto massalnya di Laragon dalam batas `12` SEC-49. File fiktif dibuat oleh pembantu uji, bukan di-commit. | `tests/` | Uji manual tercatat di §12 (laporan FASE-03). |

**Selesai bila:** import 1.000 siswa fiktif dan foto massalnya berhasil di Laragon dalam batas `12` SEC-49, dengan laporan baris gagal sesuai `11` §5.7 dan §5.8 (`14` FASE-03).

## 8. FASE-04 — Kalender dan aturan jam

| Item | Nilai |
|---|---|
| Branch dan PR | `fase-04-kalender`, "FASE-04 Kalender dan aturan jam" |
| Fitur | FS-PRS-01, FS-PRS-02, FS-PRS-03, FS-PRS-10 |
| Halaman | HAL-PRS-01, HAL-PRS-02, HAL-PRS-03, HAL-PRS-09 (`14` §7) |
| Perkiraan | 1–2 minggu |

| ID | Langkah | File utama | Uji |
|---|---|---|---|
| L04-01 | **Migration**: tabel kalender `pola_mingguan`, `pola_mingguan_hari`, `jadwal_khusus`, `jadwal_hari_ini`, `libur`, dan `libur_cakupan` (`06` §7), serta `antrean_hitung_ulang` (`06` §11.5) dan `log_presensi` (`06` §12.1) (§2.2). | `app/Database/Migrations/`, `app/Models/` | Uji migration naik dan turun. |
| L04-02 | **Log dan antrean**: service `LogPresensi` (ARS-16, `04` §4.4) dan penulisan antrean dalam transaksi yang sama (ARS-35). Service FASE-02 dan FASE-03 disambungkan: tanggal semester (log dan antrean), masa aktif dan penempatan termasuk import (antrean). | `app/Services/Presensi/`, service di `app/Services/MasterData/` | Uji database: setiap pemicu `06` §11.4 yang sudah ada menulis antrean dengan siswa dan tanggal sesuai ARS-35, dalam transaksi yang sama. |
| L04-03 | **`AturanJam` dan `Kalender`** (ARS-16): aturan jam tanggal mana pun, hari sekolah per siswa (BR-KAL-05), dan jendela scan (BR-JAM-03 s.d. BR-JAM-05). Kasus uji JSON bersama dari `05` §4, §6, dan §13 (ARS-59), yang juga dipakai `aturan.js` di FASE-06. | `app/Services/Kalender/`, `tests/kasus/`, `tests/unit/` | Kasus bersama lulus di PHPUnit. |
| L04-04 | **Pola mingguan** (FS-PRS-01, HAL-PRS-01) dengan kunci `spensada:kalender`, log, dan antrean. | `app/Controllers/Panel/PolaMingguan.php` | AC-PRS-01-*. |
| L04-05 | **Jadwal khusus** (FS-PRS-02, HAL-PRS-02), termasuk pemeriksaan tumpang tindih. | `app/Controllers/Panel/JadwalKhusus.php` | AC-PRS-02-*. |
| L04-06 | **Libur** (FS-PRS-03, HAL-PRS-03) dengan cakupan tingkat dan rombel. | `app/Controllers/Panel/Libur.php` | AC-PRS-03-*. |
| L04-07 | **Batas mundur** (FS-PRS-10, HAL-PRS-09) dan pemeriksaannya di `HakAkses` (`04` §4.2, ARS-15), termasuk teks "Dapat diubah: …" (`08` UI-28). | `app/Controllers/Panel/BatasMundur.php`, `app/Services/Akun/` | AC-PRS-10-*. Uji batas mundur untuk staf dan admin. |

**Selesai bila:** aturan jam tanggal mana pun dan hari sekolah setiap siswa dapat dihitung, kasus uji bersama lulus di PHPUnit (`14` FASE-04), dan semua perubahan kalender, semester, masa aktif, dan penempatan menulis antrean, serta log sesuai `04` §4.4.

## 9. FASE-05 — Mesin status dan presensi staf

| Item | Nilai |
|---|---|
| Branch dan PR | `fase-05-status-presensi`, "FASE-05 Mesin status dan presensi staf" |
| Fitur | FS-PRS-05, FS-PRS-04, FS-PRS-06, FS-PRS-07, FS-PRS-08, FS-PRS-09, FS-PRS-11 |
| Halaman | HAL-PRS-07, HAL-PRS-08, HAL-PRS-04, HAL-PRS-05, HAL-PRS-06, HAL-PRS-10 (`14` §7) |
| Perkiraan | 2–3 minggu |

FS-PRS-05 dibagi menjadi L05-02 s.d. L05-05 (`14` RM-13).

| ID | Langkah | File utama | Uji |
|---|---|---|---|
| L05-01 | **Migration**: `status_harian`, `presensi_manual`, `koreksi_status`, `mode_darurat`, `scan`, `scan_tinjauan`, `izin_kelompok`, `izin`, `izin_riwayat`, dan `lampiran` (`06` §8.2, §8.3, §9, §10, §11.2), dengan urutan foreign key dan kolom turunan DB-10. | `app/Database/Migrations/`, `app/Models/` | Uji migration naik dan turun; uji kunci unik presensi manual aktif, koreksi aktif, dan mode darurat aktif. |
| L05-02 | **`PenentuStatus`** sebagai fungsi murni (ARS-34 butir 1): status, kejadian pulang, penanda, dan kolom hasil scan. Kasus uji JSON bersama dari `05` §6 dan §13 serta AC-PRS-05-*. | `app/Services/Presensi/`, `tests/kasus/` | Kasus status lulus, termasuk izin, koreksi, presensi manual, mode darurat, dan scan bertanda. |
| L05-03 | **`HitungUlang`, pemrosesan antrean, dan `PembacaStatus`** (ARS-34 s.d. ARS-38): kunci `spensada:status`, putaran berurutan, batas waktu per pemroses, coba ulang bertingkat, pembuatan baris per tanggal, batas bawah `status_mulai` (`06` §11.4), mode darurat berakhir otomatis, dan tanda "sedang diperbarui". Tanggal sebelum `status_mulai` menolak input (`04` §4.10). | `app/Services/Presensi/` | Uji database untuk setiap pemicu `06` §11.4. Uji antrean yang ID-nya lebih kecil tetapi commit belakangan (ARS-36 butir 2). |
| L05-04 | **Perintah CLI**: `status:bangun` termasuk `--periksa` (ARS-39), `status:antrean`, `status:mulai` (`14` GL-08), `tugas:menit`, dan `tugas:harian` beserta pembersihan `12` SEC-67 dan `cron_terakhir_at` (ARS-56, ARS-57). | `app/Commands/` | Uji `status:mulai` menolak bila `status_harian` berisi baris. Uji `tugas:harian` menghapus hanya data teknis. |
| L05-05 | **Data contoh**: scan dan izin fiktif lewat `DataContoh`, sampai kiosk dan fitur izin ada (`14` FASE-05). | `app/Database/Seeds/` | `status:bangun --periksa` tidak menemukan perbedaan pada data contoh. |
| L05-06 | **Presensi manual** (FS-PRS-06, HAL-PRS-07, EP-PRS-01): foto 240×320 px dan pratinjau status (`08` UI-34), pembatalan dengan alasan, log, dan antrean. | `app/Controllers/Panel/PresensiManual.php`, `public/aset/js/` (pratinjau) | AC-PRS-06-*. |
| L05-07 | **Koreksi status** (FS-PRS-07, HAL-PRS-08) dengan penjaga keadaan ARS-41. | `app/Controllers/Panel/Koreksi.php` | AC-PRS-07-*. |
| L05-08 | **Jadwal hari ini** (FS-PRS-04, HAL-PRS-04). | `app/Controllers/Panel/JadwalHariIni.php` | AC-PRS-04-*. |
| L05-09 | **Mode darurat** (FS-PRS-08, HAL-PRS-05) dan bilah merah di semua halaman panel (`08` UI-30). | `app/Controllers/Panel/ModeDarurat.php`, `app/Views/layout/` | AC-PRS-08-*. |
| L05-10 | **Presensi per kelas saat darurat** (FS-PRS-09, HAL-PRS-06). | `app/Controllers/Panel/PresensiDarurat.php` | AC-PRS-09-*. |
| L05-11 | **Log perubahan presensi** (FS-PRS-11, HAL-PRS-10). Export CSV (LP-07) adalah R2. | `app/Controllers/Panel/LogPresensi.php` | AC-PRS-11-*. |
| L05-12 | **Pemeriksaan sistem lengkap**: waktu terakhir cron, antrean menunggu dan gagal, dan `status_mulai` (HAL-AKN-07, `09` §5). `aplikasi:cek` memeriksa butir yang sama. | `app/Controllers/Panel/Sistem.php`, `app/Commands/` | Uji tampilan antrean gagal. |

**Selesai bila:** kasus uji penentuan status di `tests/kasus/` lulus, termasuk kasus izin dengan data dari `DataContoh`; AC-PRS-05-* yang tidak memerlukan halaman izin atau daftar presensi lulus, dan sisanya diuji ulang di akhir FASE-07 dan FASE-08; hitung ulang berjalan untuk semua pemicu di `06` §11.4; dan `status:bangun --periksa` tidak menemukan perbedaan pada data contoh (`14` FASE-05).

## 10. FASE-06 — Kiosk dan sinkron

| Item | Nilai |
|---|---|
| Branch dan PR | `fase-06-kiosk`, "FASE-06 Kiosk dan sinkron" |
| Fitur | FS-AKN-04, FS-KIO-01 s.d. FS-KIO-06, dan bagian akun stasiun di FS-AKN-01 |
| Halaman | HAL-KIO-02 (akun stasiun dan PIN petugas lebih dulu), HAL-KIO-01, HAL-KIO-03 (`14` §7) |
| Perkiraan | 3–4 minggu |

FS-KIO-02 dibagi menjadi L06-05 dan L06-06 (`14` RM-13). Hasil prototipe FASE-01 (§12) dibaca sebelum L06-05.

| ID | Langkah | File utama | Uji |
|---|---|---|---|
| L06-01 | **Akun stasiun dan PIN petugas** (FS-AKN-04, HAL-KIO-02): migration `status_stasiun` (`06` §8.1), kelola akun stasiun, ganti kredensial, buka kunci, dan PIN petugas dengan hash PBKDF2 (`12` SEC-21). Login stasiun 90 hari dengan cookie `__Secure-spensada_stasiun` dan satu login aktif (ARS-30, ARS-31, `12` SEC-18, SEC-19) di filter `sesi`. | `app/Controllers/Panel/Stasiun.php`, `PinKiosk.php`, `app/Filters/`, `app/Services/Kiosk/` | AC-AKN-04-*. AC-AKN-01-05 untuk akun stasiun. |
| L06-02 | **API data kiosk** (FS-KIO-01, EP-KIO-01, EP-KIO-02): isi data kiosk dan versinya (ARS-23), cache 60 detik, foto kiosk, jam server di setiap jawaban (ARS-27), dan pembatasan laju (`12` SEC-54). | `app/Controllers/Kiosk/Api/V1/`, `app/Services/Kiosk/` | Uji EP-KIO-01 dan EP-KIO-02 sesuai `10` §5. |
| L06-03 | **Kerangka kiosk** (HAL-KIO-01): halaman `/kiosk`, manifest, Service Worker `public/sw-kiosk.js` (ARS-22), `db.js` untuk IndexedDB (ARS-21), dan `data.js` untuk memuat data dan foto. CSS kiosk dengan token (`08` UI-73). | `app/Controllers/Kiosk/Halaman.php`, `public/sw-kiosk.js`, `public/aset/kiosk/` | AC-KIO-01-*. Uji manual offline (`07` §15.3). |
| L06-04 | **`aturan.js` dan `jam.js`**: aturan dan jam terkoreksi (ARS-27, ARS-28), dijalankan pada kasus bersama dari L04-03 dan L05-02 dengan `node --test`. | `public/aset/kiosk/`, `tests/js/` | Kasus bersama lulus di `node --test`. |
| L06-05 | **Scan dan umpan balik, bagian input**: `scan.js` dengan kamera dan Web Worker `public/kiosk-pemindai.js` (ARS-25), scanner USB (ARS-26), dan penyimpanan scan dengan `durability: 'strict'` sebelum hasil tampil (ARS-21 butir 7). | `public/aset/kiosk/`, `public/kiosk-pemindai.js` | AC-KIO-02-* yang menyangkut kamera, scanner, dan penyimpanan. |
| L06-06 | **Scan dan umpan balik, bagian layar**: `app.js` dengan layar hasil, bunyi Web Audio, lama tampil, bilah status, dan teks kiosk (`08` §7). | `public/aset/kiosk/` | AC-KIO-02-* lainnya. |
| L06-07 | **Penerimaan sinkron** (FS-KIO-04, EP-KIO-03): `PenilaiScan`, penyimpanan dengan `ON DUPLICATE KEY UPDATE`, status stasiun, antrean, scan ditolak di log aktivitas (`12` SEC-24), dan penandaan selisih jam (ARS-27 butir 6). | `app/Controllers/Kiosk/Api/V1/`, `app/Services/Kiosk/` | AC-KIO-04-*. Uji kiriman ulang dengan UUID yang sama dan scan dari akun lain (`akun_berbeda`). |
| L06-08 | **Sinkron dari kiosk** (FS-KIO-03): `sinkron.js` dengan kontak berkala, coba ulang bertingkat, token CSRF lewat header (ARS-29), menu petugas dengan PIN, logout kiosk (EP-KIO-04), dan hapus data lokal. | `public/aset/kiosk/`, `app/Controllers/Kiosk/Api/V1/` | AC-KIO-03-*. |
| L06-09 | **Status stasiun** (FS-KIO-05, HAL-KIO-02, EP-KIO-05): daftar, fragmen 30 detik dengan `polling.js` (ARS-50), dan sorotan stasiun. | `app/Controllers/Panel/Stasiun.php`, `public/aset/js/polling.js` | AC-KIO-05-*. |
| L06-10 | **Tinjauan scan bertanda** (FS-KIO-06, HAL-KIO-03) dengan penjaga kunci unik, log, dan antrean. | `app/Controllers/Panel/ScanBertanda.php` | AC-KIO-06-*. |
| L06-11 | **Uji manual kiosk** di laptop sekolah: `07` §14 dan §15.3, serta AC-01 dan AC-02 di `01` §7. | — | Uji manual tercatat di §12 (laporan FASE-06). |

**Selesai bila:** AC-01 dan AC-02 di `01` §7 lulus di laptop sekolah, uji manual kiosk di `07` §14 dan §15.3 tercatat, dan kasus uji bersama lulus di PHPUnit dan `node --test` (`14` FASE-06).

## 11. FASE-07, FASE-08, dan FASE-09

### 11.1 FASE-07 — Izin, sakit, dan dispensasi

| Item | Nilai |
|---|---|
| Branch dan PR | `fase-07-izin`, "FASE-07 Izin, sakit, dan dispensasi" |
| Fitur | FS-IZN-01 s.d. FS-IZN-06 |
| Halaman | HAL-IZN-08, 05, 06, 01, 02, 03, 04, 07, 09 (`14` §7) |
| Perkiraan | 2 minggu |

| ID | Langkah | File utama | Uji |
|---|---|---|---|
| L07-01 | **Service izin**: buat, setujui, tolak, batalkan, dan persingkat dengan penjaga keadaan (ARS-41), `izin_riwayat`, log perubahan presensi, dan antrean. Lampiran: penyimpanan, penyimpanan ulang gambar, dan penyajian dengan catatan `lampiran_dibuka` (ARS-51 s.d. ARS-53, `12` SEC-49 s.d. SEC-52, SEC-60). | `app/Services/Izin/`, `app/Controllers/Panel/Lampiran.php`, `app/Controllers/Portal/Lampiran.php` | Uji database setiap perubahan keputusan dan pengaruhnya ke antrean. Uji PDF palsu dan log `lampiran_dibuka`. |
| L07-02 | **Input izin oleh staf** (FS-IZN-02, HAL-IZN-08). | `app/Controllers/Panel/Izin.php` | AC-IZN-02-*. |
| L07-03 | **Daftar izin dan detail dengan verifikasi** (FS-IZN-06, FS-IZN-04, HAL-IZN-05, HAL-IZN-06). | `app/Controllers/Panel/Izin.php` | AC-IZN-04-*, AC-IZN-06-*. |
| L07-04 | **Izin di portal** (FS-IZN-01, HAL-IZN-01 s.d. HAL-IZN-03, EP-IZN-01). | `app/Controllers/Portal/Izin.php`, `app/Views/portal/izin/` | AC-IZN-01-*. |
| L07-05 | **Pengajuan menunggu** (HAL-IZN-04) dengan jumlah di lencana menu (`09` RT-21). | `app/Controllers/Panel/Izin.php` | Uji jumlah lencana dihitung tanpa cache. |
| L07-06 | **Ubah keputusan** (FS-IZN-05, HAL-IZN-07). | `app/Controllers/Panel/Izin.php` | AC-IZN-05-*. |
| L07-07 | **Dispensasi massal** (FS-IZN-03, HAL-IZN-09) dengan konfirmasi bersyarat lewat file sementara (ARS-55). | `app/Controllers/Panel/DispensasiMassal.php` | AC-IZN-03-*. |
| L07-08 | **Uji ulang** AC-PRS-05-* yang memerlukan izin, dan AC-03 di `01` §7. | `tests/` | Semua lulus. |

**Selesai bila:** AC-03 di `01` §7 dan AC-PRS-05-* yang memerlukan izin lulus, dan izin yang disetujui atau diubah keputusannya langsung tercermin di status harian (`14` FASE-07).

### 11.2 FASE-08 — Dashboard dan laporan

| Item | Nilai |
|---|---|
| Branch dan PR | `fase-08-dashboard-laporan`, "FASE-08 Dashboard dan laporan" |
| Fitur | FS-LAP-01 s.d. FS-LAP-04 |
| Halaman | HAL-LAP-01 s.d. HAL-LAP-07 (`14` §7) |
| Perkiraan | 1–2 minggu |

| ID | Langkah | File utama | Uji |
|---|---|---|---|
| L08-01 | **Dashboard hari ini** (FS-LAP-01, HAL-LAP-01, EP-LAP-01): ubin, tabel per kelas, peringatan sesuai hak termasuk cron dan antrean gagal bagi admin, dan polling 30 detik. Halaman sementara `/panel` diganti. | `app/Controllers/Panel/Dashboard.php`, `app/Views/panel/dashboard/`, `app/Services/Laporan/` | AC-LAP-01-*. |
| L08-02 | **Siswa per status** (HAL-LAP-02). | `app/Controllers/Panel/Dashboard.php` | Uji `HA-LAP-02` dan cakupan. |
| L08-03 | **Kelas saya** (HAL-LAP-03) bagi wali kelas. | `app/Controllers/Panel/PresensiRombel.php` | Uji menu "Kelas saya" hanya bagi wali kelas. |
| L08-04 | **Daftar presensi kelas** (FS-LAP-02, HAL-LAP-04), dengan tombol presensi manual dan koreksi yang memakai service FASE-05 (`14` §7). | `app/Controllers/Panel/PresensiRombel.php` | AC-LAP-02-*. |
| L08-05 | **Rekap per kelas** (FS-LAP-03, HAL-LAP-05) dengan `PembacaStatus` (`13` IE-01). Export adalah R2. | `app/Controllers/Panel/RekapRombel.php`, `app/Services/Laporan/` | AC-LAP-03-*. |
| L08-06 | **Riwayat siswa** (FS-LAP-04, HAL-LAP-06). | `app/Controllers/Panel/RiwayatSiswa.php` | AC-LAP-04-* untuk staf. |
| L08-07 | **Riwayat di portal** (FS-LAP-04, HAL-LAP-07) dengan kalender bulanan (`08` UI-36). Halaman sementara `/portal` diganti. | `app/Controllers/Portal/Riwayat.php` | AC-LAP-04-* untuk siswa. |
| L08-08 | **Uji ulang dan peragaan**: semua AC-PRS-05-*, serta kriteria keberhasilan v1 butir 1 s.d. 5 (`01` §6) dengan data contoh di Laragon. | `tests/` | Peragaan tercatat di §12 (laporan FASE-08). |

**Selesai bila:** semua fitur R1 di `04` §3 memenuhi RM-05, semua AC-PRS-05-* lulus, dan kriteria keberhasilan v1 butir 1 s.d. 5 (`01` §6) dapat diperagakan di Laragon dengan data contoh (`14` FASE-08).

### 11.3 FASE-09 — Kesiapan production

| Item | Nilai |
|---|---|
| Branch dan PR | `fase-09-production`, "FASE-09 Kesiapan production" |
| Perkiraan | 1–2 minggu |
| Masukan sebelum mulai | Server Windows dan nama domain, serta jawaban OQ-20: lokasi server, klien ACME, dan cara menjalankan layanan (`07` §16.4, `14` §4). |

| ID | Langkah | File utama | Uji |
|---|---|---|---|
| L09-01 | **Uji keamanan menyeluruh** `12` SEC-81 untuk semua route di `php spark routes`, dan tinjauan diff keamanan seluruh R1. | `tests/` | Semua route memiliki uji akses. |
| L09-02 | **Uji beban lokal** `14` §9.2 dengan skrip PHP CLI tanpa library baru. Hasilnya menetapkan sementara jumlah proses php-cgi, batas waktu antrean, dan batas laju (ARS-04, ARS-36, `12` SEC-54), dan nilainya ditulis di dokumen asalnya. | Skrip uji beban di `tests/`, dokumen asal nilai | Hasil tercatat di §12 (laporan FASE-09). |
| L09-03 | **Panduan pengguna** singkat untuk admin, wali kelas, guru piket, petugas stasiun, dan siswa, berdasarkan alur `03` (`14` §10), dalam bahasa Indonesia. | `docs/panduan/` | Ditinjau pengembang. |
| L09-04 | **Skrip production Windows**: contoh `php-web.ini` dan `spensada.conf`, serta skrip PowerShell `web.ps1` dan `backup.ps1` (`07` §16.1, §16.3). | `deploy/windows/` | Dicoba di Windows: restart tanpa login, proses php-cgi yang berhenti dijalankan ulang, dan backup terenkripsi. |
| L09-05 | **Penyiapan server** sesuai `07` §16.1, termasuk tugas terjadwal dan backup terenkripsi (ARS-06, `12` SEC-75), lalu tag rilis kandidat `r1.0.0-rc.<n>` (`14` RM-10). | — | `aplikasi:cek` lulus di server. |

**Selesai bila:** production terpasang dengan `CI_ENVIRONMENT = production`, `aplikasi:cek` lulus di server, dan belum ada data asli di dalamnya (`14` FASE-09, RM-09).

## 12. Laporan fase

Diisi pengembang di akhir setiap fase (`14` RM-15): fitur yang selesai, perkiraan yang meleset, masalah yang ditemukan, dan uji manual yang tercatat. Perkiraan fase berikutnya di `14` §5 disesuaikan bila perlu.

| Fase | Selesai | Perkiraan dan realisasi | Masalah dan catatan | Uji manual tercatat |
|---|---|---|---|---|
| FASE-00 | — | — | — | Panduan lokal (L00-09) |
| FASE-01 | — | — | — | Prototipe kiosk (L01-09) |
| FASE-02 | — | — | — | — |
| FASE-03 | — | — | — | Uji volume (L03-06) |
| FASE-04 | — | — | — | — |
| FASE-05 | — | — | — | — |
| FASE-06 | — | — | — | Uji manual kiosk (L06-11) |
| FASE-07 | — | — | — | — |
| FASE-08 | — | — | — | Peragaan (L08-08) |
| FASE-09 | — | — | — | Uji beban (L09-02), skrip Windows (L09-04) |

## 13. Perubahan pada dokumen lain

Perubahan karena Session 11:

| Dokumen | Versi | Perubahan |
|---|---|---|
| `00-project-overview.md` | 0.11 | Peta dokumen memuat `15`, progres sesi menandai Session 11 selesai, kondisi repository diperbarui, dan glosarium ditambah langkah implementasi. |
| `14-development-roadmap.md` | 0.2 | FASE-00 butir 4 (kerangka `sesi` dan `wajib-ganti`), FASE-04 (tabel `antrean_hitung_ulang` dan `log_presensi`, `LogPresensi`, dan penulisan antrean), dan FASE-05 (tanpa kedua tabel itu) diperbarui (§2.2). |

Keputusan pemilik proyek 2026-10-10 yang dipakai di sini (§2.1) sudah dicatat di dokumen asalnya: `07` §2.7 dan §16, `08` §2.6, `12` §16, dan `14` §2.4.

## 14. Pertanyaan terbuka dan nilai yang dipastikan nanti

Session 11 tidak menjawab OQ dan tidak menambah OQ. OQ-20 ditambahkan oleh keputusan pemilik proyek 2026-10-10 (`07` §16.4).

| Hal | Rujukan | Dipastikan di |
|---|---|---|
| Lokasi server production, klien ACME, dan cara menjalankan layanan (OQ-20) | L09-04, L09-05, `07` §16.4 | Pemilik proyek, awal FASE-09 |
| Hasil prototipe kiosk dan alternatif bila pembacaan QR lambat | L01-09 | Akhir FASE-01 |
| Perkiraan waktu setiap fase | `14` §5 | Laporan fase (§12) |

## Riwayat perubahan

| Versi | Tanggal | Perubahan |
|---|---|---|
| 0.1 | 2026-10-10 | Draft awal dari Session 11: keputusan yang dipakai dan temuan (§2), aturan umum langkah (§3), langkah FASE-00 s.d. FASE-09 (`L00-01` s.d. `L09-05`), laporan fase, dan perubahan dokumen lain. |
| 0.2 | 2026-10-10 | Lisensi proyek MIT (§2.1). FASE-00 (masukan sebelum mulai, L00-01) dan §14 diperbarui. |
