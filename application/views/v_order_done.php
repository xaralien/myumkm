<!-- application/views/v_order_done.php -->
<?php
$lunas   = ($order['payment_status'] === 'paid');
$cod     = ($order['payment_method'] === 'cod');
$link    = site_url('checkout/done/' . $order['order_number'] . '/' . $order['access_token']);
$pesanWa = rawurlencode('Halo, saya mau tanya pesanan ' . $order['order_number']);
?>

<div class="hero hero-page">
  <div class="container">
    <div class="row">
      <div class="col-lg-12">
        <div class="intro-excerpt text-center">
          <h1>Pesanan diterima</h1>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="untree_co-section before-footer-section">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8">

        <?php if ($this->session->flashdata('error')): ?>
          <div class="alert-box alert-error mb-4"><?= html_escape($this->session->flashdata('error')) ?></div>
        <?php endif; ?>

        <div class="form-card text-center">
          <p class="hint mb-1">Nomor pesanan</p>
          <p class="order-number"><?= html_escape($order['order_number']) ?></p>

          <p class="mb-4">
            <span class="badge-status <?= $lunas ? 'is-ok' : 'is-wait' ?>">
              <?= label_bayar($order['payment_status']) ?>
            </span>
            <span class="badge-status"><?= label_status($order['order_status']) ?></span>
          </p>

          <?php if ($cod): ?>
            <p>Pesanan kamu sudah masuk. Kami akan menghubungi lewat WhatsApp
              untuk konfirmasi, lalu bunga dikirim sesuai jadwal.
              Siapkan <strong><?= rupiah($order['total']) ?></strong> saat kurir tiba.</p>
          <?php elseif ($lunas): ?>
            <p>Pembayaran sudah kami terima. Bunga akan dirangkai dan dikirim sesuai jadwal.</p>
          <?php else: ?>
            <p>Pembayaran belum kami terima. Kalau kamu sudah membayar, status
              akan berubah otomatis dalam beberapa menit.</p>
            <p class="pantau" id="statusPantau"></p>
            <?php if ($order['duitku_reference'] && $order['payment_method'] === 'duitku'): ?>
              <a href="<?= site_url('payment/pay/' . $order['order_number'] . '/' . $order['access_token']) ?>"
                class="btn btn-primary mt-2">Lanjutkan pembayaran</a>
            <?php endif; ?>
          <?php endif; ?>
        </div>

        <div class="form-card">
          <h3 class="form-card-title">Pengiriman</h3>
          <table class="detail-table">
            <tr>
              <td>Penerima</td>
              <td><?= html_escape($order['recipient_name']) ?></td>
            </tr>
            <tr>
              <td>Alamat</td>
              <td><?= nl2br(html_escape($order['recipient_address'])) ?>,
                <?= html_escape($order['recipient_city']) ?></td>
            </tr>
            <tr>
              <td>Jadwal</td>
              <td><?= tgl_id($order['delivery_date']) ?> &middot;
                <?= html_escape($order['delivery_slot']) ?></td>
            </tr>
            <?php if ($order['recipient_notes']): ?>
              <tr>
                <td>Catatan</td>
                <td><?= html_escape($order['recipient_notes']) ?></td>
              </tr>
            <?php endif; ?>
            <?php if ($order['surprise_mode']): ?>
              <tr>
                <td>Mode</td>
                <td>Kejutan &mdash; penerima tidak dihubungi lebih dulu</td>
              </tr>
            <?php endif; ?>
          </table>
        </div>

        <div class="form-card">
          <h3 class="form-card-title">Rincian</h3>
          <?php foreach ($items as $it): ?>
            <div class="summary-item">
              <span>
                <?= html_escape($it['product_name']) ?>
                <?php if ($it['variant_name']): ?><em><?= html_escape($it['variant_name']) ?></em><?php endif; ?>
                <?php if ($it['addons']): ?>
                  <em><?php
                      $n = array();
                      foreach ($it['addons'] as $a) {
                        $n[] = $a['name'];
                      }
                      echo html_escape(implode(', ', $n));
                      ?></em>
                <?php endif; ?>
                <em><?= (int) $it['qty'] ?> &times; <?= rupiah($it['unit_price']) ?></em>
              </span>
              <strong><?= rupiah($it['line_total']) ?></strong>
            </div>
          <?php endforeach; ?>

          <div class="summary-row mt-3"><span>Subtotal</span><strong><?= rupiah($order['subtotal']) ?></strong></div>
          <div class="summary-row"><span>Ongkos kirim</span><strong><?= rupiah($order['shipping_fee']) ?></strong></div>
          <div class="summary-row summary-total"><span>Total</span><strong><?= rupiah($order['total']) ?></strong></div>
        </div>

        <!-- Guest tidak punya akun, jadi link ini SATU-SATUNYA cara mereka
             kembali ke halaman ini. Ditampilkan besar dan bisa disalin. -->
        <div class="form-card">
          <h3 class="form-card-title">Simpan link ini</h3>
          <p class="hint mb-2">Untuk memeriksa status pesanan kapan saja, tanpa perlu login.</p>
          <div class="copy-row">
            <input type="text" class="form-control" id="trackLink" value="<?= html_escape($link) ?>" readonly>
            <button type="button" class="btn btn-black-hover-outline" id="btnCopy">Salin</button>
          </div>
          <p class="hint mt-3">
            Atau buka <a href="<?= site_url('track') ?>">halaman lacak</a> dan masukkan
            nomor pesanan bersama nomor WhatsApp kamu.
          </p>
        </div>

        <div class="text-center">
          <a href="https://wa.me/<?= $whatsapp ?>?text=<?= $pesanWa ?>" class="btn btn-primary">
            Tanya lewat WhatsApp
          </a>
          <a href="<?= site_url('shop') ?>" class="btn btn-black-hover-outline">Belanja lagi</a>
        </div>

      </div>
    </div>
  </div>
</div>

<?php if (! $lunas && $order['payment_method'] === 'duitku'): ?>
  <!-- Pembeli yang kembali ke halaman ini setelah membayar akan melihat
     statusnya berubah sendiri, tanpa perlu menekan muat ulang. -->
  <script>
    window.PAY_STATUS = {
      url: '<?= site_url('payment/status/' . $order['order_number'] . '/' . $order['access_token']) ?>',
      awal: 3,
      akhir: 12,
      maks: 300
    };
  </script>
  <script src="<?= base_url('assets/js/payment-status.js') ?>"></script>
<?php endif; ?>


<?php $this->load->view('v_chat_widget', array('order' => $order)); ?>

<script>
  document.getElementById('btnCopy').addEventListener('click', function() {
    var f = document.getElementById('trackLink');
    f.select();
    f.setSelectionRange(0, 99999);
    try {
      if (navigator.clipboard) {
        navigator.clipboard.writeText(f.value);
      } else {
        document.execCommand('copy');
      }
      this.textContent = 'Tersalin';
      var b = this;
      setTimeout(function() {
        b.textContent = 'Salin';
      }, 2000);
    } catch (e) {
      alert('Salin manual: ' + f.value);
    }
  });
</script>