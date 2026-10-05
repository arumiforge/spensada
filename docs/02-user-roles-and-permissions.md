# Spensada — User Roles & Permissions

| Item | Nilai |
|---|---|
| Versi | 0.6 (draft) |
| Tanggal | 2026-10-05 |
| Sumber | Discovery Session 3 (User Roles & User Flow). Diperbarui dengan keputusan Session 4 (Business Rules), Session 4b (Feature Specification), Session 5 (Database Architecture), Session 6 (System Architecture), dan Session 8 (Routes / Pages / API). |
| Bergantung pada | [00-project-overview.md](00-project-overview.md): label status, glosarium, risiko (`R-xx`), dan pertanyaan terbuka (`OQ-xx`). [01-product-requirements.md](01-product-requirements.md): ID requirement (`FR-*`, `NFR-*`). |
| Dokumen terkait | [03-user-flow.md](03-user-flow.md), [04-feature-specification.md](04-feature-specification.md), [05-business-rules.md](05-business-rules.md), [06-database-design.md](06-database-design.md), [07-system-architecture.md](07-system-architecture.md), [09-page-and-route-specification.md](09-page-and-route-specification.md), [13-reporting-import-export.md](13-reporting-import-export.md) |

Dokumen ini menetapkan jenis akun, role, cakupan data, dan hak akses setiap role. Dokumen ini menjawab OQ-02 dan OQ-14, serta sebagian OQ-08. Batas mundur (OQ-15) ditetapkan di `05` dan diterapkan pada cakupan di §5.

Nama teknis seperti tabel, kolom, kunci permission, dan route ditetapkan di Session 5–8. Akun dan role disimpan di tabel `akun` dan `akun_role` (`06` §5). Hak akses di sini memakai ID `HA-<MODUL>-<NN>` dengan kode modul dari `01` §2. ID tidak pernah dinomori ulang.

## 1. Ringkasan keputusan Session 3

| Topik | Keputusan | Status |
|---|---|---|
| Model role | Role bersifat tetap. Daftar role dan matriks hak akses ditetapkan di dokumen ini dan di kode, bukan diatur lewat UI. Admin hanya memberi role ke akun. Satu akun staf boleh memiliki beberapa role. | DECISION |
| Sub-peran staf (OQ-02) | Wali kelas, guru piket, guru BK, dan pimpinan (kepala sekolah/wakasek). Admin adalah operator/TU. | DECISION |
| Siapa yang berakun staf | Semua guru dan staf sejak R1. Staf tanpa tugas khusus hanya melihat angka kehadiran. | DECISION |
| Guru piket | Role tetap. Hak piket tidak mengikuti jadwal piket harian. | DECISION |
| Data rombel lain | Wali kelas melihat detail siswa hanya untuk rombelnya. Untuk rombel lain, wali kelas hanya melihat angka. | DECISION |
| Akun siswa (OQ-14) | Dibuat otomatis dari data siswa dengan username NISN. Password awal acak dibagikan lewat slip per rombel dan wajib diganti saat login pertama. | DECISION |
| Akun staf | Login dengan username dari admin. Password awal acak dan wajib diganti saat login pertama. | DECISION |
| Lupa password | Password siswa direset oleh admin atau wali kelas (untuk rombelnya). Password staf direset oleh admin. Tidak ada reset mandiri lewat email di v1. | DECISION |
| Nomor WA orang tua dan foto | Dapat diubah oleh admin dan wali kelas (untuk rombelnya). Siswa tidak dapat mengubahnya. Setiap perubahan dicatat. | DECISION |
| Akun stasiun | Satu akun stasiun per laptop. | DECISION |
| Stasiun scan (OQ-08) | Semua stasiun berada di gerbang utama dan dipakai untuk scan masuk dan pulang. Jumlah laptop belum diketahui. | DECISION (lokasi); OPEN (jumlah) |
| Petugas | Guru piket dan satpam/staf TU. | DECISION |

Keputusan Session 4 yang mengubah dokumen ini (rinciannya di `05`):

| Topik | Perubahan | Status |
|---|---|---|
| Usulan Session 3 | Mekanisme slip akun (§7.2), admin tidak membuka kiosk (§4 butir 3), dan status stasiun di panel (`HA-KIO-02`) disetujui. | DECISION |
| Lampiran surat | Pimpinan boleh membuka semua lampiran (`HA-IZN-05`). | DECISION |
| Penutupan sesi | Sesi ditutup otomatis, sehingga `HA-PRS-05` tidak dipakai (DEPRECATED). | DECISION |
| Jadwal hari ini | Admin dan guru piket dapat mengubah jadwal hari ini (`HA-PRS-07`). | DECISION |
| Mode darurat | Admin dan guru piket mengaktifkan dan mengakhiri mode darurat (`HA-PRS-08`). | DECISION |
| Batas mundur | Hari ini dan 7 hari kalender sebelumnya, diatur admin (`HA-PRS-09`). Admin tidak dibatasi (§5). | DECISION |
| Dispensasi | Jenis ketiga izin/sakit, diinput oleh pemegang `HA-IZN-02`, dan langsung disetujui. | DECISION |
| Ubah keputusan izin | Oleh staf yang berhak memverifikasi (`HA-IZN-06`). | DECISION |
| Pesan WA yang ditahan | Admin dan guru piket melepas atau membatalkan pesan "tidak hadir" yang ditahan (`HA-WA-03`, R2). | DECISION |

