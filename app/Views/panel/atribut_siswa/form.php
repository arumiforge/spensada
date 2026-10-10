<?php
/**
 * Add or edit an extra student attribute (docs/09 HAL-MD-16, docs/04 FS-MD-09).
 * Kode only when adding; tipe locked once a student has a value (E2).
 * Edit carries `versi` (RT-12).
 *
 * @var string                    $title
 * @var array<string, mixed>|null $atribut Null when adding; from AtributSiswa::cari()
 */
$galat  = validation_errors();
$tipe   = config('Label')->codes['atribut_siswa.tipe'];
$labels = ['label' => 'Label', 'kode' => 'Kode', 'tipe' => 'Tipe', 'pilihan' => 'Pilihan', 'urutan' => 'Urutan'];
$nilai  = static fn (string $field): string => (string) old($field, (string) ($atribut[$field] ?? ''), false);
$isian  = static fn (string $field): string => isset($galat[$field]) ? ' is-invalid" aria-invalid="true" aria-describedby="' . $field . '-galat' : '';
$pesan  = static fn (string $field): string => isset($galat[$field]) ? '<div class="invalid-feedback" id="' . $field . '-galat">' . esc($galat[$field]) . '</div>' : '';
$kunci  = $atribut !== null && (int) $atribut['jumlah_nilai'] > 0;
$pilih  = (string) old('pilihan', implode("\n", $atribut['pilihan'] ?? []), false);
$wajib  = old('wajib') !== null ? old('wajib') === '1' : (int) ($atribut['wajib'] ?? 0) === 1;
$aksi   = $atribut === null ? url_to('panel.atribut_siswa.simpan') : url_to('panel.atribut_siswa.perbarui', $atribut['id']);
?>
<?= $this->extend('layout/panel') ?>

<?= $this->section('content') ?>
<?= view('komponen/kepala_halaman', ['title' => $title, 'context' => $atribut['label'] ?? null], ['saveData' => false]) ?>

<?php if ($galat !== []): ?>
<div class="alert alert-danger" role="alert" id="ringkasan-galat" tabindex="-1">
    <p class="mb-1">Periksa <?= format_number(count($galat)) ?> isian yang ditandai.</p>
    <ul class="mb-0">
<?php foreach ($galat as $field => $teks): ?>
        <li><a href="#<?= esc($field, 'attr') ?>"><?= esc($labels[$field] ?? $field) ?></a>: <?= esc($teks) ?></li>
<?php endforeach ?>
    </ul>
</div>
<?php endif ?>

<div class="row"><div class="col-lg-8 col-xl-6">
<form method="post" action="<?= esc($aksi, 'attr') ?>" class="card card-body">
    <?= csrf_field() ?>
<?php if ($atribut !== null): ?>
    <input type="hidden" name="_method" value="PATCH">
    <input type="hidden" name="versi" value="<?= esc($atribut['updated_at'], 'attr') ?>">
<?php endif ?>
    <div class="mb-3">
        <label class="form-label" for="label">Label</label>
        <input class="form-control<?= $isian('label') ?>" type="text" id="label" name="label" maxlength="60" required value="<?= esc($nilai('label'), 'attr') ?>">
        <?= $pesan('label') ?>
        <div class="form-text">Misalnya Agama atau Asal sekolah.</div>
    </div>
    <div class="mb-3">
        <label class="form-label" for="kode">Kode</label>
<?php if ($atribut === null): ?>
        <input class="form-control<?= $isian('kode') ?>" type="text" id="kode" name="kode" maxlength="30" autocapitalize="none" spellcheck="false" value="<?= esc($nilai('kode'), 'attr') ?>">
        <?= $pesan('kode') ?>
        <div class="form-text">Menjadi judul kolom di template import. Huruf kecil, angka, dan garis bawah, diawali huruf. Kosongkan agar dibuat dari label. Kode tidak dapat diubah setelah disimpan.</div>
<?php else: ?>
        <input class="form-control" type="text" id="kode" value="<?= esc($atribut['kode'], 'attr') ?>" readonly>
<?php endif ?>
    </div>
    <div class="mb-3">
        <label class="form-label" for="tipe">Tipe</label>
<?php if ($kunci): ?>
        <input type="hidden" name="tipe" value="<?= esc($atribut['tipe'], 'attr') ?>">
<?php endif ?>
        <select class="form-select<?= $isian('tipe') ?>" id="tipe"<?= $kunci ? ' disabled aria-describedby="tipe-kunci"' : ' name="tipe" required' ?>>
<?php if ($atribut === null): ?>
            <option value="">Pilih tipe</option>
<?php endif ?>
<?php foreach ($tipe as $kode => $label): ?>
            <option value="<?= esc($kode, 'attr') ?>"<?= $nilai('tipe') === $kode ? ' selected' : '' ?>><?= esc($label) ?></option>
<?php endforeach ?>
        </select>
        <?= $pesan('tipe') ?>
<?php if ($kunci): ?>
        <div class="form-text" id="tipe-kunci">Tipe tidak dapat diubah karena sudah ada siswa yang memiliki nilai.</div>
<?php endif ?>
    </div>
    <div class="mb-3">
        <label class="form-label" for="pilihan">Pilihan</label>
        <textarea class="form-control<?= $isian('pilihan') ?>" id="pilihan" name="pilihan" rows="4"><?= esc($pilih) ?></textarea>
        <?= $pesan('pilihan') ?>
        <div class="form-text">Hanya untuk tipe Pilihan. Tulis satu pilihan per baris, paling sedikit 2 pilihan.</div>
    </div>
    <div class="mb-3 form-check">
        <input type="hidden" name="wajib" value="0">
        <input class="form-check-input" type="checkbox" id="wajib" name="wajib" value="1"<?= $wajib ? ' checked' : '' ?>>
        <label class="form-check-label" for="wajib">Wajib diisi</label>
        <div class="form-text mt-0">Siswa yang sudah ada baru wajib diisi saat datanya diubah.</div>
    </div>
    <div class="mb-4">
        <label class="form-label" for="urutan">Urutan</label>
        <input class="form-control<?= $isian('urutan') ?>" type="number" id="urutan" name="urutan" min="0" max="999" inputmode="numeric" value="<?= esc($nilai('urutan'), 'attr') ?>">
        <?= $pesan('urutan') ?>
        <div class="form-text">Angka kecil tampil lebih dulu. Kosongkan agar ditaruh paling akhir.</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-primary" type="submit"><?= view('komponen/ikon', ['name' => 'check'], ['saveData' => false]) ?> Simpan</button>
        <a class="btn btn-outline-primary" href="<?= esc(url_to('panel.atribut_siswa.index'), 'attr') ?>">Batal</a>
    </div>
</form>
</div></div>
<?= $this->endSection() ?>
