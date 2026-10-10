<?php
/**
 * Password shown once, as the direct answer of add or reset (docs/09 RT-09,
 * docs/04 FS-AKN-03 interface note). Copy and print need JavaScript
 * (salin.js); without it the password can still be read and written down.
 *
 * @var array<string, mixed> $akun
 * @var string               $password
 * @var bool                 $baru     True after adding, false after a reset
 */
$ikon = static fn (string $name): string => view('komponen/ikon', ['name' => $name], ['saveData' => false]);
?>
<?= $this->extend('layout/panel') ?>

<?= $this->section('content') ?>
<?= view('komponen/kepala_halaman', ['title' => $baru ? 'Akun staf dibuat' : 'Password direset', 'context' => $akun['nama']], ['saveData' => false]) ?>
<div class="alert alert-warning" role="status"><?= $ikon('triangle-alert') ?> <span>Password ini hanya tampil sekali dan tidak dapat dilihat lagi. Catat, salin, atau cetak sekarang, lalu serahkan ke <?= esc($akun['nama']) ?>.</span></div>
<div class="card card-body mb-3">
    <dl class="row mb-0">
        <dt class="col-sm-4">Nama</dt><dd class="col-sm-8"><?= esc($akun['nama']) ?></dd>
        <dt class="col-sm-4">Username</dt><dd class="col-sm-8"><?= esc($akun['username']) ?></dd>
        <dt class="col-sm-4"><?= $baru ? 'Password awal' : 'Password baru' ?></dt>
        <dd class="col-sm-8 mb-0"><code class="fs-4" id="password-sekali"><?= esc($password) ?></code></dd>
    </dl>
    <p class="form-text mb-0 mt-2">Saat login pertama dengan password ini, <?= esc($akun['nama']) ?> wajib menggantinya.</p>
</div>
<div class="d-flex flex-wrap gap-2 d-print-none">
    <button class="btn btn-primary" type="button" hidden data-salin="password-sekali"><?= $ikon('copy') ?> Salin password</button>
    <button class="btn btn-outline-primary" type="button" hidden data-cetak><?= $ikon('printer') ?> Cetak</button>
    <a class="btn btn-link" href="<?= esc(url_to('panel.akun_staf.lihat', $akun['id']), 'attr') ?>">Selesai</a>
</div>
<script src="<?= esc(base_url('aset/js/salin.js') . '?v=' . config('Spensada')->versi, 'attr') ?>" defer></script>
<?= $this->endSection() ?>
