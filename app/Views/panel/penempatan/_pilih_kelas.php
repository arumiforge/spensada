<?php
/**
 * Class <select> grouped by school year. Render with ['saveData' => false].
 *
 * @var string                     $name     Field name and id
 * @var list<array<string, mixed>> $rombel   Rows of Penempatan::daftarRombel()
 * @var string                     $nilai    Selected rombel ID
 * @var string|null                $galat    Field error
 */
$grup = [];
foreach ($rombel as $r) {
    $grup[$r['tahun_ajaran']][] = $r;
}
?>
<select class="form-select<?= $galat !== null ? ' is-invalid" aria-invalid="true" aria-describedby="' . esc($name, 'attr') . '-galat' : '' ?>" id="<?= esc($name, 'attr') ?>" name="<?= esc($name, 'attr') ?>" required>
    <option value="">Pilih kelas</option>
<?php foreach ($grup as $tahun => $daftar): ?>
    <optgroup label="Tahun ajaran <?= esc($tahun, 'attr') ?>">
<?php foreach ($daftar as $r): ?>
        <option value="<?= esc((string) $r['id'], 'attr') ?>"<?= (string) $r['id'] === $nilai ? ' selected' : '' ?>><?= esc($r['nama']) ?> (<?= esc($tahun) ?>)</option>
<?php endforeach ?>
    </optgroup>
<?php endforeach ?>
</select>
<?php if ($galat !== null): ?>
<div class="invalid-feedback" id="<?= esc($name, 'attr') ?>-galat"><?= esc($galat) ?></div>
<?php endif ?>
