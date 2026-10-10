<?php
/**
 * Student portal layout, mobile-first (docs/08 UI-35): top bar with logo and page
 * title; the menu (Riwayat, Izin, Akun, docs/09 §4.2) sits at the bottom on phones
 * and tablets and moves into the top bar from 1024 px. Items show by right and route
 * (App\Libraries\MenuPortal).
 *
 * @var string|null $title Page title
 */
$judul = empty($title) ? 'Spensada' : $title;
$menu  = (new \App\Libraries\MenuPortal())->untuk(service('akunAktif'), service('request')->getUri()->getPath());
?>
<!doctype html>
<html lang="id">
<head>
<?= $this->include('layout/head') ?>
</head>
<body>
<a class="visually-hidden-focusable lewati-isi" href="#isi">Lewati ke isi</a>
<header class="portal-atas">
    <img src="<?= esc((new \App\Services\MasterData\IdentitasSekolah())->urlLogo(), 'attr') ?>" alt="" width="32" height="32">
    <span class="fw-bold"><?= esc($judul) ?></span>
<?php if ($menu !== []): ?>
    <nav class="portal-menu" aria-label="Menu portal">
        <ul class="nav nav-fill h-100">
<?php foreach ($menu as $item): ?>
            <li class="nav-item"><a class="nav-link text-reset d-flex flex-column align-items-center<?= $item['aktif'] ? ' fw-bold' : '' ?>" href="<?= esc(site_url($item['alamat']), 'attr') ?>"<?= $item['aktif'] ? ' aria-current="page"' : '' ?>><?= view('komponen/ikon', ['name' => $item['ikon']], ['saveData' => false]) ?> <?= esc($item['label']) ?></a></li>
<?php endforeach ?>
        </ul>
    </nav>
<?php endif ?>
</header>
<main id="isi" class="portal-isi">
    <?= view('komponen/pesan_kilat', ['sukses' => session()->getFlashdata('sukses'), 'galat' => session()->getFlashdata('galat')], ['saveData' => false]) ?>
    <?= $this->renderSection('content') ?>
</main>
</body>
</html>
