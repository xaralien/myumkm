<!-- application/views/v_lupa.php -->
<div class="hero hero-page">
  <div class="container"><div class="row"><div class="col-lg-12">
    <div class="intro-excerpt text-center"><h1>Lupa kata sandi</h1></div>
  </div></div></div>
</div>

<div class="untree_co-section before-footer-section">
  <div class="container"><div class="row justify-content-center"><div class="col-md-7 col-lg-5">
    <?php $this->load->view('parts/auth_pesan'); ?>

    <div class="form-card">
      <p class="hint mb-4">Tulis email atau nomor WhatsApp akunmu. Kami kirim tautan untuk membuat kata sandi baru.</p>
      <?= form_open('auth/lupa') ?>
        <div class="mb-4">
          <label class="form-label" for="akun">Email atau nomor WhatsApp</label>
          <input type="text" id="akun" name="akun" class="form-control form-control-lg" required autofocus
                 autocomplete="username" placeholder="contoh: 081234567890">
        </div>
        <button type="submit" class="btn btn-primary w-100 btn-besar">Kirim tautan</button>
      <?= form_close() ?>

      <p class="akun-alih">
        <a href="<?= site_url('auth/otp') ?>">Masuk pakai kode WhatsApp</a> &middot;
        <a href="<?= site_url('auth/login') ?>">Kembali ke halaman masuk</a>
      </p>
    </div>
  </div></div></div>
</div>
