<?php
/**
 * Student photo in a 3:4 frame, or initials on gray when there is no photo
 * (docs/08 UI-22 to UI-25). Render with ['saveData' => false].
 *
 * @var string      $name       Student name
 * @var string|null $src        Photo URL, or null/empty when the student has no photo
 * @var string      $size       'daftar' (36×48), 'profil' (150×200), or 'besar' (240×320)
 * @var bool        $decorative True when the name is already written next to the photo (alt="")
 */
$size ??= 'daftar';
$decorative ??= true;
$dimensions = ['daftar' => [36, 48], 'profil' => [150, 200], 'besar' => [240, 320]];
[$width, $height] = $dimensions[$size];
$class = 'foto-siswa foto-siswa-' . $size;
?>
<?php if (! empty($src)): ?>
<img class="<?= $class ?>" src="<?= esc($src) ?>" alt="<?= $decorative ? '' : esc($name) ?>" width="<?= $width ?>" height="<?= $height ?>" loading="lazy">
<?php else: ?>
<?php
    $words    = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY);
    $initials = mb_strtoupper(implode('', array_map(static fn ($w) => mb_substr($w, 0, 1), array_slice($words, 0, 2))));
?>
<?php if ($decorative): ?>
<span class="<?= $class ?>" aria-hidden="true"><?= esc($initials) ?></span>
<?php else: ?>
<span class="<?= $class ?>" role="img" aria-label="<?= esc($name . ', belum ada foto') ?>"><?= esc($initials) ?></span>
<?php endif ?>
<?php endif ?>
