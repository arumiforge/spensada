# Spensada — Validation and Error Handling

| Item | Nilai |
|---|---|
| Versi | 0.1 (draft, menunggu review) |
| Tanggal | 2026-10-05 |
| Sumber | Discovery Session 9 (Validation, Error Handling & Security) |
| Bergantung pada | [00-project-overview.md](00-project-overview.md): label status dan glosarium. [04-feature-specification.md](04-feature-specification.md): isian, validasi, dan kode galat setiap fitur (`FS-*`, `E<n>`). [05-business-rules.md](05-business-rules.md): aturan jam, presensi, dan izin (`BR-*`). [06-database-design.md](06-database-design.md): tipe dan panjang kolom. [07-system-architecture.md](07-system-architecture.md): filter, transaksi, dan file (`ARS-*`). [08-ui-ux-design-system.md](08-ui-ux-design-system.md): komponen formulir, pesan umum, dan gaya bahasa. [09-page-and-route-specification.md](09-page-and-route-specification.md): formulir, pengalihan, dan halaman galat (`RT-*`). [10-api-specification.md](10-api-specification.md): kode galat API (`API-03`). [12-security.md](12-security.md): keputusan Session 9, password, CSRF, file, dan log (`SEC-*`). |
| Dokumen terkait | [13-reporting-import-export.md](13-reporting-import-export.md): kolom file import. |

Dokumen ini menetapkan cara Spensada memeriksa isian dan menangani galat: prinsip validasi, aturan setiap jenis isian, teks pesan validasi per fitur, pesan baris import, penanganan galat dari formulir sampai server, dan log aplikasi. Teks pesan di dokumen ini adalah teks final untuk layar, dan melengkapi teks umum di `08` UI-56.

Keputusan Session 9 dan ketentuan keamanan ada di `12`. Perubahan karena Session 9 di semua dokumen dicatat di `12` §20.

## 1. Cara membaca dokumen ini

- **ID.**
  - Aturan validasi memakai `VAL-<NN>`.
  - Aturan penanganan galat memakai `GAL-<NN>`.
  - ID tidak pernah dinomori ulang. Butir yang batal ditandai `DEPRECATED`.
- **Kode galat fitur.** `E1`, `E2`, dan seterusnya merujuk tabel "Keadaan kosong dan error" di `04`, misalnya FS-AKN-02 E3.
- **Status.** Label status mengikuti `00`. Keputusan dari ronde diskusi Session 9 berstatus DECISION (§2). Aturan dan teks lain berstatus RECOMMENDATION.
- **Teks pesan.** Teks ditulis persis seperti tampil, mengikuti gaya bahasa `08` §9: kalimat pendek, menyebut yang salah dan cara memperbaikinya. Bagian dalam kurung siku, misalnya `[label]`, diganti nilai sebenarnya. Pesan untuk siswa memakai "kamu" bila menyapa (`08` UI-56).
- **Contoh.** Contoh tanggal mengikuti `04` §1: hari ini Selasa, 13 Oktober 2026.

## 2. Keputusan Session 9

Keputusan Session 9 yang berdampak ke validasi dan penanganan galat. Daftar lengkapnya ada di `12` §2.1.

| Topik | Keputusan | Rujukan | Status |
|---|---|---|---|
| Aturan password | 8 s.d. 64 karakter, tidak ada di daftar password umum, dan tidak memuat NISN, username, atau tanggal lahir. Tanpa aturan wajib huruf besar, angka, atau simbol. | VAL-21, `12` SEC-03 | DECISION |
| Pembatasan login | Pesan kunci menyebut jam login dapat dicoba lagi, atau meminta menghubungi wali kelas atau admin untuk kunci 24 jam. | §5.1, `12` SEC-08 | DECISION |
| Format dan ukuran unggahan | Paket 10 MB (`12` SEC-49). | VAL-28 | DECISION |
| PIN petugas | Tepat 6 digit. | VAL-22, `12` SEC-21 | DECISION |

## 3. Prinsip validasi

| ID | Aturan | Status |
|---|---|---|
| VAL-01 | **Server menentukan.** Semua isian diperiksa di server (NFR-07). Atribut HTML seperti `required`, `maxlength`, `min`, `max`, dan `type`, serta pemeriksaan JavaScript, hanya membantu pengguna lebih cepat, dan aturannya sama dengan di server. Formulir tetap dapat dikirim tanpa JavaScript. | RECOMMENDATION |
| VAL-02 | **Pembersihan sebelum diperiksa.** Spasi, tab, dan baris baru di awal dan akhir isian teks dibuang, kecuali password dan PIN. Baris baru `\r\n` diubah menjadi `\n`. Teks diubah ke bentuk Unicode NFC dengan `Normalizer` (ekstensi `intl`, wajib untuk CI4), agar huruf beraksen yang diketik dengan cara berbeda dianggap sama. Isian satu baris tidak boleh memuat baris baru. Spasi ganda di tengah nama orang, nama rombel, dan keterangan pendek diubah menjadi satu spasi. Karakter kontrol ditolak lebih dulu oleh filter `invalidchars` (`12` SEC-43). | RECOMMENDATION |
| VAL-03 | **Kosong berarti tidak diisi.** Isian yang kosong setelah dibersihkan dianggap tidak diisi, sehingga alasan yang hanya berisi spasi ditolak sebagai kosong (`04` §4.3 butir 3). Isian opsional yang kosong disimpan sebagai `NULL`, bukan teks kosong. | RECOMMENDATION |
| VAL-04 | **Dua lapis pemeriksaan.** Controller memeriksa bentuk isian dengan validasi CI4 (`$this->validateData()`), dengan aturan dan pesan di file aturan per formulir di `app/Validation/`. Service memeriksa aturan bisnis yang membutuhkan data lain, misalnya cakupan, batas mundur, tumpang tindih, dan urutan jam, lalu melempar pengecualian `GagalValidasi` yang memuat pesan per isian. Controller menangani keduanya dengan cara yang sama (VAL-07). Hanya isian yang disebut di aturan validasi yang diteruskan ke service (`12` SEC-44). | RECOMMENDATION |
| VAL-05 | **Panjang dalam karakter.** Panjang dihitung per karakter Unicode, sama dengan `max_length` CI4 (`mb_strlen`) dan `VARCHAR(n)` `utf8mb4` di MySQL. Batas panjang setiap isian sama dengan atau lebih kecil dari kolomnya di `06`, sehingga mode strict MySQL (`07` ARS-43) tidak pernah menolak data yang sudah lolos validasi. | RECOMMENDATION |
| VAL-06 | **Bahasa pesan.** `Config\App::$defaultLocale` bernilai `id`, dengan `supportedLocales` `['id']` dan `negotiateLocale` mati. Pesan aturan bawaan CI4 diterjemahkan di `app/Language/id/Validation.php`, dengan `{field}` berisi label isian di layar, bukan nama teknisnya. Pesan khusus di §5 menggantikan pesan bawaan. Pesan bawaan tidak pernah tampil dalam bahasa Inggris. | RECOMMENDATION |
| VAL-07 | **Tampilan galat formulir.** Formulir yang gagal dialihkan kembali dengan isian lama dan pesan per isian (`09` RT-08 butir 2). Pesan tampil di bawah isiannya, dan isian ditandai dengan `aria-invalid="true"` dan `aria-describedby` ke pesannya. Di atas formulir tampil ringkasan "Periksa [n] isian yang ditandai." dengan tautan ke setiap isian, dan fokus pindah ke ringkasan itu. Password, PIN, dan file tidak diisi ulang. Bila formulir memuat isian file yang sudah dipilih, ringkasan menambahkan "File perlu dipilih ulang." Komponennya di `08` UI-28. | RECOMMENDATION |
| VAL-08 | **Urutan pemeriksaan.** (1) Token CSRF (`12` SEC-30). (2) Login, area, dan hak (`07` ARS-13). (3) Bentuk semua isian, dan semua pesannya ditampilkan bersama. (4) Aturan bisnis, yang hanya diperiksa bila bentuk isian sudah benar. Aturan bisnis yang tidak saling bergantung ditampilkan bersama. (5) Perubahan bersamaan (`09` RT-12). Penyimpanan berjalan setelah semuanya lolos, di dalam transaksi (`07` ARS-43). | RECOMMENDATION |
| VAL-09 | **Pilihan dari daftar.** Nilai pilihan, misalnya jenis izin, role, atau tingkat, harus ada di daftar yang diizinkan (`in_list`). ID data dari isian tersembunyi atau pilihan, misalnya rombel atau siswa, harus ada dan berada dalam cakupan. ID di luar cakupan dijawab sesuai `09` RT-11, karena pengguna biasa tidak dapat mengirimnya dari formulir. ID yang sudah tidak ada, misalnya rombel yang baru dihapus orang lain, ditampilkan sebagai galat isian: "[Label] yang dipilih sudah tidak ada. Pilih lagi." | RECOMMENDATION |
| VAL-10 | **Angka.** Bilangan bulat hanya berisi digit, tanpa tanda, pemisah ribuan, atau desimal. Angka desimal pada atribut tambahan menerima koma atau titik sebagai tanda desimal, dan disimpan dengan titik (`06` §6.9). | RECOMMENDATION |
| VAL-11 | **Tanggal.** Isian tanggal memakai `<input type="date">`, yang mengirim `YYYY-MM-DD`. Server memeriksa format dan tanggal yang benar-benar ada (`valid_date[Y-m-d]`), lalu batasnya terhadap hari ini menurut WIB dari service `Jam` (`07` ARS-44). Tanggal di pesan ditulis dengan format `08` §9.3, misalnya "13 Oktober 2026". | RECOMMENDATION |
| VAL-12 | **Jam.** Isian jam memakai `<input type="time">`, yang mengirim `HH:MM` 24 jam. Server juga menerima `HH.MM`, lalu menyimpannya dengan detik `00` (`06` §4.3). Jam di pesan ditulis dengan titik, misalnya "07.15" (`08` §9.3). | RECOMMENDATION |
| VAL-13 | **Isian yang tidak dikenal.** Isian yang tidak disebut di aturan validasi diabaikan, tidak ditolak, kecuali di API kiosk (VAL-14). Parameter saringan di alamat yang tidak sah, misalnya `status=xyz` atau tanggal yang salah format, diabaikan, dan daftar memakai nilai bawaannya (`09` RT-13). | RECOMMENDATION |
| VAL-14 | **API kiosk.** Badan JSON yang bukan JSON sah, atau isian tingkat atasnya hilang atau salah tipe, dijawab 400 `permintaan_rusak` (`10` API-03). Scan yang rusak di dalam kiriman yang sah ditolak satu per satu dengan kode tolak, tanpa menolak kiriman (`10` EP-KIO-03). Scan yang ditolak dicatat di server (`12` SEC-24). | RECOMMENDATION |

