# Spensada — Reporting, Import, and Export

| Item | Nilai |
|---|---|
| Versi | 0.5 (draft, menunggu review) |
| Tanggal | 2026-10-05 |
| Sumber | Discovery Session 5 (Database Architecture). Diperbarui dengan keputusan Session 6 (System Architecture, `07`), Session 7 (UI/UX & Design System, `08`), Session 8 (Routes / Pages / API, `09`), dan Session 9 (Validation, Error Handling & Security, `11` dan `12`). |
| Bergantung pada | [00-project-overview.md](00-project-overview.md): label status, glosarium, dan pertanyaan terbuka (`OQ-xx`). [01-product-requirements.md](01-product-requirements.md): FR-MD-05, FR-MD-07, FR-LAP-02 s.d. FR-LAP-05. [02-user-roles-and-permissions.md](02-user-roles-and-permissions.md): hak akses (`HA-*`) dan cakupan. [04-feature-specification.md](04-feature-specification.md): FS-MD-05, FS-MD-06, FS-MD-08, FS-LAP-01 s.d. FS-LAP-06. [05-business-rules.md](05-business-rules.md): aturan rekap (§12). [06-database-design.md](06-database-design.md): tabel dan aturan baca status harian (§11). [07-system-architecture.md](07-system-architecture.md): library, penyimpanan file, dan foto. [08-ui-ux-design-system.md](08-ui-ux-design-system.md): label, format, PDF, dan flyer. [11-validation-and-error-handling.md](11-validation-and-error-handling.md): pesan baris import dan foto massal (§5.7, §5.8). [12-security.md](12-security.md): batas unggahan, pencegahan rumus di spreadsheet, dan log export (`SEC-*`). |
| Dokumen terkait | [09-page-and-route-specification.md](09-page-and-route-specification.md): halaman laporan dan import, alamat unduhan template dan file hasil, serta alamat export (R2). |

Dokumen ini menetapkan laporan dan formatnya, isi flyer kehadiran, template import siswa, import penempatan, dan format nama file foto untuk upload massal. Dokumen ini menjawab OQ-11 dan OQ-12.

Laporan di layar termasuk R1 dan dirinci di `04`, kecuali tampilan rekap semua rombel (LP-02) yang termasuk R2. Export ke file dan flyer termasuk R2 (`00` §6.1). Import siswa, penempatan massal, dan foto massal termasuk R1. Halaman laporan dan import, alamat unduhan, dan alamat export ada di `09` §6, §10, dan §13.

## 1. Cara membaca dokumen ini

- **ID.**
  - Laporan memakai `LP-<NN>`, termasuk flyer.
  - Import memakai `IM-<NN>`.
  - Ketentuan umum laporan dan export memakai `IE-<NN>`.
  - ID tidak pernah dinomori ulang. Butir yang batal ditandai `DEPRECATED`.
- **Status.** Label status mengikuti `00`. Rincian yang tidak dibahas di ronde diskusi Session 5 berstatus RECOMMENDATION.
- **Nama kolom.** Judul kolom file ditulis persis seperti di dokumen ini. Rombel ditulis "Kelas" di judul kolom, sesuai label layar (`08` UI-51). Teks layar dan format tanggal mengikuti `08` §9.
- **Contoh.** Contoh tanggal mengikuti `04` §1: "hari ini" adalah Selasa, 13 Oktober 2026.

## 2. Keputusan Session 5 dan 6

| Topik | Keputusan | OQ | Status |
|---|---|---|---|
| Matriks laporan × format | Tujuh laporan dengan pembagian format di §4. Rekap tersedia dalam XLSX, CSV, dan PDF. Daftar presensi harian dan riwayat siswa dalam XLSX dan PDF. Data siswa dalam XLSX dan CSV. Log perubahan presensi dalam CSV. | OQ-11 | DECISION |
| Isi flyer | Angka saja, tanpa nama siswa (LP-08). | OQ-11 | DECISION |
| Foto siswa yang ada | Nama file foto saat ini belum seragam. | OQ-12 | CONFIRMED |
| Format nama file foto | Nama file diawali 10 digit NISN. Sisa nama setelah garis bawah, spasi, atau tanda hubung diabaikan (IM-03). | OQ-12 | DECISION |
| Kolom template import siswa | NISN, nama lengkap, dan rombel wajib. NIS, jenis kelamin, tanggal lahir, alamat, nama orang tua/wali, dan nomor WA bersifat opsional. Atribut tambahan wajib atau tidak sesuai definisinya (IM-01). | — | DECISION (isi kolom); RECOMMENDATION (judul dan format) |
| NISN yang sudah ada saat import | Baris dilewati dan dilaporkan sebagai baris gagal, beserta nama pemilik NISN. Import hanya menambah siswa baru (UF-02 E2). | — | DECISION |
| Penempatan massal | Dua cara: per rombel asal ke rombel tujuan (FS-MD-05), dan lewat file import penempatan untuk pengacakan rombel (IM-02). | — | DECISION |
| Pembulatan persentase | Persentase kehadiran ditampilkan sebagai bilangan bulat (IE-04). | — | DECISION |

