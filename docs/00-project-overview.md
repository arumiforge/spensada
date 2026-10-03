# Spensada — Project Overview

| Item | Nilai |
|---|---|
| Versi | 0.1 (draft, menunggu review) |
| Tanggal | 2026-10-03 |
| Sumber | Discovery Session 1 (Project Discovery) dan Session 2 (Product & Feature Definition) |
| Dokumen terkait | [01-product-requirements.md](01-product-requirements.md) |

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

Spensada (nama kerja) adalah aplikasi web akademik untuk sekolah dengan fokus utama presensi kehadiran siswa. Siswa memindai QR code di kartu OSIS, yang hanya berisi NISN, ke webcam laptop yang berfungsi sebagai stasiun scan.

Stasiun scan bekerja *local-first*. Data siswa dimuat lebih dulu ke laptop, sehingga setiap scan langsung mendapat umpan balik. Setelah itu data dikirim (sinkron) ke server secara otomatis.

Dari data scan, sistem menentukan status kehadiran harian, menyajikan dashboard dan rekap, dan mengirim notifikasi WhatsApp ke orang tua. Versi pertama juga memuat jadwal pelajaran sebagai informasi, pengumuman, halaman publik, dan cetak kartu untuk siswa baru. Seluruh isi versi pertama dirilis bertahap (§6).

## 2. Latar belakang dan masalah

**Kondisi saat ini** (CONFIRMED):

- Kehadiran siswa dicatat di kertas/buku, Excel/Google Sheets, dan WhatsApp.
- Semua siswa sudah memiliki kartu OSIS tercetak dengan QR berisi NISN.
- Foto digital siswa sudah ada, tetapi belum rapi.

**Masalah inti** (ASSUMPTION — disimpulkan dari kondisi saat ini; konfirmasi saat review dokumen ini):

1. Absen manual lambat, rawan salah, dan mudah dititipkan.
2. Data tersebar di tiga tempat, sehingga tidak ada satu sumber data kehadiran yang dapat dipercaya.
3. Rekap harian, bulanan, dan semester dikerjakan manual.
4. Izin dan sakit yang disampaikan lewat WhatsApp tidak terdokumentasi dan sulit ditelusuri.
5. Keterlambatan dan ketidakhadiran baru diketahui belakangan, termasuk oleh orang tua.

## 3. Tujuan produk

| No | Tujuan | Status |
|---|---|---|
| T-1 | Mencatat presensi masuk dan pulang setiap siswa lewat scan kartu, dengan umpan balik instan di stasiun scan. | DECISION |
| T-2 | Menjadi satu sumber data kehadiran siswa, menggantikan kertas, Excel, dan WhatsApp. | ASSUMPTION (A-05) |
| T-3 | Menyajikan pantauan hari ini dan rekap kehadiran tanpa kerja manual. | DECISION |
| T-4 | Mencatat izin dan sakit beserta verifikasinya. | DECISION |
| T-5 | Memberi tahu orang tua/wali lewat WhatsApp secara otomatis. | DECISION |

## 4. Pengguna dan aktor

| Aktor | Peran | Status |
|---|---|---|
| Admin | Mengelola akun, master data, aturan jam, kalender sekolah, dan pengaturan notifikasi. | CONFIRMED |
| Staf internal | Memantau dan mengoreksi presensi, memverifikasi izin/sakit, membuat rekap, export, dan flyer. | CONFIRMED; pembagian sub-peran OPEN (OQ-02) |
| Siswa | Melihat riwayat kehadiran sendiri, mengajukan izin/sakit, melihat jadwal dan pengumuman. | CONFIRMED |
| Publik | Tanpa login. Melihat pengumuman, info sekolah, dan rekap agregat hari ini tanpa nama siswa. | DECISION |
| Akun stasiun | Akun khusus laptop stasiun scan dengan hak minimal: memuat data kiosk, mencatat scan, dan sinkron. Aman bila laptop ditinggal terbuka. | RECOMMENDATION |
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

- Master data: tahun ajaran, rombel, dan siswa (termasuk nomor WA orang tua/wali).
- Import siswa dari Excel/CSV.
- Foto siswa.
- Login per role.
- Kiosk local-first dan sinkron.
- Aturan jam dan kalender sekolah.
- Presensi manual.
- Izin/sakit.
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
- Pola: server-rendered, dengan API seperlunya (misalnya untuk sinkron kiosk).
- Library tambahan hanya bila memberi manfaat nyata, dan alasannya dicatat.
- Repository: GitHub.

