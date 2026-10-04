# Spensada — Product Requirements

| Item | Nilai |
|---|---|
| Versi | 0.5 (draft) |
| Tanggal | 2026-10-04 |
| Sumber | Discovery Session 2 (Product & Feature Definition). Diperbarui dengan keputusan Session 3 (role, akun, dan stasiun scan) Session 4 (aturan bisnis, `05`), Session 4b (spesifikasi fitur, `04`), dan Session 5 (database, `06`; laporan, import, dan export, `13`). |
| Bergantung pada | [00-project-overview.md](00-project-overview.md): label status, glosarium, risiko (`R-xx`), asumsi (`A-xx`), dan pertanyaan terbuka (`OQ-xx`) |

## 1. Cara membaca dokumen ini

- **ID:**
  - Kebutuhan fungsional memakai `FR-<MODUL>-<NN>`.
  - Kebutuhan non-fungsional memakai `NFR-<NN>`.
  - Batasan memakai `C-<NN>`.
  - Acceptance criteria memakai `AC-<NN>`.
  - ID tidak pernah dinomori ulang. Requirement yang batal ditandai `DEPRECATED`, bukan dihapus.
- **Kategori:**
  - *Core*: produk tidak berfungsi tanpa kebutuhan ini.
  - *Supporting*: meningkatkan pengalaman atau operasional.
  - *Optional*: awalnya opsional, tetapi diputuskan masuk versi pertama.
- **Rilis:** R1, R2, atau R3 (lihat `00` §6.1).
- **Status:** memakai label dari `00`. Bila satu baris memuat keputusan dan usulan sekaligus, keduanya disebut.

Dokumen ini menjawab *apa* yang dibutuhkan. Rincian setiap fitur dan acceptance criteria-nya ada di `04`. Aturan rinci, struktur data, route, dan tampilan ditulis di dokumen `02`–`10`.

## 2. Modul

| Kode | Modul | Rilis |
|---|---|---|
| MD | Master data, foto, import | R1 |
| AKN | Akun dan akses | R1 |
| KIO | Kiosk dan sinkron | R1 |
| PRS | Presensi dan aturan | R1 |
| IZN | Izin, sakit, dan dispensasi | R1 |
| LAP | Dashboard, rekap, export, flyer | R1–R2 |
| WA | Notifikasi WhatsApp | R2 |
| INF | Jadwal, pengumuman, halaman publik | R3 |
| KRT | Cetak kartu | R3 |

## 3. Kebutuhan fungsional

### 3.1 MD — Master data, foto, import

| ID | Kebutuhan | Aktor | Kategori | Rilis | Status |
|---|---|---|---|---|---|
| FR-MD-01 | Admin mengelola tahun ajaran beserta semesternya, dan menandai satu tahun ajaran sebagai aktif. | Admin | Core | R1 | DECISION (master data); RECOMMENDATION (struktur tahun ajaran dan semester) |
| FR-MD-02 | Admin mengelola rombel per tahun ajaran (nama rombel dan tingkat), dan menetapkan satu wali kelas untuk setiap rombel. | Admin | Core | R1 | DECISION |
| FR-MD-03 | Admin mengelola data siswa. Atribut minimal: NISN (unik, 10 digit), nama lengkap, status aktif, nomor WhatsApp orang tua/wali, dan foto. Nomor WA bersifat opsional; bila diisi, formatnya divalidasi dan disimpan dalam format baku 62…. Atribut opsional lainnya: NIS, jenis kelamin, tanggal lahir, alamat rumah, dan nama orang tua/wali. Status aktif disimpan sebagai periode masa aktif (`06` §6.6). | Admin | Core | R1 | CONFIRMED (NISN unik); DECISION (nomor WA, foto; nomor WA opsional, Session 4b; atribut opsional dan masa aktif, Session 5) |
| FR-MD-04 | Admin menempatkan siswa ke rombel per tahun ajaran, termasuk saat kenaikan kelas, sehingga riwayat tahun sebelumnya tetap utuh. Penempatan massal dilakukan per rombel asal ke rombel tujuan, atau lewat file import penempatan (`13` IM-02). | Admin | Core | R1 | DECISION (Session 5) |
| FR-MD-05 | Admin mengimpor data siswa dari file Excel (.xlsx) atau CSV. Setiap baris divalidasi (NISN 10 digit, NISN ganda, kolom wajib, dan format nomor WA bila diisi). Baris yang gagal dilaporkan beserta alasannya. Baris dengan NISN yang sudah ada dilewati dan dilaporkan. Kolom template mengikuti `13` §6.1. | Admin | Supporting | R1 | DECISION (import Excel/CSV; nomor WA opsional, Session 4b; NISN yang sudah ada dilewati dan kolom template, Session 5); RECOMMENDATION (validasi per baris) |
| FR-MD-06 | Admin, dan wali kelas untuk rombelnya, mengunggah foto siswa satu per satu dari halaman data siswa. | Admin, wali kelas | Supporting | R1 | DECISION (Session 3) |
| FR-MD-07 | Admin mengunggah banyak foto sekaligus. Sistem mencocokkan nama file dengan NISN, melaporkan file yang tidak cocok, dan memperkecil ukuran foto secara otomatis. Nama file diawali 10 digit NISN (`13` IM-03). | Admin | Supporting | R1 | DECISION (format nama file, OQ-12, Session 5); RECOMMENDATION (rincian) |
| FR-MD-08 | Admin mengatur identitas sekolah: nama resmi, alamat, dan logo. Nama produk tetap "Spensada". | Admin | Supporting | R1 | DECISION (OQ-01) |
| FR-MD-09 | Wali kelas dapat mengubah nomor WhatsApp orang tua/wali dan foto siswa di rombelnya. Siswa tidak dapat mengubah nomor WA orang tua/wali. Setiap perubahan nomor WA dan foto dicatat: siapa, kapan, serta nilai lama dan baru (untuk foto cukup penggantiannya). | Admin, wali kelas | Supporting | R1 | DECISION (Session 3) |
| FR-MD-10 | Admin menambah atribut siswa sendiri (label, tipe, wajib atau tidak, dan urutan). Nilainya diisi per siswa dan lewat import. Atribut tambahan tidak dipakai logika presensi, rekap, atau filter laporan. | Admin | Supporting | R1 | DECISION (Session 5) |

