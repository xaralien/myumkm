<!-- application/views/penjual/v_cetak_pilih.php -->
<div class="panel-judul">
  <div>
    <h1>Cetak label &amp; nota</h1>
    <p class="hint">Centang pesanan yang mau dikirim hari ini, lalu cetak sekaligus. Bisa ke printer biasa (A6) atau printer thermal kasir.</p>
  </div>
</div>

<?php if ( ! $orders): ?>
  <div class="fitur-kosong">Tidak ada pesanan lunas yang menunggu dikirim.</div>
<?php else: ?>
  <form method="get" target="_blank" id="formCetak">
    <div class="adm-tabel mb-3">
      <label class="adm-baris cetak-semua">
        <div><input type="checkbox" id="cekSemua" checked> <strong>Pilih semua (<?= count($orders) ?>)</strong></div>
      </label>
      <?php foreach ($orders as $o): ?>
        <label class="adm-baris">
          <div>
            <input type="checkbox" name="id[]" value="<?= (int) $o['id'] ?>" checked>
            <strong><?= html_escape($o['order_number']) ?></strong>
            <em><?= html_escape($o['recipient_name']) ?> &middot; <?= html_escape($o['recipient_city']) ?> &middot; <?= label_status($o['order_status']) ?></em>
          </div>
        </label>
      <?php endforeach; ?>
    </div>
    <div class="pintas-baris">
      <button type="submit" class="btn btn-primary btn-besar" formaction="<?= site_url('seller/cetak/label') ?>">🏷 Cetak label alamat</button>
      <button type="submit" class="btn btn-black-hover-outline btn-besar" formaction="<?= site_url('seller/cetak/nota') ?>">🧾 Cetak nota</button>
    </div>
  </form>
  <script>
    document.getElementById('cekSemua').addEventListener('change', function () {
      var on = this.checked;
      document.querySelectorAll('#formCetak input[name="id[]"]').forEach(function (c) { c.checked = on; });
    });
  </script>
<?php endif; ?>
