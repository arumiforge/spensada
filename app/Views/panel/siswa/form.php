<?php
/**
 * Add a student (docs/09 HAL-MD-05) or edit one (HAL-MD-07): the fields of
 * docs/04 FS-MD-04 and the active extra attributes (FS-MD-09). Add also
 * asks for the class and start date; edit carries `versi` (RT-12).
 *
 * @var string                     $title
 * @var array<string, mixed>|null  $siswa   Null when adding
 * @var list<array<string, mixed>> $atribut Active attributes, with the student's `nilai`
 * @var list<array<string, mixed>> $rombel  Class choices (add only)
 * @var string                     $hariIni Y-m-d
 */
$galat  = validation_errors();
$baru   = $siswa === null;
$labels = [
    'nisn' => 'NISN', 'nama' => 'Nama lengkap', 'rombel_id' => 'Kelas', 'tanggal_mulai' => 'Tanggal mulai aktif',
    'wa_ortu' => 'Nomor WA orang tua/wali', 'nis' => 'NIS', 'jenis_kelamin' => 'Jenis kelamin', 'tanggal_lahir' => 'Tanggal lahir',
    'alamat' => 'Alamat rumah', 'nama_ortu' => 'Nama orang tua/wali',
];
foreach ($atribut as $a) {
    $labels['atribut_' . $a['id']] = $a['label'];
}
$nilai = static function (string $field) use ($siswa): string {
    $stored = $siswa[$field] ?? '';

    return (string) old($field, $field === 'wa_ortu' && $stored !== '' ? format_wa($stored) : $stored, false);
};
$nilaiAtribut = (array) old('atribut', [], false);
// Attributes and states of one field: invalid marker, describedby, error text.
$tanda = static function (string $field, ?string $bantuan = null) use ($galat): string {
    $ids = trim((isset($galat[$field]) ? $field . '-galat ' : '') . ($bantuan ?? ''));

    return (isset($galat[$field]) ? ' is-invalid" aria-invalid="true' : '') . '"' . ($ids === '' ? '' : ' aria-describedby="' . esc($ids, 'attr') . '"');
};
$pesan = static fn (string $field): string => isset($galat[$field]) ? '<div class="invalid-feedback" id="' . esc($field, 'attr') . '-galat">' . esc($galat[$field]) . '</div>' : '';
$opsional = ' <span class="text-body-secondary">(opsional)</span>';
?>
<?= $this->extend('layout/panel') ?>

<?= $this->section('content') ?>
<?= view('komponen/kepala_halaman', ['title' => $title, 'context' => $siswa['nama'] ?? null], ['saveData' => false]) ?>

<?= view('panel/penempatan/_galat', ['galat' => $galat, 'labels' => $labels], ['saveData' => false]) ?>

<div class="row"><div class="col-lg-8 col-xl-6">
<form method="post" action="<?= esc($baru ? url_to('panel.siswa.simpan') : url_to('panel.siswa.perbarui', $siswa['id']), 'attr') ?>" class="card card-body">
    <?= csrf_field() ?>
<?php if (! $baru): ?>
    <input type="hidden" name="_method" value="PATCH">
    <input type="hidden" name="versi" value="<?= esc($siswa['updated_at'], 'attr') ?>">
<?php endif ?>
    <div class="mb-3">
        <label class="form-label" for="nisn">NISN</label>
        <input class="form-control<?= $tanda('nisn', 'nisn-bantuan') ?> type="text" id="nisn" name="nisn" inputmode="numeric" maxlength="10" pattern="[0-9]{10}" required autocomplete="off" value="<?= esc($nilai('nisn'), 'attr') ?>">
        <?= $pesan('nisn') ?>
        <div class="form-text" id="nisn-bantuan"><?= $baru
            ? '10 digit angka. NISN juga menjadi username akun siswa.'
            : '10 digit angka. Bila NISN diubah, username akun siswa ikut berubah, dan QR di kartu harus dicetak ulang dengan NISN baru.' ?></div>
    </div>
    <div class="mb-3">
        <label class="form-label" for="nama">Nama lengkap</label>
        <input class="form-control<?= $tanda('nama') ?> type="text" id="nama" name="nama" maxlength="100" required value="<?= esc($nilai('nama'), 'attr') ?>">
        <?= $pesan('nama') ?>
    </div>
<?php if ($baru): ?>
    <div class="mb-3">
        <label class="form-label" for="rombel_id">Kelas</label>
        <?= view('panel/penempatan/_pilih_kelas', ['name' => 'rombel_id', 'rombel' => $rombel, 'nilai' => (string) old('rombel_id', '', false), 'galat' => $galat['rombel_id'] ?? null], ['saveData' => false]) ?>
    </div>
    <div class="mb-3">
        <label class="form-label" for="tanggal_mulai">Tanggal mulai aktif</label>
        <input class="form-control<?= $tanda('tanggal_mulai', 'tanggal_mulai-bantuan') ?> type="date" id="tanggal_mulai" name="tanggal_mulai" required value="<?= esc((string) old('tanggal_mulai', $hariIni, false), 'attr') ?>">
        <?= $pesan('tanggal_mulai') ?>
        <div class="form-text" id="tanggal_mulai-bantuan">Siswa tidak dihitung Alpa sebelum tanggal ini.</div>
    </div>
