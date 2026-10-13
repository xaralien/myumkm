<?php $judul = 'Nota pesanan'; $this->load->view('penjual/parts_cetak_kepala', array('judul' => $judul)); ?>
<?php foreach ($orders as $o): ?>
  <div class="lembar">
    <div class="besar"><?= html_escape($toko['name']) ?></div>
    <div class="kecil"><?= html_escape($toko['address']) ?>, <?= html_escape($toko['regency_name']) ?> &middot; WA <?= html_escape($toko['phone']) ?></div>
    <div class="garis"></div>
    <table class="kecil">
      <tr><td>No. pesanan</td><td class="k"><?= html_escape($o['order_number']) ?></td></tr>
      <tr><td>Tanggal</td><td class="k"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td></tr>
      <tr><td>Pembeli</td><td class="k"><?= html_escape($o['customer_name']) ?></td></tr>
      <tr><td>Status bayar</td><td class="k"><?= label_bayar($o['payment_status']) ?></td></tr>
    </table>
    <div class="garis"></div>
    <table>
      <?php foreach ($o['items'] as $it): ?>
        <tr>
          <td><?= html_escape($it['product_name']) ?><?= $it['variant_name'] ? ' - ' . html_escape($it['variant_name']) : '' ?>
            <div class="kecil"><?= (int) $it['qty'] ?> x <?= rupiah($it['unit_price']) ?></div></td>
          <td class="k"><?= rupiah($it['line_total']) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
    <div class="garis"></div>
    <table>
      <tr><td>Subtotal</td><td class="k"><?= rupiah($o['subtotal']) ?></td></tr>
      <tr><td>Ongkos kirim</td><td class="k"><?= rupiah($o['shipping_fee']) ?></td></tr>
      <?php foreach (array('diskon_toko' => 'Voucher toko', 'diskon_platform' => 'Voucher platform', 'subsidi_ongkir' => 'Subsidi ongkir') as $k => $l): ?>
        <?php if ( ! empty($o[$k])): ?><tr><td><?= $l ?></td><td class="k">-<?= rupiah($o[$k]) ?></td></tr><?php endif; ?>
      <?php endforeach; ?>
      <tr><td class="besar">TOTAL</td><td class="k besar"><?= rupiah($o['total']) ?></td></tr>
      <?php if ( ! empty($o['dp_jumlah'])): ?>
        <tr><td>Uang muka</td><td class="k"><?= rupiah($o['dp_jumlah']) ?></td></tr>
        <tr><td>Sisa</td><td class="k"><?= empty($o['lunas_at']) ? rupiah($o['total'] - $o['dp_jumlah']) : 'LUNAS' ?></td></tr>
      <?php endif; ?>
    </table>
    <div class="garis"></div>
    <div class="kecil" style="text-align:center">Terima kasih sudah mendukung UMKM lokal!<br><?= html_escape($brand) ?></div>
  </div>
<?php endforeach; ?>
<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });</script>
</body></html>
