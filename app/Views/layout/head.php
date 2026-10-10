<?php
/**
 * Shared <head> content for the panel, portal, and account layouts: favicon tags
 * (.claude/memory/project.md) and the three CSS files in UI-73 order (docs/08).
 *
 * @var string|null $title Page title; " · Spensada" is appended
 */
$version = '?v=' . config('Spensada')->versi;
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc(empty($title) ? 'Spensada' : $title . ' · Spensada') ?></title>
<link rel="icon" href="<?= base_url('favicon.ico') ?>" sizes="any">
<link rel="icon" href="<?= base_url('aset/logo/favicon-32.png') ?>" type="image/png">
<link rel="apple-touch-icon" href="<?= base_url('apple-touch-icon.png') ?>">
<link rel="stylesheet" href="<?= esc(base_url('aset/vendor/bootstrap/5.3.8/css/bootstrap.min.css') . $version) ?>">
<link rel="stylesheet" href="<?= esc(base_url('aset/css/token.css') . $version) ?>">
<link rel="stylesheet" href="<?= esc(base_url('aset/css/spensada.css') . $version) ?>">