Keputusan Session 6 yang berdampak ke dokumen ini: pengecualian rekap rapor semester disetujui (IE-02, LP-03), PDF dibuat dengan mPDF (IE-12), dan foto massal dapat diunggah dalam satu ZIP atau beberapa file sekaligus (§8). Rinciannya ada di `07` §2.

## 3. Ketentuan umum

| ID | Ketentuan | Status |
|---|---|---|
| IE-01 | **Satu sumber angka.** Semua laporan kehadiran (LP-01 s.d. LP-05 dan LP-08), di layar maupun file, membaca `status_harian` dengan aturan baca `06` §11.3. Tidak ada laporan yang menghitung status dengan cara lain, sehingga angka di layar, file, dan flyer selalu sama. | RECOMMENDATION |
| IE-02 | **Rombel per tanggal.** Laporan per rombel memakai rombel siswa pada setiap tanggal (BR-REK-05), kecuali rekap rapor semester (LP-03). | DECISION (BR-REK-05; pengecualian LP-03, Session 6) |
| IE-03 | **Hari ini belum final.** Laporan yang mencakup hari ini mengikuti BR-REK-04. File dan flyer memuat tanda "Data hari ini belum final, dibuat pukul JJ.MM". | DECISION (BR-REK-04); RECOMMENDATION (tanda di file dan flyer) |
| IE-04 | **Pembulatan.** Persentase kehadiran dihitung dari jumlah hari, lalu dibulatkan ke bilangan bulat terdekat. Nilai tepat setengah dibulatkan ke atas, misalnya 87,5% menjadi 88% dan 87,4% menjadi 87%. Persentase baris total dihitung dari jumlah total, bukan dari rata-rata persentase baris. Pembulatan hanya dilakukan saat ditampilkan atau ditulis ke file. | DECISION (bilangan bulat); RECOMMENDATION (setengah ke atas) |
| IE-05 | **Hak dan cakupan.** Export mengikuti hak dan cakupan laporan yang sama di layar, ditambah `HA-LAP-05` (§4). Isi file sama dengan yang boleh dilihat pengguna di layar. | RECOMMENDATION |
| IE-06 | **Identitas file.** XLSX dan PDF memuat nama sekolah (FS-MD-01), judul laporan, cakupan (rombel atau sekolah), rentang tanggal, waktu dibuat, dan nama pembuat. PDF juga memuat logo sekolah dan nomor halaman. CSV hanya berisi satu baris judul kolom dan baris data. Kop, kaki halaman, dan blok tanda tangan PDF mengikuti `08` UI-60 dan UI-61. | RECOMMENDATION; DECISION (kop dan tanda tangan, Session 7) |
| IE-07 | **Nama file.** Pola: `spensada_<laporan>_<cakupan>_<mulai>_<selesai>.<ext>`, dengan tanggal `YYYYMMDD` dan huruf ASCII. Contoh: `spensada_rekap-kelas_7A_20261001_20261013.xlsx`. Nama file tidak memuat nama siswa. Riwayat siswa memakai NISN sebagai cakupan. | RECOMMENDATION |
| IE-08 | **XLSX.** NISN dan NIS ditulis sebagai sel teks, sehingga nol di depan tetap utuh (R-12). Tanggal ditulis sebagai sel tanggal, dan jumlah sebagai sel angka. Persentase ditulis sebagai angka bulat. Setiap file memiliki lembar "Keterangan" berisi arti kode dan filter yang dipakai. Sel teks yang diawali `=`, `+`, `-`, `@`, tab, atau carriage return diberi awalan petik tunggal, agar tidak dijalankan sebagai rumus saat dibuka di Excel (`12` SEC-46). | RECOMMENDATION |
| IE-09 | **CSV.** UTF-8 dengan BOM, pemisah koma, dan tanda kutip ganda untuk teks. Judul kolom memakai `snake_case`. Tanggal `YYYY-MM-DD`, jam `HH:MM:SS`, dan status memakai kode di `06`. CSV ditujukan untuk diolah aplikasi lain. Untuk dibuka di Excel, pengguna memakai XLSX. Karena CSV tetap dapat dibuka di Excel, teks yang diawali `=`, `+`, `-`, `@`, tab, atau carriage return juga diberi awalan petik tunggal seperti IE-08 (`12` SEC-46). | RECOMMENDATION |
| IE-10 | **PDF.** Ukuran A4. Rekap dan daftar memakai orientasi lanskap bila kolomnya banyak. Rekap per rombel (LP-01) dan rekap rapor (LP-03) memuat blok tanda tangan wali kelas beserta NIP (`08` UI-61). | RECOMMENDATION; DECISION (tanda tangan dan NIP, Session 7) |
| IE-11 | **Rentang.** Rentang laporan mengikuti layar asalnya. Rentang rekap (LP-01 s.d. LP-03) berada di dalam satu tahun ajaran dan tidak melewati hari ini, sama dengan FS-LAP-03. | RECOMMENDATION |
| IE-12 | **Library.** XLSX dibuat dan dibaca dengan PhpSpreadsheet, dan PDF dibuat dengan mPDF. Keduanya dipasang lewat Composer (OQ-09, C-06, `07` ARS-07 dan ARS-10). CSV dibaca dan ditulis dengan fungsi bawaan PHP. Flyer dibuat di browser dengan Canvas API tanpa library (R-19, `08` UI-65). | DECISION (mPDF dan Composer, Session 6; Canvas API, Session 7); RECOMMENDATION (PhpSpreadsheet dan CSV) |
| IE-13 | **Catatan export.** Export laporan berisi nama siswa (LP-01, LP-03 s.d. LP-07) dicatat di `log_aktivitas` dengan jenis `ekspor` (`12` SEC-59, R-17). Isian `data` memuat laporan, format, cakupan, rentang, dan jumlah baris, dan `rombel_id` diisi bila export mencakup satu rombel. Isi file tidak dicatat. Admin melihat catatan ini di halaman log aktivitas (`09` HAL-AKN-09). | RECOMMENDATION |

