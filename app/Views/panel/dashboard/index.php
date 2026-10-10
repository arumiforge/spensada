<?= $this->extend('layout/panel') ?>

<?= $this->section('content') ?>
<?= view('komponen/kepala_halaman', ['title' => 'Dashboard hari ini'], ['saveData' => false]) ?>
<p>Halaman ini sedang disiapkan.</p>
<?= $this->endSection() ?>
