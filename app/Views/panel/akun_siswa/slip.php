<?php
/**
 * Account slips ready to print, shown once as the direct answer of the write
 * (docs/09 RT-09, docs/08 UI-57 to UI-59): 8 A7 slips per A4 page with
 * dashed cut lines; a reset shows one slip. Formal text, no "kamu" (UI-49).
 * Print layout: `.slip-a4` and `.slip*` rules in spensada.css.
 *
 * @var list<array{nama: string, nisn: string, kelas: string, password: string}> $slips
 * @var string $title
 * @var string $namaSekolah Official school name, may be empty
 * @var string $kembali     Address of the "Selesai" link
 */
$ikon   = static fn (string $name): string => view('komponen/ikon', ['name' => $name], ['saveData' => false]);
$alamat = rtrim((string) preg_replace('#^https?://#', '', base_url()), '/');
$logo   = url_to('publik.logo.index');
?>
<?= $this->extend('layout/panel') ?>

<?= $this->section('content') ?>
<div class="d-print-none">
<?= view('komponen/kepala_halaman', ['title' => $title, 'context' => format_number(count($slips)) . ' slip'], ['saveData' => false]) ?>
<div class="alert alert-warning" role="status"><?= $ikon('triangle-alert') ?> <span>Password hanya tampil sekali. Cetak atau simpan sekarang.</span></div>
<p class="form-text">Cetak dengan skala 100% dan tanpa header atau footer browser.</p>
<div class="d-flex flex-wrap gap-2 mb-3">
    <button class="btn btn-primary" type="button" hidden data-cetak><?= $ikon('printer') ?> Cetak</button>
    <a class="btn btn-link" href="<?= esc($kembali, 'attr') ?>">Selesai</a>
</div>
</div>
<div class="slip-a4">
<?php foreach ($slips as $slip): ?>
    <section class="slip" aria-label="Slip akun <?= esc($slip['nama'], 'attr') ?>">
        <div class="slip-kepala">
            <img src="<?= esc($logo, 'attr') ?>" alt="" width="32" height="32">
            <div><b><?= esc($namaSekolah) ?></b><br><span>Akun Spensada</span></div>
        </div>
        <dl class="slip-data">
            <dt>Nama</dt><dd><?= esc($slip['nama']) ?></dd>
            <dt>NISN</dt><dd><?= esc($slip['nisn']) ?></dd>
            <dt>Kelas</dt><dd><?= esc($slip['kelas'] === '' ? '–' : $slip['kelas']) ?></dd>
        </dl>
        <div>Password awal</div>
        <div class="slip-password"><span><?= esc(substr($slip['password'], 0, 4)) ?></span><span><?= esc(substr($slip['password'], 4)) ?></span></div>
        <div>Buka <span class="slip-alamat"><?= esc($alamat) ?></span></div>
        <ol>
            <li>Login dengan NISN dan password di atas.</li>
            <li>Ganti password, lalu simpan baik-baik.</li>
        </ol>
        <div>Lupa password? Hubungi wali kelas.</div>
    </section>
<?php endforeach ?>
</div>
<script src="<?= esc(base_url('aset/js/salin.js') . '?v=' . config('Spensada')->versi, 'attr') ?>" defer></script>
<?= $this->endSection() ?>
