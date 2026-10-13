<!-- application/views/v_promo.php -->
<div class="hero hero-page">
  <div class="container"><div class="row"><div class="col-lg-12">
    <div class="intro-excerpt text-center"><h1><?= html_escape($promo['judul']) ?></h1></div>
  </div></div></div>
</div>
<div class="untree_co-section before-footer-section">
  <div class="container">
    <?php if ($promo['isi']): ?><div class="promo-isi mb-4"><?= html_aman($promo['isi']) ?></div><?php endif; ?>
    <?php if ($voucher): ?>
      <div class="toko-voucher-item promo-voucher mb-4">
        <strong><?= html_escape($this->voucher_lib->keterangan($voucher)) ?></strong>
        <span>Masukkan kode ini saat checkout<?= (int) $voucher['min_belanja'] ? ' (min. belanja ' . rupiah($voucher['min_belanja']) . ')' : '' ?></span>
        <button type="button" class="voucher-kode" data-salin="<?= html_escape($voucher['kode']) ?>"><?= html_escape($voucher['kode']) ?> &middot; salin</button>
      </div>
    <?php endif; ?>
    <?php if ( ! $produk): ?>
      <div class="fitur-kosong">Produk untuk promo ini belum tersedia di wilayahmu.</div>
    <?php else: ?>
      <div class="fitur-grid">
        <?php foreach ($produk as $p): ?><?php $this->load->view('parts/kartu_produk', array('p' => $p, 'redup' => FALSE)); ?><?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