### 3.2 AKN — Akun dan akses

| ID | Kebutuhan | Aktor | Kategori | Rilis | Status |
|---|---|---|---|---|---|
| FR-AKN-01 | Admin, staf, dan siswa login dengan akun masing-masing. Setiap halaman hanya dapat dibuka oleh role yang berhak, sesuai matriks hak akses di `02`. | Admin, staf, siswa | Core | R1 | CONFIRMED (role); DECISION (hak akses rinci, Session 3) |
| FR-AKN-02 | Admin mengelola akun staf dan akun stasiun: membuat, menonaktifkan, mereset password, dan memberi role untuk akun staf. Semua guru dan staf mendapat akun staf sejak R1. Akun staf login dengan username. | Admin | Core | R1 | DECISION (Session 3) |
| FR-AKN-03 | Setiap laptop stasiun scan memakai akun stasiun sendiri (satu akun per laptop). Akun ini hanya dapat memuat data kiosk, mencatat scan, dan sinkron. | Admin, akun stasiun | Core | R1 | DECISION (Session 3) |
| FR-AKN-04 | Pengguna dapat logout dan mengganti password sendiri. | Semua pengguna berakun | Core | R1 | DECISION (Session 3) |
| FR-AKN-05 | Setiap siswa aktif otomatis memiliki akun siswa dengan username NISN. Akun dibuat saat siswa ditambah atau diimpor, dan ikut nonaktif saat siswa nonaktif. | Sistem | Core | R1 | DECISION (OQ-14) |
| FR-AKN-06 | Password awal dan password hasil reset dibuat acak oleh sistem, dan wajib diganti saat login pertama. Password awal siswa dibagikan lewat slip akun yang dicetak per rombel oleh admin atau wali kelas. | Admin, wali kelas | Core | R1 | DECISION (OQ-14; mekanisme slip `02` §7.2, Session 4) |
| FR-AKN-07 | Password siswa direset oleh admin, atau oleh wali kelas untuk rombelnya. Password staf direset oleh admin. Tidak ada reset mandiri lewat email di v1. | Admin, wali kelas | Core | R1 | DECISION (Session 3) |
| FR-AKN-08 | Role bersifat tetap, dengan hak akses sesuai matriks di `02`. Satu akun staf dapat memiliki beberapa role, dan hak efektifnya adalah gabungan semua role tersebut. | Admin | Core | R1 | DECISION (OQ-02) |

### 3.3 KIO — Kiosk dan sinkron

Kiosk bekerja *local-first* (DECISION). Scan divalidasi dan dicatat di laptop lebih dulu, lalu dikirim ke server lewat sinkron.

| ID | Kebutuhan | Aktor | Kategori | Rilis | Status |
|---|---|---|---|---|---|
| FR-KIO-01 | Saat online, kiosk memuat data siswa aktif (NISN, nama, rombel, foto) ke penyimpanan lokal laptop. | Akun stasiun | Core | R1 | DECISION |
| FR-KIO-02 | Kiosk membaca QR kartu OSIS lewat webcam. Isi QR adalah NISN polos. | Siswa | Core | R1 | CONFIRMED |
| FR-KIO-03 | Kiosk juga menerima input dari scanner QR USB yang bekerja sebagai keyboard. | Siswa | Supporting | R1 | RECOMMENDATION |
| FR-KIO-04 | Setiap scan divalidasi terhadap data lokal. Kiosk langsung menampilkan foto, nama, rombel, jenis presensi (masuk/pulang), dan status scan (Hadir, Terlambat, atau pulang lebih awal), disertai bunyi. Siswa tidak perlu menekan apa pun. | Siswa, petugas | Core | R1 | DECISION (validasi lokal, foto); RECOMMENDATION (bunyi) |
| FR-KIO-05 | Kiosk menolak scan dengan pesan yang jelas bila NISN tidak ada di data lokal, scan berada di luar jendela masuk dan jendela pulang, hari itu bukan hari sekolah, atau siswa sedang libur. Untuk scan ganda, scan pertama yang berlaku, dan kiosk menampilkan bahwa siswa sudah tercatat beserta jamnya (`05` BR-JAM-06, BR-SCN-01 s.d. BR-SCN-03). | Siswa, petugas | Core | R1 | DECISION (penolakan di luar jendela, scan ganda, Session 4); RECOMMENDATION (NISN tidak dikenal, tampilan) |
| FR-KIO-06 | Setiap scan disimpan di laptop dengan ID unik dan jam scan. Jam scan adalah jam laptop yang dikoreksi dengan selisih terhadap jam server. | Sistem | Core | R1 | RECOMMENDATION |
| FR-KIO-07 | Kiosk mengirim scan yang belum tersinkron ke server secara otomatis setiap beberapa detik saat online. Kiosk juga menyediakan tombol sinkron manual. | Akun stasiun, petugas | Core | R1 | DECISION |
| FR-KIO-08 | Kiosk menampilkan status koneksi dan jumlah scan yang belum tersinkron. | Petugas | Core | R1 | RECOMMENDATION |
| FR-KIO-09 | Kiosk tetap dapat dipakai untuk scan saat internet putus, termasuk setelah halaman atau laptop dibuka ulang tanpa internet. | Petugas | Core | R1 | DECISION (scan saat offline); RECOMMENDATION (dibuka ulang saat offline) |
| FR-KIO-10 | Server menerima data sinkron secara idempotent: kiriman ulang tidak menggandakan data. Scan dari beberapa stasiun digabung: scan paling awal yang berlaku (`05` BR-SCN-03). | Sistem | Core | R1 | RECOMMENDATION (idempotent); DECISION (penggabungan, Session 4) |
| FR-KIO-11 | Saat sinkron, server memvalidasi jam scan: tanggal harus sesuai, jam tidak boleh di masa depan, dan selisih jam yang ekstrem ditandai untuk diperiksa staf (`05` BR-SCN-08). | Sistem | Core | R1 | RECOMMENDATION |
| FR-KIO-12 | Panel staf menampilkan status setiap stasiun scan: waktu sinkron terakhir, dan jumlah scan belum tersinkron yang dilaporkan stasiun. Status ini dipakai untuk memantau sinkron, sebelum mencabut akun stasiun, dan sebagai syarat pembuatan pesan "tidak hadir" (FR-WA-07). | Admin, guru piket | Supporting | R1 | DECISION (Session 4) |

