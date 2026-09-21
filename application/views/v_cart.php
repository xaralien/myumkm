<!-- application/views/v_cart.php -->

<div class="hero hero-page">
  <div class="container">
    <div class="row">
      <div class="col-lg-12">
        <div class="intro-excerpt text-center">
          <h1>Keranjang</h1>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="untree_co-section before-footer-section">
  <div class="container">

    <?php if (empty($items)): ?>

      <div class="text-center py-5">
        <p class="mb-4">Keranjang kamu masih kosong.</p>
        <a href="<?= site_url('shop') ?>" class="btn btn-primary">Lihat katalog</a>
      </div>

    <?php else: ?>

      <div class="row">
        <div class="col-lg-8 mb-5 mb-lg-0">

          <?php foreach ($items as $it): ?>
            <div class="cart-line" data-key="<?= $it['key'] ?>">
              <img src="<?= base_url('upload/produk/' . $it['image']) ?>"
                alt="<?= html_escape($it['product_name']) ?>" class="cart-line-img">

              <div class="cart-line-body">
                <h3 class="cart-line-title"><?= html_escape($it['product_name']) ?></h3>

                <?php if ($it['variant_name']): ?>
                  <p class="cart-line-meta"><?= html_escape($it['variant_name']) ?></p>
                <?php endif; ?>

                <?php if ($it['addons']): ?>
                  <p class="cart-line-meta">
                    <?php
                    $nama = array();
                    foreach ($it['addons'] as $a) {
                      $nama[] = $a['name'];
                    }
                    echo html_escape(implode(', ', $nama));
                    ?>
                  </p>
                <?php endif; ?>

                <p class="cart-line-price"><?= rupiah($it['unit_price']) ?></p>

                <div class="cart-line-actions">
                  <div class="qty-box">
                    <button type="button" class="qty-btn" data-act="minus" aria-label="Kurangi">&minus;</button>
                    <input type="number" class="qty-input" value="<?= (int) $it['qty'] ?>"
                      min="1" max="20" aria-label="Jumlah">
                    <button type="button" class="qty-btn" data-act="plus" aria-label="Tambah">+</button>
                  </div>
                  <button type="button" class="link-remove" data-act="remove">Hapus</button>
                </div>
              </div>

              <div class="cart-line-total"><?= rupiah($it['line_total']) ?></div>
            </div>
          <?php endforeach; ?>

        </div>

        <div class="col-lg-4">
          <div class="cart-summary">
            <h3 class="mb-4">Ringkasan</h3>

            <div class="summary-row">
              <span>Subtotal</span>
              <strong id="sumSubtotal"><?= rupiah($subtotal) ?></strong>
            </div>
            <div class="summary-row">
              <span>Ongkos kirim</span>
              <span class="text-muted">Dihitung saat checkout</span>
            </div>

            <a href="<?= site_url('checkout') ?>" class="btn btn-primary w-100 mt-4">
              Lanjut ke pengiriman
            </a>
            <a href="<?= site_url('shop') ?>" class="btn btn-black-hover-outline w-100 mt-2">
              Tambah produk lain
            </a>
          </div>
        </div>
      </div>

    <?php endif; ?>

  </div>
</div>

<script>
  window.CART_URLS = {
    update: '<?= site_url('cart/update') ?>',
    remove: '<?= site_url('cart/remove') ?>'
  };
  window.CSRF = {
    name: '<?= $this->security->get_csrf_token_name() ?>',
    hash: '<?= $this->security->get_csrf_hash() ?>'
  };
</script>
<script src="<?= base_url('assets/js/cart.js') ?>"></script>