# Spensada — Feature Specification

| Item | Nilai |
|---|---|
| Versi | 0.3 (draft, menunggu review) |
| Tanggal | 2026-10-04 |
| Sumber | Discovery Session 4b (Feature Specification). Diperbarui dengan keputusan Session 5 (§2.4) dan Session 6 (§2.5). |
| Bergantung pada | [00-project-overview.md](00-project-overview.md): label status, glosarium, risiko (`R-xx`), dan pertanyaan terbuka (`OQ-xx`). [01-product-requirements.md](01-product-requirements.md): requirement (`FR-*`, `NFR-*`) dan acceptance criteria tingkat tinggi (AC-01 s.d. AC-05). [02-user-roles-and-permissions.md](02-user-roles-and-permissions.md): hak akses (`HA-*`) dan cakupan. [03-user-flow.md](03-user-flow.md): alur (`UF-*`). [05-business-rules.md](05-business-rules.md): aturan bisnis (`BR-*`). |
| Dokumen terkait | [06-database-design.md](06-database-design.md): tabel yang ditulis dan dibaca setiap fitur. [07-system-architecture.md](07-system-architecture.md): mekanisme teknis, termasuk kiosk dan hitung ulang. [13-reporting-import-export.md](13-reporting-import-export.md): laporan, template import, dan format file. |

Dokumen ini merinci setiap fitur R1 sampai siap dirancang di Session 5–8 dan diimplementasikan. Setiap fitur memuat:

- tujuan, aktor dan hak, serta prasyarat;
- input dan validasi, perilaku, dan aturan terkait;
- keadaan kosong dan error;
- data dan log, hal yang di luar cakupan, parameter, dan catatan antarmuka awal;
- acceptance criteria rinci.

Fitur R2 dan R3 dicatat sebagai kerangka, lalu dirinci menjelang rilisnya.

Dokumen ini juga mencatat keputusan Session 4b atas usulan di `05` (§2). Dokumen `00`, `01`, `02`, `03`, dan `05` sudah diperbarui sesuai keputusan tersebut (§13).

## 1. Cara membaca dokumen ini

- **ID.**
  - Fitur memakai `FS-<MODUL>-<NN>`, dengan kode modul dari `01` §2.
  - Acceptance criteria rinci memakai `AC-<MODUL>-<NN>-<NN>`. Dua bagian pertama sama dengan fiturnya. Contohnya, AC-PRS-06-02 adalah acceptance criteria kedua untuk FS-PRS-06. ID ini berbeda dari acceptance criteria tingkat tinggi AC-01 s.d. AC-05 di `01` §7.
  - Keadaan kosong dan error memakai `E<n>` di dalam satu fitur, dan dirujuk sebagai "FS-PRS-06 E1", seperti pola di UF.
  - ID tidak pernah dinomori ulang. Fitur atau acceptance criteria yang batal ditandai `DEPRECATED`.
- **Status.** Label status mengikuti `00`. Header setiap fitur memuat status fitur tersebut. Perilaku yang merujuk aturan bisnis (`BR-*`) mengikuti status aturan itu di `05`. Rincian baru di dokumen ini berstatus RECOMMENDATION, kecuali diberi label lain.
- **Struktur fitur.** Setiap fitur R1 berisi bagian berikut (DECISION, Session 4b):

| Bagian | Isi |
|---|---|
| Header | Tujuan, rilis, requirement (`FR-*`), alur (`UF-*`), aktor dan hak (`HA-*`), aturan terkait (`BR-*`), dan status. |
| Prasyarat | Keadaan atau fitur lain yang harus ada lebih dulu. |
| Input dan validasi | Isian, wajib atau tidak, dan aturan validasinya. Validasi selalu dilakukan di server (NFR-07). |
| Perilaku | Urutan kerja sistem. |
| Keadaan kosong dan error | Keadaan `E<n>` dan respons sistem. |
| Data dan log | Data yang dibaca dan ditulis, serta yang dicatat di log. Bagian ini menjadi masukan Session 5. |
| Parameter dan default | Nilai yang dapat diatur atau masih usulan, siapa yang mengaturnya, dan kapan nilainya dipastikan. |
| Di luar cakupan | Hal yang sengaja tidak dilakukan fitur ini. |
| Catatan antarmuka awal | Petunjuk awal tampilan. Bagian ini bukan keputusan; tampilan final ditetapkan di Session 7. |
| Acceptance criteria | Skenario Given/When/Then beserta rujukannya. |

- **Contoh jam dan tanggal.** Kecuali disebut lain, contoh memakai:
  - aturan jam hari Selasa di `05` §4.1: buka scan masuk 06.00, jam masuk 07.00, toleransi 0 menit, tutup sesi masuk 08.00, buka scan pulang 12.00, jam pulang 13.00, dan tutup sesi pulang 17.00;
  - batas mundur 7 hari;
  - "hari ini" adalah Selasa, 13 Oktober 2026, sehingga tanggal 6–13 Oktober 2026 masih berada dalam batas mundur.

  Semua jam memakai WIB.
- **Acceptance criteria.** Ditulis dalam format Given/When/Then seperti `01` §7. Sebuah fitur dianggap selesai bila semua acceptance criteria-nya lulus, dan dokumen fase di Session 11 merujuk ID ini. (RECOMMENDATION)
- **Nama teknis.** Tabel, kolom, route, dan teks layar ditetapkan di Session 5–8. Nama tabel dan kolom ada di `06`, dan mekanisme teknis di `07`. Contoh pesan di dokumen ini bukan teks final.

## 2. Keputusan Session 4b

### 2.1 Format dokumen

| Topik | Keputusan | Status |
|---|---|---|
| Cakupan | Fitur R1 dirinci penuh. Fitur R2 dan R3 dicatat sebagai kerangka (ID, tujuan, rujukan, dan OQ yang ditunggu), lalu dirinci menjelang rilisnya. ID-nya dipesan sekarang. | DECISION |
| Unit spesifikasi | Per fitur: satu kemampuan utuh beserta semua aksinya. | DECISION |
| Skema ID | Fitur memakai `FS-<MODUL>-<NN>`. Acceptance criteria rinci memakai `AC-<MODUL>-<NN>-<NN>`. | DECISION |
| Isi fitur | Isi minimal ditambah data dan log, di luar cakupan, parameter dan default, serta catatan antarmuka awal (§1). | DECISION |

### 2.2 Tinjauan RECOMMENDATION di `05`

| Aturan | Usulan sebelumnya | Hasil Session 4b |
|---|---|---|
| BR-STS-07 | Penanda di dashboard untuk staf yang melihat daftar nama. | Disetujui dan diperluas. Penanda juga tampil di daftar presensi rombel per tanggal dan di riwayat siswa, termasuk untuk tanggal lampau. Penanda bersifat informasi dan hilang sendiri setelah datanya diperbaiki. (DECISION) |
| BR-SCN-10 | Scan pulang tanpa presensi masuk dicatat, tidak membuat siswa Hadir, dan ditandai. | Disetujui. Kiosk menampilkan umpan balik pulang seperti biasa. (DECISION) |
| BR-KOR-08 | Koreksi dapat dihapus dengan alasan. | Disetujui. (DECISION) |
| BR-KOR-09 | Koreksi tetap dapat disimpan saat ada izin/sakit/dispensasi yang disetujui, dengan peringatan. | Disetujui. (DECISION) |
| BR-KOR-11 | Presensi manual tidak dihapus; salah input diperbaiki lewat koreksi status. | Diganti. Presensi manual dapat dibatalkan dengan alasan. Datanya tetap tersimpan dengan tanda dibatalkan, tetapi tidak dipakai. (DECISION) |
| BR-IZN-07 | Data izin/sakit/dispensasi tidak boleh tumpang tindih. | Disetujui. Pada dispensasi massal, siswa yang bentrok dilewati dan dilaporkan. (DECISION) |
| BR-IZN-10 | Pengajuan tetap dapat diverifikasi setelah tanggalnya melewati batas mundur. | Disetujui. (DECISION) |
| BR-IZN-11 | Izin/sakit/dispensasi boleh untuk tanggal ke depan di tahun ajaran aktif. | Disetujui. (DECISION) |
| BR-DRT-06 | Waktu tunda pesan "tidak hadir" dihitung sejak mode darurat diakhiri. | Disetujui. (DECISION) |
| BR-DRT-07 | Tanpa kejadian "tidak scan pulang" pada tanggal yang pernah memakai mode darurat. | Disetujui. (DECISION) |
| BR-KAL-03 | Jadwal khusus berlaku untuk semua siswa. | Disetujui. Bila jam berbeda per tingkat, admin memakai jam masuk yang paling lambat dan jam pulang yang paling awal. (DECISION) |

### 2.3 Keputusan tingkat fitur

| Topik | Keputusan | Fitur | Status |
|---|---|---|---|
| Riwayat di portal siswa | Siswa melihat status per tanggal, jam masuk dan pulang, kejadian pulang, data izin/sakit/dispensasi beserta catatan verifikasi, serta tanda "dikoreksi" beserta alasan koreksinya. Nama staf tidak ditampilkan. | FS-LAP-04, FS-IZN-01 | DECISION |
| Nomor WhatsApp orang tua/wali | Opsional. Bila diisi, formatnya divalidasi dan disimpan dalam format baku yang diawali 62. Siswa tanpa nomor ditandai di daftar siswa. | FS-MD-04, FS-MD-06 | DECISION |
| Ubah keputusan dispensasi massal | Perubahan dapat diterapkan ke satu siswa, atau sekaligus ke semua data dalam satu kelompok dengan satu alasan. Setiap data tetap tercatat di log. | FS-IZN-05 | DECISION |

### 2.4 Keputusan Session 5

Keputusan Session 5 yang berdampak ke fitur. Rinciannya ada di `06` §2 dan `13` §2.

| Topik | Keputusan | Fitur | Status |
|---|---|---|---|
| Atribut siswa | Siswa juga memiliki NIS, jenis kelamin, tanggal lahir, alamat rumah, dan nama orang tua/wali. Semuanya opsional. | FS-MD-04, FS-MD-06 | DECISION |
| Atribut tambahan | Admin dapat menambah atribut siswa sendiri. Atribut tambahan tidak dipakai logika presensi, rekap, atau filter laporan. | FS-MD-09 (baru) | DECISION |
| Alasan penonaktifan | Lulus, pindah sekolah, keluar, meninggal dunia, salah input, dan lainnya. | FS-MD-04 | DECISION |
| Penempatan massal | Per rombel asal ke rombel tujuan, dan lewat file import penempatan (`13` IM-02). | FS-MD-05 | DECISION |
| Import siswa | Kolom template mengikuti `13` §6.1. Baris dengan NISN yang sudah ada dilewati dan dilaporkan. | FS-MD-06 | DECISION |
| Nama file foto | Nama file diawali 10 digit NISN (`13` IM-03, OQ-12). | FS-MD-08 | DECISION |
| Status harian | Disimpan sebagai salinan; bagian yang bergantung pada jam sekarang diturunkan saat dibaca (`06` §11). | FS-PRS-05, §4.5 | DECISION |
| Tinjauan usulan di `05` | BR-KAL-06, BR-SCN-03 (scan ganda disimpan dengan tanda), BR-STS-06, BR-KOR-10 (cakupan log yang diperluas), BR-REK-04, dan BR-REK-05 menjadi DECISION. | FS-MD-04, FS-MD-05, FS-KIO-04, FS-PRS-05, FS-PRS-11, FS-LAP-03 | DECISION |
| Lampiran | Paling banyak 3 file per data izin/sakit/dispensasi. | FS-IZN-01 s.d. FS-IZN-03 | DECISION |
| Pembulatan persentase | Bilangan bulat (`13` IE-04). | FS-LAP-03 | DECISION |
| Laporan dan flyer | Matriks laporan × format di `13` §4. Flyer berisi angka saja (OQ-11). | FS-LAP-05, FS-LAP-06 | DECISION |

### 2.5 Keputusan Session 6

Keputusan Session 6 yang berdampak ke fitur. Rinciannya ada di `07` §2.

| Topik | Keputusan | Fitur | Status |
|---|---|---|---|
| Login akun stasiun | Bertahan 90 hari sejak kontak terakhir (`07` ARS-30). | FS-AKN-01, FS-AKN-04 | DECISION |
| Admin pertama | Dibuat lewat perintah CLI. Perintah serupa memulihkan akses bila satu-satunya admin lupa password (`07` ARS-49). | FS-AKN-03 | DECISION |
| Foto | Foto standar paling besar 600×800 px dan foto kiosk 300×400 px, dalam JPEG (`07` ARS-53). Foto massal diunggah sebagai ZIP atau beberapa file sekaligus (`07` ARS-54). | FS-MD-07, FS-MD-08, FS-KIO-01 | DECISION |
| Parameter kiosk | Nilai di `07` §2.3. Parameter lain di `07` ARS-33 mengikuti status di tabel itu. | FS-KIO-01 s.d. FS-KIO-05 | DECISION |
| Jam kiosk yang dibuka offline | Kiosk memakai selisih jam terakhir dan menampilkan peringatan (`07` ARS-28). | FS-KIO-02 | DECISION |
| Hitung ulang | Antrean ditulis dalam transaksi yang sama dengan perubahan sumber, lalu diproses segera (`07` ARS-35, ARS-36). | §4.5, FS-PRS-05 | DECISION |
| Perubahan bersamaan | `updated_at` dipakai sebagai token versi (`07` ARS-40). | §4.6 | DECISION |
| Pembaruan dashboard | Polling fragmen HTML setiap 30 detik (`07` ARS-50). | FS-LAP-01, FS-KIO-05 | DECISION |
| Tinjauan usulan Session 5 | Periode aktif yang dibatalkan tidak dihitung, tingkat rombel dikunci setelah ada penempatan, dan lampiran bersama satu kelompok paling banyak 3 file (`07` §2.2). | FS-MD-03, FS-MD-04, FS-IZN-03 | DECISION |

## 3. Daftar fitur

| ID | Fitur | Rilis | Requirement | Alur | Rincian |
|---|---|---|---|---|---|
| FS-AKN-01 | Login dan logout | R1 | FR-AKN-01, FR-AKN-04 | UF-20 | §5 |
| FS-AKN-02 | Ganti password | R1 | FR-AKN-04, FR-AKN-06 | UF-20 | §5 |
| FS-AKN-03 | Akun staf dan role | R1 | FR-AKN-02, FR-AKN-06 s.d. FR-AKN-08 | UF-04, UF-21 | §5 |
| FS-AKN-04 | Akun stasiun | R1 | FR-AKN-02, FR-AKN-03 | UF-06 | §5 |
| FS-AKN-05 | Akun siswa dan slip akun | R1 | FR-AKN-05 s.d. FR-AKN-07 | UF-05, UF-21 | §5 |
| FS-MD-01 | Identitas sekolah | R1 | FR-MD-08 | UF-01 | §6 |
| FS-MD-02 | Tahun ajaran dan semester | R1 | FR-MD-01 | UF-01, UF-08 | §6 |
| FS-MD-03 | Rombel dan wali kelas | R1 | FR-MD-02 | UF-01, UF-08 | §6 |
| FS-MD-04 | Data siswa | R1 | FR-MD-03, FR-MD-09 | UF-07 | §6 |
| FS-MD-05 | Penempatan siswa ke rombel | R1 | FR-MD-04 | UF-07, UF-08 | §6 |
| FS-MD-06 | Import siswa | R1 | FR-MD-05 | UF-02 | §6 |
| FS-MD-07 | Foto siswa satu per satu | R1 | FR-MD-06, FR-MD-09 | UF-03 | §6 |
| FS-MD-08 | Foto siswa massal | R1 | FR-MD-07, FR-MD-09 | UF-03 | §6 |
| FS-MD-09 | Atribut tambahan siswa | R1 | FR-MD-10 | UF-07 | §6 |
| FS-KIO-01 | Muat data kiosk | R1 | FR-KIO-01, FR-KIO-06, FR-KIO-09 | UF-06, UF-09 | §7 |
| FS-KIO-02 | Scan dan umpan balik | R1 | FR-KIO-02 s.d. FR-KIO-06, FR-KIO-09 | UF-10, UF-14 | §7 |
| FS-KIO-03 | Sinkron dari kiosk | R1 | FR-KIO-07 s.d. FR-KIO-09 | UF-11 | §7 |
| FS-KIO-04 | Penerimaan sinkron di server | R1 | FR-KIO-10, FR-KIO-11 | UF-11 | §7 |
| FS-KIO-05 | Status stasiun | R1 | FR-KIO-12 | UF-06, UF-11, UF-13 | §7 |
| FS-KIO-06 | Tinjauan scan bertanda | R1 | FR-KIO-11 | UF-11 | §7 |
| FS-PRS-01 | Pola mingguan | R1 | FR-PRS-02, FR-PRS-03 | UF-01 | §8 |
| FS-PRS-02 | Jadwal khusus | R1 | FR-PRS-02, FR-PRS-03 | UF-01 | §8 |
| FS-PRS-03 | Libur | R1 | FR-PRS-03 | UF-01 | §8 |
| FS-PRS-04 | Ubah jadwal hari ini | R1 | FR-PRS-09 | UF-28 | §8 |
| FS-PRS-05 | Penentuan status harian | R1 | FR-PRS-01, FR-PRS-04, FR-PRS-05, FR-PRS-08 | UF-13, UF-14 | §8 |
| FS-PRS-06 | Presensi manual | R1 | FR-PRS-06, FR-PRS-11 | UF-12 | §8 |
| FS-PRS-07 | Koreksi status | R1 | FR-PRS-07, FR-PRS-11 | UF-16 | §8 |
| FS-PRS-08 | Mode darurat | R1 | FR-PRS-10 | UF-27 | §8 |
| FS-PRS-09 | Presensi per rombel saat darurat | R1 | FR-PRS-06, FR-PRS-10 | UF-27 | §8 |
| FS-PRS-10 | Batas mundur | R1 | FR-PRS-11 | UF-01 | §8 |
| FS-PRS-11 | Log perubahan presensi | R1 | FR-PRS-07 | UF-16 | §8 |
| FS-IZN-01 | Pengajuan izin/sakit oleh siswa | R1 | FR-IZN-01, FR-IZN-04 | UF-17 | §9 |
| FS-IZN-02 | Input izin/sakit/dispensasi oleh staf | R1 | FR-IZN-02, FR-IZN-07 | UF-19 | §9 |
| FS-IZN-03 | Dispensasi massal | R1 | FR-IZN-07 | UF-19 | §9 |
| FS-IZN-04 | Verifikasi pengajuan | R1 | FR-IZN-03 | UF-18 | §9 |
| FS-IZN-05 | Ubah keputusan | R1 | FR-IZN-06 | UF-29 | §9 |
| FS-IZN-06 | Daftar izin dan lampiran | R1 | FR-IZN-04, FR-IZN-05 | UF-17, UF-18 | §9 |
| FS-LAP-01 | Dashboard hari ini | R1 | FR-LAP-01 | UF-15 | §10 |
| FS-LAP-02 | Daftar presensi rombel per tanggal | R1 | FR-LAP-01, FR-LAP-02 | UF-15, UF-16 | §10 |
| FS-LAP-03 | Rekap per rombel | R1 | FR-LAP-02 | UF-22 | §10 |
| FS-LAP-04 | Riwayat kehadiran siswa | R1 | FR-LAP-03, FR-IZN-04 | UF-22 | §10 |
| FS-LAP-05 | Export rekap | R2 | FR-LAP-04 | UF-22 | Kerangka, §11 |
| FS-LAP-06 | Flyer kehadiran | R2 | FR-LAP-05 | UF-24 | Kerangka, §11 |
| FS-WA-01 | Pengaturan notifikasi WhatsApp | R2 | FR-WA-01, FR-WA-02, FR-WA-05, FR-WA-07 | UF-23 | Kerangka, §11 |
| FS-WA-02 | Pembuatan pesan dan outbox WA | R2 | FR-WA-03, FR-WA-04, FR-WA-06 | UF-23 | Kerangka, §11 |
| FS-WA-03 | Pesan "tidak hadir" dan "tidak scan pulang" beserta penahanannya | R2 | FR-WA-07, FR-WA-08 | UF-13, UF-23 | Kerangka, §11 |
| FS-INF-01 | Mata pelajaran dan jadwal pelajaran | R3 | FR-INF-01, FR-INF-02 | — | Kerangka, §11 |
| FS-INF-02 | Pengumuman | R3 | FR-INF-03 | UF-25 | Kerangka, §11 |
| FS-INF-03 | Halaman publik | R3 | FR-INF-04, FR-INF-05 | UF-25 | Kerangka, §11 |
| FS-KRT-01 | Cetak kartu | R3 | FR-KRT-01, FR-KRT-02 | UF-26 | Kerangka, §11 |

## 4. Ketentuan umum

Ketentuan di bagian ini berlaku untuk semua fitur, sehingga tidak diulang di setiap fitur. Statusnya RECOMMENDATION, kecuali diberi label lain.

### 4.1 Hak dan cakupan

1. Server memeriksa hak (`HA-*`) dan cakupan pada setiap permintaan, termasuk permintaan dari kiosk (`02` §4 butir 5, NFR-07). Menu dan tombol yang tidak berhak disembunyikan, tetapi penyembunyian tidak menggantikan pemeriksaan di server.
2. Hak efektif adalah gabungan hak semua role sebuah akun (`02` §4 butir 1). Satu aksi boleh dilakukan bila salah satu role mengizinkannya untuk data tersebut.
3. Permintaan di luar hak ditolak dengan pesan umum, misalnya "Anda tidak berhak membuka data ini." Pesan itu tidak menyebut apakah datanya ada. Siswa di luar cakupan tidak muncul di hasil pencarian.
4. Untuk data per siswa (profil, riwayat, presensi manual, koreksi, dan izin/sakit/dispensasi), cakupan Rombel ditentukan oleh penempatan siswa pada hari ini. Siswa yang pindah rombel masuk ke cakupan wali kelas barunya untuk semua tanggal.
5. Untuk daftar presensi rombel dan rekap per rombel, cakupan Rombel adalah rombel yang diampu, termasuk semua siswa yang ditempatkan di rombel itu pada tanggal yang ditampilkan (BR-REK-05).
6. Cakupan Hari ini berarti tanggal hari berjalan menurut WIB (`02` §5).

### 4.2 Batas mundur

1. Tanggal T berada dalam batas mundur bila (hari ini − N hari) ≤ T ≤ hari ini. N adalah angka batas mundur, dengan default 7 (BR-MUN-01).
2. Batas mundur berlaku sesuai BR-MUN-02, dan tidak berlaku untuk admin (BR-MUN-03). Ringkasan penerapannya per fitur ada di FS-PRS-10.
3. Untuk data dengan rentang tanggal, setiap tanggal lampau yang terdampak harus berada dalam batas mundur. Tanggal ke depan mengikuti aturan fiturnya (BR-MUN-04, BR-IZN-11).
4. Formulir yang dibatasi batas mundur menampilkan rentang tanggal yang masih boleh, misalnya "Dapat diubah: 6–13 Oktober 2026".
5. Pesan penolakan menyebut alasannya, misalnya "Tanggal 5 Oktober 2026 di luar batas mundur (7 hari). Hubungi admin."

### 4.3 Alasan wajib

1. Tindakan berikut wajib disertai alasan:
   - presensi manual dan pembatalannya;
   - koreksi status dan penghapusannya;
   - ubah jadwal hari ini;
   - aktivasi dan pengakhiran mode darurat;
   - perubahan keputusan izin/sakit/dispensasi;
   - penolakan pengajuan izin/sakit (berupa catatan).
2. Alasan presensi manual dipilih dari daftar di BR-KOR-04. Alasan lainnya berupa teks bebas.
3. Alasan yang hanya berisi spasi dianggap kosong. Panjang maksimal alasan ditetapkan di Session 9.
4. Alasan koreksi dan catatan verifikasi dapat dibaca siswa di portal (§2.3). Formulirnya menampilkan pengingat ini. (DECISION, Session 4b)

### 4.4 Log perubahan presensi

1. Tindakan di tabel bawah ditulis ke log perubahan presensi (BR-KOR-10). Log ini dibuka lewat FS-PRS-11.
2. Isi setiap entri: jenis perubahan, siswa (bila ada), tanggal presensi yang terdampak, data lama, data baru, alasan, pelaku (atau "sistem"), dan waktu (`05` §14). Struktur tabel dan kode jenis entri ada di `06` §12.1.
3. Tindakan massal menulis satu entri per siswa, dengan penanda kelompok yang sama.
4. Log tidak dapat diubah atau dihapus dari aplikasi.
5. Log lain berada di luar log ini:
   - perubahan nomor WA, foto, masa aktif, penempatan, dan data siswa lainnya dicatat di log data siswa (FS-MD-04, FS-MD-05, `06` §12.2);
   - aktivitas akun (login, reset password, perubahan role, dan akun stasiun) dicatat di log aktivitas akun, yang dirinci di Session 9;
   - pencatatan pembukaan lampiran mengikuti OQ-17.

| Jenis perubahan | Fitur |
|---|---|
| Presensi manual dicatat atau dibatalkan, termasuk presensi per rombel saat darurat | FS-PRS-06, FS-PRS-09 |
| Koreksi status disimpan, diganti, atau dihapus | FS-PRS-07 |
| Izin/sakit/dispensasi diinput staf, diverifikasi (disetujui atau ditolak), atau diubah keputusannya | FS-IZN-02 s.d. FS-IZN-05 |
| Scan bertanda diterima atau ditolak | FS-KIO-06 |
| Jadwal hari ini diubah atau dikembalikan | FS-PRS-04 |
| Mode darurat diaktifkan atau diakhiri, termasuk berakhir otomatis | FS-PRS-08 |
| Pola mingguan, jadwal khusus, libur, atau tanggal semester diubah | FS-PRS-01 s.d. FS-PRS-03, FS-MD-02 |
| Angka batas mundur diubah | FS-PRS-10 |

### 4.5 Hitung ulang status

1. Status harian dan kejadian pulang dihitung ulang setiap kali sumbernya berubah, tanpa langkah manual (BR-STS-06). Pemicunya adalah:
   - scan tersinkron atau scan bertanda ditinjau;
   - presensi manual dicatat atau dibatalkan;
   - koreksi disimpan atau dihapus;
   - izin/sakit/dispensasi disetujui atau diubah keputusannya;
   - aturan jam, jadwal khusus, jadwal hari ini, libur, atau tanggal semester berubah;
   - masa aktif atau penempatan siswa berubah;
   - mode darurat diaktifkan atau diakhiri.
2. Perubahan karena waktu juga terjadi tanpa langkah manual, misalnya saat sesi masuk ditutup atau saat mode darurat berakhir otomatis.
3. Hasil hitungan disimpan sebagai salinan di `status_harian`. Bagian yang bergantung pada jam sekarang, yaitu belum hadir atau Alpa pada hari ini dan kejadian tidak scan pulang pada hari ini, diturunkan saat dibaca (DECISION, Session 5, `06` §11). Mekanismenya adalah antrean yang ditulis dalam transaksi yang sama dengan perubahan sumber, lalu diproses segera (DECISION, Session 6, `07` §7).

### 4.6 Perubahan bersamaan

Saat menyimpan perubahan, sistem memeriksa bahwa data belum diubah orang lain sejak formulir dibuka. Bila sudah berubah, penyimpanan ditolak. Sistem lalu menampilkan data terbaru, termasuk siapa yang mengubahnya dan kapan. Aturan ini sejalan dengan BR-IZN-08, yaitu keputusan yang tersimpan lebih dulu yang berlaku. Cara teknisnya memakai `updated_at` sebagai token versi (DECISION, Session 6, `07` ARS-40). Perubahan yang bergantung pada keadaan data, misalnya verifikasi pengajuan yang masih menunggu (BR-IZN-08), memeriksa keadaan itu saat menyimpan (RECOMMENDATION, `07` ARS-41).

### 4.7 Konfirmasi

Tindakan massal, tindakan yang membuat password baru, dan tindakan yang mengubah status banyak siswa meminta konfirmasi lebih dulu. Layar konfirmasi menyebut dampaknya, misalnya jumlah akun atau siswa yang terdampak.

### 4.8 Waktu dan tanggal

1. Semua waktu memakai WIB. "Hari ini" adalah tanggal WIB menurut jam server (BR-JAM-12).
2. Jam dibandingkan sampai detik sesuai BR-JAM-03 s.d. BR-JAM-05. Contohnya, dengan batas terlambat 07.00, pukul 07.00.59 masih Hadir.
3. Jam ditampilkan dengan format JJ.MM. Format final tanggal dan jam ditetapkan di Session 7.

### 4.9 File unggahan

1. Foto siswa, lampiran surat, dan file import disimpan di luar folder `public/`. File tersebut hanya diberikan lewat permintaan yang sudah diperiksa haknya (NFR-10, R-17).
2. Logo sekolah bukan data pribadi, sehingga boleh tampil di halaman login dan halaman publik.
3. Format dan ukuran maksimal file ditetapkan di Session 9.

## 5. Akun dan akses (AKN)

### FS-AKN-01 — Login dan logout

| Item | Isi |
|---|---|
| Tujuan | Pengguna masuk ke area sesuai jenis akunnya, lalu keluar dengan aman. |
| Rilis | R1 |
| Requirement | FR-AKN-01, FR-AKN-04, NFR-07, NFR-08 |
| Alur | UF-20 |
| Aktor dan hak | Semua jenis akun (`HA-AKN-01`). Akun stasiun hanya login. |
| Aturan terkait | `02` §2 (aturan jenis akun), §4 butir 4 (pemisahan area), §8 (area dan halaman awal) |
| Status | DECISION (login per jenis akun; pemisahan area untuk akun staf dan akun stasiun); RECOMMENDATION (satu halaman login dan rincian lain) |

**Prasyarat**

- Akun sudah dibuat (FS-AKN-03, FS-AKN-04, FS-AKN-05).

**Input dan validasi**

| Isian | Wajib | Validasi |
|---|---|---|
| Identitas login | Ya | NISN untuk siswa, atau username untuk staf dan stasiun. Spasi di awal dan akhir dibuang. |
| Password | Ya | Dicocokkan dengan hash password (`02` §2 butir 6). |

**Perilaku**

1. Semua jenis akun memakai satu halaman login (`02` §8).
2. Sistem mencari akun dari identitas login. Username staf dan stasiun tidak pernah hanya berisi angka, sehingga tidak bentrok dengan NISN (`02` §2 butir 4).
3. Bila identitas dan password cocok, dan akun aktif, sistem membuat sesi.
4. Bila akun wajib mengganti password, sistem hanya membuka halaman ganti password (FS-AKN-02).
5. Selain itu, sistem mengarahkan pengguna ke halaman awal areanya (`02` §8):
   - akun staf ke dashboard hari ini;
   - akun siswa ke riwayat kehadiran sendiri;
   - akun stasiun ke layar scan.
6. Setiap jenis akun hanya dapat membuka areanya sendiri (`02` §4 butir 4). Permintaan ke area lain diarahkan ke halaman awal akun itu.
7. Percobaan login yang gagal dibatasi per identitas login dan per alamat IP (NFR-08). Setelah batasnya tercapai, login ditolak sementara.
8. Logout mengakhiri sesi dan kembali ke halaman login. Logout akun stasiun di kiosk adalah tindakan berisiko dan diatur di FS-KIO-01.
9. Bila akun dinonaktifkan saat sesinya berjalan, sesi itu berakhir pada permintaan berikutnya (`02` §2 butir 5).

**Keadaan kosong dan error**

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | Identitas login tidak ada, atau password salah. | Pesan umum: "NISN/username atau password salah." |
| E2 | Akun nonaktif, atau akun siswa yang belum memiliki password. | Pesan umum yang sama dengan E1 (UF-20 E2). |
| E3 | Batas percobaan login tercapai. | "Terlalu banyak percobaan. Coba lagi dalam beberapa menit." |
| E4 | Sesi berakhir. | Pengguna kembali ke halaman login. Setelah login, pengguna diarahkan ke halaman yang tadi dibuka, kecuali kiosk. |
| E5 | Pengguna membuka area jenis akun lain. | Pengguna diarahkan ke halaman awal areanya sendiri. |

**Data dan log**

- Dibaca: akun (identitas login, hash password, status, penanda wajib ganti password, dan role).
- Ditulis: sesi, catatan percobaan login gagal, dan waktu login terakhir.
- Log: login gagal dan penolakan sementara dicatat di log keamanan (Session 9).

**Parameter dan default**

| Parameter | Default | Diatur oleh | Dipastikan di |
|---|---|---|---|
| Batas percobaan login gagal dan lama penolakan | Belum ditetapkan | Sistem | Session 9 |
| Masa berlaku sesi staf dan siswa | Belum ditetapkan | Sistem | Session 9 |
| Masa berlaku login akun stasiun | 90 hari sejak kontak terakhir (`02` §7.3, `07` ARS-30) | Sistem | Session 6 (DECISION) |

**Di luar cakupan**

- Reset password mandiri lewat email atau WhatsApp (FR-AKN-07).
- Login dengan akun Google atau SSO, dan verifikasi dua langkah.
- Pilihan "ingat saya" untuk akun staf dan akun siswa.

**Catatan antarmuka awal**

- Satu kolom identitas berlabel "NISN atau username".
- Halaman login menampilkan nama produk dan identitas sekolah (FS-MD-01).
- Teks bantuan: siswa yang lupa atau belum punya password menghubungi wali kelas, dan staf menghubungi admin.

**Acceptance criteria**

**AC-AKN-01-01 — Login staf dan siswa**
Rujukan: FR-AKN-01, `HA-AKN-01`, UF-20, `02` §8.

```text
Given akun staf "rina.w" dan akun siswa dengan NISN 0012345678 aktif dan tidak wajib mengganti password
When staf login dengan username dan password yang benar
Then staf masuk ke dashboard hari ini
When siswa login dengan NISN dan password yang benar
Then siswa masuk ke riwayat kehadirannya sendiri di portal siswa
```

**AC-AKN-01-02 — Pesan gagal yang sama**
Rujukan: FR-AKN-01, UF-20 E2, NFR-08.

```text
Given akun A aktif, akun B nonaktif, dan akun siswa C belum memiliki password
When login gagal karena password akun A salah, karena akun B nonaktif, atau karena akun C dicoba dengan sembarang password
Then ketiga percobaan menampilkan pesan yang sama: "NISN/username atau password salah."
```

**AC-AKN-01-03 — Pembatasan percobaan login**
Rujukan: NFR-08, UF-20 E1.

```text
Given batas percobaan login gagal sudah tercapai untuk satu identitas login
When pengguna mencoba login lagi dengan password yang benar sebelum masa penolakan habis
Then login ditolak sementara
When masa penolakan sudah habis
Then login dengan password yang benar berhasil
```

**AC-AKN-01-04 — Wajib ganti password**
Rujukan: FR-AKN-06, UF-20, `02` §2 butir 1.

```text
Given akun staf baru masih memakai password awal
When staf login
Then sistem hanya menampilkan halaman ganti password
  And membuka alamat halaman lain mengarahkan staf kembali ke halaman ganti password
```

**AC-AKN-01-05 — Pemisahan area**
Rujukan: `HA-KIO-01`, `02` §4 butir 3 dan 4.

```text
Given admin sudah login dengan akun staf
When admin membuka alamat kiosk
Then kiosk tidak terbuka dan admin diarahkan ke dashboard hari ini
Given akun stasiun sudah login
When akun stasiun membuka alamat panel staf
Then panel tidak terbuka dan akun stasiun diarahkan ke layar scan
```

**AC-AKN-01-06 — Akun dinonaktifkan saat sesi berjalan**
Rujukan: FR-AKN-02, `02` §2 butir 5.

```text
Given guru piket sedang login
When admin menonaktifkan akun guru piket tersebut
Then permintaan berikutnya dari guru piket diarahkan ke halaman login
  And guru piket tidak dapat login lagi
```

### FS-AKN-02 — Ganti password

| Item | Isi |
|---|---|
| Tujuan | Pengguna mengganti password sendiri, termasuk penggantian wajib setelah memakai password awal atau password hasil reset. |
| Rilis | R1 |
| Requirement | FR-AKN-04, FR-AKN-06 |
| Alur | UF-20 |
| Aktor dan hak | Akun staf dan akun siswa (`HA-AKN-01`). Akun stasiun tidak mengganti password sendiri; kredensialnya diganti admin (FS-AKN-04). |
| Aturan terkait | `02` §2 butir 1 dan 6, `02` §7.2 |
| Status | DECISION (ganti password sendiri; wajib ganti password awal dan password hasil reset); RECOMMENDATION (rincian) |

**Prasyarat**

- Pengguna sudah login (FS-AKN-01).

**Input dan validasi**

| Isian | Wajib | Validasi |
|---|---|---|
| Password lama | Ya, kecuali pada penggantian wajib tepat setelah login | Cocok dengan password saat ini. |
| Password baru | Ya | Memenuhi aturan password (Session 9). Tidak sama dengan password lama, dan tidak sama dengan identitas login. |
| Ulangi password baru | Ya | Sama dengan password baru. |

**Perilaku**

1. Sistem memeriksa isian, lalu menyimpan hash password baru (`02` §2 butir 6).
2. Penanda wajib ganti password dihapus.
3. Akun siswa yang berstatus belum aktif berubah menjadi aktif (`02` §7.2).
4. Sesi lain dari akun yang sama diakhiri. Sesi yang sedang dipakai tetap berjalan.
5. Pengguna diarahkan ke halaman awal areanya.

**Keadaan kosong dan error**

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | Password lama salah. | "Password lama salah." Password tidak berubah. |
| E2 | Ulangan password tidak sama. | "Ulangan password tidak sama." |
| E3 | Password baru tidak memenuhi aturan, sama dengan password lama, atau sama dengan identitas login. | Penggantian ditolak dengan penjelasan aturan yang dilanggar. |

**Data dan log**

- Dibaca: hash password dan penanda wajib ganti password.
- Ditulis: hash password, penanda wajib ganti password, status akun siswa, dan waktu penggantian password.
- Log: penggantian password dicatat tanpa isi password, di log aktivitas akun (Session 9).

