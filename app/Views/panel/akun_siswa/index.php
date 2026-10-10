<?php
/**
 * Student accounts per class (docs/09 HAL-AKN-05, docs/04 FS-AKN-05 A4, B1).
 * Without a class: the classes in scope. With one: its accounts, where rows
 * of `belum_aktif` accounts carry a checkbox, checked by default.
 *
 * @var array<string, mixed>|null       $rombel      Chosen class, or null
 * @var list<array<string, mixed>>      $daftarKelas Classes in scope (without a class)
 * @var list<array<string, mixed>>      $rows        Accounts of the class
 * @var bool                            $bolehSlip   The user may make slips for this class
 */
$statusLabel = config('Label')->codes['akun.status'];
?>
<?= $this->extend('layout/panel') ?>

<?= $this->section('content') ?>
<?php if ($rombel === null): ?>
<?= view('komponen/kepala_halaman', ['title' => 'Akun siswa'], ['saveData' => false]) ?>
<?php if ($daftarKelas === []): ?>
<p class="halaman-daftar-kosong">Belum ada kelas di tahun ajaran aktif.</p>
<?php else: ?>
<p>Pilih kelas untuk melihat akun siswanya.</p>
<ul class="list-group">
<?php foreach ($daftarKelas as $kelas): ?>
    <li class="list-group-item"><a href="<?= esc(url_to('panel.akun_siswa.index') . '?kelas=' . $kelas['id'], 'attr') ?>"><?= esc($kelas['nama']) ?></a></li>
<?php endforeach ?>
</ul>
<?php endif ?>
<?php else: ?>
<?php $belum = array_filter($rows, static fn (array $r): bool => $r['status'] === 'belum_aktif') ?>
<?= view('komponen/kepala_halaman', [
    'title'   => 'Akun siswa kelas ' . $rombel['nama'],
    'actions' => '<a class="btn btn-outline-primary" href="' . esc(url_to('panel.akun_siswa.index'), 'attr') . '">Pilih kelas lain</a>',
], ['saveData' => false]) ?>
<?php if ($rows === []): ?>
<p class="halaman-daftar-kosong">Belum ada siswa di kelas ini.</p>
<?php else: ?>
<?php if ($belum === []): ?>
<div class="alert alert-info" role="status"><?= view('komponen/ikon', ['name' => 'info'], ['saveData' => false]) ?> <span>Semua akun di kelas ini sudah aktif.</span></div>
<?php endif ?>
<?php $pakaiCentang = $bolehSlip && $belum !== [] ?>
<form method="get" action="<?= esc(url_to('panel.akun_siswa.form_slip'), 'attr') ?>">
    <input type="hidden" name="kelas" value="<?= esc((string) $rombel['id'], 'attr') ?>">
    <div class="table-responsive">
    <table class="table align-middle">
        <thead>
            <tr>
<?php if ($pakaiCentang): ?>
                <th scope="col"><span class="visually-hidden">Pilih</span></th>
<?php endif ?>
                <th scope="col">NISN</th><th scope="col">Nama</th><th scope="col">Status akun</th><th scope="col">Slip terakhir dibuat</th><th scope="col">Login terakhir</th>
            </tr>
        </thead>
        <tbody>
<?php foreach ($rows as $row): ?>
            <tr>
<?php if ($pakaiCentang): ?>
                <td>
<?php if ($row['status'] === 'belum_aktif'): ?>
                    <input class="form-check-input" type="checkbox" name="siswa[]" value="<?= esc((string) $row['siswa_id'], 'attr') ?>" id="siswa-<?= esc((string) $row['siswa_id'], 'attr') ?>" checked aria-label="Pilih <?= esc($row['nama'], 'attr') ?>">
<?php endif ?>
                </td>
<?php endif ?>
                <td><?= esc($row['nisn']) ?></td>
                <td><a href="<?= esc(url_to('panel.siswa.lihat', $row['siswa_id']), 'attr') ?>"><?= esc($row['nama']) ?></a></td>
                <td><?= esc($statusLabel[$row['status']] ?? $row['status']) ?></td>
                <td><?= $row['slip_dibuat_at'] === null ? 'Belum pernah' : esc(format_datetime($row['slip_dibuat_at'])) ?></td>
                <td><?= $row['login_terakhir_at'] === null ? 'Belum pernah' : esc(format_datetime($row['login_terakhir_at'])) ?></td>
            </tr>
<?php endforeach ?>
        </tbody>
    </table>
    </div>
<?php if ($pakaiCentang): ?>
    <p class="form-text">Slip hanya dibuat untuk akun yang belum aktif. Hapus centang siswa yang tidak perlu dibuatkan slip.</p>
    <button class="btn btn-primary" type="submit"><?= view('komponen/ikon', ['name' => 'printer'], ['saveData' => false]) ?> Buat slip akun</button>
<?php endif ?>
</form>
<?php endif ?>
<?php endif ?>
<?= $this->endSection() ?>
