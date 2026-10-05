# Spensada — Page and Route Specification

| Item | Nilai |
|---|---|
| Versi | 0.1 (draft, menunggu review) |
| Tanggal | 2026-10-05 |
| Sumber | Discovery Session 8 (Routes / Pages / API) |
| Bergantung pada | [00-project-overview.md](00-project-overview.md): label status, glosarium, dan pertanyaan terbuka (`OQ-xx`). [01-product-requirements.md](01-product-requirements.md): requirement (`FR-*`). [02-user-roles-and-permissions.md](02-user-roles-and-permissions.md): hak akses (`HA-*`), cakupan, area, dan halaman awal. [03-user-flow.md](03-user-flow.md): alur (`UF-*`). [04-feature-specification.md](04-feature-specification.md): fitur (`FS-*`) dan ketentuan umum (§4). [06-database-design.md](06-database-design.md): tabel dan kode nilai. [07-system-architecture.md](07-system-architecture.md): area, filter, kiosk, file, sesi, dan perintah CLI (`ARS-*`). [08-ui-ux-design-system.md](08-ui-ux-design-system.md): komponen, tata letak, menu, dan label (`UI-*`). [13-reporting-import-export.md](13-reporting-import-export.md): laporan, import, dan export. |
| Dokumen terkait | [10-api-specification.md](10-api-specification.md): bentuk permintaan dan respons API kiosk, fragmen, dan bantuan formulir. `11-validation-and-error-handling.md` dan `12-security.md` (Session 9), keduanya belum dibuat. |

Dokumen ini menetapkan halaman dan route Spensada: konvensi alamat dan metode HTTP, kelompok route dan filter, menu panel dan portal, setiap halaman beserta route dan tindakannya, berkas yang disajikan lewat controller, halaman galat, serta label kode yang hanya tampil di halaman admin. Dokumen ini menyelesaikan hal yang diserahkan `07` (ARS-12, ARS-57) dan `08` (UI-31, §9.2) ke Session 8.

Bentuk permintaan dan respons API, termasuk sinkron kiosk dan fragmen yang diperbarui berkala, ada di `10`. Teks validasi per isian dan pengamanan rinci ditetapkan di Session 9.

## 1. Cara membaca dokumen ini

- **ID.**
  - Aturan route dan halaman memakai `RT-<NN>`.
  - Halaman memakai `HAL-<MODUL>-<NN>`, dengan kode modul dari `01` §2.
  - ID tidak pernah dinomori ulang. Butir yang batal ditandai `DEPRECATED`.
- **Status.** Label status mengikuti `00`. Keputusan dari ronde diskusi Session 8 berstatus DECISION (§2.1). Alamat rinci, nama controller dan method, serta isi halaman berstatus RECOMMENDATION, dan menjadi arah kerja Session 9–11 serta implementasi sampai dikonfirmasi atau diganti. Perilaku yang merujuk fitur di `04` mengikuti status fitur itu.
- **Penulisan route.** Route ditulis sebagai `METODE /alamat`. Bagian `{id}` adalah ID angka di tabel `06`, `{token}` adalah 32 karakter heksadesimal (`07` ARS-55), dan `{jenis}` adalah kode nilai `06`. Parameter query ditulis setelah `?`. Formulir HTML mengirim PUT, PATCH, dan DELETE sebagai POST dengan isian `_method` (RT-05).
- **Kolom tabel route.**
  - **Halaman**: ID halaman pemilik route.
  - **Controller::method**: kelas di namespace `App\Controllers`, ditulis tanpa awalan namespace itu.
  - **Hak**: ID `HA-*` di atribut `hak` method itu (RT-02). Beberapa ID yang dipisah koma berarti cukup salah satu. Tanda "+" berarti kedua hak wajib.
  - **Hasil**: jenis jawaban. "Halaman" berarti halaman HTML. "303 → X" berarti pengalihan ke X setelah berhasil (RT-08). "Berkas" berarti file unduhan atau gambar (§13).
- **Istilah.** Dokumen tetap memakai istilah glosarium (`00` §9), misalnya "rombel". Alamat dan layar memakai "kelas" (RT-03, `08` UI-51).
- **Contoh.** Contoh tanggal mengikuti `04` §1: hari ini Selasa, 13 Oktober 2026.

## 2. Keputusan Session 8

### 2.1 Keputusan route, halaman, dan API

| Topik | Keputusan | Rujukan | Status |
|---|---|---|---|
| Kata untuk rombel di alamat | `kelas`, sama dengan label layar, misalnya `/panel/kelas` dan `/panel/presensi/kelas/12`. Nama controller, view, tabel, isian JSON, dan nama route tetap memakai "rombel". | RT-03, `08` UI-51 | DECISION |
| Metode HTTP | Gaya REST. GET membaca, POST membuat data baru, PATCH mengubah, PUT mengganti, dan DELETE menghapus. Formulir HTML mengirim PUT, PATCH, dan DELETE lewat isian `_method`. Tindakan lain memakai POST ke alamat berakhiran kata kerja, misalnya `/nonaktifkan` dan `/verifikasi`. | RT-05 | DECISION |
| Halaman formulir | Segmen `tambah` dan `ubah`, misalnya `/panel/siswa/tambah` dan `/panel/siswa/12/ubah`. | RT-06 | DECISION |
| Versi API kiosk | Versi di alamat: `/kiosk/api/v1/…`. Perubahan yang tidak kompatibel memakai versi baru, dan versi lama tetap dilayani sampai semua stasiun memakai kode baru. | `10` API-10 | DECISION |
| Status stasiun | Selain isian yang diwajibkan spesifikasi, kiosk melaporkan versi kodenya dan keadaan penyimpanan permanen. Keduanya tampil di halaman status stasiun, dan menambah kolom `versi_kiosk` dan `penyimpanan_permanen` di `status_stasiun`. | HAL-KIO-02, `10` EP-KIO-03, `06` §8.1 | DECISION |
| Kelas saya | Menu "Kelas saya" di bawah Dashboard bagi wali kelas, yang membuka daftar presensi kelasnya hari ini. | §4.1, HAL-LAP-03 | DECISION |
| Pencarian siswa | Formulir cari biasa, ditambah hasil langsung saat mengetik bila JavaScript aktif. | RT-14, `10` EP-MD-01 | DECISION |
| Hak pemeriksaan sistem | Hak baru `HA-AKN-07`, "Lihat pemeriksaan sistem", hanya untuk admin. | HAL-AKN-07, `02` §6.1 | DECISION |

### 2.2 Temuan yang ditetapkan tanpa ronde diskusi

Penyusunan route dan API menemukan kebutuhan berikut. Semuanya berstatus RECOMMENDATION, dan perubahannya di dokumen lain tercatat di §16.

| Temuan | Penetapan | Rujukan |
|---|---|---|
| FS-KIO-03 E4 meminta scan yang ditolak server dilaporkan di status stasiun, tetapi belum ada tempat menyimpannya. | Kiosk melaporkan jumlah scan galat di setiap kiriman sinkron, dan server menyimpannya di kolom baru `status_stasiun.scan_galat`. Scan galat tidak dihitung sebagai belum tersinkron. | `10` EP-KIO-03, `06` §8.1 |
| ARS-31 melarang scan dikirim dengan akun stasiun lain, tetapi server belum dapat memeriksanya. | Setiap scan di kiriman membawa ID akun stasiun pencatatnya. Server menolak scan yang akunnya berbeda dengan akun yang login. | `10` EP-KIO-03 |
| `status_stasiun.data_dimuat_at` belum memiliki cara lapor. | Kiosk melaporkan waktu data selesai dimuat di setiap kiriman sinkron. | `10` EP-KIO-03 |
| Logout akun stasiun memerlukan cookie login stasiun, yang hanya dikirim ke alamat berawalan `/kiosk` (`07` ARS-30). | Logout kiosk memakai `POST /kiosk/api/v1/logout`, bukan `/logout`. | `10` EP-KIO-04 |
| Halaman yang menampilkan password sekali tidak dapat memakai pola POST-alihkan-GET tanpa menyimpan password di sesi. | Halaman itu menjadi jawaban langsung permintaan tulisnya, tanpa disimpan di cache, dengan token sekali pakai. | RT-09 |

## 3. Konvensi route

### 3.1 Area dan hak

| ID | Aturan | Status |
|---|---|---|
| RT-01 | **Area, prefiks, dan file route.** Setiap area memiliki prefiks, file route, filter kelompok, namespace controller, dan halaman awal sendiri (tabel di bawah). File route didaftarkan di `Config\Routing::$routeFiles`, dengan `app/Config/Routes.php` di urutan pertama karena berisi placeholder, route `/`, dan route `/logo`. Auto routing tetap mati (`07` ARS-12). | RECOMMENDATION |
| RT-02 | **Hak per method.** Setiap method controller di area panel, portal, dan kiosk, serta ganti password dan logout, memiliki atribut `#[Filter(by: 'hak', having: [...])]` (`07` ARS-13). Satu atribut dengan beberapa ID berarti cukup salah satu hak. Beberapa atribut berarti semuanya wajib, misalnya export yang memerlukan hak laporan dan `HA-LAP-05` (`13` IE-05). Atribut dibaca karena `Config\Routing::$useControllerAttributes` bernilai `true`, bawaan CI4 4.7. Filter hanya memeriksa hak. Cakupan data, batas mundur, dan keadaan data diperiksa service (`07` ARS-15), sehingga pemegang hak dengan cakupan Rombel tetap ditolak untuk siswa kelas lain. Uji otomatis memastikan setiap method di area itu memiliki atribut hak. | RECOMMENDATION |

Area (RT-01):

| Area | Prefiks | File route | Filter kelompok | Namespace | Halaman awal |
|---|---|---|---|---|---|
| Akun | `/login`, `/logout`, `/akun/…` | `Routes/Akun.php` | Logout dan ganti password: `sesi`, `area:staf,siswa` | `Akun` | — |
| Panel staf/admin | `/panel` | `Routes/Panel.php` | `sesi`, `area:staf`, `wajib-ganti` | `Panel` | `/panel` (HAL-LAP-01) |
| Portal siswa | `/portal` | `Routes/Portal.php` | `sesi`, `area:siswa`, `wajib-ganti` | `Portal` | `/portal` (HAL-LAP-07) |
| Kiosk | `/kiosk` | `Routes/Kiosk.php` | `sesi`, `area:stasiun` | `Kiosk` | `/kiosk` (HAL-KIO-01) |
| Publik | `/` dan `/logo`; di R3 juga `/pengumuman/…` | `Routes.php`; di R3 juga `Routes/Publik.php` | — | `Publik` | `/` (HAL-INF-05, R3) |

File route berada di `app/Config/Routes/`. Filter `area` menerima beberapa jenis akun yang dipisah koma. Filter `csrf` tetap global untuk semua permintaan selain GET (`07` ARS-13), termasuk PUT, PATCH, dan DELETE hasil isian `_method`.

### 3.2 Alamat dan parameter

| ID | Aturan | Status |
|---|---|---|
| RT-03 | **Bahasa dan bentuk alamat.** Alamat memakai kata bahasa Indonesia dengan huruf kecil dan tanda hubung, misalnya `/panel/presensi-manual` dan `/panel/jadwal-hari-ini`. Kata diambil dari glosarium dan label layar (`08` §9.1): rombel ditulis `kelas`, dan tingkat ditulis `tingkat`. Kumpulan data memakai kata benda tanpa bentuk jamak, misalnya `/panel/siswa` dan `/panel/izin`. Alamat tidak memuat nama siswa, NISN, atau data pribadi lain. | DECISION (`kelas`, Session 8); RECOMMENDATION (lainnya) |
| RT-04 | **Parameter.** Lihat butir di bawah tabel. | RECOMMENDATION |

Butir RT-04:

1. Identitas data ditulis di bagian alamat sebagai `id` angka dari tabel `06`, tidak pernah sebagai NISN. Alasannya, NISN dapat dikoreksi (FS-MD-04), dan alamat tercatat di riwayat browser serta log server (R-17).
2. Saringan, tanggal, dan halaman daftar ditulis di query. Nama parameter memakai `snake_case`, misalnya `tahun_ajaran` dan `tanpa_wa`. Parameter `kelas` berisi ID rombel.
3. Tanggal ditulis `YYYY-MM-DD`, bulan `YYYY-MM`, dan jam `HH:MM` dalam format 24 jam.
4. Halaman daftar memakai `page`, nama bawaan Pager CI4, sebagai satu-satunya parameter berbahasa Inggris. Urutan memakai `urut` (nama kolom) dan `arah` (`naik` atau `turun`).
5. Daftar ID memakai bentuk array, misalnya `siswa[]=12&siswa[]=15`.
6. Parameter saringan yang tidak valid diabaikan dan diganti nilai bawaan, disertai pesan kilat. Parameter yang menentukan data utama halaman, misalnya `{id}`, mengikuti RT-11.

### 3.3 Metode dan formulir

| ID | Aturan | Status |
|---|---|---|
| RT-05 | **Metode HTTP.** Metode dipakai sesuai tabel di bawah, dengan butir penerapan di bawahnya. | DECISION (gaya REST, Session 8); RECOMMENDATION (pemetaan dan penerapan) |
| RT-06 | **Halaman formulir dan konfirmasi.** Setiap tindakan yang membutuhkan isian atau konfirmasi memiliki halaman GET, dengan pasangan di tabel kedua di bawah. Tindakan tanpa isian dan tanpa konfirmasi tidak memiliki halaman GET; tombolnya langsung mengirim, misalnya aktifkan akun. Halaman konfirmasi menyebut dampaknya (`04` §4.7, `08` UI-28). R1 memakai halaman konfirmasi biasa. Tampilan konfirmasi dalam `<dialog>` (UI-28) adalah peningkatan opsional tanpa route baru. | DECISION (`tambah` dan `ubah`, Session 8); RECOMMENDATION (lainnya) |
| RT-07 | **Konfirmasi bersyarat dan halaman periksa.** Bila dampak baru diketahui setelah isian diperiksa server, permintaan tulis pertama tidak menyimpan apa pun. Server menjawab 200 dengan halaman periksa yang memuat dampaknya dan isian yang sama sebagai isian tersembunyi, ditambah `konfirmasi=1`. Permintaan kedua dengan `konfirmasi=1` memeriksa ulang semua isian, lalu menyimpan. File unggahan dari permintaan pertama disimpan di `tmp/<token>/` (`07` ARS-55), dan token itu dibawa permintaan kedua. Token terikat ke akun pembuatnya dan hanya dapat dipakai sekali. Token yang tidak ada, milik akun lain, atau sudah dipakai dialihkan ke formulir asalnya dengan pesan. Pratinjau presensi manual tanpa JavaScript memakai isian `periksa=1` dengan cara yang sama, tanpa token. | RECOMMENDATION |

Metode (RT-05):

| Metode | Dipakai untuk | Contoh |
|---|---|---|
| GET | Membaca halaman, formulir, halaman konfirmasi, fragmen, dan berkas. Tidak pernah mengubah data. | `GET /panel/siswa/12` |
| POST ke kumpulan | Membuat data baru. | `POST /panel/siswa` |
| PATCH | Mengubah sebagian kolom satu data lewat formulir ubah. | `PATCH /panel/siswa/12` |
| PUT | Mengganti satu nilai atau bagian yang berdiri sendiri secara utuh, misalnya foto, nomor WA, password, batas mundur, dan jadwal hari ini. | `PUT /panel/siswa/12/foto` |
| DELETE | Menghapus data, atau mengakhiri data yang di layar disebut "hapus", misalnya koreksi (FS-PRS-07). Data transaksi tetap tersimpan sesuai `06` DB-09. | `DELETE /panel/koreksi/7` |
| POST ke alamat berakhiran kata kerja | Tindakan lain, misalnya nonaktifkan, aktifkan, verifikasi, batalkan, reset password, dan tinjau. | `POST /panel/siswa/12/nonaktifkan` |

Penerapan RT-05:

1. Formulir HTML memakai `method="post"` dan isian tersembunyi `_method` bernilai `PUT`, `PATCH`, atau `DELETE` dengan huruf kapital. CI4 hanya mengubah metode untuk permintaan POST, dan hanya untuk ketiga nilai itu (`CodeIgniter::spoofRequestMethod()`).
2. Browser tetap mengirim POST, sehingga unggahan file lewat PUT, misalnya ganti foto, tetap terbaca dengan `$this->request->getFile()`.
3. Route didaftarkan dengan metode hasil pengubahan, misalnya `$routes->patch('siswa/(:num)', 'Siswa::perbarui/$1')`.
4. Permintaan JavaScript yang mengubah data juga dikirim sebagai POST, dengan `_method` bila perlu, sehingga perilakunya sama dengan formulir. CI4 memilih kode pengalihan dari metode HTTP asli: 303 untuk POST, PUT, dan DELETE, tetapi 307 untuk PATCH, yang membuat browser mengulang PATCH ke alamat tujuan. API kiosk hanya memakai GET dan POST (`10`).

