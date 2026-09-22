<!-- application/views/v_register.php -->
<div class="hero hero-page">
  <div class="container"><div class="row"><div class="col-lg-12">
    <div class="intro-excerpt text-center"><h1>Buat akun</h1></div>
  </div></div></div>
</div>

<div class="untree_co-section before-footer-section">
  <div class="container"><div class="row justify-content-center"><div class="col-md-8 col-lg-5">

    <?php if ($this->session->flashdata('error')): ?>
      <div class="alert-box alert-error mb-4" role="alert"><?= html_escape($this->session->flashdata('error')) ?></div>
    <?php endif; ?>

    <div class="form-card">
      <p class="hint mb-4">Satu akun untuk belanja sekaligus berjualan. Kamu bisa membuka toko kapan saja dari menu akun.</p>

      <?= form_open('auth/register') ?>
        <?php if ($next): ?><input type="hidden" name="next" value="<?= html_escape($next) ?>"><?php endif; ?>

        <!-- Jebakan bot - disembunyikan dari manusia, bot biasanya mengisinya.
             tabindex -1 & autocomplete off supaya tidak terisi tanpa sengaja. -->
        <div class="akun-jebakan" aria-hidden="true">
          <label for="website">Jangan diisi</label>
          <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
        </div>

        <div class="mb-3">
          <label class="form-label" for="name">Nama</label>
          <input type="text" id="name" name="name" class="form-control" autocomplete="name"
                 value="<?= set_value('name') ?>" required autofocus>
          <?= form_error('name') ?>
        </div>
        <div class="mb-3">
          <label class="form-label" for="email">Email</label>
          <input type="email" id="email" name="email" class="form-control" autocomplete="email"
                 value="<?= set_value('email') ?>" required>
          <?= form_error('email') ?>
        </div>
        <div class="mb-3">
          <label class="form-label" for="phone">Nomor WhatsApp</label>
          <input type="tel" id="phone" name="phone" class="form-control" autocomplete="tel"
                 placeholder="081234567890" value="<?= set_value('phone') ?>" required>
          <p class="hint">Dipakai toko untuk mengabari pesananmu.</p>
          <?= form_error('phone') ?>
        </div>
        <div class="mb-3">
          <label class="form-label" for="password">Password</label>
          <input type="password" id="password" name="password" class="form-control"
                 autocomplete="new-password" minlength="8" required>
          <p class="hint">Minimal 8 karakter.</p>
          <?= form_error('password') ?>
        </div>
        <div class="mb-4">
          <label class="form-label" for="password2">Ulangi password</label>
          <input type="password" id="password2" name="password2" class="form-control"
                 autocomplete="new-password" minlength="8" required>
          <?= form_error('password2') ?>
        </div>
        <button type="submit" class="btn btn-primary w-100">Daftar</button>
      <?= form_close() ?>

      <p class="akun-alih">
        Sudah punya akun?
        <a href="<?= site_url('auth/login') . ($next ? '?next=' . rawurlencode($next) : '') ?>">Masuk</a>
      </p>
    </div>

  </div></div></div>
</div>