### 7.2 Environment

| Environment | Detail | Status |
|---|---|---|
| Lokal | Windows, Laragon, Nginx, PHP 8.3.28, MySQL 8.4.3. Panduan teknis ditulis untuk environment ini, bukan Linux. | CONFIRMED |
| Lokal — alamat | `https://spensada.test` (virtual host otomatis Laragon dengan SSL). HTTPS diperlukan agar webcam bisa dipakai. | RECOMMENDATION |
| Production | Hosting online. Jenis hosting (shared atau VPS) belum ditentukan (OQ-09). Document root wajib dapat diarahkan ke `public/`, dan cron dibutuhkan untuk notifikasi WhatsApp (R2). | DECISION (hosting online); RECOMMENDATION (syarat) |

### 7.3 Kondisi repository (CONFIRMED, dicek 2026-10-03)

| Item | Kondisi |
|---|---|
| Framework | CodeIgniter 4.7.4, dipasang tanpa Composer: folder `system/` ikut di-commit dan tidak ada `vendor/`. `composer.json` yang ada adalah milik framework, bukan aplikasi. |
| Kode aplikasi | Belum ada. Hanya `Home::index` dan `app/Views/welcome_message.php` bawaan. |
| Git | Satu commit awal. Belum ada `.gitignore`. |
| Konfigurasi database | `app/Config/Database.php`: MySQLi, `utf8mb4` / `utf8mb4_general_ci`, kredensial kosong. |
| `baseURL` | Masih `http://localhost:8080/`. |
| Virtual host Laragon | `spensada.test` belum terbentuk. Pola virtual host otomatis Laragon di mesin pengembang mengarah ke `public/` dan mendukung port 443 (SSL). |

### 7.4 Risiko arsitektur