Pasangan halaman dan tindakan (RT-06):

| Halaman GET | Tindakan |
|---|---|
| `<kumpulan>/tambah` | `POST <kumpulan>` |
| `<data>/ubah` | `PATCH <data>`, atau `PUT` untuk bagian yang berdiri sendiri, misalnya `GET /panel/siswa/12/wa/ubah` dengan `PUT /panel/siswa/12/wa` |
| `<data>/hapus` (konfirmasi) | `DELETE <data>` |
| `<data>/<kata-kerja>` (isian alasan atau konfirmasi) | `POST <data>/<kata-kerja>` |

### 3.4 Jawaban dan pengalihan

| ID | Aturan | Status |
|---|---|---|
| RT-08 | **POST-alihkan-GET dan pesan kilat.** Lihat butir di bawah tabel. | RECOMMENDATION |
| RT-09 | **Rahasia yang tampil sekali.** Password awal akun staf dan stasiun, password hasil reset atau ganti kredensial, dan slip akun (FS-AKN-03, FS-AKN-04, FS-AKN-05) tampil sebagai jawaban 200 langsung dari permintaan tulisnya, bukan lewat pengalihan, sehingga password tidak pernah ditulis ke sesi atau log. Jawaban memakai `Cache-Control: no-store`. Formulir atau halaman konfirmasi sebelumnya membawa token sekali pakai yang disimpan di sesi. Bila halaman dimuat ulang dan permintaan terkirim lagi, token sudah terpakai, sehingga server tidak membuat password baru dan mengalihkan ke halaman asal dengan pesan bahwa tindakan sudah dijalankan. Cara ini tidak bergantung pada pengaturan regenerasi token CSRF (Session 9). | RECOMMENDATION |
| RT-10 | **Kembali ke halaman asal.** Tautan tindakan dari daftar, misalnya koreksi dari daftar presensi kelas, membawa parameter `kembali` berisi alamat asal. Setelah berhasil, server mengalihkan ke alamat itu. Nilai `kembali` hanya diterima bila berupa alamat relatif di area yang sama, diawali `/panel/` atau `/portal/`, tanpa `//`, garis miring terbalik, skema, atau host. Selain itu, nilai diabaikan dan server memakai tujuan bawaan route. | RECOMMENDATION |
| RT-11 | **Data yang tidak ada dan di luar cakupan.** ID di luar cakupan pengguna dijawab 403 dengan pesan di luar hak (`08` UI-56). ID yang tidak ada dijawab sama bagi pengguna yang hanya memiliki cakupan terbatas (Rombel, Hari ini, atau Sendiri) untuk hak halaman itu, sehingga keberadaan data tidak terbaca (`04` §4.1 butir 3). Pengguna dengan cakupan Semua mendapat 404. | RECOMMENDATION |
| RT-12 | **Token versi dan penjaga di formulir.** Formulir PATCH, PUT, dan DELETE untuk data yang dapat diubah bersamaan membawa isian tersembunyi `versi` berisi `updated_at` saat formulir dibuka (`07` ARS-40). Formulir yang bergantung pada keadaan data membawa penjaganya, misalnya ID koreksi aktif saat formulir koreksi dibuka, atau ID periode mode darurat yang aktif (`07` ARS-41). Bila token atau penjaga tidak cocok, berlaku RT-08 butir 3. | RECOMMENDATION |

Butir RT-08:

1. Permintaan tulis yang berhasil dijawab 303 ke halaman GET, dengan pesan kilat (`08` UI-28). Tujuan pengalihan disebut di kolom Hasil setiap route.
2. Permintaan tulis yang gagal validasi dijawab 303 ke halaman formulirnya, dengan isian lama dan pesan galat di flashdata (`withInput()`). Tujuannya ditulis eksplisit, bukan `redirect()->back()`.
3. Perubahan bersamaan yang ditolak dialihkan ke formulir yang memuat data terbaru, dengan pesan "data sudah diubah" (`08` UI-56).
4. Pengecualian: rahasia yang tampil sekali (RT-09), halaman periksa (RT-07), dan permintaan latar belakang (`10`).

### 3.5 Daftar, pencarian, fragmen, dan berkas

| ID | Aturan | Status |
|---|---|---|
| RT-13 | **Daftar, saringan, dan urutan.** Daftar panjang memakai 50 baris per halaman (`08` UI-26) dengan parameter `page`. Saringan memakai formulir GET, sehingga alamat dapat disimpan dan dibagikan. Saringan yang tidak diisi tidak ditulis di alamat. Daftar tanpa saringan menampilkan isi bawaan yang disebut di halaman masing-masing. | RECOMMENDATION |
| RT-14 | **Pencarian siswa.** Lihat butir di bawah tabel. | DECISION (hasil langsung saat mengetik, Session 8); RECOMMENDATION (rincian) |
| RT-15 | **Fragmen dan permintaan latar belakang.** Bagian halaman yang diperbarui tanpa memuat ulang halaman memakai alamat fragmen yang disebut di halamannya, dengan hak yang sama dengan halamannya (`07` ARS-50). Permintaan ini mengirim header permintaan latar belakang, sehingga filter menjawab dengan kode JSON, bukan pengalihan (`07` ARS-13). Bentuknya di `10`. | RECOMMENDATION |
| RT-16 | **Berkas.** Foto, lampiran, template, dan file hasil disajikan lewat controller setelah hak diperiksa, dengan header di `07` ARS-52. Daftar alamatnya ada di §13. Nama file unduhan mengikuti `13` IE-07. | RECOMMENDATION |
| RT-17 | **Permintaan panjang dan sesi.** Permintaan yang hanya membaca sesi menutup sesi segera setelah identitas dibaca (`07` ARS-48): fragmen, pencarian siswa, berkas, API kiosk, dan export (R2). Unggah import dan proses foto massal menyimpan hasilnya di `tmp/<token>/`, bukan di sesi, sehingga sesi ditutup sebelum file dibaca. Permintaan yang memproses antrean hitung ulang menulis pesan kilat lebih dulu, lalu menutup sesi sebelum menunggu kunci (`07` ARS-36 butir 3). | RECOMMENDATION |

Butir RT-14:

1. Halaman yang memilih siswa, yaitu daftar siswa, presensi manual, input izin, dispensasi massal, dan riwayat siswa, memakai satu komponen pencarian.
2. Tanpa JavaScript, pengguna mengisi nama atau NISN lalu menekan Cari. Halaman dimuat ulang dengan parameter `cari`, dan hasilnya tampil di halaman itu.
3. Dengan JavaScript, hasil yang sama tampil saat mengetik, diambil dari fragmen `GET /panel/siswa/cari` (`10` EP-MD-01). Halaman dan fragmen memakai view komponen yang sama.
4. Hasil hanya memuat siswa dalam cakupan hak tujuan pencarian (`04` §4.1 butir 3). Pencarian mencocokkan bagian nama atau awal NISN, paling sedikit 2 karakter, dan menampilkan paling banyak 20 siswa beserta foto kecil, nama, NISN, kelas, dan status siswa.
5. Pada dispensasi massal, pencarian berjalan dalam mode pilih banyak. Setiap hasil memiliki kotak centang, dan siswa yang sudah dipilih dibawa sebagai `siswa[]` pada pencarian berikutnya, sehingga pilihan dari beberapa pencarian terkumpul.
6. Pintasan dari dashboard, daftar presensi kelas, dan profil siswa mengisi siswa lewat parameter `siswa`, sehingga pencarian dilewati.

### 3.6 Login, galat, dan nama teknis

| ID | Aturan | Status |
|---|---|---|
| RT-18 | **Halaman awal dan tujuan setelah login.** Lihat butir di bawah tabel. | RECOMMENDATION |
| RT-19 | **Halaman galat.** Lihat butir di bawah tabel. | RECOMMENDATION |
| RT-20 | **Nama controller, method, dan route.** Satu controller untuk satu kumpulan data di namespace area, misalnya `Panel\Siswa`, dengan nama teknis (`Rombel`, bukan `Kelas`). Nama method mengikuti tabel di bawah. Nama route berbentuk `<area>.<controller>.<method>` dalam `snake_case`, misalnya `panel.presensi_manual.form_batalkan`. View membuat alamat dengan `url_to()`. Modul JavaScript membaca alamat dari atribut `data-*` di HTML, bukan menulisnya sendiri. | RECOMMENDATION |
| RT-21 | **Menu dan tombol sesuai hak.** Menu dan tombol tindakan hanya tampil bila pengguna memiliki hak untuk halaman atau data itu (`04` §4.1 butir 1, `08` UI-31). Tombol per baris memeriksa cakupan dan batas mundur untuk siswa dan tanggal baris itu, dengan service yang sama dengan pemeriksaan di server. Jumlah di lencana menu dihitung setiap kali halaman dibuat, bukan dari cache. | RECOMMENDATION |
| RT-22 | **Route R2 dan R3.** Route R2 dan R3 di dokumen ini adalah kerangka. Route itu baru didaftarkan saat rilisnya, dan dirinci ulang bersama fiturnya menjelang rilis (`04` §11). | RECOMMENDATION |

Butir RT-18:

1. Halaman awal mengikuti `02` §8: akun staf ke `/panel`, akun siswa ke `/portal`, dan akun stasiun ke `/kiosk`. Akun yang wajib mengganti password diarahkan ke `/akun/password` lebih dulu.
2. Bila filter `sesi` menolak navigasi GET biasa, yaitu bukan permintaan latar belakang, alamat yang diminta disimpan di sesi sebagai `tujuan`, lalu pengguna dialihkan ke `/login`. Pesan "Sesi berakhir" (`08` UI-56) tampil bila permintaan membawa cookie sesi yang sudah tidak berlaku.
3. Setelah login berhasil, server mengalihkan ke `tujuan` bila alamat itu berada di area jenis akun yang login dan bukan kiosk (FS-AKN-01 E4). Selain itu, pengguna diarahkan ke halaman awalnya.
4. `GET /login` dari pengguna yang sudah login mengalihkan ke halaman awalnya.
5. Sebelum R3, `GET /` mengalihkan pengunjung yang belum login ke `/login`, dan pengguna yang sudah login ke halaman awalnya (`02` §8). Di R3, `/` menjadi halaman publik (HAL-INF-05).

Butir RT-19:

1. Halaman 403, 404, dan 500 memakai pesan `08` UI-56 dan tautan ke halaman awal area pengguna (`08` UI-38).
2. 404 memakai `Config\Routing::$override404` yang menunjuk `Galat::tidakDitemukan`. Controller ini memilih layout dari prefiks alamat dan jenis akun yang login.
3. 403 berasal dari filter `hak`, dan dari pengecualian `DiLuarHak` yang dilempar service bila cakupan, batas mundur, atau keadaan data tidak cocok. Handler pengecualian aplikasi (`Config\Exceptions::handler()`) mengubah pengecualian itu menjadi halaman 403.
4. 500 di production memakai view `app/Views/errors/html/production.php` yang disesuaikan, tanpa layout area, karena galat dapat terjadi sebelum data akun termuat.
5. Akun yang membuka area jenis akun lain dialihkan ke halaman awalnya oleh filter `area`, bukan dijawab 403 (FS-AKN-01 E5).
6. Permintaan latar belakang menerima kode JSON (`10` API-03).

Nama method (RT-20):

| Route | Method | Contoh |
|---|---|---|
| `GET <kumpulan>` | `index` | `Siswa::index` |
| `GET <kumpulan>/tambah` | `tambah` | `Siswa::tambah` |
| `POST <kumpulan>` | `simpan` | `Siswa::simpan` |
| `GET <data>` | `lihat` | `Siswa::lihat` |
| `GET <data>/ubah` | `ubah` | `Siswa::ubah` |
| `PATCH <data>` | `perbarui` | `Siswa::perbarui` |
| `PUT <bagian>` | `ganti` | `SiswaFoto::ganti` |
| `GET <data>/hapus` | `formHapus` | `Libur::formHapus` |
| `DELETE <data>` | `hapus` | `Libur::hapus` |
| `GET <data>/<kata-kerja>` | `form<KataKerja>` | `Siswa::formNonaktifkan` |
| `POST <data>/<kata-kerja>` | `<kataKerja>` | `Siswa::nonaktifkan` |
| `GET <halaman>/fragmen` | `fragmen` | `Dashboard::fragmen` |
| Unduhan | `unduh<Isi>` | `ImportSiswa::unduhTemplate` |

## 4. Menu

### 4.1 Panel staf

Menu ini menyelesaikan isi kelompok di `08` UI-31. Urutan kelompok dan butir mengikuti tabel. Kelompok tanpa butir yang boleh dibuka disembunyikan.

| Kelompok | Menu | Alamat | Hak | Lencana atau keadaan | Halaman |
|---|---|---|---|---|---|
| — | Dashboard hari ini | `/panel` | `HA-LAP-01` | — | HAL-LAP-01 |
| — | Kelas saya | `/panel/kelas-saya` | Wali kelas di tahun ajaran aktif (`HA-LAP-02`) | — | HAL-LAP-03 |
| Presensi | Daftar presensi kelas | `/panel/presensi/kelas` | `HA-LAP-02`, `HA-LAP-03` | — | HAL-LAP-04 |
| Presensi | Presensi manual | `/panel/presensi-manual` | `HA-PRS-03` | — | HAL-PRS-07 |
| Presensi | Presensi per kelas | `/panel/mode-darurat/kelas` | `HA-PRS-03` | Hanya tampil selama mode darurat aktif | HAL-PRS-06 |
| Presensi | Jadwal hari ini | `/panel/jadwal-hari-ini` | `HA-PRS-07` | "Diubah" bila jadwal hari ini sudah diubah | HAL-PRS-04 |
| Presensi | Mode darurat | `/panel/mode-darurat` | `HA-PRS-08` | "Aktif" selama mode darurat aktif | HAL-PRS-05 |
| Presensi | Scan bertanda | `/panel/scan-bertanda` | `HA-KIO-03` | Jumlah scan yang menunggu tinjauan dalam cakupan | HAL-KIO-03 |
| Presensi | Log perubahan presensi | `/panel/log-presensi` | `HA-PRS-06` | — | HAL-PRS-10 |
| Izin | Pengajuan menunggu | `/panel/izin/menunggu` | `HA-IZN-03` | Jumlah pengajuan menunggu dalam cakupan | HAL-IZN-04 |
| Izin | Izin, sakit, dispensasi | `/panel/izin` | `HA-IZN-04` | — | HAL-IZN-05 |
| Izin | Input izin | `/panel/izin/tambah` | `HA-IZN-02` | — | HAL-IZN-08 |
| Izin | Dispensasi massal | `/panel/dispensasi-massal` | `HA-IZN-04`, `HA-IZN-02` | — | HAL-IZN-09 |
| Laporan | Rekap per kelas | `/panel/laporan/rekap-kelas` | `HA-LAP-03` | — | HAL-LAP-05 |
| Laporan | Riwayat siswa | `/panel/laporan/riwayat-siswa` | `HA-LAP-04` | — | HAL-LAP-06 |
| Laporan (R2) | Rekap semua kelas | `/panel/laporan/rekap-sekolah` | `HA-LAP-05` dengan cakupan Semua | — | HAL-LAP-08 |
| Laporan (R2) | Rekap rapor | `/panel/laporan/rekap-rapor` | `HA-LAP-03` + `HA-LAP-05` | — | HAL-LAP-09 |
| Laporan (R2) | Flyer kehadiran | `/panel/laporan/flyer` | `HA-LAP-06` | — | HAL-LAP-10 |
| Siswa | Data siswa | `/panel/siswa` | `HA-MD-05` | — | HAL-MD-04 |
| Siswa | Import siswa | `/panel/siswa/import` | `HA-MD-04` | — | HAL-MD-14 |
| Siswa | Foto massal | `/panel/siswa/foto-massal` | `HA-MD-08` | — | HAL-MD-15 |
| Siswa | Penempatan kelas | `/panel/penempatan` | `HA-MD-03` | — | HAL-MD-12 |
| Siswa | Akun siswa | `/panel/akun-siswa` | `HA-AKN-06` | — | HAL-AKN-05 |
| Siswa | Atribut tambahan | `/panel/atribut-siswa` | `HA-MD-11` | — | HAL-MD-16 |
| Siswa (R3) | Cetak kartu | `/panel/kartu` | `HA-KRT-01` | — | HAL-KRT-01 |
| Sekolah | Identitas sekolah | `/panel/sekolah` | `HA-MD-09` | — | HAL-MD-01 |
| Sekolah | Tahun ajaran | `/panel/tahun-ajaran` | `HA-MD-01` | — | HAL-MD-02 |
| Sekolah | Kelas dan wali kelas | `/panel/kelas` | `HA-MD-02` | — | HAL-MD-03 |
| Sekolah | Pola mingguan | `/panel/pola-mingguan` | `HA-PRS-01` | — | HAL-PRS-01 |
| Sekolah | Jadwal khusus | `/panel/jadwal-khusus` | `HA-PRS-01` | — | HAL-PRS-02 |
| Sekolah | Libur | `/panel/libur` | `HA-PRS-02` | — | HAL-PRS-03 |
| Sekolah | Batas mundur | `/panel/batas-mundur` | `HA-PRS-09` | — | HAL-PRS-09 |
| Informasi (R3) | Jadwal pelajaran | `/panel/jadwal-pelajaran` | `HA-INF-02` | — | HAL-INF-02 |
| Informasi (R3) | Mata pelajaran | `/panel/mata-pelajaran` | `HA-INF-01` | — | HAL-INF-01 |
| Informasi (R3) | Pengumuman | `/panel/pengumuman` | `HA-INF-04` | — | HAL-INF-04 |
| Notifikasi WA (R2) | Pengaturan WA | `/panel/wa` | `HA-WA-01` | — | HAL-WA-01 |
| Notifikasi WA (R2) | Outbox WA | `/panel/wa/outbox` | `HA-WA-02` | Jumlah pesan gagal | HAL-WA-02 |
| Notifikasi WA (R2) | Pesan ditahan | `/panel/wa/penahanan` | `HA-WA-03` | Jumlah penahanan aktif | HAL-WA-03 |
| Akun dan stasiun | Akun staf | `/panel/akun-staf` | `HA-AKN-02` | — | HAL-AKN-04 |
| Akun dan stasiun | Status stasiun | `/panel/stasiun` | `HA-KIO-02`, `HA-AKN-03` | Jumlah stasiun yang disorot | HAL-KIO-02 |
| Akun dan stasiun | Pemeriksaan sistem | `/panel/sistem` | `HA-AKN-07` | Tanda bila ada pemeriksaan yang perlu tindakan | HAL-AKN-07 |

