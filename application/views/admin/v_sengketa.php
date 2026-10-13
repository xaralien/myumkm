<!-- application/views/admin/v_sengketa.php -->
<?php if ( ! $g): ?>
  <div class="panel-judul"><div><h1>Pusat sengketa</h1>
    <p class="hint">Baca utas sengketa, chat pesanan, dan riwayat status sebelum memutuskan. Yang paling lama menunggu ada di atas.</p></div></div>
  <nav class="fitur-saring mb-3">
    <a href="<?= site_url('admin/sengketa') ?>" class="<?= $aktif ? 'aktif' : '' ?>">Perlu diputuskan</a>
    <a href="<?= site_url('admin/sengketa?status=selesai') ?>" class="<?= ! $aktif ? 'aktif' : '' ?>">Selesai</a>
  </nav>
  <?php if ( ! $daftar): ?><div class="fitur-kosong">Tidak ada sengketa di sini.</div><?php endif; ?>
  <div class="adm-tabel">
    <?php foreach ($daftar as $d): ?>
      <a href="<?= site_url('admin/sengketa/lihat/' . (int) $d['id']) ?>" class="adm-baris pdm-baris">
        <div><strong>#<?= (int) $d['id'] ?> &middot; <?= html_escape($d['order_number']) ?> &middot; <?= html_escape($d['store_name']) ?></strong>
          <em><?= html_escape($d['alasan']) ?> &middot; tuntutan <?= $d['jumlah'] ? rupiah($d['jumlah']) : html_escape($d['tuntutan']) ?> &middot; dibuka <?= tgl_id(substr($d['created_at'], 0, 10)) ?></em></div>
        <div class="adm-aksi"><span class="badge-status <?= $aktif ? 'is-wait' : 'is-ok' ?>"><?= html_escape(Sengketa_model::$status[$d['status']]) ?></span></div>
      </a>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="panel-judul"><div><h1>Sengketa #<?= (int) $g['id'] ?></h1>
    <p class="hint"><?= html_escape($g['order_number']) ?> &middot; total <?= rupiah($g['total']) ?> &middot; pembeli <?= html_escape($g['customer_name']) ?> (<?= html_escape($g['customer_phone']) ?>)
      &middot; toko <?= html_escape($g['store_name']) ?> (<?= html_escape($g['store_phone']) ?>)</p></div>
    <a href="<?= site_url('admin/sengketa') ?>" class="btn btn-black-hover-outline">Semua</a></div>
  <div class="row">
    <div class="col-lg-7 mb-4">
      <h2 class="panel-sub">Utas sengketa &middot; <?= html_escape(Sengketa_model::$status[$g['status']]) ?></h2>
      <p class="hint"><?= html_escape($g['alasan']) ?> &middot; tuntutan: <?= html_escape($g['tuntutan']) ?><?= $g['jumlah'] ? ' ' . rupiah($g['jumlah']) : '' ?></p>
      <?php $this->load->view('parts/sengketa_utas', array('pesan' => $pesan, 'saya' => 'admin')); ?>
      <?php if (in_array($g['status'], array('terbuka', 'ditinjau'), TRUE)): ?>
        <?= form_open('admin/sengketa/balas/' . (int) $g['id'], array('class' => 'fitur-baris-form mb-3')) ?>
          <input type="text" name="isi" class="form-control" required placeholder="Tanya kedua pihak / minta bukti">
          <button class="btn btn-black-hover-outline">Kirim</button>
        <?= form_close() ?>
        <?= form_open('admin/sengketa/putus/' . (int) $g['id'], array('class' => 'form-card')) ?>
          <h3 class="form-card-title">Putusan</h3>
          <select name="status" class="form-select mb-2" required>
            <option value="menang_pembeli">Pembeli benar &mdash; kembalikan dana</option>
            <option value="menang_penjual">Penjual benar &mdash; pesanan sah</option>
            <option value="damai">Sudah damai / sepakat sendiri</option>
            <option value="ditutup">Tutup tanpa putusan</option>
          </select>
          <input type="text" name="jumlah" class="form-control mb-2" inputmode="numeric" placeholder="Jumlah refund (kosong = sesuai tuntutan)">
          <textarea name="keputusan" class="form-control mb-2" rows="3" required minlength="10" placeholder="Alasan putusan (dikirim ke kedua pihak)"></textarea>
          <button class="btn btn-primary">Simpan putusan</button>
        <?= form_close() ?>
      <?php elseif ($g['keputusan']): ?>
        <div class="fitur-kartu"><strong>Putusan:</strong> <?= nl2br(html_escape($g['keputusan'])) ?></div>
      <?php endif; ?>
    </div>
    <div class="col-lg-5">
      <h2 class="panel-sub">Isi pesanan</h2>
      <?php foreach ($items as $it): ?><p class="mb-1"><?= (int) $it['qty'] ?>x <?= html_escape($it['product_name']) ?> &middot; <?= rupiah($it['line_total']) ?></p><?php endforeach; ?>
      <h2 class="panel-sub mt-3">Riwayat status</h2>
      <?php foreach ($logs as $l): ?><p class="mb-1 hint"><?= html_escape($l['created_at']) ?> &middot; <?= html_escape(label_status($l['status'])) ?> &middot; <?= html_escape($l['note']) ?></p><?php endforeach; ?>
      <?php if ($refund): ?><h2 class="panel-sub mt-3">Refund</h2>
        <?php foreach ($refund as $r): ?><p class="mb-1"><?= rupiah($r['jumlah']) ?> &middot; <?= html_escape($r['status']) ?> &middot; <?= html_escape($r['alasan']) ?></p><?php endforeach; ?><?php endif; ?>
      <h2 class="panel-sub mt-3">Chat pesanan</h2>
      <div class="sk-utas">
        <?php foreach ($chat as $c): ?>
          <div class="sk-pesan"><span class="sk-dari"><?= html_escape($c['pengirim']) ?> &middot; <?= date('d/m H:i', strtotime($c['created_at'])) ?></span>
            <?php if ($c['isi']): ?><p><?= nl2br(html_escape($c['isi'])) ?></p><?php endif; ?>
            <?php if ($c['image']): ?><img src="<?= base_url('upload/produk/' . $c['image']) ?>" alt="Foto chat" class="img-fluid rounded mt-1"><?php endif; ?></div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
<?php endif; ?>
