# Spensada — Business Rules

| Item | Nilai |
|---|---|
| Versi | 0.2 (draft) |
| Tanggal | 2026-10-03 |
| Sumber | Discovery Session 4 (Business Rules). Diperbarui dengan keputusan Session 4b (Feature Specification, `04` §2). |
| Bergantung pada | [00-project-overview.md](00-project-overview.md): label status, glosarium, risiko (`R-xx`), dan pertanyaan terbuka (`OQ-xx`). [01-product-requirements.md](01-product-requirements.md): ID requirement (`FR-*`, `NFR-*`). [02-user-roles-and-permissions.md](02-user-roles-and-permissions.md): hak akses (`HA-*`) dan cakupan. [03-user-flow.md](03-user-flow.md): alur (`UF-*`). |
| Dokumen terkait | [04-feature-specification.md](04-feature-specification.md): spesifikasi fitur (`FS-*`) yang menerapkan aturan ini. |

Dokumen ini menetapkan aturan bisnis presensi: kalender, aturan jam dan sesi, scan, status harian, presensi manual dan koreksi, izin/sakit/dispensasi, batas mundur, mode darurat, notifikasi WhatsApp, dan rekap. Dokumen ini menjawab OQ-03, OQ-04, OQ-05, OQ-06, OQ-07, OQ-15, dan OQ-16, serta meninjau usulan Session 3 di `02` dan `03`.

## 1. Cara membaca dokumen ini

- **ID.** Setiap aturan memakai ID `BR-<TOPIK>-<NN>`. ID tidak pernah dinomori ulang. Aturan yang batal ditandai `DEPRECATED`, bukan dihapus.
- **Status.** Label status mengikuti `00`. Bila satu aturan memuat keputusan dan usulan sekaligus, keduanya disebut.
- **Jam.** Semua jam memakai WIB. Nilai jam di dokumen ini adalah contoh. Nilai sebenarnya diisi admin saat penyiapan (UF-01).
- **Nama teknis.** Tabel, kolom, dan route ditetapkan di Session 5–8. §14 merangkum kebutuhan data dari aturan ini sebagai masukan Session 5.

| Kode topik | Topik | Bagian | Modul `01` |
|---|---|---|---|
| KAL | Kalender dan hari sekolah | §3 | PRS |
| JAM | Aturan jam dan sesi | §4 | PRS, KIO |
| SCN | Scan dan sinkron | §5 | KIO |
| STS | Status harian | §6 | PRS |
| KOR | Presensi manual dan koreksi | §7 | PRS |
| IZN | Izin, sakit, dan dispensasi | §8 | IZN |
| MUN | Batas mundur | §9 | PRS, IZN |
| DRT | Mode darurat | §10 | PRS |
| WA | Notifikasi WhatsApp (R2) | §11 | WA |
| REK | Rekap | §12 | LAP |

## 2. Ringkasan keputusan Session 4

### 2.1 Keputusan

| Topik | Keputusan | OQ | Status |
|---|---|---|---|
| Hari sekolah | Senin–Sabtu (6 hari). Admin dapat mengubah pola mingguan. | OQ-03 | DECISION |
| Zona waktu | WIB (`Asia/Jakarta`, UTC+7). | OQ-05 | DECISION |
| Aturan jam | Per hari dalam seminggu (pola mingguan), ditambah jadwal khusus untuk tanggal atau rentang tanggal. | OQ-03 | DECISION |
| Libur | Libur dapat berlaku untuk semua siswa, tingkat tertentu, atau rombel tertentu. Siswa yang libur tidak dihitung Alpa. | OQ-03 | DECISION |
| Jenis presensi | Ditentukan oleh jam scan, lewat jendela masuk dan jendela pulang. Di luar jendela, kiosk menolak scan. | OQ-03 | DECISION |
| Tiba setelah sesi masuk ditutup | Kiosk menolak scan ("gerbang ditutup"), dan siswa menjadi Alpa. Guru piket mengganti Alpa lewat presensi manual beserta alasan. | OQ-03 | DECISION |
| Pulang lebih awal | Dicatat sebagai kejadian. Status harian tidak berubah. | OQ-03 | DECISION |
| Scan ganda | Scan pertama yang berlaku. | OQ-03, OQ-04 | DECISION |
| Penutupan sesi | Otomatis pada jam tutup sesi. | OQ-07 | DECISION |
| Prioritas status | Izin/sakit/dispensasi yang disetujui, lalu koreksi, lalu presensi masuk, lalu tanpa data. | OQ-04 | DECISION |
| Koreksi | Menetapkan kehadiran menjadi Hadir, Terlambat, atau Tidak hadir. Tidak tertimpa data yang datang belakangan. Perubahan ke Izin/Sakit dilakukan lewat input izin/sakit. | OQ-04 | DECISION |
| Dispensasi | Status keenam, untuk tugas atau kegiatan resmi sekolah. Menjadi jenis ketiga izin/sakit, hanya diinput staf, dan bukan ketidakhadiran. | — | DECISION |
| Batas mundur | Hari ini dan 7 hari kalender sebelumnya, diatur admin. Admin tidak dibatasi. Siswa boleh mengajukan izin/sakit untuk tanggal lampau dalam batas ini. | OQ-15 | DECISION |
| Ubah keputusan izin | Oleh staf yang berhak memverifikasi, dengan alasan. | — | DECISION |
| Risiko kartu | Risiko QR palsu dan kartu hilang diterima. Pengamannya pengawasan petugas. | OQ-06 | DECISION |
| Keadaan darurat | Mode darurat dan presensi manual per rombel. | OQ-16 | DECISION |
| Jadwal hari ini | Dapat diubah admin dan guru piket. | — | DECISION |
| Notifikasi | Pesan "tidak hadir" ditunda, dan ditahan bila jumlah siswa tercatat masuk di bawah ambang. Tidak ada pesan koreksi. | — | DECISION |

### 2.2 Tinjauan usulan Session 3

| Usulan | Rujukan | Hasil |
|---|---|---|
| Mekanisme slip akun | `02` §7.2, UF-05 | Disetujui (DECISION) |
| Admin tidak membuka kiosk | `02` §4 butir 3, `HA-KIO-01` | Disetujui (DECISION) |
| Perubahan ke Izin/Sakit lewat input izin/sakit, bukan koreksi status | UF-16 E2 | Disetujui (DECISION), lihat BR-KOR-07 |
| Izin/sakit yang diinput staf langsung disetujui | UF-19 | Disetujui (DECISION), berlaku juga untuk dispensasi (BR-IZN-04) |
| Pimpinan tidak membuka lampiran surat | `HA-IZN-05` | Diganti: pimpinan boleh membuka semua lampiran (DECISION, BR-IZN-12) |
| Status stasiun di panel | FR-KIO-12, `HA-KIO-02` | Disetujui (DECISION) |

### 2.3 Keputusan Session 4b

Usulan di dokumen ini yang berdampak ke fitur R1 ditinjau di Session 4b. Rinciannya ada di `04` §2.2.

