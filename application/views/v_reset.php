<!-- application/views/v_reset.php -->
<div class="hero hero-page">
  <div class="container"><div class="row"><div class="col-lg-12">
    <div class="intro-excerpt text-center"><h1>Sandi baru</h1></div>
  </div></div></div>
</div>

<div class="untree_co-section before-footer-section">
  <div class="container"><div class="row justify-content-center"><div class="col-md-7 col-lg-5">

    <div class="form-card">
      <p class="hint mb-4">
        Pasang sandi baru untuk akunmu. Minimal 8 karakter.
      </p>

      <!-- Alamat tujuan ikut membawa tokennya. Tanpa itu, POST-nya mendarat
           di auth/reset tanpa token dan ditolak - padahal sandinya sudah
           diketik. -->
      <?= form_open('auth/reset/' . $token) ?>

        <div class="mb-3">
          <label class="form-label" for="password">Sandi baru</label>
          <input type="password" id="password" name="password" class="form-control"
                 autocomplete="new-password" minlength="8" required autofocus>
          <?= form_error('password') ?>
        </div>

        <div class="mb-4">
          <label class="form-label" for="password2">Ulangi sandi baru</label>
          <input type="password" id="password2" name="password2" class="form-control"
                 autocomplete="new-password" minlength="8" required>
          <?= form_error('password2') ?>
        </div>

        <button type="submit" class="btn btn-primary w-100">Simpan sandi</button>
      <?= form_close() ?>

      <p class="akun-alih">
        <a href="<?= site_url('auth/login') ?>">Batal, kembali ke halaman masuk</a>
      </p>
    </div>

  </div></div></div>
</div>
