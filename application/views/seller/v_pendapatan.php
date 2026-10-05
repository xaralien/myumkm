<!-- application/views/seller/v_pendapatan.php -->
<div class="panel-judul">
  <div>
    <h1>Pendapatan</h1>
    <p class="hint">
      Pembayaran masuk ke rekening marketplace dulu, lalu dicairkan ke
      rekeningmu. Komisi <?= rtrim(rtrim(number_format($komisi, 1, ',', '.'), '0'), ',') ?>%
      dihitung dari harga barang saja &mdash; ongkir diteruskan utuh karena
      kamu yang membayar kurirnya.
    </p>
  </div>
</div>

<div class="adm-angka">
  <div class="adm-kartu">
    <span class="adm-nilai"><?= rupiah($siap['jumlah']) ?></span>
    <span class="adm-label">Siap dicairkan</span>
    <span class="adm-catatan"><?= (int) $siap['pesanan'] ?> pesanan selesai</span>
  </div>
  <div class="adm-kartu">
    <span class="adm-nilai"><?= rupiah($tertahan['jumlah']) ?></span>
    <span class="adm-label">Masih berjalan</span>
    <!-- Ditahan sampai pesanan selesai: pesanan yang masih dikirim bisa
         berakhir dengan refund, dan uang yang terlanjur cair jauh lebih
         sulit ditarik kembali daripada ditahan beberapa hari lagi. -->
    <span class="adm-catatan">Cair setelah pesanan selesai</span>
  </div>
  <div class="adm-kartu">
    <span class="adm-nilai"><?= rupiah($sudah) ?></span>
    <span class="adm-label">Sudah dicairkan</span>
  </div>
</div>

<div class="panel-bagian">
  <h2 class="panel-sub">Rekening pencairan</h2>

  <?php if (! $toko['bank_nomor']): ?>
    <p class="hint mb-3">
      Belum diisi. Pencairan tidak bisa diproses admin tanpa rekening.
    </p>
  <?php endif; ?>

  <?= form_open('seller/rekening') ?>
  <div class="form-card">
    <div class="row">
      <div class="col-md-4 mb-3">
        <label class="form-label" for="bank_nama">Nama bank</label>
        <input type="text" id="bank_nama" name="bank_nama" class="form-control" maxlength="60"
               placeholder="BCA, BRI, Mandiri..."
               value="<?= html_escape($toko['bank_nama']) ?>">
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label" for="bank_nomor">Nomor rekening</label>
        <input type="text" id="bank_nomor" name="bank_nomor" class="form-control" maxlength="40"
               inputmode="numeric" value="<?= html_escape($toko['bank_nomor']) ?>">
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label" for="bank_atas_nama">Atas nama</label>
        <input type="text" id="bank_atas_nama" name="bank_atas_nama" class="form-control" maxlength="100"
               value="<?= html_escape($toko['bank_atas_nama']) ?>">
      </div>
    </div>
    <button type="submit" class="btn btn-primary">Simpan rekening</button>
  </div>
  <?= form_close() ?>
</div>

<?php if ($payout): ?>
  <div class="panel-bagian">
    <h2 class="panel-sub">Riwayat pencairan</h2>
    <div class="adm-tabel">
      <?php foreach ($payout as $p): ?>
        <div class="adm-baris">
          <div>
            <strong><?= rupiah($p['jumlah']) ?></strong>
            <em>
              <?= (int) $p['jumlah_order'] ?> pesanan &middot;
              <?= tgl_id(substr($p['created_at'], 0, 10)) ?>
              <?php if ($p['bank_nomor']): ?>
                &middot; <?= html_escape($p['bank_nama']) ?> <?= html_escape($p['bank_nomor']) ?>
              <?php endif; ?>
            </em>
          </div>
          <div class="adm-aksi">
            <span class="badge-status <?= $p['status'] === 'selesai' ? 'is-ok' : ($p['status'] === 'gagal' ? '' : 'is-wait') ?>">
              <?= $p['status'] === 'selesai' ? 'Sudah ditransfer' : ($p['status'] === 'gagal' ? 'Gagal' : 'Diproses') ?>
            </span>
            <?php if ($p['bukti']): ?><span class="hint"><?= html_escape($p['bukti']) ?></span><?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<div class="panel-bagian">
  <h2 class="panel-sub">Rincian per pesanan</h2>
  <div class="panel-tabel-bungkus">
    <table class="panel-tabel">
      <thead>
        <tr>
          <th>Pesanan</th><th>Status</th><th>Barang</th><th>Ongkir</th>
          <th>Komisi</th><th>Bagianmu</th><th>Pencairan</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pesanan as $o): ?>
          <tr>
            <td><?= html_escape($o['order_number']) ?></td>
            <td><?= label_status($o['order_status']) ?></td>
            <td><?= rupiah($o['subtotal']) ?></td>
            <td><?= rupiah($o['shipping_fee']) ?></td>
            <td>&minus;<?= rupiah($o['fee_platform']) ?></td>
            <td><strong><?= rupiah($o['net_store']) ?></strong></td>
            <td>
              <?php if ($o['payout_id']): ?>
                <span class="badge-status is-ok">Dicairkan</span>
              <?php elseif ($o['order_status'] === 'delivered'): ?>
                <span class="badge-status is-wait">Siap</span>
              <?php else: ?>
                <span class="hint">Menunggu selesai</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
