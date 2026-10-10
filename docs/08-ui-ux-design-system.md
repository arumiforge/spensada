# Spensada — UI/UX & Design System

| Item | Nilai |
|---|---|
| Versi | 0.4 (draft, menunggu review) |
| Tanggal | 2026-10-10 |
| Sumber | Discovery Session 7 (UI/UX & Design System). Diperbarui dengan keputusan Session 8 (Routes / Pages / API, §2.4), Session 9 (Validation, Error Handling & Security, §2.5), dan keputusan pemilik proyek 2026-10-10 (Bootstrap 5, §2.6). |
| Bergantung pada | [00-project-overview.md](00-project-overview.md): label status, glosarium, risiko (`R-xx`), dan pertanyaan terbuka (`OQ-xx`). [01-product-requirements.md](01-product-requirements.md): requirement (`FR-*`, `NFR-*`) dan batasan (`C-*`). [02-user-roles-and-permissions.md](02-user-roles-and-permissions.md): hak akses (`HA-*`), area, dan halaman awal. [03-user-flow.md](03-user-flow.md): alur (`UF-*`). [04-feature-specification.md](04-feature-specification.md): fitur (`FS-*`), ketentuan umum (§4), dan catatan antarmuka awal. [05-business-rules.md](05-business-rules.md): aturan bisnis (`BR-*`). [06-database-design.md](06-database-design.md): kode nilai. [07-system-architecture.md](07-system-architecture.md): library, aset, kiosk, foto, dan pembaruan halaman (`ARS-*`). [13-reporting-import-export.md](13-reporting-import-export.md): laporan, PDF, dan flyer. |
| Dokumen terkait | `08-contoh-tampilan.html` (contoh visual dokumen ini, di folder yang sama). [09-page-and-route-specification.md](09-page-and-route-specification.md): halaman, route, menu, dan label kode di halaman admin. [10-api-specification.md](10-api-specification.md): fragmen dan bantuan formulir. [11-validation-and-error-handling.md](11-validation-and-error-handling.md): teks validasi per isian dan pesan galat. [12-security.md](12-security.md): PIN petugas, sesi, CSP, dan header berkas. |

Dokumen ini menetapkan sistem desain Spensada: prinsip, warna, huruf, ikon, foto, komponen, tata letak panel staf, portal siswa, dan kiosk, teks layar dan label, format tanggal dan angka, dokumen cetak, flyer, serta aksesibilitas dan dukungan perangkat. Dokumen ini menjawab R-19 dan sebagian OQ-13.

Daftar halaman, route, dan menu ada di `09`, teks validasi serta pesan galat rinci ada di `11`, dan ketentuan keamanan ada di `12`. Dokumen ini hanya menetapkan pola tampilan dan teks yang dibutuhkan dokumen tersebut.

## 1. Cara membaca dokumen ini

- **ID.** Aturan tampilan memakai ID `UI-<NN>`. ID tidak pernah dinomori ulang. Aturan yang batal ditandai `DEPRECATED`.
- **Status.** Label status mengikuti `00`. Keputusan dari ronde diskusi Session 7 berstatus DECISION. Rincian yang tidak dibahas di ronde berstatus RECOMMENDATION, dan menjadi arah kerja Session 8–11 serta implementasi sampai dikonfirmasi atau diganti.
- **Nama teknis.** Nama file, token CSS, kelas CSS, dan view di dokumen ini adalah usulan (RECOMMENDATION). Nama tabel dan kolom mengikuti `06`, dan route ditetapkan di `09`.
- **Contoh visual.** File `08-contoh-tampilan.html` menampilkan keputusan dokumen ini dan dapat dibuka langsung di browser tanpa internet. Bila contoh berbeda dengan dokumen ini, dokumen ini yang berlaku (UI-77).
- **Warna.** Nilai warna ditulis dalam heksadesimal sRGB. Kontras dihitung dengan rumus WCAG 2.2.
- **Istilah.** Dokumen tetap memakai istilah glosarium (`00` §9), misalnya "rombel". Label yang tampil di layar mengikuti §9, misalnya "Kelas".
- **Contoh.** Contoh tanggal dan jam mengikuti `04` §1: hari ini Selasa, 13 Oktober 2026.

## 2. Keputusan Session 7

### 2.1 Keputusan tampilan

| Topik | Keputusan | Rujukan | Status |
|---|---|---|---|
| Label rombel | Rombel tampil sebagai "Kelas", misalnya "Kelas 7A". Tingkat tampil sebagai "Tingkat", misalnya "Tingkat 7". Dokumen dan nama teknis tetap memakai "rombel". | `00` §9, FS-MD-03, UI-51 | DECISION |
| Cara membangun tampilan | CSS sendiri dengan token (variabel CSS), tanpa library dan tanpa build. Ikon memakai subset Lucide (lisensi ISC) yang disalin ke server sebagai satu file SVG. Kiosk juga memakai CSS sendiri. Diubah keputusan pemilik 2026-10-10 (§2.6): panel, portal, dan halaman publik memakai Bootstrap 5. Token, ikon Lucide, tanpa build, dan CSS sendiri untuk kiosk tetap berlaku. | ARS-10, ARS-11, ARS-19, NFR-16, UI-08, UI-20 | DECISION (diubah §2.6) |
| Huruf | Plus Jakarta Sans (SIL OFL), disalin ke server dan dipakai di aplikasi, flyer, dan PDF. Font didaftarkan di mPDF. | UI-13 s.d. UI-15 | DECISION |
| Warna utama | Biru dongker. Admin tidak dapat mengubah tema (FS-MD-01). | UI-09 | DECISION |
| Palet status | Palet A: Hadir hijau, Terlambat oranye tua, Izin biru, Sakit ungu, Dispensasi toska, Alpa merah, dan Belum hadir abu-abu. Status selalu disertai ikon, huruf singkat, dan teks. | FS-PRS-05, UI-01, UI-12 | DECISION |
| Tata letak kiosk | Kamera dan jadwal sesi di kiri, hasil scan di kanan dengan lebar dua pertiga layar. | FS-KIO-02, UI-39 | DECISION |
| Bunyi kiosk | Tiga nada sintetis dari Web Audio API, tanpa file suara: berhasil, peringatan, dan galat. Scan yang tercatat, termasuk Terlambat dan pulang lebih awal, berbunyi berhasil. Scan ganda berbunyi peringatan. Scan yang ditolak berbunyi galat. | FS-KIO-02, FR-KIO-04, UI-41, UI-45 | DECISION |
| Lama hasil scan tampil | 3 detik untuk scan yang tercatat dan scan ganda. 6 detik untuk scan yang ditolak. Scan berikutnya tetap langsung menggantikan hasil (FS-KIO-02 butir 8). | UF-10 langkah 7, UI-41 | DECISION |
| Navigasi | Panel: menu samping di layar ≥1024 px, yang menjadi menu lipat di tablet dan ponsel. Menu dikelompokkan dan hanya memuat halaman yang boleh dibuka role pengguna. Portal siswa: menu bawah di ponsel (Riwayat, Izin, Akun), dan menu atas di layar lebar. | `02` §8, NFR-11, UI-29, UI-31, UI-35 | DECISION |
| Sapaan | Staf disapa "Anda". Siswa disapa "kamu" di portal siswa dan kiosk. Slip dan flyer memakai bahasa formal tanpa "kamu". | UI-49 | DECISION |
| Format tanggal dan jam | Tanggal di tabel "13 Okt 2026", dan di judul "Selasa, 13 Oktober 2026". Jam memakai format 24 jam dengan titik: "07.00", atau "07.00.59" bila detik diperlukan. CSV tetap memakai format `13` IE-09. | `04` §4.8, UI-53 | DECISION |
| Foto kecil | Ukuran ketiga 120×160 px dibuat saat foto disimpan, khusus untuk daftar. Foto menjadi tiga ukuran: standar, kiosk, dan kecil. | ARS-53, FS-LAP-02, FS-PRS-09, UI-22 | DECISION |
| Paket nilai tampilan | Nilai di §2.2. | UI-19, UI-23, UI-26, UI-27 | DECISION |
| Slip akun | 8 slip per halaman A4 (ukuran A7, 2 kolom × 4 baris), dengan garis potong putus-putus, password dengan huruf berukuran besar, dan petunjuk login lengkap. | FS-AKN-05, `02` §7.2, UI-58 | DECISION |
| Template PDF (R2) | Kop berisi logo, nama, dan alamat sekolah dengan garis bawah. Rekap per rombel dan rekap rapor memuat blok tanda tangan: tempat dan tanggal cetak, "Wali Kelas 7A", ruang tanda tangan, nama wali kelas, dan NIP. NIP disimpan sebagai isian opsional di akun staf. Tempat diambil dari isian baru "Kota/kabupaten" di identitas sekolah. Kaki halaman memuat pembuat, waktu cetak, dan nomor halaman. | `13` IE-06, IE-10, UI-60, UI-61 | DECISION |
| Cara membuat flyer | Canvas API tanpa library. Flyer digambar di canvas selebar 1080 px dengan Plus Jakarta Sans, lalu diunduh sebagai PNG. Pratinjau memakai canvas yang sama. | R-19, `13` LP-08, ARS-10, UI-65 | DECISION |
| Format flyer | Potret 4:5, 1080×1350 px, untuk flyer per rombel dan flyer total sekolah. | `13` LP-08, UI-66 | DECISION |
| Desain kartu (OQ-13) | Kartu siswa baru dan kartu pengganti mengikuti tata letak kartu OSIS yang sekarang dipakai. Sekolah menyerahkan contoh kartu sebelum R3, lalu desain dirinci saat itu. OQ-13 tetap terbuka sampai contoh diterima. | OQ-13, FR-KRT-01, UI-62 | DECISION |
| Cetak kartu (R3) | PDF A4 dari mPDF berisi 10 kartu (2 × 5) dengan garis potong, untuk dicetak di kertas foto atau lembar PVC lalu dilaminasi. QR dibuat di server dengan chillerlan/php-qrcode 6 (lisensi MIT atau Apache-2.0). | FS-KRT-01, ARS-10, UI-63 | DECISION |
| Contoh visual | Halaman pratinjau Session 7 diperbarui menjadi contoh tampilan final dan disimpan sebagai `docs/08-contoh-tampilan.html`, tanpa CDN. Dokumen ini tetap menjadi sumber kebenaran. | UI-77 | DECISION |

### 2.2 Nilai yang dipastikan

Tabel ini memuat nilai dari `04` §14.2, `07` §19, dan `13` §9 yang dijadwalkan di Session 7. Semuanya DECISION dari ronde diskusi Session 7.

| Nilai | Ditetapkan | Fitur |
|---|---|---|
| Lama hasil scan tampil | 3 detik (tercatat dan scan ganda), 6 detik (ditolak) | FS-KIO-02 |
| Jenis bunyi | Berhasil, peringatan, dan galat, dari Web Audio API | FS-KIO-02 |
| Ukuran tampil foto | Kiosk: tinggi ±50% layar, paling besar 300×400 px dari foto kiosk. Presensi manual dan tinjauan scan: 240×320 px dari foto standar. Profil siswa: 150×200 px dari foto standar. Daftar: 36×48 px dari foto kecil 120×160 px. | FS-MD-07, FS-KIO-01, FS-PRS-06, FS-KIO-06, FS-LAP-02 |
| Jumlah slip per halaman A4 | 8 | FS-AKN-05 |
| Rentang default rekap per rombel | Bulan berjalan | FS-LAP-03 |
| Periode default riwayat siswa | Bulan berjalan | FS-LAP-04 |
| Format tanggal dan jam | "13 Okt 2026", "Selasa, 13 Oktober 2026", dan "07.00" | `04` §4.8 |
| Awal minggu di kalender | Senin | FS-PRS-03, FS-LAP-04 |
| Daftar panjang | 50 baris per halaman | Semua daftar |
| Teks dasar | 16 px | Panel, portal, halaman publik |
| Target sentuh di ponsel | Paling kecil 44×44 px | Panel, portal, halaman publik |
| Label rombel | "Kelas" | `00` §9 |
| Warna dan ikon status | Palet A, selalu dengan ikon, huruf singkat, dan teks. Nilai warna dan nama ikon di §4.2 berstatus RECOMMENDATION (UI-12). | FS-PRS-05 |
| Cara membuat flyer | Canvas API | FS-LAP-06 |

### 2.3 Catatan antarmuka awal di `04`

Setiap fitur di `04` memuat catatan antarmuka awal yang belum menjadi keputusan. Catatan itu diterapkan di dokumen ini sebagai RECOMMENDATION, kecuali bagian yang sudah diputuskan di §2.1. Pemetaannya ada di §16.2.

### 2.4 Keputusan Session 8

Keputusan Session 8 yang berdampak ke tampilan. Rinciannya ada di `09` §2.

