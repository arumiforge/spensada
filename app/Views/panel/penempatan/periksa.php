<?php
/**
 * Check page of the per-class placement (docs/09 RT-07, HAL-MD-12): the
 * impact, and the same fields again with `konfirmasi=1`.
 *
 * @var string                     $title
 * @var string                     $tanggal Y-m-d start date
 * @var string                     $form    URL back to the per-class form
 * @var array<string, mixed>       $asal
 * @var array<string, mixed>       $tujuan
 * @var list<array<string, mixed>> $siap    Students that will be placed
 * @var list<string>               $gagal   Reasons for the students that will be skipped
 */
?>
<?= $this->extend('layout/panel') ?>

<?= $this->section('content') ?>
<?= view('komponen/kepala_halaman', ['title' => $title], ['saveData' => false]) ?>
<div class="card card-body">
<?php if ($siap !== []): ?>
    <ul>
        <li><?= format_number(count($siap)) ?> siswa dari kelas <?= esc($asal['nama']) ?> (<?= esc($asal['tahun_ajaran']) ?>) masuk kelas <?= esc($tujuan['nama']) ?> (<?= esc($tujuan['tahun_ajaran']) ?>) mulai <?= esc(format_date($tanggal)) ?>.</li>
        <li>Riwayat di kelas lama tetap tersimpan.</li>
    </ul>
<?php endif ?>
<?php if ($gagal !== []): ?>
    <div class="alert alert-warning">
        <p class="mb-1"><?= format_number(count($gagal)) ?> siswa dilewati:</p>
        <ul class="mb-0">
<?php foreach ($gagal as $pesan): ?>
            <li><?= esc($pesan) ?></li>
<?php endforeach ?>
        </ul>
    </div>
<?php endif ?>
    <form method="post" action="<?= esc(url_to('panel.penempatan.simpan_kelas'), 'attr') ?>" class="d-flex flex-wrap gap-2">
        <?= csrf_field() ?>
        <input type="hidden" name="konfirmasi" value="1">
        <input type="hidden" name="asal" value="<?= esc((string) $asal['id'], 'attr') ?>">
        <input type="hidden" name="tujuan" value="<?= esc((string) $tujuan['id'], 'attr') ?>">
        <input type="hidden" name="tanggal_mulai" value="<?= esc($tanggal, 'attr') ?>">
<?php foreach ($siap as $s): ?>
        <input type="hidden" name="siswa[]" value="<?= esc((string) $s['id'], 'attr') ?>">
<?php endforeach ?>
<?php if ($siap !== []): ?>
        <button class="btn btn-primary" type="submit"><?= view('komponen/ikon', ['name' => 'check'], ['saveData' => false]) ?> Simpan</button>
<?php endif ?>
        <a class="btn btn-outline-primary" href="<?= esc($form, 'attr') ?>">Kembali</a>
    </form>
</div>
<?= $this->endSection() ?>
