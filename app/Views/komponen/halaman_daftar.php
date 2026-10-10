<?php
/**
 * List page body: total, table, and previous/next links, 50 rows per page by
 * default (docs/08 UI-26). Filters stay in the GET query; only `page` changes.
 * Render with ['saveData' => false].
 *
 * @var int    $total   Total rows across all pages
 * @var int    $page    Current page, starting at 1
 * @var int    $perPage Rows per page
 * @var string $table   HTML of the table for this page, built by the calling view
 * @var string $empty   Empty-state sentence, e.g. "Belum ada siswa."
 */
$perPage ??= 50;
$first    = ($page - 1) * $perPage + 1;
$last     = min($page * $perPage, $total);
$pageUrl  = static fn (int $n): string => (string) (clone current_url(true))->addQuery('page', (string) $n);
?>
<?php if ($total === 0): ?>
<p class="halaman-daftar-kosong"><?= esc($empty) ?></p>
<?php else: ?>
<p class="halaman-daftar-info">Menampilkan <?= format_number($first) ?>–<?= format_number($last) ?> dari <?= format_number($total) ?> data</p>
<div class="table-responsive"><?= $table ?></div>
<?php if ($total > $perPage): ?>
<nav aria-label="Halaman daftar">
    <ul class="pagination">
<?php if ($page > 1): ?>
        <li class="page-item"><a class="page-link" href="<?= esc($pageUrl($page - 1)) ?>" rel="prev"><?= view('komponen/ikon', ['name' => 'chevron-left'], ['saveData' => false]) ?> Sebelumnya</a></li>
<?php endif ?>
<?php if ($last < $total): ?>
        <li class="page-item"><a class="page-link" href="<?= esc($pageUrl($page + 1)) ?>" rel="next">Berikutnya <?= view('komponen/ikon', ['name' => 'chevron-right'], ['saveData' => false]) ?></a></li>
<?php endif ?>
    </ul>
</nav>
<?php endif ?>
<?php endif ?>
