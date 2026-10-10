# Spensada — Project Overview

| Item | Nilai |
|---|---|
| Versi | 0.11 (draft) |
| Tanggal | 2026-10-10 |
| Sumber | Discovery Session 1 (Project Discovery) dan Session 2 (Product & Feature Definition). Diperbarui dengan hasil review dan keputusan Session 3, Session 4, Session 4b, Session 5, Session 6, Session 7, Session 8, Session 9, Session 10, dan keputusan pemilik proyek 2026-10-10. |
| Dokumen terkait | [01-product-requirements.md](01-product-requirements.md), [02-user-roles-and-permissions.md](02-user-roles-and-permissions.md), [03-user-flow.md](03-user-flow.md), [04-feature-specification.md](04-feature-specification.md), [05-business-rules.md](05-business-rules.md), [06-database-design.md](06-database-design.md), [07-system-architecture.md](07-system-architecture.md), [08-ui-ux-design-system.md](08-ui-ux-design-system.md), [09-page-and-route-specification.md](09-page-and-route-specification.md), [10-api-specification.md](10-api-specification.md), [11-validation-and-error-handling.md](11-validation-and-error-handling.md), [12-security.md](12-security.md), [13-reporting-import-export.md](13-reporting-import-export.md), [14-development-roadmap.md](14-development-roadmap.md) |

Dokumen ini adalah titik masuk dokumentasi proyek. Baca dokumen ini sebelum dokumen lain.

### Label status

Setiap fakta, keputusan, dan usulan di dokumentasi ini diberi label:

| Label | Arti |
|---|---|
| CONFIRMED | Fakta dari pengguna, atau hasil pengecekan repository/environment. |
| DECISION | Keputusan yang sudah disepakati pengguna. |
| RECOMMENDATION | Usulan analis yang belum disetujui secara eksplisit. Dipakai sebagai arah kerja dokumen berikutnya sampai dikonfirmasi atau diganti. |
| ASSUMPTION | Dugaan yang belum dikonfirmasi. Jangan diperlakukan sebagai fakta. |
| OPEN QUESTION | Belum diputuskan. Kode `OQ-xx` merujuk ke daftar di §8.2. |

---

## 1. Ringkasan proyek

Spensada adalah aplikasi web akademik untuk sekolah jenjang SMP dengan fokus utama presensi kehadiran siswa. "Spensada" adalah nama produk. Nama resmi sekolah, alamat, dan logo diatur admin di pengaturan aplikasi (DECISION, OQ-01). Siswa memindai QR code di kartu OSIS, yang hanya berisi NISN, ke webcam laptop yang berfungsi sebagai stasiun scan.

Stasiun scan bekerja *local-first*. Data siswa dimuat lebih dulu ke laptop, sehingga setiap scan langsung mendapat umpan balik. Setelah itu data dikirim (sinkron) ke server secara otomatis.

Dari data scan, sistem menentukan status kehadiran harian, menyajikan dashboard dan rekap, dan mengirim notifikasi WhatsApp ke orang tua. Versi pertama juga memuat jadwal pelajaran sebagai informasi, pengumuman, halaman publik, dan cetak kartu untuk siswa baru. Seluruh isi versi pertama dirilis bertahap (§6).

## 2. Latar belakang dan masalah

**Kondisi saat ini** (CONFIRMED):

- Kehadiran siswa dicatat di kertas/buku, Excel/Google Sheets, dan WhatsApp.
- Semua siswa sudah memiliki kartu OSIS tercetak dengan QR berisi NISN.
- Foto digital siswa sudah ada, tetapi belum rapi.

**Masalah inti** (CONFIRMED di Session 3, A-02):

1. Absen manual lambat, rawan salah, dan mudah dititipkan.
2. Data tersebar di tiga tempat, sehingga tidak ada satu sumber data kehadiran yang dapat dipercaya.
3. Rekap harian, bulanan, dan semester dikerjakan manual.
4. Izin dan sakit yang disampaikan lewat WhatsApp tidak terdokumentasi dan sulit ditelusuri.
5. Keterlambatan dan ketidakhadiran baru diketahui belakangan, termasuk oleh orang tua.

## 3. Tujuan produk

| No | Tujuan | Status |
|---|---|---|
| T-1 | Mencatat presensi masuk dan pulang setiap siswa lewat scan kartu, dengan umpan balik instan di stasiun scan. | DECISION |
| T-2 | Menjadi satu sumber data kehadiran siswa, menggantikan kertas, Excel, dan WhatsApp. | CONFIRMED (A-05, Session 3) |
| T-3 | Menyajikan pantauan hari ini dan rekap kehadiran tanpa kerja manual. | DECISION |
| T-4 | Mencatat izin dan sakit beserta verifikasinya. | DECISION |
| T-5 | Memberi tahu orang tua/wali lewat WhatsApp secara otomatis. | DECISION |

## 4. Pengguna dan aktor

| Aktor | Peran | Status |
|---|---|---|
| Admin | Operator/TU. Mengelola akun, master data, aturan jam, kalender sekolah, identitas sekolah, dan pengaturan notifikasi. | CONFIRMED |
| Staf internal | Semua guru dan staf memiliki akun staf. Hak aksesnya mengikuti role: staf (dasar), wali kelas, guru piket, guru BK, dan pimpinan. Lihat [02-user-roles-and-permissions.md](02-user-roles-and-permissions.md). | CONFIRMED (aktor); DECISION (sub-peran dan hak akses, Session 3) |
| Siswa | Melihat riwayat kehadiran sendiri, mengajukan izin/sakit, melihat jadwal dan pengumuman. | CONFIRMED |
| Publik | Tanpa login. Melihat pengumuman, info sekolah, dan rekap agregat hari ini tanpa nama siswa. | DECISION |
| Akun stasiun | Akun khusus laptop stasiun scan dengan hak minimal: memuat data kiosk, mencatat scan, dan sinkron. Satu akun per laptop. Aman bila laptop ditinggal terbuka. | DECISION (Session 3) |
| Orang tua/wali | Bukan pengguna sistem, karena pengguna eksternal hanya siswa. Menjadi penerima notifikasi WhatsApp; nomor WA disimpan per siswa. | CONFIRMED (bukan pengguna); DECISION (penerima notifikasi) |

## 5. Konsep solusi

Satu aplikasi CodeIgniter 4 dengan empat area:

| Area | Pengguna | Cara kerja | Status |
|---|---|---|---|
| Kiosk (di stasiun scan) | Siswa, petugas, akun stasiun | Lihat penjelasan di bawah tabel. | DECISION (local-first, sinkron otomatis + manual, foto); RECOMMENDATION (scanner USB) |
| Panel staf/admin | Admin, staf | Server-rendered. Master data, dashboard hari ini, koreksi presensi, izin/sakit, rekap, export, flyer, dan notifikasi. | RECOMMENDATION |
| Portal siswa | Siswa | Server-rendered. Riwayat kehadiran, pengajuan izin/sakit, jadwal, dan pengumuman. | RECOMMENDATION |
| Halaman publik | Publik | Server-rendered. Pengumuman, info sekolah, dan rekap agregat hari ini. | DECISION |