| Topik | Keputusan | Rujukan | Status |
|---|---|---|---|
| Menu panel dan portal | Isi menu final ada di `09` §4, termasuk menu "Kelas saya" di bawah Dashboard bagi wali kelas, yang membuka daftar presensi rombelnya hari ini. | UI-31, `09` §4, HAL-LAP-03 | DECISION ("Kelas saya"); RECOMMENDATION (isi menu lain) |
| Pencarian siswa | Formulir cari biasa, ditambah hasil langsung saat mengetik bila JavaScript aktif. | UI-28, `09` RT-14, `10` EP-MD-01 | DECISION |
| Status stasiun | Halaman status stasiun menampilkan versi kode kiosk dan keadaan penyimpanan permanen. Stasiun juga disorot bila memiliki scan galat atau penyimpanan permanennya belum aktif. | §4.2, `09` HAL-KIO-02 | DECISION (kolom); RECOMMENDATION (sorotan) |
| Kata di alamat | Alamat memakai kata `kelas`, sama dengan label layar. | UI-51, `09` RT-03 | DECISION |
| Label kode di halaman admin | Label kode yang belum ada di §9.2 ditetapkan di `09` §14. | §9.2 | RECOMMENDATION |

### 2.5 Keputusan Session 9

Keputusan Session 9 yang berdampak ke tampilan. Daftar lengkapnya ada di `12` §2.1.

| Topik | Keputusan | Rujukan | Status |
|---|---|---|---|
| PIN petugas | Satu PIN 6 digit untuk semua stasiun, diatur admin. PIN wajib untuk logout akun stasiun dan hapus data lokal di kiosk. | UI-48, `12` SEC-21, SEC-22 | DECISION |
| Pembatasan login | Pesan kunci menyebut jam login dapat dicoba lagi, atau meminta menghubungi wali kelas atau admin untuk kunci 24 jam. | UI-38, `11` §5.1, `12` SEC-08 | DECISION |
| Masa sesi | Sesi staf dan siswa berakhir setelah 8 jam tanpa aktivitas, atau 7 hari sejak login, dan tetap berjalan setelah browser ditutup. Halaman login mengingatkan logout di komputer bersama. | UI-38, `12` SEC-12 | DECISION |
| Format dan ukuran unggahan | Paket 10 MB: foto, logo, dan lampiran paling besar 10 MB per file. Pesannya di `11` VAL-28. | §5 (unggah file), `12` SEC-49 | DECISION |
| Log aktivitas | Admin melihat log aktivitas, termasuk akses lampiran, di halaman log aktivitas (`HA-AKN-08`). Labelnya di `09` §14. | §9.2, `09` HAL-AKN-09, `12` SEC-62 | DECISION (OQ-17); RECOMMENDATION (isi halaman) |
| Tanpa atribut `style` | CSP tidak mengizinkan gaya di dalam HTML, sehingga atribut `style` tidak dipakai sama sekali. | UI-74, `12` SEC-36 | RECOMMENDATION |

### 2.6 Keputusan pemilik proyek 2026-10-10

| Topik | Keputusan | Rujukan | Status |
|---|---|---|---|
| Library tampilan | Panel, portal, akun, halaman galat, dan halaman publik memakai Bootstrap 5.3 dari file `.min` yang disalin ke server sendiri, tanpa CDN dan tanpa build. Satu file CSS aplikasi, `spensada.css`, berisi penimpaan variabel Bootstrap dan komponen aplikasi, dengan token `08` di file kecil `token.css`. Kiosk tidak memakai Bootstrap dan tetap memakai CSS sendiri. | UI-08, UI-28, UI-73, UI-74, `07` ARS-10, ARS-19 | DECISION |
| Batas ukuran | Batas ukuran CSS dan JavaScript per halaman diukur setelah kompresi gzip, bukan sebelum kompresi. | UI-71 | DECISION (cara ukur); RECOMMENDATION (angka) |

## 3. Prinsip

| ID | Aturan | Status |
|---|---|---|
| UI-01 | **Status tidak hanya warna.** Setiap status, penanda, dan hasil scan ditampilkan dengan warna, ikon, dan teks. Di tempat sempit, teks boleh diganti huruf singkat (§4.2), asalkan keterangannya tersedia di halaman yang sama. Alasannya, sebagian petugas buta warna merah-hijau, dan dokumen sering dicetak hitam-putih. | DECISION (status selalu dengan warna, ikon, huruf singkat, dan teks); RECOMMENDATION (huruf singkat menggantikan teks di tempat sempit dengan keterangan di halaman yang sama, serta penerapan pada penanda dan hasil scan) |
| UI-02 | **Terbaca dari jauh di kiosk.** Hasil scan terbaca dari jarak 1–2 meter, dan foto cukup besar untuk dicocokkan dengan wajah siswa (FS-KIO-02, R-02). | RECOMMENDATION |
| UI-03 | **Data seperlunya.** Tampilan hanya memuat data yang boleh dilihat pengguna (`02` §5). Staf tanpa role khusus hanya melihat angka. Siswa tidak melihat penanda, nama staf, atau nama stasiun (FS-LAP-04). Kiosk hanya menampilkan foto, nama, rombel, jenis, status, dan jam (FR-KIO-04). Flyer dan halaman publik hanya memuat angka (`13` LP-08, FR-INF-05). | RECOMMENDATION |
| UI-04 | **Tetap bekerja tanpa JavaScript.** Halaman dan formulir panel, portal, dan halaman publik bekerja tanpa JavaScript (ARS-19). JavaScript hanya menambah kenyamanan, misalnya pembaruan berkala, pratinjau unggahan, dan dialog konfirmasi. Pengecualiannya kiosk dan flyer, yang memang membutuhkan JavaScript. | RECOMMENDATION |
| UI-05 | **Ringan untuk ponsel.** Siswa dan wali kelas sering memakai ponsel dengan data seluler. Batas ukuran halaman ada di UI-71. | RECOMMENDATION |
| UI-06 | **Satu sumber label.** Label kode nilai, status, dan istilah layar diambil dari satu tempat (UI-75), sehingga layar, file export, PDF, dan flyer memakai kata yang sama. | RECOMMENDATION |
| UI-07 | **Satu sistem untuk semua area.** Panel, portal, halaman publik, kiosk, slip, PDF, dan flyer memakai token warna, huruf, dan ikon yang sama. Yang berbeda hanya tata letaknya. | RECOMMENDATION |

## 4. Fondasi visual

### 4.1 Warna

| ID | Aturan | Status |
|---|---|---|
| UI-08 | **Token warna.** Semua warna ditulis sebagai variabel CSS di `:root` dalam `token.css` (§13). Komponen aplikasi hanya memakai token, bukan nilai warna langsung. Warna Bootstrap disamakan dengan token dengan menimpa variabel CSS Bootstrap (`--bs-*`) di `spensada.css`, termasuk variabel per komponen seperti `--bs-btn-bg` dan `--bs-btn-hover-bg` untuk `.btn-primary`, karena tanpa build warna Bootstrap tidak dapat diubah lewat Sass. File Bootstrap sendiri tidak diubah. | DECISION (token; Bootstrap, §2.6); RECOMMENDATION (nama dan nilai token, cara menimpa) |
| UI-09 | **Warna utama.** Biru dongker dipakai untuk menu samping panel, bilah atas portal, tombol utama, tautan, dan kepala flyer serta PDF. Warna utama tidak dipakai untuk menandai status. Admin tidak dapat mengubah warna atau tema (FS-MD-01). | DECISION (biru dongker); RECOMMENDATION (pemakaian) |
| UI-10 | **Tema terang saja.** Panel, portal, dan halaman publik hanya memakai tema terang di R1–R3. Kiosk memakai latar gelap dengan blok hasil berwarna (§7). | RECOMMENDATION |
| UI-11 | **Kontras.** Teks memakai kontras paling kecil 4,5:1 terhadap latarnya. Garis isian formulir, ikon tanpa teks, dan cincin fokus paling kecil 3:1 (WCAG 2.2 AA). Semua pasangan warna yang dipakai di tabel di bawah memenuhi syarat itu. Di latar gelap, yaitu menu samping dan kiosk, cincin fokus memakai `--fokus-terang`, karena `--fokus` hanya 2,37:1 terhadap warna utama. | RECOMMENDATION |

Token warna dasar (UI-08):

| Token | Nilai | Pemakaian | Kontras |
|---|---|---|---|
| `--warna-utama` | `#1B3A6B` | Menu samping, tombol utama, tautan, kepala flyer dan PDF | 11,27:1 terhadap putih |
| `--warna-utama-gelap` | `#132A4F` | Tombol utama saat ditekan, bilah atas kiosk | 14,27:1 terhadap putih |
| `--warna-utama-muda` | `#E7EDF6` | Baris terpilih, baris rombel milik wali kelas, latar chip netral | Warna utama di atasnya 9,57:1, teks utama 13,30:1 |
| `--latar` | `#F5F7FA` | Latar halaman | — |
| `--permukaan` | `#FFFFFF` | Kartu, tabel, dan isian | — |
| `--garis` | `#D9DFE7` | Garis pemisah dan tepi kartu | Hiasan, tanpa syarat kontras |
| `--garis-isian` | `#8A94A3` | Tepi isian formulir, kotak centang | 3,07:1 terhadap putih |
| `--teks` | `#1B2430` | Teks utama | 15,65:1 terhadap putih, 14,58:1 terhadap latar |
| `--teks-redup` | `#5B6676` | Teks keterangan | 5,82:1 terhadap putih, 5,42:1 terhadap latar |
| `--fokus` | `#2F6FDB` | Cincin fokus keyboard di latar terang | 4,75:1 terhadap putih, 4,43:1 terhadap latar |
| `--fokus-terang` | `#FFFFFF` | Cincin fokus keyboard di latar gelap | 11,27:1 terhadap warna utama |
| `--sukses`, `--info`, `--peringatan`, `--bahaya` | Sama dengan warna pekat Hadir, Izin, Terlambat, dan Alpa (§4.2) | Bilah peringatan dan pesan kilat | §4.2 |

### 4.2 Warna dan ikon status

| ID | Aturan | Status |
|---|---|---|
| UI-12 | **Palet A.** Setiap status memakai tiga warna: pekat untuk latar berteks putih (kiosk, flyer, ubin pekat), muda untuk latar chip, dan teks untuk tulisan di atas warna muda. Ikon memakai nama Lucide (UI-20). Huruf singkat dipakai di kalender, tabel sempit, dan PDF. | DECISION (palet A; selalu dengan ikon, huruf singkat, dan teks; teks putih di atas warna pekat memenuhi WCAG AA); RECOMMENDATION (nilai warna dan nama ikon) |

Status harian dan keadaan sementara (BR-STS-01):

| Status | Kode `06` | Pekat | Muda | Teks | Putih di atas pekat | Ikon | Huruf |
|---|---|---|---|---|---|---|---|
| Hadir | `hadir` | `#1E7F4F` | `#E3F3EA` | `#14593A` | 5,00:1 | `circle-check` | H |
| Terlambat | `terlambat` | `#B45309` | `#FDF0DB` | `#7C3A06` | 5,02:1 | `clock-alert` | T |
| Izin | `izin` | `#2459C6` | `#E4ECFB` | `#1A428F` | 6,33:1 | `file-text` | I |
| Sakit | `sakit` | `#7B3FB8` | `#F1E8FA` | `#5A2A8A` | 6,47:1 | `thermometer` | S |
| Dispensasi | `dispensasi` | `#0E7C7B` | `#DDF3F2` | `#0A5A59` | 5,01:1 | `award` | D |
| Alpa | `alpa` | `#B42318` | `#FCE6E4` | `#8A1A12` | 6,57:1 | `circle-x` | A |
| Belum hadir | — (status kosong, `06` §11.3); token dan kelas CSS memakai `belum` | `#64748B` | `#EEF1F5` | `#475569` | 4,76:1 | `circle-dashed` | – |

Teks setiap status di atas warna mudanya berkontras 6,69:1 sampai 8,28:1. Token CSS-nya `--status-<kode>`, `--status-<kode>-muda`, dan `--status-<kode>-teks`, misalnya `--status-hadir-muda`.

Tanda lain:

| Tanda | Tampilan | Rujukan |
|---|---|---|
| Penanda | Ikon `triangle-alert` berwarna oranye tua (`#B45309`) dengan teks: "Izin, tetapi tercatat masuk" atau "Pulang tanpa presensi masuk". Judul kolomnya "Perlu diperiksa". Hanya untuk staf. | BR-STS-07, FS-PRS-05 |
| Kejadian pulang | Chip bergaris netral dengan ikon `log-out`: "Pulang lebih awal" atau "Tidak scan pulang". | BR-STS-05 |
| Dikoreksi | Chip bergaris netral dengan ikon `pencil`: "Dikoreksi". Di portal siswa diikuti alasan koreksi. | FS-LAP-04 |
| Status data izin/sakit/dispensasi | Chip bergaris, bukan chip berisi, agar tidak tertukar dengan status harian: Menunggu (oranye), Disetujui (hijau), Ditolak (merah), Dibatalkan (abu-abu). | FS-IZN-06 |
| Status akun siswa | Teks dengan titik warna: Belum aktif (abu-abu), Aktif (hijau), Nonaktif (merah). | `02` §7.2 |
| Stasiun disorot | Baris berlatar oranye muda dengan ikon dan alasannya (FS-KIO-05 butir 2, label di `09` §14): `wifi-off` untuk tanpa kontak, `cloud-upload` untuk scan belum tersinkron, dan `circle-alert` untuk selisih jam, scan galat, dan penyimpanan permanen belum aktif. Versi kode kiosk yang lama diberi chip netral "Versi lama" tanpa sorotan (`09` HAL-KIO-02). | FS-KIO-05 |
| Belum final | Chip netral "Belum final", atau di file "Data hari ini belum final, dibuat pukul 07.32" (`13` IE-03). | BR-REK-04 |
| Sedang diperbarui | Teks redup "Sedang diperbarui" di dekat judul tabel (ARS-36 butir 5). | ARS-37 |
| Mode darurat | Bilah merah di bawah bilah atas semua halaman panel (UI-30). | FS-PRS-08 |