Contoh query rekap per rombel (LP-01) dengan aturan baca `06` §11.3. Daftar siswa diambil dari penempatan, sehingga siswa yang ditempatkan tetapi tidak memiliki hari sekolah dalam rentang tetap tampil dengan angka 0. Nilai `:final_hari_ini` dan `:pulang_ditutup` dihitung sekali di PHP dari aturan jam dan mode darurat hari ini. `:ta_selesai` adalah tanggal selesai tahun ajaran rombel itu.

```sql
SELECT s.id, s.nisn, s.nis, s.nama, s.jenis_kelamin,
  COALESCE(SUM(sh.status IS NOT NULL OR sh.tanggal < :hari_ini OR :final_hari_ini), 0) AS hari_sekolah,
  COALESCE(SUM(sh.status = 'hadir'), 0)      AS hadir,
  COALESCE(SUM(sh.status = 'terlambat'), 0)  AS terlambat,
  COALESCE(SUM(sh.status = 'izin'), 0)       AS izin,
  COALESCE(SUM(sh.status = 'sakit'), 0)      AS sakit,
  COALESCE(SUM(sh.status = 'dispensasi'), 0) AS dispensasi,
  COALESCE(SUM(sh.status = 'alpa'
      OR (sh.status IS NULL AND (sh.tanggal < :hari_ini OR :final_hari_ini))), 0) AS alpa,
  COALESCE(SUM(sh.status IS NULL AND sh.tanggal = :hari_ini AND NOT :final_hari_ini), 0) AS belum_hadir,
  COALESCE(SUM(sh.pulang_awal), 0) AS pulang_awal,
  COALESCE(SUM(sh.tanpa_pulang AND (sh.tanggal < :hari_ini OR :pulang_ditutup)), 0) AS tidak_scan_pulang
FROM (
  SELECT DISTINCT p.siswa_id
  FROM penempatan p
  WHERE p.rombel_id = :rombel_id
    AND p.tanggal_mulai <= :selesai
    AND COALESCE(p.tanggal_selesai, :ta_selesai) >= :mulai
) ps
JOIN siswa s ON s.id = ps.siswa_id
LEFT JOIN status_harian sh
  ON sh.siswa_id = ps.siswa_id
 AND sh.rombel_id = :rombel_id
 AND sh.tanggal BETWEEN :mulai AND :selesai
GROUP BY s.id, s.nisn, s.nis, s.nama, s.jenis_kelamin
ORDER BY s.nama;
```

`COALESCE` diperlukan karena `SUM` menghasilkan `NULL`, bukan 0, bila siswa tidak memiliki baris dalam rentang atau semua nilainya `NULL`.

## 4. Matriks laporan × format (OQ-11)

Status: DECISION (matriks, Session 5); RECOMMENDATION (hak export di luar `HA-LAP-05`).