Cara kerja kiosk:

- Kiosk adalah halaman Vanilla JS yang memakai penyimpanan browser.
- Data dan foto siswa dimuat ke laptop lebih dulu.
- QR dibaca dari webcam, atau dari scanner QR USB.
- Setiap scan divalidasi secara lokal. Layar langsung menampilkan foto, nama, rombel, dan status.
- Data dikirim ke server otomatis tiap beberapa detik saat online. Petugas juga punya tombol sinkron manual.

Kiosk adalah satu-satunya bagian yang berperilaku seperti aplikasi client di browser. Bagian lain mengikuti pola server-rendered dengan progressive enhancement.

## 6. Ruang lingkup

### 6.1 Rilis (DECISION: rilis bertahap)

| Rilis | Fokus | Isi |
|---|---|---|
| R1 | Presensi inti | Lihat daftar di bawah tabel. |
| R2 | Komunikasi dan laporan | Notifikasi WhatsApp ke orang tua/wali; export rekap XLSX, CSV, dan PDF; flyer kehadiran (PNG). |
| R3 | Pelengkap | Jadwal dan mata pelajaran (informasi saja); pengumuman; halaman publik; cetak kartu siswa baru. |

Isi R1:

- Master data: tahun ajaran, rombel, dan siswa (termasuk nomor WA orang tua/wali dan atribut tambahan yang diatur admin).
- Import siswa dari Excel/CSV.
- Foto siswa.
- Login per role.
- Kiosk local-first dan sinkron.
- Aturan jam dan kalender sekolah.
- Presensi manual, koreksi status, dan mode darurat.
- Izin, sakit, dan dispensasi.
- Status Alpa otomatis.
- Rekap per rombel di layar dan riwayat per siswa.
- Dashboard hari ini.
- Log perubahan presensi.

Rincian kebutuhan dan ID requirement per rilis ada di [01-product-requirements.md](01-product-requirements.md).

### 6.2 Ditunda (di luar v1) — DECISION

- Snapshot webcam otomatis saat scan.
- Presensi guru/staf.

### 6.3 Tidak dipakai — DECISION

- Presensi per jam pelajaran. Presensi hanya masuk dan pulang per hari, dan jadwal tidak memengaruhi presensi.
- Cek kehadiran per siswa oleh publik lewat NISN.
- Membangun engine WhatsApp sendiri. Notifikasi memakai API gateway pihak ketiga.

## 7. Konteks teknis dan batasan

### 7.1 Stack (CONFIRMED)

- Backend: CodeIgniter 4.
- Database: MySQL dengan driver MySQLi.
- Frontend: HTML, CSS, Vanilla JavaScript. Tanpa React, Vue, Angular, Vite, atau arsitektur SPA.
- Tampilan: Bootstrap 5 dari file `.min` di server sendiri untuk panel, portal, dan halaman publik; kiosk memakai CSS sendiri (DECISION, keputusan pemilik 2026-10-10, `08` UI-73).
- Pola: server-rendered, dengan API seperlunya (misalnya untuk sinkron kiosk).
- Library tambahan hanya bila memberi manfaat nyata, dan alasannya dicatat (`07` ARS-10).
- Repository: GitHub.

### 7.2 Environment

| Environment | Detail | Status |
|---|---|---|
| Lokal | Windows, Laragon, Nginx, PHP 8.3.28, MySQL 8.4.3. Panduan teknis ditulis untuk environment ini, bukan Linux. | CONFIRMED |
| Lokal — alamat | `https://spensada.test` (virtual host otomatis Laragon dengan SSL). HTTPS diperlukan agar webcam bisa dipakai. Panduan lokal ada di `07` §15. | RECOMMENDATION |
| Production | Server Windows dengan Laragon, Nginx, PHP 8.3, dan MySQL 8.4, menggantikan VPS Linux dari Session 6. Document root diarahkan ke `public/`, seluruh aplikasi memakai HTTPS, dan tugas terjadwal (cron) lewat Windows Task Scheduler dipakai sejak R1. Langkahnya ada di `07` §16. Lokasi server menunggu OQ-20. | DECISION (hosting online, Session 6; server Windows, keputusan pemilik 2026-10-10) |

### 7.3 Kondisi repository (CONFIRMED, dicek 2026-10-05)

| Item | Kondisi |
|---|---|
| Framework | CodeIgniter 4.7.4, dipasang tanpa Composer: folder `system/` ikut di-commit dan tidak ada `vendor/`. `composer.json` yang ada adalah milik framework, bukan aplikasi. Repository dipindah ke Composer appstarter di fase implementasi pertama (`07` ARS-07). |
| Kode aplikasi | Belum ada. Hanya `Home::index` dan `app/Views/welcome_message.php` bawaan. |
| Git | Berisi dokumen `docs/` dari Session 1–10, termasuk contoh visual `docs/08-contoh-tampilan.html`, serta `.gitignore` dari Session 6 (`07` ARS-09). |
| Konfigurasi database | `app/Config/Database.php`: MySQLi, `utf8mb4` / `utf8mb4_general_ci`, kredensial kosong. |
| `baseURL` | Masih `http://localhost:8080/`. |
| `appTimezone` | Masih `UTC`. Diubah ke `Asia/Jakarta` di fase implementasi pertama (`07` ARS-44). |
| Virtual host Laragon | `spensada.test` belum terbentuk. Pola virtual host otomatis Laragon di mesin pengembang mengarah ke `public/` dan mendukung port 443 (SSL). |

### 7.4 Risiko arsitektur

