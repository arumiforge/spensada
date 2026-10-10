<?php
/**
 * Parent's WA number (docs/09 HAL-MD-08, docs/04 FS-MD-04 item 3). One
 * field; empty removes the number. Carries `versi` (docs/09 RT-12).
 *
 * @var array<string, mixed> $siswa
 */
$galat = validation_errors();
$wa    = (string) old('wa_ortu', $siswa['wa_ortu'] === null ? '' : format_wa($siswa['wa_ortu']), false);
?>
<?= $this->extend('layout/panel') ?>

<?= $this->section('content') ?>
<?= view('komponen/kepala_halaman', ['title' => 'Nomor WA orang tua/wali', 'context' => $siswa['nama']], ['saveData' => false]) ?>

<?= view('panel/penempatan/_galat', ['galat' => $galat, 'labels' => ['wa_ortu' => 'Nomor WA orang tua/wali']], ['saveData' => false]) ?>

<div class="row"><div class="col-lg-8 col-xl-6">
<form method="post" action="<?= esc(url_to('panel.siswa_wa.ganti', $siswa['id']), 'attr') ?>" class="card card-body">
    <?= csrf_field() ?>
    <input type="hidden" name="_method" value="PUT">
    <input type="hidden" name="versi" value="<?= esc($siswa['updated_at'], 'attr') ?>">
    <div class="mb-4">
        <label class="form-label" for="wa_ortu">Nomor WA <span class="text-body-secondary">(opsional)</span></label>
        <input class="form-control<?= isset($galat['wa_ortu']) ? ' is-invalid" aria-invalid="true' : '' ?>" type="tel" id="wa_ortu" name="wa_ortu" inputmode="tel" maxlength="20" autocomplete="off" value="<?= esc($wa, 'attr') ?>" aria-describedby="<?= isset($galat['wa_ortu']) ? 'wa_ortu-galat ' : '' ?>wa_ortu-bantuan">
<?php if (isset($galat['wa_ortu'])): ?>
        <div class="invalid-feedback" id="wa_ortu-galat"><?= esc($galat['wa_ortu']) ?></div>
<?php endif ?>
        <div class="form-text" id="wa_ortu-bantuan">Nomor ponsel yang diawali 08, misalnya 081234567890. Kosongkan untuk menghapus nomor.</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-primary" type="submit"><?= view('komponen/ikon', ['name' => 'check'], ['saveData' => false]) ?> Simpan</button>
        <a class="btn btn-outline-primary" href="<?= esc(url_to('panel.siswa.lihat', $siswa['id']), 'attr') ?>">Batal</a>
    </div>
</form>
</div></div>
<?= $this->endSection() ?>