## 4. Aturan per jenis isian

| ID | Jenis isian | Aturan | Pesan | Status |
|---|---|---|---|---|
| VAL-15 | Nama orang: nama siswa, nama staf, nama orang tua/wali | 2 s.d. 100 karakter, satu baris. Huruf dari bahasa apa pun, spasi, titik, koma, petik, dan tanda hubung, dengan paling sedikit satu huruf. Huruf besar dan kecil disimpan seperti diketik. | "Nama paling sedikit 2 karakter." · "Nama paling panjang 100 karakter." · "Nama hanya boleh berisi huruf, spasi, titik, koma, petik, dan tanda hubung." | RECOMMENDATION |
| VAL-16 | Alasan dan catatan | Lihat tabel di bawah. Boleh beberapa baris. Paling sedikit 5 karakter, agar alasan seperti "-" atau "ok" tidak diterima. | "[Label] wajib diisi." · "[Label] paling sedikit 5 karakter." · "[Label] paling panjang [n] karakter." | RECOMMENDATION |
| VAL-17 | Keterangan pendek: keterangan libur, jadwal khusus, dan pola mingguan | 1 s.d. 100 karakter, satu baris. Keterangan libur tampil di kiosk, sehingga dianjurkan paling banyak 40 karakter agar muat di layar hasil scan. | "Keterangan wajib diisi." · "Keterangan paling panjang 100 karakter." | RECOMMENDATION |
| VAL-18 | NISN | Tepat 10 digit setelah dibersihkan (FS-MD-04). Unik di antara semua siswa. | "NISN harus 10 digit angka." · Bagi admin: "NISN sudah terdaftar atas nama [nama] ([kelas atau status])." | RECOMMENDATION |
| VAL-19 | NIS | 1 s.d. 20 karakter, satu baris: huruf, angka, titik, garis miring, dan tanda hubung. Unik bila diisi. | "NIS hanya boleh berisi huruf, angka, titik, garis miring, dan tanda hubung." · "NIS paling panjang 20 karakter." · "NIS sudah terdaftar atas nama [nama]." | RECOMMENDATION |
| VAL-20 | Username staf dan stasiun | Diubah ke huruf kecil, lalu harus cocok dengan `^[a-z][a-z0-9._]{2,29}$`: 3 s.d. 30 karakter, diawali huruf, berisi huruf kecil, angka, titik, dan garis bawah. Karena diawali huruf, username tidak pernah hanya berisi angka (`02` §2 butir 4). Unik di semua akun. | "Username 3 sampai 30 karakter." · "Username diawali huruf, dan hanya boleh berisi huruf kecil, angka, titik, dan garis bawah." · "Username sudah dipakai." | RECOMMENDATION |
| VAL-21 | Password baru | `12` SEC-03. Pesan untuk setiap aturan di kolom berikut, ditampilkan satu per satu sesuai urutan aturan. Di bawah isian selalu tampil bantuan: "Paling sedikit 8 karakter. Boleh memakai spasi. Kalimat pendek yang mudah kamu ingat lebih aman daripada kata yang rumit." Teks untuk staf memakai "Anda". | "Password paling sedikit 8 karakter." · "Password paling panjang 64 karakter." · "Password terlalu panjang. Kurangi huruf beraksen atau simbol." (lebih dari 72 byte) · "Password ini terlalu umum dan mudah ditebak. Pilih password lain." · "Password tidak boleh memuat NISN atau username." · "Password tidak boleh memuat tanggal lahir." · "Password tidak boleh berupa huruf atau angka yang berulang atau berurutan." · "Password baru harus berbeda dengan password saat ini." · "Ulangan password tidak sama." | DECISION (aturan, `12` §2.1); RECOMMENDATION (teks) |
| VAL-22 | PIN petugas | Tepat 6 digit. Bukan satu digit yang diulang, misalnya `111111`, dan bukan urutan naik atau turun, misalnya `123456` atau `654321`. Diisi dua kali. | "PIN harus 6 digit angka." · "PIN terlalu mudah ditebak. Hindari angka yang sama atau berurutan." · "Ulangan PIN tidak sama." | DECISION (6 digit); RECOMMENDATION (larangan pola) |
| VAL-23 | Nomor WA orang tua/wali | Spasi, tanda hubung, titik, dan tanda kurung dibuang. Awalan `08` diganti `628`, dan awalan `+62` diganti `62`. Hasilnya harus diawali `628` dan berisi 10 s.d. 15 digit (FS-MD-04). | "Nomor WA tidak valid. Tulis nomor ponsel yang diawali 08, misalnya 081234567890." | RECOMMENDATION |
| VAL-24 | Tanggal lahir | Tanggal yang sah, tidak setelah hari ini, dan tidak sebelum 1 Januari 2000. | "Tanggal lahir tidak valid." · "Tanggal lahir tidak boleh setelah hari ini." · "Periksa tahun lahir." | RECOMMENDATION |
| VAL-25 | Tujuh isian aturan jam | Setiap jam berbentuk `HH:MM` (VAL-12). Toleransi terlambat bilangan bulat 0 s.d. 240 menit. Urutan mengikuti BR-JAM-02, dengan pesan pada isian yang melanggar di tabel kedua di bawah. | "[Label] wajib diisi." · "Tulis jam dengan format 07.15." · "Toleransi terlambat 0 sampai 240 menit." | RECOMMENDATION |
| VAL-26 | Rentang tanggal | Tanggal selesai tidak sebelum tanggal mulai. Batas lain mengikuti fiturnya, misalnya batas mundur dan tahun ajaran. | "Tanggal selesai tidak boleh sebelum tanggal mulai." | RECOMMENDATION |
| VAL-27 | Teks lain | Lihat tabel ketiga di bawah. | Pesan panjang dan wajib mengikuti pola VAL-16. | RECOMMENDATION |
| VAL-28 | File unggahan | Format dan ukuran di `12` SEC-49. Pesan di tabel keempat di bawah. | Tabel keempat | DECISION (Paket 10 MB); RECOMMENDATION (teks) |
| VAL-29 | Pencarian | Parameter `cari` 2 s.d. 50 karakter (`10` API-09). Kurang dari 2 karakter tidak mencari dan tidak dianggap galat. | "Tulis paling sedikit 2 huruf nama atau angka NISN." | RECOMMENDATION |
| VAL-30 | Nomor halaman | Parameter `page` bilangan bulat ≥ 1. Nilai yang tidak sah dianggap 1. Halaman setelah halaman terakhir menampilkan daftar kosong dengan tautan ke halaman 1. | — | RECOMMENDATION |
| VAL-31 | Pilihan banyak siswa | Siswa terpilih di dispensasi massal paling banyak 200, dan untuk kelompok yang lebih besar dipakai pilihan rombel atau tingkat (FS-IZN-03). Batas ini juga menjaga jumlah isian di bawah `max_input_vars` PHP (1.000). Pilihan siswa di halaman lain dibatasi jumlah siswa satu rombel. | "Pilih paling banyak 200 siswa. Untuk lebih banyak siswa, pilih per kelas atau per tingkat." | RECOMMENDATION |

