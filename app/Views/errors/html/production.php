<?php $this->extend('layout/galat') ?>
<?php $this->section('judul') ?>Terjadi gangguan<?php $this->endSection() ?>
<?php $this->section('isi') ?><?= esc(lang('Galat.gangguan', [$kodeLaporan])) ?><?php $this->endSection() ?>