### 4.3 Huruf

| ID | Aturan | Status |
|---|---|---|
| UI-13 | **Plus Jakarta Sans.** Semua area, flyer, dan PDF memakai Plus Jakarta Sans, karya Tokotype dengan lisensi SIL OFL 1.1. Versi saat dokumen ini ditulis adalah 2.071, berupa variable font dengan ketebalan 200–800. Font disalin ke `public/aset/vendor/plus-jakarta-sans/2.071/` beserta file lisensinya, dalam format WOFF2 untuk subset latin dan latin-ext dengan `unicode-range`. Subset diambil dari paket `@fontsource-variable/plus-jakarta-sans` atau dibuat dengan `pyftsubset` dari file upstream. Ukurannya ±27 KB (latin) dan ±22 KB (latin-ext). Font tidak dimuat dari CDN (ARS-11). | DECISION (font, disalin ke server, dipakai di aplikasi, flyer, dan PDF); RECOMMENDATION (versi, lokasi, dan subset) |
| UI-14 | **Pemakaian di browser.** `@font-face` memakai `font-display: swap`, dengan cadangan `system-ui, "Segoe UI", Roboto, sans-serif`. Ketebalan yang dipakai: 400 untuk teks, 600 untuk label dan judul kecil, 700 untuk judul dan angka, dan 800 untuk hasil kiosk dan angka besar flyer. Angka di tabel, ubin, jam, dan kiosk memakai `font-variant-numeric: tabular-nums`, karena angka bawaan font ini proporsional. Canvas tidak mendukung pengaturan itu, sehingga angka di flyer diratakan tengah di setiap ubin. Password dan contoh kode memakai huruf berlebar tetap bawaan sistem (`ui-monospace, Consolas, monospace`). Service Worker kiosk ikut menyimpan file font (ARS-22). | RECOMMENDATION |
| UI-15 | **Pemakaian di mPDF dan canvas.** mPDF tidak memakai variable font. Karena itu PDF memakai file TTF statis Plus Jakarta Sans Regular dan Bold yang didaftarkan di konfigurasi `fontdata` mPDF. Flyer menunggu `document.fonts.load()` selesai sebelum menggambar, agar canvas tidak memakai font cadangan. | DECISION (font didaftarkan di mPDF); RECOMMENDATION (rincian) |

Skala huruf panel, portal, dan halaman publik (teks dasar 16 px, DECISION):

| Token | Ukuran | Tinggi baris | Pemakaian |
|---|---|---|---|
| `--teks-kecil` | 13 px | 1,4 | Keterangan, judul kolom, catatan kaki |
| `--teks-dasar` | 16 px | 1,5 | Teks, isian, dan tombol |
| `--teks-sedang` | 18 px | 1,4 | Judul bagian |
| `--teks-judul` | 24 px | 1,25 | Judul halaman |
| `--teks-angka` | 28 px | 1,1 | Angka di ubin dashboard |

Ukuran huruf kiosk dihitung dari lebar layar (§7.1).

### 4.4 Ruang, ukuran, dan sudut

| ID | Aturan | Status |
|---|---|---|
| UI-16 | **Jarak.** Jarak memakai kelipatan 4 px: 4, 8, 12, 16, 24, 32, dan 48 px (`--jarak-1` s.d. `--jarak-7`). Elemen bersebelahan diberi jarak dengan `gap` di flex atau grid. | RECOMMENDATION |
| UI-17 | **Sudut dan bayangan.** Isian dan tombol bersudut 6 px, kartu 10 px, dan chip berbentuk pil. Bayangan hanya untuk dialog dan menu lipat. Kartu cukup bertepi `--garis`. | RECOMMENDATION |
| UI-18 | **Titik henti.** Ponsel di bawah 600 px, tablet 600–1023 px, dan layar lebar mulai 1024 px. Isi halaman panel selebar paling besar 1200 px, dan isi portal paling besar 720 px. Tata letak ditulis dari ponsel ke layar lebar. | RECOMMENDATION |
| UI-19 | **Target sentuh.** Tombol, tautan di daftar, dan isian di ponsel paling kecil 44×44 px. | DECISION |

### 4.5 Ikon

| ID | Aturan | Status |
|---|---|---|
| UI-20 | **Subset Lucide.** Ikon diambil dari Lucide (lisensi ISC; versi 1.52.0 saat dokumen ini ditulis), lalu digabung menjadi satu file sprite SVG `public/aset/ikon/ikon.svg` berisi `<symbol>` per ikon, beserta file lisensinya. Ikon dipanggil dengan `<svg><use href="…/ikon.svg#nama"></use></svg>`. Kiosk memakai salinan sprite yang sama di cache Service Worker. Ikon baru ditambahkan ke sprite dan ke tabel di bawah. | DECISION (subset Lucide dalam satu file SVG); RECOMMENDATION (lokasi dan cara panggil) |
| UI-21 | **Ikon selalu disertai teks.** Ikon di samping teks diberi `aria-hidden="true"`. Tombol yang hanya berisi ikon, misalnya tombol menu lipat, wajib memiliki `aria-label`. Ikon berukuran 1em dengan garis 2 px dan mengikuti warna teks. | RECOMMENDATION |

Ikon yang dipakai (selain ikon status di §4.2):

| Makna | Ikon | Makna | Ikon |
|---|---|---|---|
| Dashboard | `house` | Menu lipat | `menu` |
| Daftar presensi, Kelas saya | `clipboard-list` | Siswa | `users` |
| Presensi manual, koreksi | `pencil` | Riwayat, profil | `user` |
| Jadwal hari ini | `clock-3` | Sekolah dan pengaturan | `school`, `settings` |
| Mode darurat | `siren` | Laporan | `chart-column` |
| Scan bertanda, kiosk | `scan-line` | Kalender | `calendar-days` |
| Online, offline | `wifi`, `wifi-off` | Sinkron | `refresh-cw` |
| Belum tersinkron | `cloud-upload` | Muat ulang data, unduh | `download` |
| Unggah | `upload` | Cetak | `printer` |
| Cari | `search` | Info, peringatan umum | `info`, `circle-alert` |
| Password, akun | `key-round` | Login, logout | `log-in`, `log-out` |
| Kamera | `camera`, `camera-off` | Bunyi | `volume-2` |
| Sudah tercatat (kiosk) | `badge-check` | Kartu, QR | `id-card`, `qr-code` |
| Sebelumnya, berikutnya | `chevron-left`, `chevron-right` | Tutup, centang | `x`, `check` |
| Tampilkan, sembunyikan password | `eye`, `eye-off` | Buka kunci login, salin | `lock-open`, `copy` |

### 4.6 Foto siswa

| ID | Aturan | Status |
|---|---|---|
| UI-22 | **Tiga ukuran file.** Foto standar paling besar 600×800 px, foto kiosk 300×400 px, dan foto kecil 120×160 px, semuanya JPEG tanpa dipotong (ARS-53). Foto kecil dibuat dari foto standar, sama seperti foto kiosk, dan disimpan di `foto/kecil/` dengan nama file yang sama. Foto kecil dipakai untuk daftar, sehingga daftar 32 siswa sekitar 160 KB. | DECISION (foto kecil 120×160 untuk daftar); RECOMMENDATION (folder) |
| UI-23 | **Ukuran tampil.** Foto selalu memakai rasio 3:4 dengan `object-fit: cover`. Ukuran tampilnya ada di tabel di bawah. | DECISION (ukuran tampil di tabel); RECOMMENDATION (rasio dan `object-fit`) |
| UI-24 | **Pengganti foto.** Siswa tanpa foto, atau yang fotonya belum terunduh di kiosk, tampil dengan siluet abu-abu dan inisial nama (FS-MD-07 E3). Daftar siswa admin menandai "tanpa foto" (FS-MD-04). | RECOMMENDATION |
| UI-25 | **Pemuatan.** Foto di daftar memakai `loading="lazy"`, `width`, dan `height`, agar halaman tidak bergeser saat foto dimuat. Teks alternatif foto berisi nama siswa, kecuali di daftar yang nama siswanya sudah tertulis di sebelahnya (`alt=""`). | RECOMMENDATION |

| Tempat | Ukuran tampil | File |
|---|---|---|
| Kiosk | Tinggi ±50% layar, paling besar 300×400 px | Foto kiosk |
| Presensi manual dan tinjauan scan | 240×320 px | Foto standar |
| Profil siswa | 150×200 px | Foto standar |
| Daftar, termasuk presensi per rombel saat darurat | 36×48 px | Foto kecil |

## 5. Komponen

| ID | Aturan | Status |
|---|---|---|
| UI-26 | **Daftar panjang.** Daftar yang panjang dibagi per halaman, 50 baris per halaman, dengan tautan halaman sebelumnya dan berikutnya serta jumlah total. Saringan dan pencarian memakai formulir GET, sehingga alamat halaman dapat disimpan dan dibagikan. | DECISION (50 baris); RECOMMENDATION (rincian) |
| UI-27 | **Kalender.** Kalender bulanan dimulai hari Senin. Hari yang bukan hari sekolah tampil dengan garis putus-putus tanpa status, dan hari ini diberi bingkai warna utama. | DECISION (mulai Senin); RECOMMENDATION (rincian) |
| UI-28 | **Komponen dasar.** Komponen di tabel di bawah dipakai di semua area, dengan perilaku yang sama. Contohnya ada di `08-contoh-tampilan.html`. Di luar kiosk, komponen dibangun dari komponen Bootstrap 5 bila ada padanannya, misalnya tombol (`btn`), isian (`form-control`, `form-select`, `form-check`), tabel (`table`), bilah peringatan (`alert`), tab (`nav-tabs`), dan halaman (`pagination`), dengan tampilan disesuaikan lewat token (UI-08). Komponen tanpa padanan, misalnya chip status, ubin angka, foto siswa, dan pemilih tanggal, memakai kelas aplikasi (UI-74). Komponen Bootstrap yang membutuhkan JavaScript, misalnya dropdown, modal, dan offcanvas, hanya menambah kenyamanan: tanpa JavaScript, isi dan tindakannya tetap dapat dibuka (UI-04). | DECISION (Bootstrap, §2.6); RECOMMENDATION (rincian) |

