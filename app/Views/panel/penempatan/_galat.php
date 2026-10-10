<?php
/**
 * Error summary above a form (docs/11 VAL-07). Render with ['saveData' => false].
 *
 * @var array<string, string> $galat  Field => message
 * @var array<string, string> $labels Field => screen label
 */
?>
<?php if ($galat !== []): ?>
<div class="alert alert-danger" role="alert" id="ringkasan-galat">
    <p class="mb-1">Periksa <?= format_number(count($galat)) ?> isian yang ditandai.</p>
    <ul class="mb-0">
<?php foreach ($galat as $field => $pesan): ?>
        <li><a href="#<?= esc($field, 'attr') ?>"><?= esc($labels[$field] ?? $field) ?></a>: <?= esc($pesan) ?></li>
<?php endforeach ?>
    </ul>
</div>
<?php endif ?>
