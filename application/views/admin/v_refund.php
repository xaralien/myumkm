<!-- application/views/admin/v_refund.php -->
<div class="panel-judul">
  <div>
    <h1>Pengembalian dana</h1>
    <p class="hint">
      Duitku tidak menyediakan API pengembalian dana, jadi transfernya
      dilakukan manual. Halaman ini mencatat siapa meminta, siapa menyetujui,
      dan kapan uangnya dikirim.
    </p>
  </div>
  <a href="<?= site_url('admin/keuangan') ?>" class="btn btn-black-hover-outline">Pencairan toko</a>
</div>

<nav class="ul-tab" aria-label="Saringan">
  <a href="<?= site_url('admin/keuangan/refund') ?>" class="<?= ! $status ? 'is-aktif' : '' ?>">Semua</a>
  <a href="<?= site_url('admin/keuangan/refund?status=disetujui') ?>" class="<?= $status === 'disetujui' ? 'is-aktif' : '' ?>">
    Perlu ditransfer <?= $jumlah['disetujui'] ? '<em>' . (int) $jumlah['disetujui'] . '</em>' : '' ?>
  </a>
  <a href="<?= site_url('admin/keuangan/refund?status=diminta') ?>" class="<?= $status === 'diminta' ? 'is-aktif' : '' ?>">
    Menunggu penjual <?= $jumlah['diminta'] ? '<em>' . (int) $jumlah['diminta'] . '</em>' : '' ?>
  </a>
</nav>

<?php if (! $daftar): ?>
  <div class="empty-state"><p class="empty-title">Tidak ada permintaan di bagian ini</p></div>
<?php else: ?>

  <div class="adm-tabel">
    <?php foreach ($daftar as $r): ?>
      <?php
        $label = array(
          'diminta'   => array('Menunggu penjual', 'is-wait'),
          'disetujui' => array('Perlu ditransfer', 'is-wait'),
          'ditolak'   => array('Ditolak penjual', ''),
          'selesai'   => array('Sudah ditransfer', 'is-ok'),
        );
      ?>
      <div class="adm-baris <?= in_array($r['status'], array('diminta', 'disetujui'), TRUE) ? '' : 'is-mati' ?>"
           style="flex-direction: column; align-items: stretch;">

        <div class="adm-aksi" style="justify-content: space-between; width: 100%;">
          <div>
            <strong><?= rupiah($r['jumlah']) ?> &middot; <?= html_escape($r['order_number']) ?></strong>
            <em>
              <?= html_escape($r['toko']) ?> &rarr; <?= html_escape($r['customer_name']) ?>
              <?php if ($r['customer_phone']): ?> &middot; <?= html_escape($r['customer_phone']) ?><?php endif; ?>
              &middot; <?= waktu_lalu($r['created_at']) ?>
            </em>
          </div>
          <span class="badge-status <?= $label[$r['status']][1] ?>"><?= $label[$r['status']][0] ?></span>
        </div>

        <p class="ul-isi" style="margin-top: 8px;"><?= html_escape($r['alasan']) ?></p>

        <?php if ($r['catatan_penjual']): ?>
          <div class="ul-balasan"><strong>Catatan penjual</strong><p><?= html_escape($r['catatan_penjual']) ?></p></div>
        <?php endif; ?>

        <?php if ($r['status'] === 'disetujui'): ?>
          <!-- Nomor referensi transfer dicatat supaya sengketa "sudah
               dikirim atau belum" punya bukti, bukan hanya ingatan. -->
          <?= form_open('admin/keuangan/refund_selesai/' . (int) $r['id'], array('class' => 'inline-form adm-sandi', 'style' => 'margin-top: 10px;')) ?>
            <input type="text" name="bukti" class="form-control form-control-sm"
                   placeholder="No. referensi transfer" maxlength="120" aria-label="Nomor referensi transfer">
            <button type="submit" class="btn btn-primary btn-sm">Tandai sudah ditransfer</button>
          <?= form_close() ?>
        <?php elseif ($r['bukti_transfer']): ?>
          <p class="hint" style="margin-top: 8px;">Bukti: <?= html_escape($r['bukti_transfer']) ?></p>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
