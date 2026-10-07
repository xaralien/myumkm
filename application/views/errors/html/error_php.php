<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/* =============================================================================
   Peringatan PHP. Simpan di: application/views/errors/html/error_php.php

   BUKAN halaman penuh: berkas ini disisipkan DI TENGAH halaman yang sedang
   digambar, kadang beberapa sekaligus. Jadi bentuknya kartu ringkas yang
   bisa menumpuk tanpa merusak tata letak.

   Di produksi tidak tampil sama sekali - CodeIgniter sudah menyembunyikannya
   lewat display_errors, dan pesan seperti ini membocorkan jalur folder
   server kepada siapa pun yang melihat.
   ========================================================================== */

if (ENVIRONMENT === 'production') {
    return;
}

$b = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
?>
<div style="
  margin: 10px 0;
  padding: 12px 14px;
  border-radius: 10px;
  border: 1px solid #E8C37A;
  border-left: 4px solid #CBA02D;
  background: #FFF8E8;
  color: #4A3A12;
  font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
  font-size: 12.5px;
  line-height: 1.6;
">
  <strong style="font-family: 'Inter', sans-serif; color: #8A6A10;">
    Peringatan PHP &middot; <?= $b($severity) ?>
  </strong>
  <div><?= $b($message) ?></div>
  <div style="opacity: .8;"><?= $b($filepath) ?> baris <?= (int) $line ?></div>

  <?php if (defined('SHOW_DEBUG_BACKTRACE') && SHOW_DEBUG_BACKTRACE === TRUE): ?>
    <div style="margin-top: 6px; opacity: .75;">
      <?php foreach (debug_backtrace() as $t): ?>
        <?php if (isset($t['file']) && strpos($t['file'], realpath(BASEPATH)) !== 0): ?>
          <div><?= $b($t['file']) ?>:<?= (int) $t['line'] ?> &rarr; <?= $b($t['function']) ?>()</div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
