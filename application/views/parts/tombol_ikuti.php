<?php
/* Tombol Ikuti toko. Parameter: $toko_id, $aktif (bool), $pengikut (int). */
$CI =& get_instance();
$balik = uri_string();
?>
<div class="ikuti-wrap">
  <?php if ($CI->auth_lib->user()): ?>
    <?= form_open('favorit/ikuti/' . (int) $toko_id, array('class' => 'fav-form', 'data-ikuti' => '')) ?>
      <input type="hidden" name="balik" value="<?= html_escape($balik) ?>">
      <button type="submit" class="ikuti-tombol<?= $aktif ? ' is-aktif' : '' ?>" aria-pressed="<?= $aktif ? 'true' : 'false' ?>">
        <span data-fav-teks><?= $aktif ? t('✓ Mengikuti') : t('+ Ikuti toko') ?></span>
      </button>
    <?= form_close() ?>
  <?php else: ?>
    <a href="<?= site_url('auth/login?next=' . rawurlencode($balik)) ?>" class="ikuti-tombol"><?= t('+ Ikuti toko') ?></a>
  <?php endif; ?>
  <span class="ikuti-jumlah"><span data-fav-jumlah><?= (int) $pengikut ?></span> pengikut</span>
</div>
