<?php
/**
 * School identity form (docs/09 HAL-MD-01, docs/04 FS-MD-01, docs/11 VAL-07).
 * Carries `versi` (RT-12). The current logo is shown as its preview (UI-28).
 *
 * @var string               $title
 * @var array<string, mixed> $identitas IdentitasSekolah::ambil()
 * @var string               $urlLogo   `/logo?v=`
 */
$galat  = validation_errors();
$labels = ['sekolah_nama' => 'Nama resmi sekolah', 'sekolah_alamat' => 'Alamat', 'logo' => 'Logo', 'privasi_teks' => 'Pemberitahuan privasi'];
$nilai  = static fn (string $field) => old($field, $identitas[$field] ?? '');
$isian  = static fn (string $field): string => isset($galat[$field]) ? ' is-invalid" aria-invalid="true' : '';
$pesan  = static fn (string $field): string => isset($galat[$field]) ? '<div class="invalid-feedback" id="' . $field . '-galat">' . esc($galat[$field]) . '</div>' : '';
$terang = static fn (string $field, string $bantuan = ''): string => trim((isset($galat[$field]) ? "{$field}-galat " : '') . $bantuan);
?>
<?= $this->extend('layout/panel') ?>

<?= $this->section('content') ?>
<?= view('komponen/kepala_halaman', ['title' => $title], ['saveData' => false]) ?>

<?php if ($galat !== []): ?>
<div class="alert alert-danger" role="alert" id="ringkasan-galat">
    <p class="mb-1">Periksa <?= format_number(count($galat)) ?> isian yang ditandai.</p>
    <ul class="mb-0">
<?php foreach ($galat as $field => $teks): ?>
        <li><a href="#<?= esc($field, 'attr') ?>"><?= esc($labels[$field] ?? $field) ?></a>: <?= esc($teks) ?></li>
<?php endforeach ?>
    </ul>
</div>
<?php endif ?>

<div class="row"><div class="col-lg-8 col-xl-6">
<form method="post" action="<?= esc(url_to('panel.sekolah.perbarui'), 'attr') ?>" enctype="multipart/form-data" class="card card-body">
    <?= csrf_field() ?>
    <input type="hidden" name="_method" value="PATCH">
    <input type="hidden" name="versi" value="<?= esc($identitas['versi'], 'attr') ?>">
    <div class="mb-3">
        <label class="form-label" for="sekolah_nama">Nama resmi sekolah</label>
        <input class="form-control<?= $isian('sekolah_nama') ?>" type="text" id="sekolah_nama" name="sekolah_nama" maxlength="150" required value="<?= esc($nilai('sekolah_nama'), 'attr') ?>"<?= isset($galat['sekolah_nama']) ? ' aria-describedby="sekolah_nama-galat"' : '' ?>>
        <?= $pesan('sekolah_nama') ?>
    </div>
    <div class="mb-3">
        <label class="form-label" for="sekolah_alamat">Alamat (opsional)</label>
        <input class="form-control<?= $isian('sekolah_alamat') ?>" type="text" id="sekolah_alamat" name="sekolah_alamat" maxlength="255" value="<?= esc($nilai('sekolah_alamat'), 'attr') ?>"<?= isset($galat['sekolah_alamat']) ? ' aria-describedby="sekolah_alamat-galat"' : '' ?>>
        <?= $pesan('sekolah_alamat') ?>
    </div>
    <fieldset class="mb-3">
        <legend class="form-label fs-6">Logo (opsional)</legend>
        <div class="d-flex align-items-center gap-3 mb-2">
            <img src="<?= esc($urlLogo, 'attr') ?>" alt="Logo yang dipakai sekarang" width="96" class="border rounded p-1">
            <p class="form-text m-0"><?= $identitas['sekolah_logo'] === null ? 'Sekarang memakai lambang bawaan.' : 'Logo yang dipakai sekarang.' ?></p>
        </div>
        <label class="form-label" for="logo">Ganti logo</label>
        <input class="form-control<?= $isian('logo') ?>" type="file" id="logo" name="logo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" aria-describedby="<?= esc($terang('logo', 'logo-bantuan'), 'attr') ?>">
        <?= $pesan('logo') ?>
        <div class="form-text" id="logo-bantuan">JPG, PNG, atau WebP, paling besar 10 MB. Logo diperkecil otomatis.</div>
<?php if ($identitas['sekolah_logo'] !== null): ?>
        <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" id="hapus_logo" name="hapus_logo" value="1">
            <label class="form-check-label" for="hapus_logo">Hapus logo dan pakai lambang bawaan</label>
        </div>
<?php endif ?>
    </fieldset>
    <div class="mb-4">
        <label class="form-label" for="privasi_teks">Pemberitahuan privasi (opsional)</label>
        <p class="form-text mt-0" id="privasi_teks-bantuan">Tampil di halaman login dan portal siswa. Baris baru menjadi paragraf baru. Kosongkan bila tidak perlu ditampilkan. Paling panjang 2.000 karakter.</p>
        <textarea class="form-control<?= $isian('privasi_teks') ?>" id="privasi_teks" name="privasi_teks" rows="8" maxlength="2000" aria-describedby="<?= esc($terang('privasi_teks', 'privasi_teks-bantuan'), 'attr') ?>"><?= esc($nilai('privasi_teks')) ?></textarea>
        <?= $pesan('privasi_teks') ?>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-primary" type="submit"><?= view('komponen/ikon', ['name' => 'check'], ['saveData' => false]) ?> Simpan</button>
    </div>
</form>
</div></div>
<?= $this->endSection() ?>
