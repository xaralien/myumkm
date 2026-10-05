<!-- application/views/seller/v_refund.php -->
<div class="panel-judul">
  <div>
    <h1>Pengembalian dana</h1>
    <p class="hint">
      Menyetujui berarti pesanan dibatalkan, stok dikembalikan, dan dana
      ditransfer admin ke pembeli. Pendapatanmu dari pesanan itu ikut batal.
    </p>
  </div>
  <?php if ($baru): ?>
    <span class="badge-status is-wait"><?= (int) $baru ?> menunggu keputusan</span>
  <?php endif; ?>
</div>

<?php if (! $daftar): ?>
  <div class="empty-state">
    <p class="empty-title">Belum ada permintaan pengembalian dana</p>
    <p class="hint">Permintaan dari pembeli akan muncul di sini.</p>
  </div>
<?php else: ?>

  <div class="adm-tabel">
    <?php foreach ($daftar as $r): ?>
      <?php
        $label = array(
          'diminta'   => array('Menunggu keputusanmu', 'is-wait'),
          'disetujui' => array('Disetujui, menunggu transfer admin', 'is-ok'),
          'ditolak'   => array('Ditolak', ''),
          'selesai'   => array('Dana sudah dikembalikan', 'is-ok'),
        );
      ?>
      <div class="adm-baris <?= $r['status'] === 'diminta' ? '' : 'is-mati' ?>" style="flex-direction: column; align-items: stretch;">
        <div class="adm-aksi" style="justify-content: space-between; width: 100%;">
          <div>
            <strong><?= html_escape($r['order_number']) ?></strong>
            <em>
              <?= html_escape($r['customer_name']) ?> &middot;
              <?= rupiah($r['jumlah']) ?> &middot;
              <?= waktu_lalu($r['created_at']) ?>
            </em>
          </div>
          <span class="badge-status <?= $label[$r['status']][1] ?>"><?= $label[$r['status']][0] ?></span>
        </div>

        <p class="ul-isi" style="margin-top: 10px;">
          <strong>Alasan pembeli:</strong> <?= html_escape($r['alasan']) ?>
        </p>

        <?php if ($r['catatan_penjual']): ?>
          <div class="ul-balasan"><strong>Catatanmu</strong><p><?= html_escape($r['catatan_penjual']) ?></p></div>
        <?php endif; ?>

        <?php if ($r['status'] === 'diminta'): ?>
          <!-- Dua tombol dalam satu formulir, dibedakan nilai 'setuju'.
               Menolak wajib disertai catatan: pembeli berhak tahu alasannya,
               dan penolakan tanpa penjelasan hampir pasti berlanjut jadi
               ulasan buruk. -->
          <?= form_open('seller/refund_putus/' . (int) $r['id'], array('class' => 'refund-putus')) ?>
            <label class="visually-hidden" for="c<?= (int) $r['id'] ?>">Catatan untuk pembeli</label>
            <textarea id="c<?= (int) $r['id'] ?>" name="catatan" rows="2" maxlength="500"
                      placeholder="Catatan untuk pembeli (wajib kalau menolak)"></textarea>

            <div class="refund-putus-aksi">
              <button type="submit" name="setuju" value="1" class="btn btn-primary btn-sm"
                      onclick="return confirm('Setujui pengembalian dana? Pesanan akan dibatalkan.');">
                Setujui
              </button>
              <button type="submit" name="setuju" value="0" class="btn btn-black-hover-outline btn-sm"
                      onclick="return this.form.catatan.value.trim().length >= 5 || (alert('Tuliskan alasan penolakan dulu.'), false);">
                Tolak
              </button>
            </div>
          <?= form_close() ?>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
