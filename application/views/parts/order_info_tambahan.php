<?php
/* Info tambahan di halaman pesanan pembeli: pre-order & pelunasan.
   Memakai $order dari Checkout::done(). */
$dp      = ! empty($order['dp_jumlah']);
$belum   = $dp && empty($order['lunas_at']);
$aktif   = $order['order_status'] !== 'cancelled';
?>
<?php if ( ! empty($order['tanggal_kirim']) && $aktif): ?>
  <p class="lencana lencana-emas mb-3">Pre-order &middot; dijadwalkan dikirim <?= tgl_id($order['tanggal_kirim']) ?></p>
<?php endif; ?>

<?php if ($dp && $belum && $order['payment_status'] === 'paid' && $aktif): ?>
  <div class="alert-box alert-warn mb-3 text-start">
    Uang muka <?= rupiah($order['dp_jumlah']) ?> sudah diterima. Lunasi sisa
    <strong><?= rupiah($order['total'] - $order['dp_jumlah']) ?></strong> sebelum tanggal kirim supaya pesananmu bisa dikirim.
    <?= form_open('checkout/lunasi/' . $order['order_number'] . '/' . $order['access_token'], array('class' => 'mt-2')) ?>
      <button type="submit" class="btn btn-primary">Lunasi sekarang</button>
    <?= form_close() ?>
  </div>
<?php endif; ?>