| Aturan | Hasil |
|---|---|
| BR-KAL-03 | Disetujui: jadwal khusus berlaku untuk semua siswa. |
| BR-SCN-10, BR-KOR-08, BR-KOR-09, BR-IZN-10, BR-IZN-11, BR-DRT-06, BR-DRT-07 | Disetujui. |
| BR-STS-07 | Disetujui dan diperluas ke daftar presensi rombel dan riwayat siswa, termasuk tanggal lampau. |
| BR-IZN-07 | Disetujui. Pada dispensasi massal, siswa yang bentrok dilewati dan dilaporkan. |
| BR-KOR-11 | Diganti: presensi manual dapat dibatalkan dengan alasan. |
| BR-IZN-09 | Ditambah: perubahan keputusan dapat diterapkan ke satu kelompok dispensasi massal. |

## 3. Kalender dan hari sekolah (KAL)

| ID | Aturan | Status |
|---|---|---|
| BR-KAL-01 | **Pola mingguan.** Hari sekolah adalah Senin–Sabtu. Minggu bukan hari sekolah. Admin dapat mengubah pola ini. | DECISION |
| BR-KAL-02 | **Libur.** Admin menandai satu tanggal atau rentang tanggal sebagai libur, beserta keterangan. Libur berlaku untuk semua siswa, untuk tingkat tertentu, atau untuk rombel tertentu. Contohnya kelas 9 setelah ujian akhir, atau kelas 7 dan 9 saat kelas 8 mengikuti ANBK. | DECISION |
| BR-KAL-03 | **Jadwal khusus.** Admin membuat jadwal khusus untuk satu tanggal atau rentang tanggal, berisi aturan jam lengkap (§4.1). Contohnya Ramadan, pekan ujian, atau rapat guru. Jadwal khusus mengalahkan pola mingguan hanya pada tanggal yang dicakupnya, dan berlaku untuk semua siswa. Bila jam berbeda per tingkat, admin memakai jam masuk yang paling lambat dan jam pulang yang paling awal. | DECISION (jadwal khusus: Session 4; berlaku untuk semua siswa: Session 4b) |
| BR-KAL-04 | **Hari sekolah pengganti.** Jadwal khusus yang dipasang pada hari yang menurut pola mingguan bukan hari sekolah menjadikan tanggal itu hari sekolah, misalnya hari Minggu sebagai pengganti. | RECOMMENDATION |
| BR-KAL-05 | **Hari sekolah bagi siswa.** Status harian hanya dihitung pada hari sekolah bagi siswa (syaratnya di bawah tabel). Di luar hari tersebut siswa tidak memiliki status, sehingga juga tidak Alpa. | DECISION (libur tidak dihitung Alpa); RECOMMENDATION (syarat 1 dan 4) |
| BR-KAL-06 | **Masa aktif siswa.** Sistem mengetahui sejak dan sampai tanggal berapa siswa aktif, dan rombel siswa pada setiap tanggal. Siswa baru tidak menjadi Alpa sebelum tanggal mulai aktifnya. Siswa yang dinonaktifkan tetap memiliki riwayat sampai tanggal nonaktifnya. | RECOMMENDATION (rincian di Session 5) |
| BR-KAL-07 | **Perubahan kalender.** Perubahan libur atau jadwal khusus berlaku untuk tanggal yang dicakupnya, termasuk tanggal lampau, dan status tanggal itu dihitung ulang. Perubahan pola mingguan berlaku mulai tanggal yang dipilih admin, dan tidak berlaku surut. Semua perubahan dicatat. | RECOMMENDATION |

Syarat BR-KAL-05. Tanggal T adalah hari sekolah bagi siswa S bila keempat syarat terpenuhi:

1. T berada di dalam rentang tanggal salah satu semester.
2. T adalah hari sekolah menurut pola mingguan, atau T memiliki jadwal khusus.
3. T tidak ditandai libur untuk semua siswa, untuk tingkat S, atau untuk rombel S.
4. S berstatus aktif pada T, dan ditempatkan di sebuah rombel pada T.

## 4. Aturan jam dan sesi (JAM)

### 4.1 Isi aturan jam

Setiap hari sekolah memiliki satu aturan jam, yang berasal dari pola mingguan atau dari jadwal khusus.

| Isian | Arti |
|---|---|
| Jam buka scan masuk | Awal jendela masuk. |
| Jam masuk | Jam masuk resmi. |
| Toleransi terlambat | Jumlah menit setelah jam masuk yang masih dihitung Hadir. Batas terlambat = jam masuk + toleransi. |
| Jam tutup sesi masuk | Akhir jendela masuk. Setelah jam ini "gerbang ditutup". |
| Jam buka scan pulang | Awal jendela pulang. |
| Jam pulang | Jam pulang resmi. |
| Jam tutup sesi pulang | Akhir jendela pulang. |

Contoh pola mingguan (bukan nilai sebenarnya):

| Hari | Buka scan masuk | Jam masuk | Toleransi | Tutup sesi masuk | Buka scan pulang | Jam pulang | Tutup sesi pulang |
|---|---|---|---|---|---|---|---|
| Senin (upacara) | 06.00 | 06.45 | 0 menit | 08.00 | 12.00 | 13.00 | 17.00 |
| Selasa–Kamis | 06.00 | 07.00 | 0 menit | 08.00 | 12.00 | 13.00 | 17.00 |
| Jumat | 06.00 | 07.00 | 0 menit | 08.00 | 10.00 | 11.00 | 15.00 |
| Sabtu | 06.00 | 07.00 | 0 menit | 08.00 | 11.00 | 12.00 | 16.00 |
| Minggu | Bukan hari sekolah | | | | | | |

Contoh jadwal khusus:

- Ramadan, untuk rentang tanggal Ramadan tahun itu: jam masuk 07.30 dan jam pulang 12.00. Jam lain disesuaikan.
- Rapat guru pada satu tanggal: jam pulang 11.00.
- Hujan deras, dibuat guru piket sebagai jadwal hari ini: toleransi 30 menit, sehingga batas terlambat menjadi 07.30.

### 4.2 Aturan

