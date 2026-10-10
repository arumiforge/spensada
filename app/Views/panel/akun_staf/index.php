<?php
/**
 * Staff account list (docs/09 HAL-AKN-04, docs/08 UI-26).
 *
 * @var array<string, string>            $saringan Valid filters in use
 * @var bool                             $abaikan  Some filter value was not recognised
 * @var list<array<string, mixed>>       $rows
 * @var int                              $total
 * @var int                              $page
 */
$label      = config('Label')->codes;
$roleLabels = $label['akun_role.role'];
?>
<?= $this->extend('layout/panel') ?>

<?= $this->section('content') ?>
<?= view('komponen/kepala_halaman', [
    'title'   => 'Akun staf',
    'actions' => '<a class="btn btn-primary" href="' . esc(url_to('panel.akun_staf.tambah'), 'attr') . '">' . view('komponen/ikon', ['name' => 'user'], ['saveData' => false]) . ' Tambah akun</a>',
], ['saveData' => false]) ?>

<?php if ($abaikan): ?>
<div class="alert alert-warning" role="status">Sebagian saringan tidak dikenali, sehingga diabaikan.</div>
<?php endif ?>

<form class="row g-2 align-items-end mb-3" method="get" action="<?= esc(url_to('panel.akun_staf.index'), 'attr') ?>">
    <div class="col-12 col-md-5">
        <label class="form-label" for="cari">Cari nama atau username</label>
        <input class="form-control" type="search" id="cari" name="cari" maxlength="100" value="<?= esc($saringan['cari'] ?? '', 'attr') ?>">
    </div>
    <div class="col-6 col-md-3">
        <label class="form-label" for="role">Role</label>
        <select class="form-select" id="role" name="role">
            <option value="">Semua role</option>
<?php foreach ($roleLabels as $kode => $nama): ?>
            <option value="<?= esc($kode, 'attr') ?>"<?= ($saringan['role'] ?? '') === $kode ? ' selected' : '' ?>><?= esc($nama) ?></option>
<?php endforeach ?>
        </select>
    </div>
    <div class="col-6 col-md-2">
        <label class="form-label" for="status">Status</label>
        <select class="form-select" id="status" name="status">
            <option value="">Semua status</option>
<?php foreach (['aktif', 'nonaktif'] as $kode): ?>
            <option value="<?= $kode ?>"<?= ($saringan['status'] ?? '') === $kode ? ' selected' : '' ?>><?= esc($label['akun.status'][$kode]) ?></option>
<?php endforeach ?>
        </select>
    </div>
    <div class="col-12 col-md-2">
        <button class="btn btn-outline-primary w-100" type="submit"><?= view('komponen/ikon', ['name' => 'search'], ['saveData' => false]) ?> Cari</button>
    </div>
</form>

<?php
ob_start();
?>
<table class="table align-middle">
    <thead>
        <tr><th scope="col">Nama</th><th scope="col">Username</th><th scope="col">Role</th><th scope="col">Kelas yang diampu</th><th scope="col">Status</th><th scope="col">Login terakhir</th></tr>
    </thead>
    <tbody>
<?php foreach ($rows as $row): ?>
        <tr>
            <td><a href="<?= esc(url_to('panel.akun_staf.lihat', $row['id']), 'attr') ?>"><?= esc($row['nama']) ?></a></td>
            <td><?= esc($row['username']) ?></td>
            <td><?= esc(implode(', ', ['Staf', ...array_map(static fn ($r) => $roleLabels[$r] ?? $r, $row['roles'])])) ?></td>
            <td><?= $row['kelas'] === [] ? '–' : esc(implode(', ', $row['kelas'])) ?></td>
            <td><?= esc($label['akun.status'][$row['status']] ?? $row['status']) ?></td>
            <td><?= $row['login_terakhir_at'] === null ? 'Belum pernah' : esc(format_datetime($row['login_terakhir_at'])) ?></td>
        </tr>
<?php endforeach ?>
    </tbody>
</table>
<?php $table = ob_get_clean() ?>
<?= view('komponen/halaman_daftar', [
    'total' => $total,
    'page'  => $page,
    'table' => $table,
    'empty' => $saringan === [] ? 'Belum ada akun staf.' : 'Tidak ada akun staf yang cocok dengan saringan.',
], ['saveData' => false]) ?>
<?= $this->endSection() ?>
