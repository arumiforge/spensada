<?php
/**
 * School year list with the active year marked (docs/09 HAL-MD-02).
 *
 * @var list<array<string, mixed>> $rows From TahunAjaran::daftar()
 */
$ikon    = static fn (string $name): string => view('komponen/ikon', ['name' => $name], ['saveData' => false]);
$rentang = static fn (?array $r): string => $r === null ? '—' : format_date($r['tanggal_mulai'], 'short') . ' – ' . format_date($r['tanggal_selesai'], 'short');
$aktif   = array_filter($rows, static fn (array $r): bool => (int) $r['aktif'] === 1) !== [];
?>
<?= $this->extend('layout/panel') ?>

<?= $this->section('content') ?>
<?= view('komponen/kepala_halaman', [
    'title'   => 'Tahun ajaran',
    'actions' => '<a class="btn btn-primary" href="' . esc(url_to('panel.tahun_ajaran.tambah'), 'attr') . '">' . $ikon('calendar-days') . ' Tambah tahun ajaran</a>',
], ['saveData' => false]) ?>

<?php if ($rows !== [] && ! $aktif): ?>
<div class="alert alert-warning" role="status"><?= $ikon('triangle-alert') ?> <span>Belum ada tahun ajaran aktif. Aktifkan tahun ajaran yang sedang berjalan.</span></div>
<?php endif ?>

<?php ob_start() ?>
<table class="table align-middle">
    <thead>
        <tr><th scope="col">Tahun ajaran</th><th scope="col">Tanggal</th><th scope="col">Semester ganjil</th><th scope="col">Semester genap</th><th scope="col">Kelas</th><th scope="col"><span class="visually-hidden">Tindakan</span></th></tr>
    </thead>
    <tbody>
<?php foreach ($rows as $row): ?>
        <tr>
            <td>
                <?= esc($row['nama']) ?>
<?php if ((int) $row['aktif'] === 1): ?>
                <span class="badge text-bg-success ms-1"><?= $ikon('circle-check') ?> Aktif</span>
<?php endif ?>
            </td>
            <td><?= esc($rentang($row)) ?></td>
            <td><?= esc($rentang($row['ganjil'])) ?></td>
            <td><?= esc($rentang($row['genap'])) ?></td>
            <td><?= format_number($row['jumlah_rombel']) ?></td>
            <td class="text-end">
                <div class="d-flex flex-wrap gap-2 justify-content-end">
                    <a class="btn btn-sm btn-outline-primary" href="<?= esc(url_to('panel.tahun_ajaran.ubah', $row['id']), 'attr') ?>"><?= $ikon('pencil') ?> Ubah</a>
<?php if ((int) $row['aktif'] !== 1): ?>
                    <a class="btn btn-sm btn-outline-primary" href="<?= esc(url_to('panel.tahun_ajaran.form_aktifkan', $row['id']), 'attr') ?>"><?= $ikon('circle-check') ?> Aktifkan</a>
<?php endif ?>
<?php if ($row['jumlah_rombel'] === 0): ?>
                    <a class="btn btn-sm btn-outline-danger" href="<?= esc(url_to('panel.tahun_ajaran.form_hapus', $row['id']), 'attr') ?>"><?= $ikon('x') ?> Hapus</a>
<?php endif ?>
                </div>
            </td>
        </tr>
<?php endforeach ?>
    </tbody>
</table>
<?php $table = ob_get_clean() ?>
<?= view('komponen/halaman_daftar', [
    'total'   => count($rows),
    'page'    => 1,
    'perPage' => max(1, count($rows)),
    'table'   => $table,
    'empty'   => 'Belum ada tahun ajaran. Tambahkan tahun ajaran beserta kedua semesternya.',
], ['saveData' => false]) ?>
<?= $this->endSection() ?>