| ID | Aturan | Status |
|---|---|---|
| BR-JAM-01 | **Isi aturan jam.** Aturan jam berisi tujuh isian di §4.1. Admin mengatur pola mingguan dan jadwal khusus (`HA-PRS-01`). | DECISION (per hari, jadwal khusus, dua jendela); RECOMMENDATION (rincian isian) |
| BR-JAM-02 | **Urutan jam.** Jam buka scan masuk ≤ jam masuk ≤ batas terlambat < jam tutup sesi masuk ≤ jam buka scan pulang ≤ jam pulang < jam tutup sesi pulang. Semuanya berada pada tanggal yang sama. Sistem menolak aturan jam yang melanggar urutan ini. | RECOMMENDATION |
| BR-JAM-03 | **Dua jendela scan.** Jendela masuk berlangsung dari jam buka scan masuk sampai sebelum jam tutup sesi masuk. Jendela pulang berlangsung dari jam buka scan pulang sampai sebelum jam tutup sesi pulang. Scan di jendela masuk adalah scan masuk, dan scan di jendela pulang adalah scan pulang. Jenis presensi ditentukan oleh jam scan, bukan oleh urutan scan. | DECISION |
| BR-JAM-04 | **Hadir atau Terlambat.** Presensi masuk sampai menit batas terlambat berstatus Hadir, dan setelahnya Terlambat. Contoh dengan batas terlambat 07.00: pukul 07.00.59 Hadir, pukul 07.01.00 Terlambat. | DECISION |
| BR-JAM-05 | **Pulang lebih awal.** Presensi pulang sebelum jam pulang tercatat sebagai kejadian "pulang lebih awal". Contoh dengan jam pulang 13.00: pukul 12.59.59 pulang lebih awal, pukul 13.00.00 tidak. Status harian tidak berubah. | DECISION |
| BR-JAM-06 | **Scan ditolak.** Kiosk menolak scan di luar kedua jendela, scan pada tanggal yang bukan hari sekolah, dan scan dari siswa yang sedang libur (BR-KAL-02). Scan yang ditolak tidak dicatat sebagai presensi. Kiosk menampilkan alasannya dan mengarahkan siswa ke guru piket. Contoh pesan ada di bawah tabel. | DECISION |
| BR-JAM-07 | **Sesi ditutup otomatis.** Sesi masuk tertutup sendiri pada jam tutup sesi masuk, dan sesi pulang pada jam tutup sesi pulang. Tidak ada tombol tutup sesi. Kiosk menegakkan jendela scan dengan jamnya sendiri, termasuk saat offline. | DECISION (OQ-07) |
| BR-JAM-08 | **Setelah sesi masuk ditutup.** Siswa yang masih "belum hadir" menjadi Alpa, kecuali saat mode darurat (BR-DRT-03). Siswa yang tiba setelahnya menemui guru piket, yang mencatat presensi manual beserta alasan untuk mengganti Alpa (BR-KOR-02). | DECISION |
| BR-JAM-09 | **Setelah sesi pulang ditutup.** Siswa berstatus Hadir atau Terlambat yang tidak memiliki presensi pulang mendapat kejadian "tidak scan pulang". | DECISION |
| BR-JAM-10 | **Jadwal hari ini.** Admin dan guru piket dapat mengubah jam hari ini (`HA-PRS-07`), misalnya batas terlambat diundur karena hujan deras, atau jam pulang dimajukan karena rapat guru. Alasan wajib diisi. Perubahan disimpan sebagai jadwal khusus hari ini dan dicatat, lalu status hari ini dihitung ulang. Guru piket tidak dapat mengubah tanggal lain atau pola mingguan, dan tidak dapat menjadikan hari ini libur. | DECISION (pelaku); RECOMMENDATION (batasan guru piket) |
| BR-JAM-11 | **Server yang menentukan.** Kiosk menentukan jenis dan status scan untuk umpan balik di layar. Server menghitung ulang dari jam scan dan aturan jam tanggal itu yang tersimpan di server. Bila hasilnya berbeda, misalnya karena kiosk offline masih memakai aturan lama, hasil server yang berlaku. | RECOMMENDATION |
| BR-JAM-12 | **Zona waktu.** Semua jam memakai WIB (`Asia/Jakarta`, UTC+7). Tanggal presensi adalah tanggal menurut WIB. | DECISION (OQ-05) |

Contoh pesan kiosk untuk BR-JAM-06 dan BR-SCN-03 (teks final ditetapkan di Session 7):

| Keadaan | Pesan |
|---|---|
| Sebelum jam buka scan masuk | "Scan masuk dibuka pukul 06.00." |
| Setelah sesi masuk ditutup, sebelum jendela pulang | "Sesi masuk sudah ditutup. Temui guru piket." |
| Setelah sesi pulang ditutup | "Sesi pulang sudah ditutup." |
| Bukan hari sekolah | "Hari ini bukan hari sekolah." |
| Siswa sedang libur | "Kelas 9 libur hari ini." |
| NISN tidak dikenal | "Kartu tidak terdaftar. Temui guru piket." |
| Scan ganda | "Sudah tercatat masuk pukul 06.52." |

## 5. Scan dan sinkron (SCN)

| ID | Aturan | Status |
|---|---|---|
| BR-SCN-01 | **Pemeriksaan di kiosk.** Kiosk memeriksa setiap scan secara berurutan: (1) jam scan berada di jendela masuk atau jendela pulang hari ini; (2) NISN ada di data siswa aktif di laptop; (3) hari ini adalah hari sekolah bagi siswa itu; (4) siswa belum tercatat untuk jenis presensi yang sama. Scan yang lolos dicatat dan ditampilkan. Scan yang gagal ditolak dengan pesan sesuai §4.2. | DECISION (aturan); RECOMMENDATION (urutan) |
| BR-SCN-02 | **NISN tidak dikenal.** Scan dengan NISN yang tidak ada di data laptop tidak dicatat. Kiosk menampilkan pesan dengan bunyi berbeda, dan siswa diarahkan ke guru piket. Bila siswa itu baru ditambahkan, petugas memuat ulang data di kiosk. | RECOMMENDATION (UF-10 E1) |
| BR-SCN-03 | **Scan ganda.** Untuk setiap jenis presensi, scan pertama siswa pada tanggal itu yang berlaku. Kiosk menampilkan "sudah tercatat" beserta jamnya dengan bunyi berbeda, dan tidak mencatat ulang. Bila scan ganda datang dari stasiun lain, misalnya karena stasiun offline, server memakai scan paling awal. Scan lainnya disimpan dengan tanda "ganda" dan tidak dipakai. | DECISION (scan pertama berlaku); RECOMMENDATION (disimpan dengan tanda ganda) |
| BR-SCN-04 | **Satu presensi per jenis.** Setiap siswa memiliki paling banyak satu presensi masuk dan satu presensi pulang per tanggal. Presensi masuk adalah yang paling awal dari scan masuk dan presensi manual masuk. Aturan yang sama berlaku untuk presensi pulang. | DECISION (scan pertama berlaku); RECOMMENDATION (berlaku antarsumber) |
| BR-SCN-05 | **Kiriman ulang.** Setiap scan membawa ID unik dari kiosk. Server tidak membuat catatan baru untuk ID yang sudah diterima. | RECOMMENDATION (FR-KIO-10, NFR-04) |
| BR-SCN-06 | **Catatan scan tidak diubah.** Server menyimpan setiap scan yang diterima apa adanya, termasuk scan ganda dan scan yang ditolak server. Presensi manual dan koreksi disimpan terpisah, dan tidak mengubah catatan scan. | RECOMMENDATION |
| BR-SCN-07 | **Jam scan.** Jam scan adalah jam laptop ditambah selisihnya terhadap jam server. Selisih diukur setiap kali kiosk memuat data dan setiap kali sinkron. Kiosk mengubah jam ke WIB dengan selisih tetap +07.00, tanpa bergantung pada pengaturan zona waktu Windows. Cara kiosk tetap memakai jam yang benar bila jam Windows berubah saat kiosk berjalan ditetapkan di Session 6. | RECOMMENDATION (R-05) |
| BR-SCN-08 | **Scan ditandai.** Server menandai scan untuk ditinjau (`HA-KIO-03`) dalam empat keadaan (tabel di bawah). Peninjau menerima atau menolak scan bertanda. Scan yang ditolak tidak dipakai, dan status dihitung ulang. | RECOMMENDATION |
| BR-SCN-09 | **Risiko kartu (OQ-06).** Risiko QR palsu, foto kartu, kartu titipan, dan kartu hilang yang tidak dapat diblokir diterima. Pengamannya adalah petugas yang mencocokkan foto di layar dengan wajah siswa. Tidak ada pemblokiran kartu atau tanda khusus di kiosk. Dugaan kartu titipan ditangani lewat koreksi status (BR-KOR-06). | DECISION |
| BR-SCN-10 | **Pulang tanpa masuk.** Scan pulang dari siswa yang tidak memiliki presensi masuk tetap dicatat, tetapi tidak membuat siswa Hadir. Keadaan ini ditandai (BR-STS-07). Kiosk menampilkan umpan balik pulang seperti biasa, karena kiosk tidak tahu presensi masuk dari stasiun lain atau presensi manual. | DECISION (Session 4b) |