Menu akun di bilah atas (`08` UI-29) berisi "Ganti password" (`/akun/password`) dan "Logout" (`POST /logout`). Bilah mode darurat (`08` UI-30) menautkan `/panel/mode-darurat/kelas` bagi pemegang `HA-PRS-03`.

Contoh menu R1 per role, diturunkan dari matriks `02` §6. Akun dengan beberapa role melihat gabungannya (`02` §4 butir 1).

| Menu | Staf | Wali kelas | Guru piket | Guru BK | Pimpinan | Admin |
|---|---|---|---|---|---|---|
| Dashboard hari ini | Ya | Ya | Ya | Ya | Ya | Ya |
| Kelas saya | — | Ya | — | — | — | — |
| Daftar presensi kelas | — | Ya | Hari ini | Ya | Ya | Ya |
| Presensi manual, presensi per kelas | — | Ya | Ya | Ya | — | Ya |
| Jadwal hari ini, mode darurat, scan bertanda | — | — | Ya | — | — | Ya |
| Log perubahan presensi | — | Ya | Ya | Ya | Ya | Ya |
| Pengajuan menunggu, input izin | — | Ya | Ya | Ya | — | Ya |
| Izin, sakit, dispensasi; dispensasi massal | — | Ya | Ya | Ya | Lihat | Ya |
| Rekap per kelas, riwayat siswa | — | Ya | — | Ya | Ya | Ya |
| Data siswa | — | Ya | Ya | Ya | Ya | Ya |
| Akun siswa | — | Ya | — | — | — | Ya |
| Import siswa, foto massal, penempatan kelas, atribut tambahan | — | — | — | — | — | Ya |
| Kelompok Sekolah | — | — | — | — | — | Ya |
| Akun staf, pemeriksaan sistem | — | — | — | — | — | Ya |
| Status stasiun | — | — | Ya | — | — | Ya |

Admin yang juga wali kelas melihat "Kelas saya".

### 4.2 Portal siswa

Menu bawah di ponsel dan menu atas di layar lebar (`08` UI-35):

| Menu | Alamat | Hak | Halaman |
|---|---|---|---|
| Riwayat | `/portal` | `HA-LAP-04` | HAL-LAP-07 |
| Izin | `/portal/izin` | `HA-IZN-04` | HAL-IZN-01 |
| Jadwal (R3) | `/portal/jadwal` | `HA-INF-02` | HAL-INF-02 |
| Pengumuman (R3) | `/portal/pengumuman` | `HA-INF-04` | HAL-INF-04 |
| Akun | `/portal/akun` | `HA-MD-05` | HAL-AKN-08 |

## 5. Akun dan akses (AKN)

| Halaman | Metode | Alamat | Controller::method | Hak | Hasil |
|---|---|---|---|---|---|
| HAL-AKN-01 | GET | `/login` | `Akun\Login::index` | — | Halaman. Pengguna yang sudah login dialihkan ke halaman awalnya. |
| HAL-AKN-01 | POST | `/login` | `Akun\Login::masuk` | — | 303 → tujuan atau halaman awal (RT-18), atau `/akun/password` bila wajib ganti. Gagal: 303 → `/login`. |
| HAL-AKN-02 | POST | `/logout` | `Akun\Login::keluar` | `HA-AKN-01` | 303 → `/login` |
| HAL-AKN-03 | GET | `/akun/password` | `Akun\Password::ubah` | `HA-AKN-01` | Halaman |
| HAL-AKN-03 | PUT | `/akun/password` | `Akun\Password::ganti` | `HA-AKN-01` | 303 → halaman awal |
| HAL-AKN-04 | GET | `/panel/akun-staf` | `Panel\AkunStaf::index` | `HA-AKN-02` | Halaman |
| HAL-AKN-04 | GET | `/panel/akun-staf/tambah` | `Panel\AkunStaf::tambah` | `HA-AKN-02` | Halaman |
| HAL-AKN-04 | POST | `/panel/akun-staf` | `Panel\AkunStaf::simpan` | `HA-AKN-02` | 200 password awal (RT-09) |
| HAL-AKN-04 | GET | `/panel/akun-staf/{id}` | `Panel\AkunStaf::lihat` | `HA-AKN-02` | Halaman |
| HAL-AKN-04 | GET | `/panel/akun-staf/{id}/ubah` | `Panel\AkunStaf::ubah` | `HA-AKN-02` | Halaman |
| HAL-AKN-04 | PATCH | `/panel/akun-staf/{id}` | `Panel\AkunStaf::perbarui` | `HA-AKN-02` | 303 → detail |
| HAL-AKN-04 | GET | `/panel/akun-staf/{id}/nonaktifkan` | `Panel\AkunStaf::formNonaktifkan` | `HA-AKN-02` | Halaman konfirmasi |
| HAL-AKN-04 | POST | `/panel/akun-staf/{id}/nonaktifkan` | `Panel\AkunStaf::nonaktifkan` | `HA-AKN-02` | 303 → detail |
| HAL-AKN-04 | POST | `/panel/akun-staf/{id}/aktifkan` | `Panel\AkunStaf::aktifkan` | `HA-AKN-02` | 303 → detail |
| HAL-AKN-04 | GET | `/panel/akun-staf/{id}/reset-password` | `Panel\AkunStaf::formResetPassword` | `HA-AKN-02` | Halaman konfirmasi |
| HAL-AKN-04 | POST | `/panel/akun-staf/{id}/reset-password` | `Panel\AkunStaf::resetPassword` | `HA-AKN-02` | 200 password baru (RT-09) |
| HAL-AKN-05 | GET | `/panel/akun-siswa` | `Panel\AkunSiswa::index` | `HA-AKN-06` | Halaman (`?kelas=`) |
| HAL-AKN-05 | GET | `/panel/akun-siswa/slip` | `Panel\AkunSiswa::formSlip` | `HA-AKN-05` | Halaman konfirmasi (`?kelas=&siswa[]=`) |
| HAL-AKN-05 | POST | `/panel/akun-siswa/slip` | `Panel\AkunSiswa::slip` | `HA-AKN-05` | 200 halaman slip (RT-09) |
| HAL-AKN-06 | GET | `/panel/siswa/{id}/reset-password` | `Panel\AkunSiswa::formReset` | `HA-AKN-04` | Halaman konfirmasi |
| HAL-AKN-06 | POST | `/panel/siswa/{id}/reset-password` | `Panel\AkunSiswa::reset` | `HA-AKN-04` | 200 slip satu siswa (RT-09) |
| HAL-AKN-07 | GET | `/panel/sistem` | `Panel\Sistem::index` | `HA-AKN-07` | Halaman |
| HAL-AKN-08 | GET | `/portal/akun` | `Portal\Akun::index` | `HA-MD-05` | Halaman |
| HAL-AKN-08 | GET | `/portal/foto` | `Portal\Akun::foto` | `HA-MD-05` | Berkas foto sendiri |

Rincian halaman:

- **HAL-AKN-01 — Login** (FS-AKN-01, `08` UI-38). Satu halaman untuk semua jenis akun, dengan isian `identitas` dan `password`. Login yang gagal menampilkan pesan umum E1/E2, dan isian identitas tetap terisi. Pembatasan percobaan (E3) ditetapkan di Session 9. Login akun stasiun juga membuat cookie login stasiun (`07` ARS-30), lalu mengalihkan ke `/kiosk`.
- **HAL-AKN-02 — Logout** (FS-AKN-01 butir 8). Tombol logout di menu akun mengirim POST dengan token CSRF. Akun stasiun logout dari kiosk lewat `10` EP-KIO-04, karena hak logout akun stasiun termasuk `HA-KIO-01`.
- **HAL-AKN-03 — Ganti password** (FS-AKN-02). Selama akun wajib mengganti password, halaman memakai kerangka halaman bersama tanpa menu (`08` UI-38). Pada penggantian wajib tepat setelah login, isian password lama tidak tampil (FS-AKN-02, isian password lama); penandanya ditulis ke sesi saat login. Di luar itu, halaman memakai kerangka area pengguna. Akun stasiun yang membuka halaman ini dialihkan ke `/kiosk`.
- **HAL-AKN-04 — Akun staf** (FS-AKN-03). Daftar menampilkan nama, username, role, kelas yang diampu, status, dan login terakhir, dengan saringan `cari`, `role`, dan `status`. Detail memuat data akun dan tombol tindakan. Tambah dan reset password menjawab dengan halaman password sekali tampil (RT-09), dengan tombol salin dan cetak serta peringatan (FS-AKN-03 catatan). Konfirmasi nonaktifkan menyebut bila akun itu wali kelas (FS-AKN-03 butir 9). Tombol nonaktifkan tidak tampil untuk akun sendiri (E4). Isian NIP ditambahkan di formulir ubah mulai R2 (`06` §5.1).
- **HAL-AKN-05 — Akun siswa dan slip** (FS-AKN-05). Tanpa `kelas`, halaman menampilkan pilihan kelas dalam cakupan. Dengan `kelas`, halaman menampilkan NISN, nama, status akun, waktu slip terakhir dibuat, dan login terakhir. Baris akun belum aktif memiliki kotak centang yang tercentang secara bawaan, dan tombol "Buat slip akun" membuka halaman konfirmasi untuk siswa terpilih. Konfirmasi menyebut jumlah akun yang mendapat password baru dan peringatan slip lama (FS-AKN-05 B2). Jawabannya halaman slip siap cetak (`08` UI-57 s.d. UI-59). Bila tidak ada akun belum aktif, tombol tidak tersedia dan halaman menampilkan "Semua akun di kelas ini sudah aktif." (E1).
- **HAL-AKN-06 — Reset password siswa** (FS-AKN-05 C). Dibuka dari profil siswa (HAL-MD-06). Konfirmasi, lalu slip satu siswa dengan isi yang sama (`08` UI-59). Tombol tidak tersedia untuk akun nonaktif (E2).
- **HAL-AKN-07 — Pemeriksaan sistem** (`07` ARS-57, DECISION hak Session 8). Halaman baca saja dengan bagian:
  - hasil pemeriksaan `aplikasi:cek` yang dijalankan di PHP-FPM: versi PHP dan MySQL, ekstensi, zona waktu PHP dan MySQL, collation dan `sql_mode`, hak tulis `writable/`, `baseURL` HTTPS, serta batas unggah, `memory_limit`, dan opcache;
  - waktu terakhir cron berjalan (`pengaturan.cron_terakhir_at`, ARS-56);
  - antrean hitung ulang yang menunggu dan yang gagal beserta galat terakhirnya (ARS-36 butir 6), serta `status_dibangun_sampai` (ARS-37);
  - versi aplikasi dan versi kode kiosk terbaru.

  Setiap pemeriksaan bertanda "Baik" atau "Perlu tindakan". Halaman tidak menjalankan perintah. Perbaikan memakai perintah CLI, misalnya `php spark status:antrean`.
- **HAL-AKN-08 — Akun di portal** (FS-MD-04 butir 6, AC-MD-04-06, `08` UI-35). Profil sendiri tanpa isian yang dapat diubah: foto, NISN, nama, kelas, nomor WA orang tua/wali, serta atribut lain dan atribut tambahan yang tampil di profil. Halaman memuat tautan ganti password dan tombol logout.

## 6. Master data (MD)

