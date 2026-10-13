<?php
  /* Panduan langkah awal untuk toko baru - hilang sendiri begitu lengkap. */
  $CI =& get_instance();
  if ($CI->db->table_exists('akademi_modul')) {
    $CI->load->library('naik_kelas_lib');
    $awal = $CI->naik_kelas_lib->langkah_awal($store['id']);
  }
?>
<?php if ( ! empty($awal) && $awal['selesai'] < $awal['total']): ?>
  <a href="<?= site_url('seller/mulai') ?>" class="mulai-spanduk mb-3">
    <span>🚀 <strong>Toko siap jualan: <?= (int) $awal['selesai'] ?>/<?= (int) $awal['total'] ?> langkah</strong>
      &mdash; selesaikan supaya pembeli percaya pada tokomu.</span>
    <span class="nk-bar"><i style="width: <?= round($awal['selesai'] / $awal['total'] * 100) ?>%"></i></span>
    <span class="btn btn-primary btn-sm">Lanjutkan</span>
  </a>
<?php endif; ?>
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