| ID | Risiko | Arah penanganan | Status | Dibahas di |
|---|---|---|---|---|
| R-01 | Browser hanya mengizinkan webcam di HTTPS atau `localhost`. | Lokal memakai `https://spensada.test` dengan sertifikat Laragon yang dipercaya Windows; production memakai sertifikat Let's Encrypt (`07` §15, §16, dan ARS-02). | CONFIRMED (risiko); RECOMMENDATION (penanganan) | Session 6 |
| R-02 | QR berisi NISN polos dapat dipalsukan, misalnya dengan QR buatan sendiri atau foto kartu teman. Kartu hilang juga tidak dapat diblokir, karena kartu pengganti memakai QR yang sama. | Risiko diterima. Kiosk diawasi petugas, dan layar menampilkan foto, nama, dan rombel di setiap scan. Tidak ada pemblokiran kartu atau tanda khusus di kiosk. Dugaan kartu titipan ditangani lewat koreksi status (`05` BR-SCN-09). | Risiko CONFIRMED; penerimaan DECISION (OQ-06, Session 4) | Session 4 |
| R-03 | Antrean pagi 500–1.000 siswa. Kecepatan baca webcam tetap menjadi batas walaupun tanpa jeda jaringan. | Beberapa stasiun scan di gerbang utama. Kiosk juga menerima scanner QR USB (mode keyboard). | RECOMMENDATION; lokasi DECISION (gerbang utama, Session 3); jumlah stasiun OPEN (OQ-08) | Session 3 (lokasi); OQ-08 (jumlah) |
| R-04 | Chrome dan Edge di Windows tidak memiliki `BarcodeDetector`. Menurut MDN browser-compat-data, fitur ini hanya ada di macOS/ChromeOS. | Library zxing-wasm, disajikan dari server sendiri (`07` ARS-10, ARS-25). | CONFIRMED (risiko); DECISION (zxing-wasm, Session 6) | Session 6 |
| R-05 | Jam scan berasal dari laptop, sehingga bisa salah atau diubah. | Selisih jam laptop terhadap server diukur saat data dimuat dan setiap kali sinkron. Kiosk menjaga jam dengan jam monoton browser, dan kiosk yang dibuka offline memakai selisih terakhir dengan peringatan. Server menandai scan dengan jam tidak wajar untuk ditinjau (`05` BR-SCN-07, BR-SCN-08; `07` ARS-27, ARS-28). Jam laptop yang sudah dibetulkan sebelum scan terkirim tidak terdeteksi (`07` ARS-27 langkah 7). Laptop kiosk memakai akun Windows non-admin. | RECOMMENDATION; DECISION (kiosk yang dibuka offline, Session 6) | Session 4 (aturan), Session 6 (teknis) |
| R-06 | Scan yang belum tersinkron bisa hilang bila laptop rusak atau data browser terhapus. | IndexedDB dengan penyimpanan permanen, kiosk dipasang sebagai aplikasi, penghitung "belum tersinkron", dan sinkron otomatis (`07` ARS-21, ARS-29). Kiosk melaporkan jumlah scan belum tersinkron dan keadaan penyimpanan permanen ke status stasiun di panel (`10` EP-KIO-03, `09` HAL-KIO-02). | RECOMMENDATION; DECISION (laporan penyimpanan permanen, Session 8) | Session 6, 8 |
| R-07 | Data dan foto siswa (data anak) tersimpan di laptop stasiun scan. | Laptop dan profil browser khusus kiosk, akun Windows non-admin dengan password, dan enkripsi perangkat bila tersedia. Kiosk hanya memuat data minimal. Logout dan hapus data lokal memakai PIN petugas. Data dihapus saat akun stasiun dinonaktifkan dan saat perangkat tidak lagi dipakai (`12` SEC-21 s.d. SEC-23, SEC-69). | RECOMMENDATION; PIN petugas DECISION (Session 9) | Session 9 |
| R-08 | Halaman kiosk harus bisa dibuka ulang tanpa internet, misalnya setelah laptop restart. | Service Worker untuk halaman dan aset kiosk (`07` ARS-22). | RECOMMENDATION | Session 6 |
| R-09 | Kiriman sinkron bisa terulang, dan satu siswa bisa scan di beberapa stasiun. | ID unik per scan dari laptop. Scan pertama yang berlaku: server memakai scan paling awal dari semua stasiun (`05` BR-SCN-03). | RECOMMENDATION (ID unik); DECISION (aturan penggabungan, Session 4) | Session 4 |
| R-10 | Endpoint sinkron bisa disalahgunakan untuk mengirim presensi palsu. | Hanya untuk akun stasiun yang login, dilindungi CSRF, dan divalidasi server. Setiap scan membawa akun stasiun pencatatnya, dan server menolak scan dari akun lain (`10` EP-KIO-03). Satu akun stasiun hanya aktif di satu laptop, laju API kiosk dibatasi, dan scan yang ditolak server dicatat (`12` SEC-18 s.d. SEC-20, SEC-24, SEC-54). | RECOMMENDATION; satu login aktif per akun stasiun DECISION (Session 9) | Session 8, 9 |
| R-11 | Zona waktu default CodeIgniter adalah `UTC`, dan zona waktu server hosting bisa berbeda dari sekolah. | Zona waktu aplikasi dan database diset eksplisit ke WIB (`Asia/Jakarta`, +07:00). Lihat `05` BR-JAM-12 dan §14. Caranya di `07` ARS-44 s.d. ARS-46. | CONFIRMED (default CI4); DECISION (WIB, OQ-05) | Session 4 (zona), Session 6 (teknis) |
| R-12 | NISN bisa diawali nol, dan Excel sering membuang nol di depan. | NISN disimpan sebagai teks 10 digit, `id` internal menjadi primary key, dan import divalidasi per baris. Template XLSX memformat kolom NISN sebagai teks (`06` DB-03, `13` IM-01). | RECOMMENDATION | Session 5 |
| R-13 | Status Alpa salah bila dihitung sebelum semua data masuk. | Status dapat dihitung ulang setiap kali data berubah, sehingga scan yang tersinkron belakangan mengoreksi Alpa. Hasilnya disimpan sebagai salinan yang dapat dibangun ulang (`06` §11). Hitung ulang memakai antrean yang ditulis bersama perubahan data, sehingga tidak ada yang terlewat (`07` §7). Sesi masuk ditutup otomatis. Pesan "tidak hadir" ditunda, menunggu semua stasiun tersinkron, dan ditahan bila jumlah siswa tercatat masuk di bawah ambang (`05` BR-STS-06, BR-WA-02, BR-WA-03). | DECISION (tutup otomatis, tunda, ambang: Session 4; hitung ulang dan salinan: Session 5; antrean: Session 6) | Session 4, 5, 6 |
| R-14 | Riwayat rekap rusak saat kenaikan kelas bila siswa hanya punya satu kolom kelas. | Penempatan siswa ke rombel dicatat dengan tanggal mulai dan selesai, dan rombel pada setiap tanggal disalin ke status harian (`06` §6.7, §11; `05` BR-REK-05). | DECISION (Session 5) | Session 5 |
| R-15 | Keterbatasan hosting terkait document root dan cron. | Production memakai server Windows yang diatur sendiri, sehingga document root, tugas terjadwal, dan HTTPS dapat diatur sendiri (`07` ARS-01, ARS-02, §16). | DECISION (hosting yang diatur sendiri, Session 6; server Windows, keputusan pemilik 2026-10-10) | Session 6 (OQ-09) |
| R-16 | Volume WhatsApp tinggi. Default scan masuk berarti ±500–1.000 pesan setiap pagi. Jeda dan kuota provider membatasi kecepatan kirim, dan nomor bisa diblokir. | Pesan dikirim lewat antrean (outbox) dan proses terjadwal, lalu diuji di R2. Risiko nomor diblokir diterima pengguna. | DECISION (risiko blokir diterima); RECOMMENDATION (antrean) | Sebelum R2 (OQ-10) |
| R-17 | Data siswa termasuk data anak, dan surat sakit termasuk data kesehatan. Keduanya data pribadi spesifik menurut UU 27/2022 tentang Pelindungan Data Pribadi. | File unggahan disimpan di luar `public/` dengan akses terbatas. Halaman publik tanpa data individu. Flyer hanya berisi angka (OQ-11, `13` LP-08). Akses lampiran oleh staf dicatat (OQ-17). Data hanya dihapus setelah sekolah menetapkan kebijakan datanya (OQ-18). Ketentuannya di `12` §15. | RECOMMENDATION; isi flyer DECISION (Session 5); pencatatan akses lampiran dan retensi DECISION (Session 9) | Session 9 |
| R-18 | XLSX, PDF, dan QR membutuhkan library PHP via Composer, padahal framework saat ini dipasang tanpa Composer. Tanpa `.gitignore`, `.env` (password database) dan isi `writable/` bisa ikut ter-push. | Pindah ke Composer appstarter di commit pertama fase implementasi (`07` ARS-07). `.gitignore` dibuat di Session 6 (`07` ARS-09). | DECISION (appstarter dan `.gitignore`, Session 6); RECOMMENDATION (waktu migrasi) | Session 6 |
| R-19 | Flyer dibuat di browser menjadi PNG. | Flyer digambar dengan Canvas API tanpa library, berukuran 1080×1350 px, dan pratinjaunya memakai canvas yang sama (`08` UI-65, UI-66). | DECISION (Session 7) | Session 7 |
| R-20 | Volume data ±400 ribu catatan scan per tahun (±1.000 siswa × 2 scan × ±200 hari). | Index yang tepat (`06` §15). Volume ini ringan untuk MySQL, dan data tidak dihapus di R1 (`06` DB-13). | RECOMMENDATION | Session 5 |
| R-21 | Pesan "tidak hadir" bisa terkirim massal secara keliru bila server belum menerima scan, misalnya internet sekolah mati sepanjang pagi atau semua stasiun mati. | Waktu tunda, syarat semua stasiun tersinkron, ambang pengaman yang menahan pesan, dan mode darurat (`05` §10 dan §11). | DECISION | Session 4 |
| R-22 | Izin/sakit/dispensasi yang disetujui menang atas kehadiran fisik. Siswa berizin yang ternyata datang tetap tercatat Izin bila data izinnya tidak dibatalkan. | Penanda di dashboard, daftar presensi rombel, dan riwayat siswa bagi staf yang melihat daftar nama, serta pembatalan izin oleh staf yang berhak (`05` BR-STS-07, BR-IZN-09). | DECISION (aturan prioritas: Session 4; penanda: Session 4b) | Session 4, 4b |

