<?php
/**
 * Placement per class (docs/09 HAL-MD-12, docs/04 FS-MD-05 item 3): pick the
 * source and target class, then the source's active students, all checked.
 * Placement by file (HAL-MD-13) is added here in FASE-03.
 *
 * @var string                          $title
 * @var list<array<string, mixed>>      $rombel Class choices
 * @var array<string, mixed>|null       $asal
 * @var array<string, mixed>|null       $tujuan
 * @var bool                            $sama   Source and target are the same class
 * @var list<array<string, mixed>>|null $siswa  Students of the source class, null until both are chosen
 */
$galat   = validation_errors();
$labels  = ['asal' => 'Kelas asal', 'tujuan' => 'Kelas tujuan', 'tanggal_mulai' => 'Tanggal mulai', 'siswa' => 'Siswa'];
$adaLama = old('tanggal_mulai') !== null;
$dipilih = array_map('strval', (array) old('siswa', []));
?>
<?= $this->extend('layout/panel') ?>

<?= $this->section('content') ?>
<?= view('komponen/kepala_halaman', ['title' => $title], ['saveData' => false]) ?>

<?= view('panel/penempatan/_galat', ['galat' => $galat, 'labels' => $labels], ['saveData' => false]) ?>

<form method="get" action="<?= esc(url_to('panel.penempatan.kelas'), 'attr') ?>" class="card card-body mb-3">
    <h2 class="h5">Per kelas</h2>
    <p class="form-text mt-0">Pilih kelas asal dan kelas tujuan, misalnya untuk kenaikan kelas ke tahun ajaran baru.</p>
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label" for="asal">Kelas asal</label>
            <?= view('panel/penempatan/_pilih_kelas', ['name' => 'asal', 'rombel' => $rombel, 'nilai' => (string) ($asal['id'] ?? ''), 'galat' => $galat['asal'] ?? null], ['saveData' => false]) ?>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="tujuan">Kelas tujuan</label>
            <?= view('panel/penempatan/_pilih_kelas', ['name' => 'tujuan', 'rombel' => $rombel, 'nilai' => (string) ($tujuan['id'] ?? ''), 'galat' => $galat['tujuan'] ?? null], ['saveData' => false]) ?>
        </div>
    </div>
    <div><button class="btn btn-outline-primary" type="submit"><?= view('komponen/ikon', ['name' => 'users'], ['saveData' => false]) ?> Tampilkan siswa</button></div>
</form>

<?php if ($sama): ?>
<p class="alert alert-warning">Kelas tujuan harus berbeda dengan kelas asal.</p>
<?php elseif ($siswa === []): ?>
<p class="halaman-daftar-kosong">Tidak ada siswa aktif di kelas <?= esc($asal['nama']) ?> (<?= esc($asal['tahun_ajaran']) ?>).</p>
<?php elseif ($siswa !== null): ?>
<form method="post" action="<?= esc(url_to('panel.penempatan.simpan_kelas'), 'attr') ?>" class="card card-body">
    <?= csrf_field() ?>
    <input type="hidden" name="asal" value="<?= esc((string) $asal['id'], 'attr') ?>">
    <input type="hidden" name="tujuan" value="<?= esc((string) $tujuan['id'], 'attr') ?>">
    <h2 class="h5"><?= esc($asal['nama']) ?> (<?= esc($asal['tahun_ajaran']) ?>) ke <?= esc($tujuan['nama']) ?> (<?= esc($tujuan['tahun_ajaran']) ?>)</h2>
    <div class="mb-3">
        <label class="form-label" for="tanggal_mulai">Tanggal mulai</label>
        <input class="form-control w-auto<?= isset($galat['tanggal_mulai']) ? ' is-invalid" aria-invalid="true' : '' ?>" type="date" id="tanggal_mulai" name="tanggal_mulai" required value="<?= esc((string) old('tanggal_mulai', $tujuan['ta_mulai']), 'attr') ?>" aria-describedby="<?= isset($galat['tanggal_mulai']) ? 'tanggal_mulai-galat ' : '' ?>tanggal_mulai-bantuan">
<?php if (isset($galat['tanggal_mulai'])): ?>
        <div class="invalid-feedback" id="tanggal_mulai-galat"><?= esc($galat['tanggal_mulai']) ?></div>
<?php endif ?>
        <div class="form-text" id="tanggal_mulai-bantuan">Bawaan: tanggal mulai tahun ajaran <?= esc($tujuan['tahun_ajaran']) ?>, <?= esc(format_date($tujuan['ta_mulai'])) ?>.</div>
    </div>
    <fieldset class="mb-3" id="siswa"<?= isset($galat['siswa']) ? ' aria-describedby="siswa-galat"' : '' ?>>
        <legend class="form-label fs-6">Siswa aktif di kelas <?= esc($asal['nama']) ?> (<?= format_number(count($siswa)) ?>)</legend>
        <p class="form-text mt-0">Lepas centang siswa yang tidak naik kelas atau yang lulus.</p>
<?php if (isset($galat['siswa'])): ?>
        <div class="text-danger small mb-2" id="siswa-galat"><?= esc($galat['siswa']) ?></div>
<?php endif ?>
<?php foreach ($siswa as $s): ?>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="siswa-<?= esc((string) $s['id'], 'attr') ?>" name="siswa[]" value="<?= esc((string) $s['id'], 'attr') ?>"<?= ! $adaLama || in_array((string) $s['id'], $dipilih, true) ? ' checked' : '' ?>>
            <label class="form-check-label" for="siswa-<?= esc((string) $s['id'], 'attr') ?>"><?= esc($s['nama']) ?> <span class="text-body-secondary">· NISN <?= esc($s['nisn']) ?></span></label>
        </div>
<?php endforeach ?>
    </fieldset>
    <div><button class="btn btn-primary" type="submit">Lanjutkan <?= view('komponen/ikon', ['name' => 'chevron-right'], ['saveData' => false]) ?></button></div>
</form>
<?php endif ?>
<?= $this->endSection() ?>
