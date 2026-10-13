<!-- application/views/v_lupa.php -->
<div class="hero hero-page">
  <div class="container"><div class="row"><div class="col-lg-12">
    <div class="intro-excerpt text-center"><h1>Lupa sandi</h1></div>
  </div></div></div>
</div>

<div class="untree_co-section before-footer-section">
  <div class="container"><div class="row justify-content-center"><div class="col-md-7 col-lg-5">

    <?php if ($this->session->flashdata('error')): ?>
      <div class="alert-box alert-error mb-4" role="alert"><?= html_escape($this->session->flashdata('error')) ?></div>
    <?php endif; ?>

    <!-- Pesan sukses ditampilkan v_navbar / header mobile untuk semua
         halaman. Diulang di sini, spanduknya muncul dua kali. -->

    <div class="form-card">
      <p class="hint mb-4">
        Masukkan email yang kamu pakai mendaftar. Kami kirimkan tautan untuk
        memasang sandi baru - tautannya berlaku satu jam.
      </p>

      <?= form_open('auth/lupa') ?>
        <div class="mb-4">
          <label class="form-label" for="email">Email</label>
          <input type="email" id="email" name="email" class="form-control" autocomplete="email"
                 value="<?= set_value('email') ?>" required autofocus>
          <?= form_error('email') ?>
        </div>
        <button type="submit" class="btn btn-primary w-100">Kirim tautan</button>
      <?= form_close() ?>

      <p class="akun-alih">
        Ingat sandinya? <a href="<?= site_url('auth/login') ?>">Masuk</a>
      </p>
    </div>

  </div></div></div>
</div>