| ID | Risiko | Arah penanganan | Status | Dibahas di |
|---|---|---|---|---|
| R-01 | Browser hanya mengizinkan webcam di HTTPS atau `localhost`. | Lokal memakai `https://spensada.test`; production memakai HTTPS. | CONFIRMED | Session 6 |
| R-02 | QR berisi NISN polos dapat dipalsukan, misalnya dengan QR buatan sendiri atau foto kartu teman. Kartu hilang juga tidak dapat diblokir, karena kartu pengganti memakai QR yang sama. | Kiosk diawasi petugas, dan layar menampilkan foto, nama, dan rombel di setiap scan. | Risiko CONFIRMED; penerimaan OPEN (OQ-06) | Session 4 |
| R-03 | Antrean pagi 500–1.000 siswa. Kecepatan baca webcam tetap menjadi batas walaupun tanpa jeda jaringan. | Beberapa stasiun scan. Kiosk juga menerima scanner QR USB (mode keyboard). | RECOMMENDATION; jumlah stasiun OPEN (OQ-08) | Session 3 |
| R-04 | Chrome dan Edge di Windows tidak memiliki `BarcodeDetector`. Menurut MDN browser-compat-data, fitur ini hanya ada di macOS/ChromeOS. | Satu library JavaScript pembaca QR. | CONFIRMED | Session 6 |
| R-05 | Jam scan berasal dari laptop, sehingga bisa salah atau diubah. | Selisih jam laptop terhadap server disimpan saat data dimuat. Server memvalidasi jam saat sinkron. Laptop kiosk memakai akun Windows non-admin. | RECOMMENDATION | Session 4 dan 6 |
| R-06 | Scan yang belum tersinkron bisa hilang bila laptop rusak atau data browser terhapus. | Persistent storage, penghitung "belum tersinkron", dan sinkron otomatis. | RECOMMENDATION | Session 6 |
| R-07 | Data dan foto siswa (data anak) tersimpan di laptop stasiun scan. | Laptop dan profil browser khusus kiosk. Data dihapus saat perangkat tidak lagi dipakai. | RECOMMENDATION | Session 9 |
| R-08 | Halaman kiosk harus bisa dibuka ulang tanpa internet, misalnya setelah laptop restart. | Service Worker untuk aset halaman kiosk. | RECOMMENDATION | Session 6 |
| R-09 | Kiriman sinkron bisa terulang, dan satu siswa bisa scan di beberapa stasiun. | ID unik per scan dari laptop, ditambah aturan penggabungan scan. | RECOMMENDATION; aturan OPEN (OQ-04) | Session 4 |
| R-10 | Endpoint sinkron bisa disalahgunakan untuk mengirim presensi palsu. | Hanya untuk akun stasiun yang login, dilindungi CSRF, dan divalidasi server. | RECOMMENDATION | Session 9 |
| R-11 | Zona waktu default CodeIgniter adalah `UTC`, dan zona waktu server hosting bisa berbeda dari sekolah. | Zona waktu aplikasi dan database diset eksplisit. | CONFIRMED (default CI4); zona sekolah OPEN (OQ-05) | Session 4 dan 6 |
| R-12 | NISN bisa diawali nol, dan Excel sering membuang nol di depan. | NISN disimpan sebagai teks 10 digit, `id` internal menjadi primary key, dan import divalidasi per baris. | RECOMMENDATION | Session 5 |
| R-13 | Status Alpa salah bila dihitung sebelum semua data masuk. | Alpa dihitung saat data ditampilkan, dan menjadi final setelah sesi masuk ditutup. | RECOMMENDATION; mekanisme tutup sesi OPEN (OQ-07) | Session 4 |
| R-14 | Riwayat rekap rusak saat kenaikan kelas bila siswa hanya punya satu kolom kelas. | Penempatan siswa ke rombel dicatat per tahun ajaran. | RECOMMENDATION | Session 5 |
| R-15 | Keterbatasan hosting terkait document root dan cron. | Pastikan document root bisa diarahkan ke `public/` dan cron tersedia sebelum memilih hosting. | RECOMMENDATION | Session 6 (OQ-09) |
| R-16 | Volume WhatsApp tinggi. Default scan masuk berarti ±500–1.000 pesan setiap pagi. Jeda dan kuota provider membatasi kecepatan kirim, dan nomor bisa diblokir. | Pesan dikirim lewat antrean (outbox) dan proses terjadwal, lalu diuji di R2. Risiko nomor diblokir diterima pengguna. | DECISION (risiko blokir diterima); RECOMMENDATION (antrean) | Sebelum R2 (OQ-10) |
| R-17 | Data siswa termasuk data anak, dan surat sakit termasuk data kesehatan. Keduanya data pribadi spesifik menurut UU 27/2022 tentang Pelindungan Data Pribadi. | File unggahan disimpan di luar `public/` dengan akses terbatas. Halaman publik tanpa data individu. Isi flyer diputuskan lewat OQ-11. | RECOMMENDATION | Session 9 |
| R-18 | XLSX, PDF, dan QR membutuhkan library PHP via Composer, padahal framework saat ini dipasang tanpa Composer. Repository juga belum punya `.gitignore`, sehingga `.env` (password database) dan isi `writable/` bisa ikut ter-push. | Pindah ke Composer appstarter. `.gitignore` dibuat sebelum commit kode pertama. | RECOMMENDATION (keputusan final OQ-09) | Session 6 |
| R-19 | Flyer dibuat di browser dari template HTML menjadi PNG. | Kemungkinan butuh satu library JavaScript kecil. | RECOMMENDATION | Session 6 dan 7 |
| R-20 | Volume data ±400 ribu catatan scan per tahun (±1.000 siswa × 2 scan × ±200 hari). | Index yang tepat. Volume ini ringan untuk MySQL. | RECOMMENDATION | Session 5 |

## 8. Asumsi dan pertanyaan terbuka

### 8.1 Asumsi

| ID | Asumsi |
|---|---|
| A-01 | Sekolah berjenjang SMP, berdasarkan nama "spensada" dan penggunaan kartu OSIS. |
| A-02 | Daftar masalah inti di §2 sudah tepat. |
| A-03 | Setiap siswa aktif memiliki NISN 10 digit yang valid, dan satu siswa memiliki satu kartu. |
| A-04 | Stasiun scan memakai laptop Windows dengan Chrome atau Edge versi terbaru. |
| A-05 | Sistem menggantikan sepenuhnya pencatatan kehadiran di kertas, Excel, dan WhatsApp (tujuan T-2). |

### 8.2 Pertanyaan terbuka

