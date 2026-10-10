<?php
/**
 * Add or edit a rombel (docs/09 HAL-MD-03, docs/11 VAL-07). Edit carries
 * `versi` (RT-12); tingkat is locked once the rombel has a placement (E4).
 *
 * @var string                     $title
 * @var array<string, mixed>|null  $rombel      Null when adding
 * @var list<array<string, mixed>> $tahunAjaran School years to choose from when adding
 * @var int                        $tahunId     Selected school year
 * @var list<array<string, mixed>> $wali        Staff accounts to choose as wali kelas
 */
$galat   = validation_errors();
$labels  = ['tahun_ajaran_id' => 'Tahun ajaran', 'nama' => 'Nama kelas', 'tingkat' => 'Tingkat', 'wali_kelas_id' => 'Wali kelas'];
$nilai   = static fn (string $field): string => (string) old($field, (string) ($rombel[$field] ?? ''), false);
$isian   = static fn (string $field): string => isset($galat[$field]) ? ' is-invalid" aria-invalid="true" aria-describedby="' . $field . '-galat' : '';
$pesan   = static fn (string $field): string => isset($galat[$field]) ? '<div class="invalid-feedback" id="' . $field . '-galat">' . esc($galat[$field]) . '</div>' : '';
$kunci   = $rombel !== null && $rombel['punya_penempatan'];
$tahunId = (string) old('tahun_ajaran_id', (string) $tahunId, false);
$aksi    = $rombel === null ? url_to('panel.rombel.simpan') : url_to('panel.rombel.perbarui', $rombel['id']);
?>
<?= $this->extend('layout/panel') ?>

<?= $this->section('content') ?>
<?= view('komponen/kepala_halaman', ['title' => $title, 'context' => $rombel === null ? null : 'Kelas ' . $rombel['nama'] . ', tahun ajaran ' . $rombel['tahun_ajaran_nama']], ['saveData' => false]) ?>

<?php if ($galat !== []): ?>
<div class="alert alert-danger" role="alert" id="ringkasan-galat" tabindex="-1">
    <p class="mb-1">Periksa <?= format_number(count($galat)) ?> isian yang ditandai.</p>
    <ul class="mb-0">
<?php foreach ($galat as $field => $teks): ?>
        <li><a href="#<?= esc($field, 'attr') ?>"><?= esc($labels[$field] ?? $field) ?></a>: <?= esc($teks) ?></li>
<?php endforeach ?>
    </ul>
</div>
<?php endif ?>

<div class="row"><div class="col-lg-8 col-xl-6">
<form method="post" action="<?= esc($aksi, 'attr') ?>" class="card card-body">
    <?= csrf_field() ?>
<?php if ($rombel !== null): ?>
    <input type="hidden" name="_method" value="PATCH">
    <input type="hidden" name="versi" value="<?= esc($rombel['updated_at'], 'attr') ?>">
<?php endif ?>
<?php if ($rombel === null): ?>
    <div class="mb-3">
        <label class="form-label" for="tahun_ajaran_id">Tahun ajaran</label>
        <select class="form-select<?= $isian('tahun_ajaran_id') ?>" id="tahun_ajaran_id" name="tahun_ajaran_id" required>
<?php foreach ($tahunAjaran as $ta): ?>
            <option value="<?= esc((string) $ta['id'], 'attr') ?>"<?= (string) $ta['id'] === $tahunId ? ' selected' : '' ?>><?= esc($ta['nama']) ?><?= (int) $ta['aktif'] === 1 ? ' (aktif)' : '' ?></option>
<?php endforeach ?>
        </select>
        <?= $pesan('tahun_ajaran_id') ?>
    </div>
<?php endif ?>
    <div class="mb-3">
        <label class="form-label" for="nama">Nama kelas</label>
        <input class="form-control<?= $isian('nama') ?>" type="text" id="nama" name="nama" maxlength="20" required value="<?= esc($nilai('nama'), 'attr') ?>">
        <?= $pesan('nama') ?>
        <div class="form-text">Misalnya 7A. Boleh berisi huruf, angka, spasi, dan tanda hubung.</div>
    </div>
    <div class="mb-3">
        <label class="form-label" for="tingkat">Tingkat</label>
<?php if ($kunci): ?>
        <input type="hidden" name="tingkat" value="<?= esc((string) $rombel['tingkat'], 'attr') ?>">
<?php endif ?>
        <select class="form-select<?= $isian('tingkat') ?>" id="tingkat"<?= $kunci ? ' disabled aria-describedby="tingkat-kunci"' : ' name="tingkat" required' ?>>
<?php if ($rombel === null): ?>
            <option value="">Pilih tingkat</option>
<?php endif ?>
<?php foreach (\App\Services\MasterData\Rombel::TINGKAT as $t): ?>
            <option value="<?= $t ?>"<?= $nilai('tingkat') === $t ? ' selected' : '' ?>><?= $t ?></option>
<?php endforeach ?>
        </select>
        <?= $pesan('tingkat') ?>
<?php if ($kunci): ?>
        <div class="form-text" id="tingkat-kunci">Tingkat tidak dapat diubah karena kelas ini sudah memiliki siswa.</div>
<?php endif ?>
    </div>
    <div class="mb-4">
        <label class="form-label" for="wali_kelas_id">Wali kelas</label>
        <select class="form-select<?= $isian('wali_kelas_id') ?>" id="wali_kelas_id" name="wali_kelas_id">
            <option value="">Belum ada wali kelas</option>
<?php foreach ($wali as $akun): ?>
            <option value="<?= esc((string) $akun['id'], 'attr') ?>"<?= $nilai('wali_kelas_id') === (string) $akun['id'] ? ' selected' : '' ?>><?= esc($akun['nama']) ?> (<?= esc($akun['username']) ?>)<?= $akun['status'] !== 'aktif' ? ' – nonaktif' : '' ?></option>
<?php endforeach ?>
        </select>
        <?= $pesan('wali_kelas_id') ?>
        <div class="form-text">Pilih dari akun staf yang aktif. Penggantian wali kelas langsung berlaku.</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-primary" type="submit"><?= view('komponen/ikon', ['name' => 'check'], ['saveData' => false]) ?> Simpan</button>
        <a class="btn btn-outline-primary" href="<?= esc(url_to('panel.rombel.index') . '?tahun_ajaran=' . ($rombel['tahun_ajaran_id'] ?? $tahunId), 'attr') ?>">Batal</a>
    </div>
</form>
</div></div>
<?= $this->endSection() ?>
