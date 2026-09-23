<!-- application/views/v_location_modal.php

     Sisipkan sekali di layout (sebelum </body>), bukan di tiap halaman.
     Modal terbuka sendiri kalau pengunjung belum memilih lokasi.
-->
<?php $lokasi_terpilih = $this->location_lib->get(); ?>

<div class="locmodal<?= $lokasi_terpilih ? '' : ' is-open' ?>" id="locModal"
     role="dialog" aria-modal="true" aria-labelledby="locTitle">
  <div class="locmodal-scrim" <?= $lokasi_terpilih ? 'data-close' : '' ?>></div>

  <div class="locmodal-box">
    <?php if ($lokasi_terpilih): ?>
      <button type="button" class="locmodal-x" data-close aria-label="Tutup">&times;</button>
    <?php endif; ?>

    <h2 class="locmodal-title" id="locTitle">Kirim pesanan ke mana?</h2>
    <p class="locmodal-sub">
      Kami tampilkan toko yang bisa mengantar ke daerahmu, dari yang terdekat.
    </p>

    <!-- Pencarian cepat: mengetik "Batam" langsung memunculkan kecamatannya,
         jauh lebih cepat daripada menelusuri tiga dropdown berurutan. -->
    <div class="locmodal-search">
      <input type="search" id="locSearch" class="form-control" autocomplete="off"
             placeholder="Ketik nama kecamatan atau kota..." aria-label="Cari kecamatan">
      <ul class="locmodal-hasil" id="locHasil" hidden></ul>
    </div>

    <p class="locmodal-atau"><span>atau pilih bertingkat</span></p>

    <div class="locmodal-field">
      <label class="form-label" for="locProv">Provinsi</label>
      <select id="locProv" class="form-control">
        <option value="">Memuat...</option>
      </select>
    </div>

    <div class="locmodal-field">
      <label class="form-label" for="locReg">Kabupaten / Kota</label>
      <select id="locReg" class="form-control" disabled>
        <option value="">Pilih provinsi dulu</option>
      </select>
    </div>

    <div class="locmodal-field">
      <label class="form-label" for="locDis">Kecamatan</label>
      <select id="locDis" class="form-control" disabled>
        <option value="">Pilih kabupaten/kota dulu</option>
      </select>
    </div>

    <p class="locmodal-note" id="locNote"></p>

    <button type="button" class="btn btn-primary w-100 mt-2" id="locSimpan" disabled>
      Simpan lokasi
    </button>
  </div>
</div>

<script>
  window.REGION_URLS = {
    provinces: '<?= site_url('region/provinces') ?>',
    regencies: '<?= site_url('region/regencies') ?>',
    districts: '<?= site_url('region/districts') ?>',
    search:    '<?= site_url('region/search') ?>',
    set:       '<?= site_url('location/set') ?>'
  };
  window.CSRF = window.CSRF || {
    name: '<?= $this->security->get_csrf_token_name() ?>',
    hash: '<?= $this->security->get_csrf_hash() ?>'
  };
</script>
<script src="<?= base_url('assets/js/location.js') ?>"></script>