### 3.4 PRS — Presensi dan aturan

| ID | Kebutuhan | Aktor | Kategori | Rilis | Status |
|---|---|---|---|---|---|
| FR-PRS-01 | Sistem mencatat presensi masuk dan presensi pulang setiap siswa per hari sekolah. | Sistem | Core | R1 | CONFIRMED |
| FR-PRS-02 | Admin mengatur aturan jam per hari dalam seminggu (pola mingguan) dan jadwal khusus untuk tanggal atau rentang tanggal. Aturan jam berisi jam buka scan masuk, jam masuk, toleransi terlambat, jam tutup sesi masuk, jam buka scan pulang, jam pulang, dan jam tutup sesi pulang. Jenis presensi ditentukan oleh jendela masuk dan jendela pulang (`05` §4). | Admin | Core | R1 | DECISION (Session 4); RECOMMENDATION (rincian isian) |
| FR-PRS-03 | Admin mengelola kalender sekolah: pola hari sekolah mingguan (default Senin–Sabtu), hari libur untuk semua siswa atau untuk tingkat/rombel tertentu, dan jadwal khusus (`05` §3). | Admin | Core | R1 | DECISION (Session 4) |
| FR-PRS-04 | Sistem menentukan status harian setiap siswa: Hadir, Terlambat, Izin, Sakit, Dispensasi, atau Alpa. Urutan prioritasnya: izin/sakit/dispensasi yang disetujui, koreksi status, presensi masuk, lalu Alpa (`05` BR-STS-02). | Sistem | Core | R1 | DECISION (Dispensasi dan urutan prioritas ditambahkan di Session 4) |
| FR-PRS-05 | Status Alpa ditentukan otomatis: hari sekolah bagi siswa, tanpa izin/sakit/dispensasi yang disetujui, dan tanpa presensi masuk (atau dikoreksi Tidak hadir), setelah sesi masuk ditutup dan di luar mode darurat. Data yang datang belakangan, seperti scan yang terlambat sinkron atau izin yang disetujui, langsung mengoreksi status. | Sistem | Core | R1 | DECISION (Alpa otomatis dan syaratnya); RECOMMENDATION (status dapat dihitung ulang, `05` BR-STS-06) |
| FR-PRS-06 | Staf menginput presensi manual, misalnya untuk siswa yang lupa kartu, kartu rusak, saat kiosk terganggu, atau siswa yang tiba setelah sesi masuk ditutup. Alasan wajib diisi, dan status dihitung dari jam yang diisi. Pelakunya: admin, guru piket (hari berjalan), wali kelas (rombelnya), dan guru BK, dalam batas mundur (FR-PRS-11). Presensi manual per rombel sekaligus hanya tersedia saat mode darurat (FR-PRS-10). Presensi manual yang salah input dibatalkan dengan alasan: datanya tetap tersimpan, tetapi tidak dipakai (`05` BR-KOR-11). | Admin, guru piket, wali kelas, guru BK | Core | R1 | DECISION (presensi manual dan pelaku: Session 3; alasan wajib dan per rombel: Session 4; pembatalan: Session 4b) |
| FR-PRS-07 | Admin, guru piket (hari berjalan), wali kelas (rombelnya), dan guru BK dapat mengoreksi status presensi menjadi Hadir, Terlambat, atau Tidak hadir, dengan alasan, dalam batas mundur (FR-PRS-11). Koreksi tidak tertimpa scan atau presensi manual yang datang belakangan. Koreksi dapat dihapus dengan alasan, dan tetap dapat disimpan saat ada izin yang disetujui, dengan peringatan (`05` BR-KOR-08, BR-KOR-09). Perubahan ke Izin, Sakit, atau Dispensasi dilakukan lewat FR-IZN-02. Setiap perubahan tercatat di log perubahan presensi: siapa, kapan, nilai lama, nilai baru, dan alasan. | Admin, guru piket, wali kelas, guru BK | Core | R1 | DECISION (Session 3; aturan koreksi Session 4; hapus koreksi dan koreksi saat ada izin, Session 4b) |
| FR-PRS-08 | Sesi masuk dan sesi pulang ditutup otomatis pada jam tutup sesi masing-masing. Setelah sesi masuk ditutup, kiosk menolak scan masuk. Sebelum sesi masuk ditutup, siswa tanpa presensi tampil sebagai "belum hadir"; setelah ditutup menjadi Alpa, kecuali saat mode darurat. | Sistem | Core | R1 | DECISION (OQ-07, Session 4) |
| FR-PRS-09 | Admin dan guru piket dapat mengubah jadwal hari ini, misalnya mengundur batas terlambat karena hujan deras atau memajukan jam pulang karena rapat guru. Alasan wajib diisi, perubahan dicatat, dan status hari itu dihitung ulang. Guru piket hanya dapat mengubah jadwal hari ini (`05` BR-JAM-10). | Admin, guru piket | Supporting | R1 | DECISION (Session 4) |
| FR-PRS-10 | Guru piket atau admin dapat mengaktifkan mode darurat untuk hari ini bila semua stasiun scan tidak dapat dipakai. Selama aktif, Alpa tidak terbentuk, pesan "tidak hadir" ditahan, dan staf mencatat kehadiran per rombel sekaligus. Mode darurat berakhir saat diakhiri petugas, atau otomatis di akhir hari (`05` §10). | Admin, guru piket, wali kelas, guru BK | Supporting | R1 | DECISION (OQ-16, Session 4) |
| FR-PRS-11 | Admin mengatur batas mundur, dengan default hari ini dan 7 hari kalender sebelumnya. Batas ini berlaku untuk presensi manual, koreksi, dan izin/sakit/dispensasi (input, verifikasi, perubahan keputusan, dan pengajuan siswa untuk tanggal lampau). Admin tidak dibatasi (`05` §9). | Admin | Core | R1 | DECISION (OQ-15, Session 4) |