Alasan dan catatan (VAL-16):

| Isian | Panjang | Fitur |
|---|---|---|
| Alasan koreksi, alasan hapus koreksi | 5 s.d. 255 | FS-PRS-07 |
| Catatan presensi manual (alasan "lainnya"), alasan batal presensi manual | 5 s.d. 255 | FS-PRS-06 |
| Alasan ubah jadwal hari ini | 5 s.d. 255 | FS-PRS-04 |
| Alasan aktivasi dan pengakhiran mode darurat | 5 s.d. 255 | FS-PRS-08 |
| Catatan tinjauan scan saat menolak | 5 s.d. 255 | FS-KIO-06 |
| Keterangan nonaktif siswa (alasan "lainnya") | 5 s.d. 255 | FS-MD-04 |
| Keterangan izin dan dispensasi, keterangan kelompok dispensasi | 5 s.d. 500 | FS-IZN-01 s.d. FS-IZN-03 |
| Catatan verifikasi saat menolak, alasan ubah keputusan izin | 5 s.d. 500 | FS-IZN-04, FS-IZN-05 |

Urutan aturan jam (VAL-25, BR-JAM-02):

| Pelanggaran | Pesan pada isian |
|---|---|
| Jam buka scan masuk setelah jam masuk | Jam buka scan masuk: "Jam buka scan masuk tidak boleh setelah jam masuk." |
| Batas terlambat (jam masuk + toleransi) tidak sebelum jam tutup sesi masuk | Toleransi terlambat: "Batas terlambat pukul [jam] harus sebelum jam tutup sesi masuk." |
| Jam tutup sesi masuk setelah jam buka scan pulang | Jam tutup sesi masuk: "Jam tutup sesi masuk tidak boleh setelah jam buka scan pulang." |
| Jam buka scan pulang setelah jam pulang | Jam buka scan pulang: "Jam buka scan pulang tidak boleh setelah jam pulang." |
| Jam pulang tidak sebelum jam tutup sesi pulang | Jam tutup sesi pulang: "Jam tutup sesi pulang harus setelah jam pulang." |
| Batas terlambat melewati pukul 23.59 | Toleransi terlambat: "Batas terlambat melewati pukul 23.59. Kurangi toleransi." |

Teks lain (VAL-27):

| Isian | Aturan | Fitur |
|---|---|---|
| Nama resmi sekolah | 2 s.d. 100 karakter, satu baris | FS-MD-01 |
| Alamat sekolah, alamat rumah siswa | Paling panjang 255 karakter, boleh beberapa baris | FS-MD-01, FS-MD-04 |
| Kota di blok tanda tangan (R2) | Paling panjang 60 karakter, satu baris | `08` UI-61 |
| Nama stasiun | 2 s.d. 50 karakter, satu baris, unik | FS-AKN-04 |
| Nama tahun ajaran | Bentuk `YYYY/YYYY`, dengan tahun kedua sama dengan tahun pertama ditambah 1, misalnya "2026/2027". Unik. Pesan: "Tulis tahun ajaran seperti 2026/2027." | FS-MD-02 |
| Nama rombel | 1 s.d. 20 karakter, satu baris: huruf, angka, spasi, dan tanda hubung. Unik di dalam tahun ajaran, tanpa membedakan huruf besar dan kecil. Pesan: "Nama kelas sudah dipakai di tahun ajaran ini." | FS-MD-03 |
| Label atribut tambahan | 1 s.d. 60 karakter, satu baris | FS-MD-09 |
| Kode atribut tambahan | `^[a-z][a-z0-9_]{1,29}$`. Pesan: "Kode diawali huruf, dan hanya boleh berisi huruf kecil, angka, dan garis bawah." · "Kode ini sudah dipakai, atau sama dengan judul kolom bawaan template import." | FS-MD-09 |
| Pilihan atribut tambahan | 2 s.d. 50 pilihan, masing-masing 1 s.d. 60 karakter, tanpa pilihan ganda | FS-MD-09 |
| Nilai atribut tambahan | Teks paling panjang 255 karakter. Angka menurut VAL-10. Tanggal menurut VAL-11. Pilihan dari daftarnya. | FS-MD-04, FS-MD-09 |
| Batas mundur | Bilangan bulat 0 s.d. 31. Pesan: "Batas mundur 0 sampai 31 hari." | FS-PRS-10 |
| NIP (R2) | Paling panjang 30 karakter: angka dan spasi | `06` §5.1 |

File unggahan (VAL-28):

| Keadaan | Pesan |
|---|---|
| Format tidak diizinkan, termasuk ekstensi yang tidak sesuai isi file | Foto dan logo: "File harus berupa gambar JPG, PNG, atau WebP." · Lampiran: "File harus berupa gambar JPG, PNG, WebP, atau PDF." · Import: "File harus berupa XLSX atau CSV. Unduh template bila perlu." |
| File lebih besar dari batas aplikasi | "Ukuran file [nama] [ukuran] MB. Paling besar [batas] MB." |
| Gambar lebih dari 24 megapiksel | "Gambar [nama] terlalu besar ([lebar]×[tinggi] piksel). Perkecil dulu, lalu unggah lagi." |
| File gambar rusak atau tidak dapat dibaca | "File [nama] tidak dapat dibaca sebagai gambar." |
| PDF tidak diawali `%PDF-` | "File [nama] bukan PDF yang sah." |
| Lebih dari 3 lampiran | "Lampiran paling banyak 3 file." Bila lampiran bersama kelompok sudah ada, pesan menyebut sisa yang boleh. |
| Import lebih dari 2.000 baris data | "File berisi [n] baris data. Paling banyak 2.000 baris per file. Bagi file menjadi beberapa bagian." |
| Isi XLSX setelah diekstrak lebih dari 50 MB | "File XLSX terlalu besar setelah dibuka. Simpan ulang hanya lembar data, atau pakai CSV." |
| ZIP foto melebihi batas entri atau isi | "ZIP berisi [n] file. Paling banyak 2.000 file." · "Isi ZIP setelah dibuka lebih dari 500 MB. Bagi menjadi beberapa ZIP." |
| ZIP rusak atau terenkripsi | "ZIP tidak dapat dibuka. Buat ulang ZIP tanpa password." |
| Lebih dari 100 file sekaligus | "Paling banyak 100 file sekaligus. Untuk lebih banyak foto, pakai satu file ZIP." |
| Kiriman lebih besar dari batas PHP atau Nginx | GAL-11 |
| Galat unggah PHP | GAL-11 |