**Parameter dan default**

| Parameter | Default | Diatur oleh | Dipastikan di |
|---|---|---|---|
| Aturan password (panjang minimal dan jenis karakter) | Belum ditetapkan | Sistem | Session 9 |

**Di luar cakupan**

- Reset password mandiri.
- Larangan memakai ulang password lama selain password saat ini.
- Pengukur kekuatan password.

**Catatan antarmuka awal**

- Aturan password tampil di formulir.
- Tombol untuk menampilkan dan menyembunyikan password.

**Acceptance criteria**

**AC-AKN-02-01 — Login pertama siswa**
Rujukan: FR-AKN-06, `HA-AKN-01`, UF-05, UF-20, `02` §7.2.

```text
Given akun siswa berstatus belum aktif dan siswa memegang password awal dari slip akun
When siswa login dengan NISN dan password awal
Then sistem hanya menampilkan halaman ganti password
When siswa mengisi password baru yang memenuhi aturan dan mengulanginya dengan benar
Then password baru tersimpan
  And status akun siswa menjadi aktif
  And siswa diarahkan ke riwayat kehadirannya
  And password awal tidak dapat dipakai lagi
```

**AC-AKN-02-02 — Password lama salah**
Rujukan: FR-AKN-04, `HA-AKN-01`.

```text
Given staf sudah login dan tidak wajib mengganti password
When staf mengganti password dengan password lama yang salah
Then penggantian ditolak dengan pesan "Password lama salah."
  And password tidak berubah
```

**AC-AKN-02-03 — Password baru ditolak**
Rujukan: FR-AKN-04, `02` §10 (NISN bukan rahasia).

```text
Given siswa sedang mengganti password
When siswa memasukkan password baru yang sama dengan NISN-nya, atau yang tidak memenuhi aturan password
Then penggantian ditolak dengan penjelasan aturan yang dilanggar
```

**AC-AKN-02-04 — Sesi lain berakhir**
Rujukan: FR-AKN-04.

```text
Given staf login di dua perangkat
When staf mengganti password di perangkat pertama
Then sesi di perangkat kedua berakhir pada permintaan berikutnya
  And sesi di perangkat pertama tetap berjalan
```

### FS-AKN-03 — Akun staf dan role

| Item | Isi |
|---|---|
| Tujuan | Admin membuat dan mengelola akun staf beserta role-nya. |
| Rilis | R1 |
| Requirement | FR-AKN-02, FR-AKN-06, FR-AKN-07, FR-AKN-08 |
| Alur | UF-04, UF-21 |
| Aktor dan hak | Admin (`HA-AKN-02`). |
| Aturan terkait | `02` §2, §3, §4 butir 2 dan 6, §7.1 |
| Status | DECISION (akun staf untuk semua guru dan staf; role tetap dengan hak gabungan; reset oleh admin); RECOMMENDATION (rincian) |

**Prasyarat**

- Admin sudah login. Admin pertama dibuat saat instalasi lewat perintah CLI (`07` ARS-49).

**Input dan validasi**

| Isian | Wajib | Validasi |
|---|---|---|
| Nama lengkap | Ya | Teks. |
| Username | Ya | Unik di semua akun staf dan akun stasiun. Tidak boleh hanya berisi angka (`02` §2 butir 4). Karakter yang boleh: huruf kecil, angka, titik, dan garis bawah. Panjangnya ditetapkan di Session 9. |
| Role | Tidak | Pilihan: Admin, Guru piket, Guru BK, dan Pimpinan. Role Staf melekat otomatis. Role Wali kelas tidak dipilih di sini (`02` §4 butir 2). |

**Perilaku**

1. **Tambah akun.** Sistem membuat password awal acak dengan karakter yang mudah dibaca (`02` §7.2 butir 5), lalu menampilkannya sekali. Akun wajib mengganti password saat login pertama.
2. **Ubah akun.** Admin mengubah nama, username, dan role. Perubahan role berlaku mulai permintaan berikutnya dari pengguna itu.
3. **Daftar akun.** Daftar akun staf menampilkan nama, username, role, rombel yang diampu sebagai wali kelas (FS-MD-03), status, dan waktu login terakhir.
4. **Nonaktifkan.** Akun tidak dapat login, dan sesi yang sedang berjalan berakhir. Nama pemiliknya tetap tampil di log (`02` §2 butir 5). Admin dapat mengaktifkan kembali akun yang nonaktif.
5. **Reset password.** Sistem membuat password acak baru, menampilkannya sekali, dan mewajibkan penggantian saat login berikutnya. Password lama tidak berlaku lagi.
6. **Selalu ada admin aktif** (`02` §4 butir 6). Sistem menolak mencabut role Admin dari admin aktif terakhir, dan menolak menonaktifkan admin aktif terakhir.
7. Admin tidak dapat menonaktifkan akunnya sendiri.
8. Akun staf tidak dihapus dari aplikasi. Akun yang salah dibuat diubah atau dinonaktifkan.
9. Menonaktifkan staf yang menjadi wali kelas tidak menghapus penugasannya. Daftar rombel menandai rombel yang wali kelasnya nonaktif, dan admin menetapkan penggantinya (FS-MD-03).

**Keadaan kosong dan error**

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | Username sudah dipakai. | "Username sudah dipakai." |
| E2 | Username hanya berisi angka, atau memakai karakter yang tidak boleh. | Penyimpanan ditolak dengan penjelasan aturan username. |
| E3 | Tindakan membuat tidak ada lagi admin aktif. | "Harus ada minimal satu admin aktif." (UF-04 E3) |
| E4 | Admin menonaktifkan akunnya sendiri. | Ditolak. |
| E5 | Password awal belum diserahkan, tetapi halamannya sudah ditutup. | Admin mereset password akun itu. |

**Data dan log**

- Ditulis: akun staf (nama, username, hash password, status, penanda wajib ganti password, role, dan waktu login terakhir).
- Log: pembuatan akun, perubahan role, penonaktifan, pengaktifan kembali, dan reset password dicatat di log aktivitas akun (`02` §10, Session 9).

**Parameter dan default**

| Parameter | Default | Diatur oleh | Dipastikan di |
|---|---|---|---|
| Panjang password awal dan aturan username | Belum ditetapkan | Sistem | Session 9 |

**Di luar cakupan**

- Role baru, atau pengaturan hak lewat antarmuka (`02` §1).
- Import akun staf secara massal.
- Penghapusan akun.

**Catatan antarmuka awal**

- Password awal tampil sekali dengan tombol salin dan cetak, disertai peringatan bahwa password tidak dapat dilihat lagi.

**Acceptance criteria**

**AC-AKN-03-01 — Buat akun guru piket**
Rujukan: FR-AKN-02, FR-AKN-06, `HA-AKN-02`, UF-04.

```text
Given admin membuka daftar akun staf
When admin menambah akun "Rina Wulandari" dengan username "rina.w" dan role Guru piket
Then akun tersimpan dengan role Staf dan Guru piket
  And password awal acak tampil sekali
  And saat Rina login pertama kali, Rina wajib mengganti password
```

**AC-AKN-03-02 — Username ditolak**
Rujukan: `HA-AKN-02`, UF-04 E1, `02` §2 butir 4.

```text
Given username "rina.w" sudah dipakai
When admin menambah akun dengan username "rina.w", atau dengan username "1987654321"
Then sistem menolak dengan alasan masing-masing: username sudah dipakai, atau username tidak boleh hanya berisi angka
```

**AC-AKN-03-03 — Admin aktif terakhir**
Rujukan: `HA-AKN-02`, UF-04 E3, `02` §4 butir 6.

```text
Given hanya ada satu akun admin aktif
When admin itu mencabut role Admin dari akunnya sendiri
Then sistem menolak dengan pesan "Harus ada minimal satu admin aktif."
```

**AC-AKN-03-04 — Nonaktifkan staf**
Rujukan: FR-AKN-02, UF-04 E2, `02` §2 butir 5.

```text
Given guru BK "Andi" pernah mengoreksi presensi
When admin menonaktifkan akun Andi
Then Andi tidak dapat login, dan sesinya yang sedang berjalan berakhir
  And log perubahan presensi tetap menampilkan nama Andi pada koreksi yang dibuatnya
```

**AC-AKN-03-05 — Reset password staf**
Rujukan: FR-AKN-07, `HA-AKN-02`, UF-21.

```text
Given staf lupa password
When admin mereset password staf tersebut
Then password acak baru tampil sekali
  And password lama tidak dapat dipakai
  And staf wajib mengganti password saat login berikutnya
```

**AC-AKN-03-06 — Hak gabungan**
Rujukan: FR-AKN-08, `HA-PRS-03`, `02` §4 butir 1.

```text
Given akun staf memiliki role Guru piket dan ditetapkan sebagai wali kelas 7A
When staf membuka presensi manual
Then staf dapat mencatat presensi manual hari ini untuk siswa semua rombel
  And staf dapat mencatat presensi manual untuk tanggal lampau dalam batas mundur, tetapi hanya untuk siswa 7A
```

### FS-AKN-04 — Akun stasiun

| Item | Isi |
|---|---|
| Tujuan | Admin membuat dan mengelola akun stasiun, satu akun untuk setiap laptop stasiun scan. |
| Rilis | R1 |
| Requirement | FR-AKN-02, FR-AKN-03 |
| Alur | UF-06 |
| Aktor dan hak | Admin (`HA-AKN-03`, `HA-KIO-02`). |
| Aturan terkait | `02` §2, §4 butir 3 dan 4, §7.3, R-07 |
| Status | DECISION (satu akun stasiun per laptop; login 90 hari, Session 6); RECOMMENDATION (rincian; keamanan login di Session 9) |

**Prasyarat**

- Admin sudah login.

**Input dan validasi**

| Isian | Wajib | Validasi |
|---|---|---|
| Nama stasiun | Ya | Unik dan mudah dikenali, misalnya "Gerbang 1". |
| Username | Ya | Aturannya sama dengan username staf (FS-AKN-03). |

**Perilaku**

1. **Tambah.** Sistem membuat password acak dan menampilkannya sekali. Admin memakainya untuk login di laptop stasiun (UF-06). Akun stasiun tidak wajib mengganti password.
2. **Ganti kredensial.** Sistem membuat password baru dan mengakhiri login akun stasiun itu, sehingga kiosk meminta login ulang. Scan yang belum tersinkron tetap tersimpan di laptop dan dikirim setelah login ulang (FS-KIO-03).
3. **Nonaktifkan.**
   - Sebelum menonaktifkan, sistem menampilkan status stasiun: waktu sinkron terakhir dan jumlah scan belum tersinkron yang terakhir dilaporkan (FS-KIO-05).
   - Bila jumlah itu lebih dari nol, atau stasiun belum melapor hari ini, sistem memperingatkan bahwa scan yang belum tersinkron akan hilang (UF-06).
   - Admin tetap dapat menonaktifkan setelah konfirmasi, misalnya karena laptop hilang (UF-06 E1).
4. Kiosk yang menerima status "akun nonaktif" dari server menghapus data siswa, foto, dan scan di laptop itu (R-07). Cara teknisnya ada di `07` ARS-31.
5. Admin dapat mengaktifkan kembali akun stasiun. Kiosk lalu memuat data dari awal.
6. Akun stasiun hanya dapat membuka kiosk (`02` §4 butir 4). Admin tidak membuka kiosk dengan akun staf (`02` §4 butir 3).

**Keadaan kosong dan error**

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | Nama stasiun atau username sudah dipakai. | Penyimpanan ditolak. |
| E2 | Stasiun yang akan dinonaktifkan masih melaporkan scan belum tersinkron. | Peringatan beserta jumlah scan. Penonaktifan menunggu konfirmasi. |
| E3 | Password stasiun hilang. | Admin mengganti kredensial. |

**Data dan log**

- Ditulis: akun stasiun (nama, username, hash password, dan status).
- Dibaca: status stasiun (FS-KIO-05).
- Log: pembuatan, penggantian kredensial, penonaktifan, dan pengaktifan kembali dicatat di log aktivitas akun (`02` §10, Session 9).

**Parameter dan default**

| Parameter | Default | Diatur oleh | Dipastikan di |
|---|---|---|---|
| Masa berlaku login akun stasiun | 90 hari sejak kontak terakhir (`02` §7.3, `07` ARS-30) | Sistem | Session 6 (DECISION) |

**Di luar cakupan**

- Mengikat akun stasiun ke satu perangkat. Kebutuhan ini ditinjau di Session 9.
- Mengunci laptop dari jarak jauh selain dengan menonaktifkan akun.
- Stasiun di lokasi selain gerbang utama (`02` §9).

**Catatan antarmuka awal**

- Daftar akun stasiun digabung dengan status stasiun (FS-KIO-05).

**Acceptance criteria**

**AC-AKN-04-01 — Buat dan pakai akun stasiun**
Rujukan: FR-AKN-03, `HA-AKN-03`, UF-06.

```text
Given admin menambah akun stasiun "Gerbang 1"
When admin login dengan akun itu di laptop stasiun
Then laptop membuka layar scan
  And akun itu tidak dapat membuka panel staf atau portal siswa
```

**AC-AKN-04-02 — Nonaktifkan stasiun yang belum tersinkron**
Rujukan: FR-KIO-12, `HA-AKN-03`, `HA-KIO-02`, UF-06.

```text
Given laporan terakhir "Gerbang 2" hari ini menyisakan 15 scan belum tersinkron
When admin memilih menonaktifkan "Gerbang 2"
Then sistem memperingatkan bahwa 15 scan belum tersinkron akan hilang
  And akun baru dinonaktifkan setelah admin mengonfirmasi
```

**AC-AKN-04-03 — Kiosk dengan akun nonaktif**
Rujukan: FR-AKN-03, UF-06 E1, R-07.

```text
Given akun stasiun "Gerbang 2" sudah dinonaktifkan
When kiosk "Gerbang 2" menghubungi server
Then kiosk menampilkan bahwa akun stasiun tidak aktif
  And data siswa, foto, dan scan di laptop itu dihapus
  And kiosk tidak dapat dipakai untuk scan
```

**AC-AKN-04-04 — Ganti kredensial tanpa kehilangan scan**
Rujukan: FR-AKN-03, FR-KIO-07, NFR-03.

```text
Given kiosk "Gerbang 1" memiliki 4 scan belum tersinkron
When admin mengganti kredensial "Gerbang 1"
Then kiosk meminta login ulang
  And setelah login dengan kredensial baru, keempat scan terkirim ke server
```

### FS-AKN-05 — Akun siswa dan slip akun

| Item | Isi |
|---|---|
| Tujuan | Setiap siswa aktif memiliki akun. Password awal dibagikan lewat slip akun per rombel, dan password siswa dapat direset per siswa. |
| Rilis | R1 |
| Requirement | FR-AKN-05, FR-AKN-06, FR-AKN-07 |
| Alur | UF-05, UF-07, UF-21 |
| Aktor dan hak | Sistem membuat akun. Admin (semua) dan wali kelas (rombel) melihat status akun (`HA-AKN-06`), mencetak slip akun (`HA-AKN-05`), dan mereset password (`HA-AKN-04`). |
| Aturan terkait | `02` §2, §7.2 |
| Status | DECISION (akun otomatis, slip per rombel, mekanisme slip, reset oleh admin dan wali kelas); RECOMMENDATION (rincian) |

**Prasyarat**

- Siswa sudah ada dan ditempatkan di rombel (FS-MD-04, FS-MD-05).

**Input dan validasi**

| Isian | Wajib | Validasi |
|---|---|---|
| Rombel, untuk cetak slip | Ya | Rombel tahun ajaran aktif yang berada dalam cakupan pengguna. |
| Siswa yang dicetak | Ya | Default semua akun belum aktif di rombel itu. Pengguna dapat mengurangi pilihan, misalnya hanya siswa baru. Hanya akun belum aktif yang dapat dipilih. |
| Siswa, untuk reset password | Ya | Siswa dalam cakupan dengan akun aktif atau belum aktif. |

**Perilaku**

A. Pembuatan dan status akun:

1. Saat siswa ditambah atau diimpor, sistem membuat akun siswa dengan username NISN, tanpa password yang dapat dipakai, dan berstatus belum aktif (`02` §7.2).
2. Saat siswa dinonaktifkan, akunnya ikut nonaktif dan sesinya berakhir (`02` §2 butir 2). Saat siswa diaktifkan kembali, akunnya kembali ke status sebelum dinonaktifkan.
3. Bila admin mengoreksi NISN siswa, username akunnya ikut berubah.
4. Daftar akun per rombel menampilkan NISN, nama, status akun, waktu slip terakhir dibuat, dan waktu login terakhir.

B. Cetak slip akun:

1. Wali kelas atau admin memilih rombel dan siswa yang akan dicetak.
2. Sistem meminta konfirmasi. Layar konfirmasi menyebut jumlah akun yang akan mendapat password baru, dan memperingatkan bahwa slip lama untuk akun tersebut tidak berlaku lagi (`02` §7.2 butir 3).
3. Setelah konfirmasi, sistem membuat password acak baru untuk setiap akun terpilih dan menandainya wajib ganti password.
4. Sistem menampilkan halaman slip siap cetak, beberapa slip per halaman A4. Setiap slip berisi nama, NISN, rombel, password awal, alamat aplikasi, petunjuk singkat login pertama, dan identitas sekolah.
5. Halaman slip hanya dapat dilihat saat itu. Setelah halaman ditutup, password tidak dapat ditampilkan lagi (`02` §2 butir 6), dan sistem tidak menyimpan slip (UF-05).
6. Akun yang sudah aktif tidak tersentuh.

C. Reset password satu siswa:

1. Admin, atau wali kelas untuk rombelnya, memilih reset password untuk satu siswa.
2. Sistem membuat password acak baru, menandai akun wajib ganti password, dan menampilkan slip untuk satu siswa.
3. Status akun tidak berubah. Akun aktif tetap aktif, dengan kewajiban mengganti password saat login berikutnya.

**Keadaan kosong dan error**

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | Rombel tidak memiliki akun belum aktif. | "Semua akun di rombel ini sudah aktif." Slip tidak dibuat. |
| E2 | Reset untuk akun nonaktif. | Ditolak, karena siswa nonaktif tidak dapat login. |
| E3 | Siswa atau rombel di luar cakupan. | Ditolak (§4.1). |
| E4 | Halaman slip tertutup sebelum dicetak. | Pengguna mencetak ulang slip, yang membuat password baru (UF-05 E1). |

**Data dan log**

- Ditulis: akun siswa (username NISN, hash password, status, penanda wajib ganti password, waktu slip terakhir dibuat, dan waktu login terakhir).
- Log: cetak slip (pelaku, rombel, dan jumlah akun) dan reset password (pelaku dan siswa) dicatat di log aktivitas akun (Session 9). Password tidak pernah dicatat.

**Parameter dan default**

| Parameter | Default | Diatur oleh | Dipastikan di |
|---|---|---|---|
| Jumlah slip per halaman A4 | Belum ditetapkan | Sistem | Session 7 |
| Panjang password awal | Belum ditetapkan | Sistem | Session 9 |

**Di luar cakupan**

- Mengirim password lewat WhatsApp atau email.
- Menyimpan slip di sistem.
- Siswa mengubah username sendiri.

**Catatan antarmuka awal**

- Peringatan sebelum slip dibuat.
- Halaman slip ramah cetak, dengan pengingat untuk mencetak atau menyimpan sekarang.

**Acceptance criteria**

**AC-AKN-05-01 — Akun dibuat saat import**
Rujukan: FR-AKN-05, FR-MD-05, UF-02, `02` §7.2.

```text
Given admin mengimpor 32 siswa baru ke rombel 7A
When import selesai
Then setiap siswa memiliki akun dengan username NISN dan status belum aktif
  And akun tersebut tidak dapat dipakai login sebelum slip dicetak
```

**AC-AKN-05-02 — Slip hanya untuk akun belum aktif**
Rujukan: FR-AKN-06, `HA-AKN-05`, UF-05.

```text
Given rombel 7A memiliki 30 akun aktif dan 2 akun belum aktif
When wali kelas 7A mencetak slip akun untuk rombel 7A
Then sistem meminta konfirmasi dengan keterangan 2 akun akan mendapat password baru
  And setelah dikonfirmasi, halaman slip berisi 2 slip
  And password 30 akun aktif tidak berubah
```

**AC-AKN-05-03 — Cetak ulang membatalkan slip lama**
Rujukan: FR-AKN-06, UF-05 E1, `02` §7.2 butir 3.

```text
Given slip siswa B sudah dicetak, tetapi siswa B belum login
When wali kelas mencetak ulang slip untuk siswa B
Then password di slip lama tidak dapat dipakai login
  And password di slip baru dapat dipakai login
```

**AC-AKN-05-04 — Reset oleh wali kelas**
Rujukan: FR-AKN-07, `HA-AKN-04`, UF-21.

```text
Given siswa C di rombel 7A berstatus aktif dan lupa password
When wali kelas 7A mereset password siswa C
Then slip untuk satu siswa tampil sekali
  And siswa C wajib mengganti password saat login berikutnya
  And status akun siswa C tetap aktif
```

**AC-AKN-05-05 — Di luar cakupan wali kelas**
Rujukan: `HA-AKN-04`, `HA-AKN-05`, §4.1.

```text
Given wali kelas 7A sudah login
When wali kelas 7A mencoba mereset password siswa rombel 7B, atau mencetak slip rombel 7B
Then sistem menolak permintaan tersebut
```

**AC-AKN-05-06 — Siswa dinonaktifkan**
Rujukan: FR-AKN-05, UF-07, `02` §2 butir 2.

```text
Given siswa D sedang login di portal
When admin menonaktifkan siswa D
Then akun siswa D menjadi nonaktif dan sesinya berakhir
  And siswa D tidak dapat login
```

## 6. Master data (MD)

### FS-MD-01 — Identitas sekolah

| Item | Isi |
|---|---|
| Tujuan | Admin mengatur nama resmi, alamat, dan logo sekolah yang tampil di aplikasi. |
| Rilis | R1 |
| Requirement | FR-MD-08 |
| Alur | UF-01 |
| Aktor dan hak | Admin (`HA-MD-09`). |
| Aturan terkait | OQ-01 (terjawab) |
| Status | DECISION (isi identitas; nama produk tetap "Spensada"); RECOMMENDATION (rincian) |

**Prasyarat**

- Admin sudah login.

**Input dan validasi**

| Isian | Wajib | Validasi |
|---|---|---|
| Nama resmi sekolah | Ya | Teks. |
| Alamat | Tidak | Teks. |
| Logo | Tidak | File gambar. Format dan ukuran maksimal ditetapkan di Session 9 (§4.9). Ukurannya diperkecil otomatis. |

**Perilaku**

1. Admin menyimpan identitas sekolah. Perubahan langsung berlaku.
2. Identitas sekolah tampil di halaman login, kepala panel staf dan portal siswa, slip akun (FS-AKN-05), dan kiosk. Di rilis berikutnya juga di flyer, halaman publik, dan kartu.
3. Nama produk "Spensada" selalu tampil dan tidak dapat diubah.

**Keadaan kosong dan error**

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | Identitas belum diisi. | Aplikasi hanya menampilkan "Spensada". Dashboard admin menampilkan pengingat penyiapan (FS-LAP-01). |
| E2 | File logo tidak valid. | Penyimpanan logo ditolak; isian lain tetap dapat disimpan. |

**Data dan log**

- Ditulis: pengaturan identitas sekolah dan file logo.
- Log: perubahan identitas dicatat di log aktivitas pengaturan (Session 9).

**Parameter dan default**

Tidak ada.

**Di luar cakupan**

- Pengaturan tema atau warna aplikasi.
- Identitas untuk lebih dari satu sekolah.

**Catatan antarmuka awal**

- Pratinjau logo setelah diunggah.

**Acceptance criteria**

**AC-MD-01-01 — Identitas tampil di aplikasi**
Rujukan: FR-MD-08, `HA-MD-09`, UF-01.

```text
Given admin mengisi nama resmi sekolah dan mengunggah logo
When pengguna membuka halaman login atau mencetak slip akun
Then nama resmi dan logo sekolah tampil bersama nama produk "Spensada"
```

**AC-MD-01-02 — Hanya admin**
Rujukan: `HA-MD-09`.

```text
Given staf tanpa role admin sudah login
When staf membuka pengaturan identitas sekolah
Then permintaan ditolak
```

### FS-MD-02 — Tahun ajaran dan semester

| Item | Isi |
|---|---|
| Tujuan | Admin mengelola tahun ajaran beserta dua semesternya, dan menandai satu tahun ajaran sebagai aktif. |
| Rilis | R1 |
| Requirement | FR-MD-01 |
| Alur | UF-01, UF-08 |
| Aktor dan hak | Admin (`HA-MD-01`). |
| Aturan terkait | BR-KAL-05 (tanggal di luar semester bukan hari sekolah), BR-KAL-07 |
| Status | DECISION (tahun ajaran dan semester; satu tahun ajaran aktif); RECOMMENDATION (struktur dan rincian) |

**Prasyarat**

- Admin sudah login.

**Input dan validasi**

| Isian | Wajib | Validasi |
|---|---|---|
| Nama tahun ajaran | Ya | Unik, misalnya "2026/2027". |
| Tanggal mulai dan selesai tahun ajaran | Ya | Mulai sebelum selesai. Tidak tumpang tindih dengan tahun ajaran lain. |
| Semester ganjil: tanggal mulai dan selesai | Ya | Berada di dalam tahun ajaran. |
| Semester genap: tanggal mulai dan selesai | Ya | Berada di dalam tahun ajaran, setelah semester ganjil, dan tidak tumpang tindih. |

**Perilaku**

1. Admin membuat tahun ajaran beserta dua semesternya.
2. **Aktifkan.** Tepat satu tahun ajaran aktif. Tahun ajaran hanya dapat diaktifkan bila hari ini berada di dalam rentang tanggalnya. Sebelum mengaktifkan, sistem menjelaskan akibatnya, lalu meminta konfirmasi (UF-08 langkah 6):
   - hak wali kelas berpindah ke penugasan rombel tahun ajaran baru;
   - dashboard, presensi per rombel, dan kiosk memakai rombel tahun ajaran baru;
   - tahun ajaran lama menjadi tidak aktif, tetapi rekap dan riwayatnya tetap dapat dibuka.
3. **Ubah tanggal.** Perubahan tanggal semester mengubah hari sekolah (BR-KAL-05). Bila ada tanggal yang keluar dari semester, sistem menyebut tanggal tersebut dan meminta konfirmasi. Status tanggal yang terdampak dihitung ulang (§4.5), dan perubahannya dicatat di log perubahan presensi (§4.4).
4. **Hapus.** Tahun ajaran hanya dapat dihapus bila belum memiliki rombel.

**Keadaan kosong dan error**

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | Belum ada tahun ajaran aktif. | Kiosk tidak memiliki hari sekolah, dan dashboard menampilkan "Belum ada tahun ajaran aktif" dengan tautan penyiapan untuk admin. |
| E2 | Rentang tanggal tumpang tindih atau semester keluar dari tahun ajaran. | Penyimpanan ditolak. |
| E3 | Mengaktifkan tahun ajaran yang belum mulai atau sudah selesai. | Ditolak dengan penjelasan. |
| E4 | Menghapus tahun ajaran yang memiliki rombel. | Ditolak. |

**Data dan log**

- Ditulis: tahun ajaran (nama, tanggal mulai dan selesai, status aktif) dan semester (jenis, tanggal mulai dan selesai).
- Log: perubahan tanggal semester dicatat di log perubahan presensi (§4.4).

**Parameter dan default**

Tidak ada.

**Di luar cakupan**

- Lebih dari dua semester dalam satu tahun ajaran.
- Kurikulum, nilai, dan rapor.

**Catatan antarmuka awal**

- Tahun ajaran aktif ditandai jelas di daftar dan di kepala panel.

**Acceptance criteria**

**AC-MD-02-01 — Hanya satu tahun ajaran aktif**
Rujukan: FR-MD-01, `HA-MD-01`, UF-08.

```text
Given tahun ajaran 2026/2027 aktif
  And hari ini berada di dalam rentang tahun ajaran 2027/2028
When admin mengaktifkan tahun ajaran 2027/2028 dan mengonfirmasi
Then 2027/2028 menjadi satu-satunya tahun ajaran aktif
  And rekap dan riwayat tahun ajaran 2026/2027 tetap dapat dibuka
```

**AC-MD-02-02 — Di luar semester bukan hari sekolah**
Rujukan: FR-MD-01, BR-KAL-05.

```text
Given semester ganjil berakhir Sabtu, 19 Desember 2026 dan semester genap mulai 4 Januari 2027
When sistem menentukan status hari Senin, 21 Desember 2026
Then tanggal itu bukan hari sekolah, sehingga tidak ada siswa yang Alpa
  And kiosk menolak scan pada tanggal itu
```

**AC-MD-02-03 — Semester tumpang tindih**
Rujukan: `HA-MD-01`.

```text
Given admin mengisi semester genap yang dimulai sebelum semester ganjil selesai
When admin menyimpan
Then penyimpanan ditolak
```

### FS-MD-03 — Rombel dan wali kelas

| Item | Isi |
|---|---|
| Tujuan | Admin mengelola rombel per tahun ajaran dan menetapkan satu wali kelas untuk setiap rombel. |
| Rilis | R1 |
| Requirement | FR-MD-02 |
| Alur | UF-01, UF-08 |
| Aktor dan hak | Admin (`HA-MD-02`). |
| Aturan terkait | `02` §4 butir 2 (role wali kelas berasal dari penugasan) |
| Status | DECISION (rombel per tahun ajaran; satu wali kelas per rombel; tingkat dikunci setelah ada penempatan, Session 6); RECOMMENDATION (rincian) |

**Prasyarat**

- Tahun ajaran sudah ada (FS-MD-02). Akun staf calon wali kelas sudah ada (FS-AKN-03).

**Input dan validasi**

| Isian | Wajib | Validasi |
|---|---|---|
| Tahun ajaran | Ya | Default tahun ajaran aktif. |
| Nama rombel | Ya | Unik di dalam tahun ajaran, misalnya "7A". |
| Tingkat | Ya | 7, 8, atau 9 (A-01). |
| Wali kelas | Tidak saat dibuat | Akun staf aktif. |

**Perilaku**

1. Admin membuat, mengubah, dan melihat rombel per tahun ajaran. Daftar rombel menampilkan nama, tingkat, wali kelas, dan jumlah siswa.
2. Satu rombel memiliki paling banyak satu wali kelas. Satu staf boleh menjadi wali kelas lebih dari satu rombel.
3. Penetapan atau pergantian wali kelas langsung berlaku. Hak rombel berpindah ke wali kelas baru, dan wali kelas lama kehilangan hak untuk rombel itu (`02` §4 butir 2).
4. Hak wali kelas hanya berasal dari rombel tahun ajaran aktif. Penugasan di tahun ajaran yang belum aktif baru berlaku saat tahun ajaran itu diaktifkan.
5. Rombel hanya dapat dihapus bila belum pernah memiliki penempatan siswa.
6. Daftar rombel menandai rombel tanpa wali kelas atau dengan wali kelas yang akunnya nonaktif.
7. Tingkat rombel tidak dapat diubah setelah rombel memiliki penempatan siswa, karena libur per tingkat dan rekap bergantung padanya (`06` §6.4, DECISION Session 6).

**Keadaan kosong dan error**

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | Nama rombel sudah dipakai di tahun ajaran itu. | Penyimpanan ditolak. |
| E2 | Wali kelas yang dipilih akunnya nonaktif. | Penyimpanan ditolak. |
| E3 | Menghapus rombel yang memiliki penempatan siswa. | Ditolak. |
| E4 | Mengubah tingkat rombel yang memiliki penempatan siswa. | Ditolak. |

**Data dan log**

- Ditulis: rombel (tahun ajaran, nama, tingkat) dan penugasan wali kelas.
- Log: penetapan dan pergantian wali kelas dicatat di log aktivitas akun (Session 9).

**Parameter dan default**

| Parameter | Default | Diatur oleh | Dipastikan di |
|---|---|---|---|
| Daftar tingkat | 7, 8, 9 | Tetap | — |

**Di luar cakupan**

- Wali kelas pendamping, ruang kelas, dan jurusan.

**Catatan antarmuka awal**

- Label "rombel" di antarmuka ditetapkan di Session 7 (`00` §9).

**Acceptance criteria**

**AC-MD-03-01 — Hak wali kelas mengikuti penugasan**
Rujukan: FR-MD-02, `HA-MD-02`, `02` §4 butir 2.

```text
Given Bu Rina adalah wali kelas 7A di tahun ajaran aktif
When admin mengganti wali kelas 7A menjadi Pak Budi
Then Pak Budi langsung dapat melihat daftar nama dan mencetak slip akun siswa 7A
  And Bu Rina tidak lagi dapat membuka data siswa 7A
```

**AC-MD-03-02 — Nama rombel ganda**
Rujukan: `HA-MD-02`.

```text
Given rombel "7A" sudah ada di tahun ajaran 2026/2027
When admin membuat rombel "7A" lagi di tahun ajaran yang sama
Then penyimpanan ditolak
```

### FS-MD-04 — Data siswa

| Item | Isi |
|---|---|
| Tujuan | Admin mengelola data siswa. Admin dan wali kelas mengubah nomor WhatsApp orang tua/wali, dan setiap perubahannya tercatat. |
| Rilis | R1 |
| Requirement | FR-MD-03, FR-MD-09 |
| Alur | UF-07 |
| Aktor dan hak | Admin: tambah, ubah, dan nonaktifkan (`HA-MD-03`). Lihat profil: admin, guru piket, guru BK, dan pimpinan (semua), wali kelas (rombel), siswa (sendiri) (`HA-MD-05`). Ubah nomor WA: admin (semua) dan wali kelas (rombel) (`HA-MD-06`). Lihat log perubahan: admin, guru BK, dan pimpinan (semua), wali kelas (rombel) (`HA-MD-10`). |
| Aturan terkait | BR-KAL-06, R-12 |
| Status | DECISION (NISN unik; nomor WA opsional dan divalidasi, Session 4b; pengubah nomor WA; perubahan dicatat; atribut siswa dan alasan penonaktifan, Session 5; periode aktif yang dibatalkan tidak dihitung, Session 6); RECOMMENDATION (rincian) |

**Prasyarat**

- Tahun ajaran dan rombel sudah ada (FS-MD-02, FS-MD-03).

**Input dan validasi**

| Isian | Wajib | Validasi |
|---|---|---|
| NISN | Ya | Tepat 10 digit angka, disimpan sebagai teks sehingga nol di depan tetap utuh (R-12). Unik di antara semua siswa, aktif maupun nonaktif. |
| Nama lengkap | Ya | Teks. |
| Nomor WA orang tua/wali | Tidak | Bila diisi: nomor ponsel Indonesia yang diawali `08`, `628`, atau `+628`. Disimpan dalam format baku yang diawali 62, misalnya `081234567890` menjadi `6281234567890`. Panjangnya 10–15 digit setelah dibakukan. (DECISION opsional dan divalidasi, Session 4b) |
| Rombel dan tanggal mulai | Ya, saat siswa ditambah | Penempatan awal (FS-MD-05). |
| Tanggal mulai aktif | Ya, saat siswa ditambah | Default hari ini (BR-KAL-06). |
| NIS | Tidak | Teks, paling banyak 20 karakter. Unik bila diisi. Hanya informasi; login dan QR tetap memakai NISN. |
| Jenis kelamin | Tidak | L atau P. |
| Tanggal lahir | Tidak | Tanggal, tidak boleh tanggal ke depan. |
| Alamat rumah | Tidak | Teks. |
| Nama orang tua/wali | Tidak | Teks. |
| Atribut tambahan | Mengikuti definisinya | Isian dari FS-MD-09, divalidasi sesuai tipe dan wajib tidaknya. |

NIS, jenis kelamin, tanggal lahir, alamat rumah, nama orang tua/wali, dan atribut tambahan adalah atribut siswa yang diputuskan di Session 5 (DECISION). Aturan validasinya berstatus RECOMMENDATION.

**Perilaku**

1. **Tambah siswa.** Sistem menyimpan siswa, penempatan awalnya, dan akun siswa yang belum aktif (FS-AKN-05). Kiosk mengenali siswa ini setelah memuat ulang data (FS-KIO-01).
2. **Ubah data.** Admin mengubah NISN, nama, atribut lain, dan atribut tambahan. Koreksi NISN juga mengubah username akun siswa. Sistem memperingatkan bahwa QR di kartu harus berisi NISN baru (C-04).
3. **Ubah nomor WA.** Admin, atau wali kelas untuk rombelnya, mengubah atau mengosongkan nomor WA. Siswa tidak dapat mengubahnya (`02` §10).
4. **Nonaktifkan.** Admin mengisi tanggal terakhir aktif (default hari ini, tidak boleh tanggal ke depan) dan alasan: lulus, pindah sekolah, keluar, meninggal dunia, salah input, atau lainnya (DECISION, Session 5). Alasan "lainnya" wajib disertai keterangan (RECOMMENDATION). Akibatnya:
   - siswa tidak memiliki status setelah tanggal terakhir aktif (BR-KAL-05, BR-KAL-06), dan status tanggal itu dihitung ulang;
   - bila alasannya salah input, atau periode aktifnya belum dimulai, periode itu dibatalkan: admin tidak mengisi tanggal terakhir aktif, dan siswa tidak memiliki status pada tanggal mana pun dalam periode itu (`06` §6.6, DECISION Session 6);
   - akun siswa nonaktif (FS-AKN-05);
   - siswa tidak lagi dimuat kiosk setelah data dimuat ulang;
   - riwayat kehadirannya tetap tersimpan.
