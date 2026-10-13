<?php /* Bagian atas daftar produk penjual: peringatan stok & jalan pintas. */ ?>
<?php if ( ! empty($stok_tipis)): ?>
  <div class="alert-box alert-warn mb-3">
    <strong><?= count($stok_tipis) ?> produk stoknya menipis atau habis:</strong>
    <?php foreach ($stok_tipis as $i => $t): ?>
      <a href="<?= site_url('seller/form/' . (int) $t['id']) ?>"><?= html_escape($t['name']) ?></a> (<?= (int) $t['stock'] ?>)<?= $i < count($stok_tipis) - 1 ? ',' : '' ?>
    <?php endforeach; ?>
    <br><small>Produk berstok 0 otomatis tidak tampil di katalog. Ubah stoknya untuk menayangkannya lagi.</small>
  </div>
<?php endif; ?>

<div class="pintas-baris mb-3">
  <a href="<?= site_url('seller/massal') ?>" class="btn btn-sm btn-black-hover-outline">⬆ Unggah banyak produk (Excel)</a>
  <a href="<?= site_url('seller/libur') ?>" class="btn btn-sm btn-black-hover-outline">🌙 Mode libur</a>
  <a href="<?= site_url('seller/qr') ?>" class="btn btn-sm btn-black-hover-outline">▦ QR toko</a>
</div>