| Komponen | Aturan |
|---|---|
| Tombol | Jenis: utama (latar warna utama), kedua (bergaris), bahaya (merah, untuk pembatalan dan penonaktifan), dan teks. Satu formulir memiliki paling banyak satu tombol utama. Label tombol berupa kata kerja yang menyebut hasilnya, misalnya "Simpan presensi manual", bukan "OK". Tombol yang sedang memproses dinonaktifkan dan berlabel "Menyimpan…", agar tidak terkirim dua kali. |
| Isian formulir | Label di atas isian, tidak di dalamnya. Isian opsional diberi tulisan "(opsional)", sehingga isian tanpa tanda adalah wajib. Teks bantuan di bawah label. Galat tampil di bawah isian dengan ikon dan teks merah, dan isian ditandai `aria-invalid="true"` dengan `aria-describedby` ke pesannya. Di atas formulir tampil ringkasan "Periksa [n] isian yang ditandai." dengan tautan ke setiap isian (`role="alert"`), dan fokus pindah ke ringkasan itu. Isi formulir tetap utuh setelah galat, kecuali password, PIN, dan file (`11` VAL-07). Isian alasan koreksi dan catatan verifikasi memuat pengingat "Alasan dapat dibaca siswa." (`04` §4.3 butir 4). |
| Pilihan | Pilihan sedikit (≤ 5) memakai tombol radio, dan pilihan banyak memakai `select`. Pilihan ganda memakai kotak centang. Tidak memakai sakelar geser. |
| Tanggal dan jam | Memakai `<input type="date">` dan `<input type="time">` bawaan browser, dengan `step="60"` untuk jam. Tampilan isian mengikuti bahasa perangkat, tetapi teks lain di halaman tetap memakai format UI-53. Formulir yang dibatasi batas mundur menampilkan "Dapat diubah: 6–13 Oktober 2026" (`04` §4.2). |
| Unggah file | Tombol pilih file dengan nama file terpilih. Foto dan logo menampilkan pratinjau sebelum disimpan bila JavaScript aktif (FS-MD-01, FS-MD-07). Unggahan besar menampilkan kemajuan bertahap (ARS-54 butir 6). Format dan ukuran yang diterima ditulis di teks bantuan, misalnya "JPG, PNG, atau WebP, paling besar 10 MB." (`12` SEC-49). |
| Tabel | Judul kolom pendek dengan `scope`. Angka rata kanan dengan `tabular-nums`. Baris total tebal di bawah. Kolom yang dapat diurutkan memakai tombol di judul kolom dan `aria-sort`. Tabel lebar digeser ke samping di dalam wadahnya sendiri, dengan kolom pertama tetap di kiri. Tabel angka di ponsel memakai huruf singkat dengan keterangan di bawah tabel (§4.2). |
| Chip status | Pil berlatar warna muda, dengan ikon dan teks warna teks status (§4.2). Status data izin memakai chip bergaris. |
| Ubin angka | Kotak berisi angka besar dan label dengan ikon status. Dipakai di kepala dashboard dan ringkasan rekap. Ubin "Belum hadir" atau Alpa disorot (FS-LAP-01). |
| Bilah peringatan | Kotak berlatar warna muda dengan ikon dan teks, ditambah tautan tindakan di kanan, misalnya "Status stasiun". Jenis: info, sukses, peringatan, dan bahaya. |
| Pesan kilat | Pesan setelah tindakan berhasil atau gagal, tampil di atas isi halaman setelah pengalihan (pola POST, lalu alihkan, lalu GET). Pesan berhasil memakai `role="status"`, dan pesan gagal `role="alert"`. Pesan tetap tampil sampai pengguna pindah halaman. |
| Konfirmasi | Tindakan yang membutuhkan konfirmasi (`04` §4.7) memakai langkah konfirmasi yang dibuat server dan menyebut dampaknya, misalnya "2 akun akan mendapat password baru". Dengan JavaScript, langkah itu boleh tampil dalam `<dialog>`. `confirm()` bawaan browser tidak dipakai. Tindakan yang sudah meminta alasan, misalnya pembatalan presensi manual, tidak meminta konfirmasi tambahan. |
| Keadaan kosong | Kalimat yang menyebut keadaannya, ditambah tindakan berikutnya bila ada, misalnya "Belum ada stasiun scan." dengan tautan tambah akun stasiun (FS-KIO-05 E1). |
| Diperbarui berkala | Fragmen yang diperbarui setiap 30 detik (ARS-50) menampilkan "Diperbarui 07.32". Bila permintaan gagal, tampil "Gagal memperbarui. Mencoba lagi." Bila sesi berakhir, polling berhenti dan tampil "Sesi berakhir. Login lagi untuk melanjutkan." dengan tautan ke `/login` (`11` GAL-14). Pembaruan berkala tidak diumumkan pembaca layar, kecuali keadaan gagal. |
| Tab | Untuk membagi satu isi, misalnya baris valid dan baris gagal di pratinjau import (FS-MD-06). Tanpa JavaScript, setiap tab adalah tautan dengan parameter. |
| Pemilih tanggal | Isian tanggal dengan tombol hari sebelumnya dan berikutnya (FS-LAP-02). Tombol berikutnya nonaktif di hari ini. |
| Pencarian siswa | Isian "Nama atau NISN" dengan ikon `search` dan tombol Cari. Tanpa JavaScript, hasil tampil setelah halaman dimuat ulang. Dengan JavaScript, hasil yang sama tampil saat mengetik, mulai 2 karakter: paling banyak 20 siswa dengan foto kecil, nama, NISN, kelas, dan status siswa. Hasil berupa daftar tautan biasa di bawah isian, sehingga dapat dipilih dengan Tab dan Enter, dan jumlahnya diumumkan pembaca layar lewat `aria-live="polite"`. Mode pilih banyak memakai kotak centang (`09` RT-14, `10` EP-MD-01). |
| Foto siswa | Bingkai 3:4 dengan pengganti (UI-24). |
| Kepala halaman | Judul halaman, konteks (misalnya tanggal dan rombel), dan tombol tindakan utama di kanan. Di ponsel, tombol pindah ke bawah judul. |

## 6. Tata letak per area

### 6.1 Panel staf

| ID | Aturan | Status |
|---|---|---|
| UI-29 | **Kerangka panel.** Layar ≥1024 px memakai menu samping selebar ±220 px berlatar warna utama. Di bawah 1024 px, menu samping disembunyikan dan dibuka dengan tombol `menu` di bilah atas, sebagai menu lipat yang menutupi isi. Tanpa JavaScript, menu lipat memakai elemen `<details>`, sehingga tetap dapat dibuka tanpa halaman tambahan. Bilah atas memuat tahun ajaran dan semester aktif (FS-MD-02), nama pengguna, role, dan menu akun (ganti password, logout). | DECISION (menu samping dan menu lipat); RECOMMENDATION (rincian) |
| UI-30 | **Tanda mode darurat.** Selama mode darurat aktif, bilah merah tampil di bawah bilah atas di semua halaman panel: "Mode darurat aktif sejak 06.30. Siswa tanpa presensi tetap belum hadir, dan Alpa tidak terbentuk." Bilah itu memuat tautan ke presensi per kelas bagi pemegang `HA-PRS-03` (FS-PRS-08). | RECOMMENDATION |
| UI-31 | **Kelompok menu.** Menu dikelompokkan seperti tabel di bawah. Menu hanya memuat halaman yang boleh dibuka role pengguna (`04` §4.1), dan kelompok tanpa isi disembunyikan. Isi menu final, termasuk kelompok R2 dan R3, ada di `09` §4.1. | DECISION (dikelompokkan dan hanya halaman yang boleh dibuka; "Kelas saya" bagi wali kelas, Session 8); RECOMMENDATION (isi kelompok) |
| UI-32 | **Dashboard hari ini.** Urutan dari atas: kepala dashboard, peringatan sesuai hak, ubin ringkasan, lalu tabel per kelas (FS-LAP-01). Rombel wali kelas tampil paling atas dengan tanda "Kelas Anda". Rombel yang libur tampil sebagai baris dengan keterangan liburnya. Di ponsel, tabel memakai huruf singkat (H, T, I, S, D, dan – atau A). | RECOMMENDATION |
| UI-33 | **Daftar presensi kelas.** Siswa berpenanda dan siswa yang belum hadir tampil paling atas. Setiap baris memuat foto kecil, nama, NISN, chip status, presensi masuk dan pulang beserta sumbernya ("Scan, Gerbang 1", "Manual", atau "Darurat"), kejadian, izin, koreksi, penanda, dan tombol tindakan sesuai hak (FS-LAP-02). Di ponsel, setiap siswa tampil sebagai kartu. | RECOMMENDATION |
| UI-34 | **Presensi manual.** Setelah siswa dipilih, foto 240×320 px tampil di samping formulir untuk dicocokkan dengan wajah siswa. Formulir menampilkan pratinjau status, misalnya "Akan tercatat: Terlambat" (FS-PRS-06 butir 2). Tanpa JavaScript, pratinjau tampil setelah tombol "Periksa". | RECOMMENDATION |

| Kelompok | Isi (contoh) | Hak |
|---|---|---|
| — | Dashboard hari ini; "Kelas saya" bagi wali kelas | `HA-LAP-01`, `HA-LAP-02` |
| Presensi | Daftar presensi kelas, presensi manual, jadwal hari ini, mode darurat, scan bertanda, log perubahan presensi | `HA-LAP-02`, `HA-LAP-03`, `HA-PRS-03`, `HA-PRS-07`, `HA-PRS-08`, `HA-KIO-03`, `HA-PRS-06` |
| Izin | Pengajuan menunggu, daftar izin/sakit/dispensasi, input izin, dispensasi massal | `HA-IZN-02` s.d. `HA-IZN-04` |
| Laporan | Rekap per kelas, riwayat siswa; di R2 rekap semua kelas, rekap rapor, dan flyer | `HA-LAP-03` s.d. `HA-LAP-06` |
| Siswa | Data siswa, import, foto massal, penempatan, akun siswa dan slip, atribut tambahan | `HA-MD-03` s.d. `HA-MD-05`, `HA-MD-08`, `HA-MD-11`, `HA-AKN-05`, `HA-AKN-06` |
| Sekolah | Identitas sekolah, tahun ajaran, kelas dan wali kelas, pola mingguan, jadwal khusus, libur, batas mundur | `HA-MD-01`, `HA-MD-02`, `HA-MD-09`, `HA-PRS-01`, `HA-PRS-02`, `HA-PRS-09` |
| Akun dan stasiun | Akun staf, akun dan status stasiun, PIN petugas, log aktivitas, pemeriksaan sistem | `HA-AKN-02`, `HA-AKN-03`, `HA-AKN-07`, `HA-AKN-08`, `HA-KIO-02` |

### 6.2 Portal siswa

| ID | Aturan | Status |
|---|---|---|
| UI-35 | **Kerangka portal.** Di ponsel, portal memakai bilah atas berisi logo dan judul halaman, serta menu bawah tetap berisi Riwayat, Izin, dan Akun. Di R3, menu bawah ditambah Jadwal dan Pengumuman. Di layar lebar (≥1024 px, UI-18), menu bawah berpindah ke bilah atas. Di tablet (600–1023 px) menu bawah tetap dipakai. Halaman awal portal adalah riwayat kehadiran (`02` §8). | DECISION (menu bawah di ponsel, menu atas di layar lebar); RECOMMENDATION (rincian) |
| UI-36 | **Riwayat di portal.** Urutan dari atas: nama dan kelas, pemilih bulan, ringkasan periode (jumlah per status dan persentase kehadiran), kalender bulanan berwarna dengan huruf singkat, lalu daftar harian dari yang terbaru (FS-LAP-04). Tanggal yang dikoreksi menampilkan "Dikoreksi" beserta alasannya. Hari ini diberi tanda "Belum final" sampai statusnya final. | RECOMMENDATION |
| UI-37 | **Pengajuan izin/sakit.** Formulir satu kolom dengan pilihan "Satu hari" atau "Beberapa hari", jenis Izin atau Sakit, keterangan, dan lampiran opsional, paling banyak 3 file (FS-IZN-01). Lampiran dapat diambil langsung dari kamera ponsel. | RECOMMENDATION |

### 6.3 Halaman bersama

| ID | Aturan | Status |
|---|---|---|
| UI-38 | **Login, ganti password, dan galat.** Halaman login berada di tengah layar dan memuat logo, nama resmi sekolah, serta nama produk "Spensada" (FS-MD-01). Isinya satu isian "NISN atau username", isian password dengan tombol tampilkan atau sembunyikan, dan teks bantuan: "Siswa yang lupa password menghubungi wali kelas. Staf menghubungi admin. Di komputer bersama, logout setelah selesai." (FS-AKN-01, FS-AKN-02). Pengingat logout diperlukan karena sesi tetap berjalan setelah browser ditutup, sampai 8 jam tanpa aktivitas atau 7 hari sejak login (`12` SEC-12 butir 3). Pesan kunci login mengikuti `11` §5.1. Di bawah formulir login tampil pemberitahuan privasi dari `pengaturan.privasi_teks`, yang juga tampil di portal siswa (`12` SEC-68, `06` §6.1). Halaman galat 403 dan 404 memakai kerangka area pengguna, sedangkan halaman 500 memakai kerangka tanpa menu area (`09` RT-19), dengan pesan di §9.4 dan tautan ke halaman awal. Halaman 500 menampilkan kode laporan 8 karakter, misalnya `7F3A2C1B`, untuk dilaporkan ke admin (`11` GAL-13). Halaman 400 dan 429 memakai teks di §9.4 (`11` GAL-02, GAL-10). Halaman 413, 429 dari Nginx, dan 503 pemeliharaan adalah file statis di `public/galat/` dengan logo dan teks yang sama, tanpa menu dan tanpa skrip (`11` GAL-11, GAL-17). Halaman publik (R3) memakai kerangka portal tanpa menu bawah, dan dirinci menjelang R3. | RECOMMENDATION |

## 7. Kiosk

### 7.1 Tata letak

| ID | Aturan | Status |
|---|---|---|
| UI-39 | **Tiga bagian layar.** Kiosk berjalan sebagai jendela aplikasi layar penuh di laptop berlayar mendatar, dengan acuan 1366×768 px. Layar dibagi tiga: bilah atas (±9% tinggi), bagian tengah, dan bilah bawah (±8% tinggi). Bagian tengah dibagi dua: kolom kamera (±31% lebar) dan blok hasil (sisanya). Ukuran huruf dihitung dari ukuran layar (`vw`, `vh`, dan `clamp()`), sehingga proporsinya sama di 1366×768 dan 1920×1080. Tinggi foto ±50% layar, tetapi paling besar 400 px, sesuai ukuran foto kiosk. Sisa ruang diisi teks. | DECISION (kamera dan jadwal sesi di kiri, hasil di kanan selebar ±dua pertiga layar); RECOMMENDATION (proporsi lain dan ukuran huruf) |
| UI-40 | **Isi setiap bagian.** Lihat tabel di bawah. | RECOMMENDATION |

