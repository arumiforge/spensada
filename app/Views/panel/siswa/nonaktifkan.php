<?php
/**
 * Deactivate a student (docs/09 HAL-MD-10, docs/04 FS-MD-04 item 4).
 * Carries the open period's ID as guard (docs/09 RT-12). A period that has
 * not started is cancelled, so the date field is left out (docs/06 §6.6
 * rule 4); for "Salah input" the help text says the date is not used.
 *
 * @var array<string, mixed> $siswa
 * @var array<string, mixed> $periode    The open period
 * @var bool                 $belumMulai The period starts after today
 * @var string               $hariIni    Y-m-d
 */
$galat  = validation_errors();
$alasan = (string) old('alasan_nonaktif', '', false);
$labels = ['tanggal_selesai' => 'Tanggal terakhir aktif', 'alasan_nonaktif' => 'Alasan', 'keterangan_nonaktif' => 'Keterangan'];
$tanda  = static function (string $field, string $bantuan = '') use ($galat): string {
    $ids = trim((isset($galat[$field]) ? $field . '-galat ' : '') . $bantuan);

    return (isset($galat[$field]) ? ' is-invalid" aria-invalid="true' : '') . '"' . ($ids === '' ? '' : ' aria-describedby="' . $ids . '"');
};
$pesan  = static fn (string $field): string => isset($galat[$field]) ? '<div class="invalid-feedback" id="' . $field . '-galat">' . esc($galat[$field]) . '</div>' : '';
?>
<?= $this->extend('layout/panel') ?>

<?= $this->section('content') ?>
<?= view('komponen/kepala_halaman', ['title' => 'Nonaktifkan ' . $siswa['nama'] . '?', 'context' => 'Aktif sejak ' . format_date($periode['tanggal_mulai'])], ['saveData' => false]) ?>

<?= view('panel/penempatan/_galat', ['galat' => $galat, 'labels' => $labels], ['saveData' => false]) ?>

<div class="row"><div class="col-lg-8 col-xl-6">
<form method="post" action="<?= esc(url_to('panel.siswa.nonaktifkan', $siswa['id']), 'attr') ?>" class="card card-body">
    <?= csrf_field() ?>
    <input type="hidden" name="periode_id" value="<?= esc((string) $periode['id'], 'attr') ?>">
    <ul>
        <li><?= esc($siswa['nama']) ?> tidak punya status kehadiran setelah tanggal terakhir aktif, sehingga tidak dihitung Alpa.</li>
        <li>Akun siswanya nonaktif, dan sesi yang sedang berjalan berakhir.</li>
        <li>Riwayat kehadiran sampai tanggal terakhir aktif tetap tersimpan.</li>
    </ul>
<?php if ($belumMulai): ?>
    <p>Masa aktif ini belum dimulai, sehingga dibatalkan. <?= esc($siswa['nama']) ?> tidak punya status pada tanggal mana pun dalam masa aktif ini.</p>
<?php else: ?>
    <div class="mb-3">
        <label class="form-label" for="tanggal_selesai">Tanggal terakhir aktif</label>
        <input class="form-control<?= $tanda('tanggal_selesai', 'tanggal_selesai-bantuan') ?> type="date" id="tanggal_selesai" name="tanggal_selesai" min="<?= esc($periode['tanggal_mulai'], 'attr') ?>" max="<?= esc($hariIni, 'attr') ?>" value="<?= esc((string) old('tanggal_selesai', $hariIni, false), 'attr') ?>">
        <?= $pesan('tanggal_selesai') ?>
        <div class="form-text" id="tanggal_selesai-bantuan">Tidak dipakai untuk alasan Salah input: masa aktifnya dibatalkan seluruhnya.</div>
    </div>
<?php endif ?>
    <div class="mb-3">
        <label class="form-label" for="alasan_nonaktif">Alasan</label>
        <select class="form-select<?= $tanda('alasan_nonaktif') ?> id="alasan_nonaktif" name="alasan_nonaktif" required>
            <option value="">Pilih alasan</option>
<?php foreach (config('Label')->codes['masa_aktif.alasan_nonaktif'] as $kode => $nama): ?>
            <option value="<?= esc($kode, 'attr') ?>"<?= $alasan === $kode ? ' selected' : '' ?>><?= esc($nama) ?></option>
<?php endforeach ?>
        </select>
        <?= $pesan('alasan_nonaktif') ?>
    </div>
    <div class="mb-4">
        <label class="form-label" for="keterangan_nonaktif">Keterangan <span class="text-body-secondary">(wajib bila alasan Lainnya)</span></label>
        <textarea class="form-control<?= $tanda('keterangan_nonaktif') ?> id="keterangan_nonaktif" name="keterangan_nonaktif" rows="2" maxlength="255"><?= esc((string) old('keterangan_nonaktif', '', false)) ?></textarea>
        <?= $pesan('keterangan_nonaktif') ?>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-danger" type="submit"><?= view('komponen/ikon', ['name' => 'circle-x'], ['saveData' => false]) ?> Nonaktifkan</button>
        <a class="btn btn-outline-primary" href="<?= esc(url_to('panel.siswa.lihat', $siswa['id']), 'attr') ?>">Batal</a>
    </div>
</form>
</div></div>
<?= $this->endSection() ?>
