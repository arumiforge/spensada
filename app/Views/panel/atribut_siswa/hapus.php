<?php
/**
 * Confirm deleting an attribute without values (docs/09 HAL-MD-16, docs/04 FS-MD-09 item 5).
 *
 * @var array<string, mixed> $atribut
 */
?>
<?= $this->extend('layout/panel') ?>

<?= $this->section('content') ?>
<?= view('komponen/kepala_halaman', ['title' => 'Hapus atribut ' . $atribut['label'] . '?'], ['saveData' => false]) ?>
<div class="card card-body">
    <p>Belum ada siswa yang memiliki nilai <?= esc($atribut['label']) ?>. Setelah dihapus, atribut ini tidak dapat dikembalikan, dan kolom <code><?= esc($atribut['kode']) ?></code> hilang dari template import.</p>
    <form method="post" action="<?= esc(url_to('panel.atribut_siswa.hapus', $atribut['id']), 'attr') ?>" class="d-flex flex-wrap gap-2">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="DELETE">
        <button class="btn btn-danger" type="submit"><?= view('komponen/ikon', ['name' => 'x'], ['saveData' => false]) ?> Hapus</button>
        <a class="btn btn-outline-primary" href="<?= esc(url_to('panel.atribut_siswa.index'), 'attr') ?>">Batal</a>
    </form>
</div>
<?= $this->endSection() ?>
