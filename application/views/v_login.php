<!-- application/views/v_login.php -->
<div class="hero hero-page">
  <div class="container"><div class="row"><div class="col-lg-12">
    <div class="intro-excerpt text-center"><h1>Masuk</h1></div>
  </div></div></div>
</div>

<div class="untree_co-section before-footer-section">
  <div class="container"><div class="row justify-content-center"><div class="col-md-7 col-lg-5">

    <?php $this->load->view('parts/auth_pesan'); ?>

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

      <a href="<?= site_url('auth/otp') . ($next ? '?next=' . rawurlencode($next) : '') ?>"
         class="btn btn-outline-secondary w-100 mt-3">Masuk pakai kode WhatsApp</a>
      <p class="akun-alih mb-0"><a href="<?= site_url('auth/lupa') ?>">Lupa kata sandi?</a></p>

      <p class="akun-alih">
        Belum punya akun?
        <a href="<?= site_url('auth/register') . ($next ? '?next=' . rawurlencode($next) : '') ?>">Daftar</a>
      </p>
    </div>

  </div></div></div>
</div>