## 8. Asumsi dan pertanyaan terbuka

### 8.1 Asumsi

| ID | Asumsi | Status |
|---|---|---|
| A-01 | Sekolah berjenjang SMP, berdasarkan nama "spensada" dan penggunaan kartu OSIS. | CONFIRMED (Session 3) |
| A-02 | Daftar masalah inti di §2 sudah tepat. | CONFIRMED (Session 3) |
| A-03 | Setiap siswa aktif memiliki NISN 10 digit yang valid, dan satu siswa memiliki satu kartu. | ASSUMPTION |
| A-04 | Stasiun scan memakai laptop Windows dengan Chrome atau Edge versi terbaru. | ASSUMPTION |
| A-05 | Sistem menggantikan sepenuhnya pencatatan kehadiran di kertas, Excel, dan WhatsApp (tujuan T-2). | CONFIRMED (Session 3) |

### 8.2 Pertanyaan terbuka

| ID | Pertanyaan | Dibahas di | Status |
|---|---|---|---|
| OQ-01 | Nama resmi sekolah, jenjang, dan nama produk. | Session 3 | Terjawab: jenjang SMP; nama produk "Spensada"; nama resmi sekolah, alamat, dan logo diisi admin di pengaturan (FR-MD-08). |
| OQ-02 | Sub-peran staf (wali kelas, guru piket, BK, kepala sekolah, TU) dan hak akses masing-masing. | Session 3 | Terjawab: lihat `02`. |
| OQ-03 | Aturan jam: jam masuk, batas terlambat, jam pulang, pulang lebih awal, scan ganda, dan hari sekolah dalam seminggu. | Session 4 | Terjawab: Senin–Sabtu; aturan jam per hari ditambah jadwal khusus; libur per tingkat/rombel; jenis presensi ditentukan jendela masuk dan jendela pulang; scan setelah sesi masuk ditutup ditolak; pulang lebih awal tidak mengubah status; scan pertama berlaku. Lihat `05` §3 dan §4. |
| OQ-04 | Aturan penggabungan scan dari beberapa stasiun, dan prioritas antara scan, presensi manual, dan izin/sakit. | Session 4 | Terjawab: scan paling awal berlaku; prioritas izin/sakit/dispensasi yang disetujui, lalu koreksi, lalu presensi masuk. Lihat `05` §5 dan §6. |
| OQ-05 | Zona waktu sekolah (WIB, WITA, atau WIT). | Session 4 | Terjawab: WIB (`Asia/Jakarta`, UTC+7). |
| OQ-06 | Penerimaan risiko QR palsu dan kartu hilang (R-02). | Session 4 | Terjawab: risiko diterima; pengamannya pengawasan petugas (`05` BR-SCN-09). |
| OQ-07 | Cara menutup sesi masuk/pulang: manual oleh petugas atau otomatis pada jam tertentu. | Session 4 | Terjawab: otomatis pada jam tutup sesi (`05` BR-JAM-07). |
| OQ-08 | Jumlah stasiun scan (laptop) dan lokasinya. | Session 3 (lokasi); sebelum uji coba R1 (jumlah) | Sebagian: semua stasiun di gerbang utama, untuk scan masuk dan pulang. Jumlah laptop belum diketahui. |
| OQ-09 | Jenis hosting (shared atau VPS) dan cara instalasi framework (Composer). | Session 6 | Terjawab: VPS dengan Nginx, PHP 8.3, dan MySQL 8.4; framework dipasang lewat Composer appstarter; deploy lewat git dan Composer di server (`07` §2). Hosting diubah keputusan pemilik 2026-10-10 menjadi server Windows dengan Laragon (`07` ARS-01); rinciannya OQ-20. |
| OQ-10 | Provider gateway WhatsApp. | Sebelum R2 | Terbuka |
| OQ-11 | Laporan apa saja dan format masing-masing (matriks laporan × format); isi flyer (angka saja atau dengan nama siswa). | Session 5 (ditulis di `13-reporting-import-export.md`) | Terjawab: tujuh laporan dengan matriks format di `13` §4; flyer berisi angka saja (`13` LP-08). |
| OQ-12 | Format nama file foto siswa yang ada saat ini. | Session 5 (ditulis di `13-reporting-import-export.md`) | Terjawab: nama file foto saat ini belum seragam; format baku nama file diawali 10 digit NISN (`13` IM-03). |
| OQ-13 | Desain kartu siswa baru: mengikuti kartu lama atau desain baru. | Session 7; contoh kartu sebelum R3 | Terjawab sebagian: kartu baru mengikuti tata letak kartu OSIS lama dan dicetak sebagai PDF A4 berisi 10 kartu (`08` UI-62, UI-63). Contoh kartu lama diserahkan sekolah sebelum R3, lalu desain dirinci. |
| OQ-14 | Cara pembuatan akun siswa dan password awal. | Session 3 | Terjawab: lihat `02` §2 dan §7.2. |
| OQ-15 | Batas mundur (berapa hari ke belakang) untuk koreksi presensi dan input izin/sakit oleh staf; apakah siswa boleh mengajukan izin/sakit untuk tanggal yang sudah lewat. | Session 4 | Terjawab: hari ini dan 7 hari kalender sebelumnya, diatur admin; admin tidak dibatasi; siswa boleh mengajukan untuk tanggal lampau dalam batas ini (`05` §9). |
| OQ-16 | Prosedur darurat bila semua stasiun scan tidak dapat dipakai, termasuk kemungkinan presensi manual per rombel sekaligus. | Session 4 | Terjawab: mode darurat dan presensi manual per rombel (`05` §10). |
| OQ-17 | Apakah setiap pembukaan lampiran surat oleh staf perlu dicatat (siapa dan kapan). | Session 9 | Terjawab: setiap pembukaan dan unduhan lampiran oleh akun staf dicatat, dan admin melihatnya di halaman log aktivitas (`12` SEC-60, `HA-AKN-08`). |
| OQ-18 | Kebijakan data sekolah: masa simpan setiap jenis data, pemberitahuan privasi bagi siswa dan orang tua/wali, dasar pemrosesan data anak termasuk persetujuan orang tua/wali, dan penanggung jawab data di sekolah (`12` SEC-66, SEC-68). | Sebelum uji coba R1, oleh sekolah | Terbuka |
| OQ-19 | Tempat penyimpanan backup di luar server dan dua pemegang kunci privat backup (`12` SEC-75, `14` GL-04). | Sebelum uji coba R1, oleh sekolah dan pengelola server | Terbuka |
| OQ-20 | Server production Windows: lokasinya (VPS Windows atau komputer server di sekolah), persetujuan klien ACME untuk sertifikat HTTPS (misalnya win-acme), dan cara menjalankan layanan (Windows service dan tugas terjadwal, bukan aplikasi Laragon dengan login otomatis) (`07` §16.4). | Awal FASE-09, oleh pemilik proyek | Terbuka |

