<!-- application/views/admin/v_promo_form.php -->
<div class="panel-judul"><h1><?= $p ? 'Ubah halaman promo' : 'Halaman promo baru' ?></h1>
  <a href="<?= site_url('admin/banner') ?>" class="btn btn-black-hover-outline">Kembali</a></div>
<?= form_open('admin/banner/promo_simpan') ?>
<input type="hidden" name="id" value="<?= $p ? (int) $p['id'] : '' ?>">
<div class="form-card">
  <div class="row">
    <div class="col-md-6 mb-3"><label class="form-label">Judul *</label>
      <input type="text" name="judul" class="form-control" maxlength="150" required value="<?= html_escape($p['judul'] ?? '') ?>"></div>
    <div class="col-md-6 mb-3"><label class="form-label">Alamat</label>
      <div class="input-group"><span class="input-group-text">/promo/</span>
        <input type="text" name="slug" class="form-control" value="<?= html_escape($p['slug'] ?? '') ?>" placeholder="otomatis dari judul"></div></div>
  </div>
  <div class="mb-3"><label class="form-label">Isi kampanye</label>
    <textarea name="isi" class="form-control" rows="5"><?= html_escape($p['isi'] ?? '') ?></textarea></div>
  <div class="row">
    <div class="col-md-4 mb-3"><label class="form-label">Produk dari kategori</label>
      <select name="kategori_id" class="form-select"><option value="">Semua kategori</option>
        <?php foreach ($kategori as $k): ?><option value="<?= (int) $k['id'] ?>" <?= (int) ($p['kategori_id'] ?? 0) === (int) $k['id'] ? 'selected' : '' ?>><?= html_escape($k['name']) ?></option><?php endforeach; ?>
      </select></div>
    <div class="col-md-4 mb-3"><label class="form-label">Kata kunci produk</label>
      <input type="text" name="kata_kunci" class="form-control" maxlength="100" value="<?= html_escape($p['kata_kunci'] ?? '') ?>" placeholder="contoh: batik"></div>
    <div class="col-md-4 mb-3"><label class="form-label">Kode voucher yang ditonjolkan</label>
      <input type="text" name="voucher" class="form-control" maxlength="30" value="<?= html_escape($p['voucher'] ?? '') ?>"></div>
  </div>
  <div class="form-check mb-3"><input type="checkbox" class="form-check-input" name="is_active" value="1" id="aktif" <?= ($p['is_active'] ?? 1) ? 'checked' : '' ?>><label class="form-check-label" for="aktif">Aktif</label></div>
  <button class="btn btn-primary">Simpan</button>
</div>
<?= form_close() ?>
