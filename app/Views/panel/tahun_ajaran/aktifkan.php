<?php
/**
 * Confirm activating a school year (docs/09 HAL-MD-02, docs/04 FS-MD-02 item 2, UF-08 step 6).
 *
 * @var array<string, mixed> $ta
 */
?>
<?= $this->extend('layout/panel') ?>

<?= $this->section('content') ?>
<?= view('komponen/kepala_halaman', ['title' => 'Aktifkan tahun ajaran ' . $ta['nama'] . '?'], ['saveData' => false]) ?>
<div class="card card-body">
    <ul>
        <li>Hak wali kelas berpindah ke penugasan kelas tahun ajaran <?= esc($ta['nama']) ?>.</li>
        <li>Dashboard, presensi per kelas, dan kiosk memakai kelas tahun ajaran <?= esc($ta['nama']) ?>.</li>
        <li>Tahun ajaran yang sekarang aktif menjadi tidak aktif, tetapi rekap dan riwayatnya tetap dapat dibuka.</li>
    </ul>
    <form method="post" action="<?= esc(url_to('panel.tahun_ajaran.aktifkan', $ta['id']), 'attr') ?>" class="d-flex flex-wrap gap-2">
        <?= csrf_field() ?>
        <button class="btn btn-primary" type="submit"><?= view('komponen/ikon', ['name' => 'circle-check'], ['saveData' => false]) ?> Aktifkan</button>
        <a class="btn btn-outline-primary" href="<?= esc(url_to('panel.tahun_ajaran.index'), 'attr') ?>">Batal</a>
    </form>
</div>
<?= $this->endSection() ?>
