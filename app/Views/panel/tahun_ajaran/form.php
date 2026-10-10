<?php
/**
 * Add or edit a school year with both semesters (docs/09 HAL-MD-02, docs/11 VAL-07).
 * Edit carries `versi` (RT-12).
 *
 * @var string                    $title
 * @var array<string, mixed>|null $ta    Null when adding
 */
use App\Services\MasterData\TahunAjaran;

$galat  = validation_errors();
$labels = TahunAjaran::LABEL;
$simpan = $ta === null ? [] : TahunAjaran::isian($ta);
$aksi   = $ta === null ? url_to('panel.tahun_ajaran.simpan') : url_to('panel.tahun_ajaran.perbarui', $ta['id']);

$isian = static function (string $field, string $type, string $label, string $attrs = '') use ($galat, $simpan): string {
    $invalid = isset($galat[$field]);
    $html    = '<div class="mb-3"><label class="form-label" for="' . $field . '">' . esc($label) . '</label>'
        . '<input class="form-control' . ($invalid ? ' is-invalid' : '') . '" type="' . $type . '" id="' . $field . '" name="' . $field . '" required'
        . ' value="' . esc((string) old($field, $simpan[$field] ?? ''), 'attr') . '"' . $attrs
        . ($invalid ? ' aria-invalid="true" aria-describedby="' . $field . '-galat"' : '') . '>';

    if ($invalid) {
        $html .= '<div class="invalid-feedback" id="' . $field . '-galat">' . esc($galat[$field]) . '</div>';
    }

    return $html . '</div>';
};
?>
<?= $this->extend('layout/panel') ?>

<?= $this->section('content') ?>
<?= view('komponen/kepala_halaman', ['title' => $title, 'context' => $ta['nama'] ?? null], ['saveData' => false]) ?>

<?php if ($galat !== []): ?>
<div class="alert alert-danger" role="alert" id="ringkasan-galat">
    <p class="mb-1">Periksa <?= format_number(count($galat)) ?> isian yang ditandai.</p>
    <ul class="mb-0">
<?php foreach ($galat as $field => $pesan): ?>
        <li><a href="#<?= esc($field, 'attr') ?>"><?= esc($labels[$field] ?? $field) ?></a>: <?= esc($pesan) ?></li>
<?php endforeach ?>
    </ul>
</div>
<?php endif ?>

<div class="row"><div class="col-lg-8 col-xl-6">
<form method="post" action="<?= esc($aksi, 'attr') ?>" class="card card-body">
    <?= csrf_field() ?>
<?php if ($ta !== null): ?>
    <input type="hidden" name="_method" value="PATCH">
    <input type="hidden" name="versi" value="<?= esc($ta['updated_at'], 'attr') ?>">
<?php endif ?>
    <?= $isian('nama', 'text', $labels['nama'], ' maxlength="9" inputmode="numeric" aria-describedby="nama-bantuan"') ?>
    <p class="form-text" id="nama-bantuan">Contoh: 2026/2027.</p>
    <div class="row">
        <div class="col-sm-6"><?= $isian('tanggal_mulai', 'date', $labels['tanggal_mulai']) ?></div>
        <div class="col-sm-6"><?= $isian('tanggal_selesai', 'date', $labels['tanggal_selesai']) ?></div>
    </div>
<?php foreach (['ganjil' => 'Semester ganjil', 'genap' => 'Semester genap'] as $jenis => $judul): ?>
    <fieldset class="mb-2">
        <legend class="form-label fs-6 fw-semibold"><?= esc($judul) ?></legend>
        <div class="row">
            <div class="col-sm-6"><?= $isian("{$jenis}_mulai", 'date', 'Tanggal mulai') ?></div>
            <div class="col-sm-6"><?= $isian("{$jenis}_selesai", 'date', 'Tanggal selesai') ?></div>
        </div>
    </fieldset>
<?php endforeach ?>
    <p class="form-text">Tanggal di luar kedua semester, misalnya libur antarsemester, bukan hari sekolah.</p>
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-primary" type="submit"><?= view('komponen/ikon', ['name' => 'check'], ['saveData' => false]) ?> Simpan</button>
        <a class="btn btn-outline-primary" href="<?= esc(url_to('panel.tahun_ajaran.index'), 'attr') ?>">Batal</a>
    </div>
</form>
</div></div>
<?= $this->endSection() ?>