Keputusan Session 4b yang mengubah dokumen ini (rinciannya di `04` §2):

| Topik | Perubahan | Status |
|---|---|---|
| Pembatalan presensi manual | Pemegang `HA-PRS-03` dapat membatalkan presensi manual dengan alasan, dalam cakupannya (`05` BR-KOR-11). | DECISION |
| Hapus koreksi | Pemegang `HA-PRS-04` dapat menghapus koreksi dengan alasan, dalam cakupannya (`05` BR-KOR-08). | DECISION |
| Ubah keputusan per kelompok | Pemegang `HA-IZN-06` dapat mengubah keputusan satu kelompok dispensasi massal sekaligus, bila semua siswanya berada dalam cakupannya. | DECISION |
| Penanda | Pemegang `HA-LAP-02` dan `HA-LAP-03` melihat penanda di daftar nama, termasuk untuk tanggal lampau (`05` BR-STS-07). Siswa tidak melihat penanda. | DECISION |
| Riwayat di portal siswa | Siswa melihat alasan koreksi dan catatan verifikasi, tanpa nama staf (`HA-LAP-04`). | DECISION |
| Daftar presensi rombel per tanggal | Untuk hari ini memakai `HA-LAP-02`, dan untuk tanggal lain memakai `HA-LAP-03` (`04` FS-LAP-02). Tidak ada hak akses baru. | RECOMMENDATION |

Keputusan Session 5 yang mengubah dokumen ini (rinciannya di `06` §2 dan `13` §2):

| Topik | Perubahan | Status |
|---|---|---|
| Atribut tambahan siswa | Admin mengelola definisi atribut tambahan siswa (`HA-MD-11`, `04` FS-MD-09). | DECISION |
| Profil siswa | `HA-MD-05` mencakup atribut opsional baru dan atribut tambahan. Pembagian hak lihatnya tidak berubah. | DECISION (atribut); RECOMMENDATION (hak lihat) |
| Export | Export mengikuti matriks laporan di `13` §4. Setiap export memerlukan `HA-LAP-05` ditambah hak melihat laporan yang sama di layar (`13` IE-05). Data siswa hanya diekspor admin. Log perubahan presensi diekspor oleh pemegang `HA-PRS-06` yang juga memegang `HA-LAP-05`, sesuai cakupannya. | DECISION (matriks); RECOMMENDATION (pembagian hak export) |
| Flyer | Flyer berisi angka saja, sehingga `HA-LAP-06` tidak berubah (OQ-11). | DECISION |

Keputusan Session 6 yang mengubah dokumen ini (rinciannya di `07` §2):

| Topik | Perubahan | Status |
|---|---|---|
| Login akun stasiun | Akun stasiun login dengan username dan password di halaman login yang sama. Login bertahan 90 hari sejak kontak terakhir, dan berakhir lebih awal bila kredensial diganti, akun dinonaktifkan, atau petugas logout (`07` ARS-30). | DECISION (90 hari); RECOMMENDATION (mekanisme) |
| Admin pertama | Dibuat lewat perintah CLI saat instalasi. Perintah serupa memulihkan akses bila satu-satunya admin lupa password (`07` ARS-49). | DECISION |
| Sesi | Status akun dan penggantian password diperiksa di setiap permintaan. Penonaktifan akun mengakhiri semua sesinya, dan penggantian password mengakhiri sesi lain (`07` ARS-47). | DECISION |
| Peta hak akses di kode | Matriks di §6 ditulis sebagai konfigurasi kode dengan kunci berupa ID `HA-*` (`07` ARS-15). | RECOMMENDATION |

Keputusan Session 8 yang mengubah dokumen ini (rinciannya di `09` §2):

| Topik | Perubahan | Status |
|---|---|---|
| Pemeriksaan sistem | Hak baru `HA-AKN-07`, "Lihat pemeriksaan sistem", hanya untuk admin (§6.1, `09` HAL-AKN-07). | DECISION |
| Kelas saya | Wali kelas mendapat menu "Kelas saya" di bawah Dashboard, yang membuka daftar presensi rombelnya hari ini (§8). Menu ini memakai `HA-LAP-02` dengan cakupan Rombel, sehingga tidak ada hak akses baru. | DECISION |
| Alamat area dan halaman awal | Panel staf di `/panel`, portal siswa di `/portal`, kiosk di `/kiosk`, dan halaman login di `/login` (§8, `09` RT-01 dan RT-18). | RECOMMENDATION |

## 2. Jenis akun

Ada tiga jenis akun. Pengunjung halaman publik tidak memakai akun.

| Jenis akun | Pemilik | Identitas login | Dibuat oleh | Password awal | Lupa password | Status |
|---|---|---|---|---|---|---|
| Akun staf | Semua guru dan staf, termasuk admin | Username dari admin | Admin | Acak, ditampilkan sekali saat dibuat | Direset oleh admin | DECISION |
| Akun siswa | Setiap siswa aktif | NISN | Otomatis dari data siswa | Acak, dibagikan lewat slip akun per rombel | Direset oleh admin atau wali kelas (untuk rombelnya) | DECISION |
| Akun stasiun | Setiap laptop stasiun scan | Username dari admin | Admin | Diisi admin saat memasang laptop | Admin mengganti kredensial | DECISION (satu akun per laptop; login 90 hari sejak kontak terakhir, Session 6, `07` ARS-30); RECOMMENDATION (rincian login; keamanan di Session 9) |

