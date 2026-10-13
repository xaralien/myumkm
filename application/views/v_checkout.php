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
  <div class="container"><div class="row"><div class="col-lg-12">
    <div class="intro-excerpt text-center"><h1>Checkout</h1></div>
  </div></div></div>
</div>

<div class="untree_co-section before-footer-section">
  <div class="container">

    <?php if ($this->session->flashdata('error')): ?>
      <div class="alert-box alert-error mb-4" role="alert"><?= html_escape($this->session->flashdata('error')) ?></div>
    <?php endif; ?>

    <?= form_open('checkout/place', array('id' => 'formCheckout')) ?>
    <?php if (! empty($langsung)): ?>
      <!-- Penanda "beli sekarang" ikut terkirim, supaya pesanan dibuat dari
           wadah berisi satu barang itu - bukan dari keranjang biasa. -->
      <input type="hidden" name="langsung" value="1">
    <?php endif; ?>
    <div class="row">

      <div class="col-lg-7 mb-5 mb-lg-0">

        <!-- ============ 1. ALAMAT PENGIRIMAN ============ -->
        <div class="form-card">
          <h3 class="form-card-title">Alamat pengiriman</h3>

          <?php if (! $alamat_saya && $this->auth_lib->id()): ?>
            <!-- Belum punya alamat tersimpan. Petunjuknya ditaruh di sini,
                 bukan hanya di menu Akun: pembeli yang sedang checkout tidak
                 akan pergi mencari halaman pengaturan, jadi fitur buku alamat
                 tidak pernah ditemukan kalau tidak disebut di tempat ini. -->
            <p class="alm-ajak">
              Isi alamat di bawah, lalu centang <strong>&ldquo;Simpan alamat ini&rdquo;</strong>
              supaya belanja berikutnya tinggal pilih.
            </p>
          <?php endif; ?>

          <?php if ($alamat_saya): ?>
            <!-- Alamat tersimpan. Satu ketukan menggantikan enam kolom isian,
                 dan mengetik ulang alamat tiap belanja adalah alasan umum
                 orang berhenti di tengah checkout. -->
            <div class="alm-pilih">
              <?php foreach ($alamat_saya as $a): ?>
                <?php $terpilih = $alamat_pilih && (int) $alamat_pilih['id'] === (int) $a['id']; ?>
                <button type="button"
                        class="alm-opsi <?= $terpilih ? 'is-aktif' : '' ?>"
                        data-alamat
                        data-nama="<?= html_escape($a['recipient_name']) ?>"
                        data-hp="<?= html_escape($a['recipient_phone']) ?>"
                        data-alamat-teks="<?= html_escape($a['address']) ?>"
                        data-prov="<?= (int) $a['province_id'] ?>"
                        data-reg="<?= (int) $a['regency_id'] ?>"
                        data-dis="<?= (int) $a['district_id'] ?>">
                  <span class="alm-opsi-label">
                    <?= html_escape($a['label']) ?>
                    <?php if ($a['is_primary']): ?><em>Utama</em><?php endif; ?>
                  </span>
                  <span class="alm-opsi-nama"><?= html_escape($a['recipient_name']) ?></span>
                  <span class="alm-opsi-isi">
                    <?= html_escape($a['address']) ?>,
                    <?= html_escape($a['district_name']) ?>, <?= html_escape($a['regency_name']) ?>
                  </span>
                </button>
              <?php endforeach; ?>

              <a class="alm-opsi is-tambah" href="<?= site_url('akun/alamat_form?balik=checkout') ?>">
                <span>+ Alamat baru</span>
              </a>
            </div>
          <?php endif; ?>

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

          <!-- Blok peta dibuang atas permintaan: tidak dipakai di alur
               sekarang. Markupnya DIHAPUS, bukan sekadar ditutupi CSS -
               peta yang tersembunyi tetap memuat Leaflet dan ubin petanya
               dari internet, jadi pembeli membayar kuota untuk sesuatu yang
               tidak pernah dilihat.

               Markup lamanya ada di riwayat git berkas ini. -->


          <div class="mt-3">
            <label class="form-label" for="recipient_notes">Catatan untuk penjual <span class="hint">(opsional)</span></label>
            <input type="text" id="recipient_notes" name="recipient_notes" class="form-control" maxlength="255"
                   placeholder="Misalnya warna, ukuran, atau patokan rumah"
                   value="<?= set_value('recipient_notes') ?>">
          </div>

          <?php if ($this->auth_lib->id()): ?>
            <!-- Hanya untuk yang sudah login: tamu tidak punya tempat
                 menyimpannya. Tidak dicentang secara bawaan - menyimpan
                 alamat orang lain (kado, kiriman ke kantor) ke buku alamat
                 sendiri justru memenuhinya dengan alamat yang tidak akan
                 dipakai lagi. -->
            <div class="alm-simpan" id="almSimpan">
              <label class="form-check">
                <input type="checkbox" class="form-check-input" id="simpan_alamat"
                       name="simpan_alamat" value="1" <?= set_checkbox('simpan_alamat', '1') ?>>
                <span>Simpan alamat ini untuk belanja berikutnya</span>
              </label>

              <div class="alm-simpan-nama" id="almSimpanNama" hidden>
                <label class="form-label" for="alamat_label">Beri nama alamat ini</label>
                <input type="text" id="alamat_label" name="alamat_label" class="form-control"
                       maxlength="40" placeholder="Rumah, Kantor, Rumah Ibu..."
                       value="<?= set_value('alamat_label') ?>">
                <p class="hint">Nama ini cuma untukmu, supaya mudah dikenali saat memilih nanti.</p>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <!-- Blok "Data pemesan" dihapus, bukan dikomentari: komentar HTML
             hanya menyembunyikan hasilnya dari browser - PHP di dalamnya
             tetap dijalankan server. Nama & HP pemesan sekarang diambil dari
             data penerima di Checkout::place(). -->

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
          <?php if (! empty($langsung)): ?>
            <p class="hint mb-3">
              Pembelian langsung &mdash; isi keranjangmu tidak berubah.
            </p>
          <?php endif; ?>

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
<script src="<?= aset('assets/js/region-select.js') ?>"></script>
<script src="<?= aset('assets/js/checkout.js') ?>"></script>