5. **Aktifkan kembali.** Admin mengisi tanggal mulai aktif yang baru dan menempatkan siswa ke rombel. Sistem membuat periode aktif baru, dan periode lama tetap tersimpan (BR-KAL-06, `06` §6.6).
6. **Profil siswa** menampilkan NISN, NIS, nama, jenis kelamin, tanggal lahir, alamat, nama orang tua/wali, rombel, foto, nomor WA, atribut tambahan yang aktif, status siswa, riwayat masa aktif, dan, bagi pemegang `HA-AKN-06`, status akunnya. Siswa melihat profilnya sendiri di portal, tetapi tidak dapat mengubahnya.
7. **Daftar siswa** dapat dicari berdasarkan nama atau NISN, dan disaring berdasarkan rombel, status, "tanpa nomor WA", dan "tanpa foto". Siswa tanpa nomor WA dan siswa tanpa foto ditandai (DECISION, Session 4b, untuk nomor WA).
8. **Log data siswa.** Setiap perubahan nomor WA dicatat dengan nilai lama dan baru. Setiap penggantian foto juga dicatat (FR-MD-09). Perubahan masa aktif, NISN, dan data siswa lainnya, termasuk atribut tambahan, ikut dicatat di log ini (`06` §12.2).

**Keadaan kosong dan error**

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | NISN bukan 10 digit. | "NISN harus 10 digit angka." |
| E2 | NISN sudah dipakai siswa lain. | "NISN sudah terdaftar." Admin melihat nama siswa pemilik NISN itu. |
| E3 | Nomor WA tidak valid. | "Nomor WA tidak valid." Isian lain tidak ikut tersimpan sampai nomor diperbaiki atau dikosongkan. |
| E4 | Tanggal terakhir aktif di masa depan, atau sebelum tanggal mulai aktif periode itu. | Ditolak. |
| E5 | Wali kelas mengubah nomor WA siswa rombel lain. | Ditolak (§4.1). |
| E6 | Belum ada siswa. | Daftar kosong dengan tautan ke tambah siswa dan import siswa. |
| E7 | NIS sudah dipakai siswa lain. | "NIS sudah terdaftar." Admin melihat nama siswa pemilik NIS itu. |
| E8 | Atribut tambahan wajib kosong, atau nilainya tidak sesuai tipe. | Penyimpanan ditolak dengan menyebut atributnya (FS-MD-09). |

**Data dan log**

- Ditulis: siswa (NISN, NIS, nama, atribut lain, dan nomor WA), nilai atribut tambahan, masa aktif, penempatan awal, dan akun siswa (`06` §6.5 s.d. §6.9).
- Log data siswa: perubahan nomor WA (pelaku, waktu, nilai lama, dan nilai baru), penggantian foto, perubahan NISN, perubahan data lainnya, serta penonaktifan dan pengaktifan kembali.

**Parameter dan default**

| Parameter | Default | Diatur oleh | Dipastikan di |
|---|---|---|---|
| Daftar alasan penonaktifan | Lulus, pindah sekolah, keluar, meninggal dunia, salah input, lainnya | Tetap | — (DECISION, Session 5) |

**Di luar cakupan**

- Siswa mengubah data dirinya sendiri.
- Data orang tua selain nama dan nomor WA. Kebutuhan lain dapat ditambahkan admin sebagai atribut tambahan (FS-MD-09).
- Penghapusan siswa. Data yang keliru diubah, dan siswa yang keliru ditambahkan dinonaktifkan.

**Catatan antarmuka awal**

- Tanda "tanpa nomor WA" dan "tanpa foto" di daftar siswa.
- Nomor WA ditampilkan dengan format yang mudah dibaca.

**Acceptance criteria**

**AC-MD-04-01 — Tambah siswa**
Rujukan: FR-MD-03, FR-AKN-05, `HA-MD-03`, UF-07.

```text
Given admin menambah siswa dengan NISN 0012345678 di rombel 7A, aktif mulai hari ini
When penyimpanan berhasil
Then NISN tersimpan utuh sebagai "0012345678"
  And siswa memiliki akun belum aktif
  And kiosk mengenali siswa ini setelah memuat ulang data
```

**AC-MD-04-02 — NISN tidak valid atau ganda**
Rujukan: FR-MD-03, R-12.

```text
Given siswa dengan NISN 0012345678 sudah ada
When admin menambah siswa dengan NISN 012345678 (9 digit), atau dengan NISN 0012345678
Then penyimpanan ditolak dengan pesan "NISN harus 10 digit angka" atau "NISN sudah terdaftar"
```

**AC-MD-04-03 — Nomor WA opsional dan dibakukan**
Rujukan: FR-MD-03, §2.3.

```text
Given admin menambah siswa E tanpa nomor WA dan siswa F dengan nomor WA "0812-3456-7890"
When keduanya disimpan
Then siswa E tersimpan dan ditandai "tanpa nomor WA" di daftar siswa
  And nomor WA siswa F tersimpan sebagai "6281234567890"
```

**AC-MD-04-04 — Wali kelas mengubah nomor WA**
Rujukan: FR-MD-09, `HA-MD-06`, `HA-MD-10`.

```text
Given siswa G berada di rombel 7A
When wali kelas 7A mengubah nomor WA siswa G
Then nomor baru tersimpan
  And log data siswa mencatat wali kelas 7A, waktu, nomor lama, dan nomor baru
When wali kelas 7A mencoba mengubah nomor WA siswa rombel 7B
Then permintaan ditolak
```

**AC-MD-04-05 — Siswa dinonaktifkan**
Rujukan: FR-MD-03, BR-KAL-05, BR-KAL-06, UF-07.

```text
Given siswa H pindah sekolah dan terakhir masuk pada Senin, 12 Oktober 2026
When admin menonaktifkan siswa H dengan tanggal terakhir aktif 12 Oktober 2026
Then siswa H tidak memiliki status pada 13 Oktober 2026 dan tidak dihitung Alpa
  And akun siswa H nonaktif
  And riwayat kehadiran siswa H sampai 12 Oktober 2026 tetap dapat dibuka
```

**AC-MD-04-06 — Siswa melihat profil sendiri**
Rujukan: `HA-MD-05`, `HA-MD-06`.

```text
Given siswa sudah login di portal
When siswa membuka profilnya
Then siswa melihat NISN, nama, rombel, foto, dan nomor WA orang tua/wali
  And tidak ada isian yang dapat diubah
```

**AC-MD-04-07 — Nonaktif karena salah input**
Rujukan: FR-MD-03, BR-KAL-06, `06` §6.6.

```text
Given siswa L keliru ditambahkan dengan tanggal mulai aktif 1 Oktober 2026
When admin menonaktifkan siswa L dengan alasan "salah input"
Then siswa L tidak memiliki status pada tanggal mana pun sejak 1 Oktober 2026
  And rekap rombelnya menampilkan siswa L dengan 0 hari sekolah
  And log data siswa mencatat penonaktifan beserta alasannya
```

### FS-MD-05 — Penempatan siswa ke rombel

| Item | Isi |
|---|---|
| Tujuan | Admin menempatkan siswa ke rombel per tahun ajaran, termasuk pindah rombel dan kenaikan kelas, tanpa merusak riwayat. |
| Rilis | R1 |
| Requirement | FR-MD-04 |
| Alur | UF-07, UF-08 |
| Aktor dan hak | Admin (`HA-MD-03`). |
| Aturan terkait | BR-KAL-05 syarat 4, BR-KAL-06, BR-REK-05, R-14 |
| Status | DECISION (penempatan massal per rombel dan lewat file, Session 5; rombel per tanggal, BR-REK-05); RECOMMENDATION (rincian) |

**Prasyarat**

- Siswa dan rombel tujuan sudah ada (FS-MD-03, FS-MD-04).

**Input dan validasi**

| Isian | Wajib | Validasi |
|---|---|---|
| Siswa | Ya | Siswa aktif pada tanggal mulai penempatan. |
| Rombel tujuan | Ya | Rombel dari tahun ajaran yang rentangnya mencakup tanggal mulai. |
| Tanggal mulai | Ya | Default hari ini untuk pindah rombel, dan tanggal mulai tahun ajaran untuk kenaikan kelas. |

**Perilaku**

1. Pada setiap tanggal, seorang siswa berada di paling banyak satu rombel.
2. **Pindah rombel** dalam tahun ajaran yang sama: penempatan lama berakhir sehari sebelum tanggal mulai yang baru. Riwayat di rombel lama tetap utuh (BR-REK-05). Siswa berpindah ke cakupan wali kelas baru (§4.1).
3. **Kenaikan kelas** (UF-08):
   1. Admin memilih rombel asal di tahun ajaran lama dan rombel tujuan di tahun ajaran baru.
   2. Sistem menampilkan siswa aktif di rombel asal, semuanya terpilih.
   3. Admin melepas pilihan untuk siswa yang tidak naik atau yang lulus.
   4. Setelah konfirmasi, penempatan baru dimulai pada tanggal mulai tahun ajaran baru.
4. Siswa kelas 9 yang lulus dinonaktifkan lewat FS-MD-04.
5. Penempatan dengan tanggal lampau diperbolehkan. Status pada tanggal terdampak dihitung ulang, karena libur per tingkat atau rombel bergantung pada penempatan (BR-KAL-05).
6. **Import penempatan** (DECISION, Session 5). Untuk pengacakan ulang rombel, admin mengunduh daftar siswa aktif, mengisi kolom rombel tujuan, lalu mengunggahnya dengan tahun ajaran tujuan dan tanggal mulai. Sistem menampilkan pratinjau, lalu menyimpan semua baris valid dalam satu transaksi. Baris dengan rombel tujuan kosong dilewati. Rinciannya di `13` IM-02 dan IM-08 s.d. IM-11.

**Keadaan kosong dan error**

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | Penempatan baru tumpang tindih dengan penempatan lain. | Ditolak. |
| E2 | Siswa tidak aktif pada tanggal mulai. | Ditolak. |
| E3 | Tahun ajaran rombel tujuan tidak mencakup tanggal mulai. | Ditolak. |

**Data dan log**

- Ditulis: penempatan (siswa, rombel, tanggal mulai, dan tanggal selesai) (`06` §6.7).
- Log data siswa: setiap perubahan penempatan. Penempatan massal memakai penanda kelompok yang sama.
- Log aktivitas: setiap import penempatan (pelaku, waktu, nama file, dan jumlah baris).

**Parameter dan default**

Tidak ada.

**Di luar cakupan**

- Kenaikan kelas otomatis tanpa pilihan admin.
- Mutasi data antarsekolah.

**Catatan antarmuka awal**

- Riwayat penempatan tampil di profil siswa.

**Acceptance criteria**

**AC-MD-05-01 — Pindah rombel di tengah tahun**
Rujukan: FR-MD-04, `HA-MD-03`, UF-07, BR-REK-05.

```text
Given siswa I berada di 7A sejak awal tahun ajaran
When admin memindahkan siswa I ke 7B mulai 12 Oktober 2026
Then rekap 7A bulan Oktober mencatat siswa I untuk 1–11 Oktober
  And rekap 7B bulan Oktober mencatat siswa I mulai 12 Oktober
  And wali kelas 7B dapat membuka riwayat siswa I, sedangkan wali kelas 7A tidak lagi dapat membukanya
```

**AC-MD-05-02 — Kenaikan kelas**
Rujukan: FR-MD-04, UF-08, R-14.

```text
Given rombel 7A tahun 2026/2027 berisi 32 siswa aktif
When admin menempatkan 31 siswa ke rombel 8A tahun 2027/2028 dan melepas 1 siswa yang tidak naik
Then 31 siswa berada di 8A sejak tanggal mulai tahun ajaran 2027/2028
  And rekap 7A tahun 2026/2027 tetap sama seperti sebelumnya
```

**AC-MD-05-03 — Penempatan tumpang tindih**
Rujukan: FR-MD-04.

```text
Given siswa J berada di 7A sepanjang semester ganjil
When admin menambah penempatan siswa J di 7C dengan rentang yang tumpang tindih tanpa mengakhiri penempatan 7A
Then penyimpanan ditolak
```

**AC-MD-05-04 — Import penempatan**
Rujukan: FR-MD-04, UF-08, `13` IM-02.

```text
Given admin mengunduh daftar siswa aktif tahun ajaran 2026/2027 berisi 212 baris
  And mengisi Rombel Tujuan untuk 180 baris, membiarkan 30 baris kosong untuk siswa yang lulus, dan mengisi 2 baris dengan rombel yang tidak ada
When admin mengunggah file dengan tahun ajaran tujuan 2027/2028
Then pratinjau menampilkan 180 baris valid, 30 baris dilewati, dan 2 baris gagal beserta alasannya
When admin mengonfirmasi
Then 180 siswa ditempatkan mulai tanggal mulai tahun ajaran 2027/2028
  And log data siswa mencatat satu entri per siswa dengan penanda kelompok yang sama
```

### FS-MD-06 — Import siswa

| Item | Isi |
|---|---|
| Tujuan | Admin memasukkan banyak siswa sekaligus dari file Excel atau CSV, dengan validasi per baris. |
| Rilis | R1 |
| Requirement | FR-MD-05, FR-AKN-05 |
| Alur | UF-02 |
| Aktor dan hak | Admin (`HA-MD-04`). |
| Aturan terkait | R-12, C-06 |
| Status | DECISION (import .xlsx dan .csv; nomor WA opsional, Session 4b; kolom template dan NISN yang sudah ada dilewati, Session 5); RECOMMENDATION (validasi per baris dan alur pratinjau) |

**Prasyarat**

- Tahun ajaran dan rombel tujuan sudah ada (UF-01 catatan).

**Input dan validasi**

| Isian | Wajib | Validasi |
|---|---|---|
| File | Ya | .xlsx atau .csv. Ukuran dan jumlah baris maksimal ditetapkan di Session 9. Kolomnya mengikuti template (`13` §6). |
| Tahun ajaran tujuan | Ya | Default tahun ajaran aktif. |
| Tanggal mulai aktif dan penempatan | Ya | Default hari ini, atau tanggal mulai tahun ajaran bila tahun ajaran itu belum dimulai. |

Validasi setiap baris (UF-02 langkah 3):

| Kolom | Validasi |
|---|---|
| NISN | Dibaca sebagai teks dan harus tepat 10 digit. NISN 9 digit dari sel angka yang kehilangan nol di depan dinyatakan gagal, dengan petunjuk untuk memformat kolom sebagai teks. Tidak boleh ganda di dalam file. NISN yang sudah ada di database, aktif maupun nonaktif, membuat baris gagal dengan alasan "NISN sudah terdaftar atas nama <nama>" (DECISION, Session 5, UF-02 E2). |
| Nama lengkap | Wajib. |
| Rombel | Wajib, dan harus cocok dengan nama rombel di tahun ajaran tujuan. |
| Nomor WA orang tua/wali | Opsional. Bila diisi, divalidasi dan dibakukan seperti FS-MD-04. |
| Kolom opsional lain | NIS, jenis kelamin, tanggal lahir, alamat, nama orang tua/wali, dan atribut tambahan divalidasi sesuai `13` §6.1. Atribut tambahan yang wajib harus terisi. |

**Perilaku**

1. Admin mengunduh template file import, lalu mengisinya. Template memuat kolom atribut tambahan yang aktif (`13` IM-01).
2. Admin mengunggah file. Sistem memvalidasi setiap baris tanpa menyimpan data.
3. Sistem menampilkan pratinjau: jumlah baris valid, jumlah baris tanpa nomor WA, dan daftar baris gagal beserta alasannya. Daftar baris gagal dapat diunduh sebagai CSV.
4. Admin mengonfirmasi. Sistem menyimpan semua baris valid dalam satu transaksi: siswa, masa aktif, penempatan, dan akun siswa yang belum aktif (FS-AKN-05). Bila terjadi galat server, tidak ada baris yang tersimpan.
5. Sistem menampilkan ringkasan: jumlah siswa yang dibuat dan jumlah baris yang dilewati.
6. File import hanya disimpan sementara selama proses import (§4.9).

**Keadaan kosong dan error**

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | Semua baris gagal. | Tidak ada data yang disimpan. Admin memperbaiki file lalu mengunggah ulang (UF-02 E1). |
| E2 | Format file salah, atau kolom tidak sesuai template. | Ditolak sebelum validasi baris, dengan daftar kolom yang diharapkan. |
| E3 | File melebihi batas ukuran atau jumlah baris. | Ditolak dengan penjelasan batasnya. |
| E4 | Pratinjau ditinggalkan tanpa konfirmasi. | Tidak ada data yang disimpan. |

**Data dan log**

- Ditulis: siswa, nilai atribut tambahan, masa aktif, penempatan, dan akun siswa.
- Log: setiap import (pelaku, waktu, nama file, jumlah baris dibuat dan dilewati) dicatat di log aktivitas (Session 9). Setiap siswa yang dibuat dicatat di log data siswa dengan penanda kelompok yang sama (`06` §12.2).

**Parameter dan default**

| Parameter | Default | Diatur oleh | Dipastikan di |
|---|---|---|---|
| Kolom template | `13` §6.1 | Sistem | — (DECISION, Session 5) |
| Ukuran file dan jumlah baris maksimal | Belum ditetapkan | Sistem | Session 9 |

**Di luar cakupan**

- Import foto dari file Excel.
- Pembaruan data siswa yang sudah ada. Baris dengan NISN yang sudah ada selalu dilewati (DECISION, Session 5).
- Import akun staf.

**Catatan antarmuka awal**

- Pratinjau dengan tab baris valid dan baris gagal.

**Acceptance criteria**

**AC-MD-06-01 — Pratinjau dan simpan baris valid**
Rujukan: FR-MD-05, `HA-MD-04`, UF-02.

```text
Given file import berisi 30 baris
  And 2 baris memakai rombel yang tidak dikenal, dan baris ke-20 memiliki NISN yang sama dengan baris ke-5
When admin mengunggah file
Then pratinjau menampilkan 27 baris valid dan 3 baris gagal beserta alasannya, termasuk baris ke-20 sebagai NISN ganda di dalam file
  And belum ada data yang tersimpan
When admin mengonfirmasi
Then 27 siswa dan akun belum aktifnya tersimpan
  And ringkasan menampilkan 27 dibuat dan 3 dilewati
```

**AC-MD-06-02 — NISN kehilangan nol di depan**
Rujukan: FR-MD-05, R-12.

```text
Given satu baris berisi NISN 12345678 karena sel Excel diformat sebagai angka
When admin mengunggah file
Then baris itu dinyatakan gagal dengan alasan "NISN harus 10 digit" dan petunjuk untuk memformat kolom sebagai teks
```

**AC-MD-06-03 — Nomor WA kosong boleh**
Rujukan: FR-MD-05, §2.3.

```text
Given 5 baris tidak memiliki nomor WA dan 1 baris memiliki nomor WA "12345"
When admin mengunggah file
Then 5 baris tanpa nomor WA dinyatakan valid dan dihitung sebagai "tanpa nomor WA"
  And baris dengan nomor "12345" gagal dengan alasan nomor WA tidak valid
```

**AC-MD-06-04 — Semua baris gagal**
Rujukan: UF-02 E1.

```text
Given semua baris di file gagal validasi
When admin mengunggah file
Then pratinjau tidak menyediakan tombol konfirmasi
  And tidak ada data yang tersimpan
```

**AC-MD-06-05 — NISN sudah terdaftar**
Rujukan: FR-MD-05, UF-02 E2.

```text
Given siswa Budi dengan NISN 0012345678 sudah terdaftar dan berstatus nonaktif
When admin mengunggah file yang memuat baris dengan NISN 0012345678 dan nama berbeda
Then baris itu gagal dengan alasan "NISN sudah terdaftar atas nama Budi"
  And data siswa Budi tidak berubah
```

### FS-MD-07 — Foto siswa satu per satu

| Item | Isi |
|---|---|
| Tujuan | Admin dan wali kelas mengunggah atau mengganti foto siswa satu per satu. |
| Rilis | R1 |
| Requirement | FR-MD-06, FR-MD-09 |
| Alur | UF-03 |
| Aktor dan hak | Admin (semua) dan wali kelas (rombel) (`HA-MD-07`). |
| Aturan terkait | R-17, NFR-10 |
| Status | DECISION (pelaku, penggantian dicatat); RECOMMENDATION (rincian) |

**Prasyarat**

- Siswa sudah ada (FS-MD-04).

**Input dan validasi**

| Isian | Wajib | Validasi |
|---|---|---|
| File foto | Ya | File gambar. Format dan ukuran maksimal ditetapkan di Session 9 (§4.9). |

**Perilaku**

1. Admin, atau wali kelas untuk rombelnya, membuka profil siswa lalu mengunggah foto.
2. Sistem memperkecil foto ke ukuran standar, lalu menyimpannya di luar folder `public/` (§4.9).
3. Foto baru menggantikan foto lama. Penggantian dicatat di log data siswa: pelaku dan waktu (FR-MD-09).
4. Kiosk menampilkan foto baru setelah memuat ulang data (FS-KIO-01).

**Keadaan kosong dan error**

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | File bukan gambar, atau melebihi ukuran maksimal. | Ditolak. Foto lama tetap dipakai. |
| E2 | Siswa di luar cakupan. | Ditolak (§4.1). |
| E3 | Siswa belum memiliki foto. | Profil, kiosk, dan presensi manual menampilkan gambar pengganti. Siswa ditandai "tanpa foto" (FS-MD-04). |

**Data dan log**

- Ditulis: file foto dan waktu penggantian foto.
- Log data siswa: penggantian foto (pelaku dan waktu).

**Parameter dan default**

| Parameter | Default | Diatur oleh | Dipastikan di |
|---|---|---|---|
| Ukuran foto standar | Paling besar 600×800 px, JPEG (`07` ARS-53) | Sistem | Session 6 (DECISION); ukuran tampil di Session 7 |

**Di luar cakupan**

- Memotong atau menyunting foto di browser.
- Mengambil foto dari webcam di panel.

**Catatan antarmuka awal**

- Pratinjau foto sebelum disimpan.

**Acceptance criteria**

**AC-MD-07-01 — Wali kelas mengganti foto**
Rujukan: FR-MD-06, FR-MD-09, `HA-MD-07`, UF-03.

```text
Given siswa K berada di rombel 7A dan sudah memiliki foto
When wali kelas 7A mengunggah foto baru untuk siswa K
Then foto baru tersimpan dalam ukuran standar dan menggantikan foto lama
  And log data siswa mencatat wali kelas 7A dan waktu penggantian
  And kiosk menampilkan foto baru setelah memuat ulang data
```

**AC-MD-07-02 — File tidak valid**
Rujukan: FR-MD-06.

```text
Given admin membuka profil siswa
When admin mengunggah file PDF sebagai foto
Then unggahan ditolak dan foto lama tetap dipakai
```

### FS-MD-08 — Foto siswa massal

| Item | Isi |
|---|---|
| Tujuan | Admin mengunggah banyak foto sekaligus, dan sistem mencocokkan setiap foto dengan siswa lewat NISN. |
| Rilis | R1 |
| Requirement | FR-MD-07, FR-MD-09 |
| Alur | UF-03 |
| Aktor dan hak | Admin (`HA-MD-08`). |
| Aturan terkait | `13` IM-03 dan IM-12 s.d. IM-15 (format nama file, OQ-12) |
| Status | DECISION (format nama file, Session 5; ZIP dan beberapa file, Session 6); RECOMMENDATION (rincian) |

**Prasyarat**

- Siswa sudah ada (FS-MD-04 atau FS-MD-06).

**Input dan validasi**

| Isian | Wajib | Validasi |
|---|---|---|
| File foto | Ya | Banyak file gambar sekaligus, atau satu file ZIP (DECISION, Session 6, `07` ARS-54). Nama file diawali 10 digit NISN, lalu langsung diikuti ekstensi atau dipisah garis bawah, spasi, atau tanda hubung (`13` IM-03). Contohnya `0012345678.jpg` dan `0012345678_Budi Santoso.jpg`. |

**Perilaku**

1. Admin mengunggah file.
2. Sistem mencocokkan setiap nama file dengan NISN siswa, lalu menampilkan pratinjau:
   - file yang cocok, termasuk yang akan mengganti foto lama;
   - file yang tidak cocok, karena NISN tidak ditemukan atau nama file tidak sesuai format;
   - file ganda untuk NISN yang sama;
   - file yang bukan gambar valid.
3. Admin mengonfirmasi. Sistem memperkecil dan menyimpan foto yang cocok. File ganda dan file tidak cocok dilewati.
4. Setiap penggantian dicatat di log data siswa, satu entri per siswa.
5. Sistem menampilkan ringkasan hasil, yang dapat diunduh.

**Keadaan kosong dan error**

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | Tidak ada file yang cocok. | Tidak ada yang disimpan. |
| E2 | Ukuran unggahan melebihi batas. | Ditolak dengan saran membagi unggahan. |

**Data dan log**

- Ditulis: file foto per siswa.
- Log data siswa: penggantian foto per siswa.

**Parameter dan default**

| Parameter | Default | Diatur oleh | Dipastikan di |
|---|---|---|---|
| Format nama file | NISN di awal nama file (`13` IM-03) | Tetap | — (DECISION, Session 5) |
| Ukuran unggahan maksimal | Usulan 100 MB per unggahan; satu ZIP paling banyak 2.000 file dengan total isi 500 MB (`07` ARS-04, ARS-54) | Sistem | Session 9 |

**Di luar cakupan**

- Pencocokan foto berdasarkan nama siswa.

**Catatan antarmuka awal**

- Pratinjau dalam bentuk tabel: nama file, siswa, dan keterangan.

**Acceptance criteria**

**AC-MD-08-01 — Pencocokan nama file**
Rujukan: FR-MD-07, `HA-MD-08`, UF-03.

```text
Given admin mengunggah tiga foto: dua bernama NISN siswa yang terdaftar dan satu bernama NISN yang tidak terdaftar
When pratinjau tampil
Then dua foto ditandai cocok dan satu foto ditandai tidak cocok
When admin mengonfirmasi
Then dua foto tersimpan dalam ukuran standar
  And log data siswa mencatat dua penggantian foto
```

**AC-MD-08-02 — File ganda**
Rujukan: FR-MD-07.

```text
Given admin mengunggah "0012345678.jpg" dan "0012345678.png"
When pratinjau tampil
Then kedua file ditandai ganda dan tidak ada yang disimpan untuk NISN itu
```

**AC-MD-08-03 — Format nama file**
Rujukan: FR-MD-07, `13` IM-03, IM-12.

```text
Given siswa dengan NISN 0012345678 dan 0023456789 terdaftar
When admin mengunggah "0012345678_Budi Santoso.jpg", "Andi_0023456789.jpg", dan "00234567891.jpg"
Then file pertama ditandai cocok
  And dua file lainnya ditandai tidak sesuai format
```

### FS-MD-09 — Atribut tambahan siswa

| Item | Isi |
|---|---|
| Tujuan | Admin menambah atribut siswa sendiri, misalnya agama atau asal sekolah, tanpa perubahan kode. |
| Rilis | R1 |
| Requirement | FR-MD-10 |
| Alur | UF-07 |
| Aktor dan hak | Kelola definisi atribut: admin (`HA-MD-11`). Isi nilai: admin, bersama data siswa (`HA-MD-03`, `HA-MD-04`). Lihat nilai: sama dengan profil siswa (`HA-MD-05`). |
| Aturan terkait | `06` §6.8 dan §6.9, `13` IM-01 |
| Status | DECISION (atribut tambahan; tidak dipakai logika presensi, rekap, atau filter laporan, Session 5); RECOMMENDATION (rincian) |

**Prasyarat**

- Admin sudah login.

**Input dan validasi**

| Isian | Wajib | Validasi |
|---|---|---|
| Label | Ya | Teks, paling banyak 60 karakter. |
| Kode | Ya | Huruf kecil, angka, dan garis bawah, diawali huruf. Default dibentuk dari label. Unik, dan tidak sama dengan judul kolom bawaan template import. Tidak dapat diubah setelah disimpan. |
| Tipe | Ya | Teks, angka, tanggal, atau pilihan. Tidak dapat diubah setelah ada siswa yang memiliki nilai. |
| Pilihan | Ya, untuk tipe pilihan | Paling sedikit dua pilihan, tanpa pilihan ganda. |
| Wajib | Ya | Ya atau tidak. Default tidak. |
| Urutan | Tidak | Bilangan bulat. |

**Perilaku**

1. Admin membuat, mengubah, mengurutkan, menyembunyikan, dan menampilkan kembali atribut tambahan.
2. Atribut yang aktif tampil di formulir siswa (FS-MD-04), di profil siswa, dan sebagai kolom template import dengan judul berupa kodenya (`13` IM-01).
3. Nilai divalidasi sesuai tipe. Atribut wajib harus diisi saat siswa ditambah, saat data siswa diubah, dan saat import. Siswa yang sudah ada sebelum atribut wajib dibuat tidak berubah, dan baru wajib diisi saat datanya diubah.
4. Menyembunyikan atribut menghilangkannya dari formulir, profil, dan template, tetapi nilainya tetap tersimpan.
5. Atribut hanya dapat dihapus bila belum ada siswa yang memiliki nilai. Pilihan yang sudah dipakai siswa tidak dapat dihapus.
6. Atribut tambahan tidak dipakai logika presensi, rekap, atau filter laporan (DECISION, Session 5). Atribut ini juga tidak dimuat kiosk (RECOMMENDATION). Nilainya ikut di export data siswa (`13` LP-06).
7. Perubahan nilai dicatat di log data siswa (`06` §12.2). Perubahan definisi atribut dicatat di log aktivitas pengaturan (Session 9).

**Keadaan kosong dan error**

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | Kode sudah dipakai, atau sama dengan judul kolom bawaan. | Ditolak. |
| E2 | Mengubah tipe atribut yang sudah memiliki nilai. | Ditolak. |
| E3 | Menghapus atribut yang sudah memiliki nilai, atau pilihan yang sudah dipakai. | Ditolak. Admin dapat menyembunyikan atribut itu. |
| E4 | Belum ada atribut tambahan. | Daftar kosong dengan tombol tambah atribut. |

**Data dan log**

- Ditulis: definisi atribut (`atribut_siswa`) dan nilai per siswa (`nilai_atribut_siswa`) (`06` §6.8, §6.9).
- Log: nilai di log data siswa; definisi di log aktivitas pengaturan (Session 9).

**Parameter dan default**

Tidak ada.

**Di luar cakupan**

- Atribut tambahan untuk staf atau rombel.
- Atribut berupa file.
- Rekap, filter laporan, atau pencarian berdasarkan atribut tambahan.

**Catatan antarmuka awal**

- Atribut tambahan tampil di bawah atribut bawaan, sesuai urutan.

**Acceptance criteria**

**AC-MD-09-01 — Atribut wajib berjenis pilihan**
Rujukan: FR-MD-10, `HA-MD-11`.

```text
Given admin membuat atribut "Agama" dengan kode "agama", tipe pilihan, dan wajib
When admin menambah siswa tanpa mengisi Agama
Then penyimpanan ditolak dengan menyebut atribut Agama
When admin mengisi Agama dengan salah satu pilihan
Then siswa tersimpan dan nilai Agama tampil di profilnya
  And template import yang diunduh memuat kolom "agama"
```

**AC-MD-09-02 — Menyembunyikan atribut**
Rujukan: FR-MD-10.

```text
Given atribut "asal_sekolah" sudah memiliki nilai pada 100 siswa
When admin mencoba menghapusnya
Then penghapusan ditolak
When admin menyembunyikannya
Then atribut itu tidak tampil di formulir, profil, dan template import
  And nilai pada 100 siswa tetap tersimpan
```

**AC-MD-09-03 — Validasi saat import**
Rujukan: FR-MD-05, FR-MD-10, `13` IM-01.

```text
Given atribut "tanggal_masuk" bertipe tanggal
When admin mengimpor baris dengan tanggal_masuk "31-02-2026"
Then baris itu gagal dengan alasan tanggal_masuk tidak valid
```

## 7. Kiosk dan stasiun scan (KIO)

Kiosk adalah satu-satunya bagian yang berjalan sebagai aplikasi client di browser (`00` §5). Rincian teknis seperti penyimpanan browser, Service Worker, data kiosk, dan jam kiosk ditetapkan di `07` §6. Bentuk API sinkron ditetapkan di Session 8 (`10-api-specification.md`).

### FS-KIO-01 — Muat data kiosk

| Item | Isi |
|---|---|
| Tujuan | Kiosk menyimpan data yang dibutuhkan untuk memvalidasi scan di laptop, sehingga scan tetap berjalan saat offline. |
| Rilis | R1 |
| Requirement | FR-KIO-01, FR-KIO-06, FR-KIO-09, NFR-03 |
| Alur | UF-06, UF-09 |
| Aktor dan hak | Akun stasiun (`HA-KIO-01`). Petugas menekan tombol di kiosk tanpa login sendiri (`02` §9). |
| Aturan terkait | BR-JAM-01, BR-JAM-06, BR-KAL-02, BR-KAL-05, BR-SCN-07, R-07, R-08 |
| Status | DECISION (local-first; data dan foto dimuat lebih dulu; nilai parameter, Session 6); RECOMMENDATION (rincian) |

**Prasyarat**

- Akun stasiun sudah login di laptop (FS-AKN-04).
- Laptop online paling tidak sekali (UF-09 E2).

**Input dan validasi**

Tidak ada isian. Pemicunya adalah membuka kiosk, menekan tombol "Muat ulang data", dan perubahan data di server.

**Perilaku**

1. **Kapan dimuat.** Kiosk memuat data:
   - saat dibuka dan laptop online;
   - saat petugas menekan "Muat ulang data";
   - otomatis di latar belakang, saat respons sinkron menunjukkan data di server sudah berubah (FS-KIO-03). Contohnya siswa baru, foto baru, atau jadwal hari ini yang diubah.
2. **Isi data.**
   - Siswa aktif: NISN, nama, rombel dan tingkat saat ini, serta foto dalam ukuran kiosk.
   - Pola mingguan, jadwal khusus, jadwal hari ini, dan libur untuk hari ini dan N hari ke depan, sehingga kiosk tetap tahu aturan jam bila offline beberapa hari.
   - Identitas sekolah dan nama stasiun.
   - Jam server, untuk mengukur selisih jam laptop (BR-SCN-07).
3. **Data minimal.** Kiosk tidak memuat nomor WA, data izin/sakit/dispensasi, riwayat, atau data siswa nonaktif (R-07).
4. **Penggantian utuh.** Data baru menggantikan data lama hanya setelah selesai dimuat seluruhnya. Bila pemuatan gagal, kiosk tetap memakai data lama dan menampilkan pesan. Foto hanya diunduh bila berubah.
5. **Status siap.** Kiosk menampilkan koneksi, waktu data terakhir dimuat, jumlah siswa, jumlah scan belum tersinkron, jam WIB, dan nama stasiun (UF-09 langkah 3).
6. **Data lama.** Bila data lebih tua dari batas umur data, kiosk menampilkan peringatan untuk memuat ulang saat online, tetapi tetap dapat dipakai (UF-09 E1).
7. **Di luar rentang aturan.** Bila tanggal hari ini berada di luar rentang jadwal khusus dan libur yang dimuat, kiosk memakai pola mingguan dan menampilkan peringatan. Server tetap menilai ulang scan dengan aturan lengkap (FS-KIO-04).
8. **Tindakan berisiko di kiosk** dilindungi PIN atau konfirmasi petugas (`02` §9). Detailnya ditetapkan di Session 9.
   - Logout akun stasiun tidak menghapus scan yang belum tersinkron. Scan itu dikirim setelah akun stasiun yang sama login kembali.
   - Hapus data lokal ditolak selama masih ada scan belum tersinkron. Pengecualiannya penghapusan karena akun stasiun dinonaktifkan (FS-AKN-04).
9. **Penyiapan pertama** (UF-06): kiosk meminta izin kamera dan penyimpanan permanen di browser (NFR-03). Kiosk dipasang sebagai aplikasi di browser, agar penyimpanan permanen diberikan (`07` ARS-21). Bila penyimpanan permanen ditolak, kiosk menampilkan peringatan risiko kehilangan data.

**Keadaan kosong dan error**

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | Kiosk belum pernah memuat data dan laptop offline. | Scan dinonaktifkan dengan pesan "Data belum dimuat. Hubungkan ke internet." (UF-09 E2) |
| E2 | Login akun stasiun berakhir. | Kiosk meminta login ulang. Data dan scan di laptop tetap tersimpan (UF-09 E3). |
| E3 | Akun stasiun nonaktif. | Data di laptop dihapus, dan scan berhenti (FS-AKN-04, UF-09 E5). |
| E4 | Pemuatan gagal di tengah jalan. | Data lama tetap dipakai, dengan pesan gagal memuat. |
| E5 | Penyimpanan browser penuh. | Peringatan untuk petugas. Scan baru tidak boleh ditampilkan berhasil bila gagal disimpan (FS-KIO-02 E8). |
| E6 | Pola mingguan belum lengkap. | Kiosk menolak scan dengan pesan "Aturan jam belum diatur. Hubungi admin." |

**Data dan log**

- Ditulis di laptop: data siswa, foto, aturan jam, kalender, selisih jam, dan versi data.
- Ditulis di server: waktu data terakhir dimuat oleh stasiun, untuk status stasiun (FS-KIO-05).

**Parameter dan default**

| Parameter | Default | Diatur oleh | Dipastikan di |
|---|---|---|---|
| N hari aturan jam dan libur yang dimuat ke depan | 14 hari | Sistem | Session 6 (DECISION) |
| Batas umur data sebelum peringatan | 3 hari atau 72 jam (UF-09 E1) | Sistem | Session 6 (DECISION) |
| Ukuran foto kiosk | 300×400 px, JPEG (`07` ARS-53) | Sistem | Session 6 (DECISION); ukuran tampil di Session 7 |

**Di luar cakupan**

- Kiosk memuat data izin, riwayat, atau nomor WA.
- Kiosk memuat hanya rombel tertentu. Semua stasiun memuat semua siswa aktif.

**Catatan antarmuka awal**

- Bilah status selalu terlihat: koneksi, data terakhir dimuat, belum tersinkron, dan jam.
- Tombol "Muat ulang data" dan "Sinkron sekarang" mudah dijangkau petugas, tetapi tidak menghalangi layar scan.