## 9. Glosarium

Istilah di bawah wajib dipakai secara konsisten di seluruh dokumentasi dan antarmuka. Nama teknis (tabel, kolom, route) ditetapkan di Session 5–8 dan dipetakan ke istilah ini. Nama tabel dan kolom ada di `06`, dan route di `09`. Alamat halaman memakai label layar, misalnya `kelas` untuk rombel (`09` RT-03).

| Istilah | Definisi |
|---|---|
| NISN | Nomor Induk Siswa Nasional: 10 digit angka, unik per siswa. Isi QR di kartu OSIS. Disimpan sebagai teks karena bisa diawali nol. |
| Kartu OSIS | Kartu identitas siswa yang sudah tercetak, memuat QR berisi NISN polos. |
| Presensi | Pencatatan kehadiran siswa per hari sekolah, terdiri dari presensi masuk dan presensi pulang. |
| Scan | Satu kali pembacaan QR di kiosk. Satu scan menghasilkan satu catatan scan. |
| Stasiun scan | Perangkat di titik scan (laptop dengan webcam, opsional scanner QR USB) yang login memakai akun stasiun. |
| Kiosk | Halaman aplikasi yang berjalan di stasiun scan untuk membaca QR dan menampilkan hasil scan. |
| Akun stasiun | Akun khusus untuk stasiun scan dengan hak minimal: memuat data kiosk, mencatat scan, dan sinkron. Satu akun per laptop. |
| Petugas | Orang yang mengawasi stasiun scan: guru piket, atau satpam/staf TU. Petugas tidak login di kiosk; kiosk berjalan dengan akun stasiun. |
| Akun staf | Akun untuk guru dan staf sekolah, login dengan username. Semua guru dan staf memilikinya. Hak aksesnya mengikuti role. |
| Akun siswa | Akun untuk siswa, login dengan NISN. Dibuat otomatis dari data siswa. Statusnya belum aktif, aktif, atau nonaktif. |
| Role | Kelompok hak akses tetap yang diberikan ke akun: Admin, Staf, Wali kelas, Guru piket, Guru BK, Pimpinan, Siswa, dan Stasiun. Satu akun staf boleh memiliki beberapa role. Rinciannya ada di `02`. |
| Staf (role) | Role dasar yang otomatis dimiliki setiap akun staf. Hanya melihat angka kehadiran di dashboard hari ini. Berbeda dengan "staf internal" sebagai aktor. |
| Hak akses | Izin untuk melakukan satu tindakan, beserta cakupan datanya. Memakai ID `HA-*` di `02`. |
| Cakupan | Batas data yang boleh diakses oleh satu hak akses: semua, rombel, hari ini, sendiri, atau angka. |
| Wali kelas | Staf yang ditetapkan admin sebagai penanggung jawab satu rombel pada satu tahun ajaran. |
| Guru piket | Staf yang menangani presensi harian dan mengawasi stasiun scan. Role tetap, tidak mengikuti jadwal piket harian. |
| Guru BK | Guru bimbingan dan konseling. Memantau dan menindaklanjuti kehadiran semua siswa. |
| Pimpinan | Kepala sekolah dan wakil kepala sekolah. Melihat seluruh data kehadiran tanpa mengubahnya. |
| Password awal | Password acak dari sistem untuk akun baru atau akun yang direset. Wajib diganti saat login pertama. |
| Slip akun | Lembar cetak berisi NISN dan password awal siswa, dibuat per rombel oleh wali kelas atau admin. Hanya dapat dicetak saat password dibuat. |
| Local-first | Pola kerja kiosk: data siswa dimuat ke laptop lebih dulu, scan divalidasi dan dicatat di laptop, lalu dikirim ke server. |
| Sinkron | Pengiriman catatan scan dari stasiun scan ke server. Berjalan otomatis saat online dan bisa dipicu manual. |
| Sesi masuk / sesi pulang | Rentang waktu penerimaan scan masuk dan scan pulang dalam satu hari sekolah, sama dengan jendela masuk dan jendela pulang. Sesi ditutup otomatis pada jam tutup sesi (`05` §4). |
| Sesi login | Masa seorang pengguna tetap login di satu browser. Berbeda dari sesi masuk dan sesi pulang. Sesi staf dan siswa berakhir setelah 8 jam tanpa aktivitas atau 7 hari sejak login (`12` SEC-12). Login akun stasiun bertahan lebih lama lewat cookie login stasiun (`07` ARS-30). |
| Aturan jam | Tujuh isian jam untuk satu hari sekolah: jam buka scan masuk, jam masuk, toleransi terlambat, jam tutup sesi masuk, jam buka scan pulang, jam pulang, dan jam tutup sesi pulang. Berasal dari pola mingguan atau jadwal khusus. |
| Pola mingguan | Aturan jam untuk setiap hari dalam seminggu, termasuk hari mana yang merupakan hari sekolah. Default Senin–Sabtu. |
| Jadwal khusus | Aturan jam untuk satu tanggal atau rentang tanggal yang mengalahkan pola mingguan, misalnya Ramadan atau rapat guru. Jadwal hari ini adalah jadwal khusus untuk tanggal hari berjalan, yang dapat diubah admin atau guru piket. |
| Jendela masuk / jendela pulang | Rentang jam ketika kiosk menerima scan masuk atau scan pulang. Di luar kedua jendela, kiosk menolak scan. |
| Batas terlambat | Jam masuk ditambah toleransi terlambat. Presensi masuk sampai menit batas terlambat berstatus Hadir; setelahnya Terlambat. |
| Presensi masuk / presensi pulang | Catatan masuk atau pulang seorang siswa pada satu tanggal, berasal dari scan atau presensi manual. Bila ada lebih dari satu, yang paling awal yang berlaku. |
| Scan ganda | Scan kedua dan seterusnya untuk jenis presensi yang sama pada tanggal yang sama. Tidak mengubah presensi. |
| Hadir | Status harian: presensi masuk sampai menit batas terlambat, atau dikoreksi staf menjadi Hadir. |
| Terlambat | Status harian: presensi masuk setelah batas terlambat, atau dikoreksi staf menjadi Terlambat. |
| Izin | Status harian: izin yang sudah disetujui. Berlaku walaupun siswa memiliki presensi masuk (`05` BR-STS-03). |
| Sakit | Status harian: sakit dengan keterangan yang sudah disetujui. Berlaku walaupun siswa memiliki presensi masuk. |
| Dispensasi | Status harian: siswa menjalankan tugas atau kegiatan resmi sekolah, misalnya lomba atau study tour. Diinput staf sebagai jenis ketiga izin/sakit. Bukan ketidakhadiran. |
| Alpa | Status harian: tidak hadir tanpa keterangan. Syaratnya: hari sekolah bagi siswa, tanpa izin/sakit/dispensasi yang disetujui, dan tanpa presensi masuk (atau dikoreksi Tidak hadir), setelah sesi masuk ditutup dan di luar mode darurat. |
| Belum hadir | Keadaan sementara pada hari berjalan, sebelum sesi masuk ditutup atau selama mode darurat aktif. Bukan status final. |
| Pulang lebih awal | Kejadian: presensi pulang sebelum jam pulang. Tidak mengubah status harian. |
| Tidak scan pulang | Kejadian: siswa berstatus Hadir atau Terlambat tanpa presensi pulang setelah sesi pulang ditutup. |
| Presensi manual | Presensi yang diinput staf, misalnya karena siswa lupa kartu, kartu rusak, kiosk terganggu, atau tiba setelah sesi masuk ditutup. Alasan wajib diisi. Presensi manual yang salah input tidak dihapus, tetapi dibatalkan dengan alasan (`05` BR-KOR-11). |
| Koreksi status | Penetapan kehadiran siswa pada satu tanggal oleh staf menjadi Hadir, Terlambat, atau Tidak hadir, dengan alasan. Mengalahkan scan dan presensi manual, tetapi kalah dari izin/sakit/dispensasi yang disetujui. Dapat dihapus dengan alasan, sehingga status kembali dihitung dari presensi. |
| Mode darurat | Keadaan pada satu hari sekolah ketika semua stasiun scan tidak dapat dipakai. Diaktifkan guru piket atau admin. Selama aktif, Alpa tidak terbentuk, pesan "tidak hadir" ditahan, dan presensi manual per rombel dapat dipakai. |
| Batas mundur | Rentang tanggal lampau yang masih boleh diubah staf: hari ini dan 7 hari kalender sebelumnya (diatur admin). Admin tidak dibatasi. |
| Log perubahan presensi | Catatan setiap perubahan data presensi oleh staf: siapa, kapan, nilai lama, nilai baru, dan alasan. |
| Penanda | Tanda di daftar nama staf untuk keadaan yang perlu diperiksa: siswa Izin/Sakit/Dispensasi yang memiliki presensi masuk, dan siswa yang memiliki presensi pulang tanpa presensi masuk. Bersifat informasi, tidak mengubah status, dan tidak tampil bagi siswa (`05` BR-STS-07). |
| Daftar presensi rombel | Tampilan status setiap siswa satu rombel pada satu tanggal, beserta sumber datanya dan tindakan sesuai hak (`04` FS-LAP-02). |
| Kelompok dispensasi | Sekumpulan data dispensasi yang dibuat dari satu input massal. Keputusannya dapat diubah per siswa atau sekaligus satu kelompok (`05` BR-IZN-05, BR-IZN-09). |
| Scan bertanda | Scan yang ditandai server saat sinkron untuk ditinjau, misalnya karena jam laptop tidak wajar (`05` BR-SCN-08). |
| Status stasiun | Keadaan setiap stasiun scan yang tampil di panel: waktu kontak dan sinkron terakhir, jumlah scan belum tersinkron dan scan galat, versi kode kiosk, serta keadaan penyimpanan permanen (`04` FS-KIO-05, `09` HAL-KIO-02). |
| Scan galat | Scan yang ditolak server karena datanya rusak. Scan itu tetap tersimpan di laptop dengan tanda galat, dan dihitung terpisah dari scan belum tersinkron (`04` FS-KIO-03 E4, `10` EP-KIO-03). Server juga mencatatnya di log aktivitas (`12` SEC-24). |
| Kunci login | Penolakan login sementara untuk satu username atau NISN setelah terlalu banyak percobaan gagal, atau untuk satu alamat IP. Kunci 24 jam dapat dibuka admin, atau wali kelas untuk siswa rombelnya (`12` SEC-08 s.d. SEC-11). |
| Log aktivitas | Catatan kejadian akun, keamanan, pengaturan, import, akses lampiran, dan scan yang ditolak server: siapa, kapan, dan apa. Hanya dibuka admin (`12` SEC-57 s.d. SEC-62). Berbeda dari log perubahan presensi dan log data siswa. |
| PIN petugas | PIN 6 digit, satu untuk semua stasiun, diatur admin. Dipakai petugas untuk logout akun stasiun dan menghapus data lokal di kiosk (`12` SEC-21). |
| Kode laporan | Delapan karakter di halaman galat server yang dilaporkan pengguna ke admin, agar galatnya dapat dicari di log aplikasi (`11` GAL-13, `12` SEC-58). |
| Data kiosk | Data yang dimuat kiosk dari server: siswa aktif beserta fotonya, aturan jam dan libur untuk hari ini dan 14 hari ke depan, identitas sekolah, dan parameter kiosk. Tidak memuat nomor WA, izin, atau riwayat (`07` ARS-23). |
| Selisih jam | Selisih jam laptop stasiun terhadap jam server, diukur setiap kali kiosk menghubungi server. Jam scan adalah jam laptop ditambah selisih ini (`05` BR-SCN-07, `07` ARS-27). |
| Pengajuan izin/sakit | Permohonan izin atau sakit dari siswa lewat portal, yang menunggu verifikasi staf. Siswa tidak dapat mengajukan dispensasi. |
| Tahun ajaran | Periode akademik sekolah, umumnya Juli–Juni, terdiri dari dua semester. |
| Rombel | Rombongan belajar: kelompok kelas tempat siswa terdaftar pada satu tahun ajaran, misalnya 7A. Di layar, slip, flyer, dan file tampil sebagai "Kelas", misalnya "Kelas 7A" (`08` UI-51). |
| Tingkat | Jenjang kelas dalam satu sekolah, misalnya 7, 8, dan 9. Di layar tampil sebagai "Tingkat", misalnya "Tingkat 7" (`08` UI-51). |
| Penempatan | Catatan bahwa seorang siswa berada di satu rombel sejak tanggal mulai sampai tanggal selesai. Rombel siswa pada setiap tanggal ditentukan dari penempatan (`05` BR-REK-05). |
| Import penempatan | Penempatan banyak siswa sekaligus lewat file berisi rombel tujuan, misalnya saat rombel diacak ulang pada kenaikan kelas (`13` IM-02). |
| Masa aktif | Periode ketika siswa berstatus aktif, dari tanggal mulai sampai tanggal terakhir aktif. Seorang siswa dapat memiliki beberapa periode, misalnya setelah diaktifkan kembali (`05` BR-KAL-06). |
| NIS | Nomor induk siswa dari sekolah. Hanya informasi; login dan QR tetap memakai NISN. |
| Atribut tambahan siswa | Atribut siswa yang dibuat admin sendiri, misalnya agama. Tidak dipakai logika presensi, rekap, atau filter laporan, tetapi ikut di export data siswa (`04` FS-MD-09). |
| Salinan status harian | Hasil penentuan status yang disimpan per siswa per hari sekolah dan selalu dapat dibangun ulang dari sumbernya. Bagian yang bergantung pada jam sekarang diturunkan saat dibaca (`06` §11). |
| Antrean hitung ulang | Catatan bahwa status siswa pada tanggal tertentu perlu dihitung ulang. Ditulis bersama setiap perubahan data sumber, lalu diproses segera setelahnya (`06` §11.5, `07` §7). |
| Hari sekolah | Tanggal kegiatan belajar menurut pola mingguan, jadwal khusus, dan kalender sekolah. Karena libur dapat berlaku per tingkat atau rombel, hari sekolah ditentukan per siswa (`05` BR-KAL-05). |
| Kalender sekolah | Daftar hari libur (untuk semua siswa, tingkat tertentu, atau rombel tertentu) dan jadwal khusus yang diatur admin. |
| Dashboard hari ini | Halaman pantauan kehadiran pada hari berjalan, per rombel. |
| Fragmen | Potongan HTML dari server untuk bagian halaman yang diperbarui tanpa memuat ulang halaman, misalnya tabel dashboard hari ini yang diperbarui setiap 30 detik (`07` ARS-50, `10`). |
| Rekap | Ringkasan kehadiran per rombel atau per siswa untuk rentang tanggal tertentu. |
| Rekap agregat | Jumlah kehadiran per rombel tanpa nama atau data individu siswa. Satu-satunya data kehadiran yang tampil di halaman publik. |
| Flyer kehadiran | Gambar PNG ringkasan kehadiran (per rombel atau total) yang dibuat di browser dan diunduh staf untuk dibagikan. |
| Notifikasi WA | Pesan WhatsApp otomatis ke orang tua/wali siswa. |
| Jenis kejadian notifikasi | Tujuh pemicu notifikasi: scan masuk, scan pulang, terlambat, tidak hadir, tidak scan pulang, izin, dan pulang lebih awal. |
| Gateway WA | Layanan pihak ketiga tidak resmi yang menyediakan API untuk mengirim pesan WhatsApp. |
| Outbox WA | Antrean pesan WhatsApp yang menunggu dikirim oleh proses terjadwal. |
| Ambang pengaman | Persentase minimal siswa wajib hadir yang tercatat masuk agar pesan "tidak hadir" dibuat otomatis. Di bawah ambang, pesan ditahan sampai dilepas atau dibatalkan guru piket atau admin. Default 50%. |
| Template pesan | Teks pesan per jenis kejadian notifikasi, dengan isian otomatis seperti nama siswa dan jam. |
| R1 / R2 / R3 | Tahap rilis versi pertama (lihat §6.1). |
| Spesifikasi fitur | Rincian satu kemampuan utuh di `04`, ber-ID `FS-<MODUL>-<NN>`, beserta acceptance criteria rinci ber-ID `AC-<MODUL>-<NN>-<NN>`. |
| Uji coba R1 | Uji teknis di server production dengan data buatan sebelum data asli diisi, untuk membuktikan kesiapan go-live (`14` §11). Tidak ada masa paralel dengan cara lama. |
| Go-live R1 | Hari sekolah pertama (hari H) ketika sistem menjadi satu-satunya pencatatan kehadiran. Ditentukan segera setelah uji coba R1 lulus (`14` RM-01, §12). |
| Fase implementasi | Satu tahap pembangunan R1 berisi sekelompok fitur, ber-ID `FASE-<NN>` (`14` §6). Rinciannya di `15`. |
| Session 1–11 | Tahap diskusi discovery untuk menyusun dokumentasi (lihat §10.2). Tidak sama dengan sesi masuk/pulang. |