| ID | Laporan | Layar | XLSX | CSV | PDF | Hak |
|---|---|---|---|---|---|---|
| LP-01 | Rekap per rombel | R1 (FS-LAP-03) | R2 | R2 | R2 | `HA-LAP-03` dan `HA-LAP-05` |
| LP-02 | Rekap semua rombel | R2 (FS-LAP-05) | R2 | R2 | R2 | `HA-LAP-05`, cakupan Semua |
| LP-03 | Rekap rapor semester | — | R2 | R2 | R2 | `HA-LAP-03` dan `HA-LAP-05` |
| LP-04 | Daftar presensi rombel per tanggal | R1 (FS-LAP-02) | R2 | — | R2 | `HA-LAP-02` (hari ini) atau `HA-LAP-03` (tanggal lain), dan `HA-LAP-05` |
| LP-05 | Riwayat kehadiran siswa (tampilan staf) | R1 (FS-LAP-04) | R2 | — | R2 | `HA-LAP-04` untuk staf, dan `HA-LAP-05` |
| LP-06 | Data siswa | R1 (FS-MD-04) | R2 | R2 | — | Admin (`HA-MD-03`) |
| LP-07 | Log perubahan presensi | R1 (FS-PRS-11) | — | R2 | — | `HA-PRS-06` dan `HA-LAP-05` |
| LP-08 | Flyer kehadiran (PNG) | R2 (FS-LAP-06) | — | — | — | `HA-LAP-06` |

Catatan:

- Kolom "Layar" menyebut rilis dan fitur tampilan di layar. Semua export ke file termasuk FS-LAP-05 (R2).
- Siswa tidak mengekspor riwayatnya sendiri. Portal siswa hanya menampilkan riwayat (FS-LAP-04).
- LP-06 memuat nomor WA dan data pribadi lain, sehingga hanya admin yang dapat mengekspornya.

## 5. Rincian laporan

### LP-01 — Rekap per rombel

| Item | Isi |
|---|---|
| Tujuan | Ringkasan kehadiran setiap siswa satu rombel untuk rentang tanggal. Sama dengan FS-LAP-03. |
| Filter | Tahun ajaran, rombel, dan rentang tanggal (IE-11). |
| Baris | Satu baris per siswa yang ditempatkan di rombel itu pada salah satu tanggal dalam rentang (IE-02). Urut nama. |
| Kolom | No, NISN, NIS, Nama, L/P, Hari sekolah, Hadir, Terlambat, Izin, Sakit, Dispensasi, Alpa, Belum hadir (hanya bila hari ini belum final), Ketidakhadiran (Sakit + Izin + Alpa), Kehadiran (%), Pulang lebih awal, Tidak scan pulang, dan Keterangan (misalnya "pindah"). |
| Total | Baris total menjumlahkan semua kolom. Persentase total mengikuti IE-04. Siswa tanpa hari sekolah dalam rentang, misalnya siswa yang periode aktifnya dibatalkan (`06` §6.6), tampil dengan angka 0 dan persentase "—". |
| Kepala | Kelas, wali kelas, tahun ajaran, rentang, dan jumlah siswa. |
| Status | RECOMMENDATION (kolom); DECISION (format) |

### LP-02 — Rekap semua rombel

| Item | Isi |
|---|---|
| Tujuan | Ringkasan kehadiran semua rombel dalam satu tabel untuk pimpinan dan guru BK. Menjawab catatan "rekap seluruh sekolah" di FS-LAP-03. |
| Filter | Tahun ajaran dan rentang tanggal. |
| Baris | Satu baris per rombel di tahun ajaran itu, dikelompokkan per tingkat, dengan subtotal per tingkat dan total sekolah. |
| Kolom | Kelas, Tingkat, Wali kelas, Jumlah siswa, Hari-siswa (jumlah hari sekolah semua siswa), Hadir, Terlambat, Izin, Sakit, Dispensasi, Alpa, Ketidakhadiran, dan Kehadiran (%). |
| Hitungan | Setiap hari-siswa dihitung di rombel tempat siswa berada pada tanggal itu (IE-02). Persentase = Σ(Hadir + Terlambat + Dispensasi) ÷ Σ hari-siswa. |
| Status | DECISION (laporan dan format); RECOMMENDATION (kolom) |

### LP-03 — Rekap rapor semester

