<!-- application/views/penjual/v_libur.php -->
<div class="panel-judul">
  <div>
    <h1>Mode libur</h1>
    <p class="hint">Pulang kampung, sakit, atau stok habis? Liburkan toko supaya tidak ada pesanan yang terlantar.
      Produkmu tetap tampil, tapi pembeli tidak bisa membuat pesanan baru sampai toko buka lagi.</p>
  </div>
</div>

<?php if ($libur): ?>
  <div class="fitur-kartu mb-4 libur-aktif">
    <h3>🌙 Toko sedang libur sampai <?= tgl_id($libur) ?></h3>
    <?php if ($toko['libur_pesan']): ?><p class="mb-2">Pesan untuk pembeli: “<?= html_escape($toko['libur_pesan']) ?>”</p><?php endif; ?>
    <?= form_open('seller/libur/buka') ?>
      <button type="submit" class="btn btn-primary btn-besar">Buka toko sekarang</button>
    <?= form_close() ?>
  </div>
<?php elseif ( ! empty($toko['libur_mulai']) && $toko['libur_mulai'] > date('Y-m-d')): ?>
  <div class="alert-box alert-warn mb-4">Libur terjadwal mulai <?= tgl_id($toko['libur_mulai']) ?> sampai <?= tgl_id($toko['libur_sampai']) ?>.</div>
<?php endif; ?>

<?= form_open('seller/libur/simpan') ?>
<div class="form-card">
  <h3 class="form-card-title"><?= $libur ? 'Ubah jadwal libur' : 'Atur libur' ?></h3>
  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label" for="libur_mulai">Mulai libur</label>
      <input type="date" class="form-control form-control-lg" id="libur_mulai" name="libur_mulai" min="<?= date('Y-m-d') ?>"
             value="<?= html_escape($toko['libur_mulai'] ?: date('Y-m-d')) ?>">
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label" for="libur_sampai">Buka lagi setelah tanggal *</label>
      <input type="date" class="form-control form-control-lg" id="libur_sampai" name="libur_sampai" min="<?= date('Y-m-d') ?>" required
             value="<?= html_escape($toko['libur_sampai']) ?>">
    </div>
  </div>
  <div class="mb-3">
    <label class="form-label" for="libur_pesan">Pesan untuk pembeli <span class="hint">(opsional)</span></label>
    <input type="text" class="form-control" id="libur_pesan" name="libur_pesan" maxlength="160"
           placeholder="contoh: Mudik Lebaran, pesanan dilayani lagi tanggal 15" value="<?= html_escape($toko['libur_pesan']) ?>">
  </div>
  <button type="submit" class="btn btn-primary btn-besar">Simpan jadwal libur</button>
  <p class="hint mt-2 mb-0">Pesanan yang sudah masuk tetap harus diproses. Pre-order tetap bisa dipesan dengan tanggal kirim setelah toko buka.</p>
</div>
<?= form_close() ?>