Keadaan untuk BR-SCN-08:

| Keadaan | Contoh penyebab | Selama belum ditinjau |
|---|---|---|
| Jam scan lebih dari 2 menit di depan jam server saat scan diterima | Jam laptop maju | Dipakai |
| Selisih jam laptop berubah lebih dari 2 menit dari pengukuran sebelumnya | Jam laptop diubah setelah data dimuat | Dipakai |
| Menurut aturan jam di server, scan berada di luar jendela, atau tanggalnya bukan hari sekolah bagi siswa | Kiosk offline memakai aturan lama | Tidak dipakai |
| Scan diterima lebih lambat dari batas mundur setelah tanggal scan | Laptop offline lebih dari seminggu | Dipakai |

Angka 2 menit adalah usulan, dan ditetapkan di Session 6.

## 6. Status harian (STS)

| ID | Aturan | Status |
|---|---|---|
| BR-STS-01 | **Status harian.** Status harian siswa adalah Hadir, Terlambat, Izin, Sakit, Dispensasi, atau Alpa. "Belum hadir" adalah keadaan sementara pada hari berjalan, bukan status final. | DECISION (Dispensasi ditambahkan di Session 4) |
| BR-STS-02 | **Urutan penentuan.** Status ditentukan dengan urutan prioritas di bawah tabel. Aturan pertama yang cocok yang dipakai. | DECISION (OQ-04) |
| BR-STS-03 | **Izin/sakit/dispensasi menang.** Izin, sakit, atau dispensasi yang disetujui menentukan status walaupun siswa memiliki presensi masuk atau koreksi. Data presensi dan koreksi tetap tersimpan dan tampil di riwayat. Bila siswa berizin ternyata hadir dan sekolah ingin statusnya Hadir, staf membatalkan data izinnya (BR-IZN-09). | DECISION |
| BR-STS-04 | **Koreksi menang atas presensi.** Koreksi status tidak berubah oleh scan atau presensi manual yang datang belakangan, termasuk scan dari stasiun yang terlambat sinkron. Hanya koreksi berikutnya, atau penghapusan koreksi, yang dapat mengubahnya. | DECISION |
| BR-STS-05 | **Kejadian pulang.** Kejadian "pulang lebih awal" dan "tidak scan pulang" tidak mengubah status harian. Kejadian ini hanya dihitung pada hari berstatus Hadir atau Terlambat. | DECISION (tidak mengubah status); RECOMMENDATION (hanya dihitung pada Hadir/Terlambat) |
| BR-STS-06 | **Dapat dihitung ulang.** Status harus selalu dapat dihitung ulang dari data sumbernya: scan, presensi manual, koreksi, izin/sakit/dispensasi, aturan jam, kalender, masa aktif siswa, dan mode darurat. Bila hasil hitungan disimpan, hasil itu diperbarui setiap kali salah satu sumbernya berubah. | RECOMMENDATION (R-13) |
| BR-STS-07 | **Penanda.** Bagi staf yang melihat daftar nama, sistem menandai dua keadaan: siswa berstatus Izin, Sakit, atau Dispensasi yang ternyata memiliki presensi masuk; dan siswa yang memiliki presensi pulang tanpa presensi masuk dan belum dikoreksi. Penanda tampil di dashboard hari ini, daftar presensi rombel per tanggal, dan riwayat siswa, termasuk untuk tanggal lampau. Penanda bersifat informasi, tidak tampil bagi siswa, dan hilang sendiri setelah datanya diperbaiki. Staf lalu memutuskan apakah data izin dibatalkan atau presensi diperbaiki. Rinciannya di `04` FS-PRS-05. | DECISION (Session 4b) |
| BR-STS-08 | **Sampai hari ini.** Status dihitung untuk hari ini dan tanggal lampau. Izin/sakit/dispensasi untuk tanggal ke depan sudah tersimpan, tetapi baru menjadi status pada tanggalnya. | RECOMMENDATION |

Urutan prioritas (BR-STS-02) untuk siswa S pada hari sekolah T (BR-KAL-05):

1. Ada izin, sakit, atau dispensasi yang disetujui untuk S pada T: statusnya Izin, Sakit, atau Dispensasi.
2. Ada koreksi status: Hadir atau Terlambat sesuai koreksi. Koreksi Tidak hadir menghasilkan Alpa.
3. Ada presensi masuk (scan atau manual): Hadir atau Terlambat menurut BR-JAM-04. Pengecualiannya presensi per rombel saat mode darurat, yang statusnya ditetapkan staf (BR-DRT-04).
4. Tidak ada satu pun dari ketiganya: "belum hadir" bila T adalah hari ini, dan sesi masuk belum ditutup atau mode darurat sedang aktif. Selain itu, Alpa.

Contoh, dengan batas terlambat 07.00 dan jam tutup sesi masuk 08.00:

| Izin/sakit/dispensasi | Koreksi | Presensi masuk | Keadaan | Status |
|---|---|---|---|---|
| Sakit, disetujui | — | 06.50 | — | Sakit |
| Izin, masih menunggu | — | — | Sudah lewat 08.00 | Alpa |
| — | Tidak hadir | 06.50 (kartu titipan) | — | Alpa |
| Sakit, disetujui | Tidak hadir | 06.50 | — | Sakit |
| — | Hadir | 07.20 | — | Hadir |
| — | — | 06.58 | — | Hadir |
| — | — | 07.05 | — | Terlambat |
| — | — | — | Pukul 07.30 | Belum hadir |
| — | — | — | Pukul 08.30 | Alpa |
| — | — | — | Pukul 08.30, mode darurat aktif | Belum hadir |
| Dispensasi, disetujui | — | — | — | Dispensasi |

## 7. Presensi manual dan koreksi (KOR)