| ID | Pertanyaan | Dibahas di |
|---|---|---|
| OQ-01 | Nama resmi sekolah, jenjang, dan nama produk. | Review dokumen ini |
| OQ-02 | Sub-peran staf (wali kelas, guru piket, BK, kepala sekolah, TU) dan hak akses masing-masing. | Session 3 |
| OQ-03 | Aturan jam: jam masuk, batas terlambat, jam pulang, pulang lebih awal, scan ganda, dan hari sekolah dalam seminggu. | Session 4 |
| OQ-04 | Aturan penggabungan scan dari beberapa stasiun, dan prioritas antara scan, presensi manual, dan izin/sakit. | Session 4 |
| OQ-05 | Zona waktu sekolah (WIB, WITA, atau WIT). | Session 4 |
| OQ-06 | Penerimaan risiko QR palsu dan kartu hilang (R-02). | Session 4 |
| OQ-07 | Cara menutup sesi masuk/pulang: manual oleh petugas atau otomatis pada jam tertentu. | Session 4 |
| OQ-08 | Jumlah stasiun scan (laptop) dan lokasinya. | Session 3 |
| OQ-09 | Jenis hosting (shared atau VPS) dan cara instalasi framework (Composer). | Session 6 |
| OQ-10 | Provider gateway WhatsApp. | Sebelum R2 |
| OQ-11 | Laporan apa saja dan format masing-masing (matriks laporan × format); isi flyer (angka saja atau dengan nama siswa). | Session 5 (ditulis di `13-reporting-import-export.md`) |
| OQ-12 | Format nama file foto siswa yang ada saat ini. | Session 5 (ditulis di `13-reporting-import-export.md`) |
| OQ-13 | Desain kartu siswa baru: mengikuti kartu lama atau desain baru. | Session 7 |
| OQ-14 | Cara pembuatan akun siswa dan password awal. | Session 3 |

## 9. Glosarium

Istilah di bawah wajib dipakai secara konsisten di seluruh dokumentasi dan antarmuka. Nama teknis (tabel, kolom, route) ditetapkan di Session 5–8 dan dipetakan ke istilah ini.

| Istilah | Definisi |
|---|---|
| NISN | Nomor Induk Siswa Nasional: 10 digit angka, unik per siswa. Isi QR di kartu OSIS. Disimpan sebagai teks karena bisa diawali nol. |
| Kartu OSIS | Kartu identitas siswa yang sudah tercetak, memuat QR berisi NISN polos. |
| Presensi | Pencatatan kehadiran siswa per hari sekolah, terdiri dari presensi masuk dan presensi pulang. |
| Scan | Satu kali pembacaan QR di kiosk. Satu scan menghasilkan satu catatan scan. |
| Stasiun scan | Perangkat di titik scan (laptop dengan webcam, opsional scanner QR USB) yang login memakai akun stasiun. |
| Kiosk | Halaman aplikasi yang berjalan di stasiun scan untuk membaca QR dan menampilkan hasil scan. |
| Akun stasiun | Akun khusus untuk stasiun scan dengan hak minimal: memuat data kiosk, mencatat scan, dan sinkron. |
| Petugas | Staf yang mengawasi stasiun scan. Sub-peran yang bertugas mengikuti OQ-02. |
| Local-first | Pola kerja kiosk: data siswa dimuat ke laptop lebih dulu, scan divalidasi dan dicatat di laptop, lalu dikirim ke server. |
| Sinkron | Pengiriman catatan scan dari stasiun scan ke server. Berjalan otomatis saat online dan bisa dipicu manual. |
| Sesi masuk / sesi pulang | Rentang waktu penerimaan scan masuk dan scan pulang dalam satu hari sekolah. Aturannya mengikuti OQ-03 dan OQ-07. |
| Hadir | Status harian: siswa scan masuk, atau dicatat lewat presensi manual, sebelum batas terlambat. |
| Terlambat | Status harian: scan masuk setelah batas terlambat. |
| Izin | Status harian: tidak hadir dengan izin yang sudah disetujui. |
| Sakit | Status harian: tidak hadir karena sakit, dengan keterangan yang sudah disetujui. |
| Alpa | Status harian: tidak hadir tanpa keterangan. Syaratnya: hari sekolah, siswa aktif, tanpa scan masuk, dan tanpa izin/sakit yang disetujui. |
| Belum hadir | Keadaan sementara pada hari berjalan, sebelum sesi masuk ditutup. Bukan status final. |
| Pulang lebih awal | Kejadian: scan pulang sebelum jam pulang. |
| Tidak scan pulang | Kejadian: tidak ada scan pulang setelah sesi pulang ditutup. |
| Presensi manual | Presensi yang diinput staf, misalnya karena siswa lupa kartu, kartu rusak, atau kiosk terganggu. |
| Log perubahan presensi | Catatan setiap perubahan data presensi oleh staf: siapa, kapan, nilai lama, nilai baru, dan alasan. |
| Pengajuan izin/sakit | Permohonan izin atau sakit dari siswa lewat portal, yang menunggu verifikasi staf. |
| Tahun ajaran | Periode akademik sekolah, umumnya Juli–Juni, terdiri dari dua semester. |
| Rombel | Rombongan belajar: kelompok kelas tempat siswa terdaftar pada satu tahun ajaran, misalnya 7A. Label di antarmuka ditetapkan di Session 7. |
| Tingkat | Jenjang kelas dalam satu sekolah, misalnya 7, 8, dan 9. |
| Hari sekolah | Tanggal kegiatan belajar. Tidak termasuk hari libur di kalender sekolah. |
| Kalender sekolah | Daftar hari libur dan hari non-sekolah yang diatur admin. |
| Dashboard hari ini | Halaman pantauan kehadiran pada hari berjalan, per rombel. |
| Rekap | Ringkasan kehadiran per rombel atau per siswa untuk rentang tanggal tertentu. |
| Rekap agregat | Jumlah kehadiran per rombel tanpa nama atau data individu siswa. Satu-satunya data kehadiran yang tampil di halaman publik. |
| Flyer kehadiran | Gambar PNG ringkasan kehadiran (per rombel atau total) yang dibuat di browser dan diunduh staf untuk dibagikan. |
| Notifikasi WA | Pesan WhatsApp otomatis ke orang tua/wali siswa. |
| Jenis kejadian notifikasi | Tujuh pemicu notifikasi: scan masuk, scan pulang, terlambat, tidak hadir, tidak scan pulang, izin, dan pulang lebih awal. |
| Gateway WA | Layanan pihak ketiga tidak resmi yang menyediakan API untuk mengirim pesan WhatsApp. |
| Outbox WA | Antrean pesan WhatsApp yang menunggu dikirim oleh proses terjadwal. |
| Template pesan | Teks pesan per jenis kejadian notifikasi, dengan isian otomatis seperti nama siswa dan jam. |
| R1 / R2 / R3 | Tahap rilis versi pertama (lihat §6.1). |
| Session 1–11 | Tahap diskusi discovery untuk menyusun dokumentasi (lihat §10.2). Tidak sama dengan sesi masuk/pulang. |