| Bagian | Isi |
|---|---|
| Bilah atas | Logo dan nama sekolah, "Spensada · kiosk presensi", nama stasiun, keadaan koneksi, dan jam besar. |
| Kolom kamera | Pratinjau kamera dengan bingkai bidik, arahan "Arahkan QR kartu ke kamera, atau pakai scanner.", dan jadwal hari ini: jendela masuk, batas terlambat, dan jendela pulang. |
| Blok hasil | Saat siap: ikon `scan-line`, "Silakan scan kartu", dan keterangan sesi yang sedang berjalan. Saat ada hasil: foto, nama, kelas, judul hasil dengan ikon, dan keterangan jenis serta jam. Huruf nama ±4,2% lebar layar dan judul hasil ±5,2% lebar layar, yaitu ±57 px dan ±71 px di layar 1366 px. |
| Bilah bawah | Waktu data dimuat dan jumlah siswa, jumlah belum tersinkron, waktu sinkron terakhir, tombol "Sinkron sekarang" dan "Muat ulang data", serta tombol `menu` untuk tindakan petugas lain. |

### 7.2 Hasil scan

| ID | Aturan | Status |
|---|---|---|
| UI-41 | **Empat jenis hasil.** Hasil scan dibedakan menurut tabel di bawah. Warna blok hasil mengikuti warna status: hijau untuk tercatat, oranye tua untuk tercatat dengan catatan, dan merah untuk ditolak. Scan ganda memakai biru, sebagai informasi bahwa tidak ada yang baru dicatat. Scan berikutnya langsung menggantikan hasil yang sedang tampil (FS-KIO-02 butir 8). Setelah lama tampil habis, blok hasil kembali siap. | DECISION (bunyi dan lama tampil); RECOMMENDATION (warna, ikon, dan pengelompokan) |
| UI-42 | **Hasil tanpa siswa.** Scan yang ditolak sebelum NISN dicari, yaitu di luar jendela atau bukan hari sekolah, serta kartu yang tidak terdaftar, tampil tanpa foto dan nama. Ikon `circle-x` besar menggantikan foto. Hasil ini mengikuti urutan pemeriksaan BR-SCN-01. | RECOMMENDATION |

| Jenis hasil | Keadaan | Warna blok | Ikon | Bunyi | Lama tampil |
|---|---|---|---|---|---|
| Tercatat | Masuk Hadir, pulang | Hadir (hijau) | `circle-check` | Berhasil | 3 detik |
| Tercatat dengan catatan | Masuk Terlambat, pulang lebih awal | Terlambat (oranye tua) | `clock-alert`, `log-out` | Berhasil | 3 detik |
| Sudah tercatat | Scan ganda (FS-KIO-02 E9) | Izin (biru) | `badge-check` | Peringatan | 3 detik |
| Ditolak | FS-KIO-02 E1 s.d. E8 | Alpa (merah) | `circle-x` | Galat | 6 detik |

### 7.3 Teks kiosk

Teks ini memfinalkan contoh di `05` §4.2 dan FS-KIO-02. Judul ditulis dengan huruf kapital di layar. (RECOMMENDATION, dengan sapaan "kamu" sesuai DECISION UI-49)

| Kode FS-KIO-02 | Judul | Keterangan |
|---|---|---|
| — (masuk tepat waktu) | HADIR | Masuk · 06.52 |
| — (masuk terlambat) | TERLAMBAT | Masuk · 07.05 |
| — (pulang) | PULANG | Pulang · 13.05 |
| — (pulang lebih awal) | PULANG LEBIH AWAL | Pulang · 12.40 |
| E9 | SUDAH TERCATAT | Kamu sudah tercatat masuk pukul 06.52. |
| E1 | QR TIDAK DIKENALI | Pakai kartu OSIS kamu. Bila tetap gagal, temui guru piket. |
| E2 | KARTU TIDAK TERDAFTAR | Temui guru piket. |
| E3 | SCAN MASUK BELUM DIBUKA | Scan masuk dibuka pukul 06.00. |
| E4 | SESI MASUK SUDAH DITUTUP | Temui guru piket. |
| E5 | SESI PULANG SUDAH DITUTUP | Sesi pulang ditutup pukul 17.00. |
| E6 | BUKAN HARI SEKOLAH | Hari ini bukan hari sekolah. Bila libur, keterangan libur ditampilkan, misalnya "Libur: Maulid Nabi". |
| E7 | KAMU SEDANG LIBUR | Keterangan libur, misalnya "Tingkat 9 libur hari ini: pasca-asesmen." |
| E8 | SCAN TIDAK TERSIMPAN | Panggil petugas. |

Sebelum jam buka scan masuk dan di antara jendela masuk dan jendela pulang, blok hasil yang siap menampilkan jam buka sesi berikutnya, misalnya "Scan pulang dibuka pukul 12.00."

### 7.4 Layar tidak siap dan peringatan petugas

| ID | Aturan | Status |
|---|---|---|
| UI-43 | **Layar tidak siap.** Bila kiosk tidak dapat menerima scan, blok hasil diganti pesan tetap untuk petugas (tabel pertama di bawah). Kamera dan scanner tidak memproses scan selama pesan itu tampil. | RECOMMENDATION |
| UI-44 | **Peringatan petugas.** Keadaan yang tidak menghentikan scan tampil sebagai pita di atas bilah bawah, berlatar `--status-terlambat-muda` dengan teks `--status-terlambat-teks`,, satu per baris, dengan tombol tindakan bila ada (tabel kedua di bawah). Penghitung belum tersinkron berwarna oranye bila lebih dari nol dan kiosk sedang offline (FS-KIO-03). | RECOMMENDATION |

| Keadaan | Pesan | Rujukan |
|---|---|---|
| Data belum pernah dimuat dan offline | Data belum dimuat. Hubungkan ke internet. | FS-KIO-01 E1 |
| Pola mingguan belum lengkap | Aturan jam belum diatur. Hubungi admin. | FS-KIO-01 E6 |
| Login stasiun berakhir | Login stasiun berakhir. Login ulang dengan akun stasiun ini. Scan yang belum terkirim tetap tersimpan. | FS-KIO-01 E2, ARS-31 |
| Akun stasiun dinonaktifkan | Akun stasiun ini dinonaktifkan. Data di laptop sudah dihapus. Hubungi admin. | FS-KIO-01 E3 |
| Kiosk terbuka di tab lain | Kiosk sudah terbuka di jendela lain. Tutup jendela ini. | ARS-21 butir 6 |
| Scan milik akun lain tertahan | Ada 12 scan dari akun "Gerbang 1" yang belum terkirim. Login kembali dengan akun itu untuk mengirimnya. | ARS-31 |

| Keadaan | Pesan | Tindakan | Rujukan |
|---|---|---|---|
| Data lebih tua dari 72 jam | Data terakhir dimuat 10 Okt 2026, 06.05. Muat ulang saat online. | Muat ulang data | FS-KIO-01 butir 6 |
| Jam belum dicek hari ini | Jam belum dicek hari ini. Hubungkan ke internet. | — | ARS-28 |
| Jam laptop berubah atau mundur | Jam laptop berubah. Periksa jam Windows. | — | ARS-27, ARS-28 |
| Di luar rentang aturan yang dimuat | Aturan jam memakai pola mingguan. Muat ulang data saat online. | Muat ulang data | FS-KIO-01 butir 7 |
| Penyimpanan permanen ditolak | Penyimpanan permanen belum aktif. Pasang kiosk sebagai aplikasi. | — | FS-KIO-01 butir 9 |
| Penyimpanan hampir penuh | Penyimpanan laptop hampir penuh. Hubungi admin. | — | FS-KIO-01 E5 |
| Kamera tidak tersedia | Kamera tidak tersedia. Scanner USB tetap dapat dipakai. | Coba lagi | FS-KIO-02 E10 |
| Bunyi belum aktif | Klik di mana saja untuk mengaktifkan bunyi. | — | UI-45 |
| Versi baru siap | Versi baru kiosk siap. | Muat versi baru | ARS-22 butir 7 |
| Pemuatan data gagal | Gagal memuat data. Data sebelumnya tetap dipakai. | Coba lagi | FS-KIO-01 E4 |
| Versi API tidak dilayani | Kiosk perlu diperbarui. Tutup semua jendela kiosk, lalu buka lagi. | — | `10` API-10 |

### 7.5 Bunyi dan perilaku lain

| ID | Aturan | Status |
|---|---|---|
| UI-45 | **Bunyi Web Audio.** Bunyi dibuat dengan `OscillatorNode` dan `GainNode` dari satu `AudioContext`, tanpa file suara (tabel di bawah). Browser dapat menahan `AudioContext` sampai ada interaksi pengguna. Bila keadaannya `suspended`, kiosk menampilkan peringatan "Bunyi belum aktif" dan memanggil `resume()` pada klik atau penekanan tombol pertama. Chrome mengizinkan bunyi tanpa klik untuk aplikasi yang sudah dipasang. Di Edge, admin mengatur "Putar otomatis media" menjadi "Izinkan" untuk alamat kiosk saat pemasangan stasiun (UF-06). Bunyi pertama diputar setelah `resume()` selesai. Menu petugas memuat tombol "Tes bunyi". | DECISION (tiga nada Web Audio dan pemetaannya); RECOMMENDATION (nada dan rincian) |
| UI-46 | **Fokus dan scanner USB.** Tombol kiosk tidak menahan fokus. Setelah tombol diklik, fokus dikembalikan ke halaman, agar Enter dari scanner USB tidak menekan tombol itu (ARS-26). | RECOMMENDATION |
| UI-47 | **Layar tetap menyala.** Selama kiosk tampil, kiosk meminta `navigator.wakeLock.request('screen')` dan memintanya lagi saat jendela kembali terlihat. Bila permintaan ditolak, kiosk tidak menampilkan galat. Pengaturan daya Windows tetap menjadi pengaman utama (ARS-32). | RECOMMENDATION |
| UI-48 | **Tindakan berisiko di menu.** Logout, hapus data lokal, dan layar penuh berada di menu petugas, bukan di layar utama. Logout dan hapus data lokal meminta PIN petugas (`12` SEC-21, SEC-22, `02` §9). Layar penuh, muat ulang data, dan sinkron sekarang tidak meminta PIN. Lihat butir di bawah tabel. | DECISION (PIN untuk logout dan hapus data lokal, Session 9); RECOMMENDATION (rincian) |

| Bunyi | Nada | Bentuk gelombang | Durasi | Dipakai untuk |
|---|---|---|---|---|
| Berhasil | Satu nada ±1.046 Hz | Segitiga | 0,16 detik | Scan tercatat, termasuk Terlambat dan pulang lebih awal |
| Peringatan | Dua nada ±740 Hz dengan jeda 0,08 detik | Segitiga | 2 × 0,12 detik | Scan ganda |
| Galat | Satu nada ±196 Hz | Kotak, lebih pelan | 0,5 detik | Scan ditolak |

Butir UI-48:

1. Setelah tindakan dipilih, kiosk menampilkan dialog "Masukkan PIN petugas." dengan isian PIN yang disamarkan (`type="password"`, `inputmode="numeric"`, `autocomplete="off"`, 6 digit) dan tombol angka besar untuk layar sentuh. Teks layar mengikuti `11` §5.3.
2. PIN diperiksa di kiosk dengan hash dari data kiosk, sehingga dapat dipakai saat offline (`12` SEC-21 butir 2). PIN salah menampilkan "PIN salah. Sisa [n] percobaan." Lima kali salah mengunci menu petugas 5 menit dengan pesan "Menu petugas dikunci sampai pukul [jam]."
3. Bila PIN belum diatur, menu petugas menampilkan pita "PIN petugas belum diatur. Hubungi admin.", dan tindakan berisiko cukup memakai konfirmasi (`12` SEC-21 butir 4).
4. Hapus data lokal tetap ditolak selama ada scan belum tersinkron: "Masih ada [n] scan belum tersinkron. Sinkronkan dulu sebelum menghapus data."
5. Halaman pengaturan PIN di panel (`09` HAL-KIO-02) memakai isian yang sama, diisi dua kali, dengan pesan `11` VAL-22.

## 8. Gaya bahasa

| ID | Aturan | Status |
|---|---|---|
| UI-49 | **Sapaan.** Panel staf menyapa pengguna dengan "Anda". Portal siswa dan kiosk menyapa siswa dengan "kamu". Slip, flyer, PDF, halaman login, dan halaman publik memakai bahasa formal tanpa "kamu", dan memakai "Anda" bila sapaan diperlukan. Bila kalimat tetap jelas tanpa sapaan, sapaan tidak dipakai, misalnya "Ganti password sebelum melanjutkan." | DECISION (Anda untuk staf, kamu untuk siswa, slip dan flyer formal); RECOMMENDATION (halaman bersama dan kalimat tanpa sapaan) |
| UI-50 | **Kalimat.** Kalimat pendek dan aktif, memakai istilah glosarium dan label §9. Tombol memakai kata kerja. Pesan galat menyebut apa yang terjadi dan apa yang harus dilakukan, tanpa menyalahkan pengguna dan tanpa kode teknis. Angka ditulis dengan angka, misalnya "2 akun", bukan "dua akun". | RECOMMENDATION |

