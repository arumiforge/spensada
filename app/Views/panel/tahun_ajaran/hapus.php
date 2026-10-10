<?php
/**
 * Confirm deleting a school year without classes (docs/09 HAL-MD-02, docs/04 FS-MD-02 item 4).
 *
 * @var array<string, mixed> $ta
 */
?>
<?= $this->extend('layout/panel') ?>

<?= $this->section('content') ?>
<?= view('komponen/kepala_halaman', ['title' => 'Hapus tahun ajaran ' . $ta['nama'] . '?'], ['saveData' => false]) ?>
<div class="card card-body">
    <p>Tahun ajaran <?= esc($ta['nama']) ?> beserta kedua semesternya akan dihapus. Tahun ajaran ini belum memiliki kelas.</p>
    <form method="post" action="<?= esc(url_to('panel.tahun_ajaran.hapus', $ta['id']), 'attr') ?>" class="d-flex flex-wrap gap-2">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="DELETE">
        <input type="hidden" name="versi" value="<?= esc($ta['updated_at'], 'attr') ?>">
        <button class="btn btn-danger" type="submit"><?= view('komponen/ikon', ['name' => 'x'], ['saveData' => false]) ?> Hapus</button>
        <a class="btn btn-outline-primary" href="<?= esc(url_to('panel.tahun_ajaran.index'), 'attr') ?>">Batal</a>
    </form>
</div>
<?= $this->endSection() ?>
