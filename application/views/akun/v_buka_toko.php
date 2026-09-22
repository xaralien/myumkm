<!-- application/views/akun/v_buka_toko.php -->
<div class="hero hero-page">
  <div class="container"><div class="row"><div class="col-lg-12">
    <div class="intro-excerpt text-center"><h1>Buka toko</h1></div>
  </div></div></div>
</div>

<div class="untree_co-section before-footer-section">
  <div class="container"><div class="row justify-content-center"><div class="col-lg-7">

    <?php if ($this->session->flashdata('error')): ?>
      <div class="alert-box alert-error mb-4" role="alert"><?= html_escape($this->session->flashdata('error')) ?></div>
    <?php endif; ?>

    <?= form_open('akun/buka_toko') ?>

    <div class="form-card">
      <h3 class="form-card-title">Tentang tokomu</h3>
      <p class="hint mb-4">
        Toko baru ditinjau admin dulu sebelum tampil di katalog. Sambil menunggu,
        kamu sudah bisa masuk panel toko dan menambahkan produk.
      </p>

      <div class="mb-3">
        <label class="form-label" for="store_name">Nama toko</label>
        <input type="text" id="store_name" name="store_name" class="form-control"
               value="<?= set_value('store_name') ?>" maxlength="120" required autofocus>
        <p class="hint">Harus berbeda dari toko lain. Nama ini tampil di setiap produkmu.</p>
        <?= form_error('store_name') ?>
      </div>

      <div class="mb-3">
        <label class="form-label" for="phone">WhatsApp toko</label>
        <input type="tel" id="phone" name="phone" class="form-control" placeholder="081234567890"
               value="<?= set_value('phone', $akun['phone']) ?>" required>
        <p class="hint">Pembeli menghubungimu lewat nomor ini.</p>
        <?= form_error('phone') ?>
      </div>

      <div class="mb-1">
        <label class="form-label" for="description">Deskripsi singkat <span class="hint">(opsional)</span></label>
        <textarea id="description" name="description" class="form-control" rows="3"
                  maxlength="1000"><?= set_value('description') ?></textarea>
      </div>
    </div>

    <div class="form-card">
      <h3 class="form-card-title">Lokasi toko</h3>
      <p class="hint mb-3">Menentukan pembeli mana yang melihat tokomu lebih dulu, dan tarif ongkirnya.</p>

      <div class="mb-3">
        <label class="form-label" for="address">Alamat toko</label>
        <textarea id="address" name="address" class="form-control" rows="2" required><?= set_value('address', $akun['address']) ?></textarea>
        <?= form_error('address') ?>
      </div>

      <span class="form-label">Wilayah</span>
      <div class="row">
        <div class="col-sm-4 mb-2">
          <select id="prov" class="form-control" aria-label="Provinsi" required>
            <option value="">Provinsi</option>
            <?php foreach ($provinces as $pr): ?>
              <option value="<?= (int) $pr['id'] ?>" <?= (int) $akun['province_id'] === (int) $pr['id'] ? 'selected' : '' ?>>
                <?= html_escape($pr['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-sm-4 mb-2">
          <select id="reg" class="form-control" aria-label="Kabupaten atau kota" required disabled>
            <option value="">Kabupaten / Kota</option>
          </select>
        </div>
        <div class="col-sm-4 mb-2">
          <select id="dis" name="district_id" class="form-control" aria-label="Kecamatan" required disabled>
            <option value="">Kecamatan</option>
          </select>
        </div>
      </div>
      <?= form_error('district_id') ?>
    </div>

    <label class="akun-setuju">
      <input type="checkbox" name="setuju" value="1" required <?= set_checkbox('setuju', '1') ?>>
      <span>Produk yang saya jual adalah buatan atau usaha saya sendiri, dan saya
        bertanggung jawab atas pesanan yang masuk.</span>
    </label>
    <?= form_error('setuju') ?>

    <button type="submit" class="btn btn-primary mt-3">Buka toko</button>
    <?= form_close() ?>

  </div></div></div>
</div>

<script>
  window.REGION_URLS = {
    regencies: '<?= site_url('region/regencies') ?>',
    districts: '<?= site_url('region/districts') ?>'
  };
  // Wilayah akun dipakai sebagai titik awal - kebanyakan UMKM berjualan
  // dari rumah pemiliknya sendiri.
  window.PILIHAN_AWAL = {
    province: <?= (int) $akun['province_id'] ?>,
    regency:  <?= (int) $akun['regency_id'] ?>,
    district: <?= (int) $akun['district_id'] ?>
  };
</script>
<script src="<?= base_url('assets/js/region-select.js') ?>"></script>
