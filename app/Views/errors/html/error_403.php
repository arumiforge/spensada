<?php $this->extend('layout/galat') ?>
<?php $this->section('judul') ?>Tidak dapat dibuka<?php $this->endSection() ?>
<?php $this->section('isi') ?><?= esc(lang($siswa ? 'Galat.diLuarHakSiswa' : 'Galat.diLuarHak')) ?><?php $this->endSection() ?>