| Item | Isi |
|---|---|
| Tujuan | Jumlah Sakit, Izin, dan Alpa setiap siswa selama satu semester, untuk diisi ke kolom ketidakhadiran rapor (BR-REK-02). |
| Filter | Tahun ajaran, semester, dan rombel. Bila semester belum selesai, rentang berakhir hari ini dan file diberi tanda "semester belum selesai". |
| Baris | Siswa yang ditempatkan di rombel itu pada tanggal terakhir rentang. Urut nama. |
| Kolom | No, NISN, NIS, Nama, Sakit, Izin, Tanpa keterangan (Alpa), Hari sekolah, Dispensasi, dan Kehadiran (%). |
| Hitungan | Semua hari sekolah siswa dalam semester dihitung, termasuk hari ketika siswa berada di rombel lain. Ini berbeda dari IE-02, karena rapor menggambarkan kehadiran siswa, bukan kehadiran di satu rombel. Siswa yang pindah masuk diberi keterangan "pindah dari <rombel>". |
| Status | DECISION (laporan dan format; cara menghitung siswa pindah, Session 6) |

### LP-04 — Daftar presensi rombel per tanggal

| Item | Isi |
|---|---|
| Tujuan | Status setiap siswa satu rombel pada satu tanggal, untuk dicetak atau diarsipkan. Sama dengan FS-LAP-02. |
| Filter | Kelas dan tanggal. |
| Kolom | No, NISN, Nama, Status, Jam masuk, Sumber masuk, Jam pulang, Sumber pulang, Kejadian pulang, Keterangan (izin/sakit/dispensasi atau koreksi beserta alasannya), dan Perlu diperiksa (penanda BR-STS-07). |
| Ringkasan | Jumlah per status di bawah tabel. |
| Status | DECISION (format); RECOMMENDATION (kolom) |

### LP-05 — Riwayat kehadiran siswa

| Item | Isi |
|---|---|
| Tujuan | Riwayat harian seorang siswa untuk staf, misalnya untuk pemanggilan orang tua oleh guru BK. Sama dengan tampilan staf di FS-LAP-04. |
| Filter | Siswa dan periode. |
| Kepala | Identitas siswa dan ringkasan periode dengan kolom yang sama seperti LP-01. |
| Kolom harian | Tanggal, Hari, Kelas, Status, Jam masuk dan sumbernya, Jam pulang dan sumbernya, Kejadian pulang, Izin/sakit/dispensasi (jenis, status, catatan, verifikator), Koreksi (kehadiran, alasan, pelaku), dan Perlu diperiksa. |
| Status | DECISION (format); RECOMMENDATION (kolom) |

### LP-06 — Data siswa

| Item | Isi |
|---|---|
| Tujuan | Daftar siswa beserta atributnya, untuk arsip dan pengolahan data. |
| Filter | Sama dengan daftar siswa di FS-MD-04: rombel, status, tanpa nomor WA, dan tanpa foto. |
| Kolom | Kolom template import IM-01 dengan urutan yang sama, ditambah Status, Tanggal mulai aktif, Tanggal terakhir aktif, Alasan nonaktif, Foto (ada atau tidak), dan Status akun. |
| Catatan | File ini tidak dapat dipakai untuk memperbarui data lewat import, karena NISN yang sudah ada selalu dilewati (IM-01). |
| Status | DECISION (format); RECOMMENDATION (kolom) |

### LP-07 — Log perubahan presensi

| Item | Isi |
|---|---|
| Tujuan | Salinan log untuk pemeriksaan di luar aplikasi. |
| Filter | Sama dengan FS-PRS-11. |
| Kolom | `waktu`, `jenis`, `nisn`, `nama_siswa`, `tanggal_mulai`, `tanggal_selesai`, `data_lama`, `data_baru` (JSON), `alasan`, `pelaku`, dan `kelompok` (`06` §12.1). |
| Status | DECISION (format); RECOMMENDATION (kolom) |

### LP-08 — Flyer kehadiran

| Item | Isi |
|---|---|
| Tujuan | Gambar PNG ringkasan kehadiran untuk dibagikan staf, misalnya ke grup WhatsApp (FR-LAP-05, UF-24). |
| Cakupan | Satu rombel atau total sekolah, untuk satu tanggal. |
| Isi | Nama dan logo sekolah, judul, tanggal, cakupan, jumlah siswa yang hari itu memiliki hari sekolah, jumlah Hadir, Terlambat, Izin, Sakit, Dispensasi, Alpa, dan Belum hadir (selama data belum final), serta persentase kehadiran (IE-04). Flyer total sekolah juga memuat angka per tingkat. |
| Tidak dimuat | Nama, foto, NISN, atau data individu siswa lain (DECISION, OQ-11). |
| Hari ini | Bila status hari ini belum final, flyer memuat tanda IE-03: "Data hari ini belum final, dibuat pukul JJ.MM". |
| Status | DECISION (angka saja); RECOMMENDATION (rincian isi). Canvas API dan format potret 1080×1350 px DECISION (Session 7, `08` UI-65, UI-66); susunan flyer RECOMMENDATION (`08` UI-67). |

