<!-- application/views/v_checkout.php -->
<?php
/* Wilayah awal: nilai yang baru dikirim (kalau validasi gagal), kalau
     tidak ada pakai alamat akun. Tanpa urutan ini, pembeli yang formnya
     ditolak karena satu kolom salah harus memilih ulang tiga dropdown. */
$awal = array(
  'province' => (int) ($this->input->post('prov_awal') ?: $wilayah_awal['province']),
  'regency'  => (int) ($this->input->post('reg_awal')  ?: $wilayah_awal['regency']),
  'district' => (int) ($this->input->post('recipient_district_id') ?: $wilayah_awal['district']),
);
$a = ! empty($akun) ? $akun : array();
$isi = function ($kolom, $dari_akun) use ($a) {
  return set_value($kolom, isset($a[$dari_akun]) ? $a[$dari_akun] : '');
};
$proses = max(1, (int) $toko['waktu_proses_hari']);
?>

<div class="hero hero-page">
  <div class="container">
    <div class="row">
      <div class="col-lg-12">
        <div class="intro-excerpt text-center">
          <h1>Checkout</h1>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="untree_co-section before-footer-section">
  <div class="container">

    <?php if ($this->session->flashdata('error')): ?>
      <div class="alert-box alert-error mb-4" role="alert"><?= html_escape($this->session->flashdata('error')) ?></div>
    <?php endif; ?>

    <?= form_open('checkout/place', array('id' => 'formCheckout')) ?>
    <div class="row">

      <div class="col-lg-7 mb-5 mb-lg-0">

        <!-- ============ 1. ALAMAT PENGIRIMAN ============ -->
        <div class="form-card">
          <h3 class="form-card-title">Alamat pengiriman</h3>

          <?php if (! empty($akun) && ! empty($akun['address'])): ?>
            <!-- Alamat profil sudah terisi otomatis di kolom di bawah. Tombol
                 ini untuk MENGEMBALIKANNYA setelah pembeli mengetik alamat
                 lain - misalnya mengirim ke rumah orang tua, lalu berubah
                 pikiran. Tanpa ini, satu-satunya cara adalah memuat ulang
                 halaman dan kehilangan isian lain. -->
            <div class="alamat-profil">
              <div>
                <strong>Alamat di profilmu</strong>
                <em><?= html_escape($akun['address']) ?><?= $akun['district_name'] ? ', ' . html_escape($akun['district_name']) : '' ?></em>
              </div>
              <button type="button" class="btn btn-black-hover-outline btn-sm" id="btnPakaiProfil"
                data-nama="<?= html_escape($akun['name']) ?>"
                data-hp="<?= html_escape($akun['phone']) ?>"
                data-alamat="<?= html_escape($akun['address']) ?>"
                data-prov="<?= (int) $akun['province_id'] ?>"
                data-reg="<?= (int) $akun['regency_id'] ?>"
                data-dis="<?= (int) $akun['district_id'] ?>">Pakai alamat ini</button>
            </div>
          <?php endif; ?>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label" for="recipient_name">Nama penerima *</label>
              <input type="text" id="recipient_name" name="recipient_name" class="form-control"
                autocomplete="shipping name" value="<?= $isi('recipient_name', 'name') ?>" required>
              <?= form_error('recipient_name') ?>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label" for="recipient_phone">HP penerima *</label>
              <input type="tel" id="recipient_phone" name="recipient_phone" class="form-control"
                autocomplete="shipping tel" placeholder="081234567890"
                value="<?= $isi('recipient_phone', 'phone') ?>" required>
              <?= form_error('recipient_phone') ?>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label" for="recipient_address">Alamat lengkap *</label>
            <textarea id="recipient_address" name="recipient_address" class="form-control" rows="3"
              autocomplete="shipping street-address"
              placeholder="Nama jalan, nomor rumah, RT/RW, patokan"
              required><?= $isi('recipient_address', 'address') ?></textarea>
            <?= form_error('recipient_address') ?>
          </div>

          <span class="form-label">Wilayah *</span>
          <div class="row">
            <div class="col-md-4 mb-2">
              <select id="prov" name="prov_awal" class="form-control" aria-label="Provinsi" required>
                <option value="">Provinsi</option>
                <?php foreach ($provinces as $pr): ?>
                  <option value="<?= (int) $pr['id'] ?>" <?= $awal['province'] === (int) $pr['id'] ? 'selected' : '' ?>>
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
              <select id="dis" name="recipient_district_id" class="form-control" aria-label="Kecamatan" required disabled>
                <option value="">Kecamatan</option>
              </select>
            </div>
          </div>
          <?= form_error('recipient_district_id') ?>
          <p class="hint" id="jangkauanInfo" aria-live="polite"></p>

          <!-- <div class="peta-blok">
            <p class="hint mb-2">
              Tidak yakin kecamatannya? Klik titik tujuan di peta &mdash; alamat
              dan wilayah terisi sendiri, dan ongkirnya langsung dihitung ulang.
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
          </div> -->

          <div class="mt-3">
            <label class="form-label" for="recipient_notes">Catatan untuk penjual <span class="hint">(opsional)</span></label>
            <input type="text" id="recipient_notes" name="recipient_notes" class="form-control" maxlength="255"
              placeholder="Misalnya warna, ukuran, atau patokan rumah"
              value="<?= set_value('recipient_notes') ?>">
          </div>
        </div>

        <!-- ============ 2. PEMESAN ============ -->
        <!-- <div class="form-card">
          <h3 class="form-card-title">Data pemesan</h3>
          <p class="hint mb-3">Untuk mengabari status pesanan. Biasanya sama dengan penerima.</p>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label" for="customer_name">Nama *</label>
              <input type="text" id="customer_name" name="customer_name" class="form-control"
                autocomplete="name" value="<?= $isi('customer_name', 'name') ?>" required>
              <?= form_error('customer_name') ?>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label" for="customer_phone">Nomor WhatsApp *</label>
              <input type="tel" id="customer_phone" name="customer_phone" class="form-control"
                autocomplete="tel" placeholder="081234567890"
                value="<?= $isi('customer_phone', 'phone') ?>" required>
              <?= form_error('customer_phone') ?>
            </div>
          </div>
          <div class="mb-1">
            <label class="form-label" for="customer_email">Email <span class="hint">(opsional)</span></label>
            <input type="email" id="customer_email" name="customer_email" class="form-control"
              autocomplete="email" value="<?= $isi('customer_email', 'email') ?>">
            <?= form_error('customer_email') ?>
          </div>
        </div> -->

        <!-- ============ 3. PEMBAYARAN ============ -->
        <div class="form-card">
          <h3 class="form-card-title">Pembayaran</h3>
          <p class="mb-0">
            Transfer bank (virtual account), e-wallet, atau QRIS. Metodenya dipilih
            di halaman berikutnya.
          </p>
        </div>

      </div>

      <!-- ============ RINGKASAN ============ -->
      <div class="col-lg-5">
        <div class="cart-summary is-sticky">
          <h3 class="mb-4">Ringkasan pesanan</h3>

          <p class="ringkasan-toko">
            <?= html_escape($toko['name']) ?>
            <em>Dikirim dari <?= html_escape($toko['district_name']) ?>,
              <?= html_escape($toko['regency_name']) ?></em>
          </p>

          <?php foreach ($items as $it): ?>
            <div class="summary-item">
              <span>
                <?= html_escape($it['product_name']) ?>
                <?php if ($it['variant_name']): ?><em><?= html_escape($it['variant_name']) ?></em><?php endif; ?>
                <?php if (! empty($it['addons'])): ?>
                  <em><?= html_escape(implode(', ', array_column($it['addons'], 'name'))) ?></em>
                <?php endif; ?>
                <em><?= (int) $it['qty'] ?> &times; <?= rupiah($it['unit_price']) ?></em>
              </span>
              <strong><?= rupiah($it['line_total']) ?></strong>
            </div>
          <?php endforeach; ?>

          <div class="summary-row mt-3"><span>Subtotal</span><strong><?= rupiah($subtotal) ?></strong></div>
          <div class="summary-row"><span>Ongkos kirim</span><strong id="rowOngkir">Pilih wilayah dulu</strong></div>
          <div class="summary-row summary-total"><span>Total</span><strong id="rowTotal"><?= rupiah($subtotal) ?></strong></div>

          <?php if ($free_above > 0 && $subtotal < $free_above): ?>
            <p class="hint mt-2">Belanja <?= rupiah($free_above - $subtotal) ?> lagi untuk gratis ongkir.</p>
          <?php endif; ?>

          <!-- Perkiraan waktu, bukan janji tanggal. Di marketplace penjual
               memproses dulu lalu mengirim; pembeli perlu tahu kira-kira
               kapan, bukan memilih jam antar. -->
          <p class="ringkasan-proses">
            Diproses penjual dalam <strong><?= $proses ?> hari kerja</strong> setelah
            pembayaran diterima, lalu dikirim.
          </p>

          <button type="submit" class="btn btn-primary w-100 mt-3" id="btnPlace">Buat pesanan</button>
          <?php if (empty($akun)): ?>
            <p class="hint text-center mt-2">
              Tanpa perlu akun &mdash; atau <a href="<?= site_url('auth/login') ?>?next=checkout">masuk</a>
              supaya pesanan tersimpan di riwayatmu.
            </p>
          <?php endif; ?>
        </div>
      </div>

    </div>
    <?= form_close() ?>

  </div>
