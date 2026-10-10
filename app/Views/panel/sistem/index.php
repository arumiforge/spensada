<?php
/**
 * System check page (docs/09 HAL-AKN-07). Read-only.
 *
 * @var string                                                                    $title
 * @var \CodeIgniter\I18n\Time                                                    $checkedAt
 * @var array<string, list<array{butir: string, status: string, keterangan: string}>> $sections
 * @var int                                                                       $needAction
 */

use App\Services\Sistem\PemeriksaanSistem;

$badge = [
    PemeriksaanSistem::BAIK           => 'text-bg-success',
    PemeriksaanSistem::PERLU_TINDAKAN => 'text-bg-danger',
    PemeriksaanSistem::BELUM_TERSEDIA => 'text-bg-secondary',
];
?>
<?= $this->extend('layout/panel') ?>
<?= $this->section('content') ?>
<?= view('komponen/kepala_halaman', ['title' => $title, 'context' => 'Diperiksa ' . format_datetime($checkedAt)], ['saveData' => false]) ?>

<?php if ($needAction > 0): ?>
<div class="alert alert-danger" role="status">Ada <?= esc(format_number($needAction)) ?> pemeriksaan yang perlu tindakan. Perbaiki lewat terminal server, lalu muat ulang halaman ini.</div>
<?php else: ?>
<div class="alert alert-success" role="status">Semua pemeriksaan baik.</div>
<?php endif ?>

<?php foreach ($sections as $heading => $rows): ?>
<section class="card mb-4">
    <div class="card-body">
        <h2 class="h5"><?= esc($heading) ?></h2>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr><th scope="col">Pemeriksaan</th><th scope="col">Hasil</th><th scope="col">Keterangan</th></tr>
                </thead>
                <tbody>
<?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= esc($row['butir']) ?></td>
                        <td><span class="badge <?= $badge[$row['status']] ?>"><?= esc(PemeriksaanSistem::LABEL[$row['status']]) ?></span></td>
                        <td><?= esc($row['keterangan']) ?></td>
                    </tr>
<?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php endforeach ?>
<?= $this->endSection() ?>