Aturan jenis akun:

1. **Wajib ganti password.** Akun staf dan akun siswa wajib mengganti password awal, atau password hasil reset, sebelum dapat membuka halaman lain. (DECISION)
2. **Akun siswa mengikuti status siswa.** Akun siswa ikut nonaktif saat siswa dinonaktifkan, misalnya karena lulus, pindah, atau keluar. Data kehadirannya tetap tersimpan. (DECISION)
3. **Satu jenis per akun.** Akun siswa hanya memiliki role Siswa, dan akun stasiun hanya memiliki role Stasiun. Role staf hanya dapat diberikan ke akun staf. (RECOMMENDATION)
4. **Username staf tidak boleh hanya berisi angka.** Semua jenis akun memakai satu halaman login. Aturan ini mencegah username staf bentrok dengan NISN. (RECOMMENDATION)
5. **Nonaktifkan, jangan hapus.** Akun yang sudah memiliki jejak di log tidak dihapus, tetapi dinonaktifkan. Akun nonaktif tidak dapat login, dan sesi yang sedang berjalan berakhir. Nama pemiliknya tetap tampil di log. (RECOMMENDATION)
6. **Password disimpan sebagai hash.** Tidak ada yang dapat melihat password pengguna, termasuk admin. Karena itu password awal hanya dapat ditampilkan atau dicetak pada saat dibuat (§7). (RECOMMENDATION; detail di Session 9)

## 3. Role

| Role | Jenis akun | Cara mendapatkan role | Ringkasan hak | Status |
|---|---|---|---|---|
| Admin | Staf | Diberikan oleh admin. Admin pertama dibuat saat instalasi lewat perintah CLI (`07` ARS-49). | Semua hak di panel staf/admin: akun, master data, aturan jam, kalender, pengaturan, dan semua data siswa. | CONFIRMED |
| Staf | Staf | Otomatis untuk setiap akun staf | Role dasar: dashboard hari ini berupa angka per rombel, dan ganti password. Di R3 juga jadwal dan pengumuman. | DECISION |
| Wali kelas | Staf | Otomatis selama staf ditetapkan sebagai wali kelas sebuah rombel pada tahun ajaran aktif | Presensi, izin/sakit/dispensasi, nomor WA orang tua, foto, dan reset password untuk siswa di rombelnya. | DECISION (hak); RECOMMENDATION (didapat dari penugasan rombel) |
| Guru piket | Staf | Diberikan oleh admin | Presensi manual dan koreksi pada hari berjalan untuk semua siswa. Input dan verifikasi izin/sakit/dispensasi untuk semua siswa dalam batas mundur. Mengubah jadwal hari ini dan mengaktifkan mode darurat. | DECISION |
| Guru BK | Staf | Diberikan oleh admin | Melihat semua siswa. Presensi manual, koreksi, serta input dan verifikasi izin/sakit/dispensasi untuk semua siswa dalam batas mundur. | DECISION |
| Pimpinan | Staf | Diberikan oleh admin | Kepala sekolah dan wakasek. Melihat dashboard, rekap, riwayat, dan lampiran surat semua rombel tanpa mengubah data. | DECISION |
| Siswa | Siswa | Otomatis | Riwayat kehadiran sendiri dan pengajuan izin/sakit. Di R3 juga jadwal dan pengumuman. | CONFIRMED |
| Stasiun | Stasiun | Otomatis saat admin membuat akun stasiun | Memuat data kiosk, mencatat scan, dan sinkron. Tidak dapat membuka panel staf atau portal siswa. | DECISION |

## 4. Aturan role

1. **Hak gabungan.** Hak efektif sebuah akun adalah gabungan hak semua role-nya, termasuk cakupannya (§5). (DECISION)
   - Contoh: wali kelas 7A yang juga guru piket boleh menginput presensi manual untuk semua siswa pada hari berjalan (dari role guru piket).
   - Ia juga boleh menginputnya untuk siswa 7A pada tanggal lampau dalam batas mundur (dari role wali kelas).
2. **Wali kelas berasal dari penugasan.** Role wali kelas tidak dicentang di halaman akun. Staf memegang role ini selama admin menetapkannya sebagai wali kelas sebuah rombel pada tahun ajaran aktif (`HA-MD-02`). (RECOMMENDATION)
   - Satu rombel memiliki satu wali kelas.
   - Bila wali kelas diganti di tengah tahun, hak langsung berpindah ke wali kelas baru.
3. **Admin tidak membuka kiosk.** Admin memegang semua hak di panel, tetapi kiosk hanya berjalan dengan akun stasiun. Dengan begitu, laptop di gerbang tidak pernah menyimpan sesi admin. (DECISION, Session 4)
4. **Pemisahan area.**
   - Akun stasiun hanya dapat membuka kiosk.
   - Akun siswa hanya dapat membuka portal siswa.
   - Akun staf tidak dapat membuka kiosk.

   (DECISION untuk akun stasiun dan akun staf, Session 4; RECOMMENDATION untuk akun siswa)
5. **Hak dicek di server.** Server memeriksa role dan cakupan data pada setiap permintaan. Menyembunyikan menu saja tidak cukup (NFR-07). (RECOMMENDATION)
6. **Selalu ada admin aktif.** Sistem menolak tindakan yang membuat tidak ada lagi akun admin aktif, misalnya admin terakhir mencabut role admin dirinya sendiri. (RECOMMENDATION)

