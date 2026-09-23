<!-- application/views/akun/v_pesanan.php -->
<?php
  $label_status = array(
    'pending'    => 'Menunggu diproses',
    'confirmed'  => 'Diproses penjual',
    'preparing'  => 'Diproses penjual',
    'delivering' => 'Dikirim',
    'delivered'  => 'Selesai',
    'cancelled'  => 'Dibatalkan',
  );
?>
<div class="hero hero-page">
  <div class="container"><div class="row"><div class="col-lg-12">
    <div class="intro-excerpt text-center"><h1>Pesanan saya</h1></div>
  </div></div></div>
</div>

<div class="untree_co-section before-footer-section">
  <div class="container"><div class="row justify-content-center"><div class="col-lg-9">

    <?php if (! $pesanan): ?>
      <div class="empty-state">
        <p class="empty-title">Belum ada pesanan</p>
        <p class="hint mb-4">Pesanan yang kamu buat sambil masuk akan tersimpan di sini.</p>
        <a href="<?= site_url('shop') ?>" class="btn btn-primary">Mulai belanja</a>
      </div>
    <?php else: ?>

      <ul class="akun-pesanan">
        <?php foreach ($pesanan as $o): ?>
          <li>
            <a href="<?= site_url('checkout/done/' . $o['order_number'] . '/' . $o['access_token']) ?>">
              <span class="akun-pesanan-kiri">
                <strong><?= html_escape($o['order_number']) ?></strong>
                <em>
                  <?= html_escape($o['store_name'] ?: 'Toko') ?> &middot;
                  <?= date('d/m/Y', strtotime($o['created_at'])) ?>
                </em>
              </span>
              <span class="akun-pesanan-kanan">
                <strong><?= rupiah($o['total']) ?></strong>
                <span class="badge-status <?= $o['payment_status'] === 'paid' ? 'is-ok' : 'is-wait' ?>">
                  <?= $o['payment_status'] === 'paid'
                        ? html_escape(isset($label_status[$o['order_status']]) ? $label_status[$o['order_status']] : $o['order_status'])
                        : label_bayar($o['payment_status']) ?>
                </span>
              </span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>

      <p class="hint mt-3">
        Pesanan yang dibuat tanpa masuk akun tidak tampil di sini. Lacak lewat
        <a href="<?= site_url('track') ?>">halaman lacak</a> dengan nomor pesanan dan WhatsApp.
      </p>
    <?php endif; ?>

  </div></div></div>
</div>
