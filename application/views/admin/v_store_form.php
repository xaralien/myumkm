<!-- application/views/admin/v_store_form.php -->
<div class="panel-judul">
  <h1><?= $store ? 'Ubah Toko' : 'Tambah Toko' ?></h1>
  <a href="<?= site_url('admin/stores') ?>" class="btn btn-black-hover-outline">Kembali</a>
</div>

<div class="row"><div class="col-lg-8">
<?= form_open('admin/stores/save', array('id' => 'storeForm')) ?>
  <input type="hidden" name="id" value="<?= $store ? (int) $store['id'] : '' ?>">

  <div class="form-card">
    <h3 class="form-card-title">Akun pemilik</h3>

    <div class="row">
      <div class="col-sm-6 mb-3">
        <label class="form-label" for="owner_name">Nama pemilik *</label>
        <input type="text" id="owner_name" name="owner_name" class="form-control"
               value="<?= set_value('owner_name', $store ? $store['user_name'] : '') ?>" required>
        <?= form_error('owner_name') ?>
      </div>
      <div class="col-sm-6 mb-3">
        <label class="form-label" for="email">Email (untuk login) *</label>
        <input type="email" id="email" name="email" class="form-control"
               value="<?= set_value('email', $store ? $store['email'] : '') ?>" required>
        <?= form_error('email') ?>
      </div>
    </div>

    <div class="mb-1">
      <label class="form-label" for="password">
        Password <?= $store ? '(kosongkan kalau tidak diganti)' : '*' ?>
      </label>
      <input type="password" id="password" name="password" class="form-control"
             minlength="8" <?= $store ? '' : 'required' ?>>
      <?= form_error('password') ?>
      <p class="hint">Minimal 8 karakter. Sampaikan ke pemilik toko lewat jalur pribadi.</p>
    </div>
  </div>

  <div class="form-card">
    <h3 class="form-card-title">Data toko</h3>

    <div class="row">
      <div class="col-sm-7 mb-3">
        <label class="form-label" for="store_name">Nama toko *</label>
        <input type="text" id="store_name" name="store_name" class="form-control"
               value="<?= set_value('store_name', $store ? $store['name'] : '') ?>" required>
        <?= form_error('store_name') ?>
      </div>
      <div class="col-sm-5 mb-3">
        <label class="form-label" for="phone">WhatsApp toko *</label>
        <input type="tel" id="phone" name="phone" class="form-control" placeholder="081234567890"
               value="<?= set_value('phone', $store ? $store['phone'] : '') ?>" required>
        <?= form_error('phone') ?>
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label" for="address">Alamat toko *</label>
      <input type="text" id="address" name="address" class="form-control"
             value="<?= set_value('address', $store ? $store['address'] : '') ?>" required>
      <?= form_error('address') ?>
    </div>

    <div class="mb-1">
      <label class="form-label" for="description">Deskripsi</label>
      <textarea id="description" name="description" class="form-control" rows="3"><?= set_value('description', $store ? $store['description'] : '') ?></textarea>
    </div>
  </div>

  <div class="form-card">
    <h3 class="form-card-title">Wilayah toko</h3>
    <p class="hint mb-3">
      Menentukan urutan tampil di katalog: pembeli melihat toko di kecamatannya
      lebih dulu, lalu kabupaten/kota, lalu provinsi yang sama.
    </p>

    <div class="row">
      <div class="col-sm-4 mb-3">
        <label class="form-label" for="prov">Provinsi *</label>
        <select id="prov" class="form-control" required>
          <option value="">Pilih provinsi</option>
          <?php foreach ($provinces as $p): ?>
            <option value="<?= (int) $p['id'] ?>"
              <?= $store && (int) $store['province_id'] === (int) $p['id'] ? 'selected' : '' ?>>
              <?= html_escape($p['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-sm-4 mb-3">
        <label class="form-label" for="reg">Kabupaten / Kota *</label>
        <select id="reg" class="form-control" required disabled>
          <option value="">Pilih provinsi dulu</option>
        </select>
      </div>
      <div class="col-sm-4 mb-3">
        <label class="form-label" for="dis">Kecamatan *</label>
        <select id="dis" name="district_id" class="form-control" required disabled>
          <option value="">Pilih kabupaten/kota dulu</option>
        </select>
        <?= form_error('district_id') ?>
      </div>
    </div>
  </div>

  <button type="submit" class="btn btn-primary">Simpan</button>
<?= form_close() ?>
</div></div>

<script>
  window.REGION_URLS = {
    regencies: '<?= site_url('region/regencies') ?>',
    districts: '<?= site_url('region/districts') ?>'
  };
  window.PILIHAN_AWAL = {
    province: <?= $store ? (int) $store['province_id'] : 'null' ?>,
    regency:  <?= $store ? (int) $store['regency_id']  : 'null' ?>,
    district: <?= $store ? (int) $store['district_id'] : 'null' ?>
  };
</script>
<script src="<?= base_url('assets/js/region-select.js') ?>"></script>
