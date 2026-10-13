<?php
/* =============================================================================
   Pemilih wilayah (provinsi > kota > kecamatan).

   Id elemennya (prov / reg / dis) sudah menjadi kontrak dengan
   region-select.js, jadi jangan diganti tanpa mengubah skripnya juga.

   Peta dibuang atas permintaan: tidak dipakai di alur sekarang. Berkas ini
   ikut diganti nama dari wilayah_peta.php - nama yang menyebut peta padahal
   isinya tidak ada petanya hanya menyesatkan yang membacanya nanti.

   Diperlukan: $provinsi. Opsional: $province_id, $regency_id, $district_id.
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
</script>
<script src="<?= aset('assets/js/region-select.js') ?>"></script>
