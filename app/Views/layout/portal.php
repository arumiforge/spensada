<?php
/**
 * Student portal layout, mobile-first (docs/08 UI-35): top bar with logo and page
 * title; the menu sits at the bottom on phones and tablets and moves into the top
 * bar from 1024 px. Menu items (Riwayat, Izin, Akun) are added with their pages.
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
<header class="portal-atas">
    <img src="<?= base_url('aset/logo/logo-sekolah.png') ?>" alt="" width="32" height="31">
    <span class="fw-bold"><?= esc(empty($title) ? 'Spensada' : $title) ?></span>
    <nav class="portal-menu" aria-label="Menu portal">
        <!-- Riwayat, Izin, Akun (docs/09 §4.2): L01-08 -->
    </nav>
</header>
<main id="isi" class="portal-isi">
    <?= view('komponen/pesan_kilat', ['sukses' => session()->getFlashdata('sukses'), 'galat' => session()->getFlashdata('galat')], ['saveData' => false]) ?>
    <?= $this->renderSection('content') ?>
</main>
</body>
</html>
