<?php
/* parts/catatan_pembeli.php - catatan & gambar dari pembeli saat memesan.
   Butuh $order (baris orders). Diam kalau tidak ada isinya. */
if (empty($order['catatan_pembeli']) && empty($order['catatan_gambar'])) {
    return;
}
$gbr = ! empty($order['catatan_gambar']) ? base_url('upload/pesanan/' . rawurlencode(basename($order['catatan_gambar']))) : NULL;
?>
<div class="catatan-pembeli">
  <p class="catatan-pembeli-judul"><?= isset($judul_catatan) ? html_escape($judul_catatan) : 'Catatan dari pembeli' ?></p>
  <?php if ( ! empty($order['catatan_pembeli'])): ?>
    <p class="catatan-pembeli-isi"><?= nl2br(html_escape($order['catatan_pembeli'])) ?></p>
  <?php endif; ?>
  <?php if ($gbr): ?>
    <a href="<?= $gbr ?>" target="_blank" rel="noopener" class="catatan-pembeli-gambar">
      <img src="<?= $gbr ?>" alt="Gambar contoh dari pembeli" loading="lazy">
      <span>Lihat gambar penuh</span>
    </a>
  <?php endif; ?>
</div>