### 3.5 IZN — Izin, sakit, dan dispensasi

| ID | Kebutuhan | Aktor | Kategori | Rilis | Status |
|---|---|---|---|---|---|
| FR-IZN-01 | Siswa mengajukan izin atau sakit (bukan dispensasi) lewat portal untuk satu tanggal atau rentang tanggal, dengan keterangan dan lampiran surat (opsional). Tanggal lampau boleh diajukan dalam batas mundur (FR-PRS-11). | Siswa | Core | R1 | CONFIRMED (pengajuan siswa); DECISION (tanggal lampau, Session 4); RECOMMENDATION (rentang tanggal, lampiran) |
| FR-IZN-02 | Admin, wali kelas (rombelnya), guru piket, dan guru BK menginput izin, sakit, atau dispensasi langsung atas nama siswa, misalnya berdasarkan surat atau pesan orang tua. Data yang diinput staf langsung berstatus disetujui. | Admin, wali kelas, guru piket, guru BK | Core | R1 | CONFIRMED; DECISION (pelaku, Session 3; langsung disetujui, Session 4) |
| FR-IZN-03 | Admin, wali kelas (rombelnya), guru piket, dan guru BK memverifikasi pengajuan siswa (setujui atau tolak) dengan catatan. Hanya izin/sakit/dispensasi yang disetujui yang memengaruhi status presensi, dan yang disetujui menang atas presensi masuk (`05` BR-STS-03). | Admin, wali kelas, guru piket, guru BK | Core | R1 | CONFIRMED (verifikasi); DECISION (pelaku, Session 3; hanya yang disetujui berlaku dan menang atas presensi, Session 4) |
| FR-IZN-04 | Siswa melihat status pengajuannya (menunggu, disetujui, ditolak, atau dibatalkan), termasuk keputusan terbaru bila staf mengubahnya. | Siswa | Core | R1 | RECOMMENDATION |
| FR-IZN-05 | Lampiran surat, paling banyak 3 file per data, disimpan di luar folder publik dan hanya dapat dibuka oleh siswa pemiliknya, staf yang berhak memverifikasi, dan pimpinan (`02`, `HA-IZN-05`). Pencatatan pembukaan lampiran mengikuti OQ-17. | Sistem | Core | R1 | DECISION (akses pimpinan, Session 4; paling banyak 3 file, Session 5); RECOMMENDATION (penyimpanan di luar folder publik) |
| FR-IZN-06 | Staf yang berhak memverifikasi dapat mengubah keputusan: membatalkan data yang disetujui, memendekkan rentangnya, atau mengubah penolakan menjadi persetujuan. Alasan wajib diisi, perubahan dicatat, dan status presensi dihitung ulang (`05` BR-IZN-09). Untuk dispensasi massal, perubahan dapat diterapkan ke satu siswa atau sekaligus ke satu kelompok. | Admin, wali kelas, guru piket, guru BK | Core | R1 | DECISION (Session 4; per kelompok, Session 4b) |
| FR-IZN-07 | Dispensasi adalah jenis ketiga di samping izin dan sakit, untuk tugas atau kegiatan resmi sekolah. Dispensasi hanya diinput staf, dan dapat diinput untuk banyak siswa sekaligus (siswa terpilih, satu rombel, atau satu tingkat). Pada input massal, siswa yang sudah memiliki data izin/sakit/dispensasi pada tanggal yang sama dilewati dan dilaporkan (`05` BR-IZN-07). Dispensasi bukan ketidakhadiran (`05` BR-IZN-05, BR-REK-02). | Admin, wali kelas, guru piket, guru BK | Core | R1 | DECISION (Session 4; siswa bentrok dilewati, Session 4b); RECOMMENDATION (cara memilih siswa) |