<script>
  /* Kolom nama alamat baru muncul setelah centangnya ditekan. Ditampilkan
     sejak awal, ia jadi satu kolom lagi yang harus dilewati pembeli yang
     memang tidak ingin menyimpan apa pun. */
  (function () {
    var c = document.getElementById('simpan_alamat');
    var n = document.getElementById('almSimpanNama');
    if (!c || !n) { return; }

    function perbarui() { n.hidden = !c.checked; }

    c.addEventListener('change', perbarui);
    perbarui();   // hormati keadaan setelah validasi gagal
  })();
</script>


<script>
  /* Alamat tersimpan: menekan kartunya mengisi seluruh kolom di bawah.
     Kolomnya tetap bisa diubah setelah terisi - pembeli kadang perlu
     menambah patokan untuk kiriman tertentu tanpa mengubah alamat
     tersimpannya. */
  (function () {
    var kartu = document.querySelectorAll('[data-alamat]');
    if (!kartu.length) { return; }

    function isi(b) {
      var set = function (id, nilai) {
        var el = document.getElementById(id);
        if (el) { el.value = nilai || ''; }
      };

      set('recipient_name', b.dataset.nama);
      set('recipient_phone', b.dataset.hp);
      set('recipient_address', b.dataset.alamatTeks);

      if (window.setWilayah) {
        window.setWilayah(+b.dataset.prov, +b.dataset.reg, +b.dataset.dis);
      }

      kartu.forEach(function (x) { x.classList.toggle('is-aktif', x === b); });
    }

    kartu.forEach(function (b) {
      b.addEventListener('click', function () { isi(b); });
    });

    // Yang sudah ditandai aktif dari server diisikan saat halaman dibuka.
    var awal = document.querySelector('[data-alamat].is-aktif');
    if (awal) { isi(awal); }
  })();

  /* Mengembalikan isian ke alamat profil. Wilayah diisi lewat setWilayah()
     milik region-select.js, yang juga memicu penghitungan ongkir. */
  (function () {
    var b = document.getElementById('btnPakaiProfil');
    if (!b) { return; }

    b.addEventListener('click', function () {
      var d = b.dataset;
      document.getElementById('recipient_name').value = d.nama;
      document.getElementById('recipient_phone').value = d.hp;
      document.getElementById('recipient_address').value = d.alamat;

      if (typeof window.setWilayah === 'function' && d.prov !== '0') {
        window.setWilayah(d.prov, d.reg, d.dis);
      }
      b.textContent = 'Terpakai';
      setTimeout(function () { b.textContent = 'Pakai alamat ini'; }, 2000);
    });
  })();
</script>
