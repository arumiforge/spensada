# Spensada — Security

| Item | Nilai |
|---|---|
| Versi | 0.3 (draft, menunggu review) |
| Tanggal | 2026-10-10 |
| Sumber | Discovery Session 9 (Validation, Error Handling & Security). Diperbarui dengan keputusan Session 10 (Development Roadmap, `14`) dan keputusan pemilik proyek 2026-10-10 (server Windows dan Bootstrap 5, `07` §2.7). |
| Bergantung pada | [00-project-overview.md](00-project-overview.md): label status, glosarium, risiko (`R-xx`), dan pertanyaan terbuka (`OQ-xx`). [01-product-requirements.md](01-product-requirements.md): NFR-06 s.d. NFR-10. [02-user-roles-and-permissions.md](02-user-roles-and-permissions.md): jenis akun, role, dan hak akses (`HA-*`). [04-feature-specification.md](04-feature-specification.md): fitur akun, kiosk, dan izin (`FS-*`). [06-database-design.md](06-database-design.md): tabel `akun`, `log_aktivitas`, `pengaturan`, dan `lampiran`. [07-system-architecture.md](07-system-architecture.md): filter, sesi, cookie stasiun, file, cron, dan server (`ARS-*`). [09-page-and-route-specification.md](09-page-and-route-specification.md): route dan konvensi (`RT-*`). [10-api-specification.md](10-api-specification.md): API kiosk dan kode galat (`API-*`, `EP-*`). |
| Dokumen terkait | [11-validation-and-error-handling.md](11-validation-and-error-handling.md): aturan isian, pesan validasi, dan penanganan galat. [08-ui-ux-design-system.md](08-ui-ux-design-system.md): teks layar. [13-reporting-import-export.md](13-reporting-import-export.md): file import dan export. |

Dokumen ini menetapkan keamanan Spensada: model ancaman, autentikasi dan password, pembatasan percobaan login, sesi, keamanan stasiun dan kiosk, otorisasi, CSRF, HTTPS, cookie dan header, pencegahan injeksi, keamanan file, pembatasan laju, log aktivitas, pelindungan data pribadi, server dan backup, serta penanganan insiden.

Dokumen ini menjawab OQ-17 dan menambah OQ-18. Dokumen ini juga menyelesaikan butir keamanan yang diserahkan dokumen lain ke Session 9, antara lain `06` §5.4, `07` ARS-04, ARS-06, ARS-29 butir 5, ARS-30 butir 5, dan ARS-47, serta `10` API-03, API-04, dan EP-KIO-04. Aturan isian, pesan validasi, dan penanganan galat ada di `11`. Perubahan karena Session 9 di semua dokumen dicatat di §20.

## 1. Cara membaca dokumen ini

- **ID.**
  - Ketentuan keamanan memakai `SEC-<NN>`.
  - ID tidak pernah dinomori ulang. Butir yang batal ditandai `DEPRECATED`.
- **Status.** Label status mengikuti `00`. Keputusan dari ronde diskusi Session 9 berstatus DECISION (§2.1). Rincian yang tidak dibahas di ronde diskusi berstatus RECOMMENDATION, dan menjadi arah kerja implementasi sampai dikonfirmasi atau diganti.
- **Nilai CI4.** Nilai bawaan CodeIgniter yang disebut di dokumen ini diperiksa pada CI4 4.7.4 di folder `system/`. Bila CI4 diperbarui, nilai bawaan dan perilaku yang dirujuk diperiksa ulang (`07` §16).
- **Contoh.** Contoh tanggal mengikuti `04` §1: hari ini Selasa, 13 Oktober 2026.

## 2. Keputusan Session 9

### 2.1 Keputusan keamanan dan validasi

Keputusan dari ronde diskusi Session 9, untuk `11` dan dokumen ini:

| Topik | Keputusan | Rujukan | Status |
|---|---|---|---|
| Pencatatan akses lampiran (OQ-17) | Setiap pembukaan dan unduhan lampiran surat oleh akun staf dicatat: pelaku, waktu, lampiran, dan siswa. Siswa yang membuka lampirannya sendiri tidak dicatat. Admin melihat catatan itu di halaman log aktivitas, lewat hak baru `HA-AKN-08`. | SEC-26, SEC-60, `02` §6.1, `09` HAL-AKN-09 | DECISION |
| Masa sesi staf dan siswa | Sesi berakhir setelah 8 jam tidak ada aktivitas, atau 7 hari sejak login, mana yang lebih dulu. Sesi tetap berjalan setelah browser ditutup. Akun stasiun tetap mengikuti login 90 hari (`07` ARS-30). | SEC-12 | DECISION |
| Aturan password | 8 s.d. 64 karakter. Password tidak boleh ada di daftar password umum, dan tidak boleh memuat NISN, username, atau tanggal lahir. Tidak ada aturan wajib huruf besar, angka, atau simbol. | SEC-03, `11` VAL-21 | DECISION |
| Pembatasan percobaan login | Lima kali gagal untuk satu identitas login (username atau NISN) dalam 15 menit mengunci login identitas itu sekitar 15 menit. Dua puluh kali gagal dalam 24 jam menguncinya sekitar 24 jam. Admin, atau wali kelas untuk siswa rombelnya, dapat membuka kunci lebih awal. Satu alamat IP dibatasi 100 kali gagal dalam 15 menit. | SEC-08 s.d. SEC-11 | DECISION |
| PIN petugas kiosk | Satu PIN 6 digit untuk semua stasiun, diatur admin. PIN wajib untuk logout akun stasiun dan untuk hapus data lokal di kiosk. | SEC-21, SEC-22 | DECISION |
| Retensi data | Belum ada penghapusan data presensi, scan, izin, lampiran, log, dan data siswa, sampai sekolah menetapkan kebijakan datanya (OQ-18). Hanya data teknis yang dibersihkan otomatis. | SEC-66, SEC-67 | DECISION |
| Format dan ukuran unggahan | Paket 10 MB: foto, logo, dan lampiran paling besar 10 MB per file. Foto dan logo berformat JPG, PNG, atau WebP. Lampiran berformat JPG, PNG, WebP, atau PDF, paling banyak 3 file. File import paling besar 5 MB. Foto massal paling besar 100 MB per unggahan. | SEC-49, `11` VAL-28 | DECISION |
| Satu login aktif per akun stasiun | Satu akun stasiun hanya aktif di satu laptop. Login terakhir yang berlaku, dan laptop sebelumnya diminta login ulang. | SEC-19 | DECISION |

### 2.2 Temuan yang ditetapkan tanpa ronde diskusi

Penyusunan dokumen ini menemukan kebutuhan berikut. Semuanya berstatus RECOMMENDATION, dan perubahannya di dokumen lain tercatat di §20.

| Temuan | Penetapan | Rujukan |
|---|---|---|
| Cookie sesi bawaan CI4 tidak bertanda `Secure` (`Config\Cookie::$secure = false`). | Cookie sesi dan CSRF memakai prefiks nama `__Host-`, yang mewajibkan `Secure`, `Path=/`, dan tanpa `Domain`. | SEC-33 |
| Header keamanan bawaan filter `secureheaders` CI4 tidak memuat HSTS dan CSP. HSTS dari `force_https()` CI4 hanya dikirim saat pengalihan, padahal pengalihan ke HTTPS dilakukan Nginx. | Header yang berlaku untuk semua jawaban dikirim Nginx. CSP dan `Permissions-Policy` dikirim filter aplikasi `keamanan`, per area. | SEC-34 s.d. SEC-37 |
| CSP bawaan CI4 (`CSPEnabled`) menimpa header CSP yang ditulis controller, sehingga tidak dapat dipakai untuk berkas. | `CSPEnabled` tetap mati. Filter `keamanan` tidak menimpa CSP yang sudah ditulis controller. | SEC-36 |
| Filter CSRF bawaan CI4 di production mengalihkan ke halaman sebelumnya tanpa isian formulir, dengan pesan berbahasa Inggris. | Filter `csrf` aplikasi mengembalikan isian, kecuali password, PIN, dan file, dengan pesan berbahasa Indonesia. | SEC-30, `11` GAL-06 |
| Kiosk menghapus scan galat saat data lokal dihapus, sehingga bukti scan yang ditolak server hilang. | Server mencatat setiap scan yang ditolak di `log_aktivitas`, sehingga penghapusan data lokal aman. | SEC-24, `10` EP-KIO-03 |
| Import XLSX dan CSV dapat memuat rumus yang dijalankan saat file export dibuka. | Teks yang diawali tanda rumus diberi awalan petik di file export. | SEC-46, `13` IE-08, IE-09, IM-07 |
| Halaman galat 500 tidak memberi cara melaporkan galat yang dapat dilacak. | Setiap permintaan memiliki ID dari Nginx, yang ditampilkan sebagai kode laporan di halaman 500 dan dicatat di log aplikasi. | SEC-58, `11` GAL-13 |
| Password awal acak belum memiliki aturan huruf. | Password awal memakai huruf kecil dan angka tanpa huruf yang mirip. | SEC-05 |

### 2.3 Nilai yang dipastikan di dokumen ini

Nilai yang diserahkan dokumen lain ke Session 9 dan ditetapkan di sini:

| Nilai | Dari | Ditetapkan di |
|---|---|---|
| Masa sesi, regenerasi ID sesi, dan pembatasan login | `07` ARS-47, `06` §5.4 | SEC-08 s.d. SEC-17 |
| Pengaturan CSRF: nama, masa berlaku, dan regenerasi token | `07` ARS-29 butir 5, `10` API-04 | SEC-28 s.d. SEC-31 |
| PIN petugas untuk logout kiosk | `07` ARS-30 butir 5, `10` EP-KIO-04 | SEC-21, SEC-22 |
| Mengikat akun stasiun ke satu perangkat | `04` FS-AKN-04 | SEC-19 |
| Pembatasan laju API kiosk (`terlalu_sering`) | `10` API-03 | SEC-54, SEC-55 |
| Batas unggah PHP dan Nginx | `07` ARS-04 | SEC-49, SEC-71 |
| Retensi dan enkripsi backup | `07` ARS-06 | SEC-67, SEC-75 |
| Daftar jenis log aktivitas dan isinya | `06` §5.3, `04` §4.4 butir 5 | SEC-57, SEC-59 |
| Tempat kredensial gateway WhatsApp | `06` §6.1 | SEC-73 |
| Penanganan scan galat yang tetap ditolak | `10` EP-KIO-03, `03` UF-06 | SEC-24 |
| Logging export | `13` IE-13 | SEC-59 |

## 3. Model ancaman

Spensada terbuka ke internet, karena siswa mengajukan izin dari rumah dan staf membuka panel dari mana saja. Aset, pelaku, dan ancaman di bawah menjadi dasar ketentuan di §4 s.d. §18. (RECOMMENDATION)

| Aset | Mengapa penting | Ketentuan |
|---|---|---|
| Data siswa dan foto | Data anak, termasuk data pribadi spesifik menurut UU 27/2022 (R-17). | SEC-25, SEC-63 s.d. SEC-69 |
| Lampiran surat sakit | Data kesehatan. | SEC-26, SEC-50 s.d. SEC-53 |
| Data presensi | Dipakai untuk rapor dan tindakan disiplin. Pemalsuan merugikan siswa dan sekolah. | SEC-18 s.d. SEC-24, SEC-57 |
| Akun staf, terutama admin | Akun admin dapat mengubah semua data. | SEC-01 s.d. SEC-17 |
| Laptop stasiun | Menyimpan data siswa, foto, dan scan di IndexedDB (R-07). | SEC-18 s.d. SEC-24 |
| Server dan backup | Menyimpan semua data. | SEC-70 s.d. SEC-78 |

| Pelaku | Contoh ancaman | Ketentuan utama |
|---|---|---|
| Penyerang dari internet | Menebak password, mencoba password bocor dari layanan lain, memindai celah umum, atau membanjiri halaman login. | SEC-03, SEC-08 s.d. SEC-11, SEC-34, SEC-54 |
| Siswa | Mencatatkan presensi teman, membuka data siswa lain, mengunggah file berbahaya sebagai lampiran, atau memakai laptop stasiun yang ditinggal. | SEC-21, SEC-25, SEC-49 s.d. SEC-53, `05` BR-SCN-* |
| Staf | Membuka data di luar cakupannya, atau membuka lampiran tanpa keperluan. | SEC-25, SEC-26, SEC-60 |
| Orang yang menemukan laptop stasiun atau ponsel yang tertinggal | Membaca data siswa di kiosk, atau memakai sesi yang masih login. | SEC-12, SEC-21 s.d. SEC-23, `07` ARS-32 |
| Kode pihak ketiga | Library dengan celah keamanan. | SEC-76, SEC-77 |