## 5. Pesan per fitur

Tabel di bawah memberi teks untuk kode galat `04` yang belum memiliki teks, dan mengganti teks sementara di `04`. Kode yang sudah memiliki teks final di `04` atau `08` tidak diulang, kecuali bila teksnya berubah. Pesan isian yang mengikuti §4 tidak diulang. (RECOMMENDATION)

### 5.1 Akun dan akses (AKN)

| Fitur dan kode | Keadaan | Pesan |
|---|---|---|
| FS-AKN-01 E1, E2 | Login gagal | "NISN/username atau password salah." (tidak berubah) |
| FS-AKN-01 E3 | Kunci 15 menit untuk identitas | "Terlalu banyak percobaan login. Coba lagi pukul [jam]." Jam dihitung dari baris gagal tertua dalam 15 menit terakhir, dibulatkan ke menit berikutnya (`12` SEC-09). |
| FS-AKN-01 E3 | Kunci 24 jam untuk identitas | "Login akun ini dikunci karena terlalu banyak percobaan. Hubungi wali kelas atau admin." |
| FS-AKN-01 E3 | Kunci per alamat IP | "Terlalu banyak percobaan login dari jaringan ini. Coba lagi pukul [jam]." |
| FS-AKN-01 | Identitas atau password kosong | "Isi NISN atau username." · "Isi password." |
| FS-AKN-01 E4 | Sesi berakhir | "Sesi berakhir. Login lagi untuk melanjutkan." (`08` UI-56) |
| FS-AKN-02 E1 | Password lama salah | "Password lama salah." Lima kali salah berturut-turut di satu sesi mengakhiri sesi itu, dan pengguna login lagi. Kegagalan ini dicatat sebagai percobaan login gagal (`12` SEC-09). |
| FS-AKN-02 E3 | Aturan password | VAL-21 |
| FS-AKN-03 E1, E2 | Username | VAL-20 |
| FS-AKN-03 E3 | Tidak ada lagi admin aktif | "Harus ada minimal satu admin aktif." (tidak berubah) |
| FS-AKN-03 E4 | Menonaktifkan akun sendiri | "Anda tidak dapat menonaktifkan akun sendiri." |
| FS-AKN-03, FS-AKN-04, FS-AKN-05 | Buka kunci login berhasil | "Kunci login [nama] sudah dibuka." |
| FS-AKN-04 E1 | Nama stasiun atau username dipakai | "Nama stasiun sudah dipakai." · VAL-20 |
| FS-AKN-05 E1 | Semua akun di rombel sudah aktif | "Semua akun di kelas ini sudah aktif." (`09` HAL-AKN-05) |
| FS-AKN-05 E2 | Reset untuk akun nonaktif | "Akun [nama] nonaktif, sehingga password tidak dapat direset." |
| FS-AKN-05, RT-09 | Token sekali pakai sudah terpakai | "Tindakan ini sudah dijalankan. Password baru tidak dibuat lagi. Bila password belum tercatat, reset sekali lagi." |

### 5.2 Master data (MD)

| Fitur dan kode | Keadaan | Pesan |
|---|---|---|
| FS-MD-01 E2 | Logo tidak valid | VAL-28. Isian lain tetap tersimpan, dengan pesan kilat "Identitas sekolah disimpan. Logo tidak diganti." |
| FS-MD-02 E2 | Tumpang tindih tahun ajaran | "Tanggal tahun ajaran tumpang tindih dengan [nama tahun ajaran]." |
| FS-MD-02 E2 | Semester di luar tahun ajaran atau bertumpuk | "Semester ganjil harus berada di dalam tahun ajaran." · "Semester genap harus setelah semester ganjil dan berada di dalam tahun ajaran." |
| FS-MD-02 E3 | Aktivasi tahun ajaran di luar tanggalnya | "Tahun ajaran [nama] belum dimulai." · "Tahun ajaran [nama] sudah selesai." |
| FS-MD-02 E4 | Hapus tahun ajaran yang memiliki rombel | "Tahun ajaran ini sudah memiliki kelas, sehingga tidak dapat dihapus." |
| FS-MD-03 E1 | Nama rombel dipakai | VAL-27 |
| FS-MD-03 E2 | Wali kelas nonaktif | "Akun [nama] nonaktif. Pilih staf yang aktif." |
| FS-MD-03 E3 | Hapus rombel dengan penempatan | "Kelas ini sudah memiliki siswa, sehingga tidak dapat dihapus." |
| FS-MD-03 E4 | Ubah tingkat rombel dengan penempatan | "Tingkat kelas yang sudah memiliki siswa tidak dapat diubah." |
| FS-MD-04 E2 | NISN dipakai | VAL-18 |
| FS-MD-04 E3 | Nomor WA tidak valid | VAL-23 |
| FS-MD-04 E4 | Tanggal terakhir aktif tidak sah | "Tanggal terakhir aktif tidak boleh setelah hari ini." · "Tanggal terakhir aktif tidak boleh sebelum tanggal mulai aktif, [tanggal]." |
| FS-MD-04 E7 | NIS dipakai | VAL-19 |
| FS-MD-04 E8 | Atribut tambahan | "[Label] wajib diisi." · "[Label] harus berupa angka." · "[Label] harus berupa tanggal." · "Pilih salah satu [label]." |
| FS-MD-05 E1 | Penempatan tumpang tindih | "Penempatan ini tumpang tindih dengan penempatan [siswa] di [kelas], [tanggal mulai] s.d. [tanggal selesai]." |
| FS-MD-05 E2 | Siswa tidak aktif pada tanggal mulai | "[Nama] tidak aktif pada [tanggal]." |
| FS-MD-05 E3 | Tahun ajaran tidak mencakup tanggal mulai | "Tanggal mulai [tanggal] di luar tahun ajaran [nama]." |
| FS-MD-06 E1 | Semua baris gagal | "Semua [n] baris gagal. Tidak ada siswa yang disimpan. Unduh daftar baris gagal untuk melihat alasannya." |
| FS-MD-06 E2 | Kolom tidak sesuai | "Kolom wajib tidak ada: [daftar]." · "Kolom tidak dikenal: [daftar]. Hapus kolom itu, atau periksa ejaannya." · "Kolom ganda: [daftar]." |
| FS-MD-06 E3 | Ukuran atau jumlah baris | VAL-28 |
| FS-MD-07 E1 | Foto tidak valid | VAL-28. "Foto lama tetap dipakai." ditambahkan bila siswa sudah memiliki foto. |
| FS-MD-08 E1 | Tidak ada file yang cocok | "Tidak ada nama file yang cocok dengan NISN siswa. Nama file diawali 10 digit NISN, misalnya 0012345678.jpg." |
| FS-MD-08 E2 | Unggahan melebihi batas | VAL-28 |
| FS-MD-09 E1 | Kode atribut | VAL-27 |
| FS-MD-09 E2 | Ubah tipe atribut yang memiliki nilai | "Tipe atribut tidak dapat diubah karena sudah ada siswa yang memiliki nilai." |
| FS-MD-09 E3 | Hapus atribut atau pilihan yang dipakai | "Atribut ini sudah memiliki nilai. Sembunyikan atribut bila tidak dipakai lagi." · "Pilihan [pilihan] sudah dipakai [n] siswa, sehingga tidak dapat dihapus." |

### 5.3 Kiosk dan stasiun (KIO)

Teks layar kiosk mengikuti `08` §7.3 dan §7.4. Tambahan Session 9:

