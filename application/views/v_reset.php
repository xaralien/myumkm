<!-- application/views/v_reset.php -->
<div class="hero hero-page">
  <div class="container"><div class="row"><div class="col-lg-12">
    <div class="intro-excerpt text-center"><h1>Kata sandi baru</h1></div>
  </div></div></div>
</div>

<div class="untree_co-section before-footer-section">
  <div class="container"><div class="row justify-content-center"><div class="col-md-7 col-lg-5">
    <?php $this->load->view('parts/auth_pesan'); ?>

    <div class="form-card">
      <?= form_open('auth/reset/' . $token) ?>
        <div class="mb-3">
          <label class="form-label" for="password">Kata sandi baru</label>
          <input type="password" id="password" name="password" class="form-control" minlength="8" required
                 autocomplete="new-password" autofocus>
          <?= form_error('password') ?>
        </div>
        <div class="mb-4">
          <label class="form-label" for="password2">Ulangi kata sandi</label>
          <input type="password" id="password2" name="password2" class="form-control" minlength="8" required
                 autocomplete="new-password">
          <?= form_error('password2') ?>
        </div>
        <button type="submit" class="btn btn-primary w-100 btn-besar">Simpan &amp; masuk</button>
      <?= form_close() ?>
    </div>
  </div></div></div>
</div>