Di luar cakupan: serangan dengan akses fisik ke server, penyedia server yang tidak dapat dipercaya, dan perangkat pengguna yang sudah terinfeksi malware. Ketiganya hanya dikurangi dengan langkah umum di §16.

## 4. Autentikasi dan password

| ID | Ketentuan | Status |
|---|---|---|
| SEC-01 | **Langkah login.** Lihat butir di bawah tabel. | RECOMMENDATION |
| SEC-02 | **Hash password.** Password disimpan dengan `password_hash($password, PASSWORD_BCRYPT, ['cost' => 11])`, dan dicocokkan dengan `password_verify()`. Cost 11 memerlukan sekitar 130 ms per percobaan di lingkungan pengembangan, cukup lambat untuk menahan tebakan dari hash yang bocor, tetapi tidak memberatkan antrean login pagi. Waktunya diukur ulang di server production sebelum uji coba R1, dengan sasaran 100 s.d. 300 ms. Setelah login berhasil, `password_needs_rehash()` diperiksa, dan hash dibuat ulang bila cost berubah. Cap kredensial diturunkan dari hash (`07` ARS-47), sehingga pembuatan ulang hash juga mengakhiri sesi lain akun itu. Karena itu cost hanya diubah bersama rilis. bcrypt hanya memakai 72 byte pertama password, sehingga panjang password dibatasi (SEC-03). | RECOMMENDATION |
| SEC-03 | **Aturan password.** Berlaku untuk password yang dipilih pengguna sendiri, yaitu ganti password (FS-AKN-02). Rincian isian dan pesannya di `11` VAL-21. Lihat butir di bawah tabel. | DECISION (panjang 8 s.d. 64, daftar password umum, larangan NISN, username, dan tanggal lahir, tanpa aturan komposisi); RECOMMENDATION (rincian) |
| SEC-04 | **Daftar password umum.** Daftar disimpan sebagai file teks di `app/ThirdParty/password-umum/`, satu password per baris dalam huruf kecil, beserta file lisensi dan sumbernya. Isinya 10.000 password terumum dari SecLists (lisensi MIT), ditambah kata yang umum dipakai di Indonesia dan di sekolah ini, misalnya `bismillah`, `indonesia`, `sayang`, `rahasia`, `spensada`, dan nama sekolah. Daftar dimuat sekali per permintaan ganti password. Pencocokan memakai huruf kecil, setelah angka dan tanda baca di akhir password dibuang, sehingga `Indonesia123!` juga ditolak. | RECOMMENDATION |
| SEC-05 | **Password acak.** Password awal staf, password hasil reset, password slip siswa, dan kredensial stasiun dibuat dengan `random_int()`, dari 31 karakter: huruf kecil `a`–`z` tanpa `i`, `l`, dan `o`, ditambah angka `2`–`9`. Huruf dan angka yang mudah tertukar saat dibaca dari slip tidak dipakai. Panjangnya 8 karakter untuk siswa, agar mudah diketik dari slip, dan 12 karakter untuk staf dan stasiun. Password acak tidak diperiksa dengan SEC-03. Password acak hanya ditampilkan sekali (`09` RT-09), dan tidak pernah ditulis ke log, sesi, atau database selain sebagai hash. | RECOMMENDATION |
| SEC-06 | **Ganti dan reset password.** Ganti password sendiri meminta password lama (FS-AKN-02). Password lama yang salah dihitung sebagai login gagal untuk akun itu (SEC-09), dan lima kali salah berturut-turut di satu sesi mengakhiri sesi itu (`11` §5.1). Ganti password, reset password, dan ganti kredensial stasiun mengubah hash, sehingga semua sesi lain dari akun itu berakhir (`07` ARS-47 butir 2). Sesi tempat password diganti tetap berjalan dengan ID sesi baru (SEC-14). Setiap kejadian dicatat di log aktivitas (SEC-59). Tidak ada reset password mandiri lewat email atau WhatsApp (FS-AKN-01, di luar cakupan). | RECOMMENDATION |
| SEC-07 | **Password tidak bocor ke tempat lain.** Parameter password di kode diberi atribut `#[\SensitiveParameter]`, agar tidak muncul di jejak pengecualian. Isian password, PIN, dan token tidak pernah dicatat di log aplikasi maupun `log_aktivitas`, tidak dikembalikan ke formulir saat validasi gagal, dan tidak disimpan di data lama formulir (`old()`). Karena `withInput()` CI4 menyalin seluruh isian POST ke sesi, isian itu dibuang lebih dulu sebelum pengalihan. Formulir password memakai `autocomplete="current-password"` atau `"new-password"`, agar pengelola password browser dapat dipakai. | RECOMMENDATION |

Butir SEC-01:

1. Identitas login dibersihkan: spasi di awal dan akhir dibuang, lalu diubah ke huruf kecil. NISN tetap berupa 10 digit. Password tidak diubah sama sekali, termasuk spasinya.
2. Sistem memeriksa kunci login lebih dulu (SEC-08). Bila identitas atau alamat IP sedang dikunci, password tidak diperiksa, pesan kunci ditampilkan, dan percobaan itu tidak dicatat sebagai login gagal, sehingga kunci tidak bertambah panjang.
3. Sistem mencari akun dari identitas. Bila akun tidak ada, atau belum memiliki password, password tetap diverifikasi terhadap hash pengganti dengan cost yang sama. Dengan begitu, waktu jawaban tidak membedakan akun yang ada dan tidak ada.
4. Status akun diperiksa setelah password. Akun nonaktif dan akun siswa yang belum aktif menghasilkan pesan yang sama dengan password salah (AC-AKN-01-02).
5. Login yang berhasil membuat ID sesi baru dan membuang sesi lama (`session()->regenerate(true)`), membuat token CSRF baru (SEC-29), mencatat waktu login dan aktivitas di sesi (SEC-12), memperbarui `akun.login_terakhir_at`, menghapus catatan gagal identitas itu (SEC-09), dan menulis log `login_berhasil`.
6. Login yang gagal mencatat percobaan (SEC-09) dan menulis log `login_gagal`.
7. Login akun stasiun juga membuat cookie login stasiun (`07` ARS-30) dan ID login stasiun baru (SEC-19).

Butir SEC-03:

1. Panjang 8 s.d. 64 karakter, dihitung per karakter Unicode, dan paling besar 72 byte UTF-8 agar seluruh password dipakai bcrypt.
2. Semua karakter boleh, termasuk spasi, kecuali karakter kontrol. Password tidak dipotong spasinya.
3. Password tidak boleh memuat username atau NISN akunnya, tanpa membedakan huruf besar dan kecil. Aturan ini diperiksa sebelum daftar password umum, agar password seperti `zebra0012345678` mendapat pesan NISN, bukan pesan password umum (`04` AC-AKN-02-05).
4. Password tidak boleh ada di daftar password umum (SEC-04).
5. Untuk akun siswa, password tidak boleh memuat tanggal lahirnya dalam bentuk `DDMMYYYY`, `DDMMYY`, `YYYYMMDD`, atau `DD-MM-YYYY`, misalnya `14032011`.
6. Password tidak boleh berupa satu karakter yang diulang, atau urutan huruf atau angka, misalnya `aaaaaaaa`, `12345678`, atau `abcdefgh`.
7. Password baru harus berbeda dengan password saat ini.
8. Tidak ada aturan wajib huruf besar, angka, atau simbol, dan tidak ada masa kedaluwarsa password. Password hanya wajib diganti setelah password awal atau reset (`02` §2 butir 1).

## 5. Pembatasan percobaan login

| ID | Ketentuan | Status |
|---|---|---|
| SEC-08 | **Batas per identitas dan per IP.** Lihat tabel di bawah. Kunci berlaku untuk identitas yang dicoba, baik akunnya ada maupun tidak, sehingga pesan kunci tidak membocorkan keberadaan akun. Selama dikunci, password yang benar pun ditolak (AC-AKN-01-03). | DECISION (angka dan pembukaan kunci); RECOMMENDATION (rincian) |
| SEC-09 | **Tabel `percobaan_login`.** Setiap login gagal menulis satu baris: hash identitas, alamat IP, dan waktu (`06` §5.4). Hash identitas adalah HMAC-SHA256 atas identitas yang sudah dibersihkan, dengan kunci yang diturunkan dari kunci enkripsi aplikasi lewat `hash_hkdf()` dengan konteks `spensada-percobaan-login`. Identitas yang salah ketik, misalnya password yang terketik di kolom username, tidak tersimpan sebagai teks. Jendela waktu bersifat bergeser: kunci dihitung dari baris dalam 15 menit atau 24 jam terakhir, sehingga kunci berakhir sendiri saat baris tertua keluar dari jendela. Login berhasil menghapus baris identitas itu. Baris yang lebih tua dari 24 jam dihapus tugas harian (`07` ARS-56). | RECOMMENDATION |
| SEC-10 | **Pemeriksaan bersamaan.** Pemeriksaan kunci, verifikasi password, dan pencatatan gagal untuk satu identitas berjalan di bawah kunci bernama MySQL `spensada:login:<40 karakter pertama hash identitas>`, dengan waktu tunggu 5 detik. Dengan begitu, percobaan bersamaan tidak melewati batas. Bila kunci bernama tidak didapat, login dijawab sebagai sedang dikunci. Pembatas laju CI4 (`Throttler`) tidak dipakai di sini, karena penyimpanannya di cache tidak atomik dan cache dikosongkan saat rilis (`07` §16). | RECOMMENDATION |
| SEC-11 | **Membuka kunci dan log.** Admin membuka kunci akun staf, akun siswa, dan akun stasiun. Wali kelas membuka kunci akun siswa rombelnya (`HA-AKN-04`). Tombol "Buka kunci login" tampil di detail akun staf dan stasiun, dan di profil siswa (`09` HAL-AKN-06), selama akun dikunci, dan menghapus baris `percobaan_login` identitas itu. Kunci per IP tidak dapat dibuka dari aplikasi. Saat kunci identitas atau IP mulai berlaku, sistem menulis satu log `login_dikunci`. Pembukaan kunci menulis log `kunci_login_dibuka`. | DECISION (siapa yang membuka kunci); RECOMMENDATION (rincian) |

| Batas | Gagal | Jendela | Akibat | Pesan (`11` §5.1) |
|---|---|---|---|---|
| Per identitas, pendek | 5 | 15 menit | Login identitas itu ditolak sampai jumlah gagal dalam 15 menit terakhir di bawah 5, yaitu sekitar 15 menit. | "Terlalu banyak percobaan login. Coba lagi pukul 07.45." |
| Per identitas, panjang | 20 | 24 jam | Login identitas itu ditolak sekitar 24 jam, sampai dibuka admin atau wali kelas. | "Login akun ini dikunci karena terlalu banyak percobaan. Hubungi wali kelas atau admin." |
| Per alamat IP | 100 | 15 menit | Login dari alamat IP itu ditolak sekitar 15 menit. | "Terlalu banyak percobaan login dari jaringan ini. Coba lagi pukul 07.45." |

Batas per IP longgar, karena siswa dalam satu jaringan Wi-Fi sekolah dapat memakai satu alamat IP publik. Alamat IP diambil dari `REMOTE_ADDR`. Nginx dan PHP berada di satu server tanpa proxy di depannya, sehingga header `X-Forwarded-For` diabaikan (`Config\App::$proxyIPs` kosong). Di depan aplikasi, Nginx juga membatasi laju `POST /login` (SEC-56).

## 6. Sesi

