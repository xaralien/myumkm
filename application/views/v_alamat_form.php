<!-- application/views/v_alamat_form.php -->
<div class="untree_co-section">
  <div class="container">
    <h1 class="nt-judul"><?= $alamat ? 'Ubah alamat' : 'Tambah alamat' ?></h1>

    <?= form_open($alamat ? 'akun/alamat_simpan/' . (int) $alamat['id'] : 'akun/alamat_simpan',
                  array('class' => 'alm-form')) ?>

      <input type="hidden" name="balik" value="<?= html_escape($this->input->get('balik')) ?>">

      <div class="form-card">
        <div class="mb-3">
          <label class="form-label" for="label">Nama alamat *</label>
          <input type="text" id="label" name="label" class="form-control" maxlength="40" required
                 placeholder="Rumah, Kantor, Rumah Ibu..."
                 value="<?= set_value('label', $alamat['label'] ?? '') ?>">
          <p class="hint">
            Nama ini cuma untukmu &mdash; supaya mudah dikenali saat memilih di checkout.
          </p>
          <?= form_error('label') ?>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label" for="recipient_name">Nama penerima *</label>
            <input type="text" id="recipient_name" name="recipient_name" class="form-control"
                   maxlength="100" required
                   value="<?= set_value('recipient_name', $alamat['recipient_name'] ?? '') ?>">
            <!-- Penerima sering bukan pemesan: kurir menghubungi orang di
                 tujuan, bukan yang membayar. -->
            <p class="hint">Orang yang dihubungi kurir di tujuan.</p>
            <?= form_error('recipient_name') ?>
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label" for="recipient_phone">Nomor HP penerima *</label>
            <input type="tel" id="recipient_phone" name="recipient_phone" class="form-control"
                   maxlength="25" required inputmode="tel"
                   value="<?= set_value('recipient_phone', $alamat['recipient_phone'] ?? '') ?>">
            <?= form_error('recipient_phone') ?>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label" for="address">Alamat lengkap *</label>
          <textarea id="address" name="address" class="form-control" rows="3" maxlength="500" required
                    placeholder="Jalan, nomor rumah, RT/RW"><?= set_value('address', $alamat['address'] ?? '') ?></textarea>
          <?= form_error('address') ?>
        </div>

        <div class="row">
          <div class="col-md-8 mb-3">
            <label class="form-label" for="landmark">Patokan</label>
            <input type="text" id="landmark" name="landmark" class="form-control" maxlength="160"
                   placeholder="Depan masjid, sebelah warung biru..."
                   value="<?= set_value('landmark', $alamat['landmark'] ?? '') ?>">
            <p class="hint">Sering lebih menolong kurir daripada alamat formalnya.</p>
          </div>

          <div class="col-md-4 mb-3">
            <label class="form-label" for="postcode">Kode pos</label>
            <input type="text" id="postcode" name="postcode" class="form-control" maxlength="10"
                   inputmode="numeric"
                   value="<?= set_value('postcode', $alamat['postcode'] ?? '') ?>">
          </div>
        </div>

        <!-- Wilayah + peta: komponen yang sama dengan checkout dan profil
             toko, jadi perilakunya seragam di seluruh aplikasi. -->
        <?php $this->load->view('parts/wilayah_peta', array(
            'provinsi'    => $provinsi,
            'province_id' => $alamat['province_id'] ?? NULL,
            'regency_id'  => $alamat['regency_id'] ?? NULL,
            'district_id' => $alamat['district_id'] ?? NULL,
            'latitude'    => $alamat['latitude'] ?? NULL,
            'longitude'   => $alamat['longitude'] ?? NULL,
        )); ?>
        <?= form_error('district_id') ?>

        <?php if (! $alamat || ! $alamat['is_primary']): ?>
          <div class="form-check mb-3">
            <input type="checkbox" class="form-check-input" id="jadikan_utama" name="jadikan_utama" value="1">
            <label class="form-check-label" for="jadikan_utama">Jadikan alamat utama</label>
          </div>
        <?php endif; ?>

        <div class="alm-form-aksi">
          <button type="submit" class="btn btn-primary">Simpan alamat</button>
          <a href="<?= site_url('akun/alamat') ?>" class="btn btn-black-hover-outline">Batal</a>
        </div>
      </div>
    <?= form_close() ?>
  </div>
</div>
