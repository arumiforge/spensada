<?php
/**
 * Activity log entry (docs/09 HAL-AKN-09): `data` as a labelled table, not raw JSON.
 *
 * @var array<string, mixed>                          $entri
 * @var list<array{label: string, nilai: string}>     $rincian
 */
use App\Services\Akun\DaftarLogAktivitas;

$jenis = config('Label')->codes['log_aktivitas.jenis'][$entri['jenis']] ?? $entri['jenis'];
$back  = '<a class="btn btn-outline-secondary" href="' . esc(url_to('panel.log_aktivitas.index')) . '">Kembali ke log aktivitas</a>';
?>
<?= $this->extend('layout/panel') ?>
<?= $this->section('content') ?>
<?= view('komponen/kepala_halaman', ['title' => $jenis, 'context' => format_datetime($entri['created_at'], true), 'actions' => $back], ['saveData' => false]) ?>

<table class="table">
    <tbody>
        <tr><th scope="row">Waktu</th><td><?= esc(format_datetime($entri['created_at'], true)) ?></td></tr>
        <tr><th scope="row">Jenis</th><td><?= esc($jenis) ?></td></tr>
        <tr><th scope="row">Pelaku</th><td><?= esc($entri['pelaku_id'] === null ? 'Sistem' : DaftarLogAktivitas::namaAkun($entri['pelaku_nama'], $entri['pelaku_username'], '—')) ?></td></tr>
        <tr><th scope="row">Akun terdampak</th><td><?= esc($entri['akun_id'] === null ? '—' : DaftarLogAktivitas::namaAkun($entri['akun_nama'], $entri['akun_username'], '—')) ?></td></tr>
<?php if (! empty($entri['ip'])): ?>
        <tr><th scope="row">Alamat IP</th><td><?= esc($entri['ip']) ?></td></tr>
<?php endif ?>
    </tbody>
</table>

<h2 class="h5">Rincian</h2>
<?php if ($rincian === []): ?>
<p>Catatan ini tidak punya rincian tambahan.</p>
<?php else: ?>
<table class="table">
    <tbody>
<?php foreach ($rincian as $r): ?>
        <tr><th scope="row"><?= esc($r['label']) ?></th><td class="text-break"><?= esc($r['nilai']) ?></td></tr>
<?php endforeach ?>
    </tbody>
</table>
<?php endif ?>
<?= $this->endSection() ?>