| Fitur dan kode | Keadaan | Pesan |
|---|---|---|
| FS-KIO-01 butir 8 | Meminta PIN | "Masukkan PIN petugas." |
| FS-KIO-01 butir 8 | PIN salah | "PIN salah. Sisa [n] percobaan." |
| FS-KIO-01 butir 8 | Menu terkunci setelah 5 kali salah | "Menu petugas dikunci sampai pukul [jam]." |
| FS-KIO-01 butir 8 | PIN belum diatur | "PIN petugas belum diatur. Hubungi admin." (`12` SEC-21) |
| FS-KIO-01 butir 8 | Hapus data lokal saat ada scan belum tersinkron | "Masih ada [n] scan belum tersinkron. Sinkronkan dulu sebelum menghapus data." |
| FS-KIO-01 E2 | Akun stasiun login di laptop lain | "Akun stasiun ini sudah login di laptop lain. Scan yang belum tersinkron tetap tersimpan. Login lagi untuk memakai laptop ini." |
| FS-KIO-04 | Pembatasan laju | Kiosk menunggu tanpa pesan. Bila berlangsung lebih dari 5 menit, pita "Server membatasi kiriman. Scan tetap tersimpan." tampil. |
| FS-KIO-06 E4 | Menolak tanpa catatan | "Tulis catatan alasan penolakan." |

Halaman PIN petugas di panel (`09` HAL-KIO-02):

| Keadaan | Pesan |
|---|---|
| PIN disimpan | "PIN petugas disimpan. Kiosk memakai PIN baru setelah memuat ulang data, paling lambat 1 menit bila online." |
| PIN | VAL-22 |

### 5.4 Presensi dan aturan (PRS)

| Fitur dan kode | Keadaan | Pesan |
|---|---|---|
| FS-PRS-01 E1, FS-PRS-02 E2, FS-PRS-04 E1 | Urutan jam | VAL-25 |
| FS-PRS-01 E2 | Berlaku mulai tanggal lampau | "Berlaku mulai paling awal hari ini. Untuk tanggal yang sudah lewat, pakai jadwal khusus." |
| FS-PRS-01 E3 | Hari sekolah tanpa aturan jam lengkap | "Lengkapi aturan jam untuk hari [hari]." |
| FS-PRS-02 E1 | Jadwal khusus tumpang tindih | "Tanggal ini tumpang tindih dengan jadwal khusus [keterangan], [tanggal mulai] s.d. [tanggal selesai]." |
| FS-PRS-02 E3 | Tanggal di luar semester | Peringatan setelah disimpan: "Jadwal disimpan. [n] tanggal berada di luar semester, sehingga tetap bukan hari sekolah." |
| FS-PRS-03 E1 | Cakupan libur kosong | "Pilih paling sedikit satu tingkat atau kelas." |
| FS-PRS-03 E2, FS-PRS-02 | Tanggal selesai sebelum mulai | VAL-26 |
| FS-PRS-04 E2 | Hari ini bukan hari sekolah | "Hari ini bukan hari sekolah. Pakai jadwal khusus untuk mengubah aturan jam." |
| FS-PRS-04 E3, FS-PRS-06 E2, FS-PRS-07 E1 | Di luar cakupan tanggal | `08` UI-56 (di luar batas mundur). Untuk guru piket: "Guru piket hanya dapat mengubah data hari ini." |
| FS-PRS-06 E1 | Presensi masuk sudah ada | "[Nama] sudah tercatat masuk pukul [jam] ([sumber]). Untuk mengubahnya, pakai koreksi status." |
| FS-PRS-06 E3 | Tanggal ke depan atau jam setelah sekarang | "Tanggal tidak boleh setelah hari ini." · "Jam tidak boleh setelah jam sekarang, [jam]." |
| FS-PRS-06 E4, FS-PRS-07 E3 | Bukan hari sekolah bagi siswa | "[Tanggal] bukan hari sekolah bagi [nama]: [keterangan]." |
| FS-PRS-06 E5 | Alasan kosong atau "lainnya" tanpa catatan | "Pilih alasan." · "Tulis catatan untuk alasan lainnya." |
| FS-PRS-06 E6 | Jam masuk tidak sebelum jam pulang | "Jam masuk harus sebelum jam pulang, [jam]." |
| FS-PRS-06 E7, FS-PRS-07 E5 | Perubahan bersamaan | `08` UI-56 (data sudah diubah orang lain) |
| FS-PRS-08 E1 | Hari ini bukan hari sekolah | "Hari ini bukan hari sekolah, sehingga mode darurat tidak diperlukan." |
| FS-PRS-08 E3, FS-PRS-04 E4, FS-PRS-07 E4 | Alasan kosong | VAL-16 |
| FS-PRS-09 E1 | Mode darurat tidak aktif | "Mode darurat tidak aktif. Presensi per kelas hanya tersedia saat mode darurat." |
| FS-PRS-09 E4 | Tidak ada siswa dicentang | "Centang paling sedikit satu siswa yang hadir." |
| FS-PRS-10 E1 | Nilai di luar rentang | VAL-27 |

### 5.5 Izin, sakit, dan dispensasi (IZN)

| Fitur dan kode | Keadaan | Pesan |
|---|---|---|
| FS-IZN-01 E1 | Di luar batas mundur | "Tanggal ini sudah lewat batas pengajuan. Hubungi wali kelas." (tidak berubah) |
| FS-IZN-01 E2, FS-IZN-02 E2 | Tumpang tindih | Siswa: "Kamu sudah punya [jenis] [status] untuk [tanggal mulai] s.d. [tanggal selesai]." · Staf: "[Nama] sudah punya [jenis] [status] untuk [tanggal mulai] s.d. [tanggal selesai]." dengan tautan ke data itu. |
| FS-IZN-01 E3, FS-IZN-02 E4 | Tanpa hari sekolah | "Tidak ada hari sekolah pada tanggal yang dipilih." (tidak berubah) |
| FS-IZN-01 E4 | Tanggal selesai di luar tahun ajaran aktif | "Tanggal selesai paling lambat [tanggal selesai tahun ajaran]." |
| FS-IZN-01 E5 | Lampiran tidak valid | VAL-28. Isian lain tetap terisi, dan file perlu dipilih ulang (VAL-07). |
| FS-IZN-02 E1, FS-IZN-03 E3, FS-IZN-05 E1 | Di luar batas mundur | `08` UI-56 |
| FS-IZN-03 E1 | Tidak ada siswa dipilih | "Pilih siswa, kelas, atau tingkat." |
| FS-IZN-03 E2 | Semua siswa bentrok atau tanpa hari sekolah | "Tidak ada dispensasi yang disimpan. Semua [n] siswa bentrok dengan data lain atau tidak memiliki hari sekolah pada tanggal itu." dengan daftar siswa dan alasannya. |
| FS-IZN-04 E2 | Sudah diverifikasi staf lain | "Pengajuan ini sudah [disetujui/ditolak] oleh [nama] pukul [jam]." |
| FS-IZN-04 E3 | Dibatalkan siswa | "Pengajuan sudah dibatalkan siswa." (tidak berubah) |
| FS-IZN-04 E4 | Menolak tanpa catatan | "Tulis catatan alasan penolakan. Catatan ini dapat dibaca siswa." |
| FS-IZN-05 E2 | Rentang baru tidak sah | "Rentang baru harus berada di dalam [tanggal mulai] s.d. [tanggal selesai]." · "Rentang baru harus memuat paling sedikit satu hari sekolah." |
| FS-IZN-05 E3 | Persetujuan menimbulkan tumpang tindih | "Pengajuan ini bentrok dengan [jenis] [status] untuk [tanggal mulai] s.d. [tanggal selesai]." |
| FS-IZN-06 E2 | Lampiran di luar cakupan | `08` UI-56 (di luar hak) |
| FS-IZN-06 E3 | File lampiran tidak ditemukan | "File lampiran tidak ditemukan. Laporkan ke admin." Kejadian ini dicatat di log aplikasi tingkat `error` (GAL-18). |

### 5.6 Dashboard dan laporan (LAP)

