<?php
/**
 * Reactivate a student (docs/09 HAL-MD-10, docs/04 FS-MD-04 item 5): a
 * new active period and a placement; the old periods stay.
 *
 * @var array<string, mixed>       $siswa
 * @var list<array<string, mixed>> $rombel  Class choices
 * @var string                     $hariIni Y-m-d
 */
$galat = validation_errors();
?>
<?= $this->extend('layout/panel') ?>

<?= $this->section('content') ?>
<?= view('komponen/kepala_halaman', ['title' => 'Aktifkan kembali ' . $siswa['nama'], 'context' => 'NISN ' . $siswa['nisn']], ['saveData' => false]) ?>

<?= view('panel/penempatan/_galat', ['galat' => $galat, 'labels' => ['tanggal_mulai' => 'Tanggal mulai aktif', 'rombel_id' => 'Kelas']], ['saveData' => false]) ?>

<div class="row"><div class="col-lg-8 col-xl-6">
<form method="post" action="<?= esc(url_to('panel.siswa.aktifkan', $siswa['id']), 'attr') ?>" class="card card-body">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label" for="tanggal_mulai">Tanggal mulai aktif</label>
        <input class="form-control<?= isset($galat['tanggal_mulai']) ? ' is-invalid" aria-invalid="true' : '' ?>" type="date" id="tanggal_mulai" name="tanggal_mulai" required value="<?= esc((string) old('tanggal_mulai', $hariIni, false), 'attr') ?>" aria-describedby="<?= isset($galat['tanggal_mulai']) ? 'tanggal_mulai-galat ' : '' ?>tanggal_mulai-bantuan">
<?php if (isset($galat['tanggal_mulai'])): ?>
        <div class="invalid-feedback" id="tanggal_mulai-galat"><?= esc($galat['tanggal_mulai']) ?></div>
<?php endif ?>
        <div class="form-text" id="tanggal_mulai-bantuan">Masa aktif lama tetap tersimpan di riwayat.</div>
    </div>
    <div class="mb-4">
        <label class="form-label" for="rombel_id">Kelas</label>
        <?= view('panel/penempatan/_pilih_kelas', ['name' => 'rombel_id', 'rombel' => $rombel, 'nilai' => (string) old('rombel_id', '', false), 'galat' => $galat['rombel_id'] ?? null], ['saveData' => false]) ?>
    </div>
    <p class="form-text">Akun siswa kembali ke status sebelum dinonaktifkan.</p>
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-primary" type="submit"><?= view('komponen/ikon', ['name' => 'circle-check'], ['saveData' => false]) ?> Aktifkan kembali</button>
        <a class="btn btn-outline-primary" href="<?= esc(url_to('panel.siswa.lihat', $siswa['id']), 'attr') ?>">Batal</a>
    </div>
</form>
</div></div>
<?= $this->endSection() ?>