## 10. Peta dokumen dan progres

### 10.1 Dokumen

| Dokumen | Isi | Sesi | Status |
|---|---|---|---|
| `00-project-overview.md` | Gambaran proyek (dokumen ini) | Session 1–10 | Draft 0.10 |
| `01-product-requirements.md` | Kebutuhan fungsional dan non-fungsional | Session 2–10 | Draft 0.10 |
| `02-user-roles-and-permissions.md` | Role dan permission | Session 3–9 | Draft 0.7 |
| `03-user-flow.md` | Alur pengguna | Session 3–10 | Draft 0.9 |
| `04-feature-specification.md` | Spesifikasi fitur rinci dan acceptance criteria | Session 4b–10 | Draft 0.7 |
| `05-business-rules.md` | Aturan bisnis | Session 4–9 | Draft 0.7 |
| `06-database-design.md` | Desain database | Session 5–10 | Draft 0.6 |
| `07-system-architecture.md` | Arsitektur sistem | Session 6–10 | Draft 0.5 |
| `08-ui-ux-design-system.md` | Sistem desain UI/UX, dengan contoh visual `08-contoh-tampilan.html` | Session 7–9 | Draft 0.3 |
| `09-page-and-route-specification.md` | Halaman, route, dan menu | Session 8–10 | Draft 0.3 |
| `10-api-specification.md` | API, termasuk sinkron kiosk | Session 8–9 | Draft 0.2 |
| `11-validation-and-error-handling.md` | Validasi dan penanganan error | Session 9 | Draft 0.1 |
| `12-security.md` | Keamanan | Session 9–10 | Draft 0.2 |
| `13-reporting-import-export.md` | Laporan, import, dan export | Session 5–9 | Draft 0.5 |
| `14-development-roadmap.md` | Roadmap pengembangan | Session 10 | Draft 0.1 |
| `15-implementation-phases.md` | Dokumen fase implementasi | Session 11 | Belum dibuat |