## 5. Cakupan data

Setiap hak akses yang menyentuh data siswa memiliki cakupan:

| Cakupan | Arti |
|---|---|
| Semua | Semua siswa, semua tanggal. |
| Rombel | Siswa di rombel yang diampu sebagai wali kelas pada tahun ajaran aktif, untuk semua tanggal. Riwayat siswa tersebut dari tahun ajaran sebelumnya ikut terlihat (RECOMMENDATION). |
| Hari ini | Semua siswa, tetapi hanya untuk tanggal hari berjalan menurut WIB. Untuk `HA-PRS-07` dan `HA-PRS-08`, cakupan ini berarti jadwal atau mode darurat tanggal hari berjalan. |
| Sendiri | Hanya data siswa pemilik akun. |
| Rombelnya | Untuk siswa: data milik rombel tempat siswa terdaftar, misalnya jadwal. |
| Angka | Jumlah per rombel, tanpa nama, NISN, foto, atau data individu lain. |
| Ya | Hak tanpa cakupan data siswa, misalnya mengatur aturan jam. |
| — | Tidak berhak. |

Untuk presensi manual, koreksi, serta input, verifikasi, dan perubahan keputusan izin/sakit/dispensasi, cakupan "Semua" dan "Rombel" dibatasi batas mundur: hari ini dan 7 hari kalender sebelumnya. Angka 7 diatur admin (`05` BR-MUN-01, BR-MUN-02). Admin tidak dibatasi batas mundur (BR-MUN-03). (DECISION, OQ-15)

## 6. Matriks hak akses

Cara membaca:

- Kolom **Staf** adalah role dasar yang dimiliki setiap akun staf. Kolom role staf lainnya menunjukkan hak yang ditambahkan role tersebut. Hak efektif adalah gabungannya (§4 butir 1).
- Kolom **Status** berlaku untuk pembagian hak di baris tersebut. Status fitur itu sendiri ada di `01`.
- Hak untuk R2 dan R3 ditetapkan sekarang agar model role tidak berubah. Hak tersebut diimplementasikan bersama rilisnya.

### 6.1 Akun dan akses (AKN) — R1

| ID | Hak akses | Admin | Staf | Wali kelas | Guru piket | Guru BK | Pimpinan | Siswa | Stasiun | Rujukan | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| HA-AKN-01 | Login, logout, ganti password sendiri | Ya | Ya | Ya | Ya | Ya | Ya | Ya | Login saja | FR-AKN-04 | DECISION |
| HA-AKN-02 | Kelola akun staf: buat, ubah, nonaktifkan, reset password, beri role | Ya | — | — | — | — | — | — | — | FR-AKN-02, FR-AKN-08 | DECISION |
| HA-AKN-03 | Kelola akun stasiun: buat, ganti kredensial, nonaktifkan | Ya | — | — | — | — | — | — | — | FR-AKN-03 | DECISION |
| HA-AKN-04 | Reset password siswa | Semua | — | Rombel | — | — | — | — | — | FR-AKN-07 | DECISION |
| HA-AKN-05 | Cetak slip akun siswa | Semua | — | Rombel | — | — | — | — | — | FR-AKN-06 | DECISION (slip; mekanisme §7.2, Session 4) |
| HA-AKN-06 | Lihat status akun siswa (belum aktif, aktif, nonaktif) | Semua | — | Rombel | — | — | — | — | — | FR-AKN-05 | RECOMMENDATION |
| HA-AKN-07 | Lihat pemeriksaan sistem: hasil pemeriksaan server, waktu terakhir cron berjalan, dan antrean hitung ulang yang menunggu atau gagal | Ya | — | — | — | — | — | — | — | `07` ARS-57, `09` HAL-AKN-07 | DECISION (Session 8) |

### 6.2 Master data (MD) — R1

| ID | Hak akses | Admin | Staf | Wali kelas | Guru piket | Guru BK | Pimpinan | Siswa | Stasiun | Rujukan | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| HA-MD-01 | Kelola tahun ajaran dan semester | Ya | — | — | — | — | — | — | — | FR-MD-01 | DECISION |
| HA-MD-02 | Kelola rombel dan tetapkan wali kelas | Ya | — | — | — | — | — | — | — | FR-MD-02 | DECISION |
| HA-MD-03 | Tambah, ubah, dan nonaktifkan siswa; tempatkan siswa ke rombel | Ya | — | — | — | — | — | — | — | FR-MD-03, FR-MD-04 | DECISION |
| HA-MD-04 | Import siswa dari Excel/CSV | Ya | — | — | — | — | — | — | — | FR-MD-05 | DECISION |
| HA-MD-05 | Lihat profil siswa: NISN, nama, rombel, foto, nomor WA orang tua/wali, atribut opsional, dan atribut tambahan | Semua | — | Rombel | Semua | Semua | Semua | Sendiri | — | FR-MD-03 | RECOMMENDATION (hak lihat); DECISION (isi atribut, Session 5) |
| HA-MD-06 | Ubah nomor WA orang tua/wali | Semua | — | Rombel | — | — | — | — | — | FR-MD-09 | DECISION |
| HA-MD-07 | Unggah atau ganti foto siswa satu per satu | Semua | — | Rombel | — | — | — | — | — | FR-MD-06, FR-MD-09 | DECISION |
| HA-MD-08 | Unggah foto massal | Ya | — | — | — | — | — | — | — | FR-MD-07 | RECOMMENDATION |
| HA-MD-09 | Atur identitas sekolah: nama resmi, alamat, logo | Ya | — | — | — | — | — | — | — | FR-MD-08 | DECISION |
| HA-MD-10 | Lihat log data siswa: perubahan nomor WA, foto, dan data siswa lainnya | Semua | — | Rombel | — | Semua | Semua | — | — | FR-MD-09 | RECOMMENDATION |
| HA-MD-11 | Kelola definisi atribut tambahan siswa | Ya | — | — | — | — | — | — | — | FR-MD-10 | DECISION (Session 5) |

