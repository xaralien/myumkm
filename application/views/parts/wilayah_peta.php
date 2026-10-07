<?php
/* =============================================================================
   Pemilih wilayah + peta, dipakai bersama checkout dan buku alamat.

   Id elemennya (prov / reg / dis / petaAlamat) sudah menjadi kontrak dengan
   region-select.js dan peta-alamat.js. Markup ini ditaruh di satu berkas
   supaya kedua halaman tidak pelan-pelan jadi berbeda.

   Diperlukan: $provinsi. Opsional: $province_id, $regency_id, $district_id,
               $latitude, $longitude, $nama_alamat (nama field alamat).
   ========================================================================== */
$nama_alamat = $nama_alamat ?? 'address';
?>

<div class="mb-3">
  <span class="form-label">Wilayah tujuan *</span>
  <div class="row">
    <div class="col-md-4 mb-2">
      <select id="prov" name="prov_awal" class="form-control" aria-label="Provinsi" required>
        <option value="">Provinsi</option>
        <?php foreach ($provinsi as $pr): ?>
          <option value="<?= (int) $pr['id'] ?>" <?= (int) ($province_id ?? 0) === (int) $pr['id'] ? 'selected' : '' ?>>
            <?= html_escape($pr['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4 mb-2">
      <select id="reg" name="reg_awal" class="form-control" aria-label="Kabupaten atau kota" required disabled>
        <option value="">Kabupaten / Kota</option>
      </select>
    </div>
    <div class="col-md-4 mb-2">
      <select id="dis" name="district_id" class="form-control" aria-label="Kecamatan" required disabled>
        <option value="">Kecamatan</option>
      </select>
    </div>
  </div>
</div>

<input type="hidden" id="lat" name="latitude" value="<?= html_escape($latitude ?? '') ?>">
<input type="hidden" id="lng" name="longitude" value="<?= html_escape($longitude ?? '') ?>">

<div class="peta-blok">
  <p class="hint mb-2">
    Tidak yakin kecamatannya? Klik titik tujuan di peta &mdash; alamat dan
    wilayah terisi sendiri.
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

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
  /* Nama dan bentuknya harus sama persis dengan yang dipakai checkout -
     region-select.js membacanya dari dua variabel global ini. */
  window.REGION_URLS = {
    regencies: '<?= site_url('region/regencies') ?>',
    districts: '<?= site_url('region/districts') ?>'
  };
  window.PILIHAN_AWAL = <?= json_encode(array(
    'province' => (int) ($province_id ?? 0),
    'regency'  => (int) ($regency_id ?? 0),
    'district' => (int) ($district_id ?? 0),
  )) ?>;
  window.PETA_ALAMAT = {
    peta: 'petaAlamat',
    alamat: <?= json_encode($nama_alamat) ?>,
    info: 'petaInfo',
    cari: 'btnCariAlamat',
    lokasiSaya: 'btnLokasiSaya',
    urlCocok: '<?= site_url('region/cocok') ?>'
  };
</script>
<script src="<?= aset('assets/js/region-select.js') ?>"></script>
<script src="<?= aset('assets/js/peta-alamat.js') ?>"></script>