| Halaman | Metode | Alamat | Controller::method | Hak | Hasil |
|---|---|---|---|---|---|
| HAL-MD-01 | GET | `/panel/sekolah` | `Panel\Sekolah::index` | `HA-MD-09` | Halaman formulir |
| HAL-MD-01 | PATCH | `/panel/sekolah` | `Panel\Sekolah::perbarui` | `HA-MD-09` | 303 → `/panel/sekolah` |
| HAL-MD-02 | GET | `/panel/tahun-ajaran` | `Panel\TahunAjaran::index` | `HA-MD-01` | Halaman |
| HAL-MD-02 | GET | `/panel/tahun-ajaran/tambah` | `Panel\TahunAjaran::tambah` | `HA-MD-01` | Halaman |
| HAL-MD-02 | POST | `/panel/tahun-ajaran` | `Panel\TahunAjaran::simpan` | `HA-MD-01` | 303 → daftar |
| HAL-MD-02 | GET | `/panel/tahun-ajaran/{id}/ubah` | `Panel\TahunAjaran::ubah` | `HA-MD-01` | Halaman |
| HAL-MD-02 | PATCH | `/panel/tahun-ajaran/{id}` | `Panel\TahunAjaran::perbarui` | `HA-MD-01` | 303 → daftar; konfirmasi bersyarat (RT-07) |
| HAL-MD-02 | GET | `/panel/tahun-ajaran/{id}/aktifkan` | `Panel\TahunAjaran::formAktifkan` | `HA-MD-01` | Halaman konfirmasi |
| HAL-MD-02 | POST | `/panel/tahun-ajaran/{id}/aktifkan` | `Panel\TahunAjaran::aktifkan` | `HA-MD-01` | 303 → daftar |
| HAL-MD-02 | GET | `/panel/tahun-ajaran/{id}/hapus` | `Panel\TahunAjaran::formHapus` | `HA-MD-01` | Halaman konfirmasi |
| HAL-MD-02 | DELETE | `/panel/tahun-ajaran/{id}` | `Panel\TahunAjaran::hapus` | `HA-MD-01` | 303 → daftar |
| HAL-MD-03 | GET | `/panel/kelas` | `Panel\Rombel::index` | `HA-MD-02` | Halaman (`?tahun_ajaran=`) |
| HAL-MD-03 | GET | `/panel/kelas/tambah` | `Panel\Rombel::tambah` | `HA-MD-02` | Halaman |
| HAL-MD-03 | POST | `/panel/kelas` | `Panel\Rombel::simpan` | `HA-MD-02` | 303 → daftar |
| HAL-MD-03 | GET | `/panel/kelas/{id}/ubah` | `Panel\Rombel::ubah` | `HA-MD-02` | Halaman |
| HAL-MD-03 | PATCH | `/panel/kelas/{id}` | `Panel\Rombel::perbarui` | `HA-MD-02` | 303 → daftar |
| HAL-MD-03 | GET | `/panel/kelas/{id}/hapus` | `Panel\Rombel::formHapus` | `HA-MD-02` | Halaman konfirmasi |
| HAL-MD-03 | DELETE | `/panel/kelas/{id}` | `Panel\Rombel::hapus` | `HA-MD-02` | 303 → daftar |
| HAL-MD-04 | GET | `/panel/siswa` | `Panel\Siswa::index` | `HA-MD-05` | Halaman |
| HAL-MD-04 | GET | `/panel/siswa/cari` | `Panel\Siswa::cari` | `HA-MD-05`, `HA-PRS-03`, `HA-IZN-02`, `HA-LAP-04` | Fragmen (`10` EP-MD-01) |
| HAL-MD-05 | GET | `/panel/siswa/tambah` | `Panel\Siswa::tambah` | `HA-MD-03` | Halaman |
| HAL-MD-05 | POST | `/panel/siswa` | `Panel\Siswa::simpan` | `HA-MD-03` | 303 → profil |
| HAL-MD-06 | GET | `/panel/siswa/{id}` | `Panel\Siswa::lihat` | `HA-MD-05` | Halaman |
| HAL-MD-07 | GET | `/panel/siswa/{id}/ubah` | `Panel\Siswa::ubah` | `HA-MD-03` | Halaman |
| HAL-MD-07 | PATCH | `/panel/siswa/{id}` | `Panel\Siswa::perbarui` | `HA-MD-03` | 303 → profil |
| HAL-MD-08 | GET | `/panel/siswa/{id}/wa/ubah` | `Panel\SiswaWa::ubah` | `HA-MD-06` | Halaman |
| HAL-MD-08 | PUT | `/panel/siswa/{id}/wa` | `Panel\SiswaWa::ganti` | `HA-MD-06` | 303 → profil |
| HAL-MD-09 | GET | `/panel/siswa/{id}/foto` | `Panel\SiswaFoto::index` | `HA-MD-05` | Berkas foto (`?ukuran=kecil`) |
| HAL-MD-09 | GET | `/panel/siswa/{id}/foto/ubah` | `Panel\SiswaFoto::ubah` | `HA-MD-07` | Halaman |
| HAL-MD-09 | PUT | `/panel/siswa/{id}/foto` | `Panel\SiswaFoto::ganti` | `HA-MD-07` | 303 → profil |
| HAL-MD-10 | GET | `/panel/siswa/{id}/nonaktifkan` | `Panel\Siswa::formNonaktifkan` | `HA-MD-03` | Halaman formulir |
| HAL-MD-10 | POST | `/panel/siswa/{id}/nonaktifkan` | `Panel\Siswa::nonaktifkan` | `HA-MD-03` | 303 → profil |
| HAL-MD-10 | GET | `/panel/siswa/{id}/aktifkan` | `Panel\Siswa::formAktifkan` | `HA-MD-03` | Halaman formulir |
| HAL-MD-10 | POST | `/panel/siswa/{id}/aktifkan` | `Panel\Siswa::aktifkan` | `HA-MD-03` | 303 → profil |
| HAL-MD-11 | GET | `/panel/siswa/{id}/penempatan/tambah` | `Panel\Penempatan::tambah` | `HA-MD-03` | Halaman |
| HAL-MD-11 | POST | `/panel/siswa/{id}/penempatan` | `Panel\Penempatan::simpan` | `HA-MD-03` | 303 → profil |
| HAL-MD-12 | GET | `/panel/penempatan` | `Panel\Penempatan::index` | `HA-MD-03` | Halaman |
| HAL-MD-12 | GET | `/panel/penempatan/kelas` | `Panel\Penempatan::kelas` | `HA-MD-03` | Halaman (`?asal=&tujuan=`) |
| HAL-MD-12 | POST | `/panel/penempatan/kelas` | `Panel\Penempatan::simpanKelas` | `HA-MD-03` | 303 → `/panel/penempatan`; konfirmasi bersyarat (RT-07) |
| HAL-MD-13 | GET | `/panel/penempatan/import` | `Panel\ImportPenempatan::index` | `HA-MD-03` | Halaman |
| HAL-MD-13 | GET | `/panel/penempatan/import/daftar` | `Panel\ImportPenempatan::unduhDaftar` | `HA-MD-03` | Berkas XLSX (`?tahun_ajaran=`) |
| HAL-MD-13 | POST | `/panel/penempatan/import` | `Panel\ImportPenempatan::unggah` | `HA-MD-03` | 303 → pratinjau |
| HAL-MD-13 | GET | `/panel/penempatan/import/{token}` | `Panel\ImportPenempatan::pratinjau` | `HA-MD-03` | Halaman (`?tab=dilewati` atau `?tab=gagal`) |
| HAL-MD-13 | GET | `/panel/penempatan/import/{token}/gagal.csv` | `Panel\ImportPenempatan::unduhGagal` | `HA-MD-03` | Berkas CSV |
| HAL-MD-13 | POST | `/panel/penempatan/import/{token}/simpan` | `Panel\ImportPenempatan::simpan` | `HA-MD-03` | 303 → `/panel/penempatan` |
| HAL-MD-13 | DELETE | `/panel/penempatan/import/{token}` | `Panel\ImportPenempatan::batal` | `HA-MD-03` | 303 → `/panel/penempatan/import` |
| HAL-MD-14 | GET | `/panel/siswa/import` | `Panel\ImportSiswa::index` | `HA-MD-04` | Halaman |
| HAL-MD-14 | GET | `/panel/siswa/import/template` | `Panel\ImportSiswa::unduhTemplate` | `HA-MD-04` | Berkas XLSX (`?tahun_ajaran=`) |
| HAL-MD-14 | POST | `/panel/siswa/import` | `Panel\ImportSiswa::unggah` | `HA-MD-04` | 303 → pratinjau |
| HAL-MD-14 | GET | `/panel/siswa/import/{token}` | `Panel\ImportSiswa::pratinjau` | `HA-MD-04` | Halaman (`?tab=gagal`) |
| HAL-MD-14 | GET | `/panel/siswa/import/{token}/gagal.csv` | `Panel\ImportSiswa::unduhGagal` | `HA-MD-04` | Berkas CSV |
| HAL-MD-14 | POST | `/panel/siswa/import/{token}/simpan` | `Panel\ImportSiswa::simpan` | `HA-MD-04` | 303 → `/panel/siswa` |
| HAL-MD-14 | DELETE | `/panel/siswa/import/{token}` | `Panel\ImportSiswa::batal` | `HA-MD-04` | 303 → `/panel/siswa/import` |
| HAL-MD-15 | GET | `/panel/siswa/foto-massal` | `Panel\FotoMassal::index` | `HA-MD-08` | Halaman |
| HAL-MD-15 | POST | `/panel/siswa/foto-massal` | `Panel\FotoMassal::unggah` | `HA-MD-08` | 303 → `/panel/siswa/foto-massal/{token}` |
| HAL-MD-15 | GET | `/panel/siswa/foto-massal/{token}` | `Panel\FotoMassal::lihat` | `HA-MD-08` | Halaman pratinjau, kemajuan, atau hasil |
| HAL-MD-15 | POST | `/panel/siswa/foto-massal/{token}/proses` | `Panel\FotoMassal::proses` | `HA-MD-08` | JSON (`10` EP-MD-02), atau 303 → halaman token tanpa JavaScript |
| HAL-MD-15 | GET | `/panel/siswa/foto-massal/{token}/hasil.csv` | `Panel\FotoMassal::unduhHasil` | `HA-MD-08` | Berkas CSV |
| HAL-MD-15 | DELETE | `/panel/siswa/foto-massal/{token}` | `Panel\FotoMassal::batal` | `HA-MD-08` | 303 → `/panel/siswa/foto-massal` |
| HAL-MD-16 | GET | `/panel/atribut-siswa` | `Panel\AtributSiswa::index` | `HA-MD-11` | Halaman |
| HAL-MD-16 | GET | `/panel/atribut-siswa/tambah` | `Panel\AtributSiswa::tambah` | `HA-MD-11` | Halaman |
| HAL-MD-16 | POST | `/panel/atribut-siswa` | `Panel\AtributSiswa::simpan` | `HA-MD-11` | 303 → daftar |
| HAL-MD-16 | GET | `/panel/atribut-siswa/{id}/ubah` | `Panel\AtributSiswa::ubah` | `HA-MD-11` | Halaman |
| HAL-MD-16 | PATCH | `/panel/atribut-siswa/{id}` | `Panel\AtributSiswa::perbarui` | `HA-MD-11` | 303 → daftar |
| HAL-MD-16 | POST | `/panel/atribut-siswa/{id}/sembunyikan` | `Panel\AtributSiswa::sembunyikan` | `HA-MD-11` | 303 → daftar |
| HAL-MD-16 | POST | `/panel/atribut-siswa/{id}/tampilkan` | `Panel\AtributSiswa::tampilkan` | `HA-MD-11` | 303 → daftar |
| HAL-MD-16 | GET | `/panel/atribut-siswa/{id}/hapus` | `Panel\AtributSiswa::formHapus` | `HA-MD-11` | Halaman konfirmasi |
| HAL-MD-16 | DELETE | `/panel/atribut-siswa/{id}` | `Panel\AtributSiswa::hapus` | `HA-MD-11` | 303 → daftar |
| HAL-MD-17 | GET | `/panel/siswa/{id}/log` | `Panel\LogDataSiswa::index` | `HA-MD-10` | Halaman |

Route dengan kata tetap, misalnya `/panel/siswa/import` dan `/panel/siswa/cari`, tidak tertangkap route `/panel/siswa/{id}`, karena `{id}` memakai placeholder `(:num)`.

Rincian halaman:

- **HAL-MD-01 — Identitas sekolah** (FS-MD-01). Formulir nama resmi, alamat, dan logo, dengan pratinjau logo (`08` UI-28) dan pilihan "hapus logo". Logo yang tidak valid ditolak, tetapi isian lain tetap tersimpan, dengan pesan (E2). Isian kota/kabupaten ditambahkan di R2 (`06` §6.1).
- **HAL-MD-02 — Tahun ajaran** (FS-MD-02). Daftar dengan tanda tahun ajaran aktif. Formulir tambah dan ubah memuat tanggal tahun ajaran serta tanggal kedua semester. Perubahan tanggal semester yang mengeluarkan tanggal dari semester memakai konfirmasi bersyarat yang menyebut tanggal itu (RT-07, FS-MD-02 butir 3). Konfirmasi aktifkan menjelaskan akibat di FS-MD-02 butir 2. Tombol hapus hanya tampil untuk tahun ajaran tanpa kelas (E4).
- **HAL-MD-03 — Kelas dan wali kelas** (FS-MD-03). Daftar per tahun ajaran (bawaan tahun ajaran aktif): nama, tingkat, wali kelas, jumlah siswa, serta tanda tanpa wali kelas atau wali kelas nonaktif. Wali kelas dipilih dari akun staf aktif. Isian tingkat terkunci setelah kelas memiliki penempatan (E4). Tombol hapus hanya tampil untuk kelas tanpa penempatan (E3).
- **HAL-MD-04 — Data siswa** (FS-MD-04 butir 7). Saringan: `cari`, `kelas`, `status` (`aktif`, `akan_aktif`, `nonaktif`, atau `semua`; bawaan `aktif`), `tanpa_wa`, `tanpa_foto`, dan `tanpa_kelas` untuk siswa aktif tanpa penempatan hari ini (FS-PRS-05 E1). Kolom: foto kecil, nama, NISN, kelas, status, dan tanda. Wali kelas hanya melihat siswa kelasnya. Tombol tambah, import, dan foto massal hanya tampil bagi admin. Keadaan kosong mengikuti E6.
- **HAL-MD-05 — Tambah siswa** (FS-MD-04 butir 1). Isian di FS-MD-04, termasuk kelas dan tanggal mulai (bawaan hari ini) serta atribut tambahan yang aktif.
- **HAL-MD-06 — Profil siswa** (FS-MD-04 butir 6, `08` §4.6). Halaman memakai tab:
  - "Profil" (`/panel/siswa/{id}`): foto 150×200 px, data siswa, nomor WA dengan format `08` UI-54, atribut tambahan yang aktif, status siswa dan riwayat masa aktif, riwayat penempatan, serta status akun bagi pemegang `HA-AKN-06`;
  - "Kehadiran" (HAL-LAP-06), bagi pemegang `HA-LAP-04`;
  - "Log data" (HAL-MD-17), bagi pemegang `HA-MD-10`.

  Tombol sesuai hak: ubah data, nonaktifkan atau aktifkan kembali, dan pindah kelas (`HA-MD-03`); ubah nomor WA (`HA-MD-06`); ganti foto (`HA-MD-07`); reset password (`HA-AKN-04`); presensi manual (`HA-PRS-03`) dan input izin (`HA-IZN-02`) dengan siswa terisi; serta tautan ke daftar izin siswa (`HA-IZN-04`).
- **HAL-MD-07 — Ubah data siswa** (FS-MD-04 butir 2). Isian sama dengan tambah, tanpa kelas dan tanggal mulai. Perubahan NISN menampilkan peringatan bahwa QR kartu harus berisi NISN baru (C-04).
- **HAL-MD-08 — Nomor WA** (FS-MD-04 butir 3). Satu isian. Isian kosong menghapus nomor. Halaman ini terpisah dari ubah data, karena wali kelas boleh mengubah nomor WA tetapi tidak data lain.
- **HAL-MD-09 — Foto siswa** (FS-MD-07). Formulir unggah dengan pratinjau. `GET /panel/siswa/{id}/foto` mengirim foto standar, atau foto kecil dengan `?ukuran=kecil` (§13). Halaman yang menampilkan siswa tanpa foto memakai gambar pengganti tanpa meminta berkas (`08` UI-24).
- **HAL-MD-10 — Nonaktifkan dan aktifkan kembali** (FS-MD-04 butir 4 dan 5). Formulir nonaktifkan berisi tanggal terakhir aktif (bawaan hari ini), alasan, dan keterangan bila alasan "lainnya". Alasan "salah input", atau periode yang belum dimulai, membatalkan periode itu tanpa tanggal terakhir aktif (`06` §6.6 aturan 4), dan formulirnya menyembunyikan isian tanggal. Formulir aktifkan berisi tanggal mulai aktif dan kelas.
- **HAL-MD-11 — Pindah kelas** (FS-MD-05 butir 2). Formulir kelas tujuan dan tanggal mulai (bawaan hari ini).
- **HAL-MD-12 — Penempatan per kelas** (FS-MD-05 butir 3, `13` §7). `GET /panel/penempatan` menampilkan dua cara: per kelas dan lewat file. Cara per kelas memilih kelas asal dan kelas tujuan, lalu menampilkan siswa aktif di kelas asal dalam keadaan tercentang, dengan tanggal mulai (bawaan tanggal mulai tahun ajaran kelas tujuan). Penyimpanan memakai konfirmasi bersyarat yang menyebut jumlah siswa (RT-07).
- **HAL-MD-13 — Import penempatan** (FS-MD-05 butir 6, `13` IM-02, IM-08 s.d. IM-11). Halaman memuat unduhan daftar siswa aktif per tahun ajaran, dan formulir unggah dengan tahun ajaran tujuan dan tanggal mulai. Pratinjau memakai tab baris valid, dilewati, dan gagal, beserta jumlah siswa per kelas tujuan.
- **HAL-MD-14 — Import siswa** (FS-MD-06, `13` IM-01, IM-04 s.d. IM-07). Halaman memuat unduhan template untuk tahun ajaran tujuan, formulir unggah (file, tahun ajaran tujuan, dan tanggal mulai), dan petunjuk kolom. Unggahan menyimpan file dan hasil validasi di `tmp/<token>/`, lalu mengalihkan ke pratinjau. Pratinjau memakai tab baris valid dan baris gagal, menampilkan jumlah baris tanpa nomor WA, dan menyediakan unduhan CSV baris gagal. Tombol simpan tidak tersedia bila semua baris gagal (AC-MD-06-04). Setelah simpan, pesan kilat menyebut jumlah siswa yang dibuat dan baris yang dilewati (FS-MD-06 butir 5).
- **HAL-MD-15 — Foto massal** (FS-MD-08, `07` ARS-54, `13` §8). Unggahan berupa satu ZIP atau beberapa file. Pratinjau berbentuk tabel: nama file, siswa, dan keterangan (cocok, mengganti foto lama, tidak cocok, ganda, atau tidak valid). Setelah "Simpan foto", halaman token menampilkan kemajuan. Dengan JavaScript, modul halaman memanggil `POST …/proses` berulang sampai selesai (`10` EP-MD-02). Tanpa JavaScript, admin menekan "Lanjutkan" setelah setiap potongan. Setelah selesai, halaman menampilkan ringkasan dan unduhan CSV hasil (FS-MD-08 butir 5).
- **HAL-MD-16 — Atribut tambahan** (FS-MD-09). Daftar sesuai urutan, dengan tanda atribut tersembunyi. Formulir berisi label, kode (hanya saat tambah), tipe (terkunci bila sudah ada nilai), pilihan, wajib, dan urutan. Tombol hapus hanya tampil bila belum ada nilai (E3).
- **HAL-MD-17 — Log data siswa** (FS-MD-04 butir 8, `06` §12.2). Daftar perubahan satu siswa dari yang terbaru: waktu, pelaku, jenis (§14), data lama, data baru, dan alasan.

