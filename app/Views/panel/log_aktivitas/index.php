<?php
/**
 * Activity log list (docs/09 HAL-AKN-09, docs/08 UI-26).
 *
 * @var array<string, string>                 $saringan Valid filters in use
 * @var int                                   $page
 * @var int                                   $total
 * @var list<array<string, mixed>>            $rows
 * @var \App\Services\Akun\DaftarLogAktivitas $daftar
 * @var list<array<string, mixed>>            $akun     Accounts for the actor and account selects
 */
use App\Services\Akun\DaftarLogAktivitas;
use App\Services\Akun\LogAktivitas;

$labels   = config('Label')->codes['log_aktivitas.jenis'];
$indexUrl = url_to('panel.log_aktivitas.index');
$cepat    = ['' => 'Semua', 'lampiran' => 'Akses lampiran', 'login' => 'Login', 'scan_ditolak' => 'Scan ditolak server'];
$pilih    = static fn (string $nama, string $nilai): string => ($saringan[$nama] ?? '') === $nilai ? ' selected' : '';
?>
<?= $this->extend('layout/panel') ?>
<?= $this->section('content') ?>
<?= view('komponen/kepala_halaman', ['title' => 'Log aktivitas'], ['saveData' => false]) ?>

<nav aria-label="Saringan cepat" class="mb-3 d-flex flex-wrap gap-2">
<?php foreach ($cepat as $kode => $teks): ?>
<?php $aktif = ($saringan['cepat'] ?? '') === $kode; ?>
    <a class="btn btn-sm <?= $aktif ? 'btn-primary' : 'btn-outline-primary' ?>"<?= $aktif ? ' aria-current="page"' : '' ?> href="<?= esc($kode === '' ? $indexUrl : $indexUrl . '?cepat=' . $kode) ?>"><?= esc($teks) ?></a>
<?php endforeach ?>
</nav>

<form method="get" action="<?= esc($indexUrl) ?>" class="row g-2 align-items-end mb-3">
<?php if (isset($saringan['cepat'])): ?>
    <input type="hidden" name="cepat" value="<?= esc($saringan['cepat']) ?>">
<?php endif ?>
    <div class="col-12 col-md-4 col-lg-2">
        <label class="form-label" for="jenis">Jenis</label>
        <select class="form-select" id="jenis" name="jenis">
            <option value="">Semua jenis</option>
<?php foreach (LogAktivitas::JENIS as $kode): ?>
            <option value="<?= esc($kode) ?>"<?= $pilih('jenis', $kode) ?>><?= esc($labels[$kode] ?? $kode) ?></option>
<?php endforeach ?>
        </select>
    </div>
<?php foreach (['pelaku' => ['Pelaku', 'Semua pelaku'], 'akun' => ['Akun terdampak', 'Semua akun']] as $nama => [$teks, $semua]): ?>
    <div class="col-12 col-md-4 col-lg-2">
        <label class="form-label" for="<?= $nama ?>"><?= esc($teks) ?></label>
        <select class="form-select" id="<?= $nama ?>" name="<?= $nama ?>">
            <option value=""><?= esc($semua) ?></option>
<?php foreach ($akun as $a): ?>
            <option value="<?= esc($a['id']) ?>"<?= $pilih($nama, (string) $a['id']) ?>><?= esc(DaftarLogAktivitas::namaAkun($a['nama'], $a['username'], '')) ?> (<?= esc($a['username']) ?>)</option>
<?php endforeach ?>
        </select>
    </div>
<?php endforeach ?>
    <div class="col-6 col-md-4 col-lg-2">
        <label class="form-label" for="mulai">Tanggal mulai</label>
        <input class="form-control" type="date" id="mulai" name="mulai" value="<?= esc($saringan['mulai'] ?? '') ?>">
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <label class="form-label" for="selesai">Tanggal selesai</label>
        <input class="form-control" type="date" id="selesai" name="selesai" value="<?= esc($saringan['selesai'] ?? '') ?>">
    </div>
    <div class="col-12 col-md-4 col-lg-2 d-flex gap-2">
        <button type="submit" class="btn btn-primary">Terapkan</button>
<?php if ($saringan !== []): ?>
        <a class="btn btn-outline-secondary" href="<?= esc($indexUrl) ?>">Hapus saringan</a>
<?php endif ?>
    </div>
</form>

<?php if ($rows === [] && $total > 0): ?>
<p class="halaman-daftar-kosong">Halaman ini kosong. <a href="<?= esc((string) (clone current_url(true))->addQuery('page', '1')) ?>">Kembali ke halaman 1</a>.</p>
<?php else: ?>
<?php ob_start() ?>
<table class="table table-hover align-middle">
    <thead>
        <tr><th scope="col">Waktu</th><th scope="col">Jenis</th><th scope="col">Pelaku</th><th scope="col">Akun terdampak</th><th scope="col">Ringkasan</th></tr>
    </thead>
    <tbody>
<?php foreach ($rows as $row): ?>
        <tr>
            <td class="text-nowrap"><a href="<?= esc(url_to('panel.log_aktivitas.lihat', $row['id'])) ?>"><?= esc(format_datetime($row['created_at'])) ?></a></td>
            <td><?= esc($labels[$row['jenis']] ?? $row['jenis']) ?></td>
            <td><?= esc($row['pelaku_id'] === null ? 'Sistem' : DaftarLogAktivitas::namaAkun($row['pelaku_nama'], $row['pelaku_username'], '—')) ?></td>
            <td><?= esc($row['akun_id'] === null ? '—' : DaftarLogAktivitas::namaAkun($row['akun_nama'], $row['akun_username'], '—')) ?></td>
            <td><?= esc($daftar->ringkasan($row) ?: '—') ?></td>
        </tr>
<?php endforeach ?>
    </tbody>
</table>
<?php $table = (string) ob_get_clean() ?>
<?= view('komponen/halaman_daftar', ['total' => $total, 'page' => $page, 'perPage' => DaftarLogAktivitas::PER_PAGE, 'table' => $table, 'empty' => $saringan === [] ? 'Belum ada catatan di log aktivitas.' : 'Belum ada catatan yang cocok dengan saringan ini.'], ['saveData' => false]) ?>
<?php endif ?>
<?= $this->endSection() ?>