## 6. Import siswa (IM-01)

Rincian template untuk FS-MD-06. Alur pratinjau dan penyimpanan tetap mengikuti FS-MD-06.

### 6.1 Kolom template

| Judul kolom | Wajib | Format | Validasi |
|---|---|---|---|
| NISN | Ya | Teks 10 digit | Tepat 10 digit angka. NISN 9 digit dari sel angka yang kehilangan nol di depan dinyatakan gagal dengan petunjuk (FS-MD-06). Tidak boleh ganda di dalam file. NISN yang sudah ada di database, aktif maupun nonaktif, dinyatakan gagal dengan alasan "NISN sudah terdaftar atas nama <nama>" (DECISION, Session 5). |
| Nama Lengkap | Ya | Teks | Paling banyak 100 karakter. |
| Kelas | Ya | Teks | Sama dengan nama rombel di tahun ajaran tujuan, tanpa membedakan huruf besar dan kecil. Judul kolom "Rombel" juga diterima. |
| NIS | Tidak | Teks | Paling banyak 20 karakter. Tidak boleh ganda di dalam file maupun dengan data yang sudah ada. |
| Jenis Kelamin | Tidak | `L` atau `P` | Juga menerima "Laki-laki" dan "Perempuan". |
| Tanggal Lahir | Tidak | Tanggal | Sel tanggal Excel, atau teks `DD-MM-YYYY` atau `DD/MM/YYYY`. |
| Alamat | Tidak | Teks | Paling banyak 255 karakter. |
| Nama Orang Tua/Wali | Tidak | Teks | Paling banyak 100 karakter. |
| Nomor WA Orang Tua/Wali | Tidak | Teks | Divalidasi dan dibakukan seperti FS-MD-04. |
| `<kode atribut tambahan>` | Mengikuti atribut | Mengikuti tipe | Satu kolom per atribut tambahan yang aktif (FS-MD-09). Judul kolom adalah kode atribut. Atribut wajib harus terisi. Tipe `pilihan` harus berisi salah satu pilihan. |

Status: DECISION (isi kolom dan perlakuan NISN yang sudah ada, Session 5); RECOMMENDATION (judul, format, dan panjang kolom).

Aturan validasi setiap kolom mengikuti `11` §4, dan teks alasan baris gagal ada di `11` §5.7.

### 6.2 Aturan file

| ID | Aturan | Status |
|---|---|---|
| IM-01 | **Import siswa dan template.** Import siswa (FS-MD-06) memakai kolom di §6.1. Admin mengunduh template XLSX dari halaman import (`GET /panel/siswa/import/template`, `09` §13). Template dibuat saat diunduh, sehingga kolom atribut tambahan selalu terbaru. Lembar "Siswa" berisi judul kolom di baris 1, dengan kolom NISN dan NIS berformat teks. Lembar "Petunjuk" berisi arti setiap kolom, daftar rombel tahun ajaran tujuan, dan daftar atribut tambahan beserta pilihannya. | RECOMMENDATION |
| IM-04 | **Pencocokan kolom.** Kolom dikenali dari judulnya, tanpa membedakan huruf besar dan kecil dan tanpa spasi di awal dan akhir. Urutan kolom bebas. Kolom opsional boleh tidak ada. File ditolak sebelum validasi baris bila kolom wajib tidak ada, ada judul kolom yang tidak dikenal, atau ada judul kolom ganda (FS-MD-06 E2). | RECOMMENDATION |
| IM-05 | **CSV.** File CSV memakai UTF-8, dengan atau tanpa BOM. Pemisahnya koma atau titik koma, dikenali dari baris judul, karena Excel berbahasa Indonesia menyimpan CSV dengan titik koma. | RECOMMENDATION |
| IM-06 | **Isi sel.** Spasi di awal dan akhir dibuang. Baris yang semua selnya kosong diabaikan. Sel kosong pada kolom opsional berarti tidak diisi. | RECOMMENDATION |
| IM-07 | **Daftar baris gagal.** File CSV baris gagal (FS-MD-06 butir 3) berisi nomor baris di file asli, semua kolom asli, dan kolom alasan. Satu baris dapat memiliki beberapa alasan, dipisah titik koma, dengan teks di `11` §5.7. Teks yang diawali tanda rumus diberi awalan petik tunggal seperti IE-08 (`12` SEC-46). | RECOMMENDATION |
| IM-16 | **Batas file.** Lihat butir di bawah tabel. | DECISION (5 MB, Session 9); RECOMMENDATION (batas lain) |

Butir IM-16 (`12` SEC-46, SEC-49, `11` VAL-28):

