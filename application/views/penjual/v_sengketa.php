<!-- application/views/penjual/v_sengketa.php -->
<?php if ( ! $g): ?>
  <div class="panel-judul"><div><h1>Sengketa</h1>
    <p class="hint">Keluhan pembeli yang diteruskan ke admin. Tanggapi dengan jujur dan sertakan bukti (foto paket, resi) lewat chat pesanan.</p></div></div>
  <?php if ( ! $daftar): ?><div class="fitur-kosong">Tidak ada sengketa. Pertahankan! 👍</div><?php endif; ?>
  <div class="adm-tabel">
    <?php foreach ($daftar as $d): ?>
      <a href="<?= site_url('seller/sengketa/lihat/' . (int) $d['id']) ?>" class="adm-baris pdm-baris">
        <div><strong>#<?= (int) $d['id'] ?> &middot; <?= html_escape($d['order_number']) ?></strong><em><?= html_escape($d['alasan']) ?> &middot; <?= tgl_id(substr($d['created_at'], 0, 10)) ?></em></div>
        <div class="adm-aksi"><span class="badge-status <?= in_array($d['status'], array('terbuka', 'ditinjau'), TRUE) ? 'is-wait' : 'is-ok' ?>"><?= html_escape(Sengketa_model::$status[$d['status']]) ?></span></div>
      </a>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="panel-judul"><div><h1>Sengketa #<?= (int) $g['id'] ?></h1>
    <p class="hint">Pesanan <?= html_escape($g['order_number']) ?> &middot; <?= html_escape($g['customer_name']) ?> &middot; <?= html_escape($g['alasan']) ?>
      <?= $g['jumlah'] ? '&middot; tuntutan ' . rupiah($g['jumlah']) : '' ?></p></div>
    <a href="<?= site_url('seller/sengketa') ?>" class="btn btn-black-hover-outline">Semua sengketa</a></div>
  <p><span class="badge-status"><?= html_escape(Sengketa_model::$status[$g['status']]) ?></span></p>
  <?php $this->load->view('parts/sengketa_utas', array('pesan' => $pesan, 'saya' => 'penjual')); ?>
  <?php if (in_array($g['status'], array('terbuka', 'ditinjau'), TRUE)): ?>
    <?= form_open('seller/sengketa/balas/' . (int) $g['id'], array('class' => 'form-card')) ?>
      <label class="form-label">Tanggapanmu</label>
      <textarea name="isi" class="form-control mb-2" rows="3" required placeholder="Jelaskan dari sisimu: kapan dikirim, kondisi barang, resi, dll."></textarea>
      <button class="btn btn-primary">Kirim tanggapan</button>
    <?= form_close() ?>
  <?php endif; ?>
<?php endif; ?>
