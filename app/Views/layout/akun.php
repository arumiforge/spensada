<?php
/**
 * Account layout: login and change password, a centered card (docs/08 UI-38).
 *
 * @var string|null $title      Page title
 * @var string|null $schoolName Official school name (pengaturan.sekolah_nama), may be empty
 * @var string|null $logoUrl    School logo route `/logo?v=` (FS-MD-01); the bundled emblem when unset
 */
?>
<!doctype html>
<html lang="id">
<head>
<?= $this->include('layout/head') ?>
</head>
<body>
<a class="visually-hidden-focusable lewati-isi" href="#isi">Lewati ke isi</a>
<div class="akun">
    <div class="card">
        <div class="card-body">
            <header class="akun-kepala mb-4">
                <img src="<?= esc($logoUrl ?? base_url('aset/logo/logo-sekolah.png'), 'attr') ?>" alt="<?= esc(empty($schoolName) ? 'Logo sekolah' : 'Logo ' . $schoolName) ?>" width="72"<?= isset($logoUrl) ? '' : ' height="70"' ?>>
                <span class="akun-kepala-produk">Spensada</span>
<?php if (! empty($schoolName)): ?>
                <span class="akun-kepala-sekolah"><?= esc($schoolName) ?></span>
<?php endif ?>
            </header>
            <main id="isi">
                <?= view('komponen/pesan_kilat', ['sukses' => session()->getFlashdata('sukses'), 'galat' => session()->getFlashdata('galat')], ['saveData' => false]) ?>
                <?= $this->renderSection('content') ?>
            </main>
        </div>
    </div>
</div>
</body>
</html>