<?php endif ?>
    <div class="mb-3">
        <label class="form-label" for="wa_ortu">Nomor WA orang tua/wali<?= $opsional ?></label>
        <input class="form-control<?= $tanda('wa_ortu', 'wa_ortu-bantuan') ?> type="tel" id="wa_ortu" name="wa_ortu" inputmode="tel" maxlength="20" autocomplete="off" value="<?= esc($nilai('wa_ortu'), 'attr') ?>">
        <?= $pesan('wa_ortu') ?>
        <div class="form-text" id="wa_ortu-bantuan">Nomor ponsel yang diawali 08, misalnya 081234567890.</div>
    </div>
    <div class="mb-3">
        <label class="form-label" for="nis">NIS<?= $opsional ?></label>
        <input class="form-control<?= $tanda('nis') ?> type="text" id="nis" name="nis" maxlength="20" autocomplete="off" value="<?= esc($nilai('nis'), 'attr') ?>">
        <?= $pesan('nis') ?>
    </div>
    <div class="mb-3">
        <label class="form-label" for="jenis_kelamin">Jenis kelamin<?= $opsional ?></label>
        <select class="form-select<?= $tanda('jenis_kelamin') ?> id="jenis_kelamin" name="jenis_kelamin">
            <option value="">Belum diisi</option>
<?php foreach (config('Label')->codes['siswa.jenis_kelamin'] as $kode => $nama): ?>
            <option value="<?= esc($kode, 'attr') ?>"<?= $nilai('jenis_kelamin') === $kode ? ' selected' : '' ?>><?= esc($nama) ?></option>
<?php endforeach ?>
        </select>
        <?= $pesan('jenis_kelamin') ?>
    </div>
    <div class="mb-3">
        <label class="form-label" for="tanggal_lahir">Tanggal lahir<?= $opsional ?></label>
        <input class="form-control<?= $tanda('tanggal_lahir') ?> type="date" id="tanggal_lahir" name="tanggal_lahir" max="<?= esc($hariIni, 'attr') ?>" value="<?= esc($nilai('tanggal_lahir'), 'attr') ?>">
        <?= $pesan('tanggal_lahir') ?>
    </div>
    <div class="mb-3">
        <label class="form-label" for="alamat">Alamat rumah<?= $opsional ?></label>
        <textarea class="form-control<?= $tanda('alamat') ?> id="alamat" name="alamat" rows="2" maxlength="255"><?= esc($nilai('alamat')) ?></textarea>
        <?= $pesan('alamat') ?>
    </div>
    <div class="mb-3">
        <label class="form-label" for="nama_ortu">Nama orang tua/wali<?= $opsional ?></label>
        <input class="form-control<?= $tanda('nama_ortu') ?> type="text" id="nama_ortu" name="nama_ortu" maxlength="100" value="<?= esc($nilai('nama_ortu'), 'attr') ?>">
        <?= $pesan('nama_ortu') ?>
    </div>
<?php foreach ($atribut as $a): ?>
<?php
    $field = 'atribut_' . $a['id'];
    $name  = 'atribut[' . $a['id'] . ']';
    $isi   = (string) ($nilaiAtribut[$a['id']] ?? $a['nilai'] ?? '');
?>
    <div class="mb-3">
        <label class="form-label" for="<?= esc($field, 'attr') ?>"><?= esc($a['label']) ?><?= $a['wajib'] ? '' : $opsional ?></label>
<?php if ($a['tipe'] === 'pilihan'): ?>
        <select class="form-select<?= $tanda($field) ?> id="<?= esc($field, 'attr') ?>" name="<?= esc($name, 'attr') ?>"<?= $a['wajib'] ? ' required' : '' ?>>
            <option value="">Belum diisi</option>
<?php foreach ($a['pilihan'] as $pilihan): ?>
            <option value="<?= esc($pilihan, 'attr') ?>"<?= $isi === $pilihan ? ' selected' : '' ?>><?= esc($pilihan) ?></option>
<?php endforeach ?>
        </select>
<?php else: ?>
        <input class="form-control<?= $tanda($field) ?> type="<?= $a['tipe'] === 'tanggal' ? 'date' : 'text' ?>" id="<?= esc($field, 'attr') ?>" name="<?= esc($name, 'attr') ?>"<?= $a['tipe'] === 'angka' ? ' inputmode="decimal"' : '' ?> maxlength="255"<?= $a['wajib'] ? ' required' : '' ?> value="<?= esc($isi, 'attr') ?>">
<?php endif ?>
        <?= $pesan($field) ?>
    </div>
<?php endforeach ?>
<?php if ($baru): ?>
    <p class="form-text">Akun siswa dibuat otomatis dengan username NISN. Akun aktif setelah slip akun dicetak dan siswa login pertama kali.</p>
<?php endif ?>
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-primary" type="submit"><?= view('komponen/ikon', ['name' => 'check'], ['saveData' => false]) ?> Simpan</button>
        <a class="btn btn-outline-primary" href="<?= esc($baru ? url_to('panel.siswa.index') : url_to('panel.siswa.lihat', $siswa['id']), 'attr') ?>">Batal</a>
    </div>
</form>
</div></div>
<?= $this->endSection() ?>