### 6.3 Kiosk dan stasiun (KIO) — R1

| ID | Hak akses | Admin | Staf | Wali kelas | Guru piket | Guru BK | Pimpinan | Siswa | Stasiun | Rujukan | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| HA-KIO-01 | Buka kiosk, muat data siswa aktif, catat scan, sinkron otomatis dan manual, serta logout kiosk (`10` EP-KIO-04) | — | — | — | — | — | — | — | Ya | FR-KIO-01 s.d. FR-KIO-10 | DECISION (akun stasiun; admin tidak membuka kiosk, Session 4) |
| HA-KIO-02 | Lihat status stasiun: waktu sinkron terakhir dan jumlah scan belum tersinkron | Ya | — | — | Ya | — | — | — | — | FR-KIO-12 | DECISION (Session 4) |
| HA-KIO-03 | Tinjau scan yang ditandai saat sinkron, misalnya karena jam tidak wajar: terima atau tolak | Semua | — | — | Hari ini | — | — | — | — | FR-KIO-11, `05` BR-SCN-08 | RECOMMENDATION |

### 6.4 Presensi (PRS) — R1

| ID | Hak akses | Admin | Staf | Wali kelas | Guru piket | Guru BK | Pimpinan | Siswa | Stasiun | Rujukan | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| HA-PRS-01 | Atur aturan jam: pola mingguan dan jadwal khusus untuk tanggal mana pun | Ya | — | — | — | — | — | — | — | FR-PRS-02 | DECISION |
| HA-PRS-02 | Kelola kalender sekolah, termasuk libur per tingkat atau rombel | Ya | — | — | — | — | — | — | — | FR-PRS-03 | DECISION |
| HA-PRS-03 | Input presensi manual (per rombel hanya saat mode darurat) dan pembatalannya | Semua | — | Rombel | Hari ini | Semua | — | — | — | FR-PRS-06, FR-PRS-10 | DECISION (pembatalan, Session 4b) |
| HA-PRS-04 | Koreksi status presensi dan penghapusannya | Semua | — | Rombel | Hari ini | Semua | — | — | — | FR-PRS-07 | DECISION (penghapusan, Session 4b) |
| HA-PRS-05 | Tutup sesi masuk dan sesi pulang, bila penutupan manual (OQ-07) | — | — | — | — | — | — | — | — | FR-PRS-08 | DEPRECATED (Session 4: sesi ditutup otomatis, `05` BR-JAM-07). Semula: admin dan guru piket. |
| HA-PRS-06 | Lihat log perubahan presensi | Semua | — | Rombel | Hari ini | Semua | Semua | — | — | FR-PRS-07 | RECOMMENDATION |
| HA-PRS-07 | Ubah jadwal hari ini, dengan alasan | Hari ini | — | — | Hari ini | — | — | — | — | FR-PRS-09 | DECISION (Session 4) |
| HA-PRS-08 | Aktifkan dan akhiri mode darurat, dengan alasan | Hari ini | — | — | Hari ini | — | — | — | — | FR-PRS-10 | DECISION (Session 4) |
| HA-PRS-09 | Atur batas mundur | Ya | — | — | — | — | — | — | — | FR-PRS-11 | DECISION (Session 4) |

### 6.5 Izin, sakit, dan dispensasi (IZN) — R1

| ID | Hak akses | Admin | Staf | Wali kelas | Guru piket | Guru BK | Pimpinan | Siswa | Stasiun | Rujukan | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| HA-IZN-01 | Ajukan izin/sakit (bukan dispensasi), lihat statusnya, dan batalkan selama masih menunggu | — | — | — | — | — | — | Sendiri | — | FR-IZN-01, FR-IZN-04 | CONFIRMED (ajukan); RECOMMENDATION (batalkan) |
| HA-IZN-02 | Input izin/sakit/dispensasi atas nama siswa, langsung disetujui. Dispensasi dapat diinput untuk banyak siswa sekaligus. | Semua | — | Rombel | Semua | Semua | — | — | — | FR-IZN-02, FR-IZN-07 | DECISION (pelaku: Session 3; langsung disetujui dan dispensasi: Session 4) |
| HA-IZN-03 | Verifikasi pengajuan siswa (setujui atau tolak) | Semua | — | Rombel | Semua | Semua | — | — | — | FR-IZN-03 | DECISION |
| HA-IZN-04 | Lihat daftar izin/sakit/dispensasi beserta keterangannya | Semua | — | Rombel | Semua | Semua | Semua | Sendiri | — | FR-IZN-04 | RECOMMENDATION |
| HA-IZN-05 | Buka lampiran surat | Semua | — | Rombel | Semua | Semua | Semua | Sendiri | — | FR-IZN-05 | DECISION (pimpinan, Session 4); RECOMMENDATION (pembagian lainnya) |
| HA-IZN-06 | Ubah keputusan: batalkan yang disetujui, perpendek rentang, atau ubah penolakan menjadi persetujuan, dengan alasan. Untuk dispensasi massal, dapat diterapkan ke satu kelompok bila semua siswanya dalam cakupan. | Semua | — | Rombel | Semua | Semua | — | — | — | FR-IZN-06 | DECISION (Session 4; per kelompok, Session 4b) |

