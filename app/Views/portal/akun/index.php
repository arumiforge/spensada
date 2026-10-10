<?php
/**
 * The student's own profile, read only (docs/09 HAL-AKN-08, docs/04 FS-MD-04
 * item 6, AC-MD-04-06), with links to change password and log out.
 *
 * @var array<string, mixed> $siswa   From Services\Akun\AkunSiswa::profil()
 * @var string               $privasi Privacy notice (docs/12 SEC-68), may be empty
 */
$label = config('Label')->codes;
$nilai = static function (array $atribut): string {
    return match ($atribut['tipe']) {
        'tanggal' => format_date($atribut['nilai']),
        // Keep the decimals the value was saved with.
        'angka'   => format_number($atribut['nilai'], str_contains($atribut['nilai'], '.') ? strlen(substr(strrchr($atribut['nilai'], '.'), 1)) : 0),
        default   => $atribut['nilai'],
    };
};
$baris = [
    'NISN'               => $siswa['nisn'],
    'NIS'                => $siswa['nis'],
    'Kelas'              => $siswa['kelas'],
    'Nomor WA orang tua/wali' => $siswa['wa_ortu'] === null ? null : format_wa($siswa['wa_ortu']),
    'Jenis kelamin'      => $label['siswa.jenis_kelamin'][$siswa['jenis_kelamin']] ?? null,
    'Tanggal lahir'      => $siswa['tanggal_lahir'] === null ? null : format_date($siswa['tanggal_lahir']),
    'Alamat rumah'       => $siswa['alamat'],
    'Nama orang tua/wali' => $siswa['nama_ortu'],
];
foreach ($siswa['atribut'] as $atribut) {
    $baris[$atribut['label']] = $nilai($atribut);
}
$foto = view('komponen/foto_siswa', [
    'name'       => $siswa['nama'],
    'src'        => empty($siswa['foto_file']) ? null : url_to('portal.akun.foto'),
    'size'       => 'profil',
    'decorative' => false,
], ['saveData' => false]);
$ikon = static fn (string $name): string => view('komponen/ikon', ['name' => $name], ['saveData' => false]);
?>
<?= $this->extend('layout/portal') ?>

<?= $this->section('content') ?>
<div class="text-center mb-3">
    <?= $foto ?>
    <h1 class="h4 mt-2 mb-0"><?= esc($siswa['nama']) ?></h1>
</div>
<div class="card card-body mb-3">
    <dl class="row mb-0">
<?php foreach ($baris as $judul => $isi): ?>
        <dt class="col-5"><?= esc($judul) ?></dt><dd class="col-7"><?= esc($isi === null || $isi === '' ? '–' : $isi) ?></dd>
<?php endforeach ?>
    </dl>
</div>
<p class="form-text">Ada data yang salah? Sampaikan ke wali kelas.</p>
<div class="d-grid gap-2">
    <a class="btn btn-outline-primary" href="<?= esc(url_to('akun.password.ubah'), 'attr') ?>"><?= $ikon('key-round') ?> Ganti password</a>
    <form method="post" action="<?= esc(url_to('akun.login.keluar'), 'attr') ?>" class="d-grid">
        <?= csrf_field() ?>
        <button class="btn btn-outline-secondary" type="submit"><?= $ikon('log-out') ?> Logout</button>
    </form>
</div>
<?php if (trim($privasi) !== ''): ?>
<section class="border-top mt-4 pt-3 small text-body-secondary" aria-label="Pemberitahuan privasi">
    <?php foreach (preg_split('/\R+/', trim($privasi)) as $paragraf): ?>
    <p class="mb-2"><?= esc($paragraf) ?></p>
    <?php endforeach ?>
</section>
<?php endif ?>
<?= $this->endSection() ?>
