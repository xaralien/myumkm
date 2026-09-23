<!-- application/views/seller/v_chat.php - percakapan & pengelolaan satu pesanan -->
<?php
  $label = array(
    'pending'    => 'Pesanan baru',
    'confirmed'  => 'Diproses',
    'preparing'  => 'Diproses',
    'delivering' => 'Dikirim',
    'delivered'  => 'Selesai',
    'cancelled'  => 'Dibatalkan',
  );
  $st    = $order['order_status'];
  $lunas = ($order['payment_status'] === 'paid');
?>

<div class="panel-judul">
  <div>
    <h1><?= html_escape($order['order_number']) ?></h1>
    <p class="hint">
      <?= html_escape($order['recipient_name']) ?> &middot;
      dipesan <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
    </p>
  </div>
  <a href="<?= site_url('seller/orders') ?>" class="btn btn-black-hover-outline">Kembali</a>
</div>

<div class="row">

  <!-- ==================== PERCAKAPAN ==================== -->
  <div class="col-lg-7 mb-4">
    <div class="chat-kotak" id="chatKotak">
      <?php foreach ($pesan as $m): ?>
        <?php $this->load->view('v_chat_bubble', array('m' => $m, 'sisi' => 'seller')); ?>
      <?php endforeach; ?>
    </div>

    <form class="chat-form" id="chatForm">
      <input type="text" id="chatIsi" class="form-control" maxlength="1000"
             placeholder="Tulis pesan untuk pembeli..." autocomplete="off" aria-label="Pesan">
      <button type="submit" class="btn btn-primary">Kirim</button>
    </form>
    <p class="chat-info" id="chatInfo" aria-live="polite"></p>
  </div>

  <!-- ==================== PANEL PESANAN ==================== -->
  <div class="col-lg-5">

    <div class="form-card">
      <h3 class="form-card-title">Status</h3>
      <p class="mb-2">
        <span class="badge-status <?= $st === 'delivered' ? 'is-ok' : 'is-wait' ?>"><?= html_escape($label[$st] ?? $st) ?></span>
        <span class="badge-status <?= $lunas ? 'is-ok' : 'is-wait' ?>"><?= $lunas ? 'Lunas' : 'Belum dibayar' ?></span>
      </p>

      <?php if ($order['tracking_number'] || $order['courier']): ?>
        <p class="hint mb-0">
          <?= html_escape($order['courier'] ?: 'Kurir') ?>
          <?php if ($order['tracking_number']): ?>&middot; resi <strong><?= html_escape($order['tracking_number']) ?></strong><?php endif; ?>
        </p>
      <?php endif; ?>

      <?php if (! $lunas && $st !== 'cancelled'): ?>
        <p class="hint mt-2 mb-0">Tunggu pembayaran masuk sebelum memproses pesanan.</p>

      <?php elseif ($st === 'pending'): ?>
        <?= form_open('seller/ubah_status/' . $order['id'], array('class' => 'mt-3')) ?>
          <button type="submit" class="btn btn-primary w-100">Terima &amp; proses pesanan</button>
        <?= form_close() ?>

      <?php elseif ($st === 'confirmed' || $st === 'preparing'): ?>
        <!-- Kurir & resi opsional - banyak UMKM mengantar sendiri. -->
        <?= form_open('seller/ubah_status/' . $order['id'], array('class' => 'mt-3')) ?>
          <div class="mb-2">
            <label class="form-label" for="courier">Kurir <span class="hint">(opsional)</span></label>
            <input type="text" id="courier" name="courier" class="form-control" maxlength="50"
                   placeholder="JNE, J&amp;T, GoSend, diantar sendiri...">
          </div>
          <div class="mb-3">
            <label class="form-label" for="tracking_number">Nomor resi <span class="hint">(opsional)</span></label>
            <input type="text" id="tracking_number" name="tracking_number" class="form-control" maxlength="60">
          </div>
          <button type="submit" class="btn btn-primary w-100">Tandai sudah dikirim</button>
        <?= form_close() ?>

      <?php elseif ($st === 'delivering'): ?>
        <p class="hint mt-2">Menunggu pembeli mengonfirmasi pesanan diterima.</p>
        <?= form_open('seller/ubah_status/' . $order['id']) ?>
          <button type="submit" class="btn btn-black-hover-outline w-100">Tandai selesai</button>
        <?= form_close() ?>
      <?php endif; ?>
    </div>

    <div class="form-card">
      <h3 class="form-card-title">Kirim ke</h3>
      <p class="mb-1"><strong><?= html_escape($order['recipient_name']) ?></strong> &middot; <?= html_escape($order['recipient_phone']) ?></p>
      <p class="mb-2"><?= nl2br(html_escape($order['recipient_address'])) ?>, <?= html_escape($order['recipient_city']) ?></p>
      <?php if ($order['recipient_notes']): ?>
        <p class="hint mb-0">Catatan: <?= html_escape($order['recipient_notes']) ?></p>
      <?php endif; ?>
    </div>

    <div class="form-card">
      <h3 class="form-card-title">Barang</h3>
      <?php foreach ($items as $it): ?>
        <div class="summary-item">
          <span>
            <?= html_escape($it['product_name']) ?>
            <?php if ($it['variant_name']): ?><em><?= html_escape($it['variant_name']) ?></em><?php endif; ?>
            <?php if (! empty($it['addons'])): ?><em><?= html_escape(implode(', ', array_column($it['addons'], 'name'))) ?></em><?php endif; ?>
            <em><?= (int) $it['qty'] ?> &times; <?= rupiah($it['unit_price']) ?></em>
          </span>
          <strong><?= rupiah($it['line_total']) ?></strong>
        </div>
      <?php endforeach; ?>
      <div class="summary-row mt-2"><span>Ongkir</span><strong><?= rupiah($order['shipping_fee']) ?></strong></div>
      <div class="summary-row summary-total"><span>Total</span><strong><?= rupiah($order['total']) ?></strong></div>
    </div>

    <?php if ($lunas && $st !== 'cancelled'): ?>
      <div class="form-card">
        <h3 class="form-card-title">Kirim foto</h3>
        <p class="hint mb-3">Misalnya foto paket sebelum dikirim. Tersimpan di percakapan sebagai bukti kalau ada keluhan.</p>
        <!-- multipart: tanpa enctype, berkasnya tidak ikut terkirim. -->
        <?= form_open_multipart('seller/kirim_foto/' . $order['id']) ?>
          <input type="file" name="foto" class="form-control mb-2" required
                 accept="image/jpeg,image/png,image/webp" aria-label="Pilih foto">
          <input type="text" name="catatan" class="form-control mb-2" maxlength="500"
                 placeholder="Keterangan (opsional)" aria-label="Keterangan foto">
          <button type="submit" class="btn btn-black-hover-outline w-100">Kirim foto</button>
        <?= form_close() ?>
      </div>
    <?php endif; ?>

  </div>
</div>

<script>
  window.CHAT_SELLER = {
    base:    <?= json_encode(rtrim(site_url(), '/')) ?>,
    orderId: <?= (int) $order['id'] ?>,
    gambar:  <?= json_encode(base_url('upload/produk/')) ?>,
    sejak:   <?= $pesan ? (int) end($pesan)['id'] : 0 ?>,
    status:  <?= json_encode($st) ?>
  };
  window.CSRF = {
    name: '<?= $this->security->get_csrf_token_name() ?>',
    hash: '<?= $this->security->get_csrf_hash() ?>'
  };
</script>
<script src="<?= base_url('assets/js/chat-seller.js') ?>"></script>