| ID | Ketentuan | Status |
|---|---|---|
| SEC-12 | **Masa sesi staf dan siswa.** Sesi berakhir setelah 8 jam tanpa aktivitas, atau 7 hari sejak login, mana yang lebih dulu. Lihat butir di bawah tabel. | DECISION (8 jam dan 7 hari; bertahan setelah browser ditutup); RECOMMENDATION (rincian) |
| SEC-13 | **Konfigurasi sesi CI4.** `Config\Session`: `driver` `FileHandler`, `cookieName` `__Host-spensada_sesi`, `expiration` 604800 (7 hari), `savePath` `WRITEPATH . 'session'`, `matchIP` `false`, `timeToUpdate` 300, dan `regenerateDestroy` `false`. `matchIP` tidak dipakai, karena alamat IP ponsel berubah saat berpindah jaringan. Atribut cookie mengikuti SEC-33. ID sesi yang dibawa dari sebelum login tidak terpakai setelah login, karena login membuat ID baru dan membuang sesi lama (SEC-01 butir 5). `FileHandler` CI4 tidak menolak ID sesi yang tidak dikenal, sehingga pengaman ini tidak bergantung pada `session.use_strict_mode`. | RECOMMENDATION |
| SEC-14 | **Regenerasi ID sesi.** ID sesi dibuat baru saat login dan saat password diganti, dan sesi lama dibuang. Selebihnya CI4 membuat ID baru setiap 5 menit untuk permintaan halaman, dan tidak untuk permintaan latar belakang. ID lama tidak langsung dihapus (`regenerateDestroy = false`), agar permintaan bersamaan dari tab lain tidak kehilangan sesi. | RECOMMENDATION |
| SEC-15 | **Logout.** Logout memakai POST dengan token CSRF (`09` RT-05), menghapus data sesi dan file sesinya (`session()->destroy()`), menghapus cookie sesi, dan membuat token CSRF baru. Logout tidak dapat dipicu lewat tautan dari situs lain. | RECOMMENDATION |
| SEC-16 | **Isi sesi.** Sesi hanya menyimpan ID akun, jenis akun, waktu login, waktu aktivitas terakhir, cap kredensial (`07` ARS-47), ID login stasiun untuk akun stasiun (SEC-19), token sekali pakai (`09` RT-07, RT-09), serta pesan sekali tampil, isian lama, dan pesan validasi untuk halaman berikutnya (`09` RT-08). Isian lama tidak pernah memuat password, PIN, atau token (SEC-07). Role dan status akun dimuat dari database di setiap permintaan. | RECOMMENDATION |
| SEC-17 | **Pembersihan file sesi.** Pembersihan sesi bawaan PHP berjalan secara acak (`session.gc_probability`) dan tidak diandalkan. Tugas harian menghapus file sesi yang tidak berubah lebih dari 7 hari (`07` ARS-56). Folder `writable/session` hanya dapat dibaca akun layanan `spensada` dan pengelola (SEC-72). | RECOMMENDATION |

Butir SEC-12:

1. Filter `sesi` membaca `login_at` dan `aktif_at` dari sesi. Bila `aktif_at` lebih dari 8 jam yang lalu, atau `login_at` lebih dari 7 hari yang lalu, sesi dihapus, dan pengguna diperlakukan seperti belum login (FS-AKN-01 E4).
2. `aktif_at` diperbarui pada permintaan dari pengguna, paling sering sekali per menit. Permintaan polling fragmen (`10` EP-LAP-01, EP-KIO-05) tidak memperbaruinya, sehingga dashboard yang dibiarkan terbuka tetap berakhir setelah 8 jam tanpa tindakan. Bantuan formulir, misalnya pencarian siswa, memperbaruinya.
3. Cookie sesi berlaku 7 hari, sehingga sesi tetap berjalan setelah browser ditutup. Pengguna di komputer bersama diminta logout. Teks bantuan di halaman login menyebutnya (`08` UI-38).
4. Sesi akun stasiun tidak mengikuti batas ini. Sesi stasiun yang habis dibuat ulang dari cookie login stasiun (`07` ARS-30).
5. Halaman yang dibiarkan terbuka setelah sesi berakhir: polling berhenti dengan pesan "Sesi berakhir" (`08` UI-28), dan formulir yang dikirim membawa pengguna ke halaman login, lalu kembali ke halaman semula setelah login (FS-AKN-01 E4, `11` GAL-07).

## 7. Stasiun dan kiosk

| ID | Ketentuan | Status |
|---|---|---|
| SEC-18 | **Cookie login stasiun.** Cookie `__Secure-spensada_stasiun`, dengan atribut `Secure`, `HttpOnly`, `SameSite=Strict`, dan `Path=/kiosk` (`07` ARS-30). Isinya ID akun, waktu kedaluwarsa, ID login stasiun (SEC-19), dan tanda tangan HMAC-SHA256 atas ketiganya ditambah cap kredensial. Kunci HMAC diturunkan dengan `hash_hkdf()` dan konteks `spensada-login-stasiun`. Tanda tangan dibandingkan dengan `hash_equals()`. | RECOMMENDATION |
| SEC-19 | **Satu login aktif per akun stasiun.** Lihat butir di bawah tabel. | DECISION (satu laptop, login terakhir berlaku); RECOMMENDATION (mekanisme) |
| SEC-20 | **Ruang lingkup akun stasiun.** Akun stasiun hanya dapat memakai area kiosk (`02` §4 butir 4). Server tidak memercayai hasil penilaian kiosk: setiap scan dinilai ulang dengan aturan di server (FS-KIO-04), dan scan dari akun lain ditolak (`10` EP-KIO-03, `akun_berbeda`). Pencatat presensi palsu tetap memerlukan akun stasiun yang sah, dan setiap scan tercatat dengan stasiunnya (R-10). | RECOMMENDATION |
| SEC-21 | **PIN petugas.** Lihat butir di bawah tabel. | DECISION (satu PIN 6 digit untuk semua stasiun, diatur admin, untuk logout dan hapus data lokal); RECOMMENDATION (rincian) |
| SEC-22 | **Tindakan yang dilindungi PIN.** Logout akun stasiun dan hapus data lokal meminta PIN petugas. Penghapusan data karena akun stasiun dinonaktifkan tidak meminta PIN (FS-AKN-04 butir 4). Layar penuh, muat ulang data, dan sinkron sekarang tidak meminta PIN, karena tidak menghapus apa pun. Hapus data lokal tetap ditolak selama ada scan belum tersinkron (FS-KIO-01 butir 8). | DECISION |
| SEC-23 | **Data di laptop stasiun.** Kiosk hanya memuat data minimal (FS-KIO-01 butir 3), dan foto disimpan di IndexedDB, bukan di cache browser (`07` ARS-52). Data di IndexedDB tidak dienkripsi, karena kuncinya harus tersimpan di laptop yang sama. Pengamannya ada di laptop: profil browser khusus, akun Windows non-admin dengan password, layar terkunci otomatis di luar jam pakai, dan BitLocker atau enkripsi perangkat Windows bila tersedia (`07` ARS-32, R-07). Laptop yang hilang ditangani dengan menonaktifkan akun stasiunnya (UF-06 E1). | RECOMMENDATION |
| SEC-24 | **Scan yang ditolak server.** Server mencatat setiap scan yang ditolak saat sinkron di `log_aktivitas` dengan jenis `scan_ditolak_server`: stasiun, kode tolak, UUID, NISN, dan jam scan, serta data mentah paling besar 1 KB. Kiriman ulang scan yang sama tidak menulis log baru, berdasarkan stasiun dan hash SHA-1 data mentah lengkap, yang disimpan di isian `data` sebagai `sha1`. Kiosk tetap menyimpan scan galat sampai data lokal dihapus, dan penghapusan itu aman karena salinannya sudah ada di server. Admin melihatnya di halaman log aktivitas (`09` HAL-AKN-09). | RECOMMENDATION |

Butir SEC-19:

1. Kolom baru `akun.login_stasiun_id` (`06` §5.1) menyimpan ID login stasiun yang berlaku, berupa 32 karakter heksadesimal acak. Login akun stasiun membuat ID baru dan menulisnya ke kolom itu, ke sesi, dan ke cookie login stasiun.
2. Filter `sesi` membandingkan ID di sesi atau cookie dengan kolom itu di setiap permintaan kiosk. Bila berbeda, laptop itu tidak lagi login: sesinya dihapus, dan server menjawab 401 `login_ulang` dengan isian `alasan` bernilai `login_berpindah` (`10` API-03), agar kiosk menampilkan teks yang sesuai (`11` §5.3). Data dan scan di laptop tetap tersimpan (`07` ARS-31).
3. Login yang memindahkan akun dari laptop lain menulis log `login_stasiun_berpindah`, sehingga admin dapat melihat bila akun stasiun dipakai di dua laptop.
4. Ganti kredensial, logout, dan penonaktifan mengosongkan kolom itu.
5. Bila laptop lama masih menyimpan scan belum tersinkron, petugas login lagi di laptop lama untuk mengirimnya. Login itu memindahkan akun kembali ke laptop lama. Karena itu, laptop baru dipakai setelah laptop lama tersinkron, atau dengan akun stasiun lain (UF-06).

Butir SEC-21:

1. Admin mengatur PIN di halaman PIN petugas (`09` HAL-KIO-02, `/panel/stasiun/pin`, `HA-AKN-03`). PIN berisi tepat 6 digit (`11` VAL-22). PIN tidak ditampilkan lagi setelah disimpan.
2. PIN disimpan di `pengaturan.kiosk_pin` sebagai hash PBKDF2-SHA256 dengan garam acak 16 byte dan 100.000 iterasi, ditulis sebagai teks JSON berisi garam, jumlah iterasi, dan hash (`06` §6.1). Hash ini dikirim ke kiosk di data kiosk (`10` EP-KIO-01, isian `pin`), sehingga PIN dapat diperiksa saat offline dengan WebCrypto. Perubahan PIN mengubah versi data, sehingga kiosk memuat ulang data.
3. Lima kali PIN salah mengunci menu petugas selama 5 menit. Hitungan dan waktu kunci disimpan di `meta` IndexedDB, sehingga tidak hilang saat halaman dimuat ulang.
4. Bila PIN belum diatur, kiosk menampilkan pita "PIN petugas belum diatur. Hubungi admin." di menu petugas, dan tindakan berisiko cukup memakai konfirmasi (`08` UI-48).
5. Perubahan PIN dicatat di log `pin_kiosk_diubah`, tanpa PIN maupun hashnya.
6. PIN adalah penghalang bagi orang di gerbang, bukan pengaman terhadap orang yang dapat membaca IndexedDB di laptop. Enam digit dapat ditebak dari hash dalam waktu singkat oleh orang yang sudah menguasai profil browser. Karena itu, PIN tidak dipakai di tempat lain, dan pengaman laptop tetap mengikuti SEC-23.

## 8. Otorisasi

| ID | Ketentuan | Status |
|---|---|---|
| SEC-25 | **Hak dan cakupan di server.** Setiap halaman, fragmen, berkas, dan endpoint memeriksa hak lewat filter `hak` (`07` ARS-13), dan cakupan data lewat service `HakAkses` (ARS-15). ID di alamat tidak pernah dipercaya: data di luar cakupan dijawab 403, dan ID yang tidak ada dijawab sesuai `09` RT-11, sehingga pengguna dengan cakupan terbatas tidak dapat menebak keberadaan data (`11` GAL-08). Menu dan tombol yang disembunyikan bukan pengaman. Uji otorisasi di SEC-81 memeriksa setiap route. | RECOMMENDATION |
| SEC-26 | **Hak `HA-AKN-08`.** Hak baru "Lihat log aktivitas", hanya untuk admin (`02` §6.1). Hak ini membuka halaman log aktivitas (`09` HAL-AKN-09), yang memuat log akun, pengaturan, import, akses lampiran, dan scan yang ditolak server (SEC-59). Pimpinan dan guru BK tidak memegang hak ini, karena log memuat jejak akses staf lain. | DECISION (OQ-17, admin saja); RECOMMENDATION (isi halaman) |
| SEC-27 | **Tindakan terhadap akun sendiri dan admin terakhir.** Admin tidak dapat menonaktifkan akunnya sendiri atau mencabut role Admin dari dirinya (FS-AKN-03 E4). Role Admin terakhir yang aktif tidak dapat dicabut atau dinonaktifkan. Akses admin yang hilang dipulihkan lewat terminal server (`07` ARS-49). | RECOMMENDATION |

## 9. CSRF

| ID | Ketentuan | Status |
|---|---|---|
| SEC-28 | **Konfigurasi CSRF.** `Config\Security`: `csrfProtection` `'cookie'`, `tokenRandomize` `true`, `tokenName` `_csrf`, `headerName` `X-CSRF-TOKEN`, `cookieName` `__Host-spensada_csrf` (SEC-33), ditulis lengkap karena `Config\Cookie::$prefix` kosong, `expires` 0, dan `regenerate` `false`. Lihat butir di bawah tabel. | RECOMMENDATION |
| SEC-29 | **Token baru saat login dan logout.** Login dan logout membuat token baru dengan `service('security')->generateHash()`. Token dari sebelum login tidak berlaku setelah login. | RECOMMENDATION |
| SEC-30 | **Filter `csrf` aplikasi.** Lihat butir di bawah tabel. Pesannya di `11` GAL-06. | RECOMMENDATION |
| SEC-31 | **Pelengkap CSRF.** Cookie sesi memakai `SameSite=Lax`, dan cookie login stasiun `SameSite=Strict` (SEC-33). GET tidak pernah mengubah data (`09` RT-05). Semua formulir dikirim ke alamat sendiri (`form-action 'self'`, SEC-36). Endpoint JSON hanya menerima `Content-Type: application/json` (`10` API-01), sehingga formulir dari situs lain tidak dapat mengirimnya. | RECOMMENDATION |