| Fitur dan kode | Keadaan | Pesan |
|---|---|---|
| FS-LAP-02 E3, FS-LAP-03 E1 | Tanggal ke depan | "Tanggal tidak boleh setelah hari ini." |
| FS-LAP-03 E1 | Rentang di luar tahun ajaran | "Rentang harus berada di dalam tahun ajaran [nama], [tanggal mulai] s.d. [tanggal selesai]." |
| FS-LAP-01, `10` EP-LAP-01 | Polling gagal tiga kali berturut-turut | "Data belum dapat diperbarui. Diperbarui terakhir pukul [jam]." Polling tetap dicoba. |

### 5.7 Baris import

Alasan baris gagal ditulis di kolom alasan file baris gagal (`13` IM-07) dan di pratinjau. Satu baris dapat memiliki beberapa alasan, dipisah titik koma. (RECOMMENDATION)

| Kolom | Alasan |
|---|---|
| NISN | "NISN kosong." · "NISN harus 10 digit angka." · "NISN 9 digit. Kemungkinan nol di depan hilang. Format kolom NISN sebagai teks, lalu ketik ulang." · "NISN ganda di file ini (baris [n])." · "NISN sudah terdaftar atas nama [nama]." |
| Nama Lengkap | "Nama kosong." · Pesan VAL-15. |
| Kelas | "Kelas kosong." · "Kelas [nilai] tidak ada di tahun ajaran [nama]." |
| NIS | Pesan VAL-19. · "NIS ganda di file ini (baris [n])." |
| Jenis Kelamin | "Jenis kelamin harus L atau P." |
| Tanggal Lahir | "Tanggal lahir tidak valid. Tulis seperti 14-03-2011." · Pesan VAL-24. |
| Alamat, Nama Orang Tua/Wali | "[Kolom] paling panjang [n] karakter." |
| Nomor WA Orang Tua/Wali | "Nomor WA tidak valid." |
| Atribut tambahan | "[Kode] wajib diisi." · "[Kode] harus berupa angka." · "[Kode] harus berupa tanggal." · "[Kode] harus salah satu dari: [pilihan]." |
| Semua kolom | "Sel berisi rumus. Salin sebagai nilai, lalu unggah lagi." (`12` SEC-46) |
| Import penempatan: NISN | "NISN tidak dikenal." · "[Nama] tidak aktif pada [tanggal mulai]." · "NISN ganda di file ini (baris [n])." |
| Import penempatan: Kelas Tujuan | "Kelas [nilai] tidak ada di tahun ajaran [nama]." · "Penempatan tumpang tindih dengan [kelas], [tanggal mulai] s.d. [tanggal selesai]." |

### 5.8 Foto massal

Keterangan per file di pratinjau dan ringkasan foto massal (FS-MD-08). (RECOMMENDATION)

| Keadaan | Keterangan |
|---|---|
| Cocok | "Cocok dengan [nama] ([kelas])." Siswa nonaktif ditambah "(nonaktif)" (`13` IM-14). |
| Nama file tidak sesuai format | "Nama file tidak diawali 10 digit NISN." |
| NISN tidak dikenal | "NISN [nisn] tidak terdaftar." |
| Beberapa file untuk satu NISN | Semua file untuk NISN itu dilewati: "Ada [n] file untuk NISN [nisn]. Sisakan satu file, lalu unggah lagi." |
| Bukan gambar, terlalu besar, atau rusak | Pesan VAL-28 |
| Siswa sudah memiliki foto | "Foto lama akan diganti." (peringatan, bukan galat) |

## 6. Penanganan galat

### 6.1 Peta galat

| ID | Aturan | Status |
|---|---|---|
| GAL-01 | **Satu jalur untuk setiap jenis galat.** Setiap galat dijawab menurut jenis permintaannya, dengan tabel di bawah. Halaman memakai pesan `08` UI-56 dan pesan di dokumen ini. Permintaan latar belakang menerima kode JSON `10` API-03. Pengguna tidak pernah melihat pesan pengecualian, jejak, nama tabel, atau path. | RECOMMENDATION |

| Galat | Formulir dan halaman | Fragmen dan bantuan formulir | API kiosk | Rujukan |
|---|---|---|---|---|
| Isian tidak sah | 303 ke formulir dengan pesan per isian | Pesan di fragmen, misalnya pratinjau presensi manual | Scan ditolak satu per satu, atau 400 `permintaan_rusak` | VAL-07, VAL-14 |
| Permintaan rusak atau karakter tidak sah | 400, halaman GAL-02 | 400 `permintaan_rusak` | 400 `permintaan_rusak` | GAL-02 |
| Halaman periksa tidak berlaku | 303 ke formulir asal | — | — | GAL-04 |
| Data sudah diubah orang lain | 303 ke formulir dengan data terbaru | — | — | GAL-05 |
| Token CSRF ditolak | 303 ke halaman asal dengan isian | 403 `csrf` | 403 `csrf` | GAL-06 |
| Sesi berakhir | 303 ke `/login` | 401 `login_ulang` | 401 `login_ulang` | GAL-07 |
| Di luar hak atau cakupan | 403, halaman di luar hak | 403 `ditolak` | 403 `ditolak` | GAL-08 |
| Tidak ditemukan | 404 | 404 `tidak_ditemukan` | 404 `tidak_ditemukan` | GAL-08 |
| Keadaan data tidak memungkinkan tindakan | 303 dengan pesan galat | — | — | GAL-09 |
| Terlalu sering | 429, halaman GAL-10 | 429 `terlalu_sering` | 429 `terlalu_sering` | GAL-10 |
| Kiriman terlalu besar | 303 ke halaman asal, atau halaman 413 dari Nginx | 413 `terlalu_besar` | 413 `terlalu_besar` | GAL-11 |
| Galat database | Lihat GAL-12 | Lihat GAL-12 | Lihat GAL-12 | GAL-12 |
| Galat server | 500, halaman GAL-13 | 500 `galat_server` | 500 `galat_server` | GAL-13 |
| Pemeliharaan | 503, halaman GAL-17 | 503 | 503, diulang seperti `galat_server` | GAL-17 |

### 6.2 Galat di formulir dan halaman

