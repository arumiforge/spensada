<?php
/**
 * Confirm password reset (docs/09 HAL-AKN-04, RT-09, docs/04 §4.7).
 *
 * @var array<string, mixed> $akun
 * @var string               $token One-time token
 */
?>
<?= $this->extend('layout/panel') ?>

<?= $this->section('content') ?>
<?= view('komponen/kepala_halaman', ['title' => 'Reset password ' . $akun['nama'] . '?'], ['saveData' => false]) ?>
<div class="card card-body">
    <ul>
        <li>Password lama langsung tidak berlaku, dan <?= esc($akun['nama']) ?> keluar dari semua perangkat.</li>
        <li>Password baru tampil sekali di halaman berikutnya. Catat atau cetak sebelum menutup halaman itu.</li>
        <li><?= esc($akun['nama']) ?> wajib mengganti password saat login berikutnya.</li>
    </ul>
    <form method="post" action="<?= esc(url_to('panel.akun_staf.reset_password', $akun['id']), 'attr') ?>" class="d-flex flex-wrap gap-2">
        <?= csrf_field() ?>
        <input type="hidden" name="token_sekali" value="<?= esc($token, 'attr') ?>">
        <button class="btn btn-primary" type="submit"><?= view('komponen/ikon', ['name' => 'key-round'], ['saveData' => false]) ?> Reset password</button>
        <a class="btn btn-outline-primary" href="<?= esc(url_to('panel.akun_staf.lihat', $akun['id']), 'attr') ?>">Batal</a>
    </form>
</div>
<?= $this->endSection() ?>