**Acceptance criteria**

**AC-KIO-01-01 — Memuat data saat online**
Rujukan: FR-KIO-01, `HA-KIO-01`, UF-09.

```text
Given kiosk dibuka dan laptop online
When kiosk selesai memuat data
Then kiosk menampilkan jumlah siswa aktif, waktu data dimuat, dan jam WIB
  And data di laptop tidak memuat nomor WA atau data izin/sakit/dispensasi
```

**AC-KIO-01-02 — Dibuka ulang tanpa internet**
Rujukan: FR-KIO-09, UF-09 E1, R-08.

```text
Given kiosk memuat data kemarin, lalu laptop dimatikan
When laptop dinyalakan dan kiosk dibuka tanpa internet
Then kiosk terbuka dan dapat dipakai untuk scan
  And kiosk menampilkan waktu data terakhir dimuat
```

**AC-KIO-01-03 — Belum pernah memuat data**
Rujukan: UF-09 E2.

```text
Given laptop baru belum pernah memuat data kiosk dan sedang offline
When petugas membuka kiosk
Then scan dinonaktifkan dengan pesan "Data belum dimuat. Hubungkan ke internet."
```

**AC-KIO-01-04 — Data baru dimuat otomatis**
Rujukan: FR-KIO-01, UF-07, UF-28.

```text
Given kiosk online dan admin baru menambah siswa L
When sinkron berikutnya menunjukkan data server sudah berubah
Then kiosk memuat ulang data di latar belakang tanpa menghentikan scan
  And scan kartu siswa L setelah itu dikenali
```

**AC-KIO-01-05 — Pemuatan gagal**
Rujukan: FR-KIO-01, NFR-03.

```text
Given kiosk sedang memuat ulang data dan koneksi terputus di tengah jalan
When pemuatan gagal
Then kiosk tetap memakai data sebelumnya dan menampilkan pesan gagal memuat
  And scan tetap dapat dilakukan
```

**AC-KIO-01-06 — Hapus data lokal ditahan**
Rujukan: `02` §9, NFR-03.

```text
Given kiosk memiliki 3 scan belum tersinkron
When petugas memilih hapus data lokal
Then tindakan ditolak dengan pesan bahwa masih ada 3 scan belum tersinkron
```

### FS-KIO-02 — Scan dan umpan balik

| Item | Isi |
|---|---|
| Tujuan | Siswa memindai kartu, lalu kiosk langsung memvalidasi, menampilkan hasil, dan menyimpan scan di laptop. |
| Rilis | R1 |
| Requirement | FR-KIO-02 s.d. FR-KIO-06, FR-KIO-09, FR-PRS-08, NFR-01 |
| Alur | UF-10, UF-14 |
| Aktor dan hak | Siswa memindai, petugas mengawasi, dan kiosk berjalan dengan akun stasiun (`HA-KIO-01`). |
| Aturan terkait | BR-JAM-03 s.d. BR-JAM-07, BR-KAL-05, BR-SCN-01 s.d. BR-SCN-03, BR-SCN-07, BR-SCN-09, BR-SCN-10 |
| Status | DECISION (validasi lokal, foto, jendela scan, scan ganda, penolakan; scan pulang tanpa masuk tampil biasa, Session 4b); RECOMMENDATION (rincian, bunyi, dan pemeriksaan isi QR) |

**Prasyarat**

- Data kiosk sudah dimuat (FS-KIO-01).

**Input dan validasi**

| Isian | Wajib | Validasi |
|---|---|---|
| Isi QR dari webcam atau scanner USB | Ya | Spasi dan karakter akhir baris dibuang. Isinya harus tepat 10 digit angka. Selain itu, QR dianggap tidak dikenali. |

**Perilaku**

1. Kiosk mengabaikan pembacaan ulang NISN yang sama selama jeda singkat setelah hasil tampil. Dengan begitu, kartu yang masih berada di depan kamera tidak menghasilkan pesan "sudah tercatat".
2. Kiosk menghitung jam scan: jam laptop ditambah selisih terhadap jam server, dalam WIB (BR-SCN-07). Cara menjaga jam tetap benar, termasuk saat kiosk dibuka offline, ada di `07` ARS-27 dan ARS-28.
3. Kiosk memeriksa scan berurutan (BR-SCN-01). Scan yang gagal di satu pemeriksaan langsung ditolak dan tidak dicatat:
   1. Hari ini adalah hari sekolah, dan jam scan berada di jendela masuk atau jendela pulang (BR-JAM-03, BR-JAM-06). Jendela masuk menghasilkan jenis masuk, dan jendela pulang menghasilkan jenis pulang.
   2. NISN ada di data siswa aktif di laptop (BR-SCN-02).
   3. Hari ini adalah hari sekolah bagi siswa itu, yaitu tidak ada libur untuk tingkat atau rombelnya (BR-KAL-05).
   4. Laptop ini belum memiliki scan siswa itu untuk jenis yang sama hari ini (BR-SCN-03).
4. Kiosk menentukan status sementara:
   - scan masuk sampai menit batas terlambat adalah Hadir, dan setelahnya Terlambat (BR-JAM-04);
   - scan pulang sebelum jam pulang adalah "pulang lebih awal", dan selainnya "pulang" (BR-JAM-05).
5. Dalam paling lama 1 detik setelah QR terbaca, layar menampilkan foto, nama, rombel, jenis, status, dan jam, disertai bunyi (NFR-01). Siswa tidak perlu menekan apa pun.
6. Kiosk menyimpan scan di laptop dengan ID unik, NISN, jenis, status menurut kiosk, jam scan, jam laptop asli, selisih jam, dan nama stasiun (FR-KIO-06). Penghitung belum tersinkron bertambah satu.
7. Hasil tampil berhasil hanya setelah scan benar-benar tersimpan di laptop.
8. Bila kartu berikutnya terbaca saat hasil sebelumnya masih tampil, hasil baru langsung menggantikannya, supaya antrean tidak tertahan.
9. Scan pulang dari siswa yang tidak punya scan masuk di laptop ini ditampilkan seperti scan pulang biasa. Server yang menentukan dan menandainya (BR-SCN-10, DECISION Session 4b).
10. Kiosk tidak tahu data izin/sakit/dispensasi. Siswa yang berizin tetap dapat scan, dan statusnya ditentukan server (BR-STS-03, BR-STS-07).
11. Semua langkah di atas berjalan sama saat offline (FR-KIO-09).

**Keadaan kosong dan error**

Pesan di bawah mengikuti contoh di `05` §4.2. Teks finalnya ditetapkan di Session 7.

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | Isi QR bukan 10 digit angka. | "QR tidak dikenali." Bunyi galat. Scan tidak dicatat. |
| E2 | NISN tidak ada di data laptop (BR-SCN-02). | "Kartu tidak terdaftar. Temui guru piket." Bunyi galat. Scan tidak dicatat. |
| E3 | Sebelum jam buka scan masuk. | "Scan masuk dibuka pukul 06.00." Scan tidak dicatat. |
| E4 | Setelah sesi masuk ditutup dan sebelum jendela pulang. | "Sesi masuk sudah ditutup. Temui guru piket." Scan tidak dicatat (BR-JAM-08). |
| E5 | Setelah sesi pulang ditutup. | "Sesi pulang sudah ditutup." Scan tidak dicatat. |
| E6 | Hari ini bukan hari sekolah, atau ada libur untuk semua siswa. | "Hari ini bukan hari sekolah." Bila libur, pesan menyebut keterangannya. |
| E7 | Siswa sedang libur karena libur tingkat atau rombel. | Pesan dengan keterangan libur, misalnya "Kelas 9 libur hari ini." Scan tidak dicatat. |
| E8 | Scan gagal disimpan di laptop. | "Scan tidak tersimpan. Panggil petugas." Bunyi galat. Hasil tidak ditampilkan sebagai berhasil. |
| E9 | Siswa sudah tercatat untuk jenis yang sama di laptop ini. | "Sudah tercatat masuk pukul 06.52." Bunyi berbeda. Scan tidak dicatat ulang (BR-SCN-03). |
| E10 | Kamera tidak tersedia atau izin kamera ditolak. | Pesan untuk petugas. Scanner USB tetap dapat dipakai. |

**Data dan log**

- Ditulis di laptop: catatan scan (ID unik, NISN, jenis, status menurut kiosk, jam scan, jam laptop asli, selisih jam, dan stasiun).
- Scan yang ditolak kiosk tidak dicatat dan tidak dikirim ke server.

**Parameter dan default**

| Parameter | Default | Diatur oleh | Dipastikan di |
|---|---|---|---|
| Jeda pengabaian NISN yang sama | 5 detik | Sistem | Session 6 (DECISION) |
| Lama hasil scan tampil | Belum ditetapkan (UF-10 langkah 7) | Sistem | Session 7 |
| Jenis bunyi: berhasil, peringatan, dan galat | Belum ditetapkan | Sistem | Session 7 |

**Di luar cakupan**

- Snapshot webcam saat scan (`00` §6.2).
- Pemblokiran kartu atau tanda khusus untuk kartu tertentu (BR-SCN-09).
- Peringatan izin/sakit di kiosk.
- Kolom ketik NISN di kiosk untuk petugas. Siswa tanpa kartu yang terbaca dicatat lewat presensi manual (FS-PRS-06). Input scanner USB dibedakan dari ketikan manual menurut jeda antartombolnya (`07` ARS-26).

**Catatan antarmuka awal**

- Hasil scan tampil besar dan dapat dibaca dari jarak 1–2 meter, dengan warna berbeda untuk berhasil, peringatan, dan galat.
- Foto siswa tampil besar, karena petugas mencocokkannya dengan wajah siswa (R-02).
- Pratinjau kamera selalu terlihat.

**Acceptance criteria**

**AC-KIO-02-01 — Scan masuk tepat waktu**
Rujukan: FR-KIO-04, FR-KIO-06, NFR-01, UF-10, BR-JAM-04, AC-01.

```text
Given kiosk siap dan hari ini hari sekolah bagi siswa A di rombel 7A
When siswa A memindai kartunya pukul 06.52
Then dalam paling lama 1 detik layar menampilkan foto, nama, rombel 7A, "Masuk", "Hadir", dan pukul 06.52, disertai bunyi berhasil
  And scan tersimpan di laptop dengan ID unik
  And penghitung belum tersinkron bertambah satu
```

**AC-KIO-02-02 — Batas terlambat**
Rujukan: FR-KIO-04, BR-JAM-04.

```text
Given batas terlambat hari ini 07.00
When siswa A memindai kartu pukul 07.00.59 dan siswa B memindai kartu pukul 07.01.00
Then status siswa A adalah Hadir
  And status siswa B adalah Terlambat
```

**AC-KIO-02-03 — Gerbang ditutup**
Rujukan: FR-KIO-05, FR-PRS-08, UF-10 E6, BR-JAM-06, BR-JAM-08.

```text
Given sesi masuk ditutup pukul 08.00 dan jendela pulang dibuka pukul 12.00
When siswa C memindai kartu pukul 08.05, termasuk saat laptop offline
Then kiosk menampilkan "Sesi masuk sudah ditutup. Temui guru piket."
  And scan tidak dicatat dan penghitung belum tersinkron tidak berubah
```

**AC-KIO-02-04 — Kartu tidak terdaftar**
Rujukan: FR-KIO-05, UF-10 E1, BR-SCN-02.

```text
Given NISN 0099999999 tidak ada di data laptop
When kartu dengan NISN itu dipindai di jendela masuk
Then kiosk menampilkan "Kartu tidak terdaftar. Temui guru piket." dengan bunyi galat
  And scan tidak dicatat
```

**AC-KIO-02-05 — Scan ganda**
Rujukan: FR-KIO-05, UF-10 E2, BR-SCN-03.

```text
Given siswa A tercatat masuk pukul 06.52 di laptop ini
When siswa A memindai kartunya lagi pukul 07.10, setelah jeda pengabaian lewat
Then kiosk menampilkan "Sudah tercatat masuk pukul 06.52." dengan bunyi berbeda
  And tidak ada scan baru yang dicatat
```

**AC-KIO-02-06 — Kartu tertahan di depan kamera**
Rujukan: FR-KIO-04, NFR-01.

```text
Given siswa A baru saja memindai kartu dan hasil "Hadir" tampil
When kartu siswa A masih berada di depan kamera selama 3 detik
Then kiosk hanya menampilkan satu hasil dan hanya mencatat satu scan
```

**AC-KIO-02-07 — Scan pulang**
Rujukan: FR-KIO-04, UF-14, BR-JAM-05.

```text
Given jendela pulang dibuka pukul 12.00 dan jam pulang 13.00
When siswa A memindai kartu pukul 12.40 dan siswa B pukul 13.00.00
Then kiosk menampilkan "Pulang lebih awal" untuk siswa A dan "Pulang" untuk siswa B
```

**AC-KIO-02-08 — Scan pulang tanpa scan masuk**
Rujukan: UF-14 E3, BR-SCN-10.

```text
Given laptop ini tidak memiliki scan masuk siswa D hari ini
When siswa D memindai kartu pukul 13.10
Then kiosk menampilkan hasil "Pulang" seperti biasa dan mencatat scan pulang
```

**AC-KIO-02-09 — Siswa libur dan bukan hari sekolah**
Rujukan: FR-KIO-05, UF-09 E4, UF-10 E8, BR-JAM-06, BR-KAL-02.

```text
Given hari ini kelas 9 libur, sedangkan kelas 7 dan 8 masuk
When siswa kelas 9 memindai kartu pukul 06.50
Then kiosk menampilkan keterangan libur kelas 9 dan scan tidak dicatat
Given hari ini hari Minggu
When siswa mana pun memindai kartu
Then kiosk menampilkan "Hari ini bukan hari sekolah." dan scan tidak dicatat
```

**AC-KIO-02-10 — Scan saat offline**
Rujukan: FR-KIO-09, AC-01, NFR-01.

```text
Given kiosk sudah memuat data saat online, lalu internet terputus
When siswa memindai kartu yang terdaftar di jendela masuk
Then kiosk menampilkan hasil dalam paling lama 1 detik
  And scan tersimpan di laptop sebagai belum tersinkron
```

**AC-KIO-02-11 — Penyimpanan gagal**
Rujukan: NFR-03.

```text
Given penyimpanan browser tidak dapat menulis data
When siswa memindai kartu yang terdaftar
Then kiosk menampilkan "Scan tidak tersimpan. Panggil petugas." dengan bunyi galat
  And kiosk tidak menampilkan hasil sebagai berhasil
```

**AC-KIO-02-12 — QR bukan NISN dan scanner USB**
Rujukan: FR-KIO-02, FR-KIO-03.

```text
Given kiosk siap
When QR berisi alamat situs web dipindai
Then kiosk menampilkan "QR tidak dikenali." dan scan tidak dicatat
When kartu siswa dipindai dengan scanner USB
Then hasilnya sama dengan scan lewat webcam
```

### FS-KIO-03 — Sinkron dari kiosk

| Item | Isi |
|---|---|
| Tujuan | Kiosk mengirim scan yang belum tersinkron ke server secara otomatis, serta melaporkan keadaannya untuk status stasiun. |
| Rilis | R1 |
| Requirement | FR-KIO-07, FR-KIO-08, FR-KIO-09, NFR-03, NFR-04 |
| Alur | UF-11 |
| Aktor dan hak | Akun stasiun (`HA-KIO-01`). Petugas menekan sinkron manual. |
| Aturan terkait | BR-SCN-05, BR-SCN-07 |
| Status | DECISION (sinkron otomatis dan manual); RECOMMENDATION (rincian) |

**Prasyarat**

- Akun stasiun login (FS-AKN-04).

**Input dan validasi**

Tidak ada isian. Pemicunya adalah interval otomatis dan tombol "Sinkron sekarang".

**Perilaku**

1. Selama online, kiosk mengirim scan yang belum tersinkron ke server setiap 5 detik, paling banyak 100 scan per kiriman (`07` §2.3).
2. Kiosk juga menghubungi server setiap 60 detik walaupun tidak ada scan baru. Setiap kontak melaporkan jumlah scan belum tersinkron, jam laptop saat itu, dan versi data yang dimuat. Server mengukur selisih jam dari jam laptop itu (FS-KIO-05, `07` ARS-27).
3. Server membalas daftar ID scan yang diterima, termasuk ID yang sudah diterima sebelumnya, jam server, versi data, dan status akun (FS-KIO-04). Kiosk menandai scan tersebut tersinkron, dan penghitung berkurang.
4. Bila versi data di server berubah, kiosk memuat ulang data (FS-KIO-01).
5. Petugas dapat menekan "Sinkron sekarang". Kiosk menampilkan hasilnya: jumlah scan terkirim, atau alasan gagal.
6. Bila pengiriman gagal, kiosk mencoba lagi dengan jeda yang makin panjang. Scan yang belum dibalas server tetap berstatus belum tersinkron, dan kiriman ulang aman karena server bersifat idempotent (BR-SCN-05).
7. Scan yang sudah tersinkron disimpan di laptop sampai akhir hari untuk pemeriksaan scan ganda, lalu dihapus. Scan yang belum tersinkron tidak pernah dihapus otomatis (R-07, NFR-03).
8. Kiosk menampilkan koneksi, jumlah scan belum tersinkron, dan waktu sinkron terakhir yang berhasil (FR-KIO-08).
9. Bila petugas menutup tab kiosk saat masih ada scan belum tersinkron, browser menampilkan peringatan.
10. Laptop atau browser yang dibuka ulang tanpa internet tetap menyimpan scan yang belum tersinkron (UF-11 E3).

**Keadaan kosong dan error**

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | Offline. | Indikator koneksi berubah. Scan tetap berjalan, dan sinkron berjalan sendiri saat koneksi kembali (UF-11 E1). |
| E2 | Login akun stasiun berakhir. | Sinkron berhenti, dan kiosk meminta login ulang. Scan belum tersinkron tetap tersimpan. |
| E3 | Akun stasiun nonaktif. | Data di laptop dihapus (FS-AKN-04). |
| E4 | Server menolak sebagian scan karena datanya rusak. | Scan itu tetap di laptop dengan tanda galat dan dilaporkan di status stasiun. Scan lain tetap tersinkron. |
| E5 | Akhir hari, masih ada scan belum tersinkron dan tidak ada internet. | Laptop boleh dimatikan, dan petugas melapor ke guru piket. Sinkron berjalan saat online kembali (UF-11 E5). |

**Data dan log**

- Dibaca dan ditulis di laptop: status sinkron setiap scan.
- Ditulis di server: lihat FS-KIO-04 dan FS-KIO-05.

**Parameter dan default**

| Parameter | Default | Diatur oleh | Dipastikan di |
|---|---|---|---|
| Interval sinkron otomatis | 5 detik selama ada scan belum tersinkron (FR-KIO-07) | Sistem | Session 6 (DECISION) |
| Interval kontak berkala tanpa scan | 60 detik | Sistem | Session 6 (DECISION) |
| Jumlah scan per kiriman | Paling banyak 100 | Sistem | Session 6 (DECISION) |

**Di luar cakupan**

- Sinkron langsung antarkiosk.
- Kiosk menerima scan dari stasiun lain. Pemeriksaan scan ganda di kiosk hanya memakai scan di laptop itu; server menggabungkan semua stasiun (BR-SCN-03).

**Catatan antarmuka awal**

- Penghitung belum tersinkron berwarna mencolok bila lebih dari nol dan offline.

**Acceptance criteria**

**AC-KIO-03-01 — Sinkron setelah koneksi kembali**
Rujukan: FR-KIO-07, AC-02, UF-11.

```text
Given kiosk memiliki 40 scan belum tersinkron karena internet terputus
When koneksi kembali
Then sinkron otomatis berjalan tanpa tindakan petugas
  And semua 40 scan tersimpan di server
  And penghitung belum tersinkron kembali ke nol
```

**AC-KIO-03-02 — Respons tidak sampai**
Rujukan: FR-KIO-10, NFR-04, AC-02.

```text
Given server sudah menyimpan 10 scan, tetapi respons tidak sampai ke kiosk
When kiosk mengirim ulang 10 scan yang sama
Then server membalas kesepuluh ID sebagai diterima
  And server tidak membuat catatan scan ganda
  And kiosk menandai kesepuluh scan tersinkron
```

**AC-KIO-03-03 — Sinkron manual**
Rujukan: FR-KIO-07, UF-11 E2.

```text
Given kiosk online dan memiliki 2 scan belum tersinkron
When petugas menekan "Sinkron sekarang"
Then kiosk langsung mengirim kedua scan dan menampilkan "2 scan terkirim"
```

**AC-KIO-03-04 — Laporan tanpa scan baru**
Rujukan: FR-KIO-12.

```text
Given kiosk online dan tidak ada scan baru selama 30 menit
When guru piket membuka status stasiun
Then waktu kontak terakhir stasiun itu tidak lebih tua dari interval kontak berkala
  And jumlah belum tersinkron yang dilaporkan adalah nol
```

**AC-KIO-03-05 — Dibuka ulang tanpa internet**
Rujukan: FR-KIO-09, NFR-03, UF-11 E3.

```text
Given kiosk memiliki 12 scan belum tersinkron dan browser ditutup
When browser dibuka kembali tanpa internet
Then kiosk terbuka dari cache dan penghitung tetap menunjukkan 12
```

### FS-KIO-04 — Penerimaan sinkron di server

| Item | Isi |
|---|---|
| Tujuan | Server menerima scan dari kiosk tepat satu kali, menilai ulang setiap scan dengan aturan di server, menggabungkan scan dari semua stasiun, dan memperbarui status. |
| Rilis | R1 |
| Requirement | FR-KIO-10, FR-KIO-11, NFR-04, NFR-07, NFR-09 |
| Alur | UF-11 |
| Aktor dan hak | Akun stasiun (`HA-KIO-01`). |
| Aturan terkait | BR-JAM-11, BR-SCN-03 s.d. BR-SCN-08, BR-STS-06, BR-WA-01 |
| Status | DECISION (penggabungan: scan paling awal berlaku; scan ganda disimpan dengan tanda, Session 5); RECOMMENDATION (idempotent, penandaan, dan rincian lain) |

**Prasyarat**

- Permintaan berasal dari akun stasiun yang login dan aktif (NFR-07). Detail pengamanan endpoint ditetapkan di Session 9.

**Input dan validasi**

| Isian per scan | Validasi |
|---|---|
| ID unik | Wajib. ID yang sudah pernah diterima tidak membuat catatan baru (BR-SCN-05). |
| NISN | Wajib, 10 digit. |
| Jenis dan status menurut kiosk | Wajib. |
| Jam scan, jam laptop asli, dan selisih jam | Wajib. Jam scan dalam WIB. |

Setiap kiriman juga membawa jam laptop saat kiriman dibuat. Server memakainya untuk mengukur selisih jam stasiun (`07` ARS-27).

**Perilaku**

1. Untuk setiap scan dengan ID baru, server menyimpan catatan scan apa adanya beserta waktu diterima (BR-SCN-06). ID yang sudah ada hanya dibalas sebagai diterima.
2. Server mencari siswa dari NISN. NISN yang tidak dikenal disimpan dengan hasil "ditolak: NISN tidak dikenal" dan tidak dipakai.
3. Server menilai ulang setiap scan dengan aturan jam dan kalender tanggal itu yang tersimpan di server (BR-JAM-11). Bila hasilnya berbeda dari kiosk, hasil server yang berlaku.
4. Server menandai scan untuk ditinjau dalam keadaan BR-SCN-08:

   | Keadaan | Selama belum ditinjau |
   |---|---|
   | Jam scan lebih dari toleransi di depan jam server saat diterima | Dipakai |
   | Selisih jam yang dipakai kiosk berbeda lebih dari toleransi dari selisih yang diukur server saat kiriman diterima (`07` ARS-27) | Dipakai |
   | Menurut aturan server, scan berada di luar jendela, atau tanggalnya bukan hari sekolah bagi siswa | Tidak dipakai |
   | Scan diterima lebih lambat dari batas mundur setelah tanggal scan | Dipakai |

   Keadaan ketiga dinilai ulang setiap kali aturan jam atau kalender tanggal itu berubah (BR-JAM-11, `06` §8.2). Misalnya, scan pukul 08.30 yang diterima saat jam tutup sesi masuk diundur ke 09.00 menjadi bertanda bila jadwal hari ini kemudian dikembalikan.

5. **Penggabungan** (BR-SCN-03, BR-SCN-04). Untuk setiap siswa, tanggal, dan jenis, scan paling awal yang dipakai menjadi kandidat presensi. Scan lainnya diberi hasil "ganda". Presensi masuk adalah yang paling awal di antara scan masuk yang dipakai dan presensi manual masuk yang tidak dibatalkan. Aturan yang sama berlaku untuk presensi pulang.
6. Server menghitung ulang status dan kejadian siswa yang terdampak (FS-PRS-05, §4.5). Scan yang tiba setelah tanggal berganti tetap mengoreksi status tanggal itu (UF-11 E6).
7. Server memperbarui status stasiun: waktu kontak dan sinkron terakhir, jumlah belum tersinkron yang dilaporkan, selisih jam, dan versi data (FS-KIO-05).
8. Respons berisi ID yang diterima, jam server, versi data, serta status dan nama akun stasiun.

**Keadaan kosong dan error**

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | Permintaan tanpa login akun stasiun yang sah. | Ditolak. Kiosk meminta login ulang (FS-KIO-03 E2). |
| E2 | Akun stasiun nonaktif. | Ditolak dengan status "nonaktif" (FS-AKN-04). |
| E3 | Satu scan dalam kiriman rusak, misalnya NISN bukan 10 digit. | Hanya scan itu yang ditolak, dengan alasan. Scan lain tetap diproses. |
| E4 | NISN tidak dikenal server. | Disimpan dengan hasil ditolak dan tidak dipakai. |

**Data dan log**

- Ditulis: catatan scan dengan kolom di `05` §14, termasuk hasil di server (dipakai, ganda, ditandai, atau ditolak) dan alasan penandaan; status stasiun.
- Catatan scan tidak pernah diubah isinya atau dihapus (BR-SCN-06). Hasil tinjauan disimpan terpisah dari isi scan (FS-KIO-06).

**Parameter dan default**

| Parameter | Default | Diatur oleh | Dipastikan di |
|---|---|---|---|
| Toleransi selisih jam | 2 menit (BR-SCN-08) | Sistem | Session 6 (DECISION) |

**Di luar cakupan**

- Mengubah jam scan di server. Jam scan disimpan apa adanya.
- Notifikasi WA (R2, FS-WA-02).

**Catatan antarmuka awal**

Tidak ada. Fitur ini tidak memiliki tampilan.

**Acceptance criteria**

**AC-KIO-04-01 — Kiriman ulang tidak menggandakan data**
Rujukan: FR-KIO-10, NFR-04, BR-SCN-05.

```text
Given server sudah menerima scan dengan ID X
When kiosk mengirim scan dengan ID X lagi
Then server tidak membuat catatan scan baru
  And respons menyatakan ID X diterima
```

**AC-KIO-04-02 — Scan dari dua stasiun**
Rujukan: FR-KIO-10, BR-SCN-03, `05` §13 contoh 4.

```text
Given siswa A scan masuk di Gerbang 1 (offline) pukul 06.50, lalu di Gerbang 2 (online) pukul 06.52
  And scan Gerbang 2 sudah tersinkron
When Gerbang 1 tersinkron
Then presensi masuk siswa A menjadi pukul 06.50
  And scan pukul 06.52 tersimpan dengan hasil "ganda"
```

**AC-KIO-04-03 — Scan yang terlambat sinkron mengoreksi Alpa**
Rujukan: FR-PRS-05, BR-STS-06, R-13, UF-13 E1.

```text
Given siswa B scan masuk pukul 07.40 di stasiun yang offline
  And pukul 08.00 siswa B berstatus Alpa
When stasiun itu tersinkron pukul 09.30
Then status siswa B hari ini menjadi Terlambat tanpa langkah manual
```

**AC-KIO-04-04 — Aturan server yang berlaku**
Rujukan: BR-JAM-10, BR-JAM-11, `05` §13 contoh 10.

```text
Given pukul 07.10 guru piket mengundur batas terlambat hari ini menjadi 07.30, saat Gerbang 1 sedang offline
  And Gerbang 1 menampilkan "Terlambat" untuk scan siswa C pukul 07.20
When Gerbang 1 tersinkron
Then server menetapkan status siswa C hari ini sebagai Hadir
```

**AC-KIO-04-05 — Scan bertanda**
Rujukan: FR-KIO-11, BR-SCN-08.

```text
Given toleransi selisih jam 2 menit
When server menerima scan dengan jam scan 5 menit di depan jam server
Then scan ditandai untuk ditinjau dan tetap dipakai
When server menerima scan yang menurut aturan server berada di luar jendela scan
Then scan ditandai untuk ditinjau dan tidak dipakai sampai ditinjau
```

**AC-KIO-04-06 — Scan tiba setelah tanggal berganti**
Rujukan: UF-11 E6, BR-WA-01.

```text
Given scan masuk siswa D pada Senin pukul 06.55 baru tersinkron pada Selasa pagi
When server menerima scan itu
Then status siswa D pada hari Senin dihitung ulang memakai scan tersebut
```

### FS-KIO-05 — Status stasiun

| Item | Isi |
|---|---|
| Tujuan | Admin dan guru piket memantau keadaan setiap stasiun scan, terutama scan yang belum tersinkron. |
| Rilis | R1 |
| Requirement | FR-KIO-12 |
| Alur | UF-06, UF-11, UF-13 E1 |
| Aktor dan hak | Admin dan guru piket (`HA-KIO-02`). |
| Aturan terkait | BR-SCN-08, BR-WA-02 syarat 3 |
| Status | DECISION (status stasiun di panel); RECOMMENDATION (rincian) |

**Prasyarat**

- Akun stasiun sudah ada (FS-AKN-04).

**Input dan validasi**

Tidak ada isian.

**Perilaku**

1. Daftar stasiun menampilkan untuk setiap akun stasiun:
   - nama dan status akun;
   - waktu kontak terakhir dan waktu sinkron terakhir;
   - jumlah scan belum tersinkron yang terakhir dilaporkan;
   - selisih jam terakhir;
   - waktu data terakhir dimuat;
   - jumlah scan yang diterima hari ini.
2. Stasiun disorot bila:
   - laporan terakhirnya hari ini masih menyisakan scan belum tersinkron;
   - tidak ada kontak lebih dari batas waktu selama jendela scan hari ini;
   - selisih jam melebihi toleransi (BR-SCN-08).
3. Dashboard pemegang `HA-KIO-02` menampilkan peringatan ringkas bila ada stasiun yang disorot (FS-LAP-01).
4. Definisi tersinkron untuk R2: sebuah stasiun dianggap tersinkron bila laporan terakhirnya hari ini tidak menyisakan scan. Stasiun yang belum melapor hari ini tidak menahan pesan (BR-WA-02 syarat 3).

**Keadaan kosong dan error**

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | Belum ada akun stasiun. | "Belum ada stasiun scan." Admin melihat tautan untuk menambah akun stasiun. |
| E2 | Stasiun belum melapor hari ini. | Tampil "belum ada kontak hari ini". |

**Data dan log**

- Dibaca: status stasiun yang ditulis FS-KIO-04 dan catatan scan hari ini.

**Parameter dan default**

| Parameter | Default | Diatur oleh | Dipastikan di |
|---|---|---|---|
| Batas waktu tanpa kontak sebelum disorot | 10 menit | Sistem | Session 6 (DECISION) |

**Di luar cakupan**

- Mengendalikan kiosk dari panel, misalnya memaksa sinkron atau memuat ulang data.

**Catatan antarmuka awal**

- Daftar stasiun diperbarui setiap 30 detik tanpa memuat ulang halaman (`07` ARS-50).

**Acceptance criteria**

**AC-KIO-05-01 — Stasiun dengan scan belum tersinkron**
Rujukan: FR-KIO-12, `HA-KIO-02`, UF-13 E1.

```text
Given laporan terakhir "Gerbang 2" pukul 07.15 menyisakan 15 scan belum tersinkron
When guru piket membuka status stasiun pukul 07.30
Then "Gerbang 2" disorot dengan 15 scan belum tersinkron dan waktu kontak terakhir 07.15
  And dashboard guru piket menampilkan peringatan tentang "Gerbang 2"
When "Gerbang 2" tersinkron
Then jumlah belum tersinkron menjadi nol dan sorotan hilang
```

**AC-KIO-05-02 — Hak melihat status stasiun**
Rujukan: `HA-KIO-02`.

```text
Given wali kelas tanpa role guru piket atau admin sudah login
When wali kelas membuka status stasiun
Then permintaan ditolak
```

### FS-KIO-06 — Tinjauan scan bertanda

| Item | Isi |
|---|---|
| Tujuan | Admin dan guru piket memeriksa scan yang ditandai saat sinkron, lalu menerima atau menolaknya. |
| Rilis | R1 |
| Requirement | FR-KIO-11 |
| Alur | UF-11 E4 |
| Aktor dan hak | Admin (semua) dan guru piket (hari ini) (`HA-KIO-03`). |
| Aturan terkait | BR-SCN-08, BR-STS-06 |
| Status | RECOMMENDATION |

**Prasyarat**

- Ada scan bertanda dari FS-KIO-04.

**Input dan validasi**

| Isian | Wajib | Validasi |
|---|---|---|
| Keputusan | Ya | Terima atau tolak. |
| Catatan | Ya, saat menolak | Teks. |

**Perilaku**

1. Daftar scan bertanda yang menunggu tinjauan dapat disaring berdasarkan tanggal, stasiun, dan alasan.
2. Detail setiap scan: siswa beserta foto, jam scan, jam laptop asli, selisih jam, waktu diterima, stasiun, alasan penandaan, dan apakah scan dipakai selama belum ditinjau.
3. **Terima:** scan dipakai sesuai aturan penggabungan (FS-KIO-04).
4. **Tolak:** scan tidak dipakai.
5. Peninjau dapat memilih beberapa scan dengan alasan yang sama, lalu menerima atau menolaknya sekaligus.
6. Setelah keputusan, status dihitung ulang (§4.5) dan keputusan dicatat di log perubahan presensi (§4.4).
7. Keputusan tinjauan bersifat final. Perbaikan berikutnya dilakukan lewat presensi manual atau koreksi status.
8. Jumlah scan yang menunggu tinjauan tampil di dashboard pemegang `HA-KIO-03` (FS-LAP-01).

**Keadaan kosong dan error**

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | Tidak ada scan bertanda. | "Tidak ada scan yang perlu ditinjau." |
| E2 | Scan sudah ditinjau orang lain. | Ditolak dengan keputusan yang sudah ada dan nama peninjaunya (§4.6). |
| E3 | Guru piket membuka scan tanggal lampau. | Ditolak, karena cakupannya hanya hari ini. |
| E4 | Menolak tanpa catatan. | Ditolak. |

**Data dan log**

- Ditulis: hasil tinjauan (keputusan, catatan, peninjau, dan waktu), terpisah dari isi catatan scan (BR-SCN-06).
- Log perubahan presensi: setiap keputusan tinjauan.

**Parameter dan default**

Tidak ada.

**Di luar cakupan**

- Mengubah jam scan.
- Mengubah keputusan tinjauan yang sudah dibuat.

**Catatan antarmuka awal**

- Daftar dikelompokkan per stasiun dan alasan, karena masalah jam biasanya terjadi pada satu laptop.

**Acceptance criteria**

**AC-KIO-06-01 — Menerima scan yang tidak dipakai**
Rujukan: FR-KIO-11, `HA-KIO-03`, BR-SCN-08.

```text
Given admin memajukan jam tutup sesi masuk hari ini menjadi 07.15 lewat jadwal hari ini, saat Gerbang 1 sedang offline dengan aturan lama
  And scan masuk siswa E pukul 07.20 dari Gerbang 1 ditandai karena menurut aturan server berada di luar jendela, sehingga tidak dipakai
  And siswa E berstatus Alpa
When admin menerima scan itu
Then status siswa E menjadi Terlambat, karena pukul 07.20 setelah batas terlambat 07.00
  And log perubahan presensi mencatat keputusan admin
```

**AC-KIO-06-02 — Menolak scan yang dipakai**
Rujukan: FR-KIO-11, `HA-KIO-03`, BR-SCN-08.

```text
Given scan masuk siswa F hari ini ditandai karena jam laptop maju, tetapi tetap dipakai, sehingga siswa F berstatus Hadir
When guru piket menolak scan itu dengan catatan
Then scan tidak dipakai
  And status siswa F dihitung ulang dari data lain, atau menjadi Alpa bila tidak ada data lain setelah sesi masuk ditutup
```

**AC-KIO-06-03 — Cakupan guru piket**
Rujukan: `HA-KIO-03`.

```text
Given ada scan bertanda bertanggal kemarin
When guru piket tanpa role admin membuka scan tersebut
Then permintaan ditolak
```

## 8. Presensi dan aturan (PRS)

### FS-PRS-01 — Pola mingguan

| Item | Isi |
|---|---|
| Tujuan | Admin mengatur hari sekolah dan aturan jam untuk setiap hari dalam seminggu. |
| Rilis | R1 |
| Requirement | FR-PRS-02, FR-PRS-03 |
| Alur | UF-01 |
| Aktor dan hak | Admin (`HA-PRS-01`). |
| Aturan terkait | BR-KAL-01, BR-KAL-07, BR-JAM-01, BR-JAM-02 |
| Status | DECISION (Senin–Sabtu; aturan jam per hari); RECOMMENDATION (rincian isian, versi pola) |

**Prasyarat**

- Admin sudah login.

**Input dan validasi**

| Isian | Wajib | Validasi |
|---|---|---|
| Hari sekolah, untuk setiap hari Senin–Minggu | Ya | Ya atau tidak. Default Senin–Sabtu (BR-KAL-01). |
| Tujuh isian aturan jam, untuk setiap hari sekolah | Ya | Jam dalam format JJ.MM; toleransi terlambat dalam menit, bilangan bulat ≥ 0. Urutan jam mengikuti BR-JAM-02. |
| Berlaku mulai | Ya | Hari ini atau tanggal ke depan (BR-KAL-07). |

