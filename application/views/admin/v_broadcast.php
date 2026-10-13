<!-- application/views/admin/v_broadcast.php -->
<div class="panel-judul"><div><h1>Broadcast ke penjual</h1>
  <p class="hint">Pengumuman muncul di atas panel penjual sampai dibaca. Bisa juga dikirim lewat WhatsApp (maks. <?= Broadcast::MAKS_WA ?> toko sekali kirim).</p></div></div>
<div class="row">
  <div class="col-lg-5 mb-4">
    <?= form_open('admin/broadcast/kirim', array('class' => 'form-card')) ?>
      <div class="mb-2"><label class="form-label">Judul *</label><input type="text" name="judul" class="form-control" maxlength="150" required placeholder="Kelas foto produk gratis"></div>
      <div class="mb-2"><label class="form-label">Isi *</label><textarea name="isi" class="form-control" rows="5" required></textarea></div>
      <div class="mb-2"><label class="form-label">Wilayah</label>
        <select name="regency_id" class="form-select"><option value="">Semua wilayah</option>
          <?php foreach ($kota as $k): ?><option value="<?= (int) $k['id'] ?>"><?= html_escape($k['name']) ?> (<?= (int) $k['n'] ?> toko)</option><?php endforeach; ?></select></div>
      <div class="mb-2"><label class="form-label">Kategori toko</label>
        <select name="category_id" class="form-select"><option value="">Semua kategori</option>
          <?php foreach ($kategori as $k): ?><option value="<?= (int) $k['id'] ?>"><?= html_escape($k['name']) ?></option><?php endforeach; ?></select></div>
      <div class="form-check mb-3"><input type="checkbox" class="form-check-input" name="kirim_wa" value="1" id="wa"><label class="form-check-label" for="wa">Kirim juga lewat WhatsApp</label></div>
      <button class="btn btn-primary" onclick="return confirm('Kirim pengumuman sekarang?')">Kirim pengumuman</button>
    <?= form_close() ?>
  </div>
  <div class="col-lg-7">
    <h2 class="panel-sub">Riwayat</h2>
    <?php if ( ! $riwayat): ?><p class="hint">Belum ada pengumuman.</p><?php endif; ?>
    <div class="adm-tabel">
      <?php foreach ($riwayat as $r): ?>
        <div class="adm-baris"><div><strong><?= html_escape($r['judul']) ?></strong>
          <em><?= tgl_id(substr($r['created_at'], 0, 10)) ?> &middot; <?= (int) $r['penerima'] ?> toko &middot; <?= (int) $r['dibaca'] ?> sudah membaca<?= $r['kirim_wa'] ? ' &middot; WA ' . (int) $r['terkirim_wa'] : '' ?></em></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
