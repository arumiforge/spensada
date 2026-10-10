<?php
/**
 * Tabs of the student profile (docs/09 HAL-MD-06): "Profil", and "Log data"
 * for HA-MD-10 holders. "Kehadiran" (HAL-LAP-06) is added with its page.
 * Render with ['saveData' => false].
 *
 * @var int    $id    Student ID
 * @var string $aktif 'profil' or 'log'
 * @var bool   $log   Show the "Log data" tab
 */
$tab = static fn (string $kode, string $teks, string $url): string => '<li class="nav-item"><a class="nav-link' . ($aktif === $kode ? ' active" aria-current="page' : '') . '" href="' . esc($url, 'attr') . '">' . $teks . '</a></li>';
?>
<ul class="nav nav-tabs mb-3">
    <?= $tab('profil', 'Profil', url_to('panel.siswa.lihat', $id)) ?>
<?php if ($log): ?>
    <?= $tab('log', 'Log data', url_to('panel.log_data_siswa.index', $id)) ?>
<?php endif ?>
</ul>