**Perilaku**

1. Pola mingguan memiliki versi. Setiap versi berlaku mulai tanggal tertentu, dan tidak berlaku surut (BR-KAL-07).
2. Admin melihat versi yang sedang berlaku dan versi yang dijadwalkan. Versi yang belum berlaku dapat diubah atau dihapus.
3. Bila versi baru berlaku mulai hari ini, status hari ini dihitung ulang (§4.5).
4. Kiosk mendapat versi baru saat memuat data berikutnya (FS-KIO-01).
5. Sebelum pola mingguan pertama lengkap, kiosk menolak scan (FS-KIO-01 E6), dan dashboard admin menampilkan pengingat penyiapan (FS-LAP-01).
6. Perubahan dicatat di log perubahan presensi (§4.4).

**Keadaan kosong dan error**

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | Urutan jam melanggar BR-JAM-02, misalnya batas terlambat sama dengan atau setelah jam tutup sesi masuk. | Ditolak dengan menyebut isian yang salah. |
| E2 | Berlaku mulai di tanggal lampau. | Ditolak. Perubahan untuk tanggal lampau memakai jadwal khusus (FS-PRS-02). |
| E3 | Hari sekolah tanpa aturan jam lengkap. | Ditolak. |

**Data dan log**

- Ditulis: versi pola mingguan (tanggal mulai berlaku, hari sekolah per hari, dan tujuh isian aturan jam per hari).
- Log perubahan presensi: pembuatan, perubahan, dan penghapusan versi.

**Parameter dan default**

| Parameter | Default | Diatur oleh | Dipastikan di |
|---|---|---|---|
| Hari sekolah | Senin–Sabtu | Admin | — (DECISION) |
| Nilai jam | Diisi admin saat penyiapan; contoh di `05` §4.1 | Admin | UF-01 |

**Di luar cakupan**

- Pola berbeda per tingkat atau rombel. Pola mingguan berlaku untuk semua siswa.
- Jadwal pelajaran (R3, FS-INF-01).

**Catatan antarmuka awal**

- Tabel tujuh hari × tujuh isian, dengan pratinjau batas terlambat (jam masuk + toleransi).

**Acceptance criteria**

**AC-PRS-01-01 — Pola baru tidak berlaku surut**
Rujukan: FR-PRS-02, `HA-PRS-01`, BR-KAL-07.

```text
Given pola mingguan berlaku dengan jam masuk Selasa 07.00
When pada 13 Oktober 2026 admin membuat versi baru dengan jam masuk Selasa 06.45, berlaku mulai 19 Oktober 2026
Then status 13 Oktober 2026 tetap dihitung dengan jam masuk 07.00
  And mulai Selasa, 20 Oktober 2026, jam masuk adalah 06.45
```

**AC-PRS-01-02 — Urutan jam tidak valid**
Rujukan: FR-PRS-02, BR-JAM-02.

```text
Given admin mengisi jam masuk 07.00, toleransi 60 menit, dan jam tutup sesi masuk 08.00
When admin menyimpan
Then penyimpanan ditolak karena batas terlambat 08.00 tidak sebelum jam tutup sesi masuk
```

**AC-PRS-01-03 — Hari Minggu bukan hari sekolah**
Rujukan: FR-PRS-03, BR-KAL-01, BR-KAL-05.

```text
Given pola mingguan default Senin–Sabtu
When sistem menentukan status hari Minggu
Then tidak ada siswa yang memiliki status pada hari Minggu
  And kiosk menolak scan pada hari Minggu
```

### FS-PRS-02 — Jadwal khusus

| Item | Isi |
|---|---|
| Tujuan | Admin membuat aturan jam untuk satu tanggal atau rentang tanggal yang mengalahkan pola mingguan. |
| Rilis | R1 |
| Requirement | FR-PRS-02, FR-PRS-03 |
| Alur | UF-01 |
| Aktor dan hak | Admin (`HA-PRS-01`). |
| Aturan terkait | BR-KAL-03, BR-KAL-04, BR-KAL-05, BR-KAL-07, BR-JAM-02 |
| Status | DECISION (jadwal khusus; berlaku untuk semua siswa, Session 4b); RECOMMENDATION (hari sekolah pengganti dan rincian) |

**Prasyarat**

- Admin sudah login.

**Input dan validasi**

| Isian | Wajib | Validasi |
|---|---|---|
| Tanggal mulai dan selesai | Ya | Mulai ≤ selesai. Boleh tanggal lampau (BR-KAL-07). Tidak tumpang tindih dengan jadwal khusus lain. |
| Tujuh isian aturan jam | Ya | Sama dengan FS-PRS-01. |
| Keterangan | Ya | Misalnya "Ramadan" atau "Rapat guru". |

**Perilaku**

1. Jadwal khusus berlaku untuk semua siswa pada tanggal yang dicakupnya (BR-KAL-03, DECISION Session 4b).
2. Bila jam berbeda per tingkat, misalnya saat try out kelas 9, admin memakai jam masuk yang paling lambat dan jam pulang yang paling awal. Hasilnya tidak ada Terlambat atau pulang lebih awal yang keliru, tetapi keterlambatan tingkat lain pada hari itu tidak terdeteksi (§2.2).
3. Jadwal khusus pada tanggal yang menurut pola mingguan bukan hari sekolah menjadikan tanggal itu hari sekolah, misalnya hari Minggu pengganti (BR-KAL-04).
4. Libur tetap menang atas jadwal khusus bagi siswa yang diliburkan (BR-KAL-05 syarat 3).
5. Urutan aturan jam untuk satu tanggal: jadwal hari ini (FS-PRS-04), lalu jadwal khusus, lalu pola mingguan.
6. Pembuatan, perubahan, dan penghapusan jadwal khusus menghitung ulang status tanggal yang dicakupnya (§4.5), termasuk tanggal lampau, dan dicatat di log (§4.4).

**Keadaan kosong dan error**

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | Tumpang tindih dengan jadwal khusus lain. | Ditolak dengan menyebut jadwal yang bentrok. |
| E2 | Urutan jam tidak valid. | Ditolak (BR-JAM-02). |
| E3 | Tanggal di luar semester. | Disimpan, tetapi sistem memperingatkan bahwa tanggal di luar semester tetap bukan hari sekolah (BR-KAL-05 syarat 1). |

**Data dan log**

- Ditulis: jadwal khusus (tanggal mulai dan selesai, tujuh isian, keterangan, dan pembuat).
- Log perubahan presensi: pembuatan, perubahan, dan penghapusan.

**Parameter dan default**

Tidak ada.

**Di luar cakupan**

- Jadwal khusus per tingkat atau per rombel (§2.2).

**Catatan antarmuka awal**

- Daftar jadwal khusus dalam tampilan kalender atau daftar berurutan tanggal.

**Acceptance criteria**

**AC-PRS-02-01 — Jadwal Ramadan**
Rujukan: FR-PRS-02, `HA-PRS-01`, BR-KAL-03.

```text
Given admin membuat jadwal khusus "Ramadan" dengan jam masuk 07.30 untuk rentang Ramadan
When siswa scan masuk pukul 07.20 pada tanggal di dalam rentang itu
Then status siswa adalah Hadir
  And pada tanggal setelah rentang itu, pola mingguan kembali berlaku
```

**AC-PRS-02-02 — Hari sekolah pengganti**
Rujukan: FR-PRS-03, BR-KAL-04.

```text
Given admin membuat jadwal khusus untuk hari Minggu sebagai hari sekolah pengganti
When hari Minggu itu tiba
Then kiosk menerima scan sesuai jadwal khusus
  And siswa tanpa presensi masuk menjadi Alpa setelah sesi masuk ditutup
```

**AC-PRS-02-03 — Jadwal khusus untuk tanggal lampau**
Rujukan: BR-KAL-07, BR-STS-06.

```text
Given pada 9 Oktober 2026 sekolah memakai jam masuk 07.30, tetapi jadwal khusus belum dibuat, sehingga scan pukul 07.15 tercatat Terlambat
When admin membuat jadwal khusus 9 Oktober 2026 dengan jam masuk 07.30
Then status siswa yang scan pukul 07.15 pada tanggal itu menjadi Hadir
  And log perubahan presensi mencatat pembuatan jadwal khusus
```

**AC-PRS-02-04 — Tumpang tindih**
Rujukan: FR-PRS-02.

```text
Given ada jadwal khusus untuk 16–18 Oktober 2026
When admin membuat jadwal khusus untuk 18–20 Oktober 2026
Then penyimpanan ditolak dengan menyebut jadwal yang bentrok
```

### FS-PRS-03 — Libur

| Item | Isi |
|---|---|
| Tujuan | Admin menandai tanggal libur untuk semua siswa, tingkat tertentu, atau rombel tertentu. |
| Rilis | R1 |
| Requirement | FR-PRS-03 |
| Alur | UF-01 |
| Aktor dan hak | Admin (`HA-PRS-02`). |
| Aturan terkait | BR-KAL-02, BR-KAL-05, BR-KAL-07, BR-JAM-06, BR-IZN-02 |
| Status | DECISION (libur per semua siswa, tingkat, atau rombel; libur tidak dihitung Alpa); RECOMMENDATION (rincian) |

**Prasyarat**

- Admin sudah login.

**Input dan validasi**

| Isian | Wajib | Validasi |
|---|---|---|
| Tanggal mulai dan selesai | Ya | Mulai ≤ selesai. Boleh tanggal lampau (BR-KAL-07). |
| Keterangan | Ya | Misalnya "Libur semester" atau "ANBK kelas 8". Keterangan ini tampil di kiosk. |
| Cakupan | Ya | Semua siswa, satu atau beberapa tingkat, atau satu atau beberapa rombel. |

**Perilaku**

1. Siswa yang tercakup tidak memiliki status pada tanggal libur, sehingga tidak Alpa (BR-KAL-05).
2. Kiosk menolak scan siswa yang tercakup dengan keterangan libur (BR-JAM-06).
3. Libur boleh tumpang tindih. Seorang siswa libur bila tercakup oleh salah satu data libur.
4. Libur yang dibuat untuk tanggal lampau atau hari ini menghitung ulang status (§4.5). Contohnya sekolah tiba-tiba libur karena banjir, sehingga Alpa hari itu hilang. Data presensi yang sudah ada tetap tersimpan, tetapi tidak dihitung.
5. Izin/sakit/dispensasi yang rentangnya mencakup tanggal libur hanya berlaku untuk hari sekolah (BR-IZN-02).
6. Perubahan dicatat di log (§4.4).

**Keadaan kosong dan error**

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | Cakupan kosong. | Ditolak. |
| E2 | Tanggal selesai sebelum tanggal mulai. | Ditolak. |

**Data dan log**

- Ditulis: libur (tanggal mulai dan selesai, keterangan, dan cakupan).
- Log perubahan presensi: pembuatan, perubahan, dan penghapusan.

**Parameter dan default**

Tidak ada.

**Di luar cakupan**

- Libur untuk siswa tertentu. Ketidakhadiran perorangan memakai izin/sakit/dispensasi.

**Catatan antarmuka awal**

- Kalender bulanan dengan warna berbeda untuk libur semua siswa dan libur sebagian.

**Acceptance criteria**

**AC-PRS-03-01 — Libur satu tingkat**
Rujukan: FR-PRS-03, `HA-PRS-02`, BR-KAL-02, `05` §13 contoh 11.

```text
Given admin membuat libur "Selesai ujian kelas 9" untuk tingkat 9 pada 13 Oktober 2026
When sesi masuk ditutup pukul 08.00
Then siswa kelas 9 tidak memiliki status dan tidak dihitung Alpa
  And siswa kelas 7 dan 8 tanpa presensi masuk menjadi Alpa
  And kiosk menolak scan siswa kelas 9 dengan keterangan libur
```

**AC-PRS-03-02 — Libur mendadak untuk tanggal lampau**
Rujukan: BR-KAL-07, BR-STS-06.

```text
Given pada 12 Oktober 2026 sekolah tutup karena banjir, sehingga semua siswa berstatus Alpa
When admin membuat libur untuk semua siswa pada 12 Oktober 2026
Then tidak ada siswa yang memiliki status pada tanggal itu
  And rekap Oktober tidak menghitung tanggal itu sebagai hari sekolah
```

### FS-PRS-04 — Ubah jadwal hari ini

| Item | Isi |
|---|---|
| Tujuan | Guru piket atau admin mengubah aturan jam hari ini karena keadaan mendadak, misalnya hujan deras atau rapat guru. |
| Rilis | R1 |
| Requirement | FR-PRS-09 |
| Alur | UF-28 |
| Aktor dan hak | Admin dan guru piket, untuk hari ini (`HA-PRS-07`). |
| Aturan terkait | BR-JAM-02, BR-JAM-10, BR-JAM-11, BR-STS-06, BR-KOR-10, BR-WA-04 |
| Status | DECISION (pelaku; alasan wajib; dicatat dan dihitung ulang); RECOMMENDATION (batasan guru piket dan rincian) |

**Prasyarat**

- Hari ini adalah hari sekolah, setidaknya bagi sebagian siswa.

**Input dan validasi**

| Isian | Wajib | Validasi |
|---|---|---|
| Tujuh isian aturan jam hari ini | Ya | Terisi otomatis dengan aturan yang sedang berlaku. Urutan mengikuti BR-JAM-02. |
| Alasan | Ya | Teks (§4.3). |

**Perilaku**

1. Guru piket atau admin membuka jadwal hari ini, mengubah satu atau beberapa isian, lalu mengisi alasan (UF-28).
2. Sistem menyimpan aturan baru sebagai jadwal hari ini. Jadwal ini mengalahkan jadwal khusus dan pola mingguan untuk hari ini (FS-PRS-02 butir 5).
3. Status hari ini dihitung ulang (§4.5). Contohnya:
   - batas terlambat yang diundur mengubah scan yang tadinya Terlambat menjadi Hadir;
   - jam tutup sesi masuk yang diundur setelah lewat membuka kembali jendela masuk, sehingga Alpa kembali menjadi "belum hadir" sampai jam tutup yang baru.
4. Stasiun yang online memuat aturan baru pada sinkron berikutnya (FS-KIO-01). Stasiun yang offline memakai aturan lama sampai online kembali, tetapi server tetap menghitung status dengan aturan baru (BR-JAM-11).
5. Perubahan boleh dilakukan beberapa kali dalam sehari. Setiap perubahan dicatat di log dengan nilai lama, nilai baru, dan alasan (§4.4).
6. **Kembalikan jadwal semula.** Guru piket atau admin dapat menghapus jadwal hari ini dengan alasan. Aturan kembali ke jadwal khusus atau pola mingguan, lalu status dihitung ulang dan perubahan dicatat.
7. Guru piket tidak dapat mengubah tanggal lain atau pola mingguan, dan tidak dapat menjadikan hari ini libur (BR-JAM-10). Admin melakukannya lewat FS-PRS-01 s.d. FS-PRS-03.
8. Di R2, pesan yang sudah terkirim tidak dikoreksi (BR-WA-04).

**Keadaan kosong dan error**

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | Urutan jam tidak valid (UF-28 E2). | Ditolak dengan menyebut isian yang salah. |
| E2 | Hari ini bukan hari sekolah. | Fitur tidak tersedia. Admin memakai jadwal khusus (FS-PRS-02). |
| E3 | Guru piket mencoba mengubah tanggal lain (UF-28 E1). | Ditolak. |
| E4 | Alasan kosong. | Ditolak. |

**Data dan log**

- Ditulis: jadwal hari ini (tujuh isian, alasan, pembuat, dan waktu).
- Log perubahan presensi: setiap perubahan dan pengembalian.

**Parameter dan default**

Tidak ada.

**Di luar cakupan**

- Jadwal hari ini per tingkat atau per rombel.
- Mengubah jadwal hari ini lewat kiosk.

**Catatan antarmuka awal**

- Formulir menampilkan aturan yang sedang berlaku di samping isian baru.
- Dashboard menampilkan bahwa jadwal hari ini sudah diubah, beserta alasannya (FS-LAP-01).

**Acceptance criteria**

**AC-PRS-04-01 — Hujan deras**
Rujukan: FR-PRS-09, `HA-PRS-07`, UF-28, BR-JAM-10, `05` §4.1.

```text
Given batas terlambat hari ini 07.00 dan siswa A scan masuk pukul 07.20, sehingga berstatus Terlambat
When pukul 07.25 guru piket mengubah toleransi terlambat menjadi 30 menit dengan alasan "Hujan deras"
Then batas terlambat hari ini menjadi 07.30
  And status siswa A menjadi Hadir
  And log perubahan presensi mencatat nilai lama, nilai baru, alasan, dan nama guru piket
```

**AC-PRS-04-02 — Membuka kembali sesi masuk**
Rujukan: FR-PRS-09, BR-JAM-07, BR-JAM-08.

```text
Given pukul 08.10 siswa B berstatus Alpa karena sesi masuk ditutup pukul 08.00
When guru piket mengundur jam tutup sesi masuk hari ini menjadi 09.00
Then siswa B kembali berstatus "belum hadir"
  And kiosk yang online menerima scan masuk sampai pukul 08.59
```

**AC-PRS-04-03 — Batasan guru piket**
Rujukan: `HA-PRS-07`, UF-28 E1, BR-JAM-10.

```text
Given guru piket sudah login
When guru piket mencoba mengubah jadwal untuk besok, atau menjadikan hari ini libur
Then permintaan ditolak
```

**AC-PRS-04-04 — Urutan jam tidak valid**
Rujukan: UF-28 E2, BR-JAM-02.

```text
Given jam tutup sesi masuk hari ini 08.00
When guru piket mengubah toleransi terlambat menjadi 90 menit
Then perubahan ditolak karena batas terlambat melewati jam tutup sesi masuk
```

**AC-PRS-04-05 — Kembalikan jadwal semula**
Rujukan: FR-PRS-09, BR-KOR-10.

```text
Given guru piket sudah mengubah jadwal hari ini
When admin mengembalikan jadwal semula dengan alasan
Then aturan jam hari ini kembali ke pola mingguan atau jadwal khusus
  And status hari ini dihitung ulang
  And log mencatat pengembalian itu
```

### FS-PRS-05 — Penentuan status harian

| Item | Isi |
|---|---|
| Tujuan | Sistem menentukan status harian, kejadian pulang, dan penanda setiap siswa dari data sumbernya, tanpa langkah manual. |
| Rilis | R1 |
| Requirement | FR-PRS-01, FR-PRS-04, FR-PRS-05, FR-PRS-08 |
| Alur | UF-13, UF-14 |
| Aktor dan hak | Sistem. Hasilnya dilihat lewat FS-LAP-01 s.d. FS-LAP-04 sesuai hak masing-masing. |
| Aturan terkait | BR-KAL-05, BR-JAM-03 s.d. BR-JAM-09, BR-SCN-04, BR-SCN-10, BR-STS-01 s.d. BR-STS-08, BR-DRT-03 s.d. BR-DRT-05, BR-DRT-07, BR-IZN-02, BR-IZN-06, BR-REK-04 |
| Status | DECISION (enam status, urutan prioritas, Alpa otomatis, sesi ditutup otomatis, penanda, Session 4b; dapat dihitung ulang dan disimpan sebagai salinan, Session 5); RECOMMENDATION (definisi rinci di bawah) |

**Prasyarat**

- Tahun ajaran, semester, rombel, penempatan siswa, dan pola mingguan sudah ada.

**Input dan validasi**

Tidak ada input pengguna. Sumber datanya:

| Sumber | Fitur |
|---|---|
| Kalender: semester, pola mingguan, jadwal khusus, jadwal hari ini, dan libur | FS-MD-02, FS-PRS-01 s.d. FS-PRS-04 |
| Masa aktif dan penempatan siswa | FS-MD-04, FS-MD-05 |
| Scan dengan hasil "dipakai" | FS-KIO-04, FS-KIO-06 |
| Presensi manual yang tidak dibatalkan | FS-PRS-06, FS-PRS-09 |
| Koreksi status yang tidak dihapus | FS-PRS-07 |
| Izin/sakit/dispensasi yang disetujui | FS-IZN-01 s.d. FS-IZN-05 |
| Mode darurat | FS-PRS-08 |
| Jam sekarang | — |

**Perilaku**

Definisi yang dipakai:

- **Presensi masuk** siswa S pada tanggal T adalah yang paling awal di antara scan masuk yang dipakai dan presensi manual masuk yang tidak dibatalkan (BR-SCN-04). **Presensi pulang** ditentukan dengan cara yang sama.
- **Koreksi** adalah koreksi aktif untuk S pada T, yaitu koreksi yang belum diganti atau dihapus. Menghapus koreksi tidak mengaktifkan kembali koreksi sebelumnya (FS-PRS-07).
- **Aturan jam T** diambil dari jadwal hari ini, lalu jadwal khusus, lalu pola mingguan yang berlaku pada T (FS-PRS-02 butir 5).

Langkah penentuan untuk siswa S pada tanggal T:

1. Bila T bukan hari sekolah bagi S (BR-KAL-05), S tidak memiliki status pada T dan tidak dihitung di mana pun.
2. Bila T setelah hari ini, S belum memiliki status. Izin/sakit/dispensasi untuk T sudah tersimpan, tetapi baru menjadi status pada tanggalnya (BR-STS-08).
3. Status ditentukan dengan urutan prioritas BR-STS-02. Aturan pertama yang cocok yang dipakai:
   1. Ada izin, sakit, atau dispensasi yang disetujui dan mencakup T: statusnya Izin, Sakit, atau Dispensasi.
   2. Ada koreksi: Hadir atau Terlambat sesuai koreksi. Koreksi Tidak hadir menghasilkan Alpa.
   3. Ada presensi masuk: Hadir bila jamnya sampai menit batas terlambat T, dan Terlambat bila setelahnya (BR-JAM-04). Pengecualiannya presensi per rombel saat darurat, yang statusnya ditetapkan staf (BR-DRT-04).
   4. Tidak ada satu pun: "belum hadir" bila T adalah hari ini dan sesi masuk belum ditutup, atau bila mode darurat sedang aktif. Selain itu, Alpa.
4. Kejadian pulang hanya dihitung bila status S adalah Hadir atau Terlambat (BR-STS-05):
   - **Pulang lebih awal:** jam presensi pulang sebelum jam pulang T (BR-JAM-05).
   - **Tidak scan pulang:** S tidak memiliki presensi pulang, sesi pulang T sudah ditutup, dan T tidak pernah memakai mode darurat (BR-JAM-09, BR-DRT-07).
5. Penanda ditentukan dengan tabel di bawah (BR-STS-07, DECISION Session 4b).
6. Status hari ini belum final sampai sesi masuk ditutup dan mode darurat berakhir (BR-REK-04).

Penanda (BR-STS-07):

| Penanda | Syarat | Hilang bila |
|---|---|---|
| Izin dengan presensi masuk | Status S adalah Izin, Sakit, atau Dispensasi, dan S memiliki presensi masuk. | Data izin dibatalkan atau dipersingkat sehingga tidak mencakup T, atau presensi manual masuknya dibatalkan. |
| Pulang tanpa presensi masuk | S memiliki presensi pulang tanpa presensi masuk, dan belum ada koreksi pada T. | Presensi masuk tercatat, koreksi dibuat, atau presensi manual pulangnya dibatalkan. |

Penanda bersifat informasi. Penanda tidak mengubah status, dan tetap ada selama syaratnya terpenuhi. Penanda hanya tampil bagi staf yang melihat daftar nama: di dashboard hari ini, daftar presensi rombel per tanggal, dan riwayat siswa, termasuk untuk tanggal lampau (FS-LAP-01, FS-LAP-02, FS-LAP-04). Siswa tidak melihat penanda.

Perubahan karena waktu terjadi tanpa langkah manual (§4.5):

- Pada jam tutup sesi masuk, "belum hadir" menjadi Alpa, kecuali saat mode darurat (BR-JAM-08).
- Pada jam tutup sesi pulang, kejadian "tidak scan pulang" terbentuk (BR-JAM-09).
- Saat mode darurat berakhir, termasuk berakhir otomatis pukul 23.59, "belum hadir" menjadi Alpa (BR-DRT-05).

Hasil per siswa per tanggal: status (atau "belum hadir", atau tanpa status), sumber status (izin, koreksi, presensi, atau tanpa data), jam dan sumber presensi masuk dan pulang (scan beserta stasiun, manual, atau darurat), kejadian pulang, penanda, dan tanda final atau belum final.

**Keadaan kosong dan error**

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | Siswa aktif tetapi tidak ditempatkan di rombel pada T. | Tanggal itu bukan hari sekolah bagi siswa itu (BR-KAL-05 syarat 4). Daftar siswa admin menandai siswa aktif tanpa rombel. |
| E2 | Siswa baru aktif mulai tengah minggu. | Tanggal sebelum tanggal mulai aktif tidak memiliki status (BR-KAL-06). |

**Data dan log**

- Dibaca: semua sumber di tabel input.
- Ditulis: salinan hasil hitungan di `status_harian` (DECISION, Session 5). Bagian yang bergantung pada jam sekarang diturunkan saat dibaca (`06` §11.3).
- Log: perubahan status karena hitung ulang tidak ditulis sebagai entri tersendiri. Yang dicatat adalah perubahan sumbernya (§4.4).

**Parameter dan default**

Tidak ada. Nilai jam berasal dari aturan jam.

**Di luar cakupan**

- Status per jam pelajaran (`00` §6.3).
- Status untuk tanggal ke depan.

**Catatan antarmuka awal**

- Warna dan ikon untuk setiap status dan penanda ditetapkan di Session 7, dan dipakai sama di semua halaman.

**Acceptance criteria**

Contoh di bawah memakai aturan jam di §1. Baris tabel di `05` §6 dan contoh di `05` §13 juga menjadi kasus uji fitur ini.

**AC-PRS-05-01 — Belum hadir menjadi Alpa**
Rujukan: FR-PRS-05, FR-PRS-08, AC-03, UF-13, BR-JAM-08.

```text
Given hari ini hari sekolah bagi siswa A yang aktif
  And siswa A tidak memiliki presensi masuk maupun izin/sakit/dispensasi yang disetujui
  And mode darurat tidak aktif
When pukul 07.30
Then siswa A tampil "belum hadir"
When pukul 08.00 sesi masuk tertutup otomatis
Then status siswa A adalah Alpa tanpa langkah manual
```

**AC-PRS-05-02 — Izin yang disetujui belakangan**
Rujukan: FR-PRS-05, FR-IZN-03, AC-03, BR-STS-02.

```text
Given siswa A berstatus Alpa pukul 09.00
When staf menyetujui izin siswa A untuk hari ini
Then status siswa A menjadi Izin tanpa langkah tambahan
```

**AC-PRS-05-03 — Sakit dengan presensi masuk**
Rujukan: BR-STS-03, BR-STS-07, `05` §13 contoh 7.

```text
Given siswa B memiliki Sakit yang disetujui untuk hari ini
When siswa B scan masuk pukul 06.50
Then status siswa B tetap Sakit
  And presensi masuk pukul 06.50 tetap tersimpan
  And daftar nama staf menampilkan penanda "izin dengan presensi masuk" untuk siswa B
When data sakit siswa B dibatalkan
Then status siswa B menjadi Hadir dan penanda hilang
```

**AC-PRS-05-04 — Koreksi tidak tertimpa**
Rujukan: BR-STS-04, `05` §13 contoh 8.

```text
Given guru piket mengoreksi siswa C menjadi Tidak hadir pukul 09.00 karena kartu dititipkan
When scan masuk kartu siswa C pukul 06.50 dari stasiun offline tersinkron pukul 10.00
Then status siswa C tetap Alpa
```

**AC-PRS-05-05 — Koreksi lalu sakit**
Rujukan: BR-STS-02, BR-KOR-07, `05` §13 contoh 9.

```text
Given siswa C berstatus Alpa karena koreksi Tidak hadir
When wali kelas menginput Sakit untuk siswa C hari ini
Then status siswa C menjadi Sakit
```

**AC-PRS-05-06 — Pulang lebih awal**
Rujukan: BR-JAM-05, BR-STS-05, `05` §13 contoh 5.

```text
Given siswa D scan masuk pukul 06.55
When siswa D scan pulang pukul 12.40
Then status siswa D tetap Hadir
  And siswa D memiliki kejadian "pulang lebih awal"
```

**AC-PRS-05-07 — Tidak scan pulang**
Rujukan: BR-JAM-09, BR-STS-05, UF-14.

```text
Given siswa E berstatus Hadir dan tidak memiliki presensi pulang
  And siswa F berstatus Alpa
When pukul 17.00 sesi pulang tertutup otomatis
Then siswa E mendapat kejadian "tidak scan pulang"
  And siswa F tidak mendapat kejadian pulang
```

**AC-PRS-05-08 — Pulang tanpa presensi masuk**
Rujukan: BR-SCN-10, BR-STS-07, UF-14 E3.

```text
Given siswa G tidak memiliki presensi masuk dan berstatus Alpa
When siswa G scan pulang pukul 13.05
Then status siswa G tetap Alpa
  And daftar nama staf menampilkan penanda "pulang tanpa presensi masuk"
When guru piket mencatat presensi manual masuk pukul 07.30 untuk siswa G dengan alasan "lupa kartu"
Then status siswa G menjadi Terlambat dan penanda hilang
```

**AC-PRS-05-09 — Penanda tetap terlihat esok hari**
Rujukan: BR-STS-07, §2.2.

```text
Given kemarin siswa H berstatus Sakit dan memiliki presensi masuk pukul 06.45
When hari ini wali kelas membuka daftar presensi rombel tanggal kemarin, atau riwayat siswa H
Then penanda "izin dengan presensi masuk" tampil pada tanggal kemarin
```

**AC-PRS-05-10 — Mode darurat menahan Alpa**
Rujukan: FR-PRS-10, BR-DRT-03, BR-DRT-05, `05` §13 contoh 13.

```text
Given mode darurat diaktifkan pukul 06.30
  And siswa I tidak memiliki presensi masuk
When pukul 08.30
Then siswa I masih "belum hadir"
When mode darurat diakhiri pukul 10.00
Then status siswa I menjadi Alpa
```

**AC-PRS-05-11 — Tanpa "tidak scan pulang" pada hari darurat**
Rujukan: BR-DRT-07, UF-14 E4.

```text
Given mode darurat pernah aktif hari ini
  And siswa J berstatus Hadir tanpa presensi pulang
When pukul 17.00 sesi pulang tertutup
Then siswa J tidak mendapat kejadian "tidak scan pulang"
```

**AC-PRS-05-12 — Izin untuk tanggal ke depan**
Rujukan: BR-STS-08, BR-IZN-11.

```text
Given siswa K memiliki Izin yang disetujui untuk Senin, 19 Oktober 2026
When hari ini 13 Oktober 2026
Then siswa K belum memiliki status untuk 19 Oktober 2026
When tanggal 19 Oktober 2026 tiba
Then status siswa K pada tanggal itu adalah Izin
```

**AC-PRS-05-13 — Siswa baru di tengah minggu**
Rujukan: BR-KAL-05, BR-KAL-06.

```text
Given siswa L aktif dan ditempatkan di 7A mulai Rabu, 14 Oktober 2026
When rekap 7A untuk 12–17 Oktober 2026 dibuat
Then siswa L tidak memiliki status pada 12 dan 13 Oktober
  And hari sekolah siswa L pada minggu itu dihitung mulai 14 Oktober
```

### FS-PRS-06 — Presensi manual

| Item | Isi |
|---|---|
| Tujuan | Staf mencatat presensi masuk atau pulang satu siswa tanpa scan, dan membatalkan presensi manual yang salah input. |
| Rilis | R1 |
| Requirement | FR-PRS-06, FR-PRS-11 |
| Alur | UF-12 |
| Aktor dan hak | Admin (semua), wali kelas (rombel), guru piket (hari ini), dan guru BK (semua) (`HA-PRS-03`). Wali kelas dan guru BK dibatasi batas mundur. Pencarian siswa memakai `HA-MD-05`. |
| Aturan terkait | BR-KOR-01 s.d. BR-KOR-05, BR-KOR-10, BR-KOR-11, BR-MUN-01 s.d. BR-MUN-04, BR-SCN-04, BR-STS-03, BR-STS-04, BR-JAM-04, BR-JAM-05 |
| Status | DECISION (presensi manual, pelaku, alasan wajib, tidak terikat jendela scan; pembatalan, Session 4b); RECOMMENDATION (rincian) |

**Prasyarat**

- Siswa aktif dan ditempatkan di rombel pada tanggal yang dipilih.

**Input dan validasi**

| Isian | Wajib | Validasi |
|---|---|---|
| Siswa | Ya | Dicari berdasarkan nama, NISN, atau rombel. Hanya siswa dalam cakupan yang muncul (§4.1). |
| Tanggal | Ya | Default hari ini. Harus hari sekolah bagi siswa itu. Tidak boleh tanggal ke depan (BR-MUN-04). Guru piket hanya hari ini; wali kelas dan guru BK dalam batas mundur; admin tanpa batas (§4.2). |
| Jenis | Ya | Masuk atau pulang. |
| Jam | Ya | Default jam sekarang bila tanggalnya hari ini. Untuk hari ini, jam tidak boleh setelah jam sekarang. Boleh di luar jendela scan (BR-KOR-02). |
| Alasan | Ya | Dari daftar BR-KOR-04: lupa kartu, kartu rusak, QR tidak terbaca, kiosk terganggu, tiba setelah sesi masuk ditutup, pulang karena sakit, pulang dengan izin, darurat, atau lainnya. Pada presensi satu per satu, alasan "darurat" juga boleh dipakai, misalnya untuk rombel yang belum tercatat setelah mode darurat berakhir (UF-27 E3); statusnya tetap dihitung dari jam. |
| Catatan | Ya, bila alasan "lainnya" | Teks. |

Validasi tambahan:

- Presensi manual masuk hanya dapat dicatat bila siswa belum memiliki presensi masuk pada tanggal itu (BR-KOR-03). Aturan yang sama berlaku untuk presensi manual pulang.
- Bila siswa sudah memiliki presensi masuk dan presensi pulang, jam masuk harus sebelum jam pulang.

**Perilaku**

A. Mencatat presensi manual:

1. Staf mencari siswa. Panel menampilkan foto, nama, NISN, rombel, status hari itu, dan presensi yang sudah ada (UF-12 langkah 3).
2. Staf mengisi jenis, jam, dan alasan. Panel menampilkan pratinjau hasilnya dengan aturan jam tanggal itu (BR-KOR-01), misalnya "Terlambat" atau "pulang lebih awal".
3. Panel memberi peringatan yang tidak menghalangi penyimpanan:
   - bila siswa memiliki izin/sakit/dispensasi yang disetujui, status tetap mengikuti izin (BR-STS-03), dan staf diarahkan untuk membatalkan izin bila siswa memang hadir (UF-12 E5);
   - bila siswa memiliki koreksi pada tanggal itu, status tetap mengikuti koreksi (BR-STS-04, UF-12 E4).
4. Sistem menyimpan presensi dengan penanda manual, nama penginput, dan waktu input. Status dihitung ulang (§4.5), dan log mencatatnya (§4.4).

B. Membatalkan presensi manual (BR-KOR-11, DECISION Session 4b):

1. Pemegang `HA-PRS-03` dalam cakupannya memilih satu presensi manual, termasuk presensi per rombel saat darurat (FS-PRS-09), lalu mengisi alasan.
2. Presensi itu tetap tersimpan dengan tanda dibatalkan, pelaku, waktu, dan alasan. Presensi yang dibatalkan tidak dipakai untuk status maupun kejadian.
3. Status dihitung ulang, dan log mencatat pembatalan itu.
4. Setelah dibatalkan, presensi yang benar dapat dicatat ulang.
5. Scan tidak dapat dibatalkan lewat fitur ini (BR-SCN-06). Kesalahan karena scan diperbaiki lewat koreksi status (FS-PRS-07) atau tinjauan scan (FS-KIO-06).

**Keadaan kosong dan error**

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | Siswa sudah memiliki presensi masuk pada tanggal itu (UF-12 E1). | Ditolak. Panel menampilkan presensi yang ada dan mengarahkan ke koreksi status (FS-PRS-07). |
| E2 | Tanggal di luar cakupan atau di luar batas mundur (UF-12 E2). | Ditolak dengan alasan (§4.2). |
| E3 | Tanggal ke depan, atau jam setelah jam sekarang. | Ditolak. |
| E4 | Tanggal bukan hari sekolah bagi siswa, misalnya rombelnya libur. | Ditolak dengan keterangan. |
| E5 | Alasan kosong, atau "lainnya" tanpa catatan. | Ditolak. |
| E6 | Jam masuk tidak sebelum jam pulang. | Ditolak. |
| E7 | Presensi manual yang akan dibatalkan sudah dibatalkan orang lain. | Ditolak dengan data terbaru (§4.6). |

**Data dan log**

- Ditulis: presensi manual (siswa, tanggal, jenis, jam, alasan, catatan, penginput, waktu input, penanda darurat dan status pilihan staf bila dari FS-PRS-09, serta data pembatalan).
- Log perubahan presensi: setiap pencatatan dan pembatalan.

**Parameter dan default**

| Parameter | Default | Diatur oleh | Dipastikan di |
|---|---|---|---|
| Daftar alasan | BR-KOR-04 | Ditetapkan di dokumen | — |

**Di luar cakupan**

- Presensi manual per rombel di luar mode darurat (BR-KOR-05).
- Mengubah jam presensi manual. Presensi yang salah dibatalkan, lalu dicatat ulang.
- Menghapus presensi manual secara permanen.

**Catatan antarmuka awal**

- Dashboard dan daftar presensi rombel menyediakan pintasan presensi manual untuk siswa yang belum hadir atau Alpa.
- Foto siswa tampil besar untuk dicocokkan dengan wajah siswa.

**Acceptance criteria**