### 10.2 Progres sesi discovery

| Sesi | Topik | Status |
|---|---|---|
| 1 | Project Discovery | Selesai |
| 2 | Product & Feature Definition | Selesai; dokumen direview di Session 3 |
| 3 | User Roles & User Flow | Selesai; usulan di `02` dan `03` ditinjau di Session 4 |
| 4 | Business Rules | Selesai; usulan yang berdampak ke R1 ditinjau di Session 4b |
| 4b | Feature Specification (`04`), lanjutan Session 4 sebelum Session 5 | Selesai |
| 5 | Database Architecture (`06`), serta laporan, import, dan export (`13`) | Selesai |
| 6 | System Architecture (`07`) | Selesai |
| 7 | UI/UX & Design System (`08`) | Selesai |
| 8 | Routes / Pages / API (`09`, `10`) | Selesai |
| 9 | Security / Validation / Error Handling (`11`, `12`) | Selesai |
| 10 | Development Roadmap (`14`) | Selesai, menunggu review dokumen |
| 11 | Implementation Phase Documents (`15`) | Berikutnya |

## 11. Aturan untuk AI implementer

Aturan ini berlaku untuk AI atau developer yang mengerjakan kode di repository ini.

1. Baca dokumentasi di `docs/` sebelum mengubah kode, dimulai dari dokumen ini.
2. Dokumentasi adalah source of truth. Jangan mengarang requirement.
3. Hanya butir berlabel CONFIRMED atau DECISION yang boleh dianggap final. Butir RECOMMENDATION, ASSUMPTION, atau OPEN QUESTION harus dikonfirmasi dulu.
4. Jangan mengubah arsitektur tanpa alasan yang dicatat di dokumentasi.
5. Jangan menambahkan fitur di luar rilis atau fase yang sedang dikerjakan.
6. Periksa kode yang sudah ada sebelum membuat file baru. Hindari logika ganda.
7. Gunakan istilah sesuai glosarium (§9), dan pertahankan konsistensi nama serta pola.
8. Lakukan perubahan sekecil yang diperlukan, lalu verifikasi hasilnya.
9. Jika kode bertentangan dengan dokumentasi, jangan memilih salah satu secara diam-diam. Laporkan konfliknya beserta dampaknya.
10. Jika requirement berubah, perbarui dulu dokumen yang terdampak (§10.1), baru kemudian kode. ID requirement tidak pernah dinomori ulang; requirement yang batal ditandai `DEPRECATED`.
11. Instruksi teknis ditulis untuk Windows + Laragon + Nginx. Pisahkan dengan jelas langkah lokal dan langkah production.

