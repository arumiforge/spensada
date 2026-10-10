<?php
/**
 * Confirm deactivation (docs/09 HAL-AKN-04, docs/04 §4.7, docs/08 UI-28).
 *
 * @var array<string, mixed> $akun
 */
?>
<?= $this->extend('layout/panel') ?>

<?= $this->section('content') ?>
<?= view('komponen/kepala_halaman', ['title' => 'Nonaktifkan akun ' . $akun['nama'] . '?'], ['saveData' => false]) ?>
<div class="card card-body">
    <ul>
        <li><?= esc($akun['nama']) ?> tidak dapat login lagi, dan sesi yang sedang berjalan langsung berakhir.</li>
        <li>Nama <?= esc($akun['nama']) ?> tetap tampil di log dan riwayat perubahan.</li>
        <li>Akun dapat diaktifkan kembali kapan saja.</li>
<?php if ($akun['kelas'] !== []): ?>
        <li><?= esc($akun['nama']) ?> adalah wali kelas <?= esc(implode(', ', $akun['kelas'])) ?>. Penugasannya tetap. Tetapkan wali kelas pengganti di halaman Kelas.</li>
<?php endif ?>
    </ul>
    <form method="post" action="<?= esc(url_to('panel.akun_staf.nonaktifkan', $akun['id']), 'attr') ?>" class="d-flex flex-wrap gap-2">
        <?= csrf_field() ?>
        <button class="btn btn-danger" type="submit"><?= view('komponen/ikon', ['name' => 'circle-x'], ['saveData' => false]) ?> Nonaktifkan</button>
        <a class="btn btn-outline-primary" href="<?= esc(url_to('panel.akun_staf.lihat', $akun['id']), 'attr') ?>">Batal</a>
    </form>
</div>
<?= $this->endSection() ?>