1. File import siswa dan import penempatan berformat XLSX atau CSV, paling besar 5 MB.
2. Satu file berisi paling banyak 2.000 baris data, tanpa baris judul dan baris kosong.
3. Isi XLSX setelah diekstrak paling besar 50 MB. Hanya lembar pertama yang dibaca. Template memakai lembar "Siswa" sebagai lembar pertama (IM-01).
4. Sel XLSX yang berisi rumus ditolak sebagai baris gagal dengan alasan "Sel berisi rumus. Salin sebagai nilai, lalu unggah lagi." (`11` §5.7). Teks biasa yang diawali tanda rumus diterima sebagai teks.
5. File yang melewati batas 1 s.d. 3 ditolak sebelum validasi baris, dengan pesan di `11` VAL-28.

## 7. Penempatan massal (IM-02)

Penempatan massal memakai dua cara (DECISION, Session 5):

1. **Per rombel.** Admin memilih rombel asal dan rombel tujuan, lalu melepas siswa yang tidak naik atau lulus (FS-MD-05 butir 3). Cara ini untuk kenaikan kelas biasa.
2. **Lewat file.** Cara ini untuk pengacakan ulang rombel atau penempatan banyak siswa ke rombel berbeda. Rinciannya di bawah.

| ID | Aturan | Status |
|---|---|---|
| IM-02 | **Import penempatan.** Admin mengunduh daftar siswa aktif dari satu tahun ajaran, mengisi kolom "Kelas Tujuan", lalu mengunggahnya dengan memilih tahun ajaran tujuan dan tanggal mulai. Tanggal mulai default adalah tanggal mulai tahun ajaran tujuan. | DECISION (cara); RECOMMENDATION (rincian) |
| IM-08 | **Kolom file.** NISN (wajib), Nama, Kelas Asal, dan Kelas Tujuan. Nama dan Kelas Asal hanya sebagai bantuan membaca, dan tidak divalidasi. Format file dan pencocokan kolom mengikuti IM-04 s.d. IM-06. | RECOMMENDATION |
| IM-09 | **Validasi baris.** NISN harus milik siswa yang aktif pada tanggal mulai. Kelas Tujuan harus ada di tahun ajaran tujuan. NISN tidak boleh ganda di dalam file. Penempatan baru tidak boleh tumpang tindih dengan penempatan lain siswa itu, kecuali penempatan yang sedang berjalan di tahun ajaran yang sama, yang diakhiri sehari sebelum tanggal mulai seperti pindah rombel (FS-MD-05 butir 2). | RECOMMENDATION |
| IM-10 | **Kelas Tujuan kosong.** Baris dengan Kelas Tujuan kosong dilewati tanpa dianggap gagal, misalnya untuk siswa yang lulus atau tidak naik. Pratinjau menyebut jumlahnya. | RECOMMENDATION |
| IM-11 | **Pratinjau dan simpan.** Pratinjau menampilkan jumlah siswa per rombel tujuan, baris yang dilewati, dan baris gagal beserta alasannya. Setelah dikonfirmasi, semua baris valid disimpan dalam satu transaksi, dicatat di log data siswa dengan penanda kelompok yang sama, dan status tanggal yang terdampak dihitung ulang. | RECOMMENDATION |

## 8. Foto massal (IM-03)

Rincian format nama file untuk FS-MD-08 (OQ-12).

| ID | Aturan | Status |
|---|---|---|
| IM-03 | **Format nama file.** Nama file diawali tepat 10 digit NISN. Setelah 10 digit itu, nama file langsung diikuti titik ekstensi, atau dipisah dengan garis bawah, spasi, atau tanda hubung, lalu sisanya diabaikan. Huruf besar dan kecil pada ekstensi tidak dibedakan. | DECISION |
| IM-12 | **Contoh.** Cocok: `0012345678.jpg`, `0012345678_Budi Santoso.JPG`, `0012345678 - 7A.png`. Tidak sesuai format: `012345678.jpg` (9 digit), `00123456789.jpg` (11 digit), `Budi_0012345678.jpg` (NISN tidak di awal), `0012345678Budi.jpg` (tanpa pemisah). | DECISION |
| IM-13 | **Folder di dalam ZIP.** Foto dapat diunggah dalam ZIP (`07` ARS-54). Nama folder di dalam ZIP diabaikan; yang dicocokkan hanya nama file. | DECISION (ZIP, Session 6); RECOMMENDATION (folder diabaikan) |
| IM-14 | **Siswa yang dicocokkan.** Foto dicocokkan dengan semua siswa, aktif maupun nonaktif. Pratinjau menampilkan status siswa, dan foto siswa nonaktif tetap dapat disimpan. | RECOMMENDATION |
| IM-15 | **Foto lama sekolah.** Karena nama file foto yang ada belum seragam (CONFIRMED, OQ-12), admin perlu menambahkan NISN di depan nama file sebelum upload massal. Foto yang tidak cocok dilaporkan di pratinjau, dan dapat diunggah satu per satu (FS-MD-07). | DECISION |