## Riwayat perubahan

| Versi | Tanggal | Perubahan |
|---|---|---|
| 0.1 | 2026-10-03 | Draft awal dari Session 1–2. |
| 0.2 | 2026-10-03 | Hasil review dan keputusan Session 3. A-01, A-02, dan A-05 dikonfirmasi. OQ-01, OQ-02, dan OQ-14 terjawab; OQ-08 terjawab sebagian. OQ-15 dan OQ-16 ditambahkan. Aktor, glosarium (akun dan role), serta peta dokumen diperbarui. |
| 0.3 | 2026-10-03 | Keputusan Session 4 (`05`). OQ-03 s.d. OQ-07, OQ-15, dan OQ-16 terjawab; OQ-17 ditambahkan. Status Dispensasi ditambahkan, sehingga status harian menjadi enam. R-02, R-05, R-09, R-11, dan R-13 diperbarui; R-21 dan R-22 ditambahkan. Isi R1 dan glosarium (aturan jam, status, koreksi, mode darurat, batas mundur) diperbarui. `04` dijadwalkan di Session 4b. |
| 0.4 | 2026-10-03 | Keputusan Session 4b (`04` §2). `04` dibuat. R-22 diperbarui (penanda menjadi DECISION). Glosarium ditambah: penanda, daftar presensi rombel, kelompok dispensasi, scan bertanda, status stasiun, dan spesifikasi fitur. Definisi presensi manual dan koreksi status diperbarui. Peta dokumen dan progres sesi diperbarui. Tidak ada OQ yang terjawab atau ditambahkan. |
| 0.5 | 2026-10-04 | Keputusan Session 5 (`06` §2, `13` §2). `06` dan `13` dibuat. OQ-11 dan OQ-12 terjawab. R-12, R-13, R-14, R-17, dan R-20 diperbarui. Glosarium ditambah: penempatan, import penempatan, masa aktif, NIS, atribut tambahan siswa, dan salinan status harian. Isi R1, peta dokumen, dan progres sesi diperbarui. |
| 0.6 | 2026-10-04 | Keputusan Session 6 (`07` §2). `07` dibuat. OQ-09 terjawab. R-01, R-04 s.d. R-06, R-08, R-11, R-13, R-15, R-18, dan R-19 diperbarui. Stack (§7.1), environment (§7.2), dan kondisi repository (§7.3) diperbarui. Glosarium ditambah: data kiosk, selisih jam, antrean hitung ulang, dan sesi login. Peta dokumen dan progres sesi diperbarui. |
| 0.7 | 2026-10-04 | Keputusan Session 7 (`08` §2). `08` dan contoh visualnya dibuat. OQ-13 terjawab sebagian. R-19 diperbarui. Glosarium "Rombel" dan "Tingkat" memuat label layar. Kondisi repository, peta dokumen, dan progres sesi diperbarui. |
| 0.8 | 2026-10-05 | Keputusan Session 8 (`09` §2). `09` dan `10` dibuat. R-06 dan R-10 diperbarui. Pengantar glosarium merujuk route di `09`, dan glosarium ditambah: fragmen dan scan galat. Entri status stasiun diperbarui. Kondisi repository, peta dokumen, dan progres sesi diperbarui. Tidak ada OQ yang terjawab atau ditambahkan. |
| 0.9 | 2026-10-05 | Keputusan Session 9 (`12` §2). `11` dan `12` dibuat. OQ-17 terjawab, dan OQ-18 (kebijakan data sekolah) ditambahkan. R-07, R-10, dan R-17 diperbarui. Glosarium ditambah: kunci login, log aktivitas, PIN petugas, dan kode laporan. Entri sesi login dan scan galat diperbarui. Kondisi repository, peta dokumen, dan progres sesi diperbarui. |
| 0.10 | 2026-10-05 | Keputusan Session 10 (`14` §2). `14` dibuat. OQ-19 (tempat backup dan pemegang kunci privat) ditambahkan. Glosarium ditambah: uji coba R1, go-live R1, dan fase implementasi. Kondisi repository, peta dokumen, dan progres sesi diperbarui. |
| 0.11 | 2026-10-10 | Keputusan pemilik proyek 2026-10-10: server production Windows dengan Laragon (§7.2, R-15, OQ-09) dan Bootstrap 5 (§7.1). OQ-20 ditambahkan. |
