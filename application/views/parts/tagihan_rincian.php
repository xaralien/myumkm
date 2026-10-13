<?php /* Daftar pesanan dalam satu tagihan. Parameter: $tagihan, $orders. */ ?>
<div class="tg-rincian">
  <?php foreach ($orders as $o): ?>
    <div class="tg-baris">
      <span>
        <strong><?= html_escape($o['store_name']) ?></strong>
        <em>
          <a href="<?= site_url('checkout/done/' . $o['order_number'] . '/' . $o['access_token']) ?>"><?= html_escape($o['order_number']) ?></a>
          <?php if ($tagihan['jenis'] === 'dp' || ( ! empty($o['dp_jumlah']) && $tagihan['jenis'] === 'grup')): ?>
            &middot; uang muka dari <?= rupiah($o['total']) ?>
          <?php elseif ($tagihan['jenis'] === 'pelunasan'): ?>
            &middot; pelunasan
          <?php endif; ?>
        </em>
      </span>
      <strong><?= rupiah($o['tagihan_jumlah']) ?></strong>
    </div>
  <?php endforeach; ?>
</div>
