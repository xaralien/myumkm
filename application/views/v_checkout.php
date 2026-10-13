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
  $tanggal_post = (array) $this->input->post('tanggal_kirim');
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

          <div class="peta-blok">
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
          </div>

          <div class="mt-3">
            <label class="form-label" for="recipient_notes">Catatan untuk penjual <span class="hint">(opsional)</span></label>
            <input type="text" id="recipient_notes" name="recipient_notes" class="form-control" maxlength="255"
                   placeholder="Misalnya warna, ukuran, atau patokan rumah"
                   value="<?= set_value('recipient_notes') ?>">
          </div>
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
        <div class="cart-summary is-sticky" id="ringkasan">
          <h3 class="mb-3">Ringkasan pesanan</h3>
          <?php if (! empty($langsung)): ?>
            <p class="hint mb-3">
              Pembelian langsung &mdash; isi keranjangmu tidak berubah.
            </p>
          <?php endif; ?>
          <?php if (count($rincian['kelompok']) > 1): ?>
            <p class="hint mb-3">
              Barangmu dari <?= count($rincian['kelompok']) ?> toko. Tiap toko mengirim sendiri-sendiri,
              tapi kamu cukup membayar <strong>sekali</strong>.
            </p>
          <?php endif; ?>

          <?php foreach ($rincian['kelompok'] as $sid => $k): $t = $k['toko']; ?>
            <div class="ck-toko" data-toko="<?= (int) $sid ?>">
              <p class="ringkasan-toko">
                <?= html_escape($t['name']) ?>
                <em>Dikirim dari <?= html_escape($t['district_name']) ?>, <?= html_escape($t['regency_name']) ?></em>
              </p>

              <?php foreach ($k['items'] as $it): ?>
                <div class="summary-item">
                  <span>
                    <?= html_escape($it['product_name']) ?>
                    <?php if ($it['is_preorder']): ?><span class="lencana lencana-emas">Pre-order</span><?php endif; ?>
                    <?php if ($it['variant_name']): ?><em><?= html_escape($it['variant_name']) ?></em><?php endif; ?>
                    <?php if (! empty($it['addons'])): ?>
                      <em><?= html_escape(implode(', ', array_column($it['addons'], 'name'))) ?></em>
                    <?php endif; ?>
                    <em><?= (int) $it['qty'] ?> &times; <?= rupiah($it['unit_price']) ?>
                      <?php if ($it['harga_catatan']): ?>
                        <span class="lencana lencana-hijau"><?= html_escape($it['harga_catatan']) ?></span>
                        <s class="hint"><?= rupiah($it['harga_normal']) ?></s>
                      <?php endif; ?>
                    </em>
                  </span>
                  <strong><?= rupiah($it['line_total']) ?></strong>
                </div>
              <?php endforeach; ?>

              <?php if ($k['preorder']['ada']): ?>
                <div class="ck-po">
                  <label class="form-label" for="tgl_<?= (int) $sid ?>">Tanggal kirim pre-order *</label>
                  <input type="date" class="form-control" id="tgl_<?= (int) $sid ?>" data-tanggal
                         name="tanggal_kirim[<?= (int) $sid ?>]" min="<?= $k['tanggal_min'] ?>"
                         max="<?= date('Y-m-d', strtotime('+' . Rincian_lib::PO_MAKS_HARI . ' days')) ?>"
                         value="<?= html_escape(isset($tanggal_post[$sid]) ? $tanggal_post[$sid] : $k['tanggal_min']) ?>" required>
                  <p class="hint mb-0">
                    Dibuat setelah dipesan &mdash; paling cepat <?= tgl_id($k['tanggal_min']) ?>.
                    <?php if ($k['dp_persen'] > 0): ?>
                      Bayar uang muka <strong><?= (int) $k['dp_persen'] ?>%</strong> sekarang, sisanya sebelum dikirim.
                    <?php endif; ?>
                  </p>
                </div>
              <?php else: ?>
                <p class="ringkasan-proses">
                  Diproses dalam <strong><?= max(1, (int) $t['waktu_proses_hari']) ?> hari kerja</strong> setelah pembayaran diterima.
                </p>
              <?php endif; ?>

              <div class="summary-row"><span>Subtotal</span><strong><?= rupiah($k['subtotal']) ?></strong></div>
              <div class="summary-row"><span>Ongkos kirim</span><strong data-k="ongkir">Pilih wilayah dulu</strong></div>
              <div class="summary-row is-potongan" data-baris="diskon_toko" hidden><span>Voucher toko</span><strong data-k="diskon_toko"></strong></div>
              <div class="summary-row is-potongan" data-baris="diskon_platform" hidden><span>Voucher platform</span><strong data-k="diskon_platform"></strong></div>
              <div class="summary-row is-potongan" data-baris="subsidi_ongkir" hidden><span>Subsidi ongkir</span><strong data-k="subsidi_ongkir"></strong></div>
              <p class="hint ck-pesan" data-k="pesan" aria-live="polite"></p>

              <?php if ((int) $t['gratis_ongkir_min'] > 0 && $k['subtotal'] < (int) $t['gratis_ongkir_min']): ?>
                <p class="hint">Belanja <?= rupiah((int) $t['gratis_ongkir_min'] - $k['subtotal']) ?> lagi di toko ini untuk gratis ongkir.</p>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>

          <!-- ---------- VOUCHER ---------- -->
          <div class="ck-voucher">
            <label class="form-label" for="kodeVoucher">Punya kode voucher?</label>
            <div class="fitur-baris-form">
              <input type="text" id="kodeVoucher" class="form-control" maxlength="30" autocomplete="off"
                     placeholder="Contoh: HEMAT10" style="text-transform:uppercase">
              <button type="button" class="btn btn-black-hover-outline" id="btnVoucher">Pakai</button>
            </div>
            <ul class="ck-voucher-daftar" id="daftarVoucher">
              <?php foreach ($kode_voucher as $kd): $v = isset($rincian['voucher'][$kd]) ? $rincian['voucher'][$kd] : NULL; ?>
                <li class="<?= $v && $v['ok'] ? 'is-ok' : 'is-bad' ?>">
                  <span><strong><?= html_escape($kd) ?></strong>
                    <em><?= $v ? html_escape($v['ok'] ? $v['ket'] . ($v['toko'] ? ' · ' . $v['toko'] : '') : $v['pesan']) : '' ?></em></span>
                  <button type="button" data-lepas="<?= html_escape($kd) ?>" aria-label="Lepas voucher">&times;</button>
                </li>
              <?php endforeach; ?>
            </ul>
            <p class="hint mb-0" id="pesanVoucher" aria-live="polite"></p>
          </div>

          <div class="summary-row mt-3"><span>Subtotal</span><strong id="rowSubtotal"><?= rupiah($rincian['subtotal']) ?></strong></div>
          <div class="summary-row"><span>Ongkos kirim</span><strong id="rowOngkir">Pilih wilayah dulu</strong></div>
          <div class="summary-row is-potongan" id="barisPotongan" hidden><span>Total potongan</span><strong id="rowPotongan"></strong></div>
          <div class="summary-row summary-total"><span>Total</span><strong id="rowTotal"><?= rupiah($rincian['subtotal']) ?></strong></div>
          <div class="summary-row" id="barisBayarSekarang" hidden><span>Dibayar sekarang (uang muka)</span><strong id="rowBayarSekarang"></strong></div>

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
  window.CHECKOUT = {
    hitung: '<?= site_url('checkout/hitung' . (! empty($langsung) ? '?langsung=1' : '')) ?>',
    voucher: '<?= site_url('checkout/voucher' . (! empty($langsung) ? '?langsung=1' : '')) ?>'
  };
  window.REGION_URLS = {
    regencies: '<?= site_url('region/regencies') ?>',
    districts: '<?= site_url('region/districts') ?>'
  };
  window.PILIHAN_AWAL = <?= json_encode($awal) ?>;
</script>
<script src="<?= aset('assets/js/region-select.js') ?>"></script>
<script src="<?= aset('assets/js/checkout.js') ?>"></script>

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
<script src="<?= aset('assets/js/peta-alamat.js') ?>"></script>

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