Foto massal menerima JPG, PNG, dan WebP, beberapa file sekaligus atau satu ZIP, paling besar 100 MB per unggahan dan paling banyak 100 file per unggahan. ZIP berisi paling banyak 2.000 entri, dengan isi setelah diekstrak paling besar 500 MB. Setiap foto paling besar 10 MB dan 24 megapiksel. HEIC tidak diterima (DECISION Paket 10 MB, Session 9, `12` SEC-49). Bila ada beberapa file untuk satu NISN, semua file untuk NISN itu dilewati, dan pratinjau meminta admin menyisakan satu file. Keterangan per file di pratinjau ada di `11` §5.8. Foto diperkecil menjadi paling besar 600×800 px untuk foto standar dan 300×400 px untuk foto kiosk (DECISION, Session 6, `07` ARS-53). Foto kecil 120×160 px dan ukuran tampil ditetapkan di Session 7 (`08` UI-22, UI-23).

## 9. Pertanyaan terbuka dan nilai yang dipastikan nanti

Dokumen ini menjawab OQ-11 dan OQ-12. Perubahan pada dokumen lain dicatat di `06` §18, perubahan karena keputusan Session 6 di `07` §18, karena keputusan Session 7 di `08` §14, karena keputusan Session 8 di `09` §16, dan karena keputusan Session 9 di `12` §20.

| Hal | Rujukan | Dipastikan di |
|---|---|---|
| Library XLSX dan PDF, serta instalasi lewat Composer | IE-12, OQ-09 | Session 6: mPDF dan instalasi lewat Composer berstatus DECISION, dan PhpSpreadsheet berstatus RECOMMENDATION (`07` ARS-10) |
| Desain template PDF dan flyer, serta cara pembuatan flyer PNG | IE-10, IE-12, LP-08 | Ditetapkan di Session 7 (`08` §10.3, §11) |
| Ukuran file dan jumlah baris maksimal import | IM-01, IM-02, IM-16 | Ditetapkan di Session 9: 5 MB dan 2.000 baris data (`12` SEC-49) |
| Format gambar dan ukuran maksimal foto | IM-03, §8 | Ditetapkan di Session 9: JPG, PNG, atau WebP, 10 MB per foto, dan 100 MB per unggahan (`12` SEC-49) |
| Pencatatan export berisi nama siswa | IE-13 | Ditetapkan di Session 9: log `ekspor` (`12` SEC-59) |
| Batas jumlah baris import setelah uji beban | IM-16 | Uji beban sebelum uji coba R1 (`11` §11) |

## Riwayat perubahan

| Versi | Tanggal | Perubahan |
|---|---|---|
| 0.1 | 2026-10-04 | Draft awal dari Session 5: matriks laporan × format, rincian tujuh laporan dan flyer, template import siswa, import penempatan, format nama file foto, dan ketentuan umum. OQ-11 dan OQ-12 terjawab. |
| 0.2 | 2026-10-04 | Keputusan Session 6 (`07`). IE-02 dan LP-03 (pengecualian rekap rapor) menjadi DECISION. IE-12 memuat mPDF dan Composer. IM-13 dan §8 memuat dukungan ZIP dan ukuran foto. §2, termasuk judulnya, dan §9 diperbarui. |
| 0.3 | 2026-10-04 | Keputusan Session 7 (`08`). Judul kolom yang memuat "Rombel" diganti "Kelas", termasuk nama file contoh di IE-07. IM-01 menerima judul kolom lama "Rombel". IE-06, IE-10, IE-12, LP-08, §1, §8, dan §9 merujuk `08`. |
| 0.4 | 2026-10-05 | Keputusan Session 8 (`09`). Kepala dokumen, pengantar, dan IM-01 merujuk halaman, alamat unduhan, dan alamat export di `09`. §9 diperbarui. |
| 0.5 | 2026-10-05 | Keputusan Session 9 (`11`, `12`). IE-13 (log `ekspor`), IE-08 dan IE-09 (pencegahan rumus), IM-07 (teks alasan dan pencegahan rumus), IM-16 baru (ukuran file, jumlah baris, dan sel berumus), §6.1 (rujukan aturan validasi), §8 (format dan ukuran foto massal serta file ganda per NISN), kepala dokumen, dan §9 diperbarui. |
