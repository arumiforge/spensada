<?php
/**
 * Confirm making account slips (docs/09 HAL-AKN-05, RT-09, docs/04 FS-AKN-05 B2).
 *
 * @var array<string, mixed>       $rombel
 * @var list<array<string, mixed>> $terpilih Chosen `belum_aktif` accounts
 * @var string                     $token    One-time token
 */
$jumlah = format_number(count($terpilih));
?>
<?= $this->extend('layout/panel') ?>

<?= $this->section('content') ?>
<?= view('komponen/kepala_halaman', ['title' => 'Buat slip akun kelas ' . $rombel['nama'] . '?'], ['saveData' => false]) ?>
<div class="card card-body">
    <ul>
        <li><?= esc($jumlah) ?> akun akan mendapat password baru.</li>
        <li>Slip lama untuk akun tersebut tidak berlaku lagi.</li>
        <li>Password hanya tampil sekali di halaman berikutnya. Cetak atau simpan slip sebelum menutup halaman itu.</li>
    </ul>
    <details class="mb-3">
        <summary>Lihat <?= esc($jumlah) ?> siswa</summary>
        <ul class="mt-2 mb-0">
<?php foreach ($terpilih as $row): ?>
            <li><?= esc($row['nama']) ?> (<?= esc($row['nisn']) ?>)</li>
<?php endforeach ?>
        </ul>
    </details>
    <form method="post" action="<?= esc(url_to('panel.akun_siswa.slip'), 'attr') ?>" class="d-flex flex-wrap gap-2">
        <?= csrf_field() ?>
        <input type="hidden" name="token_sekali" value="<?= esc($token, 'attr') ?>">
        <input type="hidden" name="kelas" value="<?= esc((string) $rombel['id'], 'attr') ?>">
<?php foreach ($terpilih as $row): ?>
        <input type="hidden" name="siswa[]" value="<?= esc((string) $row['siswa_id'], 'attr') ?>">
<?php endforeach ?>
        <button class="btn btn-primary" type="submit"><?= view('komponen/ikon', ['name' => 'printer'], ['saveData' => false]) ?> Buat slip</button>
        <a class="btn btn-outline-primary" href="<?= esc(url_to('panel.akun_siswa.index') . '?kelas=' . $rombel['id'], 'attr') ?>">Batal</a>
    </form>
</div>
<?= $this->endSection() ?>