## 7. Kiosk dan stasiun (KIO)

| Halaman | Metode | Alamat | Controller::method | Hak | Hasil |
|---|---|---|---|---|---|
| HAL-KIO-01 | GET | `/kiosk` | `Kiosk\Halaman::index` | `HA-KIO-01` | Kerangka kiosk |
| HAL-KIO-01 | GET, POST | `/kiosk/api/v1/…` | `Kiosk\Api\V1\…` | `HA-KIO-01` | JSON (`10` EP-KIO-01 s.d. EP-KIO-04) |
| HAL-KIO-02 | GET | `/panel/stasiun` | `Panel\Stasiun::index` | `HA-KIO-02`, `HA-AKN-03` | Halaman |
| HAL-KIO-02 | GET | `/panel/stasiun/fragmen` | `Panel\Stasiun::fragmen` | `HA-KIO-02`, `HA-AKN-03` | Fragmen (`10` EP-KIO-05) |
| HAL-KIO-02 | GET | `/panel/stasiun/tambah` | `Panel\Stasiun::tambah` | `HA-AKN-03` | Halaman |
| HAL-KIO-02 | POST | `/panel/stasiun` | `Panel\Stasiun::simpan` | `HA-AKN-03` | 200 password (RT-09) |
| HAL-KIO-02 | GET | `/panel/stasiun/{id}/ubah` | `Panel\Stasiun::ubah` | `HA-AKN-03` | Halaman |
| HAL-KIO-02 | PATCH | `/panel/stasiun/{id}` | `Panel\Stasiun::perbarui` | `HA-AKN-03` | 303 → `/panel/stasiun` |
| HAL-KIO-02 | GET | `/panel/stasiun/{id}/ganti-kredensial` | `Panel\Stasiun::formGantiKredensial` | `HA-AKN-03` | Halaman konfirmasi |
| HAL-KIO-02 | POST | `/panel/stasiun/{id}/ganti-kredensial` | `Panel\Stasiun::gantiKredensial` | `HA-AKN-03` | 200 password baru (RT-09) |
| HAL-KIO-02 | GET | `/panel/stasiun/{id}/nonaktifkan` | `Panel\Stasiun::formNonaktifkan` | `HA-AKN-03` | Halaman konfirmasi |
| HAL-KIO-02 | POST | `/panel/stasiun/{id}/nonaktifkan` | `Panel\Stasiun::nonaktifkan` | `HA-AKN-03` | 303 → `/panel/stasiun` |
| HAL-KIO-02 | POST | `/panel/stasiun/{id}/aktifkan` | `Panel\Stasiun::aktifkan` | `HA-AKN-03` | 303 → `/panel/stasiun` |
| HAL-KIO-03 | GET | `/panel/scan-bertanda` | `Panel\ScanBertanda::index` | `HA-KIO-03` | Halaman |
| HAL-KIO-03 | POST | `/panel/scan-bertanda/tinjau` | `Panel\ScanBertanda::tinjauBanyak` | `HA-KIO-03` | 303 → daftar |
| HAL-KIO-03 | GET | `/panel/scan-bertanda/{id}` | `Panel\ScanBertanda::lihat` | `HA-KIO-03` | Halaman |
| HAL-KIO-03 | POST | `/panel/scan-bertanda/{id}/tinjau` | `Panel\ScanBertanda::tinjau` | `HA-KIO-03` | 303 → daftar |

Rincian halaman:

- **HAL-KIO-01 — Kiosk** (FS-KIO-01 s.d. FS-KIO-03, `07` ARS-20, `08` §7). `GET /kiosk` mengirim kerangka yang sama untuk semua stasiun dan tidak memuat data siswa, sehingga aman disimpan Service Worker (`07` ARS-22). Semua data dimuat lewat API (`10`). Bila login stasiun berakhir, navigasi lewat jaringan dialihkan ke `/login`. Bila kerangka diambil dari cache, kiosk mengetahuinya dari jawaban API (ARS-22 butir 6). Berkas statis kiosk ada di tabel di bawah. Folder fisik `public/kiosk/` tidak dibuat (ARS-22 butir 2).
- **HAL-KIO-02 — Status stasiun** (FS-KIO-05, FS-AKN-04). Satu baris per akun stasiun: nama, username (hanya bagi admin), status akun, kontak terakhir, sinkron terakhir, jumlah belum tersinkron, jumlah scan galat, selisih jam, waktu data dimuat, jumlah scan diterima hari ini, versi kode kiosk, dan penyimpanan permanen (DECISION, Session 8). Stasiun disorot dengan alasannya (§14) bila memenuhi FS-KIO-05 butir 2, memiliki scan galat, atau melaporkan penyimpanan permanen belum aktif. Versi kode yang berbeda dari versi kiosk terbaru diberi tanda "Versi lama" tanpa disorot. Daftar diperbarui setiap 30 detik lewat fragmen. Tindakan kelola akun hanya tampil bagi pemegang `HA-AKN-03`. Konfirmasi nonaktifkan menampilkan status stasiun dan peringatan scan belum tersinkron (FS-AKN-04 butir 3). Bila belum ada stasiun, halaman menampilkan "Belum ada stasiun scan." dengan tautan tambah bagi admin (FS-KIO-05 E1).
- **HAL-KIO-03 — Scan bertanda** (FS-KIO-06). Daftar scan yang perlu tinjauan dan belum ditinjau, dikelompokkan per stasiun dan alasan, dengan saringan `tanggal` (bawaan hari ini), `stasiun`, dan `alasan` (kode di §14). Guru piket hanya dapat membuka hari ini (E3). Setiap baris memuat foto kecil, nama, kelas, jenis, jam scan, alasan, dan apakah scan dipakai selama belum ditinjau, dengan kotak centang untuk tinjauan beberapa scan sekaligus (FS-KIO-06 butir 5). Detail memuat foto 240×320 px (`08` UI-23), jam scan, jam laptop asli, selisih jam, waktu diterima, stasiun, dan alasan, beserta formulir keputusan. Catatan wajib saat menolak (E4).

Berkas statis kiosk (HAL-KIO-01), disajikan Nginx dari `public/` dan disimpan Service Worker (`07` ARS-22):

| Alamat | Isi |
|---|---|
| `/sw-kiosk.js` | Service Worker, didaftarkan dengan cakupan `/kiosk`. |
| `/kiosk-pemindai.js` | Web Worker pembaca QR. |
| `/aset/kiosk/…` | Modul JavaScript, CSS, dan `manifest.webmanifest` dengan `scope` dan `start_url` `/kiosk`. |
| `/aset/vendor/zxing-wasm/3.1.4/…` | Pembaca QR (`07` ARS-10). |
| `/aset/ikon/ikon.svg`, `/aset/vendor/plus-jakarta-sans/2.071/…` | Ikon dan font (`08` UI-14, UI-20). |

## 8. Presensi dan aturan (PRS)

| Halaman | Metode | Alamat | Controller::method | Hak | Hasil |
|---|---|---|---|---|---|
| HAL-PRS-01 | GET | `/panel/pola-mingguan` | `Panel\PolaMingguan::index` | `HA-PRS-01` | Halaman |
| HAL-PRS-01 | GET | `/panel/pola-mingguan/tambah` | `Panel\PolaMingguan::tambah` | `HA-PRS-01` | Halaman |
| HAL-PRS-01 | POST | `/panel/pola-mingguan` | `Panel\PolaMingguan::simpan` | `HA-PRS-01` | 303 → daftar |
| HAL-PRS-01 | GET | `/panel/pola-mingguan/{id}` | `Panel\PolaMingguan::lihat` | `HA-PRS-01` | Halaman |
| HAL-PRS-01 | GET | `/panel/pola-mingguan/{id}/ubah` | `Panel\PolaMingguan::ubah` | `HA-PRS-01` | Halaman |
| HAL-PRS-01 | PATCH | `/panel/pola-mingguan/{id}` | `Panel\PolaMingguan::perbarui` | `HA-PRS-01` | 303 → daftar |
| HAL-PRS-01 | GET | `/panel/pola-mingguan/{id}/hapus` | `Panel\PolaMingguan::formHapus` | `HA-PRS-01` | Halaman konfirmasi |
| HAL-PRS-01 | DELETE | `/panel/pola-mingguan/{id}` | `Panel\PolaMingguan::hapus` | `HA-PRS-01` | 303 → daftar |
| HAL-PRS-02 | GET | `/panel/jadwal-khusus` | `Panel\JadwalKhusus::index` | `HA-PRS-01` | Halaman |
| HAL-PRS-02 | GET | `/panel/jadwal-khusus/tambah` | `Panel\JadwalKhusus::tambah` | `HA-PRS-01` | Halaman |
| HAL-PRS-02 | POST | `/panel/jadwal-khusus` | `Panel\JadwalKhusus::simpan` | `HA-PRS-01` | 303 → daftar |
| HAL-PRS-02 | GET | `/panel/jadwal-khusus/{id}/ubah` | `Panel\JadwalKhusus::ubah` | `HA-PRS-01` | Halaman |
| HAL-PRS-02 | PATCH | `/panel/jadwal-khusus/{id}` | `Panel\JadwalKhusus::perbarui` | `HA-PRS-01` | 303 → daftar |
| HAL-PRS-02 | GET | `/panel/jadwal-khusus/{id}/hapus` | `Panel\JadwalKhusus::formHapus` | `HA-PRS-01` | Halaman konfirmasi |
| HAL-PRS-02 | DELETE | `/panel/jadwal-khusus/{id}` | `Panel\JadwalKhusus::hapus` | `HA-PRS-01` | 303 → daftar |
| HAL-PRS-03 | GET | `/panel/libur` | `Panel\Libur::index` | `HA-PRS-02` | Halaman (`?bulan=&tampilan=`) |
| HAL-PRS-03 | GET | `/panel/libur/tambah` | `Panel\Libur::tambah` | `HA-PRS-02` | Halaman |
| HAL-PRS-03 | POST | `/panel/libur` | `Panel\Libur::simpan` | `HA-PRS-02` | 303 → daftar |
| HAL-PRS-03 | GET | `/panel/libur/{id}/ubah` | `Panel\Libur::ubah` | `HA-PRS-02` | Halaman |
| HAL-PRS-03 | PATCH | `/panel/libur/{id}` | `Panel\Libur::perbarui` | `HA-PRS-02` | 303 → daftar |
| HAL-PRS-03 | GET | `/panel/libur/{id}/hapus` | `Panel\Libur::formHapus` | `HA-PRS-02` | Halaman konfirmasi |
| HAL-PRS-03 | DELETE | `/panel/libur/{id}` | `Panel\Libur::hapus` | `HA-PRS-02` | 303 → daftar |
| HAL-PRS-04 | GET | `/panel/jadwal-hari-ini` | `Panel\JadwalHariIni::index` | `HA-PRS-07` | Halaman |
| HAL-PRS-04 | PUT | `/panel/jadwal-hari-ini` | `Panel\JadwalHariIni::ganti` | `HA-PRS-07` | 303 → `/panel/jadwal-hari-ini` |
| HAL-PRS-04 | DELETE | `/panel/jadwal-hari-ini` | `Panel\JadwalHariIni::hapus` | `HA-PRS-07` | 303 → `/panel/jadwal-hari-ini` |
| HAL-PRS-05 | GET | `/panel/mode-darurat` | `Panel\ModeDarurat::index` | `HA-PRS-08` | Halaman |
| HAL-PRS-05 | GET | `/panel/mode-darurat/aktifkan` | `Panel\ModeDarurat::formAktifkan` | `HA-PRS-08` | Halaman konfirmasi |
| HAL-PRS-05 | POST | `/panel/mode-darurat/aktifkan` | `Panel\ModeDarurat::aktifkan` | `HA-PRS-08` | 303 → `/panel/mode-darurat` |
| HAL-PRS-05 | GET | `/panel/mode-darurat/akhiri` | `Panel\ModeDarurat::formAkhiri` | `HA-PRS-08` | Halaman konfirmasi |
| HAL-PRS-05 | POST | `/panel/mode-darurat/akhiri` | `Panel\ModeDarurat::akhiri` | `HA-PRS-08` | 303 → `/panel/mode-darurat` |
| HAL-PRS-06 | GET | `/panel/mode-darurat/kelas` | `Panel\PresensiDarurat::index` | `HA-PRS-03` | Halaman |
| HAL-PRS-06 | GET | `/panel/mode-darurat/kelas/{id}` | `Panel\PresensiDarurat::lihat` | `HA-PRS-03` | Halaman |
| HAL-PRS-06 | POST | `/panel/mode-darurat/kelas/{id}` | `Panel\PresensiDarurat::simpan` | `HA-PRS-03` | 303 → halaman kelas yang sama |
| HAL-PRS-07 | GET | `/panel/presensi-manual` | `Panel\PresensiManual::index` | `HA-PRS-03` | Halaman (`?cari=` atau `?siswa=&tanggal=&jenis=&kembali=`) |
| HAL-PRS-07 | GET | `/panel/presensi-manual/pratinjau` | `Panel\PresensiManual::pratinjau` | `HA-PRS-03` | Fragmen (`10` EP-PRS-01) |
| HAL-PRS-07 | POST | `/panel/presensi-manual` | `Panel\PresensiManual::simpan` | `HA-PRS-03` | 303 → `kembali` atau formulir siswa yang sama; `periksa=1`: 200 pratinjau |
| HAL-PRS-07 | GET | `/panel/presensi-manual/{id}/batalkan` | `Panel\PresensiManual::formBatalkan` | `HA-PRS-03` | Halaman formulir |
| HAL-PRS-07 | POST | `/panel/presensi-manual/{id}/batalkan` | `Panel\PresensiManual::batalkan` | `HA-PRS-03` | 303 → `kembali` atau formulir siswa |
| HAL-PRS-08 | GET | `/panel/koreksi/tambah` | `Panel\Koreksi::tambah` | `HA-PRS-04` | Halaman (`?siswa=&tanggal=&kembali=`) |
| HAL-PRS-08 | POST | `/panel/koreksi` | `Panel\Koreksi::simpan` | `HA-PRS-04` | 303 → `kembali` atau riwayat siswa |
| HAL-PRS-08 | GET | `/panel/koreksi/{id}/hapus` | `Panel\Koreksi::formHapus` | `HA-PRS-04` | Halaman formulir |
| HAL-PRS-08 | DELETE | `/panel/koreksi/{id}` | `Panel\Koreksi::hapus` | `HA-PRS-04` | 303 → `kembali` atau riwayat siswa |
| HAL-PRS-09 | GET | `/panel/batas-mundur` | `Panel\BatasMundur::index` | `HA-PRS-09` | Halaman |
| HAL-PRS-09 | PUT | `/panel/batas-mundur` | `Panel\BatasMundur::ganti` | `HA-PRS-09` | 303 → `/panel/batas-mundur` |
| HAL-PRS-10 | GET | `/panel/log-presensi` | `Panel\LogPresensi::index` | `HA-PRS-06` | Halaman |
| HAL-PRS-10 | GET | `/panel/log-presensi/{id}` | `Panel\LogPresensi::lihat` | `HA-PRS-06` | Halaman |

Rincian halaman:

