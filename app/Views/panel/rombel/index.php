<?php
/**
 * Rombel of one school year (docs/09 HAL-MD-03, docs/04 FS-MD-03 items 1, 5, 6).
 *
 * @var list<array<string, mixed>> $tahunAjaran All school years, newest first
 * @var array<string, mixed>|null  $tahun       School year shown
 * @var list<array<string, mixed>> $rows        From Services\MasterData\Rombel::daftar()
 */
$ikon = static fn (string $name): string => view('komponen/ikon', ['name' => $name], ['saveData' => false]);
?>
<?= $this->extend('layout/panel') ?>

<?= $this->section('content') ?>
<?= view('komponen/kepala_halaman', [
    'title'   => 'Kelas dan wali kelas',
    'actions' => $tahun === null ? null : '<a class="btn btn-primary" href="' . esc(url_to('panel.rombel.tambah') . '?tahun_ajaran=' . $tahun['id'], 'attr') . '">' . $ikon('users') . ' Tambah kelas</a>',
], ['saveData' => false]) ?>

<?php if ($tahun === null): ?>
<p class="halaman-daftar-kosong">Belum ada tahun ajaran. Tambahkan tahun ajaran lebih dulu, lalu buat kelasnya.</p>
<a class="btn btn-primary" href="<?= esc(url_to('panel.tahun_ajaran.index'), 'attr') ?>">Buka tahun ajaran</a>
<?php else: ?>
<form class="row g-2 align-items-end mb-3" method="get" action="<?= esc(url_to('panel.rombel.index'), 'attr') ?>">
    <div class="col-8 col-md-4">
        <label class="form-label" for="tahun_ajaran">Tahun ajaran</label>
        <select class="form-select" id="tahun_ajaran" name="tahun_ajaran">
<?php foreach ($tahunAjaran as $ta): ?>
            <option value="<?= esc((string) $ta['id'], 'attr') ?>"<?= $ta['id'] === $tahun['id'] ? ' selected' : '' ?>><?= esc($ta['nama']) ?><?= (int) $ta['aktif'] === 1 ? ' (aktif)' : '' ?></option>
<?php endforeach ?>
        </select>
    </div>
    <div class="col-4 col-md-2">
        <button class="btn btn-outline-primary w-100" type="submit">Tampilkan</button>
    </div>
</form>

<?php if ($rows === []): ?>
<p class="halaman-daftar-kosong">Belum ada kelas di tahun ajaran <?= esc($tahun['nama']) ?>.</p>
<?php else: ?>
<div class="table-responsive">
<table class="table align-middle">
    <thead>
        <tr><th scope="col">Kelas</th><th scope="col">Tingkat</th><th scope="col">Wali kelas</th><th scope="col" class="text-end">Jumlah siswa</th><th scope="col"><span class="visually-hidden">Tindakan</span></th></tr>
    </thead>
    <tbody>
<?php foreach ($rows as $row): ?>
        <tr>
            <td><?= esc($row['nama']) ?></td>
            <td><?= esc((string) $row['tingkat']) ?></td>
            <td>
<?php if ($row['wali_kelas_id'] === null): ?>
                <span class="text-warning-emphasis"><?= $ikon('triangle-alert') ?> Belum ada wali kelas</span>
<?php elseif ($row['wali_status'] !== 'aktif'): ?>
                <?= esc($row['wali_nama']) ?><br><span class="text-warning-emphasis"><?= $ikon('triangle-alert') ?> Akun nonaktif, tetapkan pengganti</span>
<?php else: ?>
                <?= esc($row['wali_nama']) ?>
<?php endif ?>
            </td>
            <td class="text-end"><?= format_number((int) $row['jumlah_siswa']) ?></td>
            <td class="text-end text-nowrap">
                <a class="btn btn-sm btn-outline-primary" href="<?= esc(url_to('panel.rombel.ubah', $row['id']), 'attr') ?>"><?= $ikon('pencil') ?> Ubah<span class="visually-hidden"> kelas <?= esc($row['nama']) ?></span></a>
<?php if (! $row['terkunci']): ?>
                <a class="btn btn-sm btn-outline-danger" href="<?= esc(url_to('panel.rombel.form_hapus', $row['id']), 'attr') ?>"><?= $ikon('x') ?> Hapus<span class="visually-hidden"> kelas <?= esc($row['nama']) ?></span></a>
<?php endif ?>
            </td>
        </tr>
<?php endforeach ?>
    </tbody>
</table>
</div>
<?php endif ?>
<?php endif ?>
<?= $this->endSection() ?>
