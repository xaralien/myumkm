<!-- application/views/v_track_form.php -->

<div class="hero hero-page">
  <div class="container">
    <div class="row"><div class="col-lg-12">
      <div class="intro-excerpt text-center"><h1>Lacak pesanan</h1></div>
    </div></div>
  </div>
</div>

<div class="untree_co-section before-footer-section">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-5">

        <?php if ($this->session->flashdata('error')): ?>
          <div class="alert-box alert-error mb-4"><?= html_escape($this->session->flashdata('error')) ?></div>
        <?php endif; ?>

        <div class="form-card">
          <p class="hint mb-4">Tidak perlu akun. Cukup nomor pesanan dan nomor WhatsApp
             yang kamu pakai saat memesan.</p>

          <?= form_open('track/cari') ?>
            <div class="mb-3">
              <label class="form-label" for="order_number">Nomor pesanan</label>
              <input type="text" id="order_number" name="order_number" class="form-control"
                     placeholder="BNG-20260803-K7F3Q" value="<?= set_value('order_number') ?>" required>
              <?= form_error('order_number') ?>
            </div>
            <div class="mb-4">
              <label class="form-label" for="phone">Nomor WhatsApp</label>
              <input type="tel" id="phone" name="phone" class="form-control"
                     placeholder="081234567890" value="<?= set_value('phone') ?>" required>
              <?= form_error('phone') ?>
            </div>
            <button type="submit" class="btn btn-primary w-100">Cari pesanan</button>
          <?= form_close() ?>
        </div>

      </div>
    </div>
  </div>
</div>