## 10. Peta dokumen dan progres

### 10.1 Dokumen

| Dokumen | Isi | Sesi | Status |
|---|---|---|---|
| `00-project-overview.md` | Gambaran proyek (dokumen ini) | Session 1–2 | Draft 0.1 |
| `01-product-requirements.md` | Kebutuhan fungsional dan non-fungsional | Session 2 | Draft 0.1 |
| `02-user-roles-and-permissions.md` | Role dan permission | Session 3 | Belum dibuat |
| `03-user-flow.md` | Alur pengguna | Session 3 | Belum dibuat |
| `04-feature-specification.md` | Spesifikasi fitur rinci | Session 3–4 | Belum dibuat |
| `05-business-rules.md` | Aturan bisnis | Session 4 | Belum dibuat |
| `06-database-design.md` | Desain database | Session 5 | Belum dibuat |
| `07-system-architecture.md` | Arsitektur sistem | Session 6 | Belum dibuat |
| `08-ui-ux-design-system.md` | Sistem desain UI/UX | Session 7 | Belum dibuat |
| `09-page-and-route-specification.md` | Halaman dan route | Session 8 | Belum dibuat |
| `10-api-specification.md` | API, termasuk sinkron kiosk | Session 8 | Belum dibuat |
| `11-validation-and-error-handling.md` | Validasi dan penanganan error | Session 9 | Belum dibuat |
| `12-security.md` | Keamanan | Session 9 | Belum dibuat |
| `13-reporting-import-export.md` | Laporan, import, dan export | Session 5 | Belum dibuat |
| `14-development-roadmap.md` | Roadmap pengembangan | Session 10 | Belum dibuat |
| `15-implementation-phases.md` | Dokumen fase implementasi | Session 11 | Belum dibuat |

### 10.2 Progres sesi discovery

| Sesi | Topik | Status |
|---|---|---|
| 1 | Project Discovery | Selesai |
| 2 | Product & Feature Definition | Selesai, menunggu review dokumen |
| 3 | User Roles & User Flow | Berikutnya |
| 4 | Business Rules | Belum |
| 5 | Database Architecture | Belum |
| 6 | System Architecture | Belum |
| 7 | UI/UX & Design System | Belum |
| 8 | Routes / Pages / API | Belum |
| 9 | Security / Validation / Error Handling | Belum |
| 10 | Development Roadmap | Belum |
| 11 | Implementation Phase Documents | Belum |

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