## 9. Label dan format

### 9.1 Label istilah

| ID | Aturan | Status |
|---|---|---|
| UI-51 | **Kelas dan tingkat.** Rombel tampil sebagai "Kelas", dan tingkat sebagai "Tingkat", di semua area, slip, flyer, PDF, file export, dan pesan kiosk. Nama fitur yang memuat "rombel" ikut menyesuaikan di layar (tabel di bawah). Dokumen dan nama teknis tetap memakai "rombel" (`00` §9). | DECISION (Kelas dan Tingkat); RECOMMENDATION (nama fitur di layar) |

| Istilah dokumen | Label layar |
|---|---|
| Rombel | Kelas |
| Tingkat | Tingkat |
| Daftar presensi rombel | Daftar presensi kelas |
| Rekap per rombel, rekap semua rombel | Rekap per kelas, rekap semua kelas |
| Presensi per rombel saat darurat | Presensi per kelas |
| Libur per tingkat atau rombel | Libur tingkat atau kelas |
| Wali kelas | Wali kelas |
| Akun stasiun, stasiun scan | Akun stasiun, stasiun |
| Penanda | Perlu diperiksa |
| Scan bertanda | Scan bertanda |
| Batas mundur | Batas mundur |

Judul kolom file export dan template import di `13` ikut memakai label ini, misalnya "Kelas" dan "Kelas Tujuan". Perubahan itu tercatat di §14 dokumen ini.

### 9.2 Label kode nilai

| ID | Aturan | Status |
|---|---|---|
| UI-52 | **Label kode nilai.** Kode nilai di `06` tampil dengan label di tabel di bawah. Nama status harian mengikuti glosarium (`00` §9). | RECOMMENDATION |

| Kolom `06` | Kode → label |
|---|---|
| `status_harian.status` | `hadir` Hadir · `terlambat` Terlambat · `izin` Izin · `sakit` Sakit · `dispensasi` Dispensasi · `alpa` Alpa · kosong Belum hadir atau Alpa (`06` §11.3) |
| `status_harian.masuk_sumber` | `scan` Scan (diikuti nama stasiun) · `manual` Manual · `darurat` Darurat |
| `scan.jenis_kiosk`, `presensi_manual.jenis` | `masuk` Masuk · `pulang` Pulang |
| `scan.hasil` | `dipakai` Dipakai · `ganda` Ganda · `tidak_dipakai` Tidak dipakai · `ditolak` Ditolak |
| `scan_tinjauan.keputusan` | `terima` Diterima · `tolak` Ditolak |
| `presensi_manual.alasan` | `lupa_kartu` Lupa kartu · `kartu_rusak` Kartu rusak · `qr_tidak_terbaca` QR tidak terbaca · `kiosk_terganggu` Kiosk terganggu · `tiba_setelah_tutup` Tiba setelah sesi masuk ditutup · `pulang_sakit` Pulang karena sakit · `pulang_izin` Pulang dengan izin · `darurat` Darurat · `lainnya` Lainnya |
| `koreksi_status.kehadiran` | `hadir` Hadir · `terlambat` Terlambat · `tidak_hadir` Tidak hadir |
| `izin.jenis` | `izin` Izin · `sakit` Sakit · `dispensasi` Dispensasi |
| `izin.status` | `menunggu` Menunggu · `disetujui` Disetujui · `ditolak` Ditolak · `dibatalkan` Dibatalkan |
| `masa_aktif.alasan_nonaktif` | `lulus` Lulus · `pindah_sekolah` Pindah sekolah · `keluar` Keluar · `meninggal_dunia` Meninggal dunia · `salah_input` Salah input · `lainnya` Lainnya |
| `akun.jenis` | `staf` Staf · `siswa` Siswa · `stasiun` Stasiun |
| `akun.status` | `belum_aktif` Belum aktif · `aktif` Aktif · `nonaktif` Nonaktif |
| `akun_role.role` | `admin` Admin · `guru_piket` Guru piket · `guru_bk` Guru BK · `pimpinan` Pimpinan |
| `semester.jenis` | `ganjil` Ganjil · `genap` Genap |
| `libur.cakupan` | `semua` Semua siswa · `tingkat` Tingkat · `rombel` Kelas |
| `atribut_siswa.tipe` | `teks` Teks · `angka` Angka · `tanggal` Tanggal · `pilihan` Pilihan |

Label log, outbox WA, dan kode lain yang hanya tampil di halaman admin dan halaman staf tertentu ada di `09` §14, dengan pola yang sama: huruf awal kapital dan garis bawah menjadi spasi. Label `log_aktivitas.jenis` juga ada di `09` §14 (`12` SEC-59).

### 9.3 Format

| ID | Aturan | Status |
|---|---|---|
| UI-53 | **Tanggal dan jam.** Lihat tabel di bawah. Semua waktu memakai WIB. Label "WIB" hanya ditulis di tempat yang dibaca di luar sekolah, yaitu flyer, PDF, dan halaman publik. | DECISION (format tanggal di tabel dan judul, jam 24 jam bertitik); RECOMMENDATION (format lain dan label WIB) |
| UI-54 | **Angka dan data.** Pemisah ribuan titik dan desimal koma, misalnya "1.024". Persentase berupa bilangan bulat tanpa spasi, misalnya "88%" (`13` IE-04). NISN dan NIS ditulis utuh tanpa spasi. Nomor WA disimpan dengan awalan 62, tetapi ditampilkan dengan awalan 0 dan tanda hubung, misalnya "0812-3456-7890" (FS-MD-04). Tahun ajaran ditulis "2026/2027", dan semester "Semester ganjil 2026/2027". | RECOMMENDATION |
| UI-55 | **Cara memformat.** Server memformat dengan `IntlDateFormatter` (locale `id_ID`, zona `Asia/Jakarta`) lewat satu helper. JavaScript memakai `Intl.DateTimeFormat('id-ID')`. Kiosk memformat jam terkoreksi tanpa memakai zona waktu Windows (ARS-27 langkah 4). | RECOMMENDATION |

| Pemakaian | Format | Contoh |
|---|---|---|
| Judul halaman, dashboard, slip, flyer | Hari, tanggal bulan tahun | Selasa, 13 Oktober 2026 |
| Kalimat dan formulir | Tanggal bulan tahun | 13 Oktober 2026 |
| Tabel dan daftar | Tanggal bulan-singkat tahun | 13 Okt 2026 |
| Tabel dalam satu tahun yang sama | Tanggal bulan-singkat | 13 Okt |
| Rentang | Tanggal awal–akhir | 6–13 Oktober 2026; 28 September – 3 Oktober 2026 |
| Jam | JJ.MM | 07.00 |
| Jam dengan detik (detail scan dan log) | JJ.MM.DD | 07.00.59 |
| Tanggal dan jam | Tanggal, jam | 13 Okt 2026, 07.32 |
| Waktu kontak terakhir stasiun | Jam dan selisih | 07.15 (17 menit lalu) |

Singkatan bulan: Jan, Feb, Mar, Apr, Mei, Jun, Jul, Agu, Sep, Okt, Nov, Des.

### 9.4 Pesan umum

| ID | Aturan | Status |
|---|---|---|
| UI-56 | **Pesan umum.** Pesan yang dipakai banyak fitur memakai teks di tabel di bawah. Teks validasi per isian ada di `11` §4, dan pesan per fitur di `11` §5. | RECOMMENDATION |

| Keadaan | Teks untuk staf | Teks untuk siswa | Rujukan |
|---|---|---|---|
| Di luar hak | Anda tidak berhak membuka data ini. | Kamu tidak berhak membuka halaman ini. | `04` §4.1 |
| Di luar batas mundur | Tanggal 5 Oktober 2026 di luar batas mundur (7 hari). Hubungi admin. | Tanggal 5 Oktober 2026 sudah lewat batas pengajuan. Hubungi wali kelas. | `04` §4.2 |
| Data sudah diubah orang lain | Data ini sudah diubah oleh Rina Wulandari pukul 07.40. Periksa data terbaru, lalu simpan lagi bila perlu. | — | `04` §4.6 |
| Halaman tidak ditemukan | Halaman tidak ditemukan. | Halaman tidak ditemukan. | — |
| Gangguan server | Terjadi gangguan. Coba lagi beberapa saat lagi. Bila berulang, laporkan kode 7F3A2C1B ke admin sekolah. | Terjadi gangguan. Coba lagi beberapa saat lagi. Bila berulang, laporkan kode 7F3A2C1B ke admin sekolah. | `11` GAL-13 |
| Sesi berakhir | Sesi berakhir. Login lagi untuk melanjutkan. | Sesi berakhir. Login lagi untuk melanjutkan. | ARS-47 |
| Sesi berakhir saat mengirim formulir | Sesi berakhir sebelum formulir terkirim. Login lagi, lalu kirim ulang formulir. | Sesi berakhir sebelum formulir terkirim. Login lagi, lalu kirim ulang formulir. | `11` GAL-07 |
| Kiriman kedaluwarsa (CSRF) | Kiriman tidak dapat diproses karena halaman sudah terlalu lama dibuka. Periksa isian, lalu kirim lagi. | Kiriman tidak dapat diproses karena halaman sudah terlalu lama dibuka. Periksa isian, lalu kirim lagi. | `11` GAL-06 |
| Permintaan rusak | Permintaan tidak dapat diproses. Muat ulang halaman, lalu coba lagi. | Permintaan tidak dapat diproses. Muat ulang halaman, lalu coba lagi. | `11` GAL-02 |
| Terlalu banyak permintaan | Terlalu banyak permintaan. Tunggu sebentar, lalu coba lagi. | Terlalu banyak permintaan. Tunggu sebentar, lalu coba lagi. | `11` GAL-10 |
| Pemeliharaan | Spensada sedang diperbarui. Coba lagi dalam beberapa menit. | Spensada sedang diperbarui. Coba lagi dalam beberapa menit. | `11` GAL-17 |
| Wajib ganti password | Ganti password sebelum melanjutkan. | Ganti password sebelum melanjutkan. | `02` §2 |

## 10. Dokumen cetak

### 10.1 Aturan umum

| ID | Aturan | Status |
|---|---|---|
| UI-57 | **Cetak dari browser.** Halaman yang dicetak dari browser, yaitu slip akun, memakai CSS cetak: menu, tombol, dan pesan kilat disembunyikan, latar berwarna dihilangkan, dan baris tabel tidak terpotong antarhalaman. Halaman cetak menampilkan petunjuk "Cetak dengan skala 100% dan tanpa header atau footer browser." Laporan dan kartu memakai PDF dari mPDF, bukan cetak browser, agar ukurannya tepat. | RECOMMENDATION |

### 10.2 Slip akun

| ID | Aturan | Status |
|---|---|---|
| UI-58 | **8 slip per A4.** Halaman slip berukuran A4 tegak dengan 8 slip berukuran A7 (±105 × 74 mm), dalam 2 kolom × 4 baris, dengan garis potong putus-putus. Margin halaman 0, dan setiap slip memiliki ruang dalam ±6 mm agar isi tetap tercetak di printer yang tidak dapat mencetak sampai tepi. | DECISION (8 slip A7 dengan garis potong); RECOMMENDATION (margin) |
| UI-59 | **Isi slip.** Logo dan nama sekolah, judul "Akun Spensada", nama, NISN, kelas, password awal, alamat aplikasi, dan petunjuk: "1. Login dengan NISN dan password di atas. 2. Ganti password, lalu simpan baik-baik. Lupa password? Hubungi wali kelas." Password memakai huruf berlebar tetap berukuran ±16 pt, dan panjangnya 8 karakter dari 31 huruf kecil dan angka tanpa karakter yang mirip (`12` SEC-05, `02` §7.2 butir 5). Password tampil sebagai 2 kelompok 4 karakter dengan jarak, misalnya `k7mp 3xha`. Jarak itu hanya tampilan, dan tidak diketik. Halaman slip di layar menampilkan peringatan "Password hanya tampil sekali. Cetak atau simpan sekarang." (FS-AKN-05). Slip satu siswa hasil reset memakai isi yang sama dalam satu halaman. | DECISION (password dengan huruf berukuran besar dan petunjuk lengkap); RECOMMENDATION (rincian) |

### 10.3 PDF laporan (R2)

