<?php
/**
 * Confirm a student password reset (docs/09 HAL-AKN-06, RT-09, docs/04 FS-AKN-05 C).
 *
 * @var array<string, mixed> $siswa From Services\Akun\AkunSiswa::cariSiswa()
 * @var string               $token One-time token
 */
?>
<?= $this->extend('layout/panel') ?>

<?= $this->section('content') ?>
<?= view('komponen/kepala_halaman', ['title' => 'Reset password ' . $siswa['nama'] . '?'], ['saveData' => false]) ?>
<div class="card card-body">
    <ul>
        <li>Password lama dan slip lama langsung tidak berlaku, dan <?= esc($siswa['nama']) ?> keluar dari semua perangkat.</li>
        <li>Slip dengan password baru tampil sekali di halaman berikutnya. Cetak atau simpan sebelum menutup halaman itu.</li>
        <li><?= esc($siswa['nama']) ?> wajib mengganti password saat login berikutnya. Status akunnya tidak berubah.</li>
    </ul>
    <form method="post" action="<?= esc(url_to('panel.akun_siswa.reset_password', $siswa['id']), 'attr') ?>" class="d-flex flex-wrap gap-2">
        <?= csrf_field() ?>
        <input type="hidden" name="token_sekali" value="<?= esc($token, 'attr') ?>">
        <button class="btn btn-primary" type="submit"><?= view('komponen/ikon', ['name' => 'key-round'], ['saveData' => false]) ?> Reset password</button>
        <a class="btn btn-outline-primary" href="<?= esc(url_to('panel.siswa.lihat', $siswa['id']), 'attr') ?>">Batal</a>
    </form>
</div>
<?= $this->endSection() ?>
