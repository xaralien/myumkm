<?php
/* Pesan galat untuk halaman publik dengan formulir (masuk, lupa sandi, OTP,
   halaman pesanan). Pesan SUKSES tidak di sini - navbar (desktop & HP)
   sudah menampilkannya di semua halaman publik. */
?>
<?php if ($this->session->flashdata('error')): ?>
  <div class="alert-box alert-error mb-4" role="alert"><?= html_escape($this->session->flashdata('error')) ?></div>
<?php endif; ?>