| ID | Aturan | Status |
|---|---|---|
| UI-60 | **Kop, kaki, dan isi.** Setiap PDF dimulai dengan kop: logo di kiri, nama sekolah dengan huruf tebal kapital, alamat, dan garis ganda di bawahnya. Setelah kop: judul laporan, cakupan, rentang, dan keterangan "belum final" bila perlu (`13` IE-03, IE-06). Kaki setiap halaman memuat "Dibuat oleh <nama> pada 13 Okt 2026, 07.32 WIB" dan "Halaman 1 dari 3". Ukuran A4, dengan orientasi mengikuti `13` IE-10. Status di tabel ditulis dengan teks atau huruf singkat (§4.2), dan warna hanya sebagai pelengkap, agar tetap terbaca bila dicetak hitam-putih. | DECISION (kop dan kaki halaman); RECOMMENDATION (rincian) |
| UI-61 | **Blok tanda tangan.** Rekap per rombel (LP-01) dan rekap rapor semester (LP-03) memuat blok tanda tangan di kanan bawah halaman terakhir: "Kota Contoh, 13 Oktober 2026", "Wali Kelas 7A,", ruang tanda tangan ±25 mm, nama wali kelas, lalu "NIP. <nip>". Bila NIP kosong, baris "NIP." tetap dicetak untuk ditulis tangan. Kota berasal dari isian "Kota/kabupaten" di identitas sekolah. Bila isian itu kosong, hanya tanggal yang dicetak. NIP berasal dari isian opsional di akun staf. Kedua isian ditambahkan bersama export PDF di R2 (`06` §5.1, §6.1). | DECISION (tempat dan tanggal cetak, "Wali Kelas 7A", ruang tanda tangan, nama wali kelas, NIP opsional di akun staf, dan isian "Kota/kabupaten"); RECOMMENDATION (letak, ukuran ruang, dan perilaku bila isian kosong) |

### 10.4 Kartu siswa (R3)

| ID | Aturan | Status |
|---|---|---|
| UI-62 | **Mengikuti kartu lama.** Kartu siswa baru dan kartu pengganti mengikuti tata letak kartu OSIS yang sekarang dipakai. Sekolah menyerahkan contoh kartu, berupa kartu fisik, foto, atau file desain, sebelum R3. Desain lalu dirinci saat FS-KRT-01 dirinci. Isinya tetap mengikuti FR-KRT-01: foto, nama, NISN, dan QR berisi NISN polos (C-04). OQ-13 tetap terbuka sampai contoh diterima. | DECISION |
| UI-63 | **Cetak sebagai PDF A4.** mPDF membuat PDF A4 berisi 10 kartu (2 × 5) dengan garis potong, untuk dicetak di kertas foto atau lembar PVC lalu dipotong dan dilaminasi satu per satu. Susunan 10 kartu mengandaikan kartu lama berukuran standar 85,6 × 54 mm. QR dibuat di server dengan chillerlan/php-qrcode 6 (lisensi MIT atau Apache-2.0). | DECISION (PDF A4 dari mPDF, 10 kartu 2 × 5, garis potong, kertas foto atau lembar PVC lalu dilaminasi, chillerlan/php-qrcode 6); RECOMMENDATION (syarat ukuran 85,6 × 54 mm) |
| UI-64 | **Aturan QR kartu.** QR berisi tepat 10 digit NISN, hitam di atas putih, dengan zona kosong paling kecil 4 modul, tingkat koreksi galat M yang diatur jelas karena bawaan chillerlan adalah L, dan lebar paling kecil 20 mm termasuk zona kosong (±0,69 mm per modul), agar mudah dibaca webcam (R-03). QR disisipkan ke PDF sebagai gambar vektor (SVG), agar modul tetap tajam. Hasil cetak pertama diuji dengan kiosk sebelum dicetak massal. | RECOMMENDATION |

## 11. Flyer kehadiran (R2)

| ID | Aturan | Status |
|---|---|---|
| UI-65 | **Canvas API.** Flyer digambar dengan JavaScript di elemen canvas berukuran 1080×1350 px, tanpa library. Data diambil dari server sebagai JSON yang dibaca dengan aturan baca yang sama dengan laporan (`13` IE-01). Pratinjau di halaman memakai canvas yang sama yang diperkecil dengan CSS, sehingga hasil unduhan sama persis dengan pratinjau. Tombol "Unduh PNG" memakai `canvas.toBlob(simpan, 'image/png')`, dengan `simpan` sebagai fungsi yang menerima Blob. Logo sekolah diambil dari alamat logo di domain yang sama, sehingga canvas dapat diekspor. | DECISION (Canvas API, 1080 px, dan pratinjau dari canvas yang sama); RECOMMENDATION (rincian) |
| UI-66 | **Potret 4:5.** Flyer per rombel dan flyer total sekolah sama-sama berukuran 1080×1350 px. | DECISION |
| UI-67 | **Isi dan susunan.** Dari atas: kepala berwarna utama dengan logo dan nama sekolah, judul "Kehadiran siswa", tanggal panjang, dan cakupan ("Kelas 7A" atau "Seluruh sekolah"). Lalu persentase kehadiran berukuran besar, jumlah siswa yang memiliki hari sekolah, dan ubin berwarna per status: Hadir, Terlambat, Izin, Sakit, Dispensasi, Alpa, serta Belum hadir selama data belum final (`13` LP-08). Flyer total menambah baris per tingkat dengan persentasenya. Kaki flyer memuat cap "Data hari ini belum final, dibuat pukul 07.32 WIB" bila perlu (`13` IE-03) dan nama produk "Spensada". Flyer tidak memuat nama, foto, atau NISN siswa. | RECOMMENDATION |
| UI-68 | **Nama file.** `spensada_flyer_<cakupan>_<YYYYMMDD>.png`, misalnya `spensada_flyer_7A_20261013.png` dan `spensada_flyer_sekolah_20261013.png`, mengikuti pola `13` IE-07 dengan satu tanggal. | RECOMMENDATION |

## 12. Aksesibilitas, perangkat, dan kinerja

| ID | Aturan | Status |
|---|---|---|
| UI-69 | **Aksesibilitas.** Target WCAG 2.2 tingkat AA. Setiap halaman memiliki `lang="id"`, tautan "Lewati ke isi", landmark `header`, `nav`, dan `main`, satu `h1`, cincin fokus yang terlihat (2 px `--fokus` atau `--fokus-terang` dengan jarak 2 px, UI-11), label untuk setiap isian, dan urutan fokus yang mengikuti urutan baca. Halaman tetap dapat dipakai saat diperbesar 200%. Animasi dimatikan bila pengguna memilih `prefers-reduced-motion`. | RECOMMENDATION |
| UI-70 | **Browser.** Kiosk: Chrome atau Edge terbaru di Windows (A-04). Panel, portal, dan halaman publik: fitur yang sudah didukung Chrome dan Edge 100, Samsung Internet 19, Firefox 100, dan Safari 15.4 ke atas, termasuk `<dialog>`, `gap`, `aspect-ratio`, dan `:focus-visible`. CSS nesting, `:has()`, dan container query tidak dipakai untuk tata letak, karena ponsel lama siswa mungkin belum mendukungnya. | RECOMMENDATION |
| UI-71 | **Batas ukuran.** Ukuran per halaman panel, portal, dan halaman publik, di luar foto. CSS dan JavaScript diukur setelah kompresi gzip, seperti yang dikirim Nginx (`07` ARS-04): CSS paling besar 50 KB dan JavaScript paling besar 40 KB. Bootstrap 5.3 memakai sekitar 31 KB CSS dan 24 KB JavaScript dari batas itu. Font paling besar 60 KB, diukur sebagai ukuran file WOFF2. Foto di satu halaman daftar paling besar ±250 KB, yaitu 50 foto kecil × ±5 KB. Batas ini diperiksa saat fase implementasi lewat tab Network di DevTools. Sebelum keputusan pemilik 2026-10-10, batas CSS 40 KB dan JavaScript 30 KB diukur sebelum kompresi. | DECISION (diukur setelah gzip, §2.6); RECOMMENDATION (angka) |
| UI-72 | **Tanpa JavaScript.** Bila JavaScript mati, panel dan portal tetap dapat dibuka dan formulir tetap dapat dikirim (UI-04). Dashboard menampilkan "Muat ulang halaman untuk data terbaru." sebagai pengganti pembaruan berkala. | RECOMMENDATION |

## 13. Aset dan struktur tampilan

Bagian ini melengkapi struktur folder `07` §5.3.

| ID | Aturan | Status |
|---|---|---|
| UI-73 | **File CSS dan JavaScript Bootstrap.** CSS ditulis tanpa build. Panel, portal, akun, halaman galat, dan halaman publik memuat tiga file CSS berurutan: `vendor/bootstrap/5.3.8/css/bootstrap.min.css`, `css/token.css` (token warna, jarak, dan huruf di §4, beserta `@font-face`), lalu `css/spensada.css` (penimpaan variabel Bootstrap, komponen aplikasi §5, kerangka area §6, dan gaya cetak dalam `@media print`). Halaman yang memakai komponen Bootstrap berjavascript memuat `vendor/bootstrap/5.3.8/js/bootstrap.bundle.min.js`, yang sudah memuat Popper. Kiosk tidak memakai Bootstrap: halaman kiosk memuat `css/token.css` dan `kiosk/kiosk.css`, sehingga tokennya sama. Alamat aset panel, portal, dan publik diberi parameter versi aplikasi, misalnya `?v=1.0.0`, agar cache browser diperbarui setelah rilis. Kiosk memakai versi cache Service Worker (ARS-22). Halaman galat statis di `public/galat/` memakai `token.css` dan sedikit gaya sendiri, tanpa Bootstrap. | DECISION (Bootstrap dan satu file CSS aplikasi, §2.6); RECOMMENDATION (nama file dan urutan) |
| UI-74 | **Nama kelas dan token.** Kelas Bootstrap dipakai apa adanya di view, misalnya `btn btn-primary` untuk tombol utama. Kelas aplikasi untuk komponen tanpa padanan Bootstrap memakai bahasa Indonesia dengan huruf kecil dan tanda hubung, misalnya `.chip-status`, `.status-hadir`, `.ubin-angka`, dan `.tabel-angka`. Kelas aplikasi tidak menimpa aturan kelas Bootstrap secara langsung. Warna dan ukuran Bootstrap diubah lewat variabel `--bs-*` (UI-08). Pengecualian: warna fokus `.form-control:focus`, `.form-select:focus`, dan `.form-check-input:focus` tidak dapat diubah lewat variabel, sehingga `spensada.css` menimpa aturan itu langsung agar cincin fokus memenuhi UI-11 dan UI-69. Token memakai awalan sesuai §4, misalnya `--warna-utama` dan `--status-alpa-muda`. Tidak ada gaya di atribut `style`, karena CSP tidak mengizinkan gaya di dalam HTML (`12` SEC-36). Karena itu bilah kemajuan Bootstrap (`.progress-bar` dengan `style="width: …"`) tidak dipakai. Bilah kemajuan memakai elemen `<progress>`, atau lebarnya diatur lewat properti CSSOM dari modul JavaScript. | DECISION (Bootstrap, §2.6); RECOMMENDATION (rincian) |
| UI-75 | **Label dan komponen di view.** Label kode nilai (§9.2), status (§4.2), dan istilah layar (§9.1) ditulis sekali di `app/Config/Label.php`, lalu dipakai view, file export, PDF, dan flyer. Komponen yang menerima data, misalnya chip status, foto siswa, dan pesan kilat, ditulis sebagai view komponen di `app/Views/komponen/`. Setiap area memiliki layout di `app/Views/layout/`. | RECOMMENDATION |
| UI-76 | **JavaScript halaman.** Modul ES kecil di `public/aset/js/` (ARS-19), misalnya `polling.js` (ARS-50), `pratinjau-unggah.js`, dan `flyer.js` (R2). Menu lipat dan dialog memakai komponen Bootstrap (UI-28), sehingga tidak memerlukan modul sendiri. Modul tidak bergantung satu sama lain, kecuali modul bantu format waktu (UI-55). | RECOMMENDATION |

Lokasi aset:

```text
public/aset/
  css/          token.css, spensada.css (UI-73)
  js/           modul per halaman (UI-76)
  ikon/         ikon.svg (sprite Lucide) dan LICENSE
  vendor/
    bootstrap/5.3.8/            css/bootstrap.min.css, js/bootstrap.bundle.min.js, dan LICENSE
    plus-jakarta-sans/2.071/   file WOFF2 dan OFL.txt
    zxing-wasm/3.1.4/           (ARS-10)
  kiosk/        kiosk.css dan modul kiosk (ARS-20)
app/Config/Label.php            label kode nilai dan istilah layar
app/Views/layout/               layout per area
app/Views/komponen/             view komponen
```

File TTF untuk mPDF (UI-15) ditaruh di luar `public/`, misalnya `app/ThirdParty/font/plus-jakarta-sans/`, karena hanya dibaca server.

| ID | Aturan | Status |
|---|---|---|
| UI-77 | **Contoh tampilan.** `docs/08-contoh-tampilan.html` adalah satu file HTML mandiri yang menampilkan keputusan dokumen ini: warna dan status, komponen dasar, kiosk dengan simulator bunyi dan lama tampil, dashboard panel, portal siswa, slip, dan flyer. File itu tidak memuat apa pun dari internet, karena font dan ikon disisipkan di dalamnya. Bila contoh berbeda dengan dokumen ini, dokumen ini yang berlaku. Bila desain berubah, dokumen ini diperbarui lebih dulu, lalu file contoh. Contoh dibuat sebelum keputusan Bootstrap (§2.6), sehingga menjadi acuan tampilan (warna, susunan, dan ukuran), bukan acuan markup atau nama kelas. | DECISION (contoh final tanpa pilihan yang tidak dipakai, tanpa CDN, dan dokumen ini sebagai sumber kebenaran); RECOMMENDATION (rincian) |

