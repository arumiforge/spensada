<?php
/**
 * Student data log, tab "Log data" of the profile (docs/09 HAL-MD-17):
 * newest first, with time, actor, kind, old and new data, and reason.
 *
 * @var array<string, mixed>                               $siswa
 * @var list<array<string, mixed>>                         $rows
 * @var int                                                $total
 * @var int                                                $page
 * @var \App\Services\MasterData\DaftarLogDataSiswa        $log
 */
$jenis  = config('Label')->codes['log_data_siswa.jenis'];
$rincian = static function (array $baris): string {
    if ($baris === []) {
        return '–';
    }
    $html = '<dl class="mb-0">';
    foreach ($baris as $b) {
        $html .= '<dt class="fw-normal text-body-secondary small">' . esc($b['label']) . '</dt><dd class="mb-1">' . esc($b['nilai']) . '</dd>';
    }

    return $html . '</dl>';
};
?>
<?= $this->extend('layout/panel') ?>

<?= $this->section('content') ?>
<?= view('komponen/kepala_halaman', ['title' => $siswa['nama'], 'context' => 'NISN ' . $siswa['nisn']], ['saveData' => false]) ?>

<?= view('panel/siswa/_tab', ['id' => (int) $siswa['id'], 'aktif' => 'log', 'log' => true], ['saveData' => false]) ?>

<?php ob_start() ?>
<table class="table align-top">
    <thead>
        <tr><th scope="col">Waktu</th><th scope="col">Pelaku</th><th scope="col">Jenis</th><th scope="col">Data lama</th><th scope="col">Data baru</th><th scope="col">Alasan</th></tr>
    </thead>
    <tbody>
<?php foreach ($rows as $row): ?>
        <tr>
            <td><?= esc(format_datetime($row['created_at'])) ?></td>
            <td><?= $row['pelaku_id'] === null ? 'Sistem' : esc($row['pelaku_nama'] ?? $row['pelaku_username']) ?></td>
            <td><?= esc($jenis[$row['jenis']] ?? $row['jenis']) ?></td>
            <td><?= $rincian($log->rincian($row['data_lama'])) ?></td>
            <td><?= $rincian($log->rincian($row['data_baru'])) ?></td>
            <td><?= $row['alasan'] === null ? '–' : esc($row['alasan']) ?></td>
        </tr>
<?php endforeach ?>
    </tbody>
</table>
<?php $table = ob_get_clean() ?>
<?= view('komponen/halaman_daftar', [
    'total' => $total,
    'page'  => $page,
    'table' => $table,
    'empty' => 'Belum ada perubahan data yang tercatat.',
], ['saveData' => false]) ?>
<?= $this->endSection() ?>
