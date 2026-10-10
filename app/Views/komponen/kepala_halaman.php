<?php
/**
 * Page header: the page's only h1, optional context line, and main actions on the
 * right (below the title on phones) (docs/08 UI-28). Render with ['saveData' => false].
 *
 * @var string      $title   Page title
 * @var string|null $context Context line, e.g. a date from format_date() and a class
 * @var string|null $actions HTML of the action buttons, built by the calling view
 */
?>
<div class="kepala-halaman">
    <div>
        <h1><?= esc($title) ?></h1>
<?php if (! empty($context)): ?>
        <p class="kepala-halaman-konteks"><?= esc($context) ?></p>
<?php endif ?>
    </div>
<?php if (! empty($actions)): ?>
    <div class="kepala-halaman-aksi"><?= $actions ?></div>
<?php endif ?>
</div>