### 3.6 LAP — Dashboard, rekap, export, flyer

| ID | Kebutuhan | Aktor | Kategori | Rilis | Status |
|---|---|---|---|---|---|
| FR-LAP-01 | Dashboard hari ini menampilkan jumlah hadir, terlambat, izin, sakit, dispensasi, dan belum hadir/Alpa per rombel, beserta daftar siswa yang belum hadir. Data diperbarui seiring sinkron. Staf tanpa role khusus hanya melihat angka; wali kelas melihat daftar nama hanya untuk rombelnya (`02`, `HA-LAP-01`, `HA-LAP-02`). Daftar nama memuat penanda untuk keadaan yang perlu diperiksa (`05` BR-STS-07). | Semua akun staf | Supporting | R1 | DECISION (penanda, Session 4b) |
| FR-LAP-02 | Rekap kehadiran per rombel untuk rentang tanggal tertentu (misalnya harian, bulanan, semester) ditampilkan di layar. Ketidakhadiran adalah Sakit, Izin, dan Alpa. Persentase kehadiran = (Hadir + Terlambat + Dispensasi) ÷ hari sekolah (`05` §12). | Admin, wali kelas, guru BK, pimpinan (cakupan: `02`) | Core | R1 | DECISION |
| FR-LAP-03 | Staf dapat melihat riwayat kehadiran per siswa, dan siswa dapat melihat riwayatnya sendiri. Riwayat di portal siswa memuat status, jam masuk dan pulang, kejadian pulang, izin/sakit/dispensasi beserta catatan verifikasi, dan alasan koreksi, tanpa nama staf (`04` FS-LAP-04). | Admin, wali kelas, guru BK, pimpinan (cakupan: `02`), siswa | Core | R1 | DECISION (isi riwayat di portal, Session 4b) |
| FR-LAP-04 | Rekap dapat diekspor ke Excel (.xlsx), CSV, dan PDF. Pembagian format per laporan mengikuti matriks di `13` §4 (OQ-11). | Admin, wali kelas, guru BK, pimpinan (cakupan: `02`) | Supporting | R2 | DECISION |
| FR-LAP-05 | Staf membuat flyer kehadiran berupa gambar PNG (per rombel atau total) dari template di browser, lalu mengunduhnya untuk dibagikan manual. Flyer berisi angka saja, tanpa nama siswa (OQ-11, `13` LP-08). | Admin, wali kelas, pimpinan (cakupan: `02`) | Supporting | R2 | DECISION |

### 3.7 WA — Notifikasi WhatsApp

| ID | Kebutuhan | Aktor | Kategori | Rilis | Status |
|---|---|---|---|---|---|
| FR-WA-01 | Sistem mengirim notifikasi WhatsApp ke nomor orang tua/wali siswa lewat API gateway pihak ketiga yang tidak resmi. Engine WhatsApp tidak dibangun sendiri. Provider mengikuti OQ-10. | Sistem | Optional | R2 | DECISION |
| FR-WA-02 | Sistem mendukung tujuh jenis kejadian notifikasi: scan masuk, scan pulang, terlambat, tidak hadir, tidak scan pulang, izin, dan pulang lebih awal. Jenis izin mencakup izin, sakit, dan dispensasi (`05` BR-WA-07). Admin dapat mengaktifkan atau menonaktifkan setiap jenis. | Admin | Optional | R2 | DECISION; RECOMMENDATION (cakupan jenis izin) |
| FR-WA-03 | Secara default hanya notifikasi scan masuk yang aktif. Notifikasi dikirim pada pagi hari, segera setelah scan tersinkron, bukan sebagai rekap sore. | Sistem | Optional | R2 | DECISION |
| FR-WA-04 | Satu scan menghasilkan paling banyak satu pesan. Keterangan terlambat dimasukkan ke pesan scan masuk, bukan dikirim sebagai pesan terpisah. | Sistem | Optional | R2 | RECOMMENDATION |
| FR-WA-05 | Admin mengatur template pesan untuk setiap jenis kejadian notifikasi. | Admin | Optional | R2 | RECOMMENDATION |
| FR-WA-06 | Pesan dimasukkan ke outbox WA dan dikirim bertahap oleh proses terjadwal. Status pengiriman (menunggu, terkirim, gagal) dapat dilihat, dan pesan yang gagal dapat dikirim ulang. | Admin | Optional | R2 | RECOMMENDATION |
| FR-WA-07 | Notifikasi "tidak hadir" dibuat setelah jam tutup sesi masuk ditambah waktu tunda (default 60 menit, diatur admin), bila mode darurat tidak aktif, semua stasiun scan tersinkron, dan jumlah siswa tercatat masuk mencapai ambang pengaman (default 50%, diatur admin). Notifikasi "tidak scan pulang" mengikuti pola yang sama setelah sesi pulang ditutup. Bila status berubah setelah pesan terkirim, tidak ada pesan koreksi (`05` §11). | Sistem | Optional | R2 | DECISION (waktu tunda, ambang, tanpa pesan koreksi, Session 4); RECOMMENDATION (syarat sinkron, nilai 60 menit, pola tidak scan pulang) |
| FR-WA-08 | Bila pesan "tidak hadir" ditahan karena ambang pengaman atau karena stasiun belum tersinkron, panel admin dan guru piket menampilkan peringatan. Guru piket atau admin lalu melepas pesan, yang dikirim ke siswa yang saat itu masih Alpa, atau membatalkannya (`05` BR-WA-03). | Admin, guru piket | Optional | R2 | DECISION (Session 4) |

