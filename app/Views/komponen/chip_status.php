<?php
/**
 * Daily status chip: color, icon, and text (docs/08 UI-01, UI-12, UI-28).
 * Render with ['saveData' => false].
 *
 * @var string|null $status `status_harian.status` code; empty or null = Belum hadir
 */
$code  = (string) ($status ?? '');
$label = config('Label')->status[$code];
?>
<span class="chip-status status-<?= esc($code === '' ? 'belum' : $code) ?>"><?= view('komponen/ikon', ['name' => $label['icon']], ['saveData' => false]) ?><?= esc($label['label']) ?></span>
