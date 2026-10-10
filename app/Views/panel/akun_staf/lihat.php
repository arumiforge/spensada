<?php
/**
 * Staff account detail and actions (docs/09 HAL-AKN-04).
 *
 * @var array<string, mixed>                                    $akun   With `roles`
 * @var array{jenis: string, sampai: \CodeIgniter\I18n\Time}|null $kunci Login lock on the account
 * @var bool                                                    $diriku The admin's own account
 */
$label = config('Label')->codes;
$ikon  = static fn (string $name): string => view('komponen/ikon', ['name' => $name], ['saveData' => false]);
?>
<?= $this->extend('layout/panel') ?>

<?= $this->section('content') ?>
<?= view('komponen/kepala_halaman', [
    'title'   => $akun['nama'],
    'context' => 'Akun staf',
    'actions' => '<a class="btn btn-primary" href="' . esc(url_to('panel.akun_staf.ubah', $akun['id']), 'attr') . '">' . $ikon('pencil') . ' Ubah</a>',
], ['saveData' => false]) ?>

<?php if ($kunci !== null): ?>
<div class="alert alert-warning d-flex flex-wrap align-items-center gap-2" role="status">
    <?= $ikon('triangle-alert') ?>
    <span class="me-auto">Login akun ini sedang dikunci karena terlalu banyak percobaan gagal, sampai <?= esc(format_datetime($kunci['sampai'])) ?>.</span>
    <form method="post" action="<?= esc(url_to('panel.akun_staf.buka_kunci', $akun['id']), 'attr') ?>">
        <?= csrf_field() ?>
        <button class="btn btn-outline-primary" type="submit"><?= $ikon('lock-open') ?> Buka kunci login</button>
    </form>
</div>
<?php endif ?>

<div class="card card-body mb-3"><dl class="row mb-0">
    <dt class="col-sm-4">Nama lengkap</dt><dd class="col-sm-8"><?= esc($akun['nama']) ?></dd>
    <dt class="col-sm-4">Username</dt><dd class="col-sm-8"><?= esc($akun['username']) ?></dd>
    <dt class="col-sm-4">Role</dt><dd class="col-sm-8"><?= esc(implode(', ', ['Staf', ...array_map(static fn ($r) => $label['akun_role.role'][$r] ?? $r, $akun['roles'])])) ?></dd>
<?php if ($akun['kelas'] !== []): ?>
    <dt class="col-sm-4">Wali kelas</dt><dd class="col-sm-8"><?= esc(implode(', ', $akun['kelas'])) ?></dd>
<?php endif ?>
    <dt class="col-sm-4">Status</dt><dd class="col-sm-8"><?= esc($label['akun.status'][$akun['status']] ?? $akun['status']) ?></dd>
    <dt class="col-sm-4">Password</dt><dd class="col-sm-8"><?= $akun['wajib_ganti_password'] ? 'Belum diganti sejak dibuat atau direset' : 'Sudah diganti pemiliknya' ?></dd>
    <dt class="col-sm-4">Login terakhir</dt><dd class="col-sm-8"><?= $akun['login_terakhir_at'] === null ? 'Belum pernah' : esc(format_datetime($akun['login_terakhir_at'])) ?></dd>
    <dt class="col-sm-4">Dibuat</dt><dd class="col-sm-8 mb-0"><?= esc(format_datetime($akun['created_at'])) ?></dd>
</dl></div>

<div class="d-flex flex-wrap gap-2">
    <a class="btn btn-outline-primary" href="<?= esc(url_to('panel.akun_staf.form_reset_password', $akun['id']), 'attr') ?>"><?= $ikon('key-round') ?> Reset password</a>
<?php if ($akun['status'] === 'aktif' && ! $diriku): ?>
    <a class="btn btn-outline-danger" href="<?= esc(url_to('panel.akun_staf.form_nonaktifkan', $akun['id']), 'attr') ?>"><?= $ikon('circle-x') ?> Nonaktifkan</a>
<?php elseif ($akun['status'] === 'nonaktif'): ?>
    <form method="post" action="<?= esc(url_to('panel.akun_staf.aktifkan', $akun['id']), 'attr') ?>">
        <?= csrf_field() ?>
        <button class="btn btn-outline-primary" type="submit"><?= $ikon('circle-check') ?> Aktifkan</button>
    </form>
<?php endif ?>
    <a class="btn btn-link" href="<?= esc(url_to('panel.akun_staf.index'), 'attr') ?>"><?= $ikon('chevron-left') ?> Kembali ke daftar</a>
</div>
<?= $this->endSection() ?>
