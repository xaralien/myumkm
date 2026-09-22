<!-- application/views/v_login.php -->
<div class="hero hero-page">
  <div class="container"><div class="row"><div class="col-lg-12">
    <div class="intro-excerpt text-center"><h1>Masuk</h1></div>
  </div></div></div>
</div>

<div class="untree_co-section before-footer-section">
  <div class="container"><div class="row justify-content-center"><div class="col-md-7 col-lg-5">

    <?php if ($this->session->flashdata('error')): ?>
      <div class="alert-box alert-error mb-4" role="alert"><?= html_escape($this->session->flashdata('error')) ?></div>
    <?php endif; ?>

    <div class="form-card">
      <p class="hint mb-4">Masuk untuk menyimpan riwayat belanja dan mengelola tokomu. Belanja tanpa akun tetap bisa.</p>

      <?= form_open('auth/login') ?>
        <?php if ($next): ?><input type="hidden" name="next" value="<?= html_escape($next) ?>"><?php endif; ?>

        <div class="mb-3">
          <label class="form-label" for="email">Email</label>
          <input type="email" id="email" name="email" class="form-control" autocomplete="email"
                 value="<?= set_value('email') ?>" required autofocus>
          <?= form_error('email') ?>
        </div>
        <div class="mb-4">
          <label class="form-label" for="password">Password</label>
          <input type="password" id="password" name="password" class="form-control"
                 autocomplete="current-password" required>
          <?= form_error('password') ?>
        </div>
        <button type="submit" class="btn btn-primary w-100">Masuk</button>
      <?= form_close() ?>

      <p class="akun-alih">
        Belum punya akun?
        <a href="<?= site_url('auth/register') . ($next ? '?next=' . rawurlencode($next) : '') ?>">Daftar</a>
      </p>
    </div>

  </div></div></div>
</div>
