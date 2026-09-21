<!-- application/views/v_checkout.php -->

<div class="hero hero-page">
  <div class="container">
    <div class="row">
      <div class="col-lg-12">
        <div class="intro-excerpt text-center">
          <h1>Pengiriman</h1>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="untree_co-section before-footer-section">
  <div class="container">

    <?php if ($this->session->flashdata('error')): ?>
      <div class="alert-box alert-error mb-4"><?= html_escape($this->session->flashdata('error')) ?></div>
    <?php endif; ?>

    <?= form_open('checkout/place', array('id' => 'formCheckout')) ?>
    <div class="row">

      <div class="col-lg-7 mb-5 mb-lg-0">

        <!-- ============ 1. PENERIMA ============
             Sengaja diletakkan paling atas, bukan data pemesan. Pada toko
             bunga penerima sering orang lain, dan ini bagian yang paling
             dipikirkan pembeli. Data pemesan diminta belakangan. -->
        <div class="form-card">
          <h3 class="form-card-title">Dikirim ke</h3>

          <div class="row">
            <div class="col-sm-6 mb-3">
              <label class="form-label" for="recipient_name">Nama penerima *</label>
              <input type="text" id="recipient_name" name="recipient_name" class="form-control"
                value="<?= set_value('recipient_name') ?>" required>
              <?= form_error('recipient_name') ?>
            </div>
            <div class="col-sm-6 mb-3">
              <label class="form-label" for="recipient_phone">HP penerima *</label>
              <input type="tel" id="recipient_phone" name="recipient_phone" class="form-control"
                placeholder="081234567890" value="<?= set_value('recipient_phone') ?>" required>
              <?= form_error('recipient_phone') ?>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label" for="recipient_address">Alamat lengkap *</label>
            <textarea id="recipient_address" name="recipient_address" class="form-control" rows="3"
              placeholder="Nama jalan, nomor rumah, RT/RW, patokan" required><?= set_value('recipient_address') ?></textarea>
            <?= form_error('recipient_address') ?>
          </div>

          <div class="row">
            <div class="col-12 mb-3">
              <span class="form-label">Wilayah tujuan *</span>
              <div class="row">
                <div class="col-sm-4 mb-2">
                  <select id="prov" class="form-control" required>
                    <option value="">Provinsi</option>
                    <?php foreach ($provinces as $pr): ?>
                      <option value="<?= (int) $pr['id'] ?>"><?= html_escape($pr['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-sm-4 mb-2">
                  <select id="reg" class="form-control" required disabled>
                    <option value="">Kabupaten / Kota</option>
                  </select>
                </div>
                <div class="col-sm-4 mb-2">
                  <!-- Kecamatan inilah yang menentukan ongkir. Nama kota
                       berupa teks terlalu rapuh: "Batam" dan "Kota Batam"
                       dianggap dua kota berbeda. -->
                  <select id="dis" name="recipient_district_id" class="form-control" required disabled>
                    <option value="">Kecamatan</option>
                  </select>
                </div>
              </div>
              <?= form_error('recipient_district_id') ?>
              <p class="hint" id="jangkauanInfo"></p>
            </div>
            <div class="col-sm-12 mb-3">
              <label class="form-label" for="recipient_notes">Catatan kurir</label>
              <input type="text" id="recipient_notes" name="recipient_notes" class="form-control"
                placeholder="Boleh dititip satpam" value="<?= set_value('recipient_notes') ?>">
            </div>
          </div>

          <label class="check-line">
            <input type="checkbox" name="surprise_mode" value="1" <?= set_checkbox('surprise_mode', '1') ?>>
            <span>Ini kejutan &mdash; jangan hubungi penerima sebelum sampai</span>
          </label>
        </div>

        <!-- ============ 2. WAKTU KIRIM ============ -->
        <div class="form-card">
          <h3 class="form-card-title">Waktu pengiriman</h3>

          <div class="row">
            <div class="col-sm-6 mb-3">
              <label class="form-label" for="delivery_date">Tanggal *</label>
              <input type="date" id="delivery_date" name="delivery_date" class="form-control"
                min="<?= $tgl_min ?>" max="<?= $tgl_max ?>"
                value="<?= set_value('delivery_date', $tgl_min) ?>" required>
              <?= form_error('delivery_date') ?>
            </div>
            <div class="col-sm-6 mb-3">
              <label class="form-label" for="delivery_time">Jam *</label>

              <!-- min/max/step hanya BANTUAN di browser, bukan penjagaan.
                   Ketiganya bisa dihapus lewat DevTools, jadi batas yang
                   sesungguhnya ada di callback_valid_time di server.

                   step="900" = kelipatan 15 menit. Nilai di bawah 60 membuat
                   sebagian browser mengirim "HH:MM:SS", yang sudah ditangani
                   rapikan_jam() di controller. -->
              <input type="time" id="delivery_time" name="delivery_time" class="form-control"
                min="<?= $jam_buka ?>" max="<?= $jam_tutup ?>" step="900"
                value="<?= set_value('delivery_time', $jam_min ?: $jam_buka) ?>" required>
              <?= form_error('delivery_time') ?>
              <p class="hint">Buka <?= $jam_buka ?>&ndash;<?= $jam_tutup ?>.</p>
            </div>
          </div>

          <p class="hint-warning">
            <?php if ($batas_hari_ini): ?>
              Untuk pengiriman hari ini, jam paling awal <?= $batas_hari_ini ?>
              &mdash; bunga dirangkai dulu (butuh <?= $jeda_menit ?> menit) sebelum diantar.
            <?php else: ?>
              Pengiriman hari ini sudah tidak memungkinkan. Sisa waktu sampai
              toko tutup (<?= $jam_tutup ?>) tidak cukup untuk merangkai dan mengantar.
            <?php endif; ?>
          </p>
        </div>

        <!-- ============ 3. KARTU UCAPAN ============ -->
        <div class="form-card">
          <h3 class="form-card-title">Kartu ucapan</h3>

          <div class="mb-3">
            <label class="form-label" for="card_message">Pesan</label>
            <textarea id="card_message" name="card_message" class="form-control" rows="3"
              maxlength="500" placeholder="Selamat ulang tahun, semoga sehat selalu."><?= set_value('card_message') ?></textarea>
            <p class="hint"><span id="msgCount">0</span>/500 karakter</p>
          </div>

          <div class="mb-3">
            <label class="form-label" for="card_from">Dari</label>
            <input type="text" id="card_from" name="card_from" class="form-control"
              value="<?= set_value('card_from') ?>">
          </div>

          <label class="check-line">
            <input type="checkbox" name="is_anonymous" value="1" id="isAnon" <?= set_checkbox('is_anonymous', '1') ?>>
            <span>Kirim tanpa nama pengirim</span>
          </label>
        </div>

        <!-- ============ 4. DATA PEMESAN ============
             Diminta paling akhir dengan sengaja. Kalau formulir dibuka
             dengan permintaan nama dan email, banyak orang mengira harus
             daftar akun lalu pergi. -->
        <div class="form-card">
          <h3 class="form-card-title">Data kamu</h3>
          <p class="hint mb-3">Untuk konfirmasi pesanan dan foto bukti serah terima. Tidak perlu daftar akun.</p>

          <div class="row">
            <div class="col-sm-6 mb-3">
              <label class="form-label" for="customer_name">Nama *</label>
              <input type="text" id="customer_name" name="customer_name" class="form-control"
                value="<?= set_value('customer_name') ?>" required>
              <?= form_error('customer_name') ?>
            </div>
            <div class="col-sm-6 mb-3">
              <label class="form-label" for="customer_phone">Nomor WhatsApp *</label>
              <input type="tel" id="customer_phone" name="customer_phone" class="form-control"
                placeholder="081234567890" value="<?= set_value('customer_phone') ?>" required>
              <?= form_error('customer_phone') ?>
            </div>
          </div>

          <div class="mb-1">
            <label class="form-label" for="customer_email">Email (opsional)</label>
            <input type="email" id="customer_email" name="customer_email" class="form-control"
              value="<?= set_value('customer_email') ?>">
            <?= form_error('customer_email') ?>
          </div>
        </div>

        <!-- ============ 5. PEMBAYARAN ============ -->
        <div class="form-card">
          <h3 class="form-card-title">Pembayaran</h3>
          <?= form_error('payment_method') ?>

          <label class="pay-option">
            <input type="radio" name="payment_method" value="duitku"
              <?= set_radio('payment_method', 'duitku', TRUE) ?>>
            <span>
              <strong>Bayar online</strong>
              <em>Metode dipilih di halaman berikutnya: transfer bank, e-wallet, QRIS, kartu kredit</em>
            </span>
          </label>

            <!-- <label class="pay-option <?= $boleh_cod ? '' : 'is-disabled' ?>">
              <input type="radio" name="payment_method" value="cod" <?= $boleh_cod ? '' : 'disabled' ?>
                <?= set_radio('payment_method', 'cod') ?>>
              <span>
                <strong>Bayar di tempat (COD)</strong>
                <em>
                  <?php if ($boleh_cod): ?>
                    Siapkan uang pas saat kurir tiba
                  <?php else: ?>
                    Tidak tersedia untuk pesanan di atas <?= rupiah($cod_max) ?>
                  <?php endif; ?>
                </em>
              </span>
            </label> -->
        </div>

      </div>

      <!-- ============ RINGKASAN ============ -->
      <div class="col-lg-5">
        <div class="cart-summary is-sticky">
          <h3 class="mb-4">Ringkasan pesanan</h3>

          <!-- Satu pesanan = satu toko, jadi jam kerja dan ongkirnya
               mengikuti toko ini - bukan pengaturan global. -->
          <p class="ringkasan-toko">
            <?= html_escape($toko['name']) ?>
            <em><?= html_escape($toko['district_name']) ?>,
              <?= html_escape($toko['regency_name']) ?>
              &middot; buka <?= $jam_buka ?>&ndash;<?= $jam_tutup ?></em>
          </p>

          <?php foreach ($items as $it): ?>
            <div class="summary-item">
              <span>
                <?= html_escape($it['product_name']) ?>
                <?php if ($it['variant_name']): ?>
                  <em><?= html_escape($it['variant_name']) ?></em>
                <?php endif; ?>
                <em><?= (int) $it['qty'] ?> &times; <?= rupiah($it['unit_price']) ?></em>
              </span>
              <strong><?= rupiah($it['line_total']) ?></strong>
            </div>
          <?php endforeach; ?>

          <div class="summary-row mt-3">
            <span>Subtotal</span>
            <strong><?= rupiah($subtotal) ?></strong>
          </div>
          <div class="summary-row">
            <span>Ongkos kirim</span>
            <strong id="rowOngkir">Pilih wilayah dulu</strong>
          </div>
          <div class="summary-row summary-total">
            <span>Total</span>
            <strong id="rowTotal"><?= rupiah($subtotal) ?></strong>
          </div>

          <?php if ($free_above > 0 && $subtotal < $free_above): ?>
            <p class="hint mt-2">
              Belanja <?= rupiah($free_above - $subtotal) ?> lagi untuk gratis ongkir.
            </p>
          <?php endif; ?>

          <button type="submit" class="btn btn-primary w-100 mt-4" id="btnPlace">
            Buat pesanan
          </button>
          <p class="hint text-center mt-2">Tanpa perlu membuat akun</p>
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
</script>
<script src="<?= base_url('assets/js/region-select.js') ?>"></script>
<script src="<?= base_url('assets/js/checkout.js') ?>"></script>