| ID | Aturan | Status |
|---|---|---|
| BR-KOR-01 | **Presensi manual.** Staf yang berhak (`HA-PRS-03`) mencatat presensi masuk atau pulang untuk satu siswa. Isinya jenis presensi, jam, alasan (wajib), dan catatan. Jam default adalah jam sekarang, dan dapat diubah dalam tanggal yang sama. Status dihitung dari jam yang diisi, dengan aturan jam tanggal itu. Presensi disimpan dengan penanda manual dan nama penginput. | DECISION |
| BR-KOR-02 | **Tidak terikat jendela scan.** Presensi manual boleh memakai jam di luar jendela scan. Contohnya siswa yang tiba pukul 10.00 setelah ke dokter dicatat Terlambat, dan siswa yang pulang pukul 10.00 karena sakit dicatat pulang lebih awal. | DECISION |
| BR-KOR-03 | **Sudah ada presensi masuk.** Presensi manual masuk hanya dapat dicatat bila siswa belum memiliki presensi masuk pada tanggal itu. Bila sudah ada, perubahannya dilakukan lewat koreksi. | RECOMMENDATION (UF-12 E1) |
| BR-KOR-04 | **Alasan.** Alasan dipilih dari daftar: lupa kartu, kartu rusak, QR tidak terbaca, kiosk terganggu, tiba setelah sesi masuk ditutup, pulang karena sakit, pulang dengan izin, darurat, dan lainnya. Alasan "lainnya" wajib disertai catatan. | DECISION (alasan wajib); RECOMMENDATION (daftar alasan) |
| BR-KOR-05 | **Per rombel hanya saat darurat.** Presensi manual untuk banyak siswa sekaligus hanya tersedia saat mode darurat (BR-DRT-04). Di luar itu, presensi manual dicatat satu per satu. | DECISION |
| BR-KOR-06 | **Koreksi status.** Staf yang berhak (`HA-PRS-04`) menetapkan kehadiran siswa pada satu tanggal menjadi Hadir, Terlambat, atau Tidak hadir, dengan alasan wajib. Contohnya Tidak hadir karena kartu dititipkan, atau Hadir karena terlambat dengan surat dokter. | DECISION |
| BR-KOR-07 | **Izin, Sakit, dan Dispensasi bukan lewat koreksi.** Perubahan ke Izin, Sakit, atau Dispensasi dilakukan lewat input izin/sakit/dispensasi (UF-19), sehingga datanya tetap satu sumber. Koreksi Tidak hadir yang disusul izin yang disetujui menghasilkan status Izin. | DECISION |
| BR-KOR-08 | **Hapus koreksi.** Staf yang berhak dapat menghapus koreksi dengan alasan. Status lalu dihitung lagi dari presensi. | DECISION (Session 4b) |
| BR-KOR-09 | **Koreksi saat ada izin.** Koreksi tetap dapat disimpan walaupun siswa memiliki izin/sakit/dispensasi yang disetujui, tetapi status mengikuti izin (BR-STS-03). Sistem memberi peringatan sebelum menyimpan. Koreksi itu berlaku bila izin kemudian dibatalkan. | DECISION (Session 4b) |
| BR-KOR-10 | **Log perubahan presensi.** Log mencatat setiap presensi manual dan pembatalannya, koreksi dan penghapusannya, perubahan jadwal hari ini, aktivasi dan pengakhiran mode darurat, input, verifikasi, dan perubahan keputusan izin/sakit/dispensasi, tinjauan scan bertanda, perubahan pola mingguan, jadwal khusus, libur, dan tanggal semester (BR-KAL-07), serta perubahan batas mundur (BR-MUN-05): siapa, kapan, data lama, data baru, dan alasan. Daftar lengkapnya di `04` §4.4. | DECISION (log presensi; pembatalan presensi manual dan penghapusan koreksi, Session 4b); RECOMMENDATION (cakupan log lainnya) |
| BR-KOR-11 | **Salah input.** Presensi manual tidak dihapus, tetapi dapat dibatalkan dengan alasan oleh staf yang berhak (`HA-PRS-03`) dalam cakupannya, termasuk presensi per rombel saat darurat. Presensi yang dibatalkan tetap tersimpan dengan tanda dibatalkan dan tercatat di log, tetapi tidak dipakai untuk status maupun kejadian. Setelah itu, presensi yang benar dapat dicatat ulang. Kesalahan karena scan tetap diperbaiki lewat koreksi status. | DECISION (Session 4b; mengganti usulan Session 4: salah input diperbaiki lewat koreksi) |

## 8. Izin, sakit, dan dispensasi (IZN)

| ID | Aturan | Status |
|---|---|---|
| BR-IZN-01 | **Tiga jenis.** Jenisnya Izin, Sakit, dan Dispensasi. Dispensasi dipakai untuk tugas atau kegiatan resmi sekolah, seperti lomba atau study tour. | DECISION |
| BR-IZN-02 | **Tanggal.** Satu data mencakup satu tanggal atau rentang tanggal. Hanya hari sekolah bagi siswa di dalam rentang itu yang terdampak (BR-KAL-05). | RECOMMENDATION (rentang, FR-IZN-01) |
| BR-IZN-03 | **Pengajuan siswa.** Siswa hanya dapat mengajukan Izin atau Sakit, bukan Dispensasi. Tanggalnya boleh hari ini, tanggal ke depan, atau tanggal lampau dalam batas mundur (BR-MUN-01). Pengajuan berstatus "menunggu" sampai diverifikasi, dan siswa dapat membatalkannya selama masih menunggu. | DECISION (tanggal lampau, dispensasi tidak diajukan siswa); RECOMMENDATION (pembatalan oleh siswa) |
| BR-IZN-04 | **Input staf langsung disetujui.** Admin, wali kelas (rombelnya), guru piket, dan guru BK menginput izin, sakit, atau dispensasi atas nama siswa. Data langsung berstatus disetujui, dan penginput tercatat sebagai verifikator. | DECISION |
| BR-IZN-05 | **Dispensasi untuk banyak siswa.** Dispensasi dapat diinput sekaligus untuk siswa yang dipilih, satu rombel, atau satu tingkat, sesuai cakupan penginput. Sistem membuat satu data dispensasi per siswa, dengan penanda kelompok yang sama. Siswa yang bentrok (BR-IZN-07) dilewati dan dilaporkan. | DECISION (input massal; siswa bentrok dilewati, Session 4b); RECOMMENDATION (cara memilih siswa) |
| BR-IZN-06 | **Hanya yang disetujui berlaku.** Data yang menunggu, ditolak, atau dibatalkan tidak memengaruhi status presensi. | DECISION |
| BR-IZN-07 | **Tidak tumpang tindih.** Seorang siswa tidak boleh memiliki dua data izin/sakit/dispensasi berstatus menunggu atau disetujui yang mencakup tanggal yang sama. Sistem menolak dan menunjukkan data yang sudah ada. | DECISION (Session 4b) |
| BR-IZN-08 | **Verifikasi.** Staf yang berhak (`HA-IZN-03`) menyetujui atau menolak pengajuan. Catatan wajib diisi saat menolak. Bila dua staf memverifikasi bersamaan, keputusan yang tersimpan lebih dulu yang berlaku. | RECOMMENDATION (UF-18) |
| BR-IZN-09 | **Ubah keputusan.** Staf yang berhak memverifikasi (`HA-IZN-06`) dapat membatalkan data yang sudah disetujui, memendekkan rentangnya, atau mengubah penolakan menjadi persetujuan. Alasan wajib diisi. Perubahan dicatat, status dihitung ulang, dan siswa melihat keputusan terbaru. Untuk data dari dispensasi massal, pembatalan dan pemendekan dapat diterapkan sekaligus ke semua data dalam kelompok dengan satu alasan, dan setiap data tetap tercatat di log. Untuk memperpanjang rentang atau mengganti jenis, staf membatalkan data lalu membuat data baru. | DECISION (ubah keputusan; perubahan per kelompok, Session 4b); RECOMMENDATION (perpanjang dan ganti jenis lewat data baru) |
| BR-IZN-10 | **Batas mundur saat verifikasi.** Pengajuan yang dibuat dalam batas mundur tetap dapat diverifikasi walaupun tanggalnya sudah melewati batas mundur saat diverifikasi. Untuk perubahan keputusan (BR-IZN-09), batas mundur berlaku pada tanggal yang terdampak perubahan. | DECISION (Session 4b) |
| BR-IZN-11 | **Tanggal ke depan.** Izin, sakit, dan dispensasi boleh dicatat untuk tanggal ke depan selama masih di tahun ajaran aktif. | DECISION (Session 4b) |
| BR-IZN-12 | **Lampiran.** Lampiran surat bersifat opsional. Lampiran dapat dibuka oleh siswa pemiliknya, staf yang berhak memverifikasi sesuai cakupannya, dan pimpinan untuk semua siswa (`HA-IZN-05`). Perlu tidaknya mencatat setiap pembukaan lampiran ditinjau di Session 9 (OQ-17). | DECISION (akses pimpinan); RECOMMENDATION (lampiran opsional) |

