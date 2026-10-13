<?php
/* Kartu produk ringkas untuk deret rekomendasi, terlaris, favorit, dan
   produk baru toko langganan. Parameter: $p, [$redup] produk tidak tersedia. */
$url = site_url('produk/' . $p['store_slug'] . '/' . $p['slug']);
?>
<a href="<?= $url ?>" class="kp<?= ! empty($redup) ? ' is-redup' : '' ?>">
  <span class="kp-foto">
    <img src="<?= base_url('upload/produk/' . $p['image']) ?>" alt="<?= html_escape($p['name']) ?>" loading="lazy">
    <?php if ( ! empty($p['terjual_count']) && (int) $p['terjual_count'] >= 10): ?>
      <span class="kp-pita">Terlaris</span>
    <?php endif; ?>
  </span>
  <span class="kp-isi">
    <span class="kp-nama"><?= html_escape($p['name']) ?></span>
    <span class="kp-harga">
      <?php if ((int) ($p['variant_count'] ?? 0) > 1): ?><small>mulai </small><?php endif; ?><?= rupiah($p['price']) ?>
    </span>
    <span class="kp-toko"><?= html_escape($p['store_name']) ?><?= ! empty($p['store_district']) ? ' · ' . html_escape($p['store_district']) : '' ?></span>
    <?php if ( ! empty($p['terjual_count'])): ?>
      <span class="kp-terjual"><?= terjual_teks($p['terjual_count']) ?></span>
    <?php endif; ?>
  </span>
</a>