Butir SEC-28:

1. **Mode cookie.** Token disimpan di cookie, bukan di sesi, sehingga regenerasi tidak memerlukan sesi yang terbuka (`10` API-08) dan token tetap ada saat sesi berganti.
2. **Token diacak.** Dengan `tokenRandomize`, token yang ditulis di halaman berbeda setiap kali dibuat, meskipun nilai dasarnya sama. Ini mencegah serangan yang menebak token dari panjang jawaban yang dikompresi (BREACH), karena halaman dikompresi gzip (`10` API-07).
3. **Tanpa masa berlaku tetap.** Dengan `expires` 0, cookie CSRF berlaku sampai browser ditutup. Nilai bawaan 7200 membuat cookie berakhir 2 jam setelah dibuat, karena CI4 hanya menulis waktu kedaluwarsa saat token dibuat. Akibatnya formulir yang terbuka lebih dari 2 jam dan kiosk yang terus berjalan akan rutin ditolak (`07` ARS-29 butir 5).
4. **Tanpa regenerasi per kiriman.** Dengan `regenerate` `false`, token tidak berganti setelah setiap kiriman, sehingga beberapa tab dan tombol kembali di browser tetap dapat mengirim formulir. Token tetap berganti saat login dan logout (SEC-29).
5. **Nama isian.** Formulir memakai isian tersembunyi `_csrf` dari `csrf_field()`. JavaScript memakai header `X-CSRF-TOKEN` dengan token dari elemen `<meta>` atau dari isian `csrf` di jawaban JSON (`10` API-04).

Butir SEC-30:

1. Filter `csrf` aplikasi berjalan global sebelum filter lain (`07` ARS-13). Sebelum memeriksa token, filter memeriksa apakah badan permintaan lebih besar dari `post_max_size`. Bila ya, PHP sudah membuang semua isian, sehingga token juga hilang. Permintaan itu dijawab sebagai kiriman terlalu besar (`11` GAL-11), bukan galat CSRF.
2. Permintaan latar belakang yang tokennya ditolak dijawab 403 `csrf` beserta token baru (`10` API-04).
3. Kiriman formulir yang tokennya ditolak dialihkan dengan 303 ke halaman asal, yaitu `Referer` bila berasal dari aplikasi sendiri, atau ke halaman awal area bila tidak. Isian formulir dikembalikan, kecuali password, PIN, token, dan file. Pesan galat menjelaskan bahwa halaman sudah terlalu lama dibuka (`11` GAL-06).
4. Penolakan token CSRF tidak dicatat di `log_aktivitas`, dan dicatat di log aplikasi tingkat `info`, yang hanya tercatat di lingkungan pengembangan (`11` GAL-18).

## 10. HTTPS, cookie, dan header

| ID | Ketentuan | Status |
|---|---|---|
| SEC-32 | **HTTPS.** Semua alamat memakai HTTPS (NFR-06). Nginx mengalihkan HTTP ke HTTPS dengan 301, dan hanya menerima TLS 1.2 dan 1.3. Sertifikat dari Let's Encrypt diperbarui otomatis (`07` §4.1). `Config\App::$baseURL` berawalan `https://`, dan `forceGlobalSecureRequests` dinyalakan sebagai pengaman kedua. Laragon memakai HTTPS lokal `https://spensada.test` (`07` §15). | DECISION (HTTPS, NFR-06); RECOMMENDATION (rincian) |
| SEC-33 | **Cookie.** Lihat tabel di bawah. `Config\Cookie::$secure` diubah menjadi `true` di semua lingkungan, karena lingkungan pengembangan juga memakai HTTPS. `Config\Cookie::$httponly` tetap `true`. Prefiks `__Host-` membuat browser menolak cookie itu bila tidak `Secure`, memakai `Domain`, atau `Path`-nya bukan `/`, sehingga subdomain lain tidak dapat menimpanya. | RECOMMENDATION |
| SEC-34 | **Header dari Nginx.** Lihat tabel di bawah. Header ini ditulis di blok `server` dengan `always`, sehingga juga terkirim pada jawaban galat dan berkas statis. Blok `location` yang memakai `add_header` sendiri, misalnya `Retry-After` untuk halaman pemeliharaan, tidak mewarisi header dari blok `server`, sehingga header keamanan diulang di situ, sebaiknya lewat satu file `include`. | RECOMMENDATION |
| SEC-35 | **Filter `keamanan`.** Filter aplikasi yang dipasang di `Config\Filters::$required['after']`, sehingga juga berjalan untuk halaman 404. Filter menulis `Content-Security-Policy` sesuai area (SEC-36) dan `Permissions-Policy` (SEC-37), kecuali bila controller sudah menulis CSP sendiri, misalnya untuk berkas. Handler pengecualian aplikasi menulis kedua header yang sama untuk halaman 403 dan 500, karena filter `after` tidak berjalan saat ada pengecualian yang tidak tertangani. Filter `secureheaders` bawaan CI4 tidak dipakai, karena headernya sudah dikirim Nginx. | RECOMMENDATION |
| SEC-36 | **Content Security Policy.** Lihat tabel di bawah. `Config\App::$CSPEnabled` tetap `false`, karena CSP bawaan CI4 menimpa CSP yang ditulis controller. Aplikasi tidak memakai skrip, gaya, atau atribut event di dalam HTML, sehingga CSP tidak memerlukan `'unsafe-inline'` atau nonce. Lebar bilah kemajuan memakai elemen `<progress>` atau properti CSSOM dari modul JavaScript (`08` UI-74). Di lingkungan pengembangan, CSP dikirim sebagai `Content-Security-Policy-Report-Only`, karena Debug Toolbar CI4 memakai skrip di dalam HTML. | RECOMMENDATION |
| SEC-37 | **Permissions-Policy.** Kiosk: `camera=(self), microphone=(), geolocation=(), payment=(), usb=()`. Area lain: `camera=(), microphone=(), geolocation=(), payment=(), usb=()`. Unggah foto dari ponsel tetap dapat memakai kamera lewat pemilih file, karena pemilih file tidak memerlukan izin kamera halaman. | RECOMMENDATION |
| SEC-38 | **Cache halaman.** Halaman, fragmen, dan JSON aplikasi memakai `Cache-Control: no-store` bawaan respons CI4, sehingga data siswa tidak tertinggal di cache browser perangkat bersama, termasuk lewat tombol kembali. Pengecualiannya logo (`09` §13) dan berkas statis di `public/`, yang tidak memuat data siswa. Aset kiosk disimpan Service Worker per versi (`07` ARS-22). Foto kecil tetap `no-store` (SEC-52). | RECOMMENDATION |
| SEC-39 | **Pengalihan yang aman.** Tujuan setelah login (`09` RT-18), parameter `kembali` (RT-10), dan pengalihan galat CSRF (SEC-30) hanya menerima path di aplikasi sendiri, yang diawali satu `/` dan bukan `//` atau `/\`. Tujuan lain diganti halaman awal area. | RECOMMENDATION |
| SEC-40 | **Host.** Nginx hanya melayani nama host aplikasi. Permintaan dengan nama host lain dijawab blok `server` bawaan yang menutup koneksi (`return 444`). Blok bawaan untuk HTTPS memakai `ssl_reject_handshake on`, agar tidak memerlukan sertifikat dan tidak membocorkan nama host aplikasi. `Config\App::$allowedHostnames` kosong, sehingga `base_url()` tidak memakai header `Host` dari permintaan. | RECOMMENDATION |

Cookie aplikasi (SEC-33):

| Cookie | Isi | Path | SameSite | Masa berlaku | Rujukan |
|---|---|---|---|---|---|
| `__Host-spensada_sesi` | ID sesi CI4 | `/` | Lax | 7 hari, ditulis ulang saat ID sesi dibuat ulang dan pada permintaan latar belakang | SEC-12, SEC-13 |
| `__Host-spensada_csrf` | Nilai dasar token CSRF | `/` | Lax | Sampai browser ditutup | SEC-28 |
| `__Secure-spensada_stasiun` | Login stasiun | `/kiosk` | Strict | 90 hari sejak kontak terakhir | SEC-18, `07` ARS-30 |

Semua cookie bertanda `Secure` dan `HttpOnly`. Cookie CSRF tetap `HttpOnly`, karena JavaScript membaca token dari halaman, bukan dari cookie. Cookie sesi memakai `SameSite=Lax`, bukan `Strict`, agar tautan dari pesan WhatsApp atau email ke panel tetap membuka halaman dalam keadaan login. Prefiks cookie CI4 (`Config\Cookie::$prefix`) tetap kosong, dan prefiks `__Host-` atau `__Secure-` ditulis di nama cookie, karena sesi CI4 tidak memakai prefiks itu.

Header dari Nginx (SEC-34):

| Header | Nilai | Keterangan |
|---|---|---|
| `Strict-Transport-Security` | `max-age=31536000` | Satu tahun. Tanpa `includeSubDomains` dan `preload`, karena subdomain lain milik sekolah tidak dikelola aplikasi ini. Dikirim hanya lewat HTTPS. |
| `X-Content-Type-Options` | `nosniff` | |
| `Referrer-Policy` | `same-origin` | Alamat halaman, yang dapat memuat ID siswa, tidak dikirim ke situs lain. |
| `X-Frame-Options` | `SAMEORIGIN` | Untuk browser lama. Browser baru memakai `frame-ancestors` di CSP. |
| `Cross-Origin-Opener-Policy` | `same-origin` | |
| `Cross-Origin-Resource-Policy` | `same-origin` | Berkas dan foto tidak dapat disematkan situs lain. |

`server_tokens off` menyembunyikan versi Nginx, dan `expose_php = Off` menghapus header `X-Powered-By` (SEC-71).

Content Security Policy (SEC-36):

| Jawaban | Kebijakan |
|---|---|
| Panel, portal, akun, publik, dan halaman galat | `default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' blob: data:; font-src 'self'; connect-src 'self'; frame-src 'self'; object-src 'none'; worker-src 'none'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'` |
| Kiosk (`/kiosk`) | Sama dengan di atas, dengan `script-src 'self' 'wasm-unsafe-eval'`, `worker-src 'self'`, dan tambahan `manifest-src 'self'`. `'wasm-unsafe-eval'` diperlukan pembaca QR WebAssembly (`07` ARS-10), dan `worker-src` untuk Service Worker dan Web Worker pembaca QR. |
| Berkas foto dan logo | `sandbox; default-src 'none'; frame-ancestors 'none'` |
| Berkas lampiran | `sandbox; default-src 'none'; frame-ancestors 'self'`, agar PDF dapat tampil di bingkai halaman detail izin (SEC-52). |

`img-src blob:` dipakai pratinjau foto sebelum diunggah. `img-src data:` dipakai ikon SVG bawaan Bootstrap yang ditulis di CSS-nya, misalnya panah `select`, centang, tombol tutup, dan ikon menu lipat (`08` UI-73, keputusan pemilik 2026-10-10). Gambar `data:` tidak dapat menjalankan skrip. `frame-src 'self'` dipakai bingkai pratinjau lampiran PDF. Komponen JavaScript Bootstrap mengatur posisi lewat properti CSSOM, yang tidak dibatasi `style-src`, sehingga CSP tetap tanpa `'unsafe-inline'`.

## 11. Pencegahan injeksi

| ID | Ketentuan | Status |
|---|---|---|
| SEC-41 | **SQL.** Semua query memakai Query Builder atau binding parameter CI4. Nilai dari pengguna tidak pernah digabung ke teks SQL. Nama kolom untuk urutan dan saringan diambil dari daftar yang diizinkan di controller, bukan langsung dari parameter. Klausa `LIKE` memakai `like()` Query Builder, yang meloloskan `%` dan `_`. | RECOMMENDATION |
| SEC-42 | **HTML.** Semua keluaran di view diloloskan dengan `esc()`, sesuai konteksnya (`html`, `attr`, `url`, atau `js`). Teks dari pengguna, misalnya keterangan izin dan alasan koreksi, ditampilkan sebagai teks biasa dengan baris baru, bukan sebagai HTML. Aplikasi tidak menerima HTML dari pengguna. Pratinjau di JavaScript memakai `textContent`, bukan `innerHTML`, kecuali untuk fragmen dari server sendiri (`10` §6.1). | RECOMMENDATION |
| SEC-43 | **Karakter tidak sah.** Filter `invalidchars` bawaan CI4 dinyalakan global, kecuali untuk endpoint JSON (`/kiosk/api/…`). Filter ini menolak isian GET, POST, dan cookie yang bukan UTF-8 sah, atau yang memuat karakter kontrol selain baris baru dan tab, dengan status 400 (`11` GAL-02). Filter ini membaca badan permintaan dengan `parse_str()`, sehingga tidak memeriksa JSON dengan benar: karakter kontrol yang ditulis `\u0001` lolos, sedangkan teks seperti `%E9` di dalam JSON dapat menolak seluruh kiriman sinkron. Karena itu badan JSON diperiksa setelah `json_decode()`, per isian, dengan aturan yang sama (`11` VAL-14). Pembersihan isian lain mengikuti `11` VAL-02. | RECOMMENDATION |
| SEC-44 | **Mass assignment.** Setiap Model CI4 memakai `$allowedFields`. Kolom yang ditulis service, misalnya `status`, `password_hash`, `wajib_ganti_password`, dan `login_stasiun_id`, tidak diisi langsung dari input formulir. Controller hanya meneruskan isian yang disebut di aturan validasinya (`11` VAL-04). | RECOMMENDATION |
| SEC-45 | **Path file.** Nama file di disk selalu acak (`07` ARS-51). Nama asli file hanya disimpan sebagai teks dan dipakai di `Content-Disposition` setelah dibersihkan (SEC-50). Path tidak pernah dibuat dari input pengguna. Token `tmp/<token>/` divalidasi sebagai 32 karakter heksadesimal milik akun itu (`07` ARS-55). | RECOMMENDATION |
| SEC-46 | **Rumus di file spreadsheet.** Saat file XLSX dibuat, semua sel teks ditulis dengan `setCellValueExplicit(…, DataType::TYPE_STRING)`, sehingga teks yang diawali `=`, `+`, `-`, `@`, tab, atau carriage return tidak menjadi rumus. Sel itu juga diberi gaya `quotePrefix`, agar Excel tetap menganggapnya teks setelah sel diedit. Tanda petik tidak ditulis ke isi sel XLSX, karena akan menjadi bagian dari teks. Di file CSV, yang tidak memiliki tipe sel, teks seperti itu diberi awalan petik tunggal. Aturan ini berlaku untuk export (`13` IE-08, IE-09) dan file baris gagal import (`13` IM-07). Saat import, sel XLSX yang berisi rumus ditolak sebagai baris gagal (`11` §5.7), sedangkan teks biasa yang diawali tanda rumus diterima sebagai teks. | RECOMMENDATION |
| SEC-47 | **XML dan arsip.** XLSX dibaca dengan PhpSpreadsheet, yang menolak entitas eksternal XML. ZIP foto massal diperiksa sebelum diekstrak: jumlah entri, ukuran total setelah diekstrak, nama entri yang memuat `..` atau path absolut, dan symlink (SEC-49, `07` ARS-54). Entri hanya diekstrak dengan nama acak ke `tmp/<token>/`. | RECOMMENDATION |
| SEC-48 | **Perintah sistem.** Aplikasi tidak menjalankan perintah shell dari permintaan web. Fungsi `exec`, `shell_exec`, `system`, `passthru`, `proc_open`, dan `popen` dimatikan di proses PHP web (php-cgi, SEC-71). Perintah CLI dijalankan dari terminal atau tugas terjadwal. | RECOMMENDATION |

## 12. File unggahan

| ID | Ketentuan | Status |
|---|---|---|
| SEC-49 | **Format dan ukuran.** Lihat tabel di bawah. Pesan galatnya di `11` VAL-28. Batas PHP dan Nginx mengikuti unggahan terbesar, yaitu foto massal 100 MB, dengan sedikit ruang untuk badan multipart (SEC-71). Batas per jenis unggahan diperiksa aplikasi, sehingga file lampiran 50 MB tetap ditolak meskipun lolos batas PHP. | DECISION (Paket 10 MB); RECOMMENDATION (batas piksel, baris, dan isi ZIP) |
| SEC-50 | **Pemeriksaan isi.** Tipe file ditentukan dari isinya dengan `finfo` (aturan `mime_in` CI4), dan ekstensi nama file harus sesuai dengan tipe itu (`ext_in`). Gambar juga dibaca dengan `getimagesize()` sebelum dimuat GD, untuk memeriksa batas piksel (`07` ARS-53). PDF harus diawali `%PDF-`. Nama asli file dibersihkan sebelum disimpan: path dibuang, karakter kontrol dan karakter `"`, `\`, `/` diganti garis bawah, dan panjangnya dibatasi 255 byte. | RECOMMENDATION |
| SEC-51 | **Penyimpanan ulang gambar.** Foto siswa dan logo diproses ulang sesuai `07` ARS-53. Lampiran gambar juga disimpan ulang: diputar sesuai EXIF, diperkecil hanya bila sisi terpanjangnya lebih dari 2.000 px, diberi latar putih bila transparan, lalu disimpan sebagai JPEG kualitas 85. Logo diperkecil agar muat dalam 512×512 px dan disimpan sebagai PNG, agar transparansinya tetap ada. Penyimpanan ulang membuang metadata, termasuk lokasi GPS dari foto ponsel, dan membuang data tersembunyi yang disisipkan di file gambar. PDF disimpan apa adanya. | RECOMMENDATION |
| SEC-52 | **Penyajian berkas.** Lihat tabel di bawah. Lampiran gambar dan PDF tampil di browser, dan tautan "Unduh" memakai `?unduh=1` dengan `Content-Disposition: attachment`. Halaman detail izin menampilkan PDF di `<iframe>`, bukan `<embed>` atau `<object>`, karena CSP halaman memakai `object-src 'none'`. Bingkai ini diuji di Chromium 141 dengan header berkas di bawah, dan PDF tampil. Pengujian diulang di Chrome dan Edge versi yang dipakai sekolah sebelum uji coba R1. Bila PDF tidak tampil karena `sandbox`, lampiran PDF disajikan tanpa `sandbox`, dengan `default-src 'none'; frame-ancestors 'self'`. Penampil PDF Chromium berjalan di proses tersendiri, sehingga skrip di PDF tetap tidak berjalan di domain aplikasi. Browser ponsel yang tidak menampilkan PDF di bingkai memakai tautan "Buka" dan "Unduh" di bawahnya. Nama file unduhan memakai nama asli yang sudah dibersihkan, dengan `filename*` UTF-8. | RECOMMENDATION |
| SEC-53 | **Tanpa pemindai virus di R1.** Gambar tidak dapat membawa kode yang berjalan, karena disimpan ulang. PDF disajikan dengan CSP `sandbox` dan dibuka di penampil PDF browser, sehingga skrip di PDF tidak berjalan di domain aplikasi. Staf dianjurkan membuka lampiran di browser, bukan mengunduhnya ke aplikasi PDF di komputer. Pemindai virus, misalnya ClamAV, ditinjau bila format lain diizinkan. | RECOMMENDATION |

