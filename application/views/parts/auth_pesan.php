<?php /* Pesan sukses/galat untuk halaman akun (masuk, lupa sandi, OTP). */ ?>
<?php if ($this->session->flashdata('sukses')): ?>
  <div class="alert-box alert-ok mb-4" role="status"><?= html_escape($this->session->flashdata('sukses')) ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
  <div class="alert-box alert-error mb-4" role="alert"><?= html_escape($this->session->flashdata('error')) ?></div>
<?php endif; ?>
