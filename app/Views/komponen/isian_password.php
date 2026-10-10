<?php
/**
 * Password field with a show/hide button (docs/08 UI-38, FS-AKN-02). The
 * button appears only when JavaScript runs. Never fill the value (docs/12
 * SEC-07). Render with ['saveData' => false].
 *
 * @var string      $id           Field id
 * @var string      $name         Field name
 * @var string      $label        Label text
 * @var string      $autocomplete `current-password` or `new-password`
 * @var string|null $galat        Error message under the field
 * @var string|null $bantuan      Help text under the field
 */
$described = trim((empty($galat) ? '' : $id . '-galat ') . (empty($bantuan) ? '' : $id . '-bantuan'));
?>
<div class="mb-3">
    <label class="form-label" for="<?= esc($id, 'attr') ?>"><?= esc($label) ?></label>
    <div class="input-group has-validation">
        <input class="form-control<?= empty($galat) ? '' : ' is-invalid' ?>" type="password" id="<?= esc($id, 'attr') ?>" name="<?= esc($name, 'attr') ?>" autocomplete="<?= esc($autocomplete, 'attr') ?>"<?= $described === '' ? '' : ' aria-describedby="' . esc($described, 'attr') . '"' ?>>
        <button class="btn btn-outline-secondary" type="button" hidden data-isian-password="<?= esc($id, 'attr') ?>" aria-controls="<?= esc($id, 'attr') ?>" aria-pressed="false">
            <?= view('komponen/ikon', ['name' => 'eye'], ['saveData' => false]) ?><span class="visually-hidden">Tampilkan password</span>
        </button>
<?php if (! empty($galat)): ?>
        <div class="invalid-feedback" id="<?= esc($id, 'attr') ?>-galat"><?= esc($galat) ?></div>
<?php endif ?>
    </div>
<?php if (! empty($bantuan)): ?>
    <div class="form-text" id="<?= esc($id, 'attr') ?>-bantuan"><?= esc($bantuan) ?></div>
<?php endif ?>
</div>
<script src="<?= esc(base_url('aset/js/isian-password.js') . '?v=' . config('Spensada')->versi, 'attr') ?>" defer></script>
