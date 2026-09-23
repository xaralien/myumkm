<!-- application/views/v_track_result.php -->
<?php
/* Empat tahap marketplace. 'preparing' (dulu "dirangkai") bukan tahap
   sendiri lagi; pesanan lama yang berstatus itu ditampilkan sebagai
   "Diproses" supaya garis kemajuannya tidak melompat. */
$tahap = array('pending', 'confirmed', 'delivering', 'delivered');
$status_tampil = ($order['order_status'] === 'preparing') ? 'confirmed' : $order['order_status'];
$kini  = array_search($status_tampil, $tahap, TRUE);
if ($kini === FALSE) {
  $kini = -1;
}

$batal = ($order['order_status'] === 'cancelled');
$lunas = ($order['payment_status'] === 'paid');

$wa = $toko ? $toko['phone'] : '';
$pesan_wa = rawurlencode('Halo, saya mau tanya pesanan ' . $order['order_number']);
?>

<div class="hero hero-page">
  <div class="container">
    <div class="row">
      <div class="col-lg-12">
        <div class="intro-excerpt text-center">
          <h1>Status pesanan</h1>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="untree_co-section before-footer-section">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8">

        <div class="form-card text-center">
          <p class="hint mb-1">Nomor pesanan</p>
          <p class="order-number"><?= html_escape($order['order_number']) ?></p>
          <p>
            <span class="badge-status <?= $lunas ? 'is-ok' : 'is-wait' ?>">
              <?= label_bayar($order['payment_status']) ?>
            </span>
            <span class="badge-status"><?= label_status($order['order_status']) ?></span>
          </p>
        </div>

        <!-- ============ LANJUTKAN PEMBAYARAN ============
             Diletakkan PALING ATAS, sebelum rincian. Pembeli yang mencari
             pesanannya di sini hampir selalu datang untuk satu hal:
             menyelesaikan pembayaran yang tertunda. -->
        <?php if ($bisa_bayar): ?>
          <div class="form-card bayar-panel">
            <h3 class="form-card-title">Pembayaran belum selesai</h3>

            <div class="summary-row summary-total mb-3">
              <span>Total tagihan</span>
              <strong><?= rupiah($order['total']) ?></strong>
            </div>

            <?php if ($order['payment_status'] === 'failed'): ?>
              <!-- 'failed' berarti transaksinya belum pernah terbentuk di
                   Duitku - bukan pembayaran yang ditolak. Pesanannya masih
                   utuh, jadi tinggal dibuatkan transaksi baru. -->
              <p class="hint mb-3">
                Pembuatan tagihan sebelumnya tidak berhasil. Pesanan kamu tetap
                tersimpan &mdash; klik tombol di bawah untuk membuat tagihan baru.
              </p>
            <?php else: ?>
              <p class="hint mb-3">
                Lanjutkan pembayaran untuk memproses pesanan ini.
                Metode pembayaran dipilih di halaman berikutnya.
              </p>
            <?php endif; ?>

            <a href="<?= html_escape($url_bayar) ?>" class="btn btn-primary w-100">
              Bayar sekarang
            </a>

            <p class="hint text-center mt-2">
              Belum sempat? Simpan
              <a href="<?= html_escape($url_pesanan) ?>">link pesanan ini</a>
              supaya bisa kembali kapan saja.
            </p>
          </div>

        <?php endif; ?>

        <?php if (! $batal): ?>
          <div class="form-card">
            <h3 class="form-card-title">Perjalanan pesanan</h3>
            <ol class="track-steps">
              <?php foreach ($tahap as $i => $t): ?>
                <li class="<?= $i <= $kini ? 'is-done' : '' ?>">
                  <span class="track-dot"></span>
                  <span class="track-label"><?= label_status($t) ?></span>
                </li>
              <?php endforeach; ?>
            </ol>
          </div>
        <?php else: ?>
          <div class="alert-box alert-error mb-4">
            Pesanan ini dibatalkan.
            <?php if ($order['payment_status'] === 'expired'): ?>
              Batas waktu pembayaran sudah lewat.
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <div class="form-card">
          <h3 class="form-card-title">Pengiriman</h3>
          <table class="detail-table">
            <tr>
              <td>Penerima</td>
              <td><?= html_escape($order['recipient_name']) ?></td>
            </tr>
            <tr>
              <td>Kota</td>
              <td><?= html_escape($order['recipient_city']) ?></td>
            </tr>
            <tr>
              <td>Dipesan</td>
              <td><?= tgl_id(substr($order['created_at'], 0, 10)) ?></td>
            </tr>
            <?php if ($order['courier'] || $order['tracking_number']): ?>
              <tr>
                <td>Pengiriman</td>
                <td><?= html_escape($order['courier'] ?: '-') ?><?= $order['tracking_number'] ? ' &middot; resi ' . html_escape($order['tracking_number']) : '' ?></td>
              </tr>
            <?php endif; ?>
            <?php if ($toko): ?>
              <tr>
                <td>Toko</td>
                <td><?= html_escape($toko['name']) ?></td>
              </tr>
            <?php endif; ?>
          </table>
        </div>

        <div class="form-card">
          <h3 class="form-card-title">Rincian</h3>
          <?php foreach ($items as $it): ?>
            <div class="summary-item">
              <span><?= html_escape($it['product_name']) ?>
                <?php if ($it['variant_name']): ?><em><?= html_escape($it['variant_name']) ?></em><?php endif; ?>
                <?php if (! empty($it['addons'])): ?>
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
          <div class="summary-row summary-total">
            <span>Total</span><strong><?= rupiah($order['total']) ?></strong>
          </div>
        </div>

        <?php if ($logs): ?>
          <div class="form-card">
            <h3 class="form-card-title">Riwayat</h3>
            <?php foreach (array_reverse($logs) as $l): ?>
              <div class="summary-item">
                <span><?= label_status($l['status']) ?>
                  <?php if ($l['note']): ?><em><?= html_escape($l['note']) ?></em><?php endif; ?>
                </span>
                <span class="hint"><?= date('d/m/Y H:i', strtotime($l['created_at'])) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div class="text-center">
          <?php if ($wa): ?>
            <a href="https://wa.me/<?= $wa ?>?text=<?= $pesan_wa ?>" class="btn btn-primary"
              target="_blank" rel="noopener">Tanya toko lewat WhatsApp</a>
          <?php endif; ?>
          <a href="<?= site_url('track') ?>" class="btn btn-black-hover-outline">Cari pesanan lain</a>
        </div>

      </div>
    </div>
  </div>
</div>



<?php $this->load->view('v_chat_widget', array('order' => $order)); ?>


<?php if ($bisa_bayar && $order['payment_status'] === 'unpaid'): ?>
  <!-- Pantau status di halaman ini juga. Pembeli yang baru saja membayar lalu
     kembali ke sini akan melihat statusnya berubah sendiri, tanpa menekan
     muat ulang. -->
  <script>
    window.PAY_STATUS = {
      url: '<?= site_url('payment/status/' . $order['order_number'] . '/' . $order['access_token']) ?>',
      awal: 4,
      akhir: 15,
      maks: 300
    };
  </script>
  <script src="<?= base_url('assets/js/payment-status.js') ?>"></script>
<?php endif; ?>