Pimpinan dapat membuka lampiran (keputusan Session 4, mengganti usulan Session 3). Surat sakit termasuk data kesehatan (R-17). Perlu tidaknya mencatat setiap pembukaan lampiran ditinjau di Session 9 (OQ-17).

### 6.6 Dashboard dan laporan (LAP)

| ID | Hak akses | Admin | Staf | Wali kelas | Guru piket | Guru BK | Pimpinan | Siswa | Stasiun | Rilis | Rujukan | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| HA-LAP-01 | Dashboard hari ini: angka per rombel | Ya | Ya | Ya | Ya | Ya | Ya | — | — | R1 | FR-LAP-01 | DECISION |
| HA-LAP-02 | Dashboard hari ini: daftar nama siswa per status, beserta penanda | Semua | — | Rombel | Semua | Semua | Semua | — | — | R1 | FR-LAP-01 | DECISION (staf dan wali kelas); RECOMMENDATION (guru piket, BK, pimpinan) |
| HA-LAP-03 | Rekap per rombel untuk rentang tanggal, dan daftar presensi rombel untuk tanggal selain hari ini | Semua | — | Rombel | — | Semua | Semua | — | — | R1 | FR-LAP-02 | RECOMMENDATION |
| HA-LAP-04 | Riwayat kehadiran per siswa. Siswa melihat alasan koreksi dan catatan verifikasi tanpa nama staf. | Semua | — | Rombel | — | Semua | Semua | Sendiri | — | R1 | FR-LAP-03 | DECISION (siswa; isi riwayat di portal, Session 4b); RECOMMENDATION (staf) |
| HA-LAP-05 | Export rekap ke XLSX, CSV, dan PDF | Semua | — | Rombel | — | Semua | Semua | — | — | R2 | FR-LAP-04 | RECOMMENDATION |
| HA-LAP-06 | Buat flyer kehadiran | Ya | — | Rombel | — | — | Ya | — | — | R2 | FR-LAP-05 | RECOMMENDATION (ditinjau bersama OQ-11 di Session 5: tidak berubah, karena flyer berisi angka saja) |

### 6.7 Notifikasi WhatsApp (WA) — R2

| ID | Hak akses | Admin | Staf | Wali kelas | Guru piket | Guru BK | Pimpinan | Siswa | Stasiun | Rujukan | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| HA-WA-01 | Atur jenis notifikasi, template pesan, koneksi gateway, waktu tunda, dan ambang pengaman | Ya | — | — | — | — | — | — | — | FR-WA-02, FR-WA-05, FR-WA-07 | DECISION |
| HA-WA-02 | Lihat outbox WA dan kirim ulang pesan yang gagal | Ya | — | — | — | — | — | — | — | FR-WA-06 | RECOMMENDATION |
| HA-WA-03 | Lepas atau batalkan pesan "tidak hadir" yang ditahan | Ya | — | — | Hari ini | — | — | — | — | FR-WA-08 | DECISION (Session 4) |

### 6.8 Jadwal, pengumuman, halaman publik (INF) — R3

| ID | Hak akses | Admin | Staf | Wali kelas | Guru piket | Guru BK | Pimpinan | Siswa | Stasiun | Rujukan | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| HA-INF-01 | Kelola mata pelajaran dan jadwal pelajaran | Ya | — | — | — | — | — | — | — | FR-INF-01 | RECOMMENDATION |
| HA-INF-02 | Lihat jadwal pelajaran | Semua | Semua | Semua | Semua | Semua | Semua | Rombelnya | — | FR-INF-02 | DECISION |
| HA-INF-03 | Kelola pengumuman | Ya | — | — | — | — | — | — | — | FR-INF-03 | RECOMMENDATION |
| HA-INF-04 | Lihat pengumuman | Semua | Semua | Semua | Semua | Semua | Semua | Sasaran siswa dan publik | — | FR-INF-03 | RECOMMENDATION |

Pengunjung tanpa login hanya dapat membuka halaman login dan halaman publik. Halaman publik berisi pengumuman bersasaran publik, info sekolah, dan rekap agregat hari ini tanpa data individu (FR-INF-04, FR-INF-05).

### 6.9 Cetak kartu (KRT) — R3

| ID | Hak akses | Admin | Staf | Wali kelas | Guru piket | Guru BK | Pimpinan | Siswa | Stasiun | Rujukan | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| HA-KRT-01 | Cetak kartu siswa baru dan kartu pengganti | Ya | — | — | — | — | — | — | — | FR-KRT-01, FR-KRT-02 | DECISION |

## 7. Siklus hidup akun

### 7.1 Akun staf