Format dan ukuran unggahan (SEC-49):

| Unggahan | Format | Ukuran file | Batas lain | Fitur |
|---|---|---|---|---|
| Foto siswa | JPG, PNG, WebP | 10 MB | Paling besar 24 megapiksel (`07` ARS-53) | FS-MD-07 |
| Logo sekolah | JPG, PNG, WebP | 10 MB | Paling besar 24 megapiksel | FS-MD-01 |
| Lampiran izin | JPG, PNG, WebP, PDF | 10 MB per file | Paling banyak 3 file per data atau per kelompok (`06` §10.4). Gambar paling besar 24 megapiksel. | FS-IZN-01 s.d. FS-IZN-03 |
| Import siswa dan import penempatan | XLSX, CSV | 5 MB | Paling banyak 2.000 baris data. Isi XLSX setelah diekstrak paling besar 50 MB. Hanya lembar pertama yang dibaca. | FS-MD-05, FS-MD-06, `13` §6 dan §7 |
| Foto massal | JPG, PNG, WebP, atau satu ZIP berisi file itu | 100 MB per unggahan | Paling banyak 100 file per unggahan. ZIP paling banyak 2.000 entri, dengan isi setelah diekstrak paling besar 500 MB. Setiap foto paling besar 10 MB dan 24 megapiksel. | FS-MD-08, `07` ARS-54 |

HEIC dari iPhone tidak diterima, karena GD tidak dapat membacanya. iPhone mengubah foto menjadi JPG saat diunggah lewat pemilih file browser. Bila foto tetap ditolak, pengguna memotret layar atau mengubah pengaturan kamera ke "Paling Kompatibel".

Header berkas (SEC-52):

| Berkas | `Cache-Control` | `Content-Disposition` | CSP |
|---|---|---|---|
| Foto standar dan foto kecil | `no-store` | `inline` | `sandbox; default-src 'none'; frame-ancestors 'none'` |
| Foto kiosk (`10` EP-KIO-02) | `no-store` | `inline` | Sama dengan foto |
| Lampiran | `no-store` | `inline`, atau `attachment` dengan `?unduh=1` | `sandbox; default-src 'none'; frame-ancestors 'self'` |
| Logo | `public, max-age=86400` | `inline` | Sama dengan foto |
| Template, file baris gagal, dan export | `no-store` | `attachment` | Sama dengan foto |

Semua berkas memakai tipe yang tersimpan, bukan tipe dari nama file, ditambah header dari Nginx (SEC-34). Foto kecil tetap `no-store`, meskipun daftar siswa memuat sampai 50 foto per halaman. Foto kecil berukuran sekitar 5 KB, sehingga satu halaman memuat sekitar 250 KB, dan data anak tidak tertinggal di cache perangkat bersama (`08` §15).

## 13. Pembatasan laju

| ID | Ketentuan | Status |
|---|---|---|
| SEC-54 | **API kiosk.** Lihat tabel di bawah. Batas dihitung per akun stasiun dengan `Throttler` CI4, yang menyimpan hitungannya di cache. Hitungan yang tidak tepat karena cache tidak atomik, atau yang hilang saat cache dikosongkan, tidak berbahaya di sini, karena batas ini hanya menahan kode kiosk yang keliru dan akun stasiun yang disalahgunakan. | RECOMMENDATION |
| SEC-55 | **Jawaban 429.** Permintaan yang melewati batas dijawab 429 `terlalu_sering` dengan header `Retry-After` dalam detik (`10` API-03). Kiosk menunggu selama itu sebelum mengirim lagi, dan scan tetap tersimpan di laptop. Halaman panel menampilkan pesan di `11` GAL-10. | RECOMMENDATION |
| SEC-56 | **Batas di Nginx.** Nginx membatasi `POST /login` per alamat IP, 10 permintaan per detik dengan cadangan 50, dan menjawab 429 dengan halaman statis berbahasa Indonesia. Batas ini menahan banjir permintaan sebelum mencapai PHP. Batas percobaan gagal tetap diperiksa aplikasi (SEC-08). GET `/login` tidak dibatasi. | RECOMMENDATION |

| Endpoint | Batas per akun stasiun | Alasan |
|---|---|---|
| Muat data dan sinkron (`10` EP-KIO-01, EP-KIO-03) | 120 per menit, digabung | Kiosk normal mengirim paling banyak 12 sinkron per menit (`07` ARS-33), ditambah kontak berkala dan muat ulang. |
| Logout (EP-KIO-04) | 10 per menit | |
| Unduh foto (EP-KIO-02) | Tidak dibatasi | Pemuatan pertama mengunduh sampai ±1.000 foto. |

Bantuan formulir di panel, misalnya pencarian siswa (`10` EP-MD-01), dibatasi 120 permintaan per menit per akun dengan cara yang sama. Halaman biasa tidak dibatasi.

Contoh konfigurasi Nginx (SEC-56):

```nginx
# Di blok http
map $request_method $login_post {
    POST    $binary_remote_addr;
    default "";
}
limit_req_zone $login_post zone=login:10m rate=10r/s;

# Di blok server aplikasi
location = /login {
    limit_req zone=login burst=50 nodelay;
    limit_req_status 429;
    error_page 429 /galat/429.html;
    try_files $uri /index.php$is_args$args;
}
```

Kunci kosong pada GET membuat permintaan itu tidak dihitung. File `/galat/429.html` dan `/galat/503.html` adalah halaman statis di `public/galat/`, dengan teks dari `11` GAL-10 dan GAL-17.

## 14. Log aktivitas dan log aplikasi

