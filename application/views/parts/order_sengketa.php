<?php
/* Bagian sengketa di halaman pesanan pembeli. */
if (empty($sengketa) && empty($boleh_sengketa)) { return; }
$url = $order['order_number'] . '/' . $order['access_token'];
?>
<div class="form-card mt-4 text-start" id="sengketa">
  <?php if ( ! empty($sengketa)): ?>
    <h3 class="form-card-title">Sengketa #<?= (int) $sengketa['id'] ?> &middot; <?= html_escape(Sengketa_model::$status[$sengketa['status']]) ?></h3>
    <p class="hint">Alasan: <?= html_escape($sengketa['alasan']) ?><?= $sengketa['jumlah'] ? ' &middot; tuntutan ' . rupiah($sengketa['jumlah']) : '' ?></p>
    <?php $this->load->view('parts/sengketa_utas', array('pesan' => $sengketa_pesan, 'saya' => 'pembeli')); ?>
    <?php if (in_array($sengketa['status'], array('terbuka', 'ditinjau'), TRUE)): ?>
      <?= form_open('checkout/sengketa_balas/' . $url, array('class' => 'fitur-baris-form mt-2')) ?>
        <input type="text" name="isi" class="form-control" maxlength="2000" placeholder="Tambahkan keterangan atau jawaban" required>
        <button class="btn btn-primary">Kirim</button>
      <?= form_close() ?>
    <?php endif; ?>
  <?php endif; ?>

  <?php if ( ! empty($boleh_sengketa)): ?>
    <details <?= empty($sengketa) ? '' : 'class="mt-3"' ?>>
      <summary><strong>Ada masalah yang tidak selesai dengan penjual?</strong> Minta admin menengahi</summary>
      <?= form_open('checkout/sengketa/' . $url, array('class' => 'mt-3')) ?>
        <div class="row">
          <div class="col-md-6 mb-2"><label class="form-label">Masalahnya</label>
            <select name="alasan" class="form-select"><?php foreach (Sengketa_model::$alasan as $a): ?><option><?= $a ?></option><?php endforeach; ?></select></div>
          <div class="col-md-6 mb-2"><label class="form-label">Yang kamu minta</label>
            <select name="tuntutan" class="form-select">
              <option value="refund_penuh">Uang kembali penuh</option><option value="refund_sebagian">Uang kembali sebagian</option>
              <option value="tukar_barang">Barang ditukar</option><option value="lainnya">Lainnya</option></select></div>
        </div>
        <div class="mb-2"><label class="form-label">Jumlah (kalau sebagian)</label>
          <input type="text" name="jumlah" class="form-control" inputmode="numeric" placeholder="Rp"></div>
        <div class="mb-2"><label class="form-label">Ceritakan kronologinya</label>
          <textarea name="cerita" class="form-control" rows="4" minlength="15" required placeholder="Apa yang terjadi, kapan, dan apa yang sudah dibicarakan dengan penjual."></textarea></div>
        <p class="hint">Foto bukti bisa dikirim lewat chat pesanan &mdash; admin ikut membacanya.</p>
        <button class="btn btn-primary">Buka sengketa</button>
      <?= form_close() ?>
    </details>
  <?php endif; ?>
</div>