## 9. Batas mundur (MUN)

| ID | Aturan | Status |
|---|---|---|
| BR-MUN-01 | **Nilai.** Batas mundur mencakup hari ini dan 7 hari kalender sebelumnya. Contoh: pada Sabtu, 10 Oktober 2026, tanggal 3–10 Oktober masih dapat diubah. Admin dapat mengubah angka 7 (`HA-PRS-09`). | DECISION (OQ-15) |
| BR-MUN-02 | **Cakupan berlaku.** Batas mundur berlaku untuk: presensi manual dan koreksi oleh wali kelas dan guru BK; input, verifikasi, dan perubahan keputusan izin/sakit/dispensasi oleh wali kelas, guru piket, dan guru BK; serta pengajuan izin/sakit oleh siswa untuk tanggal lampau. Untuk presensi manual dan koreksi, guru piket tetap terbatas pada hari ini. | DECISION |
| BR-MUN-03 | **Admin tidak dibatasi.** Admin dapat mengubah data pada tanggal berapa pun. Semua perubahannya tetap tercatat di log perubahan presensi. | DECISION |
| BR-MUN-04 | **Tidak untuk tanggal ke depan.** Presensi manual dan koreksi tidak dapat dibuat untuk tanggal ke depan. | RECOMMENDATION |
| BR-MUN-05 | **Perubahan angka.** Perubahan angka batas mundur langsung berlaku dan dicatat. | RECOMMENDATION |

## 10. Mode darurat (DRT)

| ID | Aturan | Status |
|---|---|---|
| BR-DRT-01 | **Kapan dipakai.** Mode darurat dipakai bila semua stasiun scan tidak dapat dipakai, misalnya karena listrik padam lama atau laptop rusak. Internet putus bukan alasan, karena kiosk tetap bekerja offline. | DECISION (OQ-16); RECOMMENDATION (internet putus bukan alasan) |
| BR-DRT-02 | **Pelaku.** Guru piket atau admin mengaktifkan dan mengakhiri mode darurat (`HA-PRS-08`), hanya untuk hari ini, dengan alasan. Tindakan ini dicatat. | DECISION (pelaku); RECOMMENDATION (hanya hari ini) |
| BR-DRT-03 | **Akibat.** Selama mode darurat aktif: siswa tanpa presensi tetap "belum hadir" walaupun sesi masuk sudah ditutup; pesan "tidak hadir" (R2) ditahan; dan presensi per rombel tersedia. Stasiun yang kembali berfungsi tetap dapat dipakai sesuai jendela scan. | DECISION; RECOMMENDATION (stasiun tetap dapat dipakai) |
| BR-DRT-04 | **Presensi per rombel.** Staf membuka satu rombel dan melihat siswa yang belum memiliki presensi masuk. Staf mencentang siswa yang hadir dan menandai siswa yang terlambat. Sistem mencatat presensi manual masuk dengan alasan "darurat". Statusnya Hadir atau Terlambat sesuai pilihan staf, bukan dihitung dari jam input. Siswa yang tidak dicentang tidak berubah. Pelaku dan cakupannya mengikuti `HA-PRS-03`. | DECISION |
| BR-DRT-05 | **Mengakhiri.** Setelah semua rombel tercatat, guru piket atau admin mengakhiri mode darurat. Siswa tanpa presensi dan tanpa izin/sakit/dispensasi lalu menjadi Alpa. Bila tidak diakhiri, mode darurat berakhir otomatis pukul 23.59 WIB. | DECISION |
| BR-DRT-06 | **Pesan setelah darurat.** Waktu tunda pesan "tidak hadir" dihitung sejak mode darurat diakhiri (BR-WA-02). Bila mode darurat berakhir otomatis di akhir hari, pesan "tidak hadir" hari itu tidak dikirim. | DECISION (Session 4b) |
| BR-DRT-07 | **Kejadian pulang pada hari darurat.** Pada tanggal yang pernah memakai mode darurat, kejadian "tidak scan pulang" tidak dibuat, karena stasiun tidak dapat diandalkan hari itu. | DECISION (Session 4b) |

## 11. Notifikasi WhatsApp (WA) — R2

Aturan di bagian ini diputuskan sekarang, dan diimplementasikan di R2.

| ID | Aturan | Status |
|---|---|---|
| BR-WA-01 | **Hanya hari yang sama.** Pesan dibuat untuk kejadian pada tanggal hari ini. Data yang baru tiba di server setelah tanggal berganti tidak menghasilkan pesan. | RECOMMENDATION |
| BR-WA-02 | **Syarat pesan "tidak hadir".** Pesan "tidak hadir" dibuat sekali per hari untuk siswa yang saat itu berstatus Alpa, setelah semua syarat di bawah tabel terpenuhi. | DECISION (waktu tunda, ambang); RECOMMENDATION (syarat lain, nilai default) |
| BR-WA-03 | **Ambang pengaman.** Bila jumlah siswa yang tercatat masuk di bawah ambang saat pesan "tidak hadir" akan dibuat, semua pesan "tidak hadir" hari itu ditahan. Ambang default 50% dari siswa wajib hadir, dan dapat diubah admin. Panel admin dan guru piket menampilkan peringatan. Guru piket atau admin lalu melepas pesan (`HA-WA-03`), yang dikirim ke siswa yang saat itu masih Alpa, atau membatalkannya. | DECISION |
| BR-WA-04 | **Tanpa pesan koreksi.** Bila status berubah setelah pesan terkirim, sistem tidak mengirim pesan koreksi. Pesan jenis lain tetap mengikuti aturannya. Misalnya, scan masuk yang baru tersinkron pada hari yang sama tetap menghasilkan pesan scan masuk bila jenis itu aktif. | DECISION (tanpa pesan koreksi); RECOMMENDATION (pesan jenis lain tetap berjalan) |
| BR-WA-05 | **Pesan "tidak scan pulang".** Mengikuti pola BR-WA-02 dan BR-WA-03, dengan jam tutup sesi pulang sebagai waktu acuan, untuk siswa Hadir atau Terlambat tanpa presensi pulang. Ambangnya dihitung dari siswa hadir yang tercatat pulang. | RECOMMENDATION |
| BR-WA-06 | **Presensi manual.** Presensi manual masuk dan pulang menghasilkan pesan yang sama dengan scan masuk dan scan pulang, bila jenis notifikasinya aktif. | RECOMMENDATION |
| BR-WA-07 | **Jenis "izin".** Jenis kejadian notifikasi "izin" mencakup izin, sakit, dan dispensasi. Pesannya dibuat saat data disetujui. | RECOMMENDATION |

Syarat pesan "tidak hadir" (BR-WA-02):

