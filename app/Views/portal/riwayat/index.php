<?= $this->extend('layout/portal') ?>

<?= $this->section('content') ?>
<?= view('komponen/kepala_halaman', ['title' => 'Riwayat'], ['saveData' => false]) ?>
<p>Halaman ini sedang disiapkan.</p>
<?= $this->endSection() ?>
