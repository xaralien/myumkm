<!-- application/views/v_login.php -->
<div class="hero hero-page">
  <div class="container"><div class="row"><div class="col-lg-12">
    <div class="intro-excerpt text-center"><h1>Masuk</h1></div>
  </div></div></div>
</div>

<div class="untree_co-section before-footer-section">
  <div class="container"><div class="row justify-content-center"><div class="col-lg-4">

    <?php if ($this->session->flashdata('error')): ?>
      <div class="alert-box alert-error mb-4"><?= html_escape($this->session->flashdata('error')) ?></div>
    <?php endif; ?>

    <div class="form-card">
      <p class="hint mb-4">Khusus admin dan pemilik toko. Pembeli tidak perlu akun.</p>
      <?= form_open('auth/login') ?>
        <div class="mb-3">
          <label class="form-label" for="email">Email</label>
          <input type="email" id="email" name="email" class="form-control"
                 value="<?= set_value('email') ?>" required autofocus>
          <?= form_error('email') ?>
        </div>
        <div class="mb-4">
          <label class="form-label" for="password">Password</label>
          <input type="password" id="password" name="password" class="form-control" required>
          <?= form_error('password') ?>
        </div>
        <button type="submit" class="btn btn-primary w-100">Masuk</button>
      <?= form_close() ?>
    </div>

  </div></div></div>
</div>
