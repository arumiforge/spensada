<?php
/**
 * Login page (docs/09 HAL-AKN-01, docs/08 UI-38). Formal text, no "kamu".
 * Processing, error messages, and the show/hide password button come in L01-02.
 *
 * @var string|null $privacyNotice Privacy notice text from the school (docs/12 SEC-68), may be empty
 */
?>
<?= $this->extend('layout/akun') ?>

<?= $this->section('content') ?>
<h1 class="h4 text-center mb-4">Login</h1>
<form method="post" action="<?= site_url('login') ?>">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label" for="identitas">NISN atau username</label>
        <input class="form-control" type="text" id="identitas" name="identitas" value="<?= esc(old('identitas', '')) ?>" autocomplete="username" autocapitalize="none" spellcheck="false">
    </div>
    <div class="mb-4">
        <label class="form-label" for="password">Password</label>
        <input class="form-control" type="password" id="password" name="password" autocomplete="current-password">
    </div>
    <button class="btn btn-primary w-100" type="submit"><?= view('komponen/ikon', ['name' => 'log-in'], ['saveData' => false]) ?> Login</button>
</form>
<p class="text-body-secondary small mt-4 mb-0">Siswa yang lupa password menghubungi wali kelas. Staf menghubungi admin. Di komputer bersama, logout setelah selesai.</p>
<?php if (! empty($privacyNotice)): ?>
<section class="border-top mt-4 pt-3 small" aria-label="Pemberitahuan privasi">
    <?= nl2br(esc($privacyNotice)) ?>
</section>
<?php endif ?>
<?= $this->endSection() ?>