### 3.8 INF — Jadwal, pengumuman, halaman publik

| ID | Kebutuhan | Aktor | Kategori | Rilis | Status |
|---|---|---|---|---|---|
| FR-INF-01 | Admin mengelola mata pelajaran dan jadwal pelajaran per rombel. Role staf lain tidak mengelolanya (`02`, `HA-INF-01`). | Admin | Supporting | R3 | DECISION; RECOMMENDATION (hanya admin) |
| FR-INF-02 | Siswa dan staf melihat jadwal pelajaran. Jadwal hanya informasi dan tidak memengaruhi presensi. | Siswa, staf | Supporting | R3 | DECISION |
| FR-INF-03 | Admin membuat pengumuman dengan sasaran publik atau khusus siswa. Role staf lain tidak membuat pengumuman (`02`, `HA-INF-03`). | Admin | Supporting | R3 | DECISION (pengumuman); RECOMMENDATION (sasaran publik/siswa, hanya admin) |
| FR-INF-04 | Halaman publik tanpa login menampilkan pengumuman dan info sekolah. | Publik | Supporting | R3 | DECISION |
| FR-INF-05 | Halaman publik menampilkan rekap agregat hari ini (jumlah per rombel), tanpa nama, NISN, foto, atau data individu siswa lainnya. | Publik | Supporting | R3 | DECISION |

### 3.9 KRT — Cetak kartu

| ID | Kebutuhan | Aktor | Kategori | Rilis | Status |
|---|---|---|---|---|---|
| FR-KRT-01 | Admin mencetak kartu untuk siswa baru dari data siswa. Kartu memuat foto, nama, NISN, dan QR berisi NISN polos dengan format yang sama dengan kartu lama. Desain kartu mengikuti OQ-13. | Admin | Optional | R3 | DECISION |
| FR-KRT-02 | Admin dapat mencetak ulang kartu untuk siswa yang kehilangan kartu. QR tetap sama, sehingga kartu lama tidak dapat diblokir (R-02). | Admin | Optional | R3 | RECOMMENDATION |

## 4. Kebutuhan non-fungsional

| ID | Kategori | Kebutuhan | Status | Risiko |
|---|---|---|---|---|
| NFR-01 | Kinerja | Umpan balik scan di kiosk muncul paling lama 1 detik setelah QR terbaca, tanpa bergantung pada jaringan. | RECOMMENDATION | R-03 |
| NFR-02 | Kinerja | Antrean pagi 500–1.000 siswa dilayani oleh beberapa stasiun scan di gerbang utama, yang dipakai untuk scan masuk dan pulang. Jumlah stasiun mengikuti OQ-08. | DECISION (skala, lokasi) | R-03 |
| NFR-03 | Keandalan | Scan yang sudah tercatat di laptop tidak hilang karena internet putus, browser ditutup, atau laptop restart. Kiosk meminta persistent storage ke browser. | RECOMMENDATION | R-06, R-08 |
| NFR-04 | Keandalan | Sinkron bersifat idempotent: kiriman ulang tidak menggandakan data. | RECOMMENDATION | R-09 |
| NFR-05 | Keandalan | Kegagalan gateway WhatsApp tidak memengaruhi pencatatan presensi. | RECOMMENDATION | R-16 |
| NFR-06 | Keamanan | Seluruh aplikasi diakses lewat HTTPS, baik lokal maupun production. Ini wajib karena webcam hanya berjalan di HTTPS. | CONFIRMED (syarat browser) | R-01 |
| NFR-07 | Keamanan | Semua area kecuali halaman publik wajib login. Endpoint sinkron hanya untuk akun stasiun dan dilindungi CSRF. Semua input divalidasi di server, tidak hanya di JavaScript. | RECOMMENDATION | R-10 |
| NFR-08 | Keamanan | Percobaan login dibatasi (rate limit), karena aplikasi terbuka ke internet. | RECOMMENDATION | — |
| NFR-09 | Waktu | Jam presensi memakai WIB (`Asia/Jakarta`, UTC+7). Zona waktu aplikasi dan database diset eksplisit. Jam laptop dikoreksi dengan selisih jam server dan divalidasi saat sinkron (`05` BR-JAM-12, BR-SCN-07, BR-SCN-08). | DECISION (WIB, OQ-05); RECOMMENDATION (cara koreksi jam) | R-05, R-11 |
| NFR-10 | Privasi | Data siswa adalah data anak, dan surat sakit adalah data kesehatan (UU 27/2022 PDP). File unggahan disimpan di luar `public/`. Halaman publik tidak menampilkan data individu. Stasiun scan memakai laptop dan profil browser khusus. | RECOMMENDATION | R-07, R-17 |
| NFR-11 | Kompatibilitas | Kiosk berjalan di Chrome atau Edge versi terbaru di Windows (A-04). Panel staf/admin dan portal siswa dapat dipakai di desktop maupun ponsel. | ASSUMPTION (kiosk); RECOMMENDATION (responsif) | R-04 |
| NFR-12 | Volume | Sistem menangani ±1.000 siswa aktif dan ±400 ribu catatan scan per tahun tanpa penurunan kinerja yang terasa. | RECOMMENDATION | R-20 |
| NFR-13 | Volume | Outbox WA menangani ±500–1.000 pesan scan masuk setiap pagi. Target waktu habis antrean ditetapkan setelah provider dipilih (OQ-10). | DECISION (default scan masuk); RECOMMENDATION (target) | R-16 |
| NFR-14 | Data | NISN disimpan sebagai teks 10 digit. Penempatan siswa ke rombel dicatat dengan tanggal mulai dan selesai per tahun ajaran (`06` §6.7). | DECISION (penempatan per tanggal, Session 5); RECOMMENDATION (NISN sebagai teks) | R-12, R-14 |
| NFR-15 | Maintainability | Kode mengikuti struktur standar CodeIgniter 4 (controller, model, view, filter, migration, seeder, command). Tidak memakai framework frontend; kiosk ditulis dengan Vanilla JavaScript. | CONFIRMED (stack); RECOMMENDATION (struktur) | — |
| NFR-16 | Dependensi | Library tambahan hanya dipakai bila perlu, dan alasannya dicatat. Daftar kandidat ada di bawah tabel. | RECOMMENDATION | R-04, R-18, R-19 |

Kandidat library yang sudah terlihat (NFR-16):

| Library | Kegunaan | Rilis |
|---|---|---|
| Library JS pembaca QR | Membaca QR di kiosk (wajib) | R1 |
| PhpSpreadsheet | Import .xlsx (R1) dan export (R2) | R1, R2 |
| Dompdf | Export PDF | R2 |
| Library JS pembuat gambar | Flyer kehadiran, bila perlu | R2 |
| Library QR | Cetak kartu | R3 |

## 5. Batasan

| ID | Batasan | Status |
|---|---|---|
| C-01 | Stack: CodeIgniter 4, MySQL (MySQLi), HTML/CSS/Vanilla JavaScript, pola server-rendered. Tanpa React, Vue, Angular, Vite, atau SPA. | CONFIRMED |
| C-02 | Pengembangan lokal di Windows + Laragon + Nginx. Panduan teknis ditulis untuk environment ini. | CONFIRMED |
| C-03 | Production di hosting online, dengan jenis hosting mengikuti OQ-09. Document root harus dapat diarahkan ke `public/`, dan cron harus tersedia untuk R2. | DECISION (hosting online); RECOMMENDATION (syarat) |
| C-04 | Format QR kartu tidak dapat diubah: NISN polos. | CONFIRMED |
| C-05 | Gateway WhatsApp adalah layanan pihak ketiga tidak resmi. Risiko nomor diblokir diterima. | DECISION |
| C-06 | Import .xlsx di R1 membutuhkan PhpSpreadsheet yang dipasang lewat Composer. Karena itu keputusan instalasi framework (OQ-09) harus selesai sebelum fase implementasi pertama. | RECOMMENDATION |

## 6. Kriteria keberhasilan v1

Status: DECISION (dikonfirmasi di Session 3). Butir 5 disesuaikan dengan status Dispensasi (Session 4).

1. Seluruh siswa aktif dapat melakukan presensi masuk dan pulang di stasiun scan. Umpan balik muncul paling lama 1 detik, termasuk saat internet putus.
2. Tidak ada scan yang hilang atau tercatat ganda setelah sinkron, termasuk saat internet putus dan saat data terkirim ulang.
3. Staf dapat melihat siswa yang belum hadir pada hari berjalan tanpa rekap manual.
4. Rekap per rombel untuk rentang tanggal apa pun tersedia tanpa kerja manual (R1), dan dapat diekspor (R2).
5. Izin, sakit, dan dispensasi tercatat dengan status verifikasi dan otomatis tercermin di status presensi.
6. Orang tua/wali menerima notifikasi scan masuk pada pagi yang sama (R2).
7. Sekolah tidak lagi membutuhkan kertas, Excel, atau WhatsApp untuk mencatat kehadiran (A-05).

## 7. Acceptance criteria tingkat tinggi

Status: DECISION (dikonfirmasi di Session 3). AC-03 disesuaikan dengan keputusan Session 4. Acceptance criteria rinci per fitur ditulis di `04-feature-specification.md` (ID `AC-<MODUL>-<NN>-<NN>`, Session 4b) dan dirujuk dokumen fase implementasi.

### AC-01 — Scan saat offline

Rujukan: FR-KIO-01, FR-KIO-04, FR-KIO-08, FR-KIO-09, NFR-01, NFR-03.

```text
Given stasiun scan sudah memuat data siswa saat online
  And koneksi internet kemudian terputus
When siswa memindai kartu OSIS dengan NISN yang terdaftar
Then kiosk menampilkan foto, nama, rombel, jenis presensi, dan status paling lama 1 detik setelah QR terbaca
  And scan tersimpan di laptop sebagai "belum tersinkron"
  And penghitung scan belum tersinkron bertambah satu
```

### AC-02 — Sinkron ulang tanpa duplikasi

Rujukan: FR-KIO-07, FR-KIO-10, NFR-04.

```text
Given stasiun scan memiliki scan yang belum tersinkron
When koneksi kembali dan sinkron otomatis berjalan
Then semua scan tersebut tersimpan di server
  And penghitung scan belum tersinkron kembali ke nol
When data yang sama terkirim ulang, misalnya karena respons server tidak sampai ke laptop
Then server tidak membuat catatan scan ganda
```

### AC-03 — Status Alpa

Rujukan: FR-PRS-05, FR-PRS-08, FR-PRS-10, FR-IZN-03.

```text
Given hari ini adalah hari sekolah bagi siswa dan siswa berstatus aktif
  And siswa tidak memiliki presensi masuk maupun izin/sakit/dispensasi yang disetujui
  And mode darurat tidak aktif
When jam tutup sesi masuk belum tercapai
Then siswa tampil sebagai "belum hadir"
When jam tutup sesi masuk tercapai dan sesi masuk tertutup otomatis
Then status siswa hari itu adalah Alpa
When staf kemudian menyetujui izin untuk tanggal tersebut
Then status siswa berubah menjadi Izin tanpa langkah tambahan
```

### AC-04 — Notifikasi scan masuk default

Rujukan: FR-WA-02, FR-WA-03, FR-WA-04, FR-WA-06.

```text
Given pengaturan notifikasi masih default (hanya scan masuk yang aktif)
  And siswa memiliki nomor WhatsApp orang tua/wali yang valid
When scan masuk siswa tersinkron ke server pada pagi hari
Then satu pesan scan masuk masuk ke outbox WA dan dikirim pada pagi yang sama
  And bila siswa terlambat, keterangan terlambat ada di pesan tersebut tanpa pesan kedua
  And scan pulang siswa tidak menghasilkan pesan
```

### AC-05 — Halaman publik tanpa data individu

Rujukan: FR-INF-04, FR-INF-05, NFR-10.

```text
Given pengunjung belum login
When pengunjung membuka halaman publik
Then pengunjung melihat pengumuman dan jumlah kehadiran per rombel hari ini
  And tidak ada nama, NISN, foto, atau data individu siswa yang tampil
```

## 8. Pertanyaan terbuka yang memengaruhi requirement

Daftar lengkap ada di `00` §8.2. OQ-01, OQ-02, dan OQ-14 terjawab di Session 3. OQ-03 s.d. OQ-07, OQ-15, dan OQ-16 terjawab di Session 4 (`05`). OQ-11 dan OQ-12 terjawab di Session 5 (`13`).

| OQ | Pertanyaan singkat | Requirement terdampak |
|---|---|---|
| OQ-08 | Jumlah stasiun scan (lokasi sudah diputuskan) | NFR-02 |
| OQ-09 | Jenis hosting dan instalasi Composer | C-03, C-06 |
| OQ-10 | Provider gateway WhatsApp | FR-WA-01, NFR-13 |
| OQ-13 | Desain kartu siswa baru | FR-KRT-01 |
| OQ-17 | Pencatatan pembukaan lampiran surat oleh staf | FR-IZN-05 |

## 9. Traceability

Kolom rilis di §3 adalah tautan pertama dari requirement ke implementasi. Dokumen berikutnya menambahkan tautan untuk setiap `FR-*`:

| Tautan | Dokumen |
|---|---|
| Aturan bisnis | `05` |
| Tabel database | `06` (§17.2 memetakan fitur ke tabel) |
| Route dan API | `09`, `10` |
| Halaman | `08`, `09` |
| Fase implementasi | `15` |
| Spesifikasi fitur dan acceptance criteria rinci | `04` (§12.1 memetakan setiap FR ke fitur) |

## Riwayat perubahan

| Versi | Tanggal | Perubahan |
|---|---|---|
| 0.1 | 2026-10-03 | Draft awal dari Session 2. |
| 0.2 | 2026-10-03 | Keputusan Session 3. FR-MD-08, FR-MD-09, FR-AKN-05 s.d. FR-AKN-08, dan FR-KIO-12 ditambahkan. Aktor dan status FR-MD, FR-AKN, FR-PRS, FR-IZN, FR-LAP, dan FR-INF diperbarui sesuai `02`. NFR-02 memuat lokasi stasiun. Kriteria keberhasilan dan acceptance criteria dikonfirmasi. OQ-15 dan OQ-16 ditambahkan. |
| 0.3 | 2026-10-03 | Keputusan Session 4 (`05`). Modul IZN mencakup dispensasi. FR-AKN-06 (mekanisme slip disetujui), FR-KIO-04, FR-KIO-05, FR-KIO-10 s.d. FR-KIO-12, FR-PRS-02 s.d. FR-PRS-08, FR-IZN-01 s.d. FR-IZN-05, FR-LAP-01, FR-LAP-02, FR-WA-02, FR-WA-07, dan NFR-09 diperbarui. FR-PRS-09 s.d. FR-PRS-11, FR-IZN-06, FR-IZN-07, dan FR-WA-08 ditambahkan. Kriteria keberhasilan 5 dan AC-03 disesuaikan dengan dispensasi dan mode darurat. OQ-17 ditambahkan. |
| 0.4 | 2026-10-03 | Keputusan Session 4b (`04` §2). FR-MD-03 dan FR-MD-05 (nomor WA opsional), FR-PRS-06 (pembatalan presensi manual), FR-PRS-07 (hapus koreksi, koreksi saat ada izin), FR-IZN-06 dan FR-IZN-07 (dispensasi per kelompok, siswa bentrok dilewati), FR-LAP-01 (penanda), dan FR-LAP-03 (isi riwayat di portal siswa) diperbarui. §1, §7, dan §9 merujuk `04`. |
| 0.5 | 2026-10-04 | Keputusan Session 5 (`06` §2, `13` §2). FR-MD-03 (atribut opsional dan masa aktif), FR-MD-04 (penempatan massal), FR-MD-05 (NISN yang sudah ada dilewati), FR-MD-07 (format nama file foto), FR-IZN-05 (paling banyak 3 lampiran), FR-LAP-04 (matriks laporan), FR-LAP-05 (isi flyer), dan NFR-14 diperbarui. FR-MD-10 (atribut tambahan siswa) ditambahkan. OQ-11 dan OQ-12 dihapus dari §8 karena terjawab. |