| ID | Ketentuan | Status |
|---|---|---|
| SEC-57 | **Log aktivitas.** `log_aktivitas` (`06` §5.3) mencatat kejadian keamanan dan administrasi yang tidak dicatat log perubahan presensi (`04` §4.4) atau log data siswa (`06` §12.2). Aplikasi hanya menambah baris, tidak pernah mengubah atau menghapusnya. Entri ditulis di transaksi yang sama dengan perubahan datanya (`07` ARS-43). Entri login gagal dan akses lampiran ditulis di luar transaksi. Isian `data` tidak pernah memuat password, PIN, token, hash, atau isi file. | RECOMMENDATION |
| SEC-58 | **ID permintaan.** Nginx membuat ID untuk setiap permintaan (`$request_id`, 32 karakter heksadesimal), meneruskannya ke PHP sebagai `REQUEST_ID`, dan menulisnya di log akses Nginx. Aplikasi menulis ID itu di setiap baris log aplikasi. Halaman galat 500 menampilkan 8 karakter pertamanya dalam huruf besar sebagai kode laporan (`11` GAL-13), sehingga admin dapat mencari galatnya di log. | RECOMMENDATION |
| SEC-59 | **Jenis log aktivitas.** Lihat tabel di bawah. Labelnya di `09` §14. Kolom `akun_id` diisi akun yang terdampak, dan `rombel_id` diisi rombel yang terdampak bila ada. | RECOMMENDATION |
| SEC-60 | **Akses lampiran.** Setiap pembukaan dan unduhan lampiran oleh akun staf menulis `lampiran_dibuka`, dengan pelaku, waktu, alamat IP, lampiran, data izin atau kelompoknya, siswa, dan apakah lampiran diunduh. Pembukaan ulang lampiran yang sama oleh staf yang sama dalam 10 menit tidak ditulis lagi, kecuali unduhan, yang selalu ditulis, karena penampil PDF dan muat ulang halaman meminta berkas beberapa kali. Siswa yang membuka lampirannya sendiri tidak dicatat. | DECISION (OQ-17); RECOMMENDATION (rincian) |
| SEC-61 | **Log aplikasi.** Log CI4 di `writable/logs/`, satu file per hari. Ambang production 5, yaitu `warning` ke atas (`11` GAL-18). Log tidak memuat isi formulir, password, token, atau cookie. Jejak pengecualian tidak memuat argumen fungsi yang ditandai `#[\SensitiveParameter]` (SEC-07). File log yang lebih tua dari 90 hari dihapus tugas harian (`07` ARS-56). Log akses dan galat Nginx dirotasi `logrotate`, dan disimpan 90 hari. | RECOMMENDATION |
| SEC-62 | **Halaman log aktivitas.** Halaman baca saja bagi pemegang `HA-AKN-08` (`09` HAL-AKN-09). Daftar memuat waktu, jenis, pelaku, akun atau siswa yang terdampak, dan ringkasan, dengan saringan jenis, pelaku, akun, dan rentang tanggal, serta saringan cepat "Akses lampiran", "Login", dan "Scan ditolak server". Detail memuat isian `data` dalam bentuk tabel, bukan JSON mentah. | RECOMMENDATION |

Jenis log aktivitas (SEC-59):

| Jenis | Kapan | Pelaku | Isi `data` |
|---|---|---|---|
| `login_berhasil` | Login berhasil (SEC-01) | Akun itu | Jenis akun. Untuk akun stasiun, ID login stasiun yang baru. |
| `login_gagal` | Login gagal | Kosong; `akun_id` diisi bila identitasnya cocok dengan akun | Alasan: `password_salah`, `akun_tidak_ada`, `akun_nonaktif`, atau `belum_aktif`. Identitas yang tidak cocok dengan akun tidak dicatat. |
| `login_dikunci` | Kunci identitas atau IP mulai berlaku (SEC-08) | Kosong | Jenis kunci (`pendek`, `panjang`, atau `ip`) dan waktu berakhir. |
| `kunci_login_dibuka` | Admin atau wali kelas membuka kunci (SEC-11) | Pembuka | — |
| `password_diganti` | Pengguna mengganti password sendiri | Akun itu | Apakah penggantian wajib. |
| `password_direset` | Admin atau wali kelas mereset password | Pereset | — |
| `slip_dicetak` | Slip akun siswa dibuat (FS-AKN-05) | Pembuat | Jumlah akun dan daftar ID akun. `rombel_id` diisi. |
| `akun_dibuat`, `akun_diubah` | Akun staf atau stasiun dibuat atau diubah | Admin | Isian yang berubah, data lama dan baru, tanpa password. |
| `role_diubah` | Role diberikan atau dicabut | Admin | Role yang ditambah dan dicabut. |
| `akun_dinonaktifkan`, `akun_diaktifkan` | Akun staf atau stasiun dinonaktifkan atau diaktifkan | Admin | Untuk akun stasiun, jumlah scan belum tersinkron yang terakhir dilaporkan. |
| `kredensial_stasiun_diganti` | Admin mengganti kredensial stasiun | Admin | — |
| `login_stasiun_berpindah` | Akun stasiun login di laptop lain (SEC-19) | Akun stasiun | ID login lama dan baru. |
| `pin_kiosk_diubah` | PIN petugas diatur atau diganti (SEC-21) | Admin | Apakah PIN baru pertama kali diatur. |
| `admin_pertama_dibuat`, `admin_dipulihkan` | Perintah `admin:pertama` atau `admin:pulihkan` (`07` ARS-49) | Kosong | Username. |
| `pengaturan_diubah` | Identitas sekolah, logo, atau pengaturan lain di `pengaturan` diubah (FS-MD-01), kecuali yang dicatat di log perubahan presensi (`04` §4.4) | Admin | Kunci, nilai lama, dan nilai baru. Untuk logo, hanya penanda bahwa logo diganti. |
| `tahun_ajaran_diubah` | Tahun ajaran dibuat, diubah, atau diaktifkan (FS-MD-02) | Admin | Isian yang berubah. Perubahan tanggal semester dicatat di log perubahan presensi. |
| `rombel_diubah` | Rombel dibuat, diubah, atau dihapus (FS-MD-03) | Admin | Isian yang berubah. |
| `wali_kelas_diubah` | Wali kelas rombel diganti (FS-MD-03) | Admin | Wali kelas lama dan baru. `rombel_id` diisi. |
| `atribut_siswa_diubah` | Definisi atribut tambahan dibuat, diubah, atau dihapus (FS-MD-09) | Admin | Isian yang berubah. |
| `import_siswa`, `import_penempatan` | Import dikonfirmasi (FS-MD-05, FS-MD-06) | Admin | Nama file asli, jumlah baris berhasil, gagal, dan dilewati, serta penanda kelompok log data siswa. |
| `penempatan_massal` | Penempatan per rombel asal dikonfirmasi (FS-MD-05) | Admin | Rombel asal dan tujuan, tanggal mulai, dan jumlah siswa. |
| `foto_massal` | Foto massal selesai (FS-MD-08) | Admin | Jumlah foto disimpan, dilewati, dan gagal. |
| `lampiran_dibuka` | Staf membuka atau mengunduh lampiran (SEC-60) | Staf | ID lampiran, ID izin atau kelompok, ID siswa bila satu siswa, dan `unduh`. |
| `ekspor` (R2) | File laporan dibuat (`13` IE-13) | Pembuat | Laporan, format, cakupan, rentang, dan jumlah baris. `rombel_id` diisi bila satu rombel. |
| `scan_ditolak_server` | Scan ditolak saat sinkron (SEC-24) | Akun stasiun | Kode tolak, UUID, NISN, jam scan, data mentah paling besar 1 KB, dan `sha1` data mentah lengkap untuk mencegah entri ganda (SEC-24). |

Penonaktifan dan pengaktifan siswa, perubahan data siswa, foto, dan penempatan dicatat di log data siswa, bukan di sini. Penonaktifan akun siswa mengikuti status siswa (`02` §2 butir 2), sehingga tidak menulis entri di log aktivitas.

## 15. Data pribadi

| ID | Ketentuan | Status |
|---|---|---|
| SEC-63 | **Data pribadi yang disimpan.** Lihat tabel di bawah. Data siswa adalah data anak, dan surat sakit adalah data kesehatan, keduanya data pribadi spesifik menurut UU 27/2022 (R-17, NFR-10). | RECOMMENDATION |
| SEC-64 | **Data seperlunya.** Aplikasi hanya menyimpan data yang dipakai fitur. NIK, agama, dan data keluarga selain nama dan nomor WA orang tua/wali tidak disimpan. Tanggal lahir, alamat, dan nama orang tua/wali bersifat opsional (`13` IM-01). Atribut tambahan siswa (FS-MD-09) tidak dipakai untuk data kesehatan atau data pribadi spesifik lain. Panduan admin menyebut larangan ini. Kiosk hanya memuat data minimal (FS-KIO-01 butir 3), dan halaman publik tidak menampilkan data individu (NFR-10). | RECOMMENDATION |
| SEC-65 | **Akses dan jejak.** Akses data mengikuti hak dan cakupan di `02` (SEC-25). Akses ke data kesehatan, yaitu lampiran surat, dicatat (SEC-60). File export dicatat (`13` IE-13). Data pribadi tidak dikirim ke layanan pihak ketiga, kecuali nomor WA dan isi pesan ke gateway WhatsApp di R2 (OQ-10). Halaman tidak memuat font, skrip, atau gambar dari server lain (SEC-36). | RECOMMENDATION |
| SEC-66 | **Retensi.** Belum ada penghapusan data presensi, scan, izin, lampiran, log, dan data siswa, termasuk siswa yang sudah lulus, sampai sekolah menetapkan kebijakan datanya (OQ-18, `06` DB-13). Kebijakan itu menetapkan masa simpan setiap jenis data dan cara penghapusannya. Penghapusannya dirancang setelah kebijakan ada, karena menyentuh relasi, log, dan backup. | DECISION |
| SEC-67 | **Pembersihan data teknis.** Data teknis dibersihkan otomatis oleh tugas harian (`07` ARS-56). Lihat tabel di bawah. | RECOMMENDATION |
| SEC-68 | **Kebijakan data sekolah (OQ-18).** Sekolah menetapkan, sebaiknya bersama dinas pendidikan: masa simpan data, pemberitahuan privasi bagi siswa dan orang tua/wali, dasar pemrosesan data anak termasuk persetujuan orang tua/wali bila diperlukan, dan penanggung jawab data di sekolah. Aplikasi menampilkan pemberitahuan privasi di halaman login dan portal siswa, dari kunci `pengaturan.privasi_teks` (`06` §6.1). Teks bawaannya sudah disiapkan (2026-10-10), dan sekolah memeriksa atau mengubahnya. Masa simpan, dasar pemrosesan, dan penanggung jawab data tetap ditetapkan sekolah sebelum uji coba R1. | RECOMMENDATION |
| SEC-69 | **Perangkat dan kertas.** Laptop stasiun yang tidak dipakai lagi lebih dulu disinkronkan, lalu akun stasiunnya dinonaktifkan agar data lokal terhapus, dan profil browser kiosk dihapus (R-07). Slip akun dibagikan langsung ke siswa, dan slip yang tidak terbagi dimusnahkan. File export dan file import di komputer staf menjadi tanggung jawab penggunanya, dan panduan staf memintanya dihapus setelah dipakai. | RECOMMENDATION |

Data pribadi yang disimpan (SEC-63):

| Kelompok | Contoh | Tempat | Dibaca oleh |
|---|---|---|---|
| Identitas siswa | NISN, NIS, nama, jenis kelamin, tanggal lahir, alamat | `siswa` | Staf sesuai cakupan, siswa itu sendiri |
| Kontak orang tua/wali | Nama dan nomor WA | `siswa` | Staf sesuai cakupan; gateway WA di R2 |
| Foto siswa | Foto standar, kiosk, dan kecil | `writable/uploads/foto/`, IndexedDB laptop stasiun | Staf sesuai cakupan, siswa itu sendiri, kiosk |
| Kehadiran | Scan, status harian, presensi manual, koreksi | Tabel presensi, IndexedDB laptop stasiun | Staf sesuai cakupan, siswa itu sendiri |
| Izin dan surat | Keterangan izin dan lampiran surat sakit | `izin`, `lampiran`, `writable/uploads/lampiran/` | Staf sesuai `HA-IZN-05`, siswa itu sendiri |
| Akun | Username, hash password, waktu login | `akun` | Admin, wali kelas untuk siswa rombelnya |
| Jejak | Alamat IP, pelaku, dan waktu tindakan | `log_aktivitas`, log presensi, log data siswa, log server | Admin, staf sesuai hak log |
| Data staf | Nama, username, NIP (R2) | `akun` | Admin; nama tampil di log dan PDF |

Pembersihan data teknis (SEC-67):