**AC-PRS-06-01 — Tiba setelah sesi masuk ditutup**
Rujukan: FR-PRS-06, `HA-PRS-03`, UF-12, UF-13, BR-JAM-08, BR-KOR-02, `05` §13 contoh 3.

```text
Given sesi masuk sudah ditutup pukul 08.00 dan siswa A berstatus Alpa
When guru piket mencatat presensi manual masuk untuk siswa A pukul 08.10 dengan alasan "tiba setelah sesi masuk ditutup"
Then presensi tersimpan dengan penanda manual dan nama guru piket
  And status siswa A hari ini menjadi Terlambat
  And log perubahan presensi mencatat presensi manual itu beserta alasannya
```

**AC-PRS-06-02 — Lupa kartu**
Rujukan: FR-PRS-06, UF-12, BR-JAM-04.

```text
Given siswa B lupa kartu dan menemui guru piket pukul 06.55
When guru piket mencatat presensi manual masuk pukul 06.55 dengan alasan "lupa kartu"
Then status siswa B adalah Hadir
```

**AC-PRS-06-03 — Sudah ada presensi masuk**
Rujukan: UF-12 E1, BR-KOR-03.

```text
Given siswa C sudah scan masuk pukul 06.40
When guru piket mencoba mencatat presensi manual masuk untuk siswa C
Then penyimpanan ditolak dan panel menampilkan presensi pukul 06.40
  And panel mengarahkan guru piket ke koreksi status
```

**AC-PRS-06-04 — Cakupan tanggal**
Rujukan: `HA-PRS-03`, UF-12 E2, BR-MUN-01 s.d. BR-MUN-03.

```text
Given hari ini 13 Oktober 2026 dan batas mundur 7 hari
When guru piket mencoba mencatat presensi manual untuk 12 Oktober 2026
Then penyimpanan ditolak
When wali kelas mencatat presensi manual untuk siswa rombelnya pada 12 Oktober 2026
Then presensi tersimpan
When wali kelas mencoba mencatat presensi manual untuk 5 Oktober 2026
Then penyimpanan ditolak dengan alasan di luar batas mundur
When admin mencatat presensi manual untuk 5 Oktober 2026
Then presensi tersimpan
```

**AC-PRS-06-05 — Pulang karena sakit**
Rujukan: BR-KOR-02, BR-JAM-05, UF-14 E2.

```text
Given siswa D berstatus Hadir
When guru piket mencatat presensi manual pulang pukul 10.00 dengan alasan "pulang karena sakit"
Then status siswa D tetap Hadir
  And siswa D memiliki kejadian "pulang lebih awal"
```

**AC-PRS-06-06 — Siswa dengan izin yang disetujui**
Rujukan: UF-12 E5, BR-STS-03, BR-STS-07.

```text
Given siswa E memiliki Izin yang disetujui untuk hari ini
When guru piket mencatat presensi manual masuk untuk siswa E
Then panel memperingatkan bahwa status tetap Izin selama data izin berlaku
  And presensi tersimpan dan status siswa E tetap Izin
  And daftar nama staf menampilkan penanda "izin dengan presensi masuk"
```

**AC-PRS-06-07 — Membatalkan presensi pulang yang salah siswa**
Rujukan: FR-PRS-06, BR-KOR-11, §2.2.

```text
Given guru piket keliru mencatat presensi manual pulang pukul 10.00 untuk siswa F, sehingga siswa F mendapat kejadian "pulang lebih awal"
  And siswa F scan pulang pukul 13.05
When guru piket membatalkan presensi itu dengan alasan "salah siswa"
Then presensi itu tetap tersimpan dengan tanda dibatalkan, tetapi tidak dipakai
  And kejadian "pulang lebih awal" siswa F hilang
  And log perubahan presensi mencatat pembatalan beserta alasannya
  And scan pulang siswa F pukul 13.05 menjadi presensi pulangnya
```

**AC-PRS-06-08 — Mencatat ulang setelah dibatalkan**
Rujukan: BR-KOR-03, BR-KOR-11.

```text
Given presensi manual masuk siswa G pukul 07.50 dibatalkan karena jamnya salah ketik
When guru piket mencatat presensi manual masuk siswa G pukul 06.50
Then presensi baru tersimpan dan status siswa G menjadi Hadir
```

**AC-PRS-06-09 — Alasan "lainnya"**
Rujukan: BR-KOR-04.

```text
Given guru piket memilih alasan "lainnya"
When guru piket menyimpan tanpa catatan
Then penyimpanan ditolak
```

**AC-PRS-06-10 — Tanggal ke depan**
Rujukan: BR-MUN-04.

```text
Given admin membuka presensi manual
When admin memilih tanggal 14 Oktober 2026
Then penyimpanan ditolak karena presensi manual tidak dapat dibuat untuk tanggal ke depan
```

### FS-PRS-07 — Koreksi status

| Item | Isi |
|---|---|
| Tujuan | Staf menetapkan kehadiran siswa pada satu tanggal menjadi Hadir, Terlambat, atau Tidak hadir, serta menghapus koreksi bila perlu. |
| Rilis | R1 |
| Requirement | FR-PRS-07, FR-PRS-11 |
| Alur | UF-16 |
| Aktor dan hak | Admin (semua), wali kelas (rombel), guru piket (hari ini), dan guru BK (semua) (`HA-PRS-04`). Wali kelas dan guru BK dibatasi batas mundur. |
| Aturan terkait | BR-KOR-06 s.d. BR-KOR-10, BR-STS-02 s.d. BR-STS-04, BR-MUN-01 s.d. BR-MUN-04 |
| Status | DECISION (koreksi, pelaku, alasan wajib, tidak tertimpa data belakangan, bukan untuk izin; hapus koreksi dan koreksi saat ada izin, Session 4b); RECOMMENDATION (rincian) |

**Prasyarat**

- Tanggal itu hari sekolah bagi siswa.

**Input dan validasi**

| Isian | Wajib | Validasi |
|---|---|---|
| Siswa dan tanggal | Ya | Dalam cakupan dan batas mundur (§4.1, §4.2). Bukan tanggal ke depan (BR-MUN-04). Guru piket hanya hari ini. |
| Kehadiran baru | Ya | Hadir, Terlambat, atau Tidak hadir. Izin, Sakit, dan Dispensasi tidak tersedia (BR-KOR-07). |
| Alasan | Ya | Teks. Alasan ini dapat dibaca siswa (§4.3). |

**Perilaku**

A. Menyimpan koreksi:

1. Staf membuka koreksi dari daftar presensi rombel (FS-LAP-02), riwayat siswa (FS-LAP-04), atau dashboard (UF-16).
2. Panel menampilkan status saat ini beserta sumbernya: presensi, koreksi sebelumnya, dan izin.
3. Bila siswa memiliki izin/sakit/dispensasi yang disetujui pada tanggal itu, panel memperingatkan bahwa status tetap mengikuti izin, dan koreksi baru berlaku bila izin itu dibatalkan (BR-KOR-09, DECISION Session 4b). Koreksi tetap dapat disimpan.
4. Satu siswa memiliki paling banyak satu koreksi aktif per tanggal. Koreksi baru menggantikan koreksi sebelumnya, dan yang lama tercatat di log.
5. Sistem menyimpan koreksi, menghitung ulang status (§4.5), dan mencatatnya di log: pelaku, waktu, nilai lama, nilai baru, dan alasan.
6. Koreksi tidak berubah oleh scan atau presensi manual yang datang belakangan (BR-STS-04).

B. Menghapus koreksi (BR-KOR-08, DECISION Session 4b):

1. Pemegang `HA-PRS-04` dalam cakupannya menghapus koreksi dengan alasan.
2. Status dihitung lagi dari presensi (BR-STS-02), dan penghapusan dicatat di log.

**Keadaan kosong dan error**

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | Tanggal di luar cakupan atau di luar batas mundur (UF-16 E1). | Ditolak dengan alasan. |
| E2 | Staf ingin mengubah status menjadi Izin, Sakit, atau Dispensasi (UF-16 E2). | Pilihan itu tidak ada. Panel menyediakan tautan ke input izin/sakit/dispensasi (FS-IZN-02). |
| E3 | Tanggal bukan hari sekolah bagi siswa. | Ditolak. |
| E4 | Alasan kosong. | Ditolak. |
| E5 | Koreksi sudah diubah orang lain sejak formulir dibuka. | Ditolak dengan data terbaru (§4.6). |

**Data dan log**

- Ditulis: koreksi (siswa, tanggal, kehadiran, alasan, pelaku, waktu, dan data penghapusan).
- Log perubahan presensi: penyimpanan, penggantian, dan penghapusan koreksi.

**Parameter dan default**

Tidak ada.

**Di luar cakupan**

- Mengoreksi jam presensi masuk atau pulang. Perbaikannya lewat presensi manual dan pembatalannya (FS-PRS-06).
- Mengoreksi kejadian pulang secara langsung.
- Koreksi massal untuk banyak siswa.

**Catatan antarmuka awal**

- Pengingat di dekat isian alasan: "Alasan dapat dibaca siswa."

**Acceptance criteria**

**AC-PRS-07-01 — Kartu dititipkan**
Rujukan: FR-PRS-07, `HA-PRS-04`, UF-10 E4, UF-16, BR-KOR-06, BR-STS-04.

```text
Given kartu siswa A dipindai temannya pukul 06.50, sehingga siswa A berstatus Hadir
When pukul 09.00 guru piket mengoreksi siswa A menjadi Tidak hadir dengan alasan "kartu dititipkan"
Then status siswa A menjadi Alpa
  And log perubahan presensi mencatat guru piket, waktu, nilai lama Hadir, nilai baru Tidak hadir, dan alasannya
```

**AC-PRS-07-02 — Hadir dengan surat dokter**
Rujukan: FR-PRS-07, BR-KOR-06.

```text
Given siswa B scan masuk pukul 07.20 dan berstatus Terlambat
When wali kelas mengoreksi siswa B menjadi Hadir dengan alasan "terlambat karena kontrol dokter, ada surat"
Then status siswa B menjadi Hadir
```

**AC-PRS-07-03 — Koreksi saat ada izin**
Rujukan: BR-KOR-09, BR-STS-03, UF-16 E3.

```text
Given siswa C memiliki Sakit yang disetujui untuk hari ini
When guru piket mengoreksi siswa C menjadi Hadir
Then panel memperingatkan bahwa status tetap Sakit selama data sakit berlaku
  And koreksi tersimpan dan status siswa C tetap Sakit
When data sakit siswa C dibatalkan
Then status siswa C menjadi Hadir sesuai koreksi
```

**AC-PRS-07-04 — Hapus koreksi**
Rujukan: BR-KOR-08, UF-16 E4.

```text
Given siswa D scan masuk pukul 06.50, lalu dikoreksi menjadi Tidak hadir
When wali kelas menghapus koreksi itu dengan alasan "salah siswa"
Then status siswa D kembali dihitung dari presensi, yaitu Hadir
  And log perubahan presensi mencatat penghapusan beserta alasannya
```

**AC-PRS-07-05 — Cakupan guru piket**
Rujukan: `HA-PRS-04`, UF-16 E1.

```text
Given guru piket tanpa role lain sudah login
When guru piket mencoba mengoreksi status tanggal kemarin
Then permintaan ditolak
```

**AC-PRS-07-06 — Bukan lewat koreksi**
Rujukan: BR-KOR-07, UF-16 E2.

```text
Given wali kelas membuka koreksi status siswa E
When wali kelas memilih kehadiran baru
Then pilihan yang tersedia hanya Hadir, Terlambat, dan Tidak hadir
  And panel menyediakan tautan ke input izin/sakit/dispensasi
```

### FS-PRS-08 — Mode darurat

| Item | Isi |
|---|---|
| Tujuan | Guru piket atau admin menahan pembentukan Alpa hari ini saat semua stasiun scan tidak dapat dipakai, lalu mengakhirinya setelah kehadiran tercatat. |
| Rilis | R1 |
| Requirement | FR-PRS-10 |
| Alur | UF-27 |
| Aktor dan hak | Admin dan guru piket, untuk hari ini (`HA-PRS-08`). |
| Aturan terkait | BR-DRT-01 s.d. BR-DRT-07, BR-KOR-10, BR-STS-02 |
| Status | DECISION (mode darurat, pelaku, akibat, berakhir otomatis; BR-DRT-06 dan BR-DRT-07, Session 4b); RECOMMENDATION (hanya hari ini, aktivasi ulang, dan rincian lain) |

**Prasyarat**

- Hari ini adalah hari sekolah.

**Input dan validasi**

| Isian | Wajib | Validasi |
|---|---|---|
| Alasan aktivasi | Ya | Teks, misalnya "Listrik padam sejak 05.30". |
| Alasan pengakhiran | Ya | Teks. |

**Perilaku**

1. **Aktifkan.** Guru piket atau admin mengaktifkan mode darurat untuk hari ini dengan alasan. Layar konfirmasi menjelaskan akibatnya dan mengingatkan bahwa internet putus bukan alasan, karena kiosk tetap bekerja offline (BR-DRT-01).
2. **Selama aktif** (BR-DRT-03):
   - siswa tanpa presensi tetap "belum hadir" walaupun sesi masuk sudah ditutup;
   - presensi per rombel tersedia (FS-PRS-09);
   - stasiun yang kembali berfungsi tetap dapat dipakai sesuai jendela scan;
   - di R2, pesan "tidak hadir" ditahan.
3. Dashboard dan halaman presensi menampilkan tanda mode darurat, waktu aktivasi, pelaku, dan alasannya.
4. **Akhiri.** Sebelum mengakhiri, sistem menampilkan jumlah siswa yang masih "belum hadir" per rombel. Setelah konfirmasi, siswa tanpa presensi dan tanpa izin/sakit/dispensasi menjadi Alpa (BR-DRT-05).
5. **Berakhir otomatis.** Bila tidak diakhiri, mode darurat berakhir otomatis pukul 23.59 WIB dengan tanda "berakhir otomatis" (BR-DRT-05). Di R2, pesan "tidak hadir" hari itu tidak dikirim (BR-DRT-06).
6. Di R2, waktu tunda pesan "tidak hadir" dihitung sejak mode darurat diakhiri (BR-DRT-06, DECISION Session 4b).
7. Mode darurat dapat diaktifkan lagi pada hari yang sama. Setiap periode dicatat. Tanggal yang pernah memakai mode darurat tidak menghasilkan kejadian "tidak scan pulang" (BR-DRT-07, DECISION Session 4b).
8. Mode darurat hanya untuk hari ini, tidak untuk tanggal lampau atau tanggal ke depan (BR-DRT-02).
9. Aktivasi dan pengakhiran, termasuk yang otomatis, dicatat di log (§4.4), dan status hari ini dihitung ulang.

**Keadaan kosong dan error**

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | Hari ini bukan hari sekolah. | Fitur tidak tersedia. |
| E2 | Mode darurat sudah aktif. | Tombol aktifkan tidak tersedia; yang tampil tombol akhiri. |
| E3 | Alasan kosong. | Ditolak. |

**Data dan log**

- Ditulis: periode mode darurat (tanggal, waktu aktif, pelaku dan alasan aktivasi, waktu berakhir, pelaku dan alasan pengakhiran, atau tanda berakhir otomatis).
- Log perubahan presensi: aktivasi dan pengakhiran.

**Parameter dan default**

| Parameter | Default | Diatur oleh | Dipastikan di |
|---|---|---|---|
| Jam berakhir otomatis | 23.59 WIB | Tetap | — (DECISION) |

**Di luar cakupan**

- Mode darurat untuk stasiun tertentu, rombel tertentu, atau tanggal lampau.
- Pemberitahuan mode darurat kepada orang tua.

**Catatan antarmuka awal**

- Tanda mode darurat tampil mencolok di semua halaman panel selama aktif.

**Acceptance criteria**

**AC-PRS-08-01 — Mengaktifkan mode darurat**
Rujukan: FR-PRS-10, `HA-PRS-08`, UF-27, BR-DRT-02, BR-DRT-03.

```text
Given semua stasiun mati karena listrik padam
When pukul 06.30 guru piket mengaktifkan mode darurat dengan alasan "Listrik padam"
Then dashboard menampilkan tanda mode darurat beserta waktu, pelaku, dan alasannya
  And pukul 08.30 siswa tanpa presensi masih "belum hadir"
  And presensi per rombel tersedia
  And log perubahan presensi mencatat aktivasi itu
```

**AC-PRS-08-02 — Mengakhiri mode darurat**
Rujukan: FR-PRS-10, UF-27, BR-DRT-05.

```text
Given mode darurat aktif dan 4 siswa 7A masih "belum hadir"
When guru piket memilih mengakhiri mode darurat
Then sistem menampilkan jumlah siswa yang masih "belum hadir" per rombel, termasuk 4 siswa 7A
When guru piket mengonfirmasi dengan alasan
Then siswa yang masih "belum hadir" dan tidak berizin menjadi Alpa
```

**AC-PRS-08-03 — Berakhir otomatis**
Rujukan: UF-27 E2, BR-DRT-05, BR-DRT-06.

```text
Given mode darurat aktif dan tidak diakhiri sampai malam
When pukul 23.59 WIB
Then mode darurat berakhir dengan tanda "berakhir otomatis"
  And siswa yang masih "belum hadir" dan tidak berizin menjadi Alpa
  And log mencatat pengakhiran oleh sistem
```

**AC-PRS-08-04 — Hanya hari ini**
Rujukan: `HA-PRS-08`, BR-DRT-02.

```text
Given guru piket sudah login
When guru piket mencoba mengaktifkan mode darurat untuk tanggal lain
Then permintaan ditolak
```

**AC-PRS-08-05 — Aktivasi ulang**
Rujukan: BR-DRT-03, BR-DRT-07.

```text
Given mode darurat aktif pukul 06.30–10.00, lalu listrik padam lagi pukul 11.00
When guru piket mengaktifkan mode darurat lagi pukul 11.05
Then siswa yang Alpa dan tanpa presensi kembali "belum hadir" sampai mode darurat diakhiri
  And kedua periode tercatat di log
```

### FS-PRS-09 — Presensi per rombel saat darurat

| Item | Isi |
|---|---|
| Tujuan | Staf mencatat kehadiran satu rombel sekaligus saat mode darurat aktif. |
| Rilis | R1 |
| Requirement | FR-PRS-06, FR-PRS-10 |
| Alur | UF-27 langkah 4–5 |
| Aktor dan hak | Admin (semua), wali kelas (rombel), guru piket (semua rombel, hari ini), dan guru BK (semua) (`HA-PRS-03`). |
| Aturan terkait | BR-DRT-04, BR-KOR-05, BR-KOR-11, BR-STS-02, BR-STS-03 |
| Status | DECISION (presensi per rombel hanya saat darurat; status dipilih staf); RECOMMENDATION (rincian) |

**Prasyarat**

- Mode darurat aktif hari ini (FS-PRS-08).

**Input dan validasi**

| Isian | Wajib | Validasi |
|---|---|---|
| Rombel | Ya | Rombel tahun ajaran aktif dalam cakupan. |
| Centang hadir per siswa | Minimal satu | Hanya siswa rombel itu yang hari ini hari sekolah dan belum memiliki presensi masuk. |
| Tanda terlambat per siswa | Tidak | Hanya untuk siswa yang dicentang hadir. |

**Perilaku**

1. Staf membuka satu rombel. Daftar berisi siswa rombel itu yang belum memiliki presensi masuk hari ini (BR-DRT-04).
2. Siswa yang memiliki izin/sakit/dispensasi yang disetujui tetap tampil dengan label izinnya, dan tetap dapat dicentang. Statusnya tetap mengikuti izin, dan penanda "izin dengan presensi masuk" muncul (BR-STS-03, BR-STS-07).
3. Staf mencentang siswa yang hadir dan menandai siswa yang terlambat, lalu menyimpan.
4. Untuk setiap siswa yang dicentang, sistem mencatat presensi manual masuk dengan alasan "darurat", jam penyimpanan, nama penginput, dan status pilihan staf: Hadir atau Terlambat. Status tidak dihitung dari jam (BR-DRT-04). Siswa yang tidak dicentang tidak berubah.
5. Bila seorang siswa sudah memiliki presensi masuk saat disimpan, misalnya dari stasiun yang kembali berfungsi atau dari staf lain, siswa itu dilewati dan dilaporkan.
6. Rombel yang sama dapat dibuka lagi. Daftarnya hanya berisi siswa yang tersisa.
7. Kesalahan centang diperbaiki dengan membatalkan presensi manual siswa itu (FS-PRS-06 bagian B).
8. Setiap siswa yang dicatat menghasilkan satu entri log dengan penanda kelompok yang sama (§4.4).
9. Setelah mode darurat berakhir, fitur ini tidak tersedia. Siswa yang belum tercatat dicatat satu per satu lewat FS-PRS-06 (UF-27 E3).

**Keadaan kosong dan error**

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | Mode darurat tidak aktif. | Fitur tidak tersedia (BR-KOR-05). |
| E2 | Semua siswa rombel sudah memiliki presensi masuk. | "Semua siswa rombel ini sudah tercatat." |
| E3 | Rombel di luar cakupan. | Ditolak. |
| E4 | Tidak ada siswa yang dicentang. | Ditolak. |

**Data dan log**

- Ditulis: presensi manual masuk dengan penanda darurat dan status pilihan staf (`05` §14).
- Log perubahan presensi: satu entri per siswa, dengan penanda kelompok yang sama.

**Parameter dan default**

Tidak ada.

**Di luar cakupan**

- Presensi pulang per rombel.
- Presensi per rombel untuk tanggal lampau.

**Catatan antarmuka awal**

- Daftar berbentuk centang dengan foto kecil, tombol "centang semua", dan tanda terlambat per baris.

**Acceptance criteria**

**AC-PRS-09-01 — Mencatat satu rombel**
Rujukan: FR-PRS-10, `HA-PRS-03`, UF-27, BR-DRT-04.

```text
Given mode darurat aktif dan rombel 7A berisi 32 siswa tanpa presensi masuk
When wali kelas 7A mencentang 28 siswa, menandai 2 di antaranya terlambat, lalu menyimpan
Then 26 siswa berstatus Hadir dan 2 siswa berstatus Terlambat
  And setiap presensi tercatat sebagai presensi manual dengan alasan "darurat"
  And 4 siswa yang tidak dicentang tetap "belum hadir"
When mode darurat diakhiri
Then 4 siswa itu menjadi Alpa, kecuali yang memiliki izin/sakit/dispensasi yang disetujui
```

**AC-PRS-09-02 — Tidak tersedia di luar darurat**
Rujukan: FR-PRS-06, BR-KOR-05.

```text
Given mode darurat tidak aktif
When wali kelas membuka presensi per rombel
Then fitur itu tidak tersedia
```

**AC-PRS-09-03 — Siswa yang sudah tercatat dilewati**
Rujukan: BR-DRT-03, BR-SCN-04.

```text
Given wali kelas 7A membuka daftar presensi per rombel pukul 07.40
  And pukul 07.45 siswa A scan masuk di stasiun yang kembali berfungsi, masih di jendela masuk
When wali kelas menyimpan daftar yang mencentang siswa A pukul 07.50
Then siswa A dilewati dan dilaporkan, dan presensi masuknya tetap scan pukul 07.45
```

**AC-PRS-09-04 — Cakupan rombel**
Rujukan: `HA-PRS-03`.

```text
Given mode darurat aktif
When wali kelas 7A membuka presensi per rombel 7B
Then permintaan ditolak
When guru piket membuka presensi per rombel 7B
Then daftar 7B tampil
```

### FS-PRS-10 — Batas mundur

| Item | Isi |
|---|---|
| Tujuan | Admin mengatur berapa hari ke belakang data presensi dan izin masih boleh diubah oleh staf selain admin. |
| Rilis | R1 |
| Requirement | FR-PRS-11 |
| Alur | UF-01 |
| Aktor dan hak | Admin (`HA-PRS-09`). |
| Aturan terkait | BR-MUN-01 s.d. BR-MUN-05, BR-IZN-10 |
| Status | DECISION (nilai default, cakupan, admin tidak dibatasi); RECOMMENDATION (rentang nilai, berlaku langsung) |

**Prasyarat**

- Admin sudah login.

**Input dan validasi**

| Isian | Wajib | Validasi |
|---|---|---|
| Batas mundur (hari) | Ya | Bilangan bulat 0–31. Nilai 0 berarti hanya hari ini. |

**Perilaku**

1. Perubahan langsung berlaku dan dicatat di log (BR-MUN-05, §4.4).
2. Formulir yang dibatasi batas mundur menampilkan rentang tanggal yang masih boleh (§4.2).
3. Penerapan batas mundur per fitur:

   | Fitur | Admin | Wali kelas | Guru piket | Guru BK | Siswa |
   |---|---|---|---|---|---|
   | Presensi manual dan pembatalannya (FS-PRS-06) | Tanpa batas | Batas mundur | Hari ini | Batas mundur | — |
   | Koreksi dan penghapusannya (FS-PRS-07) | Tanpa batas | Batas mundur | Hari ini | Batas mundur | — |
   | Input izin/sakit/dispensasi (FS-IZN-02, FS-IZN-03) | Tanpa batas | Batas mundur | Batas mundur | Batas mundur | — |
   | Verifikasi pengajuan (FS-IZN-04) | Tanpa batas | Tidak dibatasi untuk pengajuan yang dibuat dalam batas mundur (BR-IZN-10) | Sama | Sama | — |
   | Ubah keputusan (FS-IZN-05) | Tanpa batas | Batas mundur pada tanggal terdampak | Sama | Sama | — |
   | Pengajuan izin/sakit (FS-IZN-01) | — | — | — | — | Batas mundur |

4. Tanggal ke depan: presensi manual dan koreksi tidak boleh (BR-MUN-04); izin/sakit/dispensasi boleh selama di tahun ajaran aktif (BR-IZN-11).

**Keadaan kosong dan error**

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | Nilai di luar rentang. | Ditolak. |

**Data dan log**

- Ditulis: pengaturan batas mundur.
- Log perubahan presensi: nilai lama, nilai baru, pelaku, dan waktu.

**Parameter dan default**

| Parameter | Default | Diatur oleh | Dipastikan di |
|---|---|---|---|
| Batas mundur | 7 hari | Admin | — (DECISION) |
| Rentang nilai yang boleh | 0–31 hari | Sistem | Session 9 |

**Di luar cakupan**

- Batas mundur berbeda per role.

**Catatan antarmuka awal**

- Contoh rentang tanggal hasil pengaturan ditampilkan di samping isian.

**Acceptance criteria**

**AC-PRS-10-01 — Mengubah batas mundur**
Rujukan: FR-PRS-11, `HA-PRS-09`, BR-MUN-05.

```text
Given batas mundur 7 hari dan hari ini 13 Oktober 2026
When admin mengubah batas mundur menjadi 3 hari
Then wali kelas tidak dapat lagi mengoreksi status tanggal 9 Oktober 2026
  And wali kelas masih dapat mengoreksi status tanggal 10 Oktober 2026
  And admin tetap dapat mengoreksi tanggal berapa pun
  And log mencatat perubahan dari 7 menjadi 3
```

### FS-PRS-11 — Log perubahan presensi

| Item | Isi |
|---|---|
| Tujuan | Staf yang berhak melihat siapa mengubah data presensi, kapan, apa yang berubah, dan alasannya. |
| Rilis | R1 |
| Requirement | FR-PRS-07 |
| Alur | UF-16 |
| Aktor dan hak | Admin, guru BK, dan pimpinan (semua), wali kelas (rombel), guru piket (hari ini) (`HA-PRS-06`). |
| Aturan terkait | BR-KOR-10, BR-MUN-03 |
| Status | DECISION (setiap perubahan presensi tercatat; cakupan log yang diperluas, Session 5); RECOMMENDATION (pembagian hak lihat) |

**Prasyarat**

- Ada perubahan yang tercatat (§4.4).

**Input dan validasi**

| Isian | Wajib | Validasi |
|---|---|---|
| Saringan | Tidak | Tanggal presensi, rentang waktu perubahan, rombel, siswa, jenis perubahan, dan pelaku. |

**Perilaku**

1. Log menampilkan entri terbaru lebih dulu. Setiap entri berisi waktu, pelaku, jenis perubahan, siswa (bila ada), tanggal presensi, data lama, data baru, dan alasan.
2. Cakupan:
   - wali kelas melihat entri untuk siswa rombelnya (§4.1);
   - guru piket melihat entri dengan tanggal presensi hari ini;
   - entri tanpa siswa, seperti jadwal hari ini, mode darurat, kalender, dan batas mundur, terlihat oleh semua pemegang `HA-PRS-06` untuk tanggal dalam cakupannya.
3. Riwayat siswa (FS-LAP-04) dan daftar presensi rombel (FS-LAP-02) menyediakan tautan ke entri log per siswa per tanggal.
4. Log hanya dapat dibaca. Entri tidak dapat diubah atau dihapus dari aplikasi.

**Keadaan kosong dan error**

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | Tidak ada entri yang cocok dengan saringan. | "Tidak ada perubahan." |

**Data dan log**

- Dibaca: log perubahan presensi.

**Parameter dan default**

Tidak ada.

**Di luar cakupan**

- Log akses atau log siapa yang melihat data (OQ-17, Session 9).
- Log aktivitas akun (Session 9).
- Export log di R1. Export log ke CSV termasuk R2 (`13` LP-07).

**Catatan antarmuka awal**

- Data lama dan data baru ditampilkan berdampingan.

**Acceptance criteria**

**AC-PRS-11-01 — Entri koreksi**
Rujukan: FR-PRS-07, `HA-PRS-06`, BR-KOR-10.

```text
Given guru BK mengoreksi siswa A dari Terlambat menjadi Hadir dengan alasan "surat dokter"
When admin membuka log perubahan presensi
Then entri terbaru berisi nama guru BK, waktu, siswa A, tanggal presensi, nilai lama Terlambat, nilai baru Hadir, dan alasan "surat dokter"
```

**AC-PRS-11-02 — Cakupan wali kelas dan guru piket**
Rujukan: `HA-PRS-06`.

```text
Given log berisi entri untuk siswa 7A dan 7B pada hari ini dan kemarin
When wali kelas 7A membuka log
Then hanya entri siswa 7A yang tampil, untuk hari ini dan kemarin
When guru piket membuka log
Then hanya entri dengan tanggal presensi hari ini yang tampil, untuk semua rombel
```

**AC-PRS-11-03 — Log tidak dapat diubah**
Rujukan: BR-KOR-10.

```text
Given admin membuka log perubahan presensi
When admin mencari cara mengubah atau menghapus entri
Then tidak ada fitur untuk mengubah atau menghapus entri log
```

## 9. Izin, sakit, dan dispensasi (IZN)

Status data izin/sakit/dispensasi mengikuti diagram di `03` §6: menunggu, disetujui, ditolak, dan dibatalkan. Hanya data yang disetujui yang memengaruhi status presensi (BR-IZN-06).

Validasi tanggal yang berlaku untuk semua fitur di bagian ini:

| Validasi | Aturan |
|---|---|
| Rentang | Tanggal mulai ≤ tanggal selesai. Rentang harus memuat paling sedikit satu hari sekolah bagi siswa. Hanya hari sekolah di dalam rentang yang terdampak (BR-IZN-02). |
| Tanggal lampau | Untuk selain admin, setiap tanggal lampau yang terdampak harus berada dalam batas mundur (§4.2, BR-MUN-02). |
| Tanggal ke depan | Boleh, selama masih di tahun ajaran aktif (BR-IZN-11, DECISION Session 4b). |
| Tumpang tindih | Siswa tidak boleh memiliki dua data berstatus menunggu atau disetujui yang mencakup tanggal yang sama. Sistem menolak dan menunjukkan data yang sudah ada (BR-IZN-07, DECISION Session 4b). |

### FS-IZN-01 — Pengajuan izin/sakit oleh siswa

| Item | Isi |
|---|---|
| Tujuan | Siswa mengajukan izin atau sakit lewat portal, memantau statusnya, dan membatalkannya selama masih menunggu. |
| Rilis | R1 |
| Requirement | FR-IZN-01, FR-IZN-04, FR-IZN-05 |
| Alur | UF-17 |
| Aktor dan hak | Siswa, untuk dirinya sendiri (`HA-IZN-01`, `HA-IZN-04`, `HA-IZN-05`). |
| Aturan terkait | BR-IZN-01 s.d. BR-IZN-03, BR-IZN-06, BR-IZN-07, BR-IZN-11, BR-IZN-12, BR-MUN-01, BR-MUN-02 |
| Status | DECISION (pengajuan siswa; tanggal lampau dalam batas mundur; bukan dispensasi; tumpang tindih dan tanggal ke depan, Session 4b; paling banyak 3 lampiran, Session 5); RECOMMENDATION (rentang tanggal, lampiran opsional, pembatalan oleh siswa) |

**Prasyarat**

- Siswa sudah login di portal.

**Input dan validasi**

| Isian | Wajib | Validasi |
|---|---|---|
| Jenis | Ya | Izin atau Sakit. Dispensasi tidak tersedia (BR-IZN-03). |
| Tanggal mulai dan selesai | Ya | Validasi tanggal di awal §9. |
| Keterangan | Ya | Teks. |
| Lampiran surat | Tidak | Paling banyak 3 file (DECISION, Session 5). Format dan ukurannya ditetapkan di Session 9 (§4.9). |

**Perilaku**

1. Sebelum menyimpan, portal menampilkan hari sekolah yang terdampak, misalnya "2 hari sekolah: Senin 12 dan Selasa 13 Oktober".
2. Sistem menyimpan pengajuan dengan status "menunggu". Pengajuan itu muncul di daftar verifikasi wali kelas, guru piket, guru BK, dan admin (FS-IZN-04).
3. Pengajuan yang menunggu tidak mengubah status presensi (BR-IZN-06).
4. Siswa melihat daftar pengajuannya beserta status, keputusan terbaru, dan catatan verifikasi, tanpa nama staf (FR-IZN-04, §2.3).
5. Selama status masih "menunggu", siswa dapat membatalkan pengajuan. Statusnya menjadi "dibatalkan". Setelah diverifikasi, siswa menghubungi wali kelas untuk perubahan.
6. Pengajuan tidak dapat diubah. Siswa membatalkannya, lalu mengajukan ulang.

**Keadaan kosong dan error**

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | Tanggal lampau di luar batas mundur (UF-17 E2). | Ditolak: "Tanggal ini sudah lewat batas pengajuan. Hubungi wali kelas." |
| E2 | Tumpang tindih dengan data yang menunggu atau disetujui (UF-17 E3). | Ditolak dengan menunjukkan data yang sudah ada. |
| E3 | Rentang tidak memuat hari sekolah. | "Tidak ada hari sekolah pada tanggal yang dipilih." |
| E4 | Tanggal selesai di luar tahun ajaran aktif. | Ditolak. |
| E5 | Lampiran tidak valid. | Ditolak, dan isian lain tetap terisi. |
| E6 | Siswa belum pernah mengajukan. | Daftar kosong dengan tombol "Ajukan izin/sakit". |

**Data dan log**

- Ditulis: data izin/sakit (siswa, jenis, tanggal mulai dan selesai, keterangan, lampiran, sumber "siswa", status menunggu, pengaju, dan waktu).
- Log: pengajuan dan pembatalan oleh siswa tidak ditulis ke log perubahan presensi, karena tidak mengubah status. Riwayat statusnya tersimpan di data izin itu sendiri (`05` §14).

**Parameter dan default**

| Parameter | Default | Diatur oleh | Dipastikan di |
|---|---|---|---|
| Format dan ukuran lampiran | Belum ditetapkan | Sistem | Session 9 |

**Di luar cakupan**

- Pengajuan dispensasi oleh siswa.
- Mengubah pengajuan yang sudah dikirim.
- Pemberitahuan ke orang tua atau staf. Notifikasi WA jenis "izin" ada di R2 (BR-WA-07).

**Catatan antarmuka awal**

- Formulir sederhana untuk ponsel, dengan pilihan "satu hari" atau "beberapa hari".

**Acceptance criteria**

**AC-IZN-01-01 — Sakit untuk tanggal lampau**
Rujukan: FR-IZN-01, `HA-IZN-01`, UF-17, BR-IZN-03, BR-MUN-01, `05` §13 contoh 14.

```text
Given hari ini Rabu, 14 Oktober 2026 dan batas mundur 7 hari
When siswa mengajukan Sakit untuk Senin–Selasa, 12–13 Oktober 2026 dengan keterangan dan foto surat dokter
Then pengajuan tersimpan dengan status "menunggu"
  And status presensi siswa pada 12–13 Oktober belum berubah
When wali kelas menyetujui pengajuan itu
Then status siswa pada 12 dan 13 Oktober menjadi Sakit
```

**AC-IZN-01-02 — Di luar batas mundur**
Rujukan: UF-17 E2, BR-MUN-02.

```text
Given hari ini 13 Oktober 2026 dan batas mundur 7 hari
When siswa mengajukan Izin untuk 5 Oktober 2026
Then pengajuan ditolak dengan pesan untuk menghubungi wali kelas
```

**AC-IZN-01-03 — Tumpang tindih**
Rujukan: UF-17 E3, BR-IZN-07.

```text
Given siswa memiliki pengajuan Izin yang menunggu untuk 15–16 Oktober 2026
When siswa mengajukan Sakit untuk 16–17 Oktober 2026
Then pengajuan ditolak dengan menunjukkan pengajuan Izin 15–16 Oktober
```

**AC-IZN-01-04 — Tanggal ke depan**
Rujukan: BR-IZN-11.

```text
Given tahun ajaran aktif berakhir 30 Juni 2027
When siswa mengajukan Izin untuk acara keluarga pada 22 Oktober 2026
Then pengajuan tersimpan dengan status "menunggu"
When siswa mengajukan Izin untuk 5 Juli 2027
Then pengajuan ditolak karena di luar tahun ajaran aktif
```

**AC-IZN-01-05 — Membatalkan pengajuan**
Rujukan: FR-IZN-04, `HA-IZN-01`, UF-17.