## 14. Perubahan pada dokumen lain

Perubahan karena keputusan Session 7:

| Dokumen | Versi | Perubahan |
|---|---|---|
| `00-project-overview.md` | 0.7 | OQ-13 terjawab sebagian. R-19 diperbarui. Glosarium "Rombel" dan "Tingkat" memuat label layar. Kondisi repository (§7.3), peta dokumen, dan progres sesi diperbarui. |
| `01-product-requirements.md` | 0.7 | FR-KRT-01 dan NFR-11 diperbarui. Daftar library memuat flyer dengan Canvas API, chillerlan/php-qrcode, Plus Jakarta Sans, dan Lucide. OQ-13 di §8 terjawab sebagian. §9 merujuk `08`. |
| `03-user-flow.md` | 0.6 | UF-06 langkah 4 (izin suara dan tes bunyi), UF-10 langkah 7 (lama tampil), UF-24, dan UF-26 diperbarui. §1 merujuk `08`. |
| `04-feature-specification.md` | 0.4 | §2.6 (keputusan Session 7) ditambahkan. §1 dan pengantar catatan antarmuka awal merujuk `08`. §4.8, FS-AKN-05, FS-MD-03, FS-MD-07, FS-KIO-01, FS-KIO-02, FS-PRS-05, FS-LAP-03, FS-LAP-04, §11, §13, §14.1, dan §14.2 diperbarui. |
| `05-business-rules.md` | 0.5 | Contoh pesan kiosk di `05` §4.2 merujuk teks final di `08` §7.3. Kepala dokumen dan §15 diperbarui. |
| `06-database-design.md` | 0.3 | §2.5 (keputusan Session 7) ditambahkan. Kolom `akun.nip` (R2) dan kunci `pengaturan.sekolah_kota` (R2) ditambahkan. Keterangan `siswa.foto_file` memuat foto kecil. §1, §14.1, dan §18 merujuk `08`. |
| `07-system-architecture.md` | 0.2 | §2.4 (keputusan Session 7) ditambahkan. ARS-10 (flyer dengan Canvas API, chillerlan/php-qrcode, Plus Jakarta Sans, dan Lucide), ARS-19, ARS-22 (font dan ikon di cache kiosk), ARS-51, ARS-53 (foto kecil), §2.1, §2.3, §5.3, §17.1, dan §19 diperbarui. |
| `13-reporting-import-export.md` | 0.3 | Judul kolom yang memuat "Rombel" diganti "Kelas", termasuk nama file contoh di IE-07. IE-06, IE-10, IE-12, LP-08, §1, §8, dan §9 merujuk `08`. |

File `docs/08-contoh-tampilan.html` juga dibuat di Session 7 (UI-77). `02` tidak berubah.

Perubahan karena keputusan Session 8 dicatat di `09` §16. Perubahan karena keputusan Session 9 dicatat di `12` §20.

Perubahan karena keputusan pemilik proyek 2026-10-10 (Bootstrap 5, §2.6): `07` §2.4, §2.7, ARS-10 (Bootstrap 5.3.8), ARS-19, ARS-22 butir 3, dan §5.3; `12` SEC-36 (`img-src data:` untuk ikon bawaan Bootstrap); `01` daftar library NFR-16; dan `00` §7.1.

## 15. Pertanyaan terbuka dan nilai yang dipastikan nanti

Session 7 menjawab sebagian OQ-13 dan tidak menambah OQ baru. Session 8 menetapkan daftar halaman, route, dan menu (`09`). Session 9 menetapkan teks validasi, pesan galat, dan keamanan (`11`, `12`).

| Hal | Rujukan | Dipastikan di |
|---|---|---|
| Contoh kartu OSIS lama dan desain kartu rinci | OQ-13, UI-62 | Sekolah, sebelum R3 |
| Daftar halaman, route, dan isi menu final | UI-31 | Ditetapkan di Session 8 (`09` §4 s.d. §13) |
| Teks validasi per isian dan pesan galat rinci | UI-56 | Ditetapkan di Session 9 (`11` §4 s.d. §6) |
| PIN petugas untuk tindakan berisiko di kiosk | UI-48 | Ditetapkan di Session 9 (`12` SEC-21, SEC-22) |
| Cache browser untuk foto kecil, yang saat ini tidak disimpan (ARS-52) | UI-22 | Ditetapkan di Session 9: foto kecil tetap `no-store` (`12` SEC-52) |
| Format dan panjang NIP | UI-61 | Menjelang R2 |
| Ukuran huruf, resolusi kamera, dan volume bunyi di laptop sekolah | UI-39, UI-45 | Uji di laptop sekolah sebelum uji coba R1 |
| Rincian halaman publik | UI-38 | Menjelang R3 |
| Teks pemberitahuan privasi di halaman login dan portal | UI-38 | Teks bawaan sudah ada (`06` §6.1). Sekolah memeriksanya sebelum uji coba R1 (`12` SEC-68). |
| Ukuran CSS dan JavaScript per halaman setelah gzip | UI-71 | FASE-00 (layout), diperiksa ulang di FASE-09 |

## 16. Traceability

### 16.1 Fitur dan ketentuan → tampilan

| Fitur atau ketentuan | Tampilan |
|---|---|
| FS-AKN-01, FS-AKN-02 | UI-38, UI-56, `11` §5.1 |
| FS-AKN-05 | UI-57 s.d. UI-59 |
| FS-MD-01 | UI-09, UI-38 |
| FS-MD-03 | UI-51 |
| FS-MD-04, FS-MD-07, FS-MD-08 | UI-22 s.d. UI-25, UI-54 |
| FS-KIO-01 s.d. FS-KIO-03 | UI-39 s.d. UI-48, `11` §5.3 |
| FS-KIO-05, FS-KIO-06 | §4.2, UI-23 |
| FS-PRS-05 | UI-01, UI-12 |
| FS-PRS-06 | UI-34 |
| FS-PRS-08, FS-PRS-09 | UI-30, UI-23 |
| FS-IZN-01 | UI-37 |
| FS-LAP-01 | UI-32, §5 (diperbarui berkala) |
| FS-LAP-02 | UI-33 |
| FS-LAP-03, FS-LAP-04 | UI-26, UI-27, UI-36 |
| FS-LAP-05 (R2) | UI-60, UI-61 |
| FS-LAP-06 (R2) | UI-65 s.d. UI-68 |
| FS-KRT-01 (R3) | UI-62 s.d. UI-64 |
| `04` §4.2, §4.3, §4.7, §4.8 | §5, UI-53, UI-56 |
| NFR-01, NFR-11 | UI-02, UI-18, UI-70 |
| NFR-07 | UI-28 (isian formulir), UI-56, `11` VAL-07 |
| NFR-10, R-17 | UI-03, UI-22 (foto kecil `no-store`), UI-38 (pemberitahuan privasi) |
| R-19 | UI-65 |
| OQ-13 | UI-62 |

### 16.2 Catatan antarmuka awal `04` → `08`

| Fitur | Catatan di `04` | Diterapkan di |
|---|---|---|
| FS-AKN-01 | Satu kolom "NISN atau username", identitas sekolah, teks bantuan | UI-38 |
| FS-AKN-02 | Aturan password di formulir, tombol tampilkan password | UI-38 |
| FS-AKN-03 | Password awal tampil sekali dengan tombol salin dan cetak | UI-59 (pola peringatan yang sama) |
| FS-AKN-04 | Daftar akun stasiun digabung dengan status stasiun | UI-31, `09` HAL-KIO-02 |
| FS-AKN-05 | Peringatan sebelum slip dibuat, halaman slip ramah cetak | UI-57 s.d. UI-59, §5 (konfirmasi) |
| FS-MD-01, FS-MD-07 | Pratinjau logo dan foto | §5 (unggah file) |
| FS-MD-02 | Tahun ajaran aktif ditandai jelas | UI-29 |
| FS-MD-03 | Label "rombel" | UI-51 |
| FS-MD-04 | Tanda tanpa nomor WA dan tanpa foto, nomor WA mudah dibaca | UI-24, UI-54 |
| FS-MD-05, FS-MD-09 | Riwayat penempatan dan atribut tambahan di profil | `09` HAL-MD-06 |
| FS-MD-06, FS-MD-08 | Pratinjau dengan tab, pratinjau tabel | §5 (tab, tabel) |
| FS-KIO-01 s.d. FS-KIO-03 | Bilah status, tombol petugas, hasil besar, foto besar, kamera selalu tampak, penghitung mencolok | UI-39 s.d. UI-44 |
| FS-KIO-05 | Daftar stasiun diperbarui berkala | §5 (diperbarui berkala), §4.2 |
| FS-KIO-06 | Dikelompokkan per stasiun dan alasan | `09` HAL-KIO-03 |
| FS-PRS-01 s.d. FS-PRS-04 | Tabel pola mingguan, kalender jadwal dan libur, aturan berlaku di samping isian | UI-27, `09` HAL-PRS-01 s.d. HAL-PRS-04 |
| FS-PRS-05 | Warna dan ikon status | UI-12 |
| FS-PRS-06 | Pintasan presensi manual, foto besar | UI-33, UI-34 |
| FS-PRS-07 | Pengingat alasan dapat dibaca siswa | §5 (isian formulir) |
| FS-PRS-08 | Tanda mode darurat di semua halaman | UI-30 |
| FS-PRS-09 | Daftar centang dengan foto kecil dan centang semua | UI-23, `09` HAL-PRS-06 |
| FS-PRS-10, FS-PRS-11 | Contoh rentang tanggal, data lama dan baru berdampingan | §5 (tanggal), `09` HAL-PRS-09, HAL-PRS-10 |
| FS-IZN-01 | Formulir sederhana untuk ponsel | UI-37 |
| FS-IZN-02 s.d. FS-IZN-06 | Pintasan input izin, pratinjau dispensasi, lampiran tampil langsung, riwayat keputusan, saringan cepat | §5, `09` HAL-IZN-04 s.d. HAL-IZN-09 |
| FS-LAP-01 | Rombel wali kelas di atas, belum hadir disorot, dapat dipakai di ponsel | UI-32 |
| FS-LAP-02 | Siswa berpenanda di atas, pemilih tanggal | UI-33, §5 |
| FS-LAP-03 | Kolom persentase dapat diurutkan | §5 (tabel) |
| FS-LAP-04 | Kalender bulanan berwarna di portal | UI-36 |

## Riwayat perubahan

| Versi | Tanggal | Perubahan |
|---|---|---|
| 0.1 | 2026-10-04 | Draft awal dari Session 7: keputusan tampilan, prinsip, warna dan status, huruf, ikon, foto, komponen, tata letak panel dan portal, kiosk, gaya bahasa, label dan format, dokumen cetak, flyer, aksesibilitas, aset, dan traceability. R-19 terjawab, dan OQ-13 terjawab sebagian. |
| 0.2 | 2026-10-05 | Keputusan Session 8 (§2.4, `09`, `10`). UI-28 (komponen pencarian siswa), UI-31 (menu final di `09` §4.1, termasuk "Kelas saya"), tanda stasiun disorot di §4.2, §9.2 (label kode di `09` §14), ikon "Kelas saya" di §4.5, UI-29 (menu lipat tanpa JavaScript), UI-38 (halaman 500), UI-44 (pita versi kiosk), kolom `libur.cakupan` di §9.2, kepala dokumen, §14, §15, dan §16.2 diperbarui. Contoh menu di `08-contoh-tampilan.html` memuat "Kelas saya". |
| 0.3 | 2026-10-05 | Keputusan Session 9 (§2.5, `11`, `12`). UI-28 (galat formulir, teks bantuan unggah, dan pesan sesi berakhir), UI-31 (menu PIN petugas dan log aktivitas), UI-38 (pengingat logout, pemberitahuan privasi, halaman 400, 413, 429, 503, dan kode laporan di halaman 500), UI-48 (PIN petugas), UI-56 (pesan umum baru dan pesan gangguan server dengan kode laporan), UI-59 (password slip 8 karakter), UI-74 (tanpa atribut `style`), §9.2, kepala dokumen, §14, §15, dan §16.1 diperbarui. |
| 0.4 | 2026-10-10 | Keputusan pemilik proyek 2026-10-10 (§2.6): Bootstrap 5.3 untuk panel, portal, akun, galat, dan halaman publik. §2.1 (cara membangun tampilan), UI-08 (penimpaan variabel Bootstrap), UI-28 (komponen Bootstrap), UI-71 (batas diukur setelah gzip: CSS 50 KB, JavaScript 40 KB), UI-73 (`token.css`, `spensada.css`, dan file Bootstrap), UI-74 (kelas Bootstrap dan kelas aplikasi), UI-76, UI-77 (contoh sebagai acuan tampilan), §13 (lokasi aset), §14, dan §15 diperbarui. |