1. Admin membuat akun dengan nama, username, dan role (`HA-AKN-02`).
2. Sistem membuat password awal acak dan menampilkannya sekali. Admin menyerahkannya ke staf yang bersangkutan.
3. Saat login pertama, staf wajib mengganti password.
4. Bila staf lupa password, admin meresetnya. Sistem membuat password acak baru yang juga wajib diganti.
5. Bila staf berhenti atau pindah tugas, admin menonaktifkan akunnya, atau mencabut role yang tidak lagi berlaku.

### 7.2 Akun siswa

Status akun siswa:

| Status | Arti |
|---|---|
| Belum aktif | Akun sudah ada, tetapi siswa belum mengganti password awal. Termasuk akun yang password awalnya belum pernah dibuat. |
| Aktif | Siswa sudah login dan mengganti password. |
| Nonaktif | Siswa berstatus nonaktif. Akun tidak dapat login. |

Mekanisme slip akun (DECISION, Session 4):

1. **Akun dibuat tanpa password yang dapat dipakai.** Akun siswa dibuat otomatis saat siswa ditambah atau diimpor, dengan status belum aktif.
2. **Password dibuat saat slip dicetak.** Admin atau wali kelas memilih "cetak slip akun" untuk satu rombel. Sistem lalu:
   - membuat password acak baru untuk setiap akun belum aktif di rombel itu;
   - menampilkan halaman slip siap cetak, berisi nama, NISN, rombel, password awal, alamat aplikasi, dan petunjuk singkat.

   Slip hanya dapat dilihat saat itu, karena password disimpan sebagai hash (§2 butir 6).
3. **Mencetak ulang membuat password baru.** Mencetak slip ulang untuk rombel yang sama membuat password baru, sehingga slip lama tidak berlaku lagi. Akun yang sudah aktif tidak tersentuh, dan layar memberi peringatan sebelum slip dibuat.
4. **Reset untuk satu siswa.** Siswa aktif yang lupa password direset satu per satu (`HA-AKN-04`). Hasilnya slip untuk satu siswa, dan siswa wajib mengganti password lagi.
5. **Password mudah dibaca.** Password awal acak tidak memakai karakter yang mirip, seperti `O`/`0` dan `l`/`1`. Panjang dan aturan password ditetapkan di Session 9.

### 7.3 Akun stasiun

1. Admin membuat akun stasiun dengan nama yang mudah dikenali, misalnya "Gerbang 1" (`HA-AKN-03`).
2. Admin login dengan akun stasiun sekali di laptop stasiun, di profil browser khusus kiosk (R-07), lalu memasang kiosk sebagai aplikasi di browser (`07` ARS-32).
3. Login akun stasiun bertahan 90 hari sejak kontak terakhir, sehingga petugas tidak perlu login setiap pagi (DECISION, Session 6). Mekanismenya ada di `07` ARS-30, dan rincian keamanannya di Session 9.
4. Sebelum akun dicabut, misalnya karena laptop diganti, admin memastikan stasiun tidak memiliki scan belum tersinkron (`HA-KIO-02`).
5. Setelah akun dicabut, data kiosk di laptop dihapus (R-07, `07` ARS-31).
6. Bila laptop hilang, admin langsung menonaktifkan akunnya. Scan yang belum tersinkron di laptop itu hilang.

## 8. Area dan halaman awal

Semua jenis akun memakai satu halaman login (`/login`). Setelah login, sistem mengarahkan pengguna ke area sesuai jenis akunnya. (RECOMMENDATION)

| Jenis akun | Area | Halaman awal | Isi menu |
|---|---|---|---|
| Akun staf | Panel staf/admin (`/panel`) | Dashboard hari ini (`/panel`) | Gabungan menu dari semua role-nya (`09` §4.1). Wali kelas juga mendapat menu "Kelas saya" di bawah Dashboard. |
| Akun siswa | Portal siswa (`/portal`) | Riwayat kehadiran sendiri (`/portal`) | Riwayat, izin/sakit, akun, dan di R3 jadwal serta pengumuman (`09` §4.2) |
| Akun stasiun | Kiosk (`/kiosk`) | Layar scan (`/kiosk`) | Hanya kiosk |
| Tanpa login | Halaman publik (R3) | Halaman publik (`/`); sebelum R3, `/` mengalihkan ke `/login` | Pengumuman, info sekolah, rekap agregat hari ini |

Bila password masih wajib diganti, halaman ganti password (`/akun/password`) tampil lebih dulu sebelum area mana pun. Alamat setiap halaman dan tujuan setelah login ada di `09` RT-18.

"Kelas saya" membuka daftar presensi rombel yang diampu wali kelas untuk hari ini. Menu ini tampil bagi akun yang menjadi wali kelas pada tahun ajaran aktif, termasuk admin yang juga wali kelas (`09` §4.1, HAL-LAP-03). (DECISION, Session 8)

## 9. Petugas di stasiun scan

- Semua stasiun scan berada di gerbang utama dan dipakai untuk scan masuk dan pulang (DECISION). Jumlah laptop mengikuti OQ-08.
- Petugas adalah guru piket dan satpam/staf TU (DECISION).
- Kiosk berjalan dengan akun stasiun, bukan akun pribadi petugas. Karena itu kiosk tidak mencatat siapa petugas yang berjaga (RECOMMENDATION).
- Satpam tidak memerlukan akun untuk tugas ini.
- Tindakan berisiko di kiosk dilindungi PIN atau konfirmasi petugas. Contohnya logout akun stasiun dan menghapus data lokal. Detailnya ditetapkan di Session 9 (RECOMMENDATION).

