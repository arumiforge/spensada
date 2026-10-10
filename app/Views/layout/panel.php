<?php
/**
 * Staff panel layout (docs/08 UI-29): side menu from 1024 px, folding menu
 * (<details>, works without JavaScript) below that, menu items by right
 * (UI-31, docs/09 §4.1). The school year in the top bar comes with FASE-02;
 * the emergency bar (UI-30) with FASE-05.
 *
 * @var string|null $title Page title
 */
$akunAktif = service('akunAktif');
$menu      = (new \App\Libraries\MenuPanel())->untuk($akunAktif, service('request')->getUri()->getPath());
$roleLabel = config('Label')->codes['akun_role.role'];
$roles     = array_values(array_filter(array_map(static fn (string $role): ?string => $roleLabel[$role] ?? null, $akunAktif->roles())));
$menuHtml  = view('layout/panel_menu', ['menu' => $menu], ['saveData' => false]);
?>
<!doctype html>
<html lang="id">
<head>
<?= $this->include('layout/head') ?>
</head>
<body>
<a class="visually-hidden-focusable lewati-isi" href="#isi">Lewati ke isi</a>
<div class="panel">
    <nav class="panel-samping" aria-label="Menu utama">
        <div class="merek mb-3"><img src="<?= base_url('aset/logo/logo-sekolah.png') ?>" alt="" width="32" height="31"><span>Spensada</span></div>
        <?= $menuHtml ?>
    </nav>
    <div>
        <header class="panel-atas">
            <details class="menu-lipat">
                <summary><?= view('komponen/ikon', ['name' => 'menu'], ['saveData' => false]) ?><span class="visually-hidden">Menu</span></summary>
                <nav aria-label="Menu utama">
                    <?= $menuHtml ?>
                </nav>
            </details>
            <div class="merek"><img src="<?= base_url('aset/logo/logo-sekolah.png') ?>" alt="" width="32" height="31"><span>Spensada</span></div>
<?php if ($akunAktif->akun() !== null): ?>
            <details class="menu-akun ms-auto">
                <summary>
                    <?= view('komponen/ikon', ['name' => 'user'], ['saveData' => false]) ?>
                    <span class="menu-akun-nama"><?= esc($akunAktif->akun()['nama'] ?? $akunAktif->akun()['username']) ?></span>
<?php if ($roles !== []): ?>
                    <span class="menu-akun-role"><?= esc(implode(', ', $roles)) ?></span>
<?php endif ?>
                </summary>
                <div class="menu-akun-isi">
                    <a href="<?= url_to('akun.password.ubah') ?>"><?= view('komponen/ikon', ['name' => 'key-round'], ['saveData' => false]) ?> Ganti password</a>
                    <form method="post" action="<?= url_to('akun.login.keluar') ?>">
                        <?= csrf_field() ?>
                        <button type="submit"><?= view('komponen/ikon', ['name' => 'log-out'], ['saveData' => false]) ?> Logout</button>
                    </form>
                </div>
            </details>
<?php endif ?>
        </header>
        <main id="isi" class="panel-isi">
            <?= view('komponen/pesan_kilat', ['sukses' => session()->getFlashdata('sukses'), 'galat' => session()->getFlashdata('galat')], ['saveData' => false]) ?>
            <?= $this->renderSection('content') ?>
        </main>
    </div>
</div>
</body>
</html>