| ID | Aturan | Status |
|---|---|---|
| GAL-02 | **Permintaan rusak.** Permintaan yang ditolak filter `invalidchars` (`12` SEC-43), atau yang isian wajib tersembunyinya hilang, dijawab 400 dengan view `errors/html/error_400.php` yang disesuaikan: "Permintaan tidak dapat diproses. Muat ulang halaman, lalu coba lagi." Galat ini dicatat di log aplikasi tingkat `warning`. | RECOMMENDATION |
| GAL-03 | **Isian tidak sah.** Mengikuti VAL-07 dan VAL-08. Jawaban 303 ke formulir, bukan 422 dengan halaman langsung, agar muat ulang tidak mengirim ulang formulir (`09` RT-08). | RECOMMENDATION |
| GAL-04 | **Halaman periksa dan token sekali pakai.** Token halaman periksa yang tidak ada, milik akun lain, atau sudah dipakai (`09` RT-07) dialihkan ke formulir asal dengan pesan "Halaman konfirmasi sudah tidak berlaku. Isi formulir lagi." Token rahasia sekali tampil yang sudah terpakai memakai pesan di §5.1 (RT-09). | RECOMMENDATION |
| GAL-05 | **Perubahan bersamaan.** Token versi atau penjaga yang tidak cocok (`09` RT-12) dialihkan ke formulir yang memuat data terbaru, dengan pesan "data sudah diubah" di `08` UI-56. Isian pengguna tidak dikembalikan, agar data terbaru yang tampil. Alasan yang sudah ditulis pengguna dikembalikan di isian alasan, karena alasan tidak ada di data lama. | RECOMMENDATION |
| GAL-06 | **Token CSRF ditolak.** Mengikuti `12` SEC-30. Pesan formulir: "Kiriman tidak dapat diproses karena halaman sudah terlalu lama dibuka. Periksa isian, lalu kirim lagi." Bila formulir memuat file: ditambah "File perlu dipilih ulang." Pada halaman login: "Halaman login sudah terlalu lama dibuka. Login lagi." | RECOMMENDATION |
| GAL-07 | **Sesi berakhir saat mengirim formulir.** Filter `sesi` mengalihkan kiriman formulir tanpa sesi yang sah ke `/login` dengan pesan "Sesi berakhir sebelum formulir terkirim. Login lagi, lalu kirim ulang formulir." Isian kiriman itu tidak disimpan, karena sesi pemiliknya tidak diketahui. Tujuan setelah login adalah halaman formulir, diambil dari `Referer` bila berasal dari aplikasi sendiri dan berada di area jenis akun itu (`09` RT-18, `12` SEC-39). Navigasi GET mengikuti RT-18 butir 2. | RECOMMENDATION |
| GAL-08 | **Di luar hak dan tidak ditemukan.** 403 dan 404 mengikuti `09` RT-11 dan RT-19, dengan pesan `08` UI-56. Penolakan karena hak atau cakupan dicatat di log aplikasi tingkat `info`, sehingga tidak tercatat di production. Pola penolakan yang berulang dari satu akun terlihat di log aktivitas bila tindakannya dicatat di sana. | RECOMMENDATION |
| GAL-09 | **Keadaan data.** Tindakan yang tidak lagi mungkin karena keadaan data berubah, misalnya mengaktifkan akun yang sudah aktif, atau membatalkan pengajuan yang sudah diverifikasi, dialihkan ke halaman data itu dengan pesan galat yang menyebut keadaan terbarunya, misalnya "Pengajuan ini sudah disetujui oleh Rina Wulandari pukul 07.40." Tindakan yang sama dua kali berturut-turut, misalnya klik ganda, tidak menimbulkan galat bila hasil akhirnya sama: tindakan kedua dialihkan dengan pesan berhasil dari tindakan pertama. | RECOMMENDATION |
| GAL-10 | **Terlalu sering.** Halaman 429 dari aplikasi dan dari Nginx memakai teks "Terlalu banyak permintaan. Tunggu sebentar, lalu coba lagi." Halaman Nginx adalah file statis `public/galat/429.html` (`12` SEC-56). Pesan pembatasan login ada di §5.1. | RECOMMENDATION |
| GAL-11 | **Unggahan gagal.** Lihat butir di bawah tabel. | RECOMMENDATION |
| GAL-12 | **Galat database.** Lihat butir di bawah tabel. | RECOMMENDATION |
| GAL-13 | **Galat server.** Halaman 500 di production memakai view `errors/html/production.php` yang disesuaikan, tanpa layout area (`09` RT-19 butir 4): "Terjadi gangguan. Coba lagi beberapa saat lagi. Bila berulang, laporkan kode [KODE] ke admin sekolah." Kode laporan adalah 8 karakter pertama ID permintaan dalam huruf besar, misalnya `7F3A2C1B` (`12` SEC-58). Halaman memuat tautan ke halaman awal. Jawaban 500 memakai header `Cache-Control: no-store` dan header keamanan (`12` SEC-35). Di lingkungan pengembangan, CI4 menampilkan halaman jejak bawaan. | RECOMMENDATION |

Butir GAL-11:

1. Kiriman yang lebih besar dari `post_max_size` membuat PHP membuang semua isian dan file, termasuk token CSRF. Filter `csrf` aplikasi memeriksanya lebih dulu (`12` SEC-30 butir 1), lalu mengalihkan ke halaman asal dengan pesan "Kiriman terlalu besar. Paling besar 100 MB per kiriman." Permintaan latar belakang menerima 413 `terlalu_besar`.
2. Kiriman yang lebih besar dari `client_max_body_size` ditolak Nginx dengan 413 sebelum mencapai PHP. Nginx menampilkan file statis `public/galat/413.html`: "File terlalu besar. Paling besar 100 MB per kiriman. Kembali, lalu pilih file yang lebih kecil."
3. Galat unggah PHP per file dipetakan seperti tabel di bawah.
4. Galat unggah dicatat di log aplikasi. Galat server dicatat tingkat `critical`, karena semua unggahan akan gagal sampai diperbaiki.

| Kode PHP | Pesan | Log |
|---|---|---|
| `UPLOAD_ERR_INI_SIZE`, `UPLOAD_ERR_FORM_SIZE` | "Ukuran file [nama] melebihi batas." | `info` |
| `UPLOAD_ERR_PARTIAL` | "Unggahan [nama] terputus. Coba lagi." | `info` |
| `UPLOAD_ERR_NO_FILE` | Isian wajib: "Pilih file." Isian opsional: dianggap tidak diisi. | — |
| `UPLOAD_ERR_NO_TMP_DIR`, `UPLOAD_ERR_CANT_WRITE`, `UPLOAD_ERR_EXTENSION` | "File tidak dapat disimpan karena gangguan server. Coba lagi beberapa saat lagi." | `critical` |

Butir GAL-12:

1. Koneksi MySQLi memakai `DBDebug = true`, sehingga query yang gagal melempar `DatabaseException` dengan kode galat MySQL. Bila galat terjadi di dalam transaksi, CI4 membatalkan transaksi sebelum melempar pengecualian. Transaksi dijalankan lewat satu helper yang menerima fungsi berisi seluruh tindakan. Pengecualian tidak ditangkap di dalam fungsi itu, tetapi di helper, setelah transaksi batal. Helper menghapus file unggahan yang sudah ditulis (`07` ARS-43), lalu menangani kode galat di butir berikut.
2. **1062 (kunci unik ganda).** Pemeriksaan unik di validasi dapat kalah cepat dengan permintaan lain. Helper transaksi menangkap 1062 pada kunci unik yang dikenal, misalnya `username` atau `nisn`, lalu menampilkan pesan isian yang sama dengan validasi, misalnya "Username sudah dipakai." Kunci unik yang menjaga idempotensi, misalnya `uuid` scan, tidak dianggap galat (`10` API-11).
3. **1213 (deadlock) dan 1205 (waktu tunggu kunci habis).** Seluruh transaksi diulang sekali setelah jeda acak 100–300 ms. Bila gagal lagi, permintaan dijawab sebagai galat server. Pengulangan dicatat tingkat `warning`. Antrean hitung ulang mengikuti `07` ARS-36.
4. **Galat data dari mode strict**, misalnya 1406 (data terlalu panjang), 1366, atau 1292 (nilai tidak sah). Galat ini berarti validasi kurang lengkap (VAL-05), sehingga dijawab sebagai galat server dan dicatat tingkat `error` untuk diperbaiki.
5. **Koneksi gagal**, misalnya 2002 atau 2006. Dijawab sebagai galat server dan dicatat tingkat `critical`.
6. Galat lain dijawab sebagai galat server dan dicatat tingkat `error`.

### 6.3 Galat di latar belakang dan kiosk

| ID | Aturan | Status |
|---|---|---|
| GAL-14 | **Fragmen dan bantuan formulir.** Modul JavaScript menangani jawaban dengan tabel di bawah. Isi lama tetap tampil saat galat, agar pengguna tidak kehilangan apa yang sedang dibaca. | RECOMMENDATION |
| GAL-15 | **API kiosk.** Kiosk menangani kode galat sesuai `10` API-03, dengan teks layar di `08` §7.4. Scan tidak pernah hilang karena galat jaringan atau server, karena scan baru ditandai tersinkron setelah server menjawab hasilnya (`07` ARS-29). | RECOMMENDATION |
| GAL-16 | **Galat di browser kiosk.** Galat JavaScript yang tidak tertangkap, galat IndexedDB, dan galat Service Worker dicatat di `meta` IndexedDB, paling banyak 20 entri terakhir, beserta waktu dan versi kode. Menu petugas menampilkannya di bagian "Info teknis", untuk dibacakan petugas ke admin. Galat menyimpan scan mengikuti FS-KIO-02 E8, dan penyimpanan penuh mengikuti FS-KIO-01 E5. | RECOMMENDATION |
| GAL-17 | **Pemeliharaan dan perintah CLI.** Halaman pemeliharaan 503 dari Nginx (`12` SEC-78) memakai file statis `public/galat/503.html`: "Spensada sedang diperbarui. Coba lagi dalam beberapa menit." Jawabannya memuat `Retry-After: 300`. Perintah `spark` milik aplikasi yang gagal keluar dengan kode bukan 0, menulis pesan ke STDERR, dan mencatat galat di log aplikasi. Galat cron terlihat di log aplikasi dan dari waktu terakhir cron berjalan di halaman pemeriksaan sistem (`07` ARS-56). | RECOMMENDATION |