- **HAL-PRS-01 — Pola mingguan** (FS-PRS-01). Daftar versi: versi yang sedang berlaku dan versi yang dijadwalkan. Formulir berupa tabel tujuh hari × tujuh isian dengan pratinjau batas terlambat, ditambah tanggal berlaku mulai (hari ini atau tanggal ke depan). Versi baru terisi awal dari versi yang sedang berlaku. Versi yang sudah berlaku hanya dapat dilihat.
- **HAL-PRS-02 — Jadwal khusus** (FS-PRS-02). Daftar berurutan tanggal, dengan saringan `tahun_ajaran` (bawaan aktif). Formulir berisi rentang tanggal, tujuh isian, dan keterangan. Konfirmasi hapus menyebut bahwa status tanggal yang dicakup dihitung ulang. Tanggal di luar semester tetap tersimpan, disertai pesan peringatan (E3).
- **HAL-PRS-03 — Libur** (FS-PRS-03). Tampilan daftar atau kalender bulanan (`tampilan=kalender` dan `bulan=YYYY-MM`, `08` UI-27), dengan warna berbeda untuk libur semua siswa dan libur sebagian. Formulir berisi rentang tanggal, keterangan, dan cakupan: semua siswa, tingkat, atau kelas, dengan kotak centang untuk tingkat dan kelas.
- **HAL-PRS-04 — Jadwal hari ini** (FS-PRS-04). Formulir tujuh isian terisi aturan yang berlaku, dengan aturan saat ini di sampingnya, dan isian alasan. Bila jadwal hari ini sudah diubah, halaman menampilkan alasan dan pengubahnya, serta formulir "Kembalikan jadwal semula" dengan alasan. Bila hari ini bukan hari sekolah, halaman hanya menampilkan keterangan (E2). Alamat ini tidak menerima tanggal, sehingga tanggal lain tidak dapat diubah (E3).
- **HAL-PRS-05 — Mode darurat** (FS-PRS-08). Halaman menampilkan keadaan hari ini dan setiap periode mode darurat hari ini. Konfirmasi aktifkan menjelaskan akibatnya dan mengingatkan bahwa internet putus bukan alasan (BR-DRT-01). Konfirmasi akhiri menampilkan jumlah siswa yang masih belum hadir per kelas (FS-PRS-08 butir 4), dan membawa ID periode yang aktif sebagai penjaga (RT-12). Bila hari ini bukan hari sekolah, kedua tindakan tidak tersedia (E1).
- **HAL-PRS-06 — Presensi per kelas saat darurat** (FS-PRS-09). Hanya tersedia selama mode darurat aktif. Di luar itu, halaman menampilkan bahwa fitur tidak tersedia (E1). Pilihan kelas berisi kelas dalam cakupan beserta jumlah siswa yang belum tercatat. Daftar centang berisi foto kecil, nama, label izin bila ada, kotak hadir, dan tanda terlambat, dengan "centang semua". Setelah simpan, pesan menyebut siswa yang tercatat dan siswa yang dilewati (FS-PRS-09 butir 5).
- **HAL-PRS-07 — Presensi manual** (FS-PRS-06, `08` UI-34). Tanpa `siswa`, halaman menampilkan pencarian siswa (RT-14). Dengan `siswa`, halaman menampilkan:
  - foto 240×320 px, nama, NISN, kelas, status pada tanggal terpilih, presensi yang sudah ada beserta sumbernya, serta peringatan izin atau koreksi (FS-PRS-06 butir 3);
  - formulir tanggal (bawaan hari ini, dengan rentang yang boleh), jenis, jam (bawaan jam sekarang bila tanggalnya hari ini), alasan, dan catatan;
  - pratinjau status, yang tampil langsung lewat fragmen pratinjau, atau setelah tombol "Periksa" tanpa JavaScript (RT-07);
  - presensi manual siswa pada tanggal itu, dengan tombol batalkan.
- **HAL-PRS-08 — Koreksi status** (FS-PRS-07). Dibuka dari daftar presensi kelas, riwayat siswa, atau daftar siswa per status, dengan parameter `siswa`, `tanggal`, dan `kembali`. Tanpa `siswa` atau `tanggal`, alamat ini mengalihkan ke `/panel/presensi/kelas`. Halaman menampilkan status saat ini beserta sumbernya, koreksi aktif bila ada, dan peringatan izin (FS-PRS-07 butir 3). Pilihan kehadiran hanya Hadir, Terlambat, dan Tidak hadir, dengan tautan ke input izin (E2) dan pengingat "Alasan dapat dibaca siswa." Formulir membawa ID koreksi aktif, atau nilai kosong, sebagai penjaga (RT-12).
- **HAL-PRS-09 — Batas mundur** (FS-PRS-10). Satu isian 0–31 hari, dengan contoh rentang tanggal yang dihasilkan.
- **HAL-PRS-10 — Log perubahan presensi** (FS-PRS-11). Saringan: `tanggal_mulai` dan `tanggal_selesai` (tanggal presensi), `diubah_mulai` dan `diubah_selesai` (waktu perubahan), `kelas`, `siswa`, `jenis` (§14), dan `pelaku`. Bawaannya perubahan 7 hari terakhir. Entri terbaru tampil di atas. Halaman detail menampilkan data lama dan data baru berdampingan. Cakupan mengikuti FS-PRS-11 butir 2. Tidak ada tindakan ubah atau hapus.

## 9. Izin, sakit, dan dispensasi (IZN)

| Halaman | Metode | Alamat | Controller::method | Hak | Hasil |
|---|---|---|---|---|---|
| HAL-IZN-01 | GET | `/portal/izin` | `Portal\Izin::index` | `HA-IZN-04` | Halaman |
| HAL-IZN-02 | GET | `/portal/izin/tambah` | `Portal\Izin::tambah` | `HA-IZN-01` | Halaman |
| HAL-IZN-02 | GET | `/portal/izin/hari-sekolah` | `Portal\Izin::hariSekolah` | `HA-IZN-01` | Fragmen (`10` EP-IZN-01) |
| HAL-IZN-02 | POST | `/portal/izin` | `Portal\Izin::simpan` | `HA-IZN-01` | Tanpa `konfirmasi`: 200 halaman periksa (RT-07). Dengan `konfirmasi=1`: 303 → detail. |
| HAL-IZN-03 | GET | `/portal/izin/{id}` | `Portal\Izin::lihat` | `HA-IZN-04` | Halaman |
| HAL-IZN-03 | GET | `/portal/izin/{id}/batalkan` | `Portal\Izin::formBatalkan` | `HA-IZN-01` | Halaman konfirmasi |
| HAL-IZN-03 | POST | `/portal/izin/{id}/batalkan` | `Portal\Izin::batalkan` | `HA-IZN-01` | 303 → detail |
| HAL-IZN-04 | GET | `/panel/izin/menunggu` | `Panel\Izin::menunggu` | `HA-IZN-03` | Halaman |
| HAL-IZN-05 | GET | `/panel/izin` | `Panel\Izin::index` | `HA-IZN-04` | Halaman |
| HAL-IZN-06 | GET | `/panel/izin/{id}` | `Panel\Izin::lihat` | `HA-IZN-04` | Halaman |
| HAL-IZN-06 | POST | `/panel/izin/{id}/verifikasi` | `Panel\Izin::verifikasi` | `HA-IZN-03` | 303 → `/panel/izin/menunggu` |
| HAL-IZN-07 | GET | `/panel/izin/{id}/ubah-keputusan` | `Panel\Izin::formUbahKeputusan` | `HA-IZN-06` | Halaman formulir |
| HAL-IZN-07 | POST | `/panel/izin/{id}/ubah-keputusan` | `Panel\Izin::ubahKeputusan` | `HA-IZN-06` | 303 → `kembali` atau detail |
| HAL-IZN-08 | GET | `/panel/izin/tambah` | `Panel\Izin::tambah` | `HA-IZN-02` | Halaman (`?cari=` atau `?siswa=&tanggal=&kembali=`) |
| HAL-IZN-08 | POST | `/panel/izin` | `Panel\Izin::simpan` | `HA-IZN-02` | 303 → `kembali` atau detail |
| HAL-IZN-09 | GET | `/panel/dispensasi-massal` | `Panel\DispensasiMassal::index` | `HA-IZN-04`, `HA-IZN-02` | Halaman |
| HAL-IZN-09 | GET | `/panel/dispensasi-massal/tambah` | `Panel\DispensasiMassal::tambah` | `HA-IZN-02` | Halaman |
| HAL-IZN-09 | POST | `/panel/dispensasi-massal` | `Panel\DispensasiMassal::simpan` | `HA-IZN-02` | Tanpa `konfirmasi`: 200 pratinjau (RT-07). Dengan `konfirmasi=1`: 303 → detail kelompok. |
| HAL-IZN-09 | GET | `/panel/dispensasi-massal/{id}` | `Panel\DispensasiMassal::lihat` | `HA-IZN-04`, `HA-IZN-02` | Halaman |
| HAL-IZN-09 | GET | `/panel/dispensasi-massal/{id}/ubah-keputusan` | `Panel\DispensasiMassal::formUbahKeputusan` | `HA-IZN-06` | Halaman formulir |
| HAL-IZN-09 | POST | `/panel/dispensasi-massal/{id}/ubah-keputusan` | `Panel\DispensasiMassal::ubahKeputusan` | `HA-IZN-06` | 303 → detail kelompok; konfirmasi bersyarat (RT-07) |

Rincian halaman:

- **HAL-IZN-01 — Daftar izin siswa** (FS-IZN-01 butir 4). Pengajuan dan data izin milik siswa, termasuk yang diinput staf, terbaru di atas: jenis, rentang, status, dan keputusan terbaru tanpa nama staf. Bila kosong, halaman menampilkan tombol "Ajukan izin/sakit" (E6).
- **HAL-IZN-02 — Ajukan izin/sakit** (FS-IZN-01, `08` UI-37). Formulir berisi pilihan "Satu hari" atau "Beberapa hari", jenis Izin atau Sakit, tanggal, keterangan, dan paling banyak 3 lampiran yang dapat diambil langsung dari kamera ponsel.
  - Dengan JavaScript, hari sekolah terdampak tampil di bawah tanggal setiap kali tanggal diubah (FS-IZN-01 butir 1), dan formulir dikirim dengan `konfirmasi=1`.
  - Tanpa JavaScript, kiriman pertama menampilkan halaman periksa yang memuat hari sekolah terdampak dan nama lampiran (RT-07).
- **HAL-IZN-03 — Detail izin siswa** (FS-IZN-01, FS-LAP-04 butir 5). Status, keputusan terbaru, catatan verifikasi, lampiran, dan riwayat keputusan tanpa nama staf (§14). Tombol batalkan hanya tampil selama status menunggu (FS-IZN-01 butir 5).
- **HAL-IZN-04 — Pengajuan menunggu** (FS-IZN-04 butir 1). Pengajuan menunggu dalam cakupan, yang paling lama di atas: siswa, kelas, jenis, rentang, jumlah hari sekolah terdampak, waktu diajukan, dan tanda lampiran. Bila kosong, halaman menampilkan "Tidak ada pengajuan yang menunggu." (E1).
- **HAL-IZN-05 — Daftar izin** (FS-IZN-06). Saringan: `status`, `jenis`, `mulai` dan `selesai`, `kelas`, `siswa`, `sumber`, dan `kelompok`, ditambah saringan cepat `cepat` bernilai `menunggu`, `hari_ini`, atau `minggu_ini` (FS-IZN-06 catatan). Bawaannya data yang rentangnya mencakup hari ini atau tanggal ke depan.
- **HAL-IZN-06 — Detail izin dan verifikasi** (FS-IZN-04, FS-IZN-06). Keterangan, lampiran yang tampil langsung bila berupa gambar atau PDF, riwayat keputusan dengan nama staf, dan status presensi siswa pada setiap hari sekolah terdampak (FS-IZN-04 butir 3). Selama status menunggu, pemegang `HA-IZN-03` melihat formulir verifikasi berisi keputusan dan catatan, dengan pengingat "Catatan dapat dibaca siswa." Penyimpanan memeriksa bahwa status masih menunggu (`07` ARS-41), dan keputusan yang kalah cepat mengikuti FS-IZN-04 E2 dan E3. Pimpinan melihat halaman ini tanpa tombol tindakan (AC-IZN-06-01).
- **HAL-IZN-07 — Ubah keputusan** (FS-IZN-05). Pilihan tindakan mengikuti status data: batalkan atau perpendek untuk data disetujui, dan setujui untuk data ditolak. Isian rentang baru untuk perpendek, dan alasan dengan pengingat "Alasan dapat dibaca siswa." Formulir menampilkan tanggal yang boleh diubah menurut batas mundur (FS-IZN-05 butir 2). Untuk data dari dispensasi massal, halaman menautkan ubah keputusan per kelompok bila semua siswa kelompok berada dalam cakupan pengguna.
- **HAL-IZN-08 — Input izin oleh staf** (FS-IZN-02). Tanpa `siswa`, halaman menampilkan pencarian (RT-14). Formulir berisi jenis Izin, Sakit, atau Dispensasi, tanggal mulai dan selesai (bawaan dari parameter `tanggal`, atau hari ini), keterangan, dan paling banyak 3 lampiran. Bila bentrok dengan pengajuan menunggu, pesan galat menautkan pengajuan itu (FS-IZN-02 butir 4).
- **HAL-IZN-09 — Dispensasi massal** (FS-IZN-03, FS-IZN-05 butir 5).
  - Daftar berisi kelompok dispensasi dalam cakupan, terbaru di atas: keterangan, rentang, jumlah dibuat, jumlah dilewati, dan pembuat.
  - Formulir tambah memilih siswa dengan salah satu cara: siswa terpilih (pencarian pilih banyak, RT-14 butir 5), satu kelas, atau satu tingkat. Wali kelas hanya melihat cara yang sesuai cakupannya (AC-IZN-03-03). Formulir juga berisi rentang tanggal, keterangan, dan paling banyak 3 lampiran bersama.
  - Kiriman pertama menampilkan pratinjau: siswa yang akan dicatat, siswa yang bentrok beserta data yang sudah ada, dan siswa tanpa hari sekolah (RT-07).
  - Detail kelompok memuat laporan siswa yang tersimpan dan yang dilewati beserta alasannya (FS-IZN-03 butir 3), lampiran bersama, dan tautan ke setiap data izin.
  - Ubah keputusan per kelompok berisi tindakan batalkan atau perpendek, rentang baru, dan satu alasan, lalu konfirmasi yang menyebut jumlah data terdampak. Tindakan ini hanya tersedia bila semua siswa kelompok berada dalam cakupan pengguna (FS-IZN-05 E4).

Lampiran dibuka lewat `/panel/lampiran/{id}` dan `/portal/lampiran/{id}` (§13).

## 10. Dashboard dan laporan (LAP)

| Halaman | Metode | Alamat | Controller::method | Hak | Hasil |
|---|---|---|---|---|---|
| HAL-LAP-01 | GET | `/panel` | `Panel\Dashboard::index` | `HA-LAP-01` | Halaman |
| HAL-LAP-01 | GET | `/panel/dashboard/fragmen` | `Panel\Dashboard::fragmen` | `HA-LAP-01` | Fragmen (`10` EP-LAP-01) |
| HAL-LAP-02 | GET | `/panel/dashboard/siswa` | `Panel\Dashboard::siswa` | `HA-LAP-02` | Halaman (`?status=&kelas=`) |
| HAL-LAP-03 | GET | `/panel/kelas-saya` | `Panel\PresensiRombel::kelasSaya` | `HA-LAP-02` | 303 → daftar presensi kelasnya, atau halaman pilihan kelas |
| HAL-LAP-04 | GET | `/panel/presensi/kelas` | `Panel\PresensiRombel::index` | `HA-LAP-02`, `HA-LAP-03` | Halaman |
| HAL-LAP-04 | GET | `/panel/presensi/kelas/{id}` | `Panel\PresensiRombel::lihat` | `HA-LAP-02`, `HA-LAP-03` | Halaman (`?tanggal=`) |
| HAL-LAP-05 | GET | `/panel/laporan/rekap-kelas` | `Panel\RekapRombel::index` | `HA-LAP-03` | Halaman |
| HAL-LAP-06 | GET | `/panel/laporan/riwayat-siswa` | `Panel\RiwayatSiswa::index` | `HA-LAP-04` | Halaman pencarian |
| HAL-LAP-06 | GET | `/panel/siswa/{id}/kehadiran` | `Panel\RiwayatSiswa::lihat` | `HA-LAP-04` | Halaman |
| HAL-LAP-07 | GET | `/portal` | `Portal\Riwayat::index` | `HA-LAP-04` | Halaman (`?bulan=`) |

Route R2 (kerangka, RT-22). Hak export mengikuti `13` §4 dan IE-05.