</div>

<script>
  window.TOKO = <?= $ongkir_json ?>;
  window.REGION_URLS = {
    regencies: '<?= site_url('region/regencies') ?>',
    districts: '<?= site_url('region/districts') ?>'
  };
  window.PILIHAN_AWAL = <?= json_encode($awal) ?>;
</script>
<script src="<?= base_url('assets/js/region-select.js') ?>"></script>
<script src="<?= base_url('assets/js/checkout.js') ?>"></script>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
  window.PETA_ALAMAT = {
    peta: 'petaAlamat',
    alamat: 'recipient_address',
    info: 'petaInfo',
    cari: 'btnCariAlamat',
    lokasiSaya: 'btnLokasiSaya',
    urlCocok: '<?= site_url('region/cocok') ?>'
  };
</script>
<script src="<?= base_url('assets/js/peta-alamat.js') ?>"></script>

<script>
  /* Mengembalikan isian ke alamat profil. Wilayah diisi lewat setWilayah()
     milik region-select.js, yang juga memicu penghitungan ongkir. */
  (function() {
    var b = document.getElementById('btnPakaiProfil');
    if (!b) {
      return;
    }

    b.addEventListener('click', function() {
      var d = b.dataset;
      document.getElementById('recipient_name').value = d.nama;
      document.getElementById('recipient_phone').value = d.hp;
      document.getElementById('recipient_address').value = d.alamat;

      if (typeof window.setWilayah === 'function' && d.prov !== '0') {
        window.setWilayah(d.prov, d.reg, d.dis);
      }
      b.textContent = 'Terpakai';
      setTimeout(function() {
        b.textContent = 'Pakai alamat ini';
      }, 2000);
    });
  })();
</script>