```text
Given siswa memiliki pengajuan yang menunggu
When siswa membatalkannya
Then status pengajuan menjadi "dibatalkan"
  And pengajuan itu tidak lagi muncul di daftar verifikasi staf
```

**AC-IZN-01-06 — Pengajuan menunggu tidak mengubah status**
Rujukan: BR-IZN-06, `05` §6.

```text
Given siswa mengajukan Izin untuk hari ini dan belum diverifikasi
  And siswa tidak memiliki presensi masuk
When sesi masuk ditutup pukul 08.00
Then status siswa adalah Alpa
```

**AC-IZN-01-07 — Hanya tanggal hari sekolah**
Rujukan: BR-IZN-02.

```text
Given siswa memilih rentang Sabtu–Minggu, 17–18 Oktober 2026
When pratinjau tampil
Then hanya Sabtu, 17 Oktober 2026 yang disebut sebagai hari sekolah terdampak
```

### FS-IZN-02 — Input izin/sakit/dispensasi oleh staf

| Item | Isi |
|---|---|
| Tujuan | Staf mencatat izin, sakit, atau dispensasi atas nama satu siswa, dan data itu langsung disetujui. |
| Rilis | R1 |
| Requirement | FR-IZN-02, FR-IZN-07 |
| Alur | UF-19 |
| Aktor dan hak | Admin (semua), wali kelas (rombel), guru piket (semua), dan guru BK (semua) (`HA-IZN-02`). Selain admin, dibatasi batas mundur. |
| Aturan terkait | BR-IZN-01, BR-IZN-02, BR-IZN-04, BR-IZN-06, BR-IZN-07, BR-IZN-11, BR-IZN-12, BR-STS-03, BR-MUN-02 |
| Status | DECISION (pelaku; langsung disetujui; dispensasi; tumpang tindih dan tanggal ke depan, Session 4b); RECOMMENDATION (rincian) |

**Prasyarat**

- Siswa dalam cakupan pengguna.

**Input dan validasi**

| Isian | Wajib | Validasi |
|---|---|---|
| Siswa | Ya | Dicari berdasarkan nama, NISN, atau rombel dalam cakupan. |
| Jenis | Ya | Izin, Sakit, atau Dispensasi. |
| Tanggal mulai dan selesai | Ya | Validasi tanggal di awal §9. |
| Keterangan | Ya | Teks, termasuk sumber kabar, misalnya "telepon ibu pukul 06.30". |
| Lampiran | Tidak | Foto surat atau surat tugas, paling banyak 3 file (DECISION, Session 5). |

**Perilaku**

1. Sistem menyimpan data dengan status "disetujui". Penginput tercatat sebagai verifikator (BR-IZN-04).
2. Status presensi tanggal yang terdampak dihitung ulang, walaupun siswa sudah memiliki presensi masuk atau koreksi (BR-STS-03, §4.5).
3. Log perubahan presensi mencatat input itu (§4.4).
4. Bila siswa memiliki pengajuan yang menunggu untuk tanggal yang sama, input ditolak, dan sistem menyediakan tautan untuk memverifikasi pengajuan itu (UF-19 E3).

**Keadaan kosong dan error**

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | Tanggal lampau di luar batas mundur, untuk selain admin (UF-19 E1). | Ditolak dengan alasan. |
| E2 | Tumpang tindih dengan data yang menunggu atau disetujui (UF-19 E3). | Ditolak dengan menunjukkan data yang ada. |
| E3 | Siswa di luar cakupan. | Siswa tidak muncul di pencarian, dan permintaan langsung ditolak. |
| E4 | Rentang tidak memuat hari sekolah. | Ditolak. |

**Data dan log**

- Ditulis: data izin/sakit/dispensasi dengan sumber "staf", status disetujui, penginput sebagai pengaju dan verifikator, waktu, dan lampiran.
- Log perubahan presensi: input staf.

**Parameter dan default**

Tidak ada.

**Di luar cakupan**

- Input untuk banyak siswa sekaligus selain dispensasi (FS-IZN-03).
- Input dengan status "menunggu" oleh staf.

**Catatan antarmuka awal**

- Dapat dibuka dari daftar presensi rombel, riwayat siswa, atau koreksi status (FS-PRS-07 E2).

**Acceptance criteria**

**AC-IZN-02-01 — Sakit kemarin berdasarkan surat**
Rujukan: FR-IZN-02, `HA-IZN-02`, UF-19 E1, BR-IZN-04.

```text
Given siswa A di rombel 7A berstatus Alpa pada 12 Oktober 2026
When wali kelas 7A menginput Sakit untuk siswa A pada 12 Oktober 2026 dengan keterangan "surat dokter dibawa siswa"
Then data tersimpan dengan status "disetujui" dan wali kelas sebagai verifikator
  And status siswa A pada 12 Oktober 2026 menjadi Sakit
```

**AC-IZN-02-02 — Cakupan guru piket dan wali kelas**
Rujukan: `HA-IZN-02`, `02` §5.

```text
Given orang tua siswa B di rombel 8C menelepon guru piket
When guru piket menginput Izin untuk siswa B hari ini
Then data tersimpan dengan status "disetujui"
When wali kelas 7A mencoba menginput Izin untuk siswa B
Then permintaan ditolak
```

**AC-IZN-02-03 — Ada pengajuan yang menunggu**
Rujukan: UF-19 E3, BR-IZN-07.

```text
Given siswa C memiliki pengajuan Sakit yang menunggu untuk hari ini
When guru BK menginput Sakit untuk siswa C hari ini
Then input ditolak
  And sistem menyediakan tautan untuk memverifikasi pengajuan siswa C
```

**AC-IZN-02-04 — Izin menang atas presensi masuk**
Rujukan: BR-STS-03, UF-19.

```text
Given siswa D scan masuk pukul 06.50 lalu dijemput orang tua pukul 08.30 untuk urusan keluarga
When wali kelas menginput Izin untuk siswa D hari ini
Then status siswa D hari ini menjadi Izin
  And presensi masuk pukul 06.50 tetap tersimpan dan penanda "izin dengan presensi masuk" tampil
```

### FS-IZN-03 — Dispensasi massal

| Item | Isi |
|---|---|
| Tujuan | Staf mencatat dispensasi untuk banyak siswa sekaligus, misalnya untuk lomba atau study tour. |
| Rilis | R1 |
| Requirement | FR-IZN-07 |
| Alur | UF-19 E2 |
| Aktor dan hak | Admin (semua), wali kelas (rombel), guru piket (semua), dan guru BK (semua) (`HA-IZN-02`). |
| Aturan terkait | BR-IZN-04, BR-IZN-05, BR-IZN-07, BR-REK-02, BR-REK-03 |
| Status | DECISION (input massal; bentrok dilewati dan dilaporkan, Session 4b; lampiran bersama paling banyak 3 file, Session 6); RECOMMENDATION (cara memilih siswa) |

**Prasyarat**

- Siswa yang dipilih berada dalam cakupan pengguna.

**Input dan validasi**

| Isian | Wajib | Validasi |
|---|---|---|
| Pilihan siswa | Ya | Salah satu cara: siswa terpilih (boleh lintas rombel), satu rombel, atau satu tingkat. Hanya siswa dalam cakupan yang dapat dipilih. Wali kelas hanya dapat memilih siswa rombelnya. |
| Tanggal mulai dan selesai | Ya | Validasi tanggal di awal §9. |
| Keterangan | Ya | Nama kegiatan, misalnya "Lomba OSN tingkat kabupaten". |
| Lampiran | Tidak | Surat tugas, paling banyak 3 file (DECISION, Session 6). Lampiran berlaku untuk seluruh kelompok (`06` §10.4). |

**Perilaku**

1. Sistem menampilkan pratinjau berisi:
   - siswa yang akan dicatat;
   - siswa yang bentrok dengan data menunggu atau disetujui, beserta data yang sudah ada (BR-IZN-07);
   - siswa yang tidak memiliki hari sekolah dalam rentang itu, misalnya karena rombelnya libur.
2. Setelah konfirmasi, sistem membuat satu data dispensasi per siswa yang tidak bentrok. Semua data berstatus disetujui dan memakai penanda kelompok yang sama (BR-IZN-05).
3. Siswa yang bentrok atau tidak memiliki hari sekolah dilewati. Sistem menampilkan laporan: jumlah tersimpan, jumlah dilewati, dan alasan setiap siswa yang dilewati (DECISION, Session 4b).
4. Status siswa yang dicatat dihitung ulang (§4.5), dan log mencatat satu entri per siswa dengan penanda kelompok (§4.4).
5. Kelompok dapat diubah keputusannya per siswa atau sekaligus satu kelompok (FS-IZN-05).

**Keadaan kosong dan error**

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | Tidak ada siswa yang dipilih. | Ditolak. |
| E2 | Semua siswa terpilih bentrok atau tidak memiliki hari sekolah. | Tidak ada yang disimpan; laporan tetap ditampilkan. |
| E3 | Tanggal lampau di luar batas mundur, untuk selain admin. | Ditolak untuk seluruh input. |

**Data dan log**

- Ditulis: data dispensasi per siswa, dengan penanda kelompok, keterangan, dan lampiran bersama.
- Log perubahan presensi: satu entri per siswa.

**Parameter dan default**

Tidak ada.

**Di luar cakupan**

- Izin atau sakit massal.
- Mengganti data izin/sakit siswa yang bentrok secara otomatis.

**Catatan antarmuka awal**

- Pratinjau memisahkan siswa yang akan dicatat dan siswa yang dilewati.

**Acceptance criteria**

**AC-IZN-03-01 — Dispensasi lintas rombel**
Rujukan: FR-IZN-07, `HA-IZN-02`, UF-19 E2, BR-IZN-05, BR-REK-03, `05` §13 contoh 15.

```text
Given guru BK memilih lima siswa dari tiga rombel untuk lomba pada 13 Oktober 2026
When guru BK mengonfirmasi dispensasi massal
Then kelima siswa berstatus Dispensasi pada tanggal itu
  And kelima data memakai penanda kelompok yang sama
  And persentase kehadiran kelima siswa tidak turun
```

**AC-IZN-03-02 — Siswa yang bentrok dilewati**
Rujukan: BR-IZN-07, §2.2.

```text
Given guru BK memilih rombel 8A (30 siswa) untuk study tour 15–16 Oktober 2026
  And siswa E di 8A memiliki Sakit yang disetujui untuk 15 Oktober 2026
When guru BK mengonfirmasi dispensasi massal
Then 29 siswa mendapat dispensasi
  And siswa E dilewati, dan laporan menunjukkan data Sakit siswa E sebagai alasannya
```

**AC-IZN-03-03 — Cakupan wali kelas**
Rujukan: `HA-IZN-02`, BR-IZN-05.

```text
Given wali kelas 7A membuka dispensasi massal
When wali kelas memilih cara pilih siswa
Then wali kelas hanya dapat memilih siswa rombel 7A
  And pilihan "satu tingkat" tidak tersedia baginya
```

### FS-IZN-04 — Verifikasi pengajuan

| Item | Isi |
|---|---|
| Tujuan | Staf menyetujui atau menolak pengajuan izin/sakit dari siswa. |
| Rilis | R1 |
| Requirement | FR-IZN-03, FR-IZN-05 |
| Alur | UF-18 |
| Aktor dan hak | Admin (semua), wali kelas (rombel), guru piket (semua), dan guru BK (semua) (`HA-IZN-03`). Lampiran dibuka dengan `HA-IZN-05`. |
| Aturan terkait | BR-IZN-06, BR-IZN-08, BR-IZN-10, BR-STS-03 |
| Status | DECISION (verifikasi; pelaku; hanya yang disetujui berlaku; verifikasi setelah batas mundur, Session 4b); RECOMMENDATION (catatan wajib saat menolak; keputusan pertama berlaku) |

**Prasyarat**

- Ada pengajuan berstatus menunggu dalam cakupan pengguna.

**Input dan validasi**

| Isian | Wajib | Validasi |
|---|---|---|
| Keputusan | Ya | Setujui atau tolak. |
| Catatan | Ya, saat menolak | Teks. Catatan dapat dibaca siswa (§4.3). |

**Perilaku**

1. Daftar verifikasi berisi pengajuan menunggu dalam cakupan, dengan yang paling lama di atas. Setiap baris menampilkan siswa, rombel, jenis, rentang, jumlah hari sekolah terdampak, waktu diajukan, dan tanda lampiran.
2. Jumlah pengajuan yang menunggu tampil di menu dan di dashboard (FS-LAP-01).
3. Detail pengajuan menampilkan keterangan, lampiran, dan status presensi siswa pada tanggal terdampak. Contohnya siswa sudah scan masuk pada salah satu tanggal itu.
4. **Setujui:** status menjadi "disetujui", verifikator dan waktunya dicatat, dan status presensi dihitung ulang. Status Izin atau Sakit juga berlaku bila sebelumnya Alpa, Hadir, atau Terlambat (BR-STS-03).
5. **Tolak:** status menjadi "ditolak" dengan catatan. Status presensi tidak berubah (UF-18 langkah 5).
6. Keputusan yang tersimpan lebih dulu yang berlaku. Staf kedua mendapat pesan bahwa pengajuan sudah diverifikasi, beserta nama verifikatornya (BR-IZN-08).
7. Batas mundur tidak menghalangi verifikasi pengajuan yang dibuat dalam batas mundur (BR-IZN-10, DECISION Session 4b).
8. Siswa melihat keputusan dan catatannya tanpa nama staf (§2.3). Keputusan dicatat di log (§4.4).

**Keadaan kosong dan error**

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | Tidak ada pengajuan yang menunggu. | "Tidak ada pengajuan yang menunggu." |
| E2 | Pengajuan sudah diverifikasi staf lain (UF-18 E1). | Ditolak dengan keputusan yang ada dan nama verifikatornya. |
| E3 | Pengajuan dibatalkan siswa saat sedang dibuka. | Ditolak: "Pengajuan sudah dibatalkan siswa." |
| E4 | Menolak tanpa catatan. | Ditolak. |

**Data dan log**

- Ditulis: status pengajuan, verifikator, waktu, dan catatan.
- Log perubahan presensi: persetujuan dan penolakan.

**Parameter dan default**

Tidak ada.

**Di luar cakupan**

- Menyetujui sebagian tanggal saja. Staf menyetujui, lalu memendekkan rentang (FS-IZN-05).
- Kedaluwarsa otomatis untuk pengajuan yang lama tidak diverifikasi (§2.2, BR-IZN-10).

**Catatan antarmuka awal**

- Lampiran dapat dilihat langsung di halaman detail tanpa diunduh, bila formatnya memungkinkan.

**Acceptance criteria**

**AC-IZN-04-01 — Menyetujui pengajuan**
Rujukan: FR-IZN-03, `HA-IZN-03`, UF-18, AC-03.

```text
Given siswa A berstatus Alpa hari ini dan memiliki pengajuan Izin yang menunggu untuk hari ini
When wali kelas menyetujui pengajuan itu
Then status siswa A menjadi Izin tanpa langkah tambahan
  And siswa A melihat status "disetujui" di portal, tanpa nama wali kelas
```

**AC-IZN-04-02 — Menolak pengajuan**
Rujukan: FR-IZN-03, UF-18 langkah 3 dan 5, BR-IZN-08.

```text
Given siswa B memiliki pengajuan Sakit yang menunggu
When guru BK menolak tanpa catatan
Then penolakan ditolak sistem karena catatan wajib diisi
When guru BK menolak dengan catatan "Surat tidak terbaca, mohon kirim ulang"
Then status pengajuan menjadi "ditolak" dan status presensi siswa B tidak berubah
  And siswa B melihat catatan itu di portal
```

**AC-IZN-04-03 — Dua verifikator bersamaan**
Rujukan: UF-18 E1, BR-IZN-08.

```text
Given wali kelas dan guru piket membuka pengajuan yang sama
When wali kelas menyetujui lebih dulu, lalu guru piket menolak
Then keputusan wali kelas yang berlaku
  And guru piket mendapat pesan bahwa pengajuan sudah diverifikasi oleh wali kelas
```

**AC-IZN-04-04 — Verifikasi setelah batas mundur**
Rujukan: UF-18 E2, BR-IZN-10.

```text
Given siswa C mengajukan Sakit pada 6 Oktober 2026 untuk 5–6 Oktober 2026, dalam batas mundur
  And pengajuan itu belum diverifikasi sampai 15 Oktober 2026
When wali kelas menyetujuinya pada 15 Oktober 2026
Then persetujuan tersimpan dan status siswa C pada 5–6 Oktober menjadi Sakit
```

**AC-IZN-04-05 — Cakupan daftar verifikasi**
Rujukan: `HA-IZN-03`.

```text
Given ada pengajuan menunggu dari siswa 7A dan 7B
When wali kelas 7A membuka daftar verifikasi
Then hanya pengajuan siswa 7A yang tampil
When guru piket membuka daftar verifikasi
Then pengajuan siswa 7A dan 7B tampil
```

### FS-IZN-05 — Ubah keputusan

| Item | Isi |
|---|---|
| Tujuan | Staf yang berhak membatalkan data yang disetujui, memendekkan rentangnya, atau mengubah penolakan menjadi persetujuan, untuk satu data atau satu kelompok dispensasi. |
| Rilis | R1 |
| Requirement | FR-IZN-06 |
| Alur | UF-29 |
| Aktor dan hak | Admin (semua), wali kelas (rombel), guru piket (semua), dan guru BK (semua) (`HA-IZN-06`). |
| Aturan terkait | BR-IZN-09, BR-IZN-10, BR-MUN-02, BR-MUN-03, BR-STS-03 |
| Status | DECISION (ubah keputusan dengan alasan; perubahan per kelompok, Session 4b); RECOMMENDATION (perpanjang dan ganti jenis lewat data baru) |

**Prasyarat**

- Data berstatus disetujui atau ditolak, dan siswanya dalam cakupan.

**Input dan validasi**

| Isian | Wajib | Validasi |
|---|---|---|
| Tindakan | Ya | Batalkan (untuk data disetujui), perpendek rentang (untuk data disetujui), atau ubah penolakan menjadi persetujuan (untuk data ditolak). |
| Rentang baru | Ya, untuk perpendek | Berada di dalam rentang lama, tidak kosong, dan memuat paling sedikit satu hari sekolah. Boleh dipendekkan dari awal maupun dari akhir. |
| Alasan | Ya | Teks. Alasan dapat dibaca siswa (§4.3). |
| Cakupan perubahan | Ya, untuk data dari dispensasi massal | Satu siswa, atau semua data dalam kelompok yang masih berstatus disetujui. |

**Perilaku**

1. Staf membuka data izin/sakit/dispensasi, memilih tindakan, lalu mengisi alasan (UF-29).
2. **Batas mundur** berlaku pada tanggal yang statusnya berubah karena tindakan itu (BR-IZN-10), kecuali untuk admin (BR-MUN-03):
   - batalkan: semua tanggal lampau dalam rentang data;
   - perpendek: tanggal lampau yang dikeluarkan dari rentang;
   - ubah penolakan menjadi persetujuan: semua tanggal lampau dalam rentang data.

   Tanggal ke depan tidak dibatasi.
3. Ubah penolakan menjadi persetujuan tetap mengikuti aturan tumpang tindih (BR-IZN-07).
4. Sistem menyimpan keputusan baru dan riwayat keputusannya, mencatatnya di log, dan menghitung ulang status tanggal yang terdampak (§4.4, §4.5).
5. **Per kelompok** (DECISION, Session 4b): untuk data dari dispensasi massal, staf dapat menerapkan batalkan atau perpendek ke semua data dalam kelompok yang masih berstatus disetujui, dengan satu alasan. Tindakan ini hanya tersedia bila semua siswa dalam kelompok berada dalam cakupan staf. Bila tidak, staf mengubah data satu per satu. Setiap data tetap tercatat di log.
6. Siswa melihat keputusan terbaru di portal, tanpa nama staf (§2.3).
7. Memperpanjang rentang atau mengganti jenis tidak tersedia. Staf membatalkan data lama, lalu membuat data baru (BR-IZN-09, UF-29 E2).

**Keadaan kosong dan error**

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | Tanggal terdampak di luar batas mundur, untuk selain admin (UF-29 E1). | Ditolak dengan menyebut tanggalnya. |
| E2 | Rentang baru tidak berada di dalam rentang lama, atau tidak memuat hari sekolah. | Ditolak. |
| E3 | Ubah penolakan menjadi persetujuan menimbulkan tumpang tindih. | Ditolak dengan menunjukkan data yang bentrok. |
| E4 | Perubahan per kelompok, tetapi sebagian siswa di luar cakupan. | Pilihan per kelompok tidak tersedia. |
| E5 | Alasan kosong. | Ditolak. |

**Data dan log**

- Ditulis: status dan rentang data izin, serta riwayat keputusan (tindakan, nilai lama dan baru, alasan, pelaku, dan waktu).
- Log perubahan presensi: satu entri per data yang berubah.

**Parameter dan default**

Tidak ada.

**Di luar cakupan**

- Memperpanjang rentang atau mengganti jenis.
- Membagi satu data menjadi dua rentang.
- Siswa mengubah keputusan.

**Catatan antarmuka awal**

- Riwayat keputusan tampil di detail data, dari yang terbaru.

**Acceptance criteria**

**AC-IZN-05-01 — Membatalkan sakit karena siswa hadir**
Rujukan: FR-IZN-06, `HA-IZN-06`, UF-29, BR-IZN-09, `05` §13 contoh 7.

```text
Given siswa A memiliki Sakit yang disetujui untuk hari ini, tetapi scan masuk pukul 06.50
When wali kelas membatalkan data sakit itu dengan alasan "siswa ternyata hadir"
Then status siswa A hari ini menjadi Hadir
  And penanda "izin dengan presensi masuk" hilang
  And log perubahan presensi mencatat pembatalan beserta alasannya
```

**AC-IZN-05-02 — Memendekkan rentang**
Rujukan: FR-IZN-06, BR-IZN-09.

```text
Given siswa B memiliki Sakit yang disetujui untuk Senin–Jumat, 12–16 Oktober 2026
  And siswa B kembali masuk pada Kamis, 15 Oktober 2026
When guru BK memendekkan rentang menjadi 12–14 Oktober 2026 dengan alasan
Then status siswa B pada 15 dan 16 Oktober dihitung ulang dari presensinya
  And status 12–14 Oktober tetap Sakit
```

**AC-IZN-05-03 — Tanggal terdampak di luar batas mundur**
Rujukan: UF-29 E1, BR-IZN-10, BR-MUN-03.

```text
Given hari ini 13 Oktober 2026, batas mundur 7 hari, dan siswa C memiliki Izin yang disetujui untuk 1–2 Oktober 2026
When wali kelas mencoba membatalkan data itu
Then permintaan ditolak karena tanggal terdampak di luar batas mundur
When admin membatalkan data itu dengan alasan
Then pembatalan tersimpan
```

**AC-IZN-05-04 — Mengubah penolakan menjadi persetujuan**
Rujukan: FR-IZN-06, BR-IZN-09.

```text
Given pengajuan Sakit siswa D untuk kemarin ditolak karena surat tidak terbaca
When siswa D membawa surat asli dan guru piket mengubah penolakan menjadi persetujuan dengan alasan
Then status siswa D pada tanggal kemarin menjadi Sakit
  And siswa D melihat keputusan terbaru di portal
```

**AC-IZN-05-05 — Membatalkan satu kelompok dispensasi**
Rujukan: FR-IZN-06, FR-IZN-07, §2.3.

```text
Given ada kelompok dispensasi study tour untuk 100 siswa pada 15–16 Oktober 2026
When guru BK membatalkan seluruh kelompok dengan alasan "study tour ditunda"
Then semua 100 data berstatus dibatalkan
  And log perubahan presensi mencatat 100 entri dengan penanda kelompok yang sama
When wali kelas 7A membuka kelompok yang sama, yang juga berisi siswa rombel lain
Then pilihan perubahan per kelompok tidak tersedia, dan wali kelas hanya dapat mengubah data siswa 7A satu per satu
```

### FS-IZN-06 — Daftar izin dan lampiran

| Item | Isi |
|---|---|
| Tujuan | Pengguna yang berhak melihat data izin/sakit/dispensasi beserta riwayat keputusan dan lampirannya. |
| Rilis | R1 |
| Requirement | FR-IZN-04, FR-IZN-05 |
| Alur | UF-17, UF-18 |
| Aktor dan hak | Lihat daftar (`HA-IZN-04`) dan buka lampiran (`HA-IZN-05`): admin, guru piket, guru BK, dan pimpinan (semua), wali kelas (rombel), dan siswa (sendiri). |
| Aturan terkait | BR-IZN-12, R-17, OQ-17 |
| Status | DECISION (pimpinan membuka lampiran); RECOMMENDATION (pembagian lainnya dan rincian) |

**Prasyarat**

- Pengguna sudah login.

**Input dan validasi**

| Isian | Wajib | Validasi |
|---|---|---|
| Saringan | Tidak | Status, jenis, rentang tanggal, rombel, siswa, sumber (siswa atau staf), dan kelompok. |

**Perilaku**

1. Staf melihat daftar dalam cakupannya. Detail setiap data menampilkan keterangan, lampiran, dan riwayat keputusan beserta nama staf.
2. Siswa melihat datanya sendiri di portal, tanpa nama staf (§2.3).
3. Lampiran hanya diberikan lewat permintaan yang sudah diperiksa haknya, dan tidak memiliki alamat publik (§4.9).
4. Pencatatan setiap pembukaan lampiran mengikuti keputusan OQ-17 (Session 9).

**Keadaan kosong dan error**

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | Tidak ada data yang cocok. | "Tidak ada data izin/sakit/dispensasi." |
| E2 | Lampiran di luar cakupan, atau dibuka tanpa login. | Ditolak. |
| E3 | File lampiran tidak ditemukan. | Pesan galat. Data lain tetap tampil. |

**Data dan log**

- Dibaca: data izin/sakit/dispensasi, riwayat keputusan, dan lampiran.
- Log: pembukaan lampiran mengikuti OQ-17.

**Parameter dan default**

Tidak ada.

**Di luar cakupan**

- Export daftar izin. Laporan ini tidak termasuk matriks laporan Session 5 (`13` §4).

**Catatan antarmuka awal**

- Saringan cepat "menunggu", "hari ini", dan "minggu ini".

**Acceptance criteria**

**AC-IZN-06-01 — Pimpinan membuka lampiran**
Rujukan: FR-IZN-05, `HA-IZN-05`, BR-IZN-12.

```text
Given ada data sakit siswa 9B dengan lampiran surat dokter
When kepala sekolah dengan role Pimpinan membuka lampiran itu
Then lampiran tampil
  And kepala sekolah tidak melihat tombol untuk mengubah keputusan
```

**AC-IZN-06-02 — Lampiran terlindungi**
Rujukan: FR-IZN-05, `HA-IZN-05`, R-17.

```text
Given ada lampiran milik siswa 7B
When wali kelas 7A membuka alamat lampiran itu
Then permintaan ditolak
When alamat yang sama dibuka tanpa login
Then permintaan ditolak
```

**AC-IZN-06-03 — Siswa hanya melihat datanya sendiri**
Rujukan: `HA-IZN-04`, `HA-IZN-05`.

```text
Given siswa A sudah login
When siswa A membuka alamat detail izin milik siswa B
Then permintaan ditolak
```

## 10. Dashboard dan laporan (LAP)

### FS-LAP-01 — Dashboard hari ini

| Item | Isi |
|---|---|
| Tujuan | Staf memantau kehadiran hari ini per rombel, dan staf yang berhak menindaklanjuti siswa yang belum hadir. |
| Rilis | R1 |
| Requirement | FR-LAP-01 |
| Alur | UF-15 |
| Aktor dan hak | Angka per rombel: semua akun staf (`HA-LAP-01`). Daftar nama: admin, guru piket, guru BK, dan pimpinan (semua), wali kelas (rombel) (`HA-LAP-02`). Peringatan stasiun: `HA-KIO-02`. Scan bertanda: `HA-KIO-03`. Pengajuan menunggu: `HA-IZN-03`. |
| Aturan terkait | BR-STS-01, BR-STS-07, BR-DRT-03, BR-REK-04 |
| Status | DECISION (isi dashboard; staf hanya angka; wali kelas daftar nama rombelnya; penanda, Session 4b); RECOMMENDATION (daftar nama untuk guru piket, guru BK, dan pimpinan; peringatan; rincian) |

**Prasyarat**

- Staf sudah login. Dashboard adalah halaman awal akun staf (`02` §8).

**Input dan validasi**

Tidak ada isian.

**Perilaku**

1. **Kepala dashboard** menampilkan:
   - tanggal dan keterangan hari: hari sekolah, bukan hari sekolah, atau libur semua siswa beserta keterangannya;
   - aturan jam hari ini (jam masuk, batas terlambat, tutup sesi masuk, dan jam pulang), dengan tanda bila jadwal hari ini sudah diubah beserta alasannya (FS-PRS-04);
   - tahap sesi: sebelum scan dibuka, sesi masuk berjalan, sesi masuk ditutup, sesi pulang berjalan, atau sesi pulang ditutup;
   - tanda mode darurat bila aktif (FS-PRS-08);
   - waktu data terakhir diperbarui.
2. **Tabel per rombel** tahun ajaran aktif, dengan kolom:
   - jumlah siswa yang hari ini hari sekolah;
   - Hadir, Terlambat, Izin, Sakit, dan Dispensasi;
   - "Belum hadir" sebelum sesi masuk ditutup atau selama mode darurat, dan Alpa setelahnya.

   Rombel yang libur tampil dengan keterangan liburnya. Baris terakhir berisi total semua rombel.
3. **Daftar nama.** Pemegang `HA-LAP-02` membuka satu rombel dan masuk ke daftar presensi rombel hari ini (FS-LAP-02). Wali kelas hanya dapat membuka rombelnya; untuk rombel lain, wali kelas hanya melihat angka (`02` §1).
4. Pemegang `HA-LAP-02` dengan cakupan semua juga dapat melihat daftar siswa per status lintas rombel, misalnya semua siswa yang masih "belum hadir".
5. **Penanda** tampil di daftar nama (FS-PRS-05). Jumlah siswa berpenanda tampil per rombel bagi pemegang `HA-LAP-02` (BR-STS-07).
6. **Peringatan sesuai hak:**
   - stasiun yang belum tersinkron atau tidak ada kontak (`HA-KIO-02`, FS-KIO-05);
   - jumlah scan bertanda yang menunggu tinjauan (`HA-KIO-03`, FS-KIO-06);
   - jumlah pengajuan izin/sakit yang menunggu verifikasi dalam cakupan (`HA-IZN-03`, FS-IZN-04);
   - untuk admin, pengingat penyiapan yang belum lengkap: identitas sekolah, tahun ajaran aktif, pola mingguan, rombel, siswa, dan akun stasiun (UF-01).
7. Data mengikuti sinkron dari stasiun. Dashboard diperbarui setiap 30 detik tanpa memuat ulang halaman, lewat polling fragmen HTML (DECISION, Session 6, `07` ARS-50; UF-15 langkah 5).
8. Staf tanpa role khusus hanya melihat kepala dashboard dan tabel angka (`HA-LAP-01`), tanpa daftar nama, penanda, atau peringatan selain tanda mode darurat.

**Keadaan kosong dan error**

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | Hari ini bukan hari sekolah, atau libur untuk semua siswa. | Keterangan hari tampil tanpa tabel. |
| E2 | Belum ada tahun ajaran aktif atau rombel. | "Belum ada data rombel." Admin melihat pengingat penyiapan. |
| E3 | Belum ada scan pagi ini. | Tabel tetap tampil dengan angka nol dan "belum hadir". |

**Data dan log**

- Dibaca: hasil FS-PRS-05 untuk hari ini, mode darurat, jadwal hari ini, status stasiun, scan bertanda, dan pengajuan menunggu.

**Parameter dan default**

| Parameter | Default | Diatur oleh | Dipastikan di |
|---|---|---|---|
| Interval pembaruan dashboard | 30 detik | Sistem | Session 6 (DECISION) |

**Di luar cakupan**

- Grafik tren dan perbandingan antarhari. Kebutuhan ini tidak termasuk matriks laporan Session 5 (`13` §4).
- Dashboard untuk siswa atau publik. Halaman publik ada di R3 (FS-INF-03).

**Catatan antarmuka awal**

- Wali kelas melihat rombelnya di posisi teratas.
- Angka "belum hadir" atau Alpa disorot.
- Dashboard dapat dipakai di ponsel (NFR-11).

**Acceptance criteria**

**AC-LAP-01-01 — Staf hanya melihat angka**
Rujukan: FR-LAP-01, `HA-LAP-01`, `HA-LAP-02`, UF-15.

```text
Given staf tanpa role khusus sudah login
When staf membuka dashboard hari ini
Then staf melihat jumlah Hadir, Terlambat, Izin, Sakit, Dispensasi, dan belum hadir per rombel
  And staf tidak dapat membuka daftar nama siswa rombel mana pun
```

**AC-LAP-01-02 — Wali kelas melihat nama rombelnya**
Rujukan: FR-LAP-01, `HA-LAP-02`, `02` §1.

```text
Given wali kelas 7A sudah login
When wali kelas membuka rombel 7A dari dashboard
Then daftar nama siswa 7A per status tampil
When wali kelas mencoba membuka daftar nama rombel 7B
Then permintaan ditolak, dan wali kelas hanya melihat angka 7B di dashboard
```

**AC-LAP-01-03 — Belum hadir dan Alpa**
Rujukan: FR-LAP-01, FR-PRS-08, AC-03, BR-STS-01.

```text
Given 3 siswa 7A tidak memiliki presensi masuk dan tidak berizin
When pukul 07.30 guru piket membuka dashboard
Then kolom "belum hadir" rombel 7A menunjukkan 3
When pukul 08.00 sesi masuk tertutup
Then kolom itu menjadi Alpa dengan nilai 3
```

**AC-LAP-01-04 — Tanda mode darurat**
Rujukan: FR-PRS-10, UF-15 langkah 2, BR-DRT-03.

```text
Given mode darurat aktif
When staf mana pun membuka dashboard pukul 08.30
Then tanda mode darurat tampil
  And siswa tanpa presensi dihitung "belum hadir", bukan Alpa
```

**AC-LAP-01-05 — Data mengikuti sinkron**
Rujukan: FR-LAP-01, UF-15 langkah 5.

```text
Given dashboard guru piket menunjukkan 25 siswa 7A hadir
When 5 scan masuk siswa 7A tersinkron dari stasiun
Then setelah pembaruan berikutnya, dashboard menunjukkan 30 siswa 7A hadir tanpa guru piket memuat ulang halaman
```

**AC-LAP-01-06 — Rombel libur**
Rujukan: BR-KAL-02, BR-KAL-05.

```text
Given kelas 9 libur hari ini
When admin membuka dashboard
Then rombel kelas 9 tampil dengan keterangan libur tanpa angka Alpa
  And total sekolah tidak menghitung siswa kelas 9
```

**AC-LAP-01-07 — Peringatan sesuai hak**
Rujukan: FR-KIO-12, `HA-KIO-02`, `HA-IZN-03`.

```text
Given satu stasiun masih melaporkan scan belum tersinkron dan ada 2 pengajuan menunggu dari siswa 7A
When guru piket membuka dashboard
Then peringatan stasiun dan jumlah 2 pengajuan menunggu tampil
When wali kelas 7A tanpa role lain membuka dashboard
Then jumlah 2 pengajuan menunggu tampil, tetapi peringatan stasiun tidak tampil
```

### FS-LAP-02 — Daftar presensi rombel per tanggal

| Item | Isi |
|---|---|
| Tujuan | Staf melihat status setiap siswa satu rombel pada satu tanggal beserta sumber datanya, lalu menindaklanjuti dari daftar itu. |
| Rilis | R1 |
| Requirement | FR-LAP-01, FR-LAP-02, FR-PRS-06, FR-PRS-07 |
| Alur | UF-15 langkah 3–4, UF-16 langkah 1 |
| Aktor dan hak | Hari ini: pemegang `HA-LAP-02`. Tanggal lain: pemegang `HA-LAP-03`. Tindakan di daftar mengikuti `HA-PRS-03`, `HA-PRS-04`, `HA-IZN-02`, `HA-IZN-06`, dan `HA-PRS-06`. |
| Aturan terkait | BR-STS-07, BR-REK-04, BR-REK-05 |
| Status | DECISION (penanda di daftar presensi rombel, termasuk tanggal lampau, Session 4b); RECOMMENDATION (fitur ini sebagai rincian dari FR-LAP-01 dan FR-LAP-02) |

**Prasyarat**

- Rombel dan tanggal dalam cakupan pengguna.

**Input dan validasi**

| Isian | Wajib | Validasi |
|---|---|---|
| Rombel | Ya | Dalam cakupan (§4.1). |
| Tanggal | Ya | Default hari ini. Tidak boleh tanggal ke depan. Selain hari ini, hanya untuk pemegang `HA-LAP-03`. |

**Perilaku**

1. Daftar berisi siswa yang ditempatkan di rombel itu pada tanggal tersebut (BR-REK-05). Setiap baris menampilkan:
   - foto kecil, nama, dan NISN;
   - status, "belum hadir", atau tanpa status beserta alasannya, misalnya libur atau tidak aktif;
   - presensi masuk dan pulang: jam dan sumbernya (scan beserta stasiun, manual, atau darurat);
   - kejadian pulang;
   - izin/sakit/dispensasi beserta jenis dan statusnya;
   - koreksi beserta alasannya;
   - penanda (FS-PRS-05);
   - tautan ke entri log tanggal itu, bagi pemegang `HA-PRS-06`.
2. Ringkasan jumlah per status tampil di atas daftar. Daftar dapat dikelompokkan atau diurutkan berdasarkan status.
3. Tombol tindakan per baris hanya tampil bila pengguna berhak untuk siswa dan tanggal itu:
   - presensi manual dan pembatalannya (FS-PRS-06);
   - koreksi dan penghapusannya (FS-PRS-07);
   - input izin/sakit/dispensasi (FS-IZN-02);
   - ubah keputusan izin/sakit/dispensasi (FS-IZN-05).
