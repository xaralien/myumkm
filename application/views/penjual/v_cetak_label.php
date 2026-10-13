<?php $judul = 'Label pengiriman'; $this->load->view('penjual/parts_cetak_kepala', array('judul' => $judul)); ?>
<?php foreach ($orders as $o): ?>
  <div class="lembar">
    <div class="kecil"><?= html_escape($brand) ?> &middot; <?= html_escape($o['order_number']) ?></div>
    <div class="garis"></div>
    <div class="kecil">KEPADA:</div>
    <div class="besar"><?= html_escape($o['recipient_name']) ?></div>
    <div class="besar"><?= html_escape($o['recipient_phone']) ?></div>
    <div><?= nl2br(html_escape($o['recipient_address'])) ?></div>
    <div><strong><?= html_escape(trim(($o['district_name'] ? 'Kec. ' . $o['district_name'] . ', ' : '') . $o['regency_name'] . ', ' . $o['province_name'], ', ')) ?></strong></div>
    <?php if ($o['recipient_notes']): ?><div class="kecil">Catatan: <?= html_escape($o['recipient_notes']) ?></div><?php endif; ?>
    <div class="garis"></div>
    <div class="kecil">DARI:</div>
    <div><strong><?= html_escape($toko['name']) ?></strong> &middot; <?= html_escape($toko['phone']) ?></div>
    <div class="kecil"><?= html_escape($toko['address']) ?>, <?= html_escape($toko['district_name']) ?>, <?= html_escape($toko['regency_name']) ?></div>
    <div class="garis"></div>
    <table class="kecil">
      <?php foreach ($o['items'] as $it): ?>
        <tr><td><?= (int) $it['qty'] ?>x <?= html_escape($it['product_name']) ?><?= $it['variant_name'] ? ' (' . html_escape($it['variant_name']) . ')' : '' ?></td></tr>
      <?php endforeach; ?>
    </table>
    <?php if ($o['courier'] || $o['tracking_number']): ?>
      <div class="garis"></div>
      <div><strong><?= html_escape($o['courier']) ?></strong> <?= html_escape($o['tracking_number']) ?></div>
    <?php endif; ?>
    <?php if ( ! empty($o['tanggal_kirim'])): ?><div class="kecil">Pre-order, kirim <?= tgl_id($o['tanggal_kirim']) ?></div><?php endif; ?>
  </div>
<?php endforeach; ?>
<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });</script>
</body></html>
