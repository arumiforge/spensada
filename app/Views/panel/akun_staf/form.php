<?php
/**
 * Add or edit a staff account (docs/09 HAL-AKN-04, docs/11 VAL-07).
 * Add carries the one-time token (RT-09); edit carries `versi` (RT-12).
 *
 * @var string                    $title
 * @var array<string, mixed>|null $akun  Null when adding
 * @var string|null               $token One-time token when adding
 */
$galat      = validation_errors();
$roleLabels = config('Label')->codes['akun_role.role'];
$labels     = ['nama' => 'Nama lengkap', 'username' => 'Username', 'role' => 'Role'];
$nilai      = static fn (string $field) => old($field, $akun[$field] ?? '', false);
$roles      = (array) old('role', $akun['roles'] ?? []);
$isian      = static fn (string $field): string => isset($galat[$field]) ? ' is-invalid" aria-invalid="true' : '';
$aksi = $akun === null ? url_to('panel.akun_staf.simpan') : url_to('panel.akun_staf.perbarui', $akun['id']);
?>
<?= $this->extend('layout/panel') ?>

<?= $this->section('content') ?>
<?= view('komponen/kepala_halaman', ['title' => $title, 'context' => $akun['nama'] ?? null], ['saveData' => false]) ?>

<?php if ($galat !== []): ?>
<div class="alert alert-danger" role="alert" id="ringkasan-galat">
    <p class="mb-1">Periksa <?= format_number(count($galat)) ?> isian yang ditandai.</p>
    <ul class="mb-0">
<?php foreach ($galat as $field => $pesan): ?>
        <li><a href="#<?= esc($field, 'attr') ?>"><?= esc($labels[$field] ?? $field) ?></a>: <?= esc($pesan) ?></li>
<?php endforeach ?>
    </ul>
</div>
<?php endif ?>

<div class="row"><div class="col-lg-8 col-xl-6">
<form method="post" action="<?= esc($aksi, 'attr') ?>" class="card card-body">
    <?= csrf_field() ?>
<?php if ($akun === null): ?>
    <input type="hidden" name="token_sekali" value="<?= esc($token, 'attr') ?>">
<?php else: ?>
    <input type="hidden" name="_method" value="PATCH">
    <input type="hidden" name="versi" value="<?= esc($akun['updated_at'], 'attr') ?>">
<?php endif ?>
    <div class="mb-3">
        <label class="form-label" for="nama">Nama lengkap</label>
        <input class="form-control<?= $isian('nama') ?>" type="text" id="nama" name="nama" maxlength="100" required value="<?= esc($nilai('nama'), 'attr') ?>"<?= isset($galat['nama']) ? ' aria-describedby="nama-galat"' : '' ?>>
<?php if (isset($galat['nama'])): ?>
        <div class="invalid-feedback" id="nama-galat"><?= esc($galat['nama']) ?></div>
<?php endif ?>
    </div>
    <div class="mb-3">
        <label class="form-label" for="username">Username</label>
        <input class="form-control<?= $isian('username') ?>" type="text" id="username" name="username" maxlength="30" required autocapitalize="none" spellcheck="false" value="<?= esc($nilai('username'), 'attr') ?>" aria-describedby="<?= isset($galat['username']) ? 'username-galat ' : '' ?>username-bantuan">
<?php if (isset($galat['username'])): ?>
        <div class="invalid-feedback" id="username-galat"><?= esc($galat['username']) ?></div>
<?php endif ?>
        <div class="form-text" id="username-bantuan">3 sampai 30 karakter, diawali huruf. Boleh berisi huruf kecil, angka, titik, dan garis bawah.</div>
    </div>
    <fieldset class="mb-4" id="role"<?= isset($galat['role']) ? ' aria-describedby="role-galat"' : '' ?>>
        <legend class="form-label fs-6">Role</legend>
        <p class="form-text mt-0">Role Staf melekat otomatis. Wali kelas diatur di halaman kelas.</p>
<?php foreach ($roleLabels as $kode => $label): ?>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="role-<?= esc($kode, 'attr') ?>" name="role[]" value="<?= esc($kode, 'attr') ?>"<?= in_array($kode, $roles, true) ? ' checked' : '' ?>>
            <label class="form-check-label" for="role-<?= esc($kode, 'attr') ?>"><?= esc($label) ?></label>
        </div>
<?php endforeach ?>
<?php if (isset($galat['role'])): ?>
        <div class="text-danger small" id="role-galat"><?= esc($galat['role']) ?></div>
<?php endif ?>
    </fieldset>
<?php if ($akun === null): ?>
    <p class="form-text">Password awal dibuat otomatis dan tampil sekali setelah akun disimpan.</p>
<?php endif ?>
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-primary" type="submit"><?= view('komponen/ikon', ['name' => 'check'], ['saveData' => false]) ?> Simpan</button>
        <a class="btn btn-outline-primary" href="<?= esc($akun === null ? url_to('panel.akun_staf.index') : url_to('panel.akun_staf.lihat', $akun['id']), 'attr') ?>">Batal</a>
    </div>
</form>
</div></div>
<?= $this->endSection() ?>