Penanganan jawaban di modul JavaScript (GAL-14):

| Jawaban | Tindakan |
|---|---|
| 401 `login_ulang` | Polling berhenti, dan halaman menampilkan "Sesi berakhir. Login lagi untuk melanjutkan." dengan tautan ke `/login` (`08` UI-28). |
| 403 `nonaktif` | Halaman dimuat ulang, sehingga pengguna sampai di halaman login. |
| 403 `ditolak` | Pesan di luar hak (`08` UI-56). Polling berhenti. |
| 403 `csrf` | Permintaan diulang sekali dengan token baru (`10` API-04). |
| 429 `terlalu_sering` | Menunggu sesuai `Retry-After`, lalu mencoba lagi. |
| 5xx, putus koneksi, atau jawaban yang bukan bentuk yang diharapkan | Polling mencoba lagi pada jadwal berikutnya, dan pesan §5.6 tampil setelah tiga kali gagal. Pencarian siswa menampilkan "Pencarian gagal. Coba lagi." |

## 7. Log aplikasi

| ID | Aturan | Status |
|---|---|---|
| GAL-18 | **Tingkat log.** `Config\Logger::$threshold` bernilai 5 di production, yaitu `warning` ke atas, dan 9 di lingkungan pengembangan. Pemakaian tingkat mengikuti tabel di bawah. Pesan deprecation PHP dan CI4 hanya dicatat di lingkungan pengembangan (`Config\Exceptions::$logDeprecations`), agar log production tidak penuh. | RECOMMENDATION |
| GAL-19 | **Isi baris log.** Setiap baris memuat ID permintaan (`12` SEC-58), ID akun bila sudah login, metode, dan path tanpa query string. Query string tidak dicatat, karena dapat memuat NISN atau nama yang dicari. Isi formulir, password, PIN, token, dan cookie tidak pernah dicatat (`12` SEC-61). | RECOMMENDATION |
| GAL-20 | **Pengecualian.** `Config\Exceptions::$log` tetap `true`, dan `$ignoreCodes` berisi 404. Pengecualian `GagalValidasi` dan `DiLuarHak` tidak dicatat sebagai galat, karena merupakan alur biasa. `Config\Exceptions::$sensitiveDataInTrace` berisi `password`, `password_lama`, `password_baru`, `password_ulang`, `pin`, `pin_ulang`, dan `_csrf`, sebagai pengaman tambahan untuk jejak di lingkungan pengembangan. | RECOMMENDATION |
| GAL-21 | **Penyimpanan.** Log aplikasi disimpan 90 hari (`12` SEC-61). File log tidak pernah disajikan web. | RECOMMENDATION |
| GAL-22 | **Pemeriksaan sistem.** Halaman pemeriksaan sistem (`09` HAL-AKN-07) menampilkan jumlah baris `critical` dan `error` di log hari ini dan kemarin, dengan tanda "Perlu tindakan" bila ada baris `critical`. Isi log tidak ditampilkan di halaman. Admin membacanya lewat terminal server. | RECOMMENDATION |

Tingkat log (GAL-18):

| Tingkat | Contoh |
|---|---|
| `critical` | Database tidak dapat dihubungi, folder unggah tidak dapat ditulis, disk penuh, galat unggah server (GAL-11). |
| `error` | Pengecualian yang tidak tertangani (GAL-13), galat data dari mode strict (GAL-12), file lampiran atau foto yang dirujuk database tetapi tidak ada, antrean hitung ulang yang gagal (`07` ARS-36). |
| `warning` | Transaksi diulang karena deadlock (GAL-12), permintaan rusak (GAL-02), kiosk dengan selisih jam di luar toleransi berulang kali. |
| `info` | Penolakan hak, cakupan, dan token CSRF (GAL-08, `12` SEC-30), galat unggah dari pengguna. Hanya tercatat di lingkungan pengembangan. |

## 8. Pengujian

| ID | Aturan | Status |
|---|---|---|
| VAL-32 | **Uji validasi.** Setiap aturan di §4 diuji PHPUnit dengan contoh yang lolos dan yang ditolak, termasuk batas panjang tepat di batas, huruf beraksen, dan isian yang hanya berisi spasi. Contoh di dokumen ini menjadi kasus uji. Uji juga memastikan setiap aturan validasi yang dipakai memiliki pesan di `app/Language/id/Validation.php`, sehingga tidak ada pesan bawaan berbahasa Inggris yang tampil (VAL-06). | RECOMMENDATION |
| GAL-23 | **Uji galat.** Uji PHPUnit memeriksa jawaban setiap baris tabel GAL-01 untuk halaman, fragmen, dan API kiosk: status HTTP, kode JSON, tujuan pengalihan, dan isian yang dikembalikan. Uji juga memeriksa bahwa halaman 500 production tidak memuat pesan pengecualian, dan memuat kode laporan. Pengulangan transaksi pada deadlock diuji dengan dua koneksi database uji. | RECOMMENDATION |

## 9. Traceability

| Fitur atau aturan | Validasi dan galat |
|---|---|
| NFR-07 (validasi di server) | VAL-01, VAL-04, VAL-14 |
| `04` §4.3 (alasan wajib) | VAL-03, VAL-16 |
| `04` §4.6 (perubahan bersamaan) | GAL-05, GAL-09 |
| `04` §4.9 (file unggahan) | VAL-28, GAL-11 |
| FS-AKN-01 s.d. FS-AKN-05 | VAL-20 s.d. VAL-21, §5.1 |
| FS-KIO-01, FS-KIO-04, FS-KIO-06 | VAL-14, VAL-22, §5.3, GAL-15, GAL-16 |
| FS-MD-01 s.d. FS-MD-09 | VAL-15, VAL-18, VAL-19, VAL-23, VAL-24, VAL-27, VAL-28, §5.2, §5.7, §5.8 |
| FS-PRS-01 s.d. FS-PRS-10 | VAL-16, VAL-17, VAL-25, VAL-26, §5.4 |
| FS-IZN-01 s.d. FS-IZN-06 | VAL-16, VAL-26, VAL-28, VAL-31, §5.5 |
| FS-LAP-01 s.d. FS-LAP-04 | VAL-29, VAL-30, §5.6, GAL-14 |
| `09` RT-07, RT-08, RT-11, RT-12, RT-19 | VAL-07, GAL-03 s.d. GAL-05, GAL-08, GAL-13 |
| `10` API-03 | GAL-01, GAL-14, GAL-15 |
| `07` ARS-43 (transaksi) | VAL-05, GAL-12 |

## 10. Perubahan pada dokumen lain

Perubahan karena keputusan Session 9, termasuk yang ditulis di dokumen ini, dicatat di `12` §20.

## 11. Pertanyaan terbuka dan nilai yang dipastikan nanti

Session 9 tidak menambah OQ untuk dokumen ini. OQ-18 ada di `12` §21.

| Hal | Rujukan | Dipastikan di |
|---|---|---|
| Teks bantuan password dan pesan dicoba dengan beberapa siswa | VAL-21 | Uji coba R1 |
| Batas jumlah baris import dan siswa terpilih setelah uji beban | VAL-28, VAL-31 | Uji beban sebelum uji coba R1 |

## Riwayat perubahan

| Versi | Tanggal | Perubahan |
|---|---|---|
| 0.1 | 2026-10-05 | Draft awal dari Session 9: prinsip validasi (`VAL-01` s.d. `VAL-14`), aturan per jenis isian (`VAL-15` s.d. `VAL-31`), pesan per fitur termasuk baris import dan foto massal (§5), peta dan penanganan galat (`GAL-01` s.d. `GAL-17`), log aplikasi (`GAL-18` s.d. `GAL-22`), pengujian (`VAL-32`, `GAL-23`), dan traceability. |
