<?php
/**
 * Staff panel layout (docs/08 UI-29): side menu from 1024 px, folding menu
 * (<details>, works without JavaScript) below that. Menu items, school year,
 * and the account menu are added in L01-08; the emergency bar (UI-30) in FASE-05.
 *
 * @var string|null $title Page title
 */
?>
<!doctype html>
<html lang="id">
<head>
<?= $this->include('layout/head') ?>
</head>
<body>
<a class="visually-hidden-focusable lewati-isi" href="#isi">Lewati ke isi</a>
<div class="panel">
    <nav class="panel-samping" aria-label="Menu utama">
        <div class="merek mb-3"><img src="<?= base_url('aset/logo/logo-sekolah.png') ?>" alt="" width="32" height="31"><span>Spensada</span></div>
        <!-- Menu items (docs/09 §4.1): L01-08 -->
    </nav>
    <div>
        <header class="panel-atas">
            <details class="menu-lipat">
                <summary><?= view('komponen/ikon', ['name' => 'menu'], ['saveData' => false]) ?><span class="visually-hidden">Menu</span></summary>
                <nav aria-label="Menu utama">
                    <!-- Menu items (docs/09 §4.1): L01-08 -->
                </nav>
            </details>
            <div class="merek"><img src="<?= base_url('aset/logo/logo-sekolah.png') ?>" alt="" width="32" height="31"><span>Spensada</span></div>
            <!-- School year, user name, role, account menu (UI-29): L01-08 -->
        </header>
        <main id="isi" class="panel-isi">
            <?= view('komponen/pesan_kilat', ['sukses' => session()->getFlashdata('sukses'), 'galat' => session()->getFlashdata('galat')], ['saveData' => false]) ?>
            <?= $this->renderSection('content') ?>
        </main>
    </div>
</div>
</body>
</html>