| Halaman | Metode | Alamat | Controller::method | Hak | Hasil |
|---|---|---|---|---|---|
| HAL-LAP-04 | GET | `/panel/presensi/kelas/{id}/ekspor` | `Panel\Ekspor::presensiRombel` | `HA-LAP-02`, `HA-LAP-03` + `HA-LAP-05` | Berkas XLSX atau PDF, LP-04 (`?tanggal=&format=`) |
| HAL-LAP-05 | GET | `/panel/laporan/rekap-kelas/ekspor` | `Panel\Ekspor::rekapRombel` | `HA-LAP-03` + `HA-LAP-05` | Berkas XLSX, CSV, atau PDF, LP-01 |
| HAL-LAP-06 | GET | `/panel/siswa/{id}/kehadiran/ekspor` | `Panel\Ekspor::riwayatSiswa` | `HA-LAP-04` + `HA-LAP-05` | Berkas XLSX atau PDF, LP-05 |
| HAL-MD-04 | GET | `/panel/siswa/ekspor` | `Panel\Ekspor::dataSiswa` | `HA-MD-03` | Berkas XLSX atau CSV, LP-06 |
| HAL-PRS-10 | GET | `/panel/log-presensi/ekspor` | `Panel\Ekspor::logPresensi` | `HA-PRS-06` + `HA-LAP-05` | Berkas CSV, LP-07 |
| HAL-LAP-08 | GET | `/panel/laporan/rekap-sekolah` | `Panel\RekapSekolah::index` | `HA-LAP-05` | Halaman, LP-02 |
| HAL-LAP-08 | GET | `/panel/laporan/rekap-sekolah/ekspor` | `Panel\Ekspor::rekapSekolah` | `HA-LAP-05` | Berkas XLSX, CSV, atau PDF |
| HAL-LAP-09 | GET | `/panel/laporan/rekap-rapor` | `Panel\RekapRapor::index` | `HA-LAP-03` + `HA-LAP-05` | Halaman formulir, LP-03 |
| HAL-LAP-09 | GET | `/panel/laporan/rekap-rapor/ekspor` | `Panel\Ekspor::rekapRapor` | `HA-LAP-03` + `HA-LAP-05` | Berkas XLSX, CSV, atau PDF |
| HAL-LAP-10 | GET | `/panel/laporan/flyer` | `Panel\Flyer::index` | `HA-LAP-06` | Halaman (`08` §11) |
| HAL-LAP-10 | GET | `/panel/laporan/flyer/data` | `Panel\Flyer::data` | `HA-LAP-06` | JSON (`10` EP-LAP-02) |

Rincian halaman:

- **HAL-LAP-01 — Dashboard hari ini** (FS-LAP-01, `08` UI-32). Kepala, peringatan sesuai hak, ubin ringkasan, dan tabel per kelas, dengan isi FS-LAP-01.
  - Nama kelas menautkan daftar presensi kelas hari ini bila pengguna memegang `HA-LAP-02` untuk kelas itu.
  - Angka belum hadir, Alpa, dan penanda menautkan HAL-LAP-02 bagi pemegang `HA-LAP-02` dengan cakupan Semua.
  - Peringatan menautkan status stasiun, scan bertanda, pengajuan menunggu, halaman penyiapan bagi admin (UF-01), dan pemeriksaan sistem bagi admin bila cron tidak berjalan lebih dari 5 menit atau ada antrean gagal (`07` ARS-56, ARS-36).
  - Bagian yang berubah diperbarui setiap 30 detik lewat fragmen (`07` ARS-50).
- **HAL-LAP-02 — Siswa per status** (FS-LAP-01 butir 4). Hanya untuk pemegang `HA-LAP-02` dengan cakupan Semua, dan hanya untuk hari ini. Saringan `status` bernilai `belum_hadir`, `alpa`, `hadir`, `terlambat`, `izin`, `sakit`, `dispensasi`, atau `penanda`, dengan bawaan `belum_hadir` sebelum sesi masuk ditutup dan `alpa` setelahnya, serta saringan `kelas`. Setiap baris memuat kelas, foto kecil, nama, NISN, status, presensi masuk, penanda, dan tombol tindakan sesuai hak.
- **HAL-LAP-03 — Kelas saya** (DECISION, Session 8). Menu hanya tampil bagi wali kelas di tahun ajaran aktif. Wali kelas satu kelas dialihkan ke daftar presensi kelasnya hari ini. Wali kelas lebih dari satu kelas melihat halaman pilihan yang hanya berisi kelas yang diampunya.
- **HAL-LAP-04 — Daftar presensi kelas** (FS-LAP-02, `08` UI-33).
  - Halaman indeks menampilkan kelas tahun ajaran aktif dalam cakupan, dikelompokkan per tingkat.
  - Halaman kelas memakai `tanggal` (bawaan hari ini, tidak boleh tanggal ke depan) dengan pemilih tanggal (`08` UI-28). Isi baris dan ringkasan mengikuti FS-LAP-02.
  - Tombol per baris menuju presensi manual, pembatalannya, koreksi, hapus koreksi, input izin, ubah keputusan izin, dan log, sesuai hak dan batas mundur, dengan parameter `kembali` ke halaman ini (RT-10).
  - Saat mode darurat aktif, daftar hari ini menautkan presensi per kelas (FS-LAP-02 butir 4).
- **HAL-LAP-05 — Rekap per kelas** (FS-LAP-03). Saringan: `tahun_ajaran` (bawaan aktif), `kelas`, dan `periode` bernilai `hari_ini`, `minggu_ini`, `bulan_ini`, `semester_ganjil`, `semester_genap`, atau `bebas` dengan `mulai` dan `selesai`. Bawaan periode adalah `bulan_ini` (`08` §2.2). Tanpa `kelas`, halaman menampilkan pilihan kelas dalam cakupan. Kolom persentase dapat diurutkan (`urut=persen`). Nama siswa menautkan riwayatnya bila siswa itu dalam cakupan (FS-LAP-03 butir 6).
- **HAL-LAP-06 — Riwayat siswa** (FS-LAP-04, tampilan staf). Halaman menu berisi pencarian siswa dalam cakupan `HA-LAP-04` (RT-14). Halaman riwayat memakai `periode` bernilai `bulan` dengan `bulan=YYYY-MM`, `semester`, `tahun_ajaran`, atau `bebas` dengan `mulai` dan `selesai`, dengan bawaan bulan berjalan. Isinya mengikuti FS-LAP-04 butir 1 s.d. 4, termasuk tombol tindakan seperti HAL-LAP-04.
- **HAL-LAP-07 — Riwayat kehadiran di portal** (FS-LAP-04 butir 5, `08` UI-36). Halaman awal portal. Tampilan utama memakai pemilih bulan (`bulan=YYYY-MM`, bawaan bulan berjalan), dan parameter `periode` yang sama dengan HAL-LAP-06 tetap diterima. Isinya mengikuti UI-36, tanpa nama staf, nama stasiun, penanda, dan log.
- **HAL-LAP-08 s.d. HAL-LAP-10 (R2).** Rekap semua kelas (LP-02), rekap rapor semester (LP-03), dan flyer kehadiran (LP-08, `08` §11) dirinci menjelang R2. Tombol export tampil di halaman laporan masing-masing dengan pilihan format sesuai `13` §4.

## 11. Notifikasi WhatsApp (WA) — R2

Kerangka route (RT-22). Halaman dirinci menjelang R2 setelah OQ-10 terjawab.

| Halaman | Metode | Alamat | Controller::method | Hak | Hasil |
|---|---|---|---|---|---|
| HAL-WA-01 | GET | `/panel/wa` | `Panel\Wa::index` | `HA-WA-01` | Halaman pengaturan: jenis kejadian aktif, waktu tunda, ambang pengaman, dan keadaan koneksi gateway |
| HAL-WA-01 | PATCH | `/panel/wa` | `Panel\Wa::perbarui` | `HA-WA-01` | 303 → `/panel/wa` |
| HAL-WA-01 | GET | `/panel/wa/template/{jenis}/ubah` | `Panel\WaTemplate::ubah` | `HA-WA-01` | Halaman formulir template |
| HAL-WA-01 | PUT | `/panel/wa/template/{jenis}` | `Panel\WaTemplate::ganti` | `HA-WA-01` | 303 → `/panel/wa` |
| HAL-WA-02 | GET | `/panel/wa/outbox` | `Panel\WaOutbox::index` | `HA-WA-02` | Halaman (`?status=&tanggal=`) |
| HAL-WA-02 | POST | `/panel/wa/outbox/{id}/kirim-ulang` | `Panel\WaOutbox::kirimUlang` | `HA-WA-02` | 303 → outbox |
| HAL-WA-03 | GET | `/panel/wa/penahanan` | `Panel\WaPenahanan::index` | `HA-WA-03` | Halaman |
| HAL-WA-03 | POST | `/panel/wa/penahanan/{id}/lepas` | `Panel\WaPenahanan::lepas` | `HA-WA-03` | 303 → penahanan |
| HAL-WA-03 | POST | `/panel/wa/penahanan/{id}/batalkan` | `Panel\WaPenahanan::batalkan` | `HA-WA-03` | 303 → penahanan |

Kredensial gateway tidak tampil di halaman pengaturan. Tempatnya ditetapkan di Session 9 (`06` §6.1).

## 12. Informasi dan kartu (INF, KRT) — R3

Kerangka route (RT-22). Halaman dirinci menjelang R3. Tambah, ubah, dan hapus mata pelajaran dan pengumuman mengikuti pola RT-06.

| Halaman | Metode | Alamat | Controller::method | Hak | Hasil |
|---|---|---|---|---|---|
| HAL-INF-01 | GET | `/panel/mata-pelajaran` | `Panel\MataPelajaran::index` | `HA-INF-01` | Halaman |
| HAL-INF-01 | GET | `/panel/jadwal-pelajaran/{id}/ubah` | `Panel\JadwalPelajaran::ubah` | `HA-INF-01` | Halaman formulir jadwal satu kelas |
| HAL-INF-01 | PUT | `/panel/jadwal-pelajaran/{id}` | `Panel\JadwalPelajaran::ganti` | `HA-INF-01` | 303 → jadwal kelas |
| HAL-INF-02 | GET | `/panel/jadwal-pelajaran` | `Panel\JadwalPelajaran::index` | `HA-INF-02` | Halaman pilihan kelas |
| HAL-INF-02 | GET | `/panel/jadwal-pelajaran/{id}` | `Panel\JadwalPelajaran::lihat` | `HA-INF-02` | Halaman jadwal satu kelas |
| HAL-INF-02 | GET | `/portal/jadwal` | `Portal\Jadwal::index` | `HA-INF-02` | Halaman jadwal kelas siswa |
| HAL-INF-03 | GET | `/panel/pengumuman/tambah` | `Panel\Pengumuman::tambah` | `HA-INF-03` | Halaman formulir |
| HAL-INF-04 | GET | `/panel/pengumuman` | `Panel\Pengumuman::index` | `HA-INF-04` | Halaman |
| HAL-INF-04 | GET | `/panel/pengumuman/{id}` | `Panel\Pengumuman::lihat` | `HA-INF-04` | Halaman |
| HAL-INF-04 | GET | `/portal/pengumuman` | `Portal\Pengumuman::index` | `HA-INF-04` | Halaman |
| HAL-INF-04 | GET | `/portal/pengumuman/{id}` | `Portal\Pengumuman::lihat` | `HA-INF-04` | Halaman |
| HAL-INF-05 | GET | `/` | `Publik\Beranda::index` | — | Halaman publik; sebelum R3, pengalihan (RT-18) |
| HAL-INF-05 | GET | `/pengumuman/{id}` | `Publik\Pengumuman::lihat` | — | Halaman |
| HAL-KRT-01 | GET | `/panel/kartu` | `Panel\Kartu::index` | `HA-KRT-01` | Halaman pilihan siswa |
| HAL-KRT-01 | POST | `/panel/kartu/cetak` | `Panel\Kartu::cetak` | `HA-KRT-01` | Berkas PDF A4 (`08` UI-63) |

Pada jadwal pelajaran, `{id}` adalah ID rombel. Halaman publik hanya memuat pengumuman bersasaran publik, info sekolah, dan rekap agregat hari ini tanpa data individu (FR-INF-05, AC-05).

## 13. Berkas

Berkas disajikan lewat controller dengan aturan `07` ARS-52 (RT-16). Berkas dari `writable/uploads/` memakai `X-Content-Type-Options: nosniff`, `Content-Security-Policy: sandbox`, dan `Cache-Control: no-store`, kecuali logo.

| Alamat | Isi | Hak | Cara tampil | Halaman |
|---|---|---|---|---|
| `GET /logo?v=<versi>` | Logo sekolah | Tanpa login | Tampil di browser. Boleh disimpan di cache selama 1 hari; nilai `v` berubah setiap logo diganti. | HAL-MD-01 |
| `GET /panel/siswa/{id}/foto` | Foto standar, atau foto kecil dengan `?ukuran=kecil` | `HA-MD-05` sesuai cakupan | Tampil di browser | HAL-MD-09 |
| `GET /portal/foto` | Foto siswa sendiri | `HA-MD-05` (Sendiri) | Tampil di browser | HAL-AKN-08 |
| `GET /kiosk/api/v1/foto/{id}` | Foto kiosk | `HA-KIO-01` | Diambil kiosk (`10` EP-KIO-02) | HAL-KIO-01 |
| `GET /panel/lampiran/{id}` | Lampiran izin atau lampiran bersama kelompok | `HA-IZN-05` sesuai cakupan | Gambar dan PDF tampil di browser; format lain diunduh | HAL-IZN-06, HAL-IZN-09 |
| `GET /portal/lampiran/{id}` | Lampiran milik data izin siswa itu, termasuk lampiran bersama kelompoknya | `HA-IZN-05` (Sendiri) | Sama dengan di atas | HAL-IZN-03 |
| `GET /panel/siswa/import/template` | Template import XLSX (`13` IM-01) | `HA-MD-04` | Diunduh | HAL-MD-14 |
| `GET /panel/siswa/import/{token}/gagal.csv` | Baris gagal import siswa (`13` IM-07) | `HA-MD-04` | Diunduh | HAL-MD-14 |
| `GET /panel/penempatan/import/daftar` | Daftar siswa aktif untuk import penempatan (`13` IM-08) | `HA-MD-03` | Diunduh | HAL-MD-13 |
| `GET /panel/penempatan/import/{token}/gagal.csv` | Baris gagal import penempatan | `HA-MD-03` | Diunduh | HAL-MD-13 |
| `GET /panel/siswa/foto-massal/{token}/hasil.csv` | Ringkasan foto massal | `HA-MD-08` | Diunduh | HAL-MD-15 |
| Alamat `…/ekspor` (R2) | File laporan (`13` §4) | Hak laporan + `HA-LAP-05` | Diunduh | §10 |

Aturan tambahan:

1. Lampiran kelompok dapat dibuka staf yang cakupannya mencakup paling sedikit satu siswa kelompok itu, dan siswa yang termasuk kelompok itu (`06` §10.4 aturan 2).
2. Template, file daftar, dan file hasil dibuat saat diunduh, dan tidak disimpan di `writable/uploads/`.
3. Halaman yang menampilkan siswa tanpa foto memakai gambar pengganti, sehingga tidak meminta berkas foto (`08` UI-24). Permintaan foto siswa yang tidak memiliki foto dijawab 404.
4. Pencatatan pembukaan lampiran mengikuti OQ-17 (Session 9). Cache browser untuk foto kecil ditinjau di Session 9 (`08` §15).

## 14. Label kode di halaman

Label ini melengkapi `08` §9.2 untuk kode yang hanya tampil di halaman admin dan halaman staf tertentu. Polanya sama: huruf awal kapital dan bahasa layar (`08` UI-52). Label ditulis di `app/Config/Label.php` (`08` UI-75). (RECOMMENDATION)