| Data | Dihapus setelah | Rujukan |
|---|---|---|
| Log aplikasi CI4 dan log Nginx | 90 hari | SEC-61 |
| `percobaan_login` | 24 jam | SEC-09 |
| File sesi | 7 hari tanpa perubahan | SEC-17 |
| Folder `tmp/<token>/` | 24 jam | `07` ARS-55 |
| Antrean hitung ulang yang selesai | 7 hari | `07` ARS-36 |
| File foto dan lampiran tanpa rujukan | 24 jam | `07` ARS-43 |
| Backup | Sesuai SEC-75 | SEC-75 |

## 16. Server, rahasia, dan backup

| ID | Ketentuan | Status |
|---|---|---|
| SEC-70 | **Akses server Windows.** Akses jarak jauh memakai OpenSSH Server bawaan Windows dengan kunci saja (`PasswordAuthentication no` di `C:\ProgramData\ssh\sshd_config`), hanya untuk akun pengelola. Akun bawaan `Administrator` dinonaktifkan. Remote Desktop tidak dibuka ke internet. Windows Defender Firewall hanya membuka port 80 dan 443 untuk umum, dan port 22 bila mungkin hanya dari alamat IP pengelola. Kebijakan penguncian akun Windows mengunci akun setelah 10 kali login gagal selama 15 menit. Pembaruan Windows dipasang otomatis dengan jam aktif 06.00–17.00, sehingga restart terjadi di luar jam sekolah. Nginx, PHP, dan MySQL di Laragon tidak diperbarui Windows Update, sehingga pengelola memeriksa rilis keamanannya setiap bulan (`07` ARS-02). Windows Defender Antivirus tetap aktif. MySQL hanya mendengarkan `127.0.0.1`. Hanya pengelola yang ditunjuk sekolah memegang akses server. Langkahnya di `07` §16.1. | DECISION (server Windows, keputusan pemilik 2026-10-10); RECOMMENDATION (rincian) |
| SEC-71 | **PHP untuk web.** Lihat tabel di bawah. Pengaturan ditulis di `php-web.ini` yang dipakai proses php-cgi (`07` §16.1 langkah 9). Nilai ini ditambahkan ke `07` ARS-04 dan diperiksa `aplikasi:cek` (`07` ARS-57). | DECISION (batas unggah dari Paket 10 MB); RECOMMENDATION (nilai lain) |
| SEC-72 | **Hak file.** Pembagian akun pengelola dan akun layanan `spensada` mengikuti `07` §16. Hak file NTFS diatur dengan `icacls`: `writable/`, termasuk sesi, log, dan unggahan, hanya dapat dibaca dan diubah `spensada`, pengelola, dan SYSTEM, dengan pewarisan hak ke file baru, sehingga file yang dibuat perintah `spark` pengelola tetap dapat diubah aplikasi. Folder kode hanya dapat dibaca `spensada`. Akun lain di server tidak memiliki hak ke folder aplikasi. Nginx tidak pernah menyajikan `writable/`, karena document root-nya `public/`. | RECOMMENDATION |
| SEC-73 | **Rahasia.** Kunci enkripsi aplikasi, password database aplikasi, dan kredensial gateway WhatsApp di R2 disimpan di `.env` production, yang hanya dapat diubah pengelola dan dibaca akun layanan (`07` §16.1 langkah 8), tidak di repository dan tidak di tabel `pengaturan` (`06` §6.1). `.env` dicadangkan terpisah dan terenkripsi (SEC-75). Penggantian kunci enkripsi membuat semua kiosk login ulang dan membatalkan hash identitas di `percobaan_login`, tetapi tidak memengaruhi password dan PIN. | RECOMMENDATION |
| SEC-74 | **User database.** Aplikasi memakai user MySQL `spensada_app` yang hanya berhak `SELECT`, `INSERT`, `UPDATE`, dan `DELETE` di database `spensada`. Migration memakai user `spensada_migrasi` yang juga berhak mengubah struktur, lewat grup koneksi `migrasi` di `app/Config/Database.php`: `php spark migrate -g migrasi` dan `php spark migrate:status -g migrasi`. `migrate:rollback` di CI4 4.7.4 tidak memiliki pilihan `-g`, sehingga grupnya dipilih dengan variabel lingkungan `database_defaultGroup=migrasi`. Password `spensada_migrasi` tidak ditulis di `.env`, tetapi diberikan sebagai variabel lingkungan `database_migrasi_password`, dari file di `C:\spensada\rahasia\` yang hanya dapat dibaca pengelola. Pengelola memuatnya ke PowerShell yang sedang dipakai, misalnya `$env:database_migrasi_password = Get-Content C:\spensada\rahasia\migrasi.txt`, menjalankan perintah migration, lalu menghapusnya dengan `Remove-Item Env:database_migrasi_password`. Password tidak ditulis di baris perintah, agar tidak terlihat di daftar proses. Backup memakai user `spensada_backup` yang hanya dapat membaca (`07` §16.1 langkah 5). Dengan begitu, celah di aplikasi tidak dapat mengubah atau menghapus struktur tabel. | RECOMMENDATION |
| SEC-75 | **Backup.** Lihat butir di bawah tabel. | DECISION (backup tidak dihapus sebelum kebijakan data, OQ-18, kecuali rotasi di butir 3); RECOMMENDATION (rincian) |
| SEC-76 | **Dependensi.** Versi framework dan library dikunci di `composer.lock` (`07` ARS-07). Sebelum setiap rilis, pengembang menjalankan `composer audit` dan memperbarui paket yang memiliki celah keamanan. Library JavaScript kiosk disimpan dengan nomor versi di path (`07` ARS-10). Pengumuman keamanan CodeIgniter dan PhpSpreadsheet diikuti lewat GitHub Security Advisories. | RECOMMENDATION |
| SEC-77 | **Mode production.** `CI_ENVIRONMENT = production`, `display_errors` mati (SEC-71), dan Debug Toolbar tidak dimuat. Halaman galat production tidak memuat pesan pengecualian, jejak, atau path (`11` GAL-13). `aplikasi:cek` memeriksa `CI_ENVIRONMENT`, sehingga server yang tertinggal dalam mode `development` terlihat di halaman pemeriksaan sistem. | RECOMMENDATION |
| SEC-78 | **Pemeliharaan.** Saat rilis yang mengubah struktur database, Nginx menjawab semua permintaan aplikasi dengan halaman pemeliharaan 503 selama file penanda `C:\spensada\pemeliharaan` ada. API kiosk menerima 503 dan mengulang nanti, sehingga scan tetap tersimpan di laptop (`10` API-03). Langkahnya ditambahkan ke `07` §16.2, dan teks halamannya di `11` GAL-17. | RECOMMENDATION |

PHP untuk web (SEC-71):

| Pengaturan | Nilai | Alasan |
|---|---|---|
| `upload_max_filesize` | 100M | Foto massal (SEC-49). Unggahan lain dibatasi aplikasi. |
| `post_max_size`, Nginx `client_max_body_size` | 110M | Sedikit di atas 100 MB, karena badan multipart memuat batas dan header setiap file. Batas 100 MB per unggahan tetap diperiksa aplikasi. |
| `max_file_uploads` | 100 | Foto massal tanpa ZIP. |
| `display_errors`, `display_startup_errors` | Off | SEC-77. Galat ditulis ke log. |
| `log_errors` | On | Galat PHP yang tidak tertangkap CI4 ditulis ke log PHP di `C:\spensada\log\php\`. |
| `expose_php` | Off | Tidak mengirim `X-Powered-By`. |
| `allow_url_include` | Off | Bawaan PHP 8.3, ditulis eksplisit. |
| `disable_functions` | `exec, shell_exec, system, passthru, proc_open, popen, pcntl_exec` | SEC-48. Hanya untuk proses php-cgi lewat `php-web.ini`, bukan CLI. |
| `cgi.fix_pathinfo` | 0 | File selain `.php` tidak dijalankan sebagai PHP lewat path tambahan. |
| `session.use_strict_mode`, `session.cookie_secure`, `session.cookie_httponly` | 1 | Pelengkap. Atribut cookie sesi diatur CI4 dari `Config\Cookie`. Pengaman utama sesi tetap regenerasi ID saat login (SEC-13). |

Butir SEC-75:

1. Dump database harian dengan `mysqldump --single-transaction`, dan salinan `writable/uploads/`, dienkripsi dengan `age` memakai kunci publik sebelum meninggalkan server (`07` ARS-06). Kunci privatnya tidak disimpan di server, tetapi dipegang dua orang yang ditunjuk sekolah, misalnya kepala sekolah dan pengelola server, di dua tempat terpisah.
2. Backup disimpan di luar server, di penyimpanan dengan akses tulis yang tidak dapat menghapus backup lama dari server, misalnya bucket object storage dengan kunci akses tanpa hak hapus.
3. Rotasi: 30 backup harian terakhir dan 12 backup bulanan terakhir, yaitu backup hari pertama setiap bulan. Rotasi ini menghapus salinan lama, tidak menghapus data dari database.
4. `.env` dicadangkan terpisah, dienkripsi dengan kunci yang sama, setiap kali berubah.
5. Pemulihan diuji setiap semester ke database sementara `spensada_pulih` di server yang sama, dengan hasil dicatat. Tidak ada server uji terpisah (DECISION, Session 10). Database itu dan file hasil pemulihan dihapus setelah pengujian, karena berisi data asli (`14` UC-09, RM-18).
6. Backup memuat data anak dan data kesehatan, sehingga mengikuti kebijakan data sekolah setelah OQ-18 terjawab.

## 17. Pengembangan dan pengujian

| ID | Ketentuan | Status |
|---|---|---|
| SEC-79 | **Data uji.** Lingkungan pengembangan dan uji, termasuk production selama uji coba R1 (`14` §11), memakai data buatan dari seeder atau file import fiktif, bukan salinan data asli siswa. Bila salinan data asli diperlukan untuk memeriksa masalah, salinan itu disamarkan lebih dulu: nama, NISN, tanggal lahir, alamat, nomor WA, foto, dan lampiran diganti. | RECOMMENDATION |
| SEC-80 | **Rahasia di repository.** `.env`, dump database, dan isi `writable/` tidak pernah di-commit (`07` ARS-09). Contoh konfigurasi memakai nilai palsu. Fitur secret scanning GitHub dinyalakan untuk repository. | RECOMMENDATION |
| SEC-81 | **Uji keamanan otomatis.** Uji PHPUnit (`07` ARS-58) mencakup: setiap route di `09` ditolak tanpa login dan oleh jenis akun yang salah, data di luar cakupan dijawab 403, permintaan tulis tanpa token CSRF ditolak, pembatasan login dan pembukaan kuncinya, aturan password, sesi yang berakhir setelah 8 jam dan 7 hari, satu login aktif per akun stasiun, header CSP per area dan header berkas, penolakan file berbahaya (file PHP berganti nama `.jpg`, SVG, PDF palsu, ZIP dengan `..`), dan log `lampiran_dibuka`. Daftar route diambil dari `php spark routes`, sehingga route baru tanpa uji akses terlihat. | RECOMMENDATION |
| SEC-82 | **Pemeriksaan saat uji coba.** Selama uji coba R1, sebelum data asli diisi, pengembang memeriksa production dengan daftar periksa: header dan sertifikat HTTPS dari luar, misalnya dengan `curl -I`, cookie bertanda `Secure`, `display_errors` mati, port yang terbuka, akses SSH dan Remote Desktop, dan uji pemindaian dasar OWASP ZAP terhadap production sebelum data asli diisi, karena tidak ada server uji terpisah (DECISION, Session 10). Daftar periksa ini adalah `14` UC-10, dan pemulihan backup diuji di `14` UC-09, dan hasilnya dicatat di catatan go-live (`14` §12). | RECOMMENDATION |

## 18. Penanganan insiden

| ID | Ketentuan | Status |
|---|---|---|
| SEC-83 | **Langkah awal.** Bila ada dugaan kebocoran atau penyalahgunaan, misalnya akun staf dipakai orang lain, laptop stasiun hilang, atau data siswa beredar: (1) nonaktifkan akun atau akun stasiun yang terlibat; (2) reset password akun yang diduga bocor; (3) bila server diduga disusupi, ganti kunci enkripsi aplikasi, password database, dan kunci SSH; (4) amankan log aktivitas, log aplikasi, dan log Nginx dengan menyalinnya keluar server; (5) catat kronologi. Admin menelusuri log aktivitas di halaman log aktivitas (`09` HAL-AKN-09). | RECOMMENDATION |
| SEC-84 | **Pemberitahuan.** Kegagalan pelindungan data pribadi diberitahukan secara tertulis kepada siswa dan orang tua/wali yang terdampak, dan kepada lembaga pelindungan data pribadi, paling lambat 3 × 24 jam, sesuai Pasal 46 UU 27/2022. Pemberitahuan memuat data yang terungkap, kapan dan bagaimana terjadi, serta upaya penanganan dan pemulihannya. Penanggung jawab pemberitahuan ditetapkan sekolah di kebijakan datanya (OQ-18). | RECOMMENDATION |

## 19. Traceability

### 19.1 Kebutuhan dan risiko → keamanan

| Kebutuhan atau risiko | Ketentuan |
|---|---|
| NFR-06 (HTTPS) | SEC-32 s.d. SEC-34 |
| NFR-07 (login, CSRF, validasi server) | SEC-01, SEC-12 s.d. SEC-20, SEC-25, SEC-28 s.d. SEC-31, `11` VAL-01 |
| NFR-08 (pembatasan login) | SEC-08 s.d. SEC-11, SEC-56 |
| NFR-10 (privasi) | SEC-23, SEC-49 s.d. SEC-53, SEC-63 s.d. SEC-69 |
| R-07 (data di laptop stasiun) | SEC-21 s.d. SEC-24, SEC-69 |
| R-10 (penyalahgunaan sinkron) | SEC-18 s.d. SEC-20, SEC-24, SEC-54 |
| R-17 (data anak dan kesehatan) | SEC-26, SEC-38, SEC-51, SEC-52, SEC-60, SEC-63 s.d. SEC-69, SEC-75, SEC-84 |
| OQ-17 (akses lampiran) | SEC-26, SEC-60, SEC-62 |
| OQ-18 (kebijakan data sekolah) | SEC-66, SEC-68, SEC-75, SEC-84 |

### 19.2 Fitur → keamanan

| Fitur | Ketentuan |
|---|---|
| FS-AKN-01 | SEC-01, SEC-08 s.d. SEC-17, SEC-29 |
| FS-AKN-02 | SEC-03 s.d. SEC-07 |
| FS-AKN-03 | SEC-05, SEC-11, SEC-27, SEC-59 |
| FS-AKN-04 | SEC-05, SEC-18, SEC-19, SEC-59 |
| FS-AKN-05 | SEC-05, SEC-11, SEC-59, SEC-69 |
| FS-KIO-01 | SEC-21 s.d. SEC-23 |
| FS-KIO-03, FS-KIO-04 | SEC-20, SEC-24, SEC-54, SEC-55 |
| FS-MD-01, FS-MD-07, FS-MD-08 | SEC-49 s.d. SEC-52 |
| FS-MD-05, FS-MD-06 | SEC-46, SEC-47, SEC-49 |
| FS-IZN-01 s.d. FS-IZN-03, FS-IZN-06 | SEC-49 s.d. SEC-53, SEC-60 |
| FS-LAP-05 (R2) | SEC-46, SEC-59 (`ekspor`) |

## 20. Perubahan pada dokumen lain

Perubahan karena keputusan Session 9, termasuk yang ditulis di `11`:

| Dokumen | Versi | Perubahan |
|---|---|---|
| `00-project-overview.md` | 0.9 | Kepala dokumen memuat `11` dan `12`. OQ-17 terjawab, dan OQ-18 (kebijakan data sekolah) ditambahkan. R-07, R-10, dan R-17 diperbarui. Peta dokumen dan progres sesi diperbarui. Glosarium ditambah: kunci login, log aktivitas, PIN petugas, dan kode laporan. Entri sesi login dan scan galat diperbarui. |
| `01-product-requirements.md` | 0.9 | NFR-06, NFR-07, dan NFR-10 merujuk `11` dan `12`. NFR-08 memuat batas percobaan login dan menjadi DECISION. FR-IZN-05 memuat jawaban OQ-17 dan format lampiran. Di §8, OQ-17 diganti OQ-18. Kepala dokumen dan §9 diperbarui. |
| `02-user-roles-and-permissions.md` | 0.7 | Keputusan Session 9 ditambahkan di §1. `HA-AKN-08` ditambahkan di §6.1. `HA-AKN-02` s.d. `HA-AKN-04` memuat buka kunci login, dan `HA-AKN-03` memuat PIN petugas. §2 butir 6, §6.5, §7.2 butir 5, §7.3, §9 (PIN petugas), §10, dan §11 diperbarui. |
| `03-user-flow.md` | 0.8 | §1 merujuk `11` dan `12`. UF-06 (penyiapan laptop, PIN petugas, scan galat, dan satu login aktif), UF-17 (format lampiran), UF-18 (pencatatan akses lampiran), UF-20 (aturan password dan pembatasan login), UF-21 (buka kunci), dan §10 (OQ-18) diperbarui. |
| `04-feature-specification.md` | 0.6 | §2.8 (keputusan Session 9) ditambahkan. §1, §4.3, §4.4 butir 5, §4.9, FS-AKN-01 s.d. FS-AKN-05, FS-MD-01 s.d. FS-MD-03, FS-MD-05 s.d. FS-MD-09, FS-KIO-01, FS-KIO-03, FS-KIO-04, FS-PRS-10, FS-PRS-11, FS-IZN-01 s.d. FS-IZN-04, FS-IZN-06, §12.3, §13, dan §14 diperbarui. Acceptance criteria baru: AC-AKN-01-07, AC-AKN-01-08, AC-AKN-02-05, AC-AKN-04-05, AC-KIO-01-07, AC-KIO-04-07, AC-MD-06-06, AC-IZN-01-08, dan AC-IZN-06-04. |
| `05-business-rules.md` | 0.7 | BR-IZN-12 memuat jawaban OQ-17 dan format lampiran. §1 dan kepala dokumen merujuk `11` dan `12`. Di §16, OQ-17 diganti OQ-18. |
| `06-database-design.md` | 0.5 | §2.7 (keputusan Session 9) ditambahkan. Tabel baru `percobaan_login` (§5.4), sehingga R1 memiliki 33 tabel. Kolom `akun.login_stasiun_id` (§5.1). Kunci `kiosk_pin` di `pengaturan` (§6.1). Daftar jenis `log_aktivitas` (§5.3) merujuk `12` SEC-59. §2.1, DB-03, DB-13, §4.1, §10.4, §13, §15, §16, §17.2, §18, dan §19 diperbarui. |
| `07-system-architecture.md` | 0.4 | §2.6 (keputusan Session 9) ditambahkan. §2.3, ARS-01, ARS-02, ARS-04 (pengaturan PHP dan Nginx), ARS-06 (backup), ARS-11, ARS-13 (filter `keamanan`, pembatasan login, dan `invalidchars`), ARS-21, ARS-23, ARS-29 butir 5, ARS-30, ARS-31, ARS-33, ARS-42, ARS-43, ARS-47, ARS-52 s.d. ARS-54, ARS-56, §16, §17, §18, dan §19 diperbarui. |
| `08-ui-ux-design-system.md` | 0.3 | §2.5 (keputusan Session 9) ditambahkan. UI-28 (galat formulir dan unggahan), UI-31 (menu log aktivitas dan PIN petugas), UI-38 (halaman galat, kode laporan, dan teks bantuan login), UI-48 (PIN petugas), UI-56 (pesan umum), UI-59 (panjang password slip), UI-74 (tanpa atribut `style`), §9.2, §14, §15, dan §16.1 diperbarui. |
| `09-page-and-route-specification.md` | 0.2 | §2.3 (keputusan Session 9) ditambahkan. Halaman baru HAL-AKN-09 (log aktivitas). Route buka kunci login dan PIN petugas. Menu "Log aktivitas". RT-09, RT-10, RT-18, RT-19, HAL-AKN-01, HAL-AKN-04, HAL-AKN-06, HAL-AKN-07, HAL-KIO-02, HAL-IZN-06, §11, §13 (header berkas, unduhan lampiran, dan catatan akses), §14 (label `log_aktivitas.jenis`), §15, §16, dan §17 diperbarui. |
| `10-api-specification.md` | 0.2 | API-03 (`terlalu_sering`, `login_ulang` dengan `alasan`, dan 503 pemeliharaan), API-04 butir 2 dan 6, API-08, §5.2, EP-KIO-01 (isian `pin`), EP-KIO-02 (header berkas), EP-KIO-03 (log scan ditolak, login berpindah, dan batas laju), EP-KIO-04 (PIN petugas), §7, §8, dan §9 diperbarui. |
| `13-reporting-import-export.md` | 0.5 | IE-13 (log `ekspor`), IE-08, IE-09, dan IM-07 (pencegahan rumus), IM-16 baru (batas file import), §6.1, §8 (batas foto massal), dan §9 diperbarui. |

## 21. Pertanyaan terbuka dan nilai yang dipastikan nanti

Session 9 menjawab OQ-17 (§2.1) dan menambah OQ-18. Session 10 menambah OQ-19 (`14` §18). Keputusan pemilik proyek 2026-10-10 menambah OQ-20 (`07` §16.4):

| OQ | Pertanyaan | Dijawab di | Status |
|---|---|---|---|
| OQ-18 | Kebijakan data sekolah: masa simpan setiap jenis data, pemberitahuan privasi bagi siswa dan orang tua/wali, dasar pemrosesan data anak termasuk persetujuan orang tua/wali, dan penanggung jawab data di sekolah (SEC-66, SEC-68). | Sebelum uji coba R1, oleh sekolah | Terbuka |
| OQ-19 | Tempat penyimpanan backup di luar server dan dua pemegang kunci privat (SEC-75). | Sebelum uji coba R1, oleh sekolah dan pengelola server | Terbuka |
| OQ-20 | Lokasi server production Windows, klien ACME untuk sertifikat HTTPS, dan cara menjalankan layanan (`07` §16.4). | Sebelum FASE-09, oleh pemilik proyek | Terbuka |

| Hal | Rujukan | Dipastikan di |
|---|---|---|
| Waktu hash bcrypt cost 11 di server production | SEC-02 | Penyiapan server (FASE-09) |
| Daftar password umum final, termasuk nama sekolah | SEC-04 | Implementasi FS-AKN-02 |
| Batas laju API kiosk dan Nginx | SEC-54, SEC-56 | Uji beban sebelum uji coba R1 |
| Teks pemberitahuan privasi | SEC-68 | Teks bawaan sudah ada (`06` §6.1). Sekolah memeriksanya sebelum uji coba R1 (OQ-18). |
| Tempat penyimpanan backup dan pemegang kunci privat | SEC-75 | Sekolah dan pengelola server, sebelum uji coba R1 (OQ-19); dipasang di `14` GL-04 |
| Daftar periksa keamanan production | SEC-82 | Ditetapkan di Session 10 (`14` UC-10) |

## Riwayat perubahan

| Versi | Tanggal | Perubahan |
|---|---|---|
| 0.1 | 2026-10-05 | Draft awal dari Session 9: keputusan Session 9, model ancaman, autentikasi dan password (`SEC-01` s.d. `SEC-07`), pembatasan login (`SEC-08` s.d. `SEC-11`), sesi (`SEC-12` s.d. `SEC-17`), stasiun dan kiosk termasuk PIN petugas dan satu login aktif (`SEC-18` s.d. `SEC-24`), otorisasi (`SEC-25` s.d. `SEC-27`), CSRF (`SEC-28` s.d. `SEC-31`), HTTPS, cookie, dan header (`SEC-32` s.d. `SEC-40`), pencegahan injeksi (`SEC-41` s.d. `SEC-48`), file unggahan (`SEC-49` s.d. `SEC-53`), pembatasan laju (`SEC-54` s.d. `SEC-56`), log (`SEC-57` s.d. `SEC-62`), data pribadi (`SEC-63` s.d. `SEC-69`), server dan backup (`SEC-70` s.d. `SEC-78`), pengembangan dan pengujian (`SEC-79` s.d. `SEC-82`), penanganan insiden (`SEC-83`, `SEC-84`), traceability, dan perubahan dokumen lain. Menjawab OQ-17 dan menambah OQ-18. |
| 0.2 | 2026-10-05 | Keputusan Session 10 (`14`). SEC-75 butir 5 (pemulihan ke database sementara), SEC-79 (data uji dari file import fiktif), SEC-82 (pemindaian terhadap production sebelum data asli), kepala dokumen, dan §21 (OQ-19) diperbarui. |
| 0.3 | 2026-10-10 | Keputusan pemilik proyek 2026-10-10 (`07` §2.7). Server production Windows: model ancaman, SEC-02, SEC-10 (pengantar alamat IP), SEC-17, SEC-48, SEC-58, SEC-70 (akses server Windows), SEC-71 (`php-web.ini`, `cgi.fix_pathinfo`), SEC-72 (hak file NTFS), SEC-73, SEC-74 (password migration di PowerShell, user `spensada_backup`), SEC-75, SEC-78, SEC-82, dan §21 (OQ-20) diperbarui. Bootstrap 5: SEC-36 (`img-src data:`) diperbarui. |
