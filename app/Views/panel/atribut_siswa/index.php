<?php
/**
 * Extra student attributes in display order (docs/09 HAL-MD-16, docs/04 FS-MD-09).
 *
 * @var list<array<string, mixed>> $rows From Services\MasterData\AtributSiswa::daftar()
 */
$tipe = config('Label')->codes['atribut_siswa.tipe'];
$ikon = static fn (string $name): string => view('komponen/ikon', ['name' => $name], ['saveData' => false]);
?>
<?= $this->extend('layout/panel') ?>

<?= $this->section('content') ?>
<?= view('komponen/kepala_halaman', [
    'title'   => 'Atribut tambahan',
    'actions' => '<a class="btn btn-primary" href="' . esc(url_to('panel.atribut_siswa.tambah'), 'attr') . '">' . $ikon('clipboard-list') . ' Tambah atribut</a>',
], ['saveData' => false]) ?>

<p>Atribut tambahan tampil di formulir dan profil siswa, serta menjadi kolom template import. Atribut ini tidak dipakai untuk presensi atau rekap. Jangan memakainya untuk data kesehatan atau data pribadi yang sensitif.</p>

<?php if ($rows === []): ?>
<p class="halaman-daftar-kosong">Belum ada atribut tambahan.</p>
<a class="btn btn-primary" href="<?= esc(url_to('panel.atribut_siswa.tambah'), 'attr') ?>"><?= $ikon('clipboard-list') ?> Tambah atribut</a>
<?php else: ?>
<div class="table-responsive">
<table class="table align-middle">
    <thead>
        <tr><th scope="col">Urutan</th><th scope="col">Label</th><th scope="col">Kode</th><th scope="col">Tipe</th><th scope="col">Wajib</th><th scope="col" class="text-end">Siswa terisi</th><th scope="col"><span class="visually-hidden">Tindakan</span></th></tr>
    </thead>
    <tbody>
<?php foreach ($rows as $row): ?>
        <tr<?= (int) $row['aktif'] === 1 ? '' : ' class="text-body-secondary"' ?>>
            <td><?= esc((string) $row['urutan']) ?></td>
            <td>
                <?= esc($row['label']) ?>
<?php if ((int) $row['aktif'] !== 1): ?>
                <br><span class="small"><?= $ikon('eye-off') ?> Disembunyikan</span>
<?php endif ?>
            </td>
            <td><code><?= esc($row['kode']) ?></code></td>
            <td><?= esc($tipe[$row['tipe']] ?? $row['tipe']) ?><?= $row['pilihan'] === [] ? '' : ': ' . esc(implode(', ', $row['pilihan'])) ?></td>
            <td><?= (int) $row['wajib'] === 1 ? 'Ya' : 'Tidak' ?></td>
            <td class="text-end"><?= format_number((int) $row['jumlah_nilai']) ?></td>
            <td class="text-end">
                <div class="d-flex flex-wrap justify-content-end gap-1">
                    <a class="btn btn-sm btn-outline-primary" href="<?= esc(url_to('panel.atribut_siswa.ubah', $row['id']), 'attr') ?>"><?= $ikon('pencil') ?> Ubah<span class="visually-hidden"> <?= esc($row['label']) ?></span></a>
                    <form method="post" action="<?= esc(url_to((int) $row['aktif'] === 1 ? 'panel.atribut_siswa.sembunyikan' : 'panel.atribut_siswa.tampilkan', $row['id']), 'attr') ?>">
                        <?= csrf_field() ?>
<?php if ((int) $row['aktif'] === 1): ?>
                        <button class="btn btn-sm btn-outline-primary" type="submit"><?= $ikon('eye-off') ?> Sembunyikan<span class="visually-hidden"> <?= esc($row['label']) ?></span></button>
<?php else: ?>
                        <button class="btn btn-sm btn-outline-primary" type="submit"><?= $ikon('eye') ?> Tampilkan<span class="visually-hidden"> <?= esc($row['label']) ?></span></button>
<?php endif ?>
                    </form>
<?php if ((int) $row['jumlah_nilai'] === 0): ?>
                    <a class="btn btn-sm btn-outline-danger" href="<?= esc(url_to('panel.atribut_siswa.form_hapus', $row['id']), 'attr') ?>"><?= $ikon('x') ?> Hapus<span class="visually-hidden"> <?= esc($row['label']) ?></span></a>
<?php endif ?>
                </div>
            </td>
        </tr>
<?php endforeach ?>
    </tbody>
</table>
</div>
<?php endif ?>
<?= $this->endSection() ?>
