<?php
/**
 * Move one student to another class (docs/09 HAL-MD-11, docs/04 FS-MD-05 item 2).
 *
 * @var string                     $title
 * @var array<string, mixed>       $siswa
 * @var array<string, mixed>|null  $sekarang Today's rombel, or null
 * @var list<array<string, mixed>> $rombel   Class choices
 * @var string                     $hariIni  Y-m-d
 */
$galat   = validation_errors();
$tanggal = (string) old('tanggal_mulai', $hariIni);
?>
<?= $this->extend('layout/panel') ?>

<?= $this->section('content') ?>
<?= view('komponen/kepala_halaman', [
    'title'   => $title,
    'context' => $siswa['nama'] . ' · ' . ($sekarang === null ? 'Belum ada kelas hari ini' : 'Kelas saat ini ' . $sekarang['nama']),
], ['saveData' => false]) ?>

<?= view('panel/penempatan/_galat', ['galat' => $galat, 'labels' => ['rombel_id' => 'Kelas tujuan', 'tanggal_mulai' => 'Tanggal mulai']], ['saveData' => false]) ?>

<div class="row"><div class="col-lg-8 col-xl-6">
<form method="post" action="<?= esc(url_to('panel.penempatan.simpan', $siswa['id']), 'attr') ?>" class="card card-body">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label" for="rombel_id">Kelas tujuan</label>
        <?= view('panel/penempatan/_pilih_kelas', ['name' => 'rombel_id', 'rombel' => $rombel, 'nilai' => (string) old('rombel_id', ''), 'galat' => $galat['rombel_id'] ?? null], ['saveData' => false]) ?>
    </div>
    <div class="mb-4">
        <label class="form-label" for="tanggal_mulai">Tanggal mulai</label>
        <input class="form-control<?= isset($galat['tanggal_mulai']) ? ' is-invalid" aria-invalid="true' : '' ?>" type="date" id="tanggal_mulai" name="tanggal_mulai" required value="<?= esc($tanggal, 'attr') ?>" aria-describedby="<?= isset($galat['tanggal_mulai']) ? 'tanggal_mulai-galat ' : '' ?>tanggal_mulai-bantuan">
<?php if (isset($galat['tanggal_mulai'])): ?>
        <div class="invalid-feedback" id="tanggal_mulai-galat"><?= esc($galat['tanggal_mulai']) ?></div>
<?php endif ?>
        <div class="form-text" id="tanggal_mulai-bantuan">Kelas lama berakhir sehari sebelum tanggal ini. Riwayat di kelas lama tetap tersimpan.</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-primary" type="submit"><?= view('komponen/ikon', ['name' => 'check'], ['saveData' => false]) ?> Simpan</button>
        <a class="btn btn-outline-primary" href="<?= esc(url_to('panel.siswa.lihat', $siswa['id']), 'attr') ?>">Batal</a>
    </div>
</form>
</div></div>
<?= $this->endSection() ?>