Pembagian tugas (RECOMMENDATION):

| Tugas | Guru piket | Satpam/staf TU |
|---|---|---|
| Menyalakan laptop dan memastikan kiosk siap | Ya | Ya |
| Mengawasi antrean, dan mencocokkan foto di layar dengan wajah siswa (R-02) | Ya | Ya |
| Menekan sinkron manual atau memuat ulang data di kiosk | Ya | Ya |
| Menginput presensi manual (lupa kartu, kartu rusak, QR tidak terbaca, tiba setelah sesi masuk ditutup) di panel | Ya | Arahkan siswa ke guru piket |
| Menangani dugaan kartu palsu atau scan titipan | Ya | Laporkan ke guru piket |
| Memeriksa status stasiun | Ya | — |
| Mengubah jadwal hari ini, misalnya karena hujan deras (`HA-PRS-07`) | Ya | Laporkan ke guru piket |
| Mengaktifkan mode darurat bila semua stasiun tidak dapat dipakai (`HA-PRS-08`) | Ya | Laporkan ke guru piket |

## 10. Catatan keamanan dan privasi

Rincian teknis ditulis di Session 9 (`12-security.md`).

- **Data seperlunya.** Staf tanpa tugas khusus, dan wali kelas untuk rombel lain, hanya melihat angka (DECISION). Data siswa adalah data anak (R-17, UU 27/2022).
- **Lampiran surat terbatas.** Lampiran surat hanya dapat dibuka siswa pemiliknya, staf yang berhak memverifikasi, dan pimpinan (`HA-IZN-05`). Pencatatan pembukaan lampiran mengikuti OQ-17.
- **Nomor WA orang tua dilindungi.** Siswa tidak dapat mengubah nomor WA orang tua/wali (DECISION). Ini mencegah siswa mengalihkan notifikasi ketidakhadiran (R2) ke nomornya sendiri.
- **Perubahan tercatat.**
  - Setiap perubahan presensi tercatat di log perubahan presensi (DECISION).
  - Perubahan nomor WA dan foto siswa dicatat (DECISION).
  - Reset password, perubahan role, dan pencabutan akun stasiun dicatat (RECOMMENDATION).
- **NISN bukan rahasia.** NISN tercetak di QR kartu, jadi keamanan akun siswa bergantung pada password. Pengamannya adalah password awal acak, kewajiban ganti password, dan pembatasan percobaan login (NFR-08).
- **Hak dicek di server.** Server memeriksa hak dan cakupan pada setiap permintaan, termasuk endpoint sinkron (NFR-07).

## 11. Pertanyaan terbuka terkait

OQ-07 dan OQ-15 terjawab di Session 4: `HA-PRS-05` menjadi DEPRECATED, dan batas mundur diterapkan di §5. OQ-11 terjawab di Session 5: flyer berisi angka saja, dan `HA-LAP-06` tidak berubah.

| OQ | Pertanyaan | Dampak ke dokumen ini |
|---|---|---|
| OQ-08 | Jumlah stasiun scan | Jumlah akun stasiun. Desain mendukung jumlah stasiun berapa pun. |
| OQ-17 | Pencatatan pembukaan lampiran surat | Dapat menambah pencatatan pada `HA-IZN-05`. |

## Riwayat perubahan

| Versi | Tanggal | Perubahan |
|---|---|---|
| 0.1 | 2026-10-03 | Draft awal dari Session 3. |
| 0.2 | 2026-10-03 | Keputusan Session 4 (`05`). Usulan slip akun, admin tidak membuka kiosk, dan status stasiun disetujui. Pimpinan dapat membuka lampiran. `HA-PRS-05` DEPRECATED. `HA-PRS-07` s.d. `HA-PRS-09`, `HA-IZN-06`, dan `HA-WA-03` ditambahkan. Dispensasi masuk ke hak IZN. Batas mundur diterapkan pada cakupan. Tugas petugas diperbarui. |
| 0.3 | 2026-10-03 | Keputusan Session 4b (`04` §2). Tabel keputusan Session 4b ditambahkan di §1. `HA-PRS-03` (pembatalan presensi manual), `HA-PRS-04` (hapus koreksi), `HA-IZN-06` (per kelompok), `HA-LAP-02` (penanda), `HA-LAP-03` (daftar presensi rombel per tanggal), dan `HA-LAP-04` (isi riwayat di portal) diperjelas. |
| 0.4 | 2026-10-04 | Keputusan Session 5 (`06` §2, `13` §2). Tabel keputusan Session 5 ditambahkan di §1. `HA-MD-11` (kelola atribut tambahan siswa) ditambahkan. `HA-MD-05` dan `HA-MD-10` diperjelas. `HA-LAP-06` ditinjau bersama OQ-11 tanpa perubahan. |
| 0.5 | 2026-10-04 | Keputusan Session 6 (`07`). Tabel keputusan Session 6 ditambahkan di §1. Detail login akun stasiun (§2, §7.3) dan pembuatan admin pertama (§3) diperbarui. |
| 0.6 | 2026-10-05 | Keputusan Session 8 (`09`, `10`). Tabel keputusan Session 8 ditambahkan di §1. `HA-AKN-07` (lihat pemeriksaan sistem) ditambahkan di §6.1, dan `HA-KIO-01` memuat logout kiosk. §8 memuat alamat area, halaman awal, menu akun di portal, dan menu "Kelas saya". |
