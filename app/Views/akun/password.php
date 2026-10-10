<?php
/**
 * Change password (docs/09 HAL-AKN-03, docs/04 FS-AKN-02). While the account
 * must change its password the page uses the account layout without a menu
 * (docs/08 UI-38); otherwise the area layout. Fields are never refilled
 * (docs/12 SEC-07).
 *
 * @var string                $layout    'layout/akun', 'layout/panel' or 'layout/portal'
 * @var bool                  $wajib     The account must change its password
 * @var bool                  $tanpaLama Hide the old-password field (forced change right after login)
 * @var bool                  $siswa     Student account: "kamu" instead of "Anda"
 * @var array<string, string> $galat    Message per field
 */
$bantuan = 'Paling sedikit 8 karakter. Boleh memakai spasi. Kalimat pendek yang mudah ' . ($siswa ? 'kamu' : 'Anda') . ' ingat lebih aman daripada kata yang rumit.';
$isian   = array_filter([
    'password_lama'  => $tanpaLama ? null : ['Password lama', 'current-password', null],
    'password_baru'  => ['Password baru', 'new-password', $bantuan],
    'password_ulang' => ['Ulangi password baru', 'new-password', null],
]);
?>
<?= $this->extend($layout) ?>

<?= $this->section('content') ?>
<?php if ($wajib): ?>
<h1 class="h4 text-center mb-3">Ganti password</h1>
<p>Ganti password sebelum melanjutkan.</p>
<?php else: ?>
<?= view('komponen/kepala_halaman', ['title' => 'Ganti password'], ['saveData' => false]) ?>
<?php endif ?>
<?php if ($galat !== []): ?>
<div class="alert alert-danger" role="alert">
    <p class="mb-1">Periksa <?= format_number(count($galat)) ?> isian yang ditandai.</p>
    <ul class="mb-0">
<?php foreach (array_intersect_key($isian, $galat) as $name => [$label]): ?>
        <li><a href="#<?= esc($name, 'attr') ?>"><?= esc($label) ?></a></li>
<?php endforeach ?>
    </ul>
</div>
<?php endif ?>
<form method="post" action="<?= url_to('akun.password.ganti') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="_method" value="PUT">
<?php foreach ($isian as $name => [$label, $autocomplete, $help]): ?>
    <?= view('komponen/isian_password', [
        'id'           => $name,
        'name'         => $name,
        'label'        => $label,
        'autocomplete' => $autocomplete,
        'galat'        => $galat[$name] ?? null,
        'bantuan'      => $help,
    ], ['saveData' => false]) ?>
<?php endforeach ?>
    <button class="btn btn-primary<?= $wajib ? ' w-100' : '' ?>" type="submit"><?= view('komponen/ikon', ['name' => 'check'], ['saveData' => false]) ?> Simpan</button>
</form>
<?php if ($wajib): ?>
<form class="mt-3 text-center" method="post" action="<?= url_to('akun.login.keluar') ?>">
    <?= csrf_field() ?>
    <button class="btn btn-link" type="submit"><?= view('komponen/ikon', ['name' => 'log-out'], ['saveData' => false]) ?> Logout</button>
</form>
<?php endif ?>
<?= $this->endSection() ?>