| Kode | Label |
|---|---|
| `log_presensi.jenis` | `presensi_manual_dicatat` Presensi manual dicatat · `presensi_manual_dibatalkan` Presensi manual dibatalkan · `koreksi_disimpan` Koreksi disimpan · `koreksi_diganti` Koreksi diganti · `koreksi_dihapus` Koreksi dihapus · `izin_diinput_staf` Izin diinput staf · `izin_disetujui` Pengajuan disetujui · `izin_ditolak` Pengajuan ditolak · `izin_diubah` Keputusan izin diubah · `scan_ditinjau` Scan bertanda ditinjau · `jadwal_hari_ini_diubah` Jadwal hari ini diubah · `jadwal_hari_ini_dikembalikan` Jadwal hari ini dikembalikan · `darurat_diaktifkan` Mode darurat diaktifkan · `darurat_diakhiri` Mode darurat diakhiri · `pola_mingguan_diubah` Pola mingguan diubah · `jadwal_khusus_diubah` Jadwal khusus diubah · `libur_diubah` Libur diubah · `semester_diubah` Tanggal semester diubah · `batas_mundur_diubah` Batas mundur diubah |
| `log_presensi` pelaku kosong | Sistem. Untuk `darurat_diakhiri`, tampil "Berakhir otomatis". |
| `log_presensi` jenis berakhiran `_diubah` | Kata "diubah" diganti "dibuat" bila data lama kosong, dan "dihapus" bila data baru kosong (`06` §12.1), misalnya "Libur dibuat". |
| `log_data_siswa.jenis` | `siswa_dibuat` Siswa ditambahkan · `data_diubah` Data siswa diubah · `nisn_diubah` NISN diubah · `wa_diubah` Nomor WA diubah · `foto_diganti` Foto diganti · `dinonaktifkan` Dinonaktifkan · `diaktifkan_kembali` Diaktifkan kembali · `penempatan_dibuat` Ditempatkan di kelas · `penempatan_diubah` Penempatan diubah |
| `izin_riwayat.tindakan` | `diajukan` Diajukan siswa · `dibatalkan_siswa` Dibatalkan siswa · `diinput_staf` Diinput staf · `disetujui` Disetujui · `ditolak` Ditolak · `dibatalkan` Dibatalkan · `dipersingkat` Dipersingkat · `diubah_menjadi_disetujui` Penolakan diubah menjadi disetujui |
| `izin.sumber` | `siswa` Pengajuan siswa · `staf` Input staf |
| `koreksi_status.berakhir_karena` | `diganti` Diganti koreksi baru · `dihapus` Dihapus |
| `status_harian.sumber_status` | `izin` Izin · `koreksi` Koreksi · `presensi` Presensi · `tanpa_data` Tanpa data |
| Status siswa (`06` §6.6 aturan 5) | Aktif · Akan aktif · Nonaktif. Periode yang dibatalkan tampil "Dibatalkan" di riwayat masa aktif. |
| Alasan scan bertanda (saringan `alasan`) | `jam_maju` Jam laptop di depan jam server · `selisih_berubah` Selisih jam berubah · `luar_aturan` Di luar jendela scan atau bukan hari sekolah · `sinkron_terlambat` Terlambat tersinkron. Kode ini sesuai kolom `tanda_*` di `06` §8.2. |
| Alasan stasiun disorot | Masih ada scan belum tersinkron · Tanpa kontak lebih dari 10 menit · Selisih jam lebih dari 2 menit · Ada scan galat · Penyimpanan permanen belum aktif |
| `status_stasiun.penyimpanan_permanen` | `1` Aktif · `0` Belum aktif · kosong Belum dilaporkan |
| Tahap sesi di kepala dashboard (FS-LAP-01) | Scan masuk belum dibuka · Sesi masuk berjalan · Sesi masuk ditutup · Sesi pulang berjalan · Sesi pulang ditutup · Bukan hari sekolah |
| Antrean hitung ulang (HAL-AKN-07) | Menunggu · Gagal · Selesai |
| `wa_template.jenis_kejadian` (R2) | `scan_masuk` Scan masuk · `scan_pulang` Scan pulang · `terlambat` Terlambat · `tidak_hadir` Tidak hadir · `tidak_scan_pulang` Tidak scan pulang · `izin` Izin · `pulang_awal` Pulang lebih awal |
| `wa_outbox.status` (R2) | `menunggu` Menunggu · `dikirim` Terkirim · `gagal` Gagal · `dibatalkan` Dibatalkan |
| `wa_penahanan.status` dan `penyebab` (R2) | `ditahan` Ditahan · `dilepas` Dilepas · `dibatalkan` Dibatalkan; `ambang` Di bawah ambang pengaman · `belum_sinkron` Stasiun belum tersinkron |

Label `log_aktivitas.jenis` ditetapkan bersama halamannya di Session 9.

## 15. Traceability

### 15.1 Fitur → halaman

| Fitur | Halaman |
|---|---|
| FS-AKN-01 | HAL-AKN-01, HAL-AKN-02 |
| FS-AKN-02 | HAL-AKN-03 |
| FS-AKN-03 | HAL-AKN-04 |
| FS-AKN-04 | HAL-KIO-02 |
| FS-AKN-05 | HAL-AKN-05, HAL-AKN-06 |
| FS-MD-01 | HAL-MD-01, §13 (logo) |
| FS-MD-02 | HAL-MD-02 |
| FS-MD-03 | HAL-MD-03 |
| FS-MD-04 | HAL-MD-04 s.d. HAL-MD-08, HAL-MD-10, HAL-MD-17, HAL-AKN-08 |
| FS-MD-05 | HAL-MD-11 s.d. HAL-MD-13 |
| FS-MD-06 | HAL-MD-14 |
| FS-MD-07 | HAL-MD-09 |
| FS-MD-08 | HAL-MD-15 |
| FS-MD-09 | HAL-MD-16, serta isian di HAL-MD-05 dan HAL-MD-07 |
| FS-KIO-01 s.d. FS-KIO-04 | HAL-KIO-01, `10` EP-KIO-01 s.d. EP-KIO-04 |
| FS-KIO-05 | HAL-KIO-02, HAL-LAP-01 (peringatan) |
| FS-KIO-06 | HAL-KIO-03 |
| FS-PRS-01 | HAL-PRS-01 |
| FS-PRS-02 | HAL-PRS-02 |
| FS-PRS-03 | HAL-PRS-03 |
| FS-PRS-04 | HAL-PRS-04 |
| FS-PRS-05 | Tidak memiliki halaman. Hasilnya tampil di HAL-LAP-01 s.d. HAL-LAP-07. |
| FS-PRS-06 | HAL-PRS-07 |
| FS-PRS-07 | HAL-PRS-08 |
| FS-PRS-08 | HAL-PRS-05 |
| FS-PRS-09 | HAL-PRS-06 |
| FS-PRS-10 | HAL-PRS-09 |
| FS-PRS-11 | HAL-PRS-10 |
| FS-IZN-01 | HAL-IZN-01 s.d. HAL-IZN-03 |
| FS-IZN-02 | HAL-IZN-08 |
| FS-IZN-03 | HAL-IZN-09 |
| FS-IZN-04 | HAL-IZN-04, HAL-IZN-06 |
| FS-IZN-05 | HAL-IZN-07, HAL-IZN-09 |
| FS-IZN-06 | HAL-IZN-03, HAL-IZN-05, HAL-IZN-06, §13 (lampiran) |
| FS-LAP-01 | HAL-LAP-01 s.d. HAL-LAP-03 |
| FS-LAP-02 | HAL-LAP-04 |
| FS-LAP-03 | HAL-LAP-05 |
| FS-LAP-04 | HAL-LAP-06, HAL-LAP-07 |
| FS-LAP-05 (R2) | Route `…/ekspor`, HAL-LAP-08, HAL-LAP-09 |
| FS-LAP-06 (R2) | HAL-LAP-10 |
| FS-WA-01 s.d. FS-WA-03 (R2) | HAL-WA-01 s.d. HAL-WA-03 |
| FS-INF-01 (R3) | HAL-INF-01, HAL-INF-02 |
| FS-INF-02 (R3) | HAL-INF-03, HAL-INF-04 |
| FS-INF-03 (R3) | HAL-INF-05 |
| FS-KRT-01 (R3) | HAL-KRT-01 |
| `07` ARS-57 | HAL-AKN-07 |

### 15.2 Hak akses → halaman

| Hak akses | Halaman |
|---|---|
| `HA-AKN-01` | HAL-AKN-02, HAL-AKN-03 |
| `HA-AKN-02` | HAL-AKN-04 |
| `HA-AKN-03` | HAL-KIO-02 |
| `HA-AKN-04` | HAL-AKN-06 |
| `HA-AKN-05` | HAL-AKN-05 |
| `HA-AKN-06` | HAL-AKN-05, HAL-MD-06 (status akun) |
| `HA-AKN-07` | HAL-AKN-07 |
| `HA-MD-01` | HAL-MD-02 |
| `HA-MD-02` | HAL-MD-03 |
| `HA-MD-03` | HAL-MD-05, HAL-MD-07, HAL-MD-10 s.d. HAL-MD-13, export data siswa (R2) |
| `HA-MD-04` | HAL-MD-14 |
| `HA-MD-05` | HAL-MD-04, HAL-MD-06, HAL-MD-09 (berkas foto), HAL-AKN-08 |
| `HA-MD-06` | HAL-MD-08 |
| `HA-MD-07` | HAL-MD-09 |
| `HA-MD-08` | HAL-MD-15 |
| `HA-MD-09` | HAL-MD-01 |
| `HA-MD-10` | HAL-MD-17 |
| `HA-MD-11` | HAL-MD-16 |
| `HA-KIO-01` | HAL-KIO-01, `10` EP-KIO-01 s.d. EP-KIO-04 |
| `HA-KIO-02` | HAL-KIO-02, HAL-LAP-01 (peringatan) |
| `HA-KIO-03` | HAL-KIO-03, HAL-LAP-01 (peringatan) |
| `HA-PRS-01` | HAL-PRS-01, HAL-PRS-02 |
| `HA-PRS-02` | HAL-PRS-03 |
| `HA-PRS-03` | HAL-PRS-06, HAL-PRS-07, tombol di HAL-LAP-02, HAL-LAP-04, dan HAL-LAP-06 |
| `HA-PRS-04` | HAL-PRS-08, tombol di HAL-LAP-02, HAL-LAP-04, dan HAL-LAP-06 |
| `HA-PRS-05` | DEPRECATED, tidak ada halaman |
| `HA-PRS-06` | HAL-PRS-10, tautan log di HAL-LAP-04 dan HAL-LAP-06 |
| `HA-PRS-07` | HAL-PRS-04 |
| `HA-PRS-08` | HAL-PRS-05 |
| `HA-PRS-09` | HAL-PRS-09 |
| `HA-IZN-01` | HAL-IZN-02, HAL-IZN-03 (batalkan) |
| `HA-IZN-02` | HAL-IZN-08, HAL-IZN-09 |
| `HA-IZN-03` | HAL-IZN-04, HAL-IZN-06 (verifikasi), HAL-LAP-01 (peringatan) |
| `HA-IZN-04` | HAL-IZN-01, HAL-IZN-03, HAL-IZN-05, HAL-IZN-06, HAL-IZN-09 |
| `HA-IZN-05` | §13 (lampiran) |
| `HA-IZN-06` | HAL-IZN-07, HAL-IZN-09 (per kelompok) |
| `HA-LAP-01` | HAL-LAP-01 |
| `HA-LAP-02` | HAL-LAP-02 s.d. HAL-LAP-04 |
| `HA-LAP-03` | HAL-LAP-04, HAL-LAP-05 |
| `HA-LAP-04` | HAL-LAP-06, HAL-LAP-07 |
| `HA-LAP-05` (R2) | Route `…/ekspor`, HAL-LAP-08, HAL-LAP-09 |
| `HA-LAP-06` (R2) | HAL-LAP-10 |
| `HA-WA-01` s.d. `HA-WA-03` (R2) | HAL-WA-01 s.d. HAL-WA-03 |
| `HA-INF-01` (R3) | HAL-INF-01 |
| `HA-INF-02` (R3) | HAL-INF-02 |
| `HA-INF-03` (R3) | HAL-INF-03 |
| `HA-INF-04` (R3) | HAL-INF-04 |
| `HA-KRT-01` (R3) | HAL-KRT-01 |

### 15.3 Catatan antarmuka `08` §16.2 yang dirujuk ke `09`

| Fitur | Catatan | Halaman |
|---|---|---|
| FS-MD-05, FS-MD-09 | Riwayat penempatan dan atribut tambahan di profil | HAL-MD-06 |
| FS-KIO-06 | Dikelompokkan per stasiun dan alasan | HAL-KIO-03 |
| FS-PRS-01 s.d. FS-PRS-04 | Tabel pola mingguan, kalender jadwal dan libur, aturan berlaku di samping isian | HAL-PRS-01 s.d. HAL-PRS-04 |
| FS-PRS-09 | Daftar centang dengan foto kecil dan centang semua | HAL-PRS-06 |
| FS-PRS-10, FS-PRS-11 | Contoh rentang tanggal, data lama dan baru berdampingan | HAL-PRS-09, HAL-PRS-10 |
| FS-IZN-02 s.d. FS-IZN-06 | Pintasan input izin, pratinjau dispensasi, lampiran tampil langsung, riwayat keputusan, saringan cepat | HAL-IZN-04 s.d. HAL-IZN-09 |

## 16. Perubahan pada dokumen lain

Perubahan karena keputusan Session 8, termasuk keputusan yang ditulis di `10`:

| Dokumen | Versi | Perubahan |
|---|---|---|
| `00-project-overview.md` | 0.8 | Kepala dokumen memuat `09` dan `10`. R-06 dan R-10 diperbarui. Kondisi repository (§7.3), peta dokumen, dan progres sesi diperbarui. Glosarium ditambah: fragmen. |
| `01-product-requirements.md` | 0.8 | FR-KIO-12 memuat versi kode kiosk, penyimpanan permanen, dan scan galat. Kepala dokumen diperbarui. |
| `02-user-roles-and-permissions.md` | 0.6 | Keputusan Session 8 ditambahkan di §1. `HA-AKN-07` ditambahkan di §6.1. §8 memuat alamat area dan halaman awal, menu akun di portal (`08` UI-35), dan menu "Kelas saya". |
| `03-user-flow.md` | 0.7 | §1 merujuk `09` dan `10`. UF-06 (status stasiun), UF-12 (pencarian siswa), dan UF-15 ("Kelas saya") diperbarui. |
| `04-feature-specification.md` | 0.5 | §2.7 (keputusan Session 8) ditambahkan. §1 dan pengantar §7 merujuk `09` dan `10`. FS-KIO-03 (isi laporan kiosk dan scan galat), FS-KIO-04 (akun pencatat dan isi respons), FS-KIO-05 (kolom dan sorotan baru), FS-PRS-06 dan FS-IZN-02 (pencarian siswa), FS-LAP-01 ("Kelas saya" dan peringatan pemeriksaan sistem), §12.3, §13, dan §14 diperbarui. |
| `06-database-design.md` | 0.4 | §2.6 (keputusan Session 8) ditambahkan. Kolom `scan_galat`, `versi_kiosk`, dan `penyimpanan_permanen` ditambahkan di `status_stasiun` (§8.1). Keterangan `scan.stasiun_id` (§8.2), §13, §18, dan §19 diperbarui. |
| `07-system-architecture.md` | 0.3 | §2.5 (keputusan Session 8) ditambahkan. ARS-04 (kompresi JSON), ARS-12, ARS-13, ARS-20, ARS-21, ARS-29 s.d. ARS-31, ARS-33, ARS-57, §1, §17.3, §18, dan §19 diperbarui. |
| `08-ui-ux-design-system.md` | 0.2 | §2.4 (keputusan Session 8) ditambahkan. UI-28 (komponen pencarian siswa), UI-31 (menu final di `09` §4), ikon "Kelas saya" di §4.5, tanda stasiun disorot di §4.2, §9.2, §14, §15, dan §16.2 diperbarui. Contoh menu di `08-contoh-tampilan.html` memuat "Kelas saya". |
| `13-reporting-import-export.md` | 0.4 | Kepala dokumen, pengantar, dan IM-01 merujuk halaman, alamat unduhan, dan alamat export di `09`. §9 diperbarui. |

`05` tidak berubah.

## 17. Pertanyaan terbuka dan nilai yang dipastikan nanti

Session 8 tidak menjawab dan tidak menambah OQ. Daftar lengkapnya ada di `00` §8.2.

| Hal | Rujukan | Dipastikan di |
|---|---|---|
| Teks validasi per isian, pesan galat rinci, dan pesan saat token CSRF ditolak di formulir | RT-08, RT-19 | Session 9 (`11`) |
| Pengaturan CSRF, cookie, dan header keamanan untuk halaman dan berkas, termasuk `Cache-Control` halaman berisi data siswa | RT-05, RT-09, §13 | Session 9 (`12`) |
| Pembatasan percobaan login, masa sesi staf dan siswa, serta PIN petugas untuk logout kiosk | HAL-AKN-01, `10` EP-KIO-04 | Session 9 |
| Halaman log aktivitas akun dan label `log_aktivitas.jenis` | §14 | Session 9 |
| Pencatatan pembukaan lampiran dan cache browser foto kecil | §13, OQ-17 | Session 9 |
| Rincian halaman R2: export, flyer, dan notifikasi WA | §10, §11 | Menjelang R2 (OQ-10) |
| Rincian halaman R3: jadwal pelajaran, pengumuman, halaman publik, dan kartu | §12 | Menjelang R3 (OQ-13) |
| Urutan pembuatan halaman dalam fase implementasi | — | Session 10–11 |

## Riwayat perubahan

| Versi | Tanggal | Perubahan |
|---|---|---|
| 0.1 | 2026-10-05 | Draft awal dari Session 8: keputusan route, halaman, dan API; konvensi alamat, metode, formulir, pengalihan, pencarian, fragmen, berkas, login, dan galat (`RT-01` s.d. `RT-22`); menu panel dan portal; route dan rincian setiap halaman R1; kerangka route R2 dan R3; berkas; label kode di halaman; dan traceability. |
