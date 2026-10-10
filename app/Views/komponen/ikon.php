<?php
/**
 * Lucide icon from the sprite (docs/08 UI-20, UI-21). Always placed next to text,
 * so it is hidden from screen readers. Render with ['saveData' => false].
 *
 * @var string $name Symbol id in public/aset/ikon/ikon.svg
 */
$href = base_url('aset/ikon/ikon.svg') . '?v=' . config('Spensada')->versi . '#' . $name;
?>
<svg class="ikon" aria-hidden="true" focusable="false"><use href="<?= esc($href) ?>"></use></svg>
