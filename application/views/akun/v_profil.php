<!-- application/views/akun/v_profil.php -->
<?php
  $avatar = $akun['avatar']
      ? base_url('upload/avatar/' . $akun['avatar'])
      : NULL;
?>
<div class="hero hero-page">
  <div class="container"><div class="row"><div class="col-lg-12">
    <div class="intro-excerpt text-center"><h1>Profil saya</h1></div>
  </div></div></div>
</div>

<div class="untree_co-section before-footer-section">
  <div class="container"><div class="row justify-content-center"><div class="col-lg-8">

    <?php if ($this->session->flashdata('error')): ?>
      <div class="alert-box alert-error mb-4" role="alert"><?= html_escape($this->session->flashdata('error')) ?></div>
    <?php endif; ?>

    <!-- multipart: tanpa enctype, berkas foto tidak ikut terkirim sama
         sekali dan $_FILES kosong tanpa pesan galat. -->
    <?= form_open_multipart('akun/profil') ?>

    <div class="form-card">
      <h3 class="form-card-title">Data diri</h3>

      <div class="profil-avatar mb-4">
        <?php if ($avatar): ?>
          <img src="<?= $avatar ?>" alt="" class="img-avatar" id="avatarPreview">
        <?php else: ?>
          <img src="" alt="" class="img-avatar" id="avatarPreview" hidden>
          <span class="img-avatar akun-inisial" id="avatarInisial" aria-hidden="true">
            <?= html_escape(strtoupper(mb_substr($akun['name'], 0, 1))) ?>
          </span>
        <?php endif; ?>
        <div>
          <label class="form-label" for="avatar">Foto profil</label>
          <input type="file" id="avatar" name="avatar" class="form-control"
                 accept="image/jpeg,image/png,image/webp">
          <p class="hint">JPG, PNG, atau WEBP. Maksimal 1 MB.</p>
          <p class="img-info" id="avatarInfo"></p>
        </div>
      </div>

      <div class="row">
        <div class="col-sm-6 mb-3">
          <label class="form-label" for="name">Nama</label>
          <input type="text" id="name" name="name" class="form-control" autocomplete="name"
                 value="<?= set_value('name', $akun['name']) ?>" required>
          <?= form_error('name') ?>
        </div>
        <div class="col-sm-6 mb-3">
          <label class="form-label" for="phone">Nomor WhatsApp</label>
          <input type="tel" id="phone" name="phone" class="form-control" autocomplete="tel"
                 value="<?= set_value('phone', $akun['phone']) ?>" required>
          <?= form_error('phone') ?>
        </div>
      </div>

      <div class="mb-1">
        <label class="form-label">Email</label>
        <input type="email" class="form-control" value="<?= html_escape($akun['email']) ?>" disabled>
      </div>
    </div>

    <div class="form-card">
      <h3 class="form-card-title">Alamat pengiriman</h3>
      <p class="hint mb-3">Diisi otomatis saat checkout, supaya tidak perlu mengetik ulang.</p>

      <div class="mb-3">
        <label class="form-label" for="address">Alamat</label>
        <textarea id="address" name="address" class="form-control" rows="2"
                  autocomplete="street-address"><?= set_value('address', $akun['address']) ?></textarea>
      </div>

      <span class="form-label">Wilayah</span>
      <div class="row">
        <div class="col-sm-4 mb-2">
          <select id="prov" class="form-control" aria-label="Provinsi">
            <option value="">Provinsi</option>
            <?php foreach ($provinces as $pr): ?>
              <option value="<?= (int) $pr['id'] ?>" <?= (int) $akun['province_id'] === (int) $pr['id'] ? 'selected' : '' ?>>
                <?= html_escape($pr['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-sm-4 mb-2">
          <select id="reg" class="form-control" aria-label="Kabupaten atau kota" disabled>
            <option value="">Kabupaten / Kota</option>
          </select>
        </div>
        <div class="col-sm-4 mb-2">
          <select id="dis" name="district_id" class="form-control" aria-label="Kecamatan" disabled>
            <option value="">Kecamatan</option>
          </select>
        </div>
      </div>

      <div class="peta-blok">
        <p class="hint mb-2">
          Atau tandai di peta &mdash; klik titik rumahmu, lalu kolom
          <strong>Alamat</strong> dan <strong>Wilayah</strong> di atas terisi
          sendiri. Hasilnya perkiraan; kamu tetap bisa mengubahnya.
        </p>
        <div id="petaAlamat" class="peta-toko"></div>
        <div class="peta-aksi">
          <button type="button" class="btn btn-black-hover-outline btn-sm" id="btnLokasiSaya">
            Gunakan lokasi saya
          </button>
          <button type="button" class="btn btn-black-hover-outline btn-sm" id="btnCariAlamat">
            Cari dari alamat di atas
          </button>
        </div>
        <p class="peta-info" id="petaInfo" aria-live="polite"></p>
      </div>
    </div>

    <div class="form-card">
      <h3 class="form-card-title">Ganti password</h3>
      <p class="hint mb-3">Kosongkan semuanya kalau tidak ingin mengganti.</p>
      <div class="mb-3">
        <label class="form-label" for="password_lama">Password sekarang</label>
        <input type="password" id="password_lama" name="password_lama" class="form-control" autocomplete="current-password">
        <?= form_error('password_lama') ?>
      </div>
      <div class="row">
        <div class="col-sm-6 mb-3">
          <label class="form-label" for="password">Password baru</label>
          <input type="password" id="password" name="password" class="form-control" minlength="8" autocomplete="new-password">
          <?= form_error('password') ?>
        </div>
        <div class="col-sm-6 mb-1">
          <label class="form-label" for="password2">Ulangi password baru</label>
          <input type="password" id="password2" name="password2" class="form-control" minlength="8" autocomplete="new-password">
          <?= form_error('password2') ?>
        </div>
      </div>
    </div>

    <button type="submit" class="btn btn-primary">Simpan profil</button>
    <?= form_close() ?>

  </div></div></div>
</div>

<script>
  window.REGION_URLS = {
    regencies: '<?= site_url('region/regencies') ?>',
    districts: '<?= site_url('region/districts') ?>'
  };
  window.PILIHAN_AWAL = {
    province: <?= (int) $akun['province_id'] ?>,
    regency:  <?= (int) $akun['regency_id'] ?>,
    district: <?= (int) $akun['district_id'] ?>
  };
</script>
<script src="<?= base_url('assets/js/region-select.js') ?>"></script>

<!-- Leaflet dimuat hanya di halaman yang butuh peta - bukan di semua
     halaman lewat header. -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
  window.PETA_ALAMAT = {
    peta: 'petaAlamat',
    alamat: 'address',
    info: 'petaInfo',
    cari: 'btnCariAlamat',
    lokasiSaya: 'btnLokasiSaya',
    urlCocok: '<?= site_url('region/cocok') ?>'
  };
</script>
<script src="<?= base_url('assets/js/peta-alamat.js') ?>"></script>
<script src="<?= base_url('assets/js/avatar-preview.js') ?>"></script>
<script>
  /* Akun tanpa foto menampilkan inisial, dan <img> pratinjaunya disembunyikan.
     Begitu foto dipilih, tukar keduanya. Dijalankan SESUDAH avatar-preview.js:
     kalau berkasnya ditolak (format/ukuran salah), skrip itu mengosongkan
     input lebih dulu, jadi di sini files[0] sudah kosong dan tidak ada yang
     ditukar. */
  (function () {
    var input = document.getElementById('avatar');
    var img   = document.getElementById('avatarPreview');
    var huruf = document.getElementById('avatarInisial');
    if (!input || !img || !huruf) { return; }

    input.addEventListener('change', function () {
      if (input.files && input.files[0]) {
        img.hidden = false;
        huruf.hidden = true;
      }
    });
  })();
</script>
