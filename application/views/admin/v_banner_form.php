<!-- application/views/admin/v_banner_form.php -->
<div class="panel-judul"><h1><?= $b ? 'Ubah banner' : 'Banner baru' ?></h1>
  <a href="<?= site_url('admin/banner') ?>" class="btn btn-black-hover-outline">Kembali</a></div>
<?= form_open_multipart('admin/banner/simpan') ?>
<input type="hidden" name="id" value="<?= $b ? (int) $b['id'] : '' ?>">
<div class="form-card">
  <div class="row">
    <div class="col-md-6 mb-3"><label class="form-label">Judul *</label>
      <input type="text" name="judul" class="form-control" maxlength="120" required value="<?= html_escape($b['judul'] ?? '') ?>" placeholder="Pekan Produk Lokal"></div>
    <div class="col-md-6 mb-3"><label class="form-label">Kalimat pendek</label>
      <input type="text" name="sub" class="form-control" maxlength="200" value="<?= html_escape($b['sub'] ?? '') ?>" placeholder="Diskon s.d. 20% dari UMKM sekitarmu"></div>
  </div>
  <div class="row">
    <div class="col-md-6 mb-3"><label class="form-label">Tautan tujuan</label>
      <input type="text" name="tautan" class="form-control" value="<?= html_escape($b['tautan'] ?? '') ?>" placeholder="/promo/pekan-lokal atau /shop?category=..."></div>
    <div class="col-md-3 mb-3"><label class="form-label">Warna latar</label>
      <input type="color" name="warna" class="form-control form-control-color" value="<?= html_escape($b['warna'] ?? '#22466C') ?>"></div>
    <div class="col-md-3 mb-3"><label class="form-label">Urutan</label>
      <input type="number" name="urutan" class="form-control" value="<?= (int) ($b['urutan'] ?? 0) ?>"></div>
  </div>
  <div class="row">
    <div class="col-md-4 mb-3"><label class="form-label">Gambar <span class="hint">(opsional, 1600x500)</span></label>
      <input type="file" name="gambar" class="form-control" accept="image/jpeg,image/png,image/webp">
      <?php if ( ! empty($b['gambar'])): ?><img src="<?= base_url('upload/banner/' . $b['gambar']) ?>" alt="" class="img-fluid mt-2 rounded"><?php endif; ?></div>
    <div class="col-md-3 mb-3"><label class="form-label">Mulai</label><input type="date" name="mulai" class="form-control" value="<?= html_escape($b['mulai'] ?? '') ?>"></div>
    <div class="col-md-3 mb-3"><label class="form-label">Selesai</label><input type="date" name="selesai" class="form-control" value="<?= html_escape($b['selesai'] ?? '') ?>"></div>
    <div class="col-md-2 mb-3 d-flex align-items-end"><div class="form-check"><input type="checkbox" class="form-check-input" name="is_active" value="1" id="aktif" <?= ($b['is_active'] ?? 1) ? 'checked' : '' ?>><label class="form-check-label" for="aktif">Aktif</label></div></div>
  </div>
  <button class="btn btn-primary">Simpan banner</button>
</div>
<?= form_close() ?>
