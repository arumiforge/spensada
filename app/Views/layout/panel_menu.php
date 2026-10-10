<?php
/**
 * Panel menu list, shared by the side menu and the folding menu (docs/08 UI-29, UI-31).
 *
 * @var array<string, list<array<string, mixed>>> $menu From App\Libraries\MenuPanel
 */
?>
<?php foreach ($menu as $grup => $items): ?>
<?php if ($grup !== ''): ?>
<p class="menu-grup"><?= esc($grup) ?></p>
<?php endif ?>
<ul class="menu-daftar">
<?php foreach ($items as $item): ?>
    <li><a href="<?= esc(site_url($item['alamat']), 'attr') ?>"<?= $item['aktif'] ? ' aria-current="page"' : '' ?>><?= view('komponen/ikon', ['name' => $item['ikon']], ['saveData' => false]) ?> <?= esc($item['label']) ?></a></li>
<?php endforeach ?>
</ul>
<?php endforeach ?>
