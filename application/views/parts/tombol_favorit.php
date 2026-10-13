<?php
/* Tombol ♥ Favorit. Parameter: $produk_id, $aktif (bool), [$kecil].
   Tamu diarahkan masuk dulu - lalu kembali ke halaman ini. */
$CI =& get_instance();
$balik = uri_string();
$kecil = ! empty($kecil);
?>
<?php if ($CI->auth_lib->user()): ?>
  <?= form_open('favorit/tukar/' . (int) $produk_id, array('class' => 'fav-form', 'data-fav' => '')) ?>
    <input type="hidden" name="balik" value="<?= html_escape($balik) ?>">
    <button type="submit" class="fav-tombol<?= $aktif ? ' is-aktif' : '' ?><?= $kecil ? ' is-kecil' : '' ?>"
            aria-pressed="<?= $aktif ? 'true' : 'false' ?>"
            aria-label="<?= $aktif ? 'Hapus dari Favorit' : 'Simpan ke Favorit' ?>">
      <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M12 21s-7.5-4.6-9.6-9.2C.9 8.4 3 4.5 6.8 4.5c2.1 0 3.6 1.2 5.2 3.1 1.6-1.9 3.1-3.1 5.2-3.1 3.8 0 5.9 3.9 4.4 7.3C19.5 16.4 12 21 12 21z"/></svg>
      <?php if ( ! $kecil): ?><span data-fav-teks><?= $aktif ? t('Tersimpan') : t('Favorit') ?></span><?php endif; ?>
    </button>
  <?= form_close() ?>
<?php else: ?>
  <a href="<?= site_url('auth/login?next=' . rawurlencode($balik)) ?>"
     class="fav-tombol<?= $kecil ? ' is-kecil' : '' ?>" aria-label="Masuk untuk menyimpan ke Favorit">
    <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M12 21s-7.5-4.6-9.6-9.2C.9 8.4 3 4.5 6.8 4.5c2.1 0 3.6 1.2 5.2 3.1 1.6-1.9 3.1-3.1 5.2-3.1 3.8 0 5.9 3.9 4.4 7.3C19.5 16.4 12 21 12 21z"/></svg>
    <?php if ( ! $kecil): ?><span><?= t('Favorit') ?></span><?php endif; ?>
  </a>
<?php endif; ?>