4. Saat mode darurat aktif, daftar hari ini menyediakan tautan ke presensi per rombel (FS-PRS-09).
5. Daftar untuk hari ini diberi tanda "belum final" sampai sesi masuk ditutup dan mode darurat berakhir (BR-REK-04).
6. Penanda tampil untuk tanggal mana pun yang dibuka, termasuk tanggal lampau (BR-STS-07, DECISION Session 4b).

**Keadaan kosong dan error**

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | Tanggal bukan hari sekolah bagi rombel itu. | "Bukan hari sekolah" beserta keterangannya. Daftar siswa tetap tampil tanpa status. |
| E2 | Rombel di luar cakupan, atau tanggal lain tanpa `HA-LAP-03`. | Ditolak (§4.1). |
| E3 | Tanggal ke depan. | Ditolak. |

**Data dan log**

- Dibaca: hasil FS-PRS-05 dan sumbernya untuk rombel dan tanggal itu.

**Parameter dan default**

Tidak ada.

**Di luar cakupan**

- Mengubah banyak siswa sekaligus dari daftar ini, kecuali presensi per rombel saat darurat (FS-PRS-09).

**Catatan antarmuka awal**

- Siswa berpenanda dan siswa yang belum hadir tampil di bagian atas.
- Pemilih tanggal dengan tombol hari sebelumnya dan hari berikutnya.

**Acceptance criteria**

**AC-LAP-02-01 — Wali kelas membuka tanggal kemarin**
Rujukan: FR-LAP-02, FR-PRS-07, `HA-LAP-03`, `HA-PRS-04`, UF-16, BR-STS-07.

```text
Given kemarin siswa A di 7A berstatus Sakit dan memiliki presensi masuk pukul 06.45
When wali kelas 7A membuka daftar presensi 7A tanggal kemarin
Then siswa A tampil dengan status Sakit, presensi masuk 06.45 dari stasiun, dan penanda "izin dengan presensi masuk"
  And tombol koreksi dan ubah keputusan izin tersedia, karena tanggal itu dalam batas mundur
```

**AC-LAP-02-02 — Guru piket hanya hari ini**
Rujukan: `HA-LAP-02`, `HA-LAP-03`.

```text
Given guru piket tanpa role lain sudah login
When guru piket membuka daftar presensi 8B hari ini
Then daftar tampil dengan tombol presensi manual dan koreksi
When guru piket membuka daftar presensi 8B tanggal kemarin
Then permintaan ditolak
```

**AC-LAP-02-03 — Pimpinan tanpa tombol tindakan**
Rujukan: `HA-LAP-03`, `HA-PRS-04`.

```text
Given kepala sekolah dengan role Pimpinan sudah login
When kepala sekolah membuka daftar presensi 9A tanggal 9 Oktober 2026
Then daftar tampil lengkap dengan status dan sumbernya
  And tidak ada tombol presensi manual, koreksi, atau input izin
```

**AC-LAP-02-04 — Sumber presensi**
Rujukan: FR-LAP-01, BR-SCN-04.

```text
Given siswa B tercatat masuk lewat presensi manual pukul 08.10 dengan alasan "tiba setelah sesi masuk ditutup"
When admin membuka daftar presensi rombel siswa B hari ini
Then baris siswa B menunjukkan Terlambat, jam 08.10, sumber "manual", dan alasannya
```

### FS-LAP-03 — Rekap per rombel

| Item | Isi |
|---|---|
| Tujuan | Staf melihat ringkasan kehadiran setiap siswa satu rombel untuk rentang tanggal tertentu, tanpa kerja manual. |
| Rilis | R1 |
| Requirement | FR-LAP-02 |
| Alur | UF-22 |
| Aktor dan hak | Admin, guru BK, dan pimpinan (semua), wali kelas (rombel) (`HA-LAP-03`). |
| Aturan terkait | BR-REK-01 s.d. BR-REK-05, BR-KAL-05 |
| Status | DECISION (rekap di layar; definisi ketidakhadiran dan persentase); RECOMMENDATION (isi kolom dan rincian) |

**Prasyarat**

- Rombel dalam cakupan.

**Input dan validasi**

| Isian | Wajib | Validasi |
|---|---|---|
| Tahun ajaran | Ya | Default tahun ajaran aktif. |
| Rombel | Ya | Rombel di tahun ajaran itu, dalam cakupan. |
| Rentang tanggal | Ya | Pilihan cepat: hari ini, minggu ini, bulan ini, semester ganjil, semester genap, atau rentang bebas. Rentang berada di dalam tahun ajaran. Tanggal selesai paling lambat hari ini. |

**Perilaku**

1. Tabel berisi satu baris per siswa yang ditempatkan di rombel itu pada salah satu tanggal dalam rentang (BR-REK-05). Kolomnya (BR-REK-01):
   - jumlah hari sekolah bagi siswa di rombel itu dalam rentang;
   - Hadir, Terlambat, Izin, Sakit, Dispensasi, dan Alpa;
   - ketidakhadiran, yaitu Sakit + Izin + Alpa (BR-REK-02);
   - persentase kehadiran, yaitu (Hadir + Terlambat + Dispensasi) ÷ hari sekolah × 100%, dibulatkan ke bilangan bulat (BR-REK-03, `13` IE-04);
   - jumlah kejadian pulang lebih awal dan tidak scan pulang.
2. Hari sekolah dihitung per siswa, sehingga libur tingkat atau rombel dan tanggal di luar masa aktif tidak dihitung (BR-KAL-05).
3. Siswa yang pindah rombel hanya dihitung untuk tanggal saat ia berada di rombel ini, dan diberi tanda "pindah" (BR-REK-05).
4. Bila rentang mencakup hari ini dan status hari ini belum final, siswa yang masih "belum hadir" tidak dihitung Alpa dan hari itu belum dihitung sebagai hari sekolahnya, sehingga persentasenya tidak turun sementara. Rekap memuat kolom "belum hadir" dan tanda bahwa data hari ini belum final (BR-REK-04).
5. Baris total rombel menjumlahkan semua kolom. Persentase total adalah total (Hadir + Terlambat + Dispensasi) ÷ total hari sekolah.
6. Nama siswa dapat dibuka untuk melihat riwayatnya (FS-LAP-04), bila siswa itu berada dalam cakupan pengguna untuk data per siswa (§4.1 butir 4). Untuk siswa yang sudah pindah ke rombel lain, wali kelas rombel lama hanya melihat baris rekapnya, tanpa tautan riwayat.
7. Rekap satu hari menampilkan ringkasan yang sama. Status per siswa untuk satu tanggal dilihat di FS-LAP-02.
8. Export rekap tersedia di R2 (FS-LAP-05).

**Keadaan kosong dan error**

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | Rentang di luar tahun ajaran, atau tanggal selesai setelah hari ini. | Ditolak. |
| E2 | Tidak ada hari sekolah dalam rentang. | "Tidak ada hari sekolah pada rentang ini." |
| E3 | Rombel di luar cakupan. | Ditolak. |

**Data dan log**

- Dibaca: hasil FS-PRS-05 untuk siswa dan tanggal dalam rentang, serta penempatan siswa.

**Parameter dan default**

| Parameter | Default | Diatur oleh | Dipastikan di |
|---|---|---|---|
| Rentang default | Bulan berjalan | Sistem | Session 7 |
| Pembulatan persentase | Bilangan bulat (DECISION, Session 5), dengan nilai tepat setengah dibulatkan ke atas (RECOMMENDATION) (`13` IE-04) | Sistem | — |

**Di luar cakupan**

- Rekap seluruh sekolah dalam satu tabel. Laporan ini ditetapkan sebagai LP-02 di `13` dan termasuk R2 (FS-LAP-05).
- Export (R2).

**Catatan antarmuka awal**

- Kolom persentase dapat diurutkan untuk menemukan siswa dengan kehadiran rendah.

**Acceptance criteria**

**AC-LAP-03-01 — Rekap bulanan**
Rujukan: FR-LAP-02, `HA-LAP-03`, UF-22, BR-REK-01 s.d. BR-REK-03.

```text
Given pada September 2026 siswa A di 7A memiliki 24 hari sekolah: 18 Hadir, 2 Terlambat, 1 Dispensasi, 1 Sakit, 1 Izin, dan 1 Alpa
When wali kelas 7A membuka rekap 7A untuk September 2026
Then baris siswa A menunjukkan hari sekolah 24, ketidakhadiran 3, dan persentase kehadiran 88% (87,5% dibulatkan)
```

**AC-LAP-03-02 — Libur dan dispensasi**
Rujukan: BR-REK-02, BR-REK-03, BR-KAL-05.

```text
Given rombel 9A libur 2 hari pada bulan itu, sementara rombel lain masuk
  And siswa B di 9A mendapat dispensasi lomba 3 hari
When admin membuka rekap 9A untuk bulan itu
Then 2 hari libur tidak dihitung sebagai hari sekolah siswa 9A
  And 3 hari dispensasi dihitung hadir dalam persentase dan tidak dihitung ketidakhadiran
```

**AC-LAP-03-03 — Pindah rombel**
Rujukan: BR-REK-05, R-14.

```text
Given siswa C pindah dari 7A ke 7B mulai 12 Oktober 2026
When guru BK membuka rekap 7A untuk Oktober 2026
Then siswa C tampil dengan tanda "pindah" dan hanya dihitung untuk tanggal sebelum 12 Oktober
```

**AC-LAP-03-04 — Hari ini belum final**
Rujukan: BR-REK-04.

```text
Given pukul 07.30 hari ini 2 siswa 7A masih "belum hadir"
When wali kelas membuka rekap 7A untuk minggu ini
Then rekap memuat kolom "belum hadir" bernilai 2 dan tanda bahwa data hari ini belum final
  And kedua siswa itu tidak dihitung Alpa
  And hari ini belum dihitung sebagai hari sekolah kedua siswa itu
```

**AC-LAP-03-05 — Cakupan wali kelas**
Rujukan: `HA-LAP-03`.

```text
Given wali kelas 7A sudah login
When wali kelas membuka rekap 7B
Then permintaan ditolak
```

### FS-LAP-04 — Riwayat kehadiran siswa

| Item | Isi |
|---|---|
| Tujuan | Staf melihat riwayat kehadiran harian seorang siswa, dan siswa melihat riwayatnya sendiri di portal. |
| Rilis | R1 |
| Requirement | FR-LAP-03, FR-IZN-04 |
| Alur | UF-22 |
| Aktor dan hak | Admin, guru BK, dan pimpinan (semua), wali kelas (rombel), dan siswa (sendiri) (`HA-LAP-04`). |
| Aturan terkait | BR-STS-01 s.d. BR-STS-08, BR-REK-01, BR-REK-04 |
| Status | DECISION (riwayat siswa; isi riwayat di portal tanpa nama staf, Session 4b; penanda di riwayat staf, Session 4b); RECOMMENDATION (rincian) |

**Prasyarat**

- Siswa dalam cakupan pengguna.

**Input dan validasi**

| Isian | Wajib | Validasi |
|---|---|---|
| Siswa | Ya, untuk staf | Dalam cakupan. Siswa selalu melihat dirinya sendiri. |
| Periode | Ya | Default bulan berjalan. Pilihan: bulan, semester, tahun ajaran, atau rentang bebas. |

**Perilaku**

1. Bagian atas berisi ringkasan periode, dengan kolom yang sama seperti rekap (FS-LAP-03).
2. Daftar harian berisi satu baris per hari sekolah dalam periode, dari yang terbaru.
3. **Tampilan staf** memuat, per tanggal:
   - status;
   - jam dan sumber presensi masuk dan pulang;
   - kejadian pulang;
   - izin/sakit/dispensasi beserta jenis, status, catatan, dan nama verifikator;
   - koreksi beserta alasan dan pelakunya;
   - penanda (BR-STS-07);
   - tautan log (`HA-PRS-06`);
   - tombol tindakan sesuai hak, seperti pada FS-LAP-02.
4. Wali kelas melihat riwayat siswa rombelnya, termasuk riwayat dari tahun ajaran sebelumnya (`02` §5).
5. **Tampilan siswa di portal** (DECISION, Session 4b) memuat, per tanggal:
   - status;
   - jam presensi masuk dan pulang;
   - kejadian pulang;
   - izin/sakit/dispensasi beserta jenis, status, dan catatan verifikasi;
   - tanda "dikoreksi" beserta alasan koreksinya.

   Nama staf, nama stasiun, penanda, dan log tidak ditampilkan.
6. Hari ini diberi tanda "belum final" sampai statusnya final (BR-REK-04).
7. Portal siswa juga menautkan daftar pengajuan izin/sakit siswa (FS-IZN-01).

**Keadaan kosong dan error**

| Kode | Keadaan | Respons |
|---|---|---|
| E1 | Tidak ada hari sekolah dalam periode. | "Tidak ada hari sekolah pada periode ini." |
| E2 | Siswa di luar cakupan, atau siswa membuka riwayat siswa lain. | Ditolak (§4.1). |

**Data dan log**

- Dibaca: hasil FS-PRS-05, data izin, koreksi, dan presensi siswa itu.

**Parameter dan default**

| Parameter | Default | Diatur oleh | Dipastikan di |
|---|---|---|---|
| Periode default | Bulan berjalan | Sistem | Session 7 |

**Di luar cakupan**

- Siswa mengajukan keberatan atas status lewat portal. Siswa menghubungi wali kelas.
- Orang tua membuka riwayat; orang tua bukan pengguna sistem (`00` §4).

**Catatan antarmuka awal**

- Tampilan portal dioptimalkan untuk ponsel, misalnya dalam bentuk kalender bulanan berwarna.

**Acceptance criteria**

**AC-LAP-04-01 — Siswa melihat alasan koreksi tanpa nama staf**
Rujukan: FR-LAP-03, `HA-LAP-04`, §2.3.

```text
Given status siswa A pada 9 Oktober 2026 dikoreksi guru piket menjadi Tidak hadir dengan alasan "kartu dititipkan"
When siswa A membuka riwayatnya di portal
Then tanggal 9 Oktober tampil dengan status Alpa, tanda "dikoreksi", dan alasan "kartu dititipkan"
  And nama guru piket tidak tampil
```

**AC-LAP-04-02 — Siswa melihat catatan verifikasi**
Rujukan: FR-IZN-04, §2.3.

```text
Given pengajuan Sakit siswa B ditolak dengan catatan "Surat tidak terbaca"
When siswa B membuka riwayat dan daftar pengajuannya
Then siswa B melihat status "ditolak" dan catatan "Surat tidak terbaca" tanpa nama verifikator
```

**AC-LAP-04-03 — Siswa tidak dapat membuka riwayat siswa lain**
Rujukan: `HA-LAP-04`, §4.1.

```text
Given siswa A sudah login
When siswa A mengubah alamat halaman untuk membuka riwayat siswa B
Then permintaan ditolak
```

**AC-LAP-04-04 — Riwayat tahun ajaran sebelumnya**
Rujukan: `HA-LAP-04`, `02` §5.

```text
Given siswa C sekarang berada di 8A, dan tahun lalu berada di 7B
When wali kelas 8A membuka riwayat siswa C untuk tahun ajaran lalu
Then riwayat tahun lalu tampil
```

**AC-LAP-04-05 — Tampilan staf lengkap**
Rujukan: FR-LAP-03, BR-STS-07, `HA-PRS-06`.

```text
Given siswa D memiliki koreksi pada 8 Oktober 2026 dan penanda "pulang tanpa presensi masuk" pada 9 Oktober 2026
When guru BK membuka riwayat siswa D
Then tanggal 8 Oktober menampilkan koreksi beserta alasan, pelaku, dan tautan ke log
  And tanggal 9 Oktober menampilkan penanda "pulang tanpa presensi masuk"
```

## 11. Kerangka fitur R2 dan R3

Fitur di bagian ini baru dicatat sebagai kerangka (DECISION, Session 4b). ID-nya sudah dipesan. Rinciannya, dengan struktur yang sama seperti fitur R1, ditulis menjelang rilisnya setelah OQ terkait terjawab. Aturan bisnis R2 di `05` §11 sudah berlaku sebagai dasar.

| ID | Fitur | Rilis | Tujuan | Rujukan | Menunggu |
|---|---|---|---|---|---|
| FS-LAP-05 | Export rekap | R2 | Laporan diekspor ke XLSX, CSV, dan PDF sesuai matriks `13` §4, termasuk rekap semua rombel (LP-02) dan rekap rapor semester (LP-03). | FR-LAP-04, `HA-LAP-05`, UF-22, BR-REK-01 s.d. BR-REK-05, NFR-16, `13` | Tidak ada. OQ-09 terjawab di Session 6 (`07` ARS-07, ARS-10), dan OQ-11 di Session 5. |
| FS-LAP-06 | Flyer kehadiran | R2 | Staf membuat flyer PNG per rombel atau total, berisi angka saja, lalu mengunduhnya (`13` LP-08). | FR-LAP-05, `HA-LAP-06`, UF-24, R-17, R-19 | Desain template dan cara pembuatan PNG (Session 7). OQ-11 terjawab di Session 5. |
| FS-WA-01 | Pengaturan notifikasi WhatsApp | R2 | Admin mengatur koneksi gateway, jenis kejadian yang aktif, template pesan, waktu tunda, dan ambang pengaman. | FR-WA-01, FR-WA-02, FR-WA-05, FR-WA-07, `HA-WA-01`, BR-WA-02, BR-WA-03 | OQ-10 (sebelum R2) |
| FS-WA-02 | Pembuatan pesan dan outbox WA | R2 | Kejadian presensi menghasilkan pesan di outbox, yang dikirim bertahap. Admin memantau dan mengirim ulang pesan yang gagal. | FR-WA-03, FR-WA-04, FR-WA-06, `HA-WA-02`, UF-23, BR-WA-01, BR-WA-04, BR-WA-06, BR-WA-07, NFR-05, NFR-13 | OQ-10 |
| FS-WA-03 | Pesan "tidak hadir" dan "tidak scan pulang" beserta penahanannya | R2 | Pesan dibuat setelah waktu tunda bila semua syarat terpenuhi, dan ditahan bila di bawah ambang. Guru piket atau admin melepas atau membatalkan pesan yang ditahan. | FR-WA-07, FR-WA-08, `HA-WA-03`, UF-13, UF-23, BR-WA-02, BR-WA-03, BR-WA-05, BR-DRT-06, FS-KIO-05 | OQ-10 |
| FS-INF-01 | Mata pelajaran dan jadwal pelajaran | R3 | Admin mengelola mata pelajaran dan jadwal pelajaran per rombel. Siswa dan staf melihat jadwal sebagai informasi. | FR-INF-01, FR-INF-02, `HA-INF-01`, `HA-INF-02` | — |
| FS-INF-02 | Pengumuman | R3 | Admin membuat pengumuman dengan sasaran publik atau siswa. | FR-INF-03, `HA-INF-03`, `HA-INF-04`, UF-25 | — |
| FS-INF-03 | Halaman publik | R3 | Pengunjung tanpa login melihat info sekolah, pengumuman publik, dan rekap agregat hari ini tanpa data individu. | FR-INF-04, FR-INF-05, AC-05, UF-25, NFR-10 | — |
| FS-KRT-01 | Cetak kartu | R3 | Admin mencetak kartu siswa baru dan kartu pengganti dengan QR berisi NISN polos. | FR-KRT-01, FR-KRT-02, `HA-KRT-01`, UF-26, C-04, R-02 | OQ-13 (Session 7) |

Fitur R1 yang perlu diperhatikan agar R2 tidak memerlukan perubahan besar:

- Hasil penentuan status (FS-PRS-05) dan status stasiun (FS-KIO-05) dipakai oleh FS-WA-03.
- Nomor WA yang sudah dibakukan (FS-MD-04) dipakai oleh FS-WA-02.
- Periode mode darurat beserta waktu berakhir dan tanda berakhir otomatis (FS-PRS-08) dipakai oleh BR-DRT-06.

## 12. Traceability

### 12.1 Requirement → fitur

| Requirement | Fitur |
|---|---|
| FR-MD-01 | FS-MD-02 |
| FR-MD-02 | FS-MD-03 |
| FR-MD-03 | FS-MD-04 |
| FR-MD-04 | FS-MD-05 |
| FR-MD-05 | FS-MD-06 |
| FR-MD-06 | FS-MD-07 |
| FR-MD-07 | FS-MD-08 |
| FR-MD-08 | FS-MD-01 |
| FR-MD-09 | FS-MD-04, FS-MD-07, FS-MD-08 |
| FR-MD-10 | FS-MD-09 |
| FR-AKN-01 | FS-AKN-01 |
| FR-AKN-02 | FS-AKN-03, FS-AKN-04 |
| FR-AKN-03 | FS-AKN-04, FS-KIO-01 s.d. FS-KIO-04 |
| FR-AKN-04 | FS-AKN-01, FS-AKN-02 |
| FR-AKN-05 | FS-AKN-05, FS-MD-04, FS-MD-06 |
| FR-AKN-06 | FS-AKN-02, FS-AKN-03, FS-AKN-05 |
| FR-AKN-07 | FS-AKN-03, FS-AKN-05 |
| FR-AKN-08 | FS-AKN-03 |
| FR-KIO-01 | FS-KIO-01 |
| FR-KIO-02, FR-KIO-03, FR-KIO-04, FR-KIO-05 | FS-KIO-02 |
| FR-KIO-06 | FS-KIO-01, FS-KIO-02 |
| FR-KIO-07, FR-KIO-08 | FS-KIO-03 |
| FR-KIO-09 | FS-KIO-01, FS-KIO-02, FS-KIO-03 |
| FR-KIO-10 | FS-KIO-04 |
| FR-KIO-11 | FS-KIO-04, FS-KIO-06 |
| FR-KIO-12 | FS-KIO-05 |
| FR-PRS-01 | FS-PRS-05 |
| FR-PRS-02 | FS-PRS-01, FS-PRS-02 |
| FR-PRS-03 | FS-PRS-01, FS-PRS-02, FS-PRS-03 |
| FR-PRS-04, FR-PRS-05 | FS-PRS-05 |
| FR-PRS-06 | FS-PRS-06, FS-PRS-09 |
| FR-PRS-07 | FS-PRS-07, FS-PRS-11 |
| FR-PRS-08 | FS-PRS-05, FS-KIO-02 |
| FR-PRS-09 | FS-PRS-04 |
| FR-PRS-10 | FS-PRS-08, FS-PRS-09 |
| FR-PRS-11 | FS-PRS-10, FS-PRS-06, FS-PRS-07 |
| FR-IZN-01 | FS-IZN-01 |
| FR-IZN-02 | FS-IZN-02 |
| FR-IZN-03 | FS-IZN-04 |
| FR-IZN-04 | FS-IZN-01, FS-IZN-06, FS-LAP-04 |
| FR-IZN-05 | FS-IZN-06 |
| FR-IZN-06 | FS-IZN-05 |
| FR-IZN-07 | FS-IZN-02, FS-IZN-03 |
| FR-LAP-01 | FS-LAP-01, FS-LAP-02 |
| FR-LAP-02 | FS-LAP-02, FS-LAP-03 |
| FR-LAP-03 | FS-LAP-04 |
| FR-LAP-04 | FS-LAP-05 (R2) |
| FR-LAP-05 | FS-LAP-06 (R2) |
| FR-WA-01, FR-WA-02, FR-WA-05 | FS-WA-01 (R2) |
| FR-WA-03, FR-WA-04, FR-WA-06 | FS-WA-02 (R2) |
| FR-WA-07 | FS-WA-01, FS-WA-03 (R2) |
| FR-WA-08 | FS-WA-03 (R2) |
| FR-INF-01, FR-INF-02 | FS-INF-01 (R3) |
| FR-INF-03 | FS-INF-02 (R3) |
| FR-INF-04, FR-INF-05 | FS-INF-03 (R3) |
| FR-KRT-01, FR-KRT-02 | FS-KRT-01 (R3) |

### 12.2 Alur → fitur

| Alur | Fitur |
|---|---|
| UF-01 | FS-MD-01, FS-MD-02, FS-MD-03, FS-PRS-01, FS-PRS-02, FS-PRS-03, FS-PRS-10, FS-AKN-03, FS-MD-06, FS-MD-08, FS-AKN-04, FS-AKN-05 |
| UF-02 | FS-MD-06 |
| UF-03 | FS-MD-07, FS-MD-08 |
| UF-04 | FS-AKN-03 |
| UF-05 | FS-AKN-05 |
| UF-06 | FS-AKN-04, FS-KIO-01, FS-KIO-05 |
| UF-07 | FS-MD-04, FS-MD-05, FS-MD-09, FS-AKN-05 |
| UF-08 | FS-MD-02, FS-MD-03, FS-MD-05 (termasuk import penempatan) |
| UF-09 | FS-KIO-01 |
| UF-10 | FS-KIO-02 |
| UF-11 | FS-KIO-03, FS-KIO-04, FS-KIO-05, FS-KIO-06 |
| UF-12 | FS-PRS-06 |
| UF-13 | FS-PRS-05, FS-KIO-05 |
| UF-14 | FS-KIO-02, FS-PRS-05 |
| UF-15 | FS-LAP-01, FS-LAP-02 |
| UF-16 | FS-PRS-07, FS-LAP-02, FS-PRS-11 |
| UF-17 | FS-IZN-01 |
| UF-18 | FS-IZN-04, FS-IZN-06 |
| UF-19 | FS-IZN-02, FS-IZN-03 |
| UF-20 | FS-AKN-01, FS-AKN-02 |
| UF-21 | FS-AKN-03, FS-AKN-05 |
| UF-22 | FS-LAP-03, FS-LAP-04, FS-LAP-05 (R2) |
| UF-23 | FS-WA-02, FS-WA-03 (R2) |
| UF-24 | FS-LAP-06 (R2) |
| UF-25 | FS-INF-02, FS-INF-03 (R3) |
| UF-26 | FS-KRT-01 (R3) |
| UF-27 | FS-PRS-08, FS-PRS-09 |
| UF-28 | FS-PRS-04 |
| UF-29 | FS-IZN-05 |

### 12.3 Hak akses → fitur

| Hak akses | Fitur |
|---|---|
| `HA-AKN-01` | FS-AKN-01, FS-AKN-02 |
| `HA-AKN-02` | FS-AKN-03 |
| `HA-AKN-03` | FS-AKN-04 |
| `HA-AKN-04`, `HA-AKN-05`, `HA-AKN-06` | FS-AKN-05 |
| `HA-MD-01` | FS-MD-02 |
| `HA-MD-02` | FS-MD-03 |
| `HA-MD-03` | FS-MD-04, FS-MD-05, FS-MD-09 |
| `HA-MD-04` | FS-MD-06, FS-MD-09 |
| `HA-MD-05` | FS-MD-04, FS-MD-09, FS-PRS-06, FS-IZN-02 |
| `HA-MD-06` | FS-MD-04 |
| `HA-MD-07` | FS-MD-07 |
| `HA-MD-08` | FS-MD-08 |
| `HA-MD-09` | FS-MD-01 |
| `HA-MD-10` | FS-MD-04 |
| `HA-MD-11` | FS-MD-09 |
| `HA-KIO-01` | FS-KIO-01 s.d. FS-KIO-04 |
| `HA-KIO-02` | FS-KIO-05, FS-AKN-04, FS-LAP-01 |
| `HA-KIO-03` | FS-KIO-06, FS-LAP-01 |
| `HA-PRS-01` | FS-PRS-01, FS-PRS-02 |
| `HA-PRS-02` | FS-PRS-03 |
| `HA-PRS-03` | FS-PRS-06, FS-PRS-09, FS-LAP-02 |
| `HA-PRS-04` | FS-PRS-07, FS-LAP-02 |
| `HA-PRS-05` | DEPRECATED, tidak ada fitur |
| `HA-PRS-06` | FS-PRS-11, FS-LAP-02, FS-LAP-04 |
| `HA-PRS-07` | FS-PRS-04 |
| `HA-PRS-08` | FS-PRS-08 |
| `HA-PRS-09` | FS-PRS-10 |
| `HA-IZN-01` | FS-IZN-01 |
| `HA-IZN-02` | FS-IZN-02, FS-IZN-03, FS-LAP-02 |
| `HA-IZN-03` | FS-IZN-04, FS-LAP-01 |
| `HA-IZN-04`, `HA-IZN-05` | FS-IZN-06, FS-IZN-01 |
| `HA-IZN-06` | FS-IZN-05 |
| `HA-LAP-01` | FS-LAP-01 |
| `HA-LAP-02` | FS-LAP-01, FS-LAP-02 |
| `HA-LAP-03` | FS-LAP-02, FS-LAP-03 |
| `HA-LAP-04` | FS-LAP-04 |
| `HA-LAP-05` | FS-LAP-05 (R2) |
| `HA-LAP-06` | FS-LAP-06 (R2) |
| `HA-WA-01` | FS-WA-01 (R2) |
| `HA-WA-02` | FS-WA-02 (R2) |
| `HA-WA-03` | FS-WA-03 (R2) |
| `HA-INF-01`, `HA-INF-02` | FS-INF-01 (R3) |
| `HA-INF-03`, `HA-INF-04` | FS-INF-02, FS-INF-03 (R3) |
| `HA-KRT-01` | FS-KRT-01 (R3) |

## 13. Perubahan pada dokumen lain

| Dokumen | Versi | Perubahan |
|---|---|---|
| `00-project-overview.md` | 0.4 | Header dan dokumen terkait memuat `04`. R-22 diperbarui (penanda menjadi DECISION). Glosarium ditambah: spesifikasi fitur, penanda, daftar presensi rombel, kelompok dispensasi, scan bertanda, dan status stasiun. Definisi presensi manual dan koreksi status diperbarui. Peta dokumen dan progres sesi diperbarui. |
| `01-product-requirements.md` | 0.4 | FR-MD-03, FR-MD-05, FR-PRS-06, FR-PRS-07, FR-IZN-06, FR-IZN-07, FR-LAP-01, dan FR-LAP-03 diperbarui sesuai keputusan Session 4b. Catatan acceptance criteria rinci (§7) dan traceability (§9) merujuk `04`. |
| `02-user-roles-and-permissions.md` | 0.3 | Keputusan Session 4b ditambahkan di §1. `HA-PRS-03`, `HA-PRS-04`, `HA-IZN-06`, `HA-LAP-02`, `HA-LAP-03`, dan `HA-LAP-04` diperjelas: pembatalan presensi manual, hapus koreksi, perubahan per kelompok, daftar presensi rombel, dan isi riwayat di portal. |
| `03-user-flow.md` | 0.3 | UF-02, UF-28, UF-12, UF-14, UF-15, UF-16, UF-17, UF-18, UF-19, UF-22, UF-27, dan UF-29 diperbarui sesuai keputusan Session 4b. Rujukan ke `04` ditambahkan. |
| `05-business-rules.md` | 0.2 | BR-STS-07, BR-SCN-10, BR-KOR-08, BR-KOR-09, BR-IZN-07, BR-IZN-10, BR-IZN-11, BR-DRT-06, BR-DRT-07, dan BR-KAL-03 menjadi DECISION. BR-KOR-11 diganti dengan pembatalan presensi manual. BR-KOR-10, BR-IZN-05, BR-IZN-09, BR-JAM-10 (jadwal hari ini sebagai lapisan tersendiri), dan BR-REK-04 (hari yang belum final tidak dihitung) diperbarui. Kebutuhan data di §14 dilengkapi. |

Perubahan dokumen karena keputusan Session 5 dicatat di `06` §18, dan karena keputusan Session 6 di `07` §18.

## 14. Pertanyaan terbuka dan nilai yang dipastikan nanti

### 14.1 Pertanyaan terbuka

Session 4b tidak menjawab dan tidak menambah OQ. Session 5 menjawab OQ-11 dan OQ-12 (`13` §2), dan Session 6 menjawab OQ-09 (`07`). Daftar lengkapnya ada di `00` §8.2.

| OQ | Pertanyaan singkat | Fitur terdampak | Jadwal |
|---|---|---|---|
| OQ-08 | Jumlah stasiun scan | FS-AKN-04, FS-KIO-05 | Sebelum uji coba R1 |
| OQ-09 | Jenis hosting dan instalasi Composer | FS-MD-06 (PhpSpreadsheet), FS-LAP-05 | Terjawab di Session 6: VPS dan Composer appstarter (`07` §2) |
| OQ-10 | Provider gateway WhatsApp | FS-WA-01 s.d. FS-WA-03 | Sebelum R2 |
| OQ-11 | Matriks laporan × format; isi flyer | FS-LAP-03, FS-LAP-05, FS-LAP-06 | Terjawab di Session 5 (`13` §4, LP-08) |
| OQ-12 | Format nama file foto | FS-MD-08 | Terjawab di Session 5 (`13` IM-03) |
| OQ-13 | Desain kartu siswa baru | FS-KRT-01 | Session 7 |
| OQ-17 | Pencatatan pembukaan lampiran | FS-IZN-04, FS-IZN-06 | Session 9 |

### 14.2 Nilai yang dipastikan nanti

| Nilai | Usulan | Fitur | Dipastikan di |
|---|---|---|---|
| Kolom template import dan perlakuan NISN yang sudah ada | Ditetapkan: `13` §6.1; NISN yang sudah ada dilewati | FS-MD-06 | Session 5 (DECISION) |
| Daftar alasan penonaktifan | Ditetapkan: lulus, pindah sekolah, keluar, meninggal dunia, salah input, lainnya | FS-MD-04 | Session 5 (DECISION) |
| Format nama file foto | Ditetapkan: NISN di awal nama file (`13` IM-03) | FS-MD-08 | Session 5 (DECISION) |
| Pembulatan persentase kehadiran | Ditetapkan: bilangan bulat (`13` IE-04) | FS-LAP-03 | Session 5 (DECISION) |
| N hari aturan jam dan libur yang dimuat kiosk | Ditetapkan: 14 hari | FS-KIO-01 | Session 6 (DECISION) |
| Batas umur data kiosk | Ditetapkan: 3 hari (72 jam) | FS-KIO-01 | Session 6 (DECISION) |
| Interval sinkron, interval kontak berkala, dan ukuran kiriman | Ditetapkan: 5 detik, 60 detik, dan 100 scan | FS-KIO-03 | Session 6 (DECISION) |
| Toleransi selisih jam laptop | Ditetapkan: 2 menit | FS-KIO-04 | Session 6 (DECISION) |
| Batas waktu tanpa kontak sebelum stasiun disorot | Ditetapkan: 10 menit | FS-KIO-05 | Session 6 (DECISION) |
| Jeda pengabaian NISN yang sama di kiosk | Ditetapkan: 5 detik | FS-KIO-02 | Session 6 (DECISION) |
| Interval pembaruan dashboard | Ditetapkan: 30 detik | FS-LAP-01 | Session 6 (DECISION) |
| Ukuran foto standar dan foto kiosk | Ditetapkan: 600×800 px dan 300×400 px | FS-MD-07, FS-KIO-01 | Session 6 (DECISION); ukuran tampil di Session 7 |
| Masa berlaku login akun stasiun | Ditetapkan: 90 hari sejak kontak terakhir | FS-AKN-01, FS-AKN-04 | Session 6 (DECISION) |
| Lama hasil scan tampil dan jenis bunyi | — | FS-KIO-02 | Session 7 |
| Jumlah slip per halaman A4 | — | FS-AKN-05 | Session 7 |
| Aturan password, username, dan panjang password awal | — | FS-AKN-02, FS-AKN-03 | Session 9 |
| Batas percobaan login dan masa berlaku sesi staf dan siswa | — | FS-AKN-01 | Session 9 |
| Format dan ukuran file unggahan | — | §4.9 | Session 9 |
| Rentang nilai batas mundur | 0–31 hari | FS-PRS-10 | Session 9 |

## Riwayat perubahan

| Versi | Tanggal | Perubahan |
|---|---|---|
| 0.1 | 2026-10-03 | Draft awal dari Session 4b: 40 fitur R1 dirinci beserta acceptance criteria, 9 fitur R2/R3 sebagai kerangka, ketentuan umum, traceability, dan keputusan Session 4b. |
| 0.2 | 2026-10-04 | Keputusan Session 5 (§2.4). FS-MD-09 (atribut tambahan siswa) ditambahkan beserta AC-MD-09-01 s.d. AC-MD-09-03. FS-MD-03 (tingkat tidak dapat diubah setelah ada penempatan), FS-MD-04, FS-MD-05, FS-MD-06, dan FS-MD-08 diperbarui, dengan AC-MD-04-07, AC-MD-05-04, AC-MD-06-05, dan AC-MD-08-03 ditambahkan. FS-KIO-04 (tanda di luar aturan dinilai ulang), FS-PRS-05 (definisi koreksi), FS-PRS-11, FS-IZN-01 s.d. FS-IZN-03 (paling banyak 3 lampiran), FS-IZN-06, FS-LAP-01, FS-LAP-03 (pembulatan bilangan bulat, AC-LAP-03-01), §4.4, §4.5, §11, §12, dan §14 diperbarui. |
| 0.3 | 2026-10-04 | Keputusan Session 6 (§2.5, `07`). §4.5 dan §4.6 merujuk mekanisme di `07`. Nilai parameter FS-AKN-01, FS-AKN-04, FS-MD-07, FS-KIO-01 s.d. FS-KIO-05, dan FS-LAP-01 ditetapkan, dan usulan batas unggah FS-MD-08 ditambahkan. Masa berlaku akun stasiun disebut masa login, bukan sesi (FS-AKN-01, FS-AKN-04, FS-KIO-01, FS-KIO-03, FS-KIO-04). Selisih jam diukur server dari jam laptop di setiap kiriman (FS-KIO-03, FS-KIO-04). FS-AKN-03, FS-MD-03, FS-MD-04, FS-IZN-03, pengantar §7, §11, §13, §14.1, dan §14.2 diperbarui. OQ-09 terjawab. |
