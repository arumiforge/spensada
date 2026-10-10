<?php
/**
 * Confirm deleting a rombel without placements (docs/09 HAL-MD-03, docs/04 FS-MD-03 item 5).
 *
 * @var array<string, mixed> $rombel From Services\MasterData\Rombel::cari()
 */
?>
<?= $this->extend('layout/panel') ?>

<?= $this->section('content') ?>
<?= view('komponen/kepala_halaman', ['title' => 'Hapus kelas ' . $rombel['nama'] . '?', 'context' => 'Tahun ajaran ' . $rombel['tahun_ajaran_nama']], ['saveData' => false]) ?>
<div class="card card-body">
    <p>Kelas <?= esc($rombel['nama']) ?> belum memiliki siswa. Setelah dihapus, kelas ini tidak dapat dikembalikan.</p>
    <form method="post" action="<?= esc(url_to('panel.rombel.hapus', $rombel['id']), 'attr') ?>" class="d-flex flex-wrap gap-2">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="DELETE">
        <button class="btn btn-danger" type="submit"><?= view('komponen/ikon', ['name' => 'x'], ['saveData' => false]) ?> Hapus</button>
        <a class="btn btn-outline-primary" href="<?= esc(url_to('panel.rombel.index') . '?tahun_ajaran=' . $rombel['tahun_ajaran_id'], 'attr') ?>">Batal</a>
    </form>
</div>
<?= $this->endSection() ?>
