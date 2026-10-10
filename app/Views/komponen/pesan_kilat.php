<?php
/**
 * Flash messages shown above the page content after a redirect (docs/08 UI-28).
 * Render with ['saveData' => false].
 *
 * @var string|null $sukses Success message (flashdata 'sukses')
 * @var string|null $galat  Error message (flashdata 'galat')
 */
?>
<?php if (! empty($sukses) || ! empty($galat)): ?>
<div class="pesan-kilat">
<?php if (! empty($galat)): ?>
    <div class="alert alert-danger" role="alert"><?= view('komponen/ikon', ['name' => 'circle-alert'], ['saveData' => false]) ?><span><?= esc($galat) ?></span></div>
<?php endif ?>
<?php if (! empty($sukses)): ?>
    <div class="alert alert-success" role="status"><?= view('komponen/ikon', ['name' => 'circle-check'], ['saveData' => false]) ?><span><?= esc($sukses) ?></span></div>
<?php endif ?>
</div>
<?php endif ?>
