<?php
/**
 * Check page for a semester change that takes dates out of the semesters
 * (docs/09 RT-07, HAL-MD-02, docs/04 FS-MD-02 item 3). Nothing is saved yet;
 * the form re-sends the same fields with `konfirmasi=1`.
 *
 * @var int                                          $id
 * @var string                                       $versi
 * @var array<string, string>                        $isian
 * @var list<array{mulai: string, selesai: string}>  $rentang
 * @var int                                          $jumlah Number of dates leaving
 */
?>
<?= $this->extend('layout/panel') ?>

<?= $this->section('content') ?>
<?= view('komponen/kepala_halaman', ['title' => 'Simpan perubahan semester?', 'context' => 'Tahun ajaran ' . $isian['nama']], ['saveData' => false]) ?>
<div class="card card-body">
    <p>Perubahan ini mengeluarkan <?= format_number($jumlah) ?> tanggal dari semester. Tanggal berikut tidak lagi menjadi hari sekolah, sehingga siswa tidak memiliki status presensi pada tanggal itu:</p>
    <ul>
<?php foreach ($rentang as $r): ?>
        <li><?= esc($r['mulai'] === $r['selesai'] ? format_date($r['mulai'], 'full') : format_date($r['mulai'], 'full') . ' sampai ' . format_date($r['selesai'], 'full')) ?></li>
<?php endforeach ?>
    </ul>
    <form method="post" action="<?= esc(url_to('panel.tahun_ajaran.perbarui', $id), 'attr') ?>" class="d-flex flex-wrap gap-2">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="PATCH">
        <input type="hidden" name="versi" value="<?= esc($versi, 'attr') ?>">
        <input type="hidden" name="konfirmasi" value="1">
<?php foreach ($isian as $field => $nilai): ?>
        <input type="hidden" name="<?= esc($field, 'attr') ?>" value="<?= esc($nilai, 'attr') ?>">
<?php endforeach ?>
        <button class="btn btn-primary" type="submit"><?= view('komponen/ikon', ['name' => 'check'], ['saveData' => false]) ?> Simpan perubahan</button>
        <a class="btn btn-outline-primary" href="<?= esc(url_to('panel.tahun_ajaran.ubah', $id), 'attr') ?>">Batal</a>
    </form>
</div>
<?= $this->endSection() ?>