1. Waktu acuan ditambah waktu tunda sudah lewat. Waktu acuan adalah jam tutup sesi masuk, atau saat mode darurat diakhiri bila lebih lambat. Waktu tunda default 60 menit, dan dapat diubah admin.
2. Mode darurat tidak aktif.
3. Tidak ada stasiun yang laporan sinkron terakhirnya hari ini masih menyisakan scan belum tersinkron (FR-WA-07). Bila syarat ini tidak kunjung terpenuhi, guru piket atau admin dapat melepas pesan seperti pada BR-WA-03.
4. Jumlah siswa yang tercatat masuk mencapai ambang pengaman (BR-WA-03).
5. Tanggal belum berganti (BR-WA-01).

Definisi untuk ambang:

- **Siswa wajib hadir:** siswa yang hari itu memiliki hari sekolah, dan tidak memiliki izin, sakit, atau dispensasi yang disetujui.
- **Siswa tercatat masuk:** siswa wajib hadir yang memiliki presensi masuk, atau koreksi Hadir/Terlambat.

## 12. Rekap (REK)

| ID | Aturan | Status |
|---|---|---|
| BR-REK-01 | **Isi rekap.** Rekap per siswa memuat jumlah hari sekolah, Hadir, Terlambat, Izin, Sakit, Dispensasi, dan Alpa, serta jumlah kejadian pulang lebih awal dan tidak scan pulang. Bentuk dan format laporan ditetapkan di `13` (OQ-11). | RECOMMENDATION |
| BR-REK-02 | **Ketidakhadiran.** Ketidakhadiran adalah Sakit, Izin, dan Alpa, sama dengan kolom ketidakhadiran di rapor. Dispensasi bukan ketidakhadiran. | DECISION |
| BR-REK-03 | **Persentase kehadiran.** (Hadir + Terlambat + Dispensasi) ÷ jumlah hari sekolah bagi siswa × 100%. Hari libur, termasuk libur tingkat atau rombel, tidak dihitung. | DECISION |
| BR-REK-04 | **Hari berjalan.** Pada hari ini, siswa "belum hadir" tidak dihitung Alpa sampai sesi masuk ditutup dan mode darurat berakhir. Rekap yang mencakup hari ini diberi tanda bahwa data hari ini belum final. | RECOMMENDATION |
| BR-REK-05 | **Rombel per tanggal.** Rekap per rombel memakai rombel tempat siswa ditempatkan pada setiap tanggal. Siswa yang pindah rombel tercatat di rombel lama untuk tanggal sebelum pindah. | RECOMMENDATION (R-14) |

## 13. Contoh penerapan

Contoh di bawah memakai aturan jam hari Selasa di §4.1: batas terlambat 07.00, jam tutup sesi masuk 08.00, dan jam pulang 13.00.

| No | Kejadian | Hasil | Aturan |
|---|---|---|---|
| 1 | Siswa scan pukul 07.00.40. | Hadir. | BR-JAM-04 |
| 2 | Siswa scan pukul 07.01.05. | Terlambat. | BR-JAM-04 |
| 3 | Siswa tiba dan scan pukul 08.05. | Kiosk menolak karena sesi masuk sudah ditutup, dan siswa sudah Alpa. Guru piket mencatat presensi manual pukul 08.10 dengan alasan "tiba setelah sesi masuk ditutup", sehingga status menjadi Terlambat. | BR-JAM-06, BR-JAM-08, BR-KOR-02 |
| 4 | Siswa scan di Gerbang 1 (offline) pukul 06.50, lalu di Gerbang 2 (online) pukul 06.52. | Gerbang 2 ikut mencatat karena tidak tahu scan di Gerbang 1. Setelah Gerbang 1 sinkron, server memakai scan pukul 06.50, dan scan pukul 06.52 disimpan dengan tanda ganda. | BR-SCN-03 |
| 5 | Siswa scan pulang pukul 12.40. | Kejadian pulang lebih awal. Status tetap Hadir. | BR-JAM-05, BR-STS-05 |
| 6 | Siswa scan pukul 10.00, di antara jendela masuk dan jendela pulang. | Kiosk menolak dan mengarahkan siswa ke guru piket. | BR-JAM-06 |
| 7 | Siswa memiliki Sakit yang disetujui, tetapi scan masuk pukul 06.50. | Sakit. Wali kelas melihat penanda di dashboard dan dapat membatalkan data sakit bila siswa memang hadir. | BR-STS-03, BR-STS-07, BR-IZN-09 |
| 8 | Kartu siswa A dipindai temannya pukul 06.50. Guru piket mengetahuinya pukul 09.00. | Guru piket mengoreksi A menjadi Tidak hadir, sehingga A berstatus Alpa. Scan kartu A dari stasiun offline yang tersinkron pukul 10.00 tidak mengubah status. | BR-KOR-06, BR-STS-04 |
| 9 | Lanjutan nomor 8: pukul 11.00 orang tua A mengabarkan A sakit, lalu wali kelas menginput Sakit. | Sakit. | BR-STS-02, BR-KOR-07 |
| 10 | Hujan deras. Pukul 07.10 guru piket mengundur batas terlambat hari ini ke 07.30, saat Gerbang 1 sedang offline. | Gerbang 1 masih menampilkan Terlambat untuk scan pukul 07.20, tetapi server menghitung Hadir. | BR-JAM-10, BR-JAM-11 |
| 11 | Kelas 9 libur karena ujian kelas 9 sudah selesai. | Siswa kelas 9 tidak dihitung Alpa, dan scan mereka ditolak kiosk. | BR-KAL-02, BR-KAL-05, BR-JAM-06 |
| 12 | Internet sekolah mati sampai pukul 10.00, sementara stasiun tetap mencatat scan offline. | Pukul 09.00 (08.00 + 60 menit), server mencatat 0% siswa masuk, sehingga pesan "tidak hadir" ditahan dan panel menampilkan peringatan. Setelah stasiun sinkron, guru piket melepas pesan, yang hanya dikirim ke siswa yang masih Alpa. | BR-WA-02, BR-WA-03 |
| 13 | Listrik padam sejak pagi. Guru piket mengaktifkan mode darurat pukul 06.30, wali kelas mencatat per rombel, dan mode darurat diakhiri pukul 10.00. | Sampai pukul 10.00, siswa tanpa presensi tetap belum hadir. Setelah itu, siswa yang tidak dicentang dan tidak berizin menjadi Alpa. Pesan "tidak hadir" dibuat paling cepat pukul 11.00. | BR-DRT-03 s.d. BR-DRT-06 |
| 14 | Pada hari Rabu, siswa mengajukan Sakit untuk Senin–Selasa minggu itu. | Diterima karena masih dalam batas mundur. Setelah diverifikasi wali kelas, Senin dan Selasa berstatus Sakit. | BR-IZN-03, BR-MUN-01 |
| 15 | Guru BK menginput dispensasi lomba untuk lima siswa dari tiga rombel. | Kelima siswa langsung berstatus Dispensasi, dan persentase kehadiran mereka tidak turun. | BR-IZN-04, BR-IZN-05, BR-REK-03 |

## 14. Dampak ke desain database (masukan Session 5)

Status: RECOMMENDATION. Nama tabel dan kolom ditetapkan di Session 5 (`06-database-design.md`).

| Kebutuhan data | Isi minimal | Aturan |
|---|---|---|
| Pola mingguan | Per hari dalam seminggu: hari sekolah atau bukan, dan tujuh isian aturan jam. Berversi, dengan tanggal mulai berlaku. | BR-KAL-01, BR-KAL-07, BR-JAM-01 |
| Jadwal khusus | Tanggal mulai dan selesai, tujuh isian aturan jam, keterangan, dan pembuat. Untuk jadwal hari ini juga alasannya. | BR-KAL-03, BR-JAM-10 |
| Libur | Tanggal mulai dan selesai, keterangan, dan cakupan: semua siswa, satu atau beberapa tingkat, atau satu atau beberapa rombel. | BR-KAL-02 |
| Semester | Tanggal mulai dan selesai. | BR-KAL-05 |
| Masa aktif dan penempatan siswa | Tanggal mulai dan selesai aktif, serta penempatan rombel dengan tanggal mulai dan selesai. | BR-KAL-05, BR-KAL-06, BR-REK-05 |
| Catatan scan | ID unik dari kiosk (unik di database), stasiun, siswa, jam scan (WIB), jam laptop asli, selisih jam, waktu diterima server, jenis menurut kiosk, hasil di server (dipakai, ganda, atau ditolak), tanda tinjauan beserta alasannya, dan hasil tinjauan. Tidak pernah diubah isinya atau dihapus. | BR-SCN-03 s.d. BR-SCN-08 |
| Presensi harian | Satu baris per siswa per tanggal (siswa + tanggal unik): presensi masuk dan pulang beserta jam dan sumbernya (scan atau manual), serta koreksi status (nilai, alasan, pelaku, waktu, dan data penghapusan). Status hasil hitungan boleh disimpan sebagai salinan (BR-STS-06). | BR-SCN-04, BR-STS-02, BR-KOR-06 |
| Presensi manual | Jenis, jam, alasan, catatan, penginput, serta penanda presensi per rombel darurat beserta status yang dipilih staf. Data pembatalan: tanda dibatalkan, alasan, pelaku, dan waktu. | BR-KOR-01, BR-DRT-04, BR-KOR-11 |
| Izin/sakit/dispensasi | Siswa, jenis, tanggal mulai dan selesai, keterangan, lampiran, sumber (siswa atau staf), status (menunggu, disetujui, ditolak, dibatalkan), pengaju, verifikator, waktu, catatan, riwayat perubahan keputusan, dan penanda kelompok untuk input massal beserta lampiran bersamanya. | BR-IZN-01 s.d. BR-IZN-12 |
| Mode darurat | Satu baris per periode (mode darurat dapat diaktifkan lagi pada hari yang sama): tanggal, waktu aktif, pelaku dan alasan, serta waktu berakhir dengan pelakunya atau penanda berakhir otomatis. | BR-DRT-02, BR-DRT-05 |
| Status stasiun | Waktu kontak dan sinkron terakhir, jumlah scan belum tersinkron yang dilaporkan, selisih jam terakhir, dan waktu data terakhir dimuat. | FR-KIO-12, BR-SCN-08, BR-WA-02 |
| Pengaturan | Batas mundur (hari), waktu tunda pesan (menit), dan ambang pengaman (persen). | BR-MUN-01, BR-WA-02, BR-WA-03 |
| Log perubahan presensi | Jenis data, siswa, tanggal, data lama, data baru, alasan, pelaku, waktu, dan penanda kelompok untuk tindakan massal. | BR-KOR-10 |
| Data siswa | NISN (teks), nama, dan nomor WA orang tua/wali yang opsional dan disimpan dalam format baku 62… (`04` FS-MD-04). | FR-MD-03 |
| Tinjauan scan bertanda | Keputusan, catatan, peninjau, dan waktu, terpisah dari isi catatan scan. | BR-SCN-08 |
| Outbox WA (R2) | Jenis kejadian, siswa, tanggal, status kirim, status tahan, serta pelaku yang melepas atau membatalkan. | BR-WA-01 s.d. BR-WA-07 |

Catatan waktu (R-11):

- Zona waktu aplikasi diset `Asia/Jakarta` (`appTimezone` di `app/Config/App.php`, saat ini masih `UTC`), dan zona waktu sesi MySQL `+07:00`. Kolom tanggal-waktu berisi jam WIB, dan tanggal presensi adalah tanggal WIB.
- Indonesia tidak memakai daylight saving time, sehingga selisih +07:00 tetap sepanjang tahun.

## 15. Perubahan pada dokumen lain

| Dokumen | Versi | Perubahan |
|---|---|---|
| `00-project-overview.md` | 0.3 | OQ-03 s.d. OQ-07, OQ-15, dan OQ-16 terjawab; OQ-17 ditambahkan. R-02, R-05, R-09, R-11, dan R-13 diperbarui; R-21 dan R-22 ditambahkan. Glosarium diperbarui, termasuk status Dispensasi. Peta dokumen: `04` dijadwalkan di Session 4b. |
| `01-product-requirements.md` | 0.3 | FR-AKN-06, FR-KIO-04, FR-KIO-05, FR-KIO-10 s.d. FR-KIO-12, FR-PRS-02 s.d. FR-PRS-08, FR-IZN-01 s.d. FR-IZN-05, FR-LAP-01, FR-LAP-02, FR-WA-02, FR-WA-07, NFR-09, kriteria keberhasilan 5, dan AC-03 diperbarui. FR-PRS-09 s.d. FR-PRS-11, FR-IZN-06, FR-IZN-07, dan FR-WA-08 ditambahkan. |
| `02-user-roles-and-permissions.md` | 0.2 | `HA-PRS-05` menjadi DEPRECATED. `HA-PRS-07` s.d. `HA-PRS-09`, `HA-IZN-06`, dan `HA-WA-03` ditambahkan. Pimpinan dapat membuka lampiran (`HA-IZN-05`). Batas mundur ditetapkan. Usulan yang disetujui menjadi DECISION. |
| `03-user-flow.md` | 0.2 | Status UF-05 menjadi DECISION. Alur hari sekolah dan izin disesuaikan dengan aturan ini. UF-27 (mode darurat), UF-28 (ubah jadwal hari ini), dan UF-29 (ubah keputusan izin/sakit/dispensasi) ditambahkan. |

Perubahan dokumen karena keputusan Session 4b dicatat di `04` §13.

## 16. Pertanyaan terbuka

| OQ | Pertanyaan | Dibahas di |
|---|---|---|
| OQ-17 | Apakah setiap pembukaan lampiran surat oleh staf perlu dicatat (siapa dan kapan)? | Session 9 |

Nilai usulan yang perlu dipastikan saat implementasi:

| Nilai | Usulan | Aturan | Dipastikan di |
|---|---|---|---|
| Toleransi selisih jam laptop | 2 menit | BR-SCN-08 | Session 6 |
| Waktu tunda pesan "tidak hadir" | 60 menit, diatur admin | BR-WA-02 | Sebelum R2 |

## Riwayat perubahan

| Versi | Tanggal | Perubahan |
|---|---|---|
| 0.1 | 2026-10-03 | Draft awal dari Session 4. |
| 0.2 | 2026-10-03 | Keputusan Session 4b (`04` §2). BR-KAL-03, BR-SCN-10, BR-STS-07, BR-KOR-08, BR-KOR-09, BR-IZN-07, BR-IZN-10, BR-IZN-11, BR-DRT-06, dan BR-DRT-07 menjadi DECISION; BR-STS-07 diperluas. BR-KOR-11 diganti dengan pembatalan presensi manual. BR-KOR-10, BR-IZN-05, dan BR-IZN-09 diperbarui. §2.3 ditambahkan, dan kebutuhan data di §14 dilengkapi